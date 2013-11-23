SimpleChimp
===========

Basic [MailChimp](http://mailchimp.com) subscription form for WordPress.

The plugin has no settings page. To specify your Mailchimp API key and list id, add a filter to 'simplechimp_options', eg. via the [Simplechimp options plugin](https://gist.github.com/barryceelen/7619021).


	add_filter( 'simplechimp_options', 'myprefix_filter_options', 9999 );

	public function myprefix_filter_options( $options ) {
		$options['list_id'] = 'yourlistid';
		$options['api_key'] = 'yourapikey';
		return $options;
	}

##Display the subscription form

SimpleChimp adds an [action hook](http://codex.wordpress.org/Glossary#Action) which displays the subscription form.

`<?php do_action( 'simplechimp' ); ?>`
