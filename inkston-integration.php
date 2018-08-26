<?php

/*
 * Plugin Name: Inkston Integration
 * Text Domain: inkston-integration
 * Domain Path: /languages
 * Plugin URI: https://github.com/Jon007/inkston-integration/
 * Assets URI: https://github.com/Jon007/inkston-integration/assets/
 * Author: Jonathan Moore
 * Author URI: https://jonmoblog.wordpress.com/
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Description: Integration options for inkston family sites
 * Tags: locale, language, translate, message, polylang, woocommerce
 * Contributors: jonathanmoorebcsorg
 * Version: 1.1
 * Stable Tag: 1.0
 * Requires At Least: 4.7
 * Tested Up To: 4.9.7
 * WC requires at least: 3.0.0
 * WC tested up to: 3.4.3
 * Requires PHP: 5.3
 * Version Components: {major}.{minor}.{bugfix}-{stage}{level}
 *
 * 	{major}		Major code changes / re-writes or significant feature changes.
 * 	{minor}		New features / options were added or improved.
 * 	{bugfix}	Bugfixes or minor improvements.
 * 	{stage}{level}	dev < a (alpha) < b (beta) < rc (release candidate) < # (production).
 *
 * See PHP's version_compare() documentation at http://php.net/manual/en/function.version-compare.php.
 *
 * This script is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License as published by the Free Software
 * Foundation; either version 3 of the License, or (at your option) any later
 * version.
 *
 * This script is distributed in the hope that it will be useful, but WITHOUT ANY
 * WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A
 * PARTICULAR PURPOSE. See the GNU General Public License for more details at
 * http://www.gnu.org/licenses/.
 *
 * Copyright 2017 Jonathan Moore (https://jonmoblog.wordpress.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Nothing to see here...' );
}

include_once( plugin_dir_path( __FILE__ ) . 'inkston-integration-settings.php' );

if ( ! class_exists( 'inkston_integration' ) ) {

	class inkston_integration {

		private static $instance;
		private static $wp_min_version		 = 4.7;
		private static $polylang_min_version = 2.0;

		/**
		 * enqueue scripts and hook buttons according to the options set
		 */
		public function __construct() {

			//optionally, implement different functions on front and back end
			//$is_admin = is_admin();
			//$on_front = apply_filters( 'inkston_integration_front_end', true );

			add_action( 'init', array( $this, 'activate' ) );

			add_action( 'plugins_loaded', array( __CLASS__, 'load_textdomain' ) );
			add_action( 'admin_init', array( __CLASS__, 'check_wp_version' ) );

			//register scripts and elements to be available on both back and front end
			add_action( 'admin_head', array( __CLASS__, 'ii_admin_head' ) );
			add_action( 'wp_enqueue_scripts', array( __CLASS__, 'ii_enqueue_scripts' ) );

			/*
			  if ( isset( $options[ 'saleflash_cart' ] ) ) {
			  add_action( 'woocommerce_before_cart', array( __CLASS__, 'saleflash' ) );
			  }
			  if ( isset( $options[ 'saleflash_checkout' ] ) ) {
			  add_action( 'woocommerce_before_checkout_form', array( __CLASS__, 'saleflash' ) );
			  }
			  if ( isset( $options[ 'shipping_alert_cart' ] ) ) {
			  add_action( 'woocommerce_cart_totals_after_shipping', array( __CLASS__, 'shippingalert' ), 60 );
			  }
			  if ( isset( $options[ 'shipping_alert_checkout' ] ) ) {
			  add_action( 'woocommerce_review_order_after_shipping', array( __CLASS__, 'shippingalert' ), 60 );
			  }
			 */
		}

		/**
		 * register all the strings on activation
		 */
		public static function activate() {
			//get options and load modules as appropriate
			$ii_options = ii_get_options();
			include_once( plugin_dir_path( __FILE__ ) . '_fn/_plug.php');

			/* setup inkston polylang strings */

			if ( ( function_exists( 'pll_register_string' ) ) &&
			( isset( $ii_options[ 'woofreeshippingexcept' ] )) ) {
				pll_register_string( 'woofreeshippingexcept', $ii_options[ 'woofreeshippingexcept' ], 'Inkston Integration', TRUE );
			  }
		}

		/**
		 * called in admin mode: do any extra admin stuff then our standard Front End scripts
		 */
		public static function ii_admin_head() {
			self::ii_enqueue_scripts();
		}

		/**
		 * adds .min versions of scripts unless SCRIPT_DEBUG defined
		 * also uses file timestamp as version to force update when changed
		 */
		public static function ii_enqueue_scripts() {
//          avoid adding scripts to payload unless absolutely necessary
//			$suffix	 = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
//			$csfile	 = 'css/body' . $suffix . '.css';
//			$csname	 = 'ii-body-css';
//			wp_register_style( $csname, plugin_dir_url( __FILE__ ) . $csfile, false, filemtime( plugin_dir_path( __FILE__ ) . $csfile ), 'all' );
//			wp_enqueue_style( $csname );

			/*
			  $suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

			  $csfile='css/inkston-integration' . $suffix . '.css' ;
			  wp_register_style('inkston_integration-css',	plugin_dir_url(__FILE__) . $csfile , false,
			  filemtime( plugin_dir_path(__FILE__) . $csfile), 'all' );
			  wp_enqueue_style( 'inkston_integration-css');

			  $jsfile='js/inkston-integration' . $suffix . '.js' ;
			  wp_enqueue_script( 'inkston_integration', plugin_dir_url(__FILE__) . $jsfile , array( 'jquery' ),
			  filemtime( plugin_dir_path(__FILE__) . $jsfile ), true);
			 *
			 */
		}

		/**
		 * adds .min versions of scripts unless SCRIPT_DEBUG defined
		 * also uses file timestamp as version to force update when changed
		 */
		public static function ii_enqueue_script( $csname, $csfile = '' ) {
			//default filename to script name plus .css
			if ( ! $csfile ) {
				$csfile = $csname . '.css';
			}
			//use standard or debug css directory
			$csspath = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? 'css-debug/' : 'css/';
			//register the style with wordpress
			wp_register_style( $csname, plugin_dir_url( __FILE__ ) . $csspath . $csfile, false, filemtime( plugin_dir_path( __FILE__ ) . $csspath . $csfile ), 'all' );
			//and queue it
			wp_enqueue_style( $csname );
		}

		/**
		 * Get the value of a settings field.
		 *
		 * @param string $option  settings field name
		 * @param string $section the section name this field belongs to
		 * @param string $default default text if it's not found
		 *
		 * @return mixed
		 */
		public static function get_option( $option, $section, $default = '' ) {
			$options = get_option( $section );

			if ( ! empty( $options[ $option ] ) ) {  // equivalent to: isset($options[$option]) && $options[$option]
				return $options[ $option ];
			}   // when all settings are disabled
			elseif ( isset( $options[ $option ] ) ) {
				return array();
			} else {
				return $default;
			}
		}

		public static function &get_instance() {
			if ( ! isset( self::$instance ) ) {
				self::$instance = new self;
			}
			return self::$instance;
		}

		/**
		 * load translations
		 */
		public static function load_textdomain() {
			load_plugin_textdomain( 'inkston-integration', false, 'inkston-integration/languages/' );
		}

		/**
		 * deactivates plugin if Wordpress version < 4.7
		 */
		public static function check_wp_version() {
			global $wp_version;
			if ( version_compare( $wp_version, self::$wp_min_version, '<' ) ) {
				$plugin = plugin_basename( __FILE__ );
				if ( is_plugin_active( $plugin ) ) {
					self::load_textdomain();
					if ( ! function_exists( 'deactivate_plugins' ) ) {
						require_once trailingslashit( ABSPATH ) . 'wp-admin/includes/plugin.php';
					}
					$plugin_data = get_plugin_data( __FILE__, false ); // $markup = false
					deactivate_plugins( $plugin, true ); // $silent = true
					wp_die(
					'<p>' . sprintf( __( '%1$s requires %2$s version %3$s or higher and has been deactivated.', 'inkston-integration' ), $plugin_data[ 'Name' ], 'WordPress', self::$wp_min_version ) . '</p>' .
					'<p>' . sprintf( __( 'Please upgrade %1$s before trying to reactivate the %2$s plugin.', 'inkston-integration' ), 'WordPress', $plugin_data[ 'Name' ] ) . '</p>'
					);
				}
			}
		}

	}

	//class
}//if class exists
//instantiate and make available so can be called easily
$II				 = inkston_integration::get_instance();
$GLOBALS[ 'II' ] = $II;
