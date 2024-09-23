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
 * Helper Class to obtain the proper font class depending on the version chosen.
 * Fonts supported are FontAwesome 4.7, 5.5 and IcoMoon.
 * This class may also be accessed by VCM, which may not include any other libraries.
 * 
 * @since 	1.11 (J) - 1.1 (WP)
 */
final class VikBookingIcons
{
	/**
	 * Whether to load the old FA version for legacy
	 * 
	 * @var 	boolean
	 */
	public static $fa_use_legacy = false;

	/**
	 * The name of the CSS file to load for the old FA 4.7
	 * 
	 * @var 	string
	 */
	public static $fa_file_v47 = 'font-awesome.min.css';
	
	/**
	 * The list of CSS files to load for the latest FA 5.5.
	 * To save up resources, we only load the solid icons.
	 * 
	 * @var 	array
	 */
	public static $fa_file_latest = [
		'fontawesome.min.css',
		'solid.min.css',
		'regular.min.css',
	];

	/**
	 * The list of CSS files that could be loaded remotely for the latest FA 5.5.
	 * This is in case the brands icons are needed.
	 * 
	 * @var 	array
	 */
	public static $fa_remote_assets_latest = [
		'https://use.fontawesome.com/releases/v5.12.1/css/brands.css',
	];

	/**
	 * Default font style prefix (Solid).
	 * 
	 * @var 	string
	 */
	private static $fa_default_style = 'fas';

	/**
	 * List of type-classes pairs for the icons that need specific
	 * classes (not solid) in the latest FontAwesome version.
	 * 
	 * @var 	array
	 */
	private static $fa_latest_map = [
		'calendar' 			   => 'far fa-calendar-alt',
		'refresh'  			   => 'fas fa-sync-alt',
		'external-link'  	   => 'fas fa-external-link-alt',
		'external-link-square' => 'fas fa-external-link-square-alt',
		'sort-asc' 			   => 'fas fa-sort-up',
		'sort-desc' 		   => 'fas fa-sort-down',
		'commenting' 		   => 'fas fa-comments',
		'file-text' 		   => 'fas fa-file-alt',
		'file-text-o' 		   => 'far fa-file-alt',
		'sign-in' 			   => 'fas fa-sign-in-alt',
		'sign-out' 			   => 'fas fa-sign-out-alt',
		'edit' 				   => 'far fa-edit',
		'clock' 			   => 'far fa-clock',
		'clock-o' 			   => 'far fa-clock',
		'calendar-check' 	   => 'far fa-calendar-check',
		'envelope-o' 		   => 'far fa-envelope',
		'mobile' 			   => 'fas fa-mobile-alt',
		'tablet' 			   => 'fas fa-tablet-alt',
		'pie-chart' 		   => 'fas fa-chart-pie',
		'credit-card' 		   => 'far fa-credit-card',
		'sticky-note' 		   => 'fas fa-sticky-note',
		'invoice' 			   => 'fas fa-file-invoice',
		'snowflake' 		   => 'far fa-snowflake',
		'long-arrow-up' 	   => 'fas fa-long-arrow-alt-up',
		'long-arrow-right' 	   => 'fas fa-long-arrow-alt-right',
		'long-arrow-down' 	   => 'fas fa-long-arrow-alt-down',
		'long-arrow-left' 	   => 'fas fa-long-arrow-alt-left',
	];

	/**
	 * Loads the necessary assets for the requested font family.
	 * 
	 * @param 	string 	$font 	the font identifier to load
	 *
	 * @return 	void
	 * 
	 * @since 	1.16.5 (J) - 1.6.5 (WP) added event to implement custom loadings.
	 */
	public static function loadAssets($font = 'fa')
	{
		$document = JFactory::getDocument();

		// trigger event to allow third-party plugins to prevent this loading and to load custom assets
		$load_assets = VBOFactory::getPlatform()->getDispatcher()->filter('onLoadFontAssets', [$document, $font]);
		if ($load_assets === false) {
			return;
		}

		if ($font == 'fa') {
			// get list of assets files to load
			$font_files = self::$fa_use_legacy ? self::$fa_file_v47 : self::$fa_file_latest;
			if (is_scalar($font_files)) {
				$font_files = [$font_files];
			}

			// load files
			$baseuri = VBO_SITE_URI . 'resources/';
			foreach ($font_files as $kf => $ff) {
				// we allow the use of an array with the URI expressed in the key
				$useuri = !is_numeric($kf) ? $kf : $baseuri;
				// load the file
				$document->addStyleSheet($useuri . $ff);
			}
		}

		if ($font == 'icomoon') {
			// we only need this file for IcoMoon
			$document->addStyleSheet(VBO_ADMIN_URI . 'resources/fonts/vboicomoon.css');
		}
	}

