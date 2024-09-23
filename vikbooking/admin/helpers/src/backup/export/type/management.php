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
 * MANAGEMENT backup export type.
 * 
 * @since 1.5
 */
class VBOBackupExportTypeManagement extends VBOBackupExportTypeFull
{
	/**
	 * Returns a readable name of the export type.
	 * 
	 * @return 	string
	 */
	public function getName()
	{
		return JText::translate('VBO_BACKUP_EXPORT_TYPE_MANAGEMENT');
	}

	/**
	 * Returns a readable description of the export type.
	 * 
	 * @return 	string
	 */
	public function getDescription()
	{
		return JText::translate('VBO_BACKUP_EXPORT_TYPE_MANAGEMENT_DESCRIPTION');
	}

	/**
	 * Returns an array of database tables to export.
	 * 
	 * @return 	array
	 */
	protected function getDatabaseTables()
	{
		// get database tables from parent
		$tables = parent::getDatabaseTables();

		// define list of database tables to exclude
		$exclude = [
			'#__vikbooking_busy',
			'#__vikbooking_customers',
			'#__vikbooking_customers_orders',
			'#__vikbooking_orders',
			'#__vikbooking_ordersbusy',
			'#__vikbooking_ordersrooms',
			'#__vikbooking_orderhistory',
			// channel manager
			'#__vikchannelmanager_keys',
			'#__vikchannelmanager_messaging_users_pings',
			'#__vikchannelmanager_notifications',
			'#__vikchannelmanager_notification_child',
			'#__vikchannelmanager_order_messaging_data',
			'#__vikchannelmanager_orders',
			'#__vikchannelmanager_otareviews',
			'#__vikchannelmanager_otascores',
			'#__vikchannelmanager_reslogs',
			'#__vikchannelmanager_threads',
			'#__vikchannelmanager_threads_messages',
		];

		// remove the specified tables from the list
		$tables = array_values(array_diff($tables, $exclude));

		return $tables;
	}

	/**
	 * Returns an array of files to export.
	 * 
	 * @return 	array
	 */
	protected function getFolders()
	{
		// get folders from parent
		$folders = parent::getFolders();

		// unset some folders
		unset($folders['checkins']);
		unset($folders['invoices']);
		unset($folders['idscans']);
		unset($folders['customerdocs']);
		unset($folders['visualeditor']);

		return $folders;
	}
}
