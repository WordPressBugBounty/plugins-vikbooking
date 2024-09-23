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

jimport('joomla.application.component.view');

class VikbookingViewTableaux extends JViewVikBooking
{
	public function display($tpl = null)
	{
		//set noindex instruction for robots
		$document = JFactory::getDocument();
		$document->setMetaData('robots', 'noindex,follow');
		//
		$app 	= JFactory::getApplication();
		$dbo 	= JFactory::getDbo();
		$opobj  = VikBooking::getOperatorInstance();

		$operator = $opobj->getOperatorAccount();
		if ($operator === false) {
			// operator needs to log in
			$pitemid = VikRequest::getInt('Itemid', '', 'request');
			$app->redirect(JRoute::rewrite('index.php?option=com_vikbooking&view=operators'.(!empty($pitemid) ? '&Itemid='.$pitemid : ''), false));
			exit;
		}

		// check permissions for tableaux
		if (!$opobj->checkPermissions($operator, 'tableaux')) {
			// operator is not authorized
			VikError::raiseWarning('', JText::translate('VBONOTAUTHORIZEDRES'));
			$pitemid = VikRequest::getInt('Itemid', '', 'request');
			$app->redirect(JRoute::rewrite('index.php?option=com_vikbooking&view=operators'.(!empty($pitemid) ? '&Itemid='.$pitemid : ''), false));
			exit;
		}

		/**
		 * We allow the "visible days" setting to be negative. In this case
		 * we double it to get the number of past and future visible days.
		 * 
		 * @since 	1.16.9 (J) - 1.6.9 (WP)
		 */
		$past_days    = 0;
		$visible_days = $operator['perms']['days'] ?: 0;
		if ($visible_days < 0) {
			$visible_days = $visible_days + (abs($visible_days) * 2);
			$past_days    = $visible_days;
		}

		// prepare tableaux data
		$tsinfo = getdate();
		$fromts = mktime(0, 0, 0, $tsinfo['mon'], ($tsinfo['mday'] - $past_days), $tsinfo['year']);
		$tots 	= mktime(23, 59, 59, $tsinfo['mon'], ($tsinfo['mday'] + $visible_days), $tsinfo['year']);

		$roomids = array_filter((array) $operator['perms']['rooms']);

		// get all rooms enabled for the operator
		$rooms = [];
		$q = "SELECT `id`,`name`,`units`,`params` FROM `#__vikbooking_rooms`" . ($roomids ? " WHERE `id` IN (" . implode(', ', array_map('intval', $roomids)) . ")" : "") . " ORDER BY `name` ASC;";
		$dbo->setQuery($q);
		$all = $dbo->loadAssocList();	
		foreach ($all as $r) {
			$rooms[$r['id']] = $r;
		}

		if (!$rooms) {
			VikError::raiseWarning('', JText::translate('VBONOTAUTHORIZEDRES').' (#2)');
			$pitemid = VikRequest::getInt('Itemid', '', 'request');
			$app->redirect(JRoute::rewrite('index.php?option=com_vikbooking&view=operators'.(!empty($pitemid) ? '&Itemid='.$pitemid : ''), false));
			exit;
		}

		// get all occupied dates for these rooms
		$rooms_busy = [];
		$q = "SELECT `b`.*,`ob`.`idorder`,`o`.`custdata`,`o`.`status`,`o`.`country`,`oc`.`idcustomer`,`c`.`first_name`,`c`.`last_name`,
			(SELECT GROUP_CONCAT(`or`.`roomindex` SEPARATOR ';') FROM `#__vikbooking_ordersrooms` AS `or` WHERE `or`.`idorder`=`ob`.`idorder`) AS `indexes`, 
			(SELECT GROUP_CONCAT(`or`.`adults` SEPARATOR ';') FROM `#__vikbooking_ordersrooms` AS `or` WHERE `or`.`idorder`=`ob`.`idorder`) AS `adults`, 
			(SELECT GROUP_CONCAT(`or`.`children` SEPARATOR ';') FROM `#__vikbooking_ordersrooms` AS `or` WHERE `or`.`idorder`=`ob`.`idorder`) AS `children`, 
			(SELECT GROUP_CONCAT(`or`.`pets` SEPARATOR ';') FROM `#__vikbooking_ordersrooms` AS `or` WHERE `or`.`idorder`=`ob`.`idorder`) AS `pets`, 
			(SELECT GROUP_CONCAT(`or`.`t_first_name` SEPARATOR ';') FROM `#__vikbooking_ordersrooms` AS `or` WHERE `or`.`idorder`=`ob`.`idorder`) AS `tnames`, 
			(SELECT GROUP_CONCAT(`or`.`t_last_name` SEPARATOR ';') FROM `#__vikbooking_ordersrooms` AS `or` WHERE `or`.`idorder`=`ob`.`idorder`) AS `tlnames`, 
			(SELECT GROUP_CONCAT(`or`.`optionals` SEPARATOR '__') FROM `#__vikbooking_ordersrooms` AS `or` WHERE `or`.`idorder`=`ob`.`idorder`) AS `roptions`, 
			(SELECT GROUP_CONCAT(`or`.`extracosts` SEPARATOR '__') FROM `#__vikbooking_ordersrooms` AS `or` WHERE `or`.`idorder`=`ob`.`idorder`) AS `rextras`, 
			(SELECT GROUP_CONCAT(`or`.`idtar` SEPARATOR '__') FROM `#__vikbooking_ordersrooms` AS `or` WHERE `or`.`idorder`=`ob`.`idorder`) AS `ridtars`, 
			(SELECT GROUP_CONCAT(`or`.`otarplan` SEPARATOR '__') FROM `#__vikbooking_ordersrooms` AS `or` WHERE `or`.`idorder`=`ob`.`idorder`) AS `rotarplans` 
			FROM `#__vikbooking_busy` AS `b` 
			LEFT JOIN `#__vikbooking_ordersbusy` AS `ob` ON `b`.`id`=`ob`.`idbusy` 
			LEFT JOIN `#__vikbooking_orders` AS `o` ON `ob`.`idorder`=`o`.`id` 
			LEFT JOIN `#__vikbooking_customers_orders` AS `oc` ON `ob`.`idorder`=`oc`.`idorder` 
			LEFT JOIN `#__vikbooking_customers` AS `c` ON `oc`.`idcustomer`=`c`.`id` 
			WHERE `b`.`idroom` IN (" . implode(', ', array_keys($rooms)) . ") AND (`b`.`checkin`>=" . $fromts . " OR `b`.`checkout`>=" . $fromts . ") AND (`b`.`checkin`<=" . $tots . " OR `b`.`checkout`<=" . $fromts . ") AND `o`.`status`='confirmed' AND `o`.`closure`=0 
			ORDER BY `b`.`checkin` ASC, `ob`.`idorder` ASC;";
		$dbo->setQuery($q);
		$busy = $dbo->loadAssocList();

		foreach ($busy as $b) {
			if (!isset($rooms_busy[$b['idroom']])) {
				$rooms_busy[$b['idroom']] = [];
			}
			array_push($rooms_busy[$b['idroom']], $b);
		}

		/**
		 * Check the next festivities periodically
		 * 
		 * @since 	1.13.5
		 */
		$fests = VikBooking::getFestivitiesInstance();
		if ($fests->shouldCheckFestivities()) {
			$fests->storeNextFestivities();
		}
		$festivities = $fests->loadFestDates(date('Y-m-d', $fromts), date('Y-m-d', $tots));

		/**
		 * Load room day notes from first month
		 * 
		 * @since 	1.13.5
		 */
		$rdaynotes = VikBooking::getCriticalDatesInstance()->loadRoomDayNotes(date('Y-m-d', $fromts), date('Y-m-d', $tots));

		// load all options
		$all_options = [];
		$q = "SELECT * FROM `#__vikbooking_optionals` WHERE `forcesel`=0 AND `is_citytax`=0 AND `is_fee`=0;";
		$dbo->setQuery($q);
		$records = $dbo->loadAssocList();

		foreach ($records as $opt) {
			$all_options[$opt['id']] = $opt;
		}

		$this->operator = $operator;
		$this->rooms = $rooms;
		$this->rooms_busy = $rooms_busy;
		$this->fromts = $fromts;
		$this->tots = $tots;
		$this->festivities = $festivities;
		$this->rdaynotes = $rdaynotes;
		$this->all_options = $all_options;
		$this->visible_days = $visible_days;
		$this->past_days = $past_days;

		parent::display($tpl);
	}

	/**
	 * Gets the rate plan name from the given tariff ID.
	 * 
	 * @param 	int 	$idtar 	the tariff id.
	 * 
	 * @return 	null|string 	the rate plan name or null.
	 */
	public function getRatePlanFromTariff($idtar)
	{
		$idtar = (int)$idtar;
		if (empty($idtar)) {
			return null;
		}

		$dbo = JFactory::getDbo();

		$q = "SELECT `d`.`idprice`, `p`.`name` 
			FROM `#__vikbooking_dispcost` AS `d` 
			LEFT JOIN `#__vikbooking_prices` AS `p` ON `d`.`idprice`=`p`.`id` 
			WHERE `d`.`id`={$idtar} AND `p`.`name` IS NOT NULL";
		$dbo->setQuery($q, 0, 1);
		$record = $dbo->loadAssoc();

		if (!$record) {
			return null;
		}

		return $record['name'];
	}
}
