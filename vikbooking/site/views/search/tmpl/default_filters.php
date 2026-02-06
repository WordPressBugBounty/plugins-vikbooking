<?php
/**
 * @package     VikBooking
 * @subpackage  com_vikbooking
 * @author      Alessio Gaggii - E4J srl
 * @copyright   Copyright (C) 2025 E4J srl. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 * @link        https://vikwp.com
 */

defined('ABSPATH') or die('No script kiddies please!');

/**
 * Search results filtering.
 */

$input = JFactory::getApplication()->input;

$currencysymb = VikBooking::getCurrencySymb();
$currencypos  = VikBooking::getCurrencyPosition();
$nowdf = VikBooking::getDateFormat();
if ($nowdf == "%d/%m/%Y") {
	$df = 'd/m/Y';
} elseif ($nowdf == "%m/%d/%Y") {
	$df = 'm/d/Y';
} else {
	$df = 'Y/m/d';
}

// load categories and amenities, if any
$categories = VikBooking::showCategoriesFront() ? VikBooking::getAvailabilityInstance(true)->loadRoomCategories($public = true) : [];
$amenities = VikBooking::loadRoomAmenities('', $this->vbo_tn, $all = true);

if ($categories) {
	// translate categories
	$this->vbo_tn->translateContents($categories, '#__vikbooking_categories');
}

// collect current filters set
$current_filters = (array) $input->get('filters', [], 'array');

// gather current request values
$forced_category = $input->getString('categories', '');
$forced_category_id = $input->getString('category_id', '');
$forced_categories = array_values(array_filter(array_map('intval', (array) $input->get('category_ids', [], 'array'))));
$pageItemId = $input->getUInt('Itemid', 0);

// build form action for applying the filters
$formQueryArgs = [
	'option'      => 'com_vikbooking',
	'view'        => 'vikbooking',
	'category_id' => $forced_category_id,
	'Itemid'      => $pageItemId,
];
$formAction = JRoute::rewrite('index.php?' . http_build_query(array_filter($formQueryArgs)));

/**
 * Trigger event to allow third party plugins to define additional search results filtering options.
 * 
 * @since 	1.18.3 (J) - 1.8.3 (WP)
 */
$filteringOptions = [];
VBOFactory::getPlatform()->getDispatcher()->trigger('onDisplaySearchResultsFiltering', [&$filteringOptions]);

