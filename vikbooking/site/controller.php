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

jimport('joomla.application.component.controller');

class VikBookingController extends JControllerVikBooking
{
	public function display($cachable = false, $urlparams = array())
	{
		$view = VikRequest::getVar('view', '');
		switch ($view) {
			case 'roomslist':
			case 'roomdetails':
			case 'searchdetails':
			case 'loginregister':
			case 'orderslist':
			case 'promotions':
			case 'availability':
			case 'packageslist':
			case 'packagedetails':
			case 'searchsuggestions':
			case 'booking':
			case 'operators':
			case 'tableaux':
			case 'precheckin':
			case 'revstay':
				VikRequest::setVar('view', $view);
				break;
			default:
				VikRequest::setVar('view', 'vikbooking');
		}
		parent::display();
	}

	public function search()
	{
		VikRequest::setVar('view', 'search');
		parent::display();
	}

	public function showprc()
	{
		VikRequest::setVar('view', 'showprc');
		parent::display();
	}

	public function oconfirm()
	{
		$requirelogin = VikBooking::requireLogin();
		if($requirelogin) {
			if(VikBooking::userIsLogged()) {
				VikRequest::setVar('view', 'oconfirm');
			} else {
				VikRequest::setVar('view', 'loginregister');
			}
		} else {
			VikRequest::setVar('view', 'oconfirm');
		}
		parent::display();
	}
	
	public function register()
	{
		$mainframe = JFactory::getApplication();
		$dbo = JFactory::getDBO();

		//user data
		$pname = VikRequest::getString('fname', '', 'request');
		$plname = VikRequest::getString('lname', '', 'request');
		$pemail = VikRequest::getString('email', '', 'request');
		$pusername = VikRequest::getString('username', '', 'request');
		$ppassword = VikRequest::getString('password', '', 'request');
		$pconfpassword = VikRequest::getString('confpassword', '', 'request');
		//
		//order data
		$pitemid = VikRequest::getString('Itemid', '', 'request');
		$proomid = VikRequest::getVar('roomid', array());
		$pdays = VikRequest::getInt('days', '', 'request');
		$pcheckin = VikRequest::getInt('checkin', '', 'request');
		$pcheckout = VikRequest::getInt('checkout', '', 'request');
		$proomsnum = VikRequest::getInt('roomsnum', '', 'request');
		$padults = VikRequest::getVar('adults', array());
		$pchildren = VikRequest::getVar('children', array());
		$rooms = array();
		$arrpeople = array();
		for($ir = 1; $ir <= $proomsnum; $ir++) {
			$ind = $ir - 1;
			if (!empty($proomid[$ind])) {
				$q = "SELECT * FROM `#__vikbooking_rooms` WHERE `id`='".intval($proomid[$ind])."' AND `avail`='1';";
				$dbo->setQuery($q);
				$dbo->execute();
				if ($dbo->getNumRows() > 0) {
					$takeroom = $dbo->loadAssocList();
					$rooms[$ir] = $takeroom[0];
				}
			}
			if (!empty($padults[$ind])) {
				$arrpeople[$ir]['adults'] = intval($padults[$ind]);
			} else {
				$arrpeople[$ir]['adults'] = 0;
			}
			if (!empty($pchildren[$ind])) {
				$arrpeople[$ir]['children'] = intval($pchildren[$ind]);
			} else {
				$arrpeople[$ir]['children'] = 0;
			}
		}
		$prices = array();
		foreach($rooms as $num => $r) {
			$ppriceid = VikRequest::getString('priceid'.$num, '', 'request');
			if (!empty($ppriceid)) {
				$prices[$num] = intval($ppriceid);
			}
		}
		$selopt = array();
		$q = "SELECT * FROM `#__vikbooking_optionals` ORDER BY `#__vikbooking_optionals`.`ordering` ASC;";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows() > 0) {
			$optionals = $dbo->loadAssocList();
			foreach ($rooms as $num => $r) {
				foreach ($optionals as $opt) {
					if (!empty($opt['ageintervals']) && $arrpeople[$num]['children'] > 0) {
						$tmpvar = VikRequest::getInt('optid'.$num.$opt['id'], []);
						if (is_array($tmpvar) && $tmpvar) {
							$optagenames = VikBooking::getOptionIntervalsAges($opt['ageintervals']);
							$optagepcent = VikBooking::getOptionIntervalsPercentage($opt['ageintervals']);
							$optageovrct = VikBooking::getOptionIntervalChildOverrides($opt, $arrpeople[$num]['adults'], $arrpeople[$num]['children']);
							$optorigname = $opt['name'];
							foreach ($tmpvar as $child_num => $chvar) {
								$opt['quan'] = $chvar;
								$opt['chageintv'] = $chvar;
								//ignore calculation as percetage value to reconstruct the URL
								$ageintervals_child_string = isset($optageovrct['ageintervals_child' . ($child_num + 1)]) ? $optageovrct['ageintervals_child' . ($child_num + 1)] : $opt['ageintervals'];
								$optagecosts = VikBooking::getOptionIntervalsCosts($ageintervals_child_string);
								$opt['cost'] = $optagecosts[($chvar - 1)];
								$opt['name'] = $optorigname.' ('.$optagenames[($chvar - 1)].')';
								$selopt[$num][] = $opt;
							}
						}
					} else {
						$tmpvar = VikRequest::getString('optid'.$num.$opt['id'], '', 'request');
						if (!empty($tmpvar)) {
							$opt['quan'] = $tmpvar;
							$selopt[$num][] = $opt;
						}
					}
				}
			}
		}
		$strpriceid = "";
		foreach($prices as $num => $pid) {
			$strpriceid .= ($num > 1 ? "&" : "")."priceid".$num."=".$pid;
		}
		$stroptid = "";
		for($ir = 1; $ir <= $proomsnum; $ir++) {
			if (isset($selopt[$ir]) && is_array($selopt[$ir])) {
				foreach($selopt[$ir] as $opt) {
					if (array_key_exists('chageintv', $opt)) {
						$stroptid .= "&optid".$ir.$opt['id']."[]=".$opt['chageintv'];
					} else {
						$stroptid .= "&optid".$ir.$opt['id']."=".$opt['quan'];
					}
				}
			}
		}
		$strroomid = "";
		foreach ($rooms as $num => $r) {
			$strroomid .= "&roomid[]=".$r['id'];
		}
		$straduchild = "";
		foreach ($arrpeople as $indroom => $aduch) {
			$straduchild .= "&adults[]=".$aduch['adults'];
			$straduchild .= "&children[]=".$aduch['children'];
		}
		
