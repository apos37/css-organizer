<?php 
/**
 * WordPress Customizer
 * 
 * REMINDER: If you see an error at the top of the customizer saying "Looks like something's gone wrong. Wait a couple seconds, and then try again." It is likely because you used a keyword in one of the comments and your host firewall thinks it's a SQL injection. Avoid using: select, union, insert, delete, drop, where, exec
 */

namespace PluginRx\CssOrganizer;

if ( ! defined( 'ABSPATH' ) ) exit;

class Customizer {


    /**
	 * Constructor
	 */
	public function __construct() {
        if ( wp_is_block_theme() && ! get_option( Bootstrap::textdomain() . '-force-wp-customizer' ) ) {
            add_action( 'init', [ $this, 'register_block_settings' ] );
            add_action( 'enqueue_block_editor_assets', [ $this, 'enqueue_block_editor_scripts' ] );
            add_action( 'enqueue_block_assets', [ $this, 'enqueue_custom_sections_css' ] );
        } else {
            add_action( 'customize_register', [ $this, 'register_legacy_customizer_settings' ], 100 );
            add_action( 'wp_head', [ $this, 'customize_css' ], 999999 );
            add_action( 'customize_preview_init', [ $this, 'preview_js' ] );
            add_action( 'customize_controls_init', [ $this, 'controls_js' ] );
            add_action( 'customize_controls_enqueue_scripts', [ $this, 'customizer_css' ] );
        }

        add_action( 'update_option', [ $this, 'clean_post_cache' ], 10, 3 );
	} // End __construct()


    /**
     * Register settings for the Block Editor REST API
     */
    public function register_block_settings() {
        $text_domain = Bootstrap::textdomain();

        foreach ( self::get_sections() as $section ) {
            $setting_id = $text_domain . '-css-' . $section[ 'key' ];

            register_setting( 'site', $setting_id, [
                'type'              => 'string',
                'sanitize_callback' => [ $this, 'sanitize_css_field' ],
                'show_in_rest'      => [
                    'name'   => $setting_id,
                    'schema' => [
                        'title' => $section[ 'label' ],
                        'type'  => 'string',
                    ],
                ],
                'default'           => '',
            ] );
        }
    } // End register_block_settings()


    /**
     * Sanitize the CSS field input
     *
     * @param string $value The CSS input
     * @return string The sanitized CSS
     */
    public function sanitize_css_field( $value ) {
        return wp_unslash( $value );
    } // End sanitize_css_field()


    /**
     * Enqueue Block Editor scripts
     */
    public function enqueue_block_editor_scripts() {
        $text_domain = Bootstrap::textdomain();

        wp_enqueue_script(
            'css-organizer-block-editor',
            Bootstrap::url( 'inc/js/site-editor.js' ),
            [ 
                'wp-plugins',
                'wp-edit-site',
                'wp-editor',
                'wp-components', 
                'wp-data', 
                'wp-core-data', 
                'wp-i18n',
                'wp-element' 
            ],
            Bootstrap::script_version(),
            true
        );

        // Inside your script-enqueuing method
        $sections = self::get_sections();
        foreach ( $sections as &$section ) {
            $option_key = $text_domain . '-css-' . $section[ 'key' ];
            $section[ 'value' ] = get_option( $option_key, '' );
        }

        wp_localize_script( 'css-organizer-block-editor', 'css_organizer_data', [
            'sections'     => $sections,
            'text_domain'  => $text_domain,
            'custom_label' => get_option( $text_domain . '-label', __( 'CSS Organizer', 'css-organizer' ) ),
        ] );

        if ( get_option( $text_domain . '-hide-addt-css' ) == 1 ) {
            $hide_css = "
                .wp-sidebar__panel-tab-content button[aria-label='Additional CSS'],
                .wp-sidebar__panel-tab-content [data-path='customCSS'] {
                    display: none !important;
                }
            ";
            wp_add_inline_style( 'wp-components', $hide_css );
        }
    } // End enqueue_block_editor_scripts()


    /**
     * Enqueue the custom CSS for the frontend based on the sections and their content
     */
    public function enqueue_custom_sections_css() {
        if ( is_admin() ) {
            return;
        }

        $text_domain = Bootstrap::textdomain();
        $combined_css = '';

        foreach ( self::get_sections() as $section ) {
            $unique_id = $text_domain . '-css-' . $section[ 'key' ];
            $css_content = get_option( $unique_id, '' );

            if ( ! empty( $css_content ) ) {
                $combined_css .= "/* " . esc_html( $section[ 'label' ] ) . " */\n";
                $combined_css .= wp_unslash( $css_content ) . "\n\n";
            }
        }

        if ( ! empty( $combined_css ) ) {
            wp_register_style( 'css-organizer-frontend', false, [], Bootstrap::script_version() );
            wp_enqueue_style( 'css-organizer-frontend' );
            wp_add_inline_style( 'css-organizer-frontend', $combined_css );
        }
    } // End enqueue_custom_sections_css()


