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

    private $session_timeout = 1800; // 30 minutes timeout

    public function __construct() {
    }
    
    public function init() {
        add_shortcode('custom_auth_form', [$this, 'login_form_shortcode']);
        add_shortcode('account_info', [$this, 'account_shortcode']);
        add_action('init', [$this, 'start_session']);
    }

    public function start_session() {
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
            return $content;
        // } else {
        //     return '<p>You are not logged in.</p>';
        }
    }

    public function login_form_shortcode() {
        // Process logout
        if (isset($_POST['logout']) && wp_verify_nonce($_POST['_wpnonce'], 'logout_action')) {
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
        return '<form method="post">
                    ' . wp_nonce_field('login_action') . '
                    <label>First Name:</label>
                    <input type="text" name="first_name" value="John" required>
                    <label>Last Name:</label>
                    <input type="text" name="last_name" value="Doe" required>
                    <label>Password:</label>
                    <input type="password" name="password" value="test123" required>
                    <button type="submit" name="submit">Login</button>
                </form>';
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
        session_destroy();
        wp_redirect($_SERVER['REQUEST_URI']);
        exit;
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
        if (self::$user->ID === -1) {
            return false;
        }
        return $show;
    }

    /**
     * Change default logout URL for temporary users and redirect to $login_page
     */
    public function logout_url($logout_url, $redirect) {
        if (self::$user->ID === -1) {
            $login_page = home_url('/account');
            $logout_url = add_query_arg('logout', 'true', $login_page);
            $logout_url = add_query_arg('redirect_to', urlencode($login_page), $logout_url);
            $logout_url = wp_nonce_url($logout_url, 'logout_action');
            return $logout_url;
            // return wp_nonce_url($login_page . '?logout=true', 'logout_action');
        }
    }
}
