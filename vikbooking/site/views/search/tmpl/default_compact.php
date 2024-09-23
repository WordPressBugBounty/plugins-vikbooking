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

$document = JFactory::getDocument();
$document->addStyleSheet(VBO_SITE_URI.'resources/vikfxgallery.css');
JHtml::fetch('script', VBO_SITE_URI.'resources/vikfxgallery.js');

$vat_included = VikBooking::ivaInclusa();
$tax_summary = !$vat_included && VikBooking::showTaxOnSummaryOnly() ? true : false;

$currencysymb = VikBooking::getCurrencySymb();

// compose array of rooms available for the requested parties
$rooms_res = [];
$rooms_units = [];
$rooms_parties = [];
$gallery_data = [];
foreach ($this->res as $indroom => $rooms) {
	foreach ($rooms as $roomrates) {
		$idroom = $roomrates[0]['idroom'];
		if (!isset($rooms_res[$idroom])) {
			$rooms_res[$idroom] = [];
			$rooms_units[$idroom] = (int)$roomrates[0]['unitsavail'];
		}
		$rooms_res[$idroom][$indroom] = $roomrates;
	}
}

?>

<div class="vbo-searchresults-compact-wrap">
<?php
foreach ($rooms_res as $idroom => $room_res) {
	$rooms_parties[$idroom] = array_keys($room_res);
	foreach ($room_res as $indroom => $roomrates) {
		$roominfo = $roomrates[0];
		// just one loop
		break;
	}
	$has_promotion = isset($roominfo['promotion']);
	$carats = VikBooking::getRoomCaratOriz($roominfo['idcarat'], $this->vbo_tn);

	// prepare CMS contents depending on platform
	$roominfo = VBORoomHelper::getInstance()->prepareCMSContents($roominfo, ['smalldesc']);

	$saylastavail = false;
	$showlastavail = (int)VikBooking::getRoomParam('lastavail', $roominfo['params']);
	if (!empty($showlastavail) && $showlastavail > 0) {
		if ($roominfo['unitsavail'] <= $showlastavail) {
			$saylastavail = true;
		}
	}

	// get the max units this room can have in the drop down
	$maxunits = 1;
	if (count($room_res) > 1) {
		// room is available for multiple room parties. Count how many parties have the same adults/children
		$parties = $rooms_parties[$idroom];
		$party_count = [];
		foreach ($parties as $party) {
			$party_key = "adults{$this->arrpeople[$party]['adults']}children{$this->arrpeople[$party]['children']}";
			if (!isset($party_count[$party_key])) {
				$party_count[$party_key] = 0;
			}
			$party_count[$party_key]++;
		}
		$maxunits = max($party_count);
		$maxunits = $maxunits > $roominfo['unitsavail'] ? $roominfo['unitsavail'] : $maxunits;
	}

	// gallery
	if (!empty($roominfo['moreimgs'])) {
		$gallery_data[$idroom] = [];
		$moreimages = explode(';;', $roominfo['moreimgs']);
		$imgcaptions = json_decode($roominfo['imgcaptions'], true);
		$usecaptions = is_array($imgcaptions);
		foreach ($moreimages as $iind => $mimg) {
			if (empty($mimg)) {
				continue;
			}
			$img_alt = $usecaptions && !empty($imgcaptions[$iind]) ? $imgcaptions[$iind] : substr($mimg, 0, strpos($mimg, '.'));
			array_push($gallery_data[$idroom], [
				'big' => VBO_SITE_URI . 'resources/uploads/big_' . $mimg,
				'thumb' => VBO_SITE_URI . 'resources/uploads/thumb_' . $mimg,
				'alt' => $img_alt,
				'caption' => $usecaptions && !empty($imgcaptions[$iind]) ? $imgcaptions[$iind] : "",
			]);
		}
	}

	?>
	<div class="vbo-room-result-wrap<?php echo $has_promotion ? ' vbo-promotion-price' : ''; ?>">
		<div class="vbo-room-result-inner">
			<div class="vbo-room-result-head">
				<div class="vbo-room-result-head-img">
					<span><?php echo JText::translate('VBSEARCHRESROOM'); ?></span>
				</div>
				<div class="vbo-room-result-head-details">
					<span><?php echo JText::translate('VBSEARCHRESDETAILS'); ?></span>
				</div>
				<div class="vbo-room-result-head-party">
					<span><?php echo JText::translate('VBOINVTOTGUESTS'); ?></span>
				</div>
				<div class="vbo-room-result-head-price">
					<span><?php echo JText::translate('VBPRICE'); ?></span>
				</div>
				<div class="vbo-room-result-head-select">
					<span><?php echo JText::translate('VBORESERVE'); ?></span>
				</div>
				<div class="vbo-room-result-head-bookstatus">
					<span></span>
				</div>
			</div>
			<div class="vbo-room-result-body">
				<div class="vbo-room-result-body-img">
					<div class="vikfx-gallery-container vikfx-compact-gallery-container">
						<div class="vikfx-gallery-fade-container">
							<img class="vblistimg" alt="<?php echo htmlspecialchars($roominfo['name']); ?>" id="vbroomimg<?php echo $idroom; ?>" src="<?php echo VBO_SITE_URI; ?>resources/uploads/<?php echo $roominfo['img']; ?>"/>
						<?php
						if (isset($gallery_data[$idroom]) && count($gallery_data[$idroom])) {
							?>
							<div class="vikfx-gallery-navigation-controls">
								<div class="vikfx-gallery-navigation-controls-prevnext">
									<a href="javascript: void(0);" class="vikfx-gallery-previous-image" id="vikfx-gallery-previous-image<?php echo $idroom; ?>"><?php VikBookingIcons::e('chevron-left'); ?></a>
									<a href="javascript: void(0);" class="vikfx-gallery-next-image" id="vikfx-gallery-next-image<?php echo $idroom; ?>"><?php VikBookingIcons::e('chevron-right'); ?></a>
								</div>
							</div>
							<?php
						}
						?>
						</div>
					<?php
					if (isset($gallery_data[$idroom]) && count($gallery_data[$idroom])) {
						?>
						<div class="vikfx-gallery" id="vikfx-gallery<?php echo $idroom; ?>">
						<?php
						foreach ($gallery_data[$idroom] as $mimg) {
							?>
							<a href="<?php echo $mimg['big']; ?>">
								<img src="<?php echo $mimg['thumb']; ?>" alt="<?php echo $this->escape($mimg['alt']); ?>" title="<?php echo $this->escape($mimg['caption']); ?>"/>
							</a>
							<?php
						}
						?>
						</div>
						<?php
					}
					?>
					</div>
				</div>
				<div class="vbo-room-result-body-details">
					<div class="vbo-room-result-body-rname">
						<h4><?php echo $roominfo['name']; ?></h4>
					<?php
					if ($saylastavail === true) {
						?>
						<span class="vbo-room-result-body-lastavail"><?php echo JText::sprintf('VBLASTUNITSAVAIL', $roominfo['unitsavail']); ?></span>
						<?php
					}
					?>
					</div>
					<div class="vbrowcdescr"><?php echo $roominfo['smalldesc']; ?></div>
					<?php
				if (!empty($carats)) {
					?>
					<div class="roomlist_carats">
						<?php echo $carats; ?>
					</div>
					<?php
				}
				if ($has_promotion && !empty($roominfo['promotion']['promotxt'])) {
					?>
					<div class="vbo-promotion-block">
						<div class="vbo-promotion-icon"><?php VikBookingIcons::e('percentage'); ?></div>
						<div class="vbo-promotion-description">
							<?php echo $roominfo['promotion']['promotxt']; ?>
						</div>
					</div>
					<?php
				}
				?>
				</div>
				<div class="vbo-room-result-body-bookingsolutions">
				<?php
				$room_parties_displayed = [];
				foreach ($room_res as $indroom => $roomrates) {
					$party_key = "adults{$this->arrpeople[$indroom]['adults']}children{$this->arrpeople[$indroom]['children']}";
					if (in_array($party_key, $room_parties_displayed)) {
						// skip this room because the drop down will already display the possibility of booking multiple units of this room
						continue;
					}
					// push the party displayed
					array_push($room_parties_displayed, $party_key);
					?>
					<div class="vbo-room-result-body-bookingsolution">
						<div class="vbo-room-result-body-price-party">
						<?php
						$totguests = $this->arrpeople[$indroom]['adults'] + $this->arrpeople[$indroom]['children'];
						if ($this->arrpeople[$indroom]['adults'] > 0) {
							?>
							<div class="vbo-room-result-body-price-party-adults">
							<?php
							if ($totguests > 4) {
								?>
								<span><?php VikBookingIcons::e('male'); ?> &times;<?php echo $this->arrpeople[$indroom]['adults']; ?></span>
								<?php
							} else {
								for ($i = 0; $i < $this->arrpeople[$indroom]['adults']; $i++) {
									VikBookingIcons::e('male');
								}
							}
							?>
							</div>
							<?php
						}
						if ($this->arrpeople[$indroom]['children'] > 0) {
							?>
							<div class="vbo-room-result-body-price-party-children">
							<?php
							if ($totguests > 4) {
								?>
								<span><?php VikBookingIcons::e('child'); ?> &times;<?php echo $this->arrpeople[$indroom]['children']; ?></span>
								<?php
							} else {
								for ($i = 0; $i < $this->arrpeople[$indroom]['children']; $i++) {
									VikBookingIcons::e('child');
								}
							}
							?>
							</div>
							<?php
						}
						$raw_roomcost = $tax_summary ? $roomrates[0]['cost'] : VikBooking::sayCostPlusIva($roomrates[0]['cost'], $roomrates[0]['idprice']);
						?>
						</div>
						<div class="vbo-room-result-body-price-amount">
							<span class="room_cost">
								<span class="vbo_currency"><?php echo $currencysymb; ?></span> 
								<span class="vbo_price"><?php echo VikBooking::numberFormat($raw_roomcost); ?></span>
							</span>
					<?php
					if (isset($roominfo['promotion']) && isset($roominfo['promotion']['discount'])) {
						if ($roominfo['promotion']['discount']['pcent']) {
							/**
							 * Do not make an upper-cent operation, but rather calculate the original price proportionally:
							 * final price : (100 - discount amount) = x : 100
							 * 
							 * @since 	1.13.5
							 */
							$prev_amount = $raw_roomcost * 100 / (100 - $roominfo['promotion']['discount']['amount']);
						} else {
							$prev_amount = $raw_roomcost + $roominfo['promotion']['discount']['amount'];
						}
						if ($prev_amount > 0) {
							?>
							<div class="vbo-room-result-price-before-discount">
								<span class="room_cost">
									<span class="vbo_currency"><?php echo $currencysymb; ?></span> 
									<span class="vbo_price"><?php echo VikBooking::numberFormat($prev_amount); ?></span>
								</span>
							</div>
							<?php
							if ($roominfo['promotion']['discount']['pcent']) {
								// hide by default the DIV containing the percent of discount
								?>
							<div class="vbo-room-result-price-before-discount-percent" style="display: none;">
								<span class="room_cost">
									<span><?php echo '-' . (float)$roominfo['promotion']['discount']['amount'] . ' %'; ?></span>
								</span>
							</div>
								<?php
							}
						}
					}
					?>
						</div>
						<div class="vbo-room-result-body-price-selection">
							<select id="vbo-selroom-<?php echo "{$indroom}-{$roomrates[0]['idroom']}"; ?>" class="vbo-selroom-ddown vbo-selroom-room<?php echo $roomrates[0]['idroom']; ?> vbo-selroom-party<?php echo $indroom; ?>" onchange="vboCompactSelRoom(<?php echo $indroom; ?>, <?php echo $roomrates[0]['idroom']; ?>, this.value);">
								<option value="0">0</option>
							<?php
							for ($i = 1; $i <= $maxunits; $i++) { 
								?>
								<option value="<?php echo $i; ?>"><?php echo $i; ?></option>
								<?php
							}
							?>
							</select>
						</div>
						<div class="vbo-room-result-body-bookstatus">
							<span class="vbo-room-result-body-bookmsg" style="display: none;"></span>
							<div class="vbo-room-result-body-bookbtn" style="display: none;">
								<button type="button" class="btn" onclick="vboCompactProceed();"><?php echo JText::translate('VBBOOKNOW'); ?></button>
							</div>
						</div>
					</div>
					<?php
				}
				?>
				</div>
			</div>
		</div>
	</div>
	<?php
}

