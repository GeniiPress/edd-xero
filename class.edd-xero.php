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

		// Setup actions for invoice creation success/fail
		add_action( 'edd_xero_invoice_creation_success', array( &$this, 'xero_invoice_success' ), 10, 3 );
		add_action( 'edd_xero_invoice_creation_fail', array( &$this, 'xero_invoice_fail' ), 10, 2 );

		// Action for displaying Xero 'metabox' on payment details page
		add_action( 'edd_view_order_details_sidebar_after', array( &$this, 'xero_invoice_metabox' ) );

	}

	public static function xero_invoice_success ( $invoice, $invoice_number, $payment_id ) {

		// Insert a note on the payment informing the merchant Xero invoice generation was successful
		edd_insert_payment_note( $payment_id, 'Xero invoice ' . $invoice_number . ' successfully created' );

	}

	public static function xero_invoice_fail ( $invoice, $payment_id ) {

		// Insert a note on the payment informing merchant that Xero invoice generation failed
		edd_insert_payment_note( $payment_id, 'Xero invoice could not be created. Error number: ' . $response->ErrorNumber );

	}

	public static function xero_invoice_metabox () {
		?>

		<div id="edd-order-update" class="postbox edd-order-data">
			<h3 class="hndle">
				<span><img src="<?php echo plugins_url( 'edd-xero/assets/art/xero-logo@2x.png' , dirname(__FILE__) ); ?>" width="12" height="12" style="position:relative;top:1px;" />&nbsp; Xero</span>
			</h3>
		</div>

		<?php
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

		$this->post_invoice( $invoice, $payment_id );

	}

	private function post_invoice ( $invoice, $payment_id ) {

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

			// Create object and send to Xero
			$XeroOAuth = new XeroOAuth( $xero_config );

			$request = $XeroOAuth->request( 'PUT', $XeroOAuth->url( 'Invoices', 'core' ), array(), $xml );
			$response = $XeroOAuth->parseResponse( $request['response'] ,'xml' );

			// Parse the response from Xero and fire appropriate actions
			if( $request['code'] == 200 ) {
				do_action( 'edd_xero_invoice_creation_success', $invoice, $response->Invoices->Invoice->InvoiceNumber, $payment_id );
			}
			else {
				do_action( 'edd_xero_invoice_creation_fail', $invoice, $payment_id );
			}

		}
		catch( Exception $e ) {
			// Add note to order to say Xero Invoice generation was unsuccessful
		}

	}

}

?>
