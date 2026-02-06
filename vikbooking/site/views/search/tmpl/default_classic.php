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

$app = JFactory::getApplication();
$vat_included = VikBooking::ivaInclusa();
$tax_summary = !$vat_included && VikBooking::showTaxOnSummaryOnly() ? true : false;

$currencysymb = VikBooking::getCurrencySymb();
$pitemid = VikRequest::getInt('Itemid', '', 'request');

// whether to use links to room details
$link_to_details = VBOFactory::getConfig()->getBool('search_link_roomdetails', false);

// search map
$is_search_map_enabled = VikBooking::interactiveMapEnabled();

/**
 * Search results (classic layout) style: list or grid.
 * 
 * @since 	1.18.3 (J) - 1.8.3 (WP)
 */
$layouts = [
	'list' => [
		'title' => JText::translate('VBO_LIST'),
		'icon'  => 'th-list',
	],
	'grid' => [
		'title' => JText::translate('VBO_GRID'),
		'icon'  => 'table',
		'class' => 'vblistcontainer-grid',
	],
];

// default style (name)
$default_style = $is_search_map_enabled ? 'grid' : 'list';

?>
<div class="vbo-results-filtering">
<?php
if (VBOFactory::getConfig()->getBool('search_filters')) {
	// count the active filters
	$current_filters = (array) $app->input->get('filters', [], 'array');
	$active_filters = 0;
	foreach ($current_filters as $filter_type => $filter_data) {
		if (empty($filter_data)) {
			continue;
		}
		if (is_array($filter_data)) {
			if (range(0, count($filter_data) - 1) === array_keys($filter_data)) {
				// count non-empty values for this linear array
				$active_filters += count(array_filter($filter_data));
			} else {
				// associative array filter will count as a single filter
				$is_active_filter = true;
				if ($filter_type === 'price' && !empty($filter_data['min']) && !empty($filter_data['max'])) {
					// ensure the value is not the default range of rates pool or even greater
					if (!$this->rates_pool || (floor((float) $filter_data['min']) == floor(min($this->rates_pool)) && ceil((float) $filter_data['max']) == ceil(max($this->rates_pool)))) {
						// do not count the filter as applied
						$is_active_filter = false;
					} elseif ($this->rates_pool && $filter_data['min'] <= min($this->rates_pool) && $filter_data['max'] >= max($this->rates_pool)) {
						// do not count the filter as applied
						$is_active_filter = false;
					}
				}
				if ($is_active_filter) {
					$active_filters++;
				}
			}
		} else {
			// non empty value will count as a single filter
			$active_filters++;
		}
	}
	// normalize active filters counter, if needed
	if ($active_filters > 99) {
		// convert it to a string
		$active_filters = '99+';
	}
	?>
	<div class="vbo-results-filters-wrap">
		<button type="button" class="vbo-results-filters-toggle" data-counter="<?php echo $active_filters ?: ''; ?>"><?php VikBookingIcons::e('sliders-h'); ?> <?php echo JText::translate('VBO_FILTERS'); ?></button>
		<div class="vbo-results-filters-form-helper" style="display: none;">
			<?php echo $this->loadTemplate('filters'); ?>
		</div>
	</div>
	<?php
}
?>
	<div class="vbo-results-style-wrap">
		<div class="vbo-results-style">
		<?php
		foreach ($layouts as $layout_type => $layout_data) {
			?>
			<span class="vbo-results-style-option<?php echo $layout_type == $default_style ? ' vbo-results-style-option-active' : ''; ?>" data-type="<?php echo $layout_type; ?>" data-toggle="<?php echo $layout_data['class'] ?? ''; ?>"><?php VikBookingIcons::e($layout_data['icon']); ?> <?php echo $layout_data['title'] ?? ''; ?></span>
			<?php
		}
		?>
		</div>
	</div>
</div>
<div class="vbo-results-content<?php echo !$is_search_map_enabled ? ' vbo-results-without-geomap' : ''; ?>">
<?php
/**
 * Interactive map booking. Only for classic booking layout.
 * 
 * @since 	1.14 (J) - 1.4.0 (WP)
 */
