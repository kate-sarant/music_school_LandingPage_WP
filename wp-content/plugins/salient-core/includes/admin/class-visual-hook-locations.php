<?php

/**
 * Nectar Visual Hook Locations
 *
 * @package Salient Core
 */


// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}


if (!class_exists('Nectar_Visual_Hook_Locations')) {

    class Nectar_Visual_Hook_Locations
    {
        private static $instance;
        public $show_hook_locations = false;
        private function __construct()
        {
            // Admin user only.
            if ( ! current_user_can( 'manage_options' ) || is_admin() ) {
                return;
            }

            add_action( 'wp', [ $this, 'setup' ], 40);
            add_action( 'admin_bar_menu', [ $this, 'register_admin_toolbar_link' ], 80 );
            add_action( 'wp', [ $this, 'display_hook_locations' ], 50);
            add_action( 'wp_enqueue_scripts', [$this, 'enqueue_styles'] );
        }

        /**
         * Initiator.
         */
        public static function get_instance()
        {
            if (!self::$instance) {
                self::$instance = new self;
            }
            return self::$instance;
        }

        public function setup()
        {
            if( isset( $_GET['salient-hook-locations' ] ) && 'true' === $_GET['salient-hook-locations' ] ) {
                $this->show_hook_locations = true;
            }

            return;
        }

        public function register_admin_toolbar_link( WP_Admin_Bar $admin_bar ) 
        {

            $title = '<span class="ab-icon"></span>' . esc_html__('Display Salient Hooks', 'salient-core');
            $href = add_query_arg( array('salient-hook-locations' => 'true') );
            $id = 'salient-hook-locations';

            if( $this->show_hook_locations === true ) {
                $title = '<span class="ab-icon"></span>' . esc_html__('Hide Salient Hooks', 'salient-core');
                $href = remove_query_arg( 'salient-hook-locations' );
                $id = 'salient-hook-locations-active';
            }

            $admin_bar->add_menu( array(
                'parent' => null,
                'group'  => null,
                'title' => $title,
                'href'  => $href,
                'id'    => $id
            ) );
        }

        public function display_hook_locations() 
        {
            if ( !$this->show_hook_locations ) {
                return;
            }

            $salient_hooks = Nectar_Global_Sections_Display_Options::get_instance()->theme_hooks;

            foreach( $salient_hooks as $category ) {
                foreach( $category as $hook ) {
                    if( isset($hook['value']) ) {

                        $label = esc_html($hook['label']);
                        $hook = esc_html($hook['value']);

                        if( Nectar_Global_Sections_Render::get_instance()->omit_global_section_render($hook) ) {
                            continue;
                        }

                        Nectar_Global_Sections_Render::get_instance()->modify_salient_markup($hook);

                        add_action( $hook, function() use ($label, $hook) {
                            echo '<div class="salient-hook-location nectar-global-section nectar-link-underline '.$hook.'"><div class="container normal-container row">';
                            $add_new_button = '<a target="_blank" rel="noreferrer" href="'.admin_url('post-new.php?post_type=salient_g_sections&salient_starting_hook='.$hook.'&classic-editor').'"><i class="fa fa-plus-circle"></i> <span>Add New Global Section</span></a>';
                            $row_shortcode = '[vc_row type="in_container" bottom_margin="3" top_margin="3" bg_color="#ffffff"][vc_column top_padding_desktop="20" bottom_padding_desktop="20" column_padding_type="advanced" centered_text="true"][vc_column_text]<div class="salient-hook-location__content"><span>'.esc_html__('Hook:','salient-core') . ' ' .esc_html($label). '</span>'. $add_new_button. '</div>[/vc_column_text][/vc_column][/vc_row]';
                            
                            if( class_exists('NectarElDynamicStyles') ) {
                                $global_section_css = NectarElDynamicStyles::generate_styles($row_shortcode);
                                if( $global_section_css ) {
                                    echo '<style>'.$global_section_css.'</style>';
                                }
                            }
                            echo do_shortcode( $row_shortcode );
                            echo '</div></div>';
                        }, 1 );
                    }
                }
               
            }
     
        }

        public function enqueue_styles() 
        {
            
            // Toolbar styling.
            wp_add_inline_style( 'main-styles', '
            #wpadminbar [id*="wp-admin-bar-salient-hook-locations"] .ab-item {
                display: flex;
                align-items: center;
            }
            #wpadminbar #wp-admin-bar-salient-hook-locations .ab-icon:before {
                content: "\f177";
            }
            #wpadminbar #wp-admin-bar-salient-hook-locations-active .ab-icon:before {
                content: "\f530";
            }' );

            // Hook locations styling.
            wp_add_inline_style( 'main-styles', '
                .salient-hook-location .row-bg-wrap .row-bg {
                    transition: box-shadow 0.35s ease, border-color 0.35s ease;
                    border: 1px dashed var(--nectar-accent-color);
                    border-radius: 10px;
                }
                .salient-hook-location .row-bg-wrap .inner-wrap {
                    transform: none!important;
                }
                .salient-hook-location:hover .row-bg-wrap .row-bg {
                    border: 1px dashed transparent;
                    box-shadow: 0 0 0px 4px inset var(--nectar-accent-color);
                }
                
                .salient-hook-location__content {
                    padding: 10px;
                }

                .salient-hook-location__content, 
                .row .salient-hook-location__content a span,
                .row .salient-hook-location__content a i {
                    color: #000;
                }
                .row .salient-hook-location__content a {
                    display: inline-flex;
                    gap: 5px;
                    font-weight: 700;
                    font-size: 13px;
                    align-items: center;
                    text-decoration: none;
                    line-height: 1.3;
                    transform: scale(0.9);
                    transition: transform 0.35s ease, opacity 0.35s ease;
                    opacity: 0;
                }
                .salient-hook-location__content > span {
                    display: block;
                    transform: translateY(50%);
                    transition: transform 0.35s ease, opacity 0.35s ease;
                }
                .salient-hook-location__content a i {
                    top: 0;
                }   
                .salient-hook-location:hover .salient-hook-location__content > span {
                    transform: translateY(0);
                }
                .salient-hook-location:hover .salient-hook-location__content a {
                    transform: translateY(0) scale(1);
                    opacity: 1;
                }
            ');
            
        }
        
    }

     // Init class.
     Nectar_Visual_Hook_Locations::get_instance();
}