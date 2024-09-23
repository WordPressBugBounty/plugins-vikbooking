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

$vbo_app = VikBooking::getVboApplication();
$geo = VikBooking::getGeocodingInstance()->loadAssets();
$geo_supported = $geo->isSupported();
$rooms_params = count($this->row) ? json_decode($this->row['params'], true) : array();
$geo_import_from = $geo->getImportableConfigRooms((count($this->row) ? $this->row['id'] : 0));

// load colorpicker assets
JFactory::getDocument()->addStyleSheet(VBO_ADMIN_URI.'resources/js_upload/colorpicker.css');
JHtml::fetch('script', VBO_ADMIN_URI.'resources/js_upload/colorpicker.js');

// load lang vars for JS
JText::script('VBO_GEO_MARKERS_MULTI_ERRUNIT');
JText::script('VBODISTFEATURERUNIT');
JText::script('VBO_GEO_ADDRESS');
JText::script('VBO_GEO_CUSTOMIZE_MARKER');
JText::script('VBMAINPAYMENTSDEL');
JText::script('VBO_GEO_RMCONF_MARKER');
JText::script('VBO_WIDGETS_CONFRMELEM');
JText::script('VBO_GEO_HIDE_FROM_MAP');
JText::script('VBO_GEO_MAP_GOVERLAY');
JText::script('VBO_PLEASE_SELECT');
JText::script('VBO_GEO_IMPORT_FROM');
JText::script('VBO_GEO_IMPORT_CONF');
JText::script('VBO_GEO_FAILED_REASON');
JText::script('VBO_PLEASE_FILL_FIELDS');
?>
	<fieldset class="adminform">
		<div class="vbo-params-wrap">
			<legend class="adminlegend">
				<span class="vbo-geo-legend-title"><?php VikBookingIcons::e('map-marked-alt'); ?> <?php echo JText::translate('VBO_GEO_INFO'); ?></span>
			<?php
			if ($geo_supported && count($geo_import_from)) {
				?>
				<span class="vbo-geo-legend-import">
					<select id="vbo-geo-importfrom" onchange="vboImportGeoConfig(this.value);">
						<option></option>
					<?php
					foreach ($geo_import_from as $impfrom) {
						?>
						<option value="<?php echo $impfrom['id']; ?>"><?php echo $impfrom['name']; ?></option>
						<?php
					}
					?>
					</select>
				</span>
				<?php
			}
			?>
			</legend>
			<div class="vbo-params-container">
				<div class="vbo-param-container">
					<div class="vbo-param-label"><?php echo JText::translate('VBO_ENABLE_GEOCODING'); ?></div>
					<div class="vbo-param-setting">
						<?php
						$geo_enabled = (int)$geo->getRoomGeoParams($rooms_params, 'enabled', 0);
						echo $vbo_app->printYesNoButtons('geo_enabled', JText::translate('VBYES'), JText::translate('VBNO'), $geo_enabled, 1, 0, 'vboToggleGeoParams();');
						?>
					</div>
				</div>
			<?php
			if (!$geo_supported) {
				?>
				<div class="vbo-param-container vbo-geoinfo-param" style="<?php echo !$geo_enabled ? 'display: none;' : ''; ?>">
					<div class="vbo-param-setting">
						<p class="warn notice-noicon"><?php VikBookingIcons::e('exclamation-triangle'); ?> <?php echo JText::translate('VBO_GEO_UNSUPPORTED'); ?></p>
					</div>
				</div>
				<?php
			} else {
				?>
				<div class="vbo-param-container vbo-geoinfo-param" style="display: none;">
					<div class="vbo-param-label"><?php echo JText::translate('VBO_GEO_ADDRESS'); ?></div>
					<div class="vbo-param-setting">
						<input type="text" id="geo_address" value="<?php echo $geo->getRoomGeoParams($rooms_params, 'address', ''); ?>" />
						<span class="vbo-param-setting-comment" id="geo_address_formatted"></span>
					</div>
				</div>
				<div class="vbo-param-container vbo-geoinfo-param" style="display: none;">
					<div class="vbo-param-label"><?php echo JText::translate('VBO_GEO_COORDS'); ?></div>
					<div class="vbo-param-setting">
						<span><?php echo JText::translate('VBO_GEO_COORDS_LAT'); ?></span>
						<input type="number" id="geo_latitude" class="vbo-large-input-number" min="-90" max="90" step="any" value="<?php echo $geo->getRoomGeoParams($rooms_params, 'latitude', '43.7734385'); ?>" />
						<span><?php echo JText::translate('VBO_GEO_COORDS_LNG'); ?></span>
						<input type="number" id="geo_longitude" class="vbo-large-input-number" min="-180" max="180" step="any" value="<?php echo $geo->getRoomGeoParams($rooms_params, 'longitude', '11.2565501'); ?>" />
					</div>
				</div>
				<div class="vbo-param-container vbo-geoinfo-param" style="display: none;">
					<div class="vbo-param-label"><?php echo JText::translate('VBO_GEO_ZOOM'); ?></div>
					<div class="vbo-param-setting">
						<input type="number" id="geo_zoom" min="0" step="any" value="<?php echo (int)$geo->getRoomGeoParams($rooms_params, 'zoom', 3); ?>" />
					</div>
				</div>
				<div class="vbo-param-container vbo-geoinfo-param" style="display: none;">
					<div class="vbo-param-label"><?php echo JText::translate('VBO_GEO_MTYPE'); ?></div>
					<div class="vbo-param-setting">
						<?php
						$geo_mtype = $geo->getRoomGeoParams($rooms_params, 'mtype', 'roadmap');
						?>
						<select id="geo_mtype">
							<option value="roadmap"<?php echo $geo_mtype == 'roadmap' ? ' selected="selected"' : ''; ?>><?php echo JText::translate('VBO_GEO_MTYPE_ROADMAP'); ?></option>
							<option value="satellite"<?php echo $geo_mtype == 'satellite' ? ' selected="selected"' : ''; ?>><?php echo JText::translate('VBO_GEO_MTYPE_SATELLITE'); ?></option>
							<option value="hybrid"<?php echo $geo_mtype == 'hybrid' ? ' selected="selected"' : ''; ?>><?php echo JText::translate('VBO_GEO_MTYPE_HYBRID'); ?></option>
							<option value="terrain"<?php echo $geo_mtype == 'terrain' ? ' selected="selected"' : ''; ?>><?php echo JText::translate('VBO_GEO_MTYPE_TERRAIN'); ?></option>
						</select>
					</div>
				</div>
				<div class="vbo-param-container vbo-geoinfo-param" style="display: none;">
					<div class="vbo-param-label"><?php echo JText::translate('VBO_GEO_MAP_HEIGHT'); ?></div>
					<div class="vbo-param-setting">
						<input type="number" id="geo_height" min="50" max="3500" step="5" value="<?php echo $geo->getRoomGeoParams($rooms_params, 'height', 300); ?>" /> px
					</div>
				</div>
				<div class="vbo-param-container vbo-geoinfo-param" style="display: none;">
					<div class="vbo-param-setting">
						<div id="vbo-geo-map" style="width: 100%; height: <?php echo $geo->getRoomGeoParams($rooms_params, 'height', 300); ?>px;"></div>
						<?php
						// main room marker position
						$main_marker_lat = $geo->getRoomGeoParams($rooms_params, 'marker_lat', '');
						$main_marker_lng = $geo->getRoomGeoParams($rooms_params, 'marker_lng', '');
						$main_marker_hide = $geo->getRoomGeoParams($rooms_params, 'marker_hide', '');
						$main_marker_pos = null;
						if (empty($main_marker_hide) && !empty($main_marker_lat) && !empty($main_marker_lng)) {
							// prepare main marker (for base address) object for js
							$main_marker_pos = new stdClass;
							$main_marker_pos->lat = (float)$main_marker_lat;
							$main_marker_pos->lng = (float)$main_marker_lng;
						}
						?>
						<input type="hidden" id="geo_marker_lat" value="<?php echo $main_marker_lat; ?>" />
						<input type="hidden" id="geo_marker_lng" value="<?php echo $main_marker_lng; ?>" />
						<input type="hidden" id="geo_marker_hide" value="<?php echo $main_marker_hide; ?>" />
					</div>
				</div>
				<div class="vbo-param-container vbo-geoinfo-param" style="display: none;">
					<div class="vbo-param-label">
						<?php
						echo $vbo_app->createPopover(array('title' => JText::translate('VBO_GEO_MARKERS_ONEMULTI'), 'content' => JText::translate('VBO_GEO_MARKERS_ONEMULTI_HELP'), 'icon_class' => VikBookingIcons::i('map-marker-alt')));
						echo ' ' . JText::translate('VBO_GEO_MARKERS_ONEMULTI');
						?>
					</div>
					<div class="vbo-param-setting">
						<?php
						$geo_markers_multi = $geo->getRoomGeoParams($rooms_params, 'markers_multi', 0);
						?>
						<select id="geo_markers_multi">
							<option value="0"<?php echo $geo_markers_multi == 0 ? ' selected="selected"' : ''; ?>><?php echo JText::translate('VBO_GEO_MARKERS_ONE'); ?></option>
							<option value="1"<?php echo $geo_markers_multi == 1 ? ' selected="selected"' : ''; ?>><?php echo JText::translate('VBO_GEO_MARKERS_MULTI'); ?></option>
						</select>
					</div>
				</div>
				<div class="vbo-param-container vbo-geoinfo-param" style="display: none;">
					<div class="vbo-param-setting vbo-geoinfo-markers-wrap"></div>
				</div>
				<div class="vbo-param-container vbo-geoinfo-param" style="display: none;">
					<div class="vbo-param-label">
						<?php
						echo $vbo_app->createPopover(array('title' => JText::translate('VBO_GEO_MAP_GOVERLAY'), 'content' => JText::translate('VBO_GEO_MAP_GOVERLAY_HELP'), 'icon_class' => VikBookingIcons::i('images')));
						echo ' ' . JText::translate('VBO_GEO_MAP_GOVERLAY');
						?>
					</div>
					<div class="vbo-param-setting">
						<?php
						$goverlay_enabled = (int)$geo->getRoomGeoParams($rooms_params, 'goverlay', 0);
						echo $vbo_app->printYesNoButtons('geo_goverlay', JText::translate('VBYES'), JText::translate('VBNO'), $goverlay_enabled, 1, 0, 'vboToggleGeoGroundOverlay();');
						?>
						<button type="button" id="vbo-goverlay-edit" class="btn vbo-config-btn" style="<?php echo !$goverlay_enabled ? 'display: none;' : ''; ?>" onclick="vboToggleGeoGroundOverlay();"><?php VikBookingIcons::e('images'); ?> <?php echo JText::translate('VBMAINPAYMENTSEDIT'); ?></button>
					</div>
				</div>
				<?php
			}
			?>
			</div>
		</div>
	</fieldset>

	<script type="text/javascript">
		function vboToggleGeoParams() {
			if (jQuery('input[name="geo_enabled"]').is(':checked')) {
				jQuery('.vbo-geoinfo-param').show();
				
				// make a silent AJAX request only once to prepare the temporary record used to store the room geo information
				var room_id = <?php echo count($this->row) ? $this->row['id'] : '0'; ?>;
				if (room_id < 1) {
					var call_method = 'initRoomGeoTransient';
					jQuery.ajax({
						type: "POST",
						url: "<?php echo VikBooking::ajaxUrl('index.php?option=com_vikbooking&task=geocoding_endpoint'); ?>",
						data: {
							tmpl: "component",
							callback: call_method,
							room_id: room_id
						}
					}).done(function(response) {
						try {
							var obj_res = typeof response === 'string' ? JSON.parse(response) : response;
							if (!obj_res.hasOwnProperty(call_method)) {
								console.error('initRoomGeoTransient unexpected JSON response', obj_res);
							} else {
								// console.log(obj_res[call_method]);
							}
						} catch(err) {
							console.error('initRoomGeoTransient could not parse JSON response', err, response);
						}
					}).fail(function(err) {
						console.error('initRoomGeoTransient request failed', err);
					});
				}
				//
			} else {
				jQuery('.vbo-geoinfo-param').hide();
			}
		}
	</script>
