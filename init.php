<?php
/*
Plugin Name: Easy Digital Downloads + Xero
Plugin URI: https://plugify.io
Description: Integrate your Easy Digital Downloads store with your Xero account
Author: Plugify
Version: 0.1
Author URI: https://plugify.io
*/

// Ensure WordPress has been bootstrapped
if( !defined( 'ABSPATH' ) )
	exit;

$path = trailingslashit( dirname( __FILE__ ) );

// Ensure our class dependencies class has been defined
$inits = array(
	'Xero_Resource' => $path . 'lib/class.xero-resource.php',
	'Xero_Contact' => $path . 'lib/class.xero-contact.php',
	'Xero_Invoice' => $path . 'lib/class.xero-invoice.php',
	'Xero_Line_Item' => $path . 'lib/class.xero-line-item.php'
);

foreach( $inits as $class => $file )
	require_once $file;

if( !class_exists( 'Plugify_EDD_Xero' ) )
require_once( $path . 'class.edd-xero.php' );

// Boot Plugify Xero integration for EDD
new Plugify_EDD_Xero();

?>