/**
 * Normalize the room parties object in case some rooms can also be suited for additional room parties.
 * This is to avoid situations where the sum of selected room units do not satisfy all room parties.
 * The configuration setting "Smart Search Type" should be set to "dynamic" to avoid this normalization.
 * 
 * @see 	VikBooking::getSmartSearchType()  should return "dynamic" NOT "automatic".
 * 
 * @since 	1.16.9 (J) - 1.6.9 (WP)
 */
$all_parties = range(1, $this->roomsnum);
foreach ($rooms_parties as $rid => $r_party) {
	$missing_parties = array_diff($all_parties, $r_party);
	$fit_parties     = array_intersect($r_party, $all_parties);
	if (!$missing_parties || !$fit_parties) {
		// this room can satisfy all room parties
		continue;
	}
	// compare equal room parties in terms of guests
	foreach ($missing_parties as $missing_party) {
		foreach ($fit_parties as $fit_party) {
			if ($this->arrpeople[$missing_party] === $this->arrpeople[$fit_party]) {
				// push this missing party because the guests are identical to a fit party
				$rooms_parties[$rid][] = $missing_party;
				break;
			}
		}
	}
}

?>
</div>

<script type="text/javascript">
var vboNeededBooked     = <?php echo $this->roomsnum; ?>;
var vboRoomsUnits       = <?php echo json_encode($rooms_units); ?>;
var vboRoomsParties     = <?php echo json_encode($rooms_parties); ?>;
var vboPartiesRequested = {};
var vboRoomsUnitsPool   = {};
var vboRoomsAssigned    = {};
var vboCompactMsg       = {
	missingRoomsPl: "<?php echo addslashes(JText::translate('VBONROOMSMISSINGPL')); ?>",
	missingRoomsSi: "<?php echo addslashes(JText::translate('VBONROOMSMISSINGSI')); ?>",
};

