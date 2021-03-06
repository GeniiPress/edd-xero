<?php
/**
 * Plugin Name: Easy Digital Downloads - Xero
 * Plugin URI: https://shopplugins.com
 * Description: Integrate Easy Digital Downloads with the Xero accounting system
 * Author: Daniel Espinoza
 * Version: 1.2.10
 * Author URI: https://shopplugins.com
 * Text Domain: edd-xero
 */

// Ensure WordPress has been bootstrapped
if( !defined( 'ABSPATH' ) ) {
	exit;
}

$path = trailingslashit( dirname( __FILE__ ) );

// Ensure our class dependencies class has been defined
$inits = array(
	'Xero_Resource' => $path . 'lib/class.xero-resource.php',
	'Xero_Contact' => $path . 'lib/class.xero-contact.php',
	'Xero_Invoice' => $path . 'lib/class.xero-invoice.php',
	'Xero_Line_Item' => $path . 'lib/class.xero-line-item.php',
	'Xero_Payment' => $path . 'lib/class.xero-payment.php'
);

foreach( $inits as $class => $file ) {
	require_once $file;
}

if( !class_exists( 'Plugify_EDD_Xero' ) ) {
	require_once( $path . 'class.edd-xero.php' );
}

// Before core plugin loads, instantiate the EDD license
if( class_exists( 'EDD_License' ) ) {
	new EDD_License( __FILE__, 'EDD Xero', '1.2.10', 'Daniel Espinoza' );
}

// Boot Plugify Xero integration for EDD
new Plugify_EDD_Xero( plugin_basename( __FILE__ ) );