?>
<div class="vbo-results-filters-form-wrapper">
	<form action="<?php echo $formAction; ?>" method="post" id="vbo-results-filters-form">
		<input type="hidden" name="option" value="com_vikbooking"/>
		<input type="hidden" name="task" value="search"/>
		<input type="hidden" name="checkindate" value="<?php echo date($df, $this->checkin); ?>"/>
		<input type="hidden" name="checkoutdate" value="<?php echo date($df, $this->checkout); ?>"/>
		<input type="hidden" name="roomsnum" value="<?php echo $this->roomsnum; ?>"/>
	<?php
	foreach ($this->arrpeople as $aduchild) {
		?>
		<input type="hidden" name="adults[]" value="<?php echo $aduchild['adults']; ?>"/>
		<input type="hidden" name="children[]" value="<?php echo $aduchild['children'] ?? 0; ?>"/>
		<?php
	}
	if ($forced_category && $forced_category != 'all' && !$categories) {
		// it is necessary to keep the current hidden category filter
		?>
		<input type="hidden" name="categories" value="<?php echo $forced_category; ?>"/>
		<?php
	}
	if ($forced_categories && !$categories) {
		// it is necessary to keep the current hidden category filters
		foreach ($forced_categories as $cat_id) {
			?>
		<input type="hidden" name="category_ids" value="<?php echo $cat_id; ?>"/>
			<?php
		}
	}
	if ($pageItemId) {
		?>
		<input type="hidden" name="Itemid" value="<?php echo $pageItemId; ?>"/>
		<?php
	}
	if ($categories) {
		$current_categories = (array) ($current_filters['category'] ?? null);
		if ($forced_category && $forced_category != 'all') {
			$current_categories[] = (int) $forced_category;
		}
		if ($forced_category_id) {
			$current_categories[] = (int) $forced_category_id;
		}
		if ($forced_categories) {
			$current_categories = array_merge($current_categories, $forced_categories);
		}
		?>
		<div class="vbo-results-filters-group" data-group="categories">
			<h4><?php echo JText::translate('VBCAT'); ?></h4>
			<div class="vbo-results-filter-entries">
			<?php
			foreach ($categories as $category) {
				?>
				<div class="vbo-results-filter-entry" data-filter="category">
					<input type="checkbox" name="filters[category][]" id="filter-category-<?php echo $category['id']; ?>" value="<?php echo $category['id']; ?>" <?php echo in_array($category['id'], $current_categories) ? 'checked ' : ''; ?>/>
					<label class="vbo-results-filter-entry-name" for="filter-category-<?php echo $category['id']; ?>"><?php echo $category['name']; ?></label>
				</div>
				<?php
			}
			?>
			</div>
		</div>
		<?php

	}
	if ($amenities) {
		$current_amenities = (array) ($current_filters['amenity'] ?? null);
		?>
		<div class="vbo-results-filters-group" data-group="amenities">
			<h4><?php echo JText::translate('VBO_AMENITIES'); ?></h4>
			<div class="vbo-results-filter-entries">
			<?php
			foreach ($amenities as $amenity) {
				?>
				<div class="vbo-results-filter-entry" data-filter="amenity">
					<input type="checkbox" name="filters[amenity][]" id="filter-amenity-<?php echo $amenity['id']; ?>" value="<?php echo $amenity['id']; ?>" <?php echo in_array($amenity['id'], $current_amenities) ? 'checked ' : ''; ?>/>
					<label class="vbo-results-filter-entry-name" for="filter-amenity-<?php echo $amenity['id']; ?>">
						<span class="vbo-results-filter-amenity">
						<?php
						if (!empty($amenity['textimg'])) {
							// tooltip icon text is not empty
							if (!empty($amenity['icon'])) {
								// an icon has been uploaded: display the image
								?>
								<img class="vbo-results-filter-amenity-img" src="<?php echo VBO_SITE_URI . 'resources/uploads/' . $amenity['icon']; ?>" alt="<?php echo htmlspecialchars((string) $amenity['name'], ENT_QUOTES, 'UTF-8'); ?>" />
								<span class="vbo-results-filter-amenity-name"><?php echo $amenity['textimg']; ?></span>
								<?php
							} else {
								if (strpos($amenity['textimg'], '</i>') !== false || strpos($amenity['textimg'], '<svg') !== false) {
									// the tooltip icon text is a font-icon or an SVG field, we can use the name as tooltip
									echo $amenity['textimg'];
									?>
									<span class="vbo-results-filter-amenity-name"><?php echo $amenity['name']; ?></span>
									<?php
								} else {
									// display just the text
									?>
									<span class="vbo-results-filter-amenity-name"><?php echo $amenity['textimg']; ?></span>
									<?php
								}
							}
						} else {
							if (!empty($amenity['icon'])) {
								?>
								<img class="vbo-results-filter-amenity-img" src="<?php echo VBO_SITE_URI . 'resources/uploads/' . $amenity['icon']; ?>" alt="<?php echo htmlspecialchars((string) $amenity['name'], ENT_QUOTES, 'UTF-8'); ?>" />
								<span class="vbo-results-filter-amenity-name"><?php echo $amenity['name']; ?></span>
								<?php
							} else {
								?>
								<span class="vbo-results-filter-amenity-name"><?php echo $amenity['name']; ?></span>
								<?php
							}
						}
						?>
						</span>
					</label>
				</div>
				<?php
			}
			?>
			</div>
		</div>
		<?php
	}

	// price range filter
	if (count($this->rates_pool) > 1) {
		$min_room_cost = min($this->rates_pool);
		$max_room_cost = max($this->rates_pool);
		?>
		<div class="vbo-results-filters-group" data-group="price-range">
			<h4><?php echo JText::translate('VBO_PRICE_RANGE'); ?></h4>
			<div class="vbo-results-filter-entries">
				<?php
				echo VikBooking::getVboApplication()->renderDualSlider([
					'min'          => $min_room_cost,
					'max'          => $max_room_cost,
					'step'         => 'auto',
					'class'        => 'vbo-search-results-filter-price-range',
					'min_def'      => (float) ($current_filters['price']['min'] ?? $min_room_cost),
					'max_def'      => (float) ($current_filters['price']['max'] ?? $max_room_cost),
					'min_name'     => 'filters[price][min]',
					'max_name'     => 'filters[price][max]',
					'range_tpl'    => '%s',
					'value_format' => ($currencypos === 'after' ? "%d {$currencysymb} - %d {$currencysymb}" : "{$currencysymb} %d - {$currencysymb} %d"),
				]);
				?>
			</div>
		</div>
		<?php
	}

	// handle custom filtering options
	foreach ($filteringOptions as $sectionTitle => $sectionHtml) {
		?>
		<div class="vbo-results-filters-group" data-group="<?php echo preg_replace('/[^a-z0-9\-_]+/i', '', $sectionTitle); ?>">
			<h4><?php echo $sectionTitle; ?></h4>
			<div class="vbo-results-filter-entries">
				<?php echo $sectionHtml; ?>
			</div>
		</div>
		<?php
	}
	?>
	</form>
</div>
