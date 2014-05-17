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
	public function create_invoice ( $payment_id ) {

		// Prepare required data such as customer details and cart contents
		$payment = edd_get_payment_meta( $payment_id );
		$cart = edd_get_payment_meta_cart_details( $payment_id );
		$contact = unserialize( $payment['user_info'] );

		try {

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
		catch( Exception $e ) {
			return false;
		}

		$this->post_invoice( $invoice );

	}

	private function post_invoice ( $invoice ) {

		// Abort if a Xero_Invoice object was not passed
		if( !( $invoice instanceof Xero_Invoice ) )
			return false;

		// Prepare payload and API endpoint URL
		$xml = $invoice->get_xml();

		// Create oAuth object and send request
		try {

			$path = trailingslashit( dirname( __FILE__ ) );

			require_once $path . 'lib/oauth/_config.php';
			require_once $path . 'lib/oauth/lib/OAuthSimple.php';
			require_once $path . 'lib/oauth/lib/XeroOAuth.php';

			$xero_config = array_merge (

				array(
					'application_type' => XRO_APP_TYPE,
					'oauth_callback' => OAUTH_CALLBACK,
					'user_agent' => 'Plugify-EDD-Xero'
				),

				$signatures

			);

			$XeroOAuth = new XeroOAuth( $xero_config );
			$response = $XeroOAuth->request( 'PUT', $XeroOAuth->url( 'Invoices', 'core' ), array(), $xml );

			echo $xml;

			echo '<pre>' . print_r($response, true) . '</pre>';
			wp_die();

		}
		catch( Exception $e ) {
			echo '<pre>' . print_r($e, true) . '</pre>';
		}

	}

}

?>
