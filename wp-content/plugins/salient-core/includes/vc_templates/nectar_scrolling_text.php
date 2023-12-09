<?php 

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

wp_enqueue_style( 'nectar-element-scrolling-text' );

extract(shortcode_atts(array(
  'style' => 'default',
  'scroll_direction' => 'left',
  'scroll_speed' => 'slow',
  'move_on_scroll_animation' => '',
	'outline_thickness' => 'thin',
  'outline_applies_to' => '',
	'text_color' => '',
  'custom_font_size' => '',
  'custom_font_size_mobile' => '',
  'background_image_url' => '',
  'background_image_height' => '30vh',
  'background_image_animation' => 'none',
  'separate_text_coloring' => '',
  'text_color_front' => '#fff',
	'text_repeat_number' => '3',
	'text_repeat_divider' => 'none',
	'text_repeat_divider_custom' => '',
  'text_repeat_divider_custom_color' => '',
  'text_repeat_divider_custom_spin_animation' => '',
	'text_repeat_divider_scale' => 'full',
	'overflow' => 'hidden'
), $atts));

$content = wp_kses_post($content);

// Divider.
$divider_spacing = 'false';

// Handle multiple headings.
$has_multiple_tags = '';
$tag_pattern = "/<h[1-6]>.*?<\/h[1-6]>/"; 
$tag_count = preg_match_all($tag_pattern, $content);
if($tag_count > 1) {
  $has_multiple_tags = ' has-multiple-items';
}
$content = '<div class="nectar-scrolling-text-inner__text-chunk'.$has_multiple_tags.'">'.$content.'</div>';

// Space or element between items.
if( 'space' === $text_repeat_divider ) {
	$divider_spacing = 'true';
} else if( 'custom' === $text_repeat_divider ) {
  $custom_class_names = 'custom';
  $custom_divider_attrs = '';

  if( $text_repeat_divider_custom_spin_animation === 'yes' ) {
    $custom_class_names .= ' spin';
    $animation_atts = array(
      'animation_type' => 'scroll_pos_advanced',
      'animation_trigger_offset' => '0,100',
      'animation_start_rotate' => '0',
      'animation_end_rotate' => ( $scroll_direction === 'rtl' ) ? '360' : '-360',
      'persist_animation_on_mobile' => 'true',
      'animation_inner_selector' => ''
    );
    $animations = new NectarAnimations($animation_atts);
    $custom_divider_attrs = ' data-persist-animation data-nectar-animate-settings="'.esc_attr($animations->json).'" data-advanced-animation="true"';

  }
  $outline_em_o = '';
  $outline_em_c = '';
  if ( $outline_applies_to === 'both' ) {
    $outline_em_o = '<em>';
    $outline_em_c = '</em>';
  }
  $color_style = ( !empty($text_repeat_divider_custom_color) ) ? ' style="color:'.esc_attr($text_repeat_divider_custom_color).';"' : ''; 
	$content = preg_replace('/(<\/h[1-6]>)/','<span class="'.esc_attr($custom_class_names).'" data-scale="'.esc_attr($text_repeat_divider_scale).'"'.$color_style.'><span'.$custom_divider_attrs.'>'.$outline_em_o.esc_html($text_repeat_divider_custom).$outline_em_c.'</span></span>${1}',$content);
} else {
	$content = preg_replace('/(<\/h[1-6]>)/','<span>&nbsp;</span>${1}',$content);
}

// Inner attrs.
$inner_attrs = 'class="nectar-scrolling-text-inner"';
// Move on scroll animation.
if ( $move_on_scroll_animation === 'yes' ) {
  $animation_atts = array(
    'animation_type' => 'scroll_pos_advanced',
    'animation_trigger_offset' => '0,100',
    'animation_start_translate_x' => '0',
    'animation_end_translate_x' => ( $scroll_direction === 'rtl' ) ? '25%' : '-25%',
    'persist_animation_on_mobile' => 'true',
    'animation_inner_selector' => ''
  );
  $inner_animations = new NectarAnimations($animation_atts);
  $inner_attrs .= ' data-persist-animation data-nectar-animate-settings="'.esc_attr($inner_animations->json).'" data-advanced-animation="true"';
}

$inner_content = '';
$text_repeat_number_int = intval($text_repeat_number);

// Text Repeats.
for( $i = 0; $i < $text_repeat_number_int; $i++ ) {
	$inner_content .= $content;
}

// Background Layer.
$background_markup = false;
$background_style = 'style="';

if( !empty($background_image_url) ) {
	
  // Image.
	if( !preg_match('/^\d+$/',$background_image_url) ) {   
	   $background_style .= 'height:'.esc_attr($background_image_height).'; background-image: url('.esc_url($background_image_url) . ');';
  } else {
    
		$bg_image_src = wp_get_attachment_image_src($background_image_url, 'full');
		$background_style .= 'height:'.esc_attr($background_image_height).'; background-image: url(\''.esc_url($bg_image_src[0]).'\'); ';
	}
  
  
  if( 'true' === $separate_text_coloring ) {
    $front_text_later = '<div class="nectar-scrolling-text-inner" style="color:'.esc_attr($text_color_front).'">'.$inner_content.'</div>';
  }

  
  $background_style .= '"';
  
  $background_markup = '<div class="background-layer row-bg-wrap" data-bg-animation="'.esc_attr($background_image_animation).'"><div class="inner row-bg"><div class="background-image" '.$background_style.'></div></div>'.$front_text_later.'</div>';
	
}

// Dynamic style classes.
if( function_exists('nectar_el_dynamic_classnames') ) {
	$dynamic_el_styles = nectar_el_dynamic_classnames('nectar_scrolling_text', $atts);
} else {
	$dynamic_el_styles = '';
}

$style_markup = '';
if( !empty($text_color) ) {
  $style_markup = ' style="color: '.esc_attr($text_color).';"';
} 

$data_attrs_escaped = 'data-style="'.esc_attr($style).'" ';
$data_attrs_escaped .= 'data-s-dir="'.esc_attr($scroll_direction).'" ';
$data_attrs_escaped .= 'data-spacing="'.esc_attr($divider_spacing).'" ';
$data_attrs_escaped .= 'data-outline-thickness="'.esc_attr($outline_thickness).'" ';
$data_attrs_escaped .= 'data-s-speed="'.esc_attr($scroll_speed).'" ';
$data_attrs_escaped .= 'data-overflow="'.esc_attr($overflow).'" ';

if( false !== $background_markup) {
  $data_attrs_escaped .= 'data-sep-text="'.esc_attr($separate_text_coloring).'" ';
  $data_attrs_escaped .= 'data-using-bg="true"';
}

echo '<div class="nectar-scrolling-text'.$dynamic_el_styles.'" '.$data_attrs_escaped.'>'.$background_markup.'<div '.$inner_attrs.$style_markup.'>' . do_shortcode($inner_content) . '</div></div>';

?>