function vboCompactCountSelected(idroom) {
	var totselected = 0;
	var ddowns = document.getElementsByClassName((idroom ? 'vbo-selroom-room' + idroom : 'vbo-selroom-ddown'));
	for (var i = 0; i < ddowns.length; i++) {
		totselected += parseInt(ddowns.item(i).value);
	}
	return totselected;
}

function vboCompactCountBooked() {
	var totbooked = 0;
	for (var i = 1; i <= vboNeededBooked; i++) {
		var inpslot = document.getElementById('roomopt' + i).value;
		if (inpslot.length && parseInt(inpslot) > 0) {
			totbooked++;
		}
	}
	return totbooked;
}

/**
 * Checks whether a slot is occupied by another room. Returns false or an array with the occupier information.
 */
function vboCompactIsSlotOccupied(indroom, idroom) {
	var current_val = document.getElementById('roomopt' + indroom).value;
	if (current_val && current_val.length && (current_val + '') != (idroom + '')) {
		return [parseInt(indroom), parseInt(current_val)];
	}
	return false;
}

/**
 * Frees up one slot by updating the necessary objects and input field.
 */
function vboCompactMakeSlotFree(indroom, idroom) {
	var current_room = document.getElementById('roomopt' + indroom).value;
	if (!current_room || !current_room.length) {
		return;
	}
	current_room = parseInt(current_room);
	document.getElementById('roomopt' + indroom).value = '';
	if (vboPartiesRequested.hasOwnProperty(indroom)) {
		delete vboPartiesRequested[indroom];
	}
	if (vboRoomsAssigned.hasOwnProperty(current_room) && vboRoomsAssigned[current_room].hasOwnProperty(indroom)) {
		for (var i in vboRoomsAssigned[current_room][indroom]) {
			if (vboRoomsAssigned[current_room][indroom].hasOwnProperty(i) && vboRoomsAssigned[current_room][indroom][i] == indroom) {
				vboRoomsAssigned[current_room][indroom].splice(i, 1);
				break;
			}
		}
	}
	jQuery('.vbo-selroom-room' + current_room).each(function(k, v) {
		var elem = jQuery(v);
		var current_val = parseInt(elem.val());
		if (current_val > 0 && (elem.hasClass('vbo-selroom-party' + indroom) || (Array.isArray(vboRoomsParties[current_room]) && vboRoomsParties[current_room].includes(indroom)))) {
			elem.val((current_val - 1));
			// if some drop downs were previously disabled, turn them back on
			var roomtotsel = vboCompactCountSelected(current_room);
			if (roomtotsel < vboRoomsUnits[current_room]) {
				// other units available for this room-type
				var container = elem.closest('.vbo-room-result-body-bookingsolutions');
				container.find('.vbo-room-result-body-bookingsolution').removeClass('vbo-room-result-soldout');
				container.find('select.vbo-selroom-ddown').prop('disabled', false);
			}
			// break loop
			return false;
		}
	});
}

