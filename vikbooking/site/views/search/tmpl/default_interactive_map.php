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

$geo = VikBooking::getGeocodingInstance();

$currencysymb = VikBooking::getCurrencySymb();
$vat_included = VikBooking::ivaInclusa();
$tax_summary = (!$vat_included && VikBooking::showTaxOnSummaryOnly());

$geo_info_complete = true;
$global_geo_params = null;
$rooms_geo_params = array();
$rooms_features = array();
$rooms_galleries = array();
$rooms_units_booked = array();
$rooms_units_pos = new stdClass;
$markers_parties = new stdClass;

// lang vars for JS
JText::script('VBSEARCHROOMNUM');
JText::script('VBSEARCHRESADULT');
JText::script('VBSEARCHRESADULTS');
JText::script('VBSEARCHRESCHILD');
JText::script('VBSEARCHRESCHILDREN');
JText::script('VBSELECTR');
JText::script('VBO_CANCEL_SELECTION');

// we build a fake booking array to count the units booked for the various rooms
$booking_data = array(
	'id' 		=> 0,
	'checkin' 	=> $this->checkin,
	'checkout' 	=> $this->checkout,
);

foreach ($this->res as $party_num => $party_rooms) {
	foreach ($party_rooms as $party_room) {
		foreach ($party_room as $room_rplan) {
			if (empty($room_rplan['idroom']) || empty($room_rplan['params'])) {
				// unable to proceed
				$geo_info_complete = false;
				break 3;
			}
			if (isset($rooms_geo_params[$room_rplan['idroom']])) {
				// room already parsed
				continue;
			}
			$rparams = json_decode($room_rplan['params']);
			$geo_params = $geo->getRoomGeoParams($rparams);
			if (is_object($geo_params) && isset($geo_params->enabled) && $geo_params->enabled) {
				if (!isset($global_geo_params)) {
					// we use the global geo params of the first room returned
					$global_geo_params = $geo_params;
				}
				// push room geo params
				$rooms_geo_params[$room_rplan['idroom']] = $geo_params;
				// check if distinctive features are available
				$rooms_features[$room_rplan['idroom']] = array();
				$room_features = VikBooking::getRoomParam('features', $room_rplan['params']);
				$room_features = !is_array($room_features) ? array() : $room_features;
				foreach ($room_features as $rindex => $rfeatures) {
					if (!is_array($rfeatures) || !count($rfeatures)) {
						continue;
					}
					foreach ($rfeatures as $featname => $featval) {
						if (empty($featval)) {
							continue;
						}
						// use the first distinctive feature
						$tn_featname = JText::translate($featname);
						if ($tn_featname == $featname) {
							// no translation was applied
							if (VBOPlatformDetection::isWordPress()) {
								// try to apply a translation through Gettext even if we have to pass a variable
								$tn_featname = __($featname);
							} else {
								// convert the string to a hypothetical INI constant
								$ini_constant = str_replace(' ', '_', strtoupper($featname));
								$tn_featname = JText::translate($ini_constant);
								$tn_featname = $tn_featname == $ini_constant ? $featname : $tn_featname;
							}
						}
						$rooms_features[$room_rplan['idroom']][$rindex] = $tn_featname . ' ' . $featval;
						break;
					}
				}
				// build rooms gallery
				$rooms_galleries[$room_rplan['idroom']] = array();
				if (!empty($room_rplan['moreimgs'])) {
					$moreimages = explode(';;', $room_rplan['moreimgs']);
					foreach ($moreimages as $mimg) {
						if (empty($mimg)) {
							continue;
						}
						// push thumb URL
						array_push($rooms_galleries[$room_rplan['idroom']], VBO_SITE_URI . 'resources/uploads/thumb_' . $mimg);
					}
				} elseif (!empty($room_rplan['img'])) {
					// push main image URL
					array_push($rooms_galleries[$room_rplan['idroom']], VBO_SITE_URI . 'resources/uploads/' . $room_rplan['img']);
				}
				// count room units booked
				$rooms_units_booked[$room_rplan['idroom']] = VikBooking::getRoomUnitNumsUnavailable($booking_data, $room_rplan['idroom']);
			} else {
				// unable to proceed
				$geo_info_complete = false;
				break 3;
			}
			// we parse just the first rate plan
			break;
		}
	}
}

