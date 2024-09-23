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
 * FULL Backup export type.
 * 
 * @since 1.5
 */
class VBOBackupExportTypeFull implements VBOBackupExportType
{
	/**
	 * Returns a readable name of the export type.
	 * 
	 * @return 	string
	 */
	public function getName()
	{
		return JText::translate('VBO_BACKUP_EXPORT_TYPE_FULL');
	}

	/**
	 * Returns a readable description of the export type.
	 * 
	 * @return 	string
	 */
	public function getDescription()
	{
		return JText::translate('VBO_BACKUP_EXPORT_TYPE_FULL_DESCRIPTION');
	}

	/**
	 * Configures the backup director.
	 * 
	 * @param 	VBOBackupExportDirector  $director
	 * 
	 * @return 	void
	 */
	public function build(VBOBackupExportDirector $director)
	{
		// fetch database tables to export
		$tables = $this->getDatabaseTables();

		// iterate all database tables
		foreach ($tables as $table)
		{
			// create SQL export rule
			$director->createRule('sqlfile', $table);
		}

		// register the UPDATE queries for the configuration table
		$director->createRule('sqlplain', $this->getConfigSQL());

		// fetch folders to export
		$folders = $this->getFolders();

		// iterate all folders to copy
		foreach ($folders as $folder)
		{
			// create FOLDER export rule
			$director->createRule('folder', $folder);
		}
	}

	/**
	 * Returns an array of database tables to export.
	 * 
	 * @return 	array
	 */
	protected function getDatabaseTables()
	{
		$dbo = JFactory::getDbo();

		// load all the installed database tables
		$tables = $dbo->getTableList();

		// get current database prefix
		$prefix = $dbo->getPrefix();

		// replace prefix with placeholder
		$tables = array_map(function($table) use ($prefix)
		{
			return preg_replace("/^{$prefix}/", '#__', $table);
		}, $tables);

		// remove all the tables that do not belong to VikBooking
		$tables = array_values(array_filter($tables, function($table)
		{
			if (preg_match("/^#__vik(?:booking|channelmanager)_config$/", $table))
			{
				// exclude the configuration table, which will be handled in a different way
				return false;
			}

			return preg_match("/^#__vik(?:booking|channelmanager)_/", $table);
		}));

		return $tables;
	}

