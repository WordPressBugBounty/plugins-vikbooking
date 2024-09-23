<?php
/**
 * @package     VikBooking
 * @subpackage  com_vikbooking
 * @author      Alessio Gaggii - e4j - Extensionsforjoomla.com
 * @copyright   Copyright (C) 2018 e4j - Extensionsforjoomla.com. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 * @link        https://vikwp.com
 */

defined('ABSPATH') or die('No script kiddies please!');

// import Joomla view library
jimport('joomla.application.component.view');

class VikBookingViewDashboard extends JViewVikBooking
{
	public function display($tpl = null)
	{
		// Set the toolbar
		$this->addToolBar();

		if (VBOPlatformDetection::isWordPress()) {
			/**
			 * @wponly - trigger back up of extendable files
			 */
			VikBookingLoader::import('update.manager');
			VikBookingUpdateManager::triggerExtendableClassesBackup('languages', "/^.+\-((?!en_US|it_IT).)+$/");
		} else {
			/**
			 * @joomlaonly 	Extra fields for Joomla XML Updates
			 */
			$jvobj = new JVersion;
			$jv = $jvobj->getShortVersion();
			if (version_compare($jv, '3.2.0', '>=')) {
				// With this method we populate the extra fields for this extension. We need to store the domain name encoded in base64 for the download of commercial updates.
				// Without the record stored this way, our Update Servers will reject the download request.
				require_once VBO_ADMIN_PATH . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'urihandler.php';

				$domain = JFactory::getApplication()->input->server->getString('HTTP_HOST');

				$update = new UriUpdateHandler('com_vikbooking');
				$update->addExtraField('domain', base64_encode($domain));
				$ord_num = JFactory::getApplication()->input->getString('order_number');
				if (!empty($ord_num)) {
					$update->addExtraField('order_number', $ord_num);
				}
				$update->checkSchema(E4J_SOFTWARE_VERSION);
				$update->register();
			}
		}

		$this->metrics = VikBookingHelper::getFirstSetupMetrics();

		// Display the template
		parent::display($tpl);
	}

	/**
	 * Sets the toolbar
	 */
	protected function addToolBar()
	{
		JToolBarHelper::title(JText::translate('VBMAINDASHBOARDTITLE'), 'vikbooking');
		if (JFactory::getUser()->authorise('core.admin', 'com_vikbooking')) {
			JToolBarHelper::preferences('com_vikbooking');

			/**
			 * @wponly 	add toolbar button for Shortcodes.
			 */
			if (VBOPlatformDetection::isWordPress()) {
				JToolBarHelper::shortcodes('com_vikbooking');
			}
		}
	}
}
