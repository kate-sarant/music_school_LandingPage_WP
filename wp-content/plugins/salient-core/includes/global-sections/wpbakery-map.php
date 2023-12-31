<?php
/**
 * Nectar Global Sections WPBakery Element Map
 *
 * @package Salient Core
 */

 // Exit if accessed directly
 if ( ! defined( 'ABSPATH' ) ) {
 	exit;
 }

 $global_sections_arr = array();
 

 $global_sections_query = get_posts(
   array(
     'posts_per_page' => -1,
     'post_status'    => 'publish',
     'ignore_sticky_posts' => true,
     'no_found_rows'  => true,
     'post_type'      => 'salient_g_sections'
   )
 );
 
 foreach( $global_sections_query as $section ) {
   if( property_exists( $section, 'post_title') && property_exists( $section, 'ID') ) {
     $global_sections_arr[$section->post_title] = $section->ID;
   }
 }
   
 
 
 return array(
 	"name" => esc_html__("Global Section",'salient-core'),
 	"base" => "nectar_global_section",
 	"icon" => "icon-wpb-global-section",
 	"allowed_container_element" => 'vc_row',
 	"category" => esc_html__('Query', 'salient-core'),
 	"description" => '',
 	"params" => array(
 		array(
 			"type" => "nectar_global_section_select",
 			"class" => "",
 			'save_always' => true,
      		'admin_label' => true,
 			"heading" => esc_html__('Global Section', 'salient-core'),
 			"param_name" => "id",
      		"value" => $global_sections_arr,
 			'description' => '',
		),
		array(
			'type' => 'checkbox',
			'heading' => esc_html__('Enable Display Conditions?', 'salient-core'),
			'param_name' => 'enable_display_conditions',
			'description' => esc_html__('Utilize the specified display conditions set within your global section to determine whether this section should display or not.', 'salient-core'),
			'edit_field_class' => 'vc_col-xs-12 salient-fancy-checkbox',
			'value' => array(esc_html__('Yes', 'salient-core') => 'yes'),
		),

 	),
  'js_view' => 'SalientGlobalSectionsView'
 );
