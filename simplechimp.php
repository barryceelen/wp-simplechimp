<?php

/**
 * @package SimpleChimp
 * @version 0.1
 */

/*
 * Plugin Name: SimpleChimp
 * Plugin URI: https://github.com/barryceelen/simplechimp
 * Description: Basic MailChimp subscription form
 * Author: Barry Ceelen
 * Author URI: http://github.com/barryceelen/
 * Version: 0.1
 * License: GPL2+
 */

defined( 'SIMPLECHIMP_PATH' ) or define( 'SIMPLECHIMP_PATH', plugin_dir_path( __FILE__ ) );
defined( 'SIMPLECHIMP_URL' ) or define( 'SIMPLECHIMP_URL', plugin_dir_url( __FILE__ ) );

class SimpleChimp {

	public $options = array();

	public static function get_instance() {
		static $instance = null;
		if ( $instance === null ) {
			$instance = new SimpleChimp();
		}
		return $instance;
	}

	/**
	 * Setup options, register actions and filters
	 */

	private function __construct() {

		$defaults = array(
			'api_key' => '',
			'list_id' => '',
			'strings' => array(
					'waiting' => __( 'Adding email address…', 'simplechimp' ),
					'invalid_email' => __( 'Invalid email address.', 'simplechimp' ),
					'nonce_error' => __( 'An error occurred, try reloading the page.', 'simplechimp' ),
					'success' => __( 'Thank you for subscribing.', 'simplechimp' ),
					'label_email' => __( 'E-mail', 'simplechimp' ),
					'label_submit' => __( 'Subscribe', 'simplechimp' ),
					'placeholder' => __( 'E-mail', 'simplechimp' )
				)
			);

		$this->options = apply_filters( 'simplechimp_options', $defaults );

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_filter( 'query_vars', array( $this, 'query_vars' ) );
		add_action( 'wp_ajax_simplechimp_subscribe', array( $this, 'subscribe' ) );
		add_action( 'wp_ajax_nopriv_simplechimp_subscribe', array( $this, 'subscribe' ) );
		add_action( 'simplechimp_show_form', array( $this, 'form' ) );
	}

	/**
	 * Enqueue scripts
	 */

	public function enqueue_scripts() {

		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		wp_enqueue_script(
			'simplechimp',
			SIMPLECHIMP_URL . "/js/simplechimp$suffix.js",
			array( 'jquery' ),
			'',
			true
		);

		wp_localize_script(
			'simplechimp',
			'SimpleChimpData',
			array(
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
				'action' => 'simplechimp_subscribe',
				'nonce' => esc_js( wp_create_nonce( 'simplechimp_ajax_subscribe' ) ),
				'waiting' => $this->options['strings']['waiting']
			)
		);

	}

	/**
	 * Register query vars
	 */

	public function query_vars( $vars ) {
		$vars[] = 'email';
		$vars[] = 'action';
		$vars[] = 'message';
		return $vars;
	}

	public function subscribe() {
		$email = sanitize_email( $_POST['email'] );
		if ( !wp_verify_nonce( $_POST['nonce'], 'simplechimp_ajax_subscribe' ) ) :
			$response = $this->options['strings']['nonce_error'];
		elseif ( !is_email( $email ) ) :
			$response = $this->options['strings']['invalid_email'];
		else :
			require_once SIMPLECHIMP_PATH . '/vendor/MCAPI.class.php';
			$api = new MCAPI( $this->options['api_key'] );
			if ( $api->listSubscribe( $this->options['list_id'], $email, '' ) === true ) {
				// User subscribed successfully, yay!
				$response = $this->options['strings']['success'];
			} else {
				// An error ocurred, return error message
				$response = $api->errorMessage;
			}
		endif;

		echo $response;
		die();
	}

	public function form() {

		/**
		 * Feedback for non-Ajax form submission
		 */

		$message = '';
		$class = '';

		if ( is_numeric( get_query_var( 'message' ) ) ) {
			switch ( get_query_var( 'message' ) )  {
				case 1:
					$message = $this->options['strings']['nonce_error'];
					$class = ' error';
					break;
				case 2:
					$message = $this->options['strings']['success'];
					break;
				case 3:
					$message = $this->options['strings']['nonce_error'];
					$error = ' error';
					break;
			}
		}

		include_once SIMPLECHIMP_PATH . 'inc/tmpl.subscription-form.php';

	}
}

add_action( 'plugins_loaded', array( 'SimpleChimp', 'get_instance' ) );
