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

$order = $this->row;
$rooms = $this->rooms;
$customer = $this->customer;

$dbo = JFactory::getDbo();

$vbo_app = VikBooking::getVboApplication();

/**
 * This view is usually rendered within a modal window, and with WordPress
 * the modal window is loaded through AJAX by the "editorder" View. For this
 * reason, with WP we should not load the JS assets for the contextual menu,
 * or we would reset the default setup of the library. We load the assets in
 * the parent View "editorder" to avoid issues of any kind.
 * 
 * @since 	1.16.0 (J) - 1.6.0 (WP)
 */
if ((VBOPlatformDetection::isJoomla()) || (VBOPlatformDetection::isWordPress() && !wp_doing_ajax())) {
	$vbo_app->loadContextMenuAssets();
	JText::script('VBRENTALORD');
	JText::script('VBPVIEWORDERSPEOPLE');
}

$currencysymb = VikBooking::getCurrencySymb();
$nowdf = VikBooking::getDateFormat(true);
if ($nowdf == "%d/%m/%Y") {
	$df = 'd/m/Y';
} elseif ($nowdf == "%m/%d/%Y") {
	$df = 'm/d/Y';
} else {
	$df = 'Y/m/d';
}
$datesep = VikBooking::getDateSeparator(true);
$pchanged = VikRequest::getInt('changed', '', 'request');
$tmpl = VikRequest::getVar('tmpl');
$set_parent_status = '';
$now_info = getdate();
$today_midnight = mktime(0, 0, 0, $now_info['mon'], $now_info['mday'], $now_info['year']);
$colortags = VikBooking::loadBookingsColorTags();
$otachannel = '';
$otachannel_name = '';
$otachannel_bid = '';
$otacurrency = '';
if (!empty($order['channel'])) {
	$channelparts = explode('_', $order['channel']);
	$otachannel = array_key_exists(1, $channelparts) && strlen($channelparts[1]) > 0 ? $channelparts[1] : ucwords($channelparts[0]);
	$otachannel_name = $otachannel;
	$otachannel_bid = $order['idorderota'];
	if (strstr($otachannel, '.') !== false) {
		$otaccparts = explode('.', $otachannel);
		$otachannel = $otaccparts[0];
	}
	$otacurrency = strlen($order['chcurrency']) > 0 ? $order['chcurrency'] : '';
}
$sensible_k = array('first_name', 'last_name', 'country', 'gender', 'bdate', 'pbirth');
$missing_customer_det = false;
foreach ($customer as $ck => $cv) {
	if ((!isset($customer[$ck]) || empty($customer[$ck])) && in_array($ck, $sensible_k)) {
		$missing_customer_det = true;
	}
}

/**
 * Back-end guests registration now relies on dynamic drivers within Vik Booking.
 * 
 * @since 	1.15.0 (J) - 1.5.0 (WP)
 */

// get the list of back-end pax fields according to settings
list($pax_fields, $pax_fields_attributes) = VikBooking::getPaxFields();

// grab also the fields for front-end pre check-in
list($pre_pax_fields, $pre_pax_fields_attributes) = VikBooking::getPaxFields(true);

// once the pax fields have been prepared, get the active driver instance
$pax_fields_obj = VBOCheckinPax::getInstance();

// load any previous checkin information from this customer
$previous_checkins = VBOCheckinPax::getCustomerAllPaxData($order['id']);

?>
<div class="vbo-info-overlay-block">
	<a class="vbo-info-overlay-close" href="javascript: void(0);"></a>
	<div class="vbo-info-overlay-content">
		<h3 id="vbo-overlay-title">
			<span class="vbo-info-overlay-title-close">
				<span onclick="vboOpenModal();"><?php VikBookingIcons::e('times-circle'); ?></span>
			</span>
		</h3>
		<div class="vbo-overlay-checkin-body"></div>
	</div>
</div>