if ($is_search_map_enabled) {
	echo $this->loadTemplate('interactive_map');
}
?>
	<div class="vbo-searchresults-classic-wrap<?php echo !empty($layouts[$default_style]['class']) ? ' ' . $layouts[$default_style]['class'] : ''; ?>">
<?php
$writeroomnum = [];
foreach ($this->res as $indroom => $rooms) {
	if ($this->roomsnum > 1 && !in_array($indroom, $writeroomnum) && $rooms) {
		$writeroomnum[] = $indroom;
		?>
		<div class="vbo-searchresults-step-content">
			<div id="vbpositionroom<?php echo $indroom; ?>"></div>
			<div class="vbsearchproominfo">
				<span class="vbsearchnroom"><?php echo JText::translate('VBSEARCHROOMNUM'); ?> <?php echo $indroom; ?></span>
				<span class="vbsearchroomparty"><?php VikBookingIcons::e('users', 'vbo-pref-color-text'); ?> <?php echo $this->arrpeople[$indroom]['adults']; ?> <?php echo ($this->arrpeople[$indroom]['adults'] == 1 ? JText::translate('VBSEARCHRESADULT') : JText::translate('VBSEARCHRESADULTS')); ?> <?php echo ($this->showchildren && $this->arrpeople[$indroom]['children'] > 0 ? ", ".$this->arrpeople[$indroom]['children']." ".($this->arrpeople[$indroom]['children'] == 1 ? JText::translate('VBSEARCHRESCHILD') : JText::translate('VBSEARCHRESCHILDREN')) : ""); ?></span>
			</div>
		</div>
		<?php
	}
	?>
		<div class="vbo-searchresults-party-content">
	<?php
	foreach ($rooms as $room) {
		// set a different class to the main div in case the rooms usage is for less people than the capacity
		$rdiffusage = array_key_exists('diffusage', $room[0]) && $this->arrpeople[$indroom]['adults'] < $room[0]['toadult'] ? true : false;
		$has_promotion = array_key_exists('promotion', $room[0]) ? true : false;
		$maindivclass = $rdiffusage ? "room_resultdiffusage" : "room_result";
		$carats = VikBooking::getRoomCaratOriz($room[0]['idcarat'], $this->vbo_tn);

		// prepare CMS contents depending on platform
		$room[0] = VBORoomHelper::getInstance()->prepareCMSContents($room[0], ['smalldesc']);

		$saylastavail = false;
		$showlastavail = (int)VikBooking::getRoomParam('lastavail', $room[0]['params']);
		if (!empty($showlastavail) && $showlastavail > 0) {
			if ($room[0]['unitsavail'] <= $showlastavail) {
				$saylastavail = true;
			}
		}
		$searchdet_link = JRoute::rewrite('index.php?option=com_vikbooking&view=searchdetails&roomid='.$room[0]['idroom'].'&checkin='.$this->checkin.'&checkout='.$this->checkout.'&adults='.$this->arrpeople[$indroom]['adults'].'&children='.$this->arrpeople[$indroom]['children'].'&tmpl=component'.(!empty($pitemid) ? '&Itemid='.$pitemid : ''));

		/**
		 * Build image gallery, if available
		 * 
		 * @since 	1.14 (J) - 1.4.0 (WP)
		 */
		$gallery_data = [];
		if (!empty($room[0]['moreimgs'])) {
			$moreimages = explode(';;', $room[0]['moreimgs']);
			foreach (array_filter($moreimages) as $mimg) {
				// push thumb URL
				$gallery_data[] = $mimg;
			}
		}

		// build listing details URI components
		$listing_uri_data = [
			'option'       => 'com_vikbooking',
			'view'         => 'roomdetails',
			'roomid'       => $room[0]['idroom'],
			'start_date'   => date('Y-m-d', $this->checkin),
			'end_date'     => date('Y-m-d', $this->checkout),
			'num_adults'   => $this->arrpeople[$indroom]['adults'],
			'num_children' => $this->arrpeople[$indroom]['children'],
			'Itemid'       => ($pitemid ?: null),
		];
		// route proper URI
		$listing_page_uri = JRoute::rewrite('index.php?' . http_build_query($listing_uri_data), false);

		// use link to listing details page or plain text
		$main_listing_elem = $room[0]['name'];
		if ($link_to_details) {
			// embed HTML link
			$main_listing_elem = '<a class="vbo-search-results-listing-link" href="' . $listing_page_uri . '" target="_blank">' . $room[0]['name'] . '</a>';
		}

		// cut off long descriptions by eventually adding a "read more" link
		$descr_length  = strlen((string) $room[0]['smalldesc']);
		$visible_descr = $room[0]['smalldesc'];
		$hidden_descr  = '';
		if ($descr_length > 200 && ($descr_length - 200) > 100) {
			$visible_descr = strip_tags($visible_descr);
			$hidden_descr = '1';
			if (function_exists('mb_substr')) {
				$visible_descr = mb_substr($visible_descr, 0, 200, 'UTF-8');
			} else {
				$visible_descr = substr($visible_descr, 0, 200);
			}
			$visible_descr .= '...';
		}
		?>
			<div class="room_item <?php echo $maindivclass; ?><?php echo $has_promotion === true ? ' vbo-promotion-price' : ''; ?>" id="vbcontainer<?php echo $indroom.'_'.$room[0]['idroom']; ?>">
				<div class="vblistroomblock">
					<div class="vbimglistdiv">
						<div class="vbo-dots-slider-selector">
							<a href="<?php echo $searchdet_link; ?>" class="vbmodalframe" target="_blank" data-gallery="<?php echo implode('|', $gallery_data); ?>">
							<?php
							if (!empty($room[0]['img']) && is_file(VBO_SITE_PATH . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . $room[0]['img'])) {
								?>
								<img class="vblistimg" alt="<?php echo htmlspecialchars($room[0]['name']); ?>" id="vbroomimg<?php echo $indroom.'_'.$room[0]['idroom']; ?>" src="<?php echo VBO_SITE_URI; ?>resources/uploads/<?php echo $room[0]['img']; ?>"/>
								<?php
							}
							?>
							</a>
						</div>
						<div class="vbmodalrdetails">
							<a href="<?php echo $searchdet_link; ?>" class="vbmodalframe" target="_blank"><?php VikBookingIcons::e('plus'); ?></a>
						</div>
					</div>
					<div class="vbo-info-room">
						<div class="vbdescrlistdiv">
							<h4 class="vbrowcname" id="vbroomname<?php echo $indroom.'_'.$room[0]['idroom']; ?>"><?php echo $main_listing_elem; ?></h4>
							<div class="vbrowcdescr"><?php echo $visible_descr; ?></div>
						<?php
						if ($hidden_descr) {
							// display a button to read the full description
							?>
							<div class="vbo-result-readmore-wrap">
								<a class="vbo-result-readmore-trig" href="JavaScript: void(0);"><?php echo JText::translate('VBO_READ_MORE'); ?></a>
								<div class="vbo-result-readmore-hidden" style="display: none;"><?php echo $room[0]['smalldesc']; ?></div>
							</div>
							<?php
						}
						?>
						</div>
					<?php
					if (!empty($carats)) {
						?>
						<div class="roomlist_carats">
							<?php echo $carats; ?>
						</div>
						<?php
					}
					?>
					<?php
					if ($has_promotion === true && !empty($room[0]['promotion']['promotxt'])) {
						?>
						<div class="vbo-promotion-block">
							<div class="vbo-promotion-icon"><?php VikBookingIcons::e('percentage'); ?></div>
							<div class="vbo-promotion-description">
								<?php echo $room[0]['promotion']['promotxt']; ?>
							</div>
						</div>
						<?php
					}
					?>
					</div>
				</div>
				<div class="vbcontdivtot">
					<div class="vbdivtot">
						<div class="vbdivtotinline">
							<div class="vbsrowprice">
								<div class="vbrowroomcapacity">
								<?php
								for ($i = 1; $i <= $room[0]['toadult']; $i++) {
									if ($i <= $this->arrpeople[$indroom]['adults']) {
										VikBookingIcons::e('male', 'vbo-pref-color-text');
									} else {
										VikBookingIcons::e('male', 'vbo-empty-personicn');
									}
								}
								$raw_roomcost = $tax_summary ? $room[0]['cost'] : VikBooking::sayCostPlusIva($room[0]['cost'], $room[0]['idprice']);
								?>
								</div>
								<div class="vbsrowpricediv">
									<span class="room_cost">
										<?php echo VikBooking::formatCurrencyNumber(VikBooking::numberFormat($raw_roomcost), $currencysymb, ['<span class="vbo_currency">%s</span>', '<span class="vbo_price">%s</span>']); ?>
									</span>
							<?php
							if (isset($room[0]['promotion']) && isset($room[0]['promotion']['discount'])) {
								if ($room[0]['promotion']['discount']['pcent']) {
									/**
									 * Do not make an upper-cent operation, but rather calculate the original price proportionally:
									 * final price : (100 - discount amount) = x : 100
									 * 
									 * @since 	1.13.5
									 */
									$prev_amount = $raw_roomcost * 100 / (100 - $room[0]['promotion']['discount']['amount']);
								} else {
									$prev_amount = $raw_roomcost + $room[0]['promotion']['discount']['amount'];
								}
								if ($prev_amount > 0) {
									?>
									<div class="vbo-room-result-price-before-discount">
										<span class="room_cost">
											<?php echo VikBooking::formatCurrencyNumber(VikBooking::numberFormat($prev_amount), $currencysymb, ['<span class="vbo_currency">%s</span>', '<span class="vbo_price">%s</span>']); ?>
										</span>
									</div>
									<?php
									if ($room[0]['promotion']['discount']['pcent']) {
										// hide by default the DIV containing the percent of discount
										?>
									<div class="vbo-room-result-price-before-discount-percent" style="display: none;">
										<span class="room_cost">
											<span><?php echo '-' . (float)$room[0]['promotion']['discount']['amount'] . ' %'; ?></span>
										</span>
									</div>
										<?php
									}
								}
							}
							?>
								</div>
							<?php
							if ($saylastavail === true) {
								?>
								<span class="vblastavail"><?php echo JText::sprintf('VBLASTUNITSAVAIL', $room[0]['unitsavail']); ?></span>
								<?php
							}
							?>
							</div>
							<div class="vbselectordiv">
								<button type="button" id="vbselector<?php echo $indroom.'_'.$room[0]['idroom']; ?>" class="btn vbselectr-result vbo-pref-color-btn" onclick="vbSelectRoom('<?php echo $indroom; ?>', '<?php echo $room[0]['idroom']; ?>');"><?php echo JText::translate('VBSELECTR'); ?></button>
							</div>
						</div>
					</div>
				</div>
			</div>
		<?php
	}
	?>
		</div>
	<?php
}

