<?php

// Abstract class which provides the basic foundation of all Xero resource objects

abstract class Xero_Resource {

	public function get_xml () {
		return 'You have not overriden get_xml() in ' . get_parent_class();
	}

	/**
	 * Escape illegal characters in XML
	 *
	 * @param $string
	 *
	 * @return mixed
	 */
	public function escape_xml( $string ){
		return str_replace(array('&', '<', '>', '\'', '"'), array('&amp;', '&lt;', '&gt;', '&apos;', '&quot;'), $string);
	}

}
