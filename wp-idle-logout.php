<?php
/*
	Plugin Name: WordPress Idle Logout
	Plugin URI: https://github.com/gerrytucker/wordpress-idle-logout
	Description: Automatically logs out inactive users.
	Version: 1.0.2
	Author: Gerry Tucker
	Author URI: http://gerrytucker.co.uk/
	Requires at least: 2.8
	Tested up to: 4.1
	GitHub Plugin URI: https://github.com/gerrytucker/wordpress-idle-logout
	GitHub Plugin Branch: develop
*/


class WP_Idle_Logout {
	/**
	 * Name space
	 */
	const ID = 'wp_idle_logout_';

	/**
	 * Default idle time
	 */
	const default_idle_time = 1800;

	/**
	 * Add actions and filters
	 *
	 */
	public function __construct() {;
		add_action( 'wp_login', array(&$this, 'login_key_refresh'), 10, 2 );
		add_action( 'init', array(&$this, 'check_for_inactivity') );
		add_action( 'clear_auth_cookie', array(&$this, 'clear_activity_meta') );
	}

	/**
	 * Retreives the maximum allowed idle time setting
	 *
	 * Checks if idle time is set in plugin options
	 * If not, uses the default time
	 * Returns $time in seconds, as integer
	 *
	 */
	private function get_idle_time_setting() {
		$time = get_option(self::ID . '_idle_time');
		if ( empty($time) || !is_numeric($time) ) {
			$time = self::default_idle_time;
		}
		return (int) $time;
	}

	/**
	 * Retreives the idle messsage
	 *
	 * Checks if idle message is set in plugin options
	 * If not, uses the default message
	 * Returns $message
	 *
	 */
	private function get_idle_message_setting() {
		$message = nl2br( get_option(self::ID . '_idle_message') );
		if ( empty($message) ) {
			$message = self::default_idle_message;
		}
		return $message;
	}

	/**
	 * Refreshes the meta key on login
	 *
	 * Tests if the user is logged in on 'init'.
	 * If true, checks if the 'last_active_time' meta is set.
	 * If it isn't, the meta is created for the current time.
	 * If it is, the timestamp is checked against the inactivity period.
	 *
	 */
	public function login_key_refresh( $user_login, $user ) {

		update_user_meta( $user->ID, self::ID . '_last_active_time', time() );

	}

	/**
	 * Checks for User Idleness
	 *
	 * Tests if the user is logged in on 'init'.
	 * If true, checks if the 'last_active_time' meta is set.
	 * If it isn't, the meta is created for the current time.
	 * If it is, the timestamp is checked against the inactivity period.
	 *
	 */
	public function check_for_inactivity() {
		if ( is_user_logged_in() ) {
			$user_id = get_current_user_id();
			$time = get_user_meta( $user_id, self::ID . '_last_active_time', true );

			if ( is_numeric($time) ) {
				if ( (int) $time + $this->get_idle_time_setting() < time() ) {
					wp_redirect( wp_login_url() . '?idle=1' );
					wp_logout();
					$this->clear_activity_meta( $user_id );
					exit;
				} else {
					update_user_meta( $user_id, self::ID . '_last_active_time', time() );
				}
			} else {
				delete_user_meta( $user_id, self::ID . '_last_active_time' );
				update_user_meta( $user_id, self::ID . '_last_active_time', time() );
			}
		}
	}

	/**
	 * Delete Inactivity Meta
	 *
	 * Deletes the 'last_active_time' meta when called.
	 * Used on normal logout and on idleness logout.
	 *
	 */
	public function clear_activity_meta( $user_id = false ) {
		if ( !$user_id ) {
			$user_id = get_current_user_id();
		}
		delete_user_meta( $user_id, self::ID . '_last_active_time' );
	}

}

$WP_Idle_Logout = new WP_Idle_Logout();