<form action="index.php?option=com_vikbooking" method="post" name="adminForm" id="adminForm">
	<div class="vbo-bookdet-container">
		<div class="vbo-bookdet-wrap">
			<div class="vbo-bookdet-head">
				<span>ID</span>
			<?php
			if (!empty($order['adminnotes'])) {
				?>
				<i class="vboicn-info icn-bigger icn-nomargin icn-float-left icn-clickable" onclick="vboUpdateModal('<?php echo addslashes(JText::translate('VBADMINNOTESTOGGLE')); ?>', '.adminnotes', true);"></i>
				<?php
			}
			?>
			</div>
			<div class="vbo-bookdet-foot">
				<span><a href="index.php?option=com_vikbooking&task=editorder&cid[]=<?php echo $order['id']; ?>" target="_blank"><?php echo $order['id']; ?></a></span>
			</div>
		</div>
		<?php
		if (!empty($order['channel'])) {
			?>
		<div class="vbo-bookdet-wrap">
			<div class="vbo-bookdet-head">
				<?php echo $otachannel; ?>
			</div>
			<div class="vbo-bookdet-foot">
				<span>ID <?php echo $otachannel_bid; ?></span>
			</div>
		</div>
			<?php
		}
		?>
		<div class="vbo-bookdet-wrap">
			<div class="vbo-bookdet-head">
				<span><?php echo JText::translate('VBEDITORDERONE'); ?></span>
			</div>
			<div class="vbo-bookdet-foot">
				<?php echo date(str_replace("/", $datesep, $df).' H:i', $order['ts']); ?>
			</div>
		</div>
		<?php
		if (count($customer)) {
		?>
		<div class="vbo-bookdet-wrap">
			<div class="vbo-bookdet-head">
				<span><?php echo JText::translate('VBCUSTOMERNOMINATIVE'); ?></span>
			<?php
			if ($missing_customer_det) {
				echo $vbo_app->createPopover(array('title' => JText::translate('VBCUSTOMERMISSIMPDET'), 'content' => JText::translate('VBCUSTOMERMISSIMPDETHELP'), 'icon_class' => VikBookingIcons::i('exclamation-triangle'), 'placement' => 'bottom'));
			} elseif (!empty($customer['notes'])) {
				?>
				<i class="vboicn-info icn-bigger icn-nomargin icn-float-left icn-clickable" onclick="vboUpdateModal('<?php echo addslashes(JText::translate('VBCUSTOMERNOTES')); ?>', '.customer_notes', true);"></i>
				<?php
			}
			?>
			</div>
			<div class="vbo-bookdet-foot">
				<?php echo (isset($customer['country_img']) ? $customer['country_img'].' ' : '').'<a href="javascript: void(0);" onclick="vboUpdateModal(\''.addslashes(JText::translate('VBCUSTINFO')).'\', \'.customer_info\', true);">'.ltrim($customer['first_name'].' '.$customer['last_name']).'</a>'; ?>
			</div>
		</div>
		<?php
		}
		?>
		<div class="vbo-bookdet-wrap">
			<div class="vbo-bookdet-head">
				<span><?php echo JText::translate('VBEDITORDERROOMSNUM'); ?></span>
			</div>
			<div class="vbo-bookdet-foot">
				<?php echo $order['roomsnum']; ?>
			</div>
		</div>
		<div class="vbo-bookdet-wrap">
			<div class="vbo-bookdet-head">
				<span><?php echo JText::translate('VBEDITORDERFOUR'); ?></span>
			</div>
			<div class="vbo-bookdet-foot">
				<?php echo $order['days']; ?>
			</div>
		</div>
		<div class="vbo-bookdet-wrap">
			<div class="vbo-bookdet-head">
				<span><?php echo JText::translate('VBEDITORDERFIVE'); ?></span>
			</div>
			<div class="vbo-bookdet-foot">
			<?php
			$checkin_info = getdate($order['checkin']);
			$short_wday = JText::translate('VB'.strtoupper(substr($checkin_info['weekday'], 0, 3)));
			?>
				<?php echo $short_wday.', '.date(str_replace("/", $datesep, $df).' H:i', $order['checkin']); ?>
			</div>
		</div>
		<div class="vbo-bookdet-wrap">
			<div class="vbo-bookdet-head">
				<span><?php echo JText::translate('VBEDITORDERSIX'); ?></span>
			</div>
			<div class="vbo-bookdet-foot">
			<?php
			$checkout_info = getdate($order['checkout']);
			$short_wday = JText::translate('VB'.strtoupper(substr($checkout_info['weekday'], 0, 3)));
			?>
				<?php echo $short_wday.', '.date(str_replace("/", $datesep, $df).' H:i', $order['checkout']); ?>
			</div>
		</div>
		<div class="vbo-bookdet-wrap vbo-bookdet-wrap-special">
			<div class="vbo-bookdet-head">
				<span><?php echo JText::translate('VBOCHECKEDSTATUS'); ?></span>
			</div>
			<div class="vbo-bookdet-foot">
			<?php
			if ($order['checked'] < 0) {
				//no show
				$checked_status = '<span class="label label-error">'.JText::translate('VBOCHECKEDSTATUSNOS').'</span>';
				$set_parent_status = $pchanged > 0 ? '<span style="font-weight: bold; color: red;">'.strtoupper(JText::translate('VBOCHECKEDSTATUSNOS')).'</span>' : $set_parent_status;
			} elseif ($order['checked'] == 1) {
				//checked in
				$checked_status = '<span class="label label-success">'.JText::translate('VBOCHECKEDSTATUSIN').'</span>';
				$set_parent_status = $pchanged > 0 ? '<span style="font-weight: bold; color: green;">'.strtoupper(JText::translate('VBOCHECKEDSTATUSIN')).'</span>' : $set_parent_status;
			} elseif ($order['checked'] == 2) {
				//checked out
				$checked_status = '<span class="label label-info">'.JText::translate('VBOCHECKEDSTATUSOUT').'</span>';
				$set_parent_status = $pchanged > 0 ? '<span style="font-weight: bold; color: green;">'.strtoupper(JText::translate('VBOCHECKEDSTATUSOUT')).'</span>' : $set_parent_status;
			} else {
				//none (0)
				$checked_status = '<span class="label">'.JText::translate('VBOCHECKEDSTATUSZERO').'</span>';
				$set_parent_status = $pchanged > 0 ? '<span style="font-weight: bold; color: green;">'.strtoupper(JText::translate('VBCONFIRMED')).'</span>' : $set_parent_status;
			}
			?>
				<?php echo $checked_status; ?>
			</div>
		</div>
		<?php
		if (is_array($previous_checkins) && count($previous_checkins) && $order['checked'] < 1) {
			foreach ($previous_checkins as $k => $prev_checkin) {
				$previous_checkins[$k]['checkin_dt'] = date(str_replace("/", $datesep, $df), $prev_checkin['checkin']);
				$previous_checkins[$k]['checkout_dt'] = date(str_replace("/", $datesep, $df), $prev_checkin['checkout']);
				$previous_checkins[$k]['ts_dt'] = date(str_replace("/", $datesep, $df), $prev_checkin['ts']);
				$book_tot_guests = 0;
				$prev_checkin['pax_data'] = empty($prev_checkin['pax_data']) || !is_array($prev_checkin['pax_data']) ? [] : $prev_checkin['pax_data'];
				foreach ($prev_checkin['pax_data'] as $rnum => $room_pax) {
					foreach ($room_pax as $guest_num => $guest_data) {
						$book_tot_guests++;
					}
				}
				$previous_checkins[$k]['tot_guests'] = $book_tot_guests;
			}
			?>
		<div class="vbo-bookdet-wrap">
			<div class="vbo-bookdet-head">
				<span><?php echo JText::translate('VBO_PREVIOUS_CHECKINS'); ?></span>
			</div>
			<div class="vbo-bookdet-foot">
				<button type="button" class="btn btn-small btn-primary vbo-context-menu-btn vbo-context-menu-prevchkins">
					<span class="vbo-context-menu-lbl"><?php echo JText::translate('VBO_SELECT'); ?></span>
					<span class="vbo-context-menu-ico"><?php VikBookingIcons::e('sort-down'); ?></span>
				</button>
			</div>
		</div>

		<script type="text/javascript">
			var prev_checkins = <?php echo json_encode($previous_checkins); ?>;
			var prev_checkins_btns = [];

			function populatePreviousCheckin(pax_data) {
				if (!pax_data || !pax_data.length) {
					alert('Invalid previous check-in information');
					return false;
				}

				var room_num = 0,
					guest_num = 1,
					guests_found = 0;

				pax_data.forEach((room_pax, room_index) => {
					for (let guest_num in room_pax) {
						if (!room_pax.hasOwnProperty(guest_num)) {
							continue;
						}
						if (jQuery('.vbo-roomdet-wrapper[data-roomnum="' + room_num + '"]').length && !jQuery('.vbo-roomdet-guest-details[data-guestnum="' + guest_num + '"]').length) {
							// try to go to the first guest of the next room
							room_num++;
							guest_num = 1;
						}
						let guest_found = false;
						for (let prop_name in room_pax[guest_num]) {
							if (!room_pax[guest_num].hasOwnProperty(prop_name)) {
								continue;
							}
							let field_name = 'guests[' + room_num + '][' + guest_num + '][' + prop_name + ']';
							if (jQuery('[name="' + field_name + '"]').length) {
								jQuery('[name="' + field_name + '"]').val(room_pax[guest_num][prop_name]).trigger('change');
								if (!guest_found) {
									guests_found++;
									guest_found = true;
								}
							}
						}
						guest_num++;
						if (jQuery('#vbo-roomdet-guests-details-' + room_num).length && !jQuery('#vbo-roomdet-guests-details-' + room_num).is(':visible')) {
							jQuery('#vbo-roomdet-guests-details-' + room_num).slideDown();
						}
					}
				});

				return guests_found;
			}

			prev_checkins.forEach((prev_checkin, index) => {
				prev_checkins_btns.push({
					icon: '<?php echo VikBookingIcons::i('calendar-check'); ?>',
					text: Joomla.JText._('VBRENTALORD') + ' #' + prev_checkin['idorder'] + ' - ' + Joomla.JText._('VBPVIEWORDERSPEOPLE') + ': ' + prev_checkin['tot_guests'],
					separator: (index === prev_checkins.length - 1),
					action: (root, config) => {
						var guests_populated = populatePreviousCheckin(prev_checkins[index]['pax_data']);
						console.log('guests_populated: ' + guests_populated);
					},
				});
			});

			jQuery(function() {
				jQuery('.vbo-context-menu-prevchkins').vboContextMenu({
					placement: 'bottom-left',
					buttons: prev_checkins_btns,
				});
			});
		</script>
		<?php
		}
		?>
	</div>
		<?php
		//rooms details and total information
		?>
	<div class="vbo-checkin-main-block">
		<div class="vbo-roomsdet-container">
			<?php
			$tars = array();
			$arrpeople = array();
			$is_package = (!empty($order['pkg']));
			$is_cust_cost = false;
			foreach ($rooms as $ind => $or) {
				$num = $ind + 1;
				$arrpeople[$num]['adults'] = $or['adults'];
				$arrpeople[$num]['children'] = $or['children'];
				$arrpeople[$num]['children_age'] = $or['childrenage'];
				$arrpeople[$num]['t_first_name'] = $or['t_first_name'];
				$arrpeople[$num]['t_last_name'] = $or['t_last_name'];
				if ($is_package === true || (!empty($or['cust_cost']) && $or['cust_cost'] > 0.00)) {
					//package or custom cost set from the back-end
					$is_cust_cost = true;
					continue;
				}
				$q = "SELECT * FROM `#__vikbooking_dispcost` WHERE `id`=".(int)$or['idtar'].";";
				$dbo->setQuery($q);
				$dbo->execute();
				$tar = $dbo->loadAssocList();
				$tars[$num] = $tar;
			}
			//compose the calculation for pax_data
			$pax_data = array();
			$count_pax_data = 0;
			if (count($customer) > 0) {
				$count_pax_data = 1;
				if (!empty($customer['pax_data'])) {
					$pax_data = json_decode($customer['pax_data'], true);
					$pax_data = !is_array($pax_data) ? array() : $pax_data;
				}
			}
			$all_countries = VikBooking::getCountriesArray();
			//
			foreach ($rooms as $ind => $or) {
				$num = $ind + 1;
				//total guests details available
				$count_pax_data = $num < 2 ? $count_pax_data : 0;
				if (isset($pax_data[$ind])) {
					$count_pax_data = count($pax_data[$ind]);
				}

				//Room Specific Unit
				$spec_unit = '';
				if (!empty($or['params'])) {
					$room_params = json_decode($or['params'], true);
					$arr_features = array();
					if (is_array($room_params) && array_key_exists('features', $room_params) && @count($room_params['features']) > 0) {
						foreach ($room_params['features'] as $rind => $rfeatures) {
							foreach ($rfeatures as $fname => $fval) {
								if (strlen($fval)) {
									$arr_features[$rind] = '#'.$rind.' - '.JText::translate($fname).': '.$fval;
									break;
								}
							}
						}
					}
					if (isset($arr_features[$or['roomindex']])) {
						$spec_unit = $arr_features[$or['roomindex']];
					}
				}

				// calculate the number of guests to register depending on settings
				$guests_to_register = $pax_fields_obj->registerChildren() ? ($arrpeople[$num]['adults'] + $arrpeople[$num]['children']) : $arrpeople[$num]['adults'];
				?>
			<div class="vbo-roomdet-wrapper" data-roomnum="<?php echo $ind; ?>">
				<div class="vbo-roomdet-wrap">
					<div class="vbo-roomdet-entry">
						<div class="vbo-roomdet-head">
							<span><?php echo $or['room_name']; ?></span>
						</div>
						<div class="vbo-roomdet-foot">
						<?php
						if (!empty($spec_unit)) {
							?>
							<span><?php echo $spec_unit; ?></span>
							<?php
						}
						?>
							<div class="vbo-roomdet-guests-toggle-cont">
								<span tabindex="0" class="vbo-roomdet-guests-toggle <?php echo $count_pax_data >= $guests_to_register ? 'vbo-guestscount-complete' : 'vbo-guestscount-incomplete'; ?>" data-roomind="<?php echo $ind; ?>">
									<i class="vboicn-user-plus"></i> 
									<span class="vbo-roomdet-guests-toggleword"><?php echo JText::translate('VBOGUESTSDETAILS'); ?> (<span id="vbo-guestscount-<?php echo $ind; ?>"><?php echo $count_pax_data; ?></span>/<?php echo $guests_to_register; ?>)</span>
								</span>
							</div>
						</div>
					</div>
					<div class="vbo-roomdet-entry">
						<div class="vbo-roomdet-head">
							<span><?php echo JText::translate('VBEDITORDERADULTS'); ?></span>
						</div>
						<div class="vbo-roomdet-foot">
							<span><?php echo $arrpeople[$num]['adults']; ?></span>
						</div>
					</div>
				<?php
				$age_str = '';
				if ($arrpeople[$num]['children'] > 0) {
					if (!empty($arrpeople[$num]['children_age'])) {
						$json_child = json_decode($arrpeople[$num]['children_age'], true);
						if (is_array($json_child['age']) && count($json_child['age']) > 0) {
							$age_str = ' '.JText::sprintf('VBORDERCHILDAGES', implode(', ', $json_child['age']));
						}
					}
				}
				?>
					<div class="vbo-roomdet-entry">
						<div class="vbo-roomdet-head">
							<span><?php echo JText::translate('VBEDITORDERCHILDREN'); ?></span>
						</div>
						<div class="vbo-roomdet-foot">
							<span><?php echo $arrpeople[$num]['children'].$age_str; ?></span>
						</div>
					</div>
					<div class="vbo-roomdet-entry">
						<div class="vbo-roomdet-head">
							<span><?php echo JText::translate('VBEDITORDERSEVEN'); ?></span>
						</div>
						<div class="vbo-roomdet-foot">
						<?php
						if ($is_package === true || (!empty($or['cust_cost']) && $or['cust_cost'] > 0.00)) {
							?>
							<span>
							<?php
							if (!empty($or['pkg_name'])) {
								//package
								echo $or['pkg_name'];
							} else {
								//custom cost can have an OTA Rate Plan name
								if (!empty($or['otarplan'])) {
									echo ucwords($or['otarplan']);
								} else {
									echo JText::translate('VBOROOMCUSTRATEPLAN');
								}
							}
							?>
								<?php echo $currencysymb; ?> <?php echo VikBooking::numberFormat($or['cust_cost']); ?>
							</span>
							<?php
						} elseif (array_key_exists($num, $tars) && !empty($tars[$num][0]['idprice'])) {
							$display_rate = !empty($or['room_cost']) ? $or['room_cost'] : $tars[$num][0]['cost'];
							?>
							<span>
								<?php echo VikBooking::getPriceName($tars[$num][0]['idprice']); ?> 
							<?php
							if (!empty($or['room_cost'])) {
								echo $currencysymb.' '.VikBooking::numberFormat(VikBooking::sayCostPlusIva($display_rate, $tars[$num][0]['idprice']));
							}
							?>
							</span>
							<?php
						}  elseif (!empty($or['otarplan'])) {
							?>
							<span><?php echo ucwords($or['otarplan']); ?></span>
							<?php
						} elseif ($this->row['closure'] < 1) {
							?>
							<span><?php echo JText::translate('VBOROOMNORATE'); ?></span>
							<?php
						} else {
							?>
							<span>-----</span>
							<?php
						}
						?>
						</div>
					</div>
					<div class="vbo-roomdet-entry">
						<div class="vbo-roomdet-head">
							<span><?php echo JText::translate('VBEDITORDEREIGHT'); ?></span>
						</div>
						<div class="vbo-roomdet-foot">
					<?php
					if (!empty($or['optionals'])) {
						$stepo = explode(";", $or['optionals']);
						foreach ($stepo as $roptkey => $oo) {
							if (empty($oo)) {
								continue;
							}
							$hide_price = false;
							$stept = explode(":", $oo);
							$actopt = VikBooking::getSingleOption($stept[0]);
							if (!$actopt) {
								continue;
							}
							$chvar = '';
							if (!empty($actopt['ageintervals']) && $or['children'] > 0 && strstr($stept[1], '-') != false) {
								$optagenames = VikBooking::getOptionIntervalsAges($actopt['ageintervals']);
								$optagepcent = VikBooking::getOptionIntervalsPercentage($actopt['ageintervals']);
								$optageovrct = VikBooking::getOptionIntervalChildOverrides($actopt, $or['adults'], $or['children']);
								$child_num 	 = VikBooking::getRoomOptionChildNumber($or['optionals'], $actopt['id'], $roptkey, $or['children']);
								$optagecosts = VikBooking::getOptionIntervalsCosts(isset($optageovrct['ageintervals_child' . ($child_num + 1)]) ? $optageovrct['ageintervals_child' . ($child_num + 1)] : $actopt['ageintervals']);
								$agestept = explode('-', $stept[1]);
								$stept[1] = $agestept[0];
								$chvar = $agestept[1];
								if (array_key_exists(($chvar - 1), $optagepcent) && $optagepcent[($chvar - 1)] == 1) {
									//percentage value of the adults tariff
									if ($is_package === true || (!empty($or['cust_cost']) && $or['cust_cost'] > 0.00)) {
										$optagecosts[($chvar - 1)] = $or['cust_cost'] * $optagecosts[($chvar - 1)] / 100;
									} else {
										$display_rate = !empty($or['room_cost']) ? $or['room_cost'] : $tars[$num][0]['cost'];
										$hide_price = empty($or['room_cost']) ? true : $hide_price;
										$optagecosts[($chvar - 1)] = $display_rate * $optagecosts[($chvar - 1)] / 100;
									}
								} elseif (array_key_exists(($chvar - 1), $optagepcent) && $optagepcent[($chvar - 1)] == 2) {
									//VBO 1.10 - percentage value of room base cost
									if ($is_package === true || (!empty($or['cust_cost']) && $or['cust_cost'] > 0.00)) {
										$optagecosts[($chvar - 1)] = $or['cust_cost'] * $optagecosts[($chvar - 1)] / 100;
									} else {
										$display_rate = isset($tars[$num][0]['room_base_cost']) ? $tars[$num][0]['room_base_cost'] : (!empty($or['room_cost']) ? $or['room_cost'] : $tars[$num][0]['cost']);
										$hide_price = empty($or['room_cost']) ? true : $hide_price;
										$optagecosts[($chvar - 1)] = $display_rate * $optagecosts[($chvar - 1)] / 100;
									}
								}
								$actopt['chageintv'] = $chvar;
								$actopt['name'] .= ' ('.$optagenames[($chvar - 1)].')';
								$realcost = (intval($actopt['perday']) == 1 ? (floatval($optagecosts[($chvar - 1)]) * $order['days'] * $stept[1]) : (floatval($optagecosts[($chvar - 1)]) * $stept[1]));
							} else {
								// VBO 1.11 - options percentage cost of the room total fee
								if ($is_package === true || (!empty($or['cust_cost']) && $or['cust_cost'] > 0.00)) {
									$deftar_basecosts = $or['cust_cost'];
								} else {
									$deftar_basecosts = !empty($or['room_cost']) ? $or['room_cost'] : $tars[$num][0]['cost'];
								}
								$actopt['cost'] = (int)$actopt['pcentroom'] ? ($deftar_basecosts * $actopt['cost'] / 100) : $actopt['cost'];
								//
								$realcost = (intval($actopt['perday']) == 1 ? ($actopt['cost'] * $order['days'] * $stept[1]) : ($actopt['cost'] * $stept[1]));
							}
							if ($actopt['maxprice'] > 0 && $realcost > $actopt['maxprice']) {
								$realcost = $actopt['maxprice'];
								if (intval($actopt['hmany']) == 1 && intval($stept[1]) > 1) {
									$realcost = $actopt['maxprice'] * $stept[1];
								}
							}
							$realcost = $actopt['perperson'] == 1 ? ($realcost * $arrpeople[$num]['adults']) : $realcost;
							$tmpopr = VikBooking::sayOptionalsPlusIva($realcost, $actopt['idiva']);
							?>
							<div class="vbo-roomdet-foot-options">
								<?php echo ($stept[1] > 1 ? $stept[1]." " : "").$actopt['name'].(!$hide_price ? " ".$currencysymb." ".VikBooking::numberFormat($tmpopr) : ''); ?>
							</div>
							<?php
						}
					}
					?>
						</div>
					</div>
					<div class="vbo-roomdet-entry">
						<div class="vbo-roomdet-head">
							<span><?php echo JText::translate('VBPEDITBUSYEXTRACOSTS'); ?></span>
						</div>
						<div class="vbo-roomdet-foot">
					<?php
					if (!empty($or['extracosts'])) {
						$cur_extra_costs = json_decode($or['extracosts'], true);
						foreach ($cur_extra_costs as $eck => $ecv) {
							?>
							<div class="vbo-roomdet-foot-extras">
								<?php echo $ecv['name']." ".$currencysymb." ".VikBooking::numberFormat(VikBooking::sayOptionalsPlusIva($ecv['cost'], $ecv['idtax'])); ?>
							</div>
							<?php
						}
					}
					?>
						</div>
					</div>
				</div>
				<?php
				if ($arrpeople[$num]['adults'] > 0) {
					?>
				<div class="vbo-roomdet-guests-details" id="vbo-roomdet-guests-details-<?php echo $ind; ?>">
					<?php
					/**
					 * Back-end guests registration now relies on dynamic drivers within Vik Booking.
					 * 
					 * @since 	1.15.0 (J) - 1.5.0 (WP)
					 */

					// loop through all guests that should be registered within this room-party
					for ($g = 1; $g <= $guests_to_register; $g++) {
						$current_guest = array();
						if (count($pax_data) && isset($pax_data[$ind]) && isset($pax_data[$ind][$g])) {
							$current_guest = $pax_data[$ind][$g];
						} elseif ($ind < 1 && $g == 1 && count($customer)) {
							$current_guest = $customer;
						}
						?>
					<div class="vbo-roomdet-guest-details" data-roomind="<?php echo $ind; ?>" data-guestnum="<?php echo $g; ?>" data-totguests="<?php echo $guests_to_register; ?>">
						<div class="vbo-roomdet-guest-detail vbo-roomdet-guest-detail-num">
							<span><?php echo JText::sprintf('VBOGUESTNUM', $g); ?></span>
						</div>
						<?php
						$pax_field_ind = 1;
						foreach ($pax_fields as $paxk => $paxv) {
							// get an instance of the VBOCheckinPaxfield object
							$pax_field_obj = $pax_fields_obj->getField($paxk);

							// detect the current type of guest
							$guest_type = $g > $arrpeople[$num]['adults'] ? 'child' : 'adult';

							// set object data
							$pax_field_obj->setGuestType($guest_type)
								->setFieldNumber($pax_field_ind)
								->setGuestNumber($g)
								->setGuestData($current_guest)
								->setRoomIndex($ind)
								->setRoomGuests($arrpeople[$num]['adults'], $arrpeople[$num]['children'])
								->setTotalRooms(count($arrpeople));

							// render input field
							$pax_field_html = $pax_fields_obj->render($pax_field_obj);
							if (empty($pax_field_html)) {
								// nothing to display, increase the counter and continue
								$pax_field_ind++;
								continue;
							}

							// access the implementor object
							$implementor = $pax_fields_obj->getFieldTypeImplementor($pax_field_obj);

							// check if a particular CSS class has been defined
							$field_container_class = $implementor->getContainerClassAttr();
							$field_container_class = !empty($field_container_class) ? " $field_container_class" : '';

							?>
						<div class="vbo-roomdet-guest-detail<?php echo $field_container_class; ?>">
							<div class="vbo-roomdet-guest-detail-lbl">
								<label for="<?php echo $implementor->getFieldIdAttr(); ?>"><?php echo $paxv; ?></label>
							</div>
							<div class="vbo-roomdet-guest-detail-val">
								<?php echo $pax_field_html; ?>
							</div>
						</div>
							<?php
							$pax_field_ind++;
						}
						// display any other information collected through the pre check-in
						if (count($pax_data) && isset($pax_data[$ind]) && isset($pax_data[$ind][$g])) {
							foreach ($pax_data[$ind][$g] as $extrak => $extrav) {
								if (isset($pax_fields[$extrak]) || (is_scalar($extrav) && !strlen($extrav))) {
									// this is a default pax field, we skip it
									continue;
								}
								$precheckin_field_lbl = isset($pre_pax_fields[$extrak]) ? $pre_pax_fields[$extrak] : $extrak;
								$precheckin_field_typ = isset($pre_pax_fields_attributes[$extrak]) && $pre_pax_fields_attributes[$extrak] == 'file' ? 'file' : 'text';
								?>
						<div class="vbo-roomdet-guest-detail vbo-roomdet-guest-detail-precheckin">
							<div class="vbo-roomdet-guest-detail-lbl">
								<span><?php echo $precheckin_field_lbl; ?></span>
							</div>
							<div class="vbo-roomdet-guest-detail-val">
								<?php
								if ($precheckin_field_typ == 'file') {
									// documents uploaded by the customer during pre-checkin
									$guest_files = explode('|', $extrav);
									foreach ($guest_files as $gfk => $guest_file) {
										if (empty($guest_file) || strpos($guest_file, 'http') !== 0) {
											continue;
										}
										$furl_segments = explode('/', $guest_file);
										$guest_fname = $furl_segments[(count($furl_segments) - 1)];
										$read_fname = substr($guest_fname, (strpos($guest_fname, '_') + 1));
										?>
								<div class="vbo-paxfield-file-uploaded">
									<a href="<?php echo $guest_file; ?>" target="_blank">
										<?php VikBookingIcons::e('image'); ?>
										<span><?php echo $read_fname; ?></span>
									</a>
								</div>
										<?php
									}
									?>
								<input type="hidden" data-gind="<?php echo $g; ?>" name="guests[<?php echo $ind; ?>][<?php echo $g; ?>][<?php echo $extrak; ?>]" value="<?php echo $extrav; ?>" />
									<?php
								} else {
									?>
								<input type="text" data-gind="<?php echo $g; ?>" class="vbo-paxfield" name="guests[<?php echo $ind; ?>][<?php echo $g; ?>][<?php echo $extrak; ?>]" value="<?php echo htmlspecialchars($extrav); ?>" />
									<?php
								}
								?>
							</div>
						</div>
								<?php
							}
						}
						?>
					</div>
						<?php
					}
					?>
				</div>
			</div>
					<?php
				}
			}
			?>
		</div>

		<div class="vbo-checkin-payment-container">
			<div class="vbo-checkin-payment-detail">
			<?php
			$bcolortag = VikBooking::applyBookingColorTag($order, $colortags);
			$usectag = '';
			if (count($bcolortag) > 0) {
				$bcolortag['name'] = JText::translate($bcolortag['name']);
				$usectag = '<span class="vbo-colortag-circle hasTooltip" style="background-color: '.$bcolortag['color'].';" title="'.htmlspecialchars($bcolortag['name']).'"></span> ';
			}
			?>
				<span class="vbo-checkin-payment-detail-lbl"><?php echo $usectag; ?><strong><?php echo JText::translate('VBEDITORDERNINE'); ?></strong></span>
				<span class="vbo-checkin-payment-detail-v"><?php echo (strlen($otacurrency) > 0 ? '('.$otacurrency.') '.$currencysymb : $currencysymb); ?> <?php echo VikBooking::numberFormat($order['total']); ?></span>
			</div>
		<?php
		if (!empty($order['totpaid']) && $order['totpaid'] > 0) {
			$diff_to_pay = $order['total'] - $order['totpaid'];
			?>
			<div class="vbo-checkin-payment-detail">
				<span class="vbo-checkin-payment-detail-lbl"><?php echo JText::translate('VBAMOUNTPAID'); ?></span>
				<span class="vbo-checkin-payment-detail-v"><?php echo $currencysymb; ?> <?php echo VikBooking::numberFormat($order['totpaid']); ?></span>
			</div>
			<?php
			if ($diff_to_pay > 0) {
				?>
			<div class="vbo-checkin-payment-detail">
				<span class="vbo-checkin-payment-detail-lbl"><?php echo JText::translate('VBTOTALREMAINING'); ?></span>
				<span class="vbo-checkin-payment-detail-v"><?php echo $currencysymb; ?> <span id="vbo-checkin-remaining"><?php echo VikBooking::numberFormat($diff_to_pay); ?></span></span>
			</div>
			<div class="vbo-checkin-payment-detail">
				<span class="vbo-checkin-payment-detail-lbl"><?php echo JText::translate('VBONEWAMOUNTPAID'); ?></span>
				<span class="vbo-checkin-payment-detail-v"><?php echo $currencysymb; ?> <input name="newtotpaid" id="newtotpaid" value="" min="0" type="number" step="any" style="margin: 0;"></span>
			</div>
				<?php
			}
		} else {
			// print the new amount paid anyway to let it update
			?>
			<div class="vbo-checkin-payment-detail">
				<span class="vbo-checkin-payment-detail-lbl"><?php echo JText::translate('VBONEWAMOUNTPAID'); ?></span>
				<span class="vbo-checkin-payment-detail-v"><?php echo $currencysymb; ?> <input name="newtotpaid" id="newtotpaid" value="" min="0" type="number" step="any" style="margin: 0;"></span>
			</div>
			<?php
		}
		$payment = VikBooking::getPayment($order['idpayment']);
		if (is_array($payment)) {
			?>
			<div class="vbo-checkin-payment-detail">
				<span class="vbo-checkin-payment-detail-lbl"><?php echo JText::translate('VBPAYMENTMETHOD'); ?></span>
				<span class="vbo-checkin-payment-detail-v"><?php echo $payment['name']; ?></span>
			</div>
			<?php
		}
		if (!empty($order['paymentlog'])) {
			?>
			<div class="vbo-checkin-payment-detail">
				<span class="vbo-checkin-payment-detail-lbl vbo-checkin-payment-detail-click" onclick="vboUpdateModal('<?php echo addslashes(JText::translate('VBPAYMENTLOGTOGGLE')); ?>', '.paymentlog', true);">
					<i class="vboicn-credit-card"></i> <?php echo JText::translate('VBPAYMENTLOGTOGGLE'); ?>
				</span>
			</div>
			<?php
		}
		?>
		</div>
	</div>

	<div class="vbo-checkin-notes-wrap">
		<div class="vbo-checkin-notes-inner">
			<div class="vbo-checkin-notes-trig">
				<span onclick="vboToggleCheckinNotes();"><i class="<?php echo count($customer) && !empty($customer['comments']) ? 'vboicn-bubbles2' : 'vboicn-bubble2'; ?>"></i> <?php echo JText::translate('VBOTOGGLECHECKINNOTES'); ?></span>
			</div>
			<div class="vbo-checkin-notes-cont">
				<textarea name="comments"><?php echo count($customer) && isset($customer['comments']) ? htmlspecialchars($customer['comments']) : ''; ?></textarea>
			</div>
		</div>
	</div>

	<div class="vbo-checkin-update-wrap">
		<div class="vbo-checkin-update-inner">
			<div>
				<button type="button" class="btn btn-primary" onclick="jQuery('#adminForm').submit();"><i class="vboicn-checkmark"></i> <?php echo JText::translate('VBOCHECKINUPDATEBTN'); ?></button>
			</div>
		</div>
	</div>

	<div class="vbo-checkin-commands-wrap">
		<div class="vbo-checkin-commands-inner">
		<?php
		if ($today_midnight < $order['checkout']) {
			$allowed_btns = array();
			if ($order['checked'] <= 0) {
				//check-in btn only for no-status or no-show
				$allowed_btns[] = 1;
			}
			if ($order['checked'] < 0 && !empty($order['channel']) && strpos($order['channel'], 'booking.com') !== false && VikBooking::vcmBcomReportingSupported($order)) {
				//report no show to Booking.com btn only for no-status
				$allowed_btns[] = -11;
			}
			if ($order['checked'] == 0) {
				//no-show button only if no-status
				$allowed_btns[] = -1;
			}
			if ($order['checked'] == 1 && (count($customer) && empty($customer['checkindoc']))) {
				//generate check-in doc button only for checked-in and no check-in doc
				$allowed_btns[] = 11;
			} elseif ($order['checked'] > 0 && (count($customer) && !empty($customer['checkindoc']))) {
				//download check-in doc button only for checked-in||out and check-in doc
				$allowed_btns[] = 12;
				//add also the possibility of re-creating the document
				$allowed_btns[] = 11;
			}
			if ($order['checked'] == 1 && $today_midnight > $order['checkin']) {
				//check-out button only for checked-in and after the check-in day
				$allowed_btns[] = 2;
			}
			if ($order['checked'] != 0) {
				//cancel button only if not no-status
				$allowed_btns[] = 0;
			}
			//print buttons
			foreach ($allowed_btns as $chstatus) {
				switch ($chstatus) {
					case -1:
						?>
			<div class="vbo-checkin-commands-btn">
				<button type="button" onclick="vboSetCheckinAction(-1);" class="btn btn-large btn-danger"><i class="vboicn-blocked"></i> <?php echo JText::translate('VBOSETCHECKEDSTATUSNOS'); ?></button>
			</div>
						<?php
						break;
					case 0:
						?>
			<div class="vbo-checkin-commands-btn">
				<button type="button" onclick="vboSetCheckinAction(0);" class="btn btn-large btn-secondary"><i class="vboicn-cancel-circle"></i> <?php echo JText::translate('VBOSETCHECKEDSTATUSZERO'); ?></button>
			</div>
						<?php
						break;
					case 1:
						?>
			<div class="vbo-checkin-commands-btn">
				<button type="button" onclick="vboSetCheckinAction(1);" class="btn btn-large btn-success"><i class="vboicn-checkmark"></i> <?php echo JText::translate('VBOSETCHECKEDSTATUSIN'); ?></button>
			</div>
						<?php
						break;
					case 2:
						?>
			<div class="vbo-checkin-commands-btn">
				<button type="button" onclick="vboSetCheckinAction(2);" class="btn btn-large btn-success"><i class="vboicn-exit"></i> <?php echo JText::translate('VBOSETCHECKEDSTATUSOUT'); ?></button>
			</div>
						<?php
						break;
					case 11:
						?>
			<div class="vbo-checkin-commands-btn">
				<a href="index.php?option=com_vikbooking&task=gencheckindoc&cid[]=<?php echo $order['id'].($tmpl == 'component' ? '&tmpl=component' : ''); ?>" class="btn btn-large btn-success"><i class="vboicn-profile"></i> <?php echo JText::translate('VBOGENCHECKINDOC'); ?></a>
			</div>
						<?php
						break;
					case 12:
						?>
			<div class="vbo-checkin-commands-btn">
				<button type="button" onclick="window.open('<?php echo VBO_SITE_URI.'helpers/checkins/generated/'.$customer['checkindoc']; ?>', '_blank');" class="btn btn-large btn-success"><i class="vboicn-download"></i> <?php echo JText::translate('VBODWNLCHECKINDOC'); ?></button>
			</div>
						<?php
						break;
					case -11:
						/**
						 * Build the contextual menu buttons to report the no-show to the OTA through AJAX requests.
						 * 
						 * @since 	  1.16.8 (J) - 1.6.8 (WP)
						 * 
						 * @requires  VCM >= 1.8.24
						 */
						?>
			<div class="vbo-checkin-commands-btn">
				<button type="button" class="btn btn-large btn-danger vbo-context-menu-btn vbo-context-menu-otanoshow">
					<span class="vbo-context-menu-lbl"><?php VikBookingIcons::e('ban'); ?> <?php echo JText::translate('VBOBCOMREPORTNOSHOW'); ?></span>
					<span class="vbo-context-menu-ico"><?php VikBookingIcons::e('sort-down'); ?></span>
				</button>
			</div>

			<script type="text/javascript">
				jQuery(function() {

					// callback constant will receive this.waived_fees (do not use an arrow function)
					const notify_noshow = function() {
						if (confirm('<?php echo addslashes(JText::translate('VBOBCOMREPORTNOSHOWCONF')); ?>')) {
							// show inner modal
							VBOCore.displayModal({
								title:         '.....',
								dismiss_event: 'vbo-bookingcheckin-ajax-dismiss',
								loading_event: 'vbo-bookingcheckin-ajax-loading',
								loading_body:  '<?php VikBookingIcons::e('circle-notch', 'fa-spin fa-fw'); ?>',
							});

							// start loading
							VBOCore.emitEvent('vbo-bookingcheckin-ajax-loading');

							// perform the request
							VBOCore.doAjax(
								"<?php echo VikBooking::ajaxUrl('index.php?option=com_vikchannelmanager&task=breporting.no_show'); ?>",
								{
									otaid: '<?php echo $order['idorderota']; ?>',
									waived_fees: this.waived_fees,
								},
								(success) => {
									// remove inner modal
									VBOCore.emitEvent('vbo-bookingcheckin-ajax-dismiss');

									// hide main modal
									try {
										jQuery('#jmodal-vbo-checkin-booking').modal('hide');
									} catch(e) {
										// do nothing
									}

									// reload (parent/current) window
									(window.parent || window).location.reload();
								},
								(error) => {
									// remove inner modal
									VBOCore.emitEvent('vbo-bookingcheckin-ajax-dismiss');

									// log and display the error
									console.error(error);
									alert(error.responseText);
								}
							);
						}
					};

					jQuery('.vbo-context-menu-otanoshow').vboContextMenu({
						placement: 'bottom-right',
						buttons: [
							{
								icon: '<?php echo VikBookingIcons::i('ban'); ?>',
								text: '<?php echo addslashes(JText::translate('VBO_DONT_WAIVE_FEES')); ?>',
								class: 'vbo-context-menu-entry-warning',
								separator: true,
								waived_fees: 0,
								action: notify_noshow,
							},
							{
								icon: '<?php echo VikBookingIcons::i('ban'); ?>',
								text: '<?php echo addslashes(JText::translate('VBO_DO_WAIVE_FEES')); ?>',
								class: 'vbo-context-menu-entry-danger',
								separator: false,
								waived_fees: 1,
								action: notify_noshow,
							},
						],
					});
				});
			</script>
						<?php
						break;
					default:
						break;
				}
			}
		} else {
			?>
			<p class="warn"><?php echo JText::translate('VBOCHECKINTIMEOVER'); ?></p>
			<?php
			//if the document exists, print the download button even if the check-out date is in the past
			if ($order['checked'] != 0 && (count($customer) && !empty($customer['checkindoc']))) {
				//download check-in doc button only for checked-in||out and check-in doc
				?>
			<div class="vbo-checkin-commands-btn">
				<button type="button" onclick="window.open('<?php echo VBO_SITE_URI.'helpers/checkins/generated/'.$customer['checkindoc']; ?>', '_blank');" class="btn btn-large btn-success"><i class="vboicn-download"></i> <?php echo JText::translate('VBODWNLCHECKINDOC'); ?></button>
			</div>
				<?php
			}
		}
		?>
		</div>
	</div>
	<input type="hidden" name="cid[]" value="<?php echo $order['id']; ?>">
	<input type="hidden" name="checkin_action" id="vbo-checkin-action" value="<?php echo $order['checked']; ?>">
	<input type="hidden" name="task" value="updatebookingcheckin" />
	<input type="hidden" name="option" value="com_vikbooking" />
		<?php
		if ($tmpl == 'component') {
		?>
	<input type="hidden" name="tmpl" value="component" />
		<?php
		}
		?>