/**
 * Disables or enables the parties that can be booked.
 */
function vboCompactUpdateParties() {
	// always free up the already chosen elements for the now empty parties
	for (var i = 1; i <= vboNeededBooked; i++) {
		var inpslot = document.getElementById('roomopt' + i).value;
		if (!inpslot.length) {
			// no room for this party, make sure the drop downs are enabled
			jQuery('.vbo-room-result-alreadychosen-' + i).prop('disabled', false).removeClass('vbo-room-result-alreadychosen-' + i);
		}
	}
	//
	for (var indparty in vboPartiesRequested) {
		if (!vboPartiesRequested.hasOwnProperty(indparty) || !vboPartiesRequested[indparty]) {
			continue;
		}
		for (var room in vboRoomsParties) {
			if (!vboRoomsParties.hasOwnProperty(room) || !vboRoomsParties[room].length) {
				continue;
			}
			var check_indparty = parseInt(indparty);
			if (vboRoomsParties[room].includes(check_indparty)) {
				// this room is suitable for this party already chosen
				var elem = jQuery('#vbo-selroom-' + indparty + '-' + room);
				if (!elem.length) {
					continue;
				}
				if (elem.val() < 1) {
					// we can disable this drop down so that no units will be selected
					elem.prop('disabled', true).addClass('vbo-room-result-alreadychosen-' + indparty);
				} else if (!elem.closest('.vbo-room-result-body-bookingsolution').hasClass('vbo-room-result-soldout')) {
					// we can enable this drop down
					elem.prop('disabled', false);
				}
			}
		}
	}
}

