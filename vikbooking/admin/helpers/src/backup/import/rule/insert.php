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
 * Backup SQL INSERT import rule.
 * 
 * @since 1.5
 */
class VBOBackupImportRuleInsert extends VBOBackupImportRule
{
	/**
	 * Executes the backup import command.
	 * 
	 * @param 	mixed  $data  The import rule instructions.
	 * 
	 * @return 	void
	 */
	public function execute($data)
	{
		$dbo = JFactory::getDbo();

		$dbo->setQuery($data);
		$dbo->execute();
	}
}
