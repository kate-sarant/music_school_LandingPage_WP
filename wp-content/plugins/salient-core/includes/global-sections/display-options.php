<?php

/**
 * Nectar Global Sections Display Conditions
 *
 * @package Salient Core
 */


// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}


if (!class_exists('Nectar_Global_Sections_Display_Options')) {

    class Nectar_Global_Sections_Display_Options
    {

        private static $instance;

        public static $post_meta_key = 'nectar_g_section_options';

        public static $locations = array();
        public static $conditions = array();
        public static $conditions_operator = array('and');

        public $theme_hooks = array();
        public $special_locations = array();

        public static $exclude = false;
        public static $options = array();
        public static $options_saved = array(
            'conditions' => array(array()),
            'locations' => array(array())
        );

        private function __construct()
        {

            $this->add_filters();

            if ( is_admin() ) {
                add_action('add_meta_boxes', array($this, 'setup_data'));
            } else {
                add_action('wp', array($this, 'init'));
            }
            
       
            add_action('add_meta_boxes', array($this, 'add_display_options_meta_box'), 90);
            add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_assets'));

            add_action('save_post', array($this, 'save_display_conditions'));
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

        public function init() {
            // This is still needed for visual hook locations.
            if ( current_user_can( 'manage_options' ) ) {
                $this->set_settings();
            }
        }

        
        public function add_filters() 
        {   
            // Limit to only one special location 
            add_filter('nectar_available_special_locations', function($locations) {

                $modified_locations = array();

                foreach($locations as $location) {

                    $location_in_use = self::is_special_location_active($location['value']);

                    if($location_in_use && strval($location_in_use) !== strval(get_the_ID())) {
                        continue;
                    }

                    $modified_locations[] = $location;

                }

                

                return $modified_locations;
            });
        }

        /**
         * Set up data for conditional selects.
         */
        public function setup_data()
        {
            // global $post;
            // get_post_meta($post->ID, 'nectar_g_section_options', '');
            // Create settings list.
            $this->set_settings();

            self::$options_saved = array(

                'conditions' => json_encode(
                    array(
                        array(
                            'options' => array()
                        )
                    )
                ),
                'conditions_operator' => json_encode(array('and')),
                'locations' => json_encode(
                    array(
                        array(
                            'options' => array()
                        )
                    )
                )
            );


            // Populate saved.
            global $post;
   
            self::$conditions = ($post && isset($post->ID)) ? get_post_meta($post->ID, 'nectar_g_section_conditions', true) : false;

            if (!empty(self::$conditions)) {
                self::$options_saved['conditions'] = json_encode(self::$conditions);
            }

            self::$conditions_operator = ($post && isset($post->ID)) ? get_post_meta($post->ID, 'nectar_g_section_conditions_operator', true) : false;

            if (!empty(self::$conditions_operator)) {
                self::$options_saved['conditions_operator'] = json_encode(self::$conditions_operator);
            }

            self::$locations = ($post && isset($post->ID)) ? get_post_meta($post->ID, 'nectar_g_section_locations', true) : false;

           // // Starting location from visual hook link.
           if ( isset($_GET['salient_starting_hook']) && 
           !empty($_GET['salient_starting_hook']) && 
           empty(self::$locations) ) {
                $this->set_starting_hook();
           }

            if (!empty(self::$locations)) {
                self::$options_saved['locations'] = json_encode(self::$locations);
            }

        }


        // Sets the default location to the visual hook that was clicked.
        public function set_starting_hook()
        {
           
            $starting_location = sanitize_text_field($_GET['salient_starting_hook']);

            // verify that it's a valid location.
            if( in_array($starting_location, $this->get_flat_hook_list()) ) {

                // Set location to selected visual hook.
                self::$options_saved['locations'] = json_encode(
                    array (
                        array(
                        'key' => 'v8Pj8YdOlBx0N_q8Di4um',
                        'options' => array (
                                array(
                                    'type' => 'priority',
                                    'value' => '10',
                                ),
                                array(
                                    'type' => 'location',
                                    'value' => $starting_location,
                                )
                            ),
                        ),
                    )
                );

                //Set a default condition to display.
                if ( empty(self::$conditions) ) {
                    self::$options_saved['conditions'] = json_encode(
                        array (
                            array(
                                'key' => 'Ve4fxg4ZdA-nrPVtcXem6',
                                'options' => array (
                                    array(
                                        'type' => 'include',
                                        'value' => 'include',
                                    ),
                                    array(
                                        'type' => 'condition',
                                        'value' => 'everywhere',
                                    )
                                ),
                            ),
                        )
                    );
                }

            }
        
        }
        
        
        public function set_settings()
        {   
            // Post types.
            $post_types = get_post_types(
                array(
                    'public' => true,
                )
            );
            $exlcude_post_types = array('salient_g_sections', 'home_slider', 'nectar_slider');
            
            // Post types.
            $formatted_post_types = array();
            foreach ($post_types as $post_type) {
                if (in_array($post_type, $exlcude_post_types)) {
                    continue;
                }

                $formatted_post_types[] = array(
                    'value' => 'post_type__'.$post_type,
                    'label' => $post_type,
                );
            }

            // User Roles.
            $user_roles = array();
            $roles = get_editable_roles();
            foreach ($roles as $role => $details) {
                $user_roles[] = array(
                    'value' => 'role__'.$role,
                    'label' => $details['name'],
                );
            }

            // Speical locations
            $this->special_locations = array(
                array(
                    'value' => 'nectar_special_location__blog_loop',
                    'label' => esc_html__('Blog Archive Loop', 'salient-core'),
                )
            );

            // Theme hooks.
            $this->theme_hooks = array(
                'top' => array(
                    array(
                        'value' => 'nectar_hook_before_secondary_header',
                        'label' => esc_html__('Inside Header Navigation Top', 'salient-core'),
                    ),

                    array(
                        'value' => 'nectar_hook_global_section_after_header_navigation',
                        'label' => esc_html__('After Header Navigation', 'salient-core'),
                    )
                ),
                'main_content' => array(
                    array(
                        'value' => 'nectar_hook_before_content_global_section',
                        'label' => esc_html__('Before Page/Post Content', 'salient-core'),
                    ),
                    array(
                        'value' => 'nectar_hook_global_section_after_content',
                        'label' => esc_html__('After Page/Post Content', 'salient-core'),
                    ),
                    array(
                        'value' => 'nectar_hook_sidebar_top',
                        'label' => esc_html__('Sidebar Top', 'salient-core'),
                    ),
                    array(
                        'value' => 'nectar_hook_sidebar_bottom',
                        'label' => esc_html__('Sidebar Bottom', 'salient-core'),
                    ),
                    array(
                        'value' => 'nectar_before_blog_loop_start',
                        'label' => esc_html__('Before Blog Loop', 'salient-core'),
                    ),
                    array(
                        'value' => 'nectar_before_blog_loop_end',
                        'label' => esc_html__('After Blog Loop', 'salient-core'),
                    ),
                    array(
                        'value' => 'nectar_hook_404_content',
                        'label' => esc_html__('404 Content', 'salient-core'),
                    ),
                ),

                'menu' => array(
                    array(
                        'value' => 'nectar_hook_ocm_before_menu',
                        'label' => esc_html__('Before Off Canvas Menu Items', 'salient-core'),
                    ),
                    array(
                        'value' => 'nectar_hook_ocm_after_menu',
                        'label' => esc_html__('After Off Canvas Menu Items', 'salient-core'),
                    ),
                    array(
                        'value' => 'nectar_hook_ocm_bottom_meta',
                        'label' => esc_html__('Off Canvas Menu Meta Area', 'salient-core'),
                    ),

                   
                ),

                'bottom' => array(
                    array(
                        'value' => 'nectar_hook_global_section_footer',
                        'label' => esc_html__('Footer', 'salient-core'),
                    ),
                    array(
                        'value' => 'nectar_hook_global_section_parallax_footer',
                        'label' => esc_html__('Footer Parallax', 'salient-core'),
                    ),

                    array(
                        'value' => 'nectar_hook_global_section_after_footer',
                        'label' => esc_html__('After Footer', 'salient-core'),
                        'args' => ''
                    ),
                ),

                'woocommerce' => array(
                    array(
                        'value' => 'nectar_woocommerce_before_shop_loop',
                        'label' => esc_html__('Before Shop Loop', 'salient-core'),
                    ),
                    array(
                        'value' => 'nectar_woocommerce_after_shop_loop',
                        'label' => esc_html__('After Shop Loop', 'salient-core'),
                    ),
                    array(
                        'value' => 'nectar_woocommerce_before_single_product_summary',
                        'label' => esc_html__('Single Product Before Summary', 'salient-core'),
                    ),
                    array(
                        'value' => 'nectar_woocommerce_before_add_to_cart_form',
                        'label' => esc_html__('Single Product Before Add to Cart', 'salient-core'),
                    ),
                    array(
                        'value' => 'nectar_woocommerce_after_add_to_cart_form',
                        'label' => esc_html__('Single Product After Add to Cart', 'salient-core'),
                    ),
                    array(
                        'value' => 'nectar_woocommerce_after_single_product_summary',
                        'label' => esc_html__('Single Product After Summary', 'salient-core'),
                    ),
                    array(
                        'value' => 'nectar_woocommerce_before_checkout_billing_form',
                        'label' => esc_html__('Checkout Before Billing Form', 'salient-core'),
                    ),
                    array(
                        'value' => 'nectar_woocommerce_after_checkout_billing_form',
                        'label' => esc_html__('Checkout After Billing Form', 'salient-core'),
                    ),
                    array(
                        'value' => 'nectar_woocommerce_before_checkout_shipping_form',
                        'label' => esc_html__('Checkout Before Shipping Form', 'salient-core'),
                    ),
                    array(
                        'value' => 'nectar_woocommerce_before_order_notes',
                        'label' => esc_html__('Checkout Before Order Notes', 'salient-core'),
                    ),
                    array(
                        'value' => 'nectar_woocommerce_after_order_notes',
                        'label' => esc_html__('Checkout After Order Notes', 'salient-core'),
                    ),
                    array(
                        'value' => 'nectar_woocommerce_checkout_before_order_review',
                        'label' => esc_html__('Checkout Before Order Review', 'salient-core'),
                    ),
                    array(
                        'value' => 'nectar_woocommerce_review_order_before_payment',
                        'label' => esc_html__('Checkout Before Review Order Payment', 'salient-core'),
                    ),
                    array(
                        'value' => 'nectar_woocommerce_review_order_after_payment',
                        'label' => esc_html__('Checkout After Review Order Payment', 'salient-core'),
                    ),


                    array(
                        'value' => 'nectar_woocommerce_cart_coupon',
                        'label' => esc_html__('Cart Coupon', 'salient-core'),
                    ),
                    array(
                        'value' => 'nectar_woocommerce_before_cart_totals',
                        'label' => esc_html__('Cart Before Totals', 'salient-core'),
                    ),
                    array(
                        'value' => 'nectar_woocommerce_cart_totals_before_shipping',
                        'label' => esc_html__('Cart Before Shipping', 'salient-core'),
                    ),
                    array(
                        'value' => 'nectar_woocommerce_before_shipping_calculator',
                        'label' => esc_html__('Cart Before Shipping Calculator', 'salient-core'),
                    ),
                    array(
                        'value' => 'nectar_woocommerce_after_shipping_calculator',
                        'label' => esc_html__('Cart After Shipping Calculator', 'salient-core'),
                    ),
                    array(
                        'value' => 'nectar_woocommerce_proceed_to_checkout',
                        'label' => esc_html__('Cart Proceed to Checkout', 'salient-core'),
                    ),
                    

                   
                    


       
                )

            );

            // Conditions
            self::$options = array(

                'conditions' => array(
                    array(
                        'label' => 'General',
                        'options' => array(
                            array(
                                'value' => 'everywhere',
                                'label' => esc_html__('Everywhere', 'salient-core'),
                            ),

                            array(
                                'value' => 'is_archive',
                                'label' => esc_html__('Archive', 'salient-core'),
                                'args' => ''
                            ),
                            array(
                                'value' => 'is_front_page',
                                'label' => esc_html__('Front Page', 'salient-core'),
                                'args' => ''
                            ),
                            array(
                                'value' => 'is_search',
                                'label' => esc_html__('Search Results', 'salient-core'),
                                'args' => ''
                            ),

                            array(
                                'value' => 'is_single',
                                'label' => esc_html__('Single', 'salient-core'),
                                'args' => ''
                            ),
                        )

                    ),
                    array(
                        'label' => 'Post Types',
                        'options' => $formatted_post_types
                    ),
                    array(
                        'label' => 'User Roles/Permissions',
                        'options' => array_merge(
                                    array(
                                        array(
                                        'value' => 'is_user_logged_in',
                                        'label' => esc_html__('User Logged In', 'salient-core'),
                                        'args' => ''
                                        )
                                    ), 
                                    array(
                                        array(
                                        'value' => 'is_user_not_logged_in',
                                        'label' => esc_html__('User Not Logged In', 'salient-core'),
                                        'args' => ''
                                        )
                                    ), 
                                    $user_roles
                        )
 
                    ),
                ),

                'locations' => array(
                    array(
                        'label' => esc_html__('Top','salient-core'),
                        'options' => $this->theme_hooks['top']

                    ),
                    array(
                        'label' => esc_html__('Main Content','salient-core'),
                        'options' => $this->theme_hooks['main_content']

                    ),
                    array(
                        'label' => esc_html__('Footer','salient-core'),
                        'options' =>  $this->theme_hooks['bottom']

                    ),
                    array(
                        'label' => esc_html__('Menu','salient-core'),
                        'options' => $this->theme_hooks['menu']

                    ),
                    array(
                        'label' => esc_html__('WooCommerce','salient-core'),
                        'options' => $this->theme_hooks['woocommerce']

                    ),
                    array(
                        'label' => esc_html__('Special','salient-core'),
                        'options' => apply_filters('nectar_available_special_locations', $this->special_locations)
                    ),
                ),

                'include' => array(
                    array(
                        'options' => array(
                            array(
                                'value' => 'include',
                                'label' => esc_html__('True', 'salient-core'),
                            ),
                            array(
                                'value' => 'exclude',
                                'label' => esc_html__('False', 'salient-core'),
                            )
                        )
                    )
                )
            );

        }

        /**
         * Returns a flat hook list of all Salient hooks.
         */
        public function get_flat_hook_list() 
        {
                
                $flat_hook_list = array();
    
                foreach ($this->theme_hooks as $location) {
                    foreach ($location as $hook) {
                        $flat_hook_list[] = $hook['value'];
                    }
                }
    
                return $flat_hook_list;
        }

        /**
         * Handle Admin JS/CSS limited to Global Section CPT
         */
        public function admin_enqueue_assets()
        {

            $current_post_type = $this->get_post_type();

            if ('salient_g_sections' !== $current_post_type) {
                return;
            }

            global $Salient_Core;

            // Display Conditions.
            wp_enqueue_script(
                'nectar-global-sections-display-options',
                SALIENT_CORE_PLUGIN_PATH . '/includes/global-sections/display-options/build/app.js',
                array('jquery'),
                $Salient_Core->plugin_version,
                true
            );

            wp_enqueue_style(
                'nectar-global-sections-display-options',
                SALIENT_CORE_PLUGIN_PATH . '/includes/global-sections/display-options/build/app.css',
                array(),
                $Salient_Core->plugin_version
            );

            wp_localize_script(
                'nectar-global-sections-display-options',
                'nectarDisplayConditions',
                array(
                    'saved' => self::$options_saved,
                    'options' => self::$options,
                    'i18n' => array(
                        'remove' => esc_html__('Remove', 'salient-core'),
                        'and' => esc_html__('And', 'salient-core'),
                        'or' => esc_html__('Or', 'salient-core'),
                        'add_new_display_condition' => esc_html__('Add new condition', 'salient-core'),
                        'add_new_location' => esc_html__('Add new location', 'salient-core'),
                        'display_conditions' => esc_html__('Display Conditions', 'salient-core'),
                        'display_locations' => esc_html__('Display Locations', 'salient-core'),
                        'display_locations_tip' => esc_html__('Set the priority and location of where to render this section on your site. The priority will determine the order of sections when multiple are assigned to the same location.', 'salient-core'),
                        'display_conditions_tip' => esc_html__('Optionally limit the display of your section based on certain conditions such as user privilege, post type etc.', 'salient-core'),
                    )
                )
            );
        }


        /**
         * Registers display conditions metabox
         */
        public function add_display_options_meta_box($post_type)
        {
            if ('salient_g_sections' === $post_type) {
                add_meta_box(
                    'global-section-display-options',
                    esc_html__('Global Section Display', 'salient-core'),
                    array($this, 'render_meta_box_content'),
                    $post_type,
                    'side',
                    'high'
                );
            }
        }

        public function render_meta_box_content()
        {
            echo '<div id="nectar-global-sections-display-options" class="nectar-display-options"></div>';

            wp_nonce_field(basename(__FILE__), 'nectar_display_options_nonce');
        }

        public static function get_active_special_locations() {
            $special_locations = get_option( 'salient_global_section_special_locations', array() );
            return $special_locations;
        }

        public static function is_special_location_active($location) {
            $special_locations = self::get_active_special_locations();
            return isset($special_locations[$location]) ? $special_locations[$location] : false;
        }

        public function update_special_locations($saved_key, $saved_data, $post_id) 
        {
            $special_locations = array(
                'nectar_special_location__blog_loop'
            );

            if( 'nectar_g_section_locations' === $saved_key ) {
                
                $saved_special_locations = self::get_active_special_locations();

                foreach($special_locations as $location) {
                    
                    // Has special location.
                    if(strpos($saved_data, $location) !== false) {

                        $merged_special_locations = array_merge(
                            $saved_special_locations,
                            array( $location => strval($post_id) )
                        );
                        update_option( 'salient_global_section_special_locations', $merged_special_locations );
                    } 
                    else if ( isset($saved_special_locations[ $location ]) && $saved_special_locations[ $location ] === strval($post_id) ) {
                        unset($saved_special_locations[ $location ]);
                        update_option( 'salient_global_section_special_locations', $saved_special_locations );
                    }
                }
            }
        }

        public function get_post_type()
        {

            global $post, $typenow;

            $current_post_type = '';

            if ($post && $post->post_type) {
                $current_post_type = $post->post_type;
            } elseif ($typenow) {
                $current_post_type = $typenow;
            } else if (!empty($_GET['post'])) {
                $fetched_post = get_post(intval($_GET['post']));
                if ($fetched_post) {
                    $current_post_type = (property_exists($fetched_post, 'post_type')) ? $fetched_post->post_type : '';
                }
            } elseif (isset($_REQUEST['post_type'])) {
                return sanitize_text_field($_REQUEST['post_type']);
            }

            return $current_post_type;
        }

        function save_display_conditions($post_id)
        {

            // Autosave.
            if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
                return;
            }


            // Nonce.
            if (
                !isset($_POST[self::$post_meta_key]) ||
                !isset($_POST['nectar_display_options_nonce']) ||
                !wp_verify_nonce($_POST['nectar_display_options_nonce'], basename(__FILE__))
            ) {
                return;
            }


            // Privileges.
            if (!current_user_can('edit_post', $post_id)) {
                return;
            }

            
            foreach ($_POST[self::$post_meta_key] as $key => $val) {

                // Track active special locations.
                $this->update_special_locations($key, $val, $post_id);

                // Save field.
                update_post_meta($post_id, $key, json_decode(html_entity_decode(stripslashes($val))));
            }
        }
    }

    // Init class.
    Nectar_Global_Sections_Display_Options::get_instance();
}
