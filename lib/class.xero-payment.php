<?php

// Class for handling Xero Contact objects

class Xero_Payment extends Xero_Resource {

	private $_invoice_id = '';
	private $_account_code = '';
	private $_date = '';
	private $_amount = 0.00;

	/**
	* Xero_Payment constructor
	*
	* @since 1.0
	*
	* @param array $initialize Array contained first_name, last_name and email. All keys are optional.
	* @return void
	*/
	public function __construct ( $initialize = null ) {

		if( !empty( $initialize ) ) {

			$this->_invoice_id = $initialize['invoice_id'];

			if( isset( $initialize['account_code'] ) ) {
				$this->_account_code = $initialize['account_code'];
			}

			$this->_date = $initialize['date'];
			$this->_amount = $initialize['amount'];

			return $this;

		}

	}

	/**
	* Generate and return XML for this Xero_Payment object
	*
	* @since 1.0
	*
	* @return string Returns generated XML for this Xero_Payment for use in the Xero API
	*/
	public function get_xml () {

		// Initialize return array
		$_ = array();

		// Open <Contact> tag
		$_[] = '<Payments><Payment>';

		// Set invoice id
		$_[] = '<Invoice><InvoiceID>' . $this->_invoice_id . '</InvoiceID></Invoice>';

		// Set account id
		$_[] = '<Account><Code>' . $this->_account_code . '</Code></Account>';

		// Set date
		$_[] = '<Date>' . $this->_date . '</Date>';

		// Set payment amount
		$_[] = '<Amount>' . $this->_amount . '</Amount>';

		// Close <Contact> tag
		$_[] = '</Payment></Payments>';

		// Collapse in to one string and send back
		return implode( '', $_ );

	}

}
