<?php 

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

wp_enqueue_style( 'nectar-element-post-grid' );

extract( shortcode_atts( NectarPostGrid::get_attributes(), $atts ) );

if( 'yes' !== $card_design ) {
	$card_bg_color = '';
}

if( !empty($custom_image_aspect_ratio) && 'default' !== $custom_image_aspect_ratio ) {
  $aspect_ratio_image_size = '';
  $atts['aspect_ratio_image_size'] = '';
}

// Certain items need to be stored in JSON when using sortable/load more.
$el_settings = array(
	'post_type' => esc_attr($post_type),
	'pagination' => esc_attr($pagination),
	'image_size' => esc_attr($image_size),
	'parallax_scrolling' => esc_attr($parallax_scrolling),
	'aspect_ratio_image_size' => esc_attr($aspect_ratio_image_size),
	'category_position' => esc_attr($category_position),
	'category_display' => esc_attr($category_display),
	'display_categories' => esc_attr($display_categories),
	'display_excerpt' => esc_attr($display_excerpt),
	'excerpt_length' => esc_attr($excerpt_length),
	'display_date' => esc_attr($display_date),
	'display_estimated_reading_time' => esc_attr($display_estimated_reading_time),
	'display_author' => esc_attr($display_author),
	'author_functionality' => esc_attr($author_functionality),
	'author_position' => esc_attr($author_position),
	'color_overlay' => esc_attr($color_overlay),
	'color_overlay_opacity' => esc_attr($color_overlay_opacity),
	'color_overlay_hover_opacity' => esc_attr($color_overlay_hover_opacity),
	'card_bg_color' => esc_attr($card_bg_color),
	'grid_style' => esc_attr($grid_style),
	'hover_effect' => esc_attr($hover_effect),
	'post_title_overlay' => esc_attr($post_title_overlay),
	'heading_tag' => esc_attr($heading_tag),
	'enable_gallery_lightbox' => esc_attr($enable_gallery_lightbox),
	'category_style' => esc_attr($category_style),
	'overlay_secondary_project_image' => esc_attr($overlay_secondary_project_image),
	'vertical_list_hover_effect' => esc_attr($vertical_list_hover_effect),
	'vertical_list_read_more' => esc_attr($vertical_list_read_more),
	'read_more_button' => esc_attr($read_more_button),
	'animation' => esc_attr($animation),
);

$el_query = array(
	'post_type' => esc_attr($post_type),
	'posts_per_page' => esc_attr($posts_per_page),
	'order' => esc_attr($order),
	'orderby' => esc_attr($orderby),
	'offset' => esc_attr($post_offset),
	'cpt_name' => esc_attr($cpt_name),
	'custom_query_tax' => esc_attr($custom_query_tax),
  	'ignore_sticky_posts' => esc_attr($ignore_sticky_posts),
); 

$css_class_arr = array('nectar-post-grid-wrap');

if( !empty($css_class_name) ) {
	array_push($css_class_arr, esc_attr($css_class_name));
}

if( !empty($text_color) ) {
	array_push($css_class_arr, 'text-color-'.esc_attr($text_color));
}

if( !empty($additional_meta_size) && 'default' != $additional_meta_size ) {
	array_push($css_class_arr, 'additional-meta-size-'.esc_attr($additional_meta_size));
} 

if( !empty($grid_item_spacing) ) {
	array_push($css_class_arr, 'spacing-'.esc_attr($grid_item_spacing));
}

if( 'yes' === $enable_sortable && $sortable_alignment !== 'default' ) {
	array_push($css_class_arr, 'nectar-post-grid-wrap--fl-'.esc_attr($sortable_alignment));
}

$el_css_class = implode(" ", $css_class_arr);

echo "<div class='".esc_attr($el_css_class)."' data-el-settings='".json_encode($el_settings)."' data-style='".esc_attr($grid_style)."' data-query='".json_encode($el_query)."' data-load-more-color='". esc_attr($button_color) ."' data-load-more-text='".esc_html__("Load More", "salient-core") ."'>";

// Sortable filters.
$cat_links_escaped = '';
$show_all_cats = false;
$cpt_tax_query = false;

if( empty($blog_category) ) {
	$blog_category = 'all';
}
if( empty($portfolio_category) ) {
	$portfolio_category = 'all';
}

