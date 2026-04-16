<?php
/**
 * Plugin Name: Admin Bar Login
 * Plugin URI:  https://wordpress.org/plugins/admin-bar-login/
 * Description: Show a compact login form in the admin bar for logged-out visitors.
 * Version:     1.1.0
 * Author:      scribu
 * License:     GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Network:     true
 * Requires at least: 5.8
 * Tested up to: 6.9
 * Requires PHP: 7.2.24
 * Text Domain: admin-bar-login
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'ADMIN_BAR_LOGIN_VERSION', '1.1.0' );

add_filter( 'show_admin_bar', 'admin_bar_login_force_toolbar_for_logged_out_users', 999 );
add_action( 'wp_enqueue_scripts', 'admin_bar_login_enqueue_assets' );
add_action( 'admin_bar_menu', 'admin_bar_login_add_toolbar_items', 80 );

/**
 * Decide whether the inline login toolbar should be enabled on this request.
 *
 * @return bool
 */
function admin_bar_login_is_enabled() {
	if ( is_user_logged_in() || is_admin() ) {
		return false;
	}

	if ( wp_doing_ajax() || ( function_exists( 'wp_is_json_request' ) && wp_is_json_request() ) ) {
		return false;
	}

	if ( is_feed() ) {
		return false;
	}

	/**
	 * Filter whether Admin Bar Login should be enabled for the current request.
	 *
	 * @param bool $enabled Whether the plugin should render the toolbar form.
	 */
	return (bool) apply_filters( 'admin_bar_login_is_enabled', true );
}

/**
 * Force the frontend toolbar for logged-out users on eligible requests.
 *
 * @param bool $show Whether WordPress planned to show the admin bar.
 * @return bool
 */
function admin_bar_login_force_toolbar_for_logged_out_users( $show ) {
	if ( admin_bar_login_is_enabled() ) {
		return true;
	}

	return $show;
}

/**
 * Enqueue toolbar styles only when the login form can actually render.
 */
function admin_bar_login_enqueue_assets() {
	if ( ! admin_bar_login_is_enabled() || ! is_admin_bar_showing() ) {
		return;
	}

	wp_enqueue_style(
		'admin-bar-login',
		plugin_dir_url( __FILE__ ) . 'admin-bar-login.css',
		array(),
		ADMIN_BAR_LOGIN_VERSION
	);
}

/**
 * Add the login form and helpful account links to the frontend toolbar.
 *
 * @param WP_Admin_Bar $wp_admin_bar Toolbar instance.
 */
function admin_bar_login_add_toolbar_items( $wp_admin_bar ) {
	if ( ! admin_bar_login_is_enabled() || ! is_admin_bar_showing() ) {
		return;
	}

	$login_node_args = array(
		'id'    => 'admin-bar-login',
		'title' => admin_bar_login_get_form_markup(),
		'meta'  => array(
			'class' => 'admin-bar-login-node',
		),
	);

	$parent_node = admin_bar_login_get_parent_node();

	if ( ! empty( $parent_node ) ) {
		$login_node_args['parent'] = $parent_node;
	}

	$wp_admin_bar->add_menu( $login_node_args );
}

/**
 * Pick the correct toolbar area for the current language direction.
 *
 * LTR sites should keep the form in the primary left group, matching the
 * legacy plugin behavior. RTL sites can mirror it into the secondary group.
 *
 * @return string|false
 */
function admin_bar_login_get_parent_node() {
	$parent_node = is_rtl() ? 'top-secondary' : false;

	/**
	 * Filter the parent admin bar node used by Admin Bar Login.
	 *
	 * Return false to place the login form in the default primary group.
	 *
	 * @param string|false $parent_node Admin bar parent node ID or false.
	 */
	return apply_filters( 'admin_bar_login_parent_node', $parent_node );
}

/**
 * Determine whether the Register link should be shown.
 *
 * @return bool
 */
function admin_bar_login_show_register_link() {
	$show_register_link = (bool) get_option( 'users_can_register' );

	/**
	 * Filter whether the Register link should appear in the toolbar.
	 *
	 * @param bool $show_register_link Whether registration is currently enabled.
	 */
	return (bool) apply_filters( 'admin_bar_login_show_register_link', $show_register_link );
}

/**
 * Build the inline login form markup used inside the admin bar.
 *
 * @return string
 */