</form>
		<?php
		//
		?>
	<script type="text/javascript">
	var vbo_overlay_data = {};
	<?php
	if (!empty($order['adminnotes'])) {
		$order['adminnotes'] = VikBooking::strTrimLiteral(nl2br(htmlspecialchars($order['adminnotes'])));
		?>
	vbo_overlay_data['adminnotes'] = '<pre><?php echo addslashes($order['adminnotes']); ?></pre>';
		<?php
	}
	if (!empty($order['paymentlog'])) {
		$plain_log = htmlspecialchars($order['paymentlog']);
		$order['paymentlog'] = VikBooking::strTrimLiteral(nl2br(htmlspecialchars($order['paymentlog'])));
		?>
	vbo_overlay_data['paymentlog'] = '<pre><?php echo addslashes($order['paymentlog']); ?></pre>';
		<?php
		//PCI Data Retrieval
		if (!empty($order['idorderota']) && !empty($order['channel'])) {
			$channel_source = $order['channel'];
			if (strpos($order['channel'], '_') !== false) {
				$channelparts = explode('_', $order['channel']);
				$channel_source = $channelparts[0];
			}
			//Limit set to Check-out date at 29:59:59
			$checkout_info = getdate($order['checkout']);
			$checkout_midnight = mktime(23, 59, 59, $checkout_info['mon'], $checkout_info['mday'], $checkout_info['year']);
			if (time() < $checkout_midnight) {
				if (stripos($plain_log, 'card number') !== false && strpos($plain_log, '****') !== false) {
					/*
					 * Log contains credit card details.
					 * Build the URI for the administrator section of VCM according to the platform.
					 */
					$exec_pcid_uri = VBOFactory::getPlatform()->getUri()->admin('index.php?option=com_vikchannelmanager&task=execpcid&channel_source=' . $channel_source . '&otaid=' . $order['idorderota'] . '&tmpl=component', false);
					?>
	var pci_vcm_frame = '<iframe src="<?php echo $exec_pcid_uri; ?>"></iframe>';
	vbo_overlay_data['paymentlog'] = '<div class="vcm-notif-pcidrq-container">'+
			'<button type="button" class="btn vbo-config-btn vcm-pcid-launch" onclick="vboUpdateModal(\'<?php echo addslashes(JText::translate('VBPAYMENTLOGTOGGLE')); ?>\', pci_vcm_frame, false);">'+
				'<?php VikBookingIcons::e('credit-card'); ?>'+
				'<?php echo addslashes(JText::translate('GETFULLCARDDETAILS')); ?>'+
			'</button>'+
		'</div>'+
		vbo_overlay_data['paymentlog'];
					<?php
				}
			}
		}
		//
	}
	if (count($customer) && !empty($customer['notes'])) {
		$customer['notes'] = VikBooking::strTrimLiteral(nl2br(htmlspecialchars($customer['notes'])));
		?>
	vbo_overlay_data['customer_notes'] = '<pre><?php echo addslashes($customer['notes']); ?></pre>';
		<?php
	}
	if (count($customer)) {
		$displayable_fields = array(
			'first_name' => JText::translate('VBCUSTOMERFIRSTNAME'),
			'last_name' => JText::translate('VBCUSTOMERLASTNAME'),
			'company' => JText::translate('VBCUSTOMERCOMPANY'),
			'vat' => JText::translate('VBCUSTOMERCOMPANYVAT'),
			'email' => JText::translate('VBCUSTOMEREMAIL'),
			'phone' => JText::translate('VBCUSTOMERPHONE'),
			'address' => JText::translate('VBCUSTOMERADDRESS'),
			'city' => JText::translate('VBCUSTOMERCITY'),
			'zip' => JText::translate('VBCUSTOMERZIP'),
			'country' => JText::translate('VBCUSTOMERCOUNTRY'),
			'gender' => JText::translate('VBCUSTOMERGENDER'),
			'bdate' => JText::translate('VBCUSTOMERBDATE'),
			'pbirth' => JText::translate('VBCUSTOMERPBIRTH'),
			'doctype' => JText::translate('VBCUSTOMERDOCTYPE'),
			'docnum' => JText::translate('VBCUSTOMERDOCNUM')
		);
		/**
		 * We use a link rather than a button to support the
		 * modal navigation through AJAX on both J and WP.
		 */
		?>
	vbo_overlay_data['customer_info'] = '<a class="btn btn-primary pull-right" href="index.php?option=com_vikbooking&task=editcustomer&cid[]=<?php echo $customer['id'].'&checkin=1&bid='.$order['id'].($tmpl == 'component' ? '&tmpl=component' : ''); ?>"><i class="vboicn-profile"></i> <?php echo addslashes(JText::translate('VBMAINCUSTOMEREDIT')); ?></a>'+
	'<div class="vbo-checkin-custdet-cont">'+
		<?php
		foreach ($displayable_fields as $k => $v) {
			$customer_val = isset($customer[$k]) && !empty($customer[$k]) ? addslashes(VikBooking::strTrimLiteral(nl2br(htmlspecialchars($customer[$k])))) : '---';
		?>
		'<div class="vbo-checkin-custdet-entry">'+
			'<span class="vbo-checkin-custdet-key<?php echo (!isset($customer[$k]) || empty($customer[$k])) && in_array($k, $sensible_k) ? ' vbo-checkin-custdet-key-warn' : ''; ?>"><?php echo addslashes(JText::translate($v)); ?></span>'+
			'<span class="vbo-checkin-custdet-value"><?php echo $customer_val; ?></span>'+
		'</div>'+
		<?php
		}
		?>
	'</div>';
		<?php
	}
	if (!empty($set_parent_status)) {
		/**
		 * This function supports the admin widgets arriving today and departing today.
		 */
		?>
	if (jQuery('[data-status="<?php echo $order['id']; ?>"]', window.parent.document).length) {
		jQuery('[data-status="<?php echo $order['id']; ?>"]', window.parent.document).html('<?php echo str_replace("'", "", $set_parent_status); ?>');
	}
		<?php
	}
	?>
	</script>
	<script type="text/javascript">
	/* Global Variables and Functions */
	var vbo_overlay_on = false;
	var booking_total = <?php echo (float)$order['total']; ?>;
	var tot_rooms = <?php echo (int)$order['roomsnum']; ?>;
	var current_checked = <?php echo (int)$order['checked']; ?>;

	function vboOpenModal() {
	<?php
	if (VBOPlatformDetection::isWordPress()) {
		?>
		/**
		 * @wponly - we are inside the same parent page as the modal is loaded via Ajax.
		 * We need to select the values from the modal container.
		 */
		jQuery('.modal-body').find(".vbo-info-overlay-block").fadeToggle(400, function() {
			if (jQuery('.modal-body').find(".vbo-info-overlay-block").is(":visible")) {
				vbo_overlay_on = true;
			} else {
				vbo_overlay_on = false;
				jQuery('.modal-body').find('.vbo-overlay-checkin-body').html('');
			}
		});
		<?php
	} else {
		?>
		jQuery(".vbo-info-overlay-block").fadeToggle(400, function() {
			if (jQuery(".vbo-info-overlay-block").is(":visible")) {
				vbo_overlay_on = true;
			} else {
				vbo_overlay_on = false;
				jQuery('.vbo-overlay-checkin-body').html('');
			}
		});
		<?php
	}
	?>
	}

	function vboUpdateModal(title, body, call_toggle) {
	<?php
	if (VBOPlatformDetection::isWordPress()) {
		?>
		/**
		 * @wponly - we are inside the same parent page as the modal is loaded via Ajax.
		 * We need to select the values from the modal container.
		 */
		var keep_closing_btn = jQuery('.modal-body').find('#vbo-overlay-title').find('.vbo-info-overlay-title-close').html();
		jQuery('.modal-body').find('#vbo-overlay-title').html(title + '<span class="vbo-info-overlay-title-close">' + keep_closing_btn + '</span>');
		if (body.substr(0, 1) == '.') {
			//look for this value inside the global array
			body = body.substr(1, (body.length - 1));
			if (vbo_overlay_data.hasOwnProperty(body)) {
				body = vbo_overlay_data[body];
			}
		}
		jQuery('.modal-body').find('.vbo-overlay-checkin-body').html(body);
		if (call_toggle) {
			vboOpenModal();
		}
		/**
		 * @wponly - the customer_info 'body' has a link to another page, which must be manually
		 * registered for preventing the browser to follow it and make the Ajax request instead.
		 */
		var parent_modal_id = jQuery('.modal-body').find('.vbo-overlay-checkin-body').closest('.modal').attr('id');
		ajaxPreventFormSubmit(parent_modal_id);
		<?php
	} else {
		?>
		var keep_closing_btn = jQuery('#vbo-overlay-title').find('.vbo-info-overlay-title-close').html();
		jQuery('#vbo-overlay-title').html(title + '<span class="vbo-info-overlay-title-close">' + keep_closing_btn + '</span>');
		if (body.substr(0, 1) == '.') {
			//look for this value inside the global array
			body = body.substr(1, (body.length - 1));
			if (vbo_overlay_data.hasOwnProperty(body)) {
				body = vbo_overlay_data[body];
			}
		}
		jQuery('.vbo-overlay-checkin-body').html(body);
		if (call_toggle) {
			vboOpenModal();
		}
		<?php
	}
	?>
	}

	function vboToggleCheckinNotes() {
		jQuery('.vbo-checkin-notes-cont').toggle();
	}

	function vboSetCheckinAction(action) {
		jQuery('#vbo-checkin-action').val(action);
		if (action > 0) {
			//check if guests details were filled in for check-in/out actions
			var guests_filled = true;
			for (var i = 0; i < tot_rooms; i++) {
				var elem = jQuery(".vbo-roomdet-guests-toggle[data-roomind='"+i+"']");
				if (elem.length && elem.hasClass('vbo-guestscount-incomplete')) {
					guests_filled = false;
					break;
				}
			}
			if (!guests_filled) {
				if (confirm('<?php echo addslashes(JText::translate('VBOCHECKINACTCONFGUESTS')) ?>')) {
					jQuery('#adminForm').submit();
				}
				return true;
			}
		}
		jQuery('#adminForm').submit();
	}

	if (jQuery.isFunction(jQuery.fn.tooltip)) {
		jQuery(".hasTooltip").tooltip();
	} else {
		jQuery.fn.tooltip = function(){};
	}

	jQuery(function() {
		/* Guests Details events - Start */
		var focused = null;
		jQuery('.vbo-roomdet-guests-toggle').focus(function(e) {
			// focus event will be fired before the click event
			focused = null;
			e.stopPropagation();
			e.preventDefault();
			var roomind = jQuery(this).attr('data-roomind');
			var elem = jQuery('#vbo-roomdet-guests-details-'+roomind);
			if (elem.length && elem.is(':hidden')) {
				focused = '#vbo-roomdet-guests-details-'+roomind;
				elem.slideDown();
			}
		});
		jQuery('.vbo-roomdet-guests-toggle').click(function(e) {
			// click event will be fired after the focus event
			var roomind = jQuery(this).attr('data-roomind');
			var elem = jQuery('#vbo-roomdet-guests-details-'+roomind);
			if (!elem.length || focused == '#vbo-roomdet-guests-details-'+roomind) {
				// focus event has already displayed the content
				focused = null;
				return;
			}
			elem.slideToggle();
		});
		jQuery('.vbo-paxfield').keyup(function() {
			var cur_gind = jQuery(this).attr('data-gind');
			var roomind = jQuery(this).closest('.vbo-roomdet-guest-details').attr('data-roomind');
			var tot_room_guests = parseInt(jQuery(this).closest('.vbo-roomdet-guest-details').attr('data-totguests'));
			if (!cur_gind.length || isNaN(tot_room_guests) || !jQuery(this).hasClass('vbo-paxfield-'+roomind)) {
				return true;
			}
			var cur_val = jQuery(this).val();
			var tot_full_guests = 0;
			for (var i = 1; i <= tot_room_guests; i++) {
				var fullfilled = true;
				jQuery(".vbo-paxfield-"+roomind+"[data-gind='"+i+"']").each(function(k, v) {
					if (!jQuery(this).val().length) {
						fullfilled = false;
					}
				});
				if (fullfilled) {
					tot_full_guests++;
				}
			}
			if (tot_full_guests >= tot_room_guests) {
				var add_class = 'vbo-guestscount-complete';
				var rm_class = 'vbo-guestscount-incomplete';
				var ico_add_class = 'vboicn-user-check';
				var ico_rm_class = 'vboicn-user-plus';
			} else {
				var add_class = 'vbo-guestscount-incomplete';
				var rm_class = 'vbo-guestscount-complete';
				var ico_add_class = 'vboicn-user-plus';
				var ico_rm_class = 'vboicn-user-check';
			}
			jQuery('#vbo-guestscount-'+roomind).text(tot_full_guests).closest('.vbo-roomdet-guests-toggle').addClass(add_class).removeClass(rm_class).find('i').addClass(ico_add_class).removeClass(ico_rm_class);
		});
		/* Guests Details events - End */
		/* Overlay for Customer Notes, Booking Notes, Payment Logs - Start */
		jQuery(document).mouseup(function(e) {
			if (!vbo_overlay_on) {
				return false;
			}
			var vbo_overlay_cont = jQuery(".vbo-info-overlay-content");
			if (!vbo_overlay_cont.is(e.target) && vbo_overlay_cont.has(e.target).length === 0) {
				jQuery(".vbo-info-overlay-block").fadeOut();
				vbo_overlay_on = false;
			}
		});
		jQuery(document).keyup(function(e) {
			if (e.keyCode == 27 && vbo_overlay_on) {
				jQuery(".vbo-info-overlay-block").fadeOut();
				vbo_overlay_on = false;
			}
		});
		/* Overlay for Customer Notes, Booking Notes, Payment Logs - End */
		/* Update amount paid and remaining balance */
		jQuery('#newtotpaid').change(function() {
			if (!jQuery('#vbo-checkin-remaining').length) {
				return true;
			}
			var cur_val = parseFloat(jQuery(this).val());
			if (!(cur_val > 0)) {
				return true;
			}
			var new_val = booking_total - cur_val;
			jQuery('#vbo-checkin-remaining').text(new_val.toFixed(2));
		});
	});
	</script>
