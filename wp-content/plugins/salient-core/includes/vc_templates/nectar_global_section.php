<?php

// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}

extract(shortcode_atts(array(
	"id" => "",
	'enable_display_conditions' => ''
), $atts));

if (!empty($id)) {

	$section_id = intval($id);
	$section_id = apply_filters('wpml_object_id', $section_id, 'post', true);

	if( $section_id === 0 ) {
		return;
	}
	
	$section_status = get_post_status($section_id);
	
	
	$allow_output = true;
	
	if ( $enable_display_conditions === 'yes' ) {
		$allow_output = Nectar_Global_Sections_Render::get_instance()->verify_conditional_display( $section_id );
	}

	if ( 'publish' === $section_status && $allow_output ) {

		$section_content = get_post_field('post_content', $section_id);

		if ($section_content) {

			$unneeded_tags = array(
				'<p>['    => '[',
				']</p>'   => ']',
				']<br />' => ']',
				']<br>'   => ']',
			);

			
			if( function_exists('do_blocks')) {
				$section_content = do_blocks($section_content);
			}
			$section_content = wptexturize( $section_content);
			$section_content = convert_smilies( $section_content );
			$section_content = wpautop( $section_content );
			$section_content = shortcode_unautop( $section_content );
			$section_content = wp_filter_content_tags( $section_content );
			$section_content = strtr($section_content, $unneeded_tags);

			$section_content = apply_filters('nectar_global_section_content_output', $section_content);

			echo do_shortcode($section_content);
		}

		/* Output dynamic CSS */
		if (class_exists('Vc_Base')) {

			$vc = new Vc_Base();

			if (is_home() || is_front_page()) {

				$post_custom_css = get_metadata('post', $section_id, '_wpb_post_custom_css', true);

				if (!empty($post_custom_css)) {
					$post_custom_css = wp_strip_all_tags($post_custom_css);
					echo '<style type="text/css" data-type="vc_custom-css">';
					echo $post_custom_css;
					echo '</style>';
				}
			} else {
				$vc->addPageCustomCss($section_id);
			}

			$vc->addShortcodesCustomCss($section_id);
		}
	}
}
