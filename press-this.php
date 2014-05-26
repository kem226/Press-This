<?php
/*
Plugin Name: Press This
Plugin URI: http://wordpress.org/extend/plugins/press-this/
Description: Posting images, links, and cat gifs will never be the same.
Version: 0.1
Author: Press This Team
Author URI: https://corepressthis.wordpress.com/
Text Domain: press-this
Domain Path: /languages
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/

/**
 * Class WpPressThis
 */
class WpPressThis {
	/**
	 * Constructor
	 */
	public function __construct() {
		// TEMP: Remove SAMEORIGIN so we can display in iframe, see TODO below.
		// @TODO: should detect if actually in iframe context, don't remove if in popup, come up with alternative to SAMEORIGIN when removing.
		remove_action( 'admin_init', 'send_frame_options_header' );
		remove_action( 'login_init', 'send_frame_options_header' );

		// Take over /wp-admin/press-this.php
		add_action( 'admin_init', array( $this, 'press_this_php_override' ), 0 );
		// Take over Press This bookmarklet code in /wp-admin/tools.php
		add_filter( 'shortcut_link', array( $this, 'shortcut_link_override' ) );

		// AJAX handling
		add_action( 'wp_ajax_press_this_site_settings', array( $this, 'press_this_ajax_site_settings' ) );
		add_action( 'wp_ajax_nopriv_press_this_site_settings', array( $this, 'press_this_ajax_site_settings' ) );
	}

	/**
	 * @return string|void Full URL to /admin/press-this.php in current install
	 */
	public function runtime_url() {
		return admin_url( 'press-this.php' );
	}

	/**
	 * @return string|void Full URL to /admin/press-this.php in current install
	 */
	public function plugin_dir_path() {
		return rtrim( plugin_dir_path( __FILE__ ), '/' );
	}

	public function plugin_dir_url() {
		return rtrim( plugin_dir_url( __FILE__ ), '/' );
	}

	/**
	 * @return mixed Press This bookmarklet JS trigger found in /wp-admin/tools.php
	 */
	public function shortcut_link_override() {
		$url  = esc_js( self::runtime_url() );
		$link = "javascript: var u='{$url}';\n";
		$link .= file_get_contents( self::plugin_dir_path() . '/js/bookmarklet.js' );
		return str_replace( array( "\r", "\n", "\t" ), '', $link );
	}

	/**
	 * Takes over /wp-admin/press-this.php for backward compatibility and while in feature-as-plugin mode
	 */
	public function press_this_php_override() {
		// Simply drop the following test once/if this becomes the standard Press This code in core
		if ( ! preg_match( '/\/press-this\.php$/', $_SERVER['SCRIPT_NAME'] ) )
			return;

		$action = '';

		if ( ! empty( $_GET['a'] ) ) {
			switch ( $_GET['a'] ) {
				case 'init':
					$action = $_GET['a'];
					break;
			}
		}

		switch ( $action ) {
			case 'init':
				self::serve_app_html();
				return;
			default:
				return;
		}
	}

	/**
	 *
	 */
	public function serve_app_html() {
		$_POST['_runtime_url']    = self::runtime_url();
		$_POST['_plugin_dir_url'] = self::plugin_dir_url();
		$json = json_encode( $_POST );
		$app_css = self::plugin_dir_url() . '/css/app.css';
		$load_js = self::plugin_dir_url() . '/js/load.js';
		echo <<<________HTMLDOC
<!DOCTYPE html>
<html>
<head lang="en">
	<meta charset="UTF-8">
	<title></title>
	<link rel='stylesheet' id='all-css' href='{$app_css}' type='text/css' media='all' />
	<script language="JavaScript">
		window.wp_pressthis_data = {$json};
	</script>
	<script src="//ajax.googleapis.com/ajax/libs/jquery/1.11.0/jquery.min.js" language="JavaScript"></script>
	<script src="{$load_js}" language="JavaScript"></script>
</head>
<body>
	<div id='press_this_app_container'></div>
</body>
</html>
________HTMLDOC;
		die();
	}

	/**
	 *
	 */
	public function press_this_ajax_site_settings() {
		header( 'content-type: application/json' );
		echo json_encode(array(
			'nonce' => wp_create_nonce( 'press_this_site_settings' ),
			'i18n'  => array(
				'Welcome to Press This!' => __('Welcome to Press This!', 'press-this'),
			),
		));
		die();
	}
}

new WpPressThis;
