<?php
/**
 * Main plugin file
 *
 * @package SimpleChimp
 * @version 0.2.1
 */

/*
 * Plugin Name: SimpleChimp
 * Plugin URI: https://github.com/barryceelen/simplechimp
 * Description: Basic MailChimp subscription form
 * Author: Barry Ceelen
 * Author URI: http://github.com/barryceelen/
 * Version: 0.2.1
 * License: GPL2+
 */

require_once 'class-simplechimp.php';

add_action( 'plugins_loaded', array( 'SimpleChimp', 'get_instance' ) );
