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

class VikBookingViewCalendar extends JViewVikBooking
{
	public function display($tpl = null)
	{
		// Set the toolbar
		$this->addToolBar();

		if (!JFactory::getUser()->authorise('core.vbo.availability', 'com_vikbooking') && !JFactory::getUser()->authorise('core.vbo.bookings', 'com_vikbooking')) {
			VBOHttpDocument::getInstance()->close(403, JText::translate('JERROR_ALERTNOAUTHOR'));
		}

		$cid = VikRequest::getVar('cid', array(0));
		$aid = $cid[0];

		$app = JFactory::getApplication();
		$dbo = JFactory::getDbo();
		$session = JFactory::getSession();

		$rid = $session->get('vbCalRid', '');
		$aid = !empty($rid) && empty($aid) ? $rid : $aid;
		if (empty($aid)) {
			$q = "SELECT `id` FROM `#__vikbooking_rooms` ORDER BY `#__vikbooking_rooms`.`name` ASC";
			$dbo->setQuery($q, 0, 1);
			$aid = $dbo->loadResult();
		}
		if (empty($aid)) {
			VikError::raiseWarning('', 'No Rooms.');
			$app->redirect("index.php?option=com_vikbooking&task=rooms");
			$app->close();
		}

		$session->set('vbCalRid', $aid);
		$pvmode = VikRequest::getString('vmode', '', 'request');
		$cur_vmode = $session->get('vikbookingvmode', "");
		if (!empty($pvmode) && ctype_digit($pvmode)) {
			$session->set('vikbookingvmode', $pvmode);
		} elseif (empty($cur_vmode)) {
			$session->set('vikbookingvmode', "12");
		}
		$vmode = (int)$session->get('vikbookingvmode', "12");

		// new reservation ID default status
		$new_res_id = 0;

		$q = "SELECT `id`,`name`,`img`,`units` FROM `#__vikbooking_rooms` WHERE `id`=".$dbo->quote($aid);
		$dbo->setQuery($q, 0, 1);
		$room = $dbo->loadAssoc();
		if (!$room) {
			VikError::raiseWarning('', 'No Rooms.');
			$app->redirect("index.php?option=com_vikbooking&task=rooms");
			$app->close();
		}

		$q = "SELECT `id`,`name`,`units` FROM `#__vikbooking_rooms` ORDER BY `#__vikbooking_rooms`.`name` ASC;";
		$dbo->setQuery($q);
		$allc = $dbo->loadAssocList();

		$q = "SELECT `id`,`name` FROM `#__vikbooking_gpayments` ORDER BY `#__vikbooking_gpayments`.`name` ASC;";
		$dbo->setQuery($q);
		$payments = $dbo->loadAssocList();

		/**
		 * Split stay data for booking.
		 * 
		 * @since 	1.16.0 (J) - 1.6.0 (WP)
		 */
		$split_stay_data = VikRequest::getVar('split_stay_data', array());

		$actnow = time();
		$forced_reason = '';
		$forcebooking = VikRequest::getInt('forcebooking', 0, 'request');
		$pcheckindate = VikRequest::getString('checkindate', '', 'request');
		$pcheckoutdate = VikRequest::getString('checkoutdate', '', 'request');
		$pcheckinh = VikRequest::getString('checkinh', '', 'request');
		$pcheckinm = VikRequest::getString('checkinm', '', 'request');
		$pcheckouth = VikRequest::getString('checkouth', '', 'request');
		$pcheckoutm = VikRequest::getString('checkoutm', '', 'request');
		$pcustdata = VikRequest::getString('custdata', '', 'request');
		$pcustmail = VikRequest::getString('custmail', '', 'request');
		$padults = VikRequest::getInt('adults', 0, 'request');
		$pchildren = VikRequest::getInt('children', 0, 'request');
		$psetclosed = VikRequest::getInt('setclosed', 0, 'request');
		$psetunitsclosed = VikRequest::getInt('setunitsclosed', 0, 'request');
		$num_rooms = VikRequest::getInt('num_rooms', 0, 'request');
		$num_rooms = empty($num_rooms) || $num_rooms <= 0 ? 1 : $num_rooms;
		$pordstatus = VikRequest::getString('newstatus', '', 'request');
		$pordstatus = (empty($pordstatus) || !in_array($pordstatus, array('confirmed', 'standby')) ? 'confirmed' : $pordstatus);
		$pordstatus = intval($psetclosed) > 0 ? 'confirmed' : $pordstatus;
		$pcountrycode = VikRequest::getString('countrycode', '', 'request');
		$pstate = VikRequest::getString('state', '', 'request');
		$pt_first_name = VikRequest::getString('t_first_name', '', 'request');
		$pt_last_name = VikRequest::getString('t_last_name', '', 'request');
		$pphone = VikRequest::getString('phone', '', 'request');
		$pcustomer_id = VikRequest::getInt('customer_id', 0, 'request');
		$ppaymentid = VikRequest::getString('payment', '', 'request');
		$pcust_cost = VikRequest::getFloat('cust_cost', 0, 'request');
		$proomcost = VikRequest::getFloat('roomcost', 0, 'request');
		$pidprice = VikRequest::getInt('idprice', 0, 'request');
		$id_tariff = 0;
		$ptotalpnight = VikRequest::getString('totalpnight', 'total', 'request');
		$ptaxid = VikRequest::getInt('taxid', 0, 'request');
		$pcloseothers = VikRequest::getVar('closeothers', array());

		/**
		 * Mark the units as closed in case of a regular reservation for no customers.
		 * 
		 * @since 	1.16.6 (J) - 1.6.6 (WP)
		 */
		if ($psetunitsclosed && ($psetclosed || $pordstatus != 'confirmed' || $pcustomer_id)) {
			$psetunitsclosed = 0;
		}

		$paymentmeth = '';
		if (!empty($ppaymentid) && $payments) {
			foreach ($payments as $pay) {
				if (intval($pay['id']) == intval($ppaymentid)) {
					$paymentmeth = $pay['id'].'='.$pay['name'];
					break;
				}
			}
		}

		// check if a new booking should be created
		if (!empty($pcheckindate) && !empty($pcheckoutdate)) {
			// validate basic information submitted
			$can_create_res = true;
			if (!VikBooking::dateIsValid($pcheckindate) || !VikBooking::dateIsValid($pcheckoutdate)) {
				$can_create_res = false;
				VikError::raiseWarning('', JText::translate('ERRINVDATESEASON'));
			}

			// get stay timestamps
			$first = VikBooking::getDateTimestamp($pcheckindate, $pcheckinh, $pcheckinm);
			$second = VikBooking::getDateTimestamp($pcheckoutdate, $pcheckouth, $pcheckoutm);

			if ($first >= $second) {
				$can_create_res = false;
				VikError::raiseWarning('', 'Invalid Dates: current server time is '.date('Y-m-d H:i', $actnow).'. Reservation requested from '.date('Y-m-d H:i', $first).' to '.date('Y-m-d H:i', $second));
			}

			// count nights of stay
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

			if ($can_create_res) {
				/**
				 * Get an instance of the reservation model by injecting all values.
				 * 
				 * @since 	1.16.0 (J) - 1.6.0 (WP)
				 */
				$model_res = VBOModelReservation::getInstance([
					'force_booking' => $forcebooking,
					'split_stay' 	=> $split_stay_data,
					'set_closed' 	=> $psetclosed,
					'units_closed'  => $psetunitsclosed,
					'close_others' 	=> $pcloseothers,
					'status' 		=> $pordstatus,
					'checkin' 		=> $first,
					'checkout' 		=> $second,
					'checkin_h' 	=> $pcheckinh,
					'checkin_m' 	=> $pcheckinm,
					'checkout_h' 	=> $pcheckouth,
					'checkout_m' 	=> $pcheckoutm,
					'nights' 		=> $daysdiff,
					'num_rooms' 	=> $num_rooms,
					'adults' 		=> $padults,
					'children' 		=> $pchildren,
					'id_payment' 	=> $paymentmeth,
				])->setCustomer([
					'id' 		 => $pcustomer_id,
					'first_name' => $pt_first_name,
					'last_name'  => $pt_last_name,
					'data' 		 => $pcustdata,
					'email' 	 => $pcustmail,
					'country' 	 => $pcountrycode,
					'state' 	 => $pstate,
					'phone' 	 => $pphone,
				])->setRoom([
					'id' 			  => $room['id'],
					'cust_cost' 	  => $pcust_cost,
					'total_or_pnight' => $ptotalpnight,
					'room_cost' 	  => $proomcost,
					'id_price' 		  => $pidprice,
					'id_tax' 		  => $ptaxid,
				]);

				// store the reservation
				$model_res->create();

				// get the new booking ID
				$res_id = $model_res->getNewBookingID();
				if (!$res_id) {
					VikError::raiseWarning('', $model_res->getError());
					$app->redirect("index.php?option=com_vikbooking&task=calendar");
					$app->close();
				}

				// check if any action for the Channel Manager should be displayed
				$vcm_action = $model_res->getChannelManagerAction();
				if ($vcm_action) {
					VikError::raiseNotice('', $vcm_action);
				}

				if ($pordstatus == 'standby') {
					// redirect only if the booking status is pending
					$app->enqueueMessage(JText::translate('VBQUICKRESWARNSTANDBY'));
					$app->redirect("index.php?option=com_vikbooking&task=editbusy&cid[]=" . $res_id);
					$app->close();
				}

				// set new reservation ID
				$new_res_id = $res_id;
			}
		}

		$mints = mktime(0, 0, 0, date('m'), 1, date('Y'));
		$q = "SELECT `b`.*,`ob`.`idorder`,`o`.`closure` FROM `#__vikbooking_busy` AS `b` LEFT JOIN `#__vikbooking_ordersbusy` `ob` ON `ob`.`idbusy`=`b`.`id` LEFT JOIN `#__vikbooking_orders` `o` ON `o`.`id`=`ob`.`idorder` WHERE `b`.`idroom`=".(int)$room['id']." AND (`b`.`checkin`>=".$mints." OR `b`.`checkout`>=".$mints.") AND `ob`.`idorder` IS NOT NULL;";
		$dbo->setQuery($q);
		$busy = $dbo->loadAssocList();

		$this->room = $room;
		$this->new_res_id = $new_res_id;
		$this->allc = $allc;
		$this->payments = $payments;
		$this->busy = $busy;
		$this->vmode = $vmode;

		// Display the template
		parent::display($tpl);
	}

	/**
	 * Sets the toolbar
	 */
	protected function addToolBar()
	{
		JToolBarHelper::title(JText::translate('VBMAINCALTITLE'), 'vikbooking');
		JToolBarHelper::cancel( 'canceledorder', JText::translate('VBBACK'));
		JToolBarHelper::spacer();
	}
}
