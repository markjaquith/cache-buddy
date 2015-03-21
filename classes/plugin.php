<?php
defined( 'WPINC' ) or die;

class Cache_Buddy_Plugin extends WP_Stack_Plugin2 {
	const COOKIE_VERSION = 1;
	const VERSION_COOKIE = 'cache_buddy_v';
	const USERNAME_COOKIE = 'cache_buddy_username';

	/**
	 * Constructs the object, hooks in to 'plugins_loaded'
	 */
	protected function __construct() {
		$this->hook( 'plugins_loaded', 'add_hooks' );
	}

	/**
	 * Adds hooks
	 */
	public function add_hooks() {
		$this->hook( 'init' );
		// Add your hooks here
	}

	/**
	 * Initializes the plugin, registers textdomain, etc
	 */
	public function init() {
		$this->load_textdomain( 'cache-buddy', '/languages' );

		$this->maybe_alter_cookies();
	}

	/**
	 * Potentially performs cookie operations
	 */
	public function maybe_alter_cookies() {
		$this->logout_frontend();
		if (
			! is_admin() &&
			! defined( 'DOING_AJAX' ) &&
			is_user_logged_in() &&
			! current_user_can( 'publish_posts'
		) ) {
			$this->logout_frontend();
		}

		if ( is_user_logged_in() &&
			(
				! isset( $_COOKIE[self::VERSION_COOKIE] ) ||
				self::COOKIE_VERSION != $_COOKIE[self::VERSION_COOKIE]
		)) {
			$this->set_cookies();
		}
	}

	/**
	 * Logs a user out of the front of the site (but not the backend)
	 */
	public function logout_frontend() {
		$this->delete_cookie( LOGGED_IN_COOKIE  );
		$this->delete_cookie( 'wordpress_test_cookie' );
	}

	/**
	 * Sets custom cookies
	 */
	public function set_cookies() {
		$user = wp_get_current_user();

		$cookies = array(
			self::VERSION_COOKIE => self::COOKIE_VERSION,
			self::USERNAME_COOKIE => $user->user_login,
		);
		$cookies = apply_filters( 'cache_buddy_cookies', $cookies );
		foreach ( $cookies as $name => $value ) {
			$this->set_cookie( $name, $value );
		}
	}

	/**
	 * Deletes a cookie
	 *
	 * @param  string $name the name of the cookie to delete
	 */
	protected function delete_cookie( $name ) {
		$this->set_cookie( $name, ' ', time() - YEAR_IN_SECONDS );
	}

	/**
	 * Sets a cookie
	 *
	 * @param string $name the name of the cookie to set
	 * @param string $value the value of the cookie
	 * @param int    $expiration Unix timestamp of when the cookie should expire
	 */
	protected function set_cookie( $name, $value, $expiration = null ) {
		if ( null === $expiration ) {
			$expiration = time() + (14 * DAY_IN_SECONDS);
		}

		setcookie( $name, $value, $expiration, SITECOOKIEPATH, COOKIE_DOMAIN );

		if ( COOKIEPATH != SITECOOKIEPATH ) {
			setcookie( $name, $value, $expiration, COOKIEPATH, COOKIE_DOMAIN );
		}
	}
}
