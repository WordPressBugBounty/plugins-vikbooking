<?php
/**
 * @package     VikBooking
 * @subpackage  com_vikbooking
 * @author      Alessio Gaggii - E4J srl
 * @copyright   Copyright (C) 2025 e4j - E4J srl. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 * @link        https://vikwp.com
 */

defined('ABSPATH') or die('No script kiddies please!');

/**
 * Room geocoding information.
 */

// JSON-decode room parameters
$rparams = json_decode($this->room['params'], true);

$geo = VikBooking::getGeocodingInstance();
if ($geo->isSupported()) {
	// load assets
	$geo->loadAssets();
	// get all geo params
	$geo_params = $geo->getRoomGeoParams($rparams);
	if (is_object($geo_params) && isset($geo_params->enabled) && $geo_params->enabled) {
		$main_marker_pos = null;
		if (empty($geo_params->marker_hide) && !empty($geo_params->marker_lat) && !empty($geo_params->marker_lng)) {
			// prepare main marker (for base address) object for js
			$main_marker_pos = new stdClass;
			$main_marker_pos->lat = (float)$geo_params->marker_lat;
			$main_marker_pos->lng = (float)$geo_params->marker_lng;
		}
		$current_units_pos = new stdClass;
		if (isset($geo_params->units_pos) && is_object($geo_params->units_pos) && count(get_object_vars($geo_params->units_pos))) {
			$current_units_pos = $geo_params->units_pos;
		}
		$current_goverlay = null;
		if ((int)$geo_params->goverlay > 0 && !empty($geo_params->overlay_img) && !empty($geo_params->overlay_south)) {
			// ground overlay is available
			$current_goverlay = new stdClass;
			$current_goverlay->url = $geo_params->overlay_img;
			$current_goverlay->south = (float)$geo_params->overlay_south;
			$current_goverlay->west = (float)$geo_params->overlay_west;
			$current_goverlay->north = (float)$geo_params->overlay_north;
			$current_goverlay->east = (float)$geo_params->overlay_east;
		}
		$room_units_features = new stdClass;
		if ((int)$geo_params->markers_multi > 0 && $this->room['units'] > 0) {
			// try to use the distinctive features
			$room_features = VikBooking::getRoomParam('features', $rparams);
			$room_features = !is_array($room_features) ? [] : $room_features;
			foreach ($room_features as $rindex => $rfeatures) {
				if (!is_array($rfeatures) || !$rfeatures) {
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
					$room_units_features->{$rindex} = $tn_featname . ' ' . $featval;
					break;
				}
			}
		}
		?>
<div class="vbo-room-details-geo-wrapper">
	<h4><?php echo JText::translate('VBO_LISTING_WHERE_YOULLBE'); ?></h4>
	<div class="vbo-geo-wrapper">
		<div id="vbo-geo-map" style="width: 100%; height: <?php echo $geo->getRoomGeoParams($rparams, 'height', 300); ?>px;"></div>
	</div>
</div>

<script type="text/javascript">
	/**
	 * Define global scope vars
	 */
	var vbo_geomap = null,
		vbo_geomarker_room = null,
		vbo_geomarker_room_pos = <?php echo is_object($main_marker_pos) ? json_encode($main_marker_pos) : 'null'; ?>,
		vbo_info_marker_room = null,
		vbo_geomarker_units = {},
		vbo_geomarker_units_pos = <?php echo json_encode($current_units_pos); ?>,
		vbo_info_markers = {},
		vbo_info_markers_helper = <?php echo count(get_object_vars($room_units_features)) ? json_encode($room_units_features) : '{}'; ?>,
		vbo_ground_overlay = null,
		vbo_dbground_overlay = <?php echo is_object($current_goverlay) ? json_encode($current_goverlay) : 'null'; ?>;

	/**
	 * Generates the HTML content for the units marker infowindow.
	 */
	function vboGenerateInfoMarkerContent(index, marker_title) {
		marker_title = marker_title ? marker_title : Joomla.JText._('VBODISTFEATURERUNIT') + (index + '');
		var infowin_cont = '';
		infowin_cont += '<div class="vbo-geomarker-infowin-wrap">';
		infowin_cont += '	<div class="vbo-geomarker-room-title">' + marker_title + '</div>';
		infowin_cont += '</div>';
		
		return infowin_cont;
	}

	/**
	 * Generates the HTML content for the main room (base address) marker infowindow.
	 */
	function vboGenerateMainInfoMarkerContent() {
		var infowin_cont = '';
		infowin_cont += '<div class="vbo-geomarker-infowin-wrap vbo-geomarker-address-infowin-wrap">';
		infowin_cont += '	<div class="vbo-geomarker-room-title">' + Joomla.JText._('VBO_GEO_ADDRESS') + '</div>';
		infowin_cont += '</div>';
		
		return infowin_cont;
	}

	/**
	 * Given all the current positions, adds the current markers to the map.
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
		// calculate limits
		var multi_markers = <?php echo (int)$geo_params->markers_multi; ?>;
		var room_units = <?php echo $this->room['units']; ?>;
		var tot_markers = multi_markers > 0 && room_units > 1 ? room_units : 1;
		tot_markers = parseInt(tot_markers);
		// iterate through markers to add and display
		for (var i = 1; i <= tot_markers; i++) {
			var marker_options = null;
			var marker_title = Joomla.JText._('VBODISTFEATURERUNIT') + (i + '');
			if (tot_markers === 1) {
				marker_title = '<?php echo addslashes($this->room['name']); ?>';
			} else if (vbo_info_markers_helper.hasOwnProperty(i)) {
				marker_title = vbo_info_markers_helper[i];
			}
			if (vbo_geomarker_units_pos.hasOwnProperty(i)) {
				// marker index saved
				marker_options = {
					draggable: false,
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
				});
				// add unit marker to map
				var vbo_geomarker_runit = new google.maps.Marker(marker_options);
				// add listener to marker
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
		}
	}

	/**
	 * Fires when the document is ready. Renders the entire map.
	 */
	function vboInitGeoMap() {
		// default map options
		var def_map_options = {
			center: new google.maps.LatLng(<?php echo $geo_params->latitude; ?>, <?php echo $geo_params->longitude; ?>),
			zoom: <?php echo (int)$geo_params->zoom; ?>,
			mapTypeId: '<?php echo $geo_params->mtype; ?>',
			mapTypeControl: false
		};
		// initialize Map
		vbo_geomap = new google.maps.Map(document.getElementById('vbo-geo-map'), def_map_options);
		// set current default marker for main room
		if (vbo_geomarker_room_pos !== null) {
			// create infowindow
			vbo_info_marker_room = new google.maps.InfoWindow({
				content: vboGenerateMainInfoMarkerContent(),
			});
			// add map marker for base room-type
			vbo_geomarker_room = new google.maps.Marker({
				draggable: false,
				map: vbo_geomap,
				position: {
					lat: parseFloat(vbo_geomarker_room_pos.lat),
					lng: parseFloat(vbo_geomarker_room_pos.lng)
				},
				title: '<?php echo addslashes($this->room['name']); ?>'
			});
			// add listener to marker
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

	jQuery(function() {

		// init geo map with current markers, if any
		vboInitGeoMap();

	});
</script>
		<?php
	}
}
