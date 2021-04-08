<?php
/**
 * Plugin Name:  Naran Persistent Login
 * Description:  Keep the user always logged in.
 * Author:       changwoo
 * Author URI:   mailto://chwnam@gmail.com
 * Plugin URI:   https://github.com/chwnam/naran-persistent-login
 * Requires PHP: 7.2
 * Version:      1.0.3
 */

if ( defined( 'NPL_ENABLED' ) && NPL_ENABLED ) {
	add_action( 'init', 'npl_init' );
}

function npl_init() {
	if ( npl_condition() ) {
		$user = get_user_by( 'login', defined( 'NPL_USER' ) ? NPL_USER : '' );
		if ( $user && $user->exists() ) {
			wp_set_auth_cookie( $user->ID, true, is_ssl() );
			$redirect = apply_filters( 'npl_redirect', defined( 'NPL_REDIRECT' ) ? NPL_REDIRECT : null );
			if ( ! $redirect ) {
				$redirect = isset( $_SERVER['REQUEST_URI'] ) ? $_SERVER['REQUEST_URI'] : admin_url();
			}
			wp_safe_redirect( esc_url_raw( $redirect ) );
			exit;
		}
	} else {
		$notice = defined( 'NPL_NOTICE' ) ? filter_var( NPL_NOTICE, FILTER_VALIDATE_BOOLEAN ) : true;
		if ( $notice && ( $user = wp_get_current_user() ) && $user->exists() ) {
			add_action( 'admin_notices', 'npl_notice' );
		}
	}
}

function npl_condition() {
	/*
	 * 1. 로컬에서 접속할 것.
	 * 2. WP_CLI 사용 중이 아닐 것.
	 * 3. DOING_* 류 상수 정의가 되어 있지 않을 것.
	 * 4. NPL_USER 상수를 정의할 것.
	 * 5. NPL_ENABLED 상수를 정의하고 참으로 할 것.
	 * 6. 로그인되어 있지 않을 것.
	 */
	static $condition = null;

	if ( is_null( $condition ) ) {
		$condition = ( defined( 'NPL_ADDR' ) ? NPL_ADDR : '127.0.0.1' ) === ( $_SERVER['REMOTE_ADDR'] ?? '' ) &&
		             ! defined( 'WP_CLI' ) &&
		             ! defined( 'DOING_CRON' ) &&
		             ! defined( 'DOING_AJAX' ) &&
		             ! defined( 'DOING_AUTOSAVE' ) &&
		             ( defined( 'NPL_USER' ) && NPL_USER ) &&
		             ( defined( 'NPL_ENABLED' ) && NPL_ENABLED ) &&
		             ! is_user_logged_in();
	}

	return apply_filters( 'npl_condition', $condition );
}

function npl_notice() {
	echo '<div class="notice notice-info"><p>Naran Persistent Login is active!</p></div>';
}
