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

if ($geo_supported) {
?>
<div class="vbo-modal-overlay-block vbo-modal-overlay-block-geomap">
	<a class="vbo-modal-overlay-close" href="javascript: void(0);"></a>
	<div class="vbo-modal-overlay-content vbo-modal-overlay-content-geomap">
		<div class="vbo-modal-overlay-content-head vbo-modal-overlay-content-head-geomap">
			<h3><span id="vbo-modal-geomap-title"><?php VikBookingIcons::e('map-marker-alt'); ?> <?php echo JText::translate('VBO_GEO_CUSTOMIZE_MARKER') ?></span> <span class="vbo-modal-overlay-close-times" onclick="hideVboModalGeoMap();">&times;</span></h3>
		</div>
		<div class="vbo-modal-overlay-content-body vbo-modal-overlay-content-body-scroll">
			<div class="vbo-cust-geo-marker-wrapper" style="display: none;">
				<div class="vbo-cust-geo-marker-top">
					<label for="vbo-geo-marker-icntype"><?php echo JText::translate('VBO_MARKER_ICN_TYPE'); ?></label>
					<select id="vbo-geo-marker-icntype" onchange="vboCustomizeMarkerChangeIconType(this.value);">
						<option value="google"><?php echo JText::translate('VBO_MARKER_ICN_TYPE_GMAPS'); ?></option>
						<option value="symbol"><?php echo JText::translate('VBO_MARKER_ICN_TYPE_SVG'); ?></option>
						<option value="icon"><?php echo JText::translate('VBO_MARKER_ICN_TYPE_IMG'); ?></option>
					</select>
				</div>
				<div class="vbo-cust-geo-marker-bottom" style="display: none;" data-geomarker="symbol">
					<div class="vbo-cust-geo-marker-svg-list">
					<?php
					$symbols = $geo->getMarkerSymbols();
					foreach ($symbols as $symbol) {
						$data_attr = array(
							'data-markerapply="0"',
							'data-markerid="' . $symbol->id . '"',
							'data-markergroup="' . (isset($symbol->group) ? $symbol->group : '') . '"',
							'data-markerpointx="' . (isset($symbol->width) ? ($symbol->width / 2) : '') . '"',
							'data-markerpointy="' . (isset($symbol->height) ? ($symbol->height / 2) : '') . '"',
						);
						?>
						<div class="vbo-cust-geo-marker-svg" <?php echo implode(' ', $data_attr); ?> onclick="vboCustomizeMarkerSelectSymbol(this);">
							<div class="vbo-cust-geo-marker-svg-name">
								<span><?php echo $symbol->name; ?></span>
							</div>
							<div class="vbo-cust-geo-marker-svg-icon">
								<svg style="<?php echo !empty($symbol->fill) ? ('fill: ' . $symbol->fill . ';') : ''; echo !empty($symbol->opacity) ? ('opacity: ' . $symbol->opacity . ';') : ''; ?>">
									<path d="<?php echo $symbol->path; ?>" />
								</svg>
							</div>
							<div class="vbo-cust-geo-marker-svg-remove">
								<span onclick="vboCustomizeMarkerDeleteSymbol(this);"><?php VikBookingIcons::e('trash'); ?></span>
							</div>
						</div>
						<?php
					}
					?>
						<div class="vbo-cust-geo-marker-svg vbo-cust-geo-marker-svg-addnew" data-markerapply="0" data-markerid="" onclick="vboCustomizeMarkerSelectSymbol(this);">
							<div class="vbo-cust-geo-marker-svg-name">
								<span><?php echo JText::translate('VBO_ADD_NEW'); ?></span>
							</div>
							<div class="vbo-cust-geo-marker-svg-icon">
								<span><?php VikBookingIcons::e('plus-circle'); ?></span>
							</div>
						</div>
					</div>
					<div class="vbo-cust-geo-marker-svg-adjust" style="display: none;">
						<div class="vbo-geo-marker-param-container vbo-cust-geo-marker-svg-newfield" style="display: none;">
							<div class="vbo-geo-marker-param-label"><?php echo JText::translate('VBO_NEW_SVG_ICN_NAME'); ?></div>
							<div class="vbo-geo-marker-param-setting">
								<input type="text" id="vbo-newsvg-name" value="" />
							</div>
						</div>
						<div class="vbo-geo-marker-param-container vbo-cust-geo-marker-svg-newfield" style="display: none;">
							<div class="vbo-geo-marker-param-label">
								<?php
								echo $vbo_app->createPopover(array('title' => JText::translate('VBO_SVG_PATH'), 'content' => JText::translate('VBO_SVG_PATH_HELP')));
								echo ' ' . JText::translate('VBO_SVG_PATH');
								?>
							</div>
							<div class="vbo-geo-marker-param-setting">
								<textarea id="vbo-newsvg-path" onkeyup="vboCustomizeMarkerSanitizeCustomSymbol();"></textarea>
							</div>
						</div>
						<div class="vbo-geo-marker-param-container vbo-cust-geo-marker-svg-newfield" style="display: none;">
							<div class="vbo-geo-marker-param-setting">
								<button type="button" class="btn vbo-config-btn" onclick="vboCustomizeMarkerAddCustomSymbol();"><?php VikBookingIcons::e('plus-circle'); ?> <?php echo JText::translate('VBCONFIGCLOSINGDATEADD'); ?></button>
								<button type="button" class="btn btn-secondary" onclick="vboCustomizeMarkerCancelCustomSymbol();"><?php VikBookingIcons::e('times-circle'); ?> <?php echo JText::translate('VBANNULLA'); ?></button>
							</div>
						</div>
						<div class="vbo-geo-marker-param-container">
							<div class="vbo-geo-marker-param-label"><?php echo JText::translate('VBO_FILL_COLOR'); ?></div>
							<div class="vbo-geo-marker-param-setting">
								<span class="vbo-inspector-colorpicker-wrap">
									<span class="vbo-inspector-colorpicker"><?php VikBookingIcons::e('palette'); ?></span>
								</span>
								<input type="hidden" id="vbo-geo-marker-fillcolor" value="" />
							</div>
						</div>
						<div class="vbo-geo-marker-param-container">
							<div class="vbo-geo-marker-param-label"><?php echo JText::translate('VBO_OPACITY'); ?></div>
							<div class="vbo-geo-marker-param-setting">
								<input type="number" id="vbo-geo-marker-opacity" value="" min="0.1" max="1" step="0.1" />
							</div>
						</div>
					</div>
				</div>
				<div class="vbo-cust-geo-marker-bottom" style="display: none;" data-geomarker="icon">
					<div class="vbo-geo-marker-param-container">
						<div class="vbo-geo-marker-param-label"><?php echo JText::translate('VBPVIEWOPTIONALSSEVEN'); ?></div>
						<div class="vbo-geo-marker-param-setting">
							<?php echo $vbo_app->getMediaField('marker_icon_img', null); ?>
						</div>
					</div>
					<div class="vbo-geo-marker-param-container">
						<div class="vbo-geo-marker-param-label"><?php echo JText::translate('VBO_WIDTH'); ?></div>
						<div class="vbo-geo-marker-param-setting">
							<input type="number" id="vbo-geo-marker-icon-width" value="" min="0" step="1" /> px
						</div>
					</div>
					<div class="vbo-geo-marker-param-container">
						<div class="vbo-geo-marker-param-label"><?php echo JText::translate('VBO_HEIGHT'); ?></div>
						<div class="vbo-geo-marker-param-setting">
							<input type="number" id="vbo-geo-marker-icon-height" value="" min="0" step="1" /> px
						</div>
					</div>
				</div>
				<div class="vbo-cust-geo-marker-save">
					<button type="button" class="btn btn-success" onclick="vboCustomizeMarkerApply();"><?php VikBookingIcons::e('check'); ?> <?php echo JText::translate('VBAPPLY'); ?></button>
				</div>
			</div>
			<div class="vbo-cust-geo-overlay-wrapper" style="display: none;">
				<div class="vbo-cust-geo-overlay-inner">
					<div class="vbo-geo-marker-param-container">
						<div class="vbo-geo-marker-param-label"><?php echo JText::translate('VBPVIEWOPTIONALSSEVEN'); ?></div>
						<div class="vbo-geo-marker-param-setting">
							<?php
							$set_overlay_img = $geo->getRoomGeoParams($rooms_params, 'overlay_img');
							if (!empty($set_overlay_img)) {
								$set_overlay_img = str_replace(JUri::root(), '', $set_overlay_img);
							}
							echo $vbo_app->getMediaField('map_overlay_img', $set_overlay_img);
							?>
						</div>
					</div>
					<div class="vbo-geo-marker-param-container">
						<div class="vbo-geo-marker-param-setting">
							<p class="info notice-noicon"><?php echo JText::translate('VBO_GEO_MAP_GOVERLAY_COORDS_HELP'); ?></p>
							<button type="button" id="vbo-overlay-copy-bounds" class="btn vbo-config-btn" onclick="vboOverlayCopyBounds();" style="display: none;"><?php echo VikBookingIcons::e('crop-alt'); ?> <?php echo JText::translate('VBO_GEO_MAP_COPYBOUNDS'); ?></button>
						</div>
					</div>
					<div class="vbo-geo-marker-params-group">
						<div class="vbo-geo-marker-param-container">
							<div class="vbo-geo-marker-param-label"><?php echo JText::translate('VBO_COORD_SOUTH'); ?></div>
							<div class="vbo-geo-marker-param-setting">
								<input type="number" id="vbo-geo-map-overlay-south" class="vbo-large-input-number" value="<?php echo $geo->getRoomGeoParams($rooms_params, 'overlay_south', ''); ?>" min="-90" max="90" step="any" />
							</div>
						</div>
						<div class="vbo-geo-marker-param-container">
							<div class="vbo-geo-marker-param-label"><?php echo JText::translate('VBO_COORD_WEST'); ?></div>
							<div class="vbo-geo-marker-param-setting">
								<input type="number" id="vbo-geo-map-overlay-west" class="vbo-large-input-number" value="<?php echo $geo->getRoomGeoParams($rooms_params, 'overlay_west', ''); ?>" min="-180" max="180" step="any" />
							</div>
						</div>
						<div class="vbo-geo-marker-param-container">
							<div class="vbo-geo-marker-param-label"><?php echo JText::translate('VBO_COORD_NORTH'); ?></div>
							<div class="vbo-geo-marker-param-setting">
								<input type="number" id="vbo-geo-map-overlay-north" class="vbo-large-input-number" value="<?php echo $geo->getRoomGeoParams($rooms_params, 'overlay_north', ''); ?>" min="-90" max="90" step="any" />
							</div>
						</div>
						<div class="vbo-geo-marker-param-container">
							<div class="vbo-geo-marker-param-label"><?php echo JText::translate('VBO_COORD_EAST'); ?></div>
							<div class="vbo-geo-marker-param-setting">
								<input type="number" id="vbo-geo-map-overlay-east" class="vbo-large-input-number" value="<?php echo $geo->getRoomGeoParams($rooms_params, 'overlay_east', ''); ?>" min="-180" max="180" step="any" />
							</div>
						</div>
					</div>
				</div>
				<div class="vbo-cust-geo-marker-save">
					<button type="button" class="btn btn-success" onclick="vboGroundOverlayApply();"><?php VikBookingIcons::e('check'); ?> <?php echo JText::translate('VBAPPLY'); ?></button>
				</div>
			</div>
		</div>
	</div>
</div>
<?php
}
