<?php
/**
 * Xero_Contact
 *
 * Class for handling Xero Contact objects
 *
 * @version 0.2
 */

class Xero_Contact extends Xero_Resource {

	private $_first_name     = '';
	private $_last_name      = '';
	private $_email          = '';
	private $_contact_number = '';
	private $_name           = '';
	/**
	 * The default mailing address for invoices
	 * @see https://developer.xero.com/documentation/api/types/#Addresses
	 * @var string
	 */
	private $_address_type   = 'POBOX';
	private $_address_1      = '';
	private $_address_2      = '';
	private $_city           = '';
	private $_region         = '';
	private $_postal_code    = '';
	private $_country        = '';


	/**
	 * Xero_Contact constructor
	 *
	 * @since 0.1
	 *
	 * @param array $initialize Array contained first_name, last_name and email. All keys are optional.
	 */
	public function __construct ( $initialize = null ) {

		if( !is_array( $initialize ) )
			return;

		if( isset( $initialize['first_name'] ) ) {
			$this->_first_name = $this->escape_xml( $initialize['first_name'] );
		}

		if( isset( $initialize['last_name'] ) ) {
			$this->_last_name = $this->escape_xml( $initialize['last_name'] );
		}

		if( isset( $initialize['email'] ) ) {
			$this->_email = $initialize['email'];
		}

		if( isset( $initialize['name'] ) ) {
			$this->_name = $this->escape_xml( $initialize['name'] );
		}

		if( isset( $initialize['contact_number'] ) ) {
			$this->_contact_number = $initialize['contact_number'];
		}

		if( isset( $initialize['address_1'] ) ) {
			$this->_address_1 = $this->escape_xml( $initialize['address_1'] );
		}

		if( isset( $initialize['address_2'] ) ) {
			$this->_address_2 = $this->escape_xml( $initialize['address_2'] );
		}

		if( isset( $initialize['city'] ) ) {
			$this->_city = $this->escape_xml( $initialize['city'] );
		}

		if( isset( $initialize['region'] ) ) {
			$this->_region = $initialize['region'];
		}

		if( isset( $initialize['postal_code'] ) ) {
			$this->_postal_code = $initialize['postal_code'];
		}

		if( isset( $initialize['country'] ) ) {
			$this->_country = $initialize['country'];
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
	 * Update name of the contact. Field is used if first_name and last_name are empty.
	 *
	 * @since 0.2
	 *
	 * @param string $name Name of the contact. EG, 'ABC Inc.'
	 * @return void
	 */
	public function set_name ( $name ) {
		$this->_name = $name;
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
	 * Update the email address of this contact
	 *
	 * @since 0.2
	 *
	 * @param string $contact_number Contact Number, usually the WordPress User ID
	 * @return void
	 */
	public function set_contact_number ( $contact_number ) {
		$this->_contact_number = $contact_number;
	}


	/**
	* Generate and return XML for this Xero_Contact object
	*
	* @since 0.2
	*
	* @return string Returns generated XML for this Xero_Contact for use in the Xero API
	*/
	public function get_xml () {

		// Initialize return array
		$_ = array();

		// Open <Contact> tag
		$_[] = '<Contact>';

		if ( !empty( $this->_first_name) && !empty($this->_last_name) ) {
			// Set first name and last name
			$_[] = '<Name>' . trim( $this->_first_name . ' ' . $this->_last_name ) . '</Name>';
		} elseif ( !empty ( $this->_name ) ) {
			$_[] = '<Name>' . trim( $this->_name ) . '</Name>';
		} else {
			$_[] = '<Name>' . trim( $this->_first_name ) . '</Name>';
		}

		if ( !empty( $this->_contact_number ) ) {
			// Set contact number
			$_[] = '<ContactNumber>' . trim( $this->_contact_number ) . '</ContactNumber>';
		}

		// Set email address
		$_[] = '<EmailAddress>' . $this->_email . '</EmailAddress>';

		// Set Address if it exists
		if ( !empty( $this->_address_1 ) ) {
			$_[] = '<Addresses>';
			$_[] = '<Address>';
			$_[] = '<AddressType>' . $this->_address_type . '</AddressType>';
			$_[] = '<AddressLine1>' . $this->_address_1 . '</AddressLine1>';
			$_[] = '<AddressLine2>' . $this->_address_2 . '</AddressLine2>';
			$_[] = '<City>' . $this->_city . '</City>';
			$_[] = '<PostalCode>' . $this->_postal_code . '</PostalCode>';
			$_[] = '<Country>' . $this->_country . '</Country>';
			$_[] = '</Address>';
			$_[] = '</Addresses>';
		}
		// Close <Contact> tag
		$_[] = '</Contact>';

		// Collapse in to one string and send back
		return implode( '', $_ );

	}

}
