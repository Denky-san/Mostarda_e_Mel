<?php
/*
 * Name: Heading
 * Section: content
 * Description: Section title
 */

$default_options = array(
	'text'                 => 'An Awesome Title',
	'align'                => 'center',
	'block_background'     => '',
	'font_family'          => '',
	'font_size'            => '',
	'font_color'           => '',
	'font_weight'          => '',
	'block_padding_left'   => 15,
	'block_padding_right'  => 15,
	'block_padding_bottom' => 15,
	'block_padding_top'    => 15
);
$options = array_merge($default_options, $options);

$title_font_family = empty( $options['font_family'] ) ? $global_title_font_family : $options['font_family'];
$title_font_size   = empty( $options['font_size'] ) ? $global_title_font_size : $options['font_size'];
$title_font_color  = empty( $options['font_color'] ) ? $global_title_font_color : $options['font_color'];
$title_font_weight = empty( $options['font_weight'] ) ? $global_title_font_weight : $options['font_weight'];

if (!empty($options['schema'])) {
    if ($options['schema'] === 'dark') {
        $options['block_background'] = '#000000';
        $options['font_color'] = '#ffffff';
    }

    if ($options['schema'] === 'bright') {
        $options['block_background'] = '#ffffff';
        $options['font_color'] = '#444444';
    }

    if ($options['schema'] === 'red') {
        $options['block_background'] = '#c00000';
        $options['font_color'] = '#ffffff';
    }
}
?>

<style>
    .title {
        padding: 0;
        text-align: <?php echo $options['align'] ?>;
        font-size: <?php echo $title_font_size ?>px;
        font-family: <?php echo $title_font_family ?>;
        font-weight: <?php echo $title_font_weight ?>;
        color: <?php echo $title_font_color ?>;
        line-height: normal !important;
        letter-spacing: normal;
    }
</style>

<div inline-class="title">
    <?php echo $options['text'] ?>
</div>
