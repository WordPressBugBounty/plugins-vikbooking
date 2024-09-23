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

class VikBookingViewImportbftpp extends JViewVikBooking
{	
	function display($tpl = null)
	{
		// Set the toolbar
		$this->addToolBar();

		$dbo = JFactory::getDbo();

		// make sure there is at least one room-type in order to allow the bookings import
		$setup_completed = 0;
		$vbo_rooms = array();
		$q = "SELECT `id`,`name` FROM `#__vikbooking_rooms`;";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows()) {
			$records = $dbo->loadAssocList();
			foreach ($records as $record) {
				$vbo_rooms[$record['id']] = $record['name'];
			}
			// change flag for setup completed
			$setup_completed = 1;
		}
		
		// process actions
		$plugins 	= VikBooking::canImportBookingsFromThirdPartyPlugins();
		$tpbookings = array();
		$tprooms 	= array();
		$tpp 		= VikRequest::getString('tpp', '', 'request');
		$bimported 	= array();
		if (!empty($tpp) && $setup_completed && is_array($plugins) && isset($plugins[$tpp])) {
			// grab the import information for this third party plugin
			$bimported = get_option("vikbooking_importbftpp_{$tpp}", null);
			$bimported = !empty($bimported) ? json_decode($bimported, true) : array();
			$bimported = !is_array($bimported) ? array() : $bimported;
			
			// load bookings from this third party plugin
			switch ($tpp) {
				case 'mphb':
					// read bookings data
					$q = "SELECT `p`.`ID`,`p`.`post_date`,`p`.`post_status`,`pm`.`meta_key`,`pm`.`meta_value` FROM `#__posts` AS `p` LEFT JOIN `#__postmeta` `pm` ON `p`.`ID`=`pm`.`post_id` WHERE `p`.`post_type`=" . $dbo->quote('mphb_booking') . " ORDER BY `p`.`post_date` DESC;";
					$dbo->setQuery($q);
					$dbo->execute();
					if (!$dbo->getNumRows()) {
						// no bookings found
						break;
					}
					
					// build records
					$records = $dbo->loadAssocList();
					foreach ($records as $record) {
						if (empty($record['ID'])) {
							continue;
						}

						// raw booking structure
						if (!isset($tpbookings[$record['ID']])) {
							$tpbookings[$record['ID']] = array(
								'id' => $record['ID'],
								'dt' => $record['post_date'],
								'last_import' => null,
								'status' => $record['post_status'],
								'metas' => array(),
								'rooms_metas' => array(),
								'room_ids' => array(),
								'room_parties' => array(),
								'infos' => array(),
							);
						}

						// push meta assoc
						$tpbookings[$record['ID']]['metas'][$record['meta_key']] = $record['meta_value'];

						// gather booking information
						if (stripos($record['meta_key'], 'first_name') !== false) {
							$tpbookings[$record['ID']]['infos']['first_name'] = $record['meta_value'];
						}
						if (stripos($record['meta_key'], 'last_name') !== false) {
							$tpbookings[$record['ID']]['infos']['last_name'] = $record['meta_value'];
						}
						if (stripos($record['meta_key'], 'email') !== false) {
							$tpbookings[$record['ID']]['infos']['email'] = $record['meta_value'];
						}
						if (stripos($record['meta_key'], 'check_in') !== false) {
							$tpbookings[$record['ID']]['infos']['checkin'] = $record['meta_value'];
						}
						if (stripos($record['meta_key'], 'check_out') !== false) {
							$tpbookings[$record['ID']]['infos']['checkout'] = $record['meta_value'];
						}
						if (stripos($record['meta_key'], 'phone') !== false && !empty($record['meta_value'])) {
							$tpbookings[$record['ID']]['infos']['phone'] = $record['meta_value'];
						}
						if (stripos($record['meta_key'], 'city') !== false && !empty($record['meta_value'])) {
							$tpbookings[$record['ID']]['infos']['city'] = $record['meta_value'];
						}
						if (stripos($record['meta_key'], 'address') !== false && !empty($record['meta_value'])) {
							$tpbookings[$record['ID']]['infos']['address'] = $record['meta_value'];
						}
						if (stripos($record['meta_key'], 'zip') !== false && !empty($record['meta_value'])) {
							$tpbookings[$record['ID']]['infos']['zip'] = $record['meta_value'];
						}
						if (stripos($record['meta_key'], 'country') !== false && !empty($record['meta_value'])) {
							$tpbookings[$record['ID']]['infos']['country'] = $record['meta_value'];
						}
						if (stripos($record['meta_key'], 'total_price') !== false && !empty($record['meta_value'])) {
							$tpbookings[$record['ID']]['infos']['total'] = $record['meta_value'];
						}
						if (stripos($record['meta_key'], 'language') !== false && !empty($record['meta_value'])) {
							$tpbookings[$record['ID']]['infos']['language'] = $record['meta_value'];
						}

						// check whether this booking was already imported
						if (count($bimported)) {
							foreach ($bimported as $old_import) {
								if (!isset($old_import['bids']) || !isset($old_import['dt']) || !is_array($old_import['bids'])) {
									continue;
								}
								if (in_array($record['ID'], $old_import['bids'])) {
									// last import date found
									$tpbookings[$record['ID']]['last_import'] = $old_import['dt'];
									break;
								}
							}
						}
					}

					// grab room info details
					$q = "SELECT `p`.`ID`,`p`.`post_parent`,`pm`.`meta_key`,`pm`.`meta_value` FROM `#__posts` AS `p` LEFT JOIN `#__postmeta` `pm` ON `p`.`ID`=`pm`.`post_id` WHERE `p`.`post_parent` IN (" . implode(', ', array_keys($tpbookings)) . ") AND `p`.`post_type`=" . $dbo->quote('mphb_reserved_room') . " ORDER BY `p`.`post_parent` ASC;";
					$dbo->setQuery($q);
					$dbo->execute();
					if ($dbo->getNumRows()) {
						$rooms_data = $dbo->loadAssocList();
						$rooms_meta_assoc = array();
						foreach ($rooms_data as $room_data) {
							if (!isset($tpbookings[$room_data['post_parent']])) {
								continue;
							}
							// push rooms meta assoc
							$tpbookings[$room_data['post_parent']]['rooms_metas'][$room_data['meta_key']] = $room_data['meta_value'];
							if (stripos($room_data['meta_key'], 'mphb_room_id') !== false) {
								// push room identifier
								array_push($tpbookings[$room_data['post_parent']]['room_ids'], $room_data['meta_value']);
								// save post ID for this room booked
								$rooms_meta_assoc[$room_data['ID']] = $room_data['meta_value'];
							}
						}
						foreach ($rooms_meta_assoc as $rmetapid => $rmetarid) {
							foreach ($rooms_data as $room_data) {
								if (!isset($tpbookings[$room_data['post_parent']])) {
									continue;
								}
								if ($rmetapid == $room_data['ID'] && stripos($room_data['meta_key'], 'adults') !== false) {
									if (!isset($tpbookings[$room_data['post_parent']]['room_parties'][$rmetarid])) {
										$tpbookings[$room_data['post_parent']]['room_parties'][$rmetarid] = array(
											'adults' => 1,
											'children' => 0,
										);
									}
									// update room party adults
									$tpbookings[$room_data['post_parent']]['room_parties'][$rmetarid]['adults'] = (int)$room_data['meta_value'];
								}
								if ($rmetapid == $room_data['ID'] && stripos($room_data['meta_key'], 'children') !== false) {
									if (!isset($tpbookings[$room_data['post_parent']]['room_parties'][$rmetarid])) {
										$tpbookings[$room_data['post_parent']]['room_parties'][$rmetarid] = array(
											'adults' => 1,
											'children' => 0,
										);
									}
									// update room party children
									$tpbookings[$room_data['post_parent']]['room_parties'][$rmetarid]['children'] = (int)$room_data['meta_value'];
								}
							}
						}
					}

					// build the third party rooms relations
					$q = "SELECT `ID`,`post_title` FROM `#__posts` WHERE `post_type`=" . $dbo->quote('mphb_room') . ";";
					$dbo->setQuery($q);
					$dbo->execute();
					if ($dbo->getNumRows()) {
						$records = $dbo->loadAssocList();
						foreach ($records as $record) {
							$tprooms[$record['ID']] = $record['post_title'];
						}
					}

					// unset all bookings with no rooms found, with non existing rooms or without required information
					foreach ($tpbookings as $bid => $booking) {
						if (!count($booking['room_ids'])) {
							unset($tpbookings[$bid]);
							continue;
						}
						foreach ($booking['room_ids'] as $tprid) {
							if (!isset($tprooms[$tprid])) {
								unset($tpbookings[$bid]);
								break 2;
							}
						}
						// make sure the check-in and check-out dates are defined
						if (empty($booking['infos']['checkin']) || empty($booking['infos']['checkout'])) {
							unset($tpbookings[$bid]);
							continue;
						}
					}
					break;
				default:
					// plugin unsupported
					break;
			}

			// check whether some bookings have been requested for import
			$do_import 		= VikRequest::getInt('do_import', 0, 'request');
			$tpp_rooms_map 	= VikRequest::getVar('tpp_rooms', array(), 'request', 'array');
			$vbo_rooms_map 	= VikRequest::getVar('vbo_rooms', array(), 'request', 'array');
			$tpp_bids 		= VikRequest::getVar('tpp_bids', array(), 'request', 'array');
			if ($do_import && is_array($tpp_rooms_map) && is_array($vbo_rooms_map) && is_array($tpp_bids) && count($vbo_rooms_map) && count($tpp_bids)) {
				// let this method proceed with the import
				$this->importThirdPartyReservations($tpp, $tpbookings, $tpp_rooms_map, $vbo_rooms_map, $tpp_bids);
			}
		}
		
