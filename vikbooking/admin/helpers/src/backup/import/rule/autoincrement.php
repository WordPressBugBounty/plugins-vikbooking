<?php
/** 
 * @package     VikBooking
 * @subpackage  core
 * @author      E4J s.r.l.
 * @copyright   Copyright (C) 2023 E4J s.r.l. All Rights Reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * @link        https://vikwp.com
 */

// No direct access
defined('ABSPATH') or die('No script kiddies please!');

/**
 * Backup SQL Autoincrement import rule.
 * 
 * @since 1.16.4 (J) - 1.6.4 (WP)
 */
class VBOBackupImportRuleAutoincrement extends VBOBackupImportRule
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

		$pk = null;

		foreach ($dbo->getTableColumns($data->table, $typeOnly = false) as $column => $info)
		{
			if ($info->Extra === 'auto_increment')
			{
				$pk = $column;
			}
		}

		if (!$pk)
		{
			// no primary keys with auto increment
			return;
		}

		// fetch highest ID
		$q = $dbo->getQuery(true)->select('MAX(' . $dbo->qn($pk) . ')')->from($dbo->qn($data->table));

		$dbo->setQuery($q);
		$dbo->execute();

		$set_ai = (int) $dbo->loadResult() + 1;

		$dbo->setQuery("ALTER TABLE `{$data->table}` AUTO_INCREMENT = {$set_ai}");
		$dbo->execute();
	}
}
