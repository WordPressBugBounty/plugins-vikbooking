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

class VikbookingViewRoomslist extends JViewVikBooking {
	function display($tpl = null) {
		VikBooking::prepareViewContent();
		$dbo = JFactory::getDbo();
		$vbo_tn = VikBooking::getTranslator();
		$pcategory_id = VikRequest::getInt('category_id', '', 'request');
		$psortby = VikRequest::getString('sortby', '', 'request');
		$psortby = !in_array($psortby, array('price', 'name', 'id', 'random')) ? 'price' : $psortby;
		$psorttype = VikRequest::getString('sorttype', '', 'request');
		$psorttype = $psorttype == 'desc' ? 'DESC' : 'ASC';
		$preslim = VikRequest::getInt('reslim', '', 'request');
		$preslim = empty($preslim) || $preslim < 1 ? 20 : $preslim;
		$category = "";
		if ($pcategory_id > 0) {
			$q = "SELECT * FROM `#__vikbooking_categories` WHERE `id`='".$pcategory_id."';";
			$dbo->setQuery($q);
			$dbo->execute();
			if($dbo->getNumRows() == 1) {
				$category = $dbo->loadAssocList();
				$category = $category[0];
				$vbo_tn->translateContents($category, '#__vikbooking_categories');
			}
		}
		$ordbyclause = '';
		if ($psortby == 'name') {
			$ordbyclause = ' ORDER BY `#__vikbooking_rooms`.`name` '.$psorttype;
		} elseif ($psortby == 'id') {
			$ordbyclause = ' ORDER BY `#__vikbooking_rooms`.`id` '.$psorttype;
		}
		if (is_array($category)) {
			$q = "SELECT * FROM `#__vikbooking_rooms` WHERE `avail`='1' AND (`idcat`='".$category['id'].";' OR `idcat` LIKE '".$category['id'].";%' OR `idcat` LIKE '%;".$category['id'].";%' OR `idcat` LIKE '%;".$category['id'].";')".$ordbyclause.";";
		} else {
			$q = "SELECT * FROM `#__vikbooking_rooms` WHERE `avail`='1'".$ordbyclause.";";
		}
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows() > 0) {
			$rooms = $dbo->loadAssocList();
			$vbo_tn->translateContents($rooms, '#__vikbooking_rooms');
			if ($vbo_tn->default_lang != $vbo_tn->current_lang && $psortby == 'name') {
				//VBO 1.9 - resort rooms by name after the translations have been applied
				$resort_map = array();
				foreach ($rooms as $k => $v) {
					$resort_map[$k] = $v['name'];
				}
				asort($resort_map);
				$resorted = array();
				foreach ($resort_map as $k => $v) {
					$resorted[$k] = $rooms[$k];
				}
				$rooms = $resorted;
				unset($resorted);
			}
			/**
			 * We calculate the default timestamps for check-in of today and check-out for tomorrow.
			 * Useful to apply the today's rate by default for one night of stay.
			 * 
			 * @since 	1.13.5
			 */
			$vbo_df = VikBooking::getDateFormat();
			$vbo_df = $vbo_df == "%d/%m/%Y" ? 'd/m/Y' : ($vbo_df == "%m/%d/%Y" ? 'm/d/Y' : 'Y/m/d');
			$checkinh = 0;
			$checkinm = 0;
			$checkouth = 0;
			$checkoutm = 0;
			$timeopst = VikBooking::getTimeOpenStore();
			if (is_array($timeopst)) {
				$opent = VikBooking::getHoursMinutes($timeopst[0]);
				$closet = VikBooking::getHoursMinutes($timeopst[1]);
				$checkinh = $opent[0];
				$checkinm = $opent[1];
				$checkouth = $closet[0];
				$checkoutm = $closet[1];
			}
			$checkin_today_ts = VikBooking::getDateTimestamp(date($vbo_df), $checkinh, $checkinm);
			$checkout_tomorrow_ts = VikBooking::getDateTimestamp(date($vbo_df, strtotime('tomorrow')), $checkouth, $checkoutm);
			//
			foreach ($rooms as $k => $c) {
				$custprice = VikBooking::getRoomParam('custprice', $c['params']);
				if (!empty($custprice)) {
					$rooms[$k]['cost'] = floatval($custprice);
				} else {
					$rooms[$k]['cost'] = 0;
					$q = "SELECT * FROM `#__vikbooking_dispcost` WHERE `idroom`=".$dbo->quote($c['id'])." ORDER BY `#__vikbooking_dispcost`.`days` ASC, `#__vikbooking_dispcost`.`cost` ASC";
					$dbo->setQuery($q, 0, 1);
					$dbo->execute();
					if ($dbo->getNumRows()) {
						$tar = $dbo->loadAssocList();
						/**
						 * We apply the today's rate by default for one night of stay.
						 * 
						 * @since 	1.13.5
						 */
						$tar = VikBooking::applySeasonsRoom($tar, $checkin_today_ts, $checkout_tomorrow_ts);
						//
						$rooms[$k]['cost'] = $tar[0]['cost'] / ($tar[0]['days'] > 1 ? $tar[0]['days'] : 1);
					}
				}
			}
			if ($psortby == 'random') {
				$keys = array_keys($rooms);
				shuffle($keys);
				$new = array();
				foreach ($keys as $key) {
					$new[$key] = $rooms[$key];
				}
				$rooms = $new;
			} elseif ($psortby == 'price') {
				$rooms = VikBooking::sortRoomPrices($rooms);
				if ($psorttype == 'DESC') {
					$rooms = array_reverse($rooms, true);
				}
			}
			//pagination
			$lim = $preslim; //results limit
			$lim0 = VikRequest::getVar('limitstart', 0, '', 'int');
			jimport('joomla.html.pagination');
			$pageNav = new JPagination(count($rooms), $lim0, $lim);
			$navig = $pageNav->getPagesLinks();
			$this->navig = $navig;
			$rooms = array_slice($rooms, $lim0, $lim, true);
			//
			
			$this->rooms = $rooms;
			$this->category = $category;
			$this->vbo_tn = $vbo_tn;
			//theme
			$theme = VikBooking::getTheme();
			if($theme != 'default') {
				$thdir = VBO_SITE_PATH.DS.'themes'.DS.$theme.DS.'roomslist';
				if(is_dir($thdir)) {
					$this->_setPath('template', $thdir.DS);
				}
			}
			//
			parent::display($tpl);
		}
	}
}
