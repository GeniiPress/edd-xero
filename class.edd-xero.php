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
	* When validated, create Xero resources and push to API as necessary
	*
	* @since 0.1
	*
	* @param int $payment_id ID of EDD payment on which to base the Xero invoice.
	* @return void
	*/
	public static function create_invoice ( $payment_id ) {

		$payment = edd_get_payment_meta( $payment_id );
		$cart = edd_get_payment_meta_cart_details( $payment_id );

		// Instantiate new invoice object
		$invoice = new Xero_Invoice();

		// Add purchased items to invoice
		foreach( $cart as $line_item ) {

			$invoice->add( new Xero_Line_Item( array(
				'description' => $line_item['name'],
				'quantity' => $line_item['quantity'],
				'unitamount' => $line_item['item_price']
			) ) );

		}

		echo '<pre>' . print_r( $payment, true ) . '</pre>';
		echo '<pre>' . print_r( $cart, true ) . '</pre>';

		wp_die();

	}

}

?>