function vboCompactSelRoom(indroom, idroom, units) {
	// adjust vars
	units = parseInt(units);

	// update rooms units counter and assigned for visible drop downs
	if (!vboRoomsUnitsPool.hasOwnProperty(idroom)) {
		vboRoomsUnitsPool[idroom] = {};
		vboRoomsAssigned[idroom] = {};
	}
	if (!vboRoomsUnitsPool[idroom].hasOwnProperty(indroom)) {
		vboRoomsUnitsPool[idroom][indroom] = units;
		vboRoomsAssigned[idroom][indroom] = new Array;
	}
	var adding_units = (vboRoomsUnitsPool[idroom][indroom] <= units);
	var removing_units = !adding_units ? (vboRoomsUnitsPool[idroom][indroom] - units) : 0;
	var truly_removing = !adding_units;
	if (truly_removing && removing_units > 0 && units > 0 && (vboCompactCountBooked() - removing_units + units) == vboNeededBooked) {
		truly_removing = false;
	}

	// update units selected for this drop down
	vboRoomsUnitsPool[idroom][indroom] = units;

	if (adding_units) {
		// populate fields when adding units
		var indcounter = 0;
		var units_added = 0;
		var slot_occupied = vboCompactIsSlotOccupied(indroom, idroom);
		if (units === 1 && (vboRoomsParties[idroom].length === 1 || slot_occupied === false)) {
			// occupy exact party requested for one unit when room not suited for other parties or when slot is free
			if (slot_occupied !== false) {
				vboCompactMakeSlotFree(slot_occupied[0], slot_occupied[1]);
			}
			indcounter++;
			units_added++;
			document.getElementById('roomopt' + indroom).value = idroom;
			vboPartiesRequested[indroom] = idroom;
			vboRoomsAssigned[idroom][indroom].push(indroom);
		}

		// selected more than one unit, or one unit for a room that is suitable for multiple parties
		for (var i = indcounter; i < units; i++) {
			// fill all containers valid for this room party until the units requested are reached
			var indparty = vboRoomsParties[idroom][i];
			if (!document.getElementById('roomopt' + indparty).value.length) {
				// populate first available party for this room
				units_added++;
				document.getElementById('roomopt' + indparty).value = idroom;
				vboPartiesRequested[indparty] = idroom;
				vboRoomsAssigned[idroom][indroom].push(indparty);
			} else {
				// find the next available party slot for this room
				var party_filled = 0;
				for (var j in vboRoomsParties[idroom]) {
					if (!vboRoomsParties[idroom].hasOwnProperty(j)) {
						continue;
					}
					var indparty = vboRoomsParties[idroom][j];
					if (!document.getElementById('roomopt' + indparty).value.length) {
						// populate available party slot for this room
						party_filled++;
						units_added++;
						document.getElementById('roomopt' + indparty).value = idroom;
						vboPartiesRequested[indparty] = idroom;
						vboRoomsAssigned[idroom][indroom].push(indparty);
						break;
					}
				}
				if (!units_added || !party_filled) {
					/**
					 * All room-parties that this room can accommodate have been booked.
					 * In case of large room parties, this kind of situations could occur.
					 */
					var totbooked = vboCompactCountBooked();
					if (totbooked < vboNeededBooked) {
						var indparty = totbooked + 1;
						if (!document.getElementById('roomopt' + indparty).value.length) {
							// populate next available party for this room
							units_added++;
							document.getElementById('roomopt' + indparty).value = idroom;
							vboPartiesRequested[indparty] = idroom;
							vboRoomsAssigned[idroom][indroom].push(indparty);
						}
					}
				}
			}
		}
	} else if (truly_removing) {
		// get the reversed list of parties assigned (use .slice() before .reverse() to avoid referencing)
		var vbo_room_party_revass = vboRoomsAssigned[idroom][indroom].slice().reverse();
		// decreasing units from the previous selection
		for (var i = (removing_units - 1); i >= 0; i--) {
			// iterate the pool of rooms assigned starting from the first one added (last, but in a reverse order is like the first)
			if (!vbo_room_party_revass.hasOwnProperty(i)) {
				continue;
			}
			// current-first index added
			var rmparty = vbo_room_party_revass[i];
			// unset room booked
			document.getElementById('roomopt' + rmparty).value = '';
			for (var rmi in vboRoomsAssigned[idroom][indroom]) {
				if (!vboRoomsAssigned[idroom][indroom].hasOwnProperty(rmi)) {
					continue;
				}
				if (vboRoomsAssigned[idroom][indroom][rmi] == rmparty) {
					vboRoomsAssigned[idroom][indroom].splice(rmi, 1);
					break;
				}
			}
			if (vboPartiesRequested.hasOwnProperty(rmparty)) {
				delete vboPartiesRequested[rmparty];
			}
		}
	}

	// count rooms selected of this type
	var roomtotsel = vboCompactCountSelected(idroom);
	if (roomtotsel >= vboRoomsUnits[idroom]) {
		// no more units available for this room-type
		jQuery('.vbo-selroom-room' + idroom).not('#vbo-selroom-' + indroom + '-' + idroom).prop('disabled', true).closest('.vbo-room-result-body-bookingsolution').addClass('vbo-room-result-soldout');
	} else {
		// other units available for this room-type
		jQuery('.vbo-selroom-room' + idroom).prop('disabled', false).closest('.vbo-room-result-body-bookingsolution').removeClass('vbo-room-result-soldout');
	}

	// count non-empty input fields
	var totbooked = vboCompactCountBooked();

	if (totbooked >= vboNeededBooked) {
		// proceed with booking process, all rooms selected
		document.getElementById('vbsearchmainsbmt').style.display = 'block';
		jQuery('.vbo-room-result-body-bookmsg, .vbo-room-result-body-bookbtn').hide();
		// show the book now button only next to the rooms selected
		for (var i = 1; i <= vboNeededBooked; i++) {
			var idroomsel = jQuery('#roomopt' + i).val();
			if (!idroomsel || !idroomsel.length) {
				continue;
			}
			jQuery('.vbo-selroom-room' + idroomsel).each(function(k, v) {
				var elem = jQuery(v);
				if (elem.val() > 0) {
					elem.closest('.vbo-room-result-body-bookingsolution').find('.vbo-room-result-body-bookbtn').fadeIn();
				}
			});
		}
	} else {
		// missing rooms to select for booking
		document.getElementById('vbsearchmainsbmt').style.display = 'none';
		jQuery('.vbo-room-result-body-bookmsg, .vbo-room-result-body-bookbtn').hide();
		var missingrooms = vboNeededBooked - totbooked;
		var missmsg = missingrooms === 1 ? vboCompactMsg.missingRoomsSi : vboCompactMsg.missingRoomsPl;
		missmsg = missmsg.replace('%d', missingrooms);
		// display the message only if more rooms can be selected
		jQuery('.vbo-selroom-ddown').each(function(k, v) {
			var elem = jQuery(v);
			if (elem.val() != elem.find('option').last().attr('value') && elem.prop('disabled') === false) {
				// more units can be selected and drop down not disabled
				elem.closest('.vbo-room-result-body-bookingsolution').find('.vbo-room-result-body-bookmsg').text(missmsg).show();
			} else if (totbooked == 0 && elem.val() > 0 && elem.prop('disabled') === false) {
				// reset units
				elem.val('0');
				// display message for this room as well
				elem.closest('.vbo-room-result-body-bookingsolution').find('.vbo-room-result-body-bookmsg').text(missmsg).show();
			}
		});
	}
}

function vboCompactProceed() {
	document.getElementById('vbselectroomform').submit();
}

jQuery(function() {
<?php
foreach ($gallery_data as $idr => $gallery) {
	?>
	window["vikfxgallery<?php echo $idr; ?>"] = jQuery("#vikfx-gallery<?php echo $idr; ?> a").vikFxGallery();
	jQuery("#vikfx-gallery-previous-image<?php echo $idr; ?>, #vikfx-gallery-next-image<?php echo $idr; ?>, #vbroomimg<?php echo $idr; ?>").click(function() {
		if (typeof window["vikfxgallery<?php echo $idr; ?>"] !== "undefined") {
			window["vikfxgallery<?php echo $idr; ?>"].open();
		}
	});
	<?php
}
?>
});
</script>