	/**
	 * Returns an associative array of folders to export, where the key is equals
	 * to the path to copy and the value is the relative destination path.
	 * 
	 * @return 	array
	 */
	protected function getFolders()
	{
		$folders = [
			'media' => [
				'source'      => JPath::clean(VBO_SITE_PATH . '/resources/uploads'),
				'destination' => 'media',
				'target'      => ['VBO_SITE_PATH', 'resources/uploads'],
			],
			'checkins' => [
				'source'      => JPath::clean(VBO_SITE_PATH . '/helpers/checkins/generated'),
				'destination' => 'checkins',
				'target'      => ['VBO_SITE_PATH', 'helpers/checkins/generated'],
			],
			'invoices' => [
				'source'      => JPath::clean(VBO_SITE_PATH . '/helpers/invoices/generated'),
				'destination' => 'invoices',
				'target'      => ['VBO_SITE_PATH', 'helpers/invoices/generated'],
			],
			'idscans' => [
				'source'      => JPath::clean(VBO_ADMIN_PATH . '/resources/idscans'),
				'destination' => 'idscans',
				'target'      => ['VBO_ADMIN_PATH', 'resources/idscans'],
			],
			'admincss' => [
				'source'      => JPath::clean(VBO_ADMIN_PATH . '/resources/vikbooking_backendcustom.css'),
				'destination' => 'css/admin',
				'target'      => ['VBO_ADMIN_PATH', 'resources'],
			],
			'mailtmpl' => [
				'source'      => JPath::clean(VBO_SITE_PATH . '/helpers/email_tmpl.php'),
				'destination' => 'tmpl',
				'target'      => ['VBO_SITE_PATH', 'helpers'],
			],
			'errorform' => [
				'source'      => JPath::clean(VBO_SITE_PATH . '/helpers/error_form.php'),
				'destination' => 'tmpl',
				'target'      => ['VBO_SITE_PATH', 'helpers'],
			],
			'invoicetmpl' => [
				'source'      => JPath::clean(VBO_SITE_PATH . '/helpers/invoices/invoice_tmpl.php'),
				'destination' => 'tmpl',
				'target'      => ['VBO_SITE_PATH', 'helpers/invoices'],
			],
			'custominvoicetmpl' => [
				'source'      => JPath::clean(VBO_SITE_PATH . '/helpers/invoices/custom_invoice_tmpl.php'),
				'destination' => 'tmpl',
				'target'      => ['VBO_SITE_PATH', 'helpers/invoices'],
			],
			'checkintmpl' => [
				'source'      => JPath::clean(VBO_SITE_PATH . '/helpers/checkins/checkin_tmpl.php'),
				'destination' => 'tmpl',
				'target'      => ['VBO_SITE_PATH', 'helpers/checkins'],
			],
			'customerdocs' => [
				'source'      => VBO_CUSTOMERS_PATH,
				'destination' => 'customerdocs',
				'target'      => 'VBO_CUSTOMERS_PATH',
				'recursive'   => true,
			],
			'visualeditor' => [
				'source'      => VBO_MEDIA_PATH,
				'destination' => 'visualeditor',
				'target'      => 'VBO_MEDIA_PATH',
			],
		];

		if ($sitelogo = VBOFactory::getConfig()->get('sitelogo'))
		{
			$folders['sitelogo'] = [
				'source'      => JPath::clean(VBO_ADMIN_PATH . '/resources/' . $sitelogo),
				'destination' => 'logos',
				'target'      => ['VBO_ADMIN_PATH', 'resources'],
			];
		}

		if ($backlogo = VBOFactory::getConfig()->get('backlogo'))
		{
			$folders['backlogo'] = [
				'source'      => JPath::clean(VBO_ADMIN_PATH . '/resources/' . $backlogo),
				'destination' => 'logos',
				'target'      => ['VBO_ADMIN_PATH', 'resources'],
			];
		}

		return $folders;
	}

	/**
	 * Returns an array of queries used to keep the configuration up-to-date.
	 * 
	 * @return 	array
	 */
	protected function getConfigSQL()
	{
		$dbo = JFactory::getDbo();

		$sql = [];

		// define list of parameters to ignore
		$lookup = [
			'vikbooking' => [
				'update_extra_fields',
				'backupfolder',
			],
			'vikchannelmanager' => [
				'version',
				'to_update',
			],
		];

		if (!is_dir(VCM_ADMIN_PATH))
		{
			unset($lookup['vikchannelmanager']);
		}

		foreach ($lookup as $table => $exclude)
		{
			// prepare update statement
			$update = $dbo->getQuery(true)->update($dbo->qn('#__' . $table . '_config'));

			// fetch all configuration settings
			$q = $dbo->getQuery(true)
				->select($dbo->qn(['param', 'setting']))
				->from($dbo->qn('#__' . $table . '_config'));

			if ($exclude)
			{
				$q->where($dbo->qn('param') . ' NOT IN (' . implode(',', array_map([$dbo, 'q'], $exclude)) . ')');
			}

			$dbo->setQuery($q);
			$dbo->execute();

			if ($dbo->getNumRows())
			{
				// iterate all settings
				foreach ($dbo->loadObjectList() as $row)
				{
					// clear update
					$update->clear('set')->clear('where');
					// define value to set
					$update->set($dbo->qn('setting') . ' = ' . $dbo->q($row->setting));
					// define parameter to update
					$update->where($dbo->qn('param') . ' = ' . $dbo->q($row->param));

					$sql[] = (string) $update;
				}
			}
		}

		return $sql;
	}
}
