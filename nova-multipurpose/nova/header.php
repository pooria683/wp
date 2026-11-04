<?php
/**
 * Main header wrapper.
 *
 * @package Nova
 */

defined( 'ABSPATH' ) || exit;
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<div id="nv-page" class="nv-page">
	<?php Nova_Header::render(); ?>
	<main id="nv-main" class="nv-main">
