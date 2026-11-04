<?php
/**
 * Comments.
 *
 * @package Nova
 */

defined( 'ABSPATH' ) || exit;

if ( post_password_required() ) {
	return;
}
?>
<section id="comments" class="nv-comments">
	<?php if ( have_comments() ) : ?>
		<h2 class="nv-comments__title">
			<?php printf( esc_html__( '%s نظر', 'nova' ), number_format_i18n( get_comments_number() ) ); ?>
		</h2>
		<ol class="nv-comments__list">
			<?php
			wp_list_comments(
				array(
					'style'       => 'ol',
					'avatar_size' => 56,
					'short_ping'  => true,
				)
			);
			?>
		</ol>
		<?php the_comments_pagination(); ?>
	<?php endif; ?>

	<?php
	comment_form(
		array(
			'title_reply'        => __( 'نظر شما', 'nova' ),
			'class_submit'       => 'nv-btn nv-btn--primary',
			'comment_notes_before' => '<p class="nv-muted nv-small">' . esc_html__( 'ایمیل شما منتشر نمی‌شود.', 'nova' ) . '</p>',
		)
	);
	?>
</section>