		$qstring = $strpriceid.$stroptid.$strroomid.$straduchild."&roomsnum=".$proomsnum."&days=".$pdays."&checkin=".$pcheckin."&checkout=".$pcheckout.(!empty($pitemid) ? "&Itemid=".$pitemid : "");
		//
		if (!VikBooking::userIsLogged()) {
			if (!empty($pname) && !empty($plname) && !empty($pusername) && !empty($pemail) && $ppassword == $pconfpassword) {
				//save user
				$newuserid=VikBooking::addJoomlaUser($pname." ".$plname, $pusername, $pemail, $ppassword);

				if ($newuserid!=false && strlen($newuserid)) {

					/**
					 * @wponly 	the return URL should be passed within the $option array of $app->login()
					 */
					$redirect_to = JRoute::rewrite('index.php?option=com_vikbooking&task=oconfirm&'.$qstring, false);

					//registration success
					$credentials = array('username' => $pusername, 'password' => $ppassword );
					//autologin
					$mainframe->login($credentials, array('redirect' => $redirect_to));
					$currentUser = JFactory::getUser();
					$currentUser->setLastVisit(time());
					$currentUser->set('guest', 0);
					//
					$mainframe->redirect($redirect_to);
				} else {
					//error while saving new user
					VikError::raiseWarning('', JText::translate('VBREGERRSAVING'));
					$mainframe->redirect(JRoute::rewrite('index.php?option=com_vikbooking&view=loginregister&'.$qstring, false));
				}
			} else {
				//invalid data
				VikError::raiseWarning('', JText::translate('VBREGERRINSDATA'));
				$mainframe->redirect(JRoute::rewrite('index.php?option=com_vikbooking&view=loginregister&'.$qstring, false));
			}
		} else {
			//user is already logged in, proceed
			$mainframe->redirect(JRoute::rewrite('index.php?option=com_vikbooking&task=oconfirm&'.$qstring, false));
		}
	}

	public function saveorder()
	{
		$dbo = JFactory::getDbo();
		$session = JFactory::getSession();
		$app = JFactory::getApplication();
		$vbo_tn = VikBooking::getTranslator();

		// availability helper
		$av_helper = VikBooking::getAvailabilityInstance();

		$prooms = VikRequest::getVar('rooms', array());
		$proomindex = VikRequest::getVar('roomindex', array());
		$proomsnum = VikRequest::getInt('roomsnum', 0, 'request');
		$padults = VikRequest::getVar('adults', array());
		$pchildren = VikRequest::getVar('children', array());
		$pdays = VikRequest::getInt('days', 0, 'request');
		$pcouponcode = VikRequest::getString('couponcode', '', 'request');
		$pcheckin = VikRequest::getInt('checkin', 0, 'request');
		$pcheckout = VikRequest::getInt('checkout', 0, 'request');
		$pprtar = VikRequest::getVar('prtar', array());
		$ppriceid = VikRequest::getVar('priceid', array());
		$poptionals = VikRequest::getString('optionals', '', 'request');
		$ptotdue = VikRequest::getString('totdue', '', 'request');
		$pgpayid = VikRequest::getString('gpayid', '', 'request');
		$ppkg_id = VikRequest::getInt('pkg_id', '', 'request');
		$pnodep = VikRequest::getInt('nodep', '', 'request');
		$split_stay = VikRequest::getVar('split_stay', array());
		$pitemid = VikRequest::getInt('Itemid', '', 'request');

		$validtoken = true;
		if (VikBooking::tokenForm()) {
			$validtoken = false;
			$pviktoken = VikRequest::getString('viktoken', '', 'request');
			$sessvbtkn = $session->get('vikbtoken', '');
			if (!empty($pviktoken) && $sessvbtkn == $pviktoken) {
				$session->set('vikbtoken', '');
				$validtoken = true;
			}
			if (!$validtoken) {
				$validtoken = JSession::checkToken();
			}
		}

		if (!$validtoken) {
			showSelectVb(JText::translate('VBINVALIDTOKEN'));
			return;
		}

		$q = "SELECT * FROM `#__vikbooking_custfields` ORDER BY `#__vikbooking_custfields`.`ordering` ASC;";
		$dbo->setQuery($q);
		$cfields = $dbo->loadAssocList();

		$suffdata = true;
		$useremail = "";
		$usercountry = '';
		$nominatives = [];
		$t_first_name = '';
		$t_last_name = '';
		$phone_number = '';
		$fieldflags = [];
		if ($cfields) {
			$vbo_tn->translateContents($cfields, '#__vikbooking_custfields');
			foreach ($cfields as $cf) {
				if (intval($cf['required']) == 1 && $cf['type'] != 'separator' && $cf['type'] != 'state') {
					$tmpcfval = VikRequest::getString('vbf' . $cf['id'], '', 'request');
					if (!strlen(str_replace(' ', '', trim($tmpcfval)))) {
						$suffdata = false;
						break;
					}
				}
			}
			//save user email, nominatives, phone number and create custdata array
			$arrcustdata = [];
			$arrcfields = [];
			$emailwasfound = false;
			foreach ($cfields as $cf) {
				$user_inp_val = VikRequest::getString('vbf' . $cf['id'], '', 'request');
				if (intval($cf['isemail']) == 1 && $emailwasfound == false) {
					$useremail = trim($user_inp_val);
					$emailwasfound = true;
				}
				if ($cf['isnominative'] == 1) {
					if (strlen(str_replace(' ', '', trim($user_inp_val)))) {
						$nominatives[] = $user_inp_val;
					}
				}
				if ($cf['isphone'] == 1) {
					if (strlen(str_replace(' ', '', trim($user_inp_val)))) {
						$phone_number = $user_inp_val;
					}
				}
				if (!empty($cf['flag'])) {
					if (strlen(str_replace(' ', '', trim($user_inp_val)))) {
						$fieldflags[$cf['flag']] = $user_inp_val;
					}
				}
				if ($cf['type'] != 'separator' && $cf['type'] != 'country' && ( $cf['type'] != 'checkbox' || ($cf['type'] == 'checkbox' && intval($cf['required']) != 1) ) ) {
					// check the input value to store for the customer raw information string
					$def_user_inp_val = $user_inp_val;
					// check for state/province field
					if ($cf['type'] == 'state' && strlen(str_replace(' ', '', trim($user_inp_val)))) {
						/**
						 * In order to assign the proper state/province to the customer,
						 * we treat this type of field as if it was a "field flag" type.
						 * 
						 * @since 	1.16.0 (J) - 1.6.0 (WP)
						 */
						$fieldflags['state'] = $user_inp_val;

						// attempt to save the full state name, not the 2-char code
						$def_user_inp_val = VBOStateHelper::getFullName($user_inp_val, $usercountry);
					}
					$arrcustdata[JText::translate($cf['name'])] = $def_user_inp_val;

					// store the original input value for this custom field ID
					$arrcfields[$cf['id']] = $user_inp_val;
				} elseif ($cf['type'] == 'country') {
					$countryval = $user_inp_val;
					if (!empty($countryval) && strstr($countryval, '::') !== false) {
						$countryparts = explode('::', $countryval);
						$usercountry = $countryparts[0];
						$arrcustdata[JText::translate($cf['name'])] = $countryparts[1];
					} else {
						$arrcustdata[JText::translate($cf['name'])] = '';
					}
				}
			}
		}
		if (!empty($phone_number) && !empty($usercountry)) {
			$phone_number = VikBooking::checkPhonePrefixCountry($phone_number, $usercountry);
		}

		if ($suffdata !== true) {
			showSelectVb(JText::translate('VBINSUFDATA'));
			return;
		}

		if (count($nominatives) >= 2) {
			$t_last_name = array_pop($nominatives);
			$t_first_name = array_pop($nominatives);
		}

		$secdiff = $pcheckout - $pcheckin;
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

		if (!VikBooking::dayValidTs($pdays, $pcheckin, $pcheckout) || $pdays != $daysdiff) {
			showSelectVb(JText::translate('VBINCONGRDATA'));
			return;
		}

		// get check-in and check-out dates information
		$checkin_info  = getdate($pcheckin);
		$checkout_info = getdate($pcheckout);

		/**
		 * Check split stay information.
		 * 
		 * @since 	1.16.0 (J) - 1.6.0 (WP)
		 */
		if (!empty($split_stay) && count($split_stay) == count($prooms) && count($split_stay) == $proomsnum && $proomsnum > 1) {
			// valid split stay request vars received
			$split_stay_checkins  = [];
			$split_stay_checkouts = [];
			$split_stay_nights 	  = [];
			foreach ($split_stay as $sps_k => $split_room) {
				// calculate and set the exact check-in and check-out timestamps for this split-room
				$room_checkin  = VikBooking::getDateTimestamp($split_room['checkin'], $checkin_info['hours'], $checkin_info['minutes'], $checkin_info['seconds']);
				$room_checkout = VikBooking::getDateTimestamp($split_room['checkout'], $checkout_info['hours'], $checkout_info['minutes'], $checkout_info['seconds']);
				$split_stay_checkins[]  = $room_checkin;
				$split_stay_checkouts[] = $room_checkout;
				// update split stay information
				$split_room['checkin_ts']  = $room_checkin;
				$split_room['checkout_ts'] = $room_checkout;
				$split_room['nights'] 	   = $av_helper->countNightsOfStay($room_checkin, $room_checkout);
				$split_stay_nights[] 	   = $split_room['nights'];
				$split_stay[$sps_k] 	   = $split_room;
			}
			// validate minimum and maximum stay dates for the split stay
			if (empty($split_stay_checkins) || empty($split_stay_checkouts)) {
				// error
				showSelectVb('Empty stay dates for split stay rooms');
				return;
			}
			if (array_sum($split_stay_nights) != $daysdiff) {
				showSelectVb('Invalid sum of total nights for split stay rooms');
				return;
			}
			if (min($split_stay_checkins) != $pcheckin) {
				// error
				showSelectVb('Invalid checkin stay date for split stay rooms');
				return;
			}
			if (max($split_stay_checkouts) != $pcheckout) {
				// error
				showSelectVb('Invalid checkout stay date for split stay rooms');
				return;
			}
		} else {
			// unset any possible value as it's invalid
			$split_stay = [];
		}

		$currencyname = VikBooking::getCurrencyName();
		$rooms = [];
		$prices = [];
		$arrpeople = [];
		for ($ir = 1; $ir <= $proomsnum; $ir++) {
			$ind = $ir - 1;
			if (!empty($prooms[$ind])) {
				$q = "SELECT * FROM `#__vikbooking_rooms` WHERE `id`=" . (int)$prooms[$ind] . " AND `avail`='1';";
				$dbo->setQuery($q);
				$rdata = $dbo->loadAssoc();
				if ($rdata) {
					$rooms[$ir] = $rdata;
				}
			}
			if (!empty($padults[$ind])) {
				$arrpeople[$ir]['adults'] = intval($padults[$ind]);
			} else {
				$arrpeople[$ir]['adults'] = 0;
			}
			if (!empty($pchildren[$ind])) {
				$arrpeople[$ir]['children'] = intval($pchildren[$ind]);
			} else {
				$arrpeople[$ir]['children'] = 0;
			}
			$arrpeople[$ir]['pets'] = 0;
			$prices[$ir] = intval($ppriceid[$ind]);
		}
		if (count($rooms) != $proomsnum) {
			VikError::raiseWarning('', JText::translate('VBROOMNOTFND'));
			$app->redirect(JRoute::rewrite('index.php?option=com_vikbooking'));
			exit;
		}
		$vbo_tn->translateContents($rooms, '#__vikbooking_rooms');

		// package
		$pkg = [];
		if (!empty($ppkg_id)) {
			$pkg = VikBooking::validateRoomPackage($ppkg_id, $rooms, $daysdiff, $pcheckin, $pcheckout);
			if (!is_array($pkg) || (is_array($pkg) && !(count($pkg) > 0)) ) {
				if (!is_array($pkg)) {
					VikError::raiseWarning('', $pkg);
				}
				$app->redirect(JRoute::rewrite("index.php?option=com_vikbooking&view=packagedetails&pkgid=".$ppkg_id.(!empty($pitemid) ? "&Itemid=".$pitemid : ""), false));
				exit;
			}
		}

		$tars = [];
		$validfares = true;
		foreach ($rooms as $num => $r) {
			if (count($pkg)) {
				break;
			}

			// determine the number of nights of stay and dates to consider
			$use_los = (int)$daysdiff;
			$room_checkin  = $pcheckin;
			$room_checkout = $pcheckout;
			if (!empty($split_stay) && !empty($split_stay[($num - 1)]) && $split_stay[($num - 1)]['idroom'] == $r['id']) {
				$use_los = (int)$split_stay[($num - 1)]['nights'];
				$room_checkin  = $split_stay[($num - 1)]['checkin_ts'];
				$room_checkout = $split_stay[($num - 1)]['checkout_ts'];
			}

			$q = "SELECT * FROM `#__vikbooking_dispcost` WHERE `idroom`=" . (int)$r['id'] . " AND `days`=" . $use_los . " AND `idprice`=" . $prices[$num];
			$dbo->setQuery($q, 0, 1);
			$dbo->execute();
			if (!$dbo->getNumRows()) {
				$validfares = false;
				break;
			}
			$tar = $dbo->loadAssocList();

			// apply seasonal rates
			$tar = VikBooking::applySeasonsRoom($tar, $room_checkin, $room_checkout);

			// apply OBP rules
			$tar = VBORoomHelper::getInstance()->applyOBPRules($tar, $r, $arrpeople[$num]['adults']);

			// push room tariffs
			$tars[$num] = $tar;
		}

		if ($validfares !== true) {
			showSelectVb(JText::translate('VBINCONGRDATAREC'));
			return;
		}

		$isdue = 0;
		$tot_taxes = 0;
		$tot_city_taxes = 0;
		$tot_fees = 0;
		$rooms_costs_map = [];
		$is_package = (bool)(count($pkg) > 0);
		if ($is_package === true) {
			foreach ($rooms as $num => $r) {
				$pkg_cost = $pkg['pernight_total'] == 1 ? ($pkg['cost'] * $daysdiff) : $pkg['cost'];
				$pkg_cost = $pkg['perperson'] == 1 ? ($pkg_cost * ($arrpeople[$num]['adults'] > 0 ? $arrpeople[$num]['adults'] : 1)) : $pkg_cost;
				$cost_plus_tax = VikBooking::sayPackagePlusIva($pkg_cost, $pkg['idiva']);
				$isdue += $cost_plus_tax;
				if ($cost_plus_tax == $pkg_cost) {
					$cost_minus_tax = VikBooking::sayPackageMinusIva($pkg_cost, $pkg['idiva']);
					$tot_taxes += ($pkg_cost - $cost_minus_tax);
				} else {
					$tot_taxes += ($cost_plus_tax - $pkg_cost);
				}
			}
		} else {
			foreach ($tars as $num => $tar) {
				$cost_plus_tax = VikBooking::sayCostPlusIva($tar[0]['cost'], $tar[0]['idprice']);
				$isdue += $cost_plus_tax;
				if ($cost_plus_tax == $tar[0]['cost']) {
					$cost_minus_tax = VikBooking::sayCostMinusIva($tar[0]['cost'], $tar[0]['idprice']);
					$tot_taxes += ($tar[0]['cost'] - $cost_minus_tax);
				} else {
					$tot_taxes += ($cost_plus_tax - $tar[0]['cost']);
				}
				$rooms_costs_map[$num] = $tar[0]['cost'];
			}
		}

		$selopt = [];
		$optstr = [];
		$children_age = [];
		if (!empty($poptionals)) {
			$stepo = explode(";", $poptionals);
			foreach ($stepo as $roptkey => $oo) {
				if (empty($oo)) {
					continue;
				}
				$stept = explode(":", $oo);
				$rnoid = explode("_", $stept[0]);
				$room_ind = $rnoid[0] - 1;

				$q = "SELECT * FROM `#__vikbooking_optionals` WHERE `id`=" . (int)$rnoid[1];
				$dbo->setQuery($q, 0, 1);
				$actopt = $dbo->loadAssocList();
				if (!$actopt) {
					continue;
				}
				$vbo_tn->translateContents($actopt, '#__vikbooking_optionals');

				// option params
				$opt_params = !empty($actopt[0]['oparams']) ? json_decode($actopt[0]['oparams'], true) : [];
				$opt_params = is_array($opt_params) ? $opt_params : [];

				// determine the number of nights of stay and dates to consider
				$use_los = (int)$daysdiff;
				$room_checkin  = $pcheckin;
				$room_checkout = $pcheckout;
				if (!empty($split_stay) && !empty($split_stay[$room_ind])) {
					$use_los = (int)$split_stay[$room_ind]['nights'];
					$room_checkin  = $split_stay[$room_ind]['checkin_ts'];
					$room_checkout = $split_stay[$room_ind]['checkout_ts'];
				}

				$chvar = '';
				if (!empty($actopt[0]['ageintervals']) && $arrpeople[$rnoid[0]]['children'] > 0 && strstr($stept[1], '-') != false) {
					$optagenames = VikBooking::getOptionIntervalsAges($actopt[0]['ageintervals']);
					$optagepcent = VikBooking::getOptionIntervalsPercentage($actopt[0]['ageintervals']);
					$optageovrct = VikBooking::getOptionIntervalChildOverrides($actopt[0], $arrpeople[$rnoid[0]]['adults'], $arrpeople[$rnoid[0]]['children']);
					$child_num 	 = VikBooking::getRoomOptionChildNumber($poptionals, $actopt[0]['id'], $roptkey, $arrpeople[$rnoid[0]]['children']);
					$optagecosts = VikBooking::getOptionIntervalsCosts(isset($optageovrct['ageintervals_child' . ($child_num + 1)]) ? $optageovrct['ageintervals_child' . ($child_num + 1)] : $actopt[0]['ageintervals']);
					$agestept = explode('-', $stept[1]);
					$stept[1] = $agestept[0];
					$chvar = $agestept[1];
					if (array_key_exists(($chvar - 1), $optagepcent) && $optagepcent[($chvar - 1)] == 1) {
						//percentage value of the adults tariff
						if ($is_package === true) {
							$optagecosts[($chvar - 1)] = ($pkg['pernight_total'] == 1 ? ($pkg['cost'] * $daysdiff) : $pkg['cost']) * $optagecosts[($chvar - 1)] / 100;
						} else {
							$optagecosts[($chvar - 1)] = $tars[$rnoid[0]][0]['cost'] * $optagecosts[($chvar - 1)] / 100;
						}
					} elseif (array_key_exists(($chvar - 1), $optagepcent) && $optagepcent[($chvar - 1)] == 2) {
						//VBO 1.10 - percentage value of room base cost
						if ($is_package === true) {
							$optagecosts[($chvar - 1)] = ($pkg['pernight_total'] == 1 ? ($pkg['cost'] * $daysdiff) : $pkg['cost']) * $optagecosts[($chvar - 1)] / 100;
						} else {
							$display_rate = isset($tars[$rnoid[0]][0]['room_base_cost']) ? $tars[$rnoid[0]][0]['room_base_cost'] : $tars[$rnoid[0]][0]['cost'];
							$optagecosts[($chvar - 1)] = $display_rate * $optagecosts[($chvar - 1)] / 100;
						}
					}
					$actopt[0]['chageintv'] = $chvar;
					$actopt[0]['name'] .= ' ('.$optagenames[($chvar - 1)].')';
					$actopt[0]['quan'] = $stept[1];
					$selopt[$rnoid[0]][] = $actopt[0];
					$selopt['room'.$rnoid[0]] = $selopt['room'.$rnoid[0]].$actopt[0]['id'].":".$stept[1]."-".$chvar.";";
					$realcost = (intval($actopt[0]['perday']) == 1 ? (floatval($optagecosts[($chvar - 1)]) * $use_los * $stept[1]) : (floatval($optagecosts[($chvar - 1)]) * $stept[1]));
					$children_age[$rnoid[0]][] = array('ageinterval' => $optagenames[($chvar - 1)], 'age' => '', 'cost' => $realcost);
				} else {
					$actopt[0]['quan'] = $stept[1];
					// VBO 1.11 - options percentage cost of the room total fee
					if ($is_package === true) {
						$deftar_basecosts = $pkg['pernight_total'] == 1 ? ($pkg['cost'] * $daysdiff) : $pkg['cost'];
					} else {
						$deftar_basecosts = $tars[$rnoid[0]][0]['cost'];
					}
					$actopt[0]['cost'] = (int)$actopt[0]['pcentroom'] ? ($deftar_basecosts * $actopt[0]['cost'] / 100) : $actopt[0]['cost'];
					//
					$selopt[$rnoid[0]][] = $actopt[0];
					if (!isset($selopt['room'.$rnoid[0]])) {
						$selopt['room'.$rnoid[0]] = '';
					}
					$selopt['room'.$rnoid[0]] .= $actopt[0]['id'] . ":" . $stept[1] . ";";
					$realcost = (intval($actopt[0]['perday']) == 1 ? ($actopt[0]['cost'] * $use_los * $stept[1]) : ($actopt[0]['cost'] * $stept[1]));
				}
				if (!empty($actopt[0]['maxprice']) && $actopt[0]['maxprice'] > 0 && $realcost > $actopt[0]['maxprice']) {
					$realcost = $actopt[0]['maxprice'];
					if (intval($actopt[0]['hmany']) == 1 && intval($stept[1]) > 1) {
						$realcost = $actopt[0]['maxprice'] * $stept[1];
					}
				}

				/**
				 * Count pets, if any.
				 * 
				 * @since 	1.16.2 (J) - 1.6.2 (WP)
				 */
				if ($opt_params && isset($opt_params['pet_fee']) && $opt_params['pet_fee']) {
					$tot_pets = 1;
					if ($actopt[0]['hmany'] > 0 && $stept[1] > 1) {
						$tot_pets = (int)$stept[1];
					}
					$arrpeople[$rnoid[0]]['pets'] = $tot_pets;
				}

				$realcost = ($actopt[0]['perperson'] == 1 ? ($realcost * $arrpeople[$rnoid[0]]['adults']) : $realcost);
				$opt_minus_iva = VikBooking::sayOptionalsMinusIva($realcost, $actopt[0]['idiva']);
				$tmpopr = VikBooking::sayOptionalsPlusIva($realcost, $actopt[0]['idiva']);
				if ($actopt[0]['is_citytax'] == 1) {
					$tot_city_taxes += $opt_minus_iva;
				} elseif ($actopt[0]['is_fee'] == 1) {
					$tot_fees += $opt_minus_iva;
				}
				// VBO 1.11 - always calculate the amount of tax no matter if this is already a tax or a fee
				if ($tmpopr == $realcost) {
					$tot_taxes += ($realcost - $opt_minus_iva);
				} else {
					$tot_taxes += ($tmpopr - $realcost);
				}
				//
				$isdue += $tmpopr;
				$optstr[$rnoid[0]][] = ($stept[1] > 1 ? $stept[1] . " " : "") . $actopt[0]['name'] . ": " . $tmpopr . " " . $currencyname . "\n";
			}
		}

		$origtotdue = $isdue;
		$usedcoupon = false;
		$strcouponeff = '';

		// access current customer
		$cpin = VikBooking::getCPinIstance();
		$customer_details = $cpin->loadCustomerDetails();

		// coupon
		if (strlen($pcouponcode) && $is_package !== true) {
			$coupon = VikBooking::getCouponInfo($pcouponcode);
			$valid_customer_coupon = true;
			if (!empty($coupon) && !empty($coupon['customers'])) {
				if (empty($customer_details['id']) || !in_array($customer_details['id'], $coupon['customers'])) {
					$valid_customer_coupon = false;
				}
			}
			if (!empty($coupon) && $valid_customer_coupon) {
				$coupondateok = true;
				if (strlen((string)$coupon['datevalid'])) {
					$dateparts = explode("-", $coupon['datevalid']);
					$pickinfo = getdate($pcheckin);
					$dropinfo = getdate($pcheckout);
					$checkpick = mktime(0, 0, 0, $pickinfo['mon'], $pickinfo['mday'], $pickinfo['year']);
					$checkdrop = mktime(0, 0, 0, $dropinfo['mon'], $dropinfo['mday'], $dropinfo['year']);
					if (!($checkpick >= $dateparts[0] && $checkpick <= $dateparts[1] && $checkdrop >= $dateparts[0] && $checkdrop <= $dateparts[1])) {
						$coupondateok = false;
					}
				}
				if (!empty($coupon['minlos']) && $coupon['minlos'] > $daysdiff) {
					$coupondateok = false;
				}
				if ($coupondateok) {
					$couponroomok = true;
					if (!$coupon['allvehicles']) {
						foreach ($rooms as $num => $r) {
							if (!(preg_match("/;".$r['id'].";/i", $coupon['idrooms']))) {
								$couponroomok = false;
								break;
							}
						}
					}
					if ($couponroomok) {
						$coupontotok = true;
						if (strlen((string)$coupon['mintotord'])) {
							if ($isdue < $coupon['mintotord']) {
								$coupontotok = false;
							}
						}
						if ($coupon['maxtotord'] > 0 && $isdue > $coupon['maxtotord']) {
							$coupontotok = false;
						}

						/**
						 * Trigger event to allow third-party plugins to implement additional coupon validations or manipulation.
						 * 
						 * @since 	1.16.7 (J) - 1.6.7 (WP)
						 */
						if ($coupontotok) {
							$coupon_validation = VBOFactory::getPlatform()->getDispatcher()->filter('onValidateCouponCode', [&$coupon]);
							if (is_array($coupon_validation) && in_array(false, $coupon_validation, true)) {
								$coupontotok = false;
							}
						}

						if ($coupontotok) {
							$usedcoupon = true;
							if ($coupon['percentot'] == 1) {
								// percent value
								$minuscoupon = 100 - $coupon['value'];
								/**
								 * We allow coupon codes to be applied on the entire reservation or as always just on the total minus mandatory taxes.
								 * 
								 * @since 	1.13.5 (J) - 1.3.5 (WP)
								 * @since 	1.14.3 (J) - 1.4.3 (WP) we also exclude the amount of taxes beside the mandatory fees.
								 * @since 	1.16.0 (J) - 1.6.0 (WP) taxes are proportionally calculated when coupon before tax.
								 * @since 	1.16.8 (J) - 1.6.8 (WP) with coupon before taxes, discounted amount calculation is an equal subtration.
								 */
								$prev_isdue = $isdue;
								$tot_net = ($isdue - $tot_taxes - $tot_city_taxes - $tot_fees);
								$coupondiscount = ($coupon['excludetaxes'] ? $tot_net : $isdue) * $coupon['value'] / 100;
								$isdue = ($coupon['excludetaxes'] ? $tot_net : $isdue) * $minuscoupon / 100;
								$tot_taxes = $coupon['excludetaxes'] ? ($tot_taxes * ($tot_net - $coupondiscount) / $tot_net) : $tot_taxes;
								$isdue += $coupon['excludetaxes'] ? ($tot_taxes + $tot_city_taxes + $tot_fees) : 0;
								$coupondiscount = abs($prev_isdue - $isdue);
							} else {
								// total value
								$coupondiscount = $coupon['value'];
								// isdue : taxes = coupon_discount : x
								$tax_prop = $tot_taxes * $coupon['value'] / $isdue;
								$tot_taxes -= $tax_prop;
								$tot_taxes = $tot_taxes < 0 ? 0 : $tot_taxes;
								$isdue -= $coupon['value'];
								$isdue = $isdue < 0 ? 0 : $isdue;
							}
							$strcouponeff = $coupon['id'].';'.$coupondiscount.';'.$coupon['code'];
						}
					}
				}
			}
		}

		$strisdue = number_format($isdue, 2) . 'vikbooking';
		$ptotdue = number_format($ptotdue, 2) . 'vikbooking';
		if ($strisdue != $ptotdue) {
			showSelectVb(JText::translate('VBINCONGRTOT'));
			return;
		}

		// pay full amount cookie (2 weeks)
		$nodep_set = !empty($pnodep) ? '1' : '0';
		$nodep_time_set = !empty($pnodep) ? (time() + (86400 * 14)) : (time() - (86400 * 14));
		$cookie = JFactory::getApplication()->input->cookie;
		VikRequest::setCookie('vboFA', $nodep_set, $nodep_time_set, '/');

		// modify booking
		$mod_booking = [];
		$skip_busy_ids = [];
		$cur_mod = $session->get('vboModBooking', '');
		if (is_array($cur_mod) && $cur_mod) {
			$mod_booking = $cur_mod;
			$skip_busy_ids = VikBooking::loadBookingBusyIds($mod_booking['id']);
		}

		$nowts = time();
		$checkts = $nowts;
		$today_bookings = VikBooking::todayBookings();
		if ($today_bookings) {
			$checkts = mktime(0, 0, 0, date('n'), date('j'), date('Y'));
		}
		if (!($checkts <= $pcheckin && $checkts < $pcheckout && $pcheckin < $pcheckout)) {
			showSelectVb(JText::translate('VBINVALIDDATES'));
			return;
		}

		$roomsavailable = true;
		foreach ($rooms as $num => $r) {
			// determine the number of nights of stay and dates to consider
			$use_los = (int)$daysdiff;
			$room_checkin  = $pcheckin;
			$room_checkout = $pcheckout;
			if (!empty($split_stay) && !empty($split_stay[($num - 1)]) && $split_stay[($num - 1)]['idroom'] == $r['id']) {
				$use_los = (int)$split_stay[($num - 1)]['nights'];
				$room_checkin  = $split_stay[($num - 1)]['checkin_ts'];
				$room_checkout = $split_stay[($num - 1)]['checkout_ts'];
			}

			if (!VikBooking::roomNotLocked($r['id'], $r['units'], $room_checkin, $room_checkout, true, $skip_busy_ids)) {
				$roomsavailable = false;
				break;
			}
		}
		if ($roomsavailable !== true) {
			showSelectVb(JText::translate('VBROOMBOOKEDBYOTHER'));
			return;
		}

		// save in session the checkin and checkout time of the reservation made
		$session->set('vikbooking_order_checkin', $pcheckin);
		$session->set('vikbooking_order_checkout', $pcheckout);

		// handle booking sid and customer information summary string
		$sid = $mod_booking ? $mod_booking['sid'] : VikBooking::getSecretLink();
		$custdata = VikBooking::buildCustData($arrcustdata, "\r\n");

		if (VBOPlatformDetection::isWordPress()) {
			$viklink = JURI::root() . "index.php?option=com_vikbooking&view=booking&sid=" . $sid . "&ts=" . $nowts . (!empty($pnodep) ? "&nodep=".$pnodep : "") . (!empty($pitemid) ? "&Itemid=" . $pitemid : "");
		} else {
			$bestitemid = VikBooking::findProperItemIdType(array('booking'));
			$viklink = VikBooking::externalroute("index.php?option=com_vikbooking&view=booking&sid=" . $sid . "&ts=" . $nowts . (!empty($pnodep) ? "&nodep=".$pnodep : ""), false, (!empty($bestitemid) ? $bestitemid : null));
		}

		$admail = VikBooking::getAdminMail();
		$ftitle = VikBooking::getFrontTitle();
		$pricestr = [];
		if ($is_package === true) {
			foreach ($rooms as $num => $r) {
				$pkg_cost = $pkg['pernight_total'] == 1 ? ($pkg['cost'] * $daysdiff) : $pkg['cost'];
				$pkg_cost = $pkg['perperson'] == 1 ? ($pkg_cost * ($arrpeople[$num]['adults'] > 0 ? $arrpeople[$num]['adults'] : 1)) : $pkg_cost;
				$cost_plus_tax = VikBooking::sayPackagePlusIva($pkg_cost, $pkg['idiva']);
				$pricestr[$num] = $pkg['name'].": ".$cost_plus_tax." ".$currencyname;
			}
		} else {
			foreach ($tars as $num => $tar) {
				$pricestr[$num] = VikBooking::getPriceName($tar[0]['idprice'], $vbo_tn) . ": " . VikBooking::sayCostPlusIva($tar[0]['cost'], $tar[0]['idprice'])  . " " . $currencyname . (!empty($tar[0]['attrdata']) ? "\n" . VikBooking::getPriceAttr($tar[0]['idprice'], $vbo_tn) . ": " . $tar[0]['attrdata'] : "");
			}
		}

		$currentUser = JFactory::getUser();
		$langtag = $vbo_tn->current_lang;
		$vcmchanneldata = $session->get('vcmChannelData', '');
		$vcmchanneldata = !empty($vcmchanneldata) && is_array($vcmchanneldata) && count($vcmchanneldata) > 0 ? $vcmchanneldata : '';

		// attempt to save customer
		$cpin->setCustomerExtraInfo($fieldflags);
		$cpin->saveCustomerDetails($t_first_name, $t_last_name, $useremail, $phone_number, $usercountry, $arrcfields);

		// collect all room IDs involved
		$rooms_involved = [];
		foreach ($rooms as $room_booked) {
			if (!in_array($room_booked['id'], $rooms_involved)) {
				$rooms_involved[] = $room_booked['id'];
			}
		}

		$must_payment = $mod_booking ? false : VikBooking::areTherePayments($rooms_involved);
		$payment = [];
		if ($must_payment) {
			$payment = VikBooking::getPayment($pgpayid);
		}
		if ($must_payment && empty($payment)) {
			// error, payment was not selected
			VikError::raiseWarning('', JText::translate('ERRSELECTPAYMENT'));

			// build redirect URI values
			$redirect_uri_vals = [
				'option' => 'com_vikbooking',
				'task' => 'oconfirm',
			];

			foreach ($prices as $num => $pid) {
				$redirect_uri_vals['priceid' . $num] = $pid;
			}

			for ($ir = 1; $ir <= $proomsnum; $ir++) {
				if (isset($selopt[$ir]) && is_array($selopt[$ir])) {
					foreach ($selopt[$ir] as $opt) {
						if (array_key_exists('chageintv', $opt)) {
							if (!isset($redirect_uri_vals['optid' . $ir . $opt['id']])) {
								$redirect_uri_vals['optid' . $ir . $opt['id']] = [];
							}
							$redirect_uri_vals['optid' . $ir . $opt['id']][] = $opt['chageintv'];
						} else {
							$redirect_uri_vals['optid' . $ir . $opt['id']] = $opt['quan'];
						}
					}
				}
			}

			$redirect_uri_vals['roomid'] = [];
			foreach ($rooms as $num => $r) {
				$redirect_uri_vals['roomid'][] = $r['id'];
			}

			$redirect_uri_vals['adults'] = [];
			$redirect_uri_vals['children'] = [];
			foreach ($arrpeople as $indroom => $aduch) {
				$redirect_uri_vals['adults'][] = $aduch['adults'];
				$redirect_uri_vals['children'][] = $aduch['children'];
			}

			$redirect_uri_vals['roomsnum'] = $proomsnum;
			$redirect_uri_vals['days'] = $pdays;
			$redirect_uri_vals['checkin'] = $pcheckin;
			$redirect_uri_vals['checkout'] = $pcheckout;
			if (!empty($split_stay)) {
				$redirect_uri_vals['split_stay'] = $split_stay;
			}
			$redirect_uri_vals['Itemid'] = !empty($pitemid) ? $pitemid : null;

			$app->redirect(JRoute::rewrite('index.php?' . http_build_query($redirect_uri_vals), false));
			exit;
		}

		// turnover seconds
		$turnover_secs = VikBooking::getHoursRoomAvail() * 3600;
		$realback = $turnover_secs + $pcheckout;

		// push data to tracker for conversion
		$vbo_tracker = VikBooking::getTracker();
		$vbo_tracker->pushDates($pcheckin, $pcheckout, $pdays)->pushParty($arrpeople)->pushData('idcustomer', $cpin->getNewCustomerId());

		if (!$mod_booking && ((!empty($payment) && intval($payment['setconfirmed']) == 1) || !$must_payment || ($usedcoupon && $isdue <= 0))) {
			// we enter this statement to set the booking to Confirmed when: no booking modification and, payment selected sets status to confirmed or no payments enabled or 100% coupon
			$arrbusy = [];
			foreach ($rooms as $num => $r) {
				// determine the number of nights of stay and dates to consider
				$room_checkin  = $pcheckin;
				$room_checkout = $pcheckout;
				$room_realback = $realback;
				if (!empty($split_stay) && !empty($split_stay[($num - 1)]) && $split_stay[($num - 1)]['idroom'] == $r['id']) {
					$room_checkin  = $split_stay[($num - 1)]['checkin_ts'];
					$room_checkout = $split_stay[($num - 1)]['checkout_ts'];
					$room_realback = $turnover_secs + $room_checkout;
				}

				$busy_record = new stdClass;
				$busy_record->idroom = $r['id'];
				$busy_record->checkin = $room_checkin;
				$busy_record->checkout = $room_checkout;
				$busy_record->realback = $room_realback;

				$dbo->insertObject('#__vikbooking_busy', $busy_record, 'id');

				if (!isset($busy_record->id)) {
					showSelectVb('Critical error while occupying the rooms. Please try again');
					return;
				}

				$arrbusy[$num] = $busy_record->id;
			}

			// store booking
			$booking_record = new stdClass;
			$booking_record->custdata = $custdata;
			$booking_record->ts = $nowts;
			$booking_record->status = 'confirmed';
			$booking_record->days = $pdays;
			$booking_record->checkin = $pcheckin;
			$booking_record->checkout = $pcheckout;
			$booking_record->custmail = $useremail;
			$booking_record->sid = $sid;
			$booking_record->idpayment = !empty($payment) ? ($payment['id'] . '=' . $payment['name']) : null;
			$booking_record->ujid = $currentUser->id;
			$booking_record->coupon = $usedcoupon === true ? $strcouponeff : null;
			$booking_record->roomsnum = count($rooms);
			$booking_record->total = (float)$isdue;
			$booking_record->channel = is_array($vcmchanneldata) && !empty($vcmchanneldata['name']) ? $vcmchanneldata['name'] : null;
			$booking_record->lang = $langtag;
			$booking_record->country = !empty($usercountry) ? $usercountry : null;
			$booking_record->tot_taxes = (float)$tot_taxes;
			$booking_record->tot_city_taxes = (float)$tot_city_taxes;
			$booking_record->tot_fees = (float)$tot_fees;
			$booking_record->phone = $phone_number;
			$booking_record->pkg = $is_package === true ? (int)$pkg['id'] : null;
			$booking_record->split_stay = !empty($split_stay) ? 1 : 0;

			$dbo->insertObject('#__vikbooking_orders', $booking_record, 'id');

			if (!isset($booking_record->id)) {
				showSelectVb('Critical error while saving the booking. Please try again');
				return;
			}
			$neworderid = $booking_record->id;

			// ConfirmationNumber
			$confirmnumber = VikBooking::generateConfirmNumber($neworderid, true);

			// assign room specific unit
			$set_room_indexes = (VikBooking::autoRoomUnit() || (count($proomindex) == count($rooms)));
			$room_indexes_usemap = [];
			$room_indexes_forcemap = [];

			foreach ($rooms as $num => $r) {
				$q = "INSERT INTO `#__vikbooking_ordersbusy` (`idorder`,`idbusy`) VALUES(" . (int)$neworderid . ", " . (int)$arrbusy[$num] . ");";
				$dbo->setQuery($q);
				$dbo->execute();
				$json_ch_age = '';
				if (array_key_exists($num, $children_age)) {
					$json_ch_age = json_encode($children_age[$num]);
				}
				// assign room specific unit
				$room_indexes = $set_room_indexes === true ? VikBooking::getRoomUnitNumsAvailable(array('id' => $neworderid, 'checkin' => $pcheckin, 'checkout' => $pcheckout), $r['id']) : array();
				$use_ind_key = 0;
				$force_rindex = 0;
				if ($room_indexes && isset($room_indexes_forcemap[$r['id']])) {
					// an index for this same room was forced already, reset the values
					foreach ($room_indexes as $av_key => $av_index) {
						if (in_array((int)$av_index, $room_indexes_forcemap[$r['id']])) {
							unset($room_indexes[$av_key]);
						}
					}
					$room_indexes = array_values($room_indexes);
				}
				if ($room_indexes) {
					if (count($proomindex) == count($rooms) && !empty($proomindex[($num - 1)])) {
						// exact distinctive feature index selected
						foreach ($room_indexes as $av_index) {
							if ((int)$av_index == (int)$proomindex[($num - 1)]) {
								// requested index is available
								$force_rindex = (int)$proomindex[($num - 1)];
								if (isset($room_indexes_forcemap[$r['id']]) && in_array($force_rindex, $room_indexes_forcemap[$r['id']])) {
									// cannot book the same unit twice
									$force_rindex = 0;
									continue;
								}
								break;
							}
						}
						if ($force_rindex) {
							// store the forced index for any possible equal room booked later in the same loop
							if (!isset($room_indexes_forcemap[$r['id']])) {
								$room_indexes_forcemap[$r['id']] = [];
							}
							array_push($room_indexes_forcemap[$r['id']], $force_rindex);
						}
					}
					if (!array_key_exists($r['id'], $room_indexes_usemap)) {
						$room_indexes_usemap[$r['id']] = $use_ind_key;
					} else {
						$use_ind_key = $room_indexes_usemap[$r['id']];
					}
					if (isset($room_indexes[$use_ind_key])) {
						$rooms[$num]['roomindex'] = (int)$room_indexes[$use_ind_key];
					}
				}
				//
				$pkg_cost = 0;
				if ($is_package === true) {
					$pkg_cost = $pkg['pernight_total'] == 1 ? ($pkg['cost'] * $daysdiff) : $pkg['cost'];
					$pkg_cost = $pkg['perperson'] == 1 ? ($pkg_cost * ($arrpeople[$num]['adults'] > 0 ? $arrpeople[$num]['adults'] : 1)) : $pkg_cost;
					// $pkg_cost = VikBooking::sayPackagePlusIva($pkg_cost, $pkg['idiva']);
				}
				
				$oroom_record = new stdClass;
				$oroom_record->idorder 		= (int)$neworderid;
				$oroom_record->idroom 		= (int)$r['id'];
				$oroom_record->adults 		= (int)$arrpeople[$num]['adults'];
				$oroom_record->children 	= (int)$arrpeople[$num]['children'];
				$oroom_record->pets 		= isset($arrpeople[$num]['pets']) ? (int)$arrpeople[$num]['pets'] : 0;
				$oroom_record->idtar 		= (int)$tars[$num][0]['id'];
				$oroom_record->optionals 	= isset($selopt['room'.$num]) ? $selopt['room'.$num] : null;
				$oroom_record->childrenage 	= (!empty($json_ch_age) ? $json_ch_age : null);
				$oroom_record->t_first_name = $t_first_name;
				$oroom_record->t_last_name 	= $t_last_name;
				$oroom_record->roomindex 	= null;
				if ($force_rindex) {
					$oroom_record->roomindex = $force_rindex;
				} elseif ($room_indexes && isset($room_indexes[$use_ind_key])) {
					$oroom_record->roomindex = (int)$room_indexes[$use_ind_key];
				}
				$oroom_record->pkg_id 		= ($is_package === true ? (int)$pkg['id'] : null);
				$oroom_record->pkg_name 	= ($is_package === true ? $pkg['name'] : null);
				$oroom_record->cust_cost 	= ($is_package === true ? $pkg_cost : null);
				$oroom_record->cust_idiva 	= ($is_package === true ? (int)$pkg['idiva'] : null);
				$oroom_record->room_cost 	= (array_key_exists($num, $rooms_costs_map) ? $rooms_costs_map[$num] : null);

				$dbo->insertObject('#__vikbooking_ordersrooms', $oroom_record, 'id');

				if ($room_indexes) {
					$room_indexes_usemap[$r['id']]++;
				}
			}

			if (!empty($split_stay)) {
				// save transient on db for split stay information
				VBOFactory::getConfig()->set('split_stay_' . $neworderid, json_encode($split_stay));
			}

			// customer booking
			$cpin->saveCustomerBooking($neworderid);

			if ($usedcoupon === true && $coupon['type'] == 2) {
				$q = "DELETE FROM `#__vikbooking_coupons` WHERE `id`='".$coupon['id']."';";
				$dbo->setQuery($q);
				$dbo->execute();
			}

			// check if some of the rooms booked have shared calendars
			VikBooking::updateSharedCalendars($neworderid, array(), $pcheckin, $pcheckout);

			// send email notification to guest and admin
			VikBooking::sendBookingEmail($neworderid, ['guest', 'admin']);

			//SMS
			VikBooking::sendBookingSMS($neworderid);

			//Booking History
			VikBooking::getBookingHistoryInstance()->setBid($neworderid)->store('NC', 'IP: '.VikRequest::getVar('REMOTE_ADDR', '', 'server'));

			//invoke VikChannelManager
			if (is_file(VCM_SITE_PATH . DIRECTORY_SEPARATOR . "helpers" . DIRECTORY_SEPARATOR . "synch.vikbooking.php")) {
				require_once(VCM_SITE_PATH . DIRECTORY_SEPARATOR . "helpers" . DIRECTORY_SEPARATOR . "synch.vikbooking.php");
				$vcm = new SynchVikBooking($neworderid);
				$vcm->setPushType('new')->sendRequest();
			}

			// VBO 1.11 - push data to tracker for conversion
			$vbo_tracker->pushData('idorder', $neworderid)->closeTrack();
			$vbo_tracker->resetTrack();

			$app->redirect(JRoute::rewrite("index.php?option=com_vikbooking&view=booking&sid=" . $sid . "&ts=" . $nowts . (!empty($pnodep) ? "&nodep=".$pnodep : "") . (!empty($pitemid) ? "&Itemid=" . $pitemid : ""), false));
		} elseif ($mod_booking) {
			// booking modification statement
			// get current orders-busy relations
			$old_busy_ids = [];
			$q = "SELECT `idbusy` FROM `#__vikbooking_ordersbusy` WHERE `idorder`=".(int)$mod_booking['id'].";";
			$dbo->setQuery($q);
			$getbusy = $dbo->loadAssocList();
			if ($getbusy) {
				foreach ($getbusy as $gbu) {
					array_push($old_busy_ids, $gbu['idbusy']);
				}
			}
			//remove current busy records
			if (count($old_busy_ids)) {
				$q = "DELETE FROM `#__vikbooking_busy` WHERE `id` IN (".implode(', ', $old_busy_ids).");";
				$dbo->setQuery($q);
				$dbo->execute();
			}
			$q = "DELETE FROM `#__vikbooking_ordersbusy` WHERE `idorder`=".(int)$mod_booking['id'].";";
			$dbo->setQuery($q);
			$dbo->execute();
			//get current rooms (for VCM and for composing the log)
			$q = "SELECT `or`.*,`r`.`name`,`r`.`idopt`,`r`.`units`,`r`.`fromadult`,`r`.`toadult` FROM `#__vikbooking_ordersrooms` AS `or`,`#__vikbooking_rooms` AS `r` WHERE `or`.`idorder`=".(int)$mod_booking['id']." AND `or`.`idroom`=`r`.`id` ORDER BY `or`.`id` ASC;";
			$dbo->setQuery($q);
			$dbo->execute();
			$old_ordersrooms = $dbo->loadAssocList();
			$mod_booking['rooms_info'] = $old_ordersrooms;
			//remove current rooms
			$q = "DELETE FROM `#__vikbooking_ordersrooms` WHERE `idorder`=".(int)$mod_booking['id'].";";
			$dbo->setQuery($q);
			$dbo->execute();
			//update the booking by creating first the new busy records
			$arrbusy = [];
			foreach ($rooms as $num => $r) {
				$q = "INSERT INTO `#__vikbooking_busy` (`idroom`,`checkin`,`checkout`,`realback`) VALUES(".(int)$r['id'].", ".$dbo->quote($pcheckin).", ".$dbo->quote($pcheckout).", ".$dbo->quote($realback).");";
				$dbo->setQuery($q);
				$dbo->execute();
				$lid = $dbo->insertid();
				$arrbusy[$num] = $lid;
			}
			// assign room specific unit
			$set_room_indexes = (VikBooking::autoRoomUnit() || (count($proomindex) == count($rooms)));
			$room_indexes_usemap = [];
			$room_indexes_forcemap = [];
			//create the new rooms and orders-busy relations
			foreach ($rooms as $num => $r) {
				$q = "INSERT INTO `#__vikbooking_ordersbusy` (`idorder`,`idbusy`) VALUES(".(int)$mod_booking['id'].", ".(int)$arrbusy[$num].");";
				$dbo->setQuery($q);
				$dbo->execute();
				$json_ch_age = '';
				if (array_key_exists($num, $children_age)) {
					$json_ch_age = json_encode($children_age[$num]);
				}
				// assign room specific unit
				$room_indexes = $set_room_indexes === true ? VikBooking::getRoomUnitNumsAvailable(array('id' => $mod_booking['id'], 'checkin' => $pcheckin, 'checkout' => $pcheckout), $r['id']) : array();
				$use_ind_key = 0;
				$force_rindex = 0;
				if ($room_indexes && isset($room_indexes_forcemap[$r['id']])) {
					// an index for this same room was forced already, reset the values
					foreach ($room_indexes as $av_key => $av_index) {
						if (in_array((int)$av_index, $room_indexes_forcemap[$r['id']])) {
							unset($room_indexes[$av_key]);
						}
					}
					$room_indexes = array_values($room_indexes);
				}
				if ($room_indexes) {
					if (count($proomindex) == count($rooms) && !empty($proomindex[($num - 1)])) {
						// exact distinctive feature index selected
						foreach ($room_indexes as $av_index) {
							if ((int)$av_index == (int)$proomindex[($num - 1)]) {
								// requested index is available
								$force_rindex = (int)$proomindex[($num - 1)];
								if (isset($room_indexes_forcemap[$r['id']]) && in_array($force_rindex, $room_indexes_forcemap[$r['id']])) {
									// cannot book the same unit twice
									$force_rindex = 0;
									continue;
								}
								break;
							}
						}
						if ($force_rindex) {
							// store the forced index for any possible equal room booked later in the same loop
							if (!isset($room_indexes_forcemap[$r['id']])) {
								$room_indexes_forcemap[$r['id']] = [];
							}
							array_push($room_indexes_forcemap[$r['id']], $force_rindex);
						}
					}
					if (!array_key_exists($r['id'], $room_indexes_usemap)) {
						$room_indexes_usemap[$r['id']] = $use_ind_key;
					} else {
						$use_ind_key = $room_indexes_usemap[$r['id']];
					}
					if (isset($room_indexes[$use_ind_key])) {
						$rooms[$num]['roomindex'] = (int)$room_indexes[$use_ind_key];
					}
				}
				//
				$pkg_cost = 0;
				if ($is_package === true) {
					$pkg_cost = $pkg['pernight_total'] == 1 ? ($pkg['cost'] * $daysdiff) : $pkg['cost'];
					$pkg_cost = $pkg['perperson'] == 1 ? ($pkg_cost * ($arrpeople[$num]['adults'] > 0 ? $arrpeople[$num]['adults'] : 1)) : $pkg_cost;
					// $pkg_cost = VikBooking::sayPackagePlusIva($pkg_cost, $pkg['idiva']);
				}

				$oroom_record = new stdClass;
				$oroom_record->idorder 		= (int)$mod_booking['id'];
				$oroom_record->idroom 		= (int)$r['id'];
				$oroom_record->adults 		= (int)$arrpeople[$num]['adults'];
				$oroom_record->children 	= (int)$arrpeople[$num]['children'];
				$oroom_record->pets 		= isset($arrpeople[$num]['pets']) ? (int)$arrpeople[$num]['pets'] : 0;
				$oroom_record->idtar 		= (int)$tars[$num][0]['id'];
				$oroom_record->optionals 	= isset($selopt['room'.$num]) ? $selopt['room'.$num] : null;
				$oroom_record->childrenage 	= (!empty($json_ch_age) ? $json_ch_age : null);
				$oroom_record->t_first_name = $t_first_name;
				$oroom_record->t_last_name 	= $t_last_name;
				$oroom_record->roomindex 	= null;
				if ($force_rindex) {
					$oroom_record->roomindex = $force_rindex;
				} elseif ($room_indexes && isset($room_indexes[$use_ind_key])) {
					$oroom_record->roomindex = (int)$room_indexes[$use_ind_key];
				}
				$oroom_record->pkg_id 		= ($is_package === true ? (int)$pkg['id'] : null);
				$oroom_record->pkg_name 	= ($is_package === true ? $pkg['name'] : null);
				$oroom_record->cust_cost 	= ($is_package === true ? $pkg_cost : null);
				$oroom_record->cust_idiva 	= ($is_package === true ? (int)$pkg['idiva'] : null);
				$oroom_record->room_cost 	= (array_key_exists($num, $rooms_costs_map) ? $rooms_costs_map[$num] : null);

				$dbo->insertObject('#__vikbooking_ordersrooms', $oroom_record, 'id');

				if ($room_indexes) {
					$room_indexes_usemap[$r['id']]++;
				}
			}

			// update the booking record (do not touch information like sid, confirmnumber, payment method etc..)
			$logmod = VikBooking::getLogBookingModification($mod_booking);
			$mod_notes = $logmod.(!empty($mod_booking['adminnotes']) ? "\n\n".$mod_booking['adminnotes'] : '');
			// if old total lower than new total, increment paymcount to allow a new payment (if configuration setting enabled)
			$mod_paymcount = (int)$mod_booking['paymcount'];
			if ($mod_booking['total'] < $isdue) {
				$mod_paymcount++;
			}

			$q = "UPDATE `#__vikbooking_orders` SET `custdata`=".$dbo->quote($custdata).",`ts`='".$nowts."',`days`=".$dbo->quote($pdays).",`checkin`=".$dbo->quote($pcheckin).",`checkout`=".$dbo->quote($pcheckout).",`custmail`=".$dbo->quote($useremail).",`ujid`='".$currentUser->id."',`coupon`=".($usedcoupon === true ? $dbo->quote($strcouponeff) : "NULL").",`roomsnum`='".count($rooms)."',`total`='".$isdue."',`channel`=".(is_array($vcmchanneldata) ? $dbo->quote($vcmchanneldata['name']) : (!empty($mod_booking['channel']) ? $dbo->quote($mod_booking['channel']) : 'NULL')).",`paymcount`=".$mod_paymcount.",`adminnotes`=".$dbo->quote($mod_notes).",`lang`=".$dbo->quote($langtag).",`country`=".(!empty($usercountry) ? $dbo->quote($usercountry) : 'NULL').",`tot_taxes`='".$tot_taxes."',`tot_city_taxes`='".$tot_city_taxes."',`tot_fees`='".$tot_fees."',`phone`=".$dbo->quote($phone_number).",`pkg`=".($is_package === true ? (int)$pkg['id'] : "NULL")." WHERE `id`=".(int)$mod_booking['id'].";";
			$dbo->setQuery($q);
			$dbo->execute();

			// remove the coupon used (should never been allowed for modifications)
			if ($usedcoupon == true && $coupon['type'] == 2) {
				$q = "DELETE FROM `#__vikbooking_coupons` WHERE `id`=".(int)$coupon['id'].";";
				$dbo->setQuery($q);
				$dbo->execute();
			}

			// unset any previously booked room due to calendar sharing (should not be necessary because busy records have already been purged)
			VikBooking::cleanSharedCalendarsBusy($mod_booking['id']);
			// check if some of the rooms booked have shared calendars
			VikBooking::updateSharedCalendars($mod_booking['id'], array(), $pcheckin, $pcheckout);

			//send email messages (admin and customer) and invoke SMS send
			VikBooking::sendBookingEmail($mod_booking['id'], array('guest', 'admin'), true, false, $type = 'modified');

			//SMS
			VikBooking::sendBookingSMS($mod_booking['id']);

			//Booking History
			VikBooking::getBookingHistoryInstance()->setBid($mod_booking['id'])->store('MW', $logmod);

			//invoke VikChannelManager
			if (is_file(VCM_SITE_PATH . DIRECTORY_SEPARATOR . "helpers" . DIRECTORY_SEPARATOR . "synch.vikbooking.php")) {
				$vcm_obj = VikBooking::getVcmInvoker();
				$vcm_obj->setOids(array($mod_booking['id']))->setSyncType('modify')->setOriginalBooking($mod_booking);
				$vcm_obj->doSync();
			}

			//unset the session value
			$session->set('vboModBooking', '');

			// VBO 1.11 - push data to tracker for conversion
			$vbo_tracker->pushData('idorder', $mod_booking['id'])->pushMessage(JText::translate('VBOBOOKINGMODOK'))->closeTrack();
			$vbo_tracker->resetTrack();

			$app->enqueueMessage(JText::translate('VBOBOOKINGMODOK'));
			$app->redirect(JRoute::rewrite("index.php?option=com_vikbooking&view=booking&sid=" . $sid . "&ts=" . $nowts . (!empty($pnodep) ? "&nodep=".$pnodep : "") . (!empty($pitemid) ? "&Itemid=" . $pitemid : ""), false));
		} else {
			// booking must have status stand-by and proceed to the payment
			$booking_record = new stdClass;
			$booking_record->custdata = $custdata;
			$booking_record->ts = $nowts;
			$booking_record->status = 'standby';
			$booking_record->days = $pdays;
			$booking_record->checkin = $pcheckin;
			$booking_record->checkout = $pcheckout;
			$booking_record->custmail = $useremail;
			$booking_record->sid = $sid;
			$booking_record->idpayment = !empty($payment) ? ($payment['id'] . '=' . $payment['name']) : null;
			$booking_record->ujid = $currentUser->id;
			$booking_record->coupon = $usedcoupon === true ? $strcouponeff : null;
			$booking_record->roomsnum = count($rooms);
			$booking_record->total = (float)$isdue;
			$booking_record->channel = is_array($vcmchanneldata) && !empty($vcmchanneldata['name']) ? $vcmchanneldata['name'] : null;
			$booking_record->lang = $langtag;
			$booking_record->country = !empty($usercountry) ? $usercountry : null;
			$booking_record->tot_taxes = (float)$tot_taxes;
			$booking_record->tot_city_taxes = (float)$tot_city_taxes;
			$booking_record->tot_fees = (float)$tot_fees;
			$booking_record->phone = $phone_number;
			$booking_record->pkg = $is_package === true ? (int)$pkg['id'] : null;
			$booking_record->split_stay = !empty($split_stay) ? 1 : 0;

			$dbo->insertObject('#__vikbooking_orders', $booking_record, 'id');

			if (!isset($booking_record->id)) {
				showSelectVb('Critical error while saving the booking. Please try again');
				return;
			}
			$neworderid = $booking_record->id;

			$room_indexes_forcemap = [];
			foreach ($rooms as $num => $r) {
				$json_ch_age = '';
				if (array_key_exists($num, $children_age)) {
					$json_ch_age = json_encode($children_age[$num]);
				}

				$pkg_cost = 0;
				if ($is_package === true) {
					$pkg_cost = $pkg['pernight_total'] == 1 ? ($pkg['cost'] * $daysdiff) : $pkg['cost'];
					$pkg_cost = $pkg['perperson'] == 1 ? ($pkg_cost * ($arrpeople[$num]['adults'] > 0 ? $arrpeople[$num]['adults'] : 1)) : $pkg_cost;
					// $pkg_cost = VikBooking::sayPackagePlusIva($pkg_cost, $pkg['idiva']);
				}
				
				$oroom_record = new stdClass;
				$oroom_record->idorder 		= (int)$neworderid;
				$oroom_record->idroom 		= (int)$r['id'];
				$oroom_record->adults 		= (int)$arrpeople[$num]['adults'];
				$oroom_record->children 	= (int)$arrpeople[$num]['children'];
				$oroom_record->pets 		= isset($arrpeople[$num]['pets']) ? (int)$arrpeople[$num]['pets'] : 0;
				$oroom_record->idtar 		= (int)$tars[$num][0]['id'];
				$oroom_record->optionals 	= isset($selopt['room'.$num]) ? $selopt['room'.$num] : null;
				$oroom_record->childrenage 	= (!empty($json_ch_age) ? $json_ch_age : null);
				$oroom_record->t_first_name = $t_first_name;
				$oroom_record->t_last_name 	= $t_last_name;
				$oroom_record->roomindex 	= null;
				if (count($proomindex) == count($rooms) && !empty($proomindex[($num - 1)])) {
					// check if the sub-unit requested is available
					if (!isset($room_indexes_forcemap[$r['id']])) {
						$room_indexes_forcemap[$r['id']] = [];
					}
					$room_indexes = VikBooking::getRoomUnitNumsAvailable(array('id' => $neworderid, 'checkin' => $pcheckin, 'checkout' => $pcheckout), $r['id']);
					$force_rindex = 0;
					foreach ($room_indexes as $av_index) {
						if ((int)$av_index == (int)$proomindex[($num - 1)] && !in_array((int)$proomindex[($num - 1)], $room_indexes_forcemap[$r['id']])) {
							// requested index is available
							$force_rindex = (int)$proomindex[($num - 1)];
							array_push($room_indexes_forcemap[$r['id']], $force_rindex);
							break;
						}
					}
					if (!empty($force_rindex)) {
						$oroom_record->roomindex = $force_rindex;
					}
				}
				$oroom_record->pkg_id 		= ($is_package === true ? (int)$pkg['id'] : null);
				$oroom_record->pkg_name 	= ($is_package === true ? $pkg['name'] : null);
				$oroom_record->cust_cost 	= ($is_package === true ? $pkg_cost : null);
				$oroom_record->cust_idiva 	= ($is_package === true ? (int)$pkg['idiva'] : null);
				$oroom_record->room_cost 	= (array_key_exists($num, $rooms_costs_map) ? $rooms_costs_map[$num] : null);

				$dbo->insertObject('#__vikbooking_ordersrooms', $oroom_record, 'id');
			}
			
			if ($usedcoupon === true && $coupon['type'] == 2) {
				$q = "DELETE FROM `#__vikbooking_coupons` WHERE `id`=" . (int)$coupon['id'] . ";";
				$dbo->setQuery($q);
				$dbo->execute();
			}

			// lock rooms waiting to be confirmed
			$lock_until_ts = VikBooking::getMinutesLock(true);
			foreach ($rooms as $num => $r) {
				// determine the number of nights of stay and dates to consider
				$room_checkin  = $pcheckin;
				$room_checkout = $pcheckout;
				$room_realback = $realback;
				if (!empty($split_stay) && !empty($split_stay[($num - 1)]) && $split_stay[($num - 1)]['idroom'] == $r['id']) {
					$room_checkin  = $split_stay[($num - 1)]['checkin_ts'];
					$room_checkout = $split_stay[($num - 1)]['checkout_ts'];
					$room_realback = $turnover_secs + $room_checkout;
				}

				$tmp_lock_record = new stdClass;
				$tmp_lock_record->idroom = $r['id'];
				$tmp_lock_record->checkin = $room_checkin;
				$tmp_lock_record->checkout = $room_checkout;
				$tmp_lock_record->until = $lock_until_ts;
				$tmp_lock_record->realback = $room_realback;
				$tmp_lock_record->idorder = (int)$neworderid;

				$dbo->insertObject('#__vikbooking_tmplock', $tmp_lock_record, 'id');
			}

			if (!empty($split_stay)) {
				// save transient on db for split stay information
				VBOFactory::getConfig()->set('split_stay_' . $neworderid, json_encode($split_stay));
			}

			// Customer Booking
			$cpin->saveCustomerBooking($neworderid);

			// send email notification to guest and admin
			VikBooking::sendBookingEmail($neworderid, ['guest', 'admin']);

			//SMS
			VikBooking::sendBookingSMS($neworderid);

			//Booking History
			VikBooking::getBookingHistoryInstance()->setBid($neworderid)->store('NP', 'IP: ' . VikRequest::getVar('REMOTE_ADDR', '', 'server'));

			/**
			 * Invoke VikChannelManager also in case of pending reservations.
			 * 
			 * @since 		1.16.5 (J) - 1.6.5 (WP)
			 * 
			 * @requires 	VCM >= 1.8.20
			 */
			if (class_exists('VCMRequestAvailability')) {
				VikBooking::getVcmInvoker()
					->setOids([$neworderid])
					->setSyncType('new')
					->doSync();
			}

			// VBO 1.11 - push data to tracker for conversion
			$vbo_tracker->pushData('idorder', $neworderid)->closeTrack();
			$vbo_tracker->resetTrack();

			$app->redirect(JRoute::rewrite("index.php?option=com_vikbooking&view=booking&sid=" . $sid . "&ts=" . $nowts . (!empty($pnodep) ? "&nodep=".$pnodep : "") . (!empty($pitemid) ? "&Itemid=" . $pitemid : ""), false));
		}
	}

	public function vieworder()
	{
		VikRequest::setVar('view', 'booking');
		parent::display();
	}
	
	public function cancelrequest()
	{
		if (!JSession::checkToken()) {
			// missing CSRF-proof token
			VBOHttpDocument::getInstance()->close(403, JText::translate('JINVALID_TOKEN'));
		}

		$dbo = JFactory::getDbo();
		$mainframe = JFactory::getApplication();

		$psid = VikRequest::getString('sid', '', 'request');
		$pidorder = VikRequest::getString('idorder', '', 'request');

		if (!empty($psid) && !empty($pidorder)) {
			$q = "SELECT * FROM `#__vikbooking_orders` WHERE `id`=".intval($pidorder)." AND `sid`=".$dbo->quote($psid).";";
			$dbo->setQuery($q);
			$order = $dbo->loadAssocList();
			if ($order) {
				$pemail = VikRequest::getString('email', '', 'request');
				$preason = VikRequest::getString('reason', '', 'request');
				if (!empty($pemail) && !empty($preason)) {
					$to = VikBooking::getAdminMail();
					if(strpos($to, ',') !== false) {
						$all_recipients = explode(',', $to);
						foreach ($all_recipients as $k => $v) {
							if(empty($v)) {
								unset($all_recipients[$k]);
							}
						}
						if(count($all_recipients) > 0) {
							$to = $all_recipients;
						}
					}
					//Booking History
					VikBooking::getBookingHistoryInstance()->setBid($order[0]['id'])->store('CR', $pemail."\n".$preason);
					//
					$subject = JText::translate('VBCANCREQUESTEMAILSUBJ') . ' #' . $order[0]['id'];
					// @wponly 	we do not need to pass the "best item ID" to externalroute()
					$uri = VikBooking::externalroute("index.php?option=com_vikbooking&view=booking&sid=" . $order[0]['sid'] . "&ts=" . $order[0]['ts'], false);
					$msg = JText::sprintf('VBCANCREQUESTEMAILHEAD', $order[0]['id'], $uri)."\n\n".$preason;
					$vbo_app = VikBooking::getVboApplication();
					$adsendermail = VikBooking::getSenderMail();
					$vbo_app->sendMail($adsendermail, $adsendermail, $to, $pemail, $subject, $msg, false);
					$mainframe->enqueueMessage(JText::translate('VBCANCREQUESTMAILSENT'));
					$mainframe->redirect(JRoute::rewrite("index.php?option=com_vikbooking&view=booking&sid=".$order[0]['sid']."&ts=".$order[0]['ts']."&Itemid=".VikRequest::getString('Itemid', '', 'request'), false));
				} else {
					$mainframe->redirect(JRoute::rewrite("index.php?option=com_vikbooking&view=booking&sid=".$order[0]['sid']."&ts=".$order[0]['ts'], false));
				}
			} else {
				$mainframe->redirect("index.php");
			}
		} else {
			$mainframe->redirect("index.php");
		}
	}

	public function reqinfo()
	{
		$proomid = VikRequest::getInt('roomid', '', 'request');
		$preqinfotoken = VikRequest::getInt('reqinfotoken', '', 'request');
		$pitemid = VikRequest::getInt('Itemid', '', 'request');
		$dbo = JFactory::getDBO();
		$session = JFactory::getSession();
		$mainframe = JFactory::getApplication();
		$vbo_app = VikBooking::getVboApplication();
		if (!empty($proomid)) {
			$q = "SELECT `id`,`name` FROM `#__vikbooking_rooms` WHERE `id`=".(int)$proomid.";";
			$dbo->setQuery($q);
			$dbo->execute();
			if ($dbo->getNumRows() == 1) {
				$room = $dbo->loadAssocList();
				$goto = JRoute::rewrite('index.php?option=com_vikbooking&view=roomdetails&roomid='.$room[0]['id'].'&Itemid='.$pitemid, false);
				$preqname = VikRequest::getString('reqname', '', 'request');
				$preqemail = VikRequest::getString('reqemail', '', 'request');
				$preqmess = VikRequest::getString('reqmess', '', 'request');
				if (!empty($preqemail) && !empty($preqmess)) {
					/**
					 * captcha verification
					 * 
					 * @since 	1.2.3
					 */
					if ($vbo_app->isCaptcha() && !$vbo_app->reCaptcha('check')) {
						VikError::raiseWarning('', 'Invalid Captcha');
						$mainframe->redirect($goto);
						exit;
					}
					//
					$sesstoken = $session->get('vboreqinfo'.$room[0]['id'], '');
					if((int)$sesstoken == (int)$preqinfotoken) {
						$session->set('vboreqinfo'.$room[0]['id'], '');
						$to = VikBooking::getAdminMail();
						if(strpos($to, ',') !== false) {
							$all_recipients = explode(',', $to);
							foreach ($all_recipients as $k => $v) {
								if(empty($v)) {
									unset($all_recipients[$k]);
								}
							}
							if(count($all_recipients) > 0) {
								$to = $all_recipients;
							}
						}
						$subject = JText::sprintf('VBOROOMREQINFOSUBJ', $room[0]['name']);
						$msg = JText::translate('VBOROOMREQINFONAME').": ".$preqname."\n\n".JText::translate('VBOROOMREQINFOEMAIL').": ".$preqemail."\n\n".JText::translate('VBOROOMREQINFOMESS').":\n\n".$preqmess;
						$adsendermail = VikBooking::getSenderMail();
						$vbo_app->sendMail($adsendermail, $adsendermail, $to, $preqemail, $subject, $msg, false);
						$mainframe->enqueueMessage(JText::translate('VBOROOMREQINFOSENTOK'));
					} else {
						VikError::raiseWarning('', JText::translate('VBOROOMREQINFOTKNERR'));
					}
					$mainframe->redirect($goto);
				} else {
					VikError::raiseWarning('', JText::translate('VBOROOMREQINFOMISSFIELD'));
					$mainframe->redirect($goto);
				}
			} else {
				$mainframe->redirect("index.php");
			}
		} else {
			$mainframe->redirect("index.php");
		}
	}

	public function cron_exec()
	{
		if (VBOPlatformDetection::isWordPress())
		{
			// in WordPress it is no more needed to schedule a server cron job
			VBOHttpDocument::getInstance()->close(406, 'Cron jobs execution is scheduled by WordPress since VikBooking 1.5.10. Please remove any scheduled execution to this end-point.');
		}

		$app = JFactory::getApplication();

		$id_cron = $app->input->getUint('cron_id', 0);
		$key     = $app->input->getString('cronkey', '');

		$model = VBOMvcModel::getInstance('cronjob');

		// dispatch the cron job by injecting the cron key within the
		// configuration array, in order to make sure that the execution
		// of the job has been requested by a reliable caller
		$response = $model->dispatch($id_cron, ['key' => $key]);

		if ($response === false)
		{
			// an error has occurred
			$error = $model->getError();

			if (!$error instanceof Exception)
			{
				// wrap error message in an exception for a better ease of use
				$error = new Exception($error ?: 'Error', 500);
			}
			
			// terminate session with an error
			VBOHttpDocument::getInstance($app)->close($error->getCode(), $error->getMessage());
		}

		// display response code and teminate the session
		echo $response;
		$app->close();
	}
	
	public function notifypayment()
	{
		$dbo = JFactory::getDbo();

		$config = VBOFactory::getConfig();
		$av_helper = VikBooking::getAvailabilityInstance();

		$psid = VikRequest::getString('sid', '', 'request');
		$pts = VikRequest::getString('ts', '', 'request');

		$nowdf = VikBooking::getDateFormat();
		if ($nowdf == "%d/%m/%Y") {
			$df = 'd/m/Y';
		} elseif ($nowdf == "%m/%d/%Y") {
			$df = 'm/d/Y';
		} else {
			$df = 'Y/m/d';
		}

		if (!strlen($psid) || !strlen($pts)) {
			VBOHttpDocument::getInstance()->close(500, 'Missing information for fetching the booking');
		}

		$admail = VikBooking::getAdminMail();
		$recipient_mail = $admail;
		if (!is_array($recipient_mail) && strpos($recipient_mail, ',') !== false) {
			$all_recipients = explode(',', $recipient_mail);
			foreach ($all_recipients as $k => $v) {
				if (empty($v)) {
					unset($all_recipients[$k]);
				}
			}
			if (count($all_recipients) > 0) {
				$recipient_mail = $all_recipients;
			}
		}

		$q = "SELECT * FROM `#__vikbooking_orders` WHERE (`sid`=" . $dbo->quote($psid) . " OR `idorderota`=" . $dbo->quote($psid) . ") AND `ts`=" . $dbo->quote($pts);
		$dbo->setQuery($q, 0, 1);
		$dbo->execute();
		if (!$dbo->getNumRows()) {
			VBOHttpDocument::getInstance()->close(404, 'Booking not found');
		}

		// load booking details
		$row = $dbo->loadAssoc();

		// check if the language in use is the same as the one used during the checkout
		if (!empty($row['lang'])) {
			$lang = JFactory::getLanguage();
			if ($lang->getTag() != $row['lang']) {
				$lang->load('com_vikbooking', (VBOPlatformDetection::isWordPress() ? VIKBOOKING_SITE_LANG : JPATH_SITE), $row['lang'], true);
				if (VBOPlatformDetection::isJoomla()) {
					$lang->load('joomla', JPATH_SITE, $row['lang'], true);
				}
			}
		}

		// translator
		$vbo_tn = VikBooking::getTranslator();

		if ($row['status'] == 'confirmed' && !(VikBooking::multiplePayments() && $row['paymcount'] > 0)) {
			// booking can be paid only if not confirmed or if multiple payments are enabled and payment counter for booking greater than zero
			VBOHttpDocument::getInstance()->close(409, 'Conflicting and unexpected payment validation for this reservation');
		}

		/**
		 * Check split stay reservation data.
		 * 
		 * @since 	1.16.0 (J) - 1.6.0 (WP)
		 */
		$split_stay = [];
		if ($row['split_stay'] && $row['status'] != 'confirmed') {
			// check for transient on DB
			$split_stay = $config->getArray('split_stay_' . $row['id'], []);
		}

		// inject admin email
		$row['admin_email'] = $admail;

		// turnover seconds
		$turnover_secs = VikBooking::getHoursRoomAvail() * 3600;
		$realback = $turnover_secs + $row['checkout'];

		$currencyname = VikBooking::getCurrencyName();
		$ftitle = VikBooking::getFrontTitle();
		$nowts = time();

		$rooms = [];
		$tars = [];
		$arrpeople = [];
		$is_package = (bool)(!empty($row['pkg']));

		// load booked rooms
		$q = "SELECT `or`.`id` AS `or_id`,`or`.`idroom`,`or`.`adults`,`or`.`children`,`or`.`idtar`,`or`.`optionals`,`or`.`roomindex`,`or`.`pkg_id`,`or`.`pkg_name`,`or`.`cust_cost`,`or`.`cust_idiva`,`or`.`extracosts`,`or`.`otarplan`,`r`.`id` AS `r_reference_id`,`r`.`name`,`r`.`img`,`r`.`idcarat`,`r`.`fromadult`,`r`.`toadult`,`r`.`params` FROM `#__vikbooking_ordersrooms` AS `or`,`#__vikbooking_rooms` AS `r` WHERE `or`.`idorder`=" . $row['id'] . " AND `or`.`idroom`=`r`.`id` ORDER BY `or`.`id` ASC;";
		$dbo->setQuery($q);
		$orderrooms = $dbo->loadAssocList();
		if ($orderrooms) {
			$vbo_tn->translateContents($orderrooms, '#__vikbooking_rooms', array('id' => 'r_reference_id'));
			foreach ($orderrooms as $kor => $or) {
				$num = $kor + 1;
				$rooms[$num] = $or;
				$arrpeople[$num]['adults'] = $or['adults'];
				$arrpeople[$num]['children'] = $or['children'];
				if ($is_package === true || (!empty($or['cust_cost']) && $or['cust_cost'] > 0.00)) {
					// package or custom cost set from the back-end
					continue;
				}

				// determine the number of nights of stay and dates to consider
				$use_los = $row['days'];
				$room_checkin  = $row['checkin'];
				$room_checkout = $row['checkout'];
				if (!empty($split_stay) && !empty($split_stay[$kor]) && $split_stay[$kor]['idroom'] == $or['idroom']) {
					$use_los = (int)$split_stay[$kor]['nights'];
					$room_checkin  = $split_stay[$kor]['checkin_ts'];
					$room_checkout = $split_stay[$kor]['checkout_ts'];
				}

				$q = "SELECT * FROM `#__vikbooking_dispcost` WHERE `id`=" . (int)$or['idtar'];
				$dbo->setQuery($q, 0, 1);
				$tar = $dbo->loadAssocList();
				if (!$tar) {
					continue;
				}

				$tar = VikBooking::applySeasonsRoom($tar, $room_checkin, $room_checkout);

				// apply OBP rules
				$tar = VBORoomHelper::getInstance()->applyOBPRules($tar, $or, $or['adults']);

				// push tariff
				$tars[$num] = $tar[0];
			}
		}

		// inject values
		$row['order_rooms'] = $orderrooms;
		$row['fares'] = $tars;

		$isdue = 0;
		$tot_taxes = 0;
		$tot_city_taxes = 0;
		$tot_fees = 0;
		$pricestr = [];
		$optstr = [];
		foreach ($orderrooms as $kor => $or) {
			$num = $kor + 1;

			// determine the number of nights of stay and dates to consider
			$use_los = $row['days'];
			$room_checkin  = $row['checkin'];
			$room_checkout = $row['checkout'];
			if (!empty($split_stay) && !empty($split_stay[$kor]) && $split_stay[$kor]['idroom'] == $or['idroom']) {
				$use_los = (int)$split_stay[$kor]['nights'];
				$room_checkin  = $split_stay[$kor]['checkin_ts'];
				$room_checkout = $split_stay[$kor]['checkout_ts'];
			}

			if ($is_package === true || (!empty($or['cust_cost']) && $or['cust_cost'] > 0.00)) {
				// package cost or cust_cost may not be inclusive of taxes if prices tax included is off
				$calctar = VikBooking::sayPackagePlusIva($or['cust_cost'], $or['cust_idiva']);
				$isdue += $calctar;
				if ($calctar == $or['cust_cost']) {
					$cost_minus_tax = VikBooking::sayPackageMinusIva($or['cust_cost'], $or['cust_idiva']);
					$tot_taxes += ($or['cust_cost'] - $cost_minus_tax);
				} else {
					$tot_taxes += ($calctar - $or['cust_cost']);
				}
				$pricestr[$num] = (!empty($or['pkg_name']) ? $or['pkg_name'] : (!empty($or['otarplan']) ? ucwords($or['otarplan']) : JText::translate('VBOROOMCUSTRATEPLAN'))).": ".$calctar." ".$currencyname;
			} elseif (array_key_exists($num, $tars) && is_array($tars[$num])) {
				$calctar = VikBooking::sayCostPlusIva($tars[$num]['cost'], $tars[$num]['idprice']);
				$tars[$num]['calctar'] = $calctar;
				$isdue += $calctar;
				if ($calctar == $tars[$num]['cost']) {
					$cost_minus_tax = VikBooking::sayCostMinusIva($tars[$num]['cost'], $tars[$num]['idprice']);
					$tot_taxes += ($tars[$num]['cost'] - $cost_minus_tax);
				} else {
					$tot_taxes += ($calctar - $tars[$num]['cost']);
				}
				$pricestr[$num] = VikBooking::getPriceName($tars[$num]['idprice'], $vbo_tn) . ": " . $calctar . " " . $currencyname . (!empty($tars[$num]['attrdata']) ? "\n" . VikBooking::getPriceAttr($tars[$num]['idprice'], $vbo_tn) . ": " . $tars[$num]['attrdata'] : "");
			}
			if (!empty($or['optionals'])) {
				$stepo = explode(";", $or['optionals']);
				foreach ($stepo as $roptkey => $oo) {
					if (empty($oo)) {
						continue;
					}
					$stept = explode(":", $oo);
					$q = "SELECT * FROM `#__vikbooking_optionals` WHERE `id`=" . $dbo->quote($stept[0]) . ";";
					$dbo->setQuery($q);
					$actopt = $dbo->loadAssocList();
					if ($actopt) {
						$vbo_tn->translateContents($actopt, '#__vikbooking_optionals');
						$chvar = '';
						if (!empty($actopt[0]['ageintervals']) && $or['children'] > 0 && strstr($stept[1], '-') != false) {
							$optagenames = VikBooking::getOptionIntervalsAges($actopt[0]['ageintervals']);
							$optagepcent = VikBooking::getOptionIntervalsPercentage($actopt[0]['ageintervals']);
							$optageovrct = VikBooking::getOptionIntervalChildOverrides($actopt[0], $or['adults'], $or['children']);
							$child_num 	 = VikBooking::getRoomOptionChildNumber($or['optionals'], $actopt[0]['id'], $roptkey, $or['children']);
							$optagecosts = VikBooking::getOptionIntervalsCosts(isset($optageovrct['ageintervals_child' . ($child_num + 1)]) ? $optageovrct['ageintervals_child' . ($child_num + 1)] : $actopt[0]['ageintervals']);
							$agestept = explode('-', $stept[1]);
							$stept[1] = $agestept[0];
							$chvar = $agestept[1];
							if (array_key_exists(($chvar - 1), $optagepcent) && $optagepcent[($chvar - 1)] == 1) {
								//percentage value of the adults tariff
								if ($is_package === true || (!empty($or['cust_cost']) && $or['cust_cost'] > 0.00)) {
									$optagecosts[($chvar - 1)] = $or['cust_cost'] * $optagecosts[($chvar - 1)] / 100;
								} else {
									$optagecosts[($chvar - 1)] = $tars[$num]['cost'] * $optagecosts[($chvar - 1)] / 100;
								}
							} elseif (array_key_exists(($chvar - 1), $optagepcent) && $optagepcent[($chvar - 1)] == 2) {
								//VBO 1.10 - percentage value of room base cost
								if ($is_package === true || (!empty($or['cust_cost']) && $or['cust_cost'] > 0.00)) {
									$optagecosts[($chvar - 1)] = $or['cust_cost'] * $optagecosts[($chvar - 1)] / 100;
								} else {
									$display_rate = isset($tars[$num]['room_base_cost']) ? $tars[$num]['room_base_cost'] : $tars[$num]['cost'];
									$optagecosts[($chvar - 1)] = $display_rate * $optagecosts[($chvar - 1)] / 100;
								}
							}
							$actopt[0]['chageintv'] = $chvar;
							$actopt[0]['name'] .= ' ('.$optagenames[($chvar - 1)].')';
							$actopt[0]['quan'] = $stept[1];
							$realcost = (intval($actopt[0]['perday']) == 1 ? (floatval($optagecosts[($chvar - 1)]) * $use_los * $stept[1]) : (floatval($optagecosts[($chvar - 1)]) * $stept[1]));
						} else {
							$actopt[0]['quan'] = $stept[1];
							// VBO 1.11 - options percentage cost of the room total fee
							if ($is_package === true || (!empty($or['cust_cost']) && $or['cust_cost'] > 0.00)) {
								$deftar_basecosts = $or['cust_cost'];
							} else {
								$deftar_basecosts = $tars[$num]['cost'];
							}
							$actopt[0]['cost'] = (int)$actopt[0]['pcentroom'] ? ($deftar_basecosts * $actopt[0]['cost'] / 100) : $actopt[0]['cost'];
							//
							$realcost = (intval($actopt[0]['perday']) == 1 ? ($actopt[0]['cost'] * $use_los * $stept[1]) : ($actopt[0]['cost'] * $stept[1]));
						}
						if (!empty($actopt[0]['maxprice']) && $actopt[0]['maxprice'] > 0 && $realcost > $actopt[0]['maxprice']) {
							$realcost = $actopt[0]['maxprice'];
							if (intval($actopt[0]['hmany']) == 1 && intval($stept[1]) > 1) {
								$realcost = $actopt[0]['maxprice'] * $stept[1];
							}
						}
						if ($actopt[0]['perperson'] == 1) {
							$realcost = $realcost * $or['adults'];
						}
						$opt_minus_tax = VikBooking::sayOptionalsMinusIva($realcost, $actopt[0]['idiva']);
						$tmpopr = VikBooking::sayOptionalsPlusIva($realcost, $actopt[0]['idiva']);
						if ($actopt[0]['is_citytax'] == 1) {
							$tot_city_taxes += $opt_minus_tax;
						} elseif ($actopt[0]['is_fee'] == 1) {
							$tot_fees += $opt_minus_tax;
						}
						// VBO 1.11 - always calculate the amount of tax no matter if this is already a tax or a fee
						if ($tmpopr == $realcost) {
							$tot_taxes += ($realcost - $opt_minus_tax);
						} else {
							$tot_taxes += ($tmpopr - $realcost);
						}
						//
						$isdue += $tmpopr;
						$optstr[$num][] = ($stept[1] > 1 ? $stept[1] . " " : "") . $actopt[0]['name'] . ": " . $tmpopr . " " . $currencyname . "\n";
					}
				}
			}

			// custom extra costs
			if (!empty($or['extracosts'])) {
				$cur_extra_costs = json_decode($or['extracosts'], true);
				foreach ($cur_extra_costs as $eck => $ecv) {
					$ecplustax = !empty($ecv['idtax']) ? VikBooking::sayOptionalsPlusIva($ecv['cost'], $ecv['idtax']) : $ecv['cost'];
					$isdue += $ecplustax;
					$optstr[$num][] = $ecv['name'] . ": " . $ecplustax . " " . $currencyname."\n";
				}
			}
		}

		// coupon
		$usedcoupon = false;
		$origisdue = $isdue;
		if (strlen($row['coupon']) > 0) {
			$usedcoupon = true;
			$expcoupon = explode(";", $row['coupon']);
			$isdue = $isdue - $expcoupon[1];
		}

		// invoke the payment method class
		$exppay = explode('=', $row['idpayment']);
		$payment = VikBooking::getPayment($exppay[0], $vbo_tn);

		if (empty($row['sid']) && !empty($row['idorderota']) && !empty($row['channel'])) {
			$row['sid'] = $row['idorderota'];
		}

		if (VBOPlatformDetection::isWordPress()) {
			/**
			 * @wponly 	The payment gateway is now loaded 
			 * 			using the apposite dispatcher.
			 *
			 * @since 1.0.5
			 */
			JLoader::import('adapter.payment.dispatcher');
			$return_url = JUri::root() . "index.php?option=com_vikbooking&view=booking&sid=" . (!empty($row['idorderota']) && !empty($row['channel']) ? $row['idorderota'] : $row['sid']) . "&ts=" . $row['ts'];
			$error_url = JUri::root() . "index.php?option=com_vikbooking&view=booking&sid=" . (!empty($row['idorderota']) && !empty($row['channel']) ? $row['idorderota'] : $row['sid']) . "&ts=" . $row['ts'];
			$notify_url = JUri::root() . "index.php?option=com_vikbooking&task=notifypayment&sid=" . (!empty($row['idorderota']) && !empty($row['channel']) ? $row['idorderota'] : $row['sid']) . "&ts=" . $row['ts']."&tmpl=component";
			$model 	= JModel::getInstance('vikbooking', 'shortcodes', 'admin');
			$itemid = $model->best(array('booking'), (!empty($row['lang']) ? $row['lang'] : null));
			$extra_data = [];
			if ($itemid) {
				$return_url = str_replace(JUri::root(), '', $return_url);
				$error_url = str_replace(JUri::root(), '', $error_url);
				$notify_url = str_replace(JUri::root(), '', $notify_url);
				$return_url = JRoute::rewrite($return_url . "&Itemid={$itemid}", false);
				$error_url = JRoute::rewrite($error_url . "&Itemid={$itemid}", false);
				$notify_url = JRoute::rewrite($notify_url . "&Itemid={$itemid}", false);
				$extra_data = array(
					'return_url' => $return_url,
					'error_url'  => $error_url,
					'notify_url' => $notify_url,
				);
			}
			$extra_data['transaction_currency'] = VikBooking::getCurrencyCodePp();

			$obj = JPaymentDispatcher::getInstance('vikbooking', $payment['file'], array_merge($row, $extra_data), $payment['params']);
		} else {
			/**
			 * @joomlaonly 	The Payment Factory library will invoke the gateway.
			 * Make sure to pass the payment gateway some common variables together with the order record.
			 * 
			 * @since 	1.14.3
			 */
			require_once VBO_ADMIN_PATH . DIRECTORY_SEPARATOR . 'payments' . DIRECTORY_SEPARATOR . 'libraries' . DIRECTORY_SEPARATOR . 'factory.php';

			$bestitemid = VikBooking::findProperItemIdType(array('booking'));
			$extra_data = array(
				'return_url' => VikBooking::externalroute("index.php?option=com_vikbooking&view=booking&sid=" . (!empty($row['idorderota']) && !empty($row['channel']) ? $row['idorderota'] : $row['sid']) . "&ts=" . $row['ts'], false, (!empty($bestitemid) ? $bestitemid : null)),
				'error_url'  => VikBooking::externalroute("index.php?option=com_vikbooking&view=booking&sid=" . (!empty($row['idorderota']) && !empty($row['channel']) ? $row['idorderota'] : $row['sid']) . "&ts=" . $row['ts'], false, (!empty($bestitemid) ? $bestitemid : null)),
				'notify_url' => VikBooking::externalroute("index.php?option=com_vikbooking&task=notifypayment&sid=" . (!empty($row['idorderota']) && !empty($row['channel']) ? $row['idorderota'] : $row['sid']) . "&ts=" . $row['ts']."&tmpl=component", false, null),
			);
			$extra_data['transaction_currency'] = VikBooking::getCurrencyCodePp();

			$obj = VBOPaymentFactory::getPaymentInstance($payment['file'], array_merge($row, $extra_data), $payment['params']);
		}

		$array_result = $obj->validatePayment();
		$newpaymentlog = date('c')."\n".$array_result['log']."\n----------\n".$row['paymentlog'];

		/**
		 * OTA reservations containing PCI-DSS card details may receive additional payments through
		 * the website for upselling or for payments requested. Therefore, the previous card logs
		 * should be appended, not prepended to the current payment logs for the card details.
		 * 
		 * @since 	1.15.0 (J) - 1.5.0 (WP)
		 */
		if (!empty($row['idorderota']) && !empty($row['channel']) && !empty($row['paymentlog'])) {
			if (stripos($row['paymentlog'], 'card number') !== false && strpos($row['paymentlog'], '*') !== false) {
				$newpaymentlog = $row['paymentlog'] . "\n----------\n" . date('c') . "\n" . $array_result['log'];
			}
		}

		/**
		 * Ensure the size of the log does not make the query fail.
		 * 
		 * @since 	1.16.9 (J) - 1.6.9 (WP)
		 */
		if (strlen($newpaymentlog) > 65000) {
			$newpaymentlog = substr($newpaymentlog, 0, 65000) . '...';
		}

		if ($array_result['verified'] == 1) {
			// valid payment
			$shouldpay = $isdue;
			if ($payment['charge'] > 0.00) {
				if ($payment['ch_disc'] == 1) {
					// charge
					if ($payment['val_pcent'] == 1) {
						// fixed value
						$shouldpay += $payment['charge'];
					} else {
						// percent value
						$percent_to_pay = $shouldpay * $payment['charge'] / 100;
						$shouldpay += $percent_to_pay;
					}
				} else {
					// discount
					if ($payment['val_pcent'] == 1) {
						// fixed value
						$shouldpay -= $payment['charge'];
					} else {
						// percent value
						$percent_to_pay = $shouldpay * $payment['charge'] / 100;
						$shouldpay -= $percent_to_pay;
					}
				}
			}
			//deposit may be skipped by customer choice
			$shouldpay_befdep = $shouldpay;
			//
			if (!VikBooking::payTotal()) {
				$percentdeposit = VikBooking::getAccPerCent();
				if ($percentdeposit > 0) {
					if (VikBooking::getTypeDeposit() == "fixed") {
						$shouldpay = $percentdeposit;
					} else {
						$shouldpay = $shouldpay * $percentdeposit / 100;
					}
				}
			}
			//check if the total amount paid is the same as the order total
			if (isset($array_result['tot_paid'])) {
				$shouldpay = round($shouldpay, 2);
				$shouldpay_befdep = round($shouldpay_befdep, 2);
				$totreceived = round($array_result['tot_paid'], 2);
				if ($shouldpay != $totreceived && $shouldpay_befdep != $totreceived && $row['paymcount'] == 0) {
					//the amount paid is different than the order total
					//fares might have changed or the deposit might be different
					//Sending just an email to the admin that will check
					$vbo_app = VikBooking::getVboApplication();
					$adsendermail = VikBooking::getSenderMail();
					$vbo_app->sendMail($adsendermail, $adsendermail, $recipient_mail, $adsendermail, JText::translate('VBTOTPAYMENTINVALID'), JText::sprintf('VBTOTPAYMENTINVALIDTXT', $row['id'], $totreceived." (".$array_result['tot_paid'].")", $shouldpay), false);
				}
				/**
				 * We store the amount paid before applying the charge for the transaction.
				 * 
				 * @since 	1.3.0
				 */
				if ($payment['charge'] > 0.00) {
					if ($payment['ch_disc'] == 1) {
						// charge
						if ($payment['val_pcent'] == 1) {
							// fixed value
							$array_result['tot_paid'] -= $payment['charge'];
						} else {
							// percent value
							$array_result['tot_paid'] = ($array_result['tot_paid'] / ((100 + $payment['charge']) / 100));
						}
					} else {
						// discount
						if ($payment['val_pcent'] == 1) {
							// fixed value
							$array_result['tot_paid'] += $payment['charge'];
						} else {
							// percent value
							$array_result['tot_paid'] = $array_result['tot_paid'] * (100 + $payment['charge']) / 100;
						}
					}
					$array_result['tot_paid'] = round($array_result['tot_paid'], 2);
				}
			}

			if ($row['paymcount'] == 0 || $row['status'] == 'standby') {
				foreach ($orderrooms as $indnum => $r) {
					$num = $indnum + 1;

					// determine the number of nights of stay and dates to consider
					$room_checkin  = $row['checkin'];
					$room_checkout = $row['checkout'];
					$room_realback = $turnover_secs + $row['checkout'];
					if (!empty($split_stay) && !empty($split_stay[$indnum]) && $split_stay[$indnum]['idroom'] == $r['idroom']) {
						$room_checkin  = $split_stay[$indnum]['checkin_ts'];
						$room_checkout = $split_stay[$indnum]['checkout_ts'];
						$room_realback = $turnover_secs + $split_stay[$indnum]['checkout_ts'];
					}

					$busy_record = new stdClass;
					$busy_record->idroom = $r['idroom'];
					$busy_record->checkin = $room_checkin;
					$busy_record->checkout = $room_checkout;
					$busy_record->realback = $room_realback;

					$dbo->insertObject('#__vikbooking_busy', $busy_record, 'id');

					if (!isset($busy_record->id)) {
						continue;
					}

					$q = "INSERT INTO `#__vikbooking_ordersbusy` (`idorder`,`idbusy`) VALUES(" . (int)$row['id'] . ", " . (int)$busy_record->id . ");";
					$dbo->setQuery($q);
					$dbo->execute();
				}
			}

			// ConfirmationNumber
			if ($row['paymcount'] == 0 || $row['status'] == 'standby') {
				$confirmnumber = VikBooking::generateConfirmNumber($row['id'], true);
			}

			// update payable amount in case of up-sells or simply in case of another payment received
			$new_payable = isset($array_result['tot_paid']) && $array_result['tot_paid'] ? ($row['payable'] - $array_result['tot_paid']) : 0;
			$new_payable = $new_payable < 0 ? 0 : $new_payable;

			// update booking record
			$booking_record = new stdClass;
			$booking_record->id = $row['id'];
			$booking_record->status = 'confirmed';
			if (isset($array_result['tot_paid']) && $array_result['tot_paid']) {
				$booking_record->totpaid = ($array_result['tot_paid'] + $row['totpaid']);
			}
			$booking_record->paymcount = ($row['paymcount'] + 1);
			if (!empty($array_result['log'])) {
				$booking_record->paymentlog = $newpaymentlog;
			}
			$booking_record->payable = $new_payable;

			$dbo->updateObject('#__vikbooking_orders', $booking_record, 'id');

			// assign room specific unit
			$set_room_indexes = VikBooking::autoRoomUnit();
			$room_indexes_usemap = [];
			if ($set_room_indexes === true) {
				$q = "SELECT `id`,`idroom`,`roomindex` FROM `#__vikbooking_ordersrooms` WHERE `idorder`=".(int)$row['id'].";";
				$dbo->setQuery($q);
				$dbo->execute();
				$orooms = $dbo->loadAssocList();
				foreach ($orooms as $oroom) {
					if (!empty($oroom['roomindex'])) {
						// room specific unit has already been assigned
						continue;
					}
					$room_indexes = VikBooking::getRoomUnitNumsAvailable($row, $oroom['idroom']);
					$use_ind_key = 0;
					if ($room_indexes) {
						if (!array_key_exists($oroom['idroom'], $room_indexes_usemap)) {
							$room_indexes_usemap[$oroom['idroom']] = $use_ind_key;
						} else {
							$use_ind_key = $room_indexes_usemap[$oroom['idroom']];
						}
						$q = "UPDATE `#__vikbooking_ordersrooms` SET `roomindex`=".(int)$room_indexes[$use_ind_key]." WHERE `id`=".(int)$oroom['id'].";";
						$dbo->setQuery($q);
						$dbo->execute();
						// update rooms references for the customer email sending function
						foreach ($rooms as $rnum => $rr) {
							if ($rr['or_id'] == $oroom['id']) {
								$rooms[$rnum]['roomindex'] = (int)$room_indexes[$use_ind_key];
								break;
							}
						}
						$room_indexes_usemap[$oroom['idroom']]++;
					}
				}
			}

			// unlock room(s) for other imminent bookings
			$q = "DELETE FROM `#__vikbooking_tmplock` WHERE `idorder`=" . intval($row['id']) . ";";
			$dbo->setQuery($q);
			$dbo->execute();

			// customer booking
			$q = "SELECT `idcustomer` FROM `#__vikbooking_customers_orders` WHERE `idorder`=".(int)$row['id'].";";
			$dbo->setQuery($q);
			$customer_id = $dbo->loadResult();
			if ($customer_id) {
				$cpin = VikBooking::getCPinIstance();
				$cpin->updateBookingCommissions($row['id'], $customer_id);
			}

			// check if some of the rooms booked have shared calendars
			VikBooking::updateSharedCalendars($row['id'], array(), $row['checkin'], $row['checkout']);

			// send email notification to guest and admin
			VikBooking::sendBookingEmail($row['id'], array('guest', 'admin'));

			// SMS
			VikBooking::sendBookingSMS($row['id']);

			/**
			 * Payment gateways may set and return the transaction information
			 * to eventually support a later transaction of type refund.
			 * 
			 * @since 	1.14 (J) - 1.4.0 (WP)
			 * @since 	1.16.2 (J) - 1.6.2 (WP) we attempt to always store the amount paid with this transaction.
			 * @since 	1.16.9 (J) - 1.6.9 (WP) we attempt to store the amount of payment processing fees for the transaction.
			 */
			$tn_data = $array_result['transaction'] ?? null;
			if (isset($array_result['tot_paid']) && $array_result['tot_paid']) {
				// check event data payload to store
				if (is_array($tn_data)) {
					// set key
					$tn_data['amount_paid'] = (float)$array_result['tot_paid'];
				} elseif (is_object($tn_data)) {
					// set property
					$tn_data->amount_paid = (float)$array_result['tot_paid'];
				} elseif (!$tn_data) {
					// build an array (we add the payment name because we know there is no other transaction data)
					$tn_data = [
						'amount_paid' 	 => (float)$array_result['tot_paid'],
						'payment_method' => $payment['name'],
					];
				}
			}
			if ($tn_data && isset($array_result['tot_fees']) && $array_result['tot_fees']) {
				// check event data payload to store
				if (is_array($tn_data)) {
					// set key
					$tn_data['processing_fees'] = (float)$array_result['tot_fees'];
				} elseif (is_object($tn_data)) {
					// set property
					$tn_data->processing_fees = (float)$array_result['tot_fees'];
				}
			}

			// Booking History
			VikBooking::getBookingHistoryInstance()->setBid($row['id'])->setExtraData($tn_data)->store('P' . ($row['paymcount'] > 0 ? 'N' : '0'), $payment['name']);

			// invoke VikChannelManager
			if (is_file(VCM_SITE_PATH . DIRECTORY_SEPARATOR . "helpers" . DIRECTORY_SEPARATOR . "synch.vikbooking.php")) {
				require_once(VCM_SITE_PATH . DIRECTORY_SEPARATOR . "helpers" . DIRECTORY_SEPARATOR . "synch.vikbooking.php");
				$vcm = new SynchVikBooking($row['id']);
				$vcm->setPushType('new')->sendRequest();
			}
			$session = JFactory::getSession();
			$vcmchanneldata = $session->get('vcmChannelData', '');
			if (!empty($vcmchanneldata)) {
				$session->set('vcmChannelData', '');
			}
			//end invoke VikChannelManager
			if (method_exists($obj, 'afterValidation')) {
				$obj->afterValidation(1);
			}
		} else {
			if (empty($array_result['skip_email'])) {
				$vbo_app = VikBooking::getVboApplication();
				$adsendermail = VikBooking::getSenderMail();
				$vbo_app->sendMail($adsendermail, $adsendermail, $recipient_mail, $adsendermail, JText::translate('VBPAYMENTNOTVER'), JText::translate('VBSERVRESP') . ":\n\n" . $array_result['log'], false);
			}
			if (!empty($array_result['log'])) {
				$q = "UPDATE `#__vikbooking_orders` SET `paymentlog`=".$dbo->quote($newpaymentlog)." WHERE `id`='" . $row['id'] . "';";
				$dbo->setQuery($q);
				$dbo->execute();
			}
			if (method_exists($obj, 'afterValidation')) {
				$obj->afterValidation(0);
			}
		}
	}
	
	public function currencyconverter()
	{
		$session = JFactory::getSession();
		$pprices = VikRequest::getVar('prices', array(0));
		$pfromsymbol = VikRequest::getString('fromsymbol', '', 'request');
		$ptocurrency = VikRequest::getString('tocurrency', '', 'request');
		$pfromcurrency = VikRequest::getString('fromcurrency', '', 'request');
		$default_cur = !empty($pfromcurrency) ? $pfromcurrency : VikBooking::getCurrencyName();
		$response = array();
		if (!empty($default_cur) && !empty($pprices) && count($pprices) > 0 && !empty($ptocurrency)) {
			require_once(VBO_SITE_PATH . DS . "helpers" . DS ."currencyconverter.php");
			if ($default_cur != $ptocurrency) {
				$format = VikBooking::getNumberFormatData();
				$converter = new VboCurrencyConverter($default_cur, $ptocurrency, $pprices, explode(':', $format));
				$exchanged = $converter->convert();
				if (count($exchanged) > 0) {
					$response = $exchanged;
					$session->set('vboLastCurrency', $ptocurrency);
				} else {
					$conv_error = $converter->getError();
					$response['error'] = !empty($conv_error) ? $conv_error : JText::translate('VBERRCURCONVINVALIDDATA');
				}
			} else {
				$session->set('vboLastCurrency', $ptocurrency);
				foreach ($pprices as $i => $price) {
					$response[$i]['symbol'] = $pfromsymbol;
					$response[$i]['price'] = $price;
				}
			}
		} else {
			$response['error'] = JText::translate('VBERRCURCONVNODATA');
		}
		if(array_key_exists('error', $response)) {
			$session->set('vboLastCurrency', $ptocurrency);
		}
		echo json_encode($response);
		exit;
	}

	public function signature()
	{
		VikRequest::setVar('view', 'signature');
		parent::display();
	}

	public function storesignature()
	{
		$sid = VikRequest::getString('sid', '', 'request');
		$ts = VikRequest::getString('ts', '', 'request');
		$psignature = VikRequest::getString('signature', '', 'request', VIKREQUEST_ALLOWRAW);
		$ppad_width = VikRequest::getInt('pad_width', '', 'request');
		$ppad_ratio = VikRequest::getInt('pad_ratio', '', 'request');
		$pitemid = VikRequest::getInt('Itemid', '', 'request');
		$ptmpl = VikRequest::getString('tmpl', '', 'request');
		$dbo = JFactory::getDBO();
		$mainframe = JFactory::getApplication();
		$q = "SELECT * FROM `#__vikbooking_orders` WHERE `ts`=" . $dbo->quote($ts) . " AND `sid`=" . $dbo->quote($sid) . " AND `status`='confirmed';";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows() < 1) {
			VikError::raiseWarning('', 'Booking not found');
			$mainframe->redirect('index.php');
			exit;
		}
		$row = $dbo->loadAssoc();
		$tonight = mktime(23, 59, 59, date('n'), date('j'), date('Y'));
		if ($tonight > $row['checkout']) {
			VikError::raiseWarning('', 'Check-out date is in the past');
			$mainframe->redirect('index.php');
			exit;
		}
		$customer = array();
		$q = "SELECT `c`.*,`co`.`idorder`,`co`.`signature`,`co`.`pax_data`,`co`.`comments` FROM `#__vikbooking_customers` AS `c` LEFT JOIN `#__vikbooking_customers_orders` `co` ON `c`.`id`=`co`.`idcustomer` WHERE `co`.`idorder`=".(int)$row['id'].";";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows() > 0) {
			$customer = $dbo->loadAssoc();
		}
		if (!(count($customer) > 0)) {
			VikError::raiseWarning('', 'Customer not found');
			$mainframe->redirect('index.php');
			exit;
		}
		//check if the signature has been submitted
		$signature_data = '';
		$cont_type = '';
		if (!empty($psignature)) {
			/**
			 * Implemented safe filtering of base64-encoded signature image
			 * to obtain content and file extension.
			 * 
			 * @since 	1.15.1 (J) - 1.5.4 (WP)
			 */
			if (preg_match("/^data:image\/(png|jpe?g|svg);base64,([A-Za-z0-9\/=+]+)$/", $psignature, $safe_match)) {
				$signature_data = base64_decode($safe_match[2]);
				$cont_type = $safe_match[1];
			}
		}
		$ret_link = JRoute::rewrite('index.php?option=com_vikbooking&task=signature&sid='.$row['sid'].'&ts='.$row['ts'].(!empty($pitemid) ? '&Itemid='.$pitemid : '').($ptmpl == 'component' ? '&tmpl=component' : ''), false);
		if (empty($signature_data)) {
			VikError::raiseWarning('', JText::translate('VBOSIGNATUREISEMPTY'));
			$mainframe->redirect($ret_link);
			exit;
		}
		//write file
		$sign_fname = $row['id'].'_'.$row['sid'].'_'.$customer['id'].'.'.$cont_type;
		$filepath = VBO_ADMIN_PATH . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'idscans' . DIRECTORY_SEPARATOR . $sign_fname;
		$fp = fopen($filepath, 'w+');
		$bytes = fwrite($fp, $signature_data);
		fclose($fp);
		if ($bytes !== false && $bytes > 0) {
			//update the signature in the DB
			$q = "UPDATE `#__vikbooking_customers_orders` SET `signature`=".$dbo->quote($sign_fname)." WHERE `idorder`=".(int)$row['id'].";";
			$dbo->setQuery($q);
			$dbo->execute();
			$mainframe->enqueueMessage(JText::translate('VBOSIGNATURETHANKS'));
			//resize image for screens with high resolution
			if ($ppad_ratio > 1) {
				$new_width = floor(($ppad_width / 2));
				$creativik = new vikResizer();
				$creativik->proportionalImage($filepath, $filepath, $new_width, $new_width);
			}
			//
		} else {
			VikError::raiseWarning('', JText::translate('VBOERRSTORESIGNFILE'));
		}
		$mainframe->redirect($ret_link);
		exit;
	}

	public function validatepin()
	{
		$cpin = VikBooking::getCPinIstance();

		$ppin = VikRequest::getString('pin', '', 'request');

		$response = [];

		$customer = $cpin->getCustomerByPin($ppin);
		if (count($customer)) {
			$response = $customer;
			$response['success'] = 1;

			if ($cpin->getCustomerCoupon($customer)) {
				// set flag indicating that the customer has got dedicated discounts
				$response['has_discounts'] = 1;
			}
		}

		echo json_encode($response);
		exit;
	}

	public function docancelbooking()
	{
		if (!JSession::checkToken()) {
			// missing CSRF-proof token
			VBOHttpDocument::getInstance()->close(403, JText::translate('JINVALID_TOKEN'));
		}

		$dbo = JFactory::getDbo();
		$mainframe = JFactory::getApplication();

		$psid = VikRequest::getString('sid', '', 'request');
		$pidorder = VikRequest::getString('idorder', '', 'request');

		if (!empty($psid) && !empty($pidorder)) {
			$q = "SELECT * FROM `#__vikbooking_orders` WHERE `id`=".intval($pidorder)." AND `sid`=".$dbo->quote($psid)." AND `status`='confirmed';";
			$dbo->setQuery($q);
			$order = $dbo->loadAssocList();
			if ($order) {
				$pemail = VikRequest::getString('email', '', 'request');
				$preason = VikRequest::getString('reason', '', 'request');
				if (!empty($pemail) && !empty($preason)) {
					$to = VikBooking::getAdminMail();
					if (strpos($to, ',') !== false) {
						$all_recipients = explode(',', $to);
						foreach ($all_recipients as $k => $v) {
							if (empty($v)) {
								unset($all_recipients[$k]);
							}
						}
						if (count($all_recipients) > 0) {
							$to = $all_recipients;
						}
					}
					//check if the booking can be cancelled
					$days_to_arrival = 0;
					$is_refundable = 0;
					$daysadv_refund_arr = array();
					$daysadv_refund = 0;
					$now_info = getdate();
					$checkin_info = getdate($order[0]['checkin']);
					if ($now_info[0] < $checkin_info[0]) {
						while ($now_info[0] < $checkin_info[0]) {
							if (!($now_info['mday'] != $checkin_info['mday'] || $now_info['mon'] != $checkin_info['mon'] || $now_info['year'] != $checkin_info['year'])) {
								break;
							}
							$days_to_arrival++;
							$now_info = getdate(mktime(0, 0, 0, $now_info['mon'], ($now_info['mday'] + 1), $now_info['year']));
						}
					}
					$tars = array();
					$is_package = !empty($order[0]['pkg']) ? true : false;
					$orderrooms = array();
					$q = "SELECT `or`.`idroom`,`or`.`adults`,`or`.`children`,`or`.`idtar`,`or`.`optionals`,`or`.`roomindex`,`or`.`pkg_id`,`or`.`pkg_name`,`or`.`cust_cost`,`or`.`cust_idiva`,`or`.`extracosts`,`or`.`room_cost`,`or`.`otarplan`,`r`.`id` AS `r_reference_id`,`r`.`name`,`r`.`img`,`r`.`idcarat`,`r`.`fromadult`,`r`.`toadult` FROM `#__vikbooking_ordersrooms` AS `or`,`#__vikbooking_rooms` AS `r` WHERE `or`.`idorder`='".$order[0]['id']."' AND `or`.`idroom`=`r`.`id` ORDER BY `or`.`id` ASC;";
					$dbo->setQuery($q);
					$orderrooms = $dbo->loadAssocList();
					if ($orderrooms) {
						foreach($orderrooms as $kor => $or) {
							$num = $kor + 1;
							if($is_package === true || (!empty($or['cust_cost']) && $or['cust_cost'] > 0.00)) {
								//package or custom cost set from the back-end
								continue;
							}
							$q = "SELECT `t`.*,`p`.`name`,`p`.`free_cancellation`,`p`.`canc_deadline`,`p`.`canc_policy` FROM `#__vikbooking_dispcost` AS `t` LEFT JOIN `#__vikbooking_prices` AS `p` ON `t`.`idprice`=`p`.`id` WHERE `t`.`id`='" . $or['idtar'] . "';";
							$dbo->setQuery($q);
							$tar = $dbo->loadAssocList();
							if ($tar) {
								$tars[$num] = $tar[0];
							}
						}
					}
					foreach ($tars as $num => $tar) {
						if ($tar['free_cancellation'] < 1) {
							//if at least one rate plan is non-refundable, the whole reservation cannot be cancelled
							$is_refundable = 0;
							$daysadv_refund_arr = array();
							break;
						}
						$is_refundable = 1;
						$daysadv_refund_arr[] = $tar['canc_deadline'];
					}
					//get the rate plan with the lowest cancellation deadline
					$daysadv_refund = count($daysadv_refund_arr) > 0 ? min($daysadv_refund_arr) : $daysadv_refund;
					$resmodcanc = VikBooking::getReservationModCanc();
					$resmodcanc = $days_to_arrival < 1 ? 0 : $resmodcanc;
					$resmodcancmin = VikBooking::getReservationModCancMin();
					$canc_allowed = ($resmodcanc > 1 && $resmodcanc != 2 && $is_refundable > 0 && $daysadv_refund <= $days_to_arrival && $days_to_arrival >= $resmodcancmin);
					if (!$canc_allowed) {
						VikError::raiseWarning('', JText::translate('VBOERRCANNOTCANCBOOK'));
						$mainframe->redirect(JRoute::rewrite("index.php?option=com_vikbooking&view=booking&sid=".$order[0]['sid']."&ts=".$order[0]['ts']."&Itemid=".VikRequest::getString('Itemid', '', 'request'), false));
						exit;
					}
					//make the cancellation in the db and update the administrator notes with the reason specified by the customer
					$new_adminotes = JText::translate('VBOBOOKCANCELLEDEMAILSUBJ').' ('.$pemail.")\n".$preason."\n\n".$order[0]['adminnotes'];
					$q = "UPDATE `#__vikbooking_orders` SET `status`='cancelled',`adminnotes`=".$dbo->quote($new_adminotes)." WHERE `id`=".(int)$order[0]['id'].";";
					$dbo->setQuery($q);
					$dbo->execute();
					$q = "SELECT * FROM `#__vikbooking_ordersbusy` WHERE `idorder`=".(int)$order[0]['id'].";";
					$dbo->setQuery($q);
					$ordbusy = $dbo->loadAssocList();
					if ($ordbusy) {
						foreach ($ordbusy as $ob) {
							$q = "DELETE FROM `#__vikbooking_busy` WHERE `id`=".(int)$ob['idbusy'].";";
							$dbo->setQuery($q);
							$dbo->execute();
						}
					}
					$q = "DELETE FROM `#__vikbooking_ordersbusy` WHERE `idorder`=".(int)$order[0]['id'].";";
					$dbo->setQuery($q);
					$dbo->execute();

					if ($order[0]['split_stay']) {
						// attempt to remove the transient record
						VBOFactory::getConfig()->remove('split_stay_' . $order[0]['id']);
					}

					// Booking History
					$history_obj = VikBooking::getBookingHistoryInstance()->setBid($order[0]['id']);
					$history_obj->store('CW', $preason);

					/**
					 * Check if the amount paid can be refunded.
					 * 
					 * @since 	1.14 (J) - 1.4.0 (WP)
					 */
					$admin_refund_error = '';
					$currencysymb = VikBooking::getCurrencySymb();
					$payment = VikBooking::getPayment($order[0]['idpayment']);
					$tn_driver = is_array($payment) ? $payment['file'] : null;

					// transaction data validation callback
					$tn_data_callback = function($data) use ($tn_driver) {
						return (is_object($data) && isset($data->driver) && basename($data->driver, '.php') == basename($tn_driver, '.php'));
					};
					// get previous transactions
					$prev_tn_data = $history_obj->getEventsWithData(array('P0', 'PN'), $tn_data_callback);

					if (is_array($prev_tn_data) && count($prev_tn_data) && $order[0]['totpaid'] > 0) {
						// previous transactions found and total paid > 0
						$refund_amount = $order[0]['totpaid'];

						// push refund information for the payment gateway
						$order[0]['total_to_refund'] = $refund_amount;
						$order[0]['transaction'] = $prev_tn_data;
						$order[0]['refund_reason'] = $preason;

						// push the transaction currency information
						$order[0]['transaction_currency'] = VikBooking::getCurrencyCodePp();

						/**
						 * @wponly 	The payment gateway is loaded 
						 * 			through the apposite dispatcher.
						 */
						JLoader::import('adapter.payment.dispatcher');
						$obj = JPaymentDispatcher::getInstance('vikbooking', $payment['file'], $order[0], $payment['params']);

						// check if refund is supported by this gateway
						if (method_exists($obj, 'isRefundSupported') && $obj->isRefundSupported()) {
							// perform the refund transaction
							$array_result = $obj->refund();

							if ($array_result['verified'] != 1) {
								// refund failed
								$admin_refund_error .= "\nRefund transaction failed\n";
								// get the refund error message
								$admin_refund_error .= !empty($array_result['log']) && is_string($array_result['log']) ? $array_result['log'] : '';
							} else {
								// refund was successful

								/**
								 * The history event extra data will contain the "amount_paid" (refunded).
								 * 
								 * @since 	1.16.9 (J) - 1.6.9 (WP)
								 */
								if (!empty($array_result['tot_paid'])) {
									// overwrite the requested amount with the returned one
									$refund_amount = (float)$array_result['tot_paid'];
								}
								$history_obj->setExtraData([
									'amount_paid' => $refund_amount,
								]);

								// update total paid, total and refund columns for the booking
								$booking = new stdClass;
								$booking->id = $order[0]['id'];
								if ($order[0]['totpaid'] > 0) {
									$booking->totpaid = (float)($order[0]['totpaid'] - $refund_amount);
								}
								if ($order[0]['total'] > 0) {
									$booking->total = (float)($order[0]['total'] - $refund_amount);
								}
								$booking->refund = (float)$order[0]['refund'] + $refund_amount;
								// update record in db
								$dbo->updateObject('#__vikbooking_orders', $booking, 'id');

								// store the refund event
								$event_descr = [
									'(' . $payment['name'] . ')',
									$currencysymb . ' ' . VikBooking::numberFormat($refund_amount),
								];
								$history_obj->store('RF', implode("\n", $event_descr));
							}
						}
					}

					// invoke VikChannelManager
					if (is_file(VCM_SITE_PATH . DIRECTORY_SEPARATOR . "helpers" . DIRECTORY_SEPARATOR . "synch.vikbooking.php")) {
						$vcm_obj = VikBooking::getVcmInvoker();
						$vcm_obj->setOids(array($order[0]['id']))->setSyncType('cancel');
						$vcm_obj->doSync();
					}
					// end invoke VikChannelManager

					//send email to the administrator
					$subject = JText::translate('VBOBOOKCANCELLEDEMAILSUBJ');
					// @wponly 	we do not need to pass the "best item id"
					$uri = VikBooking::externalroute("index.php?option=com_vikbooking&view=booking&sid=" . $order[0]['sid'] . "&ts=" . $order[0]['ts'], false);
					$msg = JText::sprintf('VBOBOOKCANCELLEDEMAILHEAD', $order[0]['id'], $uri) . "\n\n" . $preason . $admin_refund_error;
					$vbo_app = VikBooking::getVboApplication();
					$adsendermail = VikBooking::getSenderMail();
					$vbo_app->sendMail($adsendermail, $adsendermail, $to, $pemail, $subject, $msg, false);

					// SMS
					VikBooking::sendBookingSMS($order[0]['id']);

					// send cancellation email notification to guest
					VikBooking::sendBookingEmail($order[0]['id'], ['guest']);

					// go back to the booking details page to show the new status
					$mainframe->enqueueMessage(JText::translate('VBOBOOKCANCELLEDRESP'));
					$mainframe->redirect(JRoute::rewrite("index.php?option=com_vikbooking&view=booking&sid=".$order[0]['sid']."&ts=".$order[0]['ts']."&Itemid=".VikRequest::getString('Itemid', '', 'request'), false));
				} else {
					VikError::raiseWarning('', JText::translate('VBOERRMISSDATA'));
					$mainframe->redirect(JRoute::rewrite("index.php?option=com_vikbooking&view=booking&sid=".$order[0]['sid']."&ts=".$order[0]['ts']."&Itemid=".VikRequest::getString('Itemid', '', 'request'), false));
				}
			} else {
				$mainframe->redirect("index.php");
			}
		} else {
			$mainframe->redirect("index.php");
		}
	}

	public function cancelmodification()
	{
		$psid = VikRequest::getString('sid', '', 'request');
		$pidorder = VikRequest::getString('id', '', 'request');
		$dbo = JFactory::getDBO();
		$session = JFactory::getSession();
		$mainframe = JFactory::getApplication();
		if (!empty($psid) && !empty($pidorder)) {
			$q = "SELECT * FROM `#__vikbooking_orders` WHERE `id`=".intval($pidorder)." AND `sid`=".$dbo->quote($psid)." AND `status`='confirmed';";
			$dbo->setQuery($q);
			$dbo->execute();
			if ($dbo->getNumRows() == 1) {
				$order = $dbo->loadAssocList();
				//unset the session value and redirect
				$session->set('vboModBooking', '');
				$mainframe->redirect(JRoute::rewrite("index.php?option=com_vikbooking&view=booking&sid=".$order[0]['sid']."&ts=".$order[0]['ts'], false));
			} else {
				$mainframe->redirect("index.php");
			}
		} else {
			$mainframe->redirect("index.php");
		}
	}
	
	public function tac_av_l()
	{
		require_once(VBO_SITE_PATH . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'tac.vikbooking.php');
		
		//Channel Rates Module
		$pvbomodule = VikRequest::getInt('vbomodule', 0, 'request');
		$pshow_tax = VikRequest::getInt('show_tax', 0, 'request');
		$pdef_rplan = VikRequest::getInt('def_rplan', 0, 'request');
		$pchannels_sel = VikRequest::getVar('channels_sel', array());
		$pcheckin = VikRequest::getString('checkin', '', 'request');
		$pcheckout = VikRequest::getString('checkout', '', 'request');
		if ($pvbomodule > 0 && !empty($pcheckin) && !empty($pcheckout)) {
			//this is an ajax request, probably made by the module Vik Booking Channel Rates
			//we need to prepare some variables before calling the method.
			$start_date = date('Y-m-d', VikBooking::getDateTimestamp($pcheckin, 12, 0));
			$end_date = date('Y-m-d', VikBooking::getDateTimestamp($pcheckout, 10, 0));
			//set (only some) request variables (the rest is sent via Ajax)
			VikRequest::setVar('e4jauth', md5('vbo.e4j.vbo'));
			VikRequest::setVar('req_type', 'hotel_availability');
			VikRequest::setVar('start_date', $start_date);
			VikRequest::setVar('end_date', $end_date);
			//make call to get the result
			TACVBO::$getArray = true;
			$website_rates = TACVBO::tac_av_l();
			//validate response
			if (!is_array($website_rates)) {
				//error returned
				echo json_encode(array('e4j.error' => $website_rates));
				exit;
			}
			if (is_array($website_rates) && isset($website_rates['e4j.error'])) {
				//another type of error returned
				echo json_encode($website_rates);
				exit;
			}
			if (is_array($website_rates) && !(count($website_rates) > 0)) {
				//empty response
				echo json_encode(array('e4j.error' => 'empty response'));
				exit;
			}
			//get the list of channels connected, filtered by ID
			$channels_map = VikBooking::getChannelsMap($pchannels_sel);
			//get the array with the lowest and preferred room rate
			$best_room_rate = VikBooking::getBestRoomRate($website_rates, $pdef_rplan);
			//get the charge/discount value for the OTAs rates from the Bulk Rates Cache of VCM
			$otas_rates_val = VikBooking::getOtasRatesVal($best_room_rate, true);

			$otas_rmod = '';
			$otas_rmodpcent = 0;
			$otas_rmodval = 0;
			$otas_rmod_channels = array();
			if (!empty($otas_rates_val)) {
				if (is_array($otas_rates_val)) {
					$otas_rmod_channels = $otas_rates_val;
					$use_rates_val = $otas_rates_val[0];
				} else {
					// string
					$use_rates_val = $otas_rates_val;
				}
				$otas_rmod = substr($use_rates_val, 0, 1); // + or - (charge or discount)
				$otas_rmodpcent = substr($use_rates_val, -1) == '%' ? 1 : 0;
				$otas_rmodval = (float)($otas_rmodpcent > 0 ? substr($use_rates_val, 1, (strlen($use_rates_val) - 2)) : substr($use_rates_val, 1, (strlen($use_rates_val) - 1)));
			}
			if (!count($best_room_rate)) {
				// nothing to parse
				echo json_encode(array('e4j.error' => 'no rates'));
				exit;
			}
			// build the response
			$final_cost = $pshow_tax > 0 ? ($best_room_rate['cost'] + $best_room_rate['taxes']) : $best_room_rate['cost'];
			$rates_resp = array(
				'website' => VikBooking::numberFormat($final_cost)
			);
			if (count($channels_map)) {
				$rates_resp['channels'] = array();
			}
			foreach ($channels_map as $ch) {
				$ch_final_cost = $final_cost;

				/**
				 * Check if an alteration for this channel has been specified.
				 * 
				 * @since 	1.15.0 (J) - 1.5.0 (WP)
				 */
				$use_otas_rmod = $otas_rmod;
				$use_otas_rmodpcent = $otas_rmodpcent;
				$use_otas_rmodval = $otas_rmodval;
				if (is_array($otas_rmod_channels) && isset($otas_rmod_channels[$ch['id']])) {
					$use_rates_val = $otas_rmod_channels[$ch['id']];
					$use_otas_rmod = substr($use_rates_val, 0, 1); //+ or - (charge or discount)
					$use_otas_rmodpcent = substr($use_rates_val, -1) == '%' ? 1 : 0;
					$use_otas_rmodval = (float)($use_otas_rmodpcent > 0 ? substr($use_rates_val, 1, (strlen($use_rates_val) - 2)) : substr($use_rates_val, 1, (strlen($use_rates_val) - 1)));
				}

				if (!empty($use_otas_rmod)) {
					if ($use_otas_rmod == '+') {
						// charge
						if ($use_otas_rmodpcent > 0) {
							// percentage
							$ch_final_cost = $ch_final_cost * (100 + $use_otas_rmodval) / 100;
						} else {
							// absolute
							$ch_final_cost += $use_otas_rmodval * (!empty($best_room_rate['days']) ? $best_room_rate['days'] : 1);
						}
					} else {
						// discount (must be a fool)
						if ($use_otas_rmodpcent > 0) {
							// percentage
							$ch_final_cost = $ch_final_cost / (($use_otas_rmodval / 100) + 1);
						} else {
							// absolute
							$ch_final_cost -= $use_otas_rmodval * (!empty($best_room_rate['days']) ? $best_room_rate['days'] : 1);
						}
					}
				}

				$rates_resp['channels'][$ch['id']] = VikBooking::numberFormat($ch_final_cost);
			}
			// output the response
			echo json_encode($rates_resp);
			exit;
		}

		// proceed with the standard request (that will exit the process)
		TACVBO::tac_av_l();
	}

	/**
	 * Front-end authentication for the operators
	 * through their authentication code.
	 *
	 * @since 	1.11
	 */
	public function operatorlogin()
	{
		/**
		 * Extra security fix for the login form token.
		 * 
		 * @since 	September 8th 2020
		 */
		if (!JFactory::getSession()->checkToken()) {
			throw new Exception("The security token did not match.", 403);
		}
		//

		$app = JFactory::getApplication();
		$pauthcode = VikRequest::getString('authcode', '', 'request');
		$pitemid = VikRequest::getInt('Itemid', '', 'request');
		/**
		 * We add "&auth=1" to the query string just to avoid caching for a redirect to the same login page URI.
		 * 
		 * @since 	September 9th 2020
		 */
		$goto = JRoute::rewrite('index.php?option=com_vikbooking&view=operators&auth=1'.(!empty($pitemid) ? '&Itemid='.$pitemid : ''), false);

		if (empty($pauthcode) || !VikBooking::getOperatorInstance()->authOperator($pauthcode)) {
			// print warning message
			VikError::raiseWarning('', JText::translate('VBOOPERINVAUTHCODE'));
		}

		$app->redirect($goto);
	}

	/**
	 * Front-end logout for the operators.
	 *
	 * @since 	1.11
	 */
	public function operatorlogout()
	{
		$app = JFactory::getApplication();
		$pitemid = VikRequest::getInt('Itemid', '', 'request');
		$goto = JRoute::rewrite('index.php?option=com_vikbooking&view=operators'.(!empty($pitemid) ? '&Itemid='.$pitemid : ''), false);

		VikBooking::getOperatorInstance()->logoutOperator();

		$app->redirect($goto);
	}

	/**
	 * Front-end Pre Check-in submit of the guests details.
	 *
	 * @since 	1.12
	 */
	public function storeprecheckin()
	{
		$dbo 	 = JFactory::getDbo();
		$app 	 = JFactory::getApplication();
		$sid 	 = VikRequest::getString('sid', '', 'request');
		$ts 	 = VikRequest::getString('ts', '', 'request');
		$pguests = VikRequest::getVar('guests', array());
		$pitemid = VikRequest::getInt('Itemid', 0, 'request');

		$q = "SELECT `o`.* FROM `#__vikbooking_orders` AS `o` WHERE (`o`.`sid`=" . $dbo->quote($sid) . " OR `o`.`idorderota`=" . $dbo->quote($sid) . ") AND `o`.`ts`=" . $dbo->quote($ts) . " AND `o`.`status`='confirmed';";
		$dbo->setQuery($q);
		$dbo->execute();
		if (!$dbo->getNumRows()) {
			throw new Exception('Booking not found', 404);
		}
		$order = $dbo->loadAssoc();

		$orderrooms = array();
		$q = "SELECT `or`.`idroom`,`or`.`adults`,`or`.`children`,`or`.`idtar`,`or`.`optionals`,`or`.`childrenage`,`or`.`t_first_name`,`or`.`t_last_name`,`or`.`roomindex`,`or`.`pkg_id`,`or`.`pkg_name`,`or`.`cust_cost`,`or`.`cust_idiva`,`or`.`extracosts`,`or`.`room_cost`,`or`.`otarplan`,`r`.`id` AS `r_reference_id`,`r`.`name`,`r`.`img`,`r`.`idcarat`,`r`.`fromadult`,`r`.`toadult` FROM `#__vikbooking_ordersrooms` AS `or`,`#__vikbooking_rooms` AS `r` WHERE `or`.`idorder`=".(int)$order['id']." AND `or`.`idroom`=`r`.`id` ORDER BY `or`.`id` ASC;";
		$dbo->setQuery($q);
		$dbo->execute();
		if (!$dbo->getNumRows()) {
			throw new Exception('No rooms found', 404);
		}
		$orderrooms = $dbo->loadAssocList();

		$q = "SELECT * FROM `#__vikbooking_customers_orders` WHERE `idorder`=".(int)$order['id'].";";
		$dbo->setQuery($q);
		$dbo->execute();
		if (!$dbo->getNumRows()) {
			throw new Exception('No customer found', 404);
		}
		$custorder = $dbo->loadAssoc();

		// booking details page
		$goto = JRoute::rewrite('index.php?option=com_vikbooking&view=booking&sid=' . $order['sid'] . '&ts=' . $order['ts'] . (!empty($pitemid) ? '&Itemid=' . $pitemid : ''), false);
		if (empty($order['sid']) && !empty($order['idorderota'])) {
			// booking details page for OTA bookings
			$goto = JRoute::rewrite('index.php?option=com_vikbooking&view=booking&sid=' . $order['idorderota'] . '&ts=' . $order['ts'] . (!empty($pitemid) ? '&Itemid=' . $pitemid : ''), false);
		}

		// make sure pre-checkin is allowed
		$precheckin = VikBooking::precheckinEnabled();
		if ($precheckin) {
			// make sure the limit of days in advance is reflected
			$precheckin_mind = VikBooking::precheckinMinOffset();
			$precheckin_lim_ts = strtotime("+{$precheckin_mind} days 00:00:00");
			$precheckin = ($precheckin_lim_ts <= $order['checkin'] || ($precheckin_mind === 1 && time() <= $order['checkin']));
		}
		if (!$precheckin) {
			// raise error and redirect in case of website or OTA booking
			VikError::raiseWarning('', 'Pre-checkin not allowed at this time');
			$app->redirect($goto);
			exit;
		}

		// build guest details
		$guests_details = array();

		// list of keys for the guests details collected via front-end
		$front_keys = array();
		
		foreach ($pguests as $ind => $adults) {
			foreach ($adults as $aduind => $details) {
				foreach ($details as $detkey => $detval) {
					if (!in_array($detkey, $front_keys)) {
						// push the key of the guest details for later comparison
						array_push($front_keys, $detkey);
					}
					if (strlen($detval)) {
						// push value only if not empty
						if (!isset($guests_details[$ind])) {
							$guests_details[$ind] = array();
						}
						if (!isset($guests_details[$ind][$aduind])) {
							$guests_details[$ind][$aduind] = array();
						}
						$guests_details[$ind][$aduind][$detkey] = $detval;
					}
				}
			}
		}

		/**
		 * Compare the current data collected to the back-end pax_data in case there are some
		 * fields dedicated to just the back-end for the admins (like extra_notes), and merge.
		 */
		$curpaxdata = json_decode($custorder['pax_data'], true);
		if (is_array($curpaxdata) && count($curpaxdata)) {
			foreach ($curpaxdata as $ind => $adults) {
				if (!isset($guests_details[$ind])) {
					// current pax data include a room not present, set it to not lose it
					$guests_details[$ind] = $adults;
				}
				foreach ($adults as $aduind => $details) {
					if (!isset($guests_details[$ind][$aduind])) {
						// current pax data include a guest not present, set it to not lose it
						$guests_details[$ind][$aduind] = $details;
					}
					foreach ($details as $detkey => $detval) {
						if (!in_array($detkey, $front_keys)) {
							// merge this key probably reserved to the back-end
							$guests_details[$ind][$aduind][$detkey] = $detval;
						}
					}
				}
			}
		}
		
		// update checkin information
		$q = "UPDATE `#__vikbooking_customers_orders` SET `pax_data`=".$dbo->quote(json_encode($guests_details))." WHERE `id`=".(int)$custorder['id'].";";
		$dbo->setQuery($q);
		$dbo->execute();

		// Booking History
		VikBooking::getBookingHistoryInstance()->setBid($order['id'])->store('PC');

		// print success message and redirect
		$app->enqueueMessage(JText::translate('VBOSUBMITPRECHECKINTNKS'));
		$app->redirect($goto);
	}

	/**
	 * Upsell extra services/options.
	 *
	 * @since 	1.13 (J) - 1.3.0 (WP)
	 */
	public function upsellextras()
	{
		$dbo 	 = JFactory::getDbo();
		$app 	 = JFactory::getApplication();
		$sid 	 = VikRequest::getString('sid', '', 'request');
		$ts 	 = VikRequest::getString('ts', '', 'request');
		$pitemid = VikRequest::getInt('Itemid', 0, 'request');
		$paddopt = VikRequest::getVar('addopt', array());

		if (!$paddopt) {
			throw new Exception('No extra services selected', 404);
		}

		$q = "SELECT `o`.* FROM `#__vikbooking_orders` AS `o` WHERE (`o`.`sid`=" . $dbo->quote($sid) . " OR `o`.`idorderota`=" . $dbo->quote($sid) . ") AND `o`.`ts`=" . $dbo->quote($ts) . ";";
		$dbo->setQuery($q);
		$dbo->execute();
		if (!$dbo->getNumRows()) {
			throw new Exception('Booking not found', 404);
		}
		$order = $dbo->loadAssoc();

		$orderrooms = [];
		$q = "SELECT `or`.*,`r`.`name` AS `room_name` FROM `#__vikbooking_ordersrooms` AS `or`,`#__vikbooking_rooms` AS `r` WHERE `or`.`idorder`=".(int)$order['id']." AND `or`.`idroom`=`r`.`id` ORDER BY `or`.`id` ASC;";
		$dbo->setQuery($q);
		$dbo->execute();
		if (!$dbo->getNumRows()) {
			throw new Exception('No rooms found', 404);
		}
		$orderrooms = $dbo->loadAssocList();

		// availability helper
		$av_helper = VikBooking::getAvailabilityInstance();

		// room stay dates in case of split stay
		$room_stay_dates = [];
		if ($order['split_stay']) {
			if ($order['status'] == 'confirmed') {
				$room_stay_dates = $av_helper->loadSplitStayBusyRecords($order['id']);
			} else {
				$room_stay_dates = VBOFactory::getConfig()->getArray('split_stay_' . $order['id'], []);
			}
		}

		// load all valid and existing options as a security measure
		$alloptions = [];
		$q = "SELECT * FROM `#__vikbooking_optionals`;";
		$dbo->setQuery($q);
		$dbo->execute();
		if (!$dbo->getNumRows()) {
			throw new Exception('No options found', 404);
		}
		$records = $dbo->loadAssocList();
		foreach ($records as $v) {
			$alloptions[(int)$v['id']] = $v;
		}

		$extras_booked = [];
		foreach ($orderrooms as $kor => $or) {
			if (!isset($paddopt[$kor]) || !count($paddopt[$kor])) {
				continue;
			}

			// determine proper nights of stay
			$room_stay_nights = $order['days'];
			if ($order['split_stay'] && count($room_stay_dates) && isset($room_stay_dates[$kor]) && $room_stay_dates[$kor]['idroom'] == $or['idroom']) {
				$room_stay_checkin  = !empty($room_stay_dates[$kor]['checkin_ts']) ? $room_stay_dates[$kor]['checkin_ts'] : $room_stay_dates[$kor]['checkin'];
				$room_stay_checkout = !empty($room_stay_dates[$kor]['checkout_ts']) ? $room_stay_dates[$kor]['checkout_ts'] : $room_stay_dates[$kor]['checkout'];
				$room_stay_nights 	= $av_helper->countNightsOfStay($room_stay_checkin, $room_stay_checkout);
			}

			$extraoptstr = '';
			foreach ($paddopt[$kor] as $optid => $quant) {
				if (strpos($or['optionals'], $optid . ':') === 0 || strpos($or['optionals'], ';' . $optid . ':') > 0) {
					// this option has already been booked, skip it
					continue;
				}
				if (!isset($alloptions[(int)$optid])) {
					// this option ID does not exist, skip it
					continue;
				}
				$extraoptstr .= $optid . ':' . (int)$quant . ';';
				// push option booked
				array_push($extras_booked, array(
					'id' => $optid,
					'idroom' => $or['idroom'],
					'name' => $alloptions[(int)$optid]['name'],
					'quant' => $quant,
					'room_cost' => (!empty($or['cust_cost']) ? $or['cust_cost'] : $or['room_cost']),
					'room_name' => $or['room_name'],
					'optcost' => $alloptions[(int)$optid]['cost'],
					'adults' => $or['adults'],
					'children' => $or['children'],
					'nights' => $room_stay_nights,
				));
			}
			// update options for this room record
			$newoptstr = $or['optionals'] . $extraoptstr;
			$q = "UPDATE `#__vikbooking_ordersrooms` SET `optionals`=" . $dbo->quote($newoptstr) . " WHERE `id`={$or['id']};";
			$dbo->setQuery($q);
			$dbo->execute();
		}

		// increase booking total amount and build event log for the history
		$currency  = VikBooking::getCurrencySymb();
		$totrooms  = count($orderrooms);
		$increase  = 0;
		$add_tax   = 0;
		$extraslog = [];
		foreach ($extras_booked as $extra) {
			$o = $alloptions[(int)$extra['id']];
			if ((int)$o['pcentroom']) {
				// make sure we have a cost for the room, or we should skip this type of option for "incomplete" bookings
				if (empty($extra['room_cost'])) {
					continue;
				}
				$o['cost'] = ($extra['room_cost'] * $o['cost'] / 100);
			}
			$optcost = intval($o['perday']) == 1 ? ($o['cost'] * $extra['nights']) : $o['cost'];
			if (!empty($o['maxprice']) && $o['maxprice'] > 0 && $optcost > $o['maxprice']) {
				$optcost = $o['maxprice'];
			}
			if ($o['perperson'] == 1) {
				$optcost = $optcost * $extra['adults'];
			}
			$optcost *= $extra['quant'];
			$floatoptprice = VikBooking::sayOptionalsPlusIva($optcost, $o['idiva']);
			$netoptprice   = VikBooking::sayOptionalsMinusIva($optcost, $o['idiva']);
			$increase += $floatoptprice;
			$add_tax  += $floatoptprice - $netoptprice;
			array_push($extraslog, ($totrooms > 1 ? $extra['room_name'] . ': ' : '') . $extra['name'] . ($extra['quant'] > 1 ? ' (x' . $extra['quant'] . ')' : '') . ' ' . $currency . ' ' . VikBooking::numberFormat($floatoptprice));
		}

		$newtotbooking = $order['total'] + $increase;
		$new_tot_taxes = $order['tot_taxes'] + $add_tax;
		/**
		 * Important: the 'paymcount' should be increased only if the status is
		 * "confirmed" or no rooms may be occupied when receiving a payment.
		 */
		$q = "UPDATE `#__vikbooking_orders` SET `total`=" . $dbo->quote($newtotbooking) . ", `paymcount`=" . ($order['status'] == 'confirmed' && (int)$order['paymcount'] < 1 ? '1' : $order['paymcount']) . ", `tot_taxes`=" . $dbo->q($new_tot_taxes) . ", `payable`=" . $dbo->quote(((float)$order['payable'] + $increase)) . " WHERE `id`={$order['id']};";
		$dbo->setQuery($q);
		$dbo->execute();

		// Booking History
		VikBooking::getBookingHistoryInstance()->setBid($order['id'])->store('UE', implode("\n", $extraslog));

		// send email notification to guest and admin
		VikBooking::sendBookingEmail($order['id'], array('guest', 'admin'));

		$goto = JRoute::rewrite('index.php?option=com_vikbooking&view=booking&sid=' . (empty($order['sid']) && !empty($order['idorderota']) ? $order['idorderota'] : $order['sid']) . '&ts=' . $order['ts'] . (!empty($pitemid) ? '&Itemid=' . $pitemid : ''), false);
		$app->enqueueMessage(JText::translate('VBOUPSELLRESULTOK'));
		$app->redirect($goto);
	}

	/**
	 * Submits a new review.
	 *
	 * @since 	1.3.0
	 */
	public function sendreview()
	{
		$dbo 	 	= JFactory::getDbo();
		$app 	 	= JFactory::getApplication();
		$vbo_tn 	= VikBooking::getTranslator();
		$sid 	 	= VikRequest::getString('sid', '', 'request');
		$ts 	 	= VikRequest::getString('ts', '', 'request');
		$ratingmess = VikRequest::getString('ratingmess', '', 'request');
		$rating 	= VikRequest::getVar('rating', array(), 'request', 'array');
		$pitemid 	= VikRequest::getInt('Itemid', 0, 'request');

		$q = "SELECT `o`.* FROM `#__vikbooking_orders` AS `o` WHERE (`o`.`sid`=" . $dbo->quote($sid) . " OR `o`.`idorderota`=" . $dbo->quote($sid) . ") AND `o`.`ts`=" . $dbo->quote($ts) . " AND `o`.`status`='confirmed';";
		$dbo->setQuery($q);
		$dbo->execute();
		if (!$dbo->getNumRows()) {
			throw new Exception('Booking not found', 404);
		}
		$order = $dbo->loadAssoc();

		// make sure a review can be left for this booking
		if (!VikBooking::canBookingBeReviewed($order)) {
			throw new Exception('Cannot leave a review at this time', 403);
		}

		$orderrooms = array();
		$q = "SELECT `or`.`idroom`,`or`.`adults`,`or`.`children`,`or`.`idtar`,`or`.`optionals`,`or`.`childrenage`,`or`.`t_first_name`,`or`.`t_last_name`,`or`.`roomindex`,`or`.`pkg_id`,`or`.`pkg_name`,`or`.`cust_cost`,`or`.`cust_idiva`,`or`.`extracosts`,`or`.`room_cost`,`or`.`otarplan`,`r`.`id` AS `r_reference_id`,`r`.`name`,`r`.`img`,`r`.`idcarat`,`r`.`fromadult`,`r`.`toadult` FROM `#__vikbooking_ordersrooms` AS `or`,`#__vikbooking_rooms` AS `r` WHERE `or`.`idorder`=".(int)$order['id']." AND `or`.`idroom`=`r`.`id` ORDER BY `or`.`id` ASC;";
		$dbo->setQuery($q);
		$dbo->execute();
		if (!$dbo->getNumRows()) {
			throw new Exception('No rooms found', 404);
		}
		$orderrooms = $dbo->loadAssocList();

		// get customer information
		$customer = VikBooking::getCPinIstance()->getCustomerFromBooking($order['id']);

		// booking details page
		$goto = JRoute::rewrite('index.php?option=com_vikbooking&view=booking&sid=' . $order['sid'] . '&ts=' . $order['ts'] . (!empty($pitemid) ? '&Itemid=' . $pitemid : ''), false);
		if (empty($order['sid']) && !empty($order['idorderota'])) {
			// booking details page for OTA bookings
			$goto = JRoute::rewrite('index.php?option=com_vikbooking&view=booking&sid=' . $order['idorderota'] . '&ts=' . $order['ts'] . (!empty($pitemid) ? '&Itemid=' . $pitemid : ''), false);
		}

		// reviews settings
		$gr_approval = VikBooking::guestReviewsApproval();
		$gr_type 	 = VikBooking::guestReviewsType();
		$gr_services = VikBooking::guestReviewsServices();
		$rawservices = $gr_services;
		$vbo_tn->translateContents($gr_services, '#__vikbooking_greview_service');

		// make sure all ratings are not empty
		if ($gr_type == 'global') {
			if (empty($rating[0]) || intval($rating[0]) < 1 || intval($rating[0]) > 5) {
				// no or invalid single-rating received
				VikError::raiseWarning('', 'Please rate your experience to leave a review.');
				$app->redirect($goto);
				exit;
			}
		} else {
			if (count($gr_services) != count($rating) || !count($rating)) {
				// something is missing
				VikError::raiseWarning('', 'Please rate your experience for all services');
				$app->redirect($goto);
				exit;
			}
			// make sure all ratings are valid
			foreach ($rating as $k => $score) {
				if (empty($score) || intval($score) < 1 || intval($score) > 5) {
					// no or invalid rating received for this service
					VikError::raiseWarning('', 'Please rate your experience to leave a review (missing ' . (isset($gr_services[$k]) ? $gr_services[$k]['service_name'] : '-----') . ').');
					$app->redirect($goto);
					exit;
				}
			}
		}

		// average review score (in base 10) and services map
		$avg_score = 0;
		$serv_scores = array();

		// gather the information
		foreach ($rating as $k => $score) {
			// rating in base 10
			$score = ((int)$score * 2);
			//
			$avg_score += $score;
			if ($gr_type == 'service' && isset($gr_services[$k]) && !empty($gr_services[$k]['service_name'])) {
				$skey = $gr_services[$k]['service_name'];
				$serv_scores[$skey] = $score;
			}
		}
		// this will be the review_score
		$avg_score = round(($avg_score / count($rating)), 2);

		// build review content object
		$review_content = new stdClass;

		// creation date
		$review_content->created_timestamp = date('Y-m-d H:i:s');

		// scoring per service (if any)
		$review_content->scoring = new stdClass;
		if (count($serv_scores)) {
			$counter = 0;
			foreach ($serv_scores as $snametranx => $servscore) {
				// we build the object with the original names of the services, as they could have been translated
				$origskey = $rawservices[$counter]['service_name'];
				$review_content->scoring->{$origskey} = $servscore;
				$counter++;
			}
		}
		// scoring total value ("review_score" is a protected key) is added no matter of the review type (service/global)
		$review_content->scoring->review_score = $avg_score;

		// reviewer information
		$review_content->reviewer = new stdClass;
		$customer_name = '';
		if (count($customer)) {
			$review_content->reviewer->name = $customer['first_name'];
			$review_content->reviewer->country_code = $customer['country'];
			$customer_name = $customer['first_name'] . ' ' . $customer['last_name'];;
		} else {
			$revuname = '';
			if (!empty($order['custdata'])) {
				$uinfos = explode("\n", $order['custdata']);
				$first_info = explode(':', $uinfos[0]);
				if (count($first_info) > 1) {
					unset($first_info[0]);
					$revuname = implode(':', $first_info);
				} else {
					$revuname = trim($first_info[0]);
				}
			}
			$review_content->reviewer->name = $revuname;
			$review_content->reviewer->country_code = $order['country'];
			$customer_name = $revuname;
		}

		// maximum 2000 chars for the message review to avoid spammers
		if (!empty($ratingmess) && strlen($ratingmess) > 2000) {
			$ratingmess = substr($ratingmess, 0, 2000);
		}

		// review message
		$review_content->content = new stdClass;
		$review_content->content->message = !empty($ratingmess) ? $ratingmess : null;

		// null reply
		$review_content->reply = null;

		// check if multiple accounts to find the property name
		$property_name = null;
		$has_multiaccounts = false;
		$multi_map = array();
		$q = "SELECT * FROM `#__vikchannelmanager_roomsxref`;";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows()) {
			$xref_data = $dbo->loadAssocList();
			foreach ($xref_data as $xref) {
				if (empty($xref['prop_params'])) {
					continue;
				}
				if (!isset($multi_map[$xref['idchannel']])) {
					$multi_map[$xref['idchannel']] = array();
				}
				if (!isset($multi_map[$xref['idchannel']][$xref['prop_params']])) {
					$multi_map[$xref['idchannel']][$xref['prop_params']] = 0;
				}
				$multi_map[$xref['idchannel']][$xref['prop_params']]++;
			}
			foreach ($multi_map as $ch_id => $ch_params) {
				if (count($ch_params) > 1) {
					$has_multiaccounts = true;
					break;
				}
			}
		}
		if ($has_multiaccounts && (int)$order['roomsnum'] === 1) {
			// find the category name of the room booked (if any, and if one room booked)
			$q = "SELECT `idcat` FROM `#__vikbooking_rooms` WHERE `id`={$orderrooms[0]['idroom']};";
			$dbo->setQuery($q);
			$dbo->execute();
			if ($dbo->getNumRows()) {
				$allcats = $dbo->loadResult();
				if (!empty($allcats)) {
					$parts = explode(';', $allcats);
					if (count($parts) === 2) {
						// just one category, get the name of it
						$property_name = VikBooking::getCategoryName($parts[0]);
					}
				}
			}
			if (empty($property_name)) {
				// category not found, get the room name
				$property_name = $orderrooms[0]['name'];
			}
		}

		// create record
		$review_record = new stdClass;
		$review_record->review_id = -1;
		$review_record->prop_first_param = null;
		$review_record->prop_name = $property_name;
		$review_record->channel = null;
		$review_record->uniquekey = 0;
		$review_record->idorder = $order['id'];
		$review_record->dt = JFactory::getDate()->toSql(true);
		$review_record->customer_name = $customer_name;
		$review_record->lang = JFactory::getLanguage()->getTag();
		$review_record->score = $avg_score;
		$review_record->country = $order['country'];
		$review_record->content = json_encode($review_content);
		$review_record->published = ($gr_approval == 'auto' ? 1 : 0);

		// insert review
		if ($dbo->insertObject('#__vikchannelmanager_otareviews', $review_record, 'id')) {
			$app->enqueueMessage(JText::translate('VBOTHANKSREVIEWLEFT'));
			// Booking History
			VikBooking::getBookingHistoryInstance()->setBid($order['id'])->store('GR');
		} else {
			VikError::raiseWarning('', JText::translate('VBOREVIEWGENERROR'));
		}

		// update global score for website and this property (if multiple accounts)
		$globscore_id = null;
		$q = "SELECT `id` FROM `#__vikchannelmanager_otascores` WHERE `channel` IS NULL AND " . (is_null($property_name) ? '`prop_name` IS NULL' : '`prop_name`=' . $dbo->quote($property_name));
		$dbo->setQuery($q, 0, 1);
		$dbo->execute();
		if ($dbo->getNumRows()) {
			$globscore_id = $dbo->loadResult();
		}
		// select score for all reviews for the website and this account
		$services_scores = array();
		$services_revscount = array();
		$revs_count = 0;
		$super_tot = 0;
		$q = "SELECT `score`,`content` FROM `#__vikchannelmanager_otareviews` WHERE `channel` IS NULL AND " . (is_null($property_name) ? '`prop_name` IS NULL' : '`prop_name`=' . $dbo->quote($property_name)) . ";";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows()) {
			$all_scores = $dbo->loadAssocList();
			$revs_count += count($all_scores);
			foreach ($all_scores as $s) {
				$super_tot += $s['score'];
				// check if scores were given per service
				$s['content'] = json_decode($s['content'], true);
				if (isset($s['content']['scoring']) && count($s['content']['scoring']) > 1) {
					// review was left for services
					foreach ($s['content']['scoring'] as $sname => $sval) {
						if (!isset($services_scores[$sname])) {
							$services_scores[$sname] = 0;
							$services_revscount[$sname] = 0;
						}
						$services_scores[$sname] += $sval;
						$services_revscount[$sname]++;
					}
				}
			}
		}
		// global average score
		$revs_count = $revs_count > 0 ? $revs_count : 1;
		$glob_avg_score = ($super_tot / $revs_count);
		// services average score
		$glob_servs_avg_score = array();
		foreach ($services_scores as $sname => $val) {
			$services_revscount[$sname] = isset($services_revscount[$sname]) && $services_revscount[$sname] > 0 ? $services_revscount[$sname] : 1;
			$glob_servs_avg_score[$sname] = ($val / $services_revscount[$sname]);
		}
		
		// build global score content
		$glob_score_content = new stdClass;
		$glob_score_content->review_score = new stdClass;
		$glob_score_content->review_score->score = $glob_avg_score;
		$glob_score_content->review_score->review_count = $revs_count;
		foreach ($glob_servs_avg_score as $sname => $val) {
			$glob_score_content->{$sname} = new stdClass;
			$glob_score_content->{$sname}->score = $val;
			$glob_score_content->{$sname}->review_count = $services_revscount[$sname];
		}
		
		// build global score object
		$glob_score_obj = new stdClass;
		if (!empty($globscore_id)) {
			$glob_score_obj->id = $globscore_id;
		}
		$glob_score_obj->prop_first_param = null;
		$glob_score_obj->prop_name = $property_name;
		$glob_score_obj->channel = null;
		$glob_score_obj->uniquekey = 0;
		$glob_score_obj->last_updated = JFactory::getDate()->toSql(true);
		$glob_score_obj->score = round($glob_avg_score, 2);
		$glob_score_obj->content = json_encode($glob_score_content);

		// update or create global score
		if (!empty($globscore_id)) {
			$dbo->updateObject('#__vikchannelmanager_otascores', $glob_score_obj, 'id');
		} else {
			$dbo->insertObject('#__vikchannelmanager_otascores', $glob_score_obj, 'id');
		}

		// redirect to main view
		$app->redirect($goto);
	}

	/**
	 * AJAX task to get one monthly availability calendar of the room details page.
	 *
	 * @since 	1.13.5
	 */
	public function get_avcalendars_data()
	{
		$dbo 	 	= JFactory::getDbo();
		$rid 		= VikRequest::getInt('rid', 0, 'request');
		$direction 	= VikRequest::getString('direction', 'next', 'request');
		$fromdt 	= VikRequest::getString('fromdt', '', 'request');
		$nextdt 	= VikRequest::getString('nextdt', '', 'request');
		$prevdt 	= VikRequest::getString('prevdt', '', 'request');

		// make sure vars are not empty
		if (empty($rid) || empty($direction) || empty($fromdt) || empty($nextdt) || empty($prevdt)) {
			/**
			 * Search engines may follow this endpoint, so we have to exit with HTTP status code 200
			 * 
			 * @since 	1.16.0 (J) - 1.6.0 (WP)
			 */
			VBOHttpDocument::getInstance()->json(['Invalid request variables']);
		}

		// date format
		$vbo_df = VikBooking::getDateFormat();
		if ($vbo_df == "%d/%m/%Y") {
			$vbo_df = 'd/m/Y';
		} elseif ($vbo_df == "%m/%d/%Y") {
			$vbo_df = 'm/d/Y';
		} else {
			$vbo_df = 'Y/m/d';
		}

		// configuration settings
		$numcalendars = VikBooking::numCalendars();
		$showpartlyres = VikBooking::showPartlyReserved();
		$showcheckinoutonly = VikBooking::showStatusCheckinoutOnly();
		$usepricecal = false;
		$inonout_allowed = true;
		$timeopst = VikBooking::getTimeOpenStore();
		if (is_array($timeopst)) {
			if ($timeopst[0] < $timeopst[1]) {
				// check-in not allowed on a day where there is already a check out (no arrivals/depatures on the same day)
				$inonout_allowed = false;
			}
		}

		// week-days ordering
		$firstwday = (int)VikBooking::getFirstWeekDay();
		$days_labels = array(
			JText::translate('VBSUN'),
			JText::translate('VBMON'),
			JText::translate('VBTUE'),
			JText::translate('VBWED'),
			JText::translate('VBTHU'),
			JText::translate('VBFRI'),
			JText::translate('VBSAT')
		);
		$days_indexes = array();
		for ($i = 0; $i < 7; $i++) {
			$days_indexes[$i] = (6 - ($firstwday - $i) + 1) % 7;
		}

		// first day timestamp of month to read in case of forward navigation
		$start_ts = strtotime($fromdt);
		$start_info = getdate($start_ts);
		if (!$start_ts || !$start_info) {
			VBOHttpDocument::getInstance()->close(500, 'Invalid date provided');
		}
		// backward navigation
		if ($direction == 'prev') {
			// we need to get the previous month
			$start_ts = mktime(0, 0, 0, ($start_info['mon'] - 1), 1, $start_info['year']);
			$start_info = getdate($start_ts);
			if (!$start_ts || !$start_info) {
				VBOHttpDocument::getInstance()->close(500, 'Invalid date calculated');
			}
		}
		
		// make sure minimum date is respected
		$min_lim_ts = mktime(0, 0, 0, date('n'), 1, date('Y'));
		if ($start_ts < $min_lim_ts) {
			VBOHttpDocument::getInstance()->close(500, 'Dates in the past not allowed');
		}

		// check the current next and prev dates to help the next AJAX navigations
		$nextnav_ts = strtotime($nextdt);
		$nextnav_info = getdate($nextnav_ts);
		if (!$nextnav_ts || !$nextnav_info) {
			VBOHttpDocument::getInstance()->close(500, 'Invalid next navigation date provided');
		}
		$prevnav_ts = strtotime($prevdt);
		$prevnav_info = getdate($prevnav_ts);
		if (!$prevnav_ts || !$prevnav_info) {
			VBOHttpDocument::getInstance()->close(500, 'Invalid prev navigation date provided');
		}

		// make sure maximum date is respected
		$max_months_future = 12;
		$max_date_future  = VikBooking::getMaxDateFuture($rid);
		if (!empty($max_date_future)) {
			$numlim = (int)substr($max_date_future, 1, (strlen($max_date_future) - 2));
			$numlim = $numlim < 1 ? 1 : $numlim;
			$quantlim = substr($max_date_future, -1, 1);
			if ($quantlim == 'm' || $quantlim == 'y') {
				$max_months_future = $numlim * ($quantlim == 'm' ? 1 : 12);
				$max_ts_future = strtotime("+{$max_months_future} months");
				$max_info = getdate($max_ts_future);
				$max_endts_future = mktime(23, 59, 59, $max_info['mon'], date('t', $max_info[0]), $max_info['year']);
				if ($start_ts > $max_endts_future) {
					VBOHttpDocument::getInstance()->close(500, 'Maximum date in the future exceeded');
				}
			}
		}

		// get global property closing dates
		$cal_closing_dates = VikBooking::parseJsClosingDates();
		if (count($cal_closing_dates)) {
			foreach ($cal_closing_dates as $ccdk => $ccdv) {
				if (!(count($ccdv) == 2)) {
					continue;
				}
				$cal_closing_dates[$ccdk][0] = strtotime($ccdv[0]);
				$cal_closing_dates[$ccdk][1] = strtotime($ccdv[1]);
			}
		}

		// load room details
		$q = "SELECT * FROM `#__vikbooking_rooms` WHERE `id`={$rid}";
		$dbo->setQuery($q, 0, 1);
		$dbo->execute();
		if (!$dbo->getNumRows()) {
			VBOHttpDocument::getInstance()->close(404, 'Room not found');
		}
		$room_details = $dbo->loadAssoc();

		// get the future busy records
		$today_ts = mktime(0, 0, 0, date('n'), date('j'), date('Y'));
		$previousdayclass = '';

		$q = "SELECT * FROM `#__vikbooking_busy` WHERE `idroom`={$room_details['id']} AND `checkout`>={$start_ts};";
		$dbo->setQuery($q);
		$busy = $dbo->loadAssocList();

		// empty day element
		$empty_elem = new stdClass;
		$empty_elem->type = 'placeholder';
		$empty_elem->cont = '&nbsp;';

		// build response container
		$calendars = array();

		// prepare calendar object
		$calendar = new stdClass;
		$calendar->ts = $start_info[0];
		$calendar->mon = $start_info['mon'];
		$calendar->mday = $start_info['mday'];
		$calendar->year = $start_info['year'];
		$calendar->month = VikBooking::sayMonth($start_info['mon']);
		$calendar->wdays = array();
		for ($i = 0; $i < 7; $i++) {
			$d_ind = ($i + $firstwday) < 7 ? ($i + $firstwday) : ($i + $firstwday - 7);
			array_push($calendar->wdays, $days_labels[$d_ind]);
		}

		// build the calendar rows by looping over the days of this month
		$calendar->rows = array();

		// the row will contain all the placeholders and real days (7 elements at most)
		$row = array();
		$d_count = 0;

		// first, we push empty dates placeholders for printing the first table cells (cells before the 1st of the month)
		for ($i = 0, $n = $days_indexes[$start_info['wday']]; $i < $n; $i++, $d_count++) {
			// push fake-day element (placeholder)
			array_push($row, $empty_elem);
		}

		// start looping
		$loop_end_month = $start_info['mon'];
		while ($start_info['mon'] == $loop_end_month) {
			if ($d_count > 6) {
				// push the current row
				array_push($calendar->rows, $row);

				// start a new row and reset cells counter
				$row = array();
				$d_count = 0;
			}

			// build real-day element
			$elem = new stdClass;
			$elem->type = 'day';
			$elem->cont = $start_info['mday'] < 10 ? "0{$start_info['mday']}" : $start_info['mday'];
			$elem->dt 	= date($vbo_df, $start_info[0]);
			$elem->ymd 	= date('Y-m-d', $start_info[0]);
			$elem->ts 	= $start_info[0];
			$elem->class = 'vbtdfree';
			$elem->past_class = $start_info[0] < $today_ts ? ' vbtdpast' : '';

			// check whether this day has got bookings
			$totfound = 0;
			$ischeckinday = false;
			$ischeckoutday = false;
			foreach ($busy as $b) {
				$info_in = getdate($b['checkin']);
				$checkin_ts = mktime(0, 0, 0, $info_in['mon'], $info_in['mday'], $info_in['year']);
				$info_out = getdate($b['checkout']);
				$checkout_ts = mktime(0, 0, 0, $info_out['mon'], $info_out['mday'], $info_out['year']);
				if ($start_info[0] >= $checkin_ts && $start_info[0] == $checkout_ts) {
					$ischeckoutday = true;
				}
				if ($start_info[0] >= $checkin_ts && $start_info[0] < $checkout_ts) {
					$totfound++;
					if ($start_info[0] == $checkin_ts) {
						$ischeckinday = true;
					}
				}
			}
			if ($totfound >= $room_details['units']) {
				$elem->class = "vbtdbusy";
				if ($ischeckinday && $showcheckinoutonly && !$usepricecal && $inonout_allowed && $previousdayclass != "vbtdbusy" && $previousdayclass != "vbtdbusy vbtdbusyforcheckin") {
					$elem->class = "vbtdbusy vbtdbusyforcheckin";
				} elseif ($ischeckinday && !$usepricecal && !$inonout_allowed && $previousdayclass != "vbtdbusy" && $previousdayclass != "vbtdbusy vbtdbusyforcheckin") {
					// check-out not allowed on a day where someone is already checking-in
					$elem->class = "vbtdbusy";
				}
			} elseif ($totfound > 0) {
				if ($showpartlyres) {
					$elem->class = "vbtdwarning";
				}
			} else {
				if ($ischeckoutday && !$usepricecal && $showcheckinoutonly && $inonout_allowed && !($room_details['units'] > 1)) {
					$elem->class = "vbtdbusy vbtdbusyforcheckout";
				} elseif ($ischeckoutday && !$usepricecal && !$inonout_allowed && !($room_details['units'] > 1)) {
					$elem->class = "vbtdbusy";
				}
			}

			// check global closing dates
			if (count($cal_closing_dates)) {
				foreach ($cal_closing_dates as $closed_interval) {
					if ($start_info[0] >= $closed_interval[0] && $start_info[0] <= $closed_interval[1]) {
						$elem->class = "vbtdbusy";
						break;
					}
				}
			}

			// push cell element and increase counter
			array_push($row, $elem);
			$d_count++;

			// update previous day class
			$previousdayclass = $elem->class;

			// go to next day
			$start_info = getdate(mktime(0, 0, 0, $start_info['mon'], ($start_info['mday'] + 1), $start_info['year']));
		}

		if (count($row)) {
			// the last row still need to be pushed in the rows
			for ($i = $d_count; $i <= 6; $i++) {
				// fill last empty days
				array_push($row, $empty_elem);
			}

			// push ending row
			array_push($calendar->rows, $row);
		}

		// push this month's calendar object
		array_push($calendars, $calendar);

		// check whether next request can navigate forward
		$can_nav_next = false;
		if ($direction == 'prev') {
			// we went prev, so we got to be able to go next
			$can_nav_next = true;
		} elseif ($direction == 'next' && strtotime("+{$max_months_future} months") > $start_info[0]) {
			// went next and max future date is still greater than next hypothetical month
			$can_nav_next = true;
		}

		// check whether next request can navigate backward
		$can_nav_prev = false;
		if ($direction == 'next') {
			// we went next, so we got to be able to go prev
			$can_nav_prev = true;
		} elseif ($direction == 'prev' && $min_lim_ts < mktime(0, 0, 0, ($start_info['mon'] - 1), 1, $start_info['year'])) {
			// went prev and first day of today's month is less than (not equal to) the last month just rendered
			$can_nav_prev = true;
		}

		// calculate next and prev dates for the next navigations to help the AJAX request
		$next_ymd = null;
		if ($can_nav_next) {
			if ($direction == 'next') {
				// next forward navigation will start from the ne month
				$next_ymd = date('Y-m-d', $start_info[0]);
			} else {
				// we add the total number of calendars to the month after the one lastly displayed
				$next_ymd = date('Y-m-d', strtotime("+" . ($numcalendars - 1) . " months", $start_info[0]));
			}
		}
		$prev_ymd = null;
		if ($can_nav_prev) {
			if ($direction == 'prev') {
				// next backward navigation will start from the last month we just displayed
				$prev_ymd = date('Y-m-d', mktime(0, 0, 0, ($start_info['mon'] - 1), 1, $start_info['year']));
			} else {
				// we add the total number of calendars to the month after the one lastly displayed
				$prev_ymd = date('Y-m-d', strtotime("-{$numcalendars} months", $start_info[0]));
			}
		}

		// build response object
		$response = new stdClass;
		$response->calendars 	= $calendars;
		$response->can_nav_next = $can_nav_next;
		$response->can_nav_prev = $can_nav_prev;
		$response->next_ymd 	= $next_ymd;
		$response->prev_ymd 	= $prev_ymd;

		echo json_encode($response);
		exit;
	}

	/**
	 * AJAX request for adding a new room-day note from the front-end tableaux.
	 * 
	 * @return 	void
	 * 
	 * @since 	1.13.5
	 */
	public function add_roomdaynote()
	{
		$dt 	 = VikRequest::getString('dt', '', 'request');
		$idroom  = VikRequest::getInt('idroom', 0, 'request');
		$subunit = VikRequest::getInt('subunit', 0, 'request');
		$type 	 = VikRequest::getString('type', '', 'request');
		$type 	 = empty($type) ? 'custom' : $type;
		$name 	 = VikRequest::getString('name', '', 'request');
		$descr 	 = VikRequest::getString('descr', '', 'request');
		$cdays   = VikRequest::getInt('cdays', 0, 'request');
		$cdays 	 = $cdays < 0 ? 0 : $cdays;
		$cdays 	 = $cdays > 365 ? 365 : $cdays;
		if (empty($idroom) || empty($dt) || !strtotime($dt)) {
			echo 'e4j.error.1';
			exit;
		}

		// we put the operator name in the description (if available)
		$operator = VikBooking::getOperatorInstance()->getOperatorAccount();
		if ($operator !== false) {
			$oper_signature = "({$operator['first_name']} {$operator['last_name']})";
			$descr = empty($descr) ? $oper_signature : $descr . " \n" . $oper_signature;
		}

		// reload end date
		$end_date = $dt;
		
		// build critical date object
		$new_note = array(
			'name'  => $name,
			'type'  => $type,
			'descr' => $descr,
		);

		// get object
		$notes  = VikBooking::getCriticalDatesInstance();

		// store the notes for all consecutive dates
		for ($i = 0; $i <= $cdays; $i++) {
			$store_dt = $dt;
			if ($i > 0) {
				$dt_info = getdate(strtotime($store_dt));
				$store_dt = date('Y-m-d', mktime(0, 0, 0, $dt_info['mon'], ($dt_info['mday'] + $i), $dt_info['year']));
				$end_date = $store_dt;
			}
			$result = $notes->storeDayNote($new_note, $store_dt, $idroom, $subunit);
			if (!$result) {
				echo 'e4j.error.2';
				exit;
			}
		}

		// reload all room day notes for this day for the AJAX response
		$all_notes = $notes->loadRoomDayNotes($dt, $end_date, $idroom, $subunit);

		if (!$all_notes || !count($all_notes)) {
			// no notes found even after storing it
			echo 'e4j.error.3';
			exit;
		}

		echo json_encode($all_notes);
		exit;
	}

	/**
	 * AJAX endpoint to upload customer documents during the pre-checkin.
	 * 
	 * @throws 	Exception
	 * 
	 * @since 	1.14 (J) - 1.4.0 (WP)
	 */
	public function precheckin_upload_docs()
	{
		$dbo 	= JFactory::getDbo();
		$app 	= JFactory::getApplication();
		$input  = $app->input;

		// get request values
		$order_sid 	 = $input->getString('sid', '');
		$order_ts 	 = $input->getString('ts', '');
		/**
		 * This is a simple file uploading process, but if
		 * we wanted to automatically update the pax_data,
		 * the request vars below would be necessary.
		 */
		$room_index  = $input->getInt('room_index', 0);
		$guest_index = $input->getInt('guest_index', 0);
		$pax_index 	 = $input->getString('pax_index', '');

		if (empty($order_sid) || empty($order_ts)) {
			throw new Exception('Missing booking details', 404);
		}

		$q = "SELECT `o`.*,(SELECT SUM(`or`.`adults`) FROM `#__vikbooking_ordersrooms` AS `or` WHERE `or`.`idorder`=`o`.`id`) AS `tot_adults` FROM `#__vikbooking_orders` AS `o` WHERE (`o`.`sid`=" . $dbo->quote($order_sid) . " OR `o`.`idorderota`=" . $dbo->quote($order_sid) . ") AND `o`.`ts`=" . $dbo->quote($order_ts) . " AND `o`.`status`='confirmed';";
		$dbo->setQuery($q);
		$dbo->execute();
		if (!$dbo->getNumRows()) {
			throw new Exception('Booking not found', 404);
		}
		$order = $dbo->loadAssoc();

		$customer = array();
		$q = "SELECT `c`.*,`co`.`idorder`,`co`.`signature`,`co`.`pax_data`,`co`.`comments`,`co`.`checkindoc` FROM `#__vikbooking_customers` AS `c` LEFT JOIN `#__vikbooking_customers_orders` `co` ON `c`.`id`=`co`.`idcustomer` WHERE `co`.`idorder`=".$order['id'].";";
		$dbo->setQuery($q);
		$dbo->execute();
		if (!$dbo->getNumRows()) {
			// one customer must be assigned to this booking for the pre-checkin
			throw new Exception('No customers associated to this booking', 404);
		}
		$customer = $dbo->loadObject();

		// make sure pre-checkin is allowed
		$precheckin = VikBooking::precheckinEnabled();
		if ($precheckin) {
			// make sure the limit of days in advance is reflected
			$precheckin_mind = VikBooking::precheckinMinOffset();
			$precheckin_lim_ts = strtotime("+{$precheckin_mind} days 00:00:00");
			$precheckin = ($precheckin_lim_ts <= $order['checkin'] || ($precheckin_mind === 1 && time() <= $order['checkin']));
		}
		if (!$precheckin) {
			throw new Exception('Pre-checkin not allowed at this time', 403);
		}

		// get uploaded files array (use "raw" to avoid filtering the file to upload)
		$files = $input->files->get('docs', array(), 'raw');
		if (!count($files)) {
			throw new Exception('No files to be uploaded', 500);
		}

		if (isset($files['name'])) {
			// we have a single associative array, we need to push it within a list,
			// because the upload iterates the $files array
			$files = array($files);
		}

		// fetch documents folder path
		$dirpath = VBO_CUSTOMERS_PATH . DIRECTORY_SEPARATOR;

		// check if we have a valid directory
		if (empty($customer->docsfolder) || !is_dir($dirpath . $customer->docsfolder)) {
			// randomize string
			$customer->seed = uniqid();

			// create blocks for hashed folder
			$parts = array(
				$customer->first_name,
				$customer->last_name,
				md5(serialize($customer)),
			);

			// join fetched parts
			$customer->docsfolder = strtolower(implode('-', array_filter($parts)));

			if (strlen($customer->docsfolder) < 16) {
				throw new Exception('Possible security breach. Please specify as many details as possible.', 400);
			}

			jimport('joomla.filesystem.folder');

			// create a folder for this customer
			$created = JFolder::create($dirpath . $customer->docsfolder);

			if (!$created) {
				throw new Exception(sprintf('Unable to create the folder [%s]', $dirpath . $customer->docsfolder), 403);
			}

			unset($customer->seed);

			// update customer docs folder
			$record = new stdClass;
			$record->id = $customer->id;
			$record->docsfolder = $customer->docsfolder;
			$dbo->updateObject('#__vikbooking_customers', $record, 'id');
		}

		// prepare the response array of uploaded-file objects
		$response = array();
		$upload_err = null;

		// compose prefix for all files uploaded (must end with an underscrore for View's compatibility)
		$file_prefix = str_replace(' ', '-', JText::translate('VBOPRECHECKIN')) . '_';

		try {
			
			foreach ($files as $file) {
				// sanitize file name
				if (!empty($file['name'])) {
					// replace quotes and pipe, which is the separator in pax_data
					$file['name'] = str_replace(array("'", '"', '|'), '', $file['name']);
				}
				if (empty($file['name'])) {
					continue;
				}
				// always prepend "pre check-in" to the original file name
				$file['name'] = strtolower($file_prefix . $file['name']);
				// try to upload the file
				$result = VikBooking::uploadFileFromRequest($file, $dirpath . $customer->docsfolder, "/(image\/.+)|(application\/(zip|pdf|msword|vnd.*?))|(text\/(plain|markdown|csv))$/i");
				// set a valid URL for the uploaded file
				$result->url = str_replace(DIRECTORY_SEPARATOR, '/', str_replace(VBO_CUSTOMERS_PATH . DIRECTORY_SEPARATOR, VBO_CUSTOMERS_URI, $result->path));
				// push uploaded file
				array_push($response, $result);
			}

		} catch (Exception $e) {
			// do nothing, but catch the error
			$upload_err = $e;
		}

		if (!count($response)) {
			throw ($upload_err !== null ? $upload_err : (new Exception('No files could actually be uploaded', 500)));
		}

		// output the response
		echo json_encode($response);
		exit;
	}

	/**
	 * AJAX endpoint to submit an inquiry/information request.
	 * 
	 * @since 	1.15.0 (J) - 1.5.0 (WP)
	 */
	public function submit_inquiry()
	{
		if (!JSession::checkToken()) {
			// missing CSRF-proof token
			VBOHttpDocument::getInstance()->close(403, JText::translate('JINVALID_TOKEN'));
		}

		$dbo 	= JFactory::getDbo();
		$app 	= JFactory::getApplication();
		$input  = $app->input;

		// get request values
		$checkin_dt  = $input->getString('checkindate', '');
		$checkin_h   = $input->getInt('checkinh', 0);
		$checkin_m   = $input->getInt('checkinm', 0);
		$checkout_dt = $input->getString('checkoutdate', '');
		$checkout_h  = $input->getInt('checkouth', 0);
		$checkout_m  = $input->getInt('checkoutm', 0);
		$categories  = $input->getString('categories', '');
		$roomsnum  	 = $input->getInt('roomsnum', 1);
		$adults 	 = $input->get('adults', array(), 'int');
		$children 	 = $input->get('children', array(), 'int');
		$inquiry 	 = $input->get('inquiry', array(), 'raw');
		$ulang 		 = $input->getString('ulang', '');

		$timeopst = VikBooking::getTimeOpenStore();
		if (empty($checkin_h) && empty($checkout_h) && is_array($timeopst)) {
			$opent = VikBooking::getHoursMinutes($timeopst[0]);
			$closet = VikBooking::getHoursMinutes($timeopst[1]);
			$checkin_h  = $opent[0];
			$checkin_m  = $opent[1];
			$checkout_h = $closet[0];
			$checkout_m = $closet[1];
		}

		// compose the stay dates
		$checkin_ts  = VikBooking::getDateTimestamp($checkin_dt, $checkin_h, $checkin_m);
		$checkout_ts = VikBooking::getDateTimestamp($checkout_dt, $checkout_h, $checkout_m);

		if (empty($checkin_dt) || empty($checkout_dt) || empty($checkin_ts) || empty($checkout_ts)) {
			// invalid dates
			VBOHttpDocument::getInstance()->close(400, JText::translate('VBINVALIDDATES'));
		}

		// validate guests
		if (!is_array($adults) || !count($adults)) {
			// invalid adults
			VBOHttpDocument::getInstance()->close(400, JText::translate('VBINCONGRDATA'));
		}

		// prepare customer information
		$res_custdata  = array();
		$t_first_name  = '';
		$t_last_name   = '';
		$guest_email   = '';
		$guest_phone   = '';
		$guest_country = '';
		$guest_extras  = array();
		$guest_custom  = array();
		foreach ($inquiry as $info_type => $info_vals) {
			if (empty($info_type) || $info_type == 'checkbox') {
				// we ignore any checkbox information
				continue;
			}
			foreach ($info_vals as $info_val) {
				if (!is_scalar($info_val) || !strlen($info_val)) {
					// empty or invalid field
					continue;
				}
				if ($info_type == 'nominative') {
					if (empty($t_first_name)) {
						$t_first_name = $info_val;
						$res_custdata[JText::translate('VBNAME')] = $info_val;
					} else {
						$t_last_name = $info_val;
						$res_custdata[JText::translate('VBLNAME')] = $info_val;
					}
				} elseif ($info_type == 'email') {
					$guest_email = $info_val;
					$res_custdata[JText::translate('ORDER_EMAIL')] = $info_val;
				} elseif ($info_type == 'phone') {
					$guest_phone = $info_val;
					$res_custdata[JText::translate('ORDER_PHONE')] = $info_val;
				} elseif ($info_type == 'country') {
					$guest_country = $info_val;
					$res_custdata[JText::translate('ORDER_STATE')] = $info_val;
				} elseif ($info_type == 'city') {
					$guest_extras['city'] = $info_val;
					$res_custdata[JText::translate('ORDER_CITY')] = $info_val;
				} else {
					// we treat this as a custom data field
					$guest_custom[$info_type] = $info_val;
					// inject reservation custdata string for this value
					if ($info_type == 'special_requests') {
						$res_custdata[JText::translate('ORDER_SPREQUESTS')] = $info_val;
					} else {
						$readable_ftype = ucwords(str_replace(array('_', '-'), ' ', $info_type));
						$res_custdata[$readable_ftype] = $info_val;
					}
				}
			}
		}

		// validate fields
		if (!count($res_custdata)) {
			// we received no filled information
			VBOHttpDocument::getInstance()->close(400, JText::translate('VBINCONGRDATA'));
		}

		// build the reservation raw-text for the customer data
		$res_custdata_str = VikBooking::buildCustData($res_custdata, "\n");

		// store the customer record as first thing
		$cpin = VikBooking::getCPinIstance();
		$cpin->setCustomerExtraInfo($guest_extras);
		$cpin->saveCustomerDetails($t_first_name, $t_last_name, $guest_email, $guest_phone, $guest_country, array());

		// build customer object for the inquiry reservation
		$customer = new stdClass;
		$customer->name = $t_first_name;
		$customer->lname = $t_last_name;
		$customer->email = $guest_email;
		$customer->phone = $guest_phone;
		$customer->country = $guest_country;
		$customer->lang = $ulang;
		$customer->custdata = $res_custdata_str;
		$customer->adminnotes = isset($guest_custom['special_requests']) ? $guest_custom['special_requests'] : '';
		/**
		 * Always append the originally selected stay dates and guest party to the
		 * administrator notes string so that they can be accessed all the times.
		 */
		$customer->adminnotes .= !empty($customer->adminnotes) ? "\n" : '';
		$customer->adminnotes .= JText::translate('VBPICKUP') . ': ' . $checkin_dt . "\n";
		$customer->adminnotes .= JText::translate('VBRETURN') . ': ' . $checkout_dt . "\n";
		$customer->adminnotes .= JText::translate('VBFORMADULTS') . ': ' . array_sum($adults) . "\n";
		$customer->adminnotes .= JText::translate('VBFORMCHILDREN') . ': ' . array_sum($children) . "\n";

		// prepare response object
		$response = new stdClass;
		$response->status = 0;
		$response->error  = '';

		// invoke availability helper class
		$av_helper = VikBooking::getAvailabilityInstance();

		// turn flag on to ignore restrictions, as this is an inquiry and we must allocate the booking
		$av_helper->ignoreRestrictions(true);

		// increase the default number of back and forth days for alternative date suggestions
		$av_helper->setBackForthDays(90);

		// set stay dates
		$av_helper->setStayDates($checkin_dt, $checkout_dt);

		// set room parties, but we expect to always have one room for the inquiry
		foreach ($adults as $k => $num_adults) {
			$num_children = isset($children[$k]) ? $children[$k] : 0;
			$av_helper->setRoomParty($num_adults, $num_children);
		}

		// load available room rates
		$room_rates = $av_helper->getRates();

		// check if availability errors occurred
		$has_av_error  = strlen($av_helper->getError());
		$av_error_code = $av_helper->getErrorCode();

		// count total fitting records
		$tot_records = is_array($room_rates) && !$has_av_error ? count($room_rates) : 0;

		// build history extra data base object
		$ymd_stay_dates  = $av_helper->getStayDates();
		$hist_extra_data = new stdClass;
		$hist_extra_data->checkin_date  = $ymd_stay_dates[0];
		$hist_extra_data->checkout_date = $ymd_stay_dates[1];
		$hist_extra_data->adults = array_sum($adults);
		$hist_extra_data->children = array_sum($children);

		if ($tot_records > 0) {
			// we can create an inquiry pending reservation for a room rate
			$inquiry_res_id = 0;
			foreach ($room_rates as $rid => $rates) {
				foreach ($rates as $room_rplan) {
					if (!is_array($room_rplan) || !isset($room_rplan['idroom'])) {
						continue;
					}
					// we grab the cheapest room and cheapest rate plan (the first room-rate plan)
					$inquiry_res_id = $av_helper->createInquiryReservation($room_rplan, $customer);
					break;
				}
				// make sure to create just one inquiry reservation for the cheapest room available
				if ($inquiry_res_id) {
					break;
				}
			}

			if ($inquiry_res_id) {
				// assign booking to customer
				$cpin->saveCustomerBooking($inquiry_res_id);

				// send email notification to admin
				VikBooking::sendBookingEmail($inquiry_res_id, array('admin'));

				// trigger SMS sending
				VikBooking::sendBookingSMS($inquiry_res_id);

				// booking history (set inquiry availability type to 1)
				$hist_extra_data->av_type = 1;
				VikBooking::getBookingHistoryInstance()->setBid($inquiry_res_id)->setExtraData($hist_extra_data)->store('IR', $customer->adminnotes);
			}

			// update the response status no matter what
			$response->status = 1;

			// output response and terminate the request
			VBOHttpDocument::getInstance()->json($response);
		}

		// rely on suggestions in case of no rooms available
		if (!is_array($room_rates) || $has_av_error) {
			// try to get the suggestions when no availability
			list($alternative_dates, $alternative_parties) = $av_helper->findSuggestions();

			$inquiry_res_id = 0;
			$alt_suggestion = '';

			if (count($alternative_dates)) {
				$inquiry_res_id = $av_helper->allocateAltDatesInquiry($alternative_dates, $customer);
				$alt_suggestion = JText::translate('VBO_ALT_DATES_INQ');
				// set inquiry availability type to 2 for alternative dates
				$hist_extra_data->av_type = 2;
			} elseif (count($alternative_parties)) {
				$inquiry_res_id = $av_helper->allocateAltPartyInquiry($alternative_parties, $customer);
				$alt_suggestion = JText::translate('VBO_ALT_PARTY_INQ');
				// set inquiry availability type to 3 for alternative party
				$hist_extra_data->av_type = 3;
			}

			if ($inquiry_res_id) {
				// assign booking to customer
				$cpin->saveCustomerBooking($inquiry_res_id);

				// send email notification to admin
				VikBooking::sendBookingEmail($inquiry_res_id, array('admin'));

				// trigger SMS sending
				VikBooking::sendBookingSMS($inquiry_res_id);

				// booking history
				$history_obj = VikBooking::getBookingHistoryInstance()->setBid($inquiry_res_id);
				// store first history record
				$history_obj->store('IR', $customer->adminnotes);
				// store history record mentioning the suggestion used
				$history_obj->setExtraData($hist_extra_data)->store('IR', $alt_suggestion);
			}

			// update the response status no matter what
			$response->status = 1;

			// output response and terminate the request
			VBOHttpDocument::getInstance()->json($response);
		}

		/**
		 * If we reach this point it means that no rooms were available and no rooms/dates could be
		 * suggested as an alternative party. Therefore, we allocate the reservation on a "dummy room".
		 */
		$all_rooms = $av_helper->loadRooms();
		if (count($all_rooms)) {
			// grab the first "dummy room"
			$dummy_room_id = key($all_rooms);
			$room_rplan = array(
				'idroom' => $dummy_room_id,
			);

			// create inquiry reservation for a dummy room
			$inquiry_res_id = $av_helper->createInquiryReservation($room_rplan, $customer);
			$alt_suggestion = JText::translate('VBO_ALT_DUMMY_INQ');
			// set inquiry availability type to 4 for "dummy room"
			$hist_extra_data->av_type = 4;

			if ($inquiry_res_id) {
				// assign booking to customer
				$cpin->saveCustomerBooking($inquiry_res_id);

				// send email notification to admin
				VikBooking::sendBookingEmail($inquiry_res_id, array('admin'));

				// trigger SMS sending
				VikBooking::sendBookingSMS($inquiry_res_id);

				// booking history
				$history_obj = VikBooking::getBookingHistoryInstance()->setBid($inquiry_res_id);
				// store first history record
				$history_obj->store('IR', $customer->adminnotes);
				// store history record mentioning the suggestion used
				$history_obj->setExtraData($hist_extra_data)->store('IR', $alt_suggestion);

				// update the response status
				$response->status = 1;

				// output response and terminate the request
				VBOHttpDocument::getInstance()->json($response);
			}
		}

		// if not even a dummy room could be allocated, it means Vik Booking isn't set up
		$response->error = $av_helper->explainErrorCode();
		if (empty($response->error)) {
			$response->error = 'No rooms have been configured on this site yet';
		}

		// output response and terminate the request
		VBOHttpDocument::getInstance()->json($response);
	}

	/**
	 * AJAX endpoint to load the states of a given country.
	 * 
	 * @return 	void
	 * 
	 * @since 	1.16.0 (J) - 1.6.0 (WP)
	 */
	public function states_load_from_country()
	{
		$dbo = JFactory::getDbo();

		$id_country 	= VikRequest::getInt('id_country', 0, 'request');
		$country_3_code = VikRequest::getString('country_3_code', '', 'request');
		$country_2_code = VikRequest::getString('country_2_code', '', 'request');
		$country_name 	= VikRequest::getString('country_name', '', 'request');

		if (empty($id_country) && empty($country_3_code) && empty($country_2_code) && empty($country_name)) {
			VBOHttpDocument::getInstance()->close(500, 'Missing country identifier');
		}

		if (!empty($id_country)) {
			$q = "SELECT * FROM `#__vikbooking_states` WHERE `id_country`=" . $id_country;
			$dbo->setQuery($q);
			$dbo->execute();
			if (!$dbo->getNumRows()) {
				// no records found for this country
				VBOHttpDocument::getInstance()->json([]);
			}
			// output the JSON encoded list of states found
			VBOHttpDocument::getInstance()->json($dbo->loadAssocList());
		}

		// find country ID by name or code
		$field_name = $dbo->qn('country_name');
		$field_value = $country_name;
		if (!empty($country_3_code)) {
			$field_name = $dbo->qn('country_3_code');
			$field_value = $country_3_code;
		}
		if (!empty($country_2_code)) {
			$field_name = $dbo->qn('country_2_code');
			$field_value = $country_2_code;
		}

		$q = "SELECT `id` FROM `#__vikbooking_countries` WHERE {$field_name}=" . $dbo->quote($field_value);
		$dbo->setQuery($q, 0, 1);
		$dbo->execute();
		if (!$dbo->getNumRows()) {
			// country not found
			VBOHttpDocument::getInstance()->close(404, sprintf('Country [%s] not found', $field_value));
		}

		$id_country = $dbo->loadResult();

		$q = "SELECT * FROM `#__vikbooking_states` WHERE `id_country`=" . $id_country;
		$dbo->setQuery($q);
		$dbo->execute();
		if (!$dbo->getNumRows()) {
			// no records found for this country
			VBOHttpDocument::getInstance()->json([]);
		}
		// output the JSON encoded list of states found
		VBOHttpDocument::getInstance()->json($dbo->loadAssocList());
	}

	/**
	 * AJAX endpoint to perform the room upgrade operation.
	 * 
	 * @return 	void
	 * 
	 * @since 	1.16.0 (J) - 1.6.0 (WP)
	 */
	public function upgrade_room()
	{
		$dbo = JFactory::getDbo();

		$bid = VikRequest::getInt('bid', 0, 'request');
		$sid = VikRequest::getString('sid', '', 'request');
		$ts = VikRequest::getString('ts', '', 'request');
		$room_index = VikRequest::getInt('room_index', 0, 'request');
		$room_id = VikRequest::getInt('room_id', 0, 'request');

		if (empty($room_id)) {
			VBOHttpDocument::getInstance()->close(500, 'Invalid data provided');
		}

		$q = "SELECT * FROM `#__vikbooking_orders` WHERE `id`={$bid} AND (`sid`=" . $dbo->q($sid) . " OR `idorderota`=" . $dbo->q($sid) . ") AND `ts`=" . $dbo->q($ts) . " AND `status`='confirmed'";
		$dbo->setQuery($q, 0, 1);
		$dbo->execute();
		if (!$dbo->getNumRows()) {
			VBOHttpDocument::getInstance()->close(404, JText::translate('VBORDERNOTFOUND'));
		}

		$booking = $dbo->loadAssoc();

		$booking_rooms = VikBooking::loadOrdersRoomsData($booking['id']);
		if (!$booking_rooms || !isset($booking_rooms[$room_index])) {
			VBOHttpDocument::getInstance()->close(404, JText::translate('VBORDERNOTFOUND'));
		}

		// access the room helper object
		$room_helper = VBORoomHelper::getInstance([
			'booking' => $booking,
			'rooms'   => $booking_rooms,
		]);

		// load the upgrade options for this booking
		$upgrade_options = $room_helper->getUpgradeOptions();

		if (!$upgrade_options || !isset($upgrade_options['upgrade'][$room_index]) || !isset($upgrade_options['upgrade'][$room_index]['r_costs'][$room_id])) {
			// the room for the upgrade is not available
			VBOHttpDocument::getInstance()->close(500, 'Could not upgrade to the selected room. Please reload the page and try again');
		}

		$upgrade_room_rate = $upgrade_options['upgrade'][$room_index]['r_costs'][$room_id];

		// calculate the cost difference, if any
		$current_room_cost  = $booking_rooms[$room_index]['cust_cost'] > 0 ? (float)$booking_rooms[$room_index]['cust_cost'] : (float)$booking_rooms[$room_index]['room_cost'];
		$upgrade_room_cost  = $current_room_cost;
		$upgrade_difference = 0;
		$upgtax_difference  = 0;

		if ($current_room_cost < $upgrade_room_rate['upgrade_cost']) {
			// we update the total booking amount only if the upgrade room is more expensive
			$upgrade_room_cost  = $upgrade_room_rate['upgrade_cost'];
			$upgrade_difference = $upgrade_room_rate['upgrade_cost'] - $current_room_cost;
			// handle taxes
			$current_tariff_data = VBORoomHelper::getInstance()->getTariffData($booking_rooms[$room_index]['idtar']);
			if ($current_tariff_data) {
				// current room tax
				$current_cost_plus_tax = VikBooking::sayCostPlusIva($current_room_cost, $current_tariff_data['idprice']);
				$current_cost_minus_tax = VikBooking::sayCostMinusIva($current_room_cost, $current_tariff_data['idprice']);
				$current_room_tax = $current_cost_plus_tax - $current_cost_minus_tax;
				// upgrade room tax
				$upgrade_cost_plus_tax = VikBooking::sayCostPlusIva($upgrade_room_rate['upgrade_cost'], $upgrade_room_rate['idprice']);
				$upgrade_cost_minus_tax = VikBooking::sayCostMinusIva($upgrade_room_rate['upgrade_cost'], $upgrade_room_rate['idprice']);
				$upgrade_room_tax = $upgrade_cost_plus_tax - $upgrade_cost_minus_tax;
				// calculate tax difference
				$upgtax_difference = $upgrade_room_tax - $current_room_tax;
			}
			
		}

		// update booking total amounts as first
		if ($upgrade_difference > 0) {
			$upd_booking = new stdClass;
			$upd_booking->id = $booking['id'];
			$upd_booking->total = (float)($booking['total'] + $upgrade_difference);
			$upd_booking->tot_taxes = (float)($booking['tot_taxes'] + $upgtax_difference);

			$dbo->updateObject('#__vikbooking_orders', $upd_booking, 'id');
		}

		// perform the room upgrade (switch)
		$broom_record = new stdClass;
		$broom_record->id = $booking_rooms[$room_index]['id'];
		$broom_record->idorder = $booking_rooms[$room_index]['idorder'];
		$broom_record->idroom = $room_id;
		$broom_record->idtar = $upgrade_room_rate['id'];
		$broom_record->room_cost = $upgrade_room_cost;

		$dbo->updateObject('#__vikbooking_ordersrooms', $broom_record, ['id', 'idorder']);

		// Booking History
		$hist_descr = !empty($booking_rooms[$room_index]['room_name']) ? $booking_rooms[$room_index]['room_name'] . ' ' : '';
		$hist_descr .= '-&gt; ' . $upgrade_options['rooms'][$room_id]['name'];
		VikBooking::getBookingHistoryInstance()->setBid($booking['id'])->store('UR', $hist_descr);

		// output the JSON encoded successful response
		VBOHttpDocument::getInstance()->json(['success' => 1]);
	}
}
