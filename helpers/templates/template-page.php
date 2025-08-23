<!DOCTYPE html>
<html lang="<?php echo OpenSim::content_lang() ?? 'en'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script src="https://cdn.jsdelivr.net/npm/masonry-layout@4.2.2/dist/masonry.pkgd.min.js" integrity="sha384-GNFwBvfVxBkLMJpYMOABq3c+d3KnQxudP/mGPkzpZSTYykLBNsZEnG2D9G/X/+7D" crossorigin="anonymous" async></script>
    <?php 
        OpenSim::get_styles( 'head', true );
        OpenSim::get_scripts( 'head', true );
    ?>
</head>
<body class="d-flex flex-column min-vh-100">
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
    <div class="container-fluid flex-grow-1 p-4">
        <!-- <div class="row justify-content-center"> -->
        <div class="row justify-content-center">
            <?php 
            // DEBUG
            // $sidebar_left = '<div class="card">Card<div>';
            // $sidebar_right = '<div class="card">Card<div>';
            ?>
            <main id="main" class="col-lg-auto">
                <h1 class="page-title"><?php echo $page_title; ?></h1>
                <div class="content text-start">
                    <?php echo $content; ?>
                </div>
            </main>
            <?php 
            $sidebar_left = $page->get_sidebar('left');
            if ( ! empty( $sidebar_left ) ) : 
            ?>
            <aside id="sidebar-left" class="col-lg-4 col-xl-3 sidebar">
                <div class="d-grid d-md-flex d-lg-grid gap-4">
                    <?php echo $sidebar_left; ?>
            </aside>
            <?php endif; ?>
            <?php 
            $sidebar_right = $page->get_sidebar('right');
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
    <footer class="bg-secondary text-center mt-auto">
        <nav class="container navbar navbar-expand-lg">
            <?php echo $footer; ?>
            <?php echo $footer_menu_html; ?>
        </nav>
    </footer>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <?php
        OpenSim::get_scripts( 'footer', true );
        OpenSim::get_styles( 'footer', true );
    ?>
</body>
</html>
