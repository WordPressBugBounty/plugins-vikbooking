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

class VikbookingViewSearch extends JViewVikBooking
{
	public function display($tpl = null)
	{
		$dbo = JFactory::getDbo();
		if (VikBooking::allowBooking()) {
			$session = JFactory::getSession();
			$mainframe = JFactory::getApplication();
			$vbo_tn = VikBooking::getTranslator();
			$pcheckindate = VikRequest::getString('checkindate', '', 'request');
			$pcheckinm = VikRequest::getString('checkinm', '', 'request');
			$pcheckinh = VikRequest::getString('checkinh', '', 'request');
			$pcheckoutdate = VikRequest::getString('checkoutdate', '', 'request');
			$pcheckoutm = VikRequest::getString('checkoutm', '', 'request');
			$pcheckouth = VikRequest::getString('checkouth', '', 'request');
			$pcategories = VikRequest::getString('categories', '', 'request');
			$proomsnum = VikRequest::getInt('roomsnum', '', 'request');
			$proomsnum = $proomsnum < 1 ? 1 : $proomsnum;
			$showchildren = VikBooking::showChildrenFront();
			$padults = VikRequest::getVar('adults', array());
			$pchildren = VikRequest::getVar('children', array());
			$ppkg_id = VikRequest::getInt('pkg_id', '', 'request');
			$pcategory_ids = VikRequest::getVar('category_ids', array());
			$nowdf = VikBooking::getDateFormat();
			if ($nowdf == "%d/%m/%Y") {
				$df = 'd/m/Y';
			} elseif ($nowdf == "%m/%d/%Y") {
				$df = 'm/d/Y';
			} else {
				$df = 'Y/m/d';
			}
			$timeopst = VikBooking::getTimeOpenStore();
			if (!(strlen($pcheckinh) > 0) && !(strlen($pcheckouth) > 0) && is_array($timeopst)) {
				$opent = VikBooking::getHoursMinutes($timeopst[0]);
				$closet = VikBooking::getHoursMinutes($timeopst[1]);
				$pcheckinh = $opent[0];
				$pcheckinm = $opent[1];
				$pcheckouth = $closet[0];
				$pcheckoutm = $closet[1];
			}
			//vikbooking 1.5 channel manager
			$ch_start_date = VikRequest::getString('start_date', '', 'request');
			$ch_end_date = VikRequest::getString('end_date', '', 'request');
			$ch_num_adults = VikRequest::getInt('num_adults', '', 'request');
			$ch_num_children = VikRequest::getInt('num_children', '', 'request');
			if (!empty($ch_start_date) && !empty($ch_end_date)) {
				if (!empty($ch_num_adults) && $ch_num_adults > 0) {
					$padults = array(0 => $ch_num_adults);
				}
				if (!empty($ch_num_children) && $ch_num_children > 0) {
					$pchildren = array(0 => $ch_num_children);
				}
				if ($ch_start_date_ts = strtotime($ch_start_date)) {
					if ($ch_end_date_ts = strtotime($ch_end_date)) {
						$pcheckindate = date($df, $ch_start_date_ts);
						$pcheckoutdate = date($df, $ch_end_date_ts);
						if (is_array($timeopst)) {
							$opent = VikBooking::getHoursMinutes($timeopst[0]);
							$closet = VikBooking::getHoursMinutes($timeopst[1]);
							$pcheckinh = $opent[0];
							$pcheckinm = $opent[1];
							$pcheckouth = $closet[0];
							$pcheckoutm = $closet[1];
						} else {
							$pcheckinh = 0;
							$pcheckinm = 0;
							$pcheckouth = 0;
							$pcheckoutm = 0;
						}
					}
				}
			}
			//

			/**
			 * To facilitate the search suggestions to drive more bookings in case of low
			 * availability on the requested dates, we grab all rooms by ignoring their
			 * minimum needed occupancy, and by considering only their maximum capacity.
			 * This can make sense because "solo-rates" (for single-occupancy) are, most
			 * of the times, forced by most OTAs. If the "search suggestions" are enabled
			 * then the minimum occupancy of the rooms will be ignored to guarantee the
			 * completion of a reservation by the guest, which would be booking a greater
			 * room. In terms of pricing and availability nothing changes of course.
			 * 
			 * @since 	1.14.3 (J) - 1.4.3 (WP)
			 */
			$booking_suggestion = VikRequest::getInt('suggestion', 0, 'request');
			//

			$arradultsrooms = array();
			$arradultsclause = array();
			$arrpeople = array();
			if (count($padults) > 0) {
				foreach ($padults as $kad => $adu) {
					$roomnumb = $kad + 1;
					if (strlen($adu)) {
						$numadults = intval($adu);
						if ($numadults >= 0) {
							$arradultsrooms[$roomnumb] = $numadults;
							$arrpeople[$roomnumb]['adults'] = $numadults;
							// build capacity requirement clauses
							$room_requirements = array();
							if (!$booking_suggestion) {
								array_push($room_requirements, "`r`.`fromadult`<=" . $numadults);
							}
							array_push($room_requirements, "`r`.`toadult`>=" . $numadults);
							if ($showchildren && !empty($pchildren[$kad]) && intval($pchildren[$kad]) > 0) {
								$numchildren = intval($pchildren[$kad]);
								$arrpeople[$roomnumb]['children'] = $numchildren;
								if (!$booking_suggestion) {
									array_push($room_requirements, "`r`.`fromchild`<=" . $numchildren);
								}
								array_push($room_requirements, "`r`.`tochild`>=" . $numchildren);
							} else {
								$arrpeople[$roomnumb]['children'] = 0;
								//VikBooking 1.4 May Patch: if no children then the room must accept no children
								if ($showchildren && intval($pchildren[$kad]) == 0) {
									array_push($room_requirements, "`r`.`fromchild` = 0");
								}
								//
							}
							array_push($room_requirements, "`r`.`totpeople` >= " . ($arrpeople[$roomnumb]['adults'] + $arrpeople[$roomnumb]['children']));
							if (!$booking_suggestion) {
								array_push($room_requirements, "`r`.`mintotpeople` <= " . ($arrpeople[$roomnumb]['adults'] + $arrpeople[$roomnumb]['children']));
							}
							$strclause = '(' . implode(' AND ', $room_requirements) . ')';
							$arradultsclause[] = $strclause;
						}
					}
				}
			}

			//VBO 1.10 - Modify booking
			$mod_booking = array();
			$skip_busy_ids = array();
			$cur_mod = $session->get('vboModBooking', '');
			if (is_array($cur_mod) && count($cur_mod)) {
				$mod_booking = $cur_mod;
				$skip_busy_ids = VikBooking::loadBookingBusyIds($mod_booking['id']);
			}
			//
			$session->set('vbroomsnum', $proomsnum);
			$session->set('vbarrpeople', $arrpeople);
			if (!empty($pcheckindate) && !empty($pcheckoutdate) && $proomsnum > 0 && count($arradultsrooms) == $proomsnum) {
				if (VikBooking::dateIsValid($pcheckindate) && VikBooking::dateIsValid($pcheckoutdate)) {
					$first = VikBooking::getDateTimestamp($pcheckindate, $pcheckinh, $pcheckinm);
					$second = VikBooking::getDateTimestamp($pcheckoutdate, $pcheckouth, $pcheckoutm);
					$actnow = time();
					$today_bookings = VikBooking::todayBookings();
					if ($today_bookings) {
						$actnow = mktime(0, 0, 0, date('n'), date('j'), date('Y'));
					}
					if ($second > $first && $first >= $actnow) {
						$session->set('vbcheckin', $first);
						$session->set('vbcheckout', $second);
						$secdiff = $second - $first;
						$daysdiff = $secdiff / 86400;
						if (is_int($daysdiff)) {
							if ($daysdiff < 1) {
								$daysdiff = 1;
							}
						} else {
							if ($daysdiff < 1) {
								$daysdiff = 1;
							} else {
								$sum = floor($daysdiff) * 86400;
								$newdiff = $secdiff - $sum;
								$maxhmore = VikBooking::getHoursMoreRb() * 3600;
								if ($maxhmore >= $newdiff) {
									$daysdiff = floor($daysdiff);
								} else {
									$daysdiff = ceil($daysdiff);
								}
							}
						}
						// VBO 1.11 - push data to tracker
						VikBooking::getTracker()->pushDates($first, $second, $daysdiff)->pushParty($arrpeople);
						//
						//Restrictions
						$allrestrictions = VikBooking::loadRestrictions(false);
						$restrictions = VikBooking::globalRestrictions($allrestrictions);
						$restrcheckin = getdate($first);
						$restrcheckout = getdate($second);
						$restrictionsvalid = true;
						$restrictions_affcount = 0;
						$restrictionerrmsg = '';
						if (count($restrictions) > 0) {
							if (array_key_exists($restrcheckin['mon'], $restrictions)) {
								//restriction found for this month, checking:
								$restrictions_affcount++;
								if (strlen((string)$restrictions[$restrcheckin['mon']]['wday'])) {
									$rvalidwdays = array($restrictions[$restrcheckin['mon']]['wday']);
									if (strlen((string)$restrictions[$restrcheckin['mon']]['wdaytwo'])) {
										$rvalidwdays[] = $restrictions[$restrcheckin['mon']]['wdaytwo'];
									}
									if (!in_array($restrcheckin['wday'], $rvalidwdays)) {
										$restrictionsvalid = false;
										$restrictionerrmsg = JText::sprintf('VBRESTRERRWDAYARRIVAL', VikBooking::sayMonth($restrcheckin['mon']), VikBooking::sayWeekDay($restrictions[$restrcheckin['mon']]['wday']).(strlen($restrictions[$restrcheckin['mon']]['wdaytwo']) > 0 ? '/'.VikBooking::sayWeekDay($restrictions[$restrcheckin['mon']]['wdaytwo']) : ''));
									} elseif ($restrictions[$restrcheckin['mon']]['multiplyminlos'] == 1) {
										if (($daysdiff % $restrictions[$restrcheckin['mon']]['minlos']) != 0) {
											$restrictionsvalid = false;
											$restrictionerrmsg = JText::sprintf('VBRESTRERRMULTIPLYMINLOS', VikBooking::sayMonth($restrcheckin['mon']), $restrictions[$restrcheckin['mon']]['minlos']);
										}
									}
									$comborestr = VikBooking::parseJsDrangeWdayCombo($restrictions[$restrcheckin['mon']]);
									if (count($comborestr) > 0) {
										if (array_key_exists($restrcheckin['wday'], $comborestr)) {
											if (!in_array($restrcheckout['wday'], $comborestr[$restrcheckin['wday']])) {
												$restrictionsvalid = false;
												$restrictionerrmsg = JText::sprintf('VBRESTRERRWDAYCOMBO', VikBooking::sayMonth($restrcheckin['mon']), VikBooking::sayWeekDay($comborestr[$restrcheckin['wday']][0]).(count($comborestr[$restrcheckin['wday']]) == 2 ? '/'.VikBooking::sayWeekDay($comborestr[$restrcheckin['wday']][1]) : ''), VikBooking::sayWeekDay($restrcheckin['wday']));
											}
										}
									}
								} elseif (!empty($restrictions[$restrcheckin['mon']]['ctad']) || !empty($restrictions[$restrcheckin['mon']]['ctdd'])) {
									if (!empty($restrictions[$restrcheckin['mon']]['ctad'])) {
										$ctarestrictions = explode(',', $restrictions[$restrcheckin['mon']]['ctad']);
										if (in_array('-'.$restrcheckin['wday'].'-', $ctarestrictions)) {
											$restrictionsvalid = false;
											$restrictionerrmsg = JText::sprintf('VBRESTRERRWDAYCTAMONTH', VikBooking::sayWeekDay($restrcheckin['wday']), VikBooking::sayMonth($restrcheckin['mon']));
										}
									}
									if (!empty($restrictions[$restrcheckin['mon']]['ctdd'])) {
										$ctdrestrictions = explode(',', $restrictions[$restrcheckin['mon']]['ctdd']);
										if (in_array('-'.$restrcheckout['wday'].'-', $ctdrestrictions)) {
											$restrictionsvalid = false;
											$restrictionerrmsg = JText::sprintf('VBRESTRERRWDAYCTDMONTH', VikBooking::sayWeekDay($restrcheckout['wday']), VikBooking::sayMonth($restrcheckin['mon']));
										}
									}
								}
								if (!empty($restrictions[$restrcheckin['mon']]['maxlos']) && $restrictions[$restrcheckin['mon']]['maxlos'] > 0 && $restrictions[$restrcheckin['mon']]['maxlos'] > $restrictions[$restrcheckin['mon']]['minlos']) {
									if ($daysdiff > $restrictions[$restrcheckin['mon']]['maxlos']) {
										$restrictionsvalid = false;
										$restrictionerrmsg = JText::sprintf('VBRESTRERRMAXLOSEXCEEDED', VikBooking::sayMonth($restrcheckin['mon']), $restrictions[$restrcheckin['mon']]['maxlos']);
									}
								}
								if ($daysdiff < $restrictions[$restrcheckin['mon']]['minlos']) {
									$restrictionsvalid = false;
									$restrictionerrmsg = JText::sprintf('VBRESTRERRMINLOSEXCEEDED', VikBooking::sayMonth($restrcheckin['mon']), $restrictions[$restrcheckin['mon']]['minlos']);
								}
							} elseif (array_key_exists('range', $restrictions)) {
								/**
								 * We use this map to know which restriction IDs are okay or not okay with the Min LOS.
								 * The most recent restrictions will have a higher priority over the oldest ones.
								 * 
								 * @since 	1.13.5 (J) - 1.3.6 (WP)
								 */
								$minlos_priority = array(
									'ok'  => array(),
									'nok' => array()
								);
								//
								foreach ($restrictions['range'] as $restr) {
									/**
									 * We should not always add 82799 seconds to the end date of the restriction
									 * because if they only last for one day (like a Saturday), then $restr['dto']
									 * will be already set to the time 23:59:59.
									 * 
									 * @since 	1.13 (J) - 1.3.0 (WP)
									 */
									$end_operator = date('Y-m-d', $restr['dfrom']) != date('Y-m-d', $restr['dto']) ? 82799 : 0;
									//
									if ($restr['dfrom'] <= $restrcheckin[0] && ($restr['dto'] + $end_operator) >= $restrcheckin[0]) {
										// restriction found for this date range based on arrival date, check if compliant
										$restrictions_affcount++;
										if (strlen((string)$restr['wday'])) {
											$rvalidwdays = array($restr['wday']);
											if (strlen((string)$restr['wdaytwo'])) {
												$rvalidwdays[] = $restr['wdaytwo'];
											}
											if (!in_array($restrcheckin['wday'], $rvalidwdays)) {
												$restrictionsvalid = false;
												$restrictionerrmsg = JText::sprintf('VBRESTRERRWDAYARRIVALRANGE', VikBooking::sayWeekDay($restr['wday']).(strlen((string)$restr['wdaytwo']) ? '/'.VikBooking::sayWeekDay($restr['wdaytwo']) : ''));
											} elseif ($restr['multiplyminlos'] == 1) {
												if (($daysdiff % $restr['minlos']) != 0) {
													$restrictionsvalid = false;
													$restrictionerrmsg = JText::sprintf('VBRESTRERRMULTIPLYMINLOSRANGE', $restr['minlos']);
												}
											}
											$comborestr = VikBooking::parseJsDrangeWdayCombo($restr);
											if ($comborestr) {
												if (array_key_exists($restrcheckin['wday'], $comborestr)) {
													if (!in_array($restrcheckout['wday'], $comborestr[$restrcheckin['wday']])) {
														$restrictionsvalid = false;
														$restrictionerrmsg = JText::sprintf('VBRESTRERRWDAYCOMBORANGE', VikBooking::sayWeekDay($comborestr[$restrcheckin['wday']][0]).(count($comborestr[$restrcheckin['wday']]) == 2 ? '/'.VikBooking::sayWeekDay($comborestr[$restrcheckin['wday']][1]) : ''), VikBooking::sayWeekDay($restrcheckin['wday']));
													}
												}
											}
										} elseif (!empty($restr['ctad']) || !empty($restr['ctdd'])) {
											if (!empty($restr['ctad'])) {
												$ctarestrictions = explode(',', $restr['ctad']);
												if (in_array('-'.$restrcheckin['wday'].'-', $ctarestrictions)) {
													$restrictionsvalid = false;
													$restrictionerrmsg = JText::sprintf('VBRESTRERRWDAYCTARANGE', VikBooking::sayWeekDay($restrcheckin['wday']));
												}
											}
											if (!empty($restr['ctdd'])) {
												$ctdrestrictions = explode(',', $restr['ctdd']);
												if (in_array('-'.$restrcheckout['wday'].'-', $ctdrestrictions) && $restrcheckout[0] <= ($restr['dto'] + $end_operator)) {
													$restrictionsvalid = false;
													$restrictionerrmsg = JText::sprintf('VBRESTRERRWDAYCTDRANGE', VikBooking::sayWeekDay($restrcheckout['wday']));
												}
											}
										}
										if (!empty($restr['maxlos']) && $restr['maxlos'] > 0 && $restr['maxlos'] > $restr['minlos']) {
											if ($daysdiff > $restr['maxlos']) {
												$restrictionsvalid = false;
												$restrictionerrmsg = JText::sprintf('VBRESTRERRMAXLOSEXCEEDEDRANGE', $restr['maxlos']);
											}
										}
										if ($daysdiff < $restr['minlos']) {
											$restrictionsvalid = false;
											$restrictionerrmsg = JText::sprintf('VBRESTRERRMINLOSEXCEEDEDRANGE', $restr['minlos']);
											array_push($minlos_priority['nok'], (int)$restr['id']);
										} else {
											array_push($minlos_priority['ok'], (int)$restr['id']);
										}
									} elseif ($restr['dfrom'] <= $restrcheckout[0] && ($restr['dto'] + $end_operator) >= $restrcheckout[0] && !empty($restr['ctdd'])) {
										/**
										 * We validate the CTD restrictions depending on the check-out date.
										 * 
										 * @since 	1.16.3 (J) - 1.6.3 (WP)
										 */
										$ctdrestrictions = explode(',', $restr['ctdd']);
										if (in_array('-'.$restrcheckout['wday'].'-', $ctdrestrictions)) {
											$restrictions_affcount++;
											$restrictionsvalid = false;
											$restrictionerrmsg = JText::sprintf('VBRESTRERRWDAYCTDRANGE', VikBooking::sayWeekDay($restrcheckout['wday']));
										}
									}
								}
								/**
								 * We give priority to more recent restrictions to override MinLOS.
								 * 
								 * @since 	1.13.5 (J) - 1.3.6 (WP)
								 */
								if (!$restrictionsvalid && count($minlos_priority['ok']) && count($minlos_priority['nok']) && max($minlos_priority['ok']) > max($minlos_priority['nok'])) {
									// we unset the error message and we reset the validity because a more recent restriction is allowing this MinLOS
									$restrictionsvalid = true;
									$restrictionerrmsg = '';
								}
								//
							}
						}
						/**
						 * We are no longer counting the "global restriction records" applied to all rooms, but
						 * we are rather counting all restriction records, global and for individual rooms. This
						 * way we apply the default Min LOS only when there are no restrictions at all. We are
						 * also ignoring if some restrictions were applied. This is the previous IF statement:
						 * 
						 * if (!count($restrictions) || $restrictions_affcount <= 0) {
						 * 
						 * @since 	1.15.0 (J) - 1.5.0 (WP)
						 */
						if (!count($allrestrictions)) {
							// check global MinLOS (only in case there are no restrictions affecting these dates or no restrictions at all)
							$globminlos = VikBooking::getDefaultNightsCalendar();
							if ($globminlos > 1 && $daysdiff < $globminlos) {
								$restrictionsvalid = false;
								$restrictionerrmsg = JText::sprintf('VBRESTRERRMINLOSEXCEEDEDRANGE', $globminlos);
							}
						}
						//VBO 1.9 - check if we are coming from a package to not consider the global restrictions for the standard booking procedure
						if (!empty($ppkg_id)) {
							$pkg = VikBooking::getPackage($ppkg_id);
							if (count($pkg)) {
								//the package exists so we let the next view perform the validation of the package criteria (MinLOS, MaxLOS etc..).
								$restrictionsvalid = true;
								$restrictionerrmsg = '';
							}
						}
						//
						//Closing Dates
						$err_closingdates = VikBooking::validateClosingDates($first, $second, $df);
						if (!empty($err_closingdates)) {
							$restrictionsvalid = false;
							$restrictionerrmsg = JText::sprintf('VBERRDATESCLOSED', $err_closingdates);
						}
						//
						//Maximum date in the future for bookings
						$err_maxdatefuture = VikBooking::validateMaxDateBookings($first);
						if (!empty($err_maxdatefuture)) {
							$restrictionsvalid = false;
							$restrictionerrmsg = JText::sprintf('VBOERRMAXDATEBOOKINGS', $err_maxdatefuture);
						}
						//
						//Minimum days in advance for bookings
						$err_mindaysadv = VikBooking::validateMinDaysAdvance($first);
						if (!empty($err_mindaysadv)) {
							$restrictionsvalid = false;
							$restrictionerrmsg = JText::sprintf('VBOERRMINDAYSADV', $err_mindaysadv);
						}
						//
						if ($restrictionsvalid === true) {
							$hoursdiff = VikBooking::countHoursToArrival($first);
							$q = "SELECT `p`.*,`r`.`id` AS `r_reference_id`,`r`.`name`,`r`.`img`,`r`.`idcat`,`r`.`idcarat`,`r`.`units`,`r`.`moreimgs`,`r`.`fromadult`,`r`.`toadult`,`r`.`fromchild`,`r`.`tochild`,`r`.`smalldesc`,`r`.`totpeople`,`r`.`params`,`r`.`imgcaptions`,`rp`.`name` AS `rpname`,`rp`.`minlos`,`rp`.`minhadv` FROM `#__vikbooking_dispcost` AS `p`, `#__vikbooking_rooms` AS `r`, `#__vikbooking_prices` AS `rp` WHERE `p`.`days`=".(int)$daysdiff." AND `p`.`idroom`=`r`.`id` AND `p`.`idprice`=`rp`.`id` AND `r`.`avail`='1' AND (".implode(" OR ", $arradultsclause).") ORDER BY `p`.`cost` ASC, `p`.`idprice` DESC, `p`.`idroom` ASC;";
							$dbo->setQuery($q);
							$tars = $dbo->loadAssocList();
							if ($tars) {
								$vbo_tn->translateContents($tars, '#__vikbooking_rooms', array('id' => 'r_reference_id'));
								$arrtar = array();
								foreach ($tars as $tar) {
									$arrtar[$tar['idroom']][] = $tar;
								}
								//Closed rate plans on these dates
								$roomrpclosed = VikBooking::getRoomRplansClosedInDates(array_keys($arrtar), $first, $daysdiff);
								if (count($roomrpclosed) > 0) {
									foreach ($arrtar as $kk => $tt) {
										if (array_key_exists($kk, $roomrpclosed)) {
											foreach ($tt as $tk => $tv) {
												if (array_key_exists($tv['idprice'], $roomrpclosed[$kk])) {
													unset($arrtar[$kk][$tk]);
												}
											}
											if (!(count($arrtar[$kk]) > 0)) {
												unset($arrtar[$kk]);
											} else {
												$arrtar[$kk] = array_values($arrtar[$kk]);
											}
										}
									}
								}
								//
								//VBO 1.10 - rate plans with a minlos, or with a min hours in advance
								foreach ($arrtar as $kk => $tt) {
									foreach ($tt as $tk => $tv) {
										if (!empty($tv['minlos']) && $tv['minlos'] > $daysdiff) {
											unset($arrtar[$kk][$tk]);
										} elseif ($hoursdiff < $tv['minhadv']) {
											unset($arrtar[$kk][$tk]);
										}
									}
									if (!(count($arrtar[$kk]) > 0)) {
										unset($arrtar[$kk]);
									} else {
										$arrtar[$kk] = array_values($arrtar[$kk]);
									}
								}
								//
								$filtercat = (!empty($pcategories) && $pcategories != "all");
								$filtermulticat = (is_array($pcategory_ids) && count($pcategory_ids));
								//vikbooking 1.1
								$groupdays = VikBooking::getGroupDays($first, $second, $daysdiff);
								$morehst = VikBooking::getHoursRoomAvail() * 3600;
								//
								$allbusy = VikBooking::loadBusyRecords(array_keys($arrtar), $first, $second);
								$all_locked = VikBooking::loadLockedRecords(array_keys($arrtar), $actnow);
								foreach ($arrtar as $kk => $tt) {
									$cats = explode(";", $tt[0]['idcat']);
									if ($filtercat) {
										if (!in_array($pcategories, $cats)) {
											unset($arrtar[$kk]);
											continue;
										}
									}
									if ($filtermulticat) {
										/**
										 * Added support for multiple category IDs as filter
										 * 
										 * @since 	1.13
										 */
										$multicat_found = false;
										foreach ($pcategory_ids as $fcatid) {
											if (!empty($fcatid) && in_array($fcatid, $cats)) {
												$multicat_found = true;
												break;
											}
										}
										if (!$multicat_found) {
											unset($arrtar[$kk]);
											continue;
										}
									}
									$arrtar[$kk][0]['unitsavail'] = $tt[0]['units'];
									if ($allbusy && isset($allbusy[$kk]) && $allbusy[$kk]) {
										$units_booked = [];
										$check_locked = (bool)($all_locked && isset($all_locked[$kk]) && $all_locked[$kk]);
										foreach ($groupdays as $gday) {
											$bfound = 0;
											foreach ($allbusy[$kk] as $bu) {
												if (in_array($bu['id'], $skip_busy_ids)) {
													//VBO 1.10 - Booking modification
													continue;
												}
												if ($gday >= $bu['checkin'] && $gday <= ($morehst + $bu['checkout'])) {
													$bfound++;
												}
											}
											if ($bfound >= $tt[0]['units']) {
												unset($arrtar[$kk]);
												break;
											} else {
												$units_booked[] = $bfound;
												if ($check_locked === true) {
													foreach ($all_locked[$kk] as $bu) {
														if ($gday >= $bu['checkin'] && $gday <= $bu['realback']) {
															$bfound++;
														}
													}
													if ($bfound >= $tt[0]['units']) {
														unset($arrtar[$kk]);
														break;
													} else {
														// push peak to properly calculate the units left by considering the locked records
														$units_booked[] = $bfound;
													}
												}
											}
										}
										if (isset($arrtar[$kk]) && $units_booked) {
											$tot_u_booked = max($units_booked);
											$tot_u_left = ($tt[0]['units'] - $tot_u_booked);
											$arrtar[$kk][0]['unitsavail'] = $tot_u_left >= 0 ? $tot_u_left : 0;
										}
									} elseif (!VikBooking::roomNotLocked($kk, $tt[0]['units'], $first, $second)) {
										// room is fully locked
										unset($arrtar[$kk]);
									} elseif ($all_locked && isset($all_locked[$kk]) && $all_locked[$kk]) {
										// update number of units left by counting the units temporary locked
										$units_booked = [];
										foreach ($groupdays as $gday) {
											$bfound = 0;
											foreach ($all_locked[$kk] as $bu) {
												if ($gday >= $bu['checkin'] && $gday <= $bu['realback']) {
													$bfound++;
												}
											}
											// push peak to properly calculate the units left
											$units_booked[] = $bfound;
										}
										if (isset($arrtar[$kk]) && $units_booked) {
											$tot_u_booked = max($units_booked);
											$tot_u_left = ($tt[0]['units'] - $tot_u_booked);
											$arrtar[$kk][0]['unitsavail'] = $tot_u_left >= 0 ? $tot_u_left : 0;
										}
									}
									// single room restrictions
									if (isset($arrtar[$kk]) && $allrestrictions) {
										$roomrestr = VikBooking::roomRestrictions($kk, $allrestrictions);
										if (count($roomrestr) > 0) {
											$restrictionerrmsg = VikBooking::validateRoomRestriction($roomrestr, $restrcheckin, $restrcheckout, $daysdiff);
											if (strlen($restrictionerrmsg) > 0) {
												//VBO 1.9 - check if we are coming from a package to not consider the restrictions for the standard booking procedure
												$canunset = true;
												if (!empty($ppkg_id)) {
													$pkg = VikBooking::getPackage($ppkg_id);
													if (count($pkg)) {
														//the package exists so we let the next view perform the validation of the package criterai (MinLOS, MaxLOS etc..).
														$canunset = false;
													}
												}
												if ($canunset === true) {
													unset($arrtar[$kk]);
												} else {
													$restrictionerrmsg = '';
												}
												//
											}
										}
									}
									//end single room restrictions

									/**
									 * Room-level maximum advance booking notice validation.
									 * 
									 * @since 	1.16.3 (J) - 1.6.3 (WP)
									 */
									$err_maxdatefuture_room = VikBooking::validateMaxDateBookings($first, $kk);
									if (!empty($err_maxdatefuture_room)) {
										// unset non-compliant room and register last error
										unset($arrtar[$kk]);
										$restrictionerrmsg = JText::sprintf('VBOERRMAXDATEBOOKINGS', $err_maxdatefuture_room);
									}
								}
								if (is_array($arrtar) && $arrtar) {
									/**
									 * Despite of the higher resources needed, we parse all rate plans for all rooms.
									 * 
									 * @since 	1.13 (J) - 1.3.0 (WP)
									 */
									$arrtar = VikBooking::applySeasonalPrices($arrtar, $first, $second);
									$multi_rates = 1;
									foreach ($arrtar as $idr => $tars) {
										$multi_rates = count($tars) > $multi_rates ? count($tars) : $multi_rates;
									}
									if ($multi_rates > 1) {
										for ($r = 1; $r < $multi_rates; $r++) {
											$deeper_rates = array();
											foreach ($arrtar as $idr => $tars) {
												foreach ($tars as $tk => $tar) {
													if ($tk == $r) {
														$deeper_rates[$idr][0] = $tar;
														break;
													}
												}
											}
											if (!$deeper_rates) {
												continue;
											}
											$deeper_rates = VikBooking::applySeasonalPrices($deeper_rates, $first, $second);
											foreach ($deeper_rates as $idr => $dtars) {
												foreach ($dtars as $dtk => $dtar) {
													$arrtar[$idr][$r] = $dtar;
												}
											}
										}
									}
									$arrtar = VikBooking::sortResults($arrtar);
									//

									// separate results per number of rooms with $results
									$tmparrtar = $arrtar;
									$results = array();
									$multiroomcount = array();
									foreach ($arrpeople as $numroom => $aduchild) {
										$arrtar = $tmparrtar;
										$diffusage = array();
										$aduchild['children'] = !array_key_exists('children', $aduchild) ? 0 : $aduchild['children'];
										$nowtotpeople = $aduchild['adults'] + $aduchild['children'];
										foreach ($arrtar as $kk => $tt) {
											$validchildren = true;
											if ($showchildren) {
												if (!($tt[0]['fromchild'] <= $aduchild['children'] && $tt[0]['tochild'] >= $aduchild['children']) && $aduchild['children'] > 0) {
													$validchildren = false;
												}
											}
											$validtotpeople = true;
											if ($nowtotpeople > $tt[0]['totpeople']) {
												$errmess = JText::sprintf('VBERRPEOPLEPERROOM', $nowtotpeople, $aduchild['adults'], $aduchild['children']);
												$validtotpeople = false;
											}
											if ($validchildren && $validtotpeople) {
												if ($tt[0]['toadult'] == $aduchild['adults']) {
													//clean the diffusage from best usage in case it exists from before
													foreach ($arrtar[$kk] as $kpr => $vpr) {
														if (array_key_exists('diffusage', $arrtar[$kk][$kpr])) {
															unset($arrtar[$kk][$kpr]['diffusage']);
														}
														if (array_key_exists('diffusagecost', $arrtar[$kk][$kpr])) {
															//restore original price
															$operator = substr($arrtar[$kk][$kpr]['diffusagecost'], 0, 1);
															$valpcent = substr($arrtar[$kk][$kpr]['diffusagecost'], -1);
															if ($operator == "+") {
																if ($valpcent == "%") {
																	$diffvalue = substr($arrtar[$kk][$kpr]['diffusagecost'], 1, (strlen($arrtar[$kk][$kpr]['diffusagecost']) - 1));
																	if (array_key_exists('diffusagecostpernight', $arrtar[$kk][$kpr]) && $arrtar[$kk][$kpr]['diffusagecostpernight'] > 0) {
																		$arrtar[$kk][$kpr]['cost'] = $arrtar[$kk][$kpr]['diffusagecostpernight'];
																	} else {
																		$arrtar[$kk][$kpr]['cost'] = round(($vpr['cost'] * (100 - $diffvalue) / 100), 2);
																	}
																} else {
																	$diffvalue = substr($arrtar[$kk][$kpr]['diffusagecost'], 1);
																	$arrtar[$kk][$kpr]['cost'] = $vpr['cost'] - $diffvalue;
																}
															} elseif ($operator == "-") {
																if ($valpcent == "%") {
																	$diffvalue = substr($arrtar[$kk][$kpr]['diffusagecost'], 1, (strlen($arrtar[$kk][$kpr]['diffusagecost']) - 1));
																	if (array_key_exists('diffusagecostpernight', $arrtar[$kk][$kpr]) && $arrtar[$kk][$kpr]['diffusagecostpernight'] > 0) {
																		$arrtar[$kk][$kpr]['cost'] = $arrtar[$kk][$kpr]['diffusagecostpernight'];
																	} else {
																		$arrtar[$kk][$kpr]['cost'] = round(($vpr['cost'] * (100 + $diffvalue) / 100), 2);
																	}
																} else {
																	$diffvalue = substr($arrtar[$kk][$kpr]['diffusagecost'], 1);
																	$arrtar[$kk][$kpr]['cost'] = $vpr['cost'] + $diffvalue;
																}
															}
															//
															unset($arrtar[$kk][$kpr]['diffusagecost']);
															unset($arrtar[$kk][$kpr]['diffusagecostpernight']);
														}
													}
													//
													//VikBooking 1.3 - Maximum Occupancy Charges/Discounts
													$diffusageprice = VikBooking::loadAdultsDiff($kk, $aduchild['adults']);
													//Occupancy Override
													if (array_key_exists('occupancy_ovr', $tt[0]) && array_key_exists($aduchild['adults'], $tt[0]['occupancy_ovr']) && strlen($tt[0]['occupancy_ovr'][$aduchild['adults']]['value'])) {
														$diffusageprice = $tt[0]['occupancy_ovr'][$aduchild['adults']];
													}
													//
													if (is_array($diffusageprice)) {
														//set a charge or discount to the price(s) for the different usage of the room
														foreach ($arrtar[$kk] as $kpr => $vpr) {
															$arrtar[$kk][$kpr]['diffusage'] = $aduchild['adults'];
															if ($diffusageprice['chdisc'] == 1) {
																//charge
																if ($diffusageprice['valpcent'] == 1) {
																	//fixed value
																	$arrtar[$kk][$kpr]['diffusagecostpernight'] = $diffusageprice['pernight'] == 1 ? 1 : 0;
																	$aduseval = $diffusageprice['pernight'] == 1 ? $diffusageprice['value'] * $arrtar[$kk][$kpr]['days'] : $diffusageprice['value'];
																	$arrtar[$kk][$kpr]['diffusagecost'] = "+".$aduseval;
																	$arrtar[$kk][$kpr]['cost'] = $vpr['cost'] + $aduseval;
																} else {
																	//percentage value
																	$arrtar[$kk][$kpr]['diffusagecostpernight'] = $diffusageprice['pernight'] == 1 ? $vpr['cost'] : 0;
																	$aduseval = $diffusageprice['pernight'] == 1 ? round(($vpr['cost'] * $diffusageprice['value'] / 100) * $arrtar[$kk][$kpr]['days'] + $vpr['cost'], 2) : round(($vpr['cost'] * (100 + $diffusageprice['value']) / 100), 2);
																	$arrtar[$kk][$kpr]['diffusagecost'] = "+".$diffusageprice['value']."%";
																	$arrtar[$kk][$kpr]['cost'] = $aduseval;
																}
															} else {
																//discount
																if ($diffusageprice['valpcent'] == 1) {
																	//fixed value
																	$arrtar[$kk][$kpr]['diffusagecostpernight'] = $diffusageprice['pernight'] == 1 ? 1 : 0;
																	$aduseval = $diffusageprice['pernight'] == 1 ? $diffusageprice['value'] * $arrtar[$kk][$kpr]['days'] : $diffusageprice['value'];
																	$arrtar[$kk][$kpr]['diffusagecost'] = "-".$aduseval;
																	$arrtar[$kk][$kpr]['cost'] = $vpr['cost'] - $aduseval;
																} else {
																	//percentage value
																	$arrtar[$kk][$kpr]['diffusagecostpernight'] = $diffusageprice['pernight'] == 1 ? $vpr['cost'] : 0;
																	$aduseval = $diffusageprice['pernight'] == 1 ? round($vpr['cost'] - ((($vpr['cost'] / $arrtar[$kk][$kpr]['days']) * $diffusageprice['value'] / 100) * $arrtar[$kk][$kpr]['days']), 2) : round(($vpr['cost'] * (100 - $diffusageprice['value']) / 100), 2);
																	$arrtar[$kk][$kpr]['diffusagecost'] = "-".$diffusageprice['value']."%";
																	$arrtar[$kk][$kpr]['cost'] = $aduseval;
																}
															}
														}
													}
													//VikBooking 1.3 - Maximum Occupancy Charges/Discounts
													//best usage
													$results[$numroom][] = $arrtar[$kk];
													if (!isset($multiroomcount[$arrtar[$kk][0]['idroom']])) {
														$multiroomcount[$arrtar[$kk][0]['idroom']] = array('count' => 0);
													}
													$multiroomcount[$arrtar[$kk][0]['idroom']]['count'] += 1;
													$multiroomcount[$arrtar[$kk][0]['idroom']]['unitsavail'] = (int)$arrtar[$kk][0]['unitsavail'];
													$multiroomcount[$arrtar[$kk][0]['idroom']]['diffusage_r'.$numroom] = 0;
												} elseif (($tt[0]['fromadult'] <= $aduchild['adults'] || $booking_suggestion) && $tt[0]['toadult'] > $aduchild['adults']) {
													//different usage
													$diffusageprice = VikBooking::loadAdultsDiff($kk, $aduchild['adults']);
													//Occupancy Override
													if (array_key_exists('occupancy_ovr', $tt[0]) && array_key_exists($aduchild['adults'], $tt[0]['occupancy_ovr']) && strlen($tt[0]['occupancy_ovr'][$aduchild['adults']]['value'])) {
														$diffusageprice = $tt[0]['occupancy_ovr'][$aduchild['adults']];
													}
													//
													if (is_array($diffusageprice)) {
														//set a charge or discount to the price(s) for the different usage of the room
														foreach ($arrtar[$kk] as $kpr => $vpr) {
															$arrtar[$kk][$kpr]['diffusage'] = $aduchild['adults'];
															if ($diffusageprice['chdisc'] == 1) {
																//charge
																if ($diffusageprice['valpcent'] == 1) {
																	//fixed value
																	$arrtar[$kk][$kpr]['diffusagecostpernight'] = $diffusageprice['pernight'] == 1 ? 1 : 0;
																	$aduseval = $diffusageprice['pernight'] == 1 ? $diffusageprice['value'] * $arrtar[$kk][$kpr]['days'] : $diffusageprice['value'];
																	$arrtar[$kk][$kpr]['diffusagecost'] = "+".$aduseval;
																	$arrtar[$kk][$kpr]['cost'] = $vpr['cost'] + $aduseval;
																} else {
																	//percentage value
																	$arrtar[$kk][$kpr]['diffusagecostpernight'] = $diffusageprice['pernight'] == 1 ? $vpr['cost'] : 0;
																	$aduseval = $diffusageprice['pernight'] == 1 ? round(($vpr['cost'] * $diffusageprice['value'] / 100) * $arrtar[$kk][$kpr]['days'] + $vpr['cost'], 2) : round(($vpr['cost'] * (100 + $diffusageprice['value']) / 100), 2);
																	$arrtar[$kk][$kpr]['diffusagecost'] = "+".$diffusageprice['value']."%";
																	$arrtar[$kk][$kpr]['cost'] = $aduseval;
																}
															} else {
																//discount
																if ($diffusageprice['valpcent'] == 1) {
																	//fixed value
																	$arrtar[$kk][$kpr]['diffusagecostpernight'] = $diffusageprice['pernight'] == 1 ? 1 : 0;
																	$aduseval = $diffusageprice['pernight'] == 1 ? $diffusageprice['value'] * $arrtar[$kk][$kpr]['days'] : $diffusageprice['value'];
																	$arrtar[$kk][$kpr]['diffusagecost'] = "-".$aduseval;
																	$arrtar[$kk][$kpr]['cost'] = $vpr['cost'] - $aduseval;
																} else {
																	//percentage value
																	$arrtar[$kk][$kpr]['diffusagecostpernight'] = $diffusageprice['pernight'] == 1 ? $vpr['cost'] : 0;
																	$aduseval = $diffusageprice['pernight'] == 1 ? round($vpr['cost'] - ((($vpr['cost'] / $arrtar[$kk][$kpr]['days']) * $diffusageprice['value'] / 100) * $arrtar[$kk][$kpr]['days']), 2) : round(($vpr['cost'] * (100 - $diffusageprice['value']) / 100), 2);
																	$arrtar[$kk][$kpr]['diffusagecost'] = "-".$diffusageprice['value']."%";
																	$arrtar[$kk][$kpr]['cost'] = $aduseval;
																}
															}
														}
													} else {
														$arrtar[$kk][0]['diffusage'] = $aduchild['adults'];
													}
													$diffusage[$numroom][] = $arrtar[$kk];
													if (!isset($multiroomcount[$arrtar[$kk][0]['idroom']])) {
														$multiroomcount[$arrtar[$kk][0]['idroom']] = array('count' => 0);
													}
													$multiroomcount[$arrtar[$kk][0]['idroom']]['count'] += 1;
													$multiroomcount[$arrtar[$kk][0]['idroom']]['unitsavail'] = (int)$arrtar[$kk][0]['unitsavail'];
													$multiroomcount[$arrtar[$kk][0]['idroom']]['diffusage_r'.$numroom] = 1;
												}
											}
										}
										//merge $diffusage rooms with and after best usage rooms in $results
										if (count($diffusage) > 0) {
											foreach ($diffusage as $nr => $du) {
												foreach ($du as $duroom) {
													$results[$nr][]=$duroom;
												}
											}
										}
										//
									}
									//
									//check if rooms repeated passed the availability
									$limitpassed = false;
									$search_type = VikBooking::getSmartSearchType();
									$js_search = $search_type == 'dynamic' ? true : false;
									//dynamic smart search via PHP
									$js_overcounter = array();
									if ($js_search && $proomsnum > 1 && count($multiroomcount) > 0) {
										$tot_avail = 0;
										foreach ($multiroomcount as $idroom => $info) {
											$tot_avail += $info['unitsavail'];
										}
										if ($tot_avail >= $proomsnum) {
											$gen_avail = $results;
											foreach ($multiroomcount as $idroom => $info) {
												$multiroomcount[$idroom]['used'] = 0;
												if ($info['count'] > $info['unitsavail']) {
													$excessnum = $info['count'] - $info['unitsavail'];
													if ($excessnum > 0) {
														for ($z = $proomsnum; $z >= 1; $z--) {
															if ($excessnum > 0) {
																/**
																 * VBO 1.10 Patch May 2018
																 * @see  comments below
																 */
																$excessproc = false;
																//
																foreach ($results[$z] as $kres => $res) {
																	if ($res[0]['idroom'] == $idroom) {
																		/**
																		 * We do not need to unset the rooms as we risk to produce no results.
																		 * We skip this control if the "search type" setting is set to "dynamic".
																		 * 
																		 * @since 	1.12 Patch October 2nd 2019
																		 */
																		if (!$js_search) {
																			unset($gen_avail[$z][$kres]);
																		}
																		$excessproc = true;
																	}
																}
																if ($excessproc) {
																	/**
																	 * VBO 1.10 Patch May 2018
																	 * @see  comments below
																	 */
																	$excessnum--;
																}
															}
														}
													}
												}
											}
											/**
											 * We do not need to unset the rooms as we risk to produce no results.
											 * We skip this control if the "search type" setting is set to "dynamic".
											 * 
											 * @since 	1.12 Patch October 2nd 2019
											 */
											if (!$js_search) {
												for ($z = $proomsnum; $z >= 1; $z--) {
													if (!isset($gen_avail[$z]) || !count($gen_avail[$z])) {
														foreach ($gen_avail as $oknroom => $res) {
															if (count($gen_avail[$oknroom]) > 1) {
																$searchfrom = min(array_keys($res));
																foreach ($res as $kr => $rr) {
																	if (intval($kr) > intval($searchfrom)) {
																		//check if the second, third.. cheapest room(s) is compatible
																		if ($rr[0]['fromadult'] <= $arrpeople[$z]['adults'] && $rr[0]['toadult'] >= $arrpeople[$z]['adults'] && $rr[0]['fromchild'] <= $arrpeople[$z]['children'] && $rr[0]['tochild'] >= $arrpeople[$z]['children']) {
																			$gen_avail[$z][] = $gen_avail[$oknroom][$kr];
																			unset($gen_avail[$oknroom][$kr]);
																			break 2;
																		}
																	}
																}
															}
														}
													}
												}
											}
											/**
											 * VBO 1.10 Patch May 2018
											 *
											 * Scenario: One room with 3 units in total, 2 units booked and 1 free on certain dates, for 6 adults.
											 * Another room with 5 units available on these dates, for 2 adults. Dynamic search enabled.
											 *
											 * Reservation: 3 room types, 6 adults 1st room, 6 adults 2nd room, 2 adults 3rd room.
											 *
											 * Results: the 6-adult room is displayed for the 1st and 2nd rooms requested, the 2-adult room for the 3rd.
											 * With the Dynamic search enabled, by selecting the 6-adult room once, it gets removed from the other combo.
											 * This leaves one room-party empty, with no results, but no errors are displayed for the search suggestions,
											 * the search results page just gets stuck in there because for a party there are no rooms that can be chosen.
											 * 
											 * Solution: if one room-party has no more rooms, unset it so that $js_overcounter won't be set, and the
											 * "automatic smart search via PHP" statement will be processed below. This will raise the error "not enough rooms",
											 * and the Search Suggestions will correctly calculate the availability.
											 *
											 */
											foreach ($gen_avail as $oknroom => $res) {
												if (!count($gen_avail[$oknroom])) {
													unset($gen_avail[$oknroom]);
												}
											}
											//
											if (count($gen_avail) == $proomsnum) {
												$js_overcounter = $multiroomcount;
												unset($gen_avail);
											}
										}
									}
									//
									//automatic smart search via PHP
									if ($proomsnum > 1 && count($multiroomcount) > 0) {
										foreach ($multiroomcount as $idroom => $info) {
											if ($info['count'] > $info['unitsavail']) {
												$excessnum = $info['count'] - $info['unitsavail'];
												for ($z = $proomsnum; $z >= 1; $z--) {
													/**
													 * VBO 1.10 Patch June 2018
													 * the different usage clause below should be ignored, or we risk to keep a room in excess
													 * for some room parties when there should be actually no availability for booking multiple times.
													 * This room in excess may be removed only for the parties with different usage, and this is incorrect.
													 */
													if (true || (array_key_exists('diffusage_r'.$z, $info) && $info['diffusage_r'.$z] == 1)) {
														//remove repeated room where diffusage and excessnum still exceeds
														if ($excessnum > 0 && count($js_overcounter) == 0) {
															foreach ($results[$z] as $kres => $res) {
																if ($res[0]['idroom'] == $idroom) {
																	unset($results[$z][$kres]);
																	$limitpassed = true;
																}
															}
															if ($limitpassed) {
																$excessnum--;
															}
														}
														//
													}
												}
												//if excessnum still exceeds, means that the room is not available for the repeated best usages
												if ($excessnum > 0) {
													for ($z = $proomsnum; $z >= 1; $z--) {
														if ($excessnum > 0 && count($js_overcounter) == 0) {
															foreach ($results[$z] as $kres => $res) {
																if ($res[0]['idroom'] == $idroom) {
																	unset($results[$z][$kres]);
																	$limitpassed = true;
																}
															}
															if ($limitpassed) {
																$excessnum--;
															}
														}
													}
												}
												//
											}
										}
									}
									//
									//if some room was repeated and removed from the multi rooms searched, check if enough results for each room
									if ($limitpassed == true) {
										$critic = array();
										for ($z = $proomsnum; $z >= 1; $z--) {
											if (!isset($results[$z]) || !count($results[$z])) {
												$critic[] = $z;
												unset($results[$z]);
											}
										}
										if (count($critic) > 0) {
											//some rooms have 0 results, check if something good for this num of adults can be placed here
											$moved = array();
											foreach ($critic as $kcr => $nroom) {
												foreach ($results as $oknroom => $res) {
													if (count($results[$oknroom]) > 1) {
														$searchfrom = min(array_keys($res));
														foreach ($res as $kr => $rr) {
															if (intval($kr) > intval($searchfrom)) {
																//check if the second, third.. cheapest room(s) is compatible
																if ($rr[0]['fromadult'] <= $arrpeople[$nroom]['adults'] && $rr[0]['toadult'] >= $arrpeople[$nroom]['adults'] && $rr[0]['fromchild'] <= $arrpeople[$nroom]['children'] && $rr[0]['tochild'] >= $arrpeople[$nroom]['children']) {
																	$results[$nroom][] = $results[$oknroom][$kr];
																	$moved[] = $oknroom.'_'.$nroom;
																	unset($results[$oknroom][$kr]);
																	unset($critic[$kcr]);
																	break 2;
																}
															}
														}
													}
												}
											}
											ksort($results);
											if (count($moved) > 0) {
												//check if moved rooms had charges/discounts for adults occupancy to update it
												foreach ($moved as $move) {
													$movedata = explode('_', $move);
													if ($arrpeople[$movedata[0]]['adults'] != $arrpeople[$movedata[1]]['adults'] && array_key_exists('diffusagecost', $results[$movedata[1]][0][0])) {
														//reset prices of the room
														foreach ($results[$movedata[1]][0] as $kpr => $vpr) {
															if (array_key_exists('diffusage', $results[$movedata[1]][0][$kpr])) {
																unset($results[$movedata[1]][0][$kpr]['diffusage']);
															}
															if (array_key_exists('diffusagecost', $results[$movedata[1]][0][$kpr])) {
																//restore original price
																$operator = substr($results[$movedata[1]][0][$kpr]['diffusagecost'], 0, 1);
																$valpcent = substr($results[$movedata[1]][0][$kpr]['diffusagecost'], -1);
																if ($operator == "+") {
																	if ($valpcent == "%") {
																		$diffvalue = substr($results[$movedata[1]][0][$kpr]['diffusagecost'], 1, (strlen($results[$movedata[1]][0][$kpr]['diffusagecost']) - 1));
																		if (array_key_exists('diffusagecostpernight', $results[$movedata[1]][0][$kpr]) && $results[$movedata[1]][0][$kpr]['diffusagecostpernight'] > 0) {
																			$results[$movedata[1]][0][$kpr]['cost'] = $results[$movedata[1]][0][$kpr]['diffusagecostpernight'];
																		} else {
																			$results[$movedata[1]][0][$kpr]['cost'] = round(($vpr['cost'] * (100 - $diffvalue) / 100), 2);
																		}
																	} else {
																		$diffvalue = substr($results[$movedata[1]][0][$kpr]['diffusagecost'], 1);
																		$results[$movedata[1]][0][$kpr]['cost'] = $vpr['cost'] - $diffvalue;
																	}
																} elseif ($operator == "-") {
																	if ($valpcent == "%") {
																		$diffvalue = substr($results[$movedata[1]][0][$kpr]['diffusagecost'], 1, (strlen($results[$movedata[1]][0][$kpr]['diffusagecost']) - 1));
																		if (array_key_exists('diffusagecostpernight', $results[$movedata[1]][0][$kpr]) && $results[$movedata[1]][0][$kpr]['diffusagecostpernight'] > 0) {
																			$results[$movedata[1]][0][$kpr]['cost'] = $results[$movedata[1]][0][$kpr]['diffusagecostpernight'];
																		} else {
																			$results[$movedata[1]][0][$kpr]['cost'] = round(($vpr['cost'] * (100 + $diffvalue) / 100), 2);
																		}
																	} else {
																		$diffvalue = substr($results[$movedata[1]][0][$kpr]['diffusagecost'], 1);
																		$results[$movedata[1]][0][$kpr]['cost'] = $vpr['cost'] + $diffvalue;
																	}
																}
																//
																unset($results[$movedata[1]][0][$kpr]['diffusagecost']);
																unset($results[$movedata[1]][0][$kpr]['diffusagecostpernight']);
															}
														}
														//end reset prices of the room
														$diffusageprice = VikBooking::loadAdultsDiff($results[$movedata[1]][0][0]['idroom'], $arrpeople[$movedata[1]]['adults']);
														//Occupancy Override - Special Price may be setting a charge/discount for this occupancy while default price had no occupancy pricing
														if (!is_array($diffusageprice)) {
															foreach ($results[$movedata[1]][0] as $kpr => $vpr) {
																if (array_key_exists('occupancy_ovr', $vpr) && array_key_exists($arrpeople[$movedata[1]]['adults'], $vpr['occupancy_ovr']) && strlen($vpr['occupancy_ovr'][$arrpeople[$movedata[1]]['adults']]['value'])) {
																	$diffusageprice = $vpr['occupancy_ovr'][$arrpeople[$movedata[1]]['adults']];
																	break;
																}
															}
															reset($results[$movedata[1]][0]);
														}
														//
														if (is_array($diffusageprice)) {
															//set a charge or discount to the price(s) for the different usage of the room
															foreach ($results[$movedata[1]][0] as $kpr => $vpr) {
																//Occupancy Override
																if (array_key_exists('occupancy_ovr', $results[$movedata[1]][0][$kpr]) && array_key_exists($arrpeople[$movedata[1]]['adults'], $results[$movedata[1]][0][$kpr]['occupancy_ovr']) && strlen($results[$movedata[1]][0][$kpr]['occupancy_ovr'][$arrpeople[$movedata[1]]['adults']]['value'])) {
																	$diffusageprice = $results[$movedata[1]][0][$kpr]['occupancy_ovr'][$arrpeople[$movedata[1]]['adults']];
																}
																//
																$results[$movedata[1]][0][$kpr]['diffusage'] = $arrpeople[$movedata[1]]['adults'];
																if ($diffusageprice['chdisc'] == 1) {
																	//charge
																	if ($diffusageprice['valpcent'] == 1) {
																		//fixed value
																		$results[$movedata[1]][0][$kpr]['diffusagecostpernight'] = $diffusageprice['pernight'] == 1 ? 1 : 0;
																		$aduseval = $diffusageprice['pernight'] == 1 ? $diffusageprice['value'] * $results[$movedata[1]][0][$kpr]['days'] : $diffusageprice['value'];
																		$results[$movedata[1]][0][$kpr]['diffusagecost'] = "+".$aduseval;
																		$results[$movedata[1]][0][$kpr]['cost'] = $vpr['cost'] + $aduseval;
																	} else {
																		//percentage value
																		$results[$movedata[1]][0][$kpr]['diffusagecostpernight'] = $diffusageprice['pernight'] == 1 ? $vpr['cost'] : 0;
																		$aduseval = $diffusageprice['pernight'] == 1 ? round(($vpr['cost'] * $diffusageprice['value'] / 100) * $results[$movedata[1]][0][$kpr]['days'] + $vpr['cost'], 2) : round(($vpr['cost'] * (100 + $diffusageprice['value']) / 100), 2);
																		$results[$movedata[1]][0][$kpr]['diffusagecost'] = "+".$diffusageprice['value']."%";
																		$results[$movedata[1]][0][$kpr]['cost'] = $aduseval;
																	}
																} else {
																	//discount
																	if ($diffusageprice['valpcent'] == 1) {
																		//fixed value
																		$results[$movedata[1]][0][$kpr]['diffusagecostpernight'] = $diffusageprice['pernight'] == 1 ? 1 : 0;
																		$aduseval = $diffusageprice['pernight'] == 1 ? $diffusageprice['value'] * $results[$movedata[1]][0][$kpr]['days'] : $diffusageprice['value'];
																		$results[$movedata[1]][0][$kpr]['diffusagecost'] = "-".$aduseval;
																		$results[$movedata[1]][0][$kpr]['cost'] = $vpr['cost'] - $aduseval;
																	} else {
																		//percentage value
																		$results[$movedata[1]][0][$kpr]['diffusagecostpernight'] = $diffusageprice['pernight'] == 1 ? $vpr['cost'] : 0;
																		$aduseval = $diffusageprice['pernight'] == 1 ? round($vpr['cost'] - ((($vpr['cost'] / $results[$movedata[1]][0][$kpr]['days']) * $diffusageprice['value'] / 100) * $results[$movedata[1]][0][$kpr]['days']), 2) : round(($vpr['cost'] * (100 - $diffusageprice['value']) / 100), 2);
																		$results[$movedata[1]][0][$kpr]['diffusagecost'] = "-".$diffusageprice['value']."%";
																		$results[$movedata[1]][0][$kpr]['cost'] = $aduseval;
																	}
																}
															}
														}
													}
												}
												//end check if moved rooms had charges/discounts for adults occupancy to update it
											}
											//
										}
									}
									//
									$results = VikBooking::sortMultipleResults($results);
									//save prices in session for the modules
									$sessvals = array();
									$modprices = array();
									$sessvals['roomsnum'] = $proomsnum;
									$sessvals['checkin'] = $first;
									$sessvals['checkout'] = $second;
									for ($i = 1; $i <= $proomsnum; $i++) {
										if (!isset($results[$i])) {
											continue;
										}
										foreach ($results[$i] as $indres => $res) {
											$modprices[$i][] = $res[0]['cost'];
										}
									}
									for ($i = 1; $i <= $proomsnum; $i++) {
										if (!isset($modprices[$i])) {
											continue;
										}
										$mincost = min($modprices[$i]);
										$maxcost = max($modprices[$i]);
										$sessvals[$i]['min'] = $mincost;
										$sessvals[$i]['max'] = $maxcost;
										$sessvals[$i]['adults'] = $arrpeople[$i]['adults'];
										$sessvals[$i]['children'] = $arrpeople[$i]['children'];
									}
									$session->set('vbsearchdata', $sessvals);
									//end save prices in session for the modules
									//apply price filters
									$ppricefrom = VikRequest::getInt('r1pricefrom', '', 'request');
									$ppriceto = VikRequest::getInt('r1priceto', '', 'request');
									if (!empty($ppricefrom) && !empty($ppriceto)) {
										foreach ($results as $oknroom => $res) {
											$totroomres = count($res);
											foreach ($res as $kr => $rr) {
												if ($oknroom > 1) {
													$ppricefrom = VikRequest::getInt('r'.$oknroom.'pricefrom', '', 'request');
													$ppriceto = VikRequest::getInt('r'.$oknroom.'priceto', '', 'request');
												}
												if (!empty($ppricefrom) && !empty($ppriceto)) {
													if ($rr[0]['cost'] < $ppricefrom || $rr[0]['cost'] > $ppriceto) {
														if ($totroomres > 1) {
															unset($results[$oknroom][$kr]);
															$totroomres--;
														}
													}
												}
											}
										}
									}
									//end apply price filters
									if (count($results) == $proomsnum) {
										// check whether the user is coming from roomdetails
										$proomdetail = VikRequest::getInt('roomdetail', 0, 'request');
										$rate_plan_id = VikRequest::getInt('rate_plan_id', 0, 'request');
										$user_currency = VikRequest::getString('user_currency', '', 'request');
										$children_age = VikRequest::getVar('children_age', array());
										$pitemid = VikRequest::getInt('Itemid', 0, 'request');
										if (!empty($proomdetail) && array_key_exists($proomdetail, $arrtar) && $proomsnum == 1) {
											// VBO 1.11 - push data to tracker and close
											VikBooking::getTracker()->pushRooms($proomdetail)->closeTrack();
											// store in the session that we are coming from the room details page
											$session->set('vboSearchRoomId', $proomdetail);

											/**
											 * Build redirect URI to the "showprc" View to continue the booking process, and
											 * keep some special vars that may serve to reflect the original booking link.
											 * 
											 * @since 	1.15.0 (J) - 1.5.0 (WP)
											 */
											$url_query_args = [
												'option' 	=> 'com_vikbooking',
												'task' 		=> 'showprc',
												'roomsnum' 	=> '1',
												'roomopt' 	=> [
													$proomdetail,
												],
												'adults' 	=> [
													$arrpeople[1]['adults'],
												],
												'children' 	=> [
													$arrpeople[1]['children'],
												],
												'days' 		=> $daysdiff,
												'checkin' 	=> $first,
												'checkout' 	=> $second,
											];
											if (!empty($ppkg_id)) {
												$url_query_args['pkg_id'] = $ppkg_id;
											}
											if (!empty($rate_plan_id)) {
												$url_query_args['rate_plan_id'] = $rate_plan_id;
											}
											if (is_array($children_age) && count($children_age)) {
												$url_query_args['children_age'] = $children_age;
											}
											if (!empty($user_currency)) {
												$url_query_args['user_currency'] = $user_currency;
											}
											if (!empty($pcategory_ids)) {
												$url_query_args['category_ids'] = $pcategory_ids;
											}
											if (!empty($pitemid)) {
												$url_query_args['Itemid'] = $pitemid;
											}
											
											$mainframe->redirect(JRoute::rewrite('index.php?' . http_build_query($url_query_args), false));
											exit;
										} else {
											if (!empty($proomdetail) && $proomsnum == 1) {
												$q = "SELECT `id`,`name` FROM `#__vikbooking_rooms` WHERE `id`=".intval($proomdetail).";";
												$dbo->setQuery($q);
												$cdet = $dbo->loadAssocList();
												if ($cdet) {
													$vbo_tn->translateContents($cdet, '#__vikbooking_rooms');
													$msg = JText::sprintf('VBDETAILCNOTAVAIL', $cdet[0]['name'], $daysdiff);
													VikError::raiseWarning('', $msg);
													// VBO 1.11 - push data to tracker and close
													VikBooking::getTracker()->pushRooms($proomdetail)->pushMessage($msg, 'warning')->closeTrack();
													//
												}
											} elseif (!empty($proomdetail) && $proomsnum > 1) {
												//check whether the user is coming from roomdetails and if the room is available for any room party
												$room_missing = false;
												foreach ($results as $indroom => $rooms) {
													$room_found = false;
													foreach ($rooms as $room) {
														if ($room[0]['idroom'] == $proomdetail && (!array_key_exists('unitsavail', $room[0]) || $room[0]['unitsavail'] >= $proomsnum)) {
															$room_found = true;
															break;
														}
													}
													if (!$room_found) {
														$room_missing = true;
														break;
													}
												}
												if ($room_missing === false) {
													$aduchild_str = '';
													foreach ($arrpeople as $people) {
														$aduchild_str .= '&adults[]='.$people['adults'].'&children[]='.$people['children'];
													}
													$roomopt_str = '';
													for ($ri = 0; $ri < $proomsnum; $ri++) { 
														$roomopt_str .= '&roomopt[]='.$proomdetail;
													}
													// store in the session that we are coming from the room details page
													$session->set('vboSearchRoomId', $proomdetail);
													//
													$mainframe->redirect(JRoute::rewrite("index.php?option=com_vikbooking&task=showprc&roomsnum=".$proomsnum.$roomopt_str.$aduchild_str."&days=".$daysdiff."&checkin=".$first."&checkout=".$second.(!empty($ppkg_id) ? "&pkg_id=" . $ppkg_id : "").(!empty($pitemid) ? "&Itemid=" . $pitemid : ""), false));
													// VBO 1.11 - push data to tracker and close
													VikBooking::getTracker()->pushRooms(array_fill(0, $proomsnum, $proomdetail))->closeTrack();
													//
													exit;
												} else {
													$q = "SELECT `id`,`name` FROM `#__vikbooking_rooms` WHERE `id`=".intval($proomdetail).";";
													$dbo->setQuery($q);
													$cdet = $dbo->loadAssocList();
													if ($cdet) {
														$vbo_tn->translateContents($cdet, '#__vikbooking_rooms');
														$msg = JText::sprintf('VBDETAILMULTIRNOTAVAIL', $proomsnum, $cdet[0]['name'], $daysdiff);
														VikError::raiseWarning('', $msg);
														// VBO 1.11 - push data to tracker and close
														VikBooking::getTracker()->pushMessage($msg, 'warning')->closeTrack();
														//
													}
												}
											}
											$this->res = $results;
											$this->days = $daysdiff;
											$this->checkin = $first;
											$this->checkout = $second;
											$this->roomsnum = $proomsnum;
											$this->arrpeople = $arrpeople;
											$showchildren = $showchildren ? 1 : 0;
											$this->showchildren = $showchildren;
											$this->js_overcounter = $js_overcounter;
											$this->mod_booking = $mod_booking;
											$this->vbo_tn = $vbo_tn;
											//theme
											$theme = VikBooking::getTheme();
											if ($theme != 'default') {
												$thdir = VBO_SITE_PATH.DS.'themes'.DS.$theme.DS.'search';
												if (is_dir($thdir)) {
													$this->_setPath('template', $thdir.DS);
												}
											}

											// unset the session if we were coming from the room details page
											$session->set('vboSearchRoomId', '');

											// VBO 1.11 - close tracker
											VikBooking::getTracker()
												->setExtraInfo('results', $results)
												->closeTrack();

											parent::display($tpl);
										}
										//
									} else {
										//zero results for some room
										$errmess = array();
										if (isset($critic) && count($critic) > 0) {
											foreach ($critic as $nroom) {
												$errmess[] = $arrpeople[$nroom]['adults']." ".($arrpeople[$nroom]['adults'] == 1 ? JText::translate('VBSEARCHRESADULT') : JText::translate('VBSEARCHRESADULTS')).($arrpeople[$nroom]['children'] > 0 ? ", ".$arrpeople[$nroom]['children']." ".($arrpeople[$nroom]['children'] == 1 ? JText::translate('VBSEARCHRESCHILD') : JText::translate('VBSEARCHRESCHILDREN')) : "");
											}
											$errmess = array_unique($errmess);
										} else {
											$errmess[] = JText::translate('VBOSEARCHERRCODETHREEBASE');
										}
										$err_code_info = array(
											'code' => 3,
											'fromts' => $first,
											'tots' => $second,
											'party' => $arrpeople
										);
										$msg = JText::sprintf('VBSEARCHERRNOTENOUGHROOMS', implode(" - ", $errmess));
										// VBO 1.11 - push data to tracker and close
										VikBooking::getTracker()->pushMessage($msg, 'error')->closeTrack();
										//
										$this->setVboError($msg, $err_code_info);
									}
								} else {
									$err_code_info = array();
									if (strlen($restrictionerrmsg) > 0) {
										VikError::raiseWarning('', $restrictionerrmsg);
									} else {
										$err_code_info = array(
											'code' => 1,
											'fromts' => $first,
											'tots' => $second,
											'party' => $arrpeople
										);
									}
									$msg = JText::translate('VBNOROOMSINDATE');
									// VBO 1.11 - push data to tracker and close
									VikBooking::getTracker()->pushMessage($msg, 'error')->closeTrack();
									//
									$this->setVboError($msg, $err_code_info);
								}
							} else {
								$sayerr = JText::translate('VBNOROOMAVFOR') . " " . $daysdiff . " " . ($daysdiff > 1 ? JText::translate('VBDAYS') : JText::translate('VBDAY'));
								if (count($padults) == 1) {
									$sayerr .= ", ".$arrpeople[1]['adults']." ".($arrpeople[1]['adults'] > 1 ? JText::translate('VBSEARCHRESADULTS') : JText::translate('VBSEARCHRESADULT'));
									if ($arrpeople[1]['children'] > 0) {
										$sayerr .= ", ".$arrpeople[1]['children']." ".($arrpeople[1]['children'] > 1 ? JText::translate('VBSEARCHRESCHILDREN') : JText::translate('VBSEARCHRESCHILD'));
									}
								}
								$err_code_info = array(
									'code' => 2,
									'fromts' => $first,
									'tots' => $second,
									'party' => $arrpeople
								);
								// VBO 1.11 - push data to tracker and close
								VikBooking::getTracker()->pushMessage($sayerr, 'error')->closeTrack();
								//
								$this->setVboError($sayerr, $err_code_info);
							}
						} else {
							$this->setVboError($restrictionerrmsg);
						}
					} else {
						$session->set('vbcheckin', '');
						$session->set('vbcheckout', '');
						if ($first <= $actnow) {
							if (date('d/m/Y', $first) == date('d/m/Y', $actnow)) {
								$emess = JText::translate('VBSRCHERRCHKINPASSED');
							} else {
								$emess = JText::translate('VBSRCHERRCHKINPAST');
							}
						} else {
							$emess = JText::translate('VBPICKBRET');
						}
						$this->setVboError($emess);
					}
				} else {
					$this->setVboError(JText::translate('VBWRONGDF') . ": " . VikBooking::sayDateFormat());
				}
			} else {
				$this->setVboError(JText::translate('VBSELPRDATE'));
			}
		} else {
			echo VikBooking::getDisabledBookingMsg();
		}
	}

	protected function setVboError($err, $err_code_info = [])
	{
		$app = JFactory::getApplication();

		$ppkg_id = VikRequest::getInt('pkg_id', '', 'request');
		$pitemid = VikRequest::getInt('Itemid', '', 'request');

		if (!empty($ppkg_id)) {
			if (!empty($err)) {
				VikError::raiseWarning('', $err);
			}

			$app->redirect(JRoute::rewrite("index.php?option=com_vikbooking&view=packagedetails&pkgid=".$ppkg_id.(!empty($pitemid) ? "&Itemid=".$pitemid : ""), false));
			$app->close();
		} else {
			showSelectVb($err, $err_code_info);
		}
	}
}
