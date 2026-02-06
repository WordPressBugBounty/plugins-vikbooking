<?php
/** 
 * @package     VikBooking
 * @subpackage  core
 * @author      E4J s.r.l.
 * @copyright   Copyright (C) 2026 E4J s.r.l. All Rights Reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * @link        https://vikwp.com
 */

// No direct access
defined('ABSPATH') or die('No script kiddies please!');

/**
 * Reservations & Pricing backup export type.
 * 
 * @since 	1.18.6 (J) - 1.8.6 (WP)
 */
class VBOBackupExportTypeRespricing extends VBOBackupExportTypeFull
{
	/**
	 * Returns a readable name of the export type.
	 * 
	 * @return 	string
	 */
	public function getName()
	{
		return sprintf('%s - %s', JText::translate('VBMENUONE'), JText::translate('VBMENUFARES'));
	}

	/**
	 * Returns a readable description of the export type.
	 * 
	 * @return 	string
	 */
	public function getDescription()
	{
		return 'Exports only time-sensitive operational data. ' .
		'Useful when migrating a live site to a parallel installation ' .
		'that is already redesigned, and that already had a full backup imported.';
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
			'#__vikbooking_custfields',
			'#__vikbooking_categories',
			'#__vikbooking_rooms',
			'#__vikbooking_characteristics',
			'#__vikbooking_optionals',
		];

		if (VBOPlatformDetection::isWordPress()) {
			// exclude shortcodes
			$exclude[] = '#__vikbooking_wpshortcodes';
		}

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
		unset($folders['media']);
		unset($folders['admincss']);
		unset($folders['mailtmpl']);
		unset($folders['invoicetmpl']);
		unset($folders['custominvoicetmpl']);
		unset($folders['checkintmpl']);
		unset($folders['sitelogo']);
		unset($folders['backlogo']);

		return $folders;
	}
}
