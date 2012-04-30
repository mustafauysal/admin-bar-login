<?php
/*
Plugin Name: Admin Bar Login
Version: 1.0.1-alpha
Description: Show login form in the admin bar for non-logged-in users.
Author: scribu
Plugin URI: http://wordpress.org/extend/plugins/admin-bar-login/
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/

add_action( 'show_admin_bar', '__return_true' );

add_action( 'template_redirect', 'admin_bar_login_css' );


function admin_bar_login_css() {
	if ( is_user_logged_in() )
		return;

	wp_enqueue_style( 'admin-bar-login', plugins_url( 'admin-bar-login.css', __FILE__ ), array(), '1.0' );

	add_action( 'admin_bar_menu', 'admin_bar_login_menu' );
}

function admin_bar_login_menu( $wp_admin_bar ) {
	$form = wp_login_form( array(
		'form_id' => 'adminloginform',
		'echo' => false,
		'value_remember' => true
	) );

	$wp_admin_bar->add_menu( array(
		'id'     => 'login',
		'title'  => $form,
	) );

	$wp_admin_bar->add_menu( array(
		'id'     => 'lostpassword',
		'title'  => __( 'Lost your password?' ),
		'href' => wp_lostpassword_url()
	) );

	if ( get_option( 'users_can_register' ) ) {
		$wp_admin_bar->add_menu( array(
			'id'     => 'register',
			'title'  => __( 'Register' ),
			'href' => site_url( 'wp-login.php?action=register', 'login' )
		) );
	}
}

