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

class VikBookingViewPrices extends JViewVikBooking {
	
	function display($tpl = null) {
		// Set the toolbar
		$this->addToolBar();

		$navbut = "";
		$dbo = JFactory::getDbo();
		$mainframe = JFactory::getApplication();

		// wizard
		$wizard = VikRequest::getInt('wizard', 0, 'request');
		$rplanpub1 = VikRequest::getInt('rplanpub1', 0, 'request');
		$rplanpub2 = VikRequest::getInt('rplanpub2', 0, 'request');
		$rplanbk1 = VikRequest::getInt('rplanbk1', 0, 'request');
		$rplanbk2 = VikRequest::getInt('rplanbk2', 0, 'request');
		if ($wizard) {
			if ($rplanpub1) {
				$q = "INSERT INTO `#__vikbooking_prices` (`name`, `idiva`, `breakfast_included`, `free_cancellation`, `minlos`) VALUES (" . $dbo->quote(JText::translate('VBOSTANDARDRATE')) . ", 0,  $rplanbk1, 1, 0);";
				$dbo->setQuery($q);
				$dbo->execute();
			}
			if ($rplanpub2) {
				$q = "INSERT INTO `#__vikbooking_prices` (`name`, `idiva`, `breakfast_included`, `free_cancellation`, `canc_deadline`, `minlos`) VALUES (" . $dbo->quote(JText::translate('VBONONREFRATE')) . ", 0, $rplanbk2, 0, 7, 0);";
				$dbo->setQuery($q);
				$dbo->execute();
			}
			$mainframe->redirect('index.php?option=com_vikbooking&wizard=1');
			exit;
		}
		//
		
		$lim = $mainframe->getUserStateFromRequest("com_vikbooking.limit", 'limit', $mainframe->get('list_limit'), 'int');
		$lim0 = VikRequest::getVar('limitstart', 0, '', 'int');
		$q = "SELECT SQL_CALC_FOUND_ROWS * FROM `#__vikbooking_prices`";
		$dbo->setQuery($q, $lim0, $lim);
		$rows = $dbo->loadAssocList();
		if ($rows) {
			$dbo->setQuery('SELECT FOUND_ROWS();');
			jimport('joomla.html.pagination');
			$pageNav = new JPagination( $dbo->loadResult(), $lim0, $lim );
			$navbut="<table align=\"center\"><tr><td>".$pageNav->getListFooter()."</td></tr></table>";
		}
		
		$this->rows = $rows;
		$this->lim0 = $lim0;
		$this->navbut = $navbut;
		
		// Display the template
		parent::display($tpl);
	}

	/**
	 * Sets the toolbar
	 */
	protected function addToolBar() {
		JToolBarHelper::title(JText::translate('VBMAINPRICETITLE'), 'vikbooking');
		if (JFactory::getUser()->authorise('core.create', 'com_vikbooking')) {
			JToolBarHelper::addNew('newprice', JText::translate('VBMAINPRICENEW'));
			JToolBarHelper::spacer();
		}
		if (JFactory::getUser()->authorise('core.edit', 'com_vikbooking')) {
			JToolBarHelper::editList('editprice', JText::translate('VBMAINPRICEEDIT'));
			JToolBarHelper::spacer();
		}
		if (JFactory::getUser()->authorise('core.delete', 'com_vikbooking')) {
			JToolBarHelper::deleteList(JText::translate('VBDELCONFIRM'), 'removeprice', JText::translate('VBMAINPRICEDEL'));
			JToolBarHelper::spacer();
		}
	}

}
