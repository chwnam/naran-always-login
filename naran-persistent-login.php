<?php
/**
 * Plugin Name:  Naran Persistent Login
 * Description:  Keep the user always logged in.
 * Author:       changwoo
 * Author URI:   https://blog.changwoo.pe.kr
 * Plugin URI:   https://github.com/chwnam/naran-persistent-login
 * Requires PHP: 7.2
 * Version:      1.1.1
 */

if ( ! function_exists( 'npl_init' ) ) {
	if ( defined( 'NPL_ENABLED' ) && NPL_ENABLED ) {
		add_action( 'init', 'npl_init' );
	}

	function npl_init() {
		if ( is_user_logged_in() && npl_is_suppressed() ) {
			wp_logout();
			wp_safe_redirect( home_url() );
			exit;
		} elseif ( npl_condition() ) {
			$user = get_user_by( 'login', defined( 'NPL_USER' ) ? NPL_USER : '' );
			if ( $user && $user->exists() ) {
				wp_set_auth_cookie( $user->ID, true, is_ssl() );
				$redirect = apply_filters( 'npl_redirect', defined( 'NPL_REDIRECT' ) ? NPL_REDIRECT : null );
				if ( ! $redirect ) {
					$redirect = $_SERVER['REQUEST_URI'] ?? admin_url();
				}
				wp_safe_redirect( esc_url_raw( $redirect ) );
				exit;
			}
		} else {
			$notice = ! defined( 'NPL_NOTICE' ) || filter_var( NPL_NOTICE, FILTER_VALIDATE_BOOLEAN );
			if ( $notice && ( $user = wp_get_current_user() ) && $user->exists() ) {
				add_action( 'admin_notices', 'npl_notice' );
			} elseif ( npl_is_suppressed() ) {
				add_action( 'wp_footer', 'npl_footer_suppressed' );
			}
		}
	}
}


if ( ! function_exists( 'npl_condition' ) ) {
	function npl_condition(): bool {
		/*
		 * 1. 로컬에서 접속할 것.
		 * 2. WP_CLI 사용 중이 아닐 것.
		 * 3. DOING_* 류 상수 정의가 되어 있지 않을 것.
		 * 4. REST API 수행 중이 아닐 것.
		 * 5. NPL_USER 상수를 정의할 것.
		 * 6. NPL_ENABLED 상수를 정의하고 참으로 할 것.
		 * 7. 로그인되어 있지 않을 것.
		 */
		static $condition = null;

		if ( is_null( $condition ) ) {
			$condition = ( defined( 'NPL_ADDR' ) ? NPL_ADDR : '127.0.0.1' ) === ( $_SERVER['REMOTE_ADDR'] ?? '' ) &&
			             ! defined( 'WP_CLI' ) &&
			             ! defined( 'DOING_CRON' ) &&
			             ! defined( 'DOING_AJAX' ) &&
			             ! defined( 'DOING_AUTOSAVE' ) &&
			             ! npl_is_rest_api() &&
			             ( defined( 'NPL_USER' ) && NPL_USER ) &&
			             ( defined( 'NPL_ENABLED' ) && NPL_ENABLED ) &&
			             ! is_user_logged_in() &&
			             ! npl_is_suppressed();
		}

		return apply_filters( 'npl_condition', $condition );
	}
}


if ( ! function_exists( 'npl_is_rest_api' ) ) {
	function npl_is_rest_api() {
		$request_uri = trim( $_SERVER['REQUEST_URI'] ?? '', '/' );
		$result      = false;

		if ( $request_uri ) {
			global $wp_rewrite;

			$index  = $wp_rewrite->index;
			$prefix = rest_get_url_prefix();
			$result = (bool) preg_match( ";^(?:$prefix/?|$index/$prefix/);", $request_uri );
		}

		return $result;
	}
}


if ( ! function_exists( 'npl_notice' ) ) {
	function npl_notice() {
		$url = wp_nonce_url( admin_url( 'admin-post.php' ) . '?action=npl_suppress', 'npl_suppress' );
		echo '<div class="notice notice-info"><p>';
		echo 'Naran Persistent Login is active!';
		echo ' <a href="' . esc_url( $url ) . '">Suppress temporarily?</a>';
		echo '</p></div>';
	}
}


if ( ! function_exists( 'npl_footer_suppressed' ) ) {
	function npl_footer_suppressed() {
		$url = wp_nonce_url( admin_url( 'admin-post.php' ) . '?action=npl_unsupress', 'npl_unsuppress' );
		echo '<p style="padding: 10px 20px;">Persistent login is temporarily suppressed now.';
		echo ' <a href="' . esc_url( $url ) . '">Enable now</a>?</p>';
	}
}


if ( ! function_exists( 'npl_admin_post_suppress' ) ) {
	add_action( 'admin_post_npl_suppress', 'npl_admin_post_suppress' );
	function npl_admin_post_suppress() {
		check_admin_referer( 'npl_suppress' );
		npl_suppress( true );
		wp_safe_redirect( home_url() );
	}
}


if ( ! function_exists( 'npl_admin_post_unsuppress' ) ) {
	add_action( 'admin_post_nopriv_npl_unsupress', 'npl_admin_post_unsuppress' );
	function npl_admin_post_unsuppress() {
		check_admin_referer( 'npl_unsuppress' );
		npl_suppress( false );
		wp_safe_redirect( home_url() );
	}
}


if ( ! function_exists( 'npl_is_suppressed' ) ) {
	function npl_is_suppressed(): bool {
		return (bool) get_site_option( 'npl_suppress' );
	}
}


if ( ! function_exists( 'npl_suppress' ) ) {
	function npl_suppress( bool $suppress ) {
		update_site_option( 'npl_suppress', $suppress );
	}
}