/**
 * Unavailable listings.
 * 
 * @since 	1.17.2 (J) - 1.7.2 (WP)
 */
if ($this->roomsnum === 1 && $this->unavailable_listings && VBOFactory::getConfig()->getBool('search_show_busy_listings', false)) {
	?>
		<div class="vbo-searchresults-party-content vbo-searchresults-party-unavailable-content">
	<?php
	foreach ($this->unavailable_listings as $listing_id) {
		// fetch the room details
		$listing_details = [VikBooking::getRoomInfo($listing_id)];
		if (!$listing_details[0]) {
			continue;
		}

		// translate records list
		$this->vbo_tn->translateContents($listing_details, '#__vikbooking_rooms');

		// convert list into associative
		$listing_details = $listing_details[0];

		// prepare CMS contents depending on platform
		$listing_details = VBORoomHelper::getInstance()->prepareCMSContents($listing_details, ['smalldesc']);

		// build listing details URI components
		$listing_uri_data = [
			'option'       => 'com_vikbooking',
			'view'         => 'roomdetails',
			'roomid'       => $listing_details['id'],
			'num_adults'   => ($this->arrpeople[1]['adults'] ?? 2),
			'num_children' => ($this->arrpeople[1]['children'] ?? 0),
			'Itemid'       => ($pitemid ?: null),
		];
		// route proper URI
		$listing_page_uri = JRoute::rewrite('index.php?' . http_build_query($listing_uri_data), false);

		// listing amenities
		$carats = VikBooking::getRoomCaratOriz($listing_details['idcarat'], $this->vbo_tn);

		// build image gallery, if available
		$gallery_data = [];
		if (!empty($listing_details['moreimgs'])) {
			$moreimages = explode(';;', $listing_details['moreimgs']);
			foreach (array_filter($moreimages) as $mimg) {
				// push thumb URL
				$gallery_data[] = $mimg;
			}
		}

		// cut off long descriptions by eventually adding a "read more" link
		$descr_length  = strlen((string) $listing_details['smalldesc']);
		$visible_descr = $listing_details['smalldesc'];
		$hidden_descr  = '';
		if ($descr_length > 200 && ($descr_length - 200) > 100) {
			$visible_descr = strip_tags($visible_descr);
			$hidden_descr = '1';
			if (function_exists('mb_substr')) {
				$visible_descr = mb_substr($visible_descr, 0, 200, 'UTF-8');
			} else {
				$visible_descr = substr($visible_descr, 0, 200);
			}
			$visible_descr .= '...';
		}

		// print unavailable listing details
		?>
			<div class="room_item room_result vbo-result-listing-unavailable" data-unavailable-id="<?php echo $listing_details['id']; ?>">
				<div class="vblistroomblock">
					<div class="vbimglistdiv">
						<div class="vbo-dots-slider-selector">
							<a href="<?php echo $listing_page_uri; ?>" target="_blank" data-gallery="<?php echo implode('|', $gallery_data); ?>">
							<?php
							if (!empty($listing_details['img']) && is_file(VBO_SITE_PATH . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . $listing_details['img'])) {
								?>
								<img class="vblistimg" alt="<?php echo htmlspecialchars($listing_details['name']); ?>" src="<?php echo VBO_SITE_URI; ?>resources/uploads/<?php echo $listing_details['img']; ?>"/>
								<?php
							}
							?>
							</a>
						</div>
						<div class="vbmodalrdetails">
							<a href="<?php echo $listing_page_uri; ?>" target="_blank"><?php VikBookingIcons::e('plus'); ?></a>
						</div>
					</div>
					<div class="vbo-info-room">
						<div class="vbdescrlistdiv">
							<h4 class="vbrowcname"><a class="vbo-search-results-listing-link" href="<?php echo $listing_page_uri; ?>" target="_blank"><?php echo $listing_details['name']; ?></a></h4>
							<div class="vbrowcdescr"><?php echo $visible_descr; ?></div>
						<?php
						if ($hidden_descr) {
							// display a button to read the full description
							?>
							<div class="vbo-result-readmore-wrap">
								<a class="vbo-result-readmore-trig" href="JavaScript: void(0);"><?php echo JText::translate('VBO_READ_MORE'); ?></a>
								<div class="vbo-result-readmore-hidden" style="display: none;"><?php echo $listing_details['smalldesc']; ?></div>
							</div>
							<?php
						}
						?>
						</div>
					<?php
					if (!empty($carats)) {
						?>
						<div class="roomlist_carats">
							<?php echo $carats; ?>
						</div>
						<?php
					}
					?>
						<div class="vbo-unavailable-block">
							<div class="vbo-unavailable-icon"><?php VikBookingIcons::e('ban'); ?></div>
							<div class="vbo-unavailable-description"><?php echo JText::translate('VBLEGBUSY'); ?></div>
						</div>
					</div>
				</div>
				<div class="vbcontdivtot">
					<div class="vbdivtot">
						<div class="vbdivtotinline">
							<div class="vbsrowprice">
								<div class="vbrowroomcapacity">
								<?php
								for ($i = 1; $i <= $listing_details['toadult']; $i++) {
									if ($i <= ($this->arrpeople[1]['adults'] ?? 2)) {
										VikBookingIcons::e('male', 'vbo-pref-color-text');
									} else {
										VikBookingIcons::e('male', 'vbo-empty-personicn');
									}
								}
								?>
								</div>
							</div>
							<div class="vbselectordiv">
								<a class="btn vbselectr-result vbo-result-unavailable vbo-pref-color-btn" href="<?php echo $listing_page_uri; ?>" target="_blank"><?php echo JText::translate('VBSEARCHRESDETAILS'); ?></a>
							</div>
						</div>
					</div>
				</div>
			</div>
		<?php
	}
	?>
		</div>
	<?php
}
?>
	</div>