    /**
     * Get the sections
     *
     * @return array
     */
    public static function get_sections() {
        $sections = get_option( Bootstrap::textdomain() . '-sections', '' );
        if ( ! $sections ) {
            $sections = Settings::default_sections();
        }
        return $sections;
    } // End get_sections()


    /**
     * Get the section keys
     *
     * @return array
     */
    public function get_section_keys() {
        $keys = [];
        foreach ( self::get_sections() as $section ) {
            $keys[] = $section[ 'key' ];
        }
        return $keys;
    } // End get_section_keys()


    /**
     * Get the section labels
     *
     * @return array
     */
    public function get_section_labels() {
        $labels = [];
        foreach ( self::get_sections() as $section ) {
            $labels[] = $section[ 'label' ];
        }
        return $labels;
    } // End get_section_labels()

    
    /**
     * Add our own Theme Customizer settings, sections, and controls
     *
     * @return void
     */
    public function register_legacy_customizer_settings( $wp_customize ) {
        $text_domain = Bootstrap::textdomain();
        $panel_id = $text_domain . '-custom-css';

        // Custom label
        $custom_label = get_option( $text_domain . '-label' );
        if ( ! $custom_label ) {
            $custom_label = __( 'CSS Organizer', 'css-organizer' );
        }

        // Add our own panel
        $wp_customize->add_panel( $panel_id,
            [
                'panel'         => $panel_id,
                'title'         => $custom_label,
                'description'   => __( 'Our own neatly organized css.', 'css-organizer' ),
                'priority'      => 999,
            ] 
        );

        // Count
        $priority = 1;

        // First Row: Expansion Buttons + Body Dropdown
        $expand_btns = apply_filters( 'css_organizer_expand_btns', [ 18, 30, 50, 80 ] );
        $section_desc = '<div class="css-organizer-expand-btns">';
            foreach ( $expand_btns as $percent ) {
                $disabled = $percent == 18 ? ' disabled' : '';
                $section_desc .= '<button class="button button-secondary css-organizer-expand-btn" data-width="' . $percent . '"' . $disabled . '>' . $percent . '%</button> ';
            }
            $section_desc .= '<button class="button button-secondary css-organizer-right-float-btn css-organizer-body-tags-dropdown" title="Body Classes">&lt;body&gt;</button>';
        $section_desc .= '</div>';

        // Second Row: Media Queries + Local Vars
        $section_desc .= '<div class="css-organizer-mq-row">';
            $mobile_sizes = get_option( $text_domain . '-mobile-screen-sizes', '480px, 768px, 1024px, 1280px' );
            $mobile_sizes = array_map( 'trim', explode( ',', $mobile_sizes ) );

            foreach ( $mobile_sizes as $width ) {
                $width_num = intval( $width );
                $section_desc .= '<button class="button button-secondary css-organizer-mq-btn" data-mq="' . esc_attr( $width ) . '" title="Media Query ' . esc_attr( $width ) . '">' . esc_html( $width_num ) . '</button> ';
            }

            $section_desc .= '<button class="button button-secondary css-organizer-right-float-btn css-organizer-local-vars-btn" title="Variable Picker">--x</button>';
        $section_desc .= '</div>';

        // Iter the sections
        foreach ( self::get_sections() as $order => $section ) {

            $section_id = $text_domain . '-css-' . $section[ 'key' ];
            $control_id = $section_id . '-control';

            // Add them
            $wp_customize->add_section( $section_id,
                [
                    'panel'         => $panel_id,
                    'title'         => $section[ 'label' ],
                    'description'   => $section_desc,
                    'capability'    => 'edit_css',
                    'priority'      => $priority,
                ] 
            );
            
            // Add the css settings to each section
            $wp_customize->add_setting( $section_id,
                [
                    'default'    => '',
                    'type'       => 'option',
                    'capability' => 'edit_css',
                    'transport'  => 'postMessage',
                ]
            );
            
            // First make sure the customer control class exists
            if ( class_exists( '\WP_Customize_Code_Editor_Control' ) ) {

                // Connect the setting to the section
                $wp_customize->add_control( new \WP_Customize_Code_Editor_Control( $wp_customize, $control_id,
                    [
                        'code_type'  => 'text/css',
                        'settings'   => $section_id,
                        'section'    => $section_id,
                        'priority'   => $priority,
                    ]
                ) );

            } else {
                
                // Connect the setting to the section
                $wp_customize->add_control( $control_id,
                    [
                        'type'       => 'textarea',
                        'settings'   => $section_id,
                        'section'    => $section_id,
                        'priority'   => $priority,
                    ]
                );
            }

            // Increase count
            $priority++;
        }
        
        // Remove default additional css panel
        if ( get_option( $text_domain . '-hide-addt-css' ) == 1 ) {
            $wp_customize->remove_section( 'custom_css' );
        }
    } // End register_legacy_customizer_settings()


