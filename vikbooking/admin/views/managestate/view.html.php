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

class VikBookingViewManagestate extends JViewVikBooking
{
	public function display($tpl = null)
	{
		// Set the toolbar
		$this->addToolBar();

		$dbo = JFactory::getDbo();
		$app = JFactory::getApplication();

		$id = VikRequest::getInt('id', 0);

		$row = [];
		if (!empty($id)) {
			$q = "SELECT * FROM `#__vikbooking_states` WHERE `id`=" . $id;
			$dbo->setQuery($q);
			$dbo->execute();
			if (!$dbo->getNumRows()) {
				$app->enqueueMessage(JText::translate('JGLOBAL_NO_MATCHING_RESULTS'), 'error');
				$this->cancel();
				$app->close();
			}
			$row = $dbo->loadAssoc();
		}

		$countries = VikBooking::getCountriesArray($tn = true, $no_id = false);
		
		$this->row = $row;
		$this->countries = $countries;

		// Display the template
		parent::display($tpl);
	}

	/**
	 * Sets the toolbar
	 */
	protected function addToolBar() {
		$id = VikRequest::getInt('id', 0);
		
		if (!empty($id)) {
			// edit
			JToolBarHelper::title(JText::translate('VBMAINSTATESTITLE') . ' - ' . JText::translate('VBMAINPAYMENTSEDIT'), 'vikbooking');
			if (JFactory::getUser()->authorise('core.edit', 'com_vikbooking')) {
				JToolBarHelper::apply('states.update_stay', JText::translate('VBSAVE'));
				JToolBarHelper::spacer();
				JToolBarHelper::save('states.update', JText::translate('VBSAVECLOSE'));
				JToolBarHelper::spacer();
			}
			JToolBarHelper::cancel('states.cancel', JText::translate('VBANNULLA'));
			JToolBarHelper::spacer();
		} else {
			// new
			JToolBarHelper::title(JText::translate('VBMAINSTATESTITLE') . ' - ' . JText::translate('VBMAINPAYMENTSNEW'), 'vikbooking');
			if (JFactory::getUser()->authorise('core.create', 'com_vikbooking')) {
				JToolBarHelper::save('states.save', JText::translate('VBSAVE'));
				JToolBarHelper::spacer();
			}
			JToolBarHelper::cancel('states.cancel', JText::translate('VBANNULLA'));
			JToolBarHelper::spacer();
		}
	}
}