// compose markers positions
foreach ($rooms_geo_params as $idroom => $geo_params) {
	if (isset($geo_params->units_pos) && is_object($geo_params->units_pos) && count(get_object_vars($geo_params->units_pos))) {
		$rooms_units_pos->{$idroom} = $geo_params->units_pos;
	}
}

if ($geo->isSupported() && $geo_info_complete === true && count($rooms_geo_params) && count(get_object_vars($rooms_units_pos))) {
	// load assets
	$geo->loadAssets();
	
	// check if ground overlay is implemented by the first room that returns the global geo params
	$current_goverlay = null;
	if ((int)$global_geo_params->goverlay > 0 && !empty($global_geo_params->overlay_img) && !empty($global_geo_params->overlay_south)) {
		// ground overlay is available
		$current_goverlay = new stdClass;
		$current_goverlay->url = $global_geo_params->overlay_img;
		$current_goverlay->south = (float)$global_geo_params->overlay_south;
		$current_goverlay->west = (float)$global_geo_params->overlay_west;
		$current_goverlay->north = (float)$global_geo_params->overlay_north;
		$current_goverlay->east = (float)$global_geo_params->overlay_east;
	}

	// compose markers parties
	foreach ($this->res as $party_num => $party_rooms) {
		$markers_parties->{$party_num} = array();
		foreach ($party_rooms as $party_room) {
			foreach ($party_room as $room_rplan) {
				if (empty($room_rplan['idroom']) || !isset($rooms_geo_params[$room_rplan['idroom']])) {
					continue;
				}
				if (!in_array($room_rplan['idroom'], $markers_parties->{$party_num})) {
					$raw_roomcost = $tax_summary ? $room_rplan['cost'] : VikBooking::sayCostPlusIva($room_rplan['cost'], $room_rplan['idprice']);
					// build room info object
					$room_info = new stdClass;
					$room_info->id = $room_rplan['idroom'];
					$room_info->name = $room_rplan['name'];
					$room_info->cost = VikBooking::numberFormat($raw_roomcost);
					$room_info->units = isset($room_rplan['unitsavail']) ? (int)$room_rplan['unitsavail'] : (int)$room_rplan['units'];
					$room_info->tot_markers = 1;
					if ($rooms_geo_params[$room_rplan['idroom']]->markers_multi > 0 && $room_rplan['units'] > 1) {
						$room_info->tot_markers = (int)$room_rplan['units'];
					}
					// check if a promo was applied
					if (isset($room_rplan['promotion']) && isset($room_rplan['promotion']['discount'])) {
						if ($room_rplan['promotion']['discount']['pcent']) {
							$prev_amount = $raw_roomcost * 100 / (100 - $room_rplan['promotion']['discount']['amount']);
						} else {
							$prev_amount = $raw_roomcost + $room_rplan['promotion']['discount']['amount'];
						}
						if ($prev_amount > 0) {
							$room_info->prev_cost = VikBooking::numberFormat($prev_amount);
						}
					}
					// push room info
					array_push($markers_parties->{$party_num}, $room_info);
				}
				// we parse just the first rate plan
				break;
			}
		}
	}
	?>
<div class="vbo-searchresults-geo-wrapper">
	<div class="vbo-geo-wrapper">
	<?php
	if ($this->roomsnum > 1) {
		?>
		<div class="vbo-geomap-uicontrol">
			<div class="vbo-geomap-uicontrol-inner">
				<span class="vbo-geomap-uicontrol-partynum"><?php echo JText::translate('VBSEARCHROOMNUM'); ?> 1</span>
				<span class="vbo-geomap-uicontrol-partyguests">
					<?php VikBookingIcons::e('users', 'vbo-pref-color-text'); ?> 
					<?php echo $this->arrpeople[1]['adults']; ?> 
					<?php echo ($this->arrpeople[1]['adults'] == 1 ? JText::translate('VBSEARCHRESADULT') : JText::translate('VBSEARCHRESADULTS')); ?> 
					<?php echo ($this->showchildren && $this->arrpeople[1]['children'] > 0 ? ", ".$this->arrpeople[1]['children']." ".($this->arrpeople[1]['children'] == 1 ? JText::translate('VBSEARCHRESCHILD') : JText::translate('VBSEARCHRESCHILDREN')) : ""); ?>
				</span>
			</div>
		</div>
		<?php
	}
	?>
		<div class="vbo-results-geo-map-container">
		<?php
		if ($this->roomsnum > 1) {
			?>
			<div class="vbo-geomap-minicart" style="display: none;">
				<div class="vbo-geomap-minicart-inner">
					<div class="vbo-geomap-minicart-head"><?php VikBookingIcons::e('shopping-cart'); ?></div>
					<div class="vbo-geomap-minicart-body"></div>
				</div>
			</div>
			<?php
		}
		?>
			<div id="vbo-results-geo-map" style="width: 100%; height: <?php echo $geo->getRoomGeoParams($global_geo_params, 'height', 300); ?>px;"></div>
		</div>
	</div>
</div>

<script type="text/javascript">
	/**
	 * Define global scope vars
	 */
	var vbo_geomap = null,
		vbo_geo_currency = '<?php echo $currencysymb; ?>';
		vbo_geo_showchildren = <?php echo (int)$this->showchildren; ?>,
		vbo_geo_rooms_galleries = <?php echo json_encode($rooms_galleries); ?>,
		vbo_geo_guestparties = <?php echo json_encode($this->arrpeople); ?>,
		vbo_geomarker_parties = <?php echo json_encode($markers_parties); ?>,
		vbo_rooms_units_booked = <?php echo json_encode($rooms_units_booked); ?>,
		vbo_rooms_units_selected = {},
		vbo_geomarker_units = {},
		vbo_geomarker_units_pos = <?php echo json_encode($rooms_units_pos); ?>,
		vbo_info_markers = {},
		vbo_info_markers_helper = <?php echo json_encode($rooms_features); ?>,
		vbo_ground_overlay = null,
		vbo_dbground_overlay = <?php echo is_object($current_goverlay) ? json_encode($current_goverlay) : 'null'; ?>,
		vbo_geomap_booking_step = 1
		vbo_geomap_total_steps = <?php echo $this->roomsnum; ?>;

	// turn off the use of the modal/dialog window in case of multi rooms booking
	var vbo_multirooms_dialog = false;

	/**
	 * Returns the room information.
	 */
	function vboGetGeoRoomPartyInformation(idroom) {
		if (!vbo_geomarker_parties.hasOwnProperty(vbo_geomap_booking_step)) {
			return null;
		}
		for (var room in vbo_geomarker_parties[vbo_geomap_booking_step]) {
			if (!vbo_geomarker_parties[vbo_geomap_booking_step].hasOwnProperty(room)) {
				continue;
			}
			if (vbo_geomarker_parties[vbo_geomap_booking_step][room]['id'] == idroom) {
				return vbo_geomarker_parties[vbo_geomap_booking_step][room];
			}
		}
		return null;
	}

	/**
	 * Generates the HTML content for the units marker infowindow.
	 */
	function vboGeoInfoMarkerContent(idroom, index, marker_title, rcost, rprevcost) {
		var rdomselector = '#vbcontainer' + vbo_geomap_booking_step + '_' + idroom;
		var infowin_cont = '';
		infowin_cont += '<div class="vbo-geomarker-infowin-wrap">';
		// check if gallery is available
		if (vbo_geo_rooms_galleries.hasOwnProperty(idroom) && vbo_geo_rooms_galleries[idroom].length) {
			infowin_cont += '	<div class="vbo-geomarker-infowin-room-gallery" data-idroom="' + idroom + '"></div>';
		}
		infowin_cont += '	<div class="vbo-geomarker-room-title">';
		if (jQuery(rdomselector).find('.vbmodalframe').length) {
			infowin_cont += '	<span class="vbo-geomarker-rstay-info" onclick="jQuery(\'' + rdomselector + '\').find(\'.vbmodalframe\').first().trigger(\'click\');"><?php VikBookingIcons::e('info-circle'); ?></span>';
		}
		infowin_cont += '		<span class="vbo-geomarker-room-title-cont">' + marker_title + '</span>';
		infowin_cont += '	</div>';
		infowin_cont += '	<div class="vbo-geomarker-priceinfo">';
		if (rprevcost != null) {
			infowin_cont += '	<span class="vbo-geomarker-priceinfo-cost-beforedisc"><span class="vbo_currency">' + vbo_geo_currency + '</span> <span class="vbo_price">' + rprevcost + '</span></span>';
		}
		infowin_cont += '		<span class="vbo-geomarker-priceinfo-cost"><span class="vbo_currency">' + vbo_geo_currency + '</span> <span class="vbo_price">' + rcost + '</span></span>';
		infowin_cont += '	</div>';
		infowin_cont += '	<div class="vbo-geomarker-bookroom">';
		infowin_cont += '		<button type="button" class="btn vbo-pref-color-btn" onclick="vboInteractiveGeoMapSelectRoom(' + idroom + ', ' + index + ');">' + Joomla.JText._('VBSELECTR') + '</button>';
		infowin_cont += '	</div>';
		infowin_cont += '</div>';
		
		return infowin_cont;
	}

	/**
	 * Selects one room and index for booking the current party.
	 */
	function vboInteractiveGeoMapSelectRoom(idroom, index) {
		var current_step = vbo_geomap_booking_step;
		var room_info = vboGetGeoRoomPartyInformation(idroom);
		var tot_markers = room_info !== null ? room_info['tot_markers'] : 1;
		// register room index hidden field
		if (jQuery('#vbo-geo-roomindex-' + current_step).length) {
			jQuery('#vbo-geo-roomindex-' + current_step).remove();
		}
		// if room does not support sub-units or has markers for sub-units, index value is 0 so that we can skip it later on in the booking process
		var room_index_val = tot_markers === 1 ? 0 : index;
		jQuery('#vbselectroomform').append('<input type="hidden" id="vbo-geo-roomindex-' + current_step + '" name="roomindex[]" value="' + room_index_val + '" />');
		// trigger the main function to select the room
		var res = vbSelectRoom(current_step, idroom);
		if (res === false) {
			jQuery('#vbo-geo-roomindex-' + current_step).remove();
			return;
		}
		// check if the next step should be displayed
		if (vbo_geomap_total_steps > 1 && vbo_geomap_total_steps > current_step) {
			// register the room index selected
			vbo_rooms_units_selected[current_step] = {
				idroom: idroom,
				index: index
			};
			// populate mini-cart
			if (jQuery('.vbo-geomap-minicart').length) {
				var rcost = room_info !== null ? room_info['cost'] : '';
				var rname = room_info !== null ? room_info['name'] : '';
				var room_title = rname + ' #' + (index + '');
				if (tot_markers === 1) {
					room_title = rname;
				} else if (vbo_info_markers_helper.hasOwnProperty(idroom) && vbo_info_markers_helper[idroom].hasOwnProperty(index)) {
					room_title = rname + ' - ' + vbo_info_markers_helper[idroom][index];
				}
				// get party info
				var vbo_uicontrol_guests = '';
				vbo_uicontrol_guests += '<?php VikBookingIcons::e('users', 'vbo-pref-color-text'); ?> ';
				vbo_uicontrol_guests += vbo_geo_guestparties[current_step]['adults'] + ' ';
				vbo_uicontrol_guests += (vbo_geo_guestparties[current_step]['adults'] == 1 ? Joomla.JText._('VBSEARCHRESADULT') : Joomla.JText._('VBSEARCHRESADULTS')) + ' ';
				vbo_uicontrol_guests += (vbo_geo_showchildren > 0 && vbo_geo_guestparties[current_step]['children'] > 1 ? (', ' + vbo_geo_guestparties[current_step]['children'] + ' ' + (vbo_geo_guestparties[current_step]['children'] == 1 ? Joomla.JText._('VBSEARCHRESCHILD') : Joomla.JText._('VBSEARCHRESCHILDREN'))) : '');
				var minicart_row = '';
				minicart_row += '<div class="vbo-geomap-minicart-row" data-minicartrow="' + current_step + '">';
				minicart_row += '	<div class="vbo-geomap-minicart-party">';
				minicart_row += '		<span class="vbo-geomap-minicart-party-num">' + Joomla.JText._('VBSEARCHROOMNUM') + ' ' + current_step + '</span>';
				minicart_row += '		<span class="vbo-geomap-minicart-party-guests">' + vbo_uicontrol_guests + '</span>';
				minicart_row += '	</div>';
				minicart_row += '	<div class="vbo-geomap-minicart-room">';
				minicart_row += '		<span class="vbo-geomap-minicart-room-name">' + room_title + '</span>';
				minicart_row += '		<span class="vbo-geomap-minicart-room-price"><span class="vbo_currency">' + vbo_geo_currency + '</span> <span class="vbo_price">' + rcost + '</span></span>';
				minicart_row += '		<span class="vbo-geomap-minicart-room-trash" onclick="vboInteractiveGeoMapDeselectRoom(' + current_step + ', ' + idroom + ');"><?php VikBookingIcons::e('trash'); ?></span>';
				minicart_row += '	</div>';
				minicart_row += '</div>';
				jQuery('.vbo-geomap-minicart-body').append(minicart_row).removeClass('vbo-geomap-minicart-body-hid').addClass('vbo-geomap-minicart-body-enter');
				jQuery('.vbo-geomap-minicart').show();
				// register closing event for the minicart body
				setTimeout(function() {
					jQuery('.vbo-geomap-minicart-body').removeClass('vbo-geomap-minicart-body-enter').addClass('vbo-geomap-minicart-body-hid');
				}, 1500);
			}
			// increase step for next selection
			vbo_geomap_booking_step++;
			// reload map for the next booking party
			vboInitInteractiveGeoMap();
			return;
		}
		if (res === true) {
			// form is being submitted
			return;
		}
		if (vbo_geomap_booking_step == vbo_geomap_total_steps) {
			// we can submit the form
			document.getElementById('vbselectroomform').submit();
			return true;
		}
	}

	/**
	 * Deselects the room from the given party step, as well as for any step after.
	 */
	function vboInteractiveGeoMapDeselectRoom(step, idroom) {
		if (confirm(Joomla.JText._('VBO_CANCEL_SELECTION'))) {
			if (step > vbo_geomap_total_steps) {
				console.error('invalid step given', step, vbo_geomap_total_steps);
				return false;
			}
			// loop through all room parties starting from the last one until reaching the selected one
			for (var now_step = vbo_geomap_total_steps; now_step >= step; now_step--) {
				// remove cart row
				if (jQuery('.vbo-geomap-minicart-row[data-minicartrow="' + now_step + '"]').length) {
					jQuery('.vbo-geomap-minicart-row[data-minicartrow="' + now_step + '"]').remove();
				}
				// hide cart if no more rows
				if (!jQuery('.vbo-geomap-minicart-row').length) {
					jQuery('.vbo-geomap-minicart').hide();
				}
				// remove hidden field for room index
				if (jQuery('#vbo-geo-roomindex-' + now_step).length) {
					jQuery('#vbo-geo-roomindex-' + now_step).remove();
				}
				// delete the room index previously selected
				if (vbo_rooms_units_selected.hasOwnProperty(now_step)) {
					delete vbo_rooms_units_selected[now_step];
				}
				// deselect room from classic template
				// remove selected class
				if (jQuery('#vbcontainer' + now_step + '_' + idroom).length) {
					jQuery('#vbcontainer' + now_step + '_' + idroom).removeClass('room_selected');
				}
				// change selector label
				if (jQuery('#vbselector' + now_step + '_' + idroom).length) {
					jQuery('#vbselector' + now_step + '_' + idroom).text(Joomla.JText._('VBSELECTR'));
				}
				// empty hidden input field
				if (jQuery('#roomopt' + now_step).length) {
					jQuery('#roomopt' + now_step).val('');
				}
				// update global r_counter object from classic template
				if (typeof r_counter !== 'undefined' && r_counter.hasOwnProperty(idroom)) {
					// decrease units used for this room
					if (r_counter[idroom].hasOwnProperty('used') && r_counter[idroom]['used'] > 0) {
						r_counter[idroom]['used']--;
					}
				}
			}
			// update global booking step
			vbo_geomap_booking_step = step;
			// reload map for the current booking party
			vboInitInteractiveGeoMap();
		}
	}

	/**
	 * Given the markers of the rooms for the current booking party, calculates and sets the map bounds
	 */
	function vboInteractiveGeoMapCenterBounds() {
		if (vbo_geomap === null || !vbo_geomarker_parties.hasOwnProperty(vbo_geomap_booking_step)) {
			console.error('map is null or booking step not found');
			return false;
		}
		// set map center and zoom automatically
		var latlngbounds = new google.maps.LatLngBounds();
		
		// get the position of all markers for the current room party
		var party_rooms = vbo_geomarker_parties[vbo_geomap_booking_step];
		var tot_coords = 0;
		for (var room in party_rooms) {
			if (!party_rooms.hasOwnProperty(room)) {
				continue;
			}
			var idroom = party_rooms[room]['id'];
			if (!vbo_geomarker_units_pos.hasOwnProperty(idroom)) {
				continue;
			}
			for (var m in vbo_geomarker_units_pos[idroom]) {
				if (!vbo_geomarker_units_pos[idroom].hasOwnProperty(m)) {
					continue;
				}
				latlngbounds.extend({
					lat: parseFloat(vbo_geomarker_units_pos[idroom][m]['lat']),
					lng: parseFloat(vbo_geomarker_units_pos[idroom][m]['lng'])
				});
				tot_coords++;
			}
		}

		// apply calculated center and bounds
		if (tot_coords > 0) {
			vbo_geomap.setCenter(latlngbounds.getCenter());
			vbo_geomap.fitBounds(latlngbounds);
		}
	}

	/**
	 * Given the the current booking party, adds the markers to the map
	 */
	function vboInteractiveGeoMapSetPartyMarkers() {
		if (vbo_geomap === null || !vbo_geomarker_parties.hasOwnProperty(vbo_geomap_booking_step)) {
			console.error('map is null or booking step not found');
			return false;
		}

		// update UI control for this room party
		if (jQuery('.vbo-geomap-uicontrol').length) {
			jQuery('.vbo-geomap-uicontrol-partynum').text(Joomla.JText._('VBSEARCHROOMNUM') + ' ' + vbo_geomap_booking_step);
			var vbo_uicontrol_guests = '';
				vbo_uicontrol_guests += '<?php VikBookingIcons::e('users', 'vbo-pref-color-text'); ?> ';
				vbo_uicontrol_guests += vbo_geo_guestparties[vbo_geomap_booking_step]['adults'] + ' ';
				vbo_uicontrol_guests += (vbo_geo_guestparties[vbo_geomap_booking_step]['adults'] == 1 ? Joomla.JText._('VBSEARCHRESADULT') : Joomla.JText._('VBSEARCHRESADULTS')) + ' ';
				vbo_uicontrol_guests += (vbo_geo_showchildren > 0 && vbo_geo_guestparties[vbo_geomap_booking_step]['children'] > 1 ? (', ' + vbo_geo_guestparties[vbo_geomap_booking_step]['children'] + ' ' + (vbo_geo_guestparties[vbo_geomap_booking_step]['children'] == 1 ? Joomla.JText._('VBSEARCHRESCHILD') : Joomla.JText._('VBSEARCHRESCHILDREN'))) : '');
			jQuery('.vbo-geomap-uicontrol-partyguests').html(vbo_uicontrol_guests);
		}
		
		// unset any previously defined marker, if any
		for (var i in vbo_geomarker_units) {
			if (vbo_geomarker_units.hasOwnProperty(i)) {
				vbo_geomarker_units[i].setMap(null);
			}
		}
		vbo_geomarker_units = {};

		// iterate through rooms of current party
		var party_rooms = vbo_geomarker_parties[vbo_geomap_booking_step];
		for (var room in party_rooms) {
			if (!party_rooms.hasOwnProperty(room)) {
				continue;
			}
			var idroom = party_rooms[room]['id'];
			var rname = party_rooms[room]['name'];
			var runits = party_rooms[room]['units'];
			var rcost = party_rooms[room]['cost'];
			var rprevcost = party_rooms[room].hasOwnProperty('prev_cost') ? party_rooms[room]['prev_cost'] : null;
			var tot_markers = party_rooms[room]['tot_markers'];
			if (!vbo_geomarker_units_pos.hasOwnProperty(idroom)) {
				continue;
			}
			// iterate through all markers for this room
			for (var m = 1; m <= tot_markers; m++) {
				if (!vbo_geomarker_units_pos[idroom].hasOwnProperty(m)) {
					continue;
				}
				// make sure this room index is available on these dates
				if (vbo_rooms_units_booked.hasOwnProperty(idroom)) {
					var room_index_booked = false;
					for (var res_id in vbo_rooms_units_booked[idroom]) {
						if (!vbo_rooms_units_booked[idroom].hasOwnProperty(res_id)) {
							continue;
						}
						if (vbo_rooms_units_booked[idroom][res_id] == m) {
							room_index_booked = true;
							break;
						}
					}
					if (room_index_booked === true) {
						// this marker (room unit) index is booked
						continue;
					}
				}
				// make sure this room is still selectable
				var selected_units = 0;
				for (var step in vbo_rooms_units_selected) {
					if (!vbo_rooms_units_selected.hasOwnProperty(step)) {
						continue;
					}
					if (vbo_rooms_units_selected[step]['idroom'] == idroom) {
						// increase units selected for this room
						selected_units++;
						if (tot_markers > 1 && vbo_rooms_units_selected[step]['index'] == m) {
							// this exact index was selected, and the room has got multiple markers, so skip it
							selected_units = runits;
							break;
						}
					}
				}
				if (selected_units >= runits) {
					// this room has no more units available or this multi-marker was selected already, so skip the marker
					continue;
				}
				// prepare marker object for the current room unit
				var marker_title = rname + ' #' + (m + '');
				if (tot_markers === 1) {
					marker_title = rname;
				} else if (vbo_info_markers_helper.hasOwnProperty(idroom) && vbo_info_markers_helper[idroom].hasOwnProperty(m)) {
					marker_title = rname + ' - ' + vbo_info_markers_helper[idroom][m];
				}
				var marker_options = {
					draggable: false,
					map: vbo_geomap,
					position: {
						lat: parseFloat(vbo_geomarker_units_pos[idroom][m]['lat']),
						lng: parseFloat(vbo_geomarker_units_pos[idroom][m]['lng'])
					},
					title: marker_title
				};
				// check if we know a custom icon for this marker
				if (vbo_geomarker_units_pos[idroom][m].hasOwnProperty('icon')) {
					marker_options['icon'] = vbo_geomarker_units_pos[idroom][m]['icon'];
				}
				// set custom properties
				marker_options['room_id'] = idroom;
				marker_options['room_name'] = rname;
				marker_options['room_units'] = runits;
				marker_options['room_cost'] = rcost;
				marker_options['room_prev_cost'] = rprevcost;
				marker_options['room_index'] = m;
				marker_options['room_gallery'] = (vbo_geo_rooms_galleries.hasOwnProperty(idroom) && vbo_geo_rooms_galleries[idroom].length ? 1 : 0);
				// create marker infowindow
				var vbo_info_marker_cont = vboGeoInfoMarkerContent(idroom, m, marker_title, rcost, rprevcost);
				var vbo_info_marker = new google.maps.InfoWindow({
					content: vbo_info_marker_cont,
				});
				// add unit marker to map
				var vbo_geomarker_runit = new google.maps.Marker(marker_options);
				// add listener to marker's click event
				vbo_geomarker_runit.addListener('click', function() {
					// close any other open infowindow first
					for (var infom in vbo_info_markers) {
						if (!vbo_info_markers.hasOwnProperty(infom)) {
							continue;
						}
						vbo_info_markers[infom].close();
					}
					// display its infowindow only
					if (this['room_id'] && this['room_index']) {
						var identifier = this['room_id'] + '_' + this['room_index'];
						if (vbo_info_markers.hasOwnProperty(identifier)) {
							vbo_info_markers[identifier].open(vbo_geomap, this);
							if (this['room_gallery'] > 0) {
								var gallery_params = {
									images: vbo_geo_rooms_galleries[this['room_id']],
									navButPrevContent: '<?php VikBookingIcons::e('chevron-left'); ?>',
									navButNextContent: '<?php VikBookingIcons::e('chevron-right'); ?>',
									containerHeight: '195px',
								};
								var gallery_selector = '.vbo-geomarker-infowin-room-gallery[data-idroom="' + this['room_id'] + '"]';
								if (jQuery(gallery_selector).length) {
									// render gallery immediately
									vboGeoMapRenderInfowinGallery(gallery_selector, gallery_params);
								} else {
									// infowindows may take some seconds to be displayed, so we delay the rendering
									window.setTimeout(vboGeoMapRenderInfowinGallery.bind(null, gallery_selector, gallery_params), 500);
								}
							}
						} else {
							console.error('info marker identifier not found', this, identifier);
						}
					} else {
						console.error('info marker properties not found', this);
					}
				});
				// register marker to pool
				var identifier = idroom + '_' + m;
				vbo_geomarker_units[identifier] = vbo_geomarker_runit;
				// register info window
				vbo_info_markers[identifier] = vbo_info_marker;
			}
		}
	}

	/**
	 * Renders the vikDotsSlider gallery for an infowindow.
	 * We wrap the use of this plugin inside a function so that we can delay the execution.
	 */
	function vboGeoMapRenderInfowinGallery(selector, params) {
		jQuery(selector).vikDotsSlider(params);
	}

	/**
	 * Initializes the interactive geo map through Google Maps for the current booking step.
	 */
	function vboInitInteractiveGeoMap() {
		// default map options
		var def_map_options = {
			center: new google.maps.LatLng(<?php echo $global_geo_params->latitude; ?>, <?php echo $global_geo_params->longitude; ?>),
			zoom: <?php echo (int)$global_geo_params->zoom; ?>,
			mapTypeId: '<?php echo $global_geo_params->mtype; ?>',
			mapTypeControl: false
		};
		// initialize Map
		vbo_geomap = new google.maps.Map(document.getElementById('vbo-results-geo-map'), def_map_options);
		// add listeners to map
		vbo_geomap.addListener('click', function(e) {
			// close all infowindows
			for (var infom in vbo_info_markers) {
				if (!vbo_info_markers.hasOwnProperty(infom)) {
					continue;
				}
				vbo_info_markers[infom].close();
			}
		});
		// add markers and set UI control for the current rooms party
		vboInteractiveGeoMapSetPartyMarkers();
		// center bounds
		vboInteractiveGeoMapCenterBounds();
		// populate ground overlay image, if set
		if (vbo_dbground_overlay !== null) {
			// compose LatLngBounds object
			var overlay_bounds = new google.maps.LatLngBounds(
				new google.maps.LatLng(parseFloat(vbo_dbground_overlay.south), parseFloat(vbo_dbground_overlay.west)),
				new google.maps.LatLng(parseFloat(vbo_dbground_overlay.north), parseFloat(vbo_dbground_overlay.east))
			);
			// update ground overlay object
			vbo_ground_overlay = new google.maps.GroundOverlay(vbo_dbground_overlay.url, overlay_bounds);
			// set the overlay to the map
			vbo_ground_overlay.setMap(vbo_geomap);
		}
	}

	jQuery(document).ready(function() {

		// init interactive geo map with markers
		vboInitInteractiveGeoMap();

		// register hovering events for minicart body
		if (jQuery('.vbo-geomap-minicart').length) {
			jQuery('.vbo-geomap-minicart').hover(function() {
				jQuery('.vbo-geomap-minicart-body').removeClass('vbo-geomap-minicart-body-hid').addClass('vbo-geomap-minicart-body-enter');
			}, function() {
				jQuery('.vbo-geomap-minicart-body').removeClass('vbo-geomap-minicart-body-enter').addClass('vbo-geomap-minicart-body-hid');
			});
		}

		// preload image gallery
		jQuery(vbo_geo_rooms_galleries).vikDotsSlider('preloadImages');
	});

</script>
	<?php
}
