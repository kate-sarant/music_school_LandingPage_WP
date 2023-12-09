<?php
/**
* Salient Post Loop Element -- previously named "Post Grid"
*
* @version 1.2
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

// Loop Markup.
require_once( SALIENT_CORE_ROOT_DIR_PATH.'includes/post-grid/loop-markup.php' );

// Post Grid Class.
class NectarPostGrid {
  
  /**
	 * Constructor.
	 */
  public function __construct() {
		
    add_action( 'wp_ajax_nectar_get_post_grid_segment', array($this, 'nectar_get_post_grid_segment') );
    add_action( 'wp_ajax_nopriv_nectar_get_post_grid_segment', array($this, 'nectar_get_post_grid_segment') );
    
    add_action( 'wp', array($this, 'frontend_actions') );
  }

  public function frontend_actions() {

    // Adds Featured Image for Fullscreen Reveal First Post
    add_action('nectar_blog_post_grid_item_start', function($atts, $query){
      // global $post;

      // if( !isset($atts['animation']) || $atts['animation'] !== 'fullscreen-reveal-first-post' ) {
      //   return;
      // }

      // if ( $query->current_post === 0  && has_post_thumbnail() ) {
      //   echo '<span class="nectar-post-grid__reveal-image"><span class="nectar-post-grid__reveal-image__inner">';
      //   echo get_the_post_thumbnail( $post->ID, 'full' );
      //   echo '</span></span>';
       
      // }
    }, 10, 2);
  }
  
  public function nectar_get_post_grid_segment() {
    
    // Query args.
    $post_type        = sanitize_text_field( $_POST['post_type'] );
    $cpt_name         = sanitize_text_field( $_POST['cpt_name'] );
    $custom_query_tax = sanitize_text_field( $_POST['custom_query_tax'] );
    $posts_per_page   = intval($_POST['posts_per_page']);
    $current_page     = intval($_POST['current_page']);
    $post_offset      = intval($_POST['offset']);
    $order            = ( 'DESC' === $_POST['order'] ) ? 'DESC' : 'ASC';
    $orderby          = sanitize_text_field( $_POST['orderby'] );
    $category         = sanitize_text_field( $_POST['category'] );
    $action           = sanitize_text_field( $_POST['load_action'] );
    $ignore_sticky    = sanitize_text_field( $_POST['ignore_sticky_posts'] );
    $posts_shown      = isset($_POST['posts_shown']) ? sanitize_text_field( $_POST['posts_shown'] ) : false;
    
    // Post Grid Instance Settings.
    $attributes = array();
    $attributes['image_loading']                   = 'normal';
    $attributes['cpt_name']                        = $cpt_name; 
    $attributes['post_type']                       = sanitize_text_field($_POST['settings']['post_type']); 
    $attributes['image_size']                      = sanitize_text_field($_POST['settings']['image_size']); 
    $attributes['aspect_ratio_image_size']         = sanitize_text_field($_POST['settings']['aspect_ratio_image_size']); 
    $attributes['parallax_scrolling']              = sanitize_text_field($_POST['settings']['parallax_scrolling']);
    $attributes['category_position']               = sanitize_text_field($_POST['settings']['category_position']);
    $attributes['category_display']                = sanitize_text_field($_POST['settings']['category_display']);
    $attributes['display_categories']              = sanitize_text_field($_POST['settings']['display_categories']); 
    $attributes['display_author']                  = sanitize_text_field($_POST['settings']['display_author']); 
    $attributes['display_excerpt']                 = sanitize_text_field($_POST['settings']['display_excerpt']); 
    $attributes['excerpt_length']                  = sanitize_text_field($_POST['settings']['excerpt_length']); 
    $attributes['display_date']                    = sanitize_text_field($_POST['settings']['display_date']);
    $attributes['display_estimated_reading_time']  = sanitize_text_field($_POST['settings']['display_estimated_reading_time']);
    $attributes['color_overlay']                   = sanitize_text_field($_POST['settings']['color_overlay']);  
    $attributes['color_overlay_opacity']           = sanitize_text_field($_POST['settings']['color_overlay_opacity']);  
    $attributes['color_overlay_hover_opacity']     = sanitize_text_field($_POST['settings']['color_overlay_hover_opacity']);  
    $attributes['card_bg_color']                   = sanitize_text_field($_POST['settings']['card_bg_color']);  
    $attributes['grid_style']                      = sanitize_text_field($_POST['settings']['grid_style']);  
    $attributes['hover_effect']                    = sanitize_text_field($_POST['settings']['hover_effect']);  
    $attributes['post_title_overlay']              = sanitize_text_field($_POST['settings']['post_title_overlay']);  
    $attributes['heading_tag']                     = sanitize_text_field($_POST['settings']['heading_tag']);  
    $attributes['enable_gallery_lightbox']         = sanitize_text_field($_POST['settings']['enable_gallery_lightbox']); 
    $attributes['category_style']                  = sanitize_text_field($_POST['settings']['category_style']); 
    $attributes['overlay_secondary_project_image'] = sanitize_text_field($_POST['settings']['overlay_secondary_project_image']); 
    $attributes['vertical_list_hover_effect']      = sanitize_text_field($_POST['settings']['vertical_list_hover_effect']); 
    $attributes['vertical_list_read_more']         = sanitize_text_field($_POST['settings']['vertical_list_read_more']); 
    $attributes['animation']                       = sanitize_text_field($_POST['settings']['animation']);
    $attributes['read_more_button']                = sanitize_text_field($_POST['settings']['read_more_button']);
    
    if( 'all' === $category || '-1' === $category ) {
      $category  = null;
    }
    
    // Load More
    $sticky_post_IDs = array();

    if( 'load-more' === $action && $current_page > 0 ) {
      $post_offset = $post_offset + ($posts_per_page*$current_page);

      if( $ignore_sticky != 'yes' ) {
        $sticky_post_IDs = get_option( 'sticky_posts' );
      }
      
    } 

    $excluded_posts = $sticky_post_IDs;

    // Random order prevent duplicates.
    if( 'load-more' === $action && $current_page > 0 && 'rand' === $orderby && $posts_shown ) {
      
      $posts_shown_arr = explode(',', $posts_shown);
      foreach($posts_shown_arr as $id) {
        $excluded_posts[] = $id;
      }

      $post_offset = 0;
    }

    // Query
    $nectar_post_grid_query_args = array(
      'post_status'         => 'publish',
      'posts_per_page'      => $posts_per_page,
      'order'               => $order,
      'orderby'             => $orderby,
      'offset'              => $post_offset,
      'post__not_in'        => $excluded_posts
    );

    if( 'portfolio' === $post_type ) {
      $nectar_post_grid_query_args['post_type']    = $post_type;
      $nectar_post_grid_query_args['project-type'] = $category;
    } 
    else if( 'post' === $post_type ) {
      $nectar_post_grid_query_args['post_type']     = $post_type;
      $nectar_post_grid_query_args['category_name'] = $category;
    } 
    else if( 'custom' === $post_type ) {
      $nectar_post_grid_query_args['post_type'] = $cpt_name;
      
      if( !empty($custom_query_tax) ) {
    		
    		$nectar_taxonomies_types = get_taxonomies( array( 'public' => true ) );
    		$terms = get_terms( array_keys( $nectar_taxonomies_types ), array(
    			'hide_empty' => false,
    			'include' => $custom_query_tax,
    		) );
    		
    		$tax_query   = array(); 
    		$tax_queries = array(); 
    		foreach ( $terms as $term ) {
    			if ( ! isset( $tax_queries[ $term->taxonomy ] ) ) {
    				$tax_queries[ $term->taxonomy ] = array(
    					'taxonomy' => $term->taxonomy,
    					'field' => 'id',
    					'terms' => array( $term->term_id ),
    					'relation' => 'IN',
    				);
    			} else {
    				$tax_queries[ $term->taxonomy ]['terms'][] = $term->term_id;
    			}
    		}
    		$tax_query = array_values( $tax_queries );
    		$tax_query['relation'] = 'OR';
    		
    		$nectar_post_grid_query_args['tax_query'] = $tax_query;
    
    	} // end not empty custom tax
      
    }

    $nectar_post_grid_query = new WP_Query( $nectar_post_grid_query_args );
          
    if( $nectar_post_grid_query->have_posts() ) : while( $nectar_post_grid_query->have_posts() ) : $nectar_post_grid_query->the_post();
          
      echo nectar_post_grid_item_markup($attributes, $nectar_post_grid_query->current_post, 'ajax' );
    
    endwhile; endif; 
    
    wp_die(); 
    
  }

  /* Attribute list/defaults for post grid */
  public static function get_attributes() {
    return array(
        'post_type' => 'post',
        'cpt_name' => 'post',
        'custom_query_tax' => '',
        'cpt_all_filter' => '',
        'portfolio_category' 	=> 'all',
        'blog_category' 	=> 'all',
        'text_content_layout' => 'top_left',
        'subtext' => 'none',
        'orderby' => 'date',
        'order' 	=> 'DESC',
        'display_type' => 'grid',
        'flickity_controls' => '',
        'flickity_overflow' => '',
        'flickity_wrap_around' => '',
        'flickity_touch_total_style' => 'default',
        'flickity_touch_total_indicator_bg_color' => '#000',
        'flickity_touch_total_indicator_icon_color' => '#fff',
        'flickity_touch_total_blurred_bg' => '0',
        'posts_per_page' => '-1',
        'post_offset' => '0',
        'enable_gallery_lightbox' => '0',
        'enable_sortable' => '',
        'sortable_color' => 'default',
        'sortable_alignment' => 'default',
        'pagination' => 'none',
        'additional_meta_display' => 'default',
        'display_categories' => '0',
        'display_date' => '0',
        'display_excerpt' => '0',
        'excerpt_length' => '20',
        'display_estimated_reading_time' => '0',
        'display_author' => '0',
        'read_more_button' => '0',
        'author_functionality' => 'default',
        'author_position' => 'default',
        'additional_meta_size' => 'default',
        'ignore_sticky_posts' => '',
        'category_functionality' => 'default',
        'category_style' => 'underline',
        'category_position' => 'default',
        'category_display' => 'default',
        'grid_item_height' => '30vh',
        'grid_item_spacing' => '10px',
        'columns' => '4',
        'enable_masonry' => '',
        '2_col_masonry_layout' => 'default',
        '3_col_masonry_layout' => 'default',
        '4_col_masonry_layout' => 'default',
        'featured_top_item' => '',
        'aspect_ratio_image_size' => '',
        'custom_image_aspect_ratio' => 'default',
        'image_size' => 'large',
        'image_loading' => 'normal',
        'image_loading_lazy_skip' => '0',
        'parallax_scrolling' => '',
        'button_color' => 'black',
        'color_overlay' => '',
        'color_overlay_opacity' => '',
        'color_overlay_hover_opacity' => '',
        'text_color' => 'dark',
        'text_color_hover' => 'dark',
        'shadow_on_hover' => '',
        'enable_indicator' => '',
        'mouse_indicator_style' => 'default',
        'mouse_indicator_color' => '#000',
        'mouse_indicator_text' => 'view',
        'mouse_indicator_blurred_bg' => '',
        'mouse_indicator_text_color' => '#fff',
        'hover_effect' => '',
        'border_radius' => 'none',
        'text_style' => 'default',
        'grid_style' => 'content_overlaid',
        'opacity_hover_animation' => '',
        'post_title_overlay' => '',
        'mouse_follow_image_alignment' => '',
        'mouse_follow_post_spacing' => '25px',
        'heading_tag' => 'default',
        'animation' => 'none',
        'animation_stagger' => '90',
        'animation_easing' => 'default',
        'custom_font_size' => '',
        'font_size_min' => '',
        'font_size_max' => '',
        'font_size_tablet' => '',
        'font_size_phone' => '',
        'content_under_image_text_align' => 'left',
        'overlay_secondary_project_image' => '',
        'card_design' => '',
        'card_bg_color' => '',
        'card_bg_color_hover' => '',
        'vertical_list_hover_effect' => 'none',
        'vertical_list_read_more' => '',
        'content_next_to_image_image_width' => 'default',
        'content_next_to_image_image_gap' => 'default',
        'content_next_to_image_image_position' => 'left',
        'content_next_to_image_vertical_align' => 'top',
        'css_class_name' => ''
      );
  }

  // A standardized location to handle all conditional attributes
  public static function get_data_attributes( $atts ) {

    extract( shortcode_atts( NectarPostGrid::get_attributes(), $atts ) );

    // Attribute restrictions.
    if( 'view' === $mouse_indicator_text ) {
      $indicator_text = esc_html__('View','salient-core');
    } else {
      $indicator_text = esc_html__('Read','salient-core');
    }

    if( 'content_overlaid' !== $grid_style ) {
      $text_color_hover = $text_color;
    }
    if( 'mouse_follow_image' === $grid_style ) {
      $grid_item_spacing = 'none';
    }

    // Locked aspect ratio disables masonry option.
    if( 'content_under_image' === $grid_style ) {

      if( 'yes' === $aspect_ratio_image_size || 
          !empty($custom_image_aspect_ratio) && 'default' !== $custom_image_aspect_ratio )  {

          if( isset($atts[$columns.'_col_masonry_layout']) && strpos($atts[$columns.'_col_masonry_layout'], 'staggered') !== 0) {
            // allow pass thru.
          } else {
            $enable_masonry = 'false';
          }

      }
    }
    if( $featured_top_item == 'yes' ) {
      $enable_masonry = 'false';
    }

    // Gather data attributes.
    $data_attributes = '';

    if ( !empty($image_loading_lazy_skip) && $image_loading_lazy_skip !== '0' ) {
      $data_attributes .= 'data-lazy-skip="'.esc_attr($image_loading_lazy_skip).'" ';
    }

    if( 'carousel' !== $display_type ) {
      $data_attributes .= 'data-indicator="'.esc_attr($enable_indicator).'" '; 
      $data_attributes .= 'data-indicator-style="'.esc_attr($mouse_indicator_style).'" '; 
      if( $mouse_indicator_blurred_bg == 'yes' ) {
        $data_attributes .= 'data-indicator-blur="true" '; 
      }
      $data_attributes .= 'data-indicator-text-color="'.esc_attr($mouse_indicator_text_color).'" '; 
      $data_attributes .= 'data-indicator-color="'.esc_attr($mouse_indicator_color).'" ';
      $data_attributes .= 'data-indicator-text="'. esc_html($indicator_text). '" ';
      if( $enable_masonry ) {
        $data_attributes .= 'data-masonry="'.esc_attr($enable_masonry).'" ';
      }
    }
    
    if( 'vertical_list' === $grid_style ) {
      $columns = '1';
    }
    $data_attributes .= 'data-columns="'. esc_attr($columns) .'" ';
    $data_attributes .= 'data-hover-effect="'.esc_attr($hover_effect).'" ';
    $data_attributes .= 'data-text-style="'.esc_attr($text_style).'" ';

    $data_attributes .= 'data-border-radius="'.esc_attr($border_radius).'" ';

    $data_attributes .= 'data-grid-item-height="'.esc_attr($grid_item_height).'" ';
    $data_attributes .= 'data-grid-spacing="'.esc_attr($grid_item_spacing).'" ';
    $data_attributes .= 'data-text-layout="'.esc_attr($text_content_layout).'" ';
    $data_attributes .= 'data-text-color="'.esc_attr($text_color).'" ';
    $data_attributes .= 'data-text-hover-color="'.esc_attr($text_color_hover).'" ';
    $data_attributes .= 'data-shadow-hover="'.esc_attr($shadow_on_hover).'" ';
   
    if( 'mouse_follow_image' === $grid_style && !in_array($animation, array('fade-in-from-bottom', 'none')) ) {
      $animation = 'fade-in-from-bottom';
    }
    $data_attributes .= 'data-animation="'.esc_attr($animation).'" ';
    $data_attributes .= 'data-animation-stagger="'.esc_attr($animation_stagger).'" ';
    $data_attributes .= 'data-cat-click="'.esc_attr($category_functionality).'" ';

    if ( 'content_next_to_image' === $grid_style ) {
      $data_attributes .= 'data-image-width="'.esc_attr($content_next_to_image_image_width).'" ';
      $data_attributes .= 'data-image-gap="'.esc_attr($content_next_to_image_image_gap).'" ';
      $data_attributes .= 'data-image-position="'.esc_attr($content_next_to_image_image_position).'" ';
      $data_attributes .= 'data-vertical-align="'.esc_attr($content_next_to_image_vertical_align).'" ';
    }

    if( 'content_under_image' === $grid_style) {
      $data_attributes .= ' data-lock-aspect="'.esc_attr($aspect_ratio_image_size).'" ';
      $data_attributes .= ' data-text-align="'.esc_attr($content_under_image_text_align).'" ';
      $data_attributes .= 'data-card="'.esc_attr($card_design).'" ';
    }

    if( 'mouse_follow_image' === $grid_style) {
      $data_attributes .= ' data-opacity-hover="'. esc_attr($opacity_hover_animation).'" ';
      $data_attributes .= 'data-post-title-overlay="'. esc_attr($post_title_overlay).'" ';
      $data_attributes .= 'data-mouse-follow-image-alignment="'. esc_attr($mouse_follow_image_alignment).'" ';
      $data_attributes .= 'data-mouse_follow_post_spacing="'. esc_attr($mouse_follow_post_spacing).'" ';
    }

    if( 'carousel' === $display_type ) {
      $data_attributes .= 'data-controls="'.esc_attr($flickity_controls).'" ';
      $data_attributes .= 'data-r-bottom-total="true" ';
      $data_attributes .= 'data-control-style="material_pagination" ';
      $data_attributes .= 'data-wrap="'.esc_attr($flickity_wrap_around).'" ';
      $data_attributes .= 'data-overflow="'.esc_attr($flickity_overflow).'" ';
      
      if( $flickity_touch_total_style === 'solid_bg' || $flickity_touch_total_style === 'tooltip_text') {

        if( $flickity_touch_total_blurred_bg == 'yes') {
          $data_attributes .= 'data-indicator-blur="true" '; 
        }
        $data_attributes .= 'data-indicator-text="'.esc_html__('Drag','salient-core').'" ';
        $data_attributes .= 'data-indicator-bg="'.esc_attr($flickity_touch_total_indicator_bg_color).'" ';
        $data_attributes .= 'data-indicator-icon="'.esc_attr($flickity_touch_total_indicator_icon_color).'" ';
        $data_attributes .= 'data-indicator-style="'.esc_attr($flickity_touch_total_style).'" ';

      }
      

    }

    // border radius
    if( 'none' !== $border_radius ) {
      $data_attributes .= 'style="--post-grid-border-radius:'.esc_attr($border_radius).';" '; 
    }

    return $data_attributes;
  }
  
}

// Start it up.
$nectar_post_grid = new NectarPostGrid();