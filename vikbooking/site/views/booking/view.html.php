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

class VikbookingViewBooking extends JViewVikBooking
{
	public function display($tpl = null)
	{
		$dbo = JFactory::getDbo();
		$app = JFactory::getApplication();
		$document = JFactory::getDocument();
		$vbo_tn = VikBooking::getTranslator();
		
		// validation of data and availability before the rendering
		$sid = VikRequest::getString('sid', '', 'request');
		$ts = VikRequest::getString('ts', '', 'request');
		if (empty($sid) || empty($ts)) {
			showSelectVb(JText::translate('VBINSUFDATA'));
			return;
		}

		$q = "SELECT `o`.*,(SELECT SUM(`or`.`adults`) FROM `#__vikbooking_ordersrooms` AS `or` WHERE `or`.`idorder`=`o`.`id`) AS `tot_adults` FROM `#__vikbooking_orders` AS `o` WHERE (`o`.`sid`=" . $dbo->quote($sid) . " OR `o`.`idorderota`=" . $dbo->quote($sid) . ") AND `o`.`ts`=" . $dbo->quote($ts);
		$dbo->setQuery($q, 0, 1);
		$order = $dbo->loadAssoc();
		if (!$order) {
			showSelectVb(JText::translate('VBORDERNOTFOUND'));
			return;
		}

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

		if ($order['status'] == 'confirmed') {
			// prepare impression data for channels
			$impressiondata = $order;
			$q = "SELECT `or`.`idtar`,`d`.`idprice`,`p`.`idiva`,`t`.`aliq`,`t`.`taxcap` FROM `#__vikbooking_ordersrooms` AS `or` " .
				"LEFT JOIN `#__vikbooking_dispcost` `d` ON `d`.`id`=`or`.`idtar` " . 
				"LEFT JOIN `#__vikbooking_prices` `p` ON `p`.`id`=`d`.`idprice` " . 
				"LEFT JOIN `#__vikbooking_iva` `t` ON `t`.`id`=`p`.`idiva` " . 
				"WHERE `or`.`idorder`='".$order['id']."' ORDER BY `t`.`aliq` ASC";
			$dbo->setQuery($q, 0, 1);
			$taxdata = $dbo->loadAssoc();
			if ($taxdata) {
				$taxes = 0;
				if (!empty($taxdata['aliq'])) {
					$realtotal = round(($order['total'] / ((100 + $taxdata['aliq']) / 100)), 2);
					$taxes = round(($order['total'] - $realtotal), 2);
					/**
					 * Tax Cap implementation.
					 * 
					 * @since 	1.12
					 */
					if ($taxdata['taxcap'] > 0 && $taxes > $taxdata['taxcap']) {
						$realtotal = $order['total'] - $taxdata['taxcap'];
						$taxes = $taxdata['taxcap'];
					}
					$impressiondata['total'] = $realtotal;
				}
				$impressiondata['taxes'] = $taxes;
				$impressiondata['fees'] = 0;
			}
			VikBooking::invokeChannelManager(true, $impressiondata);
			// end prepare impression data for channels
		} elseif ($order['status'] == 'standby') {
			$roomavail = false;

			$q = $dbo->getQuery(true)
				->select($dbo->qn('or') . '.*')
				->select($dbo->qn('r.units'))
				->from($dbo->qn('#__vikbooking_ordersrooms', 'or'))
				->leftJoin($dbo->qn('#__vikbooking_rooms', 'r') . ' ON ' . $dbo->qn('or.idroom') . ' = ' . $dbo->qn('r.id'))
				->where($dbo->qn('or.idorder') . ' = ' . (int)$order['id'])
				->order($dbo->qn('or.id') . ' ASC');

			$dbo->setQuery($q);
			$orderrooms = $dbo->loadAssocList();

			/**
			 * Even if redundant, we read the eventually occupied records by this booking ID to ensure
			 * server-side cache will not produce a booking cancellation after receiving a valid payment.
			 * An eventual list of busy record IDs is passed to the method for checking if the room is
			 * available, because the status of the reservation may actually be confirmed already.
			 * 
			 * @since 	1.16.9 (J) - 1.6.9 (WP)
			 */
			$dbo->setQuery(
				$dbo->getQuery(true)
					->select($dbo->qn('idbusy'))
					->from($dbo->qn('#__vikbooking_ordersbusy'))
					->where($dbo->qn('idorder') . ' = ' . (int)$order['id'])
			);
			$skip_busy_ids = array_column($dbo->loadAssocList(), 'idbusy');

			foreach ($orderrooms as $kor => $or) {
				// determine proper values for this room
				$room_stay_checkin  = $order['checkin'];
				$room_stay_checkout = $order['checkout'];
				if ($order['split_stay'] && $room_stay_dates && isset($room_stay_dates[$kor]) && $room_stay_dates[$kor]['idroom'] == $or['idroom']) {
					$room_stay_checkin  = !empty($room_stay_dates[$kor]['checkin_ts']) ? $room_stay_dates[$kor]['checkin_ts'] : $room_stay_dates[$kor]['checkin'];
					$room_stay_checkout = !empty($room_stay_dates[$kor]['checkout_ts']) ? $room_stay_dates[$kor]['checkout_ts'] : $room_stay_dates[$kor]['checkout'];
				}

				// make sure room is still available
				$roomavail = VikBooking::roomBookable($or['idroom'], $or['units'], $room_stay_checkin, $room_stay_checkout, $skip_busy_ids);
				if (!$roomavail) {
					break;
				}
			}

			$today_midnight = mktime(0, 0, 0, date('n'), date('j'), date('Y'));
			$autoremove = false;
			if ($today_midnight > $order['checkin']) {
				$roomavail = false;
			}
			$minautoremove = VikBooking::getMinutesAutoRemove();
			$mins_elapsed = floor((time() - $order['ts']) / 60);
			if ($minautoremove > 0 && $mins_elapsed > $minautoremove) {
				$roomavail = false;
				$autoremove = true;
			}

			/**
			 * Make sure not to cancel an OTA pending reservation.
			 * 
			 * @since 	1.15.4 (J) - 1.5.10 (WP)
			 */
			$is_ota_pending = (!empty($order['type']) && !empty($order['channel']));

			if ($roomavail || $is_ota_pending) {
				// invoke channel impression
				VikBooking::invokeChannelManager(false);
				//
			} else {
				// set the booking to cancelled
				$q = "UPDATE `#__vikbooking_orders` SET `status`='cancelled' WHERE `id`=".(int)$order['id'].";";
				$dbo->setQuery($q);
				$dbo->execute();
				// update status in the array
				$order['status'] = 'cancelled';
				//
				$q = "DELETE FROM `#__vikbooking_tmplock` WHERE `idorder`=" . (int)$order['id'] . ";";
				$dbo->setQuery($q);
				$dbo->execute();
				$q = "SELECT * FROM `#__vikbooking_ordersbusy` WHERE `idorder`=".(int)$order['id'].";";
				$dbo->setQuery($q);
				$ordbusy = $dbo->loadAssocList();
				if ($ordbusy) {
					foreach ($ordbusy as $ob) {
						$q = "DELETE FROM `#__vikbooking_busy` WHERE `id`=".(int)$ob['idbusy'].";";
						$dbo->setQuery($q);
						$dbo->execute();
					}
				}
				$q = "DELETE FROM `#__vikbooking_ordersbusy` WHERE `idorder`=".(int)$order['id'].";";
				$dbo->setQuery($q);
				$dbo->execute();

				/**
				 * We now save onto a var the full error message to understand what made the booking become cancelled.
				 * This way, by checking the booking history it will be easy to understand the cause of the issue.
				 * 
				 * @since 	October 15th 2020
				 */
				$history_err_descr = '';
				if ($today_midnight > $order['checkin']) {
					$history_err_descr = JText::translate('VBOBOOKNOLONGERPAYABLE');
				} elseif ($autoremove === true) {
					$history_err_descr = JText::translate('VBOERRAUTOREMOVED');
				} else {
					$history_err_descr = JText::translate('VBERRREPSEARCH');
				}
				if (!empty($history_err_descr)) {
					VikError::raiseWarning('', $history_err_descr);
				}

				//Booking History
				VikBooking::getBookingHistoryInstance()->setBid($order['id'])->store('CA', $history_err_descr);
				//
			}
		}

		// render the booking details

		// set noindex instruction for robots
		$document->setMetaData('robots', 'noindex,follow');

		// load jQuery
		if (VikBooking::loadJquery()) {
			JHtml::fetch('jquery.framework', true, true);
		}

		$tars = [];
		$cookie = $app->input->cookie;
		$pcheckin = $order['checkin'];
		$pcheckout = $order['checkout'];
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
		$is_package = !empty($order['pkg']) ? true : false;
		$orderrooms = [];
		$q = "SELECT `or`.`idroom`,`or`.`adults`,`or`.`children`,`or`.`idtar`,`or`.`optionals`,`or`.`roomindex`,`or`.`pkg_id`,`or`.`pkg_name`,`or`.`cust_cost`,`or`.`cust_idiva`,`or`.`extracosts`,`or`.`room_cost`,`or`.`otarplan`,`r`.`id` AS `r_reference_id`,`r`.`name`,`r`.`img`,`r`.`idcarat`,`r`.`fromadult`,`r`.`toadult` FROM `#__vikbooking_ordersrooms` AS `or`,`#__vikbooking_rooms` AS `r` WHERE `or`.`idorder`='".$order['id']."' AND `or`.`idroom`=`r`.`id` ORDER BY `or`.`id` ASC;";
		$dbo->setQuery($q);
		$orderrooms = $dbo->loadAssocList();
		if ($orderrooms) {
			$vbo_tn->translateContents($orderrooms, '#__vikbooking_rooms', array('id' => 'r_reference_id'));
			foreach ($orderrooms as $kor => $or) {
				$num = $kor + 1;
				if ($is_package === true || (!empty($or['cust_cost']) && $or['cust_cost'] > 0.00)) {
					//package or custom cost set from the back-end
					continue;
				}
				$q = "SELECT `t`.*,`p`.`name`,`p`.`free_cancellation`,`p`.`canc_deadline`,`p`.`canc_policy` FROM `#__vikbooking_dispcost` AS `t` LEFT JOIN `#__vikbooking_prices` AS `p` ON `t`.`idprice`=`p`.`id` WHERE `t`.`id`=" . (int)$or['idtar'] . ";";
				$dbo->setQuery($q);
				$tar = $dbo->loadAssocList();
				if (!$tar) {
					continue;
				}
				$vbo_tn->translateContents($tar, '#__vikbooking_prices', array('id' => 'idprice'));

				// determine proper values for this room
				$room_stay_checkin  = $order['checkin'];
				$room_stay_checkout = $order['checkout'];
				$room_stay_nights 	= $order['days'];
				if ($order['split_stay'] && count($room_stay_dates) && isset($room_stay_dates[$kor]) && $room_stay_dates[$kor]['idroom'] == $or['idroom']) {
					$room_stay_checkin  = !empty($room_stay_dates[$kor]['checkin_ts']) ? $room_stay_dates[$kor]['checkin_ts'] : $room_stay_dates[$kor]['checkin'];
					$room_stay_checkout = !empty($room_stay_dates[$kor]['checkout_ts']) ? $room_stay_dates[$kor]['checkout_ts'] : $room_stay_dates[$kor]['checkout'];
					$room_stay_nights 	= $av_helper->countNightsOfStay($room_stay_checkin, $room_stay_checkout);
				}

				// apply seasonal rates
				$tar = VikBooking::applySeasonsRoom($tar, $room_stay_checkin, $room_stay_checkout);

				// apply OBP rules
				$tar = VBORoomHelper::getInstance()->applyOBPRules($tar, $or, $or['adults']);

				// push tariffs
				$tars[$num] = $tar[0];
			}
		}

		$days_to_arrival = 0;
		$now_info = getdate();
		$checkin_info = getdate($order['checkin']);
		if ($now_info[0] < $checkin_info[0]) {
			while ($now_info[0] < $checkin_info[0]) {
				if (!($now_info['mday'] != $checkin_info['mday'] || $now_info['mon'] != $checkin_info['mon'] || $now_info['year'] != $checkin_info['year'])) {
					break;
				}
				$days_to_arrival++;
				$now_info = getdate(mktime(0, 0, 0, $now_info['mon'], ($now_info['mday'] + 1), $now_info['year']));
			}
		}

		$is_refundable = 0;
		$daysadv_refund_arr = [];
		$daysadv_refund = 0;
		$canc_policy = '';
		foreach ($tars as $num => $tar) {
			if ($tar['free_cancellation'] < 1) {
				// if at least one rate plan is non-refundable, the whole reservation cannot be cancelled
				$is_refundable = 0;
				$daysadv_refund_arr = [];
				break;
			}
			$is_refundable = 1;
			$daysadv_refund_arr[] = $tar['canc_deadline'];
		}

		// get the rate plan with the lowest cancellation deadline
		$daysadv_refund = count($daysadv_refund_arr) > 0 ? min($daysadv_refund_arr) : $daysadv_refund;
		if ($daysadv_refund > 0) {
			foreach ($tars as $num => $tar) {
				if ($tar['free_cancellation'] > 0 && $tar['canc_deadline'] == $daysadv_refund) {
					//get the cancellation policy from the first rate plan with free cancellation and same cancellation deadline
					$canc_policy = $tar['canc_policy'];
					break;
				}
			}
		}

		$payment = "";
		if (!empty($order['idpayment'])) {
			$exppay = explode('=', $order['idpayment']);
			$payment = VikBooking::getPayment($exppay[0], $vbo_tn);
		}
		$pnodep = VikRequest::getString('nodep', '', 'request');
		$cnodep = $cookie->get('vboFA', '', 'string');
		$nodep = intval($pnodep) > 0 || intval($cnodep) > 0 ? 1 : 0;

		/**
		 * Upselling extra services only for non-cancelled bookings
		 * with a check-out date in the future.
		 * 
		 * @since 	1.13.0 (J) - 1.3.0 (WP)
		 */
		$upselling = [];
		if ($order['status'] != 'cancelled' && VikBooking::upsellingEnabled() && $order['checkout'] > time()) {
			$upsell_data = [];
			foreach ($orderrooms as $kor => $or) {
				$room_data = new stdClass;
				$room_data->id = $or['idroom'];
				$room_data->name = $or['name'];
				$room_data->img = $or['img'];
				$room_data->adults = $or['adults'];
				$room_data->children = $or['children'];
				$room_data->nights = $order['days'];
				$room_data->options = [];
				if (!empty($or['optionals'])) {
					$optids = explode(';', $or['optionals']);
					foreach ($optids as $optid) {
						if (!empty($optid)) {
							// the : may express the quantity
							$optidparts = explode(':', $optid);
							array_push($room_data->options, (int)$optidparts[0]);
						}
					}
				}
				array_push($upsell_data, $room_data);
			}
			$upselling = VikBooking::loadUpsellingData(
				$upsell_data, 
				array(
					'id' => $order['id'], 
					'checkin' => $order['checkin'], 
					'checkout' => $order['checkout'],
				), 
				$vbo_tn
			);
		}

		/**
		 * Trigger first tracking conversion on booking.
		 * 
		 * @since 	1.16.3 (J) - 1.6.3 (WP)
		 */
		VikBooking::getTracker()->triggerBookingConversion($order);

		$this->ord = $order;
		$this->orderrooms = $orderrooms;
		$this->tars = $tars;
		$this->days_to_arrival = $days_to_arrival;
		$this->is_refundable = $is_refundable;
		$this->daysadv_refund = $daysadv_refund;
		$this->canc_policy = $canc_policy;
		$this->payment = $payment;
		$this->nodep = $nodep;
		$this->upselling = $upselling;
		$this->vbo_tn = $vbo_tn;

		// theme
		$theme = VikBooking::getTheme();
		if ($theme != 'default') {
			$thdir = VBO_SITE_PATH . DIRECTORY_SEPARATOR . 'themes' . DIRECTORY_SEPARATOR . $theme . DIRECTORY_SEPARATOR . 'booking';
			if (is_dir($thdir)) {
				$this->_setPath('template', $thdir . DIRECTORY_SEPARATOR);
			}
		}
		//

		parent::display($tpl);
	}
}
