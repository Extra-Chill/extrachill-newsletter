<?php
/**
 * Recent Newsletters Sidebar Widget Template
 *
 * Displays recent newsletters in sidebar.
 *
 * @package ExtraChillNewsletter
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Switch to newsletter site to query newsletters
switch_to_blog( 9 );

$newsletter_args = array(
	'post_type' => 'newsletter',
	'posts_per_page' => 3,
	'post_status' => 'publish',
	'orderby' => 'date',
	'order' => 'DESC',
);

$newsletter_query = new WP_Query( $newsletter_args );

restore_current_blog();

if ( $newsletter_query->have_posts() ) : ?>
	<div class="sidebar-card recent-newsletters-widget">
		<h3 class="widget-title"><?php esc_html_e( 'Recent Newsletters', 'extrachill-newsletter' ); ?></h3>
		<ul class="recent-newsletters-list">
			<?php
			while ( $newsletter_query->have_posts() ) : $newsletter_query->the_post();
				// Already on newsletter site from query - get data directly
				$newsletter_id = get_the_ID();

				// Switch back to newsletter site for permalink generation
				switch_to_blog( 9 );
				$newsletter_permalink = get_permalink( $newsletter_id );
				$newsletter_title = get_the_title( $newsletter_id );
				$newsletter_date = get_the_date( '', $newsletter_id );
				restore_current_blog();
				?>
				<li>
					<a href="<?php echo esc_url( $newsletter_permalink ); ?>" class="newsletter-link">
						<strong><?php echo esc_html( $newsletter_title ); ?></strong>
						<span class="newsletter-date"><?php echo esc_html( $newsletter_date ); ?></span>
					</a>
				</li>
			<?php endwhile; ?>
		</ul>
		<a href="https://newsletter.extrachill.com" class="view-all-link">
			<?php esc_html_e( 'View All Newsletters', 'extrachill-newsletter' ); ?> →
		</a>
	</div>
	<?php
	wp_reset_postdata();
endif;
