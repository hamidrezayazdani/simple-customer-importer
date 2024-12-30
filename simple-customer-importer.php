<?php
/*
Plugin Name: Simple Customer Importer
Plugin URI: https://example.com/
Description: A plugin to import customers from an Excel file.
Version: 1.0.0
Author: HamidReza Yazdani
Author URI: https://example.com/
Text Domain: simple-customer-importer
Domain Path: /languages
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! defined( 'YSCI_VERSION' ) ) {
	define( 'YSCI_VERSION', '1.0.0' );
}

if ( ! defined( 'YSCI_DIR' ) ) {
	define( 'YSCI_DIR', plugin_dir_path( __FILE__ ) );
}

if ( ! defined( 'YSCI_URI' ) ) {
	define( 'YSCI_URI', plugin_dir_url( __FILE__ ) );
}

// Include PhpSpreadsheet
require_once __DIR__ . '/vendor/autoload.php';

// Include necessary files
require_once YSCI_DIR . 'includes/admin-page.php';
require_once YSCI_DIR . 'includes/import-functions.php';

if ( ! function_exists( 'ysci_create_customer_role' ) ) {

	/**
	 * Register activation hook to create the customer role
	 *
	 * @return void
	 */
	function ysci_create_customer_role() {
		if ( ! get_role( 'customer' ) ) {
			add_role( 'customer', 'Customer', get_role( 'subscriber' )->capabilities );
		}
	}

	register_activation_hook( __FILE__, 'ysci_create_customer_role' );
}

if ( ! function_exists( 'ysci_enqueue_scripts' ) ) {

	/**
	 * Enqueue scripts and styles
	 *
	 * @param [type] $hook
	 *
	 * @return void
	 */
	function ysci_enqueue_scripts( $hook ) {
		if ( $hook != 'users_page_import-customers' ) {
			return;
		}

		wp_enqueue_style( 'sci-styles', YSCI_URI . 'assets/css/styles.css', array(), YSCI_VERSION );
		wp_enqueue_script( 'sci-scripts', YSCI_URI . 'assets/js/scripts.js', array( 'jquery' ), YSCI_VERSION, true );

		wp_localize_script( 'sci-scripts', 'ysciParams', array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'nonce'    => wp_create_nonce( 'ysci_nonce' )
		) );
	}

	add_action( 'admin_enqueue_scripts', 'ysci_enqueue_scripts' );
}

if ( ! function_exists( 'ysci_add_import_customers_menu' ) ) {

	/**
	 * Add submenu under Users
	 *
	 * @return void
	 */
	function ysci_add_import_customers_menu() {
		add_submenu_page(
			'users.php',
			'Import Customers',
			'Import Customers',
			'manage_options',
			'import-customers',
			'ysci_import_customers_page'
		);
	}

	add_action( 'admin_menu', 'ysci_add_import_customers_menu' );
}