<?php
if ($geo_supported) {
	$current_units_pos = $geo->getRoomGeoParams($rooms_params, 'units_pos');
	$current_units_pos = is_object($current_units_pos) ? $current_units_pos : (new stdClass);
	$current_goverlay = null;
	if ((int)$geo->getRoomGeoParams($rooms_params, 'goverlay', 0)) {
		$current_overlay_img = $geo->getRoomGeoParams($rooms_params, 'overlay_img', '');
		$current_overlay_south = $geo->getRoomGeoParams($rooms_params, 'overlay_south');
		$current_overlay_west = $geo->getRoomGeoParams($rooms_params, 'overlay_west');
		$current_overlay_north = $geo->getRoomGeoParams($rooms_params, 'overlay_north');
		$current_overlay_east = $geo->getRoomGeoParams($rooms_params, 'overlay_east');
		if (!empty($current_overlay_img) && !empty($current_overlay_south)) {
			// ground overlay is available
			$current_goverlay = new stdClass;
			$current_goverlay->url = $current_overlay_img;
			$current_goverlay->south = (float)$current_overlay_south;
			$current_goverlay->west = (float)$current_overlay_west;
			$current_goverlay->north = (float)$current_overlay_north;
			$current_goverlay->east = (float)$current_overlay_east;
		}
	}
	?>
	<script type="text/javascript">
		/**
		 * Define global scope vars
		 */
		var vbo_geomap = null,
			vbo_geomap_saving = false,
			vbo_geocoder = null,
			vbo_geomarker_room = null,
			vbo_geomarker_room_pos = <?php echo is_object($main_marker_pos) ? json_encode($main_marker_pos) : 'null'; ?>,
			vbo_info_marker_room = null,
			vbo_geomarker_units = {},
			vbo_geomarker_units_pos = <?php echo json_encode($current_units_pos); ?>,
			vbo_info_markers = {},
			vbo_current_marker_index = null,
			vbo_icn_marker_in = '<?php echo VikBookingIcons::i('map-marked-alt', 'vbo-geo-marker-icn-in'); ?>',
			vbo_icn_marker_out = '<?php echo VikBookingIcons::i('map', 'vbo-geo-marker-icn-out'); ?>',
			vbo_marker_base_uri = '<?php echo JUri::root(); ?>',
			vbo_doing_ground_overlay = false,
			vbo_ground_overlay = null,
			vbo_dbground_overlay = <?php echo is_object($current_goverlay) ? json_encode($current_goverlay) : 'null'; ?>;

		var vbo_modal_geomap_on = false;

		/**
		 * Delay saving button if saving the geomap
		 */
		Joomla.submitbutton = function(task) {
			if (vbo_geomap_saving && (task.indexOf('updateroom') >= 0 || task.indexOf('createroom') >= 0)) {
				console.log('Delay saving to allow geomap saving');
				setTimeout(function() {
					Joomla.submitform(task, document.adminForm);
				}, 750);
			} else {
				Joomla.submitform(task, document.adminForm);
			}
		}

		/**
		 * Shows the modal window
		 */
		function vboOpenModalGeoMap() {
			jQuery('.vbo-modal-overlay-block-geomap').show();
			vbo_modal_geomap_on = true;
		}

		/**
		 * Hides the modal window
		 */
		function hideVboModalGeoMap() {
			if (vbo_modal_geomap_on === true) {
				jQuery(".vbo-modal-overlay-block-geomap").fadeOut(400, function () {
					jQuery(".vbo-modal-overlay-content-geomap").show();
				});
				// turn flag off
				vbo_modal_geomap_on = false;
			}
			if (vbo_doing_ground_overlay === true) {
				if (vbo_ground_overlay === null) {
					// no overlay set, make sure to deselect the toggle
					jQuery('input[name="geo_goverlay"]').prop('checked', false);
				}
				// turn flag off
				vbo_doing_ground_overlay = false;
			}
		}

		/**
		 * Converts an RGB (or RGBA) color to hexadecimal
		 */
		function vboRgb2Hex(rgb) {
			var rgb_match = rgb.match(/^rgba?\((\d+),\s*(\d+),\s*(\d+)(?:,\s*([0-9.]+))?\)$/);
			if (!rgb_match) {
				return rgb;
			}
			return "#" + vboHex(rgb_match[1]) + vboHex(rgb_match[2]) + vboHex(rgb_match[3]);
		}

		/**
		 * Composes RGB (or RGBA) color values to hexadecimal
		 */
		function vboHex(x) {
			var vboHexDigits = new Array ("0","1","2","3","4","5","6","7","8","9","a","b","c","d","e","f");
			return isNaN(x) ? "00" : vboHexDigits[(x - x % 16) / 16] + vboHexDigits[x % 16];
		}

		/**
		 * Debounce technique to group a flurry of events into one single event.
		 * This is useful for listening to the key up event of the address.
		 */
		function vboDebounceEvent(func, wait, immediate) {
			var timeout;
			return function() {
				var context = this, args = arguments;
				var later = function() {
					timeout = null;
					if (!immediate) func.apply(context, args);
				};
				var callNow = immediate && !timeout;
				clearTimeout(timeout);
				timeout = setTimeout(later, wait);
				if (callNow) {
					func.apply(context, args);
				}
			}
		}

		/**
		 * Fires when the document is ready. Renders the entire map.
		 */
		function vboInitGeoMap() {
			// default map options
			var def_map_options = {
				center: new google.maps.LatLng(jQuery('#geo_latitude').val(), jQuery('#geo_longitude').val()),
				zoom: parseFloat(jQuery('#geo_zoom').val()),
				mapTypeId: jQuery('#geo_mtype').val()
			};
			// initialize Map
			vbo_geomap = new google.maps.Map(document.getElementById('vbo-geo-map'), def_map_options);
			// add listeners to map
			vbo_geomap.addListener('bounds_changed', function() {
				// update zoom
				jQuery('#geo_zoom').val(vbo_geomap.getZoom());
				// update center coordinates
				var center_bounds = vbo_geomap.getBounds().getCenter();
				jQuery('#geo_latitude').val(center_bounds.lat());
				jQuery('#geo_longitude').val(center_bounds.lng());
				// trigger the geo-saving event
				document.dispatchEvent(new Event('vbo-room-geosaving'));
			});
			vbo_geomap.addListener('maptypeid_changed', function() {
				// this will return the constant of the mapTypeId
				var maptypeid = vbo_geomap.getMapTypeId();
				jQuery('#geo_mtype').val((maptypeid + '').toLowerCase());
				// trigger the geo-saving event
				document.dispatchEvent(new Event('vbo-room-geosaving'));
			});
			vbo_geomap.addListener('click', function(e) {
				// handle the map click by passing the position
				vboGeoMapClickedPos(e.latLng);
			});
			// initialize Geocoder
			vbo_geocoder = new google.maps.Geocoder();
			// set current default marker for main room
			if (vbo_geomarker_room_pos !== null) {
				// create infowindow
				vbo_info_marker_room = new google.maps.InfoWindow({
					content: vboGenerateMainInfoMarkerContent(),
					disableAutoPan: true
				});
				// add map marker for base room-type
				vbo_geomarker_room = new google.maps.Marker({
					draggable: true,
					map: vbo_geomap,
					position: {
						lat: parseFloat(vbo_geomarker_room_pos.lat),
						lng: parseFloat(vbo_geomarker_room_pos.lng)
					},
					title: jQuery('input[name="cname"]').val()
				});
				// add listener to marker
				vbo_geomarker_room.addListener('dragend', function() {
					// update lat and lng of main room's marker
					var current_lat = vbo_geomarker_room.getPosition().lat();
					var current_lng = vbo_geomarker_room.getPosition().lng();
					jQuery('#geo_marker_lat').val(current_lat);
					jQuery('#geo_marker_lng').val(current_lng);
					// update the same information in the marker position
					if (vbo_geomarker_room_pos !== null) {
						vbo_geomarker_room_pos['lat'] = current_lat;
						vbo_geomarker_room_pos['lng'] = current_lng;
					}
					// trigger the geo-saving event
					document.dispatchEvent(new Event('vbo-room-geosaving'));
				});
				vbo_geomarker_room.addListener('click', function() {
					// close any other open infowindow first
					for (var m in vbo_info_markers) {
						if (!vbo_info_markers.hasOwnProperty(m)) {
							continue;
						}
						vbo_info_markers[m].close();
					}
					vbo_info_marker_room.open(vbo_geomap, vbo_geomarker_room);
				});

			}
			// populate current markers, if any
			vboPopulateMapMarkers();
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

		/**
		 * Simple way for centering the map, but this won't fit all marker bounds.
		 */
		function vboSetGeoMapCenter(lat, lng) {
			if (vbo_geomap === null) {
				console.error('map is null');
				return false;
			}
			if (isNaN(lat) || isNaN(lng)) {
				console.error('latitude and longitude must be numbers', lat, lng);
				return false;
			}
			vbo_geomap.setCenter(new google.maps.LatLng(lat, lng));
		}

		/**
		 * Gets the position of all markers to extend the bounds,
		 * and then sets the zoom and bounds to fit them all.
		 */
		function vboGeoMapCenterBounds() {
			if (vbo_geomap === null) {
				console.error('map is null');
				return false;
			}
			// set map center and zoom automatically
			var latlngbounds = new google.maps.LatLngBounds();
			// check if main address marker is in the map
			if (vbo_geomarker_room !== null) {
				latlngbounds.extend(vbo_geomarker_room.getPosition());
			}
			// parse all room-units markers
			for (var i in vbo_geomarker_units) {
				if (!vbo_geomarker_units.hasOwnProperty(i)) {
					continue;
				}
				latlngbounds.extend(vbo_geomarker_units[i].getPosition());
			}
			// apply calculated center and bounds
			vbo_geomap.setCenter(latlngbounds.getCenter());
			vbo_geomap.fitBounds(latlngbounds);
		}

		/**
		 * Fires when typing an address to geocode it within the map.
		 */
		function vboAddressGeocoding() {
			// reset formatted address
			jQuery('#geo_address_formatted').text('');

			var address = jQuery('#geo_address').val();
			if (!address.length) {
				return;
			}
			vbo_geocoder.geocode({'address': address}, function(results, status) {
				if (status == 'OK') {
					if (results.length > 1) {
						console.log('multiple address results returned', results);
					}
					// populate formatted address
					if (results[0].hasOwnProperty('formatted_address')) {
						jQuery('#geo_address_formatted').text(results[0].formatted_address);
					}
					// update coordinates
					jQuery('#geo_latitude').val(results[0].geometry.location.lat());
					jQuery('#geo_longitude').val(results[0].geometry.location.lng());
					// remove any previously added marker for main room
					if (vbo_geomarker_room !== null) {
						vbo_geomarker_room.setMap(null);
					}
					// create infowindow
					vbo_info_marker_room = new google.maps.InfoWindow({
						content: vboGenerateMainInfoMarkerContent(),
						disableAutoPan: true
					});
					// add map marker for base room-type
					vbo_geomarker_room = new google.maps.Marker({
						draggable: true,
						map: vbo_geomap,
						position: results[0].geometry.location,
						title: jQuery('input[name="cname"]').val()
					});
					// set new coords for marker
					jQuery('#geo_marker_lat').val(results[0].geometry.location.lat());
					jQuery('#geo_marker_lng').val(results[0].geometry.location.lng());
					jQuery('#geo_marker_hide').val('');
					// add listener to marker
					vbo_geomarker_room.addListener('dragend', function() {
						// update lat and lng of main room's marker
						var current_lat = vbo_geomarker_room.getPosition().lat();
						var current_lng = vbo_geomarker_room.getPosition().lng();
						jQuery('#geo_marker_lat').val(current_lat);
						jQuery('#geo_marker_lng').val(current_lng);
						// update the same information in the marker position
						if (vbo_geomarker_room_pos === null) {
							vbo_geomarker_room_pos = {};
						}
						vbo_geomarker_room_pos['lat'] = current_lat;
						vbo_geomarker_room_pos['lng'] = current_lng;
						// trigger the geo-saving event
						document.dispatchEvent(new Event('vbo-room-geosaving'));
					});
					vbo_geomarker_room.addListener('click', function() {
						// close any other open infowindow first
						for (var m in vbo_info_markers) {
							if (!vbo_info_markers.hasOwnProperty(m)) {
								continue;
							}
							vbo_info_markers[m].close();
						}
						vbo_info_marker_room.open(vbo_geomap, vbo_geomarker_room);
					});
					// set map center and zoom automatically
					vboGeoMapCenterBounds();
				} else {
					alert(Joomla.JText._('VBO_GEO_FAILED_REASON') + ' ' + status);
				}
			});
		}

		/**
		 * Given all the current positions, adds the current markers to the map and register the events.
		 */
		function vboPopulateMapMarkers() {
			// always reset markers pool and remove them from map
			for (var i in vbo_geomarker_units) {
				if (!vbo_geomarker_units.hasOwnProperty(i)) {
					continue;
				}
				// remove current marker from map
				vbo_geomarker_units[i].setMap(null);
			}
			// reset vars
			vbo_geomarker_units = {};
			vbo_info_markers = {};
			// empty markers wrap
			jQuery('.vbo-geoinfo-markers-wrap').html('');
			// calculate limits
			var multi_markers = jQuery('#geo_markers_multi').val();
			var room_units = jQuery('#room_units').val();
			var tot_markers = multi_markers > 0 && room_units > 1 ? room_units : 1;
			tot_markers = parseInt(tot_markers);
			// iterate through markers to add and display
			for (var i = 1; i <= tot_markers; i++) {
				var marker_options = null;
				var marker_title = Joomla.JText._('VBODISTFEATURERUNIT') + (i + '');
				if (tot_markers === 1 && jQuery('input[name="cname"]').val().length) {
					marker_title = jQuery('input[name="cname"]').val();
				}
				if (vbo_geomarker_units_pos.hasOwnProperty(i)) {
					// marker index saved
					marker_options = {
						draggable: true,
						map: vbo_geomap,
						position: {
							lat: parseFloat(vbo_geomarker_units_pos[i].lat),
							lng: parseFloat(vbo_geomarker_units_pos[i].lng)
						},
						title: marker_title
					};
					// set custom unit property
					marker_options['vbo_unit'] = i;
					// check if we know a custom icon for this marker
					if (vbo_geomarker_units_pos[i].hasOwnProperty('icon')) {
						marker_options['icon'] = vbo_geomarker_units_pos[i]['icon'];
					}
					// create marker infowindow
					var vbo_info_marker_cont = vboGenerateInfoMarkerContent(i, marker_title);
					var vbo_info_marker = new google.maps.InfoWindow({
						content: vbo_info_marker_cont,
						disableAutoPan: true
					});
					// add unit marker to map
					var vbo_geomarker_runit = new google.maps.Marker(marker_options);
					// add listener to marker
					vbo_geomarker_runit.addListener('dragend', function(e) {
						// update lat and lng of room unit marker
						var current_lat = this.getPosition().lat();
						var current_lng = this.getPosition().lng();
						if (vbo_geomarker_units_pos.hasOwnProperty(this['vbo_unit'])) {
							vbo_geomarker_units_pos[this['vbo_unit']]['lat'] = current_lat;
							vbo_geomarker_units_pos[this['vbo_unit']]['lng'] = current_lng;
						}
						// trigger the geo-saving event
						document.dispatchEvent(new Event('vbo-room-geosaving'));
					});
					vbo_geomarker_runit.addListener('click', function() {
						if (this['vbo_unit'] && vbo_info_markers.hasOwnProperty(this['vbo_unit'])) {
							// close any other open infowindow first
							for (var m in vbo_info_markers) {
								if (!vbo_info_markers.hasOwnProperty(m) || m == this['vbo_unit']) {
									continue;
								}
								vbo_info_markers[m].close();
							}
							if (vbo_geomarker_room !== null && vbo_info_marker_room !== null) {
								// close address marker infowindow
								vbo_info_marker_room.close();
							}
							vbo_info_markers[this['vbo_unit']].open(vbo_geomap, this);
						} else {
							console.error('info marker not found', this);
						}
					});
					// register marker to pool
					vbo_geomarker_units[i] = vbo_geomarker_runit;
					// register info window
					vbo_info_markers[i] = vbo_info_marker;
				}
				// build marker settings GUI
				var marker_setting_wrap = '';
				marker_setting_wrap += '<div class="vbo-geoinfo-marker-cont ' + (marker_options === null ? 'vbo-geoinfo-marker-out' : 'vbo-geoinfo-marker-in') + '" data-markerindex="' + i + '">';
				marker_setting_wrap += '	<div class="vbo-geoinfo-marker-label">' + marker_title + '</div>';
				marker_setting_wrap += '	<div class="vbo-geoinfo-marker-cmd">';
				var marker_btn_icn = '';
				if (marker_options === null) {
					marker_btn_icn = '<i class="' + vbo_icn_marker_out + '"></i>';
				} else {
					marker_btn_icn = '<i class="' + vbo_icn_marker_in + '"></i>';
				}
				marker_setting_wrap += '		<button type="button" class="btn btn-small btn-secondary vbo-geomarker-status" onclick="vboManageRoomMarker(' + i + ');">' + marker_btn_icn + '</button>';
				marker_setting_wrap += '	</div>';
				marker_setting_wrap += '</div>';
				// append marker settings
				jQuery('.vbo-geoinfo-markers-wrap').append(marker_setting_wrap);
			}
		}

		/**
		 * Adds a new marker to the map, or opens its info window if the marker index exists already.
		 */
		function vboManageRoomMarker(index, position) {
			if (!index || isNaN(index)) {
				console.error('vboManageRoomMarker invalid argument', index);
				return false;
			}
			if (vbo_geomap === null) {
				console.error('map is empty');
				return false;
			}
			if (vbo_geomarker_units.hasOwnProperty(index)) {
				// close any other open infowindow first
				for (var m in vbo_info_markers) {
					if (!vbo_info_markers.hasOwnProperty(m) || m == index) {
						continue;
					}
					vbo_info_markers[m].close();
				}
				if (vbo_geomarker_room !== null && vbo_info_marker_room !== null) {
					// close address marker infowindow
					vbo_info_marker_room.close();
				}
				// marker exists for this room-unit, open its infowindow
				vbo_info_markers[index].open(vbo_geomap, vbo_geomarker_units[index]);
			} else {
				// marker does not exist, add it to map
				var map_center = vbo_geomap.getCenter();
				if (!map_center) {
					console.error('Map center or bounds have not been set');
					return false;
				}
				if (!position) {
					// calculate new random position from current center and zoom level
					var center_lat = map_center.lat();
					var decims_lat = center_lat - parseInt(center_lat);
					var center_lng = map_center.lng();
					var decims_lng = center_lng - parseInt(center_lng);
					var current_zoom = vbo_geomap.getZoom();
					var increase = ((current_zoom + 1) * 2) / 100000;
					decims_lat += increase;
					decims_lng += increase;
					position = {
						lat: (parseInt(center_lat) + decims_lat),
						lng: (parseInt(center_lng) + decims_lng)
					}
				}
				// create marker
				var marker_options = {
					draggable: true,
					map: vbo_geomap,
					position: position,
					title: Joomla.JText._('VBODISTFEATURERUNIT') + (index + '')
				};
				// set custom unit property
				marker_options['vbo_unit'] = index;
				// create marker infowindow
				var vbo_info_marker_cont = vboGenerateInfoMarkerContent(index, null);
				var vbo_info_marker = new google.maps.InfoWindow({
					content: vbo_info_marker_cont,
					disableAutoPan: true
				});
				// add unit marker to map
				var vbo_geomarker_runit = new google.maps.Marker(marker_options);
				// add listener to marker
				vbo_geomarker_runit.addListener('dragend', function(e) {
					// update lat and lng of room unit marker
					var current_lat = this.getPosition().lat();
					var current_lng = this.getPosition().lng();
					if (vbo_geomarker_units_pos.hasOwnProperty(this['vbo_unit'])) {
						vbo_geomarker_units_pos[this['vbo_unit']]['lat'] = current_lat;
						vbo_geomarker_units_pos[this['vbo_unit']]['lng'] = current_lng;
					}
					// trigger the geo-saving event
					document.dispatchEvent(new Event('vbo-room-geosaving'));
				});
				vbo_geomarker_runit.addListener('click', function() {
					if (this['vbo_unit'] && vbo_info_markers.hasOwnProperty(this['vbo_unit'])) {
						// close any other open infowindow first
						for (var m in vbo_info_markers) {
							if (!vbo_info_markers.hasOwnProperty(m) || m == this['vbo_unit']) {
								continue;
							}
							vbo_info_markers[m].close();
						}
						if (vbo_geomarker_room !== null && vbo_info_marker_room !== null) {
							// close address marker infowindow
							vbo_info_marker_room.close();
						}
						vbo_info_markers[this['vbo_unit']].open(vbo_geomap, this);
					} else {
						console.error('info marker not found', this);
					}
				});
				// register marker to pool
				vbo_geomarker_units[index] = vbo_geomarker_runit;
				// register marker position
				if (typeof position.lat == 'function') {
					// position may be a LatLng object, convert it to number properties
					var prop_position = {
						lat: position.lat(),
						lng: position.lng()
					};
					position = prop_position;
				}
				vbo_geomarker_units_pos[index] = position;
				// register info window
				vbo_info_markers[index] = vbo_info_marker;
				// center map to fit all markers
				vboGeoMapCenterBounds();
				// update marker settings status
				var settings_btn = jQuery('.vbo-geoinfo-marker-cont[data-markerindex="' + index + '"]').find('.vbo-geoinfo-marker-cmd').find('.vbo-geomarker-status');
				if (settings_btn && settings_btn.length) {
					settings_btn.find('i').removeClass().addClass(vbo_icn_marker_in);
					jQuery('.vbo-geoinfo-marker-cont[data-markerindex="' + index + '"]').removeClass('vbo-geoinfo-marker-out').addClass('vbo-geoinfo-marker-in');
				}
				// trigger the geo-saving event
				document.dispatchEvent(new Event('vbo-room-geosaving'));
			}
		}

		/**
		 * Prompts the confirmation for removing a room-unit marker from the map
		 */
		function vboRemoveMarker(index) {
			if (confirm(Joomla.JText._('VBO_GEO_RMCONF_MARKER'))) {
				// remove marker from map
				vbo_geomarker_units[index].setMap(null);
				// remove object properties
				delete vbo_geomarker_units[index];
				delete vbo_geomarker_units_pos[index];
				delete vbo_info_markers[index];
				// update marker settings status
				var settings_btn = jQuery('.vbo-geoinfo-marker-cont[data-markerindex="' + index + '"]').find('.vbo-geoinfo-marker-cmd').find('.vbo-geomarker-status');
				if (settings_btn && settings_btn.length) {
					settings_btn.find('i').removeClass().addClass(vbo_icn_marker_out);
					jQuery('.vbo-geoinfo-marker-cont[data-markerindex="' + index + '"]').removeClass('vbo-geoinfo-marker-in').addClass('vbo-geoinfo-marker-out');
				}
				// trigger the geo-saving event
				document.dispatchEvent(new Event('vbo-room-geosaving'));
			}
		}

		/**
		 * Prompts the confirmation for removing the address marker from the map
		 */
		function vboRemoveAddressMarker() {
			if (vbo_geomarker_room === null) {
				return false;
			}
			if (confirm(Joomla.JText._('VBO_GEO_RMCONF_MARKER'))) {
				// remove marker from map
				vbo_geomarker_room.setMap(null);
				// unset object properties
				vbo_geomarker_room = null;
				vbo_geomarker_room_pos = null;
				// set main marker for hiding
				jQuery('#geo_marker_hide').val('1');
				// trigger the geo-saving event
				document.dispatchEvent(new Event('vbo-room-geosaving'));
			}
		}

		/**
		 * Opens the modal to customize the currently selected map marker
		 */
		function vboCustomizeMarker(index) {
			if (!vbo_geomarker_units.hasOwnProperty(index)) {
				console.error('marker not found from given index', index, vbo_geomarker_units);
				return false;
			}
			// update current marker index
			vbo_current_marker_index = index;
			// set proper modal title
			jQuery('#vbo-modal-geomap-title').html('<?php VikBookingIcons::e('map-marker-alt'); ?> ' + Joomla.JText._('VBO_GEO_CUSTOMIZE_MARKER'));
			// open modal
			jQuery('.vbo-cust-geo-overlay-wrapper').hide();
			jQuery('.vbo-cust-geo-marker-wrapper').show();
			vboOpenModalGeoMap();
			// populate current values of the modal depending on the current marker icon type
			var current_icon = vbo_geomarker_units[index].getIcon();
			if (!current_icon) {
				// default icon of Google Maps (null or undefined)
				jQuery('#vbo-geo-marker-icntype').val('google').trigger('change');
			} else if (current_icon.hasOwnProperty('path')) {
				// SVG path symbol
				jQuery('#vbo-geo-marker-icntype').val('symbol').trigger('change');
				// deselect all SVG icons
				jQuery('.vbo-cust-geo-marker-svg').attr('data-markerapply', '0');
				// select current SVG icon by using the custom property "vbo_icon_id"
				if (current_icon.hasOwnProperty('vbo_icon_id')) {
					jQuery('.vbo-cust-geo-marker-svg[data-markerid="' + current_icon['vbo_icon_id'] + '"]').trigger('click');
				}
			} else if (current_icon.hasOwnProperty('url')) {
				// icon URL
				jQuery('#vbo-geo-marker-icntype').val('icon').trigger('change');
				// set width and height, if any
				var icon_width = null,
					icon_height = null;
				if (current_icon.hasOwnProperty('scaledSize') && current_icon['scaledSize'].hasOwnProperty('width')) {
					icon_width = current_icon['scaledSize']['width'];
					icon_height = current_icon['scaledSize']['height'];
				} else if (current_icon.hasOwnProperty('size') && current_icon['size'].hasOwnProperty('width')) {
					icon_width = current_icon['size']['width'];
					icon_height = current_icon['size']['height'];
				}
				if (icon_width && icon_height) {
					jQuery('#vbo-geo-marker-icon-width').val(icon_width);
					jQuery('#vbo-geo-marker-icon-height').val(icon_height);
				}
				// try to populate the input value of the media manager
				jQuery('input[name="marker_icon_img"]').val(current_icon['url'].replace(vbo_marker_base_uri, ''));
				// try to set the current image, if any (this changes from J to WP)
				if (jQuery('.image-preview-wrapper').length) {
					jQuery('.vbo-cust-geo-marker-wrapper').find('.image-preview-wrapper').html('<img src="' + current_icon['url'] + '" />').show();
				}
			}
			// populate default values
			if (current_icon && current_icon.hasOwnProperty('fillColor') && current_icon['fillColor'].length) {
				jQuery('#vbo-geo-marker-fillcolor').val(current_icon['fillColor']);
				jQuery('.vbo-inspector-colorpicker').css('backgroundColor', current_icon['fillColor']);
			}
			if (current_icon && current_icon.hasOwnProperty('fillOpacity') && current_icon['fillOpacity'] >= 0) {
				jQuery('#vbo-geo-marker-opacity').val(current_icon['fillOpacity']);
			}
		}

		/**
		 * Triggers when changing the type of icon for the marker
		 */
		function vboCustomizeMarkerChangeIconType(type) {
			// always hide all conditional fields
			jQuery('.vbo-cust-geo-marker-bottom').hide();
			if (jQuery('.vbo-cust-geo-marker-bottom[data-geomarker="' + type + '"]').length) {
				// show requested type
				jQuery('.vbo-cust-geo-marker-bottom[data-geomarker="' + type + '"]').show();
				if (type == 'icon') {
					// lower the z-index of our modal to allow the media-manager modal to be displayed on top
					jQuery('.vbo-modal-overlay-block-geomap').addClass('vbo-modal-overlay-block-geomap-lowerzindex');
				} else {
					// restore the original z-index of our modal
					jQuery('.vbo-modal-overlay-block-geomap').removeClass('vbo-modal-overlay-block-geomap-lowerzindex');
				}
			}
		}

		/**
		 * Fires when clicking on an SVG path symbol
		 */
		function vboCustomizeMarkerSelectSymbol(elem) {
			elem = jQuery(elem);
			jQuery('.vbo-cust-geo-marker-svg').attr('data-markerapply', '0');
			elem.attr('data-markerapply', '1');
			if (elem.hasClass('vbo-cust-geo-marker-svg-addnew')) {
				elem.hide();
				jQuery('.vbo-cust-geo-marker-svg-newfield').show();
				// hide all regular customization fields
				jQuery('.vbo-geo-marker-param-container').not('.vbo-cust-geo-marker-svg-newfield').hide();
			} else {
				jQuery('.vbo-cust-geo-marker-svg-addnew').show();
				jQuery('.vbo-cust-geo-marker-svg-newfield').hide();
				// restore all regular customization fields
				jQuery('.vbo-geo-marker-param-container').not('.vbo-cust-geo-marker-svg-newfield').show();
				// populate default styling from selected symbol
				var current_fill = elem.find('svg').css('fill');
				var current_opacity = elem.find('svg').css('opacity');
				if (current_fill && current_fill.length) {
					jQuery('.vbo-inspector-colorpicker').css('backgroundColor', current_fill);
					if (current_fill.indexOf('rgb') >= 0) {
						current_fill = vboRgb2Hex(current_fill);
					}
					jQuery('#vbo-geo-marker-fillcolor').val(current_fill);
				}
				if (current_opacity && current_opacity > 0) {
					jQuery('#vbo-geo-marker-opacity').val(current_opacity);
				}
			}
			jQuery('.vbo-cust-geo-marker-svg-adjust').show();
		}

		/**
		 * Fires on the keyup event of the textarea for the SVG path
		 */
		function vboCustomizeMarkerSanitizeCustomSymbol() {
			var cur_path = jQuery('#vbo-newsvg-path').val();
			if (!cur_path.length) {
				return;
			}
			// check if we got a full path tag with the "d" attribute
			if ((/[^i]d="(.+)"/i).test(cur_path)) {
				// grab only what's inside the path attribute
				var matches = cur_path.match(/[^i]d="(.+)"/i);
				// replace the value with just the content of the path attribute
				jQuery('#vbo-newsvg-path').val(matches[1]);
				return;
			}
			// always replace invalid characters that may break the content of the SVG
			jQuery('#vbo-newsvg-path').val(cur_path.replace(/<|=|"|\/|>|\?/gi, ''));
			return;
		}

		/**
		 * Fires when adding a new custom SVG symbol path
		 */
		function vboCustomizeMarkerAddCustomSymbol() {
			var cur_name = jQuery('#vbo-newsvg-name').val();
			var cur_path = jQuery('#vbo-newsvg-path').val();
			if (!cur_name.length || !cur_path.length) {
				alert(Joomla.JText._('VBO_PLEASE_FILL_FIELDS'));
				return false;
			}
			// compose ID for custom SVG
			var now = new Date;
			var svgid = now.getTime();
			// compose new SVG element
			var svgelem = '';
			svgelem += '<div class="vbo-cust-geo-marker-svg" data-markerapply="0" data-markerid="' + svgid + '" data-markergroup="custom" onclick="vboCustomizeMarkerSelectSymbol(this);">';
			svgelem += '	<div class="vbo-cust-geo-marker-svg-name">';
			svgelem += '		<span>' + cur_name + '</span>';
			svgelem += '	</div>';
			svgelem += '	<div class="vbo-cust-geo-marker-svg-icon">';
			svgelem += '		<svg>';
			svgelem += '			<path d="' + cur_path + '" />';
			svgelem += '		</svg>';
			svgelem += '	</div>';
			svgelem += '</div>';
			// deselect all icons
			jQuery('.vbo-cust-geo-marker-svg').attr('data-markerapply', '0');
			// unset and hide fields for adding new custom SVG
			jQuery('.vbo-cust-geo-marker-svg-newfield, .vbo-cust-geo-marker-svg-adjust').hide();
			jQuery('#vbo-newsvg-name, #vbo-newsvg-path').val('');
			// append new SVG element
			jQuery(svgelem).insertBefore('.vbo-cust-geo-marker-svg-addnew');
			// display again the add new button, but only one icon can be saved
			jQuery('.vbo-cust-geo-marker-svg-addnew').show();
		}

		/**
		 * Cancels the action of creating a custom symbol
		 */
		function vboCustomizeMarkerCancelCustomSymbol() {
			// unset and hide fields for adding new custom SVG
			jQuery('.vbo-cust-geo-marker-svg-newfield, .vbo-cust-geo-marker-svg-adjust').hide();
			jQuery('#vbo-newsvg-name, #vbo-newsvg-path').val('');
			// restore add new button and deselect all choices
			jQuery('.vbo-cust-geo-marker-svg-addnew').show();
			jQuery('.vbo-cust-geo-marker-svg').attr('data-markerapply', '0');
		}

		/**
		 * Applies the selected changes to the current map marker
		 */
		function vboCustomizeMarkerApply() {
			if (!vbo_current_marker_index || !vbo_geomarker_units.hasOwnProperty(vbo_current_marker_index)) {
				console.error('no active map marker found', vbo_current_marker_index, vbo_geomarker_units);
				return false;
			}
			// get icon type
			var icntype = jQuery('#vbo-geo-marker-icntype').val();
			if (!icntype.length || icntype == 'google') {
				// set default icon of Google Maps (by passing null)
				vbo_geomarker_units[vbo_current_marker_index].setIcon(null);
				// update icon in global object
				if (vbo_geomarker_units_pos !== null && vbo_geomarker_units_pos.hasOwnProperty(vbo_current_marker_index)) {
					delete vbo_geomarker_units_pos[vbo_current_marker_index]['icon'];
				}
			} else if (icntype == 'symbol') {
				// SVG path symbol
				var current_svg = jQuery('.vbo-cust-geo-marker-svg[data-markerapply="1"]');
				if (!current_svg.length || !current_svg.first().find('svg').length) {
					console.error('no SVG path symbol selected');
					alert(Joomla.JText._('VBO_PLEASE_SELECT'));
					return false;
				}
				current_svg = current_svg.first();
				// compose selected icon object
				var icon_obj = {
					id: current_svg.attr('data-markerid'),
					name: current_svg.find('.vbo-cust-geo-marker-svg-name').find('span').text(),
					group: current_svg.attr('data-markergroup'),
					fill: jQuery('#vbo-geo-marker-fillcolor').val(),
					opacity: parseFloat(jQuery('#vbo-geo-marker-opacity').val()),
					path: current_svg.find('.vbo-cust-geo-marker-svg-icon').find('svg').find('path').attr('d')
				};
				// check if width and height can be used to set anchor point with X/Y coordinates
				var coord_x = current_svg.attr('data-markerpointx');
				var coord_y = current_svg.attr('data-markerpointy');
				if (coord_x && coord_y) {
					icon_obj['anchor'] = {
						x: parseFloat(coord_x),
						y: parseFloat(coord_y)
					};
				}

				// make a silent AJAX request to update the symbol in the DB or to create it if that's a new icon
				var call_method = 'storeSvgSymbol';
				jQuery.ajax({
					type: "POST",
					url: "<?php echo VikBooking::ajaxUrl('index.php?option=com_vikbooking&task=geocoding_endpoint'); ?>",
					data: {
						tmpl: "component",
						callback: call_method,
						symbol: icon_obj
					}
				}).done(function(response) {
					try {
						var obj_res = JSON.parse(response);
						if (!obj_res.hasOwnProperty(call_method)) {
							console.error('storeSvgSymbol unexpected JSON response', obj_res);
						} else {
							// console.log(obj_res[call_method]);
							// trigger the geo-saving event
							document.dispatchEvent(new Event('vbo-room-geosaving'));
						}
					} catch(err) {
						console.error('storeSvgSymbol could not parse JSON response', err, response);
					}
				}).fail(function(err) {
					console.error('storeSvgSymbol request failed', err);
				});

				// compose icon symbol object
				var symbol_obj = {
					path: icon_obj.path,
					fillColor: icon_obj.fill,
					fillOpacity: icon_obj.opacity,
					strokeWeight: 0,
					scale: 1,
					vbo_icon_id: icon_obj.id
				};
				// check if anchor point is defined
				if (icon_obj.hasOwnProperty('anchor')) {
					symbol_obj['anchor'] = icon_obj['anchor'];
				}
				// update marker icon on map
				vbo_geomarker_units[vbo_current_marker_index].setIcon(symbol_obj);
				// update icon in global object
				if (vbo_geomarker_units_pos !== null && vbo_geomarker_units_pos.hasOwnProperty(vbo_current_marker_index)) {
					vbo_geomarker_units_pos[vbo_current_marker_index]['icon'] = symbol_obj;
				}
			} else if (icntype == 'icon') {
				// icon URL (Icon interface)
				var custom_icon_url = jQuery('input[name="marker_icon_img"]').val();
				if (!custom_icon_url.length) {
					console.error('no image selected');
					alert(Joomla.JText._('VBO_PLEASE_SELECT'));
					return false;
				}
				if (custom_icon_url.substr(0, 1) == '/') {
					custom_icon_url = custom_icon_url.substr(1);
				}
				var icon_url = vbo_marker_base_uri + custom_icon_url;
				var icon_obj = {
					url: icon_url
				};
				var icon_width = jQuery('#vbo-geo-marker-icon-width').val();
				var icon_height = jQuery('#vbo-geo-marker-icon-height').val();
				if (icon_width && icon_width > 0 && icon_height > 0) {
					var scaledSize = {
						width: parseInt(icon_width),
						height: parseInt(icon_height)
					}
					icon_obj['scaledSize'] = scaledSize;
				}
				// update marker icon on map
				vbo_geomarker_units[vbo_current_marker_index].setIcon(icon_obj);
				// update icon in global object
				if (vbo_geomarker_units_pos !== null && vbo_geomarker_units_pos.hasOwnProperty(vbo_current_marker_index)) {
					vbo_geomarker_units_pos[vbo_current_marker_index]['icon'] = icon_obj;
				}
			}

			// hide modal
			hideVboModalGeoMap();

			// trigger the geo-saving event
			document.dispatchEvent(new Event('vbo-room-geosaving'));
		}

		/**
		 * Removes an SVG symbol from DOM and DB
		 */
		function vboCustomizeMarkerDeleteSymbol(elem) {
			if (confirm(Joomla.JText._('VBO_WIDGETS_CONFRMELEM'))) {
				var symbol_id = jQuery(elem).closest('.vbo-cust-geo-marker-svg').attr('data-markerid');
				if (!symbol_id || !symbol_id.length) {
					console.error('symbol id is empty', elem);
					return false;
				}
				// remove the element from the DOM
				jQuery(elem).closest('.vbo-cust-geo-marker-svg').remove();
				// make a silent AJAX request to remove the symbol from the DB
				var call_method = 'deleteSvgSymbol';
				jQuery.ajax({
					type: "POST",
					url: "<?php echo VikBooking::ajaxUrl('index.php?option=com_vikbooking&task=geocoding_endpoint'); ?>",
					data: {
						tmpl: "component",
						callback: call_method,
						symbol_id: symbol_id
					}
				}).done(function(response) {
					try {
						var obj_res = JSON.parse(response);
						if (!obj_res.hasOwnProperty(call_method)) {
							console.error('deleteSvgSymbol unexpected JSON response', obj_res);
						} else {
							// console.log(obj_res[call_method]);
						}
					} catch(err) {
						console.error('deleteSvgSymbol could not parse JSON response', err, response);
					}
				}).fail(function(err) {
					console.error('deleteSvgSymbol request failed', err);
				});
			}
		}

		/**
		 * Toggles the use of a ground overlay image for the map.
		 */
		function vboToggleGeoGroundOverlay() {
			if (vbo_geomap === null && vbo_ground_overlay === null) {
				console.error('both map and ground overlay are null');
				return false;
			}
			if (jQuery('input[name="geo_goverlay"]').is(':checked')) {
				// populate bounds coords, and current image if empty
				var south_west = null,
					north_east = null;
				if (vbo_ground_overlay !== null) {
					// get current values from ground overlay
					var bounds = vbo_ground_overlay.getBounds();
					south_west = bounds.getSouthWest();
					north_east = bounds.getNorthEast();
					// check if current image is set
					if (!jQuery('input[name="map_overlay_img"]').val().length) {
						var overlay_url = vbo_ground_overlay.getUrl();
						if (overlay_url) {
							// set input value
							jQuery('input[name="map_overlay_img"]').val(overlay_url.replace(vbo_marker_base_uri, ''));
							// try to set the image source (this changes from J to WP)
							if (jQuery('.image-preview-wrapper').length) {
								jQuery('.vbo-cust-geo-overlay-wrapper').find('.image-preview-wrapper').html('<img src="' + overlay_url + '" />').show();
							}
						}
					}
					// display button to copy the current map coordinates because the user may need to adjust the overlay position
					jQuery('#vbo-overlay-copy-bounds').show();
				} else if (vbo_geomap !== null) {
					// get current values from geo map
					var bounds = vbo_geomap.getBounds();
					south_west = bounds.getSouthWest();
					north_east = bounds.getNorthEast();
				}
				if (south_west !== null && north_east !== null) {
					jQuery('#vbo-geo-map-overlay-south').val(south_west.lat());
					jQuery('#vbo-geo-map-overlay-west').val(south_west.lng());
					jQuery('#vbo-geo-map-overlay-north').val(north_east.lat());
					jQuery('#vbo-geo-map-overlay-east').val(north_east.lng());
				}
				// turn on flag for editing ground overlay
				vbo_doing_ground_overlay = true;
				// set proper modal title
				jQuery('#vbo-modal-geomap-title').html('<?php VikBookingIcons::e('images'); ?> ' + Joomla.JText._('VBO_GEO_MAP_GOVERLAY'));
				// open modal
				jQuery('.vbo-cust-geo-marker-wrapper').hide();
				jQuery('.vbo-cust-geo-overlay-wrapper').show();
				vboOpenModalGeoMap();
				// lower the z-index of our modal to allow the media-manager modal to be displayed on top
				jQuery('.vbo-modal-overlay-block-geomap').addClass('vbo-modal-overlay-block-geomap-lowerzindex');
			} else {
				// unset the ground overlay, if any
				if (vbo_ground_overlay !== null) {
					vbo_ground_overlay.setMap(null);
					vbo_ground_overlay = null;
				}
				// hide edit button
				jQuery('#vbo-goverlay-edit').hide();
				// restore the original z-index of our modal
				jQuery('.vbo-modal-overlay-block-geomap').removeClass('vbo-modal-overlay-block-geomap-lowerzindex');
				// close modal even though it should be closed already
				hideVboModalGeoMap();
				// trigger the geo-saving event
				document.dispatchEvent(new Event('vbo-room-geosaving'));
			}
		}

		/**
		 * Applies a ground overlay image to the map.
		 */
		function vboGroundOverlayApply() {
			if (vbo_geomap === null) {
				console.error('map is null');
				return false;
			}
			var overlay_url = jQuery('input[name="map_overlay_img"]').val();
			if (!overlay_url || !overlay_url.length) {
				alert(Joomla.JText._('VBO_PLEASE_SELECT'));
				return false;
			}
			var overlay_s = jQuery('#vbo-geo-map-overlay-south').val();
			var overlay_w = jQuery('#vbo-geo-map-overlay-west').val();
			var overlay_n = jQuery('#vbo-geo-map-overlay-north').val();
			var overlay_e = jQuery('#vbo-geo-map-overlay-east').val();
			if (!overlay_s.length || !overlay_w.length || !overlay_n.length || !overlay_e.length) {
				alert(Joomla.JText._('VBO_PLEASE_SELECT'));
				return false;
			}
			var use_overlay_url = overlay_url;
			if (use_overlay_url.indexOf(vbo_marker_base_uri) < 0) {
				use_overlay_url = vbo_marker_base_uri + overlay_url;
			}
			// compose LatLngBounds object
			var overlay_bounds = new google.maps.LatLngBounds(
				new google.maps.LatLng(parseFloat(overlay_s), parseFloat(overlay_w)),
				new google.maps.LatLng(parseFloat(overlay_n), parseFloat(overlay_e))
			);
			if (vbo_ground_overlay !== null) {
				// remove it from the map first
				vbo_ground_overlay.setMap(null);
			}
			// update ground overlay object
			vbo_ground_overlay = new google.maps.GroundOverlay(use_overlay_url, overlay_bounds);
			// set the overlay to the map
			vbo_ground_overlay.setMap(vbo_geomap);
			// turn off flag for editing ground overlay
			vbo_doing_ground_overlay = false;
			// show edit button
			jQuery('#vbo-goverlay-edit').show();
			// close the modal
			hideVboModalGeoMap();
			// trigger the geo-saving event
			document.dispatchEvent(new Event('vbo-room-geosaving'));
		}

		/**
		 * Copies the current map bounds and updates the input fields for the ground overlay coordinates.
		 */
		function vboOverlayCopyBounds() {
			if (vbo_geomap === null) {
				console.error('map is null');
				return false;
			}
			// get current values from geo map
			var bounds = vbo_geomap.getBounds();
			var south_west = bounds.getSouthWest();
			var north_east = bounds.getNorthEast();
			// set values in input fields
			jQuery('#vbo-geo-map-overlay-south').val(south_west.lat());
			jQuery('#vbo-geo-map-overlay-west').val(south_west.lng());
			jQuery('#vbo-geo-map-overlay-north').val(north_east.lat());
			jQuery('#vbo-geo-map-overlay-east').val(north_east.lng());
			var overlay_url = jQuery('input[name="map_overlay_img"]').val();
			if (overlay_url && overlay_url.length) {
				// we auto-apply the ground overlay image
				vboGroundOverlayApply();
			}
		}

		/**
		 * Prompts for confirmation for importing geo config from the selected room id.
		 */
		function vboImportGeoConfig(room_id) {
			if (!room_id) {
				return false;
			}
			if (isNaN(room_id)) {
				console.error('invalid room_id provided', room_id);
				return false;
			}
			if (confirm(Joomla.JText._('VBO_GEO_IMPORT_CONF'))) {
				var call_method = 'importRoomGeoConfig';
				jQuery.ajax({
					type: "POST",
					url: "<?php echo VikBooking::ajaxUrl('index.php?option=com_vikbooking&task=geocoding_endpoint'); ?>",
					data: {
						tmpl: "component",
						callback: call_method,
						room_id: room_id
					}
				}).done(function(response) {
					try {
						var obj_res = JSON.parse(response);
						if (!obj_res.hasOwnProperty(call_method)) {
							console.error('importRoomGeoConfig unexpected JSON response', obj_res);
						} else {
							var geo_params = obj_res[call_method];
							if (parseInt(geo_params['enabled']) < 1) {
								// should never occur, but it would break the entire code
								alert('The room selected does not have any geographical information');
								return false;
							}
							// populate internal variables
							if (!jQuery('input[name="geo_enabled"]').is(':checked')) {
								// enable geo-coding params
								jQuery('input[name="geo_enabled"]').trigger('click');
							}
							// set address
							jQuery('#geo_address').val(geo_params['address']);
							// set center coordinates
							jQuery('#geo_latitude').val(geo_params['latitude']);
							jQuery('#geo_longitude').val(geo_params['longitude']);
							// set zoom
							jQuery('#geo_zoom').val(geo_params['zoom']);
							// set map type
							jQuery('#geo_mtype').val(geo_params['mtype']);
							// set height
							jQuery('#geo_height').val(geo_params['height']);
							jQuery('#vbo-geo-map').css('height', geo_params['height'] + 'px');
							// map markers type
							jQuery('#geo_markers_multi').val(geo_params['markers_multi']);
							// ground overlay
							if (parseInt(geo_params['goverlay']) > 0) {
								jQuery('input[name="geo_goverlay"]').prop('checked', true);
								jQuery('#vbo-goverlay-edit').show();
							} else {
								jQuery('input[name="geo_goverlay"]').prop('checked', false);
								jQuery('#vbo-goverlay-edit').hide();
							}
							// main room marker hidden data
							jQuery('#geo_marker_lat').val(geo_params['marker_lat']);
							jQuery('#geo_marker_lng').val(geo_params['marker_lng']);
							jQuery('#geo_marker_hide').val(geo_params['marker_hide']);
							// main room marker position object
							vbo_geomarker_room_pos = null;
							if (parseInt(geo_params['marker_hide']) < 1 && geo_params['marker_lat'].length && geo_params['marker_lng'].length) {
								vbo_geomarker_room_pos = {
									lat: parseFloat(geo_params['marker_lat']),
									lng: parseFloat(geo_params['marker_lng'])
								};
							}
							// room units markers positions
							if (geo_params.hasOwnProperty('units_pos') && geo_params['units_pos'].hasOwnProperty('1')) {
								vbo_geomarker_units_pos = geo_params['units_pos'];
							} else {
								vbo_geomarker_units_pos = {};
							}
							// ground overlay
							if (parseInt(geo_params['goverlay']) > 0 && geo_params['overlay_img'].length && (geo_params['overlay_south'] + '').length) {
								vbo_dbground_overlay = {
									url: geo_params['overlay_img'],
									south: geo_params['overlay_south'],
									west: geo_params['overlay_west'],
									north: geo_params['overlay_north'],
									east: geo_params['overlay_east']
								};
							} else {
								vbo_dbground_overlay = null;
							}
							// let the main function build up the whole map to complete the process
							vboInitGeoMap();
						}
					} catch(err) {
						console.error('importRoomGeoConfig could not parse JSON response', err, response);
					}
				}).fail(function(err) {
					console.error('importRoomGeoConfig request failed', err);
				});
			}
		}

		/**
		 * Geo map click handler.
		 */
		function vboGeoMapClickedPos(position) {
			if (vbo_geomap === null) {
				console.error('map is empty');
				return false;
			}
			if (!position) {
				console.error('position is empty');
				return false;
			}
			// calculate limits
			var multi_markers = jQuery('#geo_markers_multi').val();
			var room_units = jQuery('#room_units').val();
			var tot_markers = multi_markers > 0 && room_units > 1 ? room_units : 1;
			tot_markers = parseInt(tot_markers);
			// iterate through markers
			for (var i = 1; i <= tot_markers; i++) {
				if (!vbo_geomarker_units_pos.hasOwnProperty(i)) {
					// marker for this index is missing, add it to given position
					vboManageRoomMarker(i, position);
					break;
				}
			}
			// if we reach this point it means all room units have got a marker
			return true;
		}

		/**
		 * Generates the HTML content for the units marker infowindow.
		 */
		function vboGenerateInfoMarkerContent(index, marker_title) {
			marker_title = marker_title ? marker_title : Joomla.JText._('VBODISTFEATURERUNIT') + (index + '');
			var infowin_cont = '';
			infowin_cont += '<div class="vbo-geomarker-infowin-wrap">';
			infowin_cont += '	<h4>' + marker_title + '</h4>';
			infowin_cont += '	<div class="vbo-geomarker-infowin-inner">';
			infowin_cont += '		<button type="button" class="btn btn-primary" onclick="vboCustomizeMarker(' + index + ');"><?php VikBookingIcons::e('palette'); ?> ' + Joomla.JText._('VBO_GEO_CUSTOMIZE_MARKER') + '</button>';
			infowin_cont += '		<button type="button" class="btn btn-danger" onclick="vboRemoveMarker(' + index + ');"><?php VikBookingIcons::e('trash'); ?> ' + Joomla.JText._('VBMAINPAYMENTSDEL') + '</button>';
			infowin_cont += '	</div>';
			infowin_cont += '</div>';
			
			return infowin_cont;
		}

		/**
		 * Generates the HTML content for the main room (base address) marker infowindow.
		 */
		function vboGenerateMainInfoMarkerContent() {
			var infowin_cont = '';
			infowin_cont += '<div class="vbo-geomarker-infowin-wrap vbo-geomarker-address-infowin-wrap">';
			infowin_cont += '	<h4>' + Joomla.JText._('VBO_GEO_ADDRESS') + '</h4>';
			infowin_cont += '	<div class="vbo-geomarker-infowin-inner">';
			infowin_cont += '		<button type="button" class="btn btn-secondary" onclick="vboRemoveAddressMarker();"><?php VikBookingIcons::e('trash'); ?> ' + Joomla.JText._('VBO_GEO_HIDE_FROM_MAP') + '</button>';
			infowin_cont += '	</div>';
			infowin_cont += '</div>';
			
			return infowin_cont;
		}

		/**
		 * Saves the geo information for this room in a transient-like record.
		 * The controller will then resume this transient to store the geo params.
		 */
		function vboHandleGeoSaving() {
			if (vbo_geomap === null) {
				console.error('saving is useless as the map is null');
				return false;
			}
			// turn on flag for saving
			vbo_geomap_saving = true;
			// make a silent AJAX request to store the current room geo information
			var call_method = 'updateRoomGeoTransient';
			jQuery.ajax({
				type: "POST",
				url: "<?php echo VikBooking::ajaxUrl('index.php?option=com_vikbooking&task=geocoding_endpoint'); ?>",
				data: {
					tmpl: "component",
					callback: call_method,
					room_id: <?php echo count($this->row) ? $this->row['id'] : '0'; ?>,
					geo_enabled: (jQuery('input[name="geo_enabled"]').is(':checked') ? 1 : 0),
					geo_address: jQuery('#geo_address').val(),
					geo_latitude: jQuery('#geo_latitude').val(),
					geo_longitude: jQuery('#geo_longitude').val(),
					geo_zoom: jQuery('#geo_zoom').val(),
					geo_mtype: jQuery('#geo_mtype').val(),
					geo_height: jQuery('#geo_height').val(),
					geo_markers_multi: jQuery('#geo_markers_multi').val(),
					geo_marker_lat: jQuery('#geo_marker_lat').val(),
					geo_marker_lng: jQuery('#geo_marker_lng').val(),
					geo_marker_hide: jQuery('#geo_marker_hide').val(),
					geo_units_pos: vbo_geomarker_units_pos,
					geo_goverlay: (jQuery('input[name="geo_goverlay"]').is(':checked') ? 1 : 0),
					geo_overlay_img: (jQuery('input[name="map_overlay_img"]').val().length ? (vbo_marker_base_uri + jQuery('input[name="map_overlay_img"]').val()) : ''),
					geo_overlay_south: jQuery('#vbo-geo-map-overlay-south').val(),
					geo_overlay_west: jQuery('#vbo-geo-map-overlay-west').val(),
					geo_overlay_north: jQuery('#vbo-geo-map-overlay-north').val(),
					geo_overlay_east: jQuery('#vbo-geo-map-overlay-east').val()
				}
			}).done(function(response) {
				try {
					var obj_res = JSON.parse(response);
					if (!obj_res.hasOwnProperty(call_method)) {
						console.error('updateRoomGeoTransient unexpected JSON response', obj_res);
					} else {
						// console.log(obj_res[call_method]);
					}
				} catch(err) {
					console.error('updateRoomGeoTransient could not parse JSON response', err, response);
				}
				// turn off flag for saving
				vbo_geomap_saving = false;
			}).fail(function(err) {
				console.error('updateRoomGeoTransient request failed', err);
			});
			//
		}

		jQuery(document).ready(function() {
			
			// display geocoding params, if necessary
			vboToggleGeoParams();

			// init geo map with current markers, if any
			vboInitGeoMap();

			// listener to the geocode-address change event with debounce handler.
			document.addEventListener('vbo-geocode-address', vboDebounceEvent(vboAddressGeocoding, 500));

			// listener to the change-address event
			jQuery('#geo_address').keyup(function(e) {
				if (e.keyCode && e.keyCode == 13) {
					// if enter is pressed, launch the function immediately
					vboAddressGeocoding();
					return;
				}
				// trigger the geocode-address change event debounced
				document.dispatchEvent(new Event('vbo-geocode-address'));
			});

			// listener to the change-center event
			jQuery('#geo_latitude, #geo_longitude').change(function() {
				var lat = jQuery('#geo_latitude').val(),
					lng = jQuery('#geo_longitude').val();
				vboSetGeoMapCenter(lat, lng);
				// trigger the geo-saving event
				document.dispatchEvent(new Event('vbo-room-geosaving'));
			});

			// listener to the change-zoom event
			jQuery('#geo_zoom').change(function() {
				var zoom = parseFloat(jQuery(this).val());
				if (vbo_geomap === null || isNaN(zoom)) {
					return;
				}
				vbo_geomap.setZoom(zoom);
				// trigger the geo-saving event
				document.dispatchEvent(new Event('vbo-room-geosaving'));
			});

			// listener to the change-map-type event
			jQuery('#geo_mtype').change(function() {
				var mtype = jQuery(this).val();
				if (vbo_geomap === null) {
					return;
				}
				vbo_geomap.setMapTypeId(mtype);
				// trigger the geo-saving event
				document.dispatchEvent(new Event('vbo-room-geosaving'));
			});

			// listener to address suggestion
			jQuery('#geo_address_formatted').click(function() {
				var address_format = jQuery(this).text();
				if (address_format.length < 2) {
					return;
				}
				jQuery('#geo_address').val(address_format);
				jQuery(this).text('');
				// trigger the geo-saving event
				document.dispatchEvent(new Event('vbo-room-geosaving'));
			});

			// listener to map height change
			jQuery('#geo_height').change(function() {
				jQuery('#vbo-geo-map').css('height', jQuery(this).val() + 'px');
				// trigger the geo-saving event
				document.dispatchEvent(new Event('vbo-room-geosaving'));
			});

			// listener to room main name to update the marker's title
			jQuery('input[name="cname"]').change(function() {
				if (vbo_geomarker_room === null) {
					return;
				}
				vbo_geomarker_room.setTitle(jQuery(this).val());
			});

			// listener to map markers quantity change
			jQuery('#geo_markers_multi').change(function() {
				var mmtype = jQuery(this).val();
				if (parseInt(mmtype) == 1) {
					// one for each sub-unit, make sure the room has got enough units
					var now_units = jQuery('#room_units').val();
					if (now_units < 2) {
						alert(Joomla.JText._('VBO_GEO_MARKERS_MULTI_ERRUNIT'));
						jQuery('#geo_markers_multi').val(0);
					}
				}
				// update map markers
				vboPopulateMapMarkers();
				// trigger the geo-saving event
				document.dispatchEvent(new Event('vbo-room-geosaving'));
			});

			// register colorpicker
			jQuery('.vbo-inspector-colorpicker').ColorPicker({
				color: '#ffffff',
				onShow: function (colpkr, el) {
					if (!jQuery('.vbo-cust-geo-marker-svg[data-markerapply="1"]').length) {
						return false;
					}
					var cur_color = jQuery(el).css('backgroundColor');
					jQuery(el).ColorPickerSetColor(vboRgb2Hex(cur_color));
					jQuery(colpkr).show();
					return false;
				},
				onChange: function (hsb, hex, rgb, el) {
					var element = jQuery(el);
					element.css('backgroundColor', '#'+hex);
					if (jQuery('.vbo-cust-geo-marker-svg[data-markerapply="1"]').length) {
						jQuery('.vbo-cust-geo-marker-svg[data-markerapply="1"]').first().find('svg').css('fill', '#'+hex);
					}
					jQuery('#vbo-geo-marker-fillcolor').val('#'+hex);
				},
				onSubmit: function(hsb, hex, rgb, el) {
					var element = jQuery(el);
					element.css('backgroundColor', '#'+hex);
					if (jQuery('.vbo-cust-geo-marker-svg[data-markerapply="1"]').length) {
						jQuery('.vbo-cust-geo-marker-svg[data-markerapply="1"]').first().find('svg').css('fill', '#'+hex);
					}
					jQuery('#vbo-geo-marker-fillcolor').val('#'+hex);
					element.ColorPickerHide();
				}
			});

			// register opacity change
			jQuery('#vbo-geo-marker-opacity').change(function() {
				var opacity = jQuery(this).val();
				if (!jQuery('.vbo-cust-geo-marker-svg[data-markerapply="1"]').length) {
					return;
				}
				jQuery('.vbo-cust-geo-marker-svg[data-markerapply="1"]').first().find('svg').css('opacity', opacity);
			});

			// register select2 for importing geo configuration from another room
			if (jQuery('#vbo-geo-importfrom').length) {
				jQuery('#vbo-geo-importfrom').select2({
					placeholder: Joomla.JText._('VBO_GEO_IMPORT_FROM'),
					width: '190px',
					allowClear: true
				});
			}

			/**
			 * Add event listener to the geo-saving event with debounce handler.
			 */
			document.addEventListener('vbo-room-geosaving', vboDebounceEvent(vboHandleGeoSaving, 500));

		});
	</script>
	<?php
}
