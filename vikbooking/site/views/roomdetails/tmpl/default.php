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

$room = $this->room;
$busy = $this->busy;
$seasons_cal = $this->seasons_cal;
$promo_season = $this->promo_season;
$vbo_tn = $this->vbo_tn;

// register lang vars for JS
JText::script('VBODISTFEATURERUNIT');
JText::script('VBO_GEO_ADDRESS');
JText::script('VBSELPRDATE');

$vbo_app = VikBooking::getVboApplication();
$vbo_app->loadDatePicker();

$is_mobile = VikBooking::detectUserAgent(false, false);

$session = JFactory::getSession();
$currencysymb = VikBooking::getCurrencySymb();
$showpartlyres = VikBooking::showPartlyReserved();
$showcheckinoutonly = VikBooking::showStatusCheckinoutOnly();
$vbdateformat = VikBooking::getDateFormat();
if ($vbdateformat == "%d/%m/%Y") {
	$df = 'd/m/Y';
} elseif ($vbdateformat == "%m/%d/%Y") {
	$df = 'm/d/Y';
} else {
	$df = 'Y/m/d';
}
$datesep = VikBooking::getDateSeparator();
$inonout_allowed = true;
$timeopst = VikBooking::getTimeOpenStore();
if (is_array($timeopst)) {
	if ($timeopst[0] < $timeopst[1]) {
		//check-in not allowed on a day where there is already a check out (no arrivals/depatures on the same day)
		$inonout_allowed = false;
	}
}

$carats = VikBooking::getRoomCaratOriz($room['idcarat'], $vbo_tn);
$pitemid = VikRequest::getInt('Itemid', '', 'request');

$min_days_advance = VikBooking::getMinDaysAdvance();
$max_date_future  = VikBooking::getMaxDateFuture($room['id']);

$timeopst = VikBooking::getTimeOpenStore();
if (is_array($timeopst)) {
	$opent = VikBooking::getHoursMinutes($timeopst[0]);
	$closet = VikBooking::getHoursMinutes($timeopst[1]);
	$hcheckin = $opent[0];
	$mcheckin = $opent[1];
	$hcheckout = $closet[0];
	$mcheckout = $closet[1];
} else {
	$hcheckin = 0;
	$mcheckin = 0;
	$hcheckout = 0;
	$mcheckout = 0;
}

$document = JFactory::getDocument();
$document->addStyleSheet(VBO_SITE_URI.'resources/jquery.fancybox.css');
JHtml::fetch('script', VBO_SITE_URI.'resources/jquery.fancybox.js');
$navdecl = '
jQuery(function() {
	jQuery(".vbomodalframe").fancybox({
		"helpers": {
			"overlay": {
				"locked": false
			}
		},
		"width": "75%",
		"height": "75%",
	    "autoScale": false,
	    "transitionIn": "none",
		"transitionOut": "none",
		"padding": 0,
		"type": "iframe"
	});
});';
$document->addScriptDeclaration($navdecl);

