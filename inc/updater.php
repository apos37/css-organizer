<?php
/**
 * PluginRx Updater (drop-in)
 *
 * Identical in every PluginRx-exclusive plugin. Nothing to configure.
 * Requires this header in the main plugin file: Update URI: https://pluginrx.com/
 * Load it with: require_once __DIR__ . '/inc/updater.php';
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'PluginRx_Updater' ) ) {

    class PluginRx_Updater {

        const HOST      = 'pluginrx.com';
        const ENDPOINT  = 'https://pluginrx.com/wp-content/svn/info.php';
        const CACHE_TTL = 600;


        /**
         * The single instance of the class
         *
         * @var self|null
         */
        private static $instance = null;


        /**
         * Get the singleton instance
         *
         * @return self
         */
        public static function instance() {
            if ( null === self::$instance ) {
                self::$instance = new self();
            }
            return self::$instance;
        } // End instance()


        /**
         * Constructor
         */
        private function __construct() {
            add_filter( 'update_plugins_' . self::HOST, [ $this, 'check' ], 10, 3 );
            add_filter( 'plugins_api', [ $this, 'details' ], 20, 3 );
        } // End __construct()


        /**
         * Offer an update to WordPress core for any plugin whose Update URI is ours
         *
         * @param array|false $update
         * @param array $plugin_data
         * @param string $plugin_file
         * @return array|false
         */
        public function check( $update, $plugin_data, $plugin_file ) {
            $slug = dirname( $plugin_file );
            if ( '.' === $slug ) {
                return $update;
            }

            $remote = $this->request( $slug );
            if ( ! $remote || ! $this->is_compatible( $remote ) ) {
                return $update;
            }

            return [
                'id'           => isset( $plugin_data[ 'UpdateURI' ] ) ? $plugin_data[ 'UpdateURI' ] : '',
                'slug'         => $slug,
                'version'      => $remote->version,
                'url'          => ! empty( $remote->url ) ? $remote->url : 'https://' . self::HOST . '/plugin/' . $slug,
                'package'      => $remote->package,
                'tested'       => ! empty( $remote->tested ) ? $remote->tested : '',
                'requires'     => ! empty( $remote->requires ) ? $remote->requires : '',
                'requires_php' => ! empty( $remote->requires_php ) ? $remote->requires_php : '',
                'icons'        => ! empty( $remote->icons ) ? (array) $remote->icons : [],
                'banners'      => ! empty( $remote->banners ) ? (array) $remote->banners : [],
            ];
        } // End check()


        /**
         * Feed the "View details" modal
         *
         * @param false|object|array $res
         * @param string $action
         * @param object $args
         * @return false|object|array
         */
        public function details( $res, $action, $args ) {
            if ( 'plugin_information' !== $action || false !== $res || empty( $args->slug ) || ! $this->is_ours( $args->slug ) ) {
                return $res;
            }

            $remote = $this->request( $args->slug );
            if ( ! $remote ) {
                return $res;
            }

            $info                = new stdClass();
            $info->name          = $remote->name;
            $info->slug          = $args->slug;
            $info->version       = $remote->version;
            $info->author        = '<a href="https://' . self::HOST . '/">PluginRx</a>';
            $info->homepage      = ! empty( $remote->url ) ? $remote->url : 'https://' . self::HOST . '/plugin/' . $args->slug;
            $info->requires      = ! empty( $remote->requires ) ? $remote->requires : '';
            $info->requires_php  = ! empty( $remote->requires_php ) ? $remote->requires_php : '';
            $info->tested        = ! empty( $remote->tested ) ? $remote->tested : '';
            $info->last_updated  = ! empty( $remote->last_updated ) ? $remote->last_updated : '';
            $info->download_link = $remote->package;
            $info->sections      = ! empty( $remote->sections ) ? (array) $remote->sections : [];
            $info->banners       = ! empty( $remote->banners ) ? (array) $remote->banners : [];

            return $info;
        } // End details()


        /**
         * Fetch the latest release info for a plugin
         *
         * @param string $slug
         * @return object|false
         */
        private function request( $slug ) {
            $cache_key = 'prx_upd_' . md5( $slug );
            $force     = isset( $_GET[ 'force-check' ] ); // phpcs:ignore

            if ( ! $force ) {
                $cached = get_site_transient( $cache_key );
                if ( 'error' === $cached ) {
                    return false;
                } elseif ( is_object( $cached ) ) {
                    return $cached;
                }
            }

            $url      = add_query_arg( [ 'plugin' => $slug, 'site' => home_url() ], self::ENDPOINT );
            $response = wp_remote_get( $url, [ 'timeout' => 10, 'headers' => [ 'Accept' => 'application/json' ] ] );

            if ( is_wp_error( $response ) ) {
                $this->log( $slug . ': ' . $response->get_error_message() );
            } elseif ( 200 !== wp_remote_retrieve_response_code( $response ) ) {
                $this->log( $slug . ': HTTP ' . wp_remote_retrieve_response_code( $response ) );
            } else {
                $data = json_decode( wp_remote_retrieve_body( $response ) );
                if ( is_object( $data ) && ! empty( $data->version ) && ! empty( $data->package ) ) {
                    set_site_transient( $cache_key, $data, self::CACHE_TTL );
                    return $data;
                }
                $this->log( $slug . ': invalid response' );
            }

            set_site_transient( $cache_key, 'error', 5 * MINUTE_IN_SECONDS );
            return false;
        } // End request()


        /**
         * Check the release against this site's WordPress and PHP versions
         *
         * @param object $remote
         * @return bool
         */
        private function is_compatible( $remote ) {
            $requires     = ! empty( $remote->requires ) ? $remote->requires : '0';
            $requires_php = ! empty( $remote->requires_php ) ? $remote->requires_php : '0';
            return version_compare( get_bloginfo( 'version' ), $requires, '>=' ) && version_compare( PHP_VERSION, $requires_php, '>=' );
        } // End is_compatible()


        /**
         * Check that an installed plugin folder belongs to us
         *
         * @param string $slug
         * @return bool
         */
        private function is_ours( $slug ) {
            if ( ! function_exists( 'get_plugins' ) ) {
                require_once ABSPATH . 'wp-admin/includes/plugin.php';
            }

            foreach ( get_plugins() as $file => $data ) {
                if ( dirname( $file ) === $slug && ! empty( $data[ 'UpdateURI' ] ) && wp_parse_url( $data[ 'UpdateURI' ], PHP_URL_HOST ) === self::HOST ) {
                    return true;
                }
            }
            return false;
        } // End is_ours()


        /**
         * Log when WP_DEBUG is on
         *
         * @param string $message
         * @return void
         */
        private function log( $message ) {
            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                error_log( 'PluginRx Updater: ' . $message ); // phpcs:ignore
            }
        } // End log()

    }

}

PluginRx_Updater::instance();