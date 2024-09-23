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

class VikBookingViewTableaux extends JViewVikBooking
{
	public function display($tpl = null)
	{
		// Set the toolbar
		$this->addToolBar();

		$dbo = JFactory::getDbo();
		$session = JFactory::getSession();
		$cookie = JFactory::getApplication()->input->cookie;
		$roomids = VikRequest::getVar('roomids', array(), 'request', 'int');
		$month = VikRequest::getInt('month', 0, 'request');
		$fromdate = VikRequest::getString('tbxfromdate', '', 'request');
		$todate = VikRequest::getString('tbxtodate', '', 'request');
		$sessfromts = $session->get('vbTbxFrom', '');
		$sesstots = $session->get('vbTbxTo', '');
		// view mode (classic or tag, taken from overv View)
		$cbmode = $cookie->get('vbTagsMode', $session->get('vbTagsMode', ''), 'string');
		$pbmode = !empty($cbmode) && ($cbmode == 'classic' || $cbmode == 'tags') ? $cbmode : 'classic';
		VikRequest::setCookie('vbTagsMode', $pbmode, (time() + (86400 * 365)), '/');
		$session->set('vbTagsMode', $pbmode);
		// prepare dates
		$now = empty($month) ? time() : $month;
		$tsinfo = getdate($now);
		$fromts = mktime(0, 0, 0, $tsinfo['mon'], 1, $tsinfo['year']);
		$tots = mktime(23, 59, 59, $tsinfo['mon'], date('t', $now), $tsinfo['year']);
		if (!empty($fromdate) && !empty($todate)) {
			$fromts = VikBooking::getDateTimestamp($fromdate, 0, 0);
			$tots = VikBooking::getDateTimestamp($todate, 23, 59, 59);
		} elseif (!empty($sessfromts) && !empty($sesstots)) {
			$fromts = $sessfromts;
			$tots = $sesstots;
		}
		if (empty($fromts) || empty($tots) || $tots <= $fromts) {
			$fromts = mktime(0, 0, 0, $tsinfo['mon'], 1, $tsinfo['year']);
			$tots = mktime(23, 59, 59, $tsinfo['mon'], date('t', $now), $tsinfo['year']);
		}
		$session->set('vbTbxFrom', $fromts);
		$session->set('vbTbxTo', $tots);

		// make sure the list of rooms does not contain empty values for "all"
		if (count($roomids) && empty($roomids[0])) {
			$roomids = array();
		}

		// check the category filter by pushing the proper room ids
		$reqcats = array();
		$cat_ids_filter = array();
		foreach ($roomids as $k => $rid) {
			if (empty($rid)) {
				continue;
			}
			$rid = (int)$rid;
			if ($rid < 0) {
				// it's a category ID when we get negative values
				array_push($reqcats, ($rid + (abs($rid) * 2)));
				// unset this value as we do not need it there for the query
				unset($roomids[$k]);
			}
		}
		// reset keys in case some were unset
		$roomids = array_values($roomids);
		if (count($reqcats)) {
			// gather all room IDs from the given category IDs
			$clauses = array();
			foreach ($reqcats as $cat_id) {
				array_push($clauses, "(`idcat`='" . $cat_id . ";' OR `idcat` LIKE '" . $cat_id . ";%' OR `idcat` LIKE '%;" . $cat_id . ";%' OR `idcat` LIKE '%;" . $cat_id . ";')");
			}
			$q = "SELECT `id`,`name`,`units`,`params` FROM `#__vikbooking_rooms` WHERE " . implode(' OR ', $clauses) . " ORDER BY `name` ASC;";
			$dbo->setQuery($q);
			$catrooms = $dbo->loadAssocList();
			if ($catrooms) {
				// push rooms gathered from category ID filter, if not already in filter
				foreach ($catrooms as $catroom) {
					if (in_array((string)$catroom['id'], $roomids) || in_array((int)$catroom['id'], $roomids)) {
						continue;
					}
					array_push($roomids, $catroom['id']);
				}
			}
		}
		//

		// get min and max dates
		$mindate = 0;
		$maxdate = 0;
		$q = "SELECT `checkin` FROM `#__vikbooking_orders` WHERE `status`='confirmed' ORDER BY `checkin` ASC LIMIT 1;";
		$dbo->setQuery($q);
		$mindate = $dbo->loadResult();
		if (!$mindate) {
			$mindate = 0;
		}
		$q = "SELECT `checkout` FROM `#__vikbooking_orders` WHERE `status`='confirmed' ORDER BY `checkout` DESC LIMIT 1;";
		$dbo->setQuery($q);
		$maxdate = $dbo->loadResult();
		if (!$maxdate) {
			$maxdate = 0;
		}

		// build months array
		$months = array();
		$months_labels = array(
			JText::translate('VBMONTHONE'),
			JText::translate('VBMONTHTWO'),
			JText::translate('VBMONTHTHREE'),
			JText::translate('VBMONTHFOUR'),
			JText::translate('VBMONTHFIVE'),
			JText::translate('VBMONTHSIX'),
			JText::translate('VBMONTHSEVEN'),
			JText::translate('VBMONTHEIGHT'),
			JText::translate('VBMONTHNINE'),
			JText::translate('VBMONTHTEN'),
			JText::translate('VBMONTHELEVEN'),
			JText::translate('VBMONTHTWELVE')
		);
		if ($mindate > 0 && $maxdate > 0) {
			// prev months (max 5, if any)
			$minback = mktime(0, 0, 0, date('n', $mindate), 1, date('Y', $mindate));
			$startinfo = getdate(mktime(23, 59, 59, ($tsinfo['mon'] - 1), $tsinfo['mday'], $tsinfo['year']));
			$maxmonths = 5;
			$monthscount = 0;
			while ($startinfo[0] >= $minback && $monthscount < 5) {
				// push prev month
				array_push($months, array(
					'name' => $months_labels[($startinfo['mon'] - 1)] . ' ' . $startinfo['year'],
					'from' => mktime(0, 0, 0, $startinfo['mon'], 1, $startinfo['year']),
					'to'   => mktime(0, 0, 0, $startinfo['mon'], date('t', $startinfo[0]), $startinfo['year']),
				));

				// next prev month
				$startinfo = getdate(mktime(23, 59, 59, ($startinfo['mon'] - 1), $startinfo['mday'], $startinfo['year']));
				$monthscount++;
			}
			// revert the array until now for the past months
			$months = array_reverse($months);
			// push current month
			array_push($months, array(
				'name' => $months_labels[($tsinfo['mon'] - 1)] . ' ' . $tsinfo['year'],
				'from' => mktime(0, 0, 0, $tsinfo['mon'], 1, $tsinfo['year']),
				'to'   => mktime(0, 0, 0, $tsinfo['mon'], date('t', $tsinfo[0]), $tsinfo['year']),
			));
			// future months (max 5, if any)
			$maxnext = mktime(23, 59, 59, date('n', $maxdate), date('t', $maxdate), date('Y', $maxdate));
			$startinfo = getdate(mktime(0, 0, 0, ($tsinfo['mon'] + 1), $tsinfo['mday'], $tsinfo['year']));
			$maxmonths = 5;
			$monthscount = 0;
			while ($startinfo[0] <= $maxnext && $monthscount < 5) {
				// push next month
				array_push($months, array(
					'name' => $months_labels[($startinfo['mon'] - 1)] . ' ' . $startinfo['year'],
					'from' => mktime(0, 0, 0, $startinfo['mon'], 1, $startinfo['year']),
					'to'   => mktime(0, 0, 0, $startinfo['mon'], date('t', $startinfo[0]), $startinfo['year']),
				));

				// next future month
				$startinfo = getdate(mktime(0, 0, 0, ($startinfo['mon'] + 1), $startinfo['mday'], $startinfo['year']));
				$monthscount++;
			}
		}

		// get all rooms from filters
		$rooms = array();
		$q = "SELECT `id`,`name`,`units`,`params` FROM `#__vikbooking_rooms`".($roomids ? " WHERE `id` IN (".implode(', ', $roomids).")" : "")." ORDER BY `name` ASC;";
		$dbo->setQuery($q);
		$all = $dbo->loadAssocList();
		if ($all) {
			foreach ($all as $r) {
				$rooms[$r['id']] = $r;
			}
		}
		if (!$rooms) {
			JFactory::getApplication()->redirect('index.php?option=com_vikbooking');
			exit;
		}
		// all rooms must always be taken to compose the drop down
		$allrooms = array();
		if (!$roomids) {
			$allrooms = $rooms;
		} else {
			$q = "SELECT `id`,`name`,`units` FROM `#__vikbooking_rooms` ORDER BY `name` ASC;";
			$dbo->setQuery($q);
			$all = $dbo->loadAssocList();
			if ($all) {
				foreach ($all as $r) {
					$allrooms[$r['id']] = $r;
				}
			}
		}

		// get all occupied dates for these rooms
		$rooms_busy = array();
		$q = "SELECT `b`.*,`ob`.`idorder`,`o`.`custdata`,`o`.`status`,`o`.`totpaid`,`o`.`roomsnum`,`o`.`total`,`o`.`idorderota`,`o`.`channel`,`o`.`country`,`o`.`colortag`,`oc`.`idcustomer`,`c`.`first_name`,`c`.`last_name`,`c`.`pic`,
			(SELECT GROUP_CONCAT(`or`.`roomindex` SEPARATOR ';') FROM `#__vikbooking_ordersrooms` AS `or` WHERE `or`.`idorder`=`ob`.`idorder`) AS `indexes`,
			(SELECT GROUP_CONCAT(`or`.`idroom` SEPARATOR ';') FROM `#__vikbooking_ordersrooms` AS `or` WHERE `or`.`idorder`=`ob`.`idorder`) AS `roomids` 
			FROM `#__vikbooking_busy` AS `b` 
			LEFT JOIN `#__vikbooking_ordersbusy` AS `ob` ON `b`.`id`=`ob`.`idbusy` 
			LEFT JOIN `#__vikbooking_orders` AS `o` ON `ob`.`idorder`=`o`.`id` 
			LEFT JOIN `#__vikbooking_customers_orders` AS `oc` ON `ob`.`idorder`=`oc`.`idorder` 
			LEFT JOIN `#__vikbooking_customers` AS `c` ON `oc`.`idcustomer`=`c`.`id` 
			WHERE `b`.`idroom` IN (".implode(', ', array_keys($rooms)).") AND (`b`.`checkin`>=".$fromts." OR `b`.`checkout`>=".$fromts.") AND (`b`.`checkin`<=".$tots." OR `b`.`checkout`<=".$fromts.") AND `o`.`status`='confirmed' AND `o`.`closure`=0 
			ORDER BY `b`.`checkin` ASC, `ob`.`idorder` ASC;";
		$dbo->setQuery($q);
		$busy = $dbo->loadAssocList();
		if ($busy) {
			foreach ($busy as $b) {
				if (!isset($rooms_busy[$b['idroom']])) {
					$rooms_busy[$b['idroom']] = array();
				}
				array_push($rooms_busy[$b['idroom']], $b);
			}
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
		//

		/**
		 * Load categories to be used for group filter.
		 * 
		 * @since 	1.13.5
		 */
		$q = "SELECT `id`,`name` FROM `#__vikbooking_categories` ORDER BY `#__vikbooking_categories`.`name` ASC;";
		$dbo->setQuery($q);
		$categories = $dbo->loadAssocList();
		$categories = $categories ? $categories : [];
		
		$this->roomids = $roomids;
		$this->rooms = $rooms;
		$this->allrooms = $allrooms;
		$this->categories = $categories;
		$this->reqcats = $reqcats;
		$this->mindate = $mindate;
		$this->maxdate = $maxdate;
		$this->rooms_busy = $rooms_busy;
		$this->months = $months;
		$this->fromts = $fromts;
		$this->tots = $tots;
		$this->pbmode = $pbmode;
		$this->festivities = $festivities;
		$this->rdaynotes = $rdaynotes;

		// Display the template
		parent::display($tpl);
	}

	/**
	 * Sets the toolbar
	 */
	protected function addToolBar()
	{
		JToolBarHelper::title(JText::translate('VBMAINTABLEAUXTITLE'), 'vikbooking');
		JToolBarHelper::cancel( 'canceldash', JText::translate('VBBACK'));
		JToolBarHelper::spacer();
	}
}
