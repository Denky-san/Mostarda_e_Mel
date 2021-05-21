<?php

/*
 * Name: Hero
 * Section: content
 * Description: Image, title, text and call to action all in one
 */

/* @var $options array */
/* @var $wpdb wpdb */

$defaults = array(
    'title' => 'An Awesome Title',
    'text' => 'This is just a simple text you should change',
    'font_family' => '',
    'font_size' => '',
    'font_weight' => '',
    'font_color' => '',
    'title_font_family' => '',
    'title_font_size' => '',
    'title_font_weight' => '',
    'title_font_color' => '',
    'layout' => 'full',
    'button_url'         => '',
    'button_label'       => __( 'Click Here', 'newsletter' ),
    'button_background'  => '',
    'button_font_color'  => '',
    'button_font_family' => '',
    'button_font_size'   => '',
    'button_font_weight' => '',
    'block_padding_top' => 30,
    'block_padding_bottom' => 30,
    'block_padding_left' => 0,
    'block_padding_right' => 0,
    'block_background' => '',
);

$options = array_merge($defaults, $options);

if (!empty($options['schema'])) {
    if ($options['schema'] === 'dark') {
        $options['block_background'] = '#000000';
        $options['title_font_color'] = '#ffffff';
        $options['font_color'] = '#ffffff';
        $options['button_font_color'] = '#ffffff';
        $options['button_background'] = '#96969C';
    }

    if ($options['schema'] === 'bright') {
        $options['block_background'] = '#ffffff';
        $options['title_font_color'] = '#000000';
        $options['font_color'] = '#000000';
        $options['button_font_color'] = '#ffffff';
        $options['button_background'] = '#256F9C';
    }
}

$layout = $options['layout'];

if ($layout == 'full') {
    $options = array_merge(array('block_padding_left' => 0, 'block_padding_right' => 0), $options);
} else {
    $options = array_merge(array('block_padding_left' => 15, 'block_padding_right' => 15), $options);
}

$title_font_family = empty( $options['title_font_family'] ) ? $global_title_font_family : $options['title_font_family'];
$title_font_size   = empty( $options['title_font_size'] ) ? $global_title_font_size : $options['title_font_size'];
$title_font_color  = empty( $options['title_font_color'] ) ? $global_title_font_color : $options['title_font_color'];
$title_font_weight = empty( $options['title_font_weight'] ) ? $global_title_font_weight : $options['title_font_weight'];

$text_font_family = empty( $options['font_family'] ) ? $global_text_font_family : $options['font_family'];
$text_font_size   = empty( $options['font_size'] ) ? $global_text_font_size : $options['font_size'];
$text_font_color  = empty( $options['font_color'] ) ? $global_text_font_color : $options['font_color'];
$text_font_weight = empty( $options['font_weight'] ) ? $global_text_font_weight : $options['font_weight'];

$layout = $options['layout'];

$button_options = $options;
$button_options['button_font_family'] = empty( $options['button_font_family'] ) ? $global_button_font_family : $options['button_font_family'];
$button_options['button_font_size']   = empty( $options['button_font_size'] ) ? $global_button_font_size : $options['button_font_size'];
$button_options['button_font_color']  = empty( $options['button_font_color'] ) ? $global_button_font_color : $options['button_font_color'];
$button_options['button_font_weight'] = empty( $options['button_font_weight'] ) ? $global_button_font_weight : $options['button_font_weight'];
$button_options['button_background']  = empty( $options['button_background'] ) ? $global_button_background_color : $options['button_background'];

if (!empty($options['image']['id'])) {
    if ($layout == 'full') {
        $media = tnp_resize_2x($options['image']['id'], array(600, 0));
        if ($media) {
            $media->set_width(600 - $options['block_padding_left'] - $options['block_padding_right']);
        }
    } else {

        $media = tnp_resize_2x($options['image']['id'], array(300, 0));
        if ($media) {
            $media->set_width(300 - $options['block_padding_left']);
        }
    }
    if ($media) {
        if (!empty($options['image_alt'])) {
            $media->alt = $options['image_alt'];
        } else if (!empty($options['title'])) {
            $media->alt = $options['title'];
        } else {
            $alt_texts = array('picture', 'image', 'pic', 'photo');
            $media->alt = $alt_texts[array_rand($alt_texts)];
        }
        $media->link = $options['button_url'];
    }
} else {
    $media = false;
}

switch ($layout) {
    case 'left':
        include __DIR__ . '/block-left.php';
        return;
    case 'right':
        include __DIR__ . '/block-right.php';
        return;
    case 'full':
        include __DIR__ . '/block-full.php';
        return;
}
