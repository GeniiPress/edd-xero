<?php

// Class for handling Xero Invoice objects

class Xero_Invoice extends Xero_Resource {

	private $_contact = '';
	private $_date = '';
	private $_due_date = '';
	private $_line_amount_types = '';
	private $_currency_code = '';
	private $_status = 'DRAFT';

	private $_line_items = array();

	/**
	* Xero_Invoice constructor
	*
	* @since 0.1
	*
	* @return void
	*/
	public function __construct () {


	}

	/**
	* Change the status of an invoice.
	*
	* @since 1.0
	*
	* @param string $new_status The new status of the invoice. Must be either "DRAFT", "SUBMITTED" or "AUTHORISED"
	* @return void
	*/
	public function set_status ( $new_status ) {

		// Invoice statuses that Xero supports. $new_status must match one of these
		$statuses = array( 'DRAFT', 'SUBMITTED', 'AUTHORISED' );

		// Ensure specified status is valid
		if( in_array( $new_status, $statuses ) ){
			return $this->_status = $new_status;
		}
		else {
			return false;
		}

	}

	/**
	* Change the currency code of this invoice.
	*
	* @since 1.0
	*
	* @param string $new_status The new status of the invoice. Must be either "DRAFT", "SUBMITTED" or "AUTHORISED"
	* @return void
	*/
	public function set_currency_code ( $new_currency_code ) {
		$this->_currency_code = $new_currency_code;
	}

	/**
	* Adds a Xero_Line_Item to the $_line_items array for later use
	*
	* @since 0.1
	*
	* @param Xero_Line_Item $line_item The line item object to add to the invoice
	* @return void
	*/
	public function add ( $line_item ) {

		if( $line_item instanceof Xero_Line_Item ) {
			$this->_line_items[] = $line_item;
		}

	}

	/**
	* Set the contact name of this invoice
	*
	* @since 0.1
	*
	* @param string $name Name of the person to which this invoice is sent to. EG, 'Joe Bloggs'
	* @return void
	*/
	public function set_contact ( $contact ) {

		if( $contact instanceof Xero_Contact ) {
			$this->_contact = $contact;
		}

	}

	/**
	* Set the creation date of this invoice
	*
	* @since 0.1
	*
	* @param string $date Date this invoice is to be created. Format 'YYYY-MM-DD'
	* @return void
	*/
	public function set_date ( $date ) {
		$this->_date = $date;
	}

	/**
	* Set the due date of this invoice
	*
	* @since 0.1
	*
	* @param string $date Date this invoice is to be due on. Format 'YYYY-MM-DD'
	* @return void
	*/
	public function set_due_date ( $due_date ) {
		$this->_due_date = $due_date;
	}

	/**
	* Generate and return XML for this Xero Invoice which will be sent to the Xero API
	*
	* @since 0.1
	*
	* @return string Returns XML for use with the Xero API
	*/
	public function get_xml () {

		// Initialize XML array
		$_ = array();

		// Open Invoice element and set as a sales invoice
		$_[] = '<Invoice>';
		$_[] = '<Type>ACCREC</Type>';

		// Get <Contact>...</Contact> XML
		$_[] = $this->_contact->get_xml();

		// Set status
		$_[] = '<Status>' . $this->_status . '</Status>';

		// Set currency code if applicable
		if( !empty( $this->_currency_code ) ) {
			$_[] = '<CurrencyCode>' . $this->_currency_code . '</CurrencyCode>';
		}

		// Set dates
		$_[] = '<Date>' . $this->_date . '</Date>';
		$_[] = '<DueDate>' . $this->_due_date . '</DueDate>';

		// Get line items
		$_[] = '<LineItems>';

		foreach( $this->_line_items as $line_item ) {
			$_[] = $line_item->get_xml();
		}

		$_[] = '</LineItems>';
		$_[] = '<LineAmountTypes>Exclusive</LineAmountTypes>';

		// Close <Invoice> tag
		$_[] = '</Invoice>';

		// Collapse in to one string and send back
		return implode( '', $_ );

	}

}
