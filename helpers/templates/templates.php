<?php

$site_title = $site_title ?? 'OpenSimulator Helpers';
$page_title = $page_title ?? 'Unknown page';
$content = $content ?? 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nullam in dui mauris.';

$sidebar_left = $sidebar_left ?? '';
$sidebar_right = $sidebar_right ?? '';
$version = Helpers::get_version();

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


// $menus['user'] = array(
//     'userprofile' => array(
//         'url' => '/profile',
//         'label' => Helpers::display_name( _('Profile') ),
//         'icon' => [ 'OpenSim', 'user_icon' ],
//         'condition' => 'logged_in',
//         'children' => array(
//             'account' => array(
//                 'url' => '/account',
//                 'label' => _('Account Settings'),
//                 'icon' => 'sliders'
//             ),
//             'logout' => array(
//                 'url' => '?action=logout',
//                 'label' => _('Logout'),
//                 'icon' => 'box-arrow-right',
//             ),
//         ),
//     ),
//     'login' => array(
//         'url' => '/login',
//         'label' => 'Login',
//         'icon' => 'box-arrow-in-right',
//         'condition' => 'logged_out',
//     ),
// );

// $menus['footer'] = array(
//     'github' => array(
//         'url' => 'http://github.com/magicoli/opensim-helpers',
//         'label' => 'GitHub Repository',
//     ),
//     'w4os' => array(
//         'url' => 'https://w4os.org',
//         'label' => 'W4OS Project',
//     ),
// );
$menus = $menus ?? array(); // We don't use menu everywhere, so initialize it to avoid undefined variable errors

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
        if (isset($item['condition']) && ! Helpers::validate_condition($item['condition'])) {
            continue;
        }
        $item_id = "nav-$slug-$key";
        if (isset($item['children'])) {
            // Add 'dropdown-hover' class for hover functionality
            $html .= '<li class="nav-item dropdown dropdown-hover">';
            $html .= sprintf(
                '<a class="nav-link dropdown-toggle" href="%s" id="%s" role="button" aria-expanded="false">%s%s</a>',
                Helpers::sanitize_uri($item['url']),
                $item_id,
                Helpers::icon($item['icon']),
                strip_tags($item['label']),
            );

            $html .= '<ul class="dropdown-menu" aria-labelledby="navbarDropdown">';
            foreach ($item['children'] as $child_key => $child) {
                $child_id = "nav-$slug-$key-$child_key";
                $html .= sprintf( 
                    '<li><a class="dropdown-item" id="%s" href="%s">%s%s</a></li>',
                    $child_id,
                    Helpers::sanitize_uri($child['url']),
                    Helpers::icon($child['icon']),
                    strip_tags($child['label']),
                );
                '<li><a class="dropdown-item" href="' . do_not_sanitize($child['url']) . '">' . do_not_sanitize($child['label']) . '</a></li>';
            }
            $html .= '</ul>';
            $html .= '</li>';
        } else {
            $html .= '<li class="nav-item">';
            $html .= '<a class="nav-link" href="' . do_not_sanitize($item['url']) . '">' . do_not_sanitize($item['label']) . '</a>';
            $html .= '</li>';
        }
    }
    $html .= '</ul>';
    $html .= '</div>';

    return $html;
}

// $branding = $branding ?? '<a class="navbar-brand" href="#">' . do_not_sanitize($GLOBALS['site_title'] ?? 'OpenSimulator Helpers') . '</a>';
$branding = isset($branding) ? $branding : sprintf( '<a class="navbar-brand" href="%s">%s</a>', Helpers::get_home_url(), do_not_sanitize($site_title) );
$footer = isset($footer) ? $footer : sprintf( _('OpenSimulator Helpers %s'), $version );

// Generate HTML for each menu
$main_menu_html = format_menu( ($menus['main'] ?? null ), 'main' );
$user_menu_html = format_menu( $menus['user'] ?? null, 'user' );
$footer_menu_html = format_menu( $menus['footer'] ?? null, 'footer' );

Helpers::enqueue_style( 'bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css', array(), '5.3.3' );
Helpers::enqueue_style( 'bootstrap-icons', 'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css' );

Helpers::enqueue_script( 'boostrap-bundle', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js', array(), '5.3.3', true);
// Helpers::enqueue_script( 'bootstrap-masonry', 'https://cdn.jsdelivr.net/npm/bootstrap-masonry@1.0.0/dist/bootstrap-masonry.min.js', array( 'bootstrap' ), '1.0.0', true);

Helpers::enqueue_style( 'template-page', 'templates/template-page.css' );
Helpers::enqueue_script( 'template-page', 'templates/template-page.js' );

require( 'template-page.php' );
