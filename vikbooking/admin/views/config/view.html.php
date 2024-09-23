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

class VikBookingViewConfig extends JViewVikBooking
{
	public function display($tpl = null)
	{
		// Set the toolbar
		$this->addToolBar();

		if (!JFactory::getUser()->authorise('core.vbo.global', 'com_vikbooking')) {
			VBOHttpDocument::getInstance()->close(403, JText::translate('JERROR_ALERTNOAUTHOR'));
		}

		if (VBOPlatformDetection::isWordPress()) {
			/**
			 * @wponly - trigger back up of extendable files
			 */
			VikBookingLoader::import('update.manager');
			VikBookingUpdateManager::triggerExtendableClassesBackup('smsapi');
		}

		$dbo = JFactory::getDbo();
		$preset_tags = VikRequest::getInt('reset_tags', '', 'request');
		if ($preset_tags > 0) {
			$q = "UPDATE `#__vikbooking_orders` SET `colortag`=NULL;";
			$dbo->setQuery($q);
			$dbo->execute();
			$q = "UPDATE `#__vikbooking_config` SET `setting`='' WHERE `param`='bookingsctags';";
			$dbo->setQuery($q);
			$dbo->execute();
			$mainframe = JFactory::getApplication();
			$mainframe->redirect("index.php?option=com_vikbooking&task=config");
			exit;
		}

		$cookie = JFactory::getApplication()->input->cookie;
		$curtabid = $cookie->get('vbConfPt', '', 'string');
		$curtabid = empty($curtabid) ? 1 : (int)$curtabid;

		/**
		 * Pre-select one specific tab via query string.
		 * 
		 * @since 	1.14 (J) - 1.4.0 (WP)
		 */
		$tab = VikRequest::getInt('tab', 0, 'request');
		if (!empty($tab)) {
			$curtabid = $tab;
		}

		$this->curtabid = $curtabid;

		/**
		 * Fetch all the supported backup export types.
		 * 
		 * @since 	1.15 (J) - 1.5 (WP)
		 */
		$this->backupExportTypes = VBOBackupManager::getExportTypes();
		
		// Display the template
		parent::display($tpl);
	}

	/**
	 * Sets the toolbar
	 */
	protected function addToolBar()
	{
		JToolBarHelper::title(JText::translate('VBMAINCONFIGTITLE'), 'vikbookingconfig');
		if (JFactory::getUser()->authorise('core.edit', 'com_vikbooking')) {
			JToolBarHelper::apply( 'saveconfig', JText::translate('VBSAVE'));
			JToolBarHelper::spacer();
		}
		JToolBarHelper::cancel( 'cancel', JText::translate('VBANNULLA'));
		JToolBarHelper::spacer();
	}
}
