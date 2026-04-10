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
        ?>
        <style>
        h2 { margin: 3rem 0 1rem 0; }
        </style>
		<div class="wrap">
			<h1><?php echo get_admin_page_title() ?></h1>
			<form method="post" action="options.php">
				<?php
					settings_fields( Bootstrap::textdomain() . '-settings' );
					do_settings_sections( Bootstrap::textdomain() . '-settings' );
					submit_button();
				?>
			</form>
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
                'key'       => 'label',
                'title'     => __( 'CSS Organizer Label', 'css-organizer' ),
                'type'      => 'text',
                'sanitize'  => 'sanitize_text_field',
                'section'   => 'general',
                'comments'  => '<br><em>' . __( 'Replace the "CSS Organizer" label in your WordPress Customizer', 'css-organizer' ) . '</em>',
                'default' 	=> __( 'CSS Organizer', 'css-organizer' ),
            ],
            [ 
                'key'       => 'hide-addt-css',
                'title'     => __( 'Hide Default "Additional CSS" Option', 'css-organizer' ),
                'type'      => 'checkbox',
                'sanitize'  => [ $this, 'sanitize_checkbox' ],
                'section'   => 'general',
            ],
            [
                'key'       => 'sections',
                'title'     => __( 'CSS Sections', 'css-organizer' ),
                'type'      => 'text_plus',
                'sanitize'  => '',
                'section'   => 'general',
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
            ]
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
            [ 'general', '', '' ],
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

        if ( isset( $args[ 'revert' ] ) && $args[ 'revert' ] == true && trim( $value ) == '' ) {
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
        ?>
            <label>
                <input type="checkbox" id="<?php echo esc_attr( $args[ 'name' ] ); ?>" name="<?php echo esc_attr( $args[ 'name' ] ); ?>" <?php checked( $value, 1 ) ?> /> <?php echo isset( $args[ 'label' ] ) ? esc_attr( $args[ 'label' ] ) : ''; ?>
            </label>
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

		// Register and enqueue your JavaScript
        wp_register_script( $text_domain, Bootstrap::url( 'inc/js/settings.js' ), [ 'jquery' ], Bootstrap::script_version(), true );
		wp_enqueue_script( $text_domain );
		wp_enqueue_script( 'jquery-ui-sortable' );

        // Localize the script with translation messages
        wp_localize_script( $text_domain, str_replace( '-', '_', $text_domain ), [
            'duplicateLabel' => __( 'Duplicate label detected! It must be unique.', 'css-organizer' ),
            'duplicateKey'   => __( 'Duplicate key detected! It must be unique.', 'css-organizer' ),
        ] );

		// Register and enqueue your CSS
		wp_enqueue_style( $text_domain . '-styles', Bootstrap::url( 'inc/css/settings.css' ), [], Bootstrap::script_version() );
    } // End enqueue_scripts()

}


Settings::instance();