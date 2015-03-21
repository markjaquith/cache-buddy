<?php
defined( 'WPINC' ) or die;

class Cache_Buddy_Plugin extends WP_Stack_Plugin2 {
	const COOKIE_VERSION = 1;
	const VERSION_COOKIE = 'cache_buddy_v';
	const USERNAME_COOKIE = 'cache_buddy_username';
	const ROLE_COOKIE = 'cache_buddy_role';

	protected $user_id;

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
		$this->hook( 'clear_auth_cookie' );
		$this->hook( 'set_logged_in_cookie' );
		$this->hook( 'set_auth_cookie' );
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
		// Temporarily running these on every load
		if ( is_admin() ) {
			$this->logout_frontend();
			$this->set_cookies();
		}
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
	 * Clears the custom cookies on `clear_auth_cookie` action
	 */
	public function clear_auth_cookie() {
		foreach ( $this->get_cookies() as $name => $value ) {
			$this->delete_cookie( $name );
		}

		foreach ( array( AUTH_COOKIE, SECURE_AUTH_COOKIE, LOGGED_IN_COOKIE ) as $name ) {
			$this->delete_cookie( $name, trailingslashit( SITECOOKIEPATH ) . 'wp-login.php' );
		}
	}

	/**
	 * Sets the custom cookies on `set_logged_in_cookie` action
	 */
	public function set_logged_in_cookie( $value, $grace, $expiration, $user_id ) {
		$this->user_id = $user_id;
		$this->set_cookies();
		$this->set_cookie( LOGGED_IN_COOKIE, $value, $expiration, trailingslashit( SITECOOKIEPATH ) . 'wp-login.php' );
	}

	/**
	 * Logs a user out of the front of the site (but not the backend)
	 */
	public function logout_frontend() {
		$this->delete_cookie( LOGGED_IN_COOKIE  );
		$this->delete_cookie( 'wordpress_test_cookie' );
	}

	public function get_cookies() {
		$user_id = get_current_user_id();

		if ( ! $user_id ) {
			$user_id = $this->user_id;
		}

		$user = new WP_User( $user_id );

		$role = $user->ID ? $user->roles[0] : '';

		$cookies = array(
			self::VERSION_COOKIE  => self::COOKIE_VERSION,
			self::USERNAME_COOKIE => $user->user_login,
			self::ROLE_COOKIE     => $role,
		);
		return apply_filters( 'cache_buddy_cookies', $cookies );
	}

	/**
	 * Sets custom cookies
	 */
	public function set_cookies() {
		foreach ( $this->get_cookies() as $name => $value ) {
			$this->set_cookie( $name, $value );
		}
	}

	/**
	 * Deletes a cookie
	 *
	 * @param  string $name the name of the cookie to delete
	 */
	protected function delete_cookie( $name, $path = null ) {
		$this->set_cookie( $name, ' ', time() - YEAR_IN_SECONDS, $path );
	}

	/**
	 * Sets a cookie
	 *
	 * @param string $name the name of the cookie to set
	 * @param string $value the value of the cookie
	 * @param int    $expiration Unix timestamp of when the cookie should expire
	 * @param string $path Optional path that the cookie should be set to
	 */
	protected function set_cookie( $name, $value, $expiration = null, $path = null ) {
		if ( null === $expiration ) {
			$expiration = time() + (14 * DAY_IN_SECONDS);
		}

		if ( isset( $path ) ) {
			setcookie( $name, $value, $expiration, $path, COOKIE_DOMAIN );
		} else {
			setcookie( $name, $value, $expiration, SITECOOKIEPATH, COOKIE_DOMAIN );

			if ( COOKIEPATH != SITECOOKIEPATH ) {
				setcookie( $name, $value, $expiration, COOKIEPATH, COOKIE_DOMAIN );
			}
		}
	}

	public function set_auth_cookie( $value, $grace, $expiration, $user_id, $scheme ) {
		$cookie_name = 'secure_auth' === $scheme ? SECURE_AUTH_COOKIE : AUTH_COOKIE;
		$this->set_cookie( $cookie_name, $value, $expiration, trailingslashit( SITECOOKIEPATH ) . 'wp-login.php' );
	}
}
