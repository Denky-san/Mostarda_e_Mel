<?php
/* @var $fields NewsletterFields */
?>


<?php $fields->text('view', __('View online label','newsletter')) ?>
<?php $fields->text('profile', __('Subscription details label','newsletter')) ?>

<?php $fields->select('url', '', array('profile'=>__('Use profile link','newsletter'), 'unsubscription' => __('Use unsubscription link','newsletter'))) ?>

<?php $fields->font( 'font', __( 'Text', 'newsletter' ), [
	'family_default' => true,
	'size_default'   => true,
	'weight_default' => true
] ) ?>

<?php $fields->block_commons() ?>
