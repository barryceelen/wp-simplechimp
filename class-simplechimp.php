<?php
/**
 * Contains plugin class
 *
 * @package   SimpleChimp
 * @author    Barry Ceelen <b@rryceelen.com>
 * @license   GPL-3.0+
 * @link      https://github.com/barryceelen/wp-simplechimp
 * @copyright 2013 Barry Ceelen
 */

/**
 * Plugin class.
 */
class SimpleChimp {

	/**
	 * Plugin version, used for cache-busting of style and script file references.
	 *
	 * @since 0.2.0
	 *
	 * @var string
	 */
	const VERSION = '0.2.0';

	/**
	 * Instance of this class.
	 *
	 * @since 0.1.0
	 *
	 * @var object
	 */
	protected static $instance = null;

	/**
	 * Return an instance of this class.
	 *
	 * @since 0.1.0
	 *
	 * @return object A single instance of this class.
	 */
	public static function get_instance() {
		// If the single instance hasn't been set, set it now.
		if ( null === self::$instance ) {
			self::$instance = new self;
		}
		return self::$instance;
	}

	/**
	 * Initialize the plugin by setting options, localization, actions and filters.
	 *
	 * There is no settings page for the plugin, add your api_key and list_id via
	 * adding a filter to 'simplechimp_options'.
	 *
	 * @see https://gist.github.com/barryceelen/7619021
	 *
	 * Response messages are currently defined in the plugin in stead of passing the MailChimp messages
	 * because the MailChimp API translations are not always satisfactory.
	 *
	 * @todo Allow using the default MailChimp API messages.
	 *
	 * @since 0.1.0
	 */
	private function __construct() {

		$this->messages = array(
			'error'         => __( 'An error occurred.', 'simplechimp' ),
			'error_nonce'   => __( 'An error occurred, try reloading the page.', 'simplechimp' ),
			'invalid_email' => __( 'Invalid email address.', 'simplechimp' ),
			'already'       => __( 'Already subscribed.', 'simplechimp' ),
			'success'       => __( 'Thank you for subscribing.', 'simplechimp' )
		);

		// Load plugin text domain.
		add_action( 'init', array( $this, 'load_plugin_textdomain' ) );

		// Register query vars.
		add_filter( 'query_vars', array( $this, 'query_vars' ) );

		// Load public-facing JavaScript.
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		// Handle subscription request.
		if ( isset( $_POST['simplechimp-submit'] ) ) {
			add_action( 'template_redirect', array( $this, 'subscription_form_submit' ) );
		}

		// Add ajax handlers.
		add_action( 'wp_ajax_simplechimp_subscribe', array( $this, 'subscription_form_submit' ) );
		add_action( 'wp_ajax_nopriv_simplechimp_subscribe', array( $this, 'subscription_form_submit' ) );

		// Register do_action used for displaying the form.
		add_action( 'simplechimp', array( $this, 'form' ) );
	}

	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since 0.2.0
	 */
	public function load_plugin_textdomain() {

		$domain = 'simplechimp';
		$locale = apply_filters( 'plugin_locale', get_locale(), $domain );

		load_textdomain( $domain, trailingslashit( WP_LANG_DIR ) . $domain . '/' . $domain . '-' . $locale . '.mo' );
		load_plugin_textdomain( $domain, FALSE, basename( dirname( __FILE__ ) ) . '/languages' );
	}

	/**
	 * Register query vars.
	 *
	 * @since 0.1.0
	 */
	public function query_vars( $vars ) {
		$vars[] = 'email';
		$vars[] = 'subscribe';
		return $vars;
	}

	/**
	 * Register and enqueue JavaScript files.
	 *
	 * @since 0.1.0
	 */
	public function enqueue_scripts() {

		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		wp_enqueue_script(
			'simplechimp-plugin-script',
			plugins_url( 'js/public.js', __FILE__ ),
			array( 'jquery' ),
			self::VERSION
		);

		wp_localize_script(
			'simplechimp-plugin-script',
			'simplechimpVars',
			array(
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
				'action' => 'simplechimp_subscribe',
			)
		);
	}

