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

class VikBookingViewManagecarat extends JViewVikBooking {
	
	function display($tpl = null) {
		// Set the toolbar
		$this->addToolBar();

		$cid = VikRequest::getVar('cid', array(0));
		if (!empty($cid[0])) {
			$id = $cid[0];
		}

		$dbo = JFactory::getDbo();
		$row = array();
		$allrooms = array();
		if (!empty($cid[0])) {
			$q = "SELECT * FROM `#__vikbooking_characteristics` WHERE `id`=".(int)$id.";";
			$dbo->setQuery($q);
			$dbo->execute();
			if ($dbo->getNumRows() != 1) {
				VikError::raiseWarning('', 'Not found.');
				$mainframe = JFactory::getApplication();
				$mainframe->redirect("index.php?option=com_vikbooking&task=carat");
				exit;
			}
			$row = $dbo->loadAssoc();
		}
		
		// read all rooms
		$q = "SELECT `id`, `name`, `idcarat` FROM `#__vikbooking_rooms`;";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows()) {
			$records = $dbo->loadAssocList();
			foreach ($records as $r) {
				$r['idcarat'] = empty($r['idcarat']) ? array() : explode(';', rtrim($r['idcarat'], ';'));
				$allrooms[$r['id']] = $r;
			}
		}
		
		// preset icons
		$exclude_html = array();
		$q = "SELECT `textimg` FROM `#__vikbooking_characteristics`;";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows()) {
			$records = $dbo->loadAssocList();
			foreach ($records as $v) {
				if (count($row) && !empty($row['textimg']) && $row['textimg'] == $v['textimg']) {
					// when in edit mode, we want the current icon to be kept in the list
					continue;
				}
				if (strpos($v['textimg'], '</i>') !== false) {
					// we set this pre-set icon as already used for excluding it from the list
					array_push($exclude_html, $v['textimg']);
				}
			}
		}
		$preset_icons = VikBookingIcons::loadCharacteristicsPreset($exclude_html);
		
		$this->row = $row;
		$this->allrooms = $allrooms;
		$this->preset_icons = $preset_icons;
		
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
			JToolBarHelper::title(JText::translate('VBMAINCARATTITLEEDIT'), 'vikbooking');
			if (JFactory::getUser()->authorise('core.edit', 'com_vikbooking')) {
				JToolBarHelper::save( 'updatecarat', JText::translate('VBSAVE'));
				JToolBarHelper::spacer();
			}
			JToolBarHelper::cancel( 'cancelcarat', JText::translate('VBANNULLA'));
			JToolBarHelper::spacer();
		} else {
			//new
			JToolBarHelper::title(JText::translate('VBMAINCARATTITLENEW'), 'vikbooking');
			if (JFactory::getUser()->authorise('core.create', 'com_vikbooking')) {
				JToolBarHelper::save( 'createcarat', JText::translate('VBSAVE'));
				JToolBarHelper::spacer();
			}
			JToolBarHelper::cancel( 'cancelcarat', JText::translate('VBANNULLA'));
			JToolBarHelper::spacer();
		}
	}

}
