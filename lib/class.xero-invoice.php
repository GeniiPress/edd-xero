<?php

// Class for handling Xero Invoice objects

class Xero_Invoice extends Xero_Resource {

	private $_contact = '';
	private $_date = '';
	private $_due_date = '';
	private $_line_amount_types = '';

	private $_line_items = array();

	public function __construct () {


	}

	public function add ( $line_item ) {

	}

}

?>
