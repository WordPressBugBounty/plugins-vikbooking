<?php
/** 
 * @package     VikBooking
 * @subpackage  core
 * @author      E4J s.r.l.
 * @copyright   Copyright (C) 2021 E4J s.r.l. All Rights Reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * @link        https://vikwp.com
 */

// No direct access
defined('ABSPATH') or die('No script kiddies please!');

/**
 * Plain SQL Backup export rule.
 * 
 * @since 1.5
 * @since 1.6  The rule now directly extends `VBOBackupExportRuleSql` for a better reusability.
 */
class VBOBackupExportRuleSqlplain extends VBOBackupExportRuleSql
{
	/**
	 * An array of SQL statements.
	 * 
	 * @var array
	 */
	protected $queries = [];

	/**
	 * Returns the rule identifier.
	 * 
	 * @return 	string
	 */
	public function getRule()
	{
		// treat as SQL role
		return 'sql';
	}

	/**
	 * Returns the rules instructions.
	 * 
	 * @return 	mixed
	 */
	public function getData()
	{
		return $this->queries;
	}

	/**
	 * Configures the rule to work according to the specified data.
	 * 
	 * @param 	mixed 	$data  Either a query string or an array.
	 * 
	 * @return 	void
	 */
	protected function setup($data)
	{
		// reset all the registered query
		$this->queries = [];
		
		foreach ((array) $data as $query)
		{
			/**
			 * Register query through the apposite helper provided by the parent class.
			 * This way we can prevent the issue that occurs on WordPress while exporting SQL queries
			 * without executing them, namely that a "%" is always escaped with a random hash.
			 * 
			 * @since 1.6
			 */
			$this->registerQuery($query);
		}
	}
}
