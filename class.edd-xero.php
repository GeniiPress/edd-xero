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
		$api = 'https://api.xero.com/api.xro/2.0/invoices';

		$payload = array(
			'xml' => rawurlencode( $xml )
		);

		// Build OAuth related data. Xero application should be private, so the consumer secret and key should be identical to the
		// access token and secret
		$consumer_key = 'MWWD3AUAJLWHSEIBSSUBTATYCISIET';
		$shared_secret = 'ZU6OUTLJWXUCLHH8UFTBUBQMTUEFNO';

		$composite_key = sprintf( '%s&%s', rawurlencode( $consumer_key ), rawurlencode( $consumer_key ) );
		$signature_hash = hash_hmac( 'sha1', $this->build_encoded_base_string( $api, $payload, 'POST' ), $composite_key, true );

		$oauth = array(
			'oauth_consumer_key' => $consumer_key,
			'oauth_shared_secret' => $shared_secret,
			'oauth_signature_method' => 'RSA-SHA1',
			'oauth_signature' => base64_encode( $signature_hash ),
			'oauth_token' => $consumer_key,
			'oauth_nonce' => md5( microtime() ),
			'oauth_timestamp' => time(),
			'oauth_version' => '1.0'
		);

		echo '<pre>' . print_r( $oauth, true ) . '</pre>';

		// Send invoice to Xero
		$args = array(
			'method' => 'POST',
			'timeout' => 45,
			'httpversion' => '1.1',
			'headers' => array(
				'Authorization' => $this->build_oauth_header( $oauth )
			),
			'body' => $payload
		);

		$response = wp_remote_post( $api, $args );

		echo '<pre>' . print_r( $response, true ) . '</pre>';

		wp_die();

	}

	private function build_encoded_base_string ( $uri, $query, $method = 'POST' ) {

		$base_string = array();

		ksort( $query );

		foreach( $query as $key => $value ) {
			$base_string[] = sprintf( '%s=%s', $key, $value );
		}

		return sprintf( '%s&%s&%s', $method, rawurlencode( $uri ), rawurlencode( implode( '&', $base_string ) ) );

	}

	private function build_oauth_header ( $oauth ) {

		if( !is_array( $oauth ) )
			return false;

		$header = array();

		foreach( $oauth as $key => $value ) {
			$header[] = "$key=\"" . rawurlencode( $value ) . "\"";
		}

		return 'OAuth ' . implode( ', ', $header );

	}

}

?>
