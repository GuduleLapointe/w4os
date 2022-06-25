<p><?= esc_html_e( __('Welcome', 'mb-user-profile' ) ) ?> {username}</p>
<p><?= esc_html_e( __('Please click the link below to confirm your account:', 'mb-user-profile' ) ) ?></p>
<p><a href="{confirm_link}" target="_blank"><?= esc_html_e( __('Confirm Account', 'mb-user-profile' ) ) ?></a></p>
<p><?= esc_html_e( __("If that doesn't work, copy and paste the following link in your browser:", 'mb-user-profile' ) ) ?></p>
<p>{confirm_link}</a>