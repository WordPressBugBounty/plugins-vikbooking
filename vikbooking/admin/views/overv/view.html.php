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

class VikBookingViewOverv extends JViewVikBooking
{
	public function display($tpl = null)
	{
		// Set the toolbar
		$this->addToolBar();

		if (!JFactory::getUser()->authorise('core.vbo.availability', 'com_vikbooking')) {
			VBOHttpDocument::getInstance()->close(403, JText::translate('JERROR_ALERTNOAUTHOR'));
		}

		$dbo = JFactory::getDbo();
		$app = JFactory::getApplication();

		if (file_exists(VCM_ADMIN_PATH.DIRECTORY_SEPARATOR.'assets'.DIRECTORY_SEPARATOR.'css'.DIRECTORY_SEPARATOR.'vcm-channels.css')) {
			$document = JFactory::getDocument();
			$document->addStyleSheet(VCM_ADMIN_URI.'assets/css/vcm-channels.css');
		}
		$session = JFactory::getSession();
		$cookie = JFactory::getApplication()->input->cookie;
		$pmonth = VikRequest::getString('month', '', 'request');
		$pmnum = VikRequest::getInt('mnum', '', 'request');
		$cmnum = $cookie->get('vbOvwMnum', '', 'string');
		$punits_show_type = VikRequest::getString('units_show_type', '', 'request');
		// category filter
		$pcategory_id = VikRequest::getString('category_id', '', 'request');
		$scategory_id = $session->get('vbOvwCatid', 0);
		$pcategory_id = !strlen($pcategory_id) && !empty($scategory_id) ? $scategory_id : $pcategory_id;
		$session->set('vbOvwCatid', $pcategory_id);
		//
		
		$pbmode = VikRequest::getString('bmode', '', 'request');
		if (!empty($pbmode) && ($pbmode == 'classic' || $pbmode == 'tags')) {
			VikRequest::setCookie('vbTagsMode', $pbmode, (time() + (86400 * 365)), '/');
			$session->set('vbTagsMode', $pbmode);
		} else {
			$cbmode = $cookie->get('vbTagsMode', '', 'string');
			$pbmode = (!empty($cbmode) && ($cbmode == 'classic' || $cbmode == 'tags') ? $cbmode : 'classic');
			VikRequest::setCookie('vbTagsMode', $pbmode, (time() + (86400 * 365)), '/');
			$session->set('vbTagsMode', $pbmode);
		}	
		
		if (!empty($punits_show_type)) {
			$session->set('vbUnitsShowType', $punits_show_type);
		}
		if (empty($pmonth)) {
			$sess_month = $session->get('vbOverviewMonth', '');
			if (!empty($sess_month)) {
				$pmonth = $sess_month;
			}
		}
		if (intval($cmnum) > 0 && empty($pmnum)) {
			$pmnum = $cmnum;
		}
		if ($pmnum > 0) {
			VikRequest::setCookie('vbOvwMnum', $pmnum, (time() + (86400 * 365)), '/');
			$session->set('vbOvwMnum', $pmnum);
		} else {
			$smnum = $session->get('vbOvwMnum', '1');
			$pmnum = intval($smnum) > 0 ? $smnum : 1;
		}

		// remove expired locked records
		$q = "DELETE FROM `#__vikbooking_tmplock` WHERE `until`<" . time() . ";";
		$dbo->setQuery($q);
		$dbo->execute();

		$q = "SELECT `checkin` FROM `#__vikbooking_busy` ORDER BY `checkin` ASC LIMIT 1;";
		$dbo->setQuery($q);
		$oldest_checkin = $dbo->loadResult();
		if (!$oldest_checkin) {
			$oldest_checkin = 0;
		}

		$q = "SELECT `checkout` FROM `#__vikbooking_busy` ORDER BY `checkout` DESC LIMIT 1;";
		$dbo->setQuery($q);
		$furthest_checkout = $dbo->loadResult();
		if (!$furthest_checkout) {
			$furthest_checkout = 0;
		}

		if (!empty($pmonth)) {
			$session->set('vbOverviewMonth', $pmonth);
			$tsstart = $pmonth;
		} else {
			$oggid = getdate();
			$tsstart = mktime(0, 0, 0, $oggid['mon'], 1, $oggid['year']);
		}
		$oggid = getdate($tsstart);
		$tsend = mktime(0, 0, 0, ($oggid['mon'] + $pmnum), 1, $oggid['year']);
		$today = getdate();
		$firstmonth = mktime(0, 0, 0, $today['mon'], 1, $today['year']);
		$wmonthsel = "<select name=\"month\" onchange=\"document.vboverview.submit();\">\n";
		if ($oldest_checkin) {
			$oldest_date = getdate($oldest_checkin);
			$oldest_month = mktime(0, 0, 0, $oldest_date['mon'], 1, $oldest_date['year']);
			if ($oldest_month < $firstmonth) {
				while ($oldest_month < $firstmonth) {
					$wmonthsel .= "<option value=\"".$oldest_month."\"".($oldest_month == $tsstart ? " selected=\"selected\"" : "").">".VikBooking::sayMonth($oldest_date['mon'])." ".$oldest_date['year']."</option>\n";
					if ($oldest_date['mon']==12) {
						$nextmon = 1;
						$year = $oldest_date['year'] + 1;
					} else {
						$nextmon = $oldest_date['mon'] + 1;
						$year = $oldest_date['year'];
					}
					$oldest_month = mktime(0, 0, 0, $nextmon, 1, $year);
					$oldest_date = getdate($oldest_month);
				}
			}
		}
		$wmonthsel .= "<option value=\"".$firstmonth."\"".($firstmonth == $tsstart ? " selected=\"selected\"" : "").">".VikBooking::sayMonth($today['mon'])." ".$today['year']."</option>\n";
		$futuremonths = 12;
		if ($furthest_checkout) {
			$furthest_date = getdate($furthest_checkout);
			$furthest_month = mktime(0, 0, 0, $furthest_date['mon'], 1, $furthest_date['year']);
			if ($furthest_month > $firstmonth) {
				$monthsdiff = floor(($furthest_month - $firstmonth) / (86400 * 30));
				$futuremonths = $monthsdiff > $futuremonths ? $monthsdiff : $futuremonths;
			}
		}
		for ($i = 1; $i <= $futuremonths; $i++) {
			$newts = getdate($firstmonth);
			if ($newts['mon'] == 12) {
				$nextmon = 1;
				$year = $newts['year'] + 1;
			} else {
				$nextmon = $newts['mon'] + 1;
				$year = $newts['year'];
			}
			$firstmonth = mktime(0, 0, 0, $nextmon, 1, $year);
			$newts = getdate($firstmonth);
			$wmonthsel .= "<option value=\"".$firstmonth."\"".($firstmonth==$tsstart ? " selected=\"selected\"" : "").">".VikBooking::sayMonth($newts['mon'])." ".$newts['year']."</option>\n";
		}
		$wmonthsel .= "</select>\n";
		
		$lim = $app->getUserStateFromRequest("com_vikbooking.limit", 'limit', $app->get('list_limit'), 'int');
		$lim0 = VikRequest::getVar('limitstart', 0, '', 'int');
		/**
		 * Filter by category.
		 * 
		 * @since 	1.13
		 */
		$catfilter = !empty($pcategory_id) ? "(`r`.`idcat`='" . $pcategory_id . ";' OR `r`.`idcat` LIKE '" . $pcategory_id . ";%' OR `r`.`idcat` LIKE '%;" . $pcategory_id . ";%' OR `r`.`idcat` LIKE '%;" . $pcategory_id . ";')" : "";
		//
		$q = "SELECT SQL_CALC_FOUND_ROWS `r`.* FROM `#__vikbooking_rooms` AS `r`" . (!empty($catfilter) ? " WHERE " . $catfilter : '') . " ORDER BY `r`.`name` ASC";
		$dbo->setQuery($q, $lim0, $lim);
		$rows = $dbo->loadAssocList();
		if (!$rows) {
			if ($session->get('vbOvwCatid', '')) {
				// unset empty category with no rooms
				$session->set('vbOvwCatid', 0);
			}
			VikError::raiseWarning('', JText::translate('VBOVERVIEWNOROOMS'));
			$app->redirect("index.php?option=com_vikbooking");
			$app->close();
		}
		
		$dbo->setQuery('SELECT FOUND_ROWS();');
		jimport('joomla.html.pagination');
		$pageNav = new JPagination( $dbo->loadResult(), $lim0, $lim );
		$navbut = "<table align=\"center\"><tr><td>".$pageNav->getListFooter()."</td></tr></table>";

		/**
		 * Filter by category.
		 * 
		 * @since 	1.13 (J) - 1.3 (WP)
		 */
		$categories = [];
		$q = "SELECT `id`,`name` FROM `#__vikbooking_categories` ORDER BY `name` ASC;";
		$dbo->setQuery($q);
		$allcats = $dbo->loadAssocList();
		if ($allcats) {
			foreach ($allcats as $cat) {
				$categories[$cat['id']] = $cat['name'];
			}
		}

		// load records with basic information.
		$arrbusy = [
			'tmplock' => [],
		];
		$actnow = time();
		foreach ($rows as $r) {
			$q = "SELECT `b`.*,`ob`.`idorder`,`o`.`custdata`,`o`.`days`,`o`.`roomsnum`,`o`.`idorderota`,`o`.`channel`,`o`.`closure`
				FROM `#__vikbooking_busy` AS `b` 
				LEFT JOIN `#__vikbooking_ordersbusy` AS `ob` ON `b`.`id`=`ob`.`idbusy` 
				LEFT JOIN `#__vikbooking_orders` AS `o` ON `ob`.`idorder`=`o`.`id` 
				WHERE `b`.`idroom`=" . (int)$r['id'] . " AND (`b`.`checkin`>=" . $tsstart . " OR `b`.`checkout`>=" . $tsstart . ") AND (`b`.`checkin`<=" . $tsend . " OR `b`.`checkout`<=" . $tsstart . ");";
			$dbo->setQuery($q);
			$occ_rows = $dbo->loadAssocList();
			$arrbusy[$r['id']] = $occ_rows ? $occ_rows : [];

			// locked (stand-by) records
			$q = "SELECT `l`.*,`or`.`idroom` FROM `#__vikbooking_tmplock` AS `l` LEFT JOIN `#__vikbooking_ordersrooms` AS `or` ON `l`.`idorder`=`or`.`idorder` WHERE `or`.`idroom`='".$r['id']."' AND (`l`.`checkin`>=".$tsstart." OR `l`.`checkout`>=".$tsstart.") AND (`l`.`checkin`<=".$tsend." OR `l`.`checkout`<=".$tsstart.") AND `l`.`until`>=".$actnow.";";
			$dbo->setQuery($q);
			$lock_rows = $dbo->loadAssocList();
			if ($lock_rows) {
				$arrbusy['tmplock'][$r['id']] = $lock_rows;
			}
		}

		// collect additional information for each booking
		$extra_info_map = [];
		foreach ($arrbusy as $rid => $roomres) {
			foreach ($roomres as $k => $res) {
				if (empty($res['idorder'])) {
					continue;
				}
				if (isset($extra_info_map[$res['idorder']])) {
					// merge existing extra information
					$arrbusy[$rid][$k] = array_merge($res, $extra_info_map[$res['idorder']]);
					continue;
				}
				// get booking extra information
				$q = "SELECT `oc`.`idcustomer`,`c`.`first_name`,`c`.`last_name`,`c`.`pic`
					FROM `#__vikbooking_customers_orders` AS `oc`
					LEFT JOIN `#__vikbooking_customers` AS `c` ON `oc`.`idcustomer`=`c`.`id`
					WHERE `oc`.`idorder`={$res['idorder']}";
				$dbo->setQuery($q, 0, 1);
				$extra_info = $dbo->loadAssoc();
				$extra_info = $extra_info ? $extra_info : [];
				// merge and map booking extra information
				$arrbusy[$rid][$k] = array_merge($res, $extra_info);
				$extra_info_map[$res['idorder']] = $extra_info;
			}
		}

		/**
		 * Check the next festivities periodically
		 * 
		 * @since 	1.12.0 (J) - 1.2 (WP)
		 */
		$fests = VikBooking::getFestivitiesInstance();
		if ($fests->shouldCheckFestivities()) {
			$fests->storeNextFestivities();
		}
		$festivities = $fests->loadFestDates(date('Y-m-d', $tsstart), date('Y-m-d', $tsend));

		/**
		 * Load room day notes from first month
		 * 
		 * @since 	1.13.5 (J) - 1.3 (WP)
		 */
		$rdaynotes = VikBooking::getCriticalDatesInstance()->loadRoomDayNotes(date('Y-m-d', $tsstart), date('Y-m-d', $tsend));
		//
		
		$this->rows = $rows;
		$this->arrbusy = $arrbusy;
		$this->wmonthsel = $wmonthsel;
		$this->tsstart = $tsstart;
		$this->festivities = $festivities;
		$this->rdaynotes = $rdaynotes;
		$this->lim0 = $lim0;
		$this->navbut = $navbut;
		$this->categories = $categories;
		
		// Display the template
		parent::display($tpl);
	}

	/**
	 * Sets the toolbar
	 */
	protected function addToolBar()
	{
		JToolBarHelper::title(JText::translate('VBMAINOVERVIEWTITLE'), 'vikbooking');
		JToolBarHelper::cancel( 'canceledorder', JText::translate('VBBACK'));
		JToolBarHelper::spacer();
	}
}
