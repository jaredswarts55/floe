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
 * Formalizes the belongsTo/hasOne join, where one class depends on another.
 */
class DependentRelation {
	var $owner;
	var $dependent;

	function __construct($owner, $dependent) {
		$this->owner = $owner;
		$this->dependent = $dependent;
	}

}