		$this->setup_completed = $setup_completed;
		$this->vbo_rooms = $vbo_rooms;
		$this->plugins = $plugins;
		$this->tpbookings = $tpbookings;
		$this->tprooms = $tprooms;
		$this->tpp = $tpp;
		
		// Display the template
		parent::display($tpl);
	}

	/**
	 * Do the import of the requested bookings like if this was a task of a controller.
	 * 
	 * @param 	string 	$tpp 				the name of the third party plugin.
	 * @param 	array 	$tpbookings 		all third party bookings read.
	 * @param 	array 	$tpp_rooms_map 		third party room IDs involved.
	 * @param 	array 	$vbo_rooms_map 		corresponding VBO room IDs selected.
	 * @param 	array 	$tpp_bids 			selected reservation IDs (as keys).
	 * 
	 * @return 	mixed 	void on success by redirecting the page, false on failure by setting a system error message.
	 * 
	 * @since 	1.3.5
	 */
	private function importThirdPartyReservations($tpp, $tpbookings, $tpp_rooms_map, $vbo_rooms_map, $tpp_bids)
	{
		// set the execution time to 5 minutes
		@ini_set('max_execution_time', 300);


		$dbo = JFactory::getDbo();
		$app = JFactory::getApplication();

		// date format
		$vbo_df = VikBooking::getDateFormat(true);
		if ($vbo_df == "%d/%m/%Y") {
			$vbo_df = 'd/m/Y';
		} elseif ($vbo_df == "%m/%d/%Y") {
			$vbo_df = 'm/d/Y';
		} else {
			$vbo_df = 'Y/m/d';
		}

		// check-in/check-out times
		$pcheckinh = 0;
		$pcheckinm = 0;
		$pcheckouth = 0;
		$pcheckoutm = 0;
		$timeopst = VikBooking::getTimeOpenStore();
		if (is_array($timeopst)) {
			$opent = VikBooking::getHoursMinutes($timeopst[0]);
			$closet = VikBooking::getHoursMinutes($timeopst[1]);
			$pcheckinh = $opent[0];
			$pcheckinm = $opent[1];
			$pcheckouth = $closet[0];
			$pcheckoutm = $closet[1];
		}

		// Assign room specific unit
		$set_room_indexes = VikBooking::autoRoomUnit();

		// vars validation
		if (empty($tpp)) {
			VikError::raiseWarning('', 'Missing third party plugin name');
			return false;
		}
		if (!is_array($tpbookings) || !count($tpbookings)) {
			VikError::raiseWarning('', 'No reservations found from third party plugin');
			return false;
		}

		// build the rooms mapping data
		$rooms_mapping = array();
		foreach ($tpp_rooms_map as $k => $v) {
			if (empty($vbo_rooms_map[$k])) {
				continue;
			}
			$rooms_mapping[$v] = $vbo_rooms_map[$k];
		}
		if (!count($rooms_mapping)) {
			VikError::raiseWarning('', 'No corresponding rooms of Vik Booking selected');
			return false;
		}

		// loop over the requested bookings for import
		$tot_bookings_imported = 0;
		$bookings_imported_ids = array();
		foreach ($tpp_bids as $tpbid => $val) {
			if (!isset($tpbookings[$tpbid])) {
				// booking not read
				continue;
			}
			
			// build customer raw data
			$custdata = '';
			$first_name = null;
			$last_name = null;
			$email = null;
			$phone = null;
			$country = null;
			$customer_extrainfo = array();
			if (!empty($tpbookings[$tpbid]['infos']['first_name'])) {
				$custdata .= JText::translate('VBCUSTOMERFIRSTNAME') . ': ' . $tpbookings[$tpbid]['infos']['first_name'] . "\n";
				$first_name = $tpbookings[$tpbid]['infos']['first_name'];
			}
			if (!empty($tpbookings[$tpbid]['infos']['last_name'])) {
				$custdata .= JText::translate('VBCUSTOMERLASTNAME') . ': ' . $tpbookings[$tpbid]['infos']['last_name'] . "\n";
				$last_name = $tpbookings[$tpbid]['infos']['last_name'];
			}
			if (!empty($tpbookings[$tpbid]['infos']['email'])) {
				$custdata .= JText::translate('VBCUSTOMEREMAIL') . ': ' . $tpbookings[$tpbid]['infos']['email'] . "\n";
				$email = $tpbookings[$tpbid]['infos']['email'];
			}
			if (!empty($tpbookings[$tpbid]['infos']['phone'])) {
				$custdata .= JText::translate('VBCUSTOMERPHONE') . ': ' . $tpbookings[$tpbid]['infos']['phone'] . "\n";
				$phone = $tpbookings[$tpbid]['infos']['phone'];
			}
			if (!empty($tpbookings[$tpbid]['infos']['address'])) {
				$custdata .= JText::translate('ORDER_ADDRESS') . ': ' . $tpbookings[$tpbid]['infos']['address'] . "\n";
				$customer_extrainfo['address'] = $tpbookings[$tpbid]['infos']['address'];
			}
			if (!empty($tpbookings[$tpbid]['infos']['zip'])) {
				$custdata .= JText::translate('ORDER_ZIP') . ': ' . $tpbookings[$tpbid]['infos']['zip'] . "\n";
				$customer_extrainfo['zip'] = $tpbookings[$tpbid]['infos']['zip'];
			}
			if (!empty($tpbookings[$tpbid]['infos']['city'])) {
				$custdata .= JText::translate('ORDER_CITY') . ': ' . $tpbookings[$tpbid]['infos']['city'] . "\n";
				$customer_extrainfo['city'] = $tpbookings[$tpbid]['infos']['city'];
			}
			if (!empty($tpbookings[$tpbid]['infos']['country'])) {
				$custdata .= JText::translate('VBCUSTOMERCOUNTRY') . ': ' . $tpbookings[$tpbid]['infos']['country'] . "\n";
				$country = $tpbookings[$tpbid]['infos']['country'];
			}
			$custdata = rtrim($custdata, "\n");

			// build stay dates and tot nights
			$checkin_ts  = VikBooking::getDateTimestamp(date($vbo_df, strtotime($tpbookings[$tpbid]['infos']['checkin'])), $pcheckinh, $pcheckinm);
			$checkout_ts = VikBooking::getDateTimestamp(date($vbo_df, strtotime($tpbookings[$tpbid]['infos']['checkout'])), $pcheckouth, $pcheckoutm);
			if (!$checkin_ts || !$checkout_ts) {
				VikError::raiseWarning('', 'Invalid dates for third party reservation #' . $tpbid);
				continue;
			}
			$tot_nights  = $this->countReservationNights($tpbookings[$tpbid]['infos']['checkin'], $tpbookings[$tpbid]['infos']['checkout']);

			// prepare customer record
			$cpin = VikBooking::getCPinIstance();
			$cpin->is_admin = true;
			if (count($customer_extrainfo)) {
				$cpin->setCustomerExtraInfo($customer_extrainfo);
			}
			// adjust the country code
			$country = $cpin->get3CharCountry($country);
			// save customer details
			$cpin->saveCustomerDetails($first_name, $last_name, $email, $phone, $country, array());

			// check booking status
			$new_status = 'confirmed';
			if (stripos($tpbookings[$tpbid]['status'], 'cancel') !== false || stripos($tpbookings[$tpbid]['status'], 'abandon') !== false) {
				$new_status = 'cancelled';
			} elseif (stripos($tpbookings[$tpbid]['status'], 'pending') !== false) {
				$new_status = 'standby';
			}

			// store busy record(s) by making sure we know the VBO room ID
			$booking_busy_ids = array();
			if ($new_status == 'confirmed') {
				foreach ($tpbookings[$tpbid]['room_ids'] as $orid => $tprid) {
					if (!isset($rooms_mapping[$tprid])) {
						// corresponding room ID not found, unset the key
						unset($tpbookings[$tpbid]['room_ids'][$orid]);
						continue;
					}
					$q = "INSERT INTO `#__vikbooking_busy` (`idroom`,`checkin`,`checkout`,`realback`) VALUES(" . (int)$rooms_mapping[$tprid] . ", {$checkin_ts}, {$checkout_ts}, {$checkout_ts});";
					$dbo->setQuery($q);
					$dbo->execute();
					$lid = $dbo->insertid();
					if (!empty($lid)) {
						array_push($booking_busy_ids, $lid);
					} else {
						unset($tpbookings[$tpbid]['room_ids'][$orid]);
					}
				}
			}

			// make sure some rooms were booked
			if ($new_status == 'confirmed' && !count($booking_busy_ids)) {
				VikError::raiseWarning('', 'Could not occupy rooms for third party reservation #' . $tpbid);
				continue;
			}

			// store reservation
			$res_obj = new stdClass;
			$res_obj->custdata = $custdata;
			$res_obj->ts = strtotime($tpbookings[$tpbid]['dt']);
			$res_obj->status = $new_status;
			$res_obj->days = $tot_nights;
			$res_obj->checkin = $checkin_ts;
			$res_obj->checkout = $checkout_ts;
			$res_obj->custmail = $email;
			$res_obj->sid = VikBooking::getSecretLink();
			$res_obj->roomsnum = count($tpbookings[$tpbid]['room_ids']);
			$res_obj->total = !empty($tpbookings[$tpbid]['infos']['total']) ? (float)$tpbookings[$tpbid]['infos']['total'] : 0;
			$res_obj->country = $country;
			$res_obj->phone = $phone;
			$res_obj->closure = 0;
			
			$dbo->insertObject('#__vikbooking_orders', $res_obj, 'id');
			if (!isset($res_obj->id) || empty($res_obj->id)) {
				VikError::raiseWarning('', 'Could not import third party reservation #' . $tpbid);
				continue;
			}
			$newoid = $res_obj->id;

			// check if some of the rooms booked have shared calendars
			if ($new_status == 'confirmed') {
				foreach ($tpbookings[$tpbid]['room_ids'] as $orid => $tprid) {
					// we do this before storing the relation records
					VikBooking::updateSharedCalendars($newoid, array($rooms_mapping[$tprid]), $checkin_ts, $checkout_ts);
				}

				// Confirmation Number
				$confirmnumber = VikBooking::generateConfirmNumber($newoid, true);

				// store relations between busy records and new booking
				foreach ($booking_busy_ids as $lid) {
					$q = "INSERT INTO `#__vikbooking_ordersbusy` (`idorder`, `idbusy`) VALUES(" . (int)$newoid . ", " . (int)$lid . ");";
					$dbo->setQuery($q);
					$dbo->execute();
				}
			}

			// write room records
			foreach ($tpbookings[$tpbid]['room_ids'] as $orid => $tprid) {
				// Assign room specific unit
				$room_indexes_usemap = array();
				$room_indexes = $set_room_indexes === true ? VikBooking::getRoomUnitNumsAvailable(array('id' => $newoid, 'checkin' => $checkin_ts, 'checkout' => $checkout_ts), $rooms_mapping[$tprid]) : array();
				$use_ind_key = 0;
				if (count($room_indexes)) {
					if (!array_key_exists($rooms_mapping[$tprid], $room_indexes_usemap)) {
						$room_indexes_usemap[$rooms_mapping[$tprid]] = $use_ind_key;
					} else {
						$use_ind_key = $room_indexes_usemap[$rooms_mapping[$tprid]];
					}
				}

				// room cost and room party
				$r_cust_cost = !empty($tpbookings[$tpbid]['infos']['total']) ? ($tpbookings[$tpbid]['infos']['total'] / count($tpbookings[$tpbid]['room_ids'])) : 0;
				$room_adults = isset($tpbookings[$tpbid]['room_parties'][$tprid]) && isset($tpbookings[$tpbid]['room_parties'][$tprid]['adults']) ? (int)$tpbookings[$tpbid]['room_parties'][$tprid]['adults'] : 1;
				$room_children = isset($tpbookings[$tpbid]['room_parties'][$tprid]) && isset($tpbookings[$tpbid]['room_parties'][$tprid]['children']) ? (int)$tpbookings[$tpbid]['room_parties'][$tprid]['children'] : 0;

				$order_room_obj = new stdClass;
				$order_room_obj->idorder = $newoid;
				$order_room_obj->idroom = $rooms_mapping[$tprid];
				$order_room_obj->adults = $room_adults;
				$order_room_obj->children = $room_children;
				$order_room_obj->idtar = null;
				$order_room_obj->roomindex = count($room_indexes) ? (int)$room_indexes[$use_ind_key] : null;
				$order_room_obj->cust_cost = $r_cust_cost;

				$dbo->insertObject('#__vikbooking_ordersrooms', $order_room_obj, 'id');

				// Assign room specific unit
				if (count($room_indexes)) {
					$room_indexes_usemap[$rooms_mapping[$tprid]]++;
				}
			}

			// Assign Customer Booking
			$cpin->saveCustomerBooking($newoid);
			
			// Booking History
			VikBooking::getBookingHistoryInstance()->setBid($newoid)->store('NB', JText::translate('VBO_IMPBFTPP_BOOKHIST_DESCR') . " ({$tpbid})");

			// increase bookings counter
			$tot_bookings_imported++;

			// store third party booking ID
			array_push($bookings_imported_ids, $tpbid);
		}

		if (!$tot_bookings_imported) {
			VikError::raiseWarning('', JText::sprintf('VBO_IMPBFTPP_IMPORT_TOTRES', $tot_bookings_imported));
			return false;
		}

		// update option containing the imported bookings for this third party plugin
		$bimported = get_option("vikbooking_importbftpp_{$tpp}", null);
		$bimported = !empty($bimported) ? json_decode($bimported, true) : array();
		$bimported = !is_array($bimported) ? array() : $bimported;
		// push new import data
		array_push($bimported, array(
			'dt' => date('Y-m-d H:i:s'),
			'bids' => $bookings_imported_ids,
		));
		update_option("vikbooking_importbftpp_{$tpp}", json_encode($bimported));

		// redirect to Vik Booking - All Bookings page by setting a success message
		$app->enqueueMessage(JText::sprintf('VBO_IMPBFTPP_IMPORT_TOTRES', $tot_bookings_imported));
		$app->redirect('index.php?option=com_vikbooking&task=orders');

		// do not let the View be rendered
		exit;
	}

	/**
	 * Counts the total number of nights of stay from the given stay dates.
	 * 
	 * @param 	string 	$checkin 	parsable checkin date into a timestamp.
	 * @param 	string 	$checkout 	parsable checkout date into a timestamp.
	 * 
	 * @return 	int 				the total number of nights of stay.
	 */
	private function countReservationNights($checkin, $checkout)
	{
		$nights = 1;
		$in_ts  = strtotime($checkin);
		$out_ts = strtotime($checkout);

		if (!$in_ts || !$out_ts || $in_ts >= $out_ts) {
			return $nights;
		}

		$in_info  = getdate($in_ts);
		$nights   = 0;
		while (date('Y-m-d', $in_info[0]) != date('Y-m-d', $out_ts)) {
			$nights++;
			$in_info = getdate(mktime(0, 0, 0, $in_info['mon'], ($in_info['mday'] + 1), $in_info['year']));
		}

		return $nights < 1 ? 1 : $nights;
	}

	/**
	 * Sets the toolbar
	 */
	protected function addToolBar()
	{
		JToolBarHelper::title(JText::translate('VBOMAINIMPORTBFTPPTITLE'), 'vikbooking');
		JToolBarHelper::cancel( 'canceldash', JText::translate('VBBACK'));
		JToolBarHelper::spacer();
	}

}
