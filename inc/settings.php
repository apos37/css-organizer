<?php 
/**
 * Plugin settings
 */

namespace PluginRx\CssOrganizer;

if ( ! defined( 'ABSPATH' ) ) exit;

class Settings {


    /**
     * Default sections
     *
     * @return array
     */
    public static function default_sections() : array {
        return [ 
            [
                'key'   => 'root',
                'label' => __( 'Root', 'css-organizer' )
            ],
            [
                'key'   => 'common',
                'label' => __( 'Common', 'css-organizer' )
            ],
            [
                'key'   => 'text',
                'label' => __( 'Text', 'css-organizer' )
            ],
            [
                'key'   => 'buttons',
                'label' => __( 'Buttons', 'css-organizer' )
            ],
            [
                'key'   => 'forms',
                'label' => __( 'Forms', 'css-organizer' )
            ],
            [
                'key'   => 'layout',
                'label' => __( 'Layout', 'css-organizer' )
            ]
        ];
    } // End default_sections()


    /**
     * Nonce for import/export
     *
     * @var string
     */
    private $nonce = 'css_organizer_settings_nonce';


    /**
     * The single instance of the class
     *
     * @var self|null
     */
    private static ?Settings $instance = null;


    /**
     * Get the singleton instance
     *
     * @return self
     */
    public static function instance() : self {
        return self::$instance ??= new self();
    } // End instance()