	/**
	 * Process subscription form.
	 *
	 * Redirects if not doing ajax, else echoes message.
	 *
	 * @todo Display actual MailChimp error message if WP_DEBUG is true.
	 *
	 * @since 0.2.0
	 */
	public function subscription_form_submit() {

		if ( empty( $_POST['simplechimp_email'] ) ) {
			return false;
		}

		if ( ! wp_verify_nonce( $_POST['simplechimp_subscribe'], 'simplechimp_subscribe' ) ) {

			$args['subscribe'] = 'error_nonce';

		} elseif ( ! is_email( $_POST['simplechimp_email'] ) ) {

			$args['subscribe'] = 'invalid_email';
			$args['email'] = urlencode( $_POST['simplechimp_email'] );

		} else {

			$subscribe = $this->subscribe( $_POST['simplechimp_email'] );

			if ( is_wp_error( $subscribe ) ) {

				$args['subscribe'] = $subscribe->get_error_code();

				if ( 'already' == $subscribe->get_error_code() ) {
					$args['email'] = urlencode( $subscribe->get_error_message() );
				}

			} else {
				$args['subscribe'] = 'success';
			}
		}

		$doing_ajax = defined( 'DOING_AJAX' ) && DOING_AJAX;

		if ( $doing_ajax ) {
			/**
			 * Status messages.
			 *
			 * @var array
			 */
			$messages = apply_filters( 'simplechimp_messages', $this->messages );
			if ( 'success' === $args['subscribe'] ) {
				wp_send_json_success( $messages[ $args['subscribe'] ] );
			} else {
				wp_send_json_error( $messages[ $args['subscribe'] ] );
			}

		} else {
			wp_safe_redirect( add_query_arg( $args ) );
			exit;
		}
	}

	/**
	 * MailChimp API subscription request.
	 *
	 * @since 0.1.0
	 *
	 * @param string $email Email address
	 * @return true|WP_Error Returns true if subscribed, else returns WP_Error object
	 */
	public function subscribe( $email ) {

		/**
		 * Mailchimp API Key.
		 *
		 * @var string
		 */
		$api_key = apply_filters( 'simplechimp_api_key', '' );
		$api_key = trim( $api_key );

		if ( ! $api_key ) {
			return new WP_Error( 'error', __( 'API Key not defined', 'simplechimp' ) );
		}

		/**
		 * Mailchimp API Key.
		 *
		 * @var string
		 */
		$list_id = apply_filters( 'simplechimp_list_id', '' );
		$list_id = trim( $list_id );

		if ( ! $list_id ) {
			return new WP_Error( 'error', __( 'List ID not defined', 'simplechimp' ) );
		}

		$body = json_encode([
			'email_address' => $email,
			'status'        => 'pending',
		]);

		$datacenter    = explode( '-', $api_key );
		$datacenter    = empty( $datacenter[1] ) ? 'us1' : $datacenter[1];
		$member_id     = md5( strtolower( $email ) );

		$url = sprintf(
			'https://%s.api.mailchimp.com/3.0/lists/%s/members/%s',
			$datacenter,
			$list_id,
			$member_id
		);

		$args = array(
			'method'      => 'PUT',
			'timeout'     => 5,
			'redirection' => 5,
			'httpversion' => '1.1',
			'user-agent'  => 'SimpleChimp WordPress Plugin/' . get_bloginfo( 'url' ),
			'headers'     => array( 'Authorization' => 'apikey ' . $api_key ),
			'body'        => $body
		);

		$response = wp_remote_post( $url, $args );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		$response_body = json_decode( wp_remote_retrieve_body( $response ) );

		if ( 200 != $response_code ) {

			if ( 214 == $response_code ) { // Currently unused by this plugin.

				return new WP_Error( 'already', esc_html( $email ) );

			} else {

				return new WP_Error( 'error', esc_html( $response_body->detail ) );
			}
		}

		return true;
	}

	/**
	 * Display subscription form
	 *
	 * @since 0.1.0
	 *
	 * @return string
	 */
	public function form( $id = false ) {

		$labels = array(
			'label'       => __( 'E-mail', 'simplechimp' ),
			'submit'      => __( 'Subscribe', 'simplechimp' ),
			'placeholder' => __( 'E-mail', 'simplechimp' )
		);

		/**
		 * Labels for the subscription form.
		 *
		 * @var array
		 */
		$labels = apply_filters( 'simplechimp_labels', $labels );

		/** This filter is documented in class-simplechimp.php */
		$messages = apply_filters( 'simplechimp_messages', $this->messages );

		$id      = ( $id ) ? 'id="' . esc_attr( $id ) . '"' : '';
		$action  = esc_attr( remove_query_arg( array( 'subscribe', 'email' ) ) );
		$key     = get_query_var( 'subscribe' );
		$class   = '';
		$style   = ' style="display:none;"';
		$message = '';
		$value   = esc_attr( get_query_var( 'email' ) );

		if ( array_key_exists( $key, $messages ) ) {
			$class   = ( 'success' == $key ) ? '' : ' error';
			$style   = '';
			$message = $messages[$key];
		}

		require( plugin_dir_path( __FILE__ ) . 'templates/subscription-form.php' );
	}
}

global $simplechimp;
$simplechimp = SimpleChimp::get_instance();
