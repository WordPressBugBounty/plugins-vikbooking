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

class VikBookingViewChoosebusy extends JViewVikBooking {
	
	function display($tpl = null) {
		// Set the toolbar
		$this->addToolBar();

		$reservs = "";
		$navbut = "";
		$mainframe = JFactory::getApplication();
		$pts = VikRequest::getInt('ts', '', 'request');
		$pidroom = VikRequest::getInt('idroom', '', 'request');
		if (empty($pts) || empty($pidroom)) {
			$mainframe->redirect("index.php?option=com_vikbooking&task=rooms");
			exit;
		}
		//ultimo secondo del giorno scelto
		$realcheckin = $pts + 86399;
		//
		$mainframe = JFactory::getApplication();
		$lim = $mainframe->getUserStateFromRequest("com_vikbooking.limit", 'limit', $mainframe->get('list_limit'), 'int');
		$lim0 = VikRequest::getVar('limitstart', 0, '', 'int');
		$dbo = JFactory::getDBO();
		$q = "SELECT COUNT(*) FROM `#__vikbooking_busy` AS `b` WHERE `b`.`idroom`=".$dbo->quote($pidroom)." AND `b`.`checkin`<=".$dbo->quote($realcheckin)." AND `b`.`checkout`>=".$dbo->quote($pts)."";
		$dbo->setQuery($q);
		$dbo->execute();
		$totres = $dbo->loadResult();
		$q = "SELECT SQL_CALC_FOUND_ROWS `b`.`id`,`b`.`idroom`,`b`.`checkin`,`b`.`checkout`,`ob`.`idorder`,`o`.`custdata`,`o`.`ts`,`o`.`closure`,`r`.`name`,`r`.`img`,`r`.`units`,`r`.`params` FROM `#__vikbooking_busy` AS `b`,`#__vikbooking_orders` AS `o`,`#__vikbooking_rooms` AS `r`,`#__vikbooking_ordersbusy` AS `ob` WHERE `b`.`idroom`=".$dbo->quote($pidroom)." AND `b`.`checkin`<=".$dbo->quote($realcheckin)." AND `b`.`checkout`>=".$dbo->quote($pts)." AND `ob`.`idbusy`=`b`.`id` AND `ob`.`idorder`=`o`.`id` AND `r`.`id`=`b`.`idroom` ORDER BY `b`.`checkin` ASC";
		$dbo->setQuery($q, $lim0, $lim);
		$dbo->execute();
		if ($dbo->getNumRows() < 1) {
			$mainframe->redirect("index.php?option=com_vikbooking&task=rooms");
			exit;
		}
		$reservs = $dbo->loadAssocList();
		$dbo->setQuery('SELECT FOUND_ROWS();');
		jimport('joomla.html.pagination');
		$pageNav = new JPagination( $dbo->loadResult(), $lim0, $lim );
		$navbut = "<table align=\"center\"><tr><td>".$pageNav->getListFooter()."</td></tr></table>";
		
		$this->reservs = $reservs;
		$this->totres = $totres;
		$this->pts = $pts;
		$this->lim0 = $lim0;
		$this->navbut = $navbut;
		
		// Display the template
		parent::display($tpl);
	}

	/**
	 * Sets the toolbar
	 */
	protected function addToolBar() {
		$dbo = JFactory::getDBO();
		$pgoto = VikRequest::getString('goto', '', 'request');
		$pts = VikRequest::getInt('ts', '', 'request');
		$pidroom = VikRequest::getInt('idroom', '', 'request');
		$q = "SELECT `name` FROM `#__vikbooking_rooms` WHERE `id`=".$dbo->quote($pidroom).";";
		$dbo->setQuery($q);
		$dbo->execute();
		$cname = $dbo->loadResult();
		JToolBarHelper::title(JText::translate('VBMAINCHOOSEBUSY')." ".$cname.", ".date('Y-M-d', $pts), 'vikbooking');
		JToolBarHelper::cancel( ($pgoto == 'overv' ? 'canceloverv' : 'cancelcalendar'), JText::translate('VBBACK'));
		$pvcm = VikRequest::getInt('vcm', '', 'request');
		if ($pvcm == 1) {
			JToolBarHelper::custom('cancelbusyvcm', 'back', 'back', JText::translate('VBBACKVCM'), false, false);
		}
		JToolBarHelper::spacer();
	}

}
