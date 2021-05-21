<?php
/*
 * Name: Footer
 * Section: footer
 * Description: View online ad profile links
 */

$default_options = array(
    'view' => 'View online',
    'profile' => 'Modify your subscription',
    'font_family' => '',
    'font_size' => 14,
    'font_color' => '',
    'font_weight' => '',
    'block_padding_left' => 15,
    'block_padding_right' => 15,
    'block_padding_bottom' => 15,
    'block_padding_top' => 15,
    'block_background' => '',
    'url' => 'profile'
);
$options = array_merge($default_options, $options);

$text_font_family = empty( $options['font_family'] ) ? $global_text_font_family : $options['font_family'];
$text_font_size   = empty( $options['font_size'] ) ? $global_text_font_size : $options['font_size'];
$text_font_color  = empty( $options['font_color'] ) ? $global_text_font_color : $options['font_color'];
$text_font_weight = empty( $options['font_weight'] ) ? $global_text_font_weight : $options['font_weight'];

?>
<style>
    .footer-text {
        font-family: <?php echo $text_font_family ?>;
        font-size: <?php echo $text_font_size ?>px;
        font-weight: <?php echo $text_font_weight ?>;
        color: <?php echo $text_font_color ?>;
        text-decoration: none;
    }
</style>

<a inline-class="footer-text" href="<?php if ($options['url'] == 'unsubscription') echo '{unsubscription_url}'; else echo '{profile_url}' ?>" target="_blank"><?php echo esc_html($options['profile']) ?></a>

<span inline-class="footer-text">&nbsp;&nbsp;&nbsp;|&nbsp;&nbsp;&nbsp;</span>

<a inline-class="footer-text" href="{email_url}" target="_blank"><?php echo esc_html($options['view']) ?></a>

