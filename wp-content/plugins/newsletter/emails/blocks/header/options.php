<?php

/* @var $options array contains all the options the current block we're ediging contains */
/* @var $controls NewsletterControls */
/* @var $controls NewsletterFields */
?>

<p>
    <?php echo sprintf( __( 'Company data can be globally set on <a href="%s" target="_blank">company info panel</a>.', 'newsletter' ), '?page=newsletter_main_info' ); ?>
</p>

<?php
$fields->select('layout', __('Layout', 'newsletter'), ['' => __('Default', 'newsletter'), 'logo' => __('Only the logo', 'newsletter')])
?>

<?php $fields->font( 'font', __( 'Text', 'newsletter' ), [
	'family_default' => true,
	'size_default'   => true,
	'weight_default' => true
] ) ?>

<?php $fields->block_commons() ?>
