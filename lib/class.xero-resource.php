<?php

// Abstract class which provides the basic foundation of all Xero resource objects

abstract class Xero_Resource {

	public function get_xml () {
		return 'You have not overriden get_xml() in ' . get_parent_class();
	}

}