function admin_bar_login_get_form_markup() {
	$redirect_url      = admin_bar_login_get_redirect_url();
	$lost_password_url = wp_lostpassword_url( $redirect_url );
	$show_register     = admin_bar_login_show_register_link();
	$register_url      = $show_register ? wp_registration_url() : '';

	ob_start();
	?>
	<form class="admin-bar-login-form" id="adminloginform" action="<?php echo esc_url( wp_login_url( $redirect_url ) ); ?>" method="post" aria-label="<?php echo esc_attr__( 'Log in from the admin bar', 'admin-bar-login' ); ?>">
		<div class="admin-bar-login-fields">
			<label class="admin-bar-login-screen-reader-text" for="admin-bar-login-user">
				<?php esc_html_e( 'Username or Email Address' ); ?>
			</label>
			<input
				type="text"
				name="log"
				id="admin-bar-login-user"
				class="admin-bar-login-input admin-bar-login-input-username"
				placeholder="<?php echo esc_attr_x( 'Username or Email', 'Short placeholder for the admin bar login field', 'admin-bar-login' ); ?>"
				autocomplete="username"
				aria-label="<?php echo esc_attr__( 'Username or Email Address' ); ?>"
				required
			/>

			<label class="admin-bar-login-screen-reader-text" for="admin-bar-login-password">
				<?php esc_html_e( 'Password' ); ?>
			</label>
			<input
				type="password"
				name="pwd"
				id="admin-bar-login-password"
				class="admin-bar-login-input admin-bar-login-input-password"
				placeholder="<?php echo esc_attr__( 'Password' ); ?>"
				autocomplete="current-password"
				aria-label="<?php echo esc_attr__( 'Password' ); ?>"
				required
			/>
		</div>

		<div class="admin-bar-login-controls">
			<label class="admin-bar-login-remember" for="admin-bar-login-remember">
				<input type="checkbox" name="rememberme" id="admin-bar-login-remember" value="forever" checked="checked" aria-label="<?php echo esc_attr__( 'Remember Me' ); ?>" />
				<span class="admin-bar-login-remember-label"><?php esc_html_e( 'Remember Me' ); ?></span>
			</label>

			<button type="submit" class="admin-bar-login-submit" aria-label="<?php echo esc_attr__( 'Log In' ); ?>"><?php esc_html_e( 'Log In' ); ?></button>
		</div>

		<div class="admin-bar-login-links">
			<a class="admin-bar-login-link" href="<?php echo esc_url( $lost_password_url ); ?>">
				<?php esc_html_e( 'Lost your password?' ); ?>
			</a>
			<?php if ( $show_register ) : ?>
				<a class="admin-bar-login-link admin-bar-login-link-register" href="<?php echo esc_url( $register_url ); ?>">
					<?php esc_html_e( 'Register' ); ?>
				</a>
			<?php endif; ?>
		</div>

		<div class="admin-bar-login-mobile-actions">
			<a class="admin-bar-login-submit admin-bar-login-mobile-login" href="<?php echo esc_url( wp_login_url( $redirect_url ) ); ?>">
				<?php esc_html_e( 'Log In' ); ?>
			</a>
			<?php if ( $show_register ) : ?>
				<a class="admin-bar-login-link admin-bar-login-link-register" href="<?php echo esc_url( $register_url ); ?>">
					<?php esc_html_e( 'Register' ); ?>
				</a>
			<?php endif; ?>
		</div>

		<input type="hidden" name="redirect_to" value="<?php echo esc_url( $redirect_url ); ?>" />
	</form>
	<?php

	return trim( ob_get_clean() );
}

/**
 * Build a safe redirect back to the current frontend URL after login/reset.
 *
 * @return string
 */
function admin_bar_login_get_redirect_url() {
	global $wp;

	$current_url = home_url( '/' );

	if ( isset( $wp->request ) && '' !== $wp->request ) {
		$current_url = home_url( '/' . ltrim( $wp->request, '/' ) );
	}

	if ( ! empty( $_SERVER['QUERY_STRING'] ) ) {
		parse_str( wp_unslash( $_SERVER['QUERY_STRING'] ), $query_args );
		$current_url = add_query_arg( $query_args, $current_url );
	}

	/**
	 * Filter the post-login redirect URL used by the toolbar form.
	 *
	 * @param string $current_url Safe current URL.
	 */
	return (string) apply_filters( 'admin_bar_login_redirect_url', wp_validate_redirect( $current_url, home_url( '/' ) ) );
}
