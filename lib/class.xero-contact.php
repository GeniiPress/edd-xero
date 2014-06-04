<?php

// Class for handling Xero Contact objects

class Xero_Contact extends Xero_Resource {

	private $_first_name = '';
	private $_last_name = '';
	private $_email = '';

	/**
	* Xero_Contact constructor
	*
	* @since 0.1
	*
	* @param array $initialize Array contained first_name, last_name and email. All keys are optional.
	* @return void
	*/
	public function __construct ( $initialize = null ) {

		if( !is_array( $initialize ) )
			return;

		if( isset( $initialize['first_name'] ) ) {
			$this->_first_name = $initialize['first_name'];
		}

		if( isset( $initialize['last_name'] ) ) {
			$this->_last_name = $initialize['last_name'];
		}

		if( isset( $initialize['email'] ) ) {
			$this->_email = $initialize['email'];
		}

	}

	/**
	* Update the first name of this contact
	*
	* @since 0.1
	*
	* @param string $first_name First name of the person to which this invoice is sent to. EG, 'Joe'
	* @return void
	*/
	public function set_first_name ( $first_name ) {
		$this->_first_name = $first_name;
	}

	/**
	* Update the last name of this contact
	*
	* @since 0.1
	*
	* @param string $last_name Last name of the person to which this invoice is sent to. EG, 'Smith'
	* @return void
	*/
	public function set_last_name ( $last_name ) {
		$this->_last_name = $last_name;
	}


	/**
	* Update the email address of this contact
	*
	* @since 0.1
	*
	* @param string $email Email address of the person to which this invoice is sent to. EG, 'joe.smith@email.com'
	* @return void
	*/
	public function set_email ( $email ) {
		$this->_email = $email;
	}

	/**
	* Generate and return XML for this Xero_Contact object
	*
	* @since 0.1
	*
	* @return string Returns generated XML for this Xero_Contact for use in the Xero API
	*/
	public function get_xml () {

		// Initialize return array
		$_ = array();

		// Open <Contact> tag
		$_[] = '<Contact>';

		// Set first name and last name
		$_[] = '<Name>' . trim( $this->_first_name . ' ' . $this->_last_name ) . '</Name>';

		// Set email address
		$_[] = '<EmailAddress>' . $this->_email . '</EmailAddress>';

		// Close <Contact> tag
		$_[] = '</Contact>';

		// Collapse in to one string and send back
		return implode( '', $_ );

	}

}