</div>

<script type="text/javascript">
	jQuery(function() {

		/**
		 * Configure thumbnails gallery.
		 */
		jQuery('.vbo-dots-slider-selector').each(function() {
			var sliderLink = jQuery(this).find('a').first();
			if (!sliderLink.length) {
				return;
			}
			var gallery = sliderLink.data('gallery');
			if (!gallery || !gallery.length) {
				return;
			}
			var thumbs_base_uri = '<?php echo VBO_SITE_URI . 'resources/uploads/thumb_'; ?>';
			var gallery_data = gallery.split('|');
			var images = [];
			for (var i = 0; i < gallery_data.length; i++) {
				if (!gallery_data[i].length) {
					continue;
				}
				images.push(thumbs_base_uri + gallery_data[i]);
			}
			if (!images.length) {
				return;
			}
			// move original main photo and make it hidden so that the dialog will keep showing it
			var room_main_photo = jQuery(this).find('img.vblistimg');
			if (room_main_photo.length) {
				room_main_photo.hide().appendTo(jQuery(this).parent());
			}
			// render slider
			var slideWrap = sliderLink.clone();
			jQuery(this).html('').vikDotsSlider({
				images: images,
				navButPrevContent: '<?php VikBookingIcons::e('chevron-left'); ?>',
				navButNextContent: '<?php VikBookingIcons::e('chevron-right'); ?>',
				onDisplaySlide: function() {
					var content = jQuery(this).children().clone(true, true);
				<?php
				if (VBOPlatformDetection::isWordPress()) {
					/**
					 * @wponly 	In order to avoid delays with Fancybox, we do not re-construct the A tag.
					 * 			We just append the slide image.
					 */
					?>
					jQuery(this).html('').append(content);
					<?php
				} else {
					?>
					var link = jQuery('<a target="_blank"></a>').attr('href', slideWrap.attr('href')).attr('class', slideWrap.attr('class')).append(content);
					jQuery(this).html('').append(link);
					<?php
				}
				?>
				}
			});
		});

		/**
		 * Configure layout style actions.
		 */
		document.querySelectorAll('.vbo-results-style-option').forEach((styleEl) => {
			styleEl.addEventListener('click', (e) => {
				let styleOption = e.target;
				if (!styleOption.matches('.vbo-results-style-option')) {
					styleOption = styleOption.closest('.vbo-results-style-option');
				}
				let styleType = styleEl.getAttribute('data-type');
				let styleToggle = styleEl.getAttribute('data-toggle');
				document.querySelectorAll('.vbo-searchresults-classic-wrap').forEach((wrap) => {
					wrap.setAttribute('class', 'vbo-searchresults-classic-wrap');
					if (styleToggle) {
						wrap.classList.add(styleToggle);
					}
				});
				document.querySelectorAll('.vbo-results-style-option-active').forEach((prevActive) => {
					prevActive.classList.remove('vbo-results-style-option-active');
				});
				document.querySelectorAll('.vbo-results-style-option[data-type="' + styleType + '"]').forEach((nowActive) => {
					nowActive.classList.add('vbo-results-style-option-active');
				});
			});
		});

		/**
		 * Configure description "read more" action links.
		 */
		document.querySelectorAll('.vbo-result-readmore-trig').forEach((readMore) => {
			readMore.addEventListener('click', (e) => {
				try {
					// get full (HTML & hidden) description
					let fullDescr = readMore.closest('.vbo-result-readmore-wrap').querySelector('.vbo-result-readmore-hidden').innerHTML;
					// replace visible description
					readMore.closest('.vbdescrlistdiv').querySelector('.vbrowcdescr').innerHTML = fullDescr;
				} catch(e) {
					console.error(e);
				}
				
				// delete link from DOM
				readMore.remove();
			});
		});

		/**
		 * Configure results hovering effects on map.
		 */
		document.querySelectorAll('.vbo-searchresults-classic-wrap .room_item:not(.vbo-result-listing-unavailable)').forEach((resultEl) => {
			let resultData = resultEl.getAttribute('id').split('_');
			let resultId = resultData[1] || 0;
			resultEl.addEventListener('mouseenter', () => {
				const mapTarget = document.querySelector('.vbo-map-listing-cost[data-room-id="' + resultId + '"]');
				if (mapTarget) {
					mapTarget.classList.add('vbo-map-listing-cost-highlight');
				}
				if (typeof vbo_geomarker_units !== 'undefined' && typeof vbo_geomarker_units[resultId + '_1'] !== 'undefined') {
					// take marker on top of the others overlapping the same zoomed coordinates
					vbo_geomarker_units[resultId + '_1'].zIndex = 9999;
				}
			});
			resultEl.addEventListener('mouseleave', () => {
				const mapTarget = document.querySelector('.vbo-map-listing-cost[data-room-id="' + resultId + '"]');
				if (mapTarget) {
					mapTarget.classList.remove('vbo-map-listing-cost-highlight');
				}
				if (typeof vbo_geomarker_units !== 'undefined' && typeof vbo_geomarker_units[resultId + '_1'] !== 'undefined') {
					// restore marker z-index property
					vbo_geomarker_units[resultId + '_1'].zIndex = 1;
				}
			});
		});

		/**
		 * Register event for activating the search filters.
		 */
		const filtersBtn = document.querySelector('.vbo-results-filters-toggle');
		if (filtersBtn) {
			filtersBtn.addEventListener('click', () => {
				// modal cancel filters button
				let filtersCancelBtn = document.createElement('button');
				filtersCancelBtn.setAttribute('type', 'button');
				filtersCancelBtn.classList.add('btn', 'vbo-pref-color-btn-secondary', 'vbo-modal-dismiss-btn');
				filtersCancelBtn.innerText = <?php echo json_encode(JText::translate('VBDIALOGBTNCANCEL')); ?>;
				filtersCancelBtn.addEventListener('click', () => {
					let filtersForm = document.querySelector('form#vbo-results-filters-form');
					if (filtersForm) {
						// clean up checkbox and radio elements
						filtersForm
							.querySelectorAll('input[type="checkbox"]:checked, input[type="radio"]:checked')
							.forEach((el) => {
								el.checked = false;
							});
						// clean up input text, number and textarea elements
						filtersForm
							.querySelectorAll('input[type="text"], input[type="number"], textarea')
							.forEach((el) => {
								el.value = '';
							});
						// clean up select elements
						filtersForm
							.querySelectorAll('select')
							.forEach((el) => {
								el.value = '';
							});
						// clean up range elements
						filtersForm
							.querySelectorAll('input[type="range"]')
							.forEach((el) => {
								el.disabled = true;
							});
						// submit the form with no filters set
						filtersForm.submit();
					}
					VBOCore.emitEvent('vbo-dismiss-search-results-filters-modal');
				});

				// modal apply filters button
				let filtersApplyBtn = document.createElement('button');
				filtersApplyBtn.setAttribute('type', 'button');
				filtersApplyBtn.classList.add('btn', 'vbo-pref-color-btn', 'vbo-modal-apply-btn');
				filtersApplyBtn.innerText = <?php echo json_encode(JText::translate('VBSUBMITCOUPON')); ?>;
				filtersApplyBtn.addEventListener('click', () => {
					let filtersForm = document.querySelector('form#vbo-results-filters-form');
					if (filtersForm) {
						// submit the form with the current filters set
						filtersForm.submit();
					}
					VBOCore.emitEvent('vbo-dismiss-search-results-filters-modal');
				});

				// display modal
				let filtersModal = VBOCore.displayModal({
					suffix: 'search-results-filters-modal',
					title: <?php echo json_encode(JText::translate('VBO_FILTERS')); ?>,
					extra_class: 'vbo-modal-tall vbo-modal-sticky-head vbo-modal-sticky-footer',
					body_prepend: true,
                	lock_scroll: true,
					dismiss_event: 'vbo-dismiss-search-results-filters-modal',
					onDismiss: () => {
						document
							.querySelector('.vbo-results-filters-form-helper')
							.append(
								document.querySelector('.vbo-results-filters-form-wrapper')
							);
					},
					footer_left: filtersCancelBtn,
					footer_right: filtersApplyBtn,
				});

				// populate modal body
				filtersModal[0].append(document.querySelector('.vbo-results-filters-form-wrapper'));
			});
		}
	});
</script>
