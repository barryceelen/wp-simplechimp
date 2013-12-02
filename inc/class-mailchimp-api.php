<?php
/**
 * Super-simple, minimum abstraction MailChimp API v2 wrapper.
 *
 * Adaption of Drew McLellan's Mailchimp API.
 * Uses wp_remote_post in stead of cURL.
 *
 * @see https://github.com/drewm/mailchimp-api/
 *
 * @author Drew McLellan <drew.mclellan@gmail.com>
 * @author Barry Ceelen <b@rryceelen.com>
 * @version 1.0
 */
class MailChimp {

	private $api_key;
	private $api_endpoint = 'https://<dc>.api.mailchimp.com/2.0/';
	private $verify_ssl   = false;

	/**
	 * Create a new instance
	 *
	 * @param string $api_key Your MailChimp API key
	 */
	function __construct( $api_key ) {
		$this->api_key = $api_key;
		list( , $datacentre ) = explode( '-', $this->api_key );
		$this->api_endpoint = str_replace( '<dc>', $datacentre, $this->api_endpoint );
	}

	/**
	 * Call an API method. Every request needs the API key, so that is added automatically -- you don't need to pass it in.
	 *
	 * @param  string $method The API method to call, e.g. 'lists/list'
	 * @param  array  $args   An array of arguments to pass to the method. Will be json-encoded for you.
	 * @return array          Associative array of json decoded API response.
	 */
	public function call( $method, $args = array() ) {
		return $this->_raw_request( $method, $args );
	}

	/**
	 * Performs the underlying HTTP request. Not very exciting
	 *
	 * @param  string $method The API method to be called
	 * @param  array  $body   Assoc array of parameters to be passed
	 * @return array|WP_Error assoc array of decoded result or WP_Error if the request failed
	 */
	private function _raw_request( $method, $body = array() ) {
		$body['apikey'] = $this->api_key;
		$args['body'] = json_encode( $body );
		$args['timeout'] = 10;
		$args['sslverify'] = $this->verify_ssl;
		$url = $this->api_endpoint.'/'.$method.'.json';
		$response = wp_remote_post( $url, $args );

		if ( ! is_wp_error( $response ) ) {
			$response = json_decode( wp_remote_retrieve_body( $response ), true );
		}

		return $response;
	}

}
