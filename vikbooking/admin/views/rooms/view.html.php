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

class VikBookingViewRooms extends JViewVikBooking
{
	public function display($tpl = null)
	{
		// Set the toolbar
		$this->addToolBar();

		if (!JFactory::getUser()->authorise('core.vbo.rooms', 'com_vikbooking')) {
			VBOHttpDocument::getInstance()->close(403, JText::translate('JERROR_ALERTNOAUTHOR'));
		}

		$rows = "";
		$navbut = "";
		$mainframe = JFactory::getApplication();
		$pmodtar = VikRequest::getString('modtar', '', 'request');
		//to fix js issues
		$ptarmod = VikRequest::getString('tarmod', '', 'request');
		//
		$proomid = VikRequest::getString('roomid', '', 'request');
		$dbo = JFactory::getDBO();
		if ((!empty($pmodtar) || !empty($ptarmod)) && !empty($proomid)) {
			$q = "SELECT * FROM `#__vikbooking_dispcost` WHERE `idroom`=".$dbo->quote($proomid).";";
			$dbo->setQuery($q);
			$dbo->execute();
			if ($dbo->getNumRows() > 0) {
				$tars = $dbo->loadAssocList();
				foreach ($tars as $tt) {
					$tmpcost = VikRequest::getString('cost'.$tt['id'], '', 'request');
					$tmpattr = VikRequest::getString('attr'.$tt['id'], '', 'request');
					if (strlen($tmpcost)) {
						$q = "UPDATE `#__vikbooking_dispcost` SET `cost`='".$tmpcost."'".(strlen($tmpattr) ? ", `attrdata`=".$dbo->quote($tmpattr)."" : "")." WHERE `id`='".$tt['id']."';";
						$dbo->setQuery($q);
						$dbo->execute();
					}
				}
			}
			$lim0 = VikRequest::getVar('limitstart', 0, '', 'int');
			$mainframe->redirect("index.php?option=com_vikbooking&task=tariffs&cid[]=".$proomid."&limitstart=".$lim0);
			exit;
		}
		$lim = $mainframe->getUserStateFromRequest("com_vikbooking.limit", 'limit', $mainframe->get('list_limit'), 'int');
		$lim0 = VikRequest::getVar('limitstart', 0, '', 'int');
		$session = JFactory::getSession();
		$pvborderby = VikRequest::getString('vborderby', '', 'request');
		$pvbordersort = VikRequest::getString('vbordersort', '', 'request');
		$validorderby = array('name', 'toadult', 'tochild', 'totpeople', 'units');
		$orderby = $session->get('vbViewRoomsOrderby', 'name');
		$ordersort = $session->get('vbViewRoomsOrdersort', 'ASC');
		if (!empty($pvborderby) && in_array($pvborderby, $validorderby)) {
			$orderby = $pvborderby;
			$session->set('vbViewRoomsOrderby', $orderby);
			if (!empty($pvbordersort) && in_array($pvbordersort, array('ASC', 'DESC'))) {
				$ordersort = $pvbordersort;
				$session->set('vbViewRoomsOrdersort', $ordersort);
			}
		}

		// filters
		$prname = $mainframe->getUserStateFromRequest("vbo.rooms.rname", 'rname', '', 'string');
		$pidcat = $mainframe->getUserStateFromRequest("vbo.rooms.idcat", 'idcat', 0, 'int');
		$q = "SELECT `id`, `name` FROM `#__vikbooking_categories` ORDER BY `name` ASC;";
		$dbo->setQuery($q);
		$dbo->execute();
		$allcats = $dbo->getNumRows() ? $dbo->loadAssocList() : array();
		$clauses = array();
		if (!empty($prname)) {
			array_push($clauses, "`r`.`name` LIKE " . $dbo->quote("%{$prname}%"));
		}
		if (!empty($pidcat)) {
			array_push($clauses, "(`r`.`idcat`='" . $pidcat . ";' OR `r`.`idcat` LIKE '" . $pidcat . ";%' OR `r`.`idcat` LIKE '%;" . $pidcat . ";%' OR `r`.`idcat` LIKE '%;" . $pidcat . ";')");
		}
		//
		
		$q = "SELECT SQL_CALC_FOUND_ROWS `r`.*, (SELECT 1 FROM `#__vikbooking_calendars_xref` AS `x` WHERE `x`.`mainroom`=`r`.`id` OR `x`.`childroom`=`r`.`id` LIMIT 1) AS `sharedcals` FROM `#__vikbooking_rooms` AS `r`" . (count($clauses) ? ' WHERE ' . implode(' AND ', $clauses) : '') . " ORDER BY `r`.`".$orderby."` ".$ordersort;
		$dbo->setQuery($q, $lim0, $lim);
		$dbo->execute();

		/**
		 * Call assertListQuery() from the View class to make sure the filters set
		 * do not produce an empty result. This would reset the page in this case.
		 * 
		 * @since 	1.13.0 (J) - 1.3.0 (WP)
		 */
		$this->assertListQuery($lim0, $lim);
		//

		if ($dbo->getNumRows() > 0) {
			$rows = $dbo->loadAssocList();
			$dbo->setQuery('SELECT FOUND_ROWS();');
			jimport('joomla.html.pagination');
			$pageNav = new JPagination( $dbo->loadResult(), $lim0, $lim );
			$navbut = "<table align=\"center\"><tr><td>".$pageNav->getListFooter()."</td></tr></table>";
		}
		
		$this->rows = $rows;
		$this->lim0 = $lim0;
		$this->navbut = $navbut;
		$this->orderby = $orderby;
		$this->ordersort = $ordersort;
		$this->allcats = $allcats;
		
		// Display the template
		parent::display($tpl);
	}

	/**
	 * Sets the toolbar
	 */
	protected function addToolBar()
	{
		JToolBarHelper::title(JText::translate('VBMAINDEAFULTTITLE'), 'vikbooking');
		if (JFactory::getUser()->authorise('core.create', 'com_vikbooking')) {
			JToolBarHelper::addNew('newroom', JText::translate('VBMAINDEFAULTNEW'));
			JToolBarHelper::spacer();
		}
		if (JFactory::getUser()->authorise('core.edit', 'com_vikbooking')) {
			JToolBarHelper::editList('editroom', JText::translate('VBMAINDEFAULTEDITC'));
			JToolBarHelper::spacer();
			JToolBarHelper::editList('tariffs', JText::translate('VBMAINDEFAULTEDITT'));
			JToolBarHelper::spacer();
			JToolBarHelper::custom( 'calendar', 'calendar', 'calendar', JText::translate('VBMAINDEFAULTCAL'), true, false);
			JToolBarHelper::spacer();
		}
		if (JFactory::getUser()->authorise('core.delete', 'com_vikbooking')) {
			JToolBarHelper::deleteList(JText::translate('VBDELCONFIRM'), 'removeroom', JText::translate('VBMAINDEFAULTDEL'));
			JToolBarHelper::spacer();
		}
	}
}