if( 'post' === $post_type ) {
	
	$selected_cats_arr = explode(",", $blog_category);
	$blog_cat_list     = get_categories();
	
	if( in_array('all', $selected_cats_arr) ) {
		
		if( sizeof($selected_cats_arr) < 2 ) {
			$all_filters = '-1';
			$show_all_cats = true;
		} else {
			$all_filters = $blog_category;
		}
		
		$cat_links_escaped .= '<a href="#" class="active all-filter" data-total-count="'.esc_attr(nectar_post_grid_get_category_total($all_filters, 'post')).'" data-filter="'. esc_attr($all_filters) .'">'.esc_html__('All', 'salient-core').'</a>';
	} else {
		
		if( 'yes' === $enable_sortable) {
			// Only query for the first category to start.
			$blog_category = $selected_cats_arr[0];
		}
	}
	
	foreach ($blog_cat_list as $type) {

		if( in_array($type->slug, $selected_cats_arr) || true === $show_all_cats ) {
  		$cat_links_escaped .= '<a href="#" data-filter="'.esc_attr($type->slug).'" data-total-count="'.esc_attr(nectar_post_grid_get_category_total($type->slug, 'post')).'">'. esc_attr($type->name) .'</a>';
		}
	}
	
	
} else if( 'portfolio' === $post_type && !empty($portfolio_category) ) {
	
	$selected_cats_arr = explode(",", $portfolio_category);
	$project_cat_list  = get_terms( array(
	    'taxonomy' => 'project-type'
	) );
	
	if( in_array('all', $selected_cats_arr) ) {
		
		if( sizeof($selected_cats_arr) < 2 ) {
			$all_filters = '-1';
			$show_all_cats = true;
		} else {
			$all_filters = $portfolio_category;
		}
		
		$cat_links_escaped .= '<a href="#" class="active all-filter" data-filter="'.esc_attr($all_filters).'" data-total-count="'.esc_attr(nectar_post_grid_get_category_total($all_filters, 'portfolio')).'">'.esc_html__('All', 'salient-core').'</a>';
	} else {
		// Only query for the first category to start.
		if( 'yes' === $enable_sortable) {
			$portfolio_category = $selected_cats_arr[0];
		}
	}
	
	if( !is_wp_error($project_cat_list) ) { 
		foreach ($project_cat_list as $type) {

			if( in_array($type->slug, $selected_cats_arr) || true === $show_all_cats ) {
	  		$cat_links_escaped .= '<a href="#" data-filter="'.esc_attr($type->slug).'" data-total-count="'.esc_attr(nectar_post_grid_get_category_total($type->slug, 'portfolio')).'">'. esc_attr($type->name) .'</a>';
			}
		}
	}

} 
else if( 'custom' === $post_type && !empty($cpt_name) && !empty($custom_query_tax) ) {
	
	$nectar_taxonomies_types = get_taxonomies( array( 'public' => true ) );
	$terms = get_terms( array_keys( $nectar_taxonomies_types ), array(
		'hide_empty' => false,
		'include' => $custom_query_tax,
	) );
	

	$tax_queries = array();
	$tax_links_escaped = ''; // to be able to append after All link below.
	
	foreach ( $terms as $term ) {
		
		$term_tax_query = array(
			'taxonomy' => $term->taxonomy,
			'field' => 'id',
			'terms' => array( $term->term_id ),
			'relation' => 'IN',
		);
		
		$tax_links_escaped .= '<a href="#" data-filter="'.esc_attr($term->term_id).'" data-total-count="'.esc_attr( nectar_post_grid_get_category_total( $term->slug, esc_attr($cpt_name), $term_tax_query ) ).'">'. esc_attr($term->name) .'</a>';
		
		if ( ! isset( $tax_queries[ $term->taxonomy ] ) ) {
			
			$tax_queries[ $term->taxonomy ] = $term_tax_query;

		} else {

			if( 'yes' !== $enable_sortable || ('yes' === $enable_sortable && 'yes' === $cpt_all_filter) ) {
				$tax_queries[ $term->taxonomy ]['terms'][] = $term->term_id;
			}
			
		}

	}
	
	$cpt_tax_query = array_values( $tax_queries );
	$cpt_tax_query['relation'] = 'OR';
	
	// Create filter HTML.
	//// All link for total count.
	if( 'yes' !== $enable_sortable || ('yes' === $enable_sortable && 'yes' === $cpt_all_filter) ) {
		$cat_links_escaped .= '<a href="#" class="active all-filter" data-filter="'.esc_attr($custom_query_tax).'" data-total-count="'.esc_attr(nectar_post_grid_get_category_total($custom_query_tax, esc_attr($cpt_name), $cpt_tax_query)).'">'.esc_html__('All', 'salient-core').'</a>';
	}
	//// Individual Tax links.
	$cat_links_escaped .= $tax_links_escaped;
	
	
	// Sortable without all filter only queries first cat for starting display.
	if( 'yes' === $enable_sortable && 'yes' !== $cpt_all_filter ) {
		$cpt_tax_query = array( $cpt_tax_query[0] );
	}
	
}


// Sortable filter output.

$filter_css_class_arr = array('nectar-post-grid-filters');

if( 'yes' === $enable_sortable && in_array( $sortable_alignment, array('sidebar_left','sidebar_right')) ) {
	array_push($filter_css_class_arr, 'nectar-sticky-column-css');
}

$filter_el_css_class = implode(" ", $filter_css_class_arr);

if( !empty($cat_links_escaped) ) {
	echo '<div class="'.$filter_el_css_class.'" data-active-color="'.esc_attr($sortable_color).'" data-align="'.esc_attr($sortable_alignment).'" data-animation="'.esc_attr($animation).'" data-sortable="'.esc_attr($enable_sortable).'"><h4>'.esc_html__('Filter','salient-core').'</h4><div>'.$cat_links_escaped.'</div></div>';
}
	


