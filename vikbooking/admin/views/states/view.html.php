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

class VikBookingViewStates extends JViewVikBooking
{
	public function display($tpl = null)
	{
		// Set the toolbar
		$this->addToolBar();

		$rows = [];
		$navbut = "";
		$dbo = JFactory::getDbo();
		$app = JFactory::getApplication();
		$session = JFactory::getSession();

		$pidcountry = $app->getUserStateFromRequest("vbo.states.idcountry", 'idcountry', 0, 'int');
		$pstatename = $app->getUserStateFromRequest("vbo.states.statename", 'statename', '', 'string');

		$lim = $app->getUserStateFromRequest("com_vikbooking.limit", 'limit', $app->get('list_limit'), 'int');

		$lim0 = VikRequest::getVar('limitstart', 0, '', 'int');

		$pvborderby = VikRequest::getString('vborderby', '', 'request');
		$pvbordersort = VikRequest::getString('vbordersort', '', 'request');

		$validorderby = [
			'state_name',
			'state_2_code',
			'state_3_code',
			'published',
		];

		$orderby = $session->get('vbShowStatesOrderby', 'state_name');
		$ordersort = $session->get('vbShowStatesOrdersort', 'ASC');
		if (!empty($pvborderby) && in_array($pvborderby, $validorderby)) {
			$orderby = $pvborderby;
			$session->set('vbShowStatesOrderby', $orderby);
			if (!empty($pvbordersort) && in_array($pvbordersort, ['ASC', 'DESC'])) {
				$ordersort = $pvbordersort;
				$session->set('vbShowStatesOrdersort', $ordersort);
			}
		}

		$clauses = [];
		if (!empty($pidcountry)) {
			$clauses[] = "`s`.`id_country`={$pidcountry}";
		}
		if (!empty($pstatename)) {
			$clauses[] = "(`s`.`state_name`=" . $dbo->quote($pstatename) . (strlen($pstatename) == 3 ? " OR `state_3_code`=" . $dbo->quote($pstatename) : "") . (strlen($pstatename) == 2 ? " OR `state_2_code`=" . $dbo->quote($pstatename) : "") . ")";
		}
		$q = "SELECT SQL_CALC_FOUND_ROWS `s`.*, `c`.`country_name` 
			FROM `#__vikbooking_states` AS `s` 
			LEFT JOIN `#__vikbooking_countries` AS `c` ON `s`.`id_country`=`c`.`id`" . (count($clauses) ? " WHERE " . implode(" AND ", $clauses) : "") . " ORDER BY `s`.`{$orderby}` {$ordersort}";
		$dbo->setQuery($q, $lim0, $lim);
		$dbo->execute();

		/**
		 * Call assertListQuery() from the View class to make sure the filters set
		 * do not produce an empty result. This would reset the page in this case.
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
		
		// Display the template
		parent::display($tpl);
	}

	/**
	 * Sets the toolbar
	 */
	protected function addToolBar()
	{
		JToolBarHelper::title(JText::translate('VBMAINSTATESTITLE'), 'vikbooking');
		if (JFactory::getUser()->authorise('core.create', 'com_vikbooking')) {
			JToolBarHelper::addNew('states.add', JText::translate('VBMAINPAYMENTSNEW'));
			JToolBarHelper::spacer();
		}
		if (JFactory::getUser()->authorise('core.edit', 'com_vikbooking')) {
			JToolBarHelper::editList('states.edit', JText::translate('VBMAINPAYMENTSEDIT'));
			JToolBarHelper::spacer();
		}
		if (JFactory::getUser()->authorise('core.delete', 'com_vikbooking')) {
			JToolBarHelper::deleteList(JText::translate('VBDELCONFIRM'), 'states.remove', JText::translate('VBMAINPAYMENTSDEL'));
			JToolBarHelper::spacer();
		}
	}
}
