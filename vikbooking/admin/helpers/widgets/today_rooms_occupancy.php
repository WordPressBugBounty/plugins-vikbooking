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

/**
 * Class handler for admin widget "today rooms occupancy".
 * 
 * @since 	1.4.0
 */
class VikBookingAdminWidgetTodayRoomsOccupancy extends VikBookingAdminWidget
{
	/**
	 * Class constructor will define the widget name and identifier.
	 */
	public function __construct()
	{
		// call parent constructor
		parent::__construct();

		$this->widgetName = JText::translate('VBDASHTODROCC');
		$this->widgetDescr = JText::translate('VBO_W_TODROCC_DESCR');
		$this->widgetId = basename(__FILE__, '.php');

		/**
		 * Define widget and icon and style name.
		 * 
		 * @since 	1.15.0 (J) - 1.5.0 (WP)
		 */
		$this->widgetIcon = '<i class="' . VikBookingIcons::i('users') . '"></i>';
		$this->widgetStyleName = 'green';
	}

	public function render(VBOMultitaskData $data = null)
	{
		$vbo_auth_bookings = JFactory::getUser()->authorise('core.vbo.bookings', 'com_vikbooking');
		$busy = VikBooking::getAdminWidgetsInstance()->loadBusyRecordsUnclosed();
		$info_rooms = array(
			'unpublished_rooms' => VikBooking::getAdminWidgetsInstance()->getRoomsData('unpublished_rooms'),
			'tot_rooms_units' => VikBooking::getAdminWidgetsInstance()->getRoomsData('tot_rooms_units'),
			'all_rooms_ids' => VikBooking::getAdminWidgetsInstance()->getRoomsData('all_rooms_ids'),
			'all_rooms_units' => VikBooking::getAdminWidgetsInstance()->getRoomsData('all_rooms_units'),
			'all_rooms_features' => VikBooking::getAdminWidgetsInstance()->getRoomsData('all_rooms_features'),
		);
		$today_tot_occupancy = 0;
		$today_end_ts = mktime(23, 59, 59, date("n"), date("j"), date("Y"));
		$today_info = getdate($today_end_ts);
		$tot_booked_today = 0;
		if (count($busy)) {
			foreach ($busy as $idroom => $rbusy) {
				if (in_array($idroom, $info_rooms['unpublished_rooms'])) {
					continue;
				}
				foreach ($rbusy as $b) {
					$tmpone = getdate($b['checkin']);
					$ritts = mktime(0, 0, 0, $tmpone['mon'], $tmpone['mday'], $tmpone['year']);
					$tmptwo = getdate($b['checkout']);
					$conts = mktime(0, 0, 0, $tmptwo['mon'], $tmptwo['mday'], $tmptwo['year']);
					if ($today_end_ts >= $ritts && $today_end_ts < $conts) {
						$tot_booked_today++;
					}
				}
			}
		}
		$today_tot_occupancy = $tot_booked_today;
		if ($today_tot_occupancy <= 0) {
			// no contents to be displayed
			return;
		}

		// render the necessary PHP/JS code for the modal window only once
		if (!defined('VBO_JMODAL_CHECKIN_BOOKING')) {
			define('VBO_JMODAL_CHECKIN_BOOKING', 1);
			?>
			<script type="text/javascript">
			function vboJModalShowCallback() {
				if (typeof vbo_t_on == "undefined") {
					return;
				}
				// simulate STOP click
				if (vbo_t_on) {
					vbo_t_on = false;
					clearTimeout(vbo_t);
					jQuery(".vbo-dashboard-refresh-play").fadeIn();
				}
			}
			function vboJModalHideCallback() {
				if (typeof vbo_t_on == "undefined") {
					return;
				}
				// simulate PLAY click
				if (!vbo_t_on) {
					vboStartTimer();
					jQuery(".vbo-dashboard-refresh-play").fadeOut();
				}
			}
			</script>
			<?php
			echo $this->vbo_app->getJmodalScript('', 'vboJModalHideCallback();', 'vboJModalShowCallback();');
			echo $this->vbo_app->getJmodalHtml('vbo-checkin-booking', JText::translate('VBOMANAGECHECKSINOUT'));
		}
		//

		$today_rbookmap = array();
		$today_bidbookmap = array();
		foreach ($busy as $idroom => $rbusy) {
			foreach ($rbusy as $b) {
				$tmpone = getdate($b['checkin']);
				$ritts = mktime(0, 0, 0, $tmpone['mon'], $tmpone['mday'], $tmpone['year']);
				$tmptwo = getdate($b['checkout']);
				$conts = mktime(0, 0, 0, $tmptwo['mon'], $tmptwo['mday'], $tmptwo['year']);
				if ($today_end_ts >= $ritts && $today_end_ts < $conts) {
					if (isset($today_rbookmap[$b['idroom']])) {
						$today_rbookmap[$b['idroom']]++;
						$today_bidbookmap[$b['idroom']][] = $b['id'];
					} else {
						$today_rbookmap[$b['idroom']] = 1;
						$today_bidbookmap[$b['idroom']] = array($b['id']);
					}
				}
			}
		}
		?>
<div class="vbo-admin-widget-wrapper">
	<div class="vbo-admin-widget-head vbo-dashboard-today-occ-head">
		<h4><?php echo $this->widgetIcon; ?> <span><?php echo $this->widgetName; ?></span></h4>
		<div class="btn-toolbar pull-right vbo-dashboard-search-today-occ">
			<div class="btn-wrapper input-append">
				<input type="text" class="today-search form-control" placeholder="<?php echo JText::translate('VBODASHSEARCHKEYS'); ?>">
				<button type="button" class="btn" onclick="jQuery('.today-search').val('').trigger('keyup');"><i class="icon-remove"></i></button>
			</div>
		</div>
	</div>
	<div class="vbo-dashboard-today-occ-listcont">
	<?php
	foreach ($today_rbookmap as $idr => $rbked) {
		$room_bookings_det = VikBooking::getRoomBookingsFromBusyIds($idr, $today_bidbookmap[$idr]);
		if ((count($room_bookings_det) == 1 && $room_bookings_det[0]['closure'] == 1) || !count($room_bookings_det)) {
			// skip rooms with just a closure, or with no real bookings (shared calendars)
			continue;
		}
		// calculate color for percentage of occupancy
		$tot_rooms_units = $info_rooms['all_rooms_units'][$idr];
		$tot_rooms_units = $tot_rooms_units < 1 ? 1 : $tot_rooms_units;
		$percentage_booked = round((100 * $rbked / $tot_rooms_units), 2);
		$occupancy_cls = 'vbo-roomocc-units-free';
		if ($percentage_booked > 33 && $percentage_booked <= 66) {
			$occupancy_cls = 'vbo-roomocc-units-half';
		} elseif ($percentage_booked > 66 && $percentage_booked < 100) {
			$occupancy_cls = 'vbo-roomocc-units-threefourth';
		} elseif ($percentage_booked >= 100) {
			$occupancy_cls = 'vbo-roomocc-units-full';
		}
		?>
		<div class="vbo-dashboard-today-roomocc">
			<div class="vbo-dashboard-today-roomocc-det">
				<h5>
					<span class="vbo-dashboard-today-roomocc-det-rname"><?php echo $info_rooms['all_rooms_ids'][$idr]; ?></span>
					<span class="vbo-dashboard-roomocc-units-fromto <?php echo $occupancy_cls; ?>">
						<span class="vbo-dashboard-roomocc-units-from"><?php echo $rbked; ?></span> / <span><?php echo $info_rooms['all_rooms_units'][$idr]; ?></span>
					</span>
				</h5>
				<div class="vbo-dashboard-today-roomocc-customers table-responsive">
					<table class="table vbo-table-search-today">
						<thead>
							<tr class="vbo-dashboard-today-roomocc-firstrow">
								<th class="left"><?php echo JText::translate('VBCUSTOMERNOMINATIVE'); ?></th>
								<th class="center">&nbsp;</th>
								<th class="right"><?php echo JText::translate('VBDASHUPRESFOUR'); ?></th>
							</tr>
							<tr class="warning no-results">
								<td colspan="7"><i class="vboicn-warning"></i> <?php echo JText::translate('VBONORESULTS'); ?></td>
							</tr>
						</thead>
						<tbody>
						<?php
						foreach ($room_bookings_det as $rbind => $room_booking) {
							$nominative = strlen($room_booking['nominative']) > 1 ? $room_booking['nominative'] : VikBooking::getFirstCustDataField($room_booking['custdata']);
							$country_flag = '';
							if (file_exists(VBO_ADMIN_PATH.DS.'resources'.DS.'countries'.DS.$room_booking['country'].'.png')) {
								$country_flag = '<img src="'.VBO_ADMIN_URI.'resources/countries/'.$room_booking['country'].'.png'.'" title="'.$room_booking['country'].'" class="vbo-country-flag vbo-country-flag-left"/>';
							}
							//Room specific unit
							$room_first_feature = '&nbsp;';
							if (!empty($room_booking['roomindex']) && array_key_exists($idr, $info_rooms['all_rooms_features']) && count($info_rooms['all_rooms_features'][$idr]) > 0) {
								foreach ($info_rooms['all_rooms_features'][$idr] as $rind => $rfeatures) {
									if ($rind != $room_booking['roomindex']) {
										continue;
									}
									foreach ($rfeatures as $fname => $fval) {
										if (strlen($fval)) {
											$room_first_feature = '#'.$rind.' - '.JText::translate($fname).': '.$fval;
											break 2;
										}
									}
								}
							}
							//
							$act_status = '';
							if ($vbo_auth_bookings && $room_booking['closure'] != 1 && $room_booking['checked'] != 0) {
								$act_status = '<button type="button" class="btn btn-small btn-primary pull-right pro-feature" onclick="vboOpenJModal(\'vbo-checkin-booking\', \'index.php?option=com_vikbooking&task=bookingcheckin&cid[]='.$room_booking['idorder'].'&tmpl=component\');"><i class="vboicn-users icn-nomargin"></i></button>';
							}
							?>
							<tr class="vbo-dashboard-today-roomocc-rows">
								<td class="searchable left"><?php echo $country_flag.'<a href="index.php?option=com_vikbooking&task=editorder&cid[]='.$room_booking['idorder'].'" target="_blank">'.$nominative.'</a>'; ?></td>
								<td class="searchable center"><?php echo $room_first_feature; ?></td>
								<td class="searchable right">
									<div class="vbo-dashboard-today-roomocc-row-checkout">
										<span class="vbo-dashboard-today-roomocc-checkout-dt"><?php echo date(str_replace("/", $this->datesep, $this->df).' H:i', $room_booking['checkout']); ?></span>
									<?php
									if (!empty($act_status)) {
										?>
										<span class="vbo-dashboard-today-roomocc-checkout-do"><?php echo $act_status; ?></span>
										<?php
									}
									?>
									</div>
								</td>
							</tr>
							<?php
						}
						?>
						</tbody>
					</table>
				</div>
			</div>
		</div>
		<?php
	}
	?>
	</div>
</div>

<script type="text/javascript">
	jQuery(function() {
		/* Attempt to append the modal container to the body for the multitask panel */
		let modal_container = jQuery('[id*="vbo-checkin-booking"][class*="modal"]');
		if (modal_container.length) {
			modal_container.first().appendTo('body');
		}

		/* Today Search */
		jQuery(".today-search").keyup(function () {
			var inp_elem = jQuery(this);
			var instance_elem = inp_elem.closest('.vbo-admin-widget-wrapper');
			var searchTerm = inp_elem.val();
			var searchSplit = searchTerm.replace(/ /g, "'):containsi('");
			jQuery.extend(jQuery.expr[':'], {'containsi': 
				function(elem, i, match, array) {
					return (elem.textContent || elem.innerText || '').toLowerCase().indexOf((match[3] || "").toLowerCase()) >= 0;
				}
			});
			instance_elem.find(".vbo-table-search-today tbody tr td.searchable").not(":containsi('" + searchSplit + "')").each(function(e) {
				jQuery(this).parent('tr').attr('visible', 'false');
			});
			instance_elem.find(".vbo-table-search-today tbody tr td.searchable:containsi('" + searchSplit + "')").each(function(e) {
				jQuery(this).parent('tr').attr('visible', 'true');
			});
			instance_elem.find('.vbo-table-search-today').each(function(k, v) {
				var jobCount = parseInt(jQuery(this).find('tbody tr[visible="true"]').length);
				if (jobCount > 0) {
					jQuery(this).find('.no-results').hide();
				} else {
					jQuery(this).find('.no-results').show();
				}
			});
		});
	});
</script>
		<?php
	}
}
