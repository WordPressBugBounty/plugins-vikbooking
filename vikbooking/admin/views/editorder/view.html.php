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

class VikBookingViewEditorder extends JViewVikBooking
{
	public function display($tpl = null)
	{
		// Set the toolbar
		$this->addToolBar();

		if (!JFactory::getUser()->authorise('core.vbo.bookings', 'com_vikbooking')) {
			VBOHttpDocument::getInstance()->close(403, JText::translate('JERROR_ALERTNOAUTHOR'));
		}

		$cid = VikRequest::getVar('cid', array(0));
		$ido = $cid[0];

		if (is_file(VCM_ADMIN_PATH.DIRECTORY_SEPARATOR.'assets'.DIRECTORY_SEPARATOR.'css'.DIRECTORY_SEPARATOR.'vcm-channels.css')) {
			$document = JFactory::getDocument();
			$document->addStyleSheet(VCM_ADMIN_URI.'assets/css/vikchannelmanager.css');
			$document->addStyleSheet(VCM_ADMIN_URI.'assets/css/vcm-channels.css');
		}

		$dbo = JFactory::getDbo();
		$app = JFactory::getApplication();
		$user = JFactory::getUser();

		$q = "SELECT * FROM `#__vikbooking_orders` WHERE `id`=" . $dbo->quote($ido);
		$dbo->setQuery($q, 0, 1);
		$row = $dbo->loadAssoc();
		if (!$row) {
			$app->redirect("index.php?option=com_vikbooking&task=orders");
			$app->close();
			return;
		}

		/**
		 * Check currency conversion request, if needed and supported.
		 * 
		 * @since 	1.15.0 (J) - 1.5.0 (WP)
		 */
		$do_ota_curr_conv = VikRequest::getInt('do_ota_curr_conv', 0, 'request');
		if ($do_ota_curr_conv) {
			$ota_helper = new VBOCurrencyOta($row);
			if (!$ota_helper->convertReservationCurrency()) {
				VikError::raiseWarning('', 'Something went wrong while trying to convert the OTA currency');
			}
			$app->redirect('index.php?option=com_vikbooking&task=editorder&cid[]=' . $row['id']);
			$app->close();
		}

		$q = "SELECT `id`,`name` FROM `#__vikbooking_gpayments` ORDER BY `#__vikbooking_gpayments`.`name` ASC;";
		$dbo->setQuery($q);
		$payments = $dbo->loadAssocList();

		$cpin = VikBooking::getCPinIstance();
		// VBO 1.11 - change customer assigned to this booking
		$pnewcustid = VikRequest::getInt('newcustid', 0, 'request');
		if (!empty($pnewcustid)) {
			$cpin->updateCustomerBooking($row['id'], $pnewcustid);
			// update empty values for the booking
			if (empty($row['custmail']) || empty($row['phone'])) {
				// get customer information
				$customer = $cpin->getCustomerByID($pnewcustid);
				if ($customer && (!empty($customer['email']) || !empty($customer['phone']))) {
					if (empty($row['custmail']) && !empty($customer['email'])) {
						$q = "UPDATE `#__vikbooking_orders` SET `custmail`=" . $dbo->quote($customer['email']) . " WHERE `id`=" . $row['id'] . ";";
						$dbo->setQuery($q);
						$dbo->execute();
						$row['custmail'] = $customer['email'];
					}
					if (empty($row['phone']) && !empty($customer['phone'])) {
						$q = "UPDATE `#__vikbooking_orders` SET `phone`=" . $dbo->quote($customer['phone']) . " WHERE `id`=" . $row['id'] . ";";
						$dbo->setQuery($q);
						$dbo->execute();
						$row['phone'] = $customer['phone'];
					}
				}
			}
		}

		$customer = $cpin->getCustomerFromBooking($row['id']);
		if ($customer && !empty($customer['country'])) {
			if (file_exists(VBO_ADMIN_PATH.DIRECTORY_SEPARATOR.'resources'.DIRECTORY_SEPARATOR.'countries'.DIRECTORY_SEPARATOR.$customer['country'].'.png')) {
				$customer['country_img'] = '<img src="'.VBO_ADMIN_URI.'resources/countries/'.$customer['country'].'.png'.'" title="'.$customer['country'].'" class="vbo-country-flag vbo-country-flag-left"/>';
			}
		}

		$padminnotes = VikRequest::getString('adminnotes', '', 'request');
		$pupdadmnotes = VikRequest::getString('updadmnotes', '', 'request');
		$pinvnotes = VikRequest::getString('invnotes', '', 'request', VIKREQUEST_ALLOWHTML);
		$pupdinvnotes = VikRequest::getString('updinvnotes', '', 'request');
		$pnewpayment = VikRequest::getString('newpayment', '', 'request');
		$pnewlang = VikRequest::getString('newlang', '', 'request');
		$padmindisc = VikRequest::getString('admindisc', '', 'request');
		$ptot_taxes = VikRequest::getString('tot_taxes', '', 'request');
		$ptot_city_taxes = VikRequest::getString('tot_city_taxes', '', 'request');
		$ptot_fees = VikRequest::getString('tot_fees', '', 'request');
		$pcmms = VikRequest::getString('cmms', '', 'request');
		$pcustmail = VikRequest::getString('custmail', '', 'request');
		$pcustphone = VikRequest::getString('custphone', '', 'request');
		$pmakepay = VikRequest::getInt('makepay', 0, 'request');
		$pnewamountpaid = VikRequest::getFloat('newamountpaid', -1, 'request');
		$pnewamountpaymeth = VikRequest::getString('newamountpaymeth', '', 'request');
		// we update the total paid also if the input is 0.0 (float) as the default value is -1 (int)
		if (($pnewamountpaid > 0 || ($pnewamountpaid == 0 && is_float($pnewamountpaid))) && (float)$row['totpaid'] != $pnewamountpaid) {
			$prevpaid = (float)$row['totpaid'];
			$q = "UPDATE `#__vikbooking_orders` SET `totpaid`=".$dbo->quote($pnewamountpaid)." WHERE `id`=".$row['id'].";";
			$dbo->setQuery($q);
			$dbo->execute();
			$row['totpaid'] = $pnewamountpaid;
			// Booking History log for new amount paid (payment update)
			$extra_data = null;
			$newpaympaytype = '';
			if ($pnewamountpaid > $prevpaid) {
				$extra_data = new stdClass;
				$extra_data->amount_paid = ($pnewamountpaid - $prevpaid);
				if (!empty($pnewamountpaymeth)) {
					$payparts = explode('_', $pnewamountpaymeth);
					$newpaympaytype = count($payparts) > 1 ? $payparts[1] : $pnewamountpaymeth;
					$extra_data->payment_method = $newpaympaytype;
				}
			}
			VikBooking::getBookingHistoryInstance()->setBid($row['id'])->setExtraData($extra_data)->store('PU', JText::sprintf('VBOPREVAMOUNTPAID', VikBooking::numberFormat($prevpaid) . (!empty($newpaympaytype) ? ' ('.$newpaympaytype.')' : '')));
			//
		}
		$pnewamountrefunded = VikRequest::getFloat('newamountrefunded', -1, 'request');
		// we update the total refunded also if the input is 0.0 (float) as the default value is -1 (int)
		if (($pnewamountrefunded > 0 || ($pnewamountrefunded == 0 && is_float($pnewamountrefunded))) && (float)$row['refund'] != $pnewamountrefunded) {
			$prevrefunded = $row['refund'];
			$refund_diff = $pnewamountrefunded - $row['refund'];
			if ($refund_diff > 0) {
				// refund amount increased, lower the booking total
				$newtotal = $row['total'] - $refund_diff;
				// lower also the amount paid
				$newtotpaid = $row['totpaid'] > 0 ? ($row['totpaid'] - $refund_diff) : $row['totpaid'];
			} else {
				// refund amount decreased, increase the booking total
				$newtotal = $row['total'] + abs($refund_diff);
				// leave the totpaid unchanged in this case
				$newtotpaid = $row['totpaid'];
			}
			$q = "UPDATE `#__vikbooking_orders` SET `total`=" . $dbo->quote($newtotal) . ", `totpaid`=" . ($newtotpaid == null ? 'NULL' : $dbo->quote($newtotpaid)) . ", `refund`=" . $dbo->quote($pnewamountrefunded) . " WHERE `id`=" . $row['id'] . ";";
			$dbo->setQuery($q);
			$dbo->execute();
			$row['total'] = $newtotal;
			$row['refund'] = $pnewamountrefunded;
			// booking history log for new amount refunded
			VikBooking::getBookingHistoryInstance($row['id'])
				->setExtraData([
					'amount_paid' => ($pnewamountrefunded - $prevrefunded),
				])
				->store('RU', JText::sprintf('VBO_NEWREFUND_AMOUNT', VikBooking::numberFormat($prevrefunded), VikBooking::numberFormat($pnewamountrefunded)));
		}
		$pnewamountpayable = VikRequest::getFloat('newamountpayable', -1, 'request');
		// we update the amount payable also if the input is 0.0 (float) as the default value is -1 (int)
		if (($pnewamountpayable > 0 || ($pnewamountpayable == 0 && is_float($pnewamountpayable))) && (float)$row['payable'] != $pnewamountpayable) {
			$q = "UPDATE `#__vikbooking_orders` SET `payable`=" . $dbo->quote($pnewamountpayable) . " WHERE `id`=" . $row['id'] . ";";
			$dbo->setQuery($q);
			$dbo->execute();
			$row['payable'] = $pnewamountpayable;
			// booking history log for new amount payable
			VikBooking::getBookingHistoryInstance()->setBid($row['id'])->store('PB', JText::sprintf('VBO_NEWPAYABLE_AMOUNT', VikBooking::numberFormat($pnewamountpayable)));
			// make sure to update the payment counter, or if no payments ever made, the system won't accept a transaction
			if (!$row['paymcount']) {
				// turn flag on
				$pmakepay = 1;
			}
		}
		if ($pmakepay > 0) {
			// check if the payment counter should be updated
			$q = "UPDATE `#__vikbooking_orders` SET `paymcount`=" . $pmakepay . " WHERE `id`=" . $row['id'] . ";";
			$dbo->setQuery($q);
			$dbo->execute();
			$row['paymcount'] = 1;
		}
		$pnewcancfee = VikRequest::getFloat('newcancfee', -1, 'request');
		// we update the cancellation fee amount also if the input is 0.0 (float) as the default value is -1 (int)
		if (($pnewcancfee > 0 || ($pnewcancfee == 0 && is_float($pnewcancfee))) && (float)$row['canc_fee'] != $pnewcancfee) {
			$q = "UPDATE `#__vikbooking_orders` SET `canc_fee`=" . $dbo->quote($pnewcancfee) . " WHERE `id`=" . $row['id'] . ";";
			$dbo->setQuery($q);
			$dbo->execute();
			$row['canc_fee'] = $pnewcancfee;
			// booking history log for new cancellation fee amount
			VikBooking::getBookingHistoryInstance()->setBid($row['id'])->store('MB', sprintf(JText::translate('VBO_CANC_FEE') . ': %s', VikBooking::numberFormat($pnewcancfee)));
		}
		if (!empty($padminnotes) || !empty($pupdadmnotes)) {
			$q = "UPDATE `#__vikbooking_orders` SET `adminnotes`=".$dbo->quote($padminnotes)." WHERE `id`=".$row['id'].";";
			$dbo->setQuery($q);
			$dbo->execute();
			$row['adminnotes'] = $padminnotes;
		}
		if (!empty($pinvnotes) || !empty($pupdinvnotes)) {
			$pinvnotes = strpos($pinvnotes, '<br') !== false ? $pinvnotes : nl2br($pinvnotes);
			$q = "UPDATE `#__vikbooking_orders` SET `inv_notes`=".$dbo->quote($pinvnotes)." WHERE `id`=".$row['id'].";";
			$dbo->setQuery($q);
			$dbo->execute();
			$row['inv_notes'] = $pinvnotes;
		}
		if (!empty($pnewpayment) && $payments) {
			foreach ($payments as $npay) {
				if ((int)$npay['id'] == (int)$pnewpayment) {
					$newpayvalid = $npay['id'].'='.$npay['name'];
					$q = "UPDATE `#__vikbooking_orders` SET `idpayment`=".$dbo->quote($newpayvalid)." WHERE `id`=".$row['id'].";";
					$dbo->setQuery($q);
					$dbo->execute();
					$row['idpayment'] = $newpayvalid;
					break;
				}
			}
		}
		if (!empty($pnewlang)) {
			$q = "UPDATE `#__vikbooking_orders` SET `lang`=".$dbo->quote($pnewlang)." WHERE `id`=".$row['id'].";";
			$dbo->setQuery($q);
			$dbo->execute();
			$row['lang'] = $pnewlang;
		}
		if (strlen($padmindisc) > 0) {
			if (floatval($padmindisc) > 0.00) {
				$admincoupon = '-1;'.floatval($padmindisc).';'.JText::translate('VBADMINDISCOUNT');
			} else {
				$admincoupon = '';
			}
			$expcoupon = explode(";", $row['coupon']);
			// make sure the new discount is different than the previous one, and that the total amount is greater than zero
			if ($row['total'] > 0 && (empty($row['coupon']) || (float)$expcoupon[1] != (float)$padmindisc)) {
				// re-calculate new total by adding the previous discount and by subtracting the new one
				$newtotal = $row['total'] + (!empty($row['coupon']) ? (float)$expcoupon[1] : 0);
				$newtotal -= (float)$padmindisc;
				//
				$q = "UPDATE `#__vikbooking_orders` SET `coupon`=" . $dbo->quote($admincoupon) . ", `total`=" . $dbo->quote($newtotal) . " WHERE `id`={$row['id']};";
				$dbo->setQuery($q);
				$dbo->execute();
				$row['coupon'] = $admincoupon;
				$row['total'] = $newtotal;
			}
		}
		if (strlen($ptot_taxes) > 0) {
			$q = "UPDATE `#__vikbooking_orders` SET `tot_taxes`='".floatval($ptot_taxes)."' WHERE `id`=".$row['id'].";";
			$dbo->setQuery($q);
			$dbo->execute();
			$row['tot_taxes'] = $ptot_taxes;
		}
		if (strlen($ptot_city_taxes) > 0) {
			$q = "UPDATE `#__vikbooking_orders` SET `tot_city_taxes`='".floatval($ptot_city_taxes)."' WHERE `id`=".$row['id'].";";
			$dbo->setQuery($q);
			$dbo->execute();
			$row['tot_city_taxes'] = $ptot_city_taxes;
		}
		if (strlen($ptot_fees) > 0) {
			$q = "UPDATE `#__vikbooking_orders` SET `tot_fees`='".floatval($ptot_fees)."' WHERE `id`=".$row['id'].";";
			$dbo->setQuery($q);
			$dbo->execute();
			$row['tot_fees'] = $ptot_fees;
		}
		if (strlen($pcmms) > 0) {
			$q = "UPDATE `#__vikbooking_orders` SET `cmms`='".floatval($pcmms)."' WHERE `id`=".$row['id'].";";
			$dbo->setQuery($q);
			$dbo->execute();
			$row['cmms'] = $pcmms;
		}
		if (strlen($pcustmail) > 0) {
			$q = "UPDATE `#__vikbooking_orders` SET `custmail`=".$dbo->quote($pcustmail)." WHERE `id`=".$row['id'].";";
			$dbo->setQuery($q);
			$dbo->execute();
			$row['custmail'] = $pcustmail;
		}
		if (strlen($pcustphone) > 0) {
			$q = "UPDATE `#__vikbooking_orders` SET `phone`=".$dbo->quote($pcustphone)." WHERE `id`=".$row['id'].";";
			$dbo->setQuery($q);
			$dbo->execute();
			$row['phone'] = $pcustphone;
		}

		/**
		 * Vik Channel Manager booking assignable channels.
		 * 
		 * @since 	1.16.3 (J) - 1.6.3 (WP)
		 */
		$vcm_assign_channel = VikRequest::getString('vcm_assign_channel', '', 'request');
		if (!empty($vcm_assign_channel) && class_exists('VCMOtaBooking')) {
			// current unix timestamp will be used in place of OTA Reservation ID
			$set_ota_bid = time();

			// update fields on db
			$q = "UPDATE `#__vikbooking_orders` SET `idorderota`=" . $dbo->quote($set_ota_bid) . ", `channel`=" . $dbo->quote($vcm_assign_channel) . " WHERE `id`=" . $row['id'] . ";";
			$dbo->setQuery($q);
			$dbo->execute();

			// overwrite booking properties
			$row['idorderota'] = $set_ota_bid;
			$row['channel']    = $vcm_assign_channel;

			// update booking history
			$user = JFactory::getUser();
			VikBooking::getBookingHistoryInstance()->setBid($row['id'])->store('CM', "({$user->name}) " . JText::sprintf('VBO_BOOKING_OTA_ASSIGNED', $vcm_assign_channel));
		}

		// load data
		$q = "SELECT `or`.*,`r`.`name`,`r`.`fromadult`,`r`.`toadult`,`r`.`params` FROM `#__vikbooking_ordersrooms` AS `or`,`#__vikbooking_rooms` AS `r` WHERE `or`.`idorder`=".(int)$row['id']." AND `or`.`idroom`=`r`.`id` ORDER BY `or`.`id` ASC;";
		$dbo->setQuery($q);
		$rooms = $dbo->loadAssocList();
		$q = "SELECT * FROM `#__vikbooking_ordersbusy` WHERE `idorder`=".(int)$row['id'].";";
		$dbo->setQuery($q);
		$busy = $dbo->loadAssocList();

		// Rooms Specific Unit
		$proomindex = VikRequest::getVar('roomindex', array());
		if (!empty($proomindex) && is_array($proomindex) && count($proomindex)) {
			$orig_rooms = $rooms;
			foreach ($proomindex as $or_id => $rind) {
				if (empty($or_id)) {
					continue;
				}
				$q = "UPDATE `#__vikbooking_ordersrooms` SET `roomindex`=".(!empty($rind) ? (int)$rind : "NULL")." WHERE `id`=".(int)$or_id." AND `idorder`=".(int)$row['id'].";";
				$dbo->setQuery($q);
				$dbo->execute();
				// update global array reference
				$sub_unit_changed = false;
				foreach ($rooms as $korr => $orr) {
					if ((int)$or_id == (int)$orr['id']) {
						$sub_unit_changed = ((string)$orr['roomindex'] != (string)$rind);
						$rooms[$korr]['roomindex'] = $rind;
						break;
					}
				}
				if (!$sub_unit_changed) {
					// no need to store a booking history record
					continue;
				}
				// try to store a booking history record when switching room sub-unit index
				if (!empty($rind)) {
					foreach ($orig_rooms as $korr => $orr) {
						if ((int)$or_id != (int)$orr['id']) {
							continue;
						}
						if (empty($orr['params'])) {
							continue;
						}
						$room_params = json_decode($orr['params'], true);
						if (is_array($room_params) && !empty($room_params['features'])) {
							$prev_subindex = empty($orr['roomindex']) ? '---' : $orr['roomindex'];
							$new_subindex  = $rind;
							foreach ($room_params['features'] as $origrind => $rfeatures) {
								if ($orr['roomindex'] == $origrind) {
									foreach ($rfeatures as $fname => $fval) {
										if (strlen($fval)) {
											$prev_subindex = '#' . $origrind . ' - ' . JText::translate($fname) . ': ' . $fval;
											break;
										}
									}
								}
								if ($rind == $origrind) {
									foreach ($rfeatures as $fname => $fval) {
										if (strlen($fval)) {
											$new_subindex = '#' . $origrind . ' - ' . JText::translate($fname) . ': ' . $fval;
											break;
										}
									}
								}
							}
							//Booking History
							VikBooking::getBookingHistoryInstance()->setBid($row['id'])->store('MB', "({$user->name}) " . JText::sprintf('VBOROOMSUBUNITCHANGEFT', $orr['name'], $prev_subindex, $new_subindex));
							//
						}
					}
				}
			}
		}

		// PCI DSS Checking
		if (!empty($row['idorderota']) && !empty($row['channel']) && !empty($row['paymentlog'])) {
			if (stripos($row['paymentlog'], 'card number') !== false && strpos($row['paymentlog'], '*') !== false) {
				$checkout_info = getdate($row['checkout']);
				/**
				 * Limit for accessing the credit card details has been changed to check-out
				 * day at 23:59:59 + 10 extra days. It used to be at 23:59:59 on check-out day.
				 * 
				 * @since 	1.13
				 */
				$cardlimit = mktime(23, 59, 59, $checkout_info['mon'], ($checkout_info['mday'] + 10), $checkout_info['year']);
				if (time() > $cardlimit) {
					$newlogstr = JText::translate('VBOCCLOGDATAREMOVEDPCIDSS');
					$q = "UPDATE `#__vikbooking_orders` SET `paymentlog`=".$dbo->quote($newlogstr)." WHERE `id`=".$row['id'].";";
					$dbo->setQuery($q);
					$dbo->execute();
					$row['paymentlog'] = $newlogstr;
				}
			}
		}

		// unset credit card details, if needed, maybe to re-collect them
		$unset_cc = VikRequest::getInt('unset_cc', 0, 'request');
		if ($unset_cc && empty($row['idorderota']) && !empty($row['paymentlog'])) {
			$q = "UPDATE `#__vikbooking_orders` SET `paymentlog` = NULL WHERE `id` = " . $row['id'] . ";";
			$dbo->setQuery($q);
			$dbo->execute();
			$row['paymentlog'] = null;
		}

		// detect if VCM exists
		$vcm_exists = is_file(VCM_SITE_PATH . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'lib.vikchannelmanager.php');

		// attempt to get the VCM review ID with a try-catch to avoid SQL errors
		$vcm_review = array();
		if ($vcm_exists) {
			try {
				$q = "SELECT * FROM `#__vikchannelmanager_otareviews` WHERE `idorder`=" . (int)$row['id'] . ";";
				$dbo->setQuery($q);
				$dbo->execute();
				if ($dbo->getNumRows()) {
					$vcm_review = $dbo->loadAssoc();
				}
			} catch (Exception $e) {
				// do nothing as VCM is probably not available/enabled/updated
			}
		}

		/**
		 * Check if the reservation requires/supports additional actions.
		 * 
		 * @since 	1.14 (J) - 1.4.0 (WP)
		 */
		$vcm_decline_actions = 0;
		$vcm_pre_approval = 0;
		$vcm_special_offer = 0;
		$vcm_host_to_guest_review = 0;
		$vcm_cancel_active_res = 0;
		if ($vcm_exists) {
			try {
				require_once VCM_SITE_PATH . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'lib.vikchannelmanager.php';
				if (method_exists('VikChannelManager', 'reservationNeedsDeclineReasons')) {
					$vcm_decline_actions = (int)VikChannelManager::reservationNeedsDeclineReasons($row);
				}
				if (method_exists('VikChannelManager', 'reservationSupportsPreApproval')) {
					$vcm_pre_approval = VikChannelManager::reservationSupportsPreApproval($row);
					$vcm_pre_approval = $vcm_pre_approval === false ? 0 : $vcm_pre_approval;
				}
				if (method_exists('VikChannelManager', 'reservationSupportsSpecialOffer')) {
					$vcm_special_offer = VikChannelManager::reservationSupportsSpecialOffer($row);
					$vcm_special_offer = $vcm_special_offer === false ? 0 : $vcm_special_offer;
				}
				if (method_exists('VikChannelManager', 'hostToGuestReviewSupported')) {
					$vcm_host_to_guest_review = VikChannelManager::hostToGuestReviewSupported($row);
					$vcm_host_to_guest_review = $vcm_host_to_guest_review === false ? 0 : $vcm_host_to_guest_review;
				}
				if (method_exists('VikChannelManager', 'cancelActiveOtaReservation')) {
					$vcm_cancel_active_res = (int)VikChannelManager::cancelActiveOtaReservation($row);
				}
			} catch (Exception $e) {
				// do nothing as VCM is probably not available/enabled/updated
			}
		}

		/**
		 * Build currency conversion details, if needed and supported.
		 * 
		 * @since 	1.15.0 (J) - 1.5.0 (WP)
		 */
		$ota_helper = new VBOCurrencyOta($row);
		$allows_conversion  = $ota_helper->supportsConversion($row['total']);
		$conv_from_currency = $allows_conversion !== false ? $ota_helper->getOTACurrency() : null;
		$conv_to_currency 	= $allows_conversion !== false ? $ota_helper->getCurrencyName() : null;
				
		$this->row = $row;
		$this->rooms = $rooms;
		$this->busy = $busy;
		$this->customer = $customer;
		$this->payments = $payments;
		$this->vcm_review = $vcm_review;
		$this->vcm_decline_actions = $vcm_decline_actions;
		$this->vcm_pre_approval = $vcm_pre_approval;
		$this->vcm_special_offer = $vcm_special_offer;
		$this->vcm_host_to_guest_review = $vcm_host_to_guest_review;
		$this->vcm_cancel_active_res = $vcm_cancel_active_res;
		$this->allows_conversion = $allows_conversion;
		$this->conv_from_currency = $conv_from_currency;
		$this->conv_to_currency = $conv_to_currency;
		
		// Display the template
		parent::display($tpl);
	}

	/**
	 * Sets the toolbar
	 */
	protected function addToolBar() {
		$printreceipt = VikRequest::getInt('print', 0, 'request');
		if ($printreceipt) {
			// do not print any header or buttons if we are printing the receipt
			return;
		}

		JToolBarHelper::title(JText::translate('VBMAINORDERTITLEEDIT'), 'vikbooking');
		JToolBarHelper::cancel( 'canceledorder', JText::translate('VBBACK'));
		JToolBarHelper::spacer();
		JToolBarHelper::custom( 'prev_booking', 'backward', 'backward', JText::translate('VBJQCALPREV'), false);
		JToolBarHelper::spacer();
		JToolBarHelper::custom( 'next_booking', 'forward', 'forward', JText::translate('VBJQCALNEXT'), false);
		JToolBarHelper::spacer();
	}
}
