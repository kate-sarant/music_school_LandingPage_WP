<?php

/**
 * Post grid category total count.
 *
 * @since 1.3
 */
function nectar_post_grid_get_category_total($category_id, $post_type, $term_tax_query = '') {

  // All.
  if( '-1' === $category_id) {
    $category_id = null;
  }

  if( 'post' === $post_type && empty($term_tax_query) ) {

    $nectar_post_grid_cat_query = new WP_Query( array(
      'nopaging' => false,
      'posts_per_page' => 1,
      'post_type' => 'post',
      'category_name' => sanitize_text_field($category_id)
    ));

  } else if( 'portfolio' === $post_type && empty($term_tax_query) ) {

    $nectar_post_grid_cat_query = new WP_Query( array(
      'nopaging' => false,
      'posts_per_page' => 1,
      'post_type' => 'portfolio',
      'project-type' => sanitize_text_field($category_id)
    ));

  } else {
    
    $custom_query_args = array(
      'nopaging' => false,
      'posts_per_page' => 1,
      'post_type' => sanitize_text_field($post_type),
    );

    if( $term_tax_query ) {
      $custom_query_args['tax_query'] = array($term_tax_query);
    }

    $nectar_post_grid_cat_query = new WP_Query($custom_query_args);

  }


  return $nectar_post_grid_cat_query->found_posts;

}


/**
 * Post grid item display.
 *
 * @since 1.3
 */