	/**
	 * Loads the remote assets from the CDN.
	 *
	 * @return 	bool
	 * 
	 * @since 	1.16.5 (J) - 1.6.5 (WP) added event to implement custom loadings.
	 */
	public static function loadRemoteAssets()
	{
		$document = JFactory::getDocument();

		// trigger event to allow third-party plugins to prevent this loading and to load custom assets
		$load_assets = VBOFactory::getPlatform()->getDispatcher()->filter('onLoadFontRemoteAssets', [$document]);
		if ($load_assets === false) {
			return false;
		}

		if (self::$fa_use_legacy) {
			return flase;
		}

		foreach (self::$fa_remote_assets_latest as $cdn_url) {
			$document->addStyleSheet($cdn_url);
		}

		return true;
	}

	/**
	 * Gets the proper class name for the icon depending on the type
	 * and on the FA version currently in use.
	 * 
	 * @param 	string 	$type 		the icon identifier.
	 * @param 	string 	$classes 	a string with some optional classes.
	 * @param 	string 	$style 		optional font icon style ("fas", "fab"..).
	 *
	 * @return 	string 	the full class name of the icon to load.
	 * 
	 * @since 	1.16.5 (J) - 1.6.5 (WP) added 3rd argument $style.
	 */
	public static function i($type, $classes = '', $style = '')
	{
		$classes = !empty($classes) ? ' ' . ltrim($classes) : $classes;

		if (substr($type, 0, 6) == 'vboicn') {
			// no mapping required for IcoMoon
			return $type . $classes;
		}

		// we can force the loading of a specific (full) and exact class, like for brands, in this case just return it
		if (substr($type, 0, 2) == 'fa' && strpos($type, ' ') > 2 && strpos($type, 'fa-') !== false && strlen($type) > 5) {
			// this is something like fas, far or fab, so we just print that
			if (self::$fa_use_legacy) {
				// strip anything that is not fa
				$extratype = substr($type, 2, 1);
				$type = str_replace('fa' . $extratype, 'fa', $type);
			}

			return "{$type}{$classes}";
		}

		if (self::$fa_use_legacy) {
			// FontAwesome 4.7 had no solid or regular styles
			return "fa fa-{$type}{$classes}";
		}

		// get the map to eventually adjust or override certain icons
		$override_map = self::getOverridesMap();

		if (isset($override_map[$type])) {
			// this type of icon requires a specific class for the latest FontAwesome version
			return $override_map[$type] . $classes;
		}

		// build font style (default to solid "fas")
		$fstyle = !empty($style) ? $style : self::getDefaultFontStyle();

		// by default we use the solid style for the latest FontAwesome version
		return "{$fstyle} fa-{$type}{$classes}";
	}

	/**
	 * Echoes the requested icon string by passing all arguments to i().
	 *
	 * @return 	void
	 *
	 * @uses 	i()
	 */
	public static function e()
	{
		$params = func_get_args();

		$icn_class = call_user_func_array(['VikBookingIcons', 'i'], $params);

		echo '<i class="' . $icn_class . '"></i>';
	}

