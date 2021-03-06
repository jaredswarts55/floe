<?php
/**
* This file is part of Floe, a graceful web framework.
* Copyright (C) 2005-2010 Mark Rickerby <http://maetl.net>
*
* See the LICENSE file distributed with this software for full copyright, disclaimer
* of liability, and the specific limitations that govern the use of this software.
*
* @package repository
 */

/**
 * Record not found.
 * 
 * @package repository
 */
class RecordNotFound extends Exception {
	var $status = 404;
	var $message = "Record Not Found";
	var $resource;
	var $include;
	
	function __construct($resource, $include) {
		$this->resource = $resource;
		$this->include = $include;
	}
	
}
