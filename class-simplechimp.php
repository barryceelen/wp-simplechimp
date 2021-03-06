<?php
/**
 * Main plugin class
 *
 * @package   SimpleChimp
 * @author    Barry Ceelen <b@rryceelen.com>
 * @license   GPL-2.0+
 * @link      https://github.com/barryceelen/simplechimp
 * @copyright 2013 Barry Ceelen
 */

/**
 * Plugin class.
 *
 * @package SimpleChimp
 * @author  Barry Ceelen <b@rryceelen.com>
 */
class SimpleChimp {

	/**
	 * Instance of this class.
	 *
	 * @since 0.1.0
	 *
	 * @var object
	 */
	protected static $instance = null;

	/**
	 * Plugin version, used for cache-busting of style and script file references.
	 *
	 * @since 0.2.0
	 *
	 * @var string
	 */
	const VERSION = '0.2.1';

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
			self::$instance = new self();
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

		// Register action used for displaying the form.
		add_action( 'simplechimp', array( $this, 'form' ) );
	}

	/**
	 * Get plugin options.
	 *
	 * @since 0.2.1
	 * @return array
	 */
	protected function get_options() {

		$defaults = array(
			'api_key'  => '',
			'list_id'  => '',
			'labels'   => array(
				'label'       => __( 'E-mail', 'simplechimp' ),
				'submit'      => __( 'Subscribe', 'simplechimp' ),
				'placeholder' => __( 'E-mail', 'simplechimp' ),
			),
			'messages' => array(
				'error'         => __( 'An error occurred.', 'simplechimp' ),
				'error_nonce'   => __( 'An error occurred, try reloading the page.', 'simplechimp' ),
				'invalid_email' => __( 'Invalid email address.', 'simplechimp' ),
				'already'       => __( 'Already subscribed.', 'simplechimp' ),
				'success'       => __( 'Thank you for subscribing.', 'simplechimp' ),
			),
		);

		return apply_filters( 'simplechimp_options', $defaults );
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
		load_plugin_textdomain( $domain, false, basename( dirname( __FILE__ ) ) . '/languages' );
	}

	/**
	 * Register query vars.
	 *
	 * @since 0.1.0
	 * @param array $vars List of whitelisted query vars.
	 * @return array
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
			self::VERSION,
			true
		);

		wp_localize_script(
			'simplechimp-plugin-script',
			'simplechimpVars',
			array(
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
				'action'  => 'simplechimp_subscribe',
			)
		);
	}

	/**
	 * Process subscription form.
	 *
	 * Redirects if not doing ajax, else echoes message.
	 *
	 * @since 0.2.0
	 *
	 * @todo Display actual MailChimp error message if WP_DEBUG is true
	 */
	public function subscription_form_submit() {

		if ( empty( $_POST['simplechimp_email'] ) ) {
			return false;
		}

		if ( ! wp_verify_nonce( $_POST['simplechimp_subscribe'], 'simplechimp_subscribe' ) ) {
			$args['subscribe'] = 'error_nonce';
		} elseif ( ! is_email( $_POST['simplechimp_email'] ) ) {
			$args['subscribe'] = 'invalid_email';
			$args['email']     = rawurlencode( $_POST['simplechimp_email'] );
		} else {
			$subscribe = $this->subscribe( $_POST['simplechimp_email'] );
			if ( is_wp_error( $subscribe ) ) {
				$args['subscribe'] = $subscribe->get_error_code();
				if ( 'already' === $subscribe->get_error_code() ) {
					$args['email'] = rawurlencode( $subscribe->get_error_message() );
				}
			} else {
				$args['subscribe'] = 'success';
			}
		}

		$doing_ajax = defined( 'DOING_AJAX' ) && DOING_AJAX;

		if ( $doing_ajax ) {
			$options = $this->get_options();
			die( wp_kses_post( $options['messages'][ $args['subscribe'] ] ) );
		} else {
			wp_safe_redirect( add_query_arg( $args ) );
			exit;
		}
	}

	/**
	 * MailChimp API subscription request.
	 *
	 * @param string $email Email address.
	 * @return true|WP_Error Returns true if subscribed, else returns WP_Error object
	 * @since 0.1.0
	 */
	public function subscribe( $email ) {

		$options = $this->get_options();

		$request_body = array(
			'id'    => $options['list_id'],
			'email' => array( 'email' => $email ),
		);

		require_once 'inc/class-mailchimp-api.php';
		$mailchimp = new MailChimp( $options['api_key'] );
		$response  = $mailchimp->call( 'lists/subscribe', $request_body );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		if ( 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
			$response_body = json_decode( wp_remote_retrieve_body( $response ), true );
			if ( 214 === (int) $response_body['code'] ) {
				return new WP_Error( 'already', $email );
			} else {
				return new WP_Error( 'error', $response_body['error'] );
			}
		}

		return true;
	}

	/**
	 * Display subscription form
	 *
	 * @since 0.1.0
	 * @param string $id ID attribute of the form.
	 */
	public function form( $id = false ) {

		$options = $this->get_options();

		$id      = ( $id ) ? 'id="' . esc_attr( $id ) . '"' : '';
		$action  = remove_query_arg( array( 'subscribe', 'email' ) );
		$key     = get_query_var( 'subscribe' );
		$class   = '';
		$style   = ' style="display:none;"';
		$message = '';
		$value   = get_query_var( 'email' );

		if ( array_key_exists( $key, $options['messages'] ) ) {
			$class   = ( 'success' === $key ) ? '' : ' error';
			$style   = '';
			$message = $options['messages'][ $key ];
		}

		require plugin_dir_path( __FILE__ ) . 'views/subscription-form.php';
	}
}
