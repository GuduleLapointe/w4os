<?php
/**
 * Userless Authentication
 * 
 * Proof of concept. Not ready to use in production.
 * 
 * Will allow to authenticate OpenSimulator avatars without
 * requirint the creation of a corresponding WordPress user.
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class UserlessAuth {
    public static $user = null;
    private $login_page;

    private $session_timeout = 1800; // 30 minutes timeout

    public function __construct() {
    }
    
    public function init() {
        add_shortcode('custom_auth_form', [$this, 'login_form_shortcode']);
        add_shortcode('account_info', [$this, 'account_shortcode']);
        add_action('init', [$this, 'start_session']);
        add_filter( 'show_admin_bar', [ $this, 'show_admin_bar' ] );

        // Change default logout URL for temporary users
        add_filter( 'logout_url', [$this, 'logout_url'], 99, 2);
        add_filter( 'wp_logout', [$this, 'wp_logout'] );
    }

    /**
     * Find login page by looking for a page with the shortcode [custom_auth_form]
     */
    public function get_login_page() {
        if (!empty($this->login_page)) {
            return $this->login_page;
        }
        $pages = get_pages();
        foreach ($pages as $page) {
            if (has_shortcode($page->post_content, 'custom_auth_form')) {
                $this->login_page = get_permalink($page->ID);
                return $this->login_page;
            }
        }
        // Return default wordpress login page if not found
        return wp_login_url();
    }
    
    public function start_session() {
        $this->get_login_page();

        if (!session_id()) {
            session_start();
        }

        // Handle session timeout. Disabled because it conflicts with WordPress real users.
        // if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $this->session_timeout)) {
        //     $this->logout();
        // }
        $_SESSION['last_activity'] = time();
        $user = $this->get_temporary_user();
        if ($this->is_authenticated() && !is_user_logged_in()) {
            // Automatically log in temporary user
            wp_set_current_user($user->ID, $user->user_login);
            $GLOBALS['current_user'] = $user;
            $user->avatars = W4OS3_Avatar::get_avatars_by_email($user->user_email);

            // Set user properties subject to change during session
        }
        self::$user = $user;
    }

    public function account_shortcode() {
        if ($this->is_authenticated()) {
            $current_user = wp_get_current_user();
            $content = array(
                'Welcome ' . esc_html($current_user->display_name),
            );
            $content = '<p>' . implode('</p><p>', $content) . '</p>';
            // Add logout link as a link, not a form
            $content .= sprintf(
                '<a href="%s">%s</a>',
                wp_logout_url(),
                wp_logout_url(),
            );
            return $content;
        // } else {
        //     return '<p>You are not logged in.</p>';
        }
    }

    public function login_form_shortcode() {
        // Process logout
        if (isset($_REQUEST['logout']) && wp_verify_nonce($_REQUEST['_wpnonce'], 'logout_action')) {
            $this->logout();
        }

        // Process login
        if (isset($_POST['submit']) && wp_verify_nonce($_POST['_wpnonce'], 'login_action')) {
            $this->login();
        }

        if ($this->is_authenticated()) {
            return '<form method="post">' . wp_nonce_field('logout_action') . '<button type="submit" name="logout">Logout</button></form>'
            . $this->account_shortcode() ;
        }

        // Display login form
        return '<form method="post" class="w4os-login-form login-form" id="w4os-login-form" style="display:flex;gap:20px;flex-wrap:wrap;">
                    ' . wp_nonce_field('login_action') . '
                    <p class="login-firstname">
                        <label for="first_name">First Name:</label>
                        <input type="text" name="first_name" value="John" required pattern="[A-Za-z][A-Za-z0-9]*" title="First name must start with a letter and contain only letters and numbers" maxlength="32">
                    </p>
                    <p class="login-lastname">
                        <label for="last_name">Last Name:</label>
                        <input type="text" name="last_name" value="Doe" class="input" required pattern="[A-Za-z][A-Za-z0-9]*" title="Last name must start with a letter and contain only letters and numbers" maxlength="32">
                    </p>
                    <p class="login-password">
                        <label for="password">Password:</label>
                        <input type="password" name="password" value="test123" class="input" required>
                    </p>
                    <p>
                        <button type="submit" name="submit">Login</button>
                    <p>
                </form>
                <script src="' . W4OS_PLUGIN_DIR_URL . 'assets/js/avatar-name-validation.js"></script>';
    }

    private function login() {
        // In final implementation, we should either
        // - not sanitizing value before checking against database
        // - sanitizing both posted value and database value
        // But it will probably not be a concern with external authentication
        $first_name = sanitize_text_field($_POST['first_name']);
        $last_name = sanitize_text_field($_POST['last_name']);
        $password = sanitize_text_field($_POST['password']);

        // Debug authentication
        // In final implementation, we will use an external authentication process.
        if ($first_name === 'John' && $last_name === 'Doe' && $password === 'test123') {
            $_SESSION['first_name'] = $first_name;
            $_SESSION['last_name'] = $last_name;
            $_SESSION['user_login'] = $first_name . '.' . $last_name;
            $_SESSION['user_uuid'] = wp_generate_uuid4();

            // Create temporary user object
            $user = new WP_User(0, $first_name . ' ' . $last_name);

            // Set user properties not supposed to change until logout
            $user->ID = -1; // Set value different from 0 and negative to avoid possible conflicts with real users
            $user->display_name = $first_name . ' ' . $last_name; // Set display_name
            $user->user_login = $first_name . '.' . $last_name; // Set user_login
            $user->user_email = 'john.doe@yourgrid.org';
            $user->add_cap('read'); // Example capability
            $user->add_cap('grid_user'); // Example capability

            $_SESSION['user_object'] = serialize($user);

            wp_redirect($_SERVER['REQUEST_URI']);
            exit;
        } else {
            echo '<p style="color:red;">Invalid credentials.</p>';
        }
    }

    private function logout() {
        if (self::$user->ID === -1) {
            $this->was_userless = true;
            session_destroy(); // Disconnect temporary user
        }
        wp_logout(); // Also disconnect WordPress user if any
    }

    public function wp_logout() {
        if( isset( $this->was_userless ) && $this->was_userless ) {
            $redirect = $_REQUEST['redirect_to'] ?? $this->get_login_page();
            wp_redirect( $redirect);
            exit;
        }
    }

    public function is_authenticated() {
        return isset($_SESSION['user_uuid']);
    }

    public function get_temporary_user() {
        if (isset($_SESSION['user_object'])) {
            return unserialize($_SESSION['user_object']);
        }
        return new WP_User(0);
    }

    /**
     * Hide admin bar for temporary users, return unchanged value for real users.
     */
    public function show_admin_bar( $show ) {
        if (isset(self::$user->ID) && self::$user->ID === -1) {
            return false;
        }
        return $show;
    }

    /**
     * Change default logout URL for temporary users and redirect to $login_page
     */
    public function logout_url($logout_url, $redirect) {
        if (self::$user->ID === -1) {
            $logout_url = add_query_arg(['logout' => 'true'], $this->get_login_page() );
            $logout_url = wp_nonce_url($logout_url, 'logout_action');
        }
        return $logout_url;
    }
}
