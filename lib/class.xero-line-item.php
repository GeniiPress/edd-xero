<?php

// Class for handling Xero Line Item objects

class Xero_Line_Item extends Xero_Resource {

	private $_description = '';
	private $_quantity = 0;
	private $_unitamount = 0;
	private $_accountcode = 200;

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

			return $this;

		}

	}

	public function get_description () { return $this->_description; }
	public function get_quantity () { return $this->_quantity; }
	public function get_unit_amount () { return $this->_unitamount; }

}

?>
