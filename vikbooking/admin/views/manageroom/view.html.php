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

class VikBookingViewManageroom extends JViewVikBooking
{
	public function display($tpl = null)
	{
		// Set the toolbar
		$this->addToolBar();

		if (!JFactory::getUser()->authorise('core.vbo.rooms', 'com_vikbooking')) {
			VBOHttpDocument::getInstance()->close(403, JText::translate('JERROR_ALERTNOAUTHOR'));
		}

		$cid = VikRequest::getVar('cid', array(0));
		if (!empty($cid[0])) {
			$id = (int)$cid[0];
		}

		$row = array();
		$adultsdiff = "";
		$dbo = JFactory::getDbo();
		if (!empty($cid[0])) {
			$q = "SELECT * FROM `#__vikbooking_rooms` WHERE `id`={$id};";
			$dbo->setQuery($q);
			$dbo->execute();
			if ($dbo->getNumRows() == 1) {
				$row = $dbo->loadAssoc();
			} else {
				VikError::raiseWarning('', 'Room not found.');
				$mainframe = JFactory::getApplication();
				$mainframe->redirect("index.php?option=com_vikbooking&task=rooms");
			}
		}
		
		$q = "SELECT * FROM `#__vikbooking_categories`;";
		$dbo->setQuery($q);
		$dbo->execute();
		$cats = $dbo->getNumRows() > 0 ? $dbo->loadAssocList() : "";
		$q = "SELECT * FROM `#__vikbooking_characteristics`;";
		$dbo->setQuery($q);
		$dbo->execute();
		$carats = $dbo->getNumRows() > 0 ? $dbo->loadAssocList() : "";
		$q = "SELECT * FROM `#__vikbooking_optionals` ORDER BY `#__vikbooking_optionals`.`ordering` ASC;";
		$dbo->setQuery($q);
		$dbo->execute();
		$optionals = $dbo->getNumRows() > 0 ? $dbo->loadAssocList() : "";
		if (!empty($cid[0])) {
			$q = "SELECT * FROM `#__vikbooking_adultsdiff` WHERE `idroom`={$id};";
			$dbo->setQuery($q);
			$dbo->execute();
			$adultsdiff = $dbo->getNumRows() > 0 ? $dbo->loadAssocList() : "";
		}
		
		// rooms map and calendar relations (used also for room-upgrade)
		$rooms_map = array();
		$q = "SELECT `id`, `name` FROM `#__vikbooking_rooms`" . (!empty($cid[0]) ? " WHERE `id`!={$id}" : '') . " ORDER BY `name` ASC;";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows()) {
			$rooms = $dbo->loadAssocList();
			foreach ($rooms as $r) {
				$rooms_map[$r['id']] = $r['name'];
			}
		}
		$cal_xref = array(
			'shared_with' => array(),
			'shared_by' => array(),
		);
		if (!empty($cid[0])) {
			$q = "SELECT * FROM `#__vikbooking_calendars_xref` WHERE `mainroom`={$id} OR `childroom`={$id};";
			$dbo->setQuery($q);
			$dbo->execute();
			if ($dbo->getNumRows()) {
				$xref = $dbo->loadAssocList();
				foreach ($xref as $r) {
					if ((int)$r['mainroom'] == $id) {
						array_push($cal_xref['shared_with'], $r['childroom']);
					} elseif ((int)$r['childroom'] == $id) {
						array_push($cal_xref['shared_by'], $r['mainroom']);
					}
				}
			}
		}
		//
		
		$this->row = $row;
		$this->cats = $cats;
		$this->carats = $carats;
		$this->optionals = $optionals;
		$this->adultsdiff = $adultsdiff;
		$this->rooms_map = $rooms_map;
		$this->cal_xref = $cal_xref;
		
		// Display the template
		parent::display($tpl);
	}

	/**
	 * Sets the toolbar
	 */
	protected function addToolBar()
	{
		$cid = VikRequest::getVar('cid', array(0));
		
		if (!empty($cid[0])) {
			//edit
			JToolBarHelper::title(JText::translate('VBMAINROOMTITLEEDIT'), 'vikbooking');
			if (JFactory::getUser()->authorise('core.edit', 'com_vikbooking')) {
				JToolBarHelper::apply( 'updateroomstay', JText::translate('VBSAVE'));
				JToolBarHelper::save( 'updateroom', JText::translate('VBSAVECLOSE'));
				JToolBarHelper::spacer();
			}
			JToolBarHelper::cancel( 'cancel', JText::translate('VBANNULLA'));
			JToolBarHelper::spacer();
		} else {
			//new
			JToolBarHelper::title(JText::translate('VBMAINROOMTITLENEW'), 'vikbooking');
			if (JFactory::getUser()->authorise('core.create', 'com_vikbooking')) {
				JToolBarHelper::save( 'createroom', JText::translate('VBSAVECLOSE'));
				JToolBarHelper::apply( 'createroomstay', JText::translate('VBSAVE'));
				JToolBarHelper::spacer();
			}
			JToolBarHelper::cancel( 'cancel', JText::translate('VBANNULLA'));
			JToolBarHelper::spacer();
		}
	}
}
