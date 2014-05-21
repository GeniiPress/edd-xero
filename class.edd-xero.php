<?php

// Core class for plugin functionality

final class Plugify_EDD_Xero {

	private $xero_config = array();

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
		add_action( 'edd_xero_invoice_creation_success', array( &$this, 'xero_invoice_success' ), 99, 4 );
		add_action( 'edd_xero_invoice_creation_fail', array( &$this, 'xero_invoice_fail' ), 10, 2 );

		// Action for displaying Xero 'metabox' on payment details page
		add_action( 'edd_view_order_details_sidebar_after', array( &$this, 'xero_invoice_metabox' ) );

		// Admin hooks
		add_action( 'admin_init', array( &$this, 'admin_init' ) );

		// Load Xero PHP library
		$path = trailingslashit( dirname( __FILE__ ) );

		require_once $path . 'lib/oauth/_config.php';
		require_once $path . 'lib/oauth/lib/OAuthSimple.php';
		require_once $path . 'lib/oauth/lib/XeroOAuth.php';

		$this->xero_config = array_merge (

			array(
				'application_type' => XRO_APP_TYPE,
				'oauth_callback' => OAUTH_CALLBACK,
				'user_agent' => 'Plugify-EDD-Xero'
			),

			$signatures

		);

	}

	/**
	* Register hooks which are needed for the admin area, such as the 'Generate Invoice' button and automatically
	* displaying invoice details in the metabox
	*
	* @since 0.1
	*
	* @return void
	*/
	public function admin_init () {

		// Admin AJAX hooks
		add_action( 'wp_ajax_invoice_lookup', array( &$this, 'ajax_xero_invoice_lookup' ) );
		add_action( 'wp_ajax_generate_invoice', array( &$this, 'ajax_generate_invoice' ) );

	}

	/**
	* Leverage the Xero invoice creation success access to save critical invoice data such as Number and ID as meta
	* against the EDD Payment whenever an invoice is generated
	*
	* @since 0.1
	*
	* @param Xero_Invoice $invoice Xero_Invoice object of newly generated invoice
	* @param string $invoice_number Number of Xero invoice as automatically assigned by Xero. EG, "INV-123"
	* @param guid $invoice_id Unique ID of invoice as in Xero. EG "851b2f09-36f8-4df8-a32e-da8c4c451ff0"
	* @param int $payment_id ID of EDD Payment
	* @return void
	*/
	public static function xero_invoice_success ( $invoice, $invoice_number, $invoice_id, $payment_id ) {

		// Save invoice number and ID locally
		update_post_meta( $payment_id, '_edd_payment_xero_invoice_number', $invoice_number );
		update_post_meta( $payment_id, '_edd_payment_xero_invoice_id', $invoice_id );

		// Insert a note on the payment informing the merchant Xero invoice generation was successful
		edd_insert_payment_note( $payment_id, 'Xero invoice ' . $invoice_number . ' successfully created' );

	}

	/**
	* Leverage the Xero invoice creation failure action to add an error note to the payment
	*
	* @since 0.1
	*
	* @param Xero_Invoice $invoice Xero_Invoice object of invoice which failed to generate in Xero
	* @param int $payment_id ID of EDD Payment
	* @return void
	*/
	public static function xero_invoice_fail ( $invoice, $payment_id ) {

		// Insert a note on the payment informing merchant that Xero invoice generation failed
		edd_insert_payment_note( $payment_id, 'Xero invoice could not be created.' );

	}

	/**
	* Handler to populate the Metabox found on the EDD Payment page in the backend
	*
	* @since 0.1
	*
	* @return void
	*/
	public function xero_invoice_metabox () {

		$invoice_number = get_post_meta( $_GET['id'], '_edd_payment_xero_invoice_number', true );
		$invoice_id = get_post_meta( $_GET['id'], '_edd_payment_xero_invoice_id', true );

		?>

		<link rel="stylesheet" media="all" href="<?php echo plugins_url( 'edd-xero/assets/css/styles.css', dirname( __FILE__ ) ); ?>" />

		<div id="edd-xero" class="postbox edd-order-data">

			<h3 class="hndle">
				<span><img src="<?php echo plugins_url( 'edd-xero/assets/art/xero-logo@2x.png' , dirname(__FILE__) ); ?>" width="12" height="12" style="position:relative;top:1px;" />&nbsp; Xero</span>
			</h3>
			<div class="inside">
				<div class="edd-admin-box">
					<div class="edd-admin-box-inside">

						<?php if( '' != $invoice_number ): ?>

						<h3 class="invoice-number"><?php echo $invoice_number; ?></h3>

						<?php else: ?>
						<h3 class="invoice-number text-center">No associated invoice found</h3>
						<a id="edd-xero-generate-invoice" class="button-primary text-center" href="#">Generate Invoice Now</a>
						<?php endif; ?>

						<div id="edd_xero_invoice_details">
							<p class="ajax-loader">
								<img src="<?php echo plugins_url( 'edd-xero/assets/art/ajax-loader.gif', dirname( __FILE__ ) ); ?>" alt="Loading" />
							</p>
							<input id="_edd_xero_invoice_number" type="hidden" name="edd_xero_invoice_number" value="<?php echo $invoice_number; ?>" />
						</div>

					</div>
				</div>
			</div>

			<div class="edd-order-update-box edd-admin-box edd-invoice-actions">
		    <div class="major-publishing-actions">
					<div class="publishing-action">
						<a id="edd-view-invoice-in-xero" class="button-secondary right" target="_blank" href="https://go.xero.com/AccountsReceivable/Edit.aspx?InvoiceID=<?php echo $invoice_id; ?>">View in Xero</a>
					</div>
					<div class="clear"></div>
				</div>
			</div>

		</div>

		<script src="<?php echo plugins_url( 'edd-xero/assets/js/functions.js', dirname( __FILE__ ) ); ?>"></script>

		<?php
	}

	/**
	* AJAX handler to do an invoice lookup. Uses parameter "invoice_number"
	*
	* @since 0.1
	*
	* @return HTTP
	*/
	public function ajax_xero_invoice_lookup () {

		if( !$_REQUEST['invoice_number'] ) {
			wp_send_json_error();
		}

		if( $response = $this->get_invoice( $_REQUEST['invoice_number'] ) ) {
			$return = $this->get_invoice_excerpt( $response );
			wp_send_json_success( $return );
		}

		wp_send_json_error();

	}

	/**
	* AJAX handler to generate an invoice in Xero. Uses parameter "payment_id" which represents the EDD Payment
	*
	* @since 0.1
	*
	* @return HTTP
	*/
	public function ajax_generate_invoice () {

		if( !isset( $_REQUEST['payment_id'] ) ) {
			wp_send_json_error();
		}

		if( $response = $this->create_invoice( $_REQUEST['payment_id'] ) ) {
			$return = $this->get_invoice_excerpt( $response );
			wp_send_json_success( $return );
		}
		else {
			wp_send_json_error();
		}

	}

	/**
	* Generate an array containing a snapshot of a Xero invoice
	*
	* @since 0.1
	*
	* @param $response SimpleXMLObject An XML response for a particular invoice from Xero
	* @return array
	*/
	public function get_invoice_excerpt( $response ) {

		$return = array();

		foreach( $response->Invoices as $invoice_tag ) {
			$return['ID'] = (string)$invoice_tag->Invoice->InvoiceID;
			$return['InvoiceNumber'] = (String)$invoice_tag->Invoice->InvoiceNumber;
			$return['CurrencyCode'] = (string)$invoice_tag->Invoice->CurrencyCode;
			$return['Total'] = (string)$invoice_tag->Invoice->Total;
			$return['TotalTax'] = (string)$invoice_tag->Invoice->TotalTax;
			$return['Status'] = (string)$invoice_tag->Invoice->Status;
		}

		$return['Contact']['Name'] = (string)$invoice_tag->Invoice->Contact->Name;
		$return['Contact']['Email'] = (string)$invoice_tag->Invoice->Contact->EmailAddress;

		return $return;

	}

	/**
	* Handler for edd_complete_purchase hook. Fires when a purchase is completed
	* Generates a Xero_Invoice object and then sends that object to the Xero API as XML for creation
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

			// Send the invoie to Xero
			return $this->put_invoice( $invoice, $payment_id );

		}
		catch( Exception $e ) {
			return false;
		}

	}

	/**
	* Handler for sending an invoice creation request to Xero once all processing has been completed.
	*
	* @since 0.1
	*
	* @param Xero_Invoice $invoice Xero_Invoice object which the new invoice will be generated from
	* @param int $payment_id ID of EDD payment on which to base the Xero invoice.
	* @return SimpleXMLObject
	*/
	private function put_invoice ( $invoice, $payment_id ) {

		// Abort if a Xero_Invoice object was not passed
		if( !( $invoice instanceof Xero_Invoice ) )
			return false;

		// Prepare payload and API endpoint URL
		$xml = $invoice->get_xml();

		// Create oAuth object and send request
		try {

			// Create object and send to Xero
			$XeroOAuth = new XeroOAuth( $this->xero_config );

			$request = $XeroOAuth->request( 'PUT', $XeroOAuth->url( 'Invoices', 'core' ), array(), $xml );
			$response = $XeroOAuth->parseResponse( $request['response'] ,'xml' );

			// Parse the response from Xero and fire appropriate actions
			if( $request['code'] == 200 ) {
				do_action( 'edd_xero_invoice_creation_success', $invoice, (string)$response->Invoices->Invoice->InvoiceNumber, (string)$response->Invoices->Invoice->InvoiceID, $payment_id );
			}
			else {
				do_action( 'edd_xero_invoice_creation_fail', $invoice, $payment_id );
			}

			return $response;

		}
		catch( Exception $e ) {
			do_action( 'edd_xero_invoice_creation_fail', $invoice, $payment_id );
		}

	}

	/**
	* Query the Xero API for a specific invoice by number (as opposed to the ID)
	*
	* @since 0.1
	*
	* @param int $invoice_number Automatically generated human friendly invoice number. EG "INV-123"
	* @return SimpleXMLObject
	*/
	private function get_invoice ( $invoice_number ) {

		try {

			// Get Invoice via Xero API
			$XeroOAuth = new XeroOAuth( $this->xero_config );

			$request = $XeroOAuth->request( 'GET', $XeroOAuth->url( 'Invoices/' . $invoice_number, 'core' ), array(), NULL );
			$response = $XeroOAuth->parseResponse( $request['response'] ,'xml' );

			return $response;

		}
		catch( Exception $e ) {
			return false;
		}

	}

}

?>
