<?php
/**
 * Shared header template.
 *
 * @package bt-hair-salon
 */
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<?php
$body_classes = array();

if ( function_exists( 'bt_hair_is_dashboard_page' ) && bt_hair_is_dashboard_page() ) {
	$body_classes[] = 'bt-hair-dashboard-page';
} elseif ( function_exists( 'bt_hair_is_sign_in_page' ) && bt_hair_is_sign_in_page() ) {
	$body_classes[] = 'bt-hair-sign-in-page';
} else {
	$body_classes[] = 'bt-hair-public';
}
?>
<body <?php body_class( $body_classes ); ?>>
<?php wp_body_open(); ?>



