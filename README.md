SimpleChimp
===========

Basic MailChimp subscription form for WordPress themes.

The plugin has no settings page. To specify your Mailchimp API key and list id, add a filter to 'simplechimp_options' in your theme's functions file:


	add_filter( 'simplechimp_options', 'myprefix_filter_options', 9999 );

	public function myprefix_filter_options( $options ) {
		$options['list_id'] = 'yourlistid';
		$options['api_key'] = 'yourapikey';
		return $options;
	}


##Showing the form in your theme

SimpleChimp adds an [action hook](http://codex.wordpress.org/Glossary#Action) which displays the subscription form.

`<?php do_action( 'simplechimp' ); ?>`
