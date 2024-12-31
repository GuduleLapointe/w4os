<?php
add_filter( 'show_admin_bar', '__return_false' );
?><!doctype html>
<html <?php language_attributes(); ?>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
	<?php wp_body_open(); ?>
	<div id="page" class="site">
		<div id="content" class="site-content">
			<div id="primary" class="content-area">
				<main id="main" class="site-main" role="main">
                    <header class="entry-header alignwide">
                        <?php the_title( '<h1 class="entry-title">', '</h1>' ); ?>
                    </header><!-- .entry-header -->

                    <?php
                    if ( W4OS_ENABLE_V3 ) {
                        if( isset( $_GET['name'] ) && ! empty( $_GET['name'] ) ) {
                            $avatar = new W4OS3_Avatar( $_GET['name'] );
                            $avatar->profile_page( true );
                        } else {
                            echo w4os_profile_display( $_GET['name'] );
                        }
                    } else {
                        echo w4os_profile_display( $_GET['name'] );
                    }
                    ?>
				</div><!-- #main -->
			</div><!-- #primary -->
		</div><!-- #content -->
	</div><!-- #page -->

	<?php wp_footer(); ?>

</body>
</html>
