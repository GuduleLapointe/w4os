<?php
add_filter( 'show_admin_bar', '__return_false' );

$classes = 'w4os profile-viewer page-template-profile page';

if ( isset( $_GET['name'] ) && ! empty( $_GET['name'] ) ) {
	if ( W4OS_ENABLE_V3 ) {
		W4OS3::enqueue_style( 'w4os-profile', 'v3/css/profile.css' );
		$avatar      = new W4OS3_Avatar( $_GET['name'] );
		$page_title  = sprintf(
			__( '%s\'s flux', 'w4os' ),
			$avatar->AvatarName
		);
		$profile_url = $avatar->get_profile_url();
		$actions[]   = sprintf(
			'<a href="%s" class="page-title-action" target="_blank">%s</a>',
			$profile_url,
			__( 'Open profile page', 'w4os' )
		);
		$content     = $avatar->profile_page();
	} else {
		$user    = w4os_get_user_by_avatar_name( $_GET['name'] );
		$content = w4os_profile_display( $user->ID );

		$avatar     = new W4OS_Avatar( $user->ID );
		$page_title = ( empty( $avatar->AvatarName ) ) ? __( 'Avatar not found', 'w4os' ) : $avatar->AvatarName;
	}
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
					if ( ! empty( $content ) ) {
						echo '<header class="entry-header alignwide">';
						echo '<h1 class="entry-title wp-heading-inline">' . $page_title . '</h1>';
						echo empty( $actions ) ? '' : join( ' ', $actions );
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
