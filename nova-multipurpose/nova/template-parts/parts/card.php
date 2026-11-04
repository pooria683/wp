<?php
/**
 * Post card.
 *
 * @package Nova
 */

defined( 'ABSPATH' ) || exit;

$post_id = get_the_ID();
$style   = 'post' === get_post_type( $post_id ) ? 'vertical' : 'vertical';
nova_post_card( $post_id, $style );
