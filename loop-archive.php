<header class="archive-title">
	<?php sds_archive_title(); ?>
</header>
<?php
	// Loop through posts
	if ( have_posts() ) :
		while ( have_posts() ) : the_post();
?>
	<section id="post-<?php the_ID(); ?>" <?php post_class( 'post cf' ); ?>>

		<article class="post-content">
			<section class="post-title-wrap cf <?php echo ( has_post_thumbnail() ) ? 'post-title-wrap-featured-image' : 'post-title-wrap-no-image'; ?>">
				<?php if ( $post->post_type === 'post' ) : ?>
					<p class="post-date">
						<?php
							if ( strlen( get_the_title() ) > 0 ) :
								the_time( 'F j, Y' );
							else: // No title
						?>
							<a href="<?php the_permalink(); ?>"><?php the_time( 'F j, Y' ); ?></a>
						<?php
							endif;
						?>
					</p>
				<?php endif; ?>

				<h1 class="post-title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h1>
			</section>

			<?php sds_featured_image( true ); ?>

			<?php
				// Show the excerpt if the post has one
				if ( has_excerpt() )
					the_excerpt();
				else
					the_content();
			?>
		</article>
		
		<a href="<?php the_permalink(); ?>" class="more-link post-button"><?php _e( 'Continue Reading', 'modern-business' ); ?></a>
	</section>
<?php
		endwhile;
	else: // No Posts
?>
	<section class="no-results no-posts no-archive-results post">
		<?php sds_no_posts(); ?>
	</section>
<?php endif; ?>