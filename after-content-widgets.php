<?php
/*
* Plugin Name: After Content Widgets
* Plugin URI:
* Description: Easy way to add after content widgets. Supports posts, pages and custom post types. Light and flexible.
* Version:     1.0
* Author:      Serge Liatko
* Author URI:  http://sergeliatko.com/?utm_source=after-content-widgets&utm_medium=textlink&utm_content=authorlink&utm_campaign=wpplugins
* License:     GPL2
* License URI: https://www.gnu.org/licenses/gpl-2.0.html
* Domain Path: /languages
* Text Domain: after-content-widgets
*
* This program is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*
* Copyright 2016 Serge Liatko <contact@sergeliatko.com> https://sergeliatko.com
*
* @GitHub https://github.com/sergeliatko/after-content-widgets
* @WordPress https://wordpress.org/plugins/after-content-widgets/
*/

/** prevent direct loading of the plugin php file */
defined('ABSPATH') or die( sprintf( 'Please, do not load this file directly. File: %s', __FILE__ ) );

/** define folder path */
define( 'ACW_PATH', trailingslashit( plugin_dir_path( __FILE__ ) ) );

/** define folder url */
define( 'ACW_URL', trailingslashit( plugin_dir_url( __FILE__ ) ) );

/** define plugin version */
define( 'ACW_VERSION', '0.0.1' );

/** define content-widgets language */
if( !defined('CONTENT_WIDGETS_TXD') ) {
	define( 'CONTENT_WIDGETS_TXD', 'after-content-widgets' );
}

/** load plugin class only if does not exist */
if( !class_exists('After_Content_Widgets_Plugin') ) {

	/**
	 * Class After_Content_Widgets_Plugin
	 */
	class After_Content_Widgets_Plugin {

		/** @var  After_Content_Widgets_Plugin $instance */
		public static $instance;

		/**
		 * After_Content_Widgets_Plugin constructor.
		 */
		protected function __construct() {

			/** load plugin text domain */
			load_plugin_textdomain(
				'after-content-widgets',
				false,
				dirname( plugin_basename( __FILE__ ) ) . '/languages'
			);

			/** load the content widgets module */
			require_once( ACW_PATH . 'includes/content-widgets.php' );
		}

		/**
		 * Returns plugin instance.
		 *
		 * @return After_Content_Widgets_Plugin $instance
		 */
		public static function getInstance() {
			return ( null === static::$instance ) ? ( static::$instance = new static() ) : static::$instance;
		}

		/**
		 * Prevents cloning of the plugin instance.
		 */
		protected function __clone() {}

	}
	/** load plugin after plugins were included */
	add_action( 'plugins_loaded', array( 'After_Content_Widgets_Plugin', 'getInstance' ), 10, 0 );

}
