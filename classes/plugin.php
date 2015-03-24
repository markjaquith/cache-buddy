<?php
defined( 'WPINC' ) or die;

class Cache_Buddy_Plugin extends WP_Stack_Plugin2 {
	const COOKIE_VERSION = 1;
	const STRICT_COOKIE_VERSION = 1;
	const VERSION_COOKIE = 'cache_buddy_v';
	const STRICT_VERSION_COOKIE = 'cache_buddy_strict_v';
	const USERNAME_COOKIE = 'cache_buddy_username';
	const COMMENT_NAME_COOKIE = 'cache_buddy_comment_name';
	const COMMENT_EMAIL_COOKIE = 'cache_buddy_comment_email';
	const COMMENT_URL_COOKIE = 'cache_buddy_comment_url';
	const ROLE_COOKIE = 'cache_buddy_role';
	const USER_ID_COOKIE = 'cache_buddy_id';
	const CSS_JS_VERSION = '1';

	protected $logged_in_as_message = '';
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
		if ( ! get_option( 'comment_registration' ) ) {
			$this->hook( 'init' );
			$this->hook( 'clear_auth_cookie' );
			$this->hook( 'set_logged_in_cookie' );
			$this->hook( 'set_auth_cookie' );
			remove_action( 'set_comment_cookies', 'wp_set_comment_cookies' );
			$this->hook( 'set_comment_cookies' );
			$this->hook( 'wp_enqueue_scripts' );
			$this->hook( 'comment_form_defaults', 9999 );
			$this->hook( 'comment_form_after_fields', 9999 );
		} else {
			$this->hook( 'admin_notices', 'comment_registration' );
		}
	}

	/**
	 * Initializes the plugin, registers textdomain, etc
	 */
	public function init() {
		$this->load_textdomain( 'cache-buddy', '/languages' );
		if ( ! get_option( 'comment_registration' ) ) {
			$this->maybe_alter_cookies();
		}
	}

	/**
	 * Says whether the current user should get WordPress cookies on the frontend
	 *
	 * @return bool whether the current user should get WordPress cookies on the frontend
	 */
	public function current_user_gets_frontend_cookies() {
		return apply_filters( 'cache_buddy_logged_in_frontend', current_user_can( 'publish_posts' ) );
	}

	public function comment_registration() {
		echo "<div class='notice'>";
		echo "<p>";
		_e( 'You have comment registration enabled, so Cache Buddy cannot be used.', 'cache-buddy' );
		echo "</p>";
		echo "</div>";
	}

	/**
	 * Potentially performs cookie operations
	 */
	public function maybe_alter_cookies() {
		if ( is_user_logged_in() && ! $this->current_user_gets_frontend_cookies() ) {
			$this->logout_frontend();
			$this->hook( 'template_redirect', 0 );
		}

		if (
			is_user_logged_in() &&
			(
				! isset( $_COOKIE[self::VERSION_COOKIE] ) ||
				self::COOKIE_VERSION != $_COOKIE[self::VERSION_COOKIE]
			)
		) {
			$this->set_cookies();
		}

		if ( is_user_logged_in() &&
			! isset( $_COOKIE[self::STRICT_VERSION_COOKIE] ) ||
			self::STRICT_COOKIE_VERSION != $_COOKIE[self::STRICT_VERSION_COOKIE]
		) {
			// The user needs different cookies set, but we need them to log back in to get the values
			wp_logout();
		}
	}

	public function template_redirect() {
		wp_redirect( remove_query_arg( 'Beetlejuice Beetlejuice Beetlejuice' ) );
		die();
	}

	/**
	 * Filters and inspects the comment form
	 *
	 * @param array $fields the comment form fields
	 * @return array the filtered comment form fields
	 */
	public function comment_form_defaults( $fields ) {
		$fields['comment_notes_before'] = '<div class="cache-buddy-comment-fields-wrapper">' . $fields['comment_notes_before'];
		$this->logged_in_as_message = $fields['logged_in_as'];
		return $fields;
	}

	/**
	 * Adds a hidden "logged_in_as" message to the end of the comments form
	 */
	public function comment_form_after_fields() {
		echo '</div>';
		echo '</div><div style="display:none" data-profile-url="' . admin_url( 'profile.php' ) . '" class="cache-buddy-logged-in-as">';
		echo $this->logged_in_as_message;
		echo '</div>';
	}

	/**
	 * Enqueues the comment-form-filling script on pages with comment forms
	 */
	public function wp_enqueue_scripts() {
		if ( is_singular() && comments_open() ) {
			wp_enqueue_script( 'cache-buddy-comments', $this->get_url() . 'js/cache-buddy.min.js', array( 'jquery' ), self::CSS_JS_VERSION, true );
		}
	}

	/**
	 * Sets our custom comment cookies on comment submission
	 *
	 * @param object $comment the comment object
	 * @param WP_User $user the WordPress user who submitted the comment
	 */
	public function set_comment_cookies($comment, $user) {
		if ( $user->exists() ) {
			return;
		}

		$comment_cookie_lifetime = apply_filters( 'comment_cookie_lifetime', 30000000 );
		$secure = ( 'https' === parse_url( home_url(), PHP_URL_SCHEME ) );

		// Set the name cookie
		setcookie(
			self::COMMENT_NAME_COOKIE,
			$comment->comment_author,
			time() + $comment_cookie_lifetime,
			COOKIEPATH,
			COOKIE_DOMAIN,
			$secure
		);

		// Set the email cookie
		setcookie(
			self::COMMENT_EMAIL_COOKIE,
			$comment->comment_author_email,
			time() + $comment_cookie_lifetime,
			COOKIEPATH,
			COOKIE_DOMAIN,
			$secure
		);

		// Set the URL cookie
		setcookie(
			self::COMMENT_URL_COOKIE,
			esc_url($comment->comment_author_url),
			time() + $comment_cookie_lifetime,
			COOKIEPATH,
			COOKIE_DOMAIN,
			$secure
		);
	}

	/**
	 * Clears the custom cookies on `clear_auth_cookie` action
	 */
	public function clear_auth_cookie() {
		foreach ( $this->get_cookies() as $name => $value ) {
			$this->delete_cookie( $name );
		}

		foreach ( array( AUTH_COOKIE, SECURE_AUTH_COOKIE, LOGGED_IN_COOKIE ) as $name ) {
			$this->delete_cookie( $name, trailingslashit( SITECOOKIEPATH ) . 'wp-comments-post.php' );
			$this->delete_cookie( $name, trailingslashit( SITECOOKIEPATH ) . 'wp-login.php' );
			$this->delete_cookie( $name, trailingslashit( SITECOOKIEPATH ) . 'wp-admin' );
		}
	}

	/**
	 * Sets the custom cookies on `set_logged_in_cookie` action
	 *
	 * @param string $value the value of the cookie
	 * @param int    $grace the grace period for the expiration (unused)
	 * @param int    $expiration the unix timestamp of the cookie expiration
	 * @param int    $user_id the user id being logged in
	 */
	public function set_logged_in_cookie( $value, $grace, $expiration, $user_id ) {
		$this->user_id = $user_id;
		$this->set_cookies();
		setcookie( LOGGED_IN_COOKIE, $value, $expiration, trailingslashit( SITECOOKIEPATH ) . 'wp-comments-post.php', COOKIE_DOMAIN, false, true );
		setcookie( LOGGED_IN_COOKIE, $value, $expiration, trailingslashit( SITECOOKIEPATH ) . 'wp-login.php', COOKIE_DOMAIN, false, true );
		setcookie( LOGGED_IN_COOKIE, $value, $expiration, trailingslashit( SITECOOKIEPATH ) . 'wp-admin', COOKIE_DOMAIN, false, true );
	}

	/**
	 * Logs a user out of the front of the site (but not the backend)
	 */
	public function logout_frontend() {
		$this->delete_cookie( LOGGED_IN_COOKIE  );
		$this->delete_cookie( 'wordpress_test_cookie' );
	}

	/**
	 * Gets the names and values of the custom cookies
	 *
	 * @return array the cookie names/values
	 */
	public function get_cookies() {
		$user_id = get_current_user_id();

		if ( ! $user_id ) {
			$user_id = $this->user_id;
		}

		$user = new WP_User( $user_id );

		$role = $user->ID ? $user->roles[0] : '';

		$cookies = array(
			self::VERSION_COOKIE  => self::COOKIE_VERSION,
			self::STRICT_VERSION_COOKIE => self::STRICT_COOKIE_VERSION,
			self::USERNAME_COOKIE => $user->user_login,
			self::ROLE_COOKIE     => $role,
			self::USER_ID_COOKIE => $user->ID,
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

	/**
	 * Sets an auth cookie for wp-login.php
	 *
	 * @param string $value the value of the cookie
	 * @param int    $grace the grace period for the expiration (unused)
	 * @param int    $expiration the unix timestamp of the cookie expiration
	 * @param int    $user_id the user id being logged in
	 * @param string $scheme the login scheme ('auth' or 'secure_auth')
	 */
	public function set_auth_cookie( $value, $grace, $expiration, $user_id, $scheme ) {
		$cookie_name = 'secure_auth' === $scheme ? SECURE_AUTH_COOKIE : AUTH_COOKIE;
		setcookie( $cookie_name, $value, $expiration, trailingslashit( SITECOOKIEPATH ) . 'wp-login.php', COOKIE_DOMAIN, 'secure_auth' === $scheme, true );
	}
}
