<?php
add_filter( 'show_admin_bar', '__return_false' );

if ( isset( $_GET['name'] ) && ! empty( $_GET['name'] ) ) {
	W4OS3::enqueue_style( 'w4os-profile', 'v3/css/profile.css' );
	if ( W4OS_ENABLE_V3 ) {
		$avatar = new W4OS3_Avatar( $_GET['name'] );
		$page_title = $avatar->AvatarName;
		$content = $avatar->profile_page();
	} else {
		$content = w4os_profile_display( $_GET['name'] );
	}

	$classes = "w4os page-template-profile page wp-custom-logo wp-embed-responsive";
}
?><!doctype html>
<html <?php language_attributes(); ?>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>" w4os />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<?php wp_head(); ?>
</head>
<body class="<?php echo $classes; ?>">
	<?php wp_body_open(); ?>
	<div id="page" class="site">
		<div id="content" class="site-content">
			<div id="primary" class="content-area">
				<main id="main" class="site-main" role="main">
					<?php
					error_log( ' GET ' . print_r( $_GET, true ) );
					if( ! empty( $content ) ) {
						echo '<header class="entry-header alignwide">';
						echo '<h1 class="entry-title">' . $page_title . '</h1>';
						echo '</header><!-- .entry-header -->';
						echo '<div class="entry-content">';
						echo '<div class="w4os-dev content page-profile-viewer page">';
						echo $content;
						echo '</div>';
						echo '</div><!-- .entry-content -->';
					} else {
						/* Start the Loop */
						while ( have_posts() ) :
							the_post();
							?>
							<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
	
								<header class="entry-header alignwide">
									<?php the_title( '<h1 class="entry-title">', '</h1>' ); ?>
								</header><!-- .entry-header -->
	
								<div class="entry-content">
									<?php the_content(); ?>
								</div><!-- .entry-content -->
	
							</article><!-- #post-<?php the_ID(); ?> -->
							<?php
						endwhile; // End of the loop.
					}
					?>
				</div><!-- #main -->
			</div><!-- #primary -->
		</div><!-- #content -->
	</div><!-- #page -->

	<?php wp_footer(); ?>

</body>
</html>
