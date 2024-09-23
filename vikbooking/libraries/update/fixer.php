<?php
/** 
 * @package   	VikBooking - Libraries
 * @subpackage 	update
 * @author    	E4J s.r.l.
 * @copyright 	Copyright (C) 2018 E4J s.r.l. All Rights Reserved.
 * @license  	http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * @link 		https://vikwp.com
 */

// No direct access
defined('ABSPATH') or die('No script kiddies please!');

/**
 * Implements the abstract methods to fix an update.
 *
 * Never use exit() and die() functions to stop the flow.
 * Return false instead to break process safely.
 */
class VikBookingUpdateFixer
{
	/**
	 * The current version.
	 *
	 * @var string
	 */
	protected $version;

	/**
	 * Class constructor.
	 */
	public function __construct($version)
	{
		$this->version = $version;
	}

	/**
	 * This method is called before the SQL installation.
	 *
	 * @return 	boolean  True to proceed with the update, otherwise false to stop.
	 */
	public function beforeInstallation()
	{
		/**
		 * Make sure all the necessary (and new) directories have been created.
		 * 
		 * @since 	1.5.0
		 */
		VikBookingUpdateManager::installUploadBackup();

		if (version_compare($this->version, '1.5.0', '<')) {
			/**
			 * For those upgrading to VBO 1.5.0 we need to move the customer upload
			 * document directories to the new location. Basically, they have been
			 * moved to the new dir /customerdocs inside the plugin's upload dir of WP.
			 * 
			 * @since 	1.5.0
			 */
			$dbo = JFactory::getDbo();

			$old_custdocs_path = str_replace(DIRECTORY_SEPARATOR . 'customerdocs', '', VBO_CUSTOMERS_PATH);
			$new_custdocs_path = VBO_CUSTOMERS_PATH;

			$q = "SELECT `docsfolder` FROM `#__vikbooking_customers` WHERE `docsfolder` IS NOT NULL";
			$dbo->setQuery($q);
			$dbo->execute();
			if ($dbo->getNumRows()) {
				$doc_folders = $dbo->loadObjectList();
				foreach ($doc_folders as $doc_folder) {
					if (empty($doc_folder->docsfolder)) {
						continue;
					}
					// move directory to new path
					$from_dir = $old_custdocs_path . DIRECTORY_SEPARATOR . $doc_folder->docsfolder;
					$to_dir   = $new_custdocs_path . DIRECTORY_SEPARATOR . $doc_folder->docsfolder;
					if (JFolder::copy($from_dir, $to_dir, $path = '', $force = true)) {
						JFolder::delete($from_dir);
					}
				}
			}
		}

		if (version_compare($this->version, '1.5.11', '<') && !VikBookingLiteManager::guessPro())
		{
			$dbo = JFactory::getDbo();
			// truncate the payment gateways table
			$dbo->setQuery("TRUNCATE TABLE `#__vikbooking_gpayments`");
			$dbo->execute();
		}

		if (version_compare($this->version, '1.6.0', '<'))
		{
			if (!class_exists('VikBooking')) {
				require_once VBO_SITE_PATH . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'lib.vikbooking.php';
			}

			// normalize translation records table name
			VikBooking::getTranslator()->normalizeTnTableNames();
		}

		return true;
	}

	/**
	 * This method is called after the SQL installation.
	 *
	 * @return 	boolean  True to proceed with the update, otherwise false to stop.
	 */
	public function afterInstallation()
	{
		/**
		 * Unpublish overrides and obtain tracking list.
		 *
		 * @since 1.6.5
		 */
		$track = $this->deactivateBreakingOverrides();

		// register breaking changes, if any
		VikBookingInstaller::registerBreakingChanges($track);

		if (version_compare($this->version, '1.6.1', '<'))
		{
			// resolve conflicting update checks for older VCM versions (1.8.4)
			if (defined('VIKCHANNELMANAGER_SOFTWARE_VERSION') && version_compare(VIKCHANNELMANAGER_SOFTWARE_VERSION, '1.8.5', '<'))
			{
				$old_vcm_data = file_get_contents(VCM_ADMIN_PATH . DIRECTORY_SEPARATOR . 'controller.php');
				$old_vcm_data = str_replace("'forcecheck'", "'force_check'", $old_vcm_data);
				file_put_contents(VCM_ADMIN_PATH . DIRECTORY_SEPARATOR . 'controller.php', $old_vcm_data);
			}
		}

		return true;
	}

	/**
	 * Returns a list of possible overrides that may
	 * break the site for backward compatibility errors.
	 *
	 * @return 	array  The list of overrides, grouped by client.
	 * 
	 * @since   1.6.5
	 */
	protected function getBreakingOverrides()
	{
		// define initial overrides lookup
		$lookup = [
			'admin'   => [],
			'site'    => [],
			'layouts' => [],
			'widgets' => [],
		];

		// check whether the current version (before the update)
		// was prior than 1.6.5 version
		if (version_compare($this->version, '1.6.5', '<'))
		{
			// multitasking layout must be updated to support the Service Worker
			$lookup['layouts'][] = VBO_ADMIN_PATH . '/layouts/sidepanel/multitasking.php';
		}

		/**
		 * NOTE: it is possible to use the code below to automatically deactivate all the existing overrides:
		 * `$lookup = JModel::getInstance('vikbooking', 'overrides', 'admin')->getAllOverrides();`
		 */

		return $lookup;
	}

	/**
	 * Helper function used to deactivate any overrides that
	 * may corrupt the system because of breaking changes.
	 *
	 * @return 	array  The list of unpublished overrides.
	 *
	 * @since 	1.6.5
	 */
	protected function deactivateBreakingOverrides()
	{
		// load list of breaking overrides
		$lookup = $this->getBreakingOverrides();

		$track = [];

		// get models to manage the overrides
		$listModel = JModel::getInstance('vikbooking', 'overrides', 'admin');
		$itemModel = JModel::getInstance('vikbooking', 'override', 'admin');

		foreach ($lookup as $client => $files)
		{
			// do not need to load the whole tree in case
			// the client doesn't report any files
			if ($files)
			{
				$tree = $listModel->getTree($client);

				foreach ($files as $file)
				{
					// clean file path
					$file = JPath::clean($file);

					// check whether the specified file is supported
					if ($node = $listModel->isSupported($tree, $file))
					{
						// skip in case the path has been already unpublished
						if (in_array($node['override'], isset($track[$client]) ? $track[$client] : []))
						{
							continue;
						}

						// override found, check whether we have an existing
						// and published override
						if ($node['has'] && $node['published'])
						{
							// deactivate the override
							if ($itemModel->publish($node['override'], 0))
							{
								if (!isset($track[$client]))
								{
									$track[$client] = [];
								}

								// track the unpublished file for later use
								$track[$client][] = $node['override'];
							}
						}
					}
				}
			}
		}

		return $track;
	}
}