$gallery_data = array();
if (strlen($room['moreimgs']) > 0) {
	$document->addStyleSheet(VBO_SITE_URI.'resources/vikfxgallery.css');
	JHtml::fetch('script', VBO_SITE_URI.'resources/vikfxgallery.js');
	$moreimages = explode(';;', $room['moreimgs']);
	$imgcaptions = json_decode($room['imgcaptions'], true);
	$usecaptions = is_array($imgcaptions);
	foreach ($moreimages as $iind => $mimg) {
		if (empty($mimg)) {
			continue;
		}
		$img_alt = $usecaptions && !empty($imgcaptions[$iind]) ? $imgcaptions[$iind] : substr($mimg, 0, strpos($mimg, '.'));
		array_push($gallery_data, array(
			'big' => VBO_SITE_URI . 'resources/uploads/big_' . $mimg,
			'thumb' => VBO_SITE_URI . 'resources/uploads/thumb_' . $mimg,
			'alt' => $img_alt,
			'caption' => $usecaptions && !empty($imgcaptions[$iind]) ? $imgcaptions[$iind] : "",
		));
	}
	if (count($gallery_data)) {
		$vikfx = '
jQuery(function() {
	window["vikfxgallery"] = jQuery(".vikfx-gallery a").vikFxGallery();
	jQuery(".vikfx-gallery-previous-image").click(function() {
		if (typeof window["vikfxgallery"] !== "undefined") {
			window["vikfxgallery"].open();
		}
	});
	jQuery(".vikfx-gallery-next-image").click(function() {
		if (typeof window["vikfxgallery"] !== "undefined") {
			window["vikfxgallery"].open();
		}
	});
});';
		$document->addScriptDeclaration($vikfx);
	}
}
?>
<div class="vbrdetboxtop">

	<div class="vblistroomnamediv">
		<h3><?php echo $room['name']; ?></h3>
		<span class="vblistroomcat"><?php echo VikBooking::sayCategory($room['idcat'], $vbo_tn); ?></span>
	</div>
	
	<div class="vbroomimgdesc">
	<?php 
	if (!empty($room['img'])) {
		?>
		<div class="vikfx-gallery-container vikfx-roomdetails-gallery-container">
			<div class="vikfx-gallery-fade-container">
				<img src="<?php echo VBO_SITE_URI; ?>resources/uploads/<?php echo $room['img']; ?>" alt="<?php echo htmlspecialchars($room['name']); ?>" class="vikfx-gallery-image vblistimg"/>
			<?php
			if (count($gallery_data)) {
				?>
				<div class="vikfx-gallery-navigation-controls">
					<div class="vikfx-gallery-navigation-controls-prevnext">
						<a href="javascript: void(0);" class="vikfx-gallery-previous-image"><?php VikBookingIcons::e('chevron-left'); ?></a>
						<a href="javascript: void(0);" class="vikfx-gallery-next-image"><?php VikBookingIcons::e('chevron-right'); ?></a>
					</div>
				</div>
				<?php
			}
			?>
			</div>
		<?php
		if (count($gallery_data)) {
			?>
			<div class="vikfx-gallery">
			<?php
			foreach ($gallery_data as $mimg) {
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
	<?php
	}
	?>
	</div>

	<div class="vbo-rdet-descprice-block">
		<div class="vbo-rdet-desc-cont">
	<?php
	if (VBOPlatformDetection::isWordPress()) {
		/**
		 * @wponly 	we try to parse any shortcode inside the HTML description of the room
		 */
		echo do_shortcode(wpautop($room['info']));
	} else {
		//BEGIN: Joomla Content Plugins Rendering
		JPluginHelper::importPlugin('content');
		$myItem = JTable::getInstance('content');
		$myItem->text = $room['info'];
		$objparams = array();
		if (class_exists('JEventDispatcher')) {
			$dispatcher = JEventDispatcher::getInstance();
			$dispatcher->trigger('onContentPrepare', array('com_vikbooking.roomdetails', &$myItem, &$objparams, 0));
		} else {
			/**
			 * @joomla4only
			 */
			$dispatcher = JFactory::getApplication();
			if (method_exists($dispatcher, 'triggerEvent')) {
				$dispatcher->triggerEvent('onContentPrepare', array('com_vikbooking.roomdetails', &$myItem, &$objparams, 0));
			}
		}
		$room['info'] = $myItem->text;
		//END: Joomla Content Plugins Rendering
		echo $room['info'];
	}

	if ((bool)VikBooking::getRoomParam('reqinfo', $room['params'])) {
		//Request Information form
		$reqinfotoken = rand(1, 999);
		$session->set('vboreqinfo'.$room['id'], $reqinfotoken);
		$cur_user = JFactory::getUser();
		$cur_email = '';
		if (property_exists($cur_user, 'email') && !empty($cur_user->email)) {
			$cur_email = $cur_user->email;
		}
		?>
			<div class="vbo-reqinfo-cont">
				<span><a href="Javascript: void(0);" onclick="vboShowRequestInfo();" class="vbo-reqinfo-opener vbo-pref-color-btn"><?php echo JText::translate('VBOROOMREQINFOBTN'); ?></a></span>
			</div>
			<div id="vbdialog-overlay" style="display: none;">
				<a class="vbdialog-overlay-close" href="javascript: void(0);"></a>
				<div class="vbdialog-inner vbdialog-reqinfo">
					<h3><?php echo JText::sprintf('VBOROOMREQINFOTITLE', $room['name']); ?></h3>
					<form action="<?php echo JRoute::rewrite('index.php?option=com_vikbooking&task=reqinfo'.(!empty($pitemid) ? '&Itemid='.$pitemid : '')); ?>" method="post">
						<input type="hidden" name="roomid" value="<?php echo $room['id']; ?>" />
						<input type="hidden" name="reqinfotoken" value="<?php echo $reqinfotoken; ?>" />
						<input type="hidden" name="Itemid" value="<?php echo $pitemid; ?>" />
						<div class="vbdialog-reqinfo-formcont">
							<div class="vbdialog-reqinfo-formentry">
								<label for="reqname"><?php echo JText::translate('VBOROOMREQINFONAME'); ?></label>
								<input type="text" name="reqname" id="reqname" value="" placeholder="<?php echo JText::translate('VBOROOMREQINFONAME'); ?>" required />
							</div>
							<div class="vbdialog-reqinfo-formentry">
								<label for="reqemail"><?php echo JText::translate('VBOROOMREQINFOEMAIL'); ?></label>
								<input type="email" name="reqemail" id="reqemail" value="<?php echo $cur_email; ?>" placeholder="<?php echo JText::translate('VBOROOMREQINFOEMAIL'); ?>" required />
							</div>
							<div class="vbdialog-reqinfo-formentry">
								<label for="reqmess"><?php echo JText::translate('VBOROOMREQINFOMESS'); ?></label>
								<textarea name="reqmess" id="reqmess" placeholder="<?php echo JText::translate('VBOROOMREQINFOMESS'); ?>"></textarea>
							</div>
						<?php
						if (count($this->terms_fields)) {
							foreach ($this->terms_fields as $k => $terms_field) {
								if (!empty($terms_field['poplink'])) {
									$fname = "<a href=\"" . $terms_field['poplink'] . "\" id=\"vbof{$k}\" rel=\"{handler: 'iframe', size: {x: 750, y: 600}}\" target=\"_blank\" class=\"vbomodalframe\">" . JText::translate($terms_field['name']) . "</a>";
								} else {
									$fname = "<label id=\"vbof{$k}\" for=\"vbof-inp{$k}\" style=\"display: inline-block;\">" . JText::translate($terms_field['name']) . "</label>";
								}
								?>
								<div class="vbdialog-reqinfo-formentry vbdialog-reqinfo-formentry-ckbox">
									<?php echo $fname; ?>
									<input type="checkbox" name="vbof" id="vbof-inp<?php echo $k; ?>" value="<?php echo JText::translate('VBYES'); ?>" required />
								</div>
								<?php
							}
						} else {
							?>
							<div class="vbdialog-reqinfo-formentry vbdialog-reqinfo-formentry-ckbox">
								<label id="vbof" for="vbof-inp" style="display: inline-block;"><?php echo JText::translate('ORDER_TERMSCONDITIONS'); ?></label>
								<input type="checkbox" name="vbof" id="vbof-inp" value="<?php echo JText::translate('VBYES'); ?>" required />
							</div>
							<?php
						}
						if ($vbo_app->isCaptcha()) {
							?>
							<div class="vbdialog-reqinfo-formentry vbdialog-reqinfo-formentry-captcha">
								<div><?php echo $vbo_app->reCaptcha(); ?></div>
							</div>
							<?php
						}
						?>
							<div class="vbdialog-reqinfo-formentry vbdialog-reqinfo-formsubmit">
								<button type="submit" class="btn vbo-pref-color-btn"><?php echo JText::translate('VBOROOMREQINFOSEND'); ?></button>
							</div>
						</div>
					</form>
				</div>
			</div>

			<script type="text/javascript">
			var vbdialog_on = false;
			function vboShowRequestInfo() {
				jQuery("#vbdialog-overlay").fadeIn();
				vbdialog_on = true;
			}
			function vboHideRequestInfo() {
				jQuery("#vbdialog-overlay").fadeOut();
				vbdialog_on = false;
			}
			jQuery(function() {
				jQuery(document).mouseup(function(e) {
					if (!vbdialog_on) {
						return false;
					}
					var vbdialog_cont = jQuery(".vbdialog-inner");
					if (!vbdialog_cont.is(e.target) && vbdialog_cont.has(e.target).length === 0) {
						vboHideRequestInfo();
					}
				});
				jQuery(document).keyup(function(e) {
					if (e.keyCode == 27 && vbdialog_on) {
						vboHideRequestInfo();
					}
				});
			});
			</script>
		<?php
		//
	}
	?>
		</div>
	<?php
	$custprice = VikBooking::getRoomParam('custprice', $room['params']);
	$custpricetxt = VikBooking::getRoomParam('custpricetxt', $room['params']);
	$custpricetxt = empty($custpricetxt) ? '' : JText::translate($custpricetxt);
	$custpricesubtxt = VikBooking::getRoomParam('custpricesubtxt', $room['params']);
	if ($room['cost'] > 0 || !empty($custprice)) {
		?>
		<div class="vb_detcostroomdet">
			<div class="vb_detcostroom">
				<div class="vblistroomnamedivprice">
					<div class="vblistroomname vbo-pref-color-text">
						<span class="vbliststartfromrdet"><?php echo JText::translate('VBLISTSFROM'); ?></span>
						<span class="room_cost"><span class="vbo_currency"><?php echo $currencysymb; ?></span> <span class="vbo_price"><?php echo (!empty($custprice) ? VikBooking::numberFormat($custprice) : VikBooking::numberFormat($room['cost'])); ?></span></span>
					<?php
					if (!empty($custpricetxt)) {
						?>
						<span class="roomcustcostlabel"><?php echo $custpricetxt; ?></span>
						<?php
					}
					if (!empty($custpricesubtxt)) {
						?>
						<div class="roomcustcost-subtxt"><?php echo $custpricesubtxt; ?></div>
						<?php
					}
					?>
					</div>
				</div>
			</div>
		</div>
	<?php
	}
	?>
	</div>
	<?php

	/**
	 * Room geocoding information.
	 * 
	 * @since 	1.14 (J) - 1.4.0 (WP)
	 */
	$geo = VikBooking::getGeocodingInstance();
	if ($geo->isSupported()) {
		// load assets
		$geo->loadAssets();
		// get all geo params
		$rparams = json_decode($room['params']);
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
			if ((int)$geo_params->markers_multi > 0 && $room['units'] > 0) {
				// try to use the distinctive features
				$room_features = VikBooking::getRoomParam('features', $room['params']);
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
						$room_units_features->{$rindex} = $tn_featname . ' ' . $featval;
						break;
					}
				}
			}
			?>
	<div class="vbo-room-details-geo-wrapper">
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
			var room_units = <?php echo $room['units']; ?>;
			var tot_markers = multi_markers > 0 && room_units > 1 ? room_units : 1;
			tot_markers = parseInt(tot_markers);
			// iterate through markers to add and display
			for (var i = 1; i <= tot_markers; i++) {
				var marker_options = null;
				var marker_title = Joomla.JText._('VBODISTFEATURERUNIT') + (i + '');
				if (tot_markers === 1) {
					marker_title = '<?php echo addslashes($room['name']); ?>';
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
					title: '<?php echo addslashes($room['name']); ?>'
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
	//

	if (!empty($carats)) {
		?>
	<div class="room_carats">
		<h4><?php echo JText::translate('VBCHARACTERISTICS'); ?></h4>
		<?php echo $carats; ?>
	</div>
	<?php
	}
?>
</div>

<div class="vbo-roomdet-calscontainer">
	<div class="vbo-roomdet-calscontainer-inner">
		<?php
		if (count($seasons_cal)) {
			// seasons calendar
			$price_types_show = intval(VikBooking::getRoomParam('seasoncal_prices', $room['params'])) == 1 ? false : true;
			$los_show = intval(VikBooking::getRoomParam('seasoncal_restr', $room['params'])) == 1 ? true : false;
			?>
		<div class="vbo-seasonscalendar-cont">
			<h4><?php echo JText::translate('VBOSEASONSCALENDAR'); ?></h4>
			<div class="table-responsive">
				<table class="table vbo-seasons-calendar-table">
					<tr class="vbo-seasons-calendar-nightsrow">
						<td>&nbsp;</td>
					<?php
					foreach ($seasons_cal['offseason'] as $numnights => $ntars) {
						?>
						<td><span><?php echo JText::sprintf(($numnights > 1 ? 'VBOSEASONCALNUMNIGHTS' : 'VBOSEASONCALNUMNIGHT'), $numnights); ?></span></td>
						<?php
					}
					?>
					</tr>
					<tr class="vbo-seasons-calendar-offseasonrow">
						<td>
							<span class="vbo-seasons-calendar-offseasonname"><?php echo JText::translate('VBOSEASONSCALOFFSEASONPRICES'); ?></span>
						</td>
					<?php
					foreach ($seasons_cal['offseason'] as $numnights => $tars) {
						?>
						<td>
							<div class="vbo-seasons-calendar-offseasoncosts">
								<?php
								foreach ($tars as $tar) {
									?>
								<div class="vbo-seasons-calendar-offseasoncost">
									<?php
									if ($price_types_show) {
									?>
									<span class="vbo-seasons-calendar-pricename"><?php echo $tar['name']; ?></span>
									<?php
									}
									?>
									<span class="vbo-seasons-calendar-pricecost">
										<span class="vbo_currency"><?php echo $currencysymb; ?></span><span class="vbo_price"><?php echo VikBooking::numberFormat($tar['cost']); ?></span>
									</span>
								</div>
									<?php
									if (!$price_types_show) {
										break;
									}
								}
								?>
							</div>
						</td>
						<?php
					}
					?>
					</tr>
				<?php
				foreach ($seasons_cal['seasons'] as $s_id => $s) {
					$restr_diff_nights = array();
					if ($los_show && array_key_exists($s_id, $seasons_cal['restrictions'])) {
						$restr_diff_nights = VikBooking::compareSeasonRestrictionsNights($seasons_cal['restrictions'][$s_id]);
					}
					?>
					<tr class="vbo-seasons-calendar-seasonrow">
						<td>
							<div class="vbo-seasons-calendar-seasondates">
								<span class="vbo-seasons-calendar-seasonfrom"><?php echo date(str_replace("/", $datesep, $df), $s['from_ts']); ?></span>
								<span class="vbo-seasons-calendar-seasondates-separe">-</span>
								<span class="vbo-seasons-calendar-seasonto"><?php echo date(str_replace("/", $datesep, $df), $s['to_ts']); ?></span>
							</div>
							<span class="vbo-seasons-calendar-seasonname"><?php echo $s['spname']; ?></span>
					<?php
					if ($los_show && array_key_exists($s_id, $seasons_cal['restrictions']) && count($restr_diff_nights) == 0) {
						//Season Restrictions
						$season_restrictions = array();
						foreach ($seasons_cal['restrictions'][$s_id] as $restr) {
							$season_restrictions = $restr;
							break;
						}
						?>
							<div class="vbo-seasons-calendar-restrictions">
						<?php
						if ($season_restrictions['minlos'] > 1) {
							?>
								<span class="vbo-seasons-calendar-restriction-minlos"><?php echo JText::translate('VBORESTRMINLOS'); ?><span class="vbo-seasons-calendar-restriction-minlos-badge"><?php echo $season_restrictions['minlos']; ?></span></span>
							<?php
						}
						if (array_key_exists('maxlos', $season_restrictions) && $season_restrictions['maxlos'] > 1) {
							?>
								<span class="vbo-seasons-calendar-restriction-maxlos"><?php echo JText::translate('VBORESTRMAXLOS'); ?><span class="vbo-seasons-calendar-restriction-maxlos-badge"><?php echo $season_restrictions['maxlos']; ?></span></span>
							<?php
						}
						if (array_key_exists('wdays', $season_restrictions) && count($season_restrictions['wdays']) > 0) {
							?>
								<div class="vbo-seasons-calendar-restriction-wdays">
									<label><?php echo JText::translate((count($season_restrictions['wdays']) > 1 ? 'VBORESTRARRIVWDAYS' : 'VBORESTRARRIVWDAY')); ?></label>
							<?php
							foreach ($season_restrictions['wdays'] as $wday) {
								?>
									<span class="vbo-seasons-calendar-restriction-wday"><?php echo VikBooking::sayWeekDay($wday); ?></span>
								<?php
							}
							?>
								</div>
							<?php
						} elseif ((array_key_exists('cta', $season_restrictions) && count($season_restrictions['cta']) > 0) || (array_key_exists('ctd', $season_restrictions) && count($season_restrictions['ctd']) > 0)) {
							if (array_key_exists('cta', $season_restrictions) && count($season_restrictions['cta']) > 0) {
								?>
								<div class="vbo-seasons-calendar-restriction-wdays vbo-seasons-calendar-restriction-cta">
									<label><?php echo JText::translate('VBORESTRWDAYSCTA'); ?></label>
								<?php
								foreach ($season_restrictions['cta'] as $wday) {
									?>
									<span class="vbo-seasons-calendar-restriction-wday"><?php echo VikBooking::sayWeekDay(str_replace('-', '', $wday)); ?></span>
									<?php
								}
								?>
								</div>
								<?php
							}
							if (array_key_exists('ctd', $season_restrictions) && count($season_restrictions['ctd']) > 0) {
								?>
								<div class="vbo-seasons-calendar-restriction-wdays vbo-seasons-calendar-restriction-ctd">
									<label><?php echo JText::translate('VBORESTRWDAYSCTD'); ?></label>
								<?php
								foreach ($season_restrictions['ctd'] as $wday) {
									?>
									<span class="vbo-seasons-calendar-restriction-wday"><?php echo VikBooking::sayWeekDay(str_replace('-', '', $wday)); ?></span>
									<?php
								}
								?>
								</div>
								<?php
							}
						}
						?>
							</div>
						<?php
					}
					?>
						</td>
					<?php
					if (array_key_exists($s_id, $seasons_cal['season_prices']) && count($seasons_cal['season_prices'][$s_id]) > 0) {
						foreach ($seasons_cal['season_prices'][$s_id] as $numnights => $tars) {
							$show_day_cost = true;
							if ($los_show && array_key_exists($s_id, $seasons_cal['restrictions']) && array_key_exists($numnights, $seasons_cal['restrictions'][$s_id])) {
								if ($seasons_cal['restrictions'][$s_id][$numnights]['allowed'] === false) {
									$show_day_cost = false;
								}
							}
							?>
						<td>
						<?php
						if ($show_day_cost) {
						?>
							<div class="vbo-seasons-calendar-seasoncosts">
							<?php
							foreach ($tars as $tar) {
								//print the types of price that are not being modified by this special price with opacity
								$not_affected = (!array_key_exists('origdailycost', $tar));
								//
								?>
								<div class="vbo-seasons-calendar-seasoncost<?php echo ($not_affected ? ' vbo-seasons-calendar-seasoncost-notaffected' : ''); ?>">
								<?php
								if ($price_types_show) {
									?>
									<span class="vbo-seasons-calendar-pricename"><?php echo $tar['name']; ?></span>
									<?php
								}
								?>
									<span class="vbo-seasons-calendar-pricecost">
										<span class="vbo_currency"><?php echo $currencysymb; ?></span><span class="vbo_price"><?php echo VikBooking::numberFormat($tar['cost']); ?></span>
									</span>
								</div>
								<?php
								if (!$price_types_show) {
									break;
								}
							}
							?>
							</div>
						<?php
						} else {
							?>
							<div class="vbo-seasons-calendar-seasoncosts-disabled"></div>
							<?php
						}
						?>
						</td>
						<?php
						}
					}
					?>
					</tr>
					<?php
				}
			?>
				</table>
			</div>
		</div>
		<?php
		// end Seasons Calendar
		}

		$numcalendars = VikBooking::numCalendars();
		$closing_dates = VikBooking::parseJsClosingDates();
		$cal_closing_dates = $closing_dates;
		if (count($cal_closing_dates) > 0) {
			foreach ($cal_closing_dates as $ccdk => $ccdv) {
				if (!(count($ccdv) == 2)) {
					continue;
				}
				$cal_closing_dates[$ccdk][0] = strtotime($ccdv[0]);
				$cal_closing_dates[$ccdk][1] = strtotime($ccdv[1]);
			}
		}
		$push_disabled_in = array();
		$push_disabled_out = array();

		if ($numcalendars > 0) {
			$pmonth = VikRequest::getInt('month', '', 'request');
			$arr = getdate();
			$mon = $arr['mon'];
			$realmon = ($mon < 10 ? "0".$mon : $mon);
			$year = $arr['year'];
			$day = $realmon."/01/".$year;
			$dayts = strtotime($day);
			$validmonth = false;
			if ($pmonth > 0 && $pmonth >= $dayts) {
				$validmonth = true;
			}

			/**
			 * Default number of future months is 12, but if a max date in the future is defined for at least one month, we use that number.
			 * 
			 * @since 	1.13.5 (J) - 1.3.5 (WP)
			 */
			$max_months_future = 12;
			$lim_months = $max_months_future;
			if (!empty($max_date_future)) {
				$numlim = (int)substr($max_date_future, 1, (strlen($max_date_future) - 2));
				$numlim = $numlim < 1 ? 1 : $numlim;
				$quantlim = substr($max_date_future, -1, 1);
				if ($quantlim == 'm' || $quantlim == 'y') {
					$max_months_future = $numlim * ($quantlim == 'm' ? 1 : 12);
					$lim_months = $max_months_future + 1 - $numcalendars + 1;
					$lim_months = $lim_months < 0 ? 1 : $lim_months;
				}
			}
			//

			$moptions = "";
			for ($i = 0; $i < $lim_months; $i++) {
				$moptions .= "<option value=\"".$dayts."\"".($validmonth && $pmonth == $dayts ? " selected=\"selected\"" : "").">".VikBooking::sayMonth($arr['mon'])." ".$arr['year']."</option>\n";
				$next = $arr['mon'] + 1;
				$dayts = mktime(0, 0, 0, $next, 1, $arr['year']);
				$arr = getdate($dayts);
			}
			?>

		<div id="vbo-bookingpart-init"></div>

		<div class="vbo-availcalendars-cont">

			<h4><?php echo JText::translate('VBOAVAILABILITYCALENDAR'); ?></h4>
			
			<form action="<?php echo JRoute::rewrite('index.php?option=com_vikbooking&view=roomdetails&roomid='.$room['id'].'&Itemid='.$pitemid); ?>" method="post" name="vbmonths">
				<select name="month" onchange="javascript: document.vbmonths.submit();" class="vbselectm"><?php echo $moptions; ?></select>
				<input type="hidden" name="checkin" id="checkin-hidden" value="" />
				<input type="hidden" name="promo" id="promo-hidden" value="" />
				<input type="hidden" name="booknow" value="1" />
				<input type="hidden" name="Itemid" value="<?php echo $pitemid; ?>" />
			</form>
			
			<div class="vblegendediv">
			
				<span class="vblegenda"><span class="vblegenda-status vblegfree">&nbsp;</span> <span class="vblegenda-lbl"><?php echo JText::translate('VBLEGFREE'); ?></span></span>
			<?php
			if ($showpartlyres) {
				?>
				<span class="vblegenda"><span class="vblegenda-status vblegwarning">&nbsp;</span> <span class="vblegenda-lbl"><?php echo JText::translate('VBLEGWARNING'); ?></span></span>
				<?php
			}
			if ($showcheckinoutonly) {
				?>
				<span class="vblegenda"><span class="vblegenda-status vblegbusycheckout">&nbsp;</span> <span class="vblegenda-lbl"><?php echo JText::translate('VBLEGBUSYCHECKOUT'); ?></span></span>
				<span class="vblegenda"><span class="vblegenda-status vblegbusycheckin">&nbsp;</span> <span class="vblegenda-lbl"><?php echo JText::translate('VBLEGBUSYCHECKIN'); ?></span></span>
				<?php
			}
			?>
				<span class="vblegenda"><span class="vblegenda-status vblegbusy">&nbsp;</span> <span class="vblegenda-lbl"><?php echo JText::translate('VBLEGBUSY'); ?></span></span>
				
			</div>
			
			<?php
			$check = is_array($busy);
			if ($validmonth) {
				$arr = getdate($pmonth);
				$mon = $arr['mon'];
				$realmon = ($mon < 10 ? "0".$mon : $mon);
				$year = $arr['year'];
				$day = $realmon."/01/".$year;
				$dayts = strtotime($day);
				$newarr = getdate($dayts);
			} else {
				$arr = getdate();
				$mon = $arr['mon'];
				$realmon = ($mon < 10 ? "0".$mon : $mon);
				$year = $arr['year'];
				$day = $realmon."/01/".$year;
				$dayts = strtotime($day);
				$newarr = getdate($dayts);
			}

			// price calendar
			$veryfirst = $newarr[0];
			$untilmonth = (int)$newarr['mon'] + intval(($numcalendars - 1));
			$addyears = $untilmonth > 12 ? intval(($untilmonth / 12)) : 0;
			$monthop = $addyears > 0 ? ($addyears * 12) : 0;
			$untilmonth = $untilmonth > 12 ? ($untilmonth - $monthop) : $untilmonth;
			$verylast = mktime(23, 59, 59, $untilmonth, date('t', mktime(0, 0, 0, $untilmonth, 1, ($newarr['year'] + $addyears))), ($newarr['year'] + $addyears));

			$priceseasons = [];
			$roomrate = [];
			$assumedailycost = 0;
			$usepricecal = false;
			if (intval(VikBooking::getRoomParam('pricecal', $room['params'])) == 1) {
				// turn flag on
				$usepricecal = true;

				/**
				 * In order to avoid special prices of another year to be applied over the months of the current year,
				 * in case the number of months displayed includes two years (Nov, Dec and Jan), we need to perform
				 * one call per year to properly apply the special prices of the right years.
				 * 
				 * @since 	1.4.3
				 */
				$first_year  = date('Y', $veryfirst);
				$last_year   = date('Y', $verylast);
				$parse_dates = array();
				// one call per year involved
				$checking_year = $first_year;
				$checking_first = $veryfirst;
				while ($checking_year <= $last_year) {
					if ($checking_year < $last_year) {
						// parsing first year, or an year in between in case of 3+ years of month-calendars
						array_push($parse_dates, array(
							'from' => $checking_first,
							'to'   => mktime(23, 59, 59, 12, 31, $checking_year)
						));
						// update next date to check
						$checking_first = mktime(0, 0, 0, 1, 1, ($checking_year + 1));
					} else {
						// parsing the last year
						array_push($parse_dates, array(
							'from' => $checking_first,
							'to'   => $verylast
						));
					}
					// go to next year
					$checking_year++;
				}

				// assume nightly room base cost
				$assumedailycost = VikBooking::getRoomParam('defcalcost', $room['params']);
				$assumedailycost = empty($assumedailycost) && !empty($room['base_cost']) ? $room['base_cost'] : $assumedailycost;

				/**
				 * Get the default room rate plan rates for a more accurate calculation of the nightly rates.
				 * 
				 * @since 	1.16.3 (J) - 1.6.3 (WP)
				 */
				$def_rplan_id = (int)VikBooking::getRoomParam('defrplan', $room['params']);
				$roomrate = $def_rplan_id ? VBORoomHelper::getInstance($room)->getRatePlans($room['id'], $def_rplan_id) : [];

				if (!$roomrate) {
					// loop through all dates interval just built (old method with the default cost)
					foreach ($parse_dates as $dates_intv) {
						$dummy_checkin  = $dates_intv['from'];
						$dummy_checkout = $dates_intv['to'];
						$current_year 	= date('Y', $dummy_checkin);

						$assumedays = floor((($dummy_checkout - $dummy_checkin) / (60 * 60 * 24)));
						$assumedays++;
						$assumeprice = $assumedailycost * $assumedays;
						$parserates = array(
							array(
								'id' 			 => -1,
								'idroom' 		 => $room['id'],
								'days' 			 => $assumedays,
								'idprice' 		 => -1,
								'cost' 			 => $assumeprice,
								'booking_nights' => 1,
								'attrdata' 		 => '',
							)
						);
						$priceseasons[$current_year] = VikBooking::applySeasonsRoom($parserates, $dummy_checkin, $dummy_checkout);
					}
				}
				?>
				<p class="vbpricecalwarning"><?php echo JText::translate('VBPRICECALWARNING'); ?></p>
				<?php
			}

			$firstwday = (int)VikBooking::getFirstWeekDay();
			$days_labels = array(
				JText::translate('VBSUN'),
				JText::translate('VBMON'),
				JText::translate('VBTUE'),
				JText::translate('VBWED'),
				JText::translate('VBTHU'),
				JText::translate('VBFRI'),
				JText::translate('VBSAT')
			);
			$days_indexes = array();
			for ($i = 0; $i < 7; $i++) {
				$days_indexes[$i] = (6 - ($firstwday - $i) + 1) % 7;
			}
			?>
			<div class="vbcalsblock <?php echo ($usepricecal === true ? 'vbcalsblock-price' : 'vbcalsblock-regular'); ?>">
			<?php
			$today_ts = mktime(0, 0, 0, date('n'), date('j'), date('Y'));
			$previousdayclass = "";
			for ($jj = 1; $jj <= $numcalendars; $jj++) {
				$d_count = 0;
				$cal = "";
				?>
				<div class="vbcaldivcont">
					<table class="<?php echo ($usepricecal === true ? 'vbcalprice' : 'vbcal'); ?>">
						<tr class="vbcaltrmonth">
							<td colspan="7" align="center" class="vbo-pref-bordercolor">
								<strong class="vbcaltrmonth-month"><?php echo VikBooking::sayMonth($newarr['mon']); ?></strong>
								<strong class="vbcaltrmonth-year"><?php echo $newarr['year']; ?></strong>
							</td>
						</tr>
						<tr class="vbcaldays">
						<?php
						for ($i = 0; $i < 7; $i++) {
							$d_ind = ($i + $firstwday) < 7 ? ($i + $firstwday) : ($i + $firstwday - 7);
							echo '<td>'.$days_labels[$d_ind].'</td>';
						}
						?>
						</tr>
						<tr class="<?php echo $usepricecal === true ? 'vbcalnumdaysprice' : 'vbcalnumdays'; ?>">
						<?php
						for ($i = 0, $n = $days_indexes[$newarr['wday']]; $i < $n; $i++, $d_count++) {
							$cal .= "<td align=\"center\">&nbsp;</td>";
						}
						while ($newarr['mon'] == $mon) {
							if ($d_count > 6) {
								$d_count = 0;
								$cal .= "</tr>\n<tr class=\"".($usepricecal === true ? 'vbcalnumdaysprice' : 'vbcalnumdays')."\">";
							}
							$dclass = "vbtdfree";
							$dalt = "";
							$bid = "";
							$totfound = 0;
							if ($check) {
								$ischeckinday = false;
								$ischeckoutday = false;
								foreach ($busy as $b) {
									$info_in = getdate($b['checkin']);
									$checkin_ts = mktime(0, 0, 0, $info_in['mon'], $info_in['mday'], $info_in['year']);
									$info_out = getdate($b['checkout']);
									$checkout_ts = mktime(0, 0, 0, $info_out['mon'], $info_out['mday'], $info_out['year']);
									if ($newarr[0] >= $checkin_ts && $newarr[0] == $checkout_ts) {
										$ischeckoutday = true;
									}
									if ($newarr[0] >= $checkin_ts && $newarr[0] < $checkout_ts) {
										$totfound++;
										if ($newarr[0] == $checkin_ts) {
											$ischeckinday = true;
										}
									}
								}
								if ($totfound >= $room['units']) {
									$dclass = "vbtdbusy";
									$push_disabled_in[] = '"'.date('Y-m-d', $newarr[0]).'"';
									if (!$ischeckinday || $previousdayclass == "vbtdbusy" || $previousdayclass == "vbtdbusy vbtdbusyforcheckin") {
										$push_disabled_out[] = '"'.date('Y-m-d', $newarr[0]).'"';
									}
									if ($ischeckinday && $showcheckinoutonly && $inonout_allowed && $previousdayclass != "vbtdbusy" && $previousdayclass != "vbtdbusy vbtdbusyforcheckin") {
										$dclass = "vbtdbusy vbtdbusyforcheckin";
									} elseif ($ischeckinday && !$inonout_allowed && $previousdayclass != "vbtdbusy" && $previousdayclass != "vbtdbusy vbtdbusyforcheckin") {
										//check-out not allowed on a day where someone is already checking-in
										$dclass = "vbtdbusy";
										$push_disabled_out[] = '"'.date('Y-m-d', $newarr[0]).'"';
									}
								} elseif ($totfound > 0) {
									if ($showpartlyres) {
										$dclass = "vbtdwarning";
									}
								} else {
									if ($ischeckoutday && $showcheckinoutonly && $inonout_allowed && !($room['units'] > 1)) {
										$dclass = "vbtdbusy vbtdbusyforcheckout";
									} elseif ($ischeckoutday && !$inonout_allowed && !($room['units'] > 1)) {
										$dclass = "vbtdbusy";
										$push_disabled_in[] = '"'.date('Y-m-d', $newarr[0]).'"';
									}
								}
							}
							if (count($cal_closing_dates)) {
								foreach ($cal_closing_dates as $closed_interval) {
									if ($newarr[0] >= $closed_interval[0] && $newarr[0] <= $closed_interval[1]) {
										$dclass = "vbtdbusy";
										break;
									}
								}
							}
							$previousdayclass = $dclass;
							$useday = ($newarr['mday'] < 10 ? "0".$newarr['mday'] : $newarr['mday']);
							// price calendar
							$useday = $usepricecal === true ? '<div class="vbcalpricedaynum"><span>'.$useday.'</span></div>' : $useday;
							if ($usepricecal === true) {
								$todaycost = $assumedailycost;
								if ($roomrate) {
									// new accurate calculation method (slower)
									$today_tsin = mktime($hcheckin, $mcheckin, 0, $newarr['mon'], $newarr['mday'], $newarr['year']);
									$today_tsout = mktime($hcheckout, $mcheckout, 0, $newarr['mon'], ($newarr['mday'] + 1), $newarr['year']);
									$tars = VikBooking::applySeasonsRoom([$roomrate], $today_tsin, $today_tsout);
									$todaycost = $tars[0]['cost'];
								} else {
									// fallback to old default cost (faster)
									$check_priceseasons = isset($priceseasons[$newarr['year']]) ? $priceseasons[$newarr['year']][0] : array();
									if (array_key_exists('affdayslist', $check_priceseasons) && array_key_exists($newarr['wday'].'-'.$newarr['mday'].'-'.$newarr['mon'], $check_priceseasons['affdayslist'])) {
										$todaycost = $check_priceseasons['affdayslist'][$newarr['wday'].'-'.$newarr['mday'].'-'.$newarr['mon']];
									}
								}
								$writecost = ($todaycost - intval($todaycost)) > 0.00 ? VikBooking::numberFormat($todaycost) : number_format($todaycost, 0);
								$useday .= '<div class="vbcalpricedaycost"><div><span class="vbo_currency">' . $currencysymb . '</span> <span class="vbo_price">' . $writecost . '</span></div></div>';
							} else {
								$useday = '<span>' . $useday . '</span>';
							}
							//
							$past_dclass = $newarr[0] < $today_ts ? ' vbtdpast' : '';
							if ($totfound == 1) {
								$cal .= "<td align=\"center\" class=\"" . $dclass . $past_dclass . "\" data-daydate=\"" . date($df, $newarr[0]) . "\" data-ymd=\"" . date('Y-m-d', $newarr[0]) . "\">" . $useday . "</td>\n";
							} elseif ($totfound > 1) {
								$cal .= "<td align=\"center\" class=\"" . $dclass . $past_dclass . "\" data-daydate=\"" . date($df, $newarr[0]) . "\" data-ymd=\"" . date('Y-m-d', $newarr[0]) . "\">" . $useday . "</td>\n";
							} else {
								$cal .= "<td align=\"center\" class=\"" . $dclass . $past_dclass . "\" data-daydate=\"" . date($df, $newarr[0]) . "\" data-ymd=\"" . date('Y-m-d', $newarr[0]) . "\">" . $useday . "</td>\n";
							}
							$next = $newarr['mday'] + 1;
							$dayts = mktime(0, 0, 0, $newarr['mon'], $next, $newarr['year']);
							$newarr = getdate($dayts);
							$d_count++;
						}

						for ($i = $d_count; $i <= 6; $i++) {
							$cal .= "<td align=\"center\">&nbsp;</td>";
						}

						echo $cal;
						?>
						</tr>
					</table>
				</div>
				<?php
				if ($mon == 12) {
					$mon = 1;
					$year += 1;
					$dayts = mktime(0, 0, 0, $mon, 1, $year);
				} else {
					$mon += 1;
					$dayts = mktime(0, 0, 0, $mon, 1, $year);
				}
				$newarr = getdate($dayts);
				
				if (($jj % 3) == 0) {
					echo "";
				}
			}
			?>
			</div>
		</div>
			<?php
			/**
			 * If not pricing calendar, we allow the AJAX navigation between the months.
			 * 
			 * @since 	1.13.5
			 */
			if (!$usepricecal) {
				$nav_next = (strtotime("+{$max_months_future} months") > $newarr[0]);
				$nav_next_start = date('Y-m-d', $newarr[0]);
				$lim_past_ts = mktime(0, 0, 0, date('n'), 1, date('Y'));
				$months_back_ts = strtotime("-{$numcalendars} months", $newarr[0]);
				$nav_prev_start = date('Y-m-d', $months_back_ts);
				$nav_prev = ($months_back_ts > $lim_past_ts);
				?>
		<script type="text/javascript">
		var vboAvCalsNavNext = '<?php echo $nav_next_start; ?>';
		var vboAvCalsNavPrev = '<?php echo $nav_prev_start; ?>';
		var vboAvCalsNavLoading = false;
		jQuery(function() {
		<?php
		if ($nav_next) {
			?>
			// add forward navigation
			jQuery('.vbcaldivcont').last().find('.vbcaltrmonth td').append('<span class="vbo-rdet-avcal-nav vbo-rdet-avcal-nav-next vbo-pref-color-btn">&gt;</span>');
			<?php
		}
		if ($nav_prev) {
			?>
			// add backward navigation
			jQuery('.vbcaldivcont').first().find('.vbcaltrmonth td').prepend('<span class="vbo-rdet-avcal-nav vbo-rdet-avcal-nav-prev vbo-pref-color-btn">&lt;</span>');
			<?php
		}
		?>
			jQuery(document.body).on('click', '.vbo-rdet-avcal-nav', function() {
				if (vboAvCalsNavLoading) {
					// prevent double submissions
					return false;
				}
				var direction = jQuery(this).hasClass('vbo-rdet-avcal-nav-prev') ? 'prev' : 'next';
				jQuery('.vbcaldivcont').addClass('vbcaldivcont-loading');
				vboAvCalsNavLoading = true;
				// make the AJAX request to the controller to request the new availability calendars
				var jqxhr = jQuery.ajax({
					type: "POST",
					url: "<?php echo VikBooking::ajaxUrl(JRoute::rewrite('index.php?option=com_vikbooking&task=get_avcalendars_data&tmpl=component'.(!empty($pitemid) ? '&Itemid='.$pitemid : ''), false)); ?>",
					data: {
						option: "com_vikbooking",
						task: "get_avcalendars_data",
						rid: "<?php echo $room['id']; ?>",
						direction: direction,
						fromdt: (direction == 'next' ? vboAvCalsNavNext : vboAvCalsNavPrev),
						nextdt: vboAvCalsNavNext,
						prevdt: vboAvCalsNavPrev
					}
				}).done(function(res) {
					// parse the JSON response that contains the calendars objects for the requested navigation
					try {
						var cal_data = typeof res === 'string' ? JSON.parse(res) : res;
						
						if (!cal_data || !cal_data['calendars'] || !cal_data['calendars'].length) {
							console.error('no availability calendars to parse');
							return false;
						}

						// total number of calendars returned by the navigation (1 by default)
						var tot_new_calendars = cal_data['calendars'].length;
						var new_calendars_parsed = 0;

						// build the new calendar(s)
						for (var i in cal_data['calendars']) {
							if (!cal_data['calendars'].hasOwnProperty(i)) {
								continue;
							}
							// start table
							var cal_html = '<div class="vbcaldivcont">' + "\n";
							cal_html += '<table class="vbcal">' + "\n";
							cal_html += '<tbody>' + "\n";
							// month name row
							cal_html += '<tr class="vbcaltrmonth">' + "\n";
							cal_html += '<td class="vbo-pref-bordercolor" colspan="7" align="center">' + "\n";
							cal_html += '<strong class="vbcaltrmonth-month">' + cal_data['calendars'][i].month + '</strong> <strong class="vbcaltrmonth-year">' + cal_data['calendars'][i].year + '</strong>' + "\n";
							cal_html += '</td>' + "\n";
							cal_html += '</tr>' + "\n";
							// ordered week days row
							cal_html += '<tr class="vbcaldays">' + "\n";
							for (var w in cal_data['calendars'][i]['wdays']) {
								if (!cal_data['calendars'][i]['wdays'].hasOwnProperty(w)) {
									continue;
								}
								cal_html += '<td>' + cal_data['calendars'][i]['wdays'][w] + '</td>' + "\n";
							}
							cal_html += '</tr>' + "\n";
							// calendar week rows
							for (var r in cal_data['calendars'][i]['rows']) {
								if (!cal_data['calendars'][i]['rows'].hasOwnProperty(r)) {
									continue;
								}
								// start calendar week row
								cal_html += '<tr class="vbcalnumdays">' + "\n";
								// loop over the cell dates of this row
								var rowcells = cal_data['calendars'][i]['rows'][r];
								for (var rc in rowcells) {
									if (!rowcells.hasOwnProperty(rc) || !rowcells[rc].hasOwnProperty('type')) {
										continue;
									}
									if (rowcells[rc]['type'] != 'day') {
										// empty cell placeholder
										cal_html += '<td align="center">' + rowcells[rc]['cont'] + '</td>' + "\n";
									} else {
										// real day cell
										cal_html += '<td align="center" class="' + rowcells[rc]['class'] + rowcells[rc]['past_class'] + '" data-daydate="' + rowcells[rc]['dt'] + '" data-ymd="' + rowcells[rc]['ymd'] + '"><span>' + rowcells[rc]['cont'] + '</span></td>' + "\n";
									}
								}
								// finalise calendar week row
								cal_html += '</tr>' + "\n";
							}
							// finalise table
							cal_html += '</tbody>' + "\n";
							cal_html += '</table>' + "\n";
							cal_html += '</div>';

							// remove first or last calendar, then prepend or append this calendar depending on the direction
							var cur_old_cal_index = direction == 'next' ? (jQuery('.vbcaldivcont').length - 1) : new_calendars_parsed;
							if (direction == 'next') {
								jQuery('.vbcaldivcont').eq(cur_old_cal_index).after(cal_html);
								jQuery('.vbcaldivcont').first().remove();
							} else {
								jQuery('.vbcaldivcont').eq(cur_old_cal_index).before(cal_html);
								jQuery('.vbcaldivcont').last().remove();
							}

							// increase parsed calendars counter
							new_calendars_parsed++;
						}

						// update navigation dates
						if (cal_data['next_ymd']) {
							vboAvCalsNavNext = cal_data['next_ymd'];
						}
						if (cal_data['prev_ymd']) {
							vboAvCalsNavPrev = cal_data['prev_ymd'];
						}

						// stop loading
						jQuery('.vbcaldivcont').removeClass('vbcaldivcont-loading');
						vboAvCalsNavLoading = false;

						// restore navigation arrows
						jQuery('.vbo-rdet-avcal-nav').remove();
						if (cal_data['can_nav_next']) {
							jQuery('.vbcaldivcont').last().find('.vbcaltrmonth td').append('<span class="vbo-rdet-avcal-nav vbo-rdet-avcal-nav-next vbo-pref-color-btn">&gt;</span>');
						}
						if (cal_data['can_nav_prev']) {
							jQuery('.vbcaldivcont').first().find('.vbcaltrmonth td').prepend('<span class="vbo-rdet-avcal-nav vbo-rdet-avcal-nav-prev vbo-pref-color-btn">&lt;</span>');
						}
					} catch (e) {
						console.log(e);
						alert('Invalid response');
						jQuery('.vbcaldivcont').removeClass('vbcaldivcont-loading');
						vboAvCalsNavLoading = false;
						return false;
					}
				}).fail(function(err) {
					console.error(err);
					alert('Could not navigate');
					jQuery('.vbcaldivcont').removeClass('vbcaldivcont-loading');
					vboAvCalsNavLoading = false;
				});
			});
		});
		</script>
				<?php
			}
		}

		/**
		 * We need to exclude from the datepicker calendars all booked dates.
		 * For this reason we loop for another extra year into the future for
		 * the sole purpose of pushing other dates onto $push_disabled_in/out.
		 * 
		 * @since 	1.12.0 (J) - 1.2.0 (WP)
		 */
		if (is_array($busy)) {
			if (!isset($newarr)) {
				// probably no months displayed for the availability
				$now_info = getdate();
				$newarr = getdate(mktime(0, 0, 0, $now_info['mon'], $now_info['mday'], $now_info['year']));
			}
			// we loop for one extra year ahead to disable dates for check-in/check-out
			$max_ts = mktime(23, 59, 59, $newarr['mon'], $newarr['mday'], ($newarr['year'] + 1));
			// loop until the maximum date in the future
			$wasprevbusy = false;
			while ($newarr[0] < $max_ts) {
				$totfound  		= 0;
				$ischeckinday 	= false;
				foreach ($busy as $b) {
					$info_in = getdate($b['checkin']);
					$checkin_ts = mktime(0, 0, 0, $info_in['mon'], $info_in['mday'], $info_in['year']);
					$info_out = getdate($b['checkout']);
					$checkout_ts = mktime(0, 0, 0, $info_out['mon'], $info_out['mday'], $info_out['year']);
					if ($newarr[0] >= $checkin_ts && $newarr[0] < $checkout_ts) {
						$totfound++;
						if ($newarr[0] == $checkin_ts) {
							$ischeckinday = true;
						}
					}
				}
				if ($totfound >= $room['units']) {
					$push_disabled_in[] = '"'.date('Y-m-d', $newarr[0]).'"';
					if (!$ischeckinday || $wasprevbusy) {
						$push_disabled_out[] = '"'.date('Y-m-d', $newarr[0]).'"';
					}
					// update previous day status only after checking the disabled out date
					$wasprevbusy = true;
				} else {
					$wasprevbusy = false;
				}
				// go to next day
				$newarr = getdate(mktime(0, 0, 0, $newarr['mon'], ($newarr['mday'] + 1), $newarr['year']));
			}
		}
		?>

		<div id="vbo-bookingpart-form"></div>

		<div class="vbo-seldates-cont">
			<div class="vbo-seldates-cont-inner">
				<h4><?php echo JText::translate('VBSELECTPDDATES'); ?></h4>

			<?php
			$paramshowpeople = intval(VikBooking::getRoomParam('maxminpeople', $room['params']));
			if ($paramshowpeople > 0) {
				$maxadustr = ($room['fromadult'] != $room['toadult'] ? $room['fromadult'].' - '.$room['toadult'] : $room['toadult']);
				$maxchistr = ($room['fromchild'] != $room['tochild'] ? $room['fromchild'].' - '.$room['tochild'] : $room['tochild']);
				$maxtotstr = ($room['mintotpeople'] != $room['totpeople'] ? $room['mintotpeople'].' - '.$room['totpeople'] : $room['totpeople']);
				?>
				<div class="vbmaxminpeopleroom">
				<?php
				if ($paramshowpeople == 1) {
					?>
					<div class="vbmaxadultsdet"><span class="vbmaximgdet"><?php VikBookingIcons::e('male'); ?></span><span class="vbmaxlabeldet"><?php echo JText::translate('VBFORMADULTS'); ?></span><span class="vbmaxnumberdet"><?php echo $maxadustr; ?></span></div>
					<?php
				} elseif ($paramshowpeople == 2) {
					?>
					<div class="vbmaxchildrendet"><span class="vbmaximgdet"><?php VikBookingIcons::e('child'); ?></span><span class="vbmaxlabeldet"><?php echo JText::translate('VBFORMCHILDREN'); ?></span><span class="vbmaxnumberdet"><?php echo $maxchistr; ?></span></div>
					<?php
				} elseif ($paramshowpeople == 3) {
					?>
					<div class="vbmaxadultsdet"><span class="vbmaximgdet"><?php VikBookingIcons::e('male'); ?></span><span class="vbmaxlabeldet"><?php echo JText::translate('VBFORMADULTS'); ?></span><span class="vbmaxnumberdet"><?php echo $maxadustr; ?></span></div>
					<div class="vbmaxtotdet"><span class="vbmaximgdet"><?php VikBookingIcons::e('users'); ?></span><span class="vbmaxlabeldet"><?php echo JText::translate('VBMAXTOTPEOPLE'); ?></span><span class="vbmaxnumberdet"><?php echo $maxtotstr; ?></span></div>
					<?php
				} elseif ($paramshowpeople == 4) {
					?>
					<div class="vbmaxchildrendet"><span class="vbmaximgdet"><?php VikBookingIcons::e('child'); ?></span><span class="vbmaxlabeldet"><?php echo JText::translate('VBFORMCHILDREN'); ?></span><span class="vbmaxnumberdet"><?php echo $maxchistr; ?></span></div>
					<div class="vbmaxtotdet"><span class="vbmaximgdet"><?php VikBookingIcons::e('users'); ?></span><span class="vbmaxlabeldet"><?php echo JText::translate('VBMAXTOTPEOPLE'); ?></span><span class="vbmaxnumberdet"><?php echo $maxtotstr; ?></span></div>
					<?php
				} elseif ($paramshowpeople == 5) {
					?>
					<div class="vbmaxadultsdet"><span class="vbmaximgdet"><?php VikBookingIcons::e('male'); ?></span><span class="vbmaxlabeldet"><?php echo JText::translate('VBFORMADULTS'); ?></span><span class="vbmaxnumberdet"><?php echo $maxadustr; ?></span></div>
					<div class="vbmaxchildrendet"><span class="vbmaximgdet"><?php VikBookingIcons::e('child'); ?></span><span class="vbmaxlabeldet"><?php echo JText::translate('VBFORMCHILDREN'); ?></span><span class="vbmaxnumberdet"><?php echo $maxchistr; ?></span></div>
					<div class="vbmaxtotdet"><span class="vbmaximgdet"><?php VikBookingIcons::e('users'); ?></span><span class="vbmaxlabeldet"><?php echo JText::translate('VBMAXTOTPEOPLE'); ?></span><span class="vbmaxnumberdet"><?php echo $maxtotstr; ?></span></div>
					<?php
				}
				?>
				</div>
				<?php
			}

			if (VikBooking::allowBooking()) {
				$calendartype = VikBooking::calendarType();
				$restrictions = VikBooking::loadRestrictions(true, array($room['id']));

				//vikbooking 1.5 channel manager
				$ch_start_date = VikRequest::getString('start_date', '', 'request');
				$ch_end_date = VikRequest::getString('end_date', '', 'request');
				$ch_num_adults = VikRequest::getInt('num_adults', '', 'request');
				$ch_num_children = VikRequest::getInt('num_children', '', 'request');
				$arr_adults = VikRequest::getVar('adults', array());
				$ch_num_adults = empty($ch_num_adults) && !empty($arr_adults[0]) ? $arr_adults[0] : $ch_num_adults;
				$arr_children = VikRequest::getVar('children', array());
				$ch_num_children = empty($ch_num_children) && !empty($arr_children[0]) ? $arr_children[0] : $ch_num_children;
				//
				$promo_checkin = VikRequest::getString('checkin', '', 'request');
				$ispromo = count($promo_season) > 0 ? $promo_season['id'] : 0;

				$form_method = defined('ABSPATH') ? 'post' : 'get';
				
				$selform = "<div class=\"vbdivsearch\"><form action=\"".JRoute::rewrite('index.php?option=com_vikbooking'.(!empty($pitemid) ? '&Itemid='.$pitemid : ''))."\" method=\"{$form_method}\" onsubmit=\"return vboValidateDates();\"><div class=\"vb-search-inner\">\n";
				$selform .= "<input type=\"hidden\" name=\"option\" value=\"com_vikbooking\"/>\n";
				$selform .= "<input type=\"hidden\" name=\"task\" value=\"search\"/>\n";
				if (!empty($pitemid)) {
					$selform .= "<input type=\"hidden\" name=\"Itemid\" value=\"".$pitemid."\"/>\n";
				}
				$selform .= "<input type=\"hidden\" name=\"roomdetail\" value=\"".$room['id']."\"/>\n";

				//vikbooking 1.1
				if ($calendartype == "jqueryui") {
					if ($vbdateformat == "%d/%m/%Y") {
						$juidf = 'dd/mm/yy';
					} elseif ($vbdateformat == "%m/%d/%Y") {
						$juidf = 'mm/dd/yy';
					} else {
						$juidf = 'yy/mm/dd';
					}
					//lang for jQuery UI Calendar
					$ldecl = '
			jQuery.noConflict();
			function vbGetDateObject(dstring) {
				var dparts = dstring.split("-");
				return new Date(dparts[0], (parseInt(dparts[1]) - 1), parseInt(dparts[2]), 0, 0, 0, 0);
			}
			function vbFullObject(obj) {
				var jk;
				for (jk in obj) {
					return obj.hasOwnProperty(jk);
				}
			}
			var vbrestrctarange, vbrestrctdrange, vbrestrcta, vbrestrctd;';
					$document->addScriptDeclaration($ldecl);
					//
					//VikBooking 1.4
					$totrestrictions = count($restrictions);
					$wdaysrestrictions = array();
					$wdaystworestrictions = array();
					$wdaysrestrictionsrange = array();
					$wdaysrestrictionsmonths = array();
					$ctarestrictionsrange = array();
					$ctarestrictionsmonths = array();
					$ctdrestrictionsrange = array();
					$ctdrestrictionsmonths = array();
					$monthscomborestr = array();
					$minlosrestrictions = array();
					$minlosrestrictionsrange = array();
					$maxlosrestrictions = array();
					$maxlosrestrictionsrange = array();
					$notmultiplyminlosrestrictions = array();
					if ($totrestrictions > 0) {
						foreach ($restrictions as $rmonth => $restr) {
							if ($rmonth != 'range') {
								if (strlen((string)$restr['wday'])) {
									$wdaysrestrictions[] = "'".($rmonth - 1)."': '".$restr['wday']."'";
									$wdaysrestrictionsmonths[] = $rmonth;
									if (strlen((string)$restr['wdaytwo'])) {
										$wdaystworestrictions[] = "'".($rmonth - 1)."': '".$restr['wdaytwo']."'";
										$monthscomborestr[($rmonth - 1)] = VikBooking::parseJsDrangeWdayCombo($restr);
									}
								} elseif (!empty($restr['ctad']) || !empty($restr['ctdd'])) {
									if (!empty($restr['ctad'])) {
										$ctarestrictionsmonths[($rmonth - 1)] = explode(',', $restr['ctad']);
									}
									if (!empty($restr['ctdd'])) {
										$ctdrestrictionsmonths[($rmonth - 1)] = explode(',', $restr['ctdd']);
									}
								}
								if ($restr['multiplyminlos'] == 0) {
									$notmultiplyminlosrestrictions[] = $rmonth;
								}
								$minlosrestrictions[] = "'".($rmonth - 1)."': '".$restr['minlos']."'";
								if (!empty($restr['maxlos']) && $restr['maxlos'] > 0 && $restr['maxlos'] > $restr['minlos']) {
									$maxlosrestrictions[] = "'".($rmonth - 1)."': '".$restr['maxlos']."'";
								}
							} else {
								foreach ($restr as $kr => $drestr) {
									if (strlen((string)$drestr['wday'])) {
										$wdaysrestrictionsrange[$kr][0] = date('Y-m-d', $drestr['dfrom']);
										$wdaysrestrictionsrange[$kr][1] = date('Y-m-d', $drestr['dto']);
										$wdaysrestrictionsrange[$kr][2] = $drestr['wday'];
										$wdaysrestrictionsrange[$kr][3] = $drestr['multiplyminlos'];
										$wdaysrestrictionsrange[$kr][4] = strlen((string)$drestr['wdaytwo']) ? $drestr['wdaytwo'] : -1;
										$wdaysrestrictionsrange[$kr][5] = VikBooking::parseJsDrangeWdayCombo($drestr);
									} elseif (!empty($drestr['ctad']) || !empty($drestr['ctdd'])) {
										$ctfrom = date('Y-m-d', $drestr['dfrom']);
										$ctto = date('Y-m-d', $drestr['dto']);
										if (!empty($drestr['ctad'])) {
											$ctarestrictionsrange[$kr][0] = $ctfrom;
											$ctarestrictionsrange[$kr][1] = $ctto;
											$ctarestrictionsrange[$kr][2] = explode(',', $drestr['ctad']);
										}
										if (!empty($drestr['ctdd'])) {
											$ctdrestrictionsrange[$kr][0] = $ctfrom;
											$ctdrestrictionsrange[$kr][1] = $ctto;
											$ctdrestrictionsrange[$kr][2] = explode(',', $drestr['ctdd']);
										}
									}
									$minlosrestrictionsrange[$kr][0] = date('Y-m-d', $drestr['dfrom']);
									$minlosrestrictionsrange[$kr][1] = date('Y-m-d', $drestr['dto']);
									$minlosrestrictionsrange[$kr][2] = $drestr['minlos'];
									if (!empty($drestr['maxlos']) && $drestr['maxlos'] > 0 && $drestr['maxlos'] >= $drestr['minlos']) {
										$maxlosrestrictionsrange[$kr] = $drestr['maxlos'];
									}
								}
								unset($restrictions['range']);
							}
						}
						
						$resdecl = "
			var vbrestrmonthswdays = [".implode(", ", $wdaysrestrictionsmonths)."];
			var vbrestrmonths = [".implode(", ", array_keys($restrictions))."];
			var vbrestrmonthscombojn = JSON.parse('".json_encode($monthscomborestr)."');
			var vbrestrminlos = {".implode(", ", $minlosrestrictions)."};
			var vbrestrminlosrangejn = JSON.parse('".json_encode($minlosrestrictionsrange)."');
			var vbrestrmultiplyminlos = [".implode(", ", $notmultiplyminlosrestrictions)."];
			var vbrestrmaxlos = {".implode(", ", $maxlosrestrictions)."};
			var vbrestrmaxlosrangejn = JSON.parse('".json_encode($maxlosrestrictionsrange)."');
			var vbrestrwdaysrangejn = JSON.parse('".json_encode($wdaysrestrictionsrange)."');
			var vbrestrcta = JSON.parse('".json_encode($ctarestrictionsmonths)."');
			var vbrestrctarange = JSON.parse('".json_encode($ctarestrictionsrange)."');
			var vbrestrctd = JSON.parse('".json_encode($ctdrestrictionsmonths)."');
			var vbrestrctdrange = JSON.parse('".json_encode($ctdrestrictionsrange)."');
			var vbcombowdays = {};
			function vbRefreshCheckout(darrive) {
				if (vbFullObject(vbcombowdays)) {
					var vbtosort = new Array();
					for (var vbi in vbcombowdays) {
						if (vbcombowdays.hasOwnProperty(vbi)) {
							var vbusedate = darrive;
							vbtosort[vbi] = vbusedate.setDate(vbusedate.getDate() + (vbcombowdays[vbi] - 1 - vbusedate.getDay() + 7) % 7 + 1);
						}
					}
					vbtosort.sort(function(da, db) {
						return da > db ? 1 : -1;
					});
					for (var vbnext in vbtosort) {
						if (vbtosort.hasOwnProperty(vbnext)) {
							var vbfirstnextd = new Date(vbtosort[vbnext]);
							jQuery('#checkoutdate').datepicker( 'option', 'minDate', vbfirstnextd );
							jQuery('#checkoutdate').datepicker( 'setDate', vbfirstnextd );
							break;
						}
					}
				}
			}
			function vbSetMinCheckoutDate(selectedDate) {
				var minlos = ".VikBooking::getDefaultNightsCalendar().";
				var maxlosrange = 0;
				var nowcheckin = jQuery('#checkindate').datepicker('getDate');
				var nowd = nowcheckin.getDay();
				var nowcheckindate = new Date(nowcheckin.getTime());
				vbcombowdays = {};
				if (vbFullObject(vbrestrminlosrangejn)) {
					for (var rk in vbrestrminlosrangejn) {
						if (vbrestrminlosrangejn.hasOwnProperty(rk)) {
							var minldrangeinit = vbGetDateObject(vbrestrminlosrangejn[rk][0]);
							if (nowcheckindate >= minldrangeinit) {
								var minldrangeend = vbGetDateObject(vbrestrminlosrangejn[rk][1]);
								if (nowcheckindate <= minldrangeend) {
									minlos = parseInt(vbrestrminlosrangejn[rk][2]);
									if (vbFullObject(vbrestrmaxlosrangejn)) {
										if (rk in vbrestrmaxlosrangejn) {
											maxlosrange = parseInt(vbrestrmaxlosrangejn[rk]);
										}
									}
									if (rk in vbrestrwdaysrangejn && nowd in vbrestrwdaysrangejn[rk][5]) {
										vbcombowdays = vbrestrwdaysrangejn[rk][5][nowd];
									}
								}
							}
						}
					}
				}
				var nowm = nowcheckin.getMonth();
				if (vbFullObject(vbrestrmonthscombojn) && vbrestrmonthscombojn.hasOwnProperty(nowm)) {
					if (nowd in vbrestrmonthscombojn[nowm]) {
						vbcombowdays = vbrestrmonthscombojn[nowm][nowd];
					}
				}
				if (jQuery.inArray((nowm + 1), vbrestrmonths) != -1) {
					minlos = parseInt(vbrestrminlos[nowm]);
				}
				nowcheckindate.setDate(nowcheckindate.getDate() + minlos);
				jQuery('#checkoutdate').datepicker( 'option', 'minDate', nowcheckindate );
				if (maxlosrange > 0) {
					var diffmaxminlos = maxlosrange - minlos;
					var maxcheckoutdate = new Date(nowcheckindate.getTime());
					maxcheckoutdate.setDate(maxcheckoutdate.getDate() + diffmaxminlos);
					jQuery('#checkoutdate').datepicker( 'option', 'maxDate', maxcheckoutdate );
				}
				if (nowm in vbrestrmaxlos) {
					var diffmaxminlos = parseInt(vbrestrmaxlos[nowm]) - minlos;
					var maxcheckoutdate = new Date(nowcheckindate.getTime());
					maxcheckoutdate.setDate(maxcheckoutdate.getDate() + diffmaxminlos);
					jQuery('#checkoutdate').datepicker( 'option', 'maxDate', maxcheckoutdate );
				}
				if (!vbFullObject(vbcombowdays)) {
					var is_checkout_disabled = false;
					if (typeof selectedDate !== 'undefined' && typeof jQuery('#checkoutdate').datepicker('option', 'beforeShowDay') === 'function') {
						// let the datepicker validate if the min date to set for check-out is disabled due to CTD rules
						is_checkout_disabled = !jQuery('#checkoutdate').datepicker('option', 'beforeShowDay')(nowcheckindate)[0];
					}
					if (!is_checkout_disabled) {
						jQuery('#checkoutdate').datepicker( 'setDate', nowcheckindate );
					} else {
						setTimeout(() => {
							// make sure the minimum date just set for the checkout has not populated a CTD date that we do not want
							var current_out_dt = jQuery('#checkoutdate').datepicker('getDate');
							if (current_out_dt && current_out_dt.getTime() === nowcheckindate.getTime()) {
								jQuery('#checkoutdate').datepicker( 'setDate', null );
							}
							jQuery('#checkoutdate').focus();
						}, 100);
					}
				} else {
					vbRefreshCheckout(nowcheckin);
				}
			}";
						
						if (count($wdaysrestrictions) > 0 || count($wdaysrestrictionsrange) > 0) {
							//VikBooking 1.5
							$dfull_in = '';
							$dfull_out = '';
							if (count($push_disabled_in) > 0) {
								$dfull_in = "
				var actd = jQuery.datepicker.formatDate('yy-mm-dd', date);
				if (jQuery.inArray(actd, vbfulldays_in) != -1) {
					return [false];
				}
				";
							}
							if (count($push_disabled_out) > 0) {
								$dfull_out = "
				var actd = jQuery.datepicker.formatDate('yy-mm-dd', date);
				if (jQuery.inArray(actd, vbfulldays_out) != -1) {
					return [false];
				}
				// exclude days after a fully booked day, because a date selection cannot contain a fully booked day in between.
				var exclude_after = false;
				var last_fully_booked = null;
				var nowcheckin = jQuery('#checkindate').datepicker('getDate');
				if (nowcheckin && vbfulldays_out.length) {
					var nowcheckindate = new Date(nowcheckin.getTime());
					nowcheckindate.setHours(0);
					nowcheckindate.setMinutes(0);
					nowcheckindate.setSeconds(0);
					nowcheckindate.setMilliseconds(0);
					for (var i in vbfulldays_out) {
						var nowfullday = new Date(vbfulldays_out[i]);
						nowfullday.setHours(0);
						nowfullday.setMinutes(0);
						nowfullday.setSeconds(0);
						nowfullday.setMilliseconds(0);
						exclude_after = (nowcheckindate <= nowfullday);
						if (exclude_after) {
							// selected check-in date is before a fully booked day
							last_fully_booked = nowfullday;
							break;
						}
					}
				}
				if (exclude_after) {
					date.setHours(0);
					date.setMinutes(0);
					date.setSeconds(0);
					date.setMilliseconds(0);
					if (date > last_fully_booked) {
						// current day for display is after a fully booked day, with a selected check-in day before a fully booked day. Disable it.
						return [false];
					}
				}
				//
				";
							}
							//
							$resdecl .= "
			var vbrestrwdays = {".implode(", ", $wdaysrestrictions)."};
			var vbrestrwdaystwo = {".implode(", ", $wdaystworestrictions)."};
			".(count($push_disabled_in) > 0 ? "var vbfulldays_in = [".implode(", ", $push_disabled_in)."];" : "")."
			".(count($push_disabled_out) > 0 ? "var vbfulldays_out = [".implode(", ", $push_disabled_out)."];" : "")."
			function vbIsDayDisabled(date) {
				if (!vbIsDayOpen(date) || !vboValidateCta(date)) {
					return [false];
				}
				var m = date.getMonth(), wd = date.getDay();
				if (vbFullObject(vbrestrwdaysrangejn)) {
					for (var rk in vbrestrwdaysrangejn) {
						if (vbrestrwdaysrangejn.hasOwnProperty(rk)) {
							var wdrangeinit = vbGetDateObject(vbrestrwdaysrangejn[rk][0]);
							if (date >= wdrangeinit) {
								var wdrangeend = vbGetDateObject(vbrestrwdaysrangejn[rk][1]);
								if (date <= wdrangeend) {
									if (wd != vbrestrwdaysrangejn[rk][2]) {
										if (vbrestrwdaysrangejn[rk][4] == -1 || wd != vbrestrwdaysrangejn[rk][4]) {
											return [false];
										}
									}
								}
							}
						}
					}
				}
			".(count($push_disabled_in) > 0 ? $dfull_in : '')."
				if (vbFullObject(vbrestrwdays)) {
					if (jQuery.inArray((m+1), vbrestrmonthswdays) == -1) {
						return [true];
					}
					if (wd == vbrestrwdays[m]) {
						return [true];
					}
					if (vbFullObject(vbrestrwdaystwo)) {
						if (wd == vbrestrwdaystwo[m]) {
							return [true];
						}
					}
					return [false];
				}
				return [true];
			}
			function vbIsDayDisabledCheckout(date) {
				if (!vbIsDayOpen(date) || !vboValidateCtd(date)) {
					return [false];
				}
				var m = date.getMonth(), wd = date.getDay();
			".(count($push_disabled_out) > 0 ? $dfull_out : '')."
				if (vbFullObject(vbcombowdays)) {
					if (jQuery.inArray(wd, vbcombowdays) != -1) {
						return [true];
					} else {
						return [false];
					}
				}
				if (vbFullObject(vbrestrwdaysrangejn)) {
					for (var rk in vbrestrwdaysrangejn) {
						if (vbrestrwdaysrangejn.hasOwnProperty(rk)) {
							var wdrangeinit = vbGetDateObject(vbrestrwdaysrangejn[rk][0]);
							if (date >= wdrangeinit) {
								var wdrangeend = vbGetDateObject(vbrestrwdaysrangejn[rk][1]);
								if (date <= wdrangeend) {
									if (wd != vbrestrwdaysrangejn[rk][2] && vbrestrwdaysrangejn[rk][3] == 1) {
										return [false];
									}
								}
							}
						}
					}
				}
				if (vbFullObject(vbrestrwdays)) {
					if (jQuery.inArray((m+1), vbrestrmonthswdays) == -1 || jQuery.inArray((m+1), vbrestrmultiplyminlos) != -1) {
						return [true];
					}
					if (wd == vbrestrwdays[m]) {
						return [true];
					}
					return [false];
				}
				return [true];
			}";
						}
						$document->addScriptDeclaration($resdecl);
					}
					//
					//VikBooking 1.5
					if (count($push_disabled_in) > 0) {
						$full_in_decl = "
			var vbfulldays_in = [".implode(", ", $push_disabled_in)."];
			function vbIsDayFull(date) {
				if (!vbIsDayOpen(date) || !vboValidateCta(date)) {
					return [false];
				}
				var actd = jQuery.datepicker.formatDate('yy-mm-dd', date);
				if (jQuery.inArray(actd, vbfulldays_in) == -1) {
					return [true];
				}
				return [false];
			}";
						$document->addScriptDeclaration($full_in_decl);
					}
					if (count($push_disabled_out) > 0) {
						$full_out_decl = "
			var vbfulldays_out = [".implode(", ", $push_disabled_out)."];
			function vbIsDayFullOut(date) {
				if (!vbIsDayOpen(date) || !vboValidateCtd(date)) {
					return [false];
				}
				var actd = jQuery.datepicker.formatDate('yy-mm-dd', date);
				if (jQuery.inArray(actd, vbfulldays_out) == -1) {
					// exclude days after a fully booked day, because a date selection cannot contain a fully booked day in between.
					var exclude_after = false;
					var last_fully_booked = null;
					var nowcheckin = jQuery('#checkindate').datepicker('getDate');
					if (nowcheckin && vbfulldays_out.length) {
						var nowcheckindate = new Date(nowcheckin.getTime());
						nowcheckindate.setHours(0);
						nowcheckindate.setMinutes(0);
						nowcheckindate.setSeconds(0);
						nowcheckindate.setMilliseconds(0);
						for (var i in vbfulldays_out) {
							var nowfullday = new Date(vbfulldays_out[i]);
							nowfullday.setHours(0);
							nowfullday.setMinutes(0);
							nowfullday.setSeconds(0);
							nowfullday.setMilliseconds(0);
							exclude_after = (nowcheckindate <= nowfullday);
							if (exclude_after) {
								// selected check-in date is before a fully booked day
								last_fully_booked = nowfullday;
								break;
							}
						}
					}
					if (exclude_after) {
						date.setHours(0);
						date.setMinutes(0);
						date.setSeconds(0);
						date.setMilliseconds(0);
						if (date > last_fully_booked) {
							// current day for display is after a fully booked day, with a selected check-in day before a fully booked day. Disable it.
							return [false];
						}
					}
					//
					return [true];
				}
				return [false];
			}";
						$document->addScriptDeclaration($full_out_decl);
					}
					//
					$sdecl = "
			var vbclosingdates = JSON.parse('".json_encode($closing_dates)."');
			function vbCheckClosingDates(date) {
				if (!vbIsDayOpen(date)) {
					return [false];
				}
				return [true];
			}
			function vbIsDayOpen(date) {
				if (vbFullObject(vbclosingdates)) {
					for (var cd in vbclosingdates) {
						if (vbclosingdates.hasOwnProperty(cd)) {
							var cdfrom = vbGetDateObject(vbclosingdates[cd][0]);
							var cdto = vbGetDateObject(vbclosingdates[cd][1]);
							if (date >= cdfrom && date <= cdto) {
								return false;
							}
						}
					}
				}
				return true;
			}
			function vboCheckClosingDatesIn(date) {
				var isdayopen = vbIsDayOpen(date) && vboValidateCta(date);
				return [isdayopen];
			}
			function vboCheckClosingDatesOut(date) {
				var isdayopen = vbIsDayOpen(date) && vboValidateCtd(date);
				return [isdayopen];
			}
			function vboValidateCta(date) {
				var m = date.getMonth(), wd = date.getDay();
				if (vbFullObject(vbrestrctarange)) {
					for (var rk in vbrestrctarange) {
						if (vbrestrctarange.hasOwnProperty(rk)) {
							var wdrangeinit = vbGetDateObject(vbrestrctarange[rk][0]);
							if (date >= wdrangeinit) {
								var wdrangeend = vbGetDateObject(vbrestrctarange[rk][1]);
								if (date <= wdrangeend) {
									if (jQuery.inArray('-'+wd+'-', vbrestrctarange[rk][2]) >= 0) {
										return false;
									}
								}
							}
						}
					}
				}
				if (vbFullObject(vbrestrcta)) {
					if (vbrestrcta.hasOwnProperty(m) && jQuery.inArray('-'+wd+'-', vbrestrcta[m]) >= 0) {
						return false;
					}
				}
				return true;
			}
			function vboValidateCtd(date) {
				var m = date.getMonth(), wd = date.getDay();
				if (vbFullObject(vbrestrctdrange)) {
					for (var rk in vbrestrctdrange) {
						if (vbrestrctdrange.hasOwnProperty(rk)) {
							var wdrangeinit = vbGetDateObject(vbrestrctdrange[rk][0]);
							if (date >= wdrangeinit) {
								var wdrangeend = vbGetDateObject(vbrestrctdrange[rk][1]);
								if (date <= wdrangeend) {
									if (jQuery.inArray('-'+wd+'-', vbrestrctdrange[rk][2]) >= 0) {
										return false;
									}
								}
							}
						}
					}
				}
				if (vbFullObject(vbrestrctd)) {
					if (vbrestrctd.hasOwnProperty(m) && jQuery.inArray('-'+wd+'-', vbrestrctd[m]) >= 0) {
						return false;
					}
				}
				return true;
			}
			function vbSetGlobalMinCheckoutDate() {
				var nowcheckin = jQuery('#checkindate').datepicker('getDate');
				var nowcheckindate = new Date(nowcheckin.getTime());
				nowcheckindate.setDate(nowcheckindate.getDate() + ".VikBooking::getDefaultNightsCalendar().");
				jQuery('#checkoutdate').datepicker( 'option', 'minDate', nowcheckindate );
				jQuery('#checkoutdate').datepicker( 'setDate', nowcheckindate );
			}
			jQuery(function() {
				jQuery.datepicker.setDefaults( jQuery.datepicker.regional[ '' ] );
				jQuery('#checkindate').datepicker({
					showOn: 'focus',
					numberOfMonths: ".($is_mobile ? '1' : '2').",".(count($wdaysrestrictions) > 0 || count($wdaysrestrictionsrange) > 0 ? "\nbeforeShowDay: vbIsDayDisabled,\n" : (count($push_disabled_in) > 0 ? "\nbeforeShowDay: vbIsDayFull,\n" : "\nbeforeShowDay: vboCheckClosingDatesIn,\n"))."
					onSelect: function( selectedDate ) {
						".($totrestrictions > 0 ? "vbSetMinCheckoutDate(selectedDate);" : "vbSetGlobalMinCheckoutDate();")."
						vbCalcNights();
					}
				});
				jQuery('#checkindate').datepicker( 'option', 'dateFormat', '".$juidf."');
				jQuery('#checkindate').datepicker( 'option', 'minDate', '".$min_days_advance."d');
				jQuery('#checkindate').datepicker( 'option', 'maxDate', '".$max_date_future."');
				jQuery('#checkoutdate').datepicker({
					showOn: 'focus',
					numberOfMonths: ".($is_mobile ? '1' : '2').",".(count($wdaysrestrictions) > 0 || count($wdaysrestrictionsrange) > 0 ? "\nbeforeShowDay: vbIsDayDisabledCheckout,\n" : (count($push_disabled_out) > 0 ? "\nbeforeShowDay: vbIsDayFullOut,\n" : "\nbeforeShowDay: vboCheckClosingDatesOut,\n"))."
					onSelect: function( selectedDate ) {
						vbCalcNights();
					}
				});
				jQuery('#checkoutdate').datepicker( 'option', 'dateFormat', '".$juidf."');
				jQuery('#checkoutdate').datepicker( 'option', 'minDate', '".$min_days_advance."d');
				jQuery('#checkindate').datepicker( 'option', jQuery.datepicker.regional[ 'vikbooking' ] );
				jQuery('#checkoutdate').datepicker( 'option', jQuery.datepicker.regional[ 'vikbooking' ] );
				jQuery('.vb-cal-img, .vbo-caltrigger').click(function() {
					var jdp = jQuery(this).prev('input.hasDatepicker');
					if (jdp.length) {
						jdp.focus();
					}
				});
			});";
					$document->addScriptDeclaration($sdecl);
					$selform .= "<div class=\"vbo-search-inpblock vbo-search-inpblock-checkin\"><label for=\"checkindate\">" . JText::translate('VBPICKUPROOM') . "</label><div class=\"input-group\"><input type=\"text\" name=\"checkindate\" id=\"checkindate\" size=\"10\" autocomplete=\"off\" onfocus=\"this.blur();\" readonly/><i class=\"".VikBookingIcons::i('calendar', 'vbo-caltrigger')."\"></i></div><input type=\"hidden\" name=\"checkinh\" value=\"".$hcheckin."\"/><input type=\"hidden\" name=\"checkinm\" value=\"".$mcheckin."\"/></div>\n";
					$selform .= "<div class=\"vbo-search-inpblock vbo-search-inpblock-checkout\"><label for=\"checkoutdate\">" . JText::translate('VBRETURNROOM') . "</label><div class=\"input-group\"><input type=\"text\" name=\"checkoutdate\" id=\"checkoutdate\" size=\"10\" autocomplete=\"off\" onfocus=\"this.blur();\" readonly/><i class=\"".VikBookingIcons::i('calendar', 'vbo-caltrigger')."\"></i></div><input type=\"hidden\" name=\"checkouth\" value=\"".$hcheckout."\"/><input type=\"hidden\" name=\"checkoutm\" value=\"".$mcheckout."\"/></div>\n";
				} else {
					//default Joomla Calendar
					$selform .= "<div class=\"vbo-search-inpblock vbo-search-inpblock-checkin\"><label for=\"checkindate\">" . JText::translate('VBPICKUPROOM') . "</label><div class=\"input-group\">" . $vbo_app->getCalendar('', 'checkindate', 'checkindate', $vbdateformat, array ('class' => '','size' => '10','maxlength' => '19'));
					$selform .= "<input type=\"hidden\" name=\"checkinh\" value=\"".$hcheckin."\"/><input type=\"hidden\" name=\"checkinm\" value=\"".$mcheckin."\"/></div></div>\n";
					$selform .= "<div class=\"vbo-search-inpblock vbo-search-inpblock-checkout\"><label for=\"checkoutdate\">" . JText::translate('VBRETURNROOM') . "</label><div class=\"input-group\">" . $vbo_app->getCalendar('', 'checkoutdate', 'checkoutdate', $vbdateformat, array ('class' => '','size' => '10','maxlength' => '19')); 
					$selform .= "<input type=\"hidden\" name=\"checkouth\" value=\"".$hcheckout."\"/><input type=\"hidden\" name=\"checkoutm\" value=\"".$mcheckout."\"/></div></div>\n";
				}
				//
				//rooms, adults, children
				$showchildren = VikBooking::showChildrenFront();
				$guests_label = VBOFactory::getConfig()->get('guests_label', 'adults');
				$use_guests_label = 'VBFORMADULTS';
				if (!$showchildren && !strcasecmp($guests_label, 'guests')) {
					$use_guests_label = 'VBOINVTOTGUESTS';
				}
				//max number of rooms
				$multi_units = (int)VikBooking::getRoomParam('multi_units', $room['params']);
				if ($multi_units === 1 && $room['units'] > 1) {
					$maxsearchnumrooms = (int)VikBooking::getSearchNumRooms();
					$maxsearchnumrooms = $room['units'] > $maxsearchnumrooms ? $maxsearchnumrooms : $room['units'];
					$roomsel = "<label for=\"vbo-detroomsnum\">".JText::translate('VBFORMROOMSN')."</label><select id=\"vbo-detroomsnum\" name=\"roomsnum\" onchange=\"vbSetRoomsAdults(this.value);\">\n";
					for ($r = 1; $r <= $maxsearchnumrooms; $r++) {
						$roomsel .= "<option value=\"".$r."\">".$r."</option>\n";
					}
					$roomsel .= "</select>\n";
				} else {
					$roomsel = "<input type=\"hidden\" name=\"roomsnum\" value=\"1\">\n";
				}
				//
				//max number of adults per room
				$suggocc = (int)VikBooking::getRoomParam('suggocc', $room['params']);
				$adultsel = "<select name=\"adults[]\">";
				for ($a = $room['fromadult']; $a <= $room['toadult']; $a++) {
					$adultsel .= "<option value=\"".$a."\"".((!empty($ch_num_adults) && $ch_num_adults == $a) || (empty($ch_num_adults) && $a == $suggocc) ? " selected=\"selected\"" : "").">".$a."</option>";
				}
				$adultsel .= "</select>";
				//
				//max number of children per room
				$childrensel = "<select name=\"children[]\">";
				for ($c = $room['fromchild']; $c <= $room['tochild']; $c++) {
					$childrensel .= "<option value=\"".$c."\"".(!empty($ch_num_children) && $ch_num_children == $c ? " selected=\"selected\"" : "").">".$c."</option>";
				}
				$childrensel .= "</select>";
				//

				$selform .= "<div class=\"vbo-search-num-racblock\">\n";
				$selform .= "	<div class=\"vbo-search-num-rooms\">".$roomsel."</div>\n";
				$selform .= "	<div class=\"vbo-search-num-aduchild-block\" id=\"vbo-search-num-aduchild-block\">\n";
				$selform .= "		<div class=\"vbo-search-num-aduchild-entry\">" . ($multi_units === 1 && $room['units'] > 1 ? "<span class=\"vbo-search-roomnum\">".JText::translate('VBFORMNUMROOM')." 1</span>" : '') . "\n";
				$selform .= "			<div class=\"vbo-search-num-adults-entry\"><label class=\"vbo-search-num-adults-entry-label\">".JText::translate($use_guests_label)."</label><span class=\"vbo-search-num-adults-entry-inp\">".$adultsel."</span></div>\n";
				if ($showchildren) {
					$selform .= "		<div class=\"vbo-search-num-children-entry\"><label class=\"vbo-search-num-children-entry-label\">".JText::translate('VBFORMCHILDREN')."</label><span class=\"vbo-search-num-children-entry-inp\">".$childrensel."</span></div>\n";
				}
				$selform .= "		</div>\n";
				$selform .= "	</div>\n";
				// the tag <div id=\"vbjstotnights\"></div> will be used by javascript to calculate the nights
				$selform .= "	<div id=\"vbjstotnights\"></div>\n";
				$selform .= "</div>\n";
				$selform .= "<div class=\"vbo-search-submit\"><input type=\"submit\" name=\"search\" value=\"" . JText::translate('VBBOOKTHISROOM') . "\" class=\"btn vbdetbooksubmit vbo-pref-color-btn\"/></div>\n";
				$selform .= "</div>\n";
				$selform .= "</form></div>";
				?>

				<div class="vbo-js-helpers" style="display: none;">
					<div class="vbo-add-element-html">
						<div class="vbo-search-num-aduchild-entry">
							<span class="vbo-search-roomnum"><?php echo JText::translate('VBFORMNUMROOM'); ?> %d</span>
							<div class="vbo-search-num-adults-entry">
								<label class="vbo-search-num-adults-entry-label"><?php echo JText::translate($use_guests_label); ?></label>
								<span class="vbo-search-num-adults-entry-inp"><?php echo $adultsel; ?></span>
							</div>
						<?php
						if ($showchildren) {
							?>
							<div class="vbo-search-num-children-entry">
								<label class="vbo-search-num-children-entry-label"><?php echo JText::translate('VBFORMCHILDREN'); ?></label>
								<span class="vbo-search-num-adults-entry-inp"><?php echo $childrensel; ?></span>
							</div>
							<?php
						}
						?>
						</div>
					</div>
				</div>

				<script type="text/javascript">
				function vboValidateDates() {
					var vbcheckin = document.getElementById('checkindate').value;
					var vbcheckout = document.getElementById('checkoutdate').value;
					if (!vbcheckin || !vbcheckout) {
						alert(Joomla.JText._('VBSELPRDATE'));
						return false;
					}
					return true;
				}
				function vbCalcNights() {
					var vbcheckin = document.getElementById('checkindate').value;
					var vbcheckout = document.getElementById('checkoutdate').value;
					if (vbcheckin.length > 0 && vbcheckout.length > 0) {
						var vbcheckinp = vbcheckin.split("/");
						var vbcheckoutp = vbcheckout.split("/");
					<?php
					if ($vbdateformat == "%d/%m/%Y") {
						?>
						var vbinmonth = parseInt(vbcheckinp[1]);
						vbinmonth = vbinmonth - 1;
						var vbinday = parseInt(vbcheckinp[0], 10);
						var vbcheckind = new Date(vbcheckinp[2], vbinmonth, vbinday);
						var vboutmonth = parseInt(vbcheckoutp[1]);
						vboutmonth = vboutmonth - 1;
						var vboutday = parseInt(vbcheckoutp[0], 10);
						var vbcheckoutd = new Date(vbcheckoutp[2], vboutmonth, vboutday);
						<?php
					} elseif ($vbdateformat == "%m/%d/%Y") {
						?>
						var vbinmonth = parseInt(vbcheckinp[0]);
						vbinmonth = vbinmonth - 1;
						var vbinday = parseInt(vbcheckinp[1], 10);
						var vbcheckind = new Date(vbcheckinp[2], vbinmonth, vbinday);
						var vboutmonth = parseInt(vbcheckoutp[0]);
						vboutmonth = vboutmonth - 1;
						var vboutday = parseInt(vbcheckoutp[1], 10);
						var vbcheckoutd = new Date(vbcheckoutp[2], vboutmonth, vboutday);
						<?php
					} else {
						?>
						var vbinmonth = parseInt(vbcheckinp[1]);
						vbinmonth = vbinmonth - 1;
						var vbinday = parseInt(vbcheckinp[2], 10);
						var vbcheckind = new Date(vbcheckinp[0], vbinmonth, vbinday);
						var vboutmonth = parseInt(vbcheckoutp[1]);
						vboutmonth = vboutmonth - 1;
						var vboutday = parseInt(vbcheckoutp[2], 10);
						var vbcheckoutd = new Date(vbcheckoutp[0], vboutmonth, vboutday);
						<?php
					}
					?>
						var vbdivider = 1000 * 60 * 60 * 24;
						var vbints = vbcheckind.getTime();
						var vboutts = vbcheckoutd.getTime();
						if (vboutts > vbints) {
							//var vbnights = Math.ceil((vboutts - vbints) / (vbdivider));
							var utc1 = Date.UTC(vbcheckind.getFullYear(), vbcheckind.getMonth(), vbcheckind.getDate());
							var utc2 = Date.UTC(vbcheckoutd.getFullYear(), vbcheckoutd.getMonth(), vbcheckoutd.getDate());
							var vbnights = Math.ceil((utc2 - utc1) / vbdivider);
							if (vbnights > 0) {
								document.getElementById('vbjstotnights').innerHTML = '<?php echo addslashes(JText::translate('VBJSTOTNIGHTS')); ?>: '+vbnights;
							} else {
								document.getElementById('vbjstotnights').innerHTML = '';
							}
						} else {
							document.getElementById('vbjstotnights').innerHTML = '';
						}
					} else {
						document.getElementById('vbjstotnights').innerHTML = '';
					}
				}
				function vbAddElement() {
					var ni = document.getElementById('vbo-search-num-aduchild-block');
					var numi = document.getElementById('vbroomdethelper');
					var num = (document.getElementById('vbroomdethelper').value -1)+ 2;
					numi.value = num;
					var newdiv = document.createElement('div');
					var divIdName = 'vb'+num+'detracont';
					newdiv.setAttribute('id', divIdName);
					var new_element_html = document.getElementsByClassName('vbo-add-element-html')[0].innerHTML;
					var rp_rgx = new RegExp('%d', 'g');
					newdiv.innerHTML = new_element_html.replace(rp_rgx, num);
					ni.appendChild(newdiv);
				}
				function vbSetRoomsAdults(totrooms) {
					var actrooms = parseInt(document.getElementById('vbroomdethelper').value);
					var torooms = parseInt(totrooms);
					var difrooms;
					if (torooms > actrooms) {
						difrooms = torooms - actrooms;
						for (var ir=1; ir<=difrooms; ir++) {
							vbAddElement();
						}
					}
					if (torooms < actrooms) {
						for (var ir=actrooms; ir>torooms; ir--) {
							if (ir > 1) {
								var rmra = document.getElementById('vb' + ir + 'detracont');
								rmra.parentNode.removeChild(rmra);
							}
						}
						document.getElementById('vbroomdethelper').value = torooms;
					}
				}
				<?php
				$scroll_booking = false;
				//vikbooking 1.5 channel manager
				if (!empty($ch_start_date) && !empty($ch_end_date)) {
					$ch_ts_startdate = strtotime($ch_start_date);
					$ch_ts_enddate = strtotime($ch_end_date);
					if ($ch_ts_startdate > time() && $ch_ts_startdate < $ch_ts_enddate) {
						?>
				jQuery(function() {
					document.getElementById('checkindate').value = '<?php echo date($df, $ch_ts_startdate); ?>';
					document.getElementById('checkoutdate').value = '<?php echo date($df, $ch_ts_enddate); ?>';
					vbCalcNights();
				});
						<?php
					}
				} elseif (!empty($promo_checkin) && intval($promo_checkin) > 0 && $calendartype == "jqueryui") {
					$scroll_booking = $promo_checkin > mktime(0, 0, 0, date("n"), date("j"), date("Y")) ? true : $scroll_booking;
					$min_nights = 1;
					if (count($promo_season) > 0 && $scroll_booking) {
						if ($promo_season['promominlos'] > 1) {
							$min_nights = $promo_season['promominlos'];
							$promo_end_ts = $promo_checkin + ($min_nights * 86400);
							if ((bool)date('I', $promo_checkin) !== (bool)date('I', $promo_end_ts)) {
								if ((bool)$promo_checkin === true) {
									$promo_end_ts += 3600;
								} else {
									$promo_end_ts -= 3600;
								}
							}
						}
					}
					?>
				jQuery(function() {
					jQuery("#checkin-hidden").val("<?php echo $promo_checkin; ?>");
					jQuery("#checkindate").datepicker("setDate", new Date(<?php echo date('Y', $promo_checkin); ?>, <?php echo ((int)date('n', $promo_checkin) - 1); ?>, <?php echo date('j', $promo_checkin); ?>));
					<?php
					if ($min_nights > 1) {
						?>
					jQuery("#promo-hidden").val("<?php echo $promo_season['id']; ?>");
					jQuery("#checkoutdate").datepicker("option", "minDate", new Date(<?php echo date('Y', $promo_end_ts); ?>, <?php echo ((int)date('n', $promo_end_ts) - 1); ?>, <?php echo date('j', $promo_end_ts); ?>));
						<?php
					}
					?>
					jQuery(".ui-datepicker-current-day").click();
				});
					<?php
				}
				//
				?>
				jQuery(function() {
				<?php
				if ($ispromo > 0 || $scroll_booking === true || VikRequest::getInt('booknow', 0, 'request')) {
					?>
					setTimeout(function() {
						jQuery('html,body').animate({ scrollTop: (jQuery("#vbo-bookingpart-init").offset().top - 5) }, { duration: 'slow' });
					}, 200);
					<?php
				}
				?>
					jQuery(document.body).on('click', 'td.vbtdfree, td.vbtdwarning, td.vbtdbusyforcheckout', function() {
						if (!jQuery("#checkindate").length || jQuery(this).hasClass('vbtdpast')) {
							return;
						}
						var tdday = jQuery(this).attr('data-daydate');
						var tdymd = jQuery(this).attr('data-ymd');
						if (!tdday || !tdymd) {
							return;
						}
						// make sure the clicked date is not disabled
						if (typeof jQuery('#checkindate').datepicker('option', 'beforeShowDay') === 'function') {
							// let the datepicker validate the clicked day
							let ymd_parts = tdymd.split('-');
							let ymd_object = new Date(ymd_parts[0], ymd_parts[1] - 1, ymd_parts[2], 0, 0, 0, 0);
							if (!jQuery('#checkindate').datepicker('option', 'beforeShowDay')(ymd_object)[0]) {
								return;
							}
						}
						// set check-in date in datepicker
						jQuery('#checkindate').datepicker('setDate', tdday);
						// animate to datepickers position
						jQuery('html,body').animate({
							scrollTop: (jQuery('#vbo-bookingpart-form').offset().top - 5)
						}, 600, function() {
							// animation-complete callback should simulate the onSelect event of the check-in datepicker
							if (typeof vbSetMinCheckoutDate !== "undefined") {
								vbSetMinCheckoutDate();
							} else if (typeof vbSetGlobalMinCheckoutDate !== "undefined") {
								vbSetGlobalMinCheckoutDate();
							}
							vbCalcNights();
							// give focus to check-out datepicker
							jQuery('#checkoutdate').focus();
						});
					});
				});
				</script>

				<input type="hidden" id="vbroomdethelper" value="1"/>

				<div class="vbo-intro-main"><?php echo VikBooking::getIntroMain(); ?></div>

				<div class="vbo-room-details-booking-wrapper">
				<?php
				echo $selform;
				if (count($promo_season) > 0 && !empty($promo_season['promotxt'])) {
					?>
					<div class="vbo-promotion-block">
						<div class="vbo-promotion-icon">
							<?php VikBookingIcons::e('percentage'); ?>
						</div>
						<div class="vbo-promotion-description">
							<?php echo $promo_season['promotxt']; ?>
						</div>
					</div>
					<?php
				}
				?>
				</div>

				<?php
				// check for guests allowed policy
				if (!$showchildren && $guests_allowed_policy = VikBooking::getGuestsAllowedPolicy($vbo_tn)) {
					?>
				<div class="vbo-guests-allowed-policy"><?php echo $guests_allowed_policy; ?></div>
					<?php
				}
				?>

				<div class="vbo-closing-main"><?php echo VikBooking::getClosingMain(); ?></div>
				<?php
			} else {
				echo VikBooking::getDisabledBookingMsg();
			}
			?>
			</div>
		</div>
	</div>
</div>