    /**
     * Constructor
     */
    private function __construct() {
        add_action( 'admin_menu', [ $this, 'settings_page_submenu' ] );
        add_action( 'admin_init', [  $this, 'settings_fields' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
        add_action( 'wp_ajax_css_organizer_export', [ $this, 'handle_export' ] );
        add_action( 'wp_ajax_css_organizer_import', [ $this, 'handle_import' ] );
    } // End __construct()
    

	/**
     * Settings page
     *
     * @return void
     */
    public function settings_page_submenu() {
        add_submenu_page(
            'themes.php',
            Bootstrap::name() . ' — ' . __( 'Settings', 'css-organizer' ),
            Bootstrap::name(),
            'manage_options',
            Bootstrap::textdomain(),
            [ $this, 'settings_page' ],
            null
        );
    } // End settings_page_submenu()

    
    /**
     * Settings page
     *
     * @return void
     */
    public function settings_page() {
        global $current_screen;
        if ( $current_screen->id != 'appearance_page_' . Bootstrap::textdomain() ) {
            return;
        }

        // Add a notice when options are saved
        if ( isset( $_GET[ 'settings-updated' ] ) && $_GET[ 'settings-updated' ] == 'true' ) { // phpcs:ignore 
            add_settings_error( 'css-organizer-notices', 'css-organizer-settings-saved', __( 'Settings saved successfully.', 'css-organizer' ), 'updated' );
        }

        $is_block_theme = $this->is_block_theme_active();
        $is_customizer_active = $is_block_theme ? get_option( Bootstrap::textdomain() . '-force-wp-customizer', false ) : true;
        $theme_type     = $is_block_theme ? __( 'Block', 'css-organizer' ) : __( 'Classic', 'css-organizer' );
        $theme          = wp_get_theme();
        $editor_url     = ! $is_customizer_active ? admin_url( 'site-editor.php' ) : admin_url( 'customize.php' );
        $editor_label   = ! $is_customizer_active ? __( 'Site Editor', 'css-organizer' ) : __( 'WordPress Customizer', 'css-organizer' );
        ?>
        <div class="wrap css-organizer-admin-wrap settings-page <?php echo $is_customizer_active ? 'customizer-active' : 'customizer-inactive'; ?> <?php echo $is_block_theme ? 'block-theme' : 'classic-theme'; ?>">
            <header class="co-header">
                <div class="co-header-content">
                    <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
                    <p class="description"><?php esc_html_e( 'Organize and manage your custom CSS sections with ease.', 'css-organizer' ); ?></p>
                </div>
            </header>

            <?php settings_errors( 'css-organizer-notices' ); ?>

            <div class="co-grid">
                <div class="co-main-content">
                    <div class="co-card co-welcome-card">
                        <div class="co-card-header">
                            <h2><?php esc_html_e( 'Getting Started', 'css-organizer' ); ?></h2>
                        </div>
                        <div class="co-card-body">
                            <p class="co-welcome-text ">
                                <?php
                                echo wp_kses( 
                                    sprintf( 
                                        /* translators: %1$s is the URL, %2$s is the Label. */
                                        __( 'The CSS Organizer adds a new panel to your <a href="%1$s" class="co-link">%2$s</a> where you can add custom CSS for different sections of your site.', 'css-organizer' ), 
                                        esc_url( $editor_url ), 
                                        esc_html( $editor_label ) 
                                    ), 
                                    [ 'a' => [ 'href' => [], 'class' => [] ] ] 
                                ); 
                                ?>
                            </p>
                        </div>
                    </div>

                    <form method="post" action="options.php" class="co-card">
                        <?php
                            settings_fields( Bootstrap::textdomain() . '-settings' );
                            do_settings_sections( Bootstrap::textdomain() . '-settings' );
                        ?>
                        <div class="co-form-footer">
                            <?php submit_button(); ?>
                        </div>
                    </form>
                </div>

                <div class="co-sidebar">
                    <div class="co-card">
                        <div class="co-card-header">
                            <h2><?php esc_html_e( 'Theme Information', 'css-organizer' ); ?></h2>
                        </div>
                        <div class="co-card-body">
                            <div class="co-info-row">
                                <span class="label"><?php esc_html_e( 'Active Theme', 'css-organizer' ); ?></span>
                                <span class="value"><?php echo esc_html( $theme->get( 'Name' ) ); ?> <small>v<?php echo esc_html( $theme->get( 'Version' ) ); ?></small></span>
                            </div>
                            <div class="co-info-row">
                                <span class="label"><?php esc_html_e( 'Theme Type', 'css-organizer' ); ?></span>
                                <span class="value badge"><?php echo esc_html( $theme_type ); ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="co-card">
                        <div class="co-card-header">
                            <h2><?php esc_html_e( 'Import / Export', 'css-organizer' ); ?></h2>
                        </div>
                        <div class="co-card-body">
                            <p class="description">
                                <?php esc_html_e( 'Export your CSS sections to a JSON file or import them from another site. Importing will overwrite your current settings and CSS, and cannot be undone.', 'css-organizer' ); ?>
                            </p>
                            
                            <div class="co-import-export-actions">
                                <div class="co-action-row">
                                    <button type="button" id="co-export-btn" class="button button-secondary co-full-width">
                                        <?php esc_html_e( 'Export Settings (JSON)', 'css-organizer' ); ?>
                                    </button>
                                </div>

                                <hr class="co-divider" />

                                <form id="co-import-form">
                                    <div class="co-file-input-wrapper">
                                        <input type="file" id="co-import-file" accept=".json" required />
                                    </div>
                                    
                                    <button type="submit" class="button button-primary co-full-width" id="co-import-submit">
                                        <?php esc_html_e( 'Import JSON File', 'css-organizer' ); ?>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    } // End settings_page()


    /**
     * Store the settings fields here so we can call them on uninstall as well
     *
     * @return array
     */
    public function get_settings_fields( $return_keys_only = false ) {
        $fields = [
            [ 
                'key'       => 'force-wp-customizer',
                'title'     => __( 'Force Using WP Customizer for Block Themes', 'css-organizer' ),
                'type'      => 'checkbox',
                'sanitize'  => [ $this, 'sanitize_checkbox' ],
                'section'   => 'general',
                'comments'  => '<br><em>' . sprintf(
                    /* translators: 1: Site Editor URL, 2: WP Customizer URL. */
                    __( 'By default, block themes use the new <a href="%1$s">Site Editor</a>, which puts the CSS Organizer as a panel in the template editor. Enable this option to force using the <a href="%2$s">WP Customizer</a> instead.', 'css-organizer' ),
                    esc_url( admin_url( 'site-editor.php' ) ),
                    esc_url( admin_url( 'customize.php' ) )
                ) . '</em>',
            ],
            [ 
                'key'       => 'hide-addt-css',
                'title'     => __( 'Hide Default "Additional CSS" Option on WP Customizer', 'css-organizer' ),
                'type'      => 'checkbox',
                'sanitize'  => [ $this, 'sanitize_checkbox' ],
                'section'   => 'general',
            ],
            [ 
                'key'       => 'important-body-tags',
                'title'     => __( 'Important Body Tags', 'css-organizer' ),
                'type'      => 'text',
                'width'     => '100%',
                'sanitize'  => 'sanitize_text_field',
                'section'   => 'general',
                'comments'  => '<br><em>' . __( 'Specify important body tags for the CSS Organizer\'s body class dropdown. Separate multiple tags with commas. Tags ending with a hyphen (-) will match any class that starts with that prefix.', 'css-organizer' ) . '</em>',
                'default' 	=> 'home, blog, archive, single, page, postid-, page-id-, logged-in, page-template-',
            ],
            [ 
                'key'       => 'mobile-screen-sizes',
                'title'     => __( 'Mobile Screen Sizes', 'css-organizer' ),
                'type'      => 'text',
                'sanitize'  => 'sanitize_text_field',
                'section'   => 'general',
                'comments'  => '<br><em>' . __( 'Specify mobile screen sizes for the CSS Organizer\'s media query buttons. Separate multiple sizes with commas.', 'css-organizer' ) . '</em>',
                'default' 	=> '480px, 768px, 1024px, 1280px',
            ],
            [ 
                'key'       => 'uninstall-cleanup',
                'title'     => __( 'Clean Up on Uninstall', 'css-organizer' ),
                'type'      => 'checkbox',
                'sanitize'  => [ $this, 'sanitize_checkbox' ],
                'section'   => 'general',
                'comments'  => '<br><em>' . __( 'Enable this option to have the plugin remove all its data from the database when you uninstall it, including settings and custom CSS.', 'css-organizer' ) . '</em>',
            ],
            [ 
                'key'       => 'label',
                'title'     => __( 'CSS Organizer Label', 'css-organizer' ),
                'type'      => 'text',
                'sanitize'  => 'sanitize_text_field',
                'section'   => 'sections',
                'comments'  => '<br><em>' . __( 'Replace the "CSS Organizer" label in your WordPress Customizer', 'css-organizer' ) . '</em>',
                'default' 	=> __( 'CSS Organizer', 'css-organizer' ),
            ],
            [
                'key'       => 'sections',
                'title'     => __( 'CSS Sections', 'css-organizer' ),
                'type'      => 'text_plus',
                'sanitize'  => '',
                'section'   => 'sections',
                'comments'  => '<br><em>' . __( 'You may sort the sections in any order by dragging and dropping them. Be sure to refresh your WordPres Customizer on the front-end if you update these while editing.', 'css-organizer' ) . '</em>',
                'options'   => [
                    [
                        'type'     => 'text',
                        'name'     => 'label',
                        'label'    => __( 'Name', 'css-organizer' )
                    ],
                    [
                        'type'       => 'text',
                        'input_type' => 'metakey',
                        'name'       => 'key',
                        'label'      => __( 'slug', 'css-organizer' ),
                        'class'      => 'metakey',
                        'lock'       => true
                    ],
                ],
                'default'   => self::default_sections()
            ],
        ];

        // Return
        if ( $return_keys_only ) {
            $field_keys = [];
            foreach ( $fields as $field ) {
                $field_keys[] = $field[ 'key' ];
            }
            return $field_keys;
        }
        return $fields;
    } // End get_settings_fields()


    /**
     * Check whether the active theme is a block theme.
     *
     * @return bool
     */
    private function is_block_theme_active() : bool {
        if ( function_exists( 'wp_is_block_theme' ) ) {
            return wp_is_block_theme();
        }

        return false;
    } // End is_block_theme_active()


    /**
     * Settings fields
     *
     * @return void
     */
    public function settings_fields() {
        // Slug
        $text_domain = Bootstrap::textdomain();
        $slug = $text_domain . '-settings';

        /**
         * Sections
         */
        $settings_sections = [
            [ 'general', __( 'Options', 'css-organizer' ), '' ],
            [ 'sections', __( 'Sections', 'css-organizer' ), '' ],
        ];

        // Iter the sections
        foreach ( $settings_sections as $settings_section ) {
            add_settings_section(
                $settings_section[0],
                $settings_section[1],
                $settings_section[2],
                $slug
            );
        }
        
        /**
         * Fields
         */
        $fields = $this->get_settings_fields( false );

        // Iter the fields
        foreach ( $fields as $field ) {
            $option_name = $text_domain . '-' . $field[ 'key' ];
            $callback = 'settings_field_' . $field[ 'type' ];
            $args = [
                'id'    => $option_name,
                'class' => $option_name,
                'name'  => $option_name,
            ];

            // Add comments
            if ( isset( $field[ 'comments' ] ) ) {
                $args[ 'comments' ] = $field[ 'comments' ];
            }
            
            // Add select options
            if ( isset( $field[ 'options' ] ) ) {
                $args[ 'options' ] = $field[ 'options' ];
            }

            // Add default
            if ( isset( $field[ 'default' ] ) ) {
                $args[ 'default' ] = $field[ 'default' ];
            }

            // Add revert
            if ( isset( $field[ 'revert' ] ) ) {
                $args[ 'revert' ] = $field[ 'revert' ];
            }

            // Add width
            if ( isset( $field[ 'width' ] ) ) {
                $args[ 'width' ] = $field[ 'width' ];
            }

            // Add the field
            register_setting( $slug, $option_name, $field[ 'sanitize' ] );
            add_settings_field( $option_name, $field[ 'title' ], [ $this, $callback ], $slug, $field[ 'section' ], $args );
        }
    } // End settings_fields()
    
    
    /**
     * Custom callback function to print text field
     *
     * @param array $args
     * @return void
     */
    public function settings_field_text( $args ) {
        $width = isset( $args[ 'width' ] ) ? $args[ 'width' ] : '30rem';
        $comments = isset( $args[ 'comments' ] ) ? wp_kses_post( $args[ 'comments' ] ) : '';
        $default = isset( $args[ 'default' ] ) ? $args[ 'default' ] : '';

        $value = get_option( $args[ 'name' ], $default );

        if ( empty( $value ) || ( isset( $args[ 'revert' ] ) && true === $args[ 'revert' ] && '' === trim( $value ) ) ) {
            $value = $default;
        }

        printf(
            '<input type="text" id="%1$s" name="%2$s" value="%3$s" style="width:%4$s" />%5$s',
            esc_attr( $args[ 'id' ] ),
            esc_attr( $args[ 'name' ] ),
            esc_html( $value ),
            esc_attr( $width ),
            wp_kses_post( $comments )
        );
    } // settings_field_text()


    /**
	 * Custom callback function to print text plus field
	 *
	 * @param array $args The field properties
	 */
    public function settings_field_text_plus( $args ) {
        $fields = $args[ 'options' ];
		$default = isset( $args[ 'default' ] )  ? $args[ 'default' ] : '';
        $values = get_option( $args[ 'name' ], $default );

        // Allowed HTML
        $allowed_html = [
            'a'   => [
                'href'        => [],
                'id'          => []
            ],
            'br' => [],
            'div' => [
                'class'       => [],
                'id'          => [],
                'data-row'    => []
            ],
            'input' => [
                'type'        => [],
                'name'        => [],
                'value'       => [],
                'id'          => [],
                'placeholder' => [],
                'class'       => [],
                'required'    => [],
                'disabled'    => [],
                'data-type'   => []
            ],
            'select' => [
                'name'        => [],
                'id'          => [],
                'class'       => [],
                'data-type'   => []
            ],
            'option' => [
                'value'       => [],
                'selected'    => []
            ],
            'button' => [
                'type'        => [],
                'id'          => [],
                'class'       => [],
                'data-name'   => []
			],
			'em' => [],
        ];

        // Count
        $incl_class = empty( $values ) ? ' empty' : '';

        // Start with the add new field link
        $results = '<button type="button" class="button add-new-field" data-name="' . $args[ 'name' ] . '">' . __( 'Add New Section', 'css-organizer' ) . ' +</button><br><br>
        <div id="fields_container_' . $args[ 'name' ] . '" class="fields_container' . $incl_class . '">';

            // Add the rows
            if ( !empty( $values ) ) {
                foreach ( $values as $index => $value ) {
                    $results .= $this->create_text_plus_row( $fields, $args[ 'name' ], $value, $index );
                }
            } else {
                $results .= $this->create_text_plus_row( $fields, $args[ 'name' ], [], 0 );
            }
            
        // End container
        $results .= '</div>';

		// Comments
		if ( isset( $args[ 'comments' ] ) ) {
			$results .= '<div class="comments">' . $args[ 'comments' ] . '</div>';
		}

        // Echo
        echo wp_kses( $results, $allowed_html );
	} // End settings_field_text_plus()


    /**
     * Create a row for text+ fields
     *
     * @param array $fields
     * @param string $field_name
     * @param array $values
     * @param int $index
     * @return string
     */
    public function create_text_plus_row( $fields, $field_name, $values, $index ) {
        // Start row container
        $results = '<div class="text-plus-row" data-row="'.$index.'">
			<div class="order">=</div>';
            
            // Iter the fields
            foreach ( $fields as $field ) {
                $type = $field[ 'type' ];
                $input_type = isset( $field[ 'input_type' ] ) ? $field[ 'input_type' ] : false;
                $name = $field[ 'name' ];
                $label = $field[ 'label' ];
                $class = isset( $field[ 'class' ] ) ? ' class="' . $field[ 'class' ] . '"' : '';
                $lock = isset( $field[ 'lock' ] ) && $field[ 'lock' ] ? true : false;
                
                // The value
                $field_value = '';
                if ( isset( $values[ $name ] ) ) {
                    if ( $type == 'number' ) {
                        $field_value = absint( $values[ $name ] );
                    } elseif ( $input_type == 'metakey' ) {
                        $field_value = sanitize_key( $values[ $name ] );
                    } else {
                        $field_value = sanitize_text_field( $values[ $name ] );
                    }
                }
                
                if ( $lock ) {
                    $incl_disabled = ' disabled="disabled"';
                } else {
                    $incl_disabled = '';
                }
                    
                // The input
                switch ( $type ) {
                    case 'select':
                        $results .= '<select name="' . $field_name . '[' . $index . '][' . $name . ']" data-type="' . $name . '"' . $class . '>';

                        foreach ( $field[ 'choices' ] as $choice ) {
                            $is_selected = ( $field_value == $choice[ 'value' ] ) ? ' selected' : '';
                            $results .= '<option value="' . $choice[ 'value' ] . '"' . $is_selected . '>' . $choice[ 'label' ] . '</option>';
                        }

                        $results .= '</select>';
                        break;

                    default:
                        $results .= '<input type="' . $type . '" name="' . $field_name . '[' . $index . '][' . $name . ']" data-type="' . $name . '" value="' . $field_value . '"' . $class . ' placeholder="' . $label . '" required="required"' . $incl_disabled . '/>';
                        break;
                }
            }

            // Remove button
            $results .= '<div><button type="button" class="button remove-row">(-) ' . __( 'Delete', 'css-organizer' ) . '</button></div><div class="warning-message"></div>';

        // End container
        $results .= '</div>';

        // Return
        return $results;
    } // End create_text_plus_row()


    /**
     * Custom callback function to print select field
     *
     * @param array $args
     * @return void
     */
    public function settings_field_select( $args ) {
        $default = isset( $args[ 'default' ] ) ? $args[ 'default' ] : '';
        $value = get_option( $args[ 'name' ], $default );
        if ( isset( $args[ 'revert' ] ) && $args[ 'revert' ] == true && trim( $value ) == '' ) {
            $value = $default;
        }
        ?>
            <select id="<?php echo esc_attr( $args[ 'name' ] ); ?>" name="<?php echo esc_attr( $args[ 'name' ] ); ?>">
                <?php 
                if ( isset( $args[ 'options'] ) ) {
                    foreach ( $args[ 'options'] as $key => $option ) {
                        ?>
                        <option value="<?php echo esc_attr( $key ); ?>"<?php selected( $key, $value ); ?>><?php echo esc_attr( $option ); ?></option>
                        <?php 
                    }
                }
                ?>
            </select> <?php echo isset( $args[ 'comments' ] ) ? wp_kses_post( $args[ 'comments' ] ) : ''; ?>
        <?php
    } // settings_field_select()


    /**
     * Custom callback function to print checkbox field
     *
     * @param array $args
     * @return void
     */
    public function settings_field_checkbox( $args ) {
        $value = get_option( $args[ 'name' ] );
        $comments = isset( $args[ 'comments' ] ) ? $args[ 'comments' ] : '';
        ?>
            <label>
                <input type="checkbox" id="<?php echo esc_attr( $args[ 'name' ] ); ?>" name="<?php echo esc_attr( $args[ 'name' ] ); ?>" <?php checked( $value, 1 ) ?> /> <?php echo isset( $args[ 'label' ] ) ? esc_attr( $args[ 'label' ] ) : ''; ?>
            </label>
            <?php echo wp_kses_post( $comments ); ?>
        <?php
    } // End settings_field_checkbox()


    /**
     * Custom callback function to print textarea field
     *
     * @param array $args
     * @return void
     */
    public function settings_field_textarea( $args ) {
        $value = get_option( $args[ 'name' ] );
        ?>
            <textarea id="<?php echo esc_attr( $args[ 'name' ] ); ?>" name="<?php echo esc_attr( $args[ 'name' ] ); ?>"  rows="<?php echo esc_attr( $args[ 'rows' ] ); ?>" cols="<?php echo esc_attr( $args[ 'cols' ] ); ?>"><?php echo wp_kses_post( $value ); ?></textarea> <?php echo esc_html( $args[ 'comments' ] ); ?>
        <?php
    } // settings_field_textarea()


    /**
     * Custom callback function to print number field
     *
     * @param [type] $args
     * @return void
     */
    public function settings_field_number( $args ) {
        $value = get_option( $args[ 'name' ] );

        printf(
            '<input type="number" id="%1$s" name="%2$s" value="%3$d" />',
            esc_attr( $args[ 'label_for' ] ),
            esc_attr( $args[ 'name' ] ),
            esc_attr( $value )
        );
    } // End settings_field_number()

    
    /**
     * Sanitize checkbox
     *
     * @param int $value
     * @return void
     */
    public function sanitize_checkbox( $value ) {
        return filter_var( $value, FILTER_VALIDATE_BOOLEAN );
    } // End sanitize_checkbox()


	/**
     * Enqueue javascript
     *
     * @return void
     */
    public function enqueue_scripts( $hook ) {
        // Check if we are on the correct admin page
        $text_domain = Bootstrap::textdomain();
        if ( $hook !== 'appearance_page_' . $text_domain ) {
            return;
        }

        $script_version = Bootstrap::script_version();

		// Register and enqueue your JavaScript
        wp_register_script( $text_domain, Bootstrap::url( 'inc/js/settings.js' ), [ 'jquery' ], $script_version, true );
		wp_enqueue_script( $text_domain );
		wp_enqueue_script( 'jquery-ui-sortable' );

        // Localize the script with translation messages
        wp_localize_script( $text_domain, str_replace( '-', '_', $text_domain ) . '_settings', [
            'nonce'          => wp_create_nonce( $this->nonce ),
            'duplicateLabel' => __( 'Duplicate label detected! It must be unique.', 'css-organizer' ),
            'duplicateKey'   => __( 'Duplicate key detected! It must be unique.', 'css-organizer' ),
            'exporting'      => __( 'Exporting', 'css-organizer' ),
            'exportBtn'      => __( 'Export Settings (JSON)', 'css-organizer' ),
            'importing'      => __( 'Importing', 'css-organizer' ),
            'importBtn'      => __( 'Import JSON File', 'css-organizer' ),
            'confirmImport'  => __( 'Are you sure? This will overwrite all current CSS Organizer settings.', 'css-organizer' ),
            'selectFile'     => __( 'Please select a file first.', 'css-organizer' ),
            'importError'    => __( 'An error occurred during the import.', 'css-organizer' ),
        ] );

		// Register and enqueue your CSS
		wp_enqueue_style( $text_domain . '-styles', Bootstrap::url( 'inc/css/settings.css' ), [], $script_version );
    } // End enqueue_scripts()


    /**
     * Handle export
     *
     * @return void
     */
    public function handle_export() {
        check_ajax_referer( $this->nonce, 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => __( 'Permission denied.', 'css-organizer' ) ] );
        }

        $prefix = Bootstrap::textdomain();
        $keys = $this->get_settings_fields( true );
        $export_data = [];

        // 1. Add Metadata for reference (Not used for import)
        $theme = wp_get_theme();
        $theme_name = $theme->get( 'Name' );
        $theme_name = html_entity_decode( wp_strip_all_tags( $theme_name ), ENT_QUOTES, 'UTF-8' );

        $export_data[ 'metadata' ] = [
            'domain'         => wp_parse_url( home_url(), PHP_URL_HOST ),
            'theme_type'     => ( wp_is_block_theme() ) ? 'block' : 'classic',
            'theme_name'     => $theme_name,
            'export_date'    => current_time( 'mysql' ),
            'plugin_version' => Bootstrap::version(),
        ];

        // 2. Get the explicit settings
        foreach ( $keys as $key ) {
            $full_key = $prefix . '-' . $key;
            $val = get_option( $full_key, '' );
            $export_data[ $full_key ] = ( '' === $val ) ? false : $val;
        }

        // 3. Get the dynamic CSS sections
        $sections_key = $prefix . '-sections';
        if ( isset( $export_data[ $sections_key ] ) && is_array( $export_data[ $sections_key ] ) ) {
            foreach ( $export_data[ $sections_key ] as $section ) {
                $css_key = $prefix . '-css-' . $section[ 'key' ];
                $css_val = get_option( $css_key, '' );
                $export_data[ $css_key ] = $css_val;
            }
        }

        wp_send_json_success( [
            'filename' => 'css-organizer-export-' . gmdate( 'Y-m-d' ) . '.json',
            'data'     => $export_data
        ] );
    } // End handle_export()


