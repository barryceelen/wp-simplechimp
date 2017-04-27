SimpleChimp
===========

Basic [MailChimp](http://mailchimp.com) subscription form for WordPress.

The plugin has no settings page. To specify your Mailchimp API key and list id, add a filter to 'simplechimp_options', eg. via the [SimpleChimp Options](https://gist.github.com/barryceelen/7619021) plugin.


	// Filter simplechimp options.
	add_filter( 'simplechimp_options', 'myprefix_filter_simplechimp_options' );

	/**
	 * Filter simplechimp options.
	 *
	 * @param array $options Options for the Simplechimp plugin.
	 * @return array Filtered options.
	 */
	function myprefix_filter_simplechimp_options( $options ) {

		$options['list_id'] = 'yourlistid';
		$options['api_key'] = 'yourapikey';

		return $options;
	}

## Display the subscription form

SimpleChimp adds an [action hook](http://codex.wordpress.org/Glossary#Action) which displays the subscription form.  

**Note:** No styles are added, you are on your own.

`<?php do_action( 'simplechimp' ); ?>`
