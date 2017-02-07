<?php

/**
 * Class Plugify_EDD_Xero
 *
 * Core class for plugin functionality
 */
final class Plugify_EDD_Xero {

	private $xero_config = array();

	private $basename;
	private $title;

	/**
	 * Class constructor. Hook in to EDD, setup actions and everything we need.
	 *
	 * @since 0.1
	 * @param string $basename
	 */

	public function __construct ( $basename ) {

		// Setup vars
		$this->basename = $basename; // Can't use plugin_basename etc as edd-xero.php is the activation file
		$this->title    = __( 'Easy Digital Downloads - Xero', 'edd-xero');

		// Register hooks
		$this->initialize();

		// Setup languages
		$this->load_textdomain();

	}

	/**
	 * Function to initialize everything the plugin needs to operate. WP Hooks and OAuth library
	 *
	 * @since 0.9
	 * @return void
	 */
	public function initialize() {

		// Hook in to created payments
		add_action( 'edd_complete_purchase', array( $this, 'create_invoice' ) );

		// Setup actions for invoice creation success/fail
		add_action( 'edd_xero_invoice_creation_success', array( $this, 'xero_invoice_success' ), 99, 4 );
		add_action( 'edd_xero_invoice_creation_fail', array( $this, 'xero_invoice_fail' ), 10, 4 );
		add_action( 'edd_xero_payment_success', array( $this, 'xero_payment_success' ), 10, 3 );
		add_action( 'edd_xero_payment_fail', array( $this, 'xero_payment_fail' ), 10, 3 );

		// Action for displaying Xero 'metabox' on payment details page
		add_action( 'edd_view_order_details_sidebar_after', array( &$this, 'xero_invoice_metabox' ) );

		// Admin hooks
		add_action( 'admin_init', array( $this, 'admin_init' ) );
		add_action( 'admin_notices', array( $this, 'admin_notices' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );

		// AJAX handlers
		add_action( 'wp_ajax_xero_rewrite_credentials', array( $this, 'force_xero_write_keys' ), 10, 0 );

		// Write certificate key files when user updates textarea fields
		add_action( 'updated_option', array( $this, 'xero_write_keys' ), 10, 3 );

		// Setup EDD Settings Section and add our settings to it
		add_filter( 'edd_settings_sections_extensions', array( $this, 'edd_xero_settings_section') );
		add_filter( 'edd_settings_extensions', array( $this, 'edd_xero_register_settings' ), 10, 1 );

	}

	/**
	 * Register hooks which are needed for the admin area, such as the 'Generate Invoice' button and automatically
	 * displaying invoice details in the metabox
	 *
	 * @since 0.1
	 *
	 * @return void
	 */
	public function admin_init() {

		add_filter( 'plugin_action_links_' . $this->basename, array( $this, 'plugin_links' ) );

		// Admin AJAX hooks
		add_action( 'wp_ajax_invoice_lookup', array( $this, 'ajax_xero_invoice_lookup' ) );
		add_action( 'wp_ajax_generate_invoice', array( $this, 'ajax_generate_invoice' ) );
		add_action( 'wp_ajax_disassociate_invoice', array( $this, 'ajax_disassociate_invoice' ) );

	}

	/**
	 * Queue up styles and scripts that EDD Xero uses in the admin area
	 *
	 * @since 0.9
	 *
	 * @return void
	 */
	public function admin_enqueue_scripts() {

		$screen = get_current_screen();

		if( $screen->id == 'download_page_edd-payment-history' && $screen->post_type == 'download' ) {

			// Enqueue styles for EDD Xero
			wp_enqueue_style( 'edd-xero', plugin_dir_url( __FILE__ ) . 'assets/css/edd-xero.css' );

			// Enqueue scripts for EDD
			wp_enqueue_script( 'edd-xero-js', plugin_dir_url( __FILE__ ) . 'assets/js/edd-xero.js', array( 'jquery' ) );

		}

	}

	/**
	 * Handle displaying any required admin notices.
	 * Since 0.9, displays whether EDD needs to be activated or if it's not of a high enough version
	 *
	 * @since 0.9
	 *
	 * @return void
	 */
	public function admin_notices() {

		// Display a notice if EDD is not installed and deactivate plugin
		if( !class_exists( 'Easy_Digital_Downloads' ) ) {

			// Make sure this plugin is active
			if( is_plugin_active( $this->basename ) ) {

				// Deactivate EDD Xero
				deactivate_plugins( $this->basename );

				// Turn off activation admin notice
				if( isset( $_GET['activate'] ) ) {
					unset( $_GET['activate'] );
				}

				echo '<div class="error"><p>' . sprintf( __( '%s has been deactivated because it requires Easy Digital Downloads to be installed and activated', 'edd-xero' ), $this->title ) . '</p></div>';

			}

		}
		else {

			// If EDD is installed but version is too low, display a notice
			$edd_plugin_data = get_plugin_data( WP_PLUGIN_DIR . '/easy-digital-downloads/easy-digital-downloads.php', false, false );

			if( $edd_plugin_data['Version'] < '1.9' ) {
				echo '<div class="error"><p>' . sprintf( __( '%s requires Easy Digital Downloads Version 1.9 or greater. Please update Easy Digital Downloads.', 'edd-xero' ), $this->title ) . '</p></div>';
			}

		}

	}

	/**
	 * Load language files
	 *
	 * @since 0.1
	 *
	 * @return void
	 */
	public function load_textdomain() {

		// Set filter for plugin's languages directory
		$lang_dir = plugin_dir_path( __FILE__ ) . 'languages/';

		// Traditional WordPress plugin locale filter
		$locale = apply_filters( 'plugin_locale', get_locale(), 'edd-xero' );
		$mofile = sprintf( '%1$s-%2$s.mo', 'edd-xero', $locale );

		// Setup paths to current locale file
		$mofile_local = $lang_dir . $mofile;
		$mofile_global = WP_LANG_DIR . '/edd-xero/' . $mofile;

		if ( file_exists( $mofile_global ) ) {
			load_textdomain( 'edd-xero', $mofile_global );
		}
		elseif ( file_exists( $mofile_local ) ) {
			load_textdomain( 'edd-xero', $mofile_local );
		}
		else {
			// Load the default language files
			load_plugin_textdomain( 'edd-xero', false, $lang_dir );
		}

	}

	/**
	 * Define the Xero subsection on the Extensions tab
	 *
	 * @param $sections
	 * @return mixed
	 */
	public function edd_xero_settings_section( $sections ) {
		$sections['xero-settings'] = __( 'Xero', 'edd-xero');
		return $sections;
	}

	/**
	 * Register settings fields where the user can insert the consumer key, consumer secret etc..
	 * Currently displays under the Extensions tab in EDD settings
	 *
	 * @since 0.1
	 * @param array $settings
	 * @return array $edd_settings
	 */
	public static function edd_xero_register_settings( $settings ) {

		$xero_settings = array(
			array(
				'id' => 'xero_settings_behaviour',
				'name' => __( 'Xero behaviour', 'edd-xero' ),
				'type' => 'header'
			),
			array(
				'id' => 'invoice_status',
				'name' => __( 'Invoice Status', 'edd-xero' ),
				'type' => 'select',
				'desc' => __( 'Created invoices as Draft, Submitted or Authorised', 'edd-xero' ),
				'options' => array(
					'DRAFT' => 'Draft',
					'SUBMITTED' => 'Submitted for Approval',
					'AUTHORISED' => 'Authorised'
				)
			),
			'invoice_payments' => array(
				'id' => 'invoice_payments',
				'name' => __( 'Auto Send Payments', 'edd-xero' ),
				'desc' => __( 'When an invoice is created, automatically apply the associated payment', 'edd-xero' ),
				'type' => 'checkbox'
			),
			'line_amount_type' => array(
				'id' => 'line_amount_type',
				'name' => __( 'Line Amount Type', 'edd-xero' ),
				'type' => 'select',
				'desc' => __( 'Send invoice line item amount as inclusive or exclusive of tax', 'edd-xero' ),
				'options' => array(
					'Exclusive' => 'Exclusive',
					'Inclusive' => 'Inclusive'
				)
			),
			'sales_account' => array(
				'id' => 'sales_account',
				'name' => __( 'Sales Account', 'edd-xero' ),
				'desc' => __( 'Code for Xero account which tracks sales', 'edd-xero' ),
				'class' => 'small-text',
				'type' => 'text'
			),
			'payments_account' => array(
				'id' => 'payments_account',
				'name' => __( 'Payment Account', 'edd-xero' ),
				'desc' => __( 'Code for Xero account which tracks received payments', 'edd-xero' ),
				'class' => 'small-text',
				'type' => 'text'
			),
			'xero_settings_header' => array(
				'id' => 'xero_settings_header',
				'name' => __( 'Xero Application Settings', 'edd-xero' ),
				'type' => 'header'
			),
			'xero_settings_description' => array(
				'id' => 'xero_settings_description',
				'name' => __( 'Xero Setup Instructions', 'edd-xero' ),
				'type' => 'description'
			),
			'consumer_key' => array(
				'id' => 'consumer_key',
				'name' => __( 'Consumer Key', 'edd-xero' ),
				'desc' => __( 'The consumer key of your Xero application.', 'edd-xero' ),
				'type' => 'text'
			),
			'shared_secret' => array(
				'id' => 'shared_secret',
				'name' => __( 'Consumer Secret', 'edd-xero' ),
				'desc' => __( 'The consumer secret of your Xero application.', 'edd-xero' ),
				'type' => 'text'
			),
			'private_key' => array(
				'id' => 'private_key',
				'name' => __( 'Private Key', 'edd-xero' ),
				'desc' => __( 'Private key file (.pem)', 'edd-xero' ),
				'type' => 'textarea'
			),
			'public_key' => array(
				'id' => 'public_key',
				'name' => __( 'Public Key', 'edd-xero' ),
				'desc' => __( 'Public Key file (.cer)', 'edd-xero' ),
				'type' => 'textarea'
			),
			'xero_debug' => array(
				'id' => 'xero_debug',
				'name' => __( 'Debug', 'edd-xero' ),
				'desc' => __( 'Write debug data to a log?', 'edd-xero' ),
				'type' => 'checkbox'
			)
		);

		// If EDD is at version 2.5 or later use a subsection
		if ( version_compare( EDD_VERSION, 2.5, '>=' ) ) {
			$xero_settings = array( 'xero-settings' => $xero_settings );
		}

		return array_merge( $settings, $xero_settings );

	}

	/**
	 * When a user sets new private/public key values in Xero settings, write the values to the .pem and .cer files required for OAuth
	 * Leverages the updated option hook
	 *
	 * @since 0.1
	 *
	 * @param $option string Name of the option updated. For our purposes, we're just listening for "edd_settings"
	 * @param $old_value array The old value of edd_settings option
	 * @param $new_value array The new value of edd_settings option
	 * @return void
	 */
	public static function xero_write_keys( $option, $old_value, $new_value ) {

		if( $option == 'edd_settings' ) {

			$keys = array(
				'privatekey.pem' => isset( $new_value['private_key'] ) ? $new_value['private_key'] : '',
				'publickey.cer' => isset(  $new_value['public_key'] ) ? $new_value['public_key'] : '',
			);

			// Attempt to write key files
			foreach( $keys as $filename => $data ) {
				if( !empty( $data ) ) {
					file_put_contents( dirname( __FILE__ ) . '/lib/oauth/certs/' . $filename, $data );
				}
			}

		}

	}

	/**
	 * AJAX mechanism for allowing Xero keys to be written without updated_option firing
	 *
	 * @since 1.2.1
	 *
	 * @return void
	 */
	public static function force_xero_write_keys() {

		// Get EDD settings
		$settings = edd_get_settings();

		// Trick xero_write_keys in to thinking EDD Settings were just saved
		Plugify_EDD_Xero::xero_write_keys( 'edd_settings', $settings, $settings );

		// Send back JSON success
		wp_send_json_success();

	}

	/**
	 * Leverage the Xero invoice creation success action to save critical invoice data such as Number and ID as meta
	 * against the EDD Payment whenever an invoice is generated
	 *
	 * @since 0.1
	 * @param $xero_payment
	 * @param $response
	 * @param $payment_id
	 * @return void
	 */
	public function xero_payment_success( $xero_payment, $response, $payment_id ) {

		// Add a success note to the payment
		edd_insert_payment_note( $payment_id, __( 'Payment was successfully applied to Xero invoice', 'edd-xero' ) );

	}

	/**
	 * @param $xero_payment
	 * @param $response
	 * @param $payment_id
	 */
	public function xero_payment_fail( $xero_payment, $response, $payment_id ) {

		// Add a failure notice to the payment
		edd_insert_payment_note( $payment_id, __( 'Payment could not be applied to Xero invoice. ' . $response->Elements->DataContractBase->ValidationErrors->ValidationError[0]->Message, 'edd-xero' ) );

	}

	/**
	 * Leverage the Xero invoice creation success action to save critical invoice data such as Number and ID as meta
	 * against the EDD Payment whenever an invoice is generated
	 *
	 * @since 0.1
	 *
	 * @param Xero_Invoice $invoice Xero_Invoice object of newly generated invoice
	 * @param string $invoice_number Number of Xero invoice as automatically assigned by Xero. EG, "INV-123"
	 * @param string $invoice_id Unique ID of invoice as in Xero. EG "851b2f09-36f8-4df8-a32e-da8c4c451ff0"
	 * @param int $payment_id ID of EDD Payment
	 * @return void
	 */
	public function xero_invoice_success( $invoice, $invoice_number, $invoice_id, $payment_id ) {

		// Save invoice number and ID locally
		update_post_meta( $payment_id, '_edd_payment_xero_invoice_number', $invoice_number );
		update_post_meta( $payment_id, '_edd_payment_xero_invoice_id', $invoice_id );

		// Insert a note on the payment informing the merchant Xero invoice generation was successful
		edd_insert_payment_note( $payment_id, __( 'Xero invoice ' . $invoice_number . ' successfully created', 'edd-xero' ) );

		// If automatic payment application is turned on, do just that!
		$settings = edd_get_settings();

		// If automatic payments are turned on, do eeet!
		if( $settings['invoice_payments'] ) {
			$this->create_payment( $invoice_id, $payment_id );
		}

	}

	/**
	 * Leverage the Xero invoice creation failure action to add an error note to the payment
	 *
	 * @since 0.1
	 *
	 * @param Xero_Invoice $invoice Xero_Invoice object of invoice which failed to generate in Xero
	 * @param int $payment_id ID of EDD Payment
	 * @param mixed $error_obj (optional) An object which was used in the context of creating a Xero invoice which subsequently failed
	 * @param string $custom_message (optional) Allow a developer to pass in the error message they want written on the EDD payment note
	 * @return void
	 */
	public function xero_invoice_fail( $invoice, $payment_id, $error_obj = null, $custom_message = null ) {

		$postfix = null;

		if( !is_null( $error_obj ) ){

			if( isset( $error_obj['response'] ) ) {
				$postfix = $error_obj['response'];
			}
			// Allow space here for another possible $error_obj context, an Exception object, for example
		}

		// Insert a note on the payment informing merchant that Xero invoice generation failed, and why
		$message = !is_null( $custom_message ) ? __( $custom_message, 'edd-xero' ) : __( 'Xero invoice could not be created.', 'edd-xero' );
		$message .= $invoice->get_xml();
		edd_insert_payment_note( $payment_id, $message . ( !is_null( $postfix ) ? __( ' Xero said: ' . $postfix, 'edd-xero' ) : NULL ) );

	}

	/**
	 * Handler to populate the Metabox found on the EDD Payment page in the backend
	 *
	 * @since 0.1
	 *
	 * @return void
	 */
	public function xero_invoice_metabox() {

		$invoice_number = get_post_meta( $_GET['id'], '_edd_payment_xero_invoice_number', true );
		$invoice_id = get_post_meta( $_GET['id'], '_edd_payment_xero_invoice_id', true );

		$valid_settings = $this->settings_are_valid();
		?>
		<div id="edd-xero" class="postbox edd-order-data">
			<h3 class="hndle">
				<span><img src="<?php echo plugin_dir_url( __FILE__ ) . 'assets/art/xero-logo@2x.png'; ?>" width="12" height="12" style="position:relative;top:1px;" />&nbsp; <?php _e('Xero','edd-xero'); ?></span>
			</h3>
			<div class="inside">
				<div class="edd-admin-box">
					<div class="edd-admin-box-inside">

						<?php if( !$valid_settings ): ?>

							<h3 class="invoice-number"><?php _e('Xero settings not configured','edd-xero'); ?></h3>

							<p>
								<?php _e( 'Looks like you need to configure your Xero settings!', 'edd-xero' ); ?>
								<?php _e( 'You can <a href="' . admin_url('edit.php?post_type=download&page=edd-settings&tab=extensions&section=xero-settings') . '">click here</a> to do so', 'edd-xero' ); ?>
							</p>

						<?php else: ?>

							<?php if( '' != $invoice_number ): ?>

							<h3 class="invoice-number"><?php echo $invoice_number; ?></h3>

							<?php else: ?>
							<h3 class="invoice-number text-center"><?php _e( 'No associated invoice found', 'edd-xero' ); ?></h3>
							<a id="edd-xero-generate-invoice" class="button-primary text-center" href="#"><?php _e( 'Generate Invoice Now', 'edd-xero' ); ?></a>
							<?php endif; ?>

							<div id="edd_xero_invoice_details">
								<p class="ajax-loader">
									<img src="<?php echo plugin_dir_url( __FILE__ ) . 'assets/art/ajax-loader.gif'; ?>" alt="Loading" />
								</p>
								<input id="_edd_xero_invoice_number" type="hidden" name="edd_xero_invoice_number" value="<?php echo $invoice_number; ?>" />
							</div>

						<?php endif; ?>

					</div>
				</div>
			</div>
			<?php if( $valid_settings ): ?>
			<div class="edd-order-update-box edd-admin-box edd-invoice-actions">
		    <div class="major-publishing-actions">
					<div class="publishing-action">
						<a id="edd-view-invoice-in-xero" class="button-primary right" target="_blank" href="https://go.xero.com/AccountsReceivable/Edit.aspx?InvoiceID=<?php echo $invoice_id; ?>"><?php _e( 'View in Xero', 'edd-xero' ); ?></a>
						<a id="edd-xero-disassociate-invoice" class="button-secondary right" href="#"><?php _e( 'Disassociate Invoice', 'edd-xero' ); ?></a>
					</div>
					<div class="clear"></div>
				</div>
			</div>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * AJAX handler to do an invoice lookup. Uses parameter "invoice_number"
	 *
	 * @since 0.1
	 *
	 * @return void
	 */
	public function ajax_xero_invoice_lookup() {

		if( !isset( $_REQUEST['invoice_number'] ) ) {
			wp_send_json_error();
		}

		if( $response = @$this->get_invoice( $_REQUEST['invoice_number'] ) ) {
			$return = $this->get_invoice_excerpt( $response );
			wp_send_json_success( $return );
		}

		wp_send_json_error( array(
			'error_message' => sprintf( '<p>%s <a href="' . admin_url('edit.php?post_type=download&page=edd-settings&tab=extensions') . '" target="_blank">%s</a>', __( 'We could not get the invoice details', 'edd-xero' ), __( 'Are your Xero settings configured correctly?', 'edd-xero' ) )
		) );

	}

	/**
	 * AJAX handler to generate an invoice in Xero. Uses parameter "payment_id" which represents the EDD Payment
	 *
	 * @since 0.1
	 *
	 * @return void
	 */
	public function ajax_generate_invoice () {

		if( !isset( $_REQUEST['payment_id'] ) ) {
			wp_send_json_error();
		}

		if( $response = @$this->create_invoice( $_REQUEST['payment_id'] ) ) {
			$return = $this->get_invoice_excerpt( $response );
			wp_send_json_success( $return );
		}
		else {
			wp_send_json_error( array(
				'error_message' => __( 'Xero invoice could not be created. Please refresh the page and check Payment Notes.', 'edd-xero' )
			) );
		}

	}

	/**
	 * AJAX handler to disassociate the invoice attached to a payment. Uses parameter "payment_id" which represents the EDD Payment
	 *
	 * @since 0.1
	 *
	 * @return void
	 */
	public function ajax_disassociate_invoice () {

		if( !isset( $_REQUEST['payment_id'] ) ) {
			wp_send_json_error();
		}

		$payment_id = $_REQUEST['payment_id'];
		$result 		= delete_post_meta( $payment_id, '_edd_payment_xero_invoice_number' ) && delete_post_meta( $payment_id, '_edd_payment_xero_invoice_id' );

		if( $result ) {
			wp_send_json_success();
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
	 * Apply a payment to a Xero invoice
	 *
	 * @since 1.0
	 *
	 * @param string $invoice_id ID of Xero invoice. NOT the number, looks like an MD5 hash, not the "pretty" ID
	 * @param int $payment_id EDD payment ID this payment will be mirroring
	 * @return void
	 */
	public function create_payment( $invoice_id, $payment_id ) {

		// Get EDD payment again so we can grab the correct amount
		if( $payment = edd_get_payment_meta( $payment_id ) ) {

			// Get total for order
			$subtotal   = edd_get_payment_subtotal( $payment_id );
			$tax 		= edd_get_payment_tax( $payment_id, false );
			$fees       = edd_get_payment_fees( $payment_id );
			$fee_total  = 0.00;
			foreach( $fees as $fee ){
				if ( $fee['amount'] <= 0 ) {
					continue;
				}
				$fee_total += $fee['amount'];
			}
			$total		= $subtotal + $tax + $fee_total;

			// Get EDD settings
			$settings = edd_get_settings();

			// Build Xero_Payment object
			$xero_payment = new Xero_Payment( array(
				'invoice_id' => $invoice_id,
				'account_code' => $settings['payments_account'],
				'date' => date( 'Y-m-d', strtotime( $payment['date'] ) ),
				'amount' => $total
			) );

			// Send the invoice to Xero
			if( $this->settings_are_valid () ) {
				return $this->put_payment( $xero_payment, $payment_id );
			}
			else {
				do_action( 'edd_xero_payment_fail', $xero_payment, $payment, NULL, __( 'Xero settings have not been configured', 'edd-xero' ) );
			}

		}
		else {
			do_action( 'edd_xero_payment_fail', __( 'Could not apply payment to invoice. EDD payment data not available.', 'edd-xero' ) );
		}

	}

	/**
	 * Send a payment to Xero
	 *
	 * @since 0.1
	 *
	 * @param $xero_payment
	 * @param $payment_id
	 *
	 * @return bool|string
	 */
	private function put_payment( $xero_payment, $payment_id ) {

		// Abort if a Xero_Invoice object was not passed
		if( !( $xero_payment instanceof Xero_Payment ) )
			return false;

		// Prepare payload and API endpoint URL
		$xml = $xero_payment->get_xml();

		// Create oAuth object and send request
		try {

			// Load oauth lib
			$this->load_oauth_lib();

			// Create object and send to Xero
			$XeroOAuth = new XeroOAuth( $this->xero_config );

			$request = $XeroOAuth->request( 'PUT', $XeroOAuth->url( 'Payments', 'core' ), array(), $xml );
			$response = $XeroOAuth->parseResponse( $request['response'] ,'xml' );

			$this->log( "put_payment Payment XML:\n" . $xml );
			$this->log( "put_payment Response\n" . print_r( $response, true ) );

			// Parse the response from Xero and fire appropriate actions
			if( $request['code'] == 200 ) {
				do_action( 'edd_xero_payment_success', $xero_payment, $response, $payment_id );
			}
			else {
				do_action( 'edd_xero_payment_fail', $xero_payment, $response, $payment_id );
			}

			return $response;

		}
		catch( Exception $e ) {
			do_action( 'edd_xero_payment_fail', $xero_payment, $response, $e );
		}

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
	public function create_invoice( $payment_id ) {

		// Prepare required data such as customer details and cart contents
		$payment = edd_get_payment_meta( $payment_id );
		$cart = edd_get_payment_meta_cart_details( $payment_id );

		// Get plugin settings
		$settings = edd_get_settings();

		if( is_array( $payment['user_info'] ) ) {
			$contact = $payment['user_info'];
		}
		else {
			$contact = unserialize( $payment['user_info'] );
		}

		try {

			// Instantiate new invoice object
			$invoice = new Xero_Invoice();

			// Set creation and due dates
			$time = date( 'Y-m-d', strtotime( $payment['date'] ) );

			$invoice->set_date( $time );
			$invoice->set_due_date( $time );

			// Apply invoice number filter
			$invoice->set_invoice_number( apply_filters( 'edd_xero_invoice_number', '', $payment_id, $payment ) );

			// Set the currency code as per EDD settings
			if( '' != $payment['currency'] ) {
				$invoice->set_currency_code( $payment['currency'] );
			}
			else {
				// Do nothing.. Xero will automatically assign a currency. Good fallback if the above fails.
			}

			if ( empty( $contact['first_name']) && empty( $contact['last_name'] ) ){
				// Set contact (invoice recipient) details
				$invoice->set_contact( new Xero_Contact( array(
					'email' => $contact['email'],
					'name' => $contact['email'],
				) ) );
			} else {
				// Set contact (invoice recipient) details
				$args = array();
				$args['first_name']  =  $contact['first_name'];
				$args['last_name']   =  $contact['last_name'];
				$args['email']       =  $contact['email'];
				if ( isset( $contact['address']['line1'] ) )
					$args['address_1']   =  $contact['address']['line1'];
				if ( isset( $contact['address']['line2'] ) )
					$args['address_2']   =  $contact['address']['line2'];
				if ( isset( $contact['address']['city'] ) )
					$args['city']        =  $contact['address']['city'];
				if ( isset( $contact['address']['state'] ) )
					$args['region']      =  $contact['address']['state'];
				if ( isset( $contact['address']['zip'] ) )
					$args['postal_code'] =  $contact['address']['zip'];
				if ( isset( $contact['address']['country'] ) )
					$args['country']     =  $contact['address']['country'];

				$invoice->set_contact( new Xero_Contact( $args ) );
			}

			// Add purchased items to invoice
			foreach( $cart as $line_item ) {

				$invoice->add( new Xero_Line_Item( array(
					'description' => $line_item['name'],
					'quantity' => $line_item['quantity'],
					'unitamount' => $line_item['price'],
					'tax' => $line_item['tax'],
					'total' => $line_item['price'],
					'accountcode' => $settings['sales_account']
				) ) );

			}

			// Add fees to invoice if they exist
			if( isset( $payment['fees'] ) && !empty( $payment['fees'] ) ) {

				foreach( $payment['fees'] as $fee ) {

					// Add fee line item to invoice
					$invoice->add( new Xero_Line_Item( array(
						'description' => $fee['label'],
						'quantity' => 1,
						'total' => $fee['amount'],
						'unitamount' => $fee['amount'],
						'tax' => 0,
						'accountcode' => $settings['sales_account']
					) ) );

				}

			}

			// Set invoice status
			if( isset( $settings['invoice_status'] ) && !empty( $settings['invoice_status'] ) ) {
				$invoice->set_status( $settings['invoice_status'] );
			}

			// Set line item tax status
			if( isset( $settings['line_amount_type'] ) && !empty( $settings['line_amount_type']) ) {
				$invoice->set_line_amount_types( $settings['line_amount_type'] );
			}

			// Send the invoice to Xero
			if( $this->settings_are_valid () ) {
				return @$this->put_invoice( $invoice, $payment_id );
			}
			else {
				do_action( 'edd_xero_invoice_creation_fail', $invoice, $payment_id, NULL, __( 'Xero settings have not been configured', 'edd-xero' ) );
			}

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
	private function put_invoice( $invoice, $payment_id ) {

		// Abort if a Xero_Invoice object was not passed
		if( !( $invoice instanceof Xero_Invoice ) )
			return false;

		// Prepare payload and API endpoint URL
		$xml = $invoice->get_xml();

		// Create oAuth object and send request
		try {

			// Load oauth lib
			$this->load_oauth_lib();

			// Create object and send to Xero
			$XeroOAuth = new XeroOAuth( $this->xero_config );

			$request = $XeroOAuth->request( 'PUT', $XeroOAuth->url( 'Invoices', 'core' ), array(), $xml );
			$response = $XeroOAuth->parseResponse( $request['response'] ,'xml' );

			$this->log( "put_invoice Request XML:\n" . $xml );
			$this->log( "put_invoice Response\n" . print_r( $response, true ) );

			// Parse the response from Xero and fire appropriate actions
			if( $request['code'] == 200 ) {
				do_action( 'edd_xero_invoice_creation_success', $invoice, (string)$response->Invoices->Invoice->InvoiceNumber, (string)$response->Invoices->Invoice->InvoiceID, $payment_id );
			}
			else {
				do_action( 'edd_xero_invoice_creation_fail', $invoice, $payment_id, $request );
			}

			return $response;

		}
		catch( Exception $e ) {
			do_action( 'edd_xero_invoice_creation_fail', $invoice, $payment_id, $e );
		}

	}

	/**
	 * Query the Xero API for a specific invoice by number (as opposed to the ID)
	 *
	 * @since 0.1
	 *
	 * @param int $invoice_number Automatically generated human friendly invoice number. EG "INV-123"
	 * @return string|bool
	 */
	private function get_invoice( $invoice_number ) {

		try {

			// Load oauth lib
			$this->load_oauth_lib();

			// Get Invoice via Xero API
			$XeroOAuth = new XeroOAuth( $this->xero_config );

			$request = $XeroOAuth->request( 'GET', $XeroOAuth->url( 'Invoices/' . $invoice_number, 'core' ), array(), NULL );
			$response = $XeroOAuth->parseResponse( $request['response'] ,'xml' );

			$this->log( "get_invoice Invoice Number  " . $invoice_number );
			$this->log( "get_invoice Response  " . print_r( $response, true ) );

			return $response;

		}
		catch( Exception $e ) {
			return false;
		}

	}

	/**
	 * Private helper function to check whether Xero settings exist
	 *
	 * @since 0.1
	 *
	 * @return bool Returns true if valid data is configured, false if any fields are missing
	 */
	private function settings_are_valid() {

		$valid = true;

		if( $settings = edd_get_settings() ) {

			$xero_settings = array(
				'consumer_key',
				'shared_secret',
				'private_key',
				'public_key'
			);

			foreach( $xero_settings as $xero_setting ) {
				if( !isset( $settings[$xero_setting] ) || empty( $settings[$xero_setting] ) ) {
					$valid = false;
				}
			}

		}
		else {
			$valid = false;
		}

		return $valid;

	}

	/**
	 * Private helper function to load oauth lib when a request is about to be made to Xero
	 *
	 * @since 0.8
	 *
	 * @return void
	 */
	private function load_oauth_lib() {

		// Don't load twice
		if( class_exists( 'XeroOAuth' ) ) {
			return;
		}

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
	 * Plugin page links.
	 *
	 * @param $links
	 * @return array
	 */
	function plugin_links( $links ) {

		$plugin_links = array(
			'<a href="' . admin_url( 'edit.php?post_type=download&page=edd-settings&tab=extensions' ) . '">' . __( 'Settings', 'edd' ) . '</a>',
		);

		return array_merge( $plugin_links, $links );
	}

	/**
	 * @param $message
	 */
	public function log( $message ) {

		$settings = edd_get_settings();
		if ( isset( $settings['xero_debug'] ) && $settings['xero_debug'] ) {
			$dir = dirname( __FILE__ );

			$handle = fopen( trailingslashit( $dir ) . 'log.txt', 'a' );
			if ( $handle ) {
				$time   = date_i18n( 'm-d-Y @ H:i:s -' );
				fwrite( $handle, $time . " " . $message . "\n" );
				fclose( $handle );
			}
		}

	}

}

/**
* Cheeky little EDD "description" settings field callback in the global space
* Handles rendering fields of type "description"
*
* @since 0.1
*
* @return void
*/
if( !function_exists( 'edd_description_callback' ) ) {

	/**
	 *
	 */
	function edd_description_callback() {

		?>

		<p><?php _e( 'Please supply the required details for Easy Digital Downloads - Xero to function.', 'edd-xero' ); ?><br /><?php _e( 'If you need help, please follow the below list of instructions.', 'edd-xero' ); ?></p>

		<ol class="instructions">
			<li><?php echo sprintf( '%s <a href="http://api.xero.com/" target="_blank">http://api.xero.com/</a> %s', __( 'Login to', 'edd-xero' ), __( 'using your usual Xero account', 'edd-xero' ) ); ?></li>
			<li><?php echo sprintf( '%s <a href="https://api.xero.com/Application/List" target="_blank">My Applications</a> tab', __( 'Navigate to the', 'edd-xero' ), __( 'tab', 'edd-xero' ) ); ?></li>
			<li><?php _e(' Click "Add Application"', 'edd-xero' ); ?></li>
			<li><?php _e( 'You should see an option for creating a Public or Private application.', 'edd-xero' ); ?> <strong><?php _e( 'Choose Private', 'edd-xero' ); ?></strong></li>
			<li><?php _e( 'The next step is a bit tricky', 'edd-xero' ); ?>. <a href="https://developer.xero.com/documentation/auth-and-limits/private-applications" target="_blank"><?php _e( "Please follow Xero's documentation here on creating a Private Xero Application", 'edd-xero' ); ?></a></li>
			<li><?php _e( 'When you have created the application, copy and paste your Consumer Key and Consumer Secret in to the fields below', 'edd-xero' ); ?></li>
			<li><?php _e( 'After you have pasted in your Consumer Key and Consumer Secret, open your privatekey.pen and publickey.cer files and paste their contents in to the respective fields below', 'edd-xero' ); ?></li>
			<li><?php _e( 'Click Save Changes', 'edd-xero' ); ?></li>
		</ol>

		<button class="button primary" id="xero-rewrite-credentials"><?php _e( 'Force rewrite of certificate files', 'edd-xero' ); ?></button>

		<script type="text/javascript">

			// Credentials re-write button
			jQuery('#xero-rewrite-credentials').on('click', function(e) {

				// Halt browser
				e.preventDefault();

				// Save button var
				var button = jQuery(this);

				// Confirm
				if( confirm( 'Are you sure you want to force a re-write of your Xero API credentials?' ) ) {

					// Disable button and display loading text
					button.addClass('disabled').text('Please wait..');

					// Perform AJAX request which fires off re-write event
					jQuery.ajax({
						url: ajaxurl, // Piggyback off EDD var
						type: 'POST',
						dataType: 'json',
						data: {
							action: 'xero_rewrite_credentials'
						},
						success: function(result) {
							alert( result.success ? 'Successfully re-wrote your credentials' : 'There was a problem.. Please try again' );
						},
						complete: function() {
							button.removeClass('disabled').text('Force rewrite of certificate files');
						}
					});

				}

			});

		</script>

		<?php

	}

}
