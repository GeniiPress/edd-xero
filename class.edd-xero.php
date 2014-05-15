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

		// Prepare required data such as customer details and cart contents
		$payment = edd_get_payment_meta( $payment_id );
		$cart = edd_get_payment_meta_cart_details( $payment_id );
		$contact = unserialize( $payment['user_info'] );

		// Instantiate new invoice object
		$invoice = new Xero_Invoice();

		// Set creation and due dates
		$time = date( 'Y-m-d', strtotime( $payment['date'] ) );

		$invoice->set_date( $time );
		$invoice->set_due_date( $time );

		// Set contact (invoice recipient) details
		$invoice->set_contact( new Xero_Contact( array(
			'first_name' => $contact['first_name'],
			'last_name' => $contact['last_name'],
			'email' => $contact['email']
		) ) );

		echo '<pre>' . print_r( $payment, true ) . '</pre>';
		echo '<pre>' . print_r( $cart, true ) . '</pre>';

		// Add purchased items to invoice
		foreach( $cart as $line_item ) {

			$invoice->add( new Xero_Line_Item( array(
				'description' => $line_item['name'],
				'quantity' => $line_item['quantity'],
				'unitamount' => $line_item['item_price'],
				'tax' => $line_item['tax'],
				'total' => $line_item['price']
			) ) );

		}

	}

	public static function send_to_xero ( $invoice ) {

		// Abort if a Xero_Invoice object was not passed
		if( !( $invoice instanceof Xero_Invoice ) )
			return false;

		$xml = $invoice->get_xml();
		$api = 'https://api.xero.com/api.xro/2.0/invoices';



	}

}

?>
