<?php
/**
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
	 * @since    0.1.0
	 *
	 * @var      object
	 */
	protected static $instance = null;

	/**
	 * Plugin version, used for cache-busting of style and script file references.
	 *
	 * @since   0.2.0
	 *
	 * @var     string
	 */
	const VERSION = '0.2.0';

	/**
	 * Filterable options.
	 *
	 * @since    0.1.0
	 *
	 * @var      array
	 */
	public static $options = array();

	/**
	 * Return an instance of this class.
	 *
	 * @since     0.1.0
	 *
	 * @return    object    A single instance of this class.
	 */
	public static function get_instance() {

		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 * Initialize the plugin by setting options, localization, actions and filters.
	 *
	 * As there is no settings page for the plugin, add your api_key and list_id via
	 * adding a filter to 'simplechimp_options', eg. in your functions.php file:
	 *
	 * add_filter( 'simplechimp_options', 'myprefix_filter_options', 9999 );
	 *
	 * public function myprefix_filter_options( $options ) {
	 * 	$options['list_id'] = 'yourlistid';
	 *  $options['api_key'] = 'yourapikey';
	 *  return $options;
	 * }
	 *
	 * @since     0.1.0
	 */
	private function __construct() {
		$defaults = array(
			'api_key' => '',
			'list_id' => '',
			'labels'  => array(
				'label'       => __( 'E-mail', 'simplechimp' ),
				'submit'      => __( 'Subscribe', 'simplechimp' ),
				'placeholder' => __( 'E-mail', 'simplechimp' )
			),
			'messages' => array(
				'error'         => __( 'An error occurred.', 'simplechimp' ),
				'error_nonce'   => __( 'An error occurred, try reloading the page.', 'simplechimp' ),
				'invalid_email' => __( 'Invalid email address.', 'simplechimp' ),
				'already'       => __( 'Already subscribed.', 'simplechimp' ),
				'success'       => __( 'Thank you for subscribing.', 'simplechimp' )
			)
		);

		self::$options = apply_filters( 'simplechimp_options', $defaults );

		// Load plugin text domain
		add_action( 'init', array( $this, 'load_plugin_textdomain' ) );

		// Register query vars
		add_filter( 'query_vars', array( $this, 'query_vars' ) );

		// Load public-facing JavaScript.
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		// Handle subscription request
		if ( isset( $_POST['simplechimp-submit'] ) ) {
			add_action( 'template_redirect', array( $this, 'subscription_form_submit' ) );
		}

		// Add ajax handlers
		add_action( 'wp_ajax_simplechimp_subscribe', array( $this, 'subscription_form_submit' ) );
		add_action( 'wp_ajax_nopriv_simplechimp_subscribe', array( $this, 'subscription_form_submit' ) );

		// Register action used for displaying the form
		add_action( 'simplechimp', array( $this, 'form' ) );
	}

	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    0.2.0
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
	 * @since  0.1.0
	 */
	public function query_vars( $vars ) {
		$vars[] = 'email';
		$vars[] = 'action';
		$vars[] = 'subscribe';
		return $vars;
	}

	/**
	 * Register and enqueues public-facing JavaScript files.
	 *
	 * @since    0.1.0
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
				'nonce' => esc_js( wp_create_nonce( 'simplechimp_ajax_subscribe' ) )
				)
			);
	}

	/**
	 * Handle subscription form submit.
	 *
	 * @since  0.2.0
	 * @return void
	 */
	public function subscription_form_submit() {

		if ( empty( $_POST['email'] ) ) {
			return false;
		}

		if ( isset( $_POST['simplechimp_subscribe'] ) && ! wp_verify_nonce( $_POST['simplechimp_subscribe'], 'simplechimp_subscribe' ) ) {
			$args['subscribe'] = 'error_nonce';
		} else {
			$subscribe = $this->subscribe( $_POST['email'] );
			if ( is_wp_error( $subscribe ) ) {
				$args['subscribe'] = $subscribe->get_error_code();
				if ( in_array( $subscribe->get_error_code(), array( 'invalid_email', 'already' ) ) ) {
					$args['email'] = urlencode( $subscribe->get_error_message() );
				}
			} else {
				$args['subscribe'] = 'success';
			}
		}

		$doing_ajax = defined( 'DOING_AJAX' ) && DOING_AJAX;

		if ( $doing_ajax ) {
			echo self::$options['messages'][$args['subscribe']];
			exit;
		} else {
			wp_safe_redirect( add_query_arg( $args ) );
			exit;
		}
	}

	/**
	 * Process subscription.
	 *
	 * @param string $email Email address
	 * @return true|WP_Error Returns true if subscribed, else returns WP_Error object
	 * @since  0.1.0
	 */
	public function subscribe( $email ) {

		if ( ! is_email( $email ) ) {
			return new WP_Error( 'invalid_email', $email );
		}

		require( plugin_dir_path( __FILE__ ) . '/vendor/MCAPI.class.php' );
		$api = new MCAPI( self::$options['api_key'] );

		if ( true === $api->listSubscribe( self::$options['list_id'], sanitize_email( $email ), '' ) ) {
			return true;
		} elseif ( '214' == $api->errorCode ) {
			return new WP_Error( 'already', $email );
		} else {
			return new WP_Error( 'error', $api->errorMessage );
		}
	}

	/**
	 * Display subscription form
	 *
	 * @return string
	 * @since  0.1.0
	 */
	public function form( $id = false ) {
		$id = ( $id ) ? 'id="' . esc_attr( $id ) . '"': '';
		$action = remove_query_arg( array( 'subscribe', 'email' ) );
		$key   = get_query_var( 'subscribe' );
		$class = '';
		$style = 'style="display:none;"';
		$message = '';
		$value = get_query_var( 'email' );

		if ( array_key_exists( $key, self::$options['messages'] ) ) {
			$class = ( 'success' == $key ) ? '' : ' error';
			$style = '';
			$message = self::$options['messages'][$key];
		}


		require( plugin_dir_path( __FILE__ ) . 'views/subscription-form.php' );

	}
}