    /**
     * Fetch the settings and output any necessary CSS into our header
     *
     * @return void
     */
    public function customize_css() {
        $text_domain = Bootstrap::textdomain();

        foreach ( self::get_sections() as $order => $section ) {
            $unique_id = $text_domain . '-css-' . $section[ 'key' ];
            $css_content = get_option( $unique_id, '' );

            // Add it if not blank
            if ( ! empty( $css_content ) || is_customize_preview() ) {
                // We use the section label to create a "Virtual Filename"
                $virtual_name = str_replace( ' ', '-', strtolower( $section[ 'label' ] ) ) . '.css';
                
                echo '<style id="' . esc_attr( $text_domain . '-' . $section[ 'key' ] ) . '">';
                echo wp_kses_post( wp_unslash( $css_content ) );
                
                // This is the magic line for DevTools
                echo "\n/*# sourceURL=CSS-Organizer/" . esc_attr( $virtual_name ) . " */";
                echo '</style>';
            }
        }
    } // End customize_css()


    /**
     * Enqueue the JavaScript
     *
     * @return void
     */
    public function preview_js() {
        $text_domain = Bootstrap::textdomain();
        wp_enqueue_script( $text_domain . '-css-preview', Bootstrap::url( 'inc/js/customizer-preview.js' ), [ 'customize-preview' ], Bootstrap::script_version(), true );
        wp_localize_script( $text_domain . '-css-preview', str_replace( '-', '_', $text_domain ), [ 'sections' => $this->get_section_keys() ] );
    } // End preview_js()


    /**
     * Enqueue the JavaScript
     *
     * @return void
     */
    public function controls_js() {
        $text_domain = Bootstrap::textdomain();
        wp_enqueue_script( $text_domain . '-css-controls', Bootstrap::url( 'inc/js/customizer-controls.js' ), [ 'customize-controls' ], Bootstrap::script_version(), true );

        $important_body_tags = sanitize_text_field( get_option( $text_domain . '-important-body-tags', 'home, blog, archive, single, page, postid-, page-id-, logged-in, page-template-' ) );
        $important_body_tags = array_map( 'trim', explode( ',', $important_body_tags ) );

        $saved_css_data = [];
        foreach ( self::get_sections() as $section ) {
            $option_id = $text_domain . '-css-' . $section[ 'key' ];
            $saved_css_data[ $section[ 'key' ] ] = get_option( $option_id, '' );
        }
        
        wp_localize_script( $text_domain . '-css-controls', str_replace( '-', '_', $text_domain ) . '_controls', [ 
            'text_domain'         => $text_domain,
            'important_body_tags' => $important_body_tags,
            'saved_values'        => $saved_css_data
        ] );
    } // End controls_js()


    /**
     * Add css to the customizer itself
     *
     * @return void
     */
    public function customizer_css() {
        $text_domain = Bootstrap::textdomain();
        $handle = $text_domain . '-customizer-css';

        // 1. Enqueue the static CSS file
        wp_enqueue_style( $handle, Bootstrap::url( 'inc/css/customizer.css' ), [], Bootstrap::script_version() );

        // 2. Generate the dynamic height CSS
        $sections = self::get_sections();
        $outer_classes = [];
        $inner_classes = [];

        foreach ( $sections as $section ) {
            $base_selector = '#sub-accordion-section-' . $text_domain . '-css-' . $section['key'];
            $outer_classes[] = $base_selector;
            $outer_classes[] = $base_selector . ' .customize-control-code_editor .CodeMirror';
            $outer_classes[] = $base_selector . ' .customize-control-code_editor textarea';
            $inner_classes[] = $base_selector . ' .customize-control';
        }

        $dynamic_css = implode( ', ', $outer_classes ) . ' { height: 100% !important; } ';
        $dynamic_css .= implode( ', ', $inner_classes ) . ' { height: calc(100% - 155px) !important; } ';
        $dynamic_css .= '.accordion-section-title button.accordion-trigger { height: revert !important; }';

        // 3. Attach the dynamic CSS to the handle
        wp_add_inline_style( $handle, $dynamic_css );
    } // End customizer_css()


    /**
     * Clean the post cache when a CSS option is updated
     *
     * @param string $option_name The name of the updated option
     * @param mixed $old_value The old value of the option
     * @param mixed $value The new value of the option
     */
    public function clean_post_cache( $option_name, $old_value, $value ) {
        if ( strpos( $option_name, 'css-organizer-css-' ) === 0 ) {
            clean_post_cache( get_current_blog_id() );
        }
    } // End clean_post_cache()

}


new Customizer();