if(!function_exists('nectar_post_grid_item_markup')) {

  /**
   * @param $atts
   * @param int $index
   * @param string $type
   * @return string
   */
  function nectar_post_grid_item_markup($atts, $index = 0, $type = 'regular') {

      $markup = '';

      global $post;

      if( $post ) {

          $category_markup = null;
          $excerpt_markup = '';
          $image_size = 'large';
          $skip_image = false;
          $has_image = 'false';
          $regular_image_markup = '';
          $secondary_image_markup = '';

          if( isset($atts['image_size']) && !empty($atts['image_size']) ) {
            $image_size = sanitize_text_field($atts['image_size']);
          }

          // Defaults
          if( !isset($atts['animation']) ) {
            $atts['animation'] = 'default';
          }
          if( !isset($atts['color_overlay_opacity']) ) {
            $atts['color_overlay_opacity'] = '0';
          }
          if( !isset($atts['color_overlay_hover_opacity']) ) {
            $atts['color_overlay_hover_opacity'] = '0';
          }
          if( !isset($atts['grid_style'])) {
            $atts['grid_style'] = 'content_overlaid';
          }
          if( !isset($atts['heading_tag'])) {
            $atts['heading_tag'] = 'default';
          }
          if( !isset($atts['enable_gallery_lightbox'])) {
            $atts['enable_gallery_lightbox'] = '0';
          }
          if( !isset($atts['hover_effect'])) {
            $atts['hover_effect'] = 'zoom';
          }
          if( !isset($atts['vertical_list_hover_effect'])) {
            $atts['vertical_list_hover_effect'] = 'none';
          }
          if( !isset($atts['vertical_list_read_more'])) {
            $atts['vertical_list_read_more'] = '';
          }
          if( !isset($atts['category_position'])) {
            $atts['category_position'] = 'default';
          }
          if( !isset($atts['category_display'])) {
            $atts['category_display'] = 'default';
          }
          if( !isset($atts['read_more_button'])) {
            $atts['read_more_button'] = 'no';
          }
          if( !isset($atts['parallax_scrolling'])) {
            $atts['parallax_scrolling'] = '';
          }

          // Skip lazy logic.
          $lazy_images_to_skip = 0;
          if( isset($atts['image_loading_lazy_skip']) && !empty($atts['image_loading_lazy_skip']) && in_array($type,array('regular', 'archive')) ) {
            $lazy_images_to_skip = intval($atts['image_loading_lazy_skip']);
          }
        
          //// archives.
          if( $type === 'archive' ) {
            $index = isset($GLOBALS['nectar-post-loop-index']) ? $GLOBALS['nectar-post-loop-index'] : 0;
          }
          if ( $lazy_images_to_skip !== 0 && $index <= $lazy_images_to_skip - 1 ) {
            $atts['image_loading'] = 'skip-lazy-load';
          }
          //// archives.
          if( $type === 'archive' ) {
            $GLOBALS['nectar-post-loop-index'] = $index + 1;
          }

          
          // Handle Heading Tag.
          $heading_tag = 'h3';
          switch( $atts['heading_tag'] ) {
            case 'h2':
              $heading_tag = 'h2';
              break;
            case 'default':
              $heading_tag = 'h3';
              break;
            case 'h4':
              $heading_tag = 'h4';
              break;
            default:
              $heading_tag = 'h3';
          }

 
          // Card design
          $card_color_style = '';
          if( 'content_under_image' === $atts['grid_style'] &&
              isset($atts['card_bg_color']) &&
              !empty($atts['card_bg_color']) ) {
              $card_color_style = ' style="background-color: '.esc_attr($atts['card_bg_color']).';"';
          }

          // Vertical list.
          if( 'vertical_list' === $atts['grid_style'] && 
              'featured_image' != $atts['vertical_list_hover_effect']) {
                $skip_image = true;
          }

          // Custom Class
          $custom_class_name = '';

          if( isset($atts['hover_effect']) && 
            $atts['hover_effect'] === 'animated_underline' && 
            $atts['grid_style'] !== 'mouse_follow_image' ||
            isset($atts['hover_effect']) && 
            $atts['hover_effect'] === 'animated_underline_zoom' && 
            $atts['grid_style'] !== 'mouse_follow_image' ) {
            $custom_class_name .= ' nectar-underline';
          }

          // Gird item link attributes.
          $link_classes_escaped = '';
          $link_attrs_escaped = '';

          /****************** Post. ******************/
          if( $atts['post_type'] === 'post' || $atts['post_type'] === 'custom' ) {

            // Featured Image.
            if( has_post_thumbnail() && !$skip_image ) {

              $has_image = 'true';

              // Lazy Load.
              if( 'lazy-load' === $atts['image_loading'] && NectarLazyImages::activate_lazy() ||
              ( property_exists('NectarLazyImages', 'global_option_active') && true === NectarLazyImages::$global_option_active && 'skip-lazy-load' !== $atts['image_loading']) ) {
                
                $regular_image_markup = nectar_lazy_loaded_image_markup(get_post_thumbnail_id( $post->ID ), $image_size);
                
              }

              // No Lazy Load.
              else {
                $regular_image_markup = get_the_post_thumbnail($post->ID, $image_size);
              }

            } // endif has featured img.

            // Categories.
            if( isset($atts['display_categories']) && 'yes' === $atts['display_categories'] ) {

              $category_markup .= '<span class="meta-category">';
              $category_style = (isset($atts['category_style'])) ? $atts['category_style'] : 'underline';
              $categories = get_the_category();

              if ( ! empty( $categories ) ) {
                $output = null;
                foreach ( $categories as $category ) {

                  // Category display type
                  if ( $atts['category_display'] === 'parent_only' && $category->parent !== 0 ) {
                    continue;
                  }

                  $link_style_attr = '';
    
                  if( $category_style === 'button' ) {
                    $t_id  = esc_attr($category->term_id);
                    $terms = get_option( "taxonomy_$t_id" );
                    $button_text_color = (isset($terms['category_text_color']) && !empty($terms['category_text_color']) ) ? esc_attr($terms['category_text_color']) : false;
                    $button_bg_color = (isset($terms['category_color']) && !empty($terms['category_color']) ) ? esc_attr($terms['category_color']) : false;
                    
                    if( $button_text_color || $button_bg_color ) {
                      $link_style_attr = 'style="';

                      if( $button_text_color ) {
                        $link_style_attr .= 'color: '.esc_attr($button_text_color).'; '; 
                      }
                      if($button_bg_color) {
                        $link_style_attr .= 'background-color: '.esc_attr($terms['category_color']).';';
                      }
                      
                      $link_style_attr .= '" ';
                    }

                  }

                  $output .= '<a '.$link_style_attr.'class="' . esc_attr( $category->slug ) . ' style-'.esc_attr( $category_style ).'" href="' . esc_url( get_category_link( $category->term_id ) ) . '">' . esc_html( $category->name ) . '</a>';
                }
                $category_markup .=  trim( $output );
              }

              $category_markup .= '</span>';

            }

            // Excerpt.
            if( isset($atts['display_excerpt']) && 'yes' === $atts['display_excerpt'] && function_exists('get_nectar_theme_options') ) {
              $nectar_options = get_nectar_theme_options();
              $excerpt_length = ( ! empty( $nectar_options['blog_excerpt_length'] ) ) ? intval( $nectar_options['blog_excerpt_length'] ) : 15;
              
              if( isset($atts['excerpt_length']) && !empty($atts['excerpt_length']) ) {
                $excerpt_length = intval($atts['excerpt_length']);
              }

              $excerpt_markup = '<div class="nectar-post-grid-item__excerpt-wrap item-meta-extra"><span class="meta-excerpt">' . nectar_excerpt( $excerpt_length ) . '</span></div>';
            }

            // Permalink.
            $post_perma = get_the_permalink();

            // Custom query for portfolio items.
            if( $atts['post_type'] === 'custom' && 
                isset($atts['cpt_name']) && 
                $atts['cpt_name'] === 'portfolio' ) {
                  
              $custom_project_link = get_post_meta($post->ID, '_nectar_external_project_url', true);
              $post_perma = ( !empty($custom_project_link) ) ? $custom_project_link : $post_perma;
            }
     

            
            // Lightbox item.
            if( 'yes' === $atts['enable_gallery_lightbox'] && has_post_thumbnail() ) {

              $post_featured_image_full_src = get_the_post_thumbnail_url($post->ID,'full');

              if( $post_featured_image_full_src && !empty($post_featured_image_full_src) ) {
                
                $post_perma = $post_featured_image_full_src;
                
                $post_image_caption  = get_post(get_post_thumbnail_id())->post_content;
                $post_image_caption  = strip_tags($post_image_caption);
                if( $post_image_caption && !empty($post_image_caption) ) {
                  $link_attrs_escaped .= ' title="'. wp_kses_post( $post_image_caption ) .'"';
                }
                
                $link_classes_escaped .= ' pretty_photo';
                
              }

            } // End lightbox item.
            
            // Link Format.
            if( $atts['post_type'] === 'post' && get_post_format() === 'link' ) {
              $post_link_url  = get_post_meta( $post->ID, '_nectar_link', true );
              $post_link_text = get_the_content();

              if ( empty( $post_link_text ) && !empty($post_link_url) ) {
                $post_perma = esc_url($post_link_url);
                $link_attrs_escaped .= ' target="_blank"';
              }

            }

          }

          /****************** Portfolio post type. ******************/
          else if( $atts['post_type'] === 'portfolio') {

            $custom_project_class = get_post_meta($post->ID, '_nectar_project_css_class', true);
            $custom_thumbnail     = get_post_meta($post->ID, '_nectar_portfolio_custom_thumbnail', true);

            // Class name
            if( !empty($custom_project_class) ) {
              $custom_class_name .= ' ' . $custom_project_class;
            }

            // Secondary Image.
            if( isset($atts['overlay_secondary_project_image']) && $atts['overlay_secondary_project_image'] === 'yes' ) {

              $secondary_project_image = get_post_meta($post->ID, '_nectar_portfolio_secondary_thumbnail', true);

              if( !empty($secondary_project_image) ) {

                $secondary_project_image_id = attachment_url_to_postid( $secondary_project_image );

                if( $secondary_project_image_id ) {

                  $custom_class_name .= ' nectar-post-grid-item__has-secondary';

                  if( 'lazy-load' === $atts['image_loading'] && NectarLazyImages::activate_lazy() ||
                  ( property_exists('NectarLazyImages', 'global_option_active') && true === NectarLazyImages::$global_option_active && 'skip-lazy-load' !== $atts['image_loading'] ) ) {

                    $secondary_project_image_markup = wp_get_attachment_image($secondary_project_image_id, 'large', '', array( 'class' => 'nectar-lazy nectar-post-grid-item__overlaid-img' ));
                    $secondary_image_markup .= NectarLazyImages::generate_image_markup($secondary_project_image_markup);
                  } else {

                    $secondary_image_markup = wp_get_attachment_image($secondary_project_image_id, 'large', '', array( 'class' => 'nectar-post-grid-item__overlaid-img' ));
          
                  }
       
                }
               
              }
              
            } // End using secondary project image.

            // Custom thumb.
            $thumbnail_id = '';
            
            if( !empty($custom_thumbnail) && !$skip_image ) {

              $has_image = 'true';

              // Lazy load.
              if( 'lazy-load' === $atts['image_loading'] && NectarLazyImages::activate_lazy() ||
                 ( property_exists('NectarLazyImages', 'global_option_active') && true === NectarLazyImages::$global_option_active && 'skip-lazy-load' !== $atts['image_loading'] ) ) {

                $regular_image_markup .= '<img class="nectar-lazy" data-nectar-img-src="'.nectar_ssl_check( esc_url( $custom_thumbnail ) ).'" alt="'. get_the_title() .'" />';
                
              }

              // Regular load.
              else {              
                $regular_image_markup .= '<img class="skip-lazy" src="'.nectar_ssl_check( esc_url( $custom_thumbnail ) ).'" alt="'. get_the_title() .'" />';
                
              }

            }

            // Featured Img.
            else if( !$skip_image && has_post_thumbnail()) {

              $has_image = 'true';
              $thumbnail_id = get_post_thumbnail_id( $post->ID );
              
              // Lazy load.
              if( 'lazy-load' === $atts['image_loading'] && NectarLazyImages::activate_lazy() ||
                  ( property_exists('NectarLazyImages', 'global_option_active') && true === NectarLazyImages::$global_option_active && 'skip-lazy-load' !== $atts['image_loading'] ) ) {

                  $regular_image_markup = nectar_lazy_loaded_image_markup($thumbnail_id, $image_size);
                  
              }

              // Regular Load.
              else {
                  $regular_image_markup .= wp_get_attachment_image($thumbnail_id, $image_size);
              }

            } // End Featured Img.

            
            // Categories.
            $category_markup = null;

            if( isset($atts['display_categories']) && 'yes' === $atts['display_categories'] ) {

              $category_markup .= '<span class="meta-category">';

              $project_categories = get_the_terms($post->id,"project-type");

              if ( !empty($project_categories) ){
                $output = null;
                foreach ( $project_categories as $term ) {

                  if( isset($term->slug) ) {
                    $output .= '<a class="' . esc_attr( $term->slug ) . '" href="' . esc_url( get_category_link( $term->term_id ) ) . '">' . esc_html( $term->name ) . '</a>';
                  }

                }
                $category_markup .=  trim( $output );
              }

              $category_markup .= '</span>';

            }

            // Excerpt.
            if( isset($atts['display_excerpt']) && 'yes' === $atts['display_excerpt'] ) {
              $project_excerpt = get_post_meta($post->ID, '_nectar_project_excerpt', true);
              $excerpt_markup = (!empty($project_excerpt)) ? '<div class="nectar-post-grid-item__excerpt-wrap item-meta-extra"><span class="meta-excerpt">' . wp_kses_post($project_excerpt) . '</span></div>' : '';
            }

            // Permalink.
            $custom_project_link = get_post_meta($post->ID, '_nectar_external_project_url', true);
            $lightbox_only_item  = get_post_meta($post->ID, '_nectar_portfolio_lightbox_only_grid_item', true);

            $post_perma = ( !empty($custom_project_link) ) ? $custom_project_link : get_the_permalink();

            // Lightbox item.
            if( !empty( $lightbox_only_item ) && 'on' === $lightbox_only_item || 'yes' === $atts['enable_gallery_lightbox'] ) {

              $video_embed = get_post_meta($post->ID, '_nectar_video_embed', true);
              $video_mp4 = get_post_meta($post->ID, '_nectar_video_m4v', true);

              //video
              if( !empty($video_embed) && function_exists('nectar_extract_video_lightbox_link') ||
                  !empty($video_mp4) && function_exists('nectar_extract_video_lightbox_link') ) {

                $project_video_link = nectar_extract_video_lightbox_link($post, $video_embed, $video_mp4);
                $post_perma = $project_video_link;
                $link_classes_escaped .= ' pretty_photo';

              } else if( empty($custom_project_link) ) {

                $featured_image_full_src = wp_get_attachment_image_src( $thumbnail_id, 'full');

                if( $featured_image_full_src && !empty($featured_image_full_src) ) {
                  $post_perma = $featured_image_full_src[0];
                  $project_image_caption  = get_post(get_post_thumbnail_id())->post_content;
      						$project_image_caption  = strip_tags($project_image_caption);
                  if( $project_image_caption && !empty($project_image_caption) ) {
                    $link_attrs_escaped .= ' title="'. wp_kses_post( $project_image_caption ) .'"';
                  }
                  $link_classes_escaped .= ' pretty_photo';
                }
                
              }

            } // End lightbox item.

          }

          
          $bg_overlay_markup = (isset($atts['color_overlay']) && !empty($atts['color_overlay'])) ? 'style=" background-color: '. esc_attr($atts['color_overlay']) .';"' : '';


          /****************** Output Markup ******************/
          $markup .= '<div class="nectar-post-grid-item'.esc_attr($custom_class_name).'"'.$card_color_style.' data-post-id="'.esc_attr($post->ID).'" data-has-img="'.esc_attr($has_image).'"> <div class="inner">';

          // parallax bg.
          $parallax_el_markup_open = '';
          $parallax_el_markup_close = '';

          if( 'yes' === $atts['parallax_scrolling'] ) {
            $parallax_el_markup_open = '<div class="nectar-el-parallax-scroll" data-scroll-animation="true" data-scroll-animation-mobile="true" data-scroll-animation-intensity="-0.75" data-scroll-animation-lerp="1">';
            $parallax_el_markup_close = '</div>';
          }

            $markup .= '<div class="nectar-post-grid-item-bg-wrap">'.$parallax_el_markup_open.'<div class="nectar-post-grid-item-bg-wrap-inner">';
            // Conditional based on style
            if( 'content_overlaid' !== $atts['grid_style'] && 'vertical_list' !== $atts['grid_style'] ) {
              $markup .= '<a class="bg-wrap-link" aria-label="'.get_the_title().'" href="'. esc_attr($post_perma) .'"></a>';
            }

            $markup .= $secondary_image_markup . '<div class="nectar-post-grid-item-bg">'.apply_filters('nectar_post_grid_item_image', $regular_image_markup).'</div>';
            
            $markup .= '</div></div>';
            $markup .= $parallax_el_markup_close;

  

          if( 'content_overlaid' === $atts['grid_style'] ) {
            $markup .= '<div class="bg-overlay" '.$bg_overlay_markup.' data-opacity="'. esc_attr($atts['color_overlay_opacity']) .'" data-hover-opacity="'. esc_attr($atts['color_overlay_hover_opacity']) .'"></div>';
          }

          if ( has_action('nectar_post_grid_item_bg_markup_after') ) {
            ob_start();
            do_action( 'nectar_post_grid_item_bg_markup_after' );
            $nectar_post_grid_item_bg_markup_after = ob_get_clean();
            $markup .= $nectar_post_grid_item_bg_markup_after;
          }

          $markup .= '<div class="content">';

          if ( has_action('nectar_post_grid_item_content_markup_before') ) {
            ob_start();
            do_action( 'nectar_post_grid_item_content_markup_before' );
            $nectar_post_grid_item_content_markup_before = ob_get_clean();
            $markup .= $nectar_post_grid_item_content_markup_before;
          }

          $markup .= '<a class="nectar-post-grid-link'.$link_classes_escaped.'" href="'. esc_attr($post_perma) .'" aria-label="'.get_the_title().'"'.$link_attrs_escaped.'></a>';

          $markup .= $category_markup;

          $post_title_overlay = ( isset($atts['post_title_overlay']) && 'yes' === $atts['post_title_overlay'] ) ? ' data-title-text="'.get_the_title().'"' : '';

          $markup .= '<div class="item-main">';
          
          $post_title_markup = '<'.esc_html($heading_tag).' class="post-heading"><a href="'. esc_attr($post_perma) .'"'.$post_title_overlay.'>';
          
          if ( 'zoom-out-reveal' === $atts['animation'] ) {
            $post_title_markup .= '<div class="nectar-split-heading custom-trigger" data-align="default" data-m-align="inherit" data-text-effect="default" data-animation-type="line-reveal-by-space" data-animation-delay="" data-animation-offset="" data-m-rm-animation="" data-stagger="true">';
            $post_title_markup .= '<p>'.do_shortcode(get_the_title()).'</p>';
            $post_title_markup .= '</div>';
          } else {
            $post_title_markup .= '<span>'.get_the_title().'</span>';
          }
          
          $post_title_markup .= '</a></'.esc_html($heading_tag).'>';
         
          if( 'vertical_list' !== $atts['grid_style'] ) {
            $markup .= $post_title_markup;
          }


          if( has_filter('nectar_post_grid_excerpt') ) {
            $post_type_in_use = ($atts['post_type'] === 'custom' ) ? $atts['cpt_name'] : $atts['post_type'];
            $excerpt_markup = apply_filters('nectar_post_grid_excerpt', $excerpt_markup, $post_type_in_use);
          }

          if( 'vertical_list' !== $atts['grid_style'] && in_array($atts['category_position'], array('default','overlaid')) ) {
            $markup .= $excerpt_markup;
          }

          // Meta.
          $has_meta_date = ( isset($atts['display_date']) && 'yes' === $atts['display_date'] ) ? true : false;
          $has_meta_reading_time = ( isset($atts['display_estimated_reading_time']) && 'yes' === $atts['display_estimated_reading_time'] && function_exists('nectar_estimated_reading_time') ) ? true : false;
          $has_author = ( isset($atts['display_author']) && 'yes' === $atts['display_author'] ) ? true : false;

          if( $has_meta_date || $has_meta_reading_time || $has_author) {
            $markup .= '<span class="nectar-post-grid-item__meta-wrap">';
          }


          // Date.
          $meta_date = '';
          if( $has_meta_date ) {
            $date = get_the_date();
            if( function_exists('get_nectar_theme_options') && $atts['post_type'] === 'post' ) {
              $nectar_options = get_nectar_theme_options();
              $date_functionality = (isset($nectar_options['post_date_functionality']) && !empty($nectar_options['post_date_functionality'])) ? $nectar_options['post_date_functionality'] : 'published_date';
              if( 'last_editied_date' === $date_functionality ) {
                $date = get_the_modified_date( );
              }
            }
            $meta_date .= '<span class="meta-date">' . $date . '</span>';
          }

          // Estimated reading.
          $meta_est_reading = '';
          if( $has_meta_reading_time ) {
                $meta_est_reading .= '<span class="meta-reading-time">' . nectar_estimated_reading_time(get_the_content()). ' '. esc_html__('min','salient-core') . '</span>';
          }

           // Author.
           $meta_author = '';
           $author_position = ( isset($atts['author_position']) ) ? $atts['author_position'] : 'default';
 
           if ($has_author) {

             $author_link_start = $author_link_end = '';
             if( isset($atts['author_functionality']) && 'default' === $atts['author_functionality'] ) {
               $author_link_start = '<a href="' . get_author_posts_url( get_the_author_meta( 'ID' ) ). '">';
               $author_link_end = '</a>';
             }

             $meta_author .= '<span class="meta-author">';
 
             if ( function_exists( 'get_avatar' ) ) {
               $meta_author .= get_avatar( get_the_author_meta( 'email' ), 40, null, get_the_author() ); 
             }

             $meta_author .= '<span class="meta-author-inner">';
             $meta_author .= $author_link_start;
             $meta_author .= '<span class="meta-author-name">'.get_the_author().'</span>';
             $meta_author .= $author_link_end;
             
             // Multiline combines other meta.
             if( 'multiline' === $author_position && 
                 'vertical_list' !== $atts['grid_style'] && 
                 ($has_meta_date || $has_meta_reading_time) ) {

                  $meta_author .= '<span>';
                  $meta_author .= $meta_date;
                  $meta_author .= $meta_est_reading;
                  $meta_author .= '</span>';
             }

             $meta_author .= '</span>';
             $meta_author .= '</span>';
             
           }

          // Author standard position.
          if ( 'default' === $author_position || 'vertical_list' === $atts['grid_style'] ) {
            if ('vertical_list' !== $atts['grid_style']) {
              $markup .= $meta_author;
            }
            $markup .= $meta_date;
            $markup .= $meta_est_reading;
          }
          
          // End Meta.
          if( $has_meta_date || $has_meta_reading_time || $has_author ) {
            $markup .= '</span>';
          }
          

          // excerpt.
          if( 'vertical_list' !== $atts['grid_style'] && $atts['category_position'] === 'below_title' ) {
            $markup .= $excerpt_markup;
          }
          
          if( 'vertical_list' === $atts['grid_style'] ) {
            $markup .= '<div class="post-heading-wrap">';
            $markup .= $post_title_markup . $excerpt_markup;
            $markup .= $meta_author;
            $markup .= '</div>';
          }

          // Author alt position.
          if( 'multiline' === $author_position && 'vertical_list' !== $atts['grid_style'] ) {
            $markup .= $meta_author;
          }

          // Read More.
          if( $atts['vertical_list_read_more'] === 'yes' ) {
            $markup .= '<div class="nectar-link-underline"><a href="'. esc_attr($post_perma) .'" class="nectar-underline"><span>'.esc_html__('Read More','salient-core').'</span></a></div>';
          }
          else if( $atts['read_more_button'] === 'yes') {
            $markup .= '<span class="nectar-post-grid-item__read-more nectar-cta nectar-inherit-label" data-triggered-by=".nectar-post-grid-item" data-style="curved-arrow-animation">
                <span class="link_text">';
              $markup .= esc_html__('Read More', 'salient-core') . nectar_get_curved_arrow_markup();
              $markup .= '</span></span>';
          }

          $markup .= '</div>';

          $markup .= '</div>';
          $markup .= '</div></div>';
      }

      return $markup;

  }
}
