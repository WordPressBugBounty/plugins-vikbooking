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

class VikbookingViewRoomdetails extends JViewVikBooking {
	function display($tpl = null) {
		VikBooking::prepareViewContent();
		$proomid = VikRequest::getString('roomid', '', 'request');
		$dbo = JFactory::getDbo();
		$vbo_tn = VikBooking::getTranslator();
		$q = "SELECT * FROM `#__vikbooking_rooms` WHERE `id`=".$dbo->quote($proomid)." AND `avail`='1';";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows() == 1) {
			$room = $dbo->loadAssocList();
			$vbo_tn->translateContents($room, '#__vikbooking_rooms');

			/**
			 * We calculate the default timestamps for check-in of today and check-out for tomorrow.
			 * Useful to apply the today's rate by default for one night of stay.
			 * 
			 * @since 	1.13.5
			 */
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
			$checkin_today_ts = VikBooking::getDateTimestamp(date($vbo_df), $hcheckin, $mcheckin);
			$checkout_tomorrow_ts = VikBooking::getDateTimestamp(date($vbo_df, strtotime('tomorrow')), $hcheckout, $mcheckout);
			//
			$room[0]['base_cost'] = 0;
			$room[0]['cost'] = 0;
			$q = "SELECT * FROM `#__vikbooking_dispcost` WHERE `idroom`=".$dbo->quote($room[0]['id'])." ORDER BY `#__vikbooking_dispcost`.`days` ASC, `#__vikbooking_dispcost`.`cost` ASC";
			$dbo->setQuery($q, 0, 1);
			$dbo->execute();
			if ($dbo->getNumRows()) {
				$tar = $dbo->loadAssocList();
				$room[0]['base_cost'] = $tar[0]['cost'] / ($tar[0]['days'] > 1 ? $tar[0]['days'] : 1);
				/**
				 * We apply the today's rate by default for one night of stay.
				 * 
				 * @since 	1.13.5
				 */
				$tar = VikBooking::applySeasonsRoom($tar, $checkin_today_ts, $checkout_tomorrow_ts);
				//
				$room[0]['cost'] = $tar[0]['cost'] / ($tar[0]['days'] > 1 ? $tar[0]['days'] : 1);
			}
			//

			$actnow = mktime(0, 0, 0, date('m'), 1, date('Y'));
			$busy = "";
			$q = "SELECT * FROM `#__vikbooking_busy` WHERE `idroom`='".$room[0]['id']."' AND (`checkin`>=".$actnow." OR `checkout`>=".$actnow.");";
			$dbo->setQuery($q);
			$dbo->execute();
			if ($dbo->getNumRows() > 0) {
				$busy = $dbo->loadAssocList();
			}
			//seasons calendar
			$seasons_cal = array();
			$use_seasons_cal = VikBooking::getRoomParam('seasoncal', $room[0]['params']);
			$seasons_cal_nights = explode(',', VikBooking::getRoomParam('seasoncal_nights', $room[0]['params']));
			$seasons_cal_nights = VikBooking::filterNightsSeasonsCal($seasons_cal_nights);
			if (intval($use_seasons_cal) > 0 && count($seasons_cal_nights) > 0) {
				$q = "SELECT * FROM `#__vikbooking_seasons` WHERE `idrooms` LIKE '%-".$room[0]['id']."-%'".($use_seasons_cal == 2 ? " AND `promo`=0" : ($use_seasons_cal == 3 ? " AND `type`=1 AND `promo`=0" : "")).";";
				$dbo->setQuery($q);
				$dbo->execute();
				if ($dbo->getNumRows() > 0) {
					$seasons = $dbo->loadAssocList();
					$vbo_tn->translateContents($seasons, '#__vikbooking_seasons');
					$q = "SELECT `p`.*,`tp`.`name`,`tp`.`attr`,`tp`.`idiva`,`tp`.`breakfast_included`,`tp`.`free_cancellation`,`tp`.`canc_deadline` FROM `#__vikbooking_dispcost` AS `p` LEFT JOIN `#__vikbooking_prices` `tp` ON `p`.`idprice`=`tp`.`id` WHERE `p`.`days` IN (".implode(',', $seasons_cal_nights).") AND `p`.`idroom`=".$room[0]['id']." ORDER BY `p`.`days` ASC, `p`.`cost` ASC;";
					$dbo->setQuery($q);
					$dbo->execute();
					if ($dbo->getNumRows() > 0) {
						$tars = $dbo->loadAssocList();
						$vbo_tn->translateContents($tars, '#__vikbooking_prices', array('id' => 'idprice'));
						$arrtar = array();
						foreach ($tars as $tar) {
							$arrtar[$tar['days']][] = $tar;
						}
						//Restrictions
						$all_restrictions = VikBooking::loadRestrictions(true, array($room[0]['id']));
						$all_seasons = array();
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
							$seasons_cal['nights'] = $seasons_cal_nights;
							$seasons_cal['offseason'] = $arrtar;
							$all_seasons = VikBooking::sortSeasonsRangeTs($all_seasons);
							$seasons_cal['seasons'] = $all_seasons;
							$seasons_cal['season_prices'] = array();
							$seasons_cal['restrictions'] = array();
							//calc price changes for each season and for each num-night
							foreach ($all_seasons as $sk => $s) {
								$checkin_base_ts = $s['from_ts'];
								$is_dst = date('I', $checkin_base_ts);
								foreach ($arrtar as $numnights => $tar) {
									$checkout_base_ts = $s['to_ts'];
									for ($i = 1; $i <= $numnights; $i++) {
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
									if (count($all_restrictions) > 0) {
										$season_restr = VikBooking::parseSeasonRestrictions($first, $second, $numnights, $all_restrictions);
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
			//end seasons calendar
			//promotion min number of nights
			$ppromo = VikRequest::getInt('promo', 0, 'request');
			$promo_season = array();
			if ($ppromo > 0) {
				$q = "SELECT * FROM `#__vikbooking_seasons` WHERE `id`=".(int)$ppromo." AND `promo`=1;";
				$dbo->setQuery($q);
				$dbo->execute();
				if ($dbo->getNumRows() > 0) {
					$promo_season = $dbo->loadAssoc();
					$vbo_tn->translateContents($promo_season, '#__vikbooking_seasons');
				}
			}

			// attempt to load the first two "mandatory" checkbox fields
			$terms_fields = array();
			$q = "SELECT * FROM `#__vikbooking_custfields` WHERE `type`='checkbox' AND `required`=1 ORDER BY `#__vikbooking_custfields`.`ordering` ASC";
			$dbo->setQuery($q, 0, 2);
			$dbo->execute();
			if ($dbo->getNumRows()) {
				$terms_fields = $dbo->loadAssocList();
				$vbo_tn->translateContents($terms_fields, '#__vikbooking_custfields');
			}

			$custptitle = VikBooking::getRoomParam('custptitle', $room[0]['params']);
			$custptitlew = VikBooking::getRoomParam('custptitlew', $room[0]['params']);
			$metakeywords = VikBooking::getRoomParam('metakeywords', $room[0]['params']);
			$metadescription = VikBooking::getRoomParam('metadescription', $room[0]['params']);
			$document = JFactory::getDocument();
			if (!empty($custptitle)) {
				$ctitlewhere = !empty($custptitlew) ? $custptitlew : 'before';
				$set_title = $custptitle.' - '.$document->getTitle();
				if ($ctitlewhere == 'after') {
					$set_title = $document->getTitle().' - '.$custptitle;
				} elseif ($ctitlewhere == 'replace') {
					$set_title = $custptitle;
				}
				$document->setTitle($set_title);
			}
			if (!empty($metakeywords)) {
				$document->setMetaData('keywords', $metakeywords);
			}
			if (!empty($metadescription)) {
				$document->setMetaData('description', $metadescription);
			}
			//OpenGraph Tags
			if (!empty($custptitle)) {
				$document->setMetaData('og:title', $set_title);
			}
			if (!empty($room[0]['img'])) {
				$document->setMetaData('og:image', VBO_SITE_URI.'resources/uploads/'.$room[0]['img']);
			}
			if (!empty($room[0]['smalldesc'])) {
				$document->setMetaData('og:description', strip_tags($room[0]['smalldesc']));
			}
			//
			$this->room = $room[0];
			$this->busy = $busy;
			$this->seasons_cal = $seasons_cal;
			$this->promo_season = $promo_season;
			$this->terms_fields = $terms_fields;
			$this->vbo_tn = $vbo_tn;
			//theme
			$theme = VikBooking::getTheme();
			if ($theme != 'default') {
				$thdir = VBO_SITE_PATH.DS.'themes'.DS.$theme.DS.'roomdetails';
				if (is_dir($thdir)) {
					$this->_setPath('template', $thdir.DS);
				}
			}
			//
			parent::display($tpl);
		} else {
			$mainframe = JFactory::getApplication();
			$mainframe->redirect("index.php?option=com_vikbooking&view=roomslist");
		}
	}
}
