<?php
/*
 * Name: Preheader
 * Section: header
 * Description: Preheader
 *
 */

/* @var $options array */
/* @var $wpdb wpdb */

$default_options = array(
    'view' => 'View online',
    'text' => 'Few words summary',
    'font_family' => '',
    'font_size' => 14,
    'font_color' => '',
    'font_weight' => '',
    'block_padding_left'=>15,
    'block_padding_right'=>15,
    'block_padding_bottom'=>15,
    'block_padding_top'=>15,
    'block_background' => '',
);

$options = array_merge($default_options, $options);

$text_font_family = empty( $options['font_family'] ) ? $global_text_font_family : $options['font_family'];
$text_font_size   = empty( $options['font_size'] ) ? $global_text_font_size : $options['font_size'];
$text_font_color  = empty( $options['font_color'] ) ? $global_text_font_color : $options['font_color'];
$text_font_weight = empty( $options['font_weight'] ) ? $global_text_font_weight : $options['font_weight'];

?>
<style>
    .preheader-table {
        width: 100%!important
        border: 0;
        border-collapse: collapse;
    }
    .preheader-link {
        font-family: <?php echo $text_font_family ?>;
        font-size: <?php echo $text_font_size ?>px;
        font-weight: <?php echo $text_font_weight ?>;
        color: <?php echo $text_font_color ?>;
        padding: 10px;
    }
    .preheader-view-link {
        font-family: <?php echo $text_font_family ?>;
        font-size: <?php echo $text_font_size ?>px;
        font-weight: <?php echo $text_font_weight ?>;
        color: <?php echo $text_font_color ?>;
        text-decoration: none;
    }
</style>

<table width="100%" border="0" cellpadding="0" align="center" cellspacing="0" inline-class="preheader-table">
    <tr>
        <td class="preheader-link" width="50%" valign="top" align="left">
            <?php echo $options['text'] ?>
        </td>
        <td class="preheader-link" width="50%" valign="top" align="right">
            <a href="{email_url}" target="_blank" rel="noopener" class="preheader-view-link"><?php echo $options['view'] ?></a>
        </td>
    </tr>
</table>

