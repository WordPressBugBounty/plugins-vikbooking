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

class VikBookingViewRatesoverv extends JViewVikBooking
{
	public function display($tpl = null)
	{
		// Set the toolbar
		$this->addToolBar();

		if (!JFactory::getUser()->authorise('core.vbo.pricing', 'com_vikbooking')) {
			VBOHttpDocument::getInstance()->close(403, JText::translate('JERROR_ALERTNOAUTHOR'));
		}

		/**
		 * Check the next festivities periodically
		 * 
		 * @since 	1.12.0 (J) - 1.2.0 (WP)
		 */
		$fests = VikBooking::getFestivitiesInstance();
		if ($fests->shouldCheckFestivities()) {
			$fests->storeNextFestivities();
		}
		$festivities = $fests->loadFestDates();

		$dbo = JFactory::getDbo();
		$mainframe = JFactory::getApplication();
		$session = JFactory::getSession();

		$cid = VikRequest::getVar('cid', array(0));
		$sesscids = $session->get('vbRatesOviewCids', array());
		if (empty($cid[0]) && is_array($sesscids) && count($sesscids)) {
			// load rooms from session only if no room IDs requested
			$cid = $sesscids;
		}
		
		// first room ID
		$roomid = (int)$cid[0];

		if (empty($roomid)) {
			$q = "SELECT `id` FROM `#__vikbooking_rooms` WHERE `avail`=1 ORDER BY `#__vikbooking_rooms`.`name` ASC LIMIT 1";
			$dbo->setQuery($q);
			$roomid = $dbo->loadResult();
			if (!$roomid) {
				$q = "SELECT `id` FROM `#__vikbooking_rooms` ORDER BY `#__vikbooking_rooms`.`name` ASC LIMIT 1";
				$dbo->setQuery($q);
				$roomid = $dbo->loadResult();
			}
		}
		if (empty($roomid)) {
			$mainframe->redirect("index.php?option=com_vikbooking&task=rooms");
			exit;
		}
		// make sure to set at least the first index of cid[]
		$cid[0] = $roomid;
		//
		$q = "SELECT `id`,`name` FROM `#__vikbooking_rooms` ORDER BY `#__vikbooking_rooms`.`name` ASC;";
		$dbo->setQuery($q);
		$all_rooms = $dbo->loadAssocList();

		/**
		 * Load categories to be used for group filter.
		 * 
		 * @since 	1.13 (J) - 1.3.0 (WP)
		 */
		$q = "SELECT `id`,`name` FROM `#__vikbooking_categories` ORDER BY `#__vikbooking_categories`.`name` ASC;";
		$dbo->setQuery($q);
		$categories = $dbo->loadAssocList();

		// load rooms rows for all requested rooms
		$roomrows = [];
		$reqids = [];
		$reqcats = [];
		foreach ($cid as $rid) {
			if (empty($rid)) {
				continue;
			}
			$rid = (int)$rid;
			if ($rid < 0) {
				// category
				array_push($reqcats, ($rid + (abs($rid) * 2)));
			} else {
				// room
				array_push($reqids, $rid);
			}
		}
		if (!count($reqcats) && !count($reqids)) {
			$mainframe->redirect("index.php?option=com_vikbooking&task=rooms");
			exit;
		}

		if (!count($reqcats) && empty($sesscids) && count($reqids) === 1 && count($all_rooms) > 1) {
			// push the first 5 rooms by default
			$max_def_rooms = 5;
			$def_rooms_count = 0;
			$reqids = [];
			foreach ($all_rooms as $aroom) {
				$reqids[] = $aroom['id'];
				$def_rooms_count++;
				if ($def_rooms_count >= $max_def_rooms) {
					break;
				}
			}
		}

		$clauses = [];
		if (count($reqids)) {
			array_push($clauses, "`id` IN (" . implode(', ', $reqids) . ")");
		}
		foreach ($reqcats as $cat_id) {
			array_push($clauses, "(`idcat`='" . $cat_id . ";' OR `idcat` LIKE '" . $cat_id . ";%' OR `idcat` LIKE '%;" . $cat_id . ";%' OR `idcat` LIKE '%;" . $cat_id . ";')");
		}
		$q = "SELECT * FROM `#__vikbooking_rooms` WHERE ".implode(' OR ', $clauses)." ORDER BY `name` ASC;";
		$dbo->setQuery($q);
		$rows = $dbo->loadAssocList();
		if ($rows) {
			foreach ($rows as $row) {
				$roomrows[$row['id']] = $row;
			}
		}
		if (!$roomrows) {
			$mainframe->redirect("index.php?option=com_vikbooking&task=rooms");
			exit;
		}
		if (!$reqids) {
			// in case of just a category filter we need to restore the requested room IDs for later actions
			$reqids = array_keys($roomrows);
		}
		// get all requested and valid room IDs
		$req_room_ids = array_keys($roomrows);
		$session->set('vbRatesOviewCids', $req_room_ids);
		// Restrictions
		$all_restrictions = [];
		foreach ($req_room_ids as $rid) {
			$all_restrictions[(int)$rid] = VikBooking::loadRestrictions(true, array($rid));
		}
		// length of stay pricing overview (only if one single room requested)
		$first_roomrestr = isset($all_restrictions[(int)$roomid]) ? $all_restrictions[(int)$roomid] : array();
		$pnights_cal = VikRequest::getVar('nights_cal', array());
		$pnights_cal = VikBooking::filterNightsSeasonsCal($pnights_cal);
		$room_nights_cal = isset($roomrows[(int)$roomid]) ? explode(',', VikBooking::getRoomParam('seasoncal_nights', $roomrows[(int)$roomid]['params'])) : [];
		$room_nights_cal = VikBooking::filterNightsSeasonsCal($room_nights_cal);
		$seasons_cal = [];
		$seasons_cal_nights = [];
		if ($pnights_cal) {
			$seasons_cal_nights = $pnights_cal;
		} elseif ($room_nights_cal) {
			$seasons_cal_nights = $room_nights_cal;
		} else {
			$q = "SELECT `days` FROM `#__vikbooking_dispcost` WHERE `idroom`=".intval($roomid)." ORDER BY `#__vikbooking_dispcost`.`days` ASC LIMIT 7;";
			$dbo->setQuery($q);
			$nights_vals = $dbo->loadAssocList();
			if ($nights_vals) {
				$nights_got = [];
				foreach ($nights_vals as $night) {
					$nights_got[] = $night['days'];
				}
				$seasons_cal_nights = VikBooking::filterNightsSeasonsCal($nights_got);
			}
		}
		if (count($req_room_ids) > 1) {
			// it's useless to spend server resources to calculate the seasons calendar nights (LOS Pricing Overview) since it won't be displayed when more than 1 room
			$seasons_cal_nights = [];
		}
		if ($seasons_cal_nights) {
			$q = "SELECT `p`.*,`tp`.`name`,`tp`.`attr`,`tp`.`idiva`,`tp`.`breakfast_included`,`tp`.`free_cancellation`,`tp`.`canc_deadline` FROM `#__vikbooking_dispcost` AS `p` LEFT JOIN `#__vikbooking_prices` `tp` ON `p`.`idprice`=`tp`.`id` WHERE `p`.`days` IN (".implode(',', $seasons_cal_nights).") AND `p`.`idroom`=".(int)$roomid." ORDER BY `p`.`days` ASC, `p`.`cost` ASC;";
			$dbo->setQuery($q);
			$tars = $dbo->loadAssocList();
			if ($tars) {
				$arrtar = [];
				foreach ($tars as $tar) {
					$arrtar[$tar['days']][] = $tar;
				}
				$seasons_cal['nights'] = $seasons_cal_nights;
				$seasons_cal['offseason'] = $arrtar;
				$q = "SELECT * FROM `#__vikbooking_seasons` WHERE `idrooms` LIKE '%-".$roomid."-%';";
				$dbo->setQuery($q);
				$seasons = $dbo->loadAssocList();
				if ($seasons) {
					//Restrictions
					$all_seasons = [];
					$curtime = time();
					foreach ($seasons as $sk => $s) {
						if (empty($s['from']) && empty($s['to'])) {
							continue;
						}
						$now_year = !empty($s['year']) ? $s['year'] : date('Y');
						list($sfrom, $sto) = VikBooking::getSeasonRangeTs($s['from'], $s['to'], $now_year);
						if ($sto < $curtime && empty($s['year'])) {
							$now_year += 1;
							list($sfrom, $sto) = VikBooking::getSeasonRangeTs($s['from'], $s['to'], $now_year);
						}
						if ($sto >= $curtime) {
							$s['from_ts'] = $sfrom;
							$s['to_ts'] = $sto;
							$all_seasons[] = $s;
						}
					}
					if (count($all_seasons) > 0) {
						$vbo_df = VikBooking::getDateFormat();
						$vbo_df = $vbo_df == "%d/%m/%Y" ? 'd/m/Y' : ($vbo_df == "%m/%d/%Y" ? 'm/d/Y' : 'Y/m/d');
						$hcheckin = 0;
						$mcheckin = 0;
						$hcheckout = 0;
						$mcheckout = 0;
						$timeopst = VikBooking::getTimeOpenStore();
						if (is_array($timeopst)) {
							$opent = VikBooking::getHoursMinutes($timeopst[0]);
							$closet = VikBooking::getHoursMinutes($timeopst[1]);
							$hcheckin = $opent[0];
							$mcheckin = $opent[1];
							$hcheckout = $closet[0];
							$mcheckout = $closet[1];
						}
						$all_seasons = VikBooking::sortSeasonsRangeTs($all_seasons);
						$seasons_cal['seasons'] = $all_seasons;
						$seasons_cal['season_prices'] = [];
						$seasons_cal['restrictions'] = [];
						//calc price changes for each season and for each num-night
						foreach ($all_seasons as $sk => $s) {
							$checkin_base_ts = $s['from_ts'];
							$is_dst = date('I', $checkin_base_ts);
							foreach ($arrtar as $numnights => $tar) {
								$checkout_base_ts = $s['to_ts'];
								for($i = 1; $i <= $numnights; $i++) {
									$checkout_base_ts += 86400;
									$is_now_dst = date('I', $checkout_base_ts);
									if ($is_dst != $is_now_dst) {
										if ((int)$is_dst == 1) {
											$checkout_base_ts += 3600;
										} else {
											$checkout_base_ts -= 3600;
										}
										$is_dst = $is_now_dst;
									}
								}
								//calc check-in and check-out ts for the two dates
								$first = VikBooking::getDateTimestamp(date($vbo_df, $checkin_base_ts), $hcheckin, $mcheckin);
								$second = VikBooking::getDateTimestamp(date($vbo_df, $checkout_base_ts), $hcheckout, $mcheckout);
								$tar = VikBooking::applySeasonsRoom($tar, $first, $second, $s);
								$seasons_cal['season_prices'][$sk][$numnights] = $tar;
								//Restrictions
								if (count($first_roomrestr)) {
									$season_restr = VikBooking::parseSeasonRestrictions($first, $second, $numnights, $first_roomrestr);
									if (count($season_restr) > 0) {
										$seasons_cal['restrictions'][$sk][$numnights] = $season_restr;
									}
								}
							}
						}
					}
				}
			}
		}
		//calendar rates
		$todayd = getdate();
		$tsstart = mktime(0, 0, 0, $todayd['mon'], $todayd['mday'], $todayd['year']);
		$startdate = VikRequest::getString('startdate', '', 'request');
		if (!empty($startdate)) {
			$startts = VikBooking::getDateTimestamp($startdate, 0, 0);
			if (!empty($startts)) {
				$session->set('vbRatesOviewTs', $startts);
				$tsstart = $startts;
			}
		} else {
			$prevts = $session->get('vbRatesOviewTs', '');
			if (!empty($prevts)) {
				$tsstart = $prevts;
			}
		}
		$roomrates = [];
		// read the rates for the lowest number of nights for each room requested
		foreach ($req_room_ids as $rid) {
			/**
			 * Some types of price may not have a cost for 1 or 2 nights,
			 * so joining by MIN(`days`) may exclude certain types of price.
			 * We need to manually get via PHP all types of price.
			 * Old query below is no longer in use, even though it was
			 * compatible with the SQL strict mode (only_full_group_by).
			 * $q = "SELECT `r`.`id`,`r`.`idroom`,`r`.`days`,`r`.`idprice`,`r`.`cost`,`p`.`name` FROM `#__vikbooking_dispcost` AS `r` INNER JOIN (SELECT MIN(`days`) AS `min_days` FROM `#__vikbooking_dispcost` WHERE `idroom`=".(int)$rid." GROUP BY `idroom`) AS `r2` ON `r`.`days`=`r2`.`min_days` LEFT JOIN `#__vikbooking_prices` `p` ON `p`.`id`=`r`.`idprice` WHERE `r`.`idroom`=".(int)$rid." GROUP BY `r`.`id`,`r`.`idroom`,`r`.`days`,`r`.`idprice`,`r`.`cost`,`p`.`name` ORDER BY `r`.`days` ASC, `r`.`cost` ASC;";
			 * 
			 * @since 	1.10 (J) - 1.0.14 (WP)
			 */
			$q = "SELECT `r`.`id`,`r`.`idroom`,`r`.`days`,`r`.`idprice`,`r`.`cost`,`p`.`name`,`p`.`minlos` FROM `#__vikbooking_dispcost` AS `r` LEFT JOIN `#__vikbooking_prices` `p` ON `p`.`id`=`r`.`idprice` WHERE `r`.`idroom`=".(int)$rid." ORDER BY `r`.`days` ASC, `r`.`cost` ASC LIMIT 50;";
			$dbo->setQuery($q);
			$nowroomrates = $dbo->loadAssocList();
			if ($nowroomrates) {
				$parsed_room_prices = [];
				foreach ($nowroomrates as $rrk => $rrv) {
					if (isset($parsed_room_prices[$rrv['idprice']])) {
						unset($nowroomrates[$rrk]);
						continue;
					}
					$nowroomrates[$rrk]['cost'] = round(($rrv['cost'] / $rrv['days']), 2);
					$nowroomrates[$rrk]['days'] = 1;
					$parsed_room_prices[$rrv['idprice']] = 1;
				}
			}
			$nowroomrates = array_values($nowroomrates);
			// push rates for this room
			$roomrates[(int)$rid] = $nowroomrates;
		}

		// sort room rates by the best rate plan name
		foreach ($roomrates as $rid => $roomrplans) {
			$roomrates[$rid] = VikBooking::sortRatePlans($roomrplans);
		}

		// read all the bookings between these dates for all rooms
		$booked_dates = [];
		$MAX_DAYS = 60;
		$info_start = getdate($tsstart);
		$endts = mktime(23, 59, 59, $info_start['mon'], ($info_start['mday'] + $MAX_DAYS), $info_start['year']);
		$q = "SELECT `b`.*,`ob`.`idorder` FROM `#__vikbooking_busy` AS `b`,`#__vikbooking_ordersbusy` AS `ob` WHERE `b`.`idroom` IN (".implode(', ', $reqids).") AND `b`.`id`=`ob`.`idbusy` AND (`b`.`checkin`>=".$tsstart." OR `b`.`checkout`>=".$tsstart.") AND (`b`.`checkin`<=".$endts." OR `b`.`checkout`<=".$tsstart.");";
		$dbo->setQuery($q);
		$rbusy = $dbo->loadAssocList();
		$ridbusy = [];
		foreach ($rbusy as $rb) {
			if (!isset($ridbusy[$rb['idroom']])) {
				$ridbusy[$rb['idroom']] = [];
			}
			array_push($ridbusy[$rb['idroom']], $rb);
		}
		foreach ($req_room_ids as $rid) {
			$booked_dates[(int)$rid] = isset($ridbusy[(int)$rid]) ? $ridbusy[(int)$rid] : [];
		}

		/**
		 * Load room day notes for the requested dates
		 * 
		 * @since 	1.13.5
		 */
		$rdaynotes = VikBooking::getCriticalDatesInstance()->loadRoomDayNotes(date('Y-m-d', $tsstart), date('Y-m-d', $endts));
		//
		
		$this->all_rooms = $all_rooms;
		$this->categories = $categories;
		$this->reqcats = $reqcats;
		$this->all_restrictions = $all_restrictions;
		$this->roomrows = $roomrows;
		$this->seasons_cal_nights = $seasons_cal_nights;
		$this->seasons_cal = $seasons_cal;
		$this->tsstart = $tsstart;
		$this->roomrates = $roomrates;
		$this->booked_dates = $booked_dates;
		$this->req_room_ids = $req_room_ids;
		$this->firstroom = $roomid;
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
		JToolBarHelper::title(JText::translate('VBMAINRATESOVERVIEWTITLE'), 'vikbooking');
		if (JFactory::getUser()->authorise('core.create', 'com_vikbooking')) {
			JToolBarHelper::addNew('newseason', JText::translate('VBMAINSEASONSNEW'));
			JToolBarHelper::spacer();
			JToolBarHelper::addNew('newrestriction', JText::translate('VBMAINRESTRICTIONNEW'));
			JToolBarHelper::spacer();
		}
		JToolBarHelper::cancel( 'cancel', JText::translate('VBBACK'));
		JToolBarHelper::spacer();
	}
}