    /**
     * Handle import
     *
     * @return void
     */
    public function handle_import() {
        check_ajax_referer( $this->nonce, 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => __( 'Permission denied.', 'css-organizer' ) ] );
        }

        if ( ! isset( $_FILES[ 'import_file' ] ) || empty( $_FILES[ 'import_file' ][ 'tmp_name' ] ) ) {
            wp_send_json_error( [ 'message' => __( 'No file uploaded.', 'css-organizer' ) ] );
        }

        $file_path = sanitize_text_field( wp_unslash( $_FILES[ 'import_file' ][ 'tmp_name' ] ) );
        if ( ! is_uploaded_file( $file_path ) ) {
            wp_send_json_error( [ 'message' => __( 'Invalid file upload.', 'css-organizer' ) ] );
        }

        $json_data = file_get_contents( $file_path );
        $json_data = str_replace( "\xEF\xBB\xBF", '', $json_data );
        $data = json_decode( trim( $json_data ), true );

        if ( json_last_error() !== JSON_ERROR_NONE ) {
            wp_send_json_error( [ 
                'message' => __( 'JSON Error: ', 'css-organizer' ) . json_last_error_msg() 
            ] );
        }

        if ( ! is_array( $data ) ) {
            wp_send_json_error( [ 'message' => __( 'Invalid JSON file structure.', 'css-organizer' ) ] );
        }

        $prefix = Bootstrap::textdomain();

        foreach ( $data as $key => $value ) {
            if ( 'metadata' === $key ) {
                continue;
            }

            if ( 0 === strpos( $key, $prefix ) ) {
                if ( is_array( $value ) ) {
                    $sanitized_value = array_map( function( $item ) {
                        return is_array( $item ) ? array_map( 'sanitize_text_field', $item ) : sanitize_text_field( $item );
                    }, $value );
                } elseif ( is_bool( $value ) ) {
                    $sanitized_value = $value;
                } else {
                    $sanitized_value = sanitize_textarea_field( wp_unslash( $value ) );
                }

                update_option( $key, $sanitized_value );
            }
        }

        wp_send_json_success( [ 'message' => __( 'Settings imported successfully!', 'css-organizer' ) ] );
    } // End handle_import()

}


Settings::instance();