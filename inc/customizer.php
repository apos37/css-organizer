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
        add_action( 'customize_register', [ $this, 'register' ], 100 );
        add_action( 'wp_head', [ $this, 'customize_css' ], 999999 );
        add_action( 'customize_preview_init', [ $this, 'preview_js' ] );
        add_action( 'customize_controls_init', [ $this, 'controls_js' ] );
        add_action( 'customize_controls_enqueue_scripts', [ $this, 'customizer_css' ] );
	} // End __construct()


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
    public function register( $wp_customize ) {
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

        // Expand buttons
        $expand_btns = apply_filters( 'cssorganizer_expand_btns', [ 18, 30, 50, 80 ] );
        $expand_btns_string = '';
        foreach ( $expand_btns as $percent ) {
            $disabled = $percent == 18 ? ' disabled' : '';
            $expand_btns_string .= '<button class="button button-secondary css-organizer-expand-btn" data-width="' . $percent . '"' . $disabled . '>' . $percent . '%</button> ';
        }

        // Iter the sections
        foreach ( self::get_sections() as $order => $section ) {

            $section_id = $text_domain . '-css-' . $section[ 'key' ];
            $control_id = $section_id . '-control';

            // Add them
            $wp_customize->add_section( $section_id,
                [
                    'panel'         => $panel_id,
                    'title'         => __( $section[ 'label' ], 'css-organizer' ),
                    'description'   => $expand_btns_string,
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
    } // End register()


    /**
     * Fetch the settings and output any necessary CSS into our header
     *
     * @return void
     */
    public function customize_css() {
        // Get our sections
        $sections = $this->sections;

        // Iter the sections
        foreach ( $sections as $order => $section ) {

            $unique_id = CSSORGANIZER_TEXTDOMAIN.'-css-'.$section[ 'key' ];

            // Add it if not blank
            if ( get_option( $unique_id, '' ) != '' || is_customize_preview() ) {
                echo '<style id="'.esc_attr( CSSORGANIZER_TEXTDOMAIN.'-'.$section[ 'key' ] ).'">'.wp_kses_post( get_option( $unique_id, '' ) ).'</style>';
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
        // wp_localize_script( $text_domain . '-css-controls', str_replace( '-', '_', $text_domain ), [ 'sections' => $this->get_section_keys() ] );
    } // End controls_js()


    /**
     * Add css to the customizer itself
     *
     * @return void
     */
    public function customizer_css() {
        $text_domain = Bootstrap::textdomain();
        // Get our sections
        $sections = self::get_sections();

        // Set the css field classes
        $outer_classes = [];
        foreach ( $sections as $order => $section ) {
            $outer_classes[] = '#sub-accordion-section-' . $text_domain . '-css-' . $section[ 'key' ];
            $outer_classes[] = '#sub-accordion-section-' . $text_domain . '-css-' . $section[ 'key' ] . ' .customize-control-code_editor .CodeMirror';
            $outer_classes[] = '#sub-accordion-section-' . $text_domain . '-css-' . $section[ 'key' ] . ' .customize-control-code_editor textarea';
        }
        $inner_classes = [];
        foreach ( $sections as $order => $section ) {
            $inner_classes[] = '#sub-accordion-section-' . $text_domain . '-css-' . $section[ 'key' ] . ' .customize-control';
        }

        // Let us make the css field height 100%
        echo '<style type="text/css" id="'.esc_attr( $text_domain ).'">
        '.esc_html( implode( ', ', $outer_classes ) ).' {
            height: 100% !important;
        }
        '.esc_html( implode( ', ', $inner_classes ) ).' {
            height: calc(100% - 142px) !important;
        }

        /* Fix the WP Customizer buttons */
        .accordion-section-title button.accordion-trigger {
            height: revert !important;
        }
        </style>';
    } // End customizer_css()
}