// Grid output.
$data_attrs_escaped = NectarPostGrid::get_data_attributes($atts);

// Dynamic style classes.
if( function_exists('nectar_el_dynamic_classnames') ) {
	$dynamic_el_styles = nectar_el_dynamic_classnames('nectar_post_grid', $atts);
} else {
	$dynamic_el_styles = '';
}

$nectar_post_grid_class = 'nectar-post-grid';
if( 'carousel' === $display_type ) {
	$nectar_post_grid_class .= ' nectar-flickity';
}

echo '<div class="'.$nectar_post_grid_class.$dynamic_el_styles.'" '.$data_attrs_escaped.'>';

if( 'carousel' === $display_type ) {
	echo '<div class="flickity-viewport"><div class="flickity-slider">';
}
// Posts.
if( 'post' === $post_type ) {
  
  // In case only all was selected.
  if( 'all' === $blog_category ) {
    $blog_category = null;
  }
    
  $nectar_blog_arr = array(
    'posts_per_page' => $posts_per_page,
    'post_type'      => 'post',
    'order'          => $order,
    'orderby'        => $orderby,
    'offset'         => $post_offset,
    'category_name'  => $blog_category
  );

  $nectar_blog_arr = apply_filters( 'nectar_post_grid_query', $nectar_blog_arr );

  if( $ignore_sticky_posts == 'yes' ) {
    $nectar_blog_arr['ignore_sticky_posts'] = true;
  }
  
  $nectar_blog_el_query = new WP_Query( $nectar_blog_arr );
        
  if( $nectar_blog_el_query->have_posts() ) : while( $nectar_blog_el_query->have_posts() ) : $nectar_blog_el_query->the_post();
    
  	do_action('nectar_blog_post_grid_item_start', $atts, $nectar_blog_el_query);
    echo nectar_post_grid_item_markup($atts, $nectar_blog_el_query->current_post, 'regular');
	do_action('nectar_blog_post_grid_item_end', $atts, $nectar_blog_el_query);
  
  endwhile; endif; 
  
	wp_reset_query();


} //end blog post type 

else if( 'portfolio' === $post_type ) {
	
	// In case only all was selected.
	if( 'all' === $portfolio_category ) {
		$portfolio_category = null;
	}
	
	$portfolio_arr = array(
		'posts_per_page' => $posts_per_page,
		'post_type'      => 'portfolio',
		'post_status'    => 'publish',
		'order'          => $order,
		'orderby'        => $orderby,
		'project-type'   => $portfolio_category,
		'offset'         => $post_offset,
	);

  $portfolio_arr = apply_filters( 'nectar_post_grid_query', $portfolio_arr );

  if( $ignore_sticky_posts == 'yes' ) {
    $portfolio_arr['ignore_sticky_posts'] = true;
  }
	
	if( has_filter('salient_el_post_grid_portfolio_query') ) {
		$portfolio_arr = apply_filters('salient_el_post_grid_portfolio_query', $portfolio_arr);
	}
	
	$nectar_portfolio_el_query = new WP_Query( $portfolio_arr );
        
  if( $nectar_portfolio_el_query->have_posts() ) : while( $nectar_portfolio_el_query->have_posts() ) : $nectar_portfolio_el_query->the_post();
    
  	do_action('nectar_blog_post_grid_item_start', $atts, $nectar_portfolio_el_query);
    echo nectar_post_grid_item_markup($atts, $nectar_portfolio_el_query->current_post, 'regular');
	do_action('nectar_blog_post_grid_item_end', $atts, $nectar_portfolio_el_query);

  endwhile; endif; 
	
	wp_reset_query();
  
}// end product post type

// Custom Query.
if( 'custom' === $post_type && !empty($cpt_name) ) {

  $nectar_custom_query_arr = array(
	'post_type'      => $cpt_name,
    'posts_per_page' => $posts_per_page,
    'order'          => $order,
    'orderby'        => $orderby,
    'offset'         => $post_offset
  );

  $nectar_custom_query_arr = apply_filters( 'nectar_post_grid_query', $nectar_custom_query_arr );

  if( $ignore_sticky_posts == 'yes' ) {
    $nectar_custom_query_arr['ignore_sticky_posts'] = true;
  }
	
	if( !empty($custom_query_tax) && $cpt_tax_query ) {
		
			$nectar_custom_query_arr['tax_query'] = $cpt_tax_query;

	} // end not empty custom tax
	

  $nectar_custom_query = new WP_Query( $nectar_custom_query_arr );
        
  if( $nectar_custom_query->have_posts() ) : while( $nectar_custom_query->have_posts() ) : $nectar_custom_query->the_post();
  	
 	do_action('nectar_blog_post_grid_item_start', $atts, $nectar_custom_query);
    echo nectar_post_grid_item_markup($atts, $nectar_custom_query->current_post, 'regular' );
	do_action('nectar_blog_post_grid_item_end', $atts, $nectar_custom_query);

  endwhile; endif; 
  
	wp_reset_query();


} //end custom

if( 'carousel' === $display_type ) {
	echo '</div></div>';
}

echo '</div></div>';

?>