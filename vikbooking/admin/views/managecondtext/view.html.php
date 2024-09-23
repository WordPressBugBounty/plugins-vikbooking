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

class VikBookingViewManagecondtext extends JViewVikBooking {
	
	function display($tpl = null) {
		// Set the toolbar
		$this->addToolBar();

		$cid = VikRequest::getVar('cid', array(0));
		if (!empty($cid[0])) {
			$condtextid = $cid[0];
		}

		$dbo = JFactory::getDbo();
		$condtext = array();
		if (!empty($cid[0])) {
			$q = "SELECT * FROM `#__vikbooking_condtexts` WHERE `id`=".(int)$condtextid.";";
			$dbo->setQuery($q);
			$dbo->execute();
			if ($dbo->getNumRows()) {
				$condtext = $dbo->loadAssoc();
			}
		}
		
		$this->condtext = $condtext;
		
		// Display the template
		parent::display($tpl);
	}

	/**
	 * Sets the toolbar
	 */
	protected function addToolBar() {
		$cid = VikRequest::getVar('cid', array(0));
		
		if (!empty($cid[0])) {
			//edit
			JToolBarHelper::title(JText::translate('VBO_COND_TEXT_MNG_TITLE'), 'vikbooking');
			if (JFactory::getUser()->authorise('core.edit', 'com_vikbooking')) {
				JToolBarHelper::apply('updatecondtextstay', JText::translate('VBSAVE'));
				JToolBarHelper::save('updatecondtext', JText::translate('VBSAVECLOSE'));
				JToolBarHelper::spacer();
			}
			JToolBarHelper::cancel('cancelcondtext', JText::translate('VBANNULLA'));
			JToolBarHelper::spacer();
		} else {
			//new
			JToolBarHelper::title(JText::translate('VBO_COND_TEXT_MNG_TITLE'), 'vikbooking');
			if (JFactory::getUser()->authorise('core.create', 'com_vikbooking')) {
				JToolBarHelper::save('createcondtext', JText::translate('VBSAVECLOSE'));
				JToolBarHelper::apply('createcondtextstay', JText::translate('VBSAVE'));
				JToolBarHelper::spacer();
			}
			JToolBarHelper::cancel('cancelcondtext', JText::translate('VBANNULLA'));
			JToolBarHelper::spacer();
		}
	}

}
