<?php

// Class for handling Xero Line Item objects

class Xero_Line_Item extends Xero_Resource {

	private $_description = '';
	private $_quantity = 0;
	private $_unitamount = 0;
	private $_tax = 0;
	private $_total = 0;
	private $_accountcode = 0;


	/**
	* Xero_Line_Item constructor. Takes instantiation array as only parameter and returns self
	*
	* @since 0.1
	*
	* @param array $initialize Array containing a description, quantity and unitamount
	* @return Xero_Line_Item self
	*/
	public function __construct ( $initialize = null ) {

		if( !empty( $initialize ) ) {

			$this->_description = $initialize['description'];
			$this->_quantity = $initialize['quantity'];
			$this->_unitamount = $initialize['unitamount'];
			$this->_tax = $initialize['tax'];
			$this->_total = $initialize['total'];
			$this->_accountcode = $initialize['accountcode'];

			return $this;

		}

	}

	/**
	* Generate and return XML for this Xero Line Item which will be sent to the Xero API
	*
	* @since 0.1
	*
	* @return string Returns Line Item XML for use with the Xero API
	*/
	public function get_xml () {

		// Initialize XML array
		$_ = array();

		// Open <LineItem> tag
		$_[] = '<LineItem>';

		// Description
		$_[] = '<Description>' . $this->_description . '</Description>';

		// Quantity
		$_[] = '<Quantity>' . $this->_quantity . '</Quantity>';

		// Unit amount (price)
		$_[] = '<UnitAmount>' . $this->_unitamount . '</UnitAmount>';

		// Unit tax
		$_[] = '<TaxAmount>' . $this->_tax . '</TaxAmount>';

		// Account code
		$_[] = '<AccountCode>' . $this->_accountcode . '</AccountCode>';

		// Close <LineItem> tag
		$_[] = '</LineItem>';

		// Return as one XML string
		return implode( '', $_ );

	}

}