	/**
	 * Returns a list of default font-icons for characteristics.
	 * 
	 * @param 	array 	$exclude_html 	a list of HTML full i tags to exclude.
	 * @param 	string 	$extra_class 	the default extra class for the icon tags.
	 * @param 	bool 	$sort 			whether the icons should be sorted by name ASC.
	 *
	 * @return 	array 	a list of pre-set icons into associative arrays.
	 * 
	 * @since 	1.13.5
	 */
	public static function loadCharacteristicsPreset($exclude_html = [], $extra_class = 'vbo-icn-carat', $sort = true)
	{
		$preset_icons = [
			[
				'name' 	=> 'Square Meters',
				'class' => self::i('cube', $extra_class),
			],
			[
				'name' 	=> 'Swimming Pool',
				'class' => self::i('swimming-pool', $extra_class),
			],
			[
				'name' 	=> 'Wi-Fi',
				'class' => self::i('wifi', $extra_class),
			],
			[
				'name' 	=> 'TV',
				'class' => self::i('tv', $extra_class),
			],
			[
				'name' 	=> 'Air Conditioning',
				'class' => self::i('snowflake', $extra_class),
			],
			[
				'name' 	=> 'Mini Bar',
				'class' => self::i('cocktail', $extra_class),
			],
			[
				'name' 	=> 'Extra Bed',
				'class' => self::i('bed', $extra_class),
			],
			[
				'name' 	=> 'Disabled Access',
				'class' => self::i('wheelchair', $extra_class),
			],
			[
				'name' 	=> 'Bath',
				'class' => self::i('bath', $extra_class),
			],
			[
				'name' 	=> 'Shower',
				'class' => self::i('shower', $extra_class),
			],
			[
				'name' 	=> 'No Smoking',
				'class' => self::i('smoking-ban', $extra_class),
			],
			[
				'name' 	=> 'Smoking',
				'class' => self::i('smoking', $extra_class),
			],
			[
				'name' 	=> 'Coffee',
				'class' => self::i('coffee', $extra_class),
			],
			[
				'name' 	=> 'Tea Mug',
				'class' => self::i('mug-hot', $extra_class),
			],
			[
				'name' 	=> 'Kitchen Utensils',
				'class' => self::i('utensils', $extra_class),
			],
			[
				'name' 	=> 'Gift',
				'class' => self::i('gift', $extra_class),
			],
			[
				'name' 	=> 'Terrace',
				'class' => self::i('umbrella-beach', $extra_class),
			],
			[
				'name' 	=> 'Mountain',
				'class' => self::i('mountain', $extra_class),
			],
			[
				'name' 	=> 'Landscape',
				'class' => self::i('image', $extra_class),
			],
			[
				'name' 	=> 'Nature',
				'class' => self::i('tree', $extra_class),
			],
			[
				'name' 	=> 'City',
				'class' => self::i('city', $extra_class),
			],
			[
				'name' 	=> 'Sea',
				'class' => self::i('water', $extra_class),
			],
		];

		// check if some icons should be unset, maybe because already in use
		$exclude_html = !is_array($exclude_html) ? [] : $exclude_html;
		foreach ($exclude_html as $html) {
			if (empty($html)) {
				continue;
			}
			foreach ($preset_icons as $k => $v) {
				if (strpos($html, $v['class']) !== false) {
					// this class matches in the HTML passed, unset it
					unset($preset_icons[$k]);
					break;
				}
			}
		}

		// sort icons by name
		if ($sort) {
			$names_map = [];
			foreach ($preset_icons as $k => $v) {
				$names_map[$k] = $v['name'];
			}
			asort($names_map);
			$sorted_preset = [];
			foreach ($names_map as $k => $v) {
				if (!isset($preset_icons[$k])) {
					continue;
				}
				array_push($sorted_preset, $preset_icons[$k]);
			}
			$preset_icons = $sorted_preset;
		}

		// return the list
		return $preset_icons;
	}

	/**
	 * Loads the font icons override map.
	 *
	 * @return 	array
	 * 
	 * @since 	1.16.5 (J) - 1.6.5 (WP).
	 */
	private static function getOverridesMap()
	{
		static $icons_map = null;

		if ($icons_map) {
			return $icons_map;
		}

		$icons_map = self::$fa_latest_map;

		// trigger event to allow third-party plugins to override the icons map
		VBOFactory::getPlatform()->getDispatcher()->trigger('onBuildFontOverridesMap', [&$icons_map]);

		return $icons_map ?: [];
	}

	/**
	 * Gets the default font style.
	 *
	 * @return 	string
	 * 
	 * @since 	1.16.5 (J) - 1.6.5 (WP).
	 */
	private static function getDefaultFontStyle()
	{
		static $icons_style = null;

		if ($icons_style !== null) {
			return $icons_style;
		}

		$icons_style = self::$fa_default_style;

		// trigger event to allow third-party plugins to override the default icons style
		VBOFactory::getPlatform()->getDispatcher()->trigger('onGetFontDefaultStyle', [&$icons_style]);

		return (string)$icons_style;
	}
}
