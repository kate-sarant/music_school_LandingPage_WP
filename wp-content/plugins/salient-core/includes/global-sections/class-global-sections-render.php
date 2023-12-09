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


if (!class_exists('Nectar_Global_Sections_Render')) {

    class Nectar_Global_Sections_Render
    {
        private static $instance;
        public static $exclude = false;
        public static $post_type;
        public static $post_id;
        
        private function __construct()
        {
            add_action( 'wp', array($this, 'frontend_display') );
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

        public function frontend_display() {

            // store post type and id outside of global section query 
            // to reflect real post type and id
            if ( !is_admin() ) {
                self::$post_type = get_post_type();
                self::$post_id = get_the_id();
            }

            $this->render_global_sections();
            $this->render_global_section_filters();
         }


        public function parse_conditional($conditional, $include_exclude) 
        {
            $display = true;

            if( 'is_single' === $conditional ) {
                $display = is_single();
            } 
            else if( 'is_archive' === $conditional ) {
                $display = is_archive();
            } 
            else if( 'is_search' === $conditional ) {
                $display = is_search();
            } 
            else if( 'is_front_page' === $conditional ) {
                $display = is_front_page();
            } 
            else if( 'is_user_logged_in' === $conditional ) {
                $display = is_user_logged_in();
            } 
            else if( 'is_user_not_logged_in' === $conditional ) {
                $display = !is_user_logged_in();
            } 
            else if( strpos($conditional, 'post_type__') !== false ) {

                $post_type = str_replace('post_type__', '', $conditional);
                if ( self::$post_type === $post_type ) {
                    $display = true;
                } else {
                    $display = false;
                }
            }
            else if( strpos($conditional, 'role__') !== false ) {
                $role = str_replace('role__', '', $conditional);
       
                if ( current_user_can( $role ) ) {
                    $display = true;
                } else {
                    $display = false;
                }
            }
            else if( 'everywhere' === $conditional ) {
                $display = true;
            }

            // If excluded, short circuit and prevent display.
            if( $include_exclude === 'exclude' && $display ) {
                self::$exclude = true;
            }
            if( $include_exclude === 'exclude' && !self::$exclude ) {
                $display = true;
            }

            return $display;
        }


        /**
         * Render Global Section
         */
        public function render_global_sections() 
        {
            
            // Disabled on cpt single edit.
            if( 'salient_g_sections' === get_post_type() ) {
                return;
            }

            $global_sections_query_args = array(
                'post_type'    => 'salient_g_sections',
                'post_status'  => 'publish',
                'no_found_rows'  => true
            );
            
            $global_sections_query = new WP_Query( $global_sections_query_args );
        
            if( $global_sections_query->have_posts() ) : while( $global_sections_query->have_posts() ) : $global_sections_query->the_post();
                
                $global_section_id = get_the_ID();

                // Locations.
                $locations = get_post_meta($global_section_id, 'nectar_g_section_locations', true);
                if( empty( $locations ) || !is_array($locations) ) {
                    continue;
                }

                foreach($locations as $location) {

                    $location_options = (array) $location;

                    if(!isset($location_options['options']) || !is_array($location_options['options']) ) {
                        continue;
                    }

                    $location_hook = false;
                    $location_priority = '10';

                    // Gather hook and priority
                    foreach($location_options['options'] as $option) {
                        $option = (array) $option;
                        if( $option['type'] === 'priority' ) {
                            $location_priority = sanitize_text_field($option['value']);
                        } 
                        else if($option['type'] === 'location' && !empty($option['value'])) {
                            $location_hook = sanitize_text_field($option['value']);
                        }
                    }

                    // Output to frontend.
                    if( $location_hook ) {

                        // ******** Special location has separate logic. ********
                        if( strpos($location_hook, 'nectar_special_location') !== false ) {
                            call_user_func(array($this, $location_hook), $global_section_id);
                            continue;
                        }
                        
                        // Verify display conditions.
                        $allow_output = $this->verify_conditional_display($global_section_id);
                       
                        // Add section to hook.
                        if( $allow_output ) {
                            add_action(
                                $location_hook, 
                                function() use ( $global_section_id, $location_hook ) { 
                                    $this->output_global_section($global_section_id, $location_hook);
                                }, 
                                $location_priority
                            );

                            $this->modify_salient_markup($location_hook);
                        }

                    }

                } // end foreach locations.


            
            endwhile; endif;  
            
            wp_reset_query();

           
        }


        /**
         * Conditional Logic for global section output.
         */
        public function verify_conditional_display($global_section_id) {

             // Gather and format Conditions to be used in final output below.
             $conditions = get_post_meta($global_section_id, 'nectar_g_section_conditions', true);
             $conditions_arr = array();

             if(is_array($conditions)) {
                 foreach($conditions as $condition) {

                     $condition_options = (array) $condition;

                     if(!isset($condition_options['options']) || !is_array($condition_options['options']) ) {
                         continue;
                     }
                     $conditions_arr[] = $condition_options['options'];
                 }

             }

             // Condition operator (and/or).
             $condition_operator = get_post_meta($global_section_id, 'nectar_g_section_conditions_operator', true);
             if( !empty($condition_operator) && is_array($condition_operator) ) {
                 $condition_operator = sanitize_text_field($condition_operator[0]);
             } else {
                 $condition_operator = 'and';
             }
             
             // Verify display conditions.
             $conditionals = array();
             self::$exclude = false;
             
             foreach($conditions_arr as $conditions_obj) {

                 foreach($conditions_obj as $condition) {

                     $conditional = false;
                     $condition = (array) $condition;
                     if( $condition['type'] === 'include' ) {
                         $include_exclude = $condition['value'];
                     } 
                     else if($condition['type'] === 'condition' && !empty($condition['value'])) {
                         $conditional = $condition['value'];
                     }

                     if($conditional) {
                         
                         if( !$this->parse_conditional($conditional, $include_exclude) ) {
                             $conditionals[] = false;
                         } else {
                             $conditionals[] = true;
                         }
                     
                     }

                 }
                 
             }
             
             $allow_output = false;
             
             if( self::$exclude === false ) {
                 
                 foreach($conditionals as $condition) {
                     if($condition === true) {
                         $allow_output = true;
                     }
                 }

                 // operator is 'and' and one of the conditions is false, prevent output.
                 if ( $condition_operator === 'and' && in_array(false, $conditionals) ) {
                     $allow_output = false;
                 }
             }

             return apply_filters( 'salient_global_section_allow_display', $allow_output );
            
        }

        /**
         * Frontend output.
         */
        public function output_global_section($global_section_id, $location) 
        {
            
            if ( $this->omit_global_section_render($location) ) {
                return;
            }

            $attrs = apply_filters('nectar_global_section_attrs', array(
                'class' => 'nectar-global-section '.$location
            ), $location);

            $inner_attrs = apply_filters('nectar_global_section_inner_attrs', array(
                'class' => 'container normal-container row'
            ), $location);

            $attributes = join(' ',array_map(function($key) use ($attrs)
            {
               if(is_bool($attrs[$key])) {
                  return $attrs[$key]?$key:'';
               }
               return $key.'="'.$attrs[$key].'"';
            }, array_keys($attrs)));

            $inner_attributes = join(' ',array_map(function($key) use ($inner_attrs)
            {
               if(is_bool($inner_attrs[$key])) {
                  return $inner_attrs[$key]?$key:'';
               }
               return $key.'="'. $inner_attrs[$key].'"';
            }, array_keys($inner_attrs)));

            $global_section_shortcode = ' [nectar_global_section id="'.intval($global_section_id).'"] ';
            $global_section_content = '';
            
            if( class_exists('NectarElDynamicStyles') ) {

                if( 0 !== $global_section_id ) {
                    $global_section_query = get_post($global_section_id);
        
                    if( isset($global_section_query->post_content) && !empty($global_section_query->post_content) ) {
                        $global_section_content = $global_section_query->post_content;
                    }
                }
                NectarElDynamicStyles::$element_css = array();
                $global_section_css = NectarElDynamicStyles::generate_styles($global_section_content);
                if( $global_section_css ) {
                    echo '<style>'.$global_section_css.'</style>';
                }
            }
            
            
            echo do_shortcode('<div '.$attributes.'><div '.$inner_attributes.'>'.$global_section_shortcode.'</div></div>');
            
            
     
        }  

        public function omit_global_section_render( $hook ) {

            // Disable ALL when using frontend editor.
            $nectar_using_VC_front_end_editor = ( isset( $_GET[ 'vc_editable' ] ) ) ? sanitize_text_field( $_GET[ 'vc_editable' ] ) : '';
            $nectar_using_VC_front_end_editor = ( $nectar_using_VC_front_end_editor == 'true' ) ? true : false;
            if ( $nectar_using_VC_front_end_editor === true ) {
                return true;
            }
            
            // Disabled on page full screen rows.
            if ( function_exists('nectar_get_full_page_options') ) {
                $nectar_fp_options = nectar_get_full_page_options();

                $full_screen_non_compat_hooks = array(
                    'nectar_hook_global_section_after_header_navigation',
                    'nectar_hook_global_section_after_content', 
                    'nectar_hook_before_content_global_section',
                    'nectar_hook_global_section_footer',
                    'nectar_hook_global_section_parallax_footer',
                    'nectar_hook_global_section_after_footer'
                );

                if( 'on' === $nectar_fp_options['page_full_screen_rows'] && 
                    in_array( $hook, $full_screen_non_compat_hooks ) ) {
                    return true;
                }
            }

            // Disabled locations when using contained header.
            if ( function_exists('nectar_is_contained_header') && nectar_is_contained_header() ) {
                $contained_header_non_compat_hooks = array(
                    'nectar_hook_before_secondary_header', 
                );
                if ( in_array( $hook, $contained_header_non_compat_hooks ) ) {
                    return true;
                }
            }
            

            return false;
        }

        /**
         * Frontend output markup alterations.
         */
        public function render_global_section_filters() {


            add_filter('nectar_global_section_inner_attrs', function($attrs, $location) {

                if( 'nectar_hook_global_section_parallax_footer' === $location ) {
                    $attrs['class'] .= ' nectar-el-parallax-scroll';
                    $attrs['data-scroll-animation'] = 'true';
                    $attrs['data-scroll-animation-intensity'] = '-5';
                }

                return $attrs;
            }, 10, 3);


        }

        /*
            Changes to Salient markup based on certain global sections being active.
        */
        public function modify_salient_markup($hook) {

            // Calculate nectar_hook_before_secondary_header height asap.
            if ( $hook === 'nectar_hook_before_secondary_header' && 
                function_exists('nectar_is_contained_header') && 
                !nectar_is_contained_header() ) {
                add_action('nectar_hook_before_secondary_header', function(){
                    echo '<script>
                        var contentHeight = 0;
                        var headerHooks = document.querySelectorAll(".nectar_hook_before_secondary_header");
                   
                        if( headerHooks ) {

                            Array.from(headerHooks).forEach(function(el){
                                contentHeight += el.getBoundingClientRect().height;
                            });
                        }
                       
                        document.documentElement.style.setProperty("--before_secondary_header_height", contentHeight + "px");
                    </script>';
                },99);
            }

            // Global sections that disabled transparent header.
            $transparent_non_compat_hooks = array(
                'nectar_hook_global_section_after_header_navigation', 
            );

            if( in_array( $hook, $transparent_non_compat_hooks ) ) {

                if ( function_exists('nectar_is_contained_header') && !nectar_is_contained_header() ) {
                    add_filter('nectar_activate_transparent_header', array($this,'after_header_navigation_remove_transparency'), 70);
                }
            }
        }
        public function after_header_navigation_remove_transparency() {
            return false;
        } 
       

        /**
         * Special Location: Blog loop
         */
        public function nectar_special_location__blog_loop($global_section_id) {
            
            add_action('wp_enqueue_scripts', function() use ( $global_section_id ) { 
                if( is_archive() || is_author() || is_category() || is_home() || is_tag() || is_single() ) {
                    $section_content = get_post_field('post_content', $global_section_id);

                    if (!$section_content) {
                        return;
                    }
                    wp_enqueue_style('nectar-element-post-grid');
                    if( class_exists('NectarElDynamicStyles') ) {
                        wp_add_inline_style('nectar-element-post-grid', NectarElDynamicStyles::generate_styles($section_content));
                    }

                    $css = '.nectar-archive-blog-wrap {
                        display: flex;
                    }
                    .nectar-archive-blog-wrap .post-area {
                        margin-top: 0;
                    }
                    .nectar-archive-blog-wrap #sidebar {
                        padding-left: 4%;
                        width: 30%;
                    }
                    .post-area #pagination {
                        padding-left: 0;
                        margin-top: 40px;
                    }
                    ';

                   
                    if( function_exists('get_nectar_theme_options')) {

                        $options = get_nectar_theme_options();

                        if(isset($options['blog_type_post_grid']) ) {
                             // fullwidth.
                            if( strpos($options['blog_type_post_grid'],'fullwidth') !== false ) {
                                $css .= '#ajax-content-wrap .nectar-archive-blog-wrap {
                                    margin-left: -50vw;
                                    margin-left: calc(-50vw + var(--scroll-bar-w)/2);
                                    left: 50%;
                                    width: 100vw;
                                    width: calc(100vw - var(--scroll-bar-w));
                                }
                                html body[data-bg-header="true"].category .container-wrap, 
                                html body[data-bg-header="true"].author .container-wrap,
                                html body[data-bg-header="true"].date .container-wrap, 
                                html body[data-bg-header="true"].blog .container-wrap {
                                    padding-top: 0!important;
                                }
                                .nectar-archive-blog-wrap .nectar-post-grid[data-grid-spacing*="px"],
                                .nectar-archive-blog-wrap .nectar-post-grid[data-grid-spacing*="vw"] {
                                    margin: 0;
                                }
                                .nectar-archive-blog-wrap .spacing-5px { padding: 5px; }
                                .nectar-archive-blog-wrap .spacing-10px { padding: 10px; }
                                .nectar-archive-blog-wrap .spacing-15px { padding: 15px; }
                                .nectar-archive-blog-wrap .spacing-25px { padding: 25px; }
                                .nectar-archive-blog-wrap .spacing-35px { padding: 35px; }
                                .nectar-archive-blog-wrap .spacing-40px { padding: 40px; }
                                .nectar-archive-blog-wrap .spacing-45px { padding: 45px; }
                                .nectar-archive-blog-wrap .spacing-1vw { padding: 0.5vw; }
                                .nectar-archive-blog-wrap .spacing-2vw { padding: 1vw; }
                                .nectar-archive-blog-wrap .spacing-3vw { padding: 3vw; }
                                .nectar-archive-blog-wrap .spacing-4vw { padding: 4vw; }
                                ';
                            }
                            
                        }
                    }

                    

                    wp_add_inline_style('nectar-element-post-grid', $css);

                }
            });


            // Modify blog type
            add_filter('nectar_blog_type', function( $blog_type ) {
                
                if( function_exists('get_nectar_theme_options')) {
                    $options = get_nectar_theme_options();
                    if( Nectar_Global_Sections_Display_Options::is_special_location_active('nectar_special_location__blog_loop') ) {
                        return (isset($options['blog_type_post_grid'])) ? $options['blog_type_post_grid'] : 'contained';
                    }
                }
                return $blog_type;
            });

            //// match expected blog type on single post.
            add_filter('nectar_single_blog_type', function( $blog_type ) {
                
                if( function_exists('get_nectar_theme_options')) {
                    $options = get_nectar_theme_options();
                    if( Nectar_Global_Sections_Display_Options::is_special_location_active('nectar_special_location__blog_loop') && 
                        isset($options['blog_type_post_grid']) ) {

                        if( $options['blog_type_post_grid'] === 'contained-sidebar' ) {
                            return 'std-blog-sidebar';
                        } else {
                            return 'std-blog-fullwidth';
                        }
           
                    }
                }
                return $blog_type;
            });

            add_filter('nectar_blog_row_class', function( $class ) {

                $sidebar_class = '';
                if( function_exists('get_nectar_theme_options')) {
                    $options = get_nectar_theme_options();
                    if( Nectar_Global_Sections_Display_Options::is_special_location_active('nectar_special_location__blog_loop') &&
                        isset($options['blog_type_post_grid']) ) {

                        if( $options['blog_type_post_grid'] === 'contained-sidebar' ) {
                            $sidebar_class = ' force-contained-rows';
                        } 
           
                    }
                }

                return $class . ' nectar-archive-blog-wrap top-level'.$sidebar_class;
            });

        
            // Outer Element wrap.
            add_action( 'nectar_before_blog_loop_content', function() use ( $global_section_id ) { 

                $section_content = get_post_field('post_content', $global_section_id);

		        if (!$section_content) {
                    return;
                }

                $pattern = '/\[nectar_post_grid(.*?)\]/';
                preg_match($pattern, $section_content, $matches);
                if( isset($matches[1]) ) {

                    // parse attributes from shortcode
                    $a = shortcode_parse_atts($matches[1]);
                    $a = shortcode_atts( NectarPostGrid::get_attributes(), $a);

                 

                    $css_class_arr = array('nectar-post-grid-wrap');

                    if( !empty($a['css_class_name']) ) {
                        array_push($css_class_arr, $a['css_class_name']);
                    }

                    if( !empty($a['text_color']) ) {
                        array_push($css_class_arr, 'text-color-'.$a['text_color']);
                    }

                    if( !empty($a['additional_meta_size']) && 'default' != $a['additional_meta_size'] ) {
                        array_push($css_class_arr, 'additional-meta-size-'.$a['additional_meta_size']);
                    } 

                    if( !empty($a['grid_item_spacing']) ) {
                        array_push($css_class_arr, 'spacing-'.$a['grid_item_spacing']);
                    }

                    $el_css_class = implode(" ", $css_class_arr);

                    $json = json_encode( array('post_type' => 'post') ); // unused.
                    echo "<div class='".esc_attr($el_css_class)."' data-el-settings='".$json."' data-query='".$json."' data-style='".esc_attr($a['grid_style'])."'>";

                    if( !empty($a['custom_image_aspect_ratio']) && 'default' !== $a['custom_image_aspect_ratio'] ) {
                        $a['aspect_ratio_image_size'] = '';
                    }

                    // Attributes.
                    if( is_single() ) {
                        // related posts forced attrs.
                        $a['enable_masonry'] = 'false';
                        $a['columns_tablet'] = 'default';
                        $a['columns_phone'] = 'default';
                        $a['columns'] = '3';
                        $a['animation'] = 'none';

                        if ( $a['grid_style'] === 'content_next_to_image' ) {
                            $a['columns'] = '2';
                            $a['columns_tablet'] = '1';
                        }
                        
                    }
                    $data_attrs_escaped = NectarPostGrid::get_data_attributes($a);

                    if( function_exists('nectar_el_dynamic_classnames') ) {
                        $dynamic_el_styles = nectar_el_dynamic_classnames('nectar_post_grid', $a);
                    } else {
                        $dynamic_el_styles = '';
                    }

                    echo '<div class="nectar-post-grid'.$dynamic_el_styles.'" '.$data_attrs_escaped.'>';
                    
                
                }
            });
        

            // Inner Post Loop.
            add_action( 'nectar_blog_loop_post_item', function() use ( $global_section_id ) { 
          
                $section_content = get_post_field('post_content', $global_section_id);

		        if (!$section_content) {
                    return;
                }

                $pattern = '/\[nectar_post_grid(.*?)\]/';
                preg_match($pattern, $section_content, $matches);
                if( isset($matches[1]) ) {
                   
                    // parse attributes from shortcode
                    $a = shortcode_parse_atts($matches[1]);

                    $post_grid_options = array(
                        'post_type'=> 'post',
                        'image_loading' => (isset($a['image_loading'])) ? $a['image_loading'] : 'default',
                        'image_loading_lazy_skip' => (isset($a['image_loading_lazy_skip'])) ? $a['image_loading_lazy_skip'] : '0',
                        'animation' => (isset($a['animation'])) ? $a['animation'] : '',
                        'display_categories' => (isset($a['display_categories'])) ? $a['display_categories'] : 'no',
                        'category_position' => (isset($a['category_position'])) ? $a['category_position'] : 'default',
                        'category_display' => (isset($a['category_display'])) ? $a['category_display'] : 'default',
                        'display_excerpt' => (isset($a['display_excerpt'])) ? $a['display_excerpt'] : 'no',
                        'excerpt_length' => (isset($a['excerpt_length'])) ? $a['excerpt_length'] : '15',
                        'image_size' => (isset($a['image_size'])) ? $a['image_size'] : 'large',
                        'color_overlay' => (isset($a['color_overlay'])) ? $a['color_overlay'] : '',
                        'color_overlay_opacity' => (isset($a['color_overlay_opacity'])) ? $a['color_overlay_opacity'] : '0',
                        'color_overlay_hover_opacity' => (isset($a['color_overlay_hover_opacity'])) ? $a['color_overlay_hover_opacity'] : '0',
                        'grid_style' => (isset($a['grid_style'])) ? $a['grid_style'] : 'content_overlaid',
                        'heading_tag' => (isset($a['heading_tag'])) ? $a['heading_tag'] : 'default',
                        'enable_gallery_lightbox' => (isset($a['enable_gallery_lightbox'])) ? $a['enable_gallery_lightbox'] : '0',
                        'hover_effect' => (isset($a['hover_effect'])) ? $a['hover_effect'] : 'zoom',
                        'vertical_list_hover_effect' => (isset($a['vertical_list_hover_effect'])) ? $a['vertical_list_hover_effect'] : '',
                        'category_style' => (isset($a['category_style'])) ? $a['category_style'] : 'underline',
                        'post_title_overlay' => (isset($a['post_title_overlay'])) ? $a['post_title_overlay'] : '',
                        'display_date' => (isset($a['display_date'])) ? $a['display_date'] : '0',
                        'display_estimated_reading_time' => (isset($a['display_estimated_reading_time'])) ? $a['display_estimated_reading_time'] : '0',
                        'display_author' => (isset($a['display_author'])) ? $a['display_author'] : '0',
                        'author_position' => (isset($a['author_position'])) ? $a['author_position'] : '',
                        'read_more_button' => (isset($a['read_more_button'])) ? $a['read_more_button'] : 'no',
                        'parallax_scrolling' => (isset($a['parallax_scrolling'])) ? $a['parallax_scrolling'] : 'no',
                    );

                    if( is_single() ) {
                        // related posts forced attrs.
                        $post_grid_options['image_loading_lazy_skip'] = '0';
                    }

                
                    // post grid output.
                    echo nectar_post_grid_item_markup($post_grid_options, 0, 'archive');


                } // found shortcode
                
            });

            add_action( 'nectar_after_blog_loop_content', function() use ( $global_section_id ) { 
                echo '</div></div>';
            });
        }


    /**
      * Determines the current post type.
      */
      public function get_post_type() {

        global $post, $typenow;

        $current_post_type = '';

        if ( $post && $post->post_type ) {
          $current_post_type = $post->post_type;
        }
        elseif( $typenow ) {
          $current_post_type = $typenow;
        }
        else if (!empty($_GET['post'])) {
          $fetched_post = get_post( intval($_GET['post']) );
          if($fetched_post) {
            $current_post_type = (property_exists( $fetched_post, 'post_type') ) ? $fetched_post->post_type : '';
          }
        }
        elseif ( isset( $_REQUEST['post_type'] ) ) {
          return sanitize_text_field($_REQUEST['post_type']);
        }

        return $current_post_type;
      }

    }

    // Init class.
    Nectar_Global_Sections_Render::get_instance();
}