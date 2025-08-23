<!DOCTYPE html>
<html lang="<?php echo Helpers::content_lang() ?? 'en'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <?php 
        Helpers::get_styles( 'head', true );
        Helpers::get_scripts( 'head', true );
    ?>
</head>
<body class="d-flex flex-column min-vh-100">
    <?php if(! empty( trim( $branding . $main_menu_html . $user_menu_html ) )) : ?>
    <header class="bg-primary text-center mt-auto">
        <a class="skip visually-hidden-focusable" href="#main">Skip to main content</a>
        <nav class="container navbar navbar-expand-lg">
            <?php echo $branding; ?>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target=".navbar-collapse" aria-controls="navbar-header" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <?php echo $main_menu_html; ?>
            <?php echo $user_menu_html; ?>
        </nav>
    </header>
    <?php endif; ?>
    <div class="container-fluid flex-grow-1 p-4">
        <!-- <div class="row justify-content-center"> -->
        <div class="row justify-content-center">
            <?php 
            // DEBUG
            // $sidebar_left = '<div class="card">Card<div>';
            // $sidebar_right = '<div class="card">Card<div>';
            ?>
            <main id="main" class="col-auto col-lg-8 col-xl-6 main-content">
                <h1 class="page-title"><?php echo $page_title; ?></h1>
                <div class="content text-start">
                    <?php echo $content; ?>
                </div>
            </main>
            <?php 
            $sidebar_left = $sidebar_left ?? '';
            if ( ! empty( $sidebar_left ) ) : 
            ?>
            <aside id="sidebar-left" class="col-lg-4 col-xl-3 sidebar">
                <div class="d-grid d-md-flex d-lg-grid gap-4">
                    <?php echo $sidebar_left; ?>
                </div>
            </aside>
            <?php endif; ?>
            <?php 
            $sidebar_right = $sidebar_right ?? '';
            if ( ! empty( $sidebar_right ) ) : 
            ?>
            <aside id="sidebar-right" class="col-lg-4 col-xl-3 sidebar">
                <div class="cards d-grid d-md-flex d-lg-grid gap-4">
                    <?php echo $sidebar_right; ?>
                </div>
            </aside>
            <?php endif; ?>
        </div>
    </div>
    <?php if(! empty( $footer . $footer_menu_html ) ) : ?>
    <footer class="bg-secondary text-center mt-auto">
        <nav class="container navbar navbar-expand-lg">
            <?php echo $footer; ?>
            <?php echo $footer_menu_html; ?>
        </nav>
    </footer>
    <?php endif; ?>
    <?php
        Helpers::get_scripts( 'footer', true );
        Helpers::get_styles( 'footer', true );
    ?>
</body>
</html>
