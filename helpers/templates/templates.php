<?php

$site_title = $site_title ?? 'OpenSimulator Helpers';

$page_title = $page_title ?? $page->get_page_title() ?? 'Unknown page';
$content = $content ?? $page->get_content() ?? 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nullam in dui mauris.';
$sidebar_left = $sidebar_left ?? $page->get_sidebar('left');
$sidebar_right = $sidebar_right ?? $page->get_sidebar('right');

$version = OpenSim::get_version();
$footer = $footer ?? sprintf( _('OpenSimulator Helpers %s'), $version );

// $menus['main'] = $menu ?? array(
//     'home' => array(
//         'url' => '/',
//         'label' => 'Home',
//     ),
//     'about' => array(
//         'url' => '/about',
//         'label' => 'About',
//     ),
// );

$menus['user'] = array(
    'userprofile' => array(
        'url' => '/profile',
        'label' => OpenSim::display_name( _('Profile') ),
        'icon' => [ 'OpenSim', 'user_icon' ],
        'condition' => 'logged_in',
        'children' => array(
            'account' => array(
                'url' => '/account',
                'label' => _('Account Settings'),
                'icon' => 'sliders'
            ),
            'logout' => array(
                'url' => '?action=logout',
                'label' => _('Logout'),
                'icon' => 'box-arrow-right',
            ),
        ),
    ),
    'login' => array(
        'url' => '/login',
        'label' => 'Login',
        'icon' => 'box-arrow-in-right',
        'condition' => 'logged_out',
    ),
);

$menus['footer'] = array(
    'github' => array(
        'url' => 'http://github.com/magicoli/opensim-helpers',
        'label' => 'GitHub Repository',
    ),
    'w4os' => array(
        'url' => 'https://w4os.org',
        'label' => 'W4OS Project',
    ),
);

/**
 * Build HTML menu using Bootstrap styles
 */
function format_menu( $menu, $slug = 'main', $class = '' ) {
    $id = "navbar-$slug";
    $html = sprintf(
        '<div class="collapse navbar-collapse" id="%s">',
        $id
    );
    $ul_class = "navbar-nav navbar-$slug ms-auto";
    $html .= sprintf(
        '<ul class="%s">',
        $ul_class
    );
    if( ! is_array( $menu ) ) {
        return '';
    }
    foreach ($menu as $key => $item) {
        if (isset($item['condition']) && ! OpenSim::validate_condition($item['condition'])) {
            continue;
        }
        $item_id = "nav-$slug-$key";
        if (isset($item['children'])) {
            // Add 'dropdown-hover' class for hover functionality
            $html .= '<li class="nav-item dropdown dropdown-hover">';
            $html .= sprintf(
                '<a class="nav-link dropdown-toggle" href="%s" id="%s" role="button" aria-expanded="false">%s%s</a>',
                OpenSim::sanitize_url($item['url']),
                $item_id,
                OpenSim::icon($item['icon']),
                strip_tags($item['label']),
            );

            $html .= '<ul class="dropdown-menu" aria-labelledby="navbarDropdown">';
            foreach ($item['children'] as $child_key => $child) {
                $child_id = "nav-$slug-$key-$child_key";
                $html .= sprintf( 
                    '<li><a class="dropdown-item" id="%s" href="%s">%s%s</a></li>',
                    $child_id,
                    OpenSim::sanitize_url($child['url']),
                    OpenSim::icon($child['icon']),
                    strip_tags($child['label']),
                );
                '<li><a class="dropdown-item" href="' . htmlspecialchars($child['url']) . '">' . htmlspecialchars($child['label']) . '</a></li>';
            }
            $html .= '</ul>';
            $html .= '</li>';
        } else {
            $html .= '<li class="nav-item">';
            $html .= '<a class="nav-link" href="' . htmlspecialchars($item['url']) . '">' . htmlspecialchars($item['label']) . '</a>';
            $html .= '</li>';
        }
    }
    $html .= '</ul>';
    $html .= '</div>';

    return $html;
}

$branding = '<a class="navbar-brand" href="#">' . htmlspecialchars($GLOBALS['site_title']) . '</a>';

// Generate HTML for each menu
$main_menu_html = format_menu( ($menus['main'] ?? null ), 'main' );
$user_menu_html = format_menu( $menus['user'], 'user' );
$footer_menu_html = format_menu( $menus['footer'], 'footer' );

OpenSim::enqueue_script( 'template-page', 'templates/bootstrap.js' );
OpenSim::enqueue_style( 'template-page', 'templates/bootstrap.css' );

require( 'template-page.php' );
