<?php
/**
 * Stories rail.
 *
 * @package Nova
 *
 * @var array $args ids, count, title.
 */

defined( 'ABSPATH' ) || exit;

$items = Nova_Story::items( $args['ids'] ?? '', (int) ( $args['count'] ?? 8 ) );
if ( ! $items ) {
	return;
}
echo Nova_Story::render( $items, $args['title'] ?? '' ); // phpcs:ignore WordPress.Security.EscapeOutput
