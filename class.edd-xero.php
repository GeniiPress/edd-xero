<?php

// Core class for plugin functionality

final class Plugify_EDD_Xero {

	/**
	* Class constructor. Hook in to EDD
	*
	* @since 0.1
	* @return void
	*/
	public function __construct () {

		// Hook in to created payments
		add_action( 'edd_complete_purchase', array( &$this, 'create_invoice' ) );

	}

	/**
	* Handler for edd_complete_purchase hook. Fires when a purchase is completed
	*
	* @since 0.1
	*
	* @param int $payment_id ID of EDD payment.
	* @return void
	*/
	public static function create_invoice ( $payment_id ) {

		$payment = edd_get_payment_meta( $payment_id );
		$cart = edd_get_payment_meta_cart_details( $payment_id );



		/*
		Array
		(
		    [currency] => USD
		    [downloads] => a:1:{i:0;a:3:{s:2:"id";s:2:"10";s:7:"options";a:0:{}s:8:"quantity";i:1;}}
		    [user_info] => a:6:{s:2:"id";i:1;s:5:"email";s:16:"hello@plugify.io";s:10:"first_name";s:4:"Luke";s:9:"last_name";s:7:"Rollans";s:8:"discount";s:4:"none";s:7:"address";b:0;}
		    [cart_details] => a:1:{i:0;a:9:{s:4:"name";s:15:"Testing Product";s:2:"id";s:2:"10";s:11:"item_number";a:3:{s:2:"id";s:2:"10";s:7:"options";a:0:{}s:8:"quantity";i:1;}s:10:"item_price";d:25;s:8:"quantity";i:1;s:8:"discount";d:0;s:8:"subtotal";d:25;s:3:"tax";d:0;s:5:"price";d:25;}}
		    [tax] => 0
		    [key] => 11bd483ad239143798f1aa9738c5b0b1
		    [email] => hello@plugify.io
		    [date] => 2014-05-13 12:23:28
		)

		Array
		(
		    [0] => Array
		        (
		            [name] => Testing Product
		            [id] => 10
		            [item_number] => Array
		                (
		                    [id] => 10
		                    [options] => Array
		                        (
		                        )

		                    [quantity] => 1
		                )

		            [item_price] => 25
		            [quantity] => 1
		            [discount] => 0
		            [subtotal] => 25
		            [tax] => 0
		            [price] => 25
		        )

		)
		*/

	}

}

?>
