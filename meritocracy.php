<?php
/**
 * Plugin Name: Meritocracy
 * Plugin URI: https://mycred.me
 * Description: Expands the buyCred and cashCred add-on in myCred to allow points exchanges via MyNearWallet.
 * Tags: mycred, nearprotocol, nearcoin, nearcrypto, gamification, points, rewards, loyalty
 * Version: 1.3
 * Author: myCred
 * Author URI: https://mycred.me
 * Tested up to: WP 6.6.1
 * Text Domain: meritocracy
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 */
if ( ! class_exists( 'meritocracy_Gateway_Core' ) ) :
	final class meritocracy_Gateway_Core {

		// Plugin Version
		public $version             = '1.3';

		// Plugin Slug
		public $slug                = 'meritocracy';

		// Textdomain
		public $domain              = 'meritocracy';

		// Plugin name
		public $plugin_name         = 'Meritocracy';



		// Plugin file
		public $plugin              = '';

		// Instnace
		protected static $_instance = NULL;

		// Current session
		public $session             = NULL;

		/**
		 * Setup Instance
		 * @since 1.0.0
		 * @version 1.0.0
		 */
		public static function instance() {
			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self();
			}
			return self::$_instance;
		}

		/**
		 * Not allowed
		 * @since 1.0.0
		 * @version 1.0.0
		 */
		public function __clone() { _doing_it_wrong( __FUNCTION__, 'Cheatin&#8217; huh?', $this->version ); }

		/**
		 * Not allowed
		 * @since 1.0.0
		 * @version 1.0.0
		 */
		public function __wakeup() { _doing_it_wrong( __FUNCTION__, 'Cheatin&#8217; huh?', $this->version ); }

		/**
		 * Define
		 * @since 1.0.0
		 * @version 1.0.0
		 */
		private function define( $name, $value ) {
			if ( ! defined( $name ) )
				define( $name, $value );
		}

		/**
		 * Require File
		 * @since 1.0.0
		 * @version 1.0.0
		 */
		public function file( $required_file ) {
			if ( file_exists( $required_file ) )
				require_once $required_file;
		}

		/**
		 * Construct
		 * @since 1.0.0
		 * @version 1.0.0
		 */
		public function __construct() {

			$this->define_constants();
			$this->includes();
			$this->mycred();

			$this->plugin = $this->slug . '/' . $this->slug . '.php';

		}

		/**
		 * Define Constants
		 * First, we start with defining all requires constants if they are not defined already.
		 * @since 1.0.0
		 * @version 1.0.0
		 */
		private function define_constants() {

			$this->define( 'MERITOCRACY_VERSION',      $this->version );
			$this->define( 'MERITOCRACY_SLUG',         $this->slug );

			$this->define( 'MYCRED_SLUG',                'mycred' );
			$this->define( 'MYCRED_DEFAULT_TYPE_KEY',    'mycred_default' );

			$this->define( 'MERITOCRACY',              __FILE__ );
			$this->define( 'MERITOCRACY_ROOT_DIR',     plugin_dir_path( MERITOCRACY ) );
			$this->define( 'MERITOCRACY_INCLUDES_DIR', MERITOCRACY_ROOT_DIR . 'includes/' );
			$this->define( 'MERITOCRACY_GATEWAY_DIR',  MERITOCRACY_ROOT_DIR . 'gateways/' );
			if ( ! class_exists( 'Meritocracy' ) ) {
			$this->define( 'MERITOCRACY_LIB_DIR',      MERITOCRACY_ROOT_DIR . 'lib/meritocracy-php/' );
			}

		}

		/**
		 * Include Plugin Files
		 * @since 1.0.0
		 * @version 1.0.0
		 */
		public function includes() {

			$this->file( MERITOCRACY_INCLUDES_DIR . 'functions.php' );

		}

		/**
		 * WordPress
		 * Next we hook into WordPress
		 * @since 1.0.0
		 * @version 1.0.0
		 */
		public function mycred() {

			register_activation_hook(   MERITOCRACY, array( __CLASS__, 'activate_plugin' ) );
			register_deactivation_hook( MERITOCRACY, array( __CLASS__, 'deactivate_plugin' ) );
			register_uninstall_hook(    MERITOCRACY, array( __CLASS__, 'uninstall_plugin' ) );

			add_action( 'mycred_init',                           array( $this, 'load_textdomain' ) );

			// add_filter( 'mycred_buycred_refs',                   array( $this, 'add_reference' ) );
			add_filter( 'mycred_setup_gateways',                 array( $this, 'add_buycred_gateway' ) );
			add_filter( 'mycred_cashcred_setup_gateways', array( $this, 'add_cashcred_gateway' ) );
			// add_filter( 'mycred_buycred_log_refs',               array( $this, 'purchase_log' ) );
			add_action( 'mycred_buycred_load_gateways',          array( $this, 'load_buycred_gateways' ) );
			add_action( 'mycred_cashcred_load_gateways',          array( $this, 'load_cashcred_gateways' ) );
		}

		/**
		 * Load Textdomain
		 * @since 1.0.0
		 * @version 1.0.0
		 */
		public function load_textdomain() {

			// Load Translation
			$locale = apply_filters( 'plugin_locale', get_locale(), $this->domain );

			load_textdomain( $this->domain, WP_LANG_DIR . '/' . $this->slug . '/' . $this->domain . '-' . $locale . '.mo' );
			load_plugin_textdomain( $this->domain, false, dirname( plugin_basename( __FILE__ ) ) . '/lang/' );

		}

		/**
		 * Add Gateway
		 * @since 2.0
		 * @version 1.0.0
		 */
		public function load_buycred_gateways() {
			$this->file( MERITOCRACY_GATEWAY_DIR . 'mycred-buycred-meritocracy.php' );
		}

		/**
		 * Add Gateway
		 * @since 2.0
		 * @version 1.0.0
		 */
		public function load_cashcred_gateways() {
			$this->file( MERITOCRACY_GATEWAY_DIR . 'mycred-cashcred-meritocracy.php' );
		}

		/**
		 * Add buyCRED Gateway
		 * @since 1.0
		 * @version 1.0.0
		 */
		public function add_buycred_gateway( $gateways ) {

		$gateways['meritocracy'] = array(
			'title'         => 'BuyNear',
			'callback'      => array( 'myCRED_Meritocracy' ),
			'documentation' => 'http://codex.mycred.me',
			'icon'          => 'dashicons-admin-generic',
			'sandbox'       => false,
			'external'      => true,
			'custom_rate'   => true
		);

			return $gateways;
		}


		/**
		 * Add cashCRED Gateway
		 * @since 1.0
		 * @version 1.0.0
		 */
		public function add_cashcred_gateway( $gateways ) {

			$gateways['meritocracy'] = array(
				'title'         => 'CashNear',
				'callback'      => array( 'myCRED_CashCred_Meritocracy' ),
				'documentation' => 'http://codex.mycred.me',
				'icon'          => 'dashicons-admin-generic',
				'sandbox'       => false,
				'external'      => false,
				'custom_rate'   => true
			);

				return $gateways;
			}




		/**
		 * Activate
		 * @since 1.0.0
		 * @version 1.0.0
		 */
		public static function activate_plugin() {

			global $wpdb;

			$message = array();

			// WordPress check
			$wp_version = $GLOBALS['wp_version'];
			if ( version_compare( $wp_version, '4.0', '<' ) )
				$message[] = __( 'This myCRED Add-on requires WordPress 4.0 or higher. Version detected:', 'meritocracy' ) . ' ' . $wp_version;

			// PHP check
			$php_version = phpversion();
			if ( version_compare( $php_version, '5.3.3', '<' ) )
				$message[] = __( 'This myCRED Add-on requires PHP 5.3.3 or higher. Version detected: ', 'meritocracy' ) . ' ' . $php_version;

			// SQL check
			$sql_version = $wpdb->db_version();
			if ( version_compare( $sql_version, '5.0', '<' ) )
				$message[] = __( 'This myCRED Add-on requires SQL 5.0 or higher. Version detected: ', 'meritocracy' ) . ' ' . $sql_version;

			// myCRED Check
			if ( defined( 'myCRED_VERSION' ) && version_compare( myCRED_VERSION, '1.6', '<' ) )
				$message[] = __( 'This add-on requires myCRED 1.6 or higher. Version detected:', 'meritocracy' ) . ' ' . myCRED_VERSION;

			// Not empty $message means there are issues
			if ( ! empty( $message ) ) {

				$error_message = implode( "\n", $message );
				die( __( 'Sorry but your WordPress installation does not reach the minimum requirements for running this add-on. The following errors were given:', 'meritocracy' ) . "\n" . $error_message );

			}

		}

		/**
		 * Deactivate
		 * @since 1.0.0
		 * @version 1.0.0
		 */
		public static function deactivate_plugin() { }

		/**
		 * Uninstall
		 * Deletes:
		 * - Stripe Custom IDs
		 * - Stripe Subscription Entries
		 * @since 1.0.0
		 * @version 1.0.0
		 */
		public static function uninstall_plugin() {
			// Good bye!

		}

	}
endif;

function meritocracy_gateway() {
	return meritocracy_Gateway_Core::instance();
}
meritocracy_gateway();

