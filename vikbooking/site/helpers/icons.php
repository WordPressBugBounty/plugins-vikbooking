<?php
/**
 * @package     VikBooking
 * @subpackage  com_vikbooking
 * @author      Alessio Gaggii - E4J srl
 * @copyright   Copyright (C) 2026 E4J srl. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 * @link        https://vikwp.com
 */

defined('ABSPATH') or die('No script kiddies please!');

/**
 * Helper class to load webfont utilities and render font icons.
 * Fonts supported are FontAwesome and IcoMoon.
 * 
 * @since   1.11 (J) - 1.1 (WP)
 * @since   1.18.8 (J) - 1.8.8 (WP) adopted FA 7.2.0 as default webfont library in favour of FA 5.12.1.
 */
final class VikBookingIcons
{   
    /**
     * The list of CSS files to load for the FontAwesome webfonts.
     * To save up resources, we only load the solid and regular icon styles.
     * 
     * @var     array
     */
    public static $fa_default_styles = [
        'fontawesome.min.css',
        'solid.min.css',
        'regular.min.css',
    ];

    /**
     * Associative list of known webfont style assets and related (relative) URL.
     * 
     * @var     array
     * 
     * @since   1.18.8 (J) - 1.8.8 (WP)
     */
    public static $fa_style_assets = [
        'brands' => 'brands.min.css',
    ];

    /**
     * The list of CSS files that can be loaded from remote CDN URLs.
     * 
     * @var     array
     * 
     * @deprecated  1.18.8 (J) - 1.8.8 (WP)
     */
    public static $fa_remote_assets = [];

    /**
     * Default font style prefix (Solid).
     * 
     * @var     string
     */
    private static $fa_default_style = 'fas';

    /**
     * List of name-class pairs for the icons that need specific
     * classes or adjustments in the current FontAwesome version.
     * 
     * @var     array
     */
    private static $fa_resolve_map = [
        'calendar'             => 'far fa-calendar-days',
        'refresh'              => 'fas fa-rotate',
        'sync'                 => 'fas fa-arrows-rotate',
        'external-link'        => 'fas fa-arrow-up-right-from-square',
        'external-link-square' => 'fas fa-square-up-right',
        'sort-asc'             => 'fas fa-sort-up',
        'sort-desc'            => 'fas fa-sort-down',
        'commenting'           => 'fas fa-comments',
        'file-text'            => 'far fa-file-lines',
        'file-text-o'          => 'fas fa-file-lines',
        'sign-in'              => 'fas fa-right-to-bracket',
        'sign-out'             => 'fas fa-right-from-bracket',
        'edit'                 => 'far fa-pen-to-square',
        'clock'                => 'far fa-clock',
        'clock-o'              => 'fas fa-clock',
        'calendar-check'       => 'far fa-calendar-check',
        'envelope-o'           => 'far fa-envelope',
        'mobile'               => 'fas fa-mobile-screen-button',
        'tablet'               => 'fas fa-tablet-screen-button',
        'pie-chart'            => 'fas fa-chart-pie',
        'credit-card'          => 'far fa-credit-card',
        'sticky-note'          => 'fas fa-note-sticky',
        'invoice'              => 'fas fa-file-invoice',
        'snowflake'            => 'far fa-snowflake',
        'long-arrow-up'        => 'fas fa-arrow-up-long',
        'long-arrow-right'     => 'fas fa-arrow-right-long',
        'long-arrow-down'      => 'fas fa-arrow-down-long',
        'long-arrow-left'      => 'fas fa-arrow-left-long',
        'th'                   => 'fas fa-table-cells',
        'ellipsis-h'           => 'fas fa-ellipsis',
        'ellipsis-v'           => 'fas fa-ellipsis-vertical',
        'magic'                => 'fas fa-wand-magic-sparkles',
        'cog'                  => 'fas fa-gear',
        'cogs'                 => 'fas fa-gears',
        'user-cog'             => 'fas fa-user-gear',
        'circle-question'      => 'far fa-circle-question',

        /**
         * Resolving map used until the older FA v5.12.1.
         *
        '__fa5' => [
            'calendar'             => 'far fa-calendar-alt',
            'refresh'              => 'fas fa-sync-alt',
            'external-link'        => 'fas fa-external-link-alt',
            'external-link-square' => 'fas fa-external-link-square-alt',
            'sort-asc'             => 'fas fa-sort-up',
            'sort-desc'            => 'fas fa-sort-down',
            'commenting'           => 'fas fa-comments',
            'file-text'            => 'fas fa-file-alt',
            'file-text-o'          => 'far fa-file-alt',
            'sign-in'              => 'fas fa-sign-in-alt',
            'sign-out'             => 'fas fa-sign-out-alt',
            'edit'                 => 'far fa-edit',
            'clock'                => 'far fa-clock',
            'clock-o'              => 'far fa-clock',
            'calendar-check'       => 'far fa-calendar-check',
            'envelope-o'           => 'far fa-envelope',
            'mobile'               => 'fas fa-mobile-alt',
            'tablet'               => 'fas fa-tablet-alt',
            'pie-chart'            => 'fas fa-chart-pie',
            'credit-card'          => 'far fa-credit-card',
            'sticky-note'          => 'fas fa-sticky-note',
            'invoice'              => 'fas fa-file-invoice',
            'snowflake'            => 'far fa-snowflake',
            'long-arrow-up'        => 'fas fa-long-arrow-alt-up',
            'long-arrow-right'     => 'fas fa-long-arrow-alt-right',
            'long-arrow-down'      => 'fas fa-long-arrow-alt-down',
            'long-arrow-left'      => 'fas fa-long-arrow-alt-left',
        ],
         *
         */
    ];

    /**
     * Loads the necessary assets for the requested font family.
     * 
     * @param   string  $font   the font identifier to load
     *
     * @return  void
     * 
     * @since   1.16.5 (J) - 1.6.5 (WP) added event to implement custom loadings.
     */
    public static function loadAssets($font = 'fa')
    {
        $document = JFactory::getDocument();

        // trigger event to allow third-party plugins to prevent this loading and to load custom assets
        $load_assets = VBOFactory::getPlatform()->getDispatcher()->filter('onLoadFontAssets', [$document, $font]);
        if ($load_assets === false) {
            return;
        }

        $versioning = [
            'version' => defined('VIKBOOKING_SOFTWARE_VERSION') ? VIKBOOKING_SOFTWARE_VERSION : '1.0',
        ];

        if ($font == 'fa') {
            // get list of default asset files to load
            $font_files = self::$fa_default_styles;
            if (is_scalar($font_files)) {
                $font_files = [$font_files];
            }

            // load files
            $baseuri = VBO_SITE_URI . 'resources/';
            foreach ($font_files as $kf => $ff) {
                // we allow the use of an array with the URI expressed in the key
                $useuri = !is_numeric($kf) ? $kf : $baseuri;
                // load the file
                $document->addStyleSheet($useuri . $ff, $versioning);
            }
        }

        if ($font == 'icomoon') {
            // we only need this file for IcoMoon
            $document->addStyleSheet(VBO_ADMIN_URI . 'resources/fonts/vboicomoon.css', $versioning);
        }
    }

    /**
     * Loads a specific webfont stylesheet asset, usually not loaded by default.
     * 
     * @param   string  $style  Known style identifier or stylesheet URL.
     * 
     * @return  bool    True if an asset was loaded, false otherwise.
     * 
     * @since   1.18.8 (J) - 1.8.8 (WP)
     */
    public static function loadAssetStyle(string $style)
    {
        $document = JFactory::getDocument();

        // trigger event to allow third-party plugins to prevent this loading and to load custom assets
        $load_asset = VBOFactory::getPlatform()->getDispatcher()->filter('onLoadFontAssetStyle', [$document, $style]);
        if ($load_asset === false) {
            return false;
        }

        if (preg_match('/^http/', $style)) {
            // load asset style URL
            $document->addStyleSheet($style);
            return true;
        }

        $versioning = [
            'version' => defined('VIKBOOKING_SOFTWARE_VERSION') ? VIKBOOKING_SOFTWARE_VERSION : '1.0',
        ];

        if (self::$fa_style_assets[$style] ?? null) {
            // load known and local webfont style asset
            $document->addStyleSheet(VBO_SITE_URI . 'resources/' . self::$fa_style_assets[$style], $versioning);
            return true;
        }

        return false;
    }

    /**
     * Loads the remote assets from the CDN, if any, by invoking third-party plugins.
     *
     * @return  bool
     * 
     * @since   1.16.5 (J) - 1.6.5 (WP) added event to implement custom loadings.
     * @since   1.18.8 (J) - 1.8.8 (WP) no more remote assets to load, FA brands asset loaded by default for BC.
     * 
     * @see     loadAssetStyle()
     */
    public static function loadRemoteAssets()
    {
        static $loaded = null;

        if ($loaded !== null) {
            return true;
        }

        $loaded = true;

        $document = JFactory::getDocument();

        // trigger event to allow third-party plugins to prevent this loading and to load custom assets
        $load_assets = VBOFactory::getPlatform()->getDispatcher()->filter('onLoadFontRemoteAssets', [$document]);
        if ($load_assets === false) {
            return false;
        }

        /**
         * For BC this method will no longer load remote assets, but it will
         * rather load the FA "brands" style asset through the new method.
         * 
         * @since   1.18.8 (J) - 1.8.8 (WP)
         */
        self::loadAssetStyle('brands');

        // scan remote assets list property, even if empty by default
        foreach (self::$fa_remote_assets as $cdn_url) {
            $document->addStyleSheet($cdn_url);
        }

        return true;
    }

    /**
     * Gets the proper class name for the icon depending on the type
     * and on the FA version currently in use.
     * 
     * @param   string  $type       the icon identifier.
     * @param   string  $classes    a string with some optional classes.
     * @param   string  $style      optional font icon style ("fas", "fab"..).
     *
     * @return  string  the full class name of the icon to load.
     * 
     * @since   1.16.5 (J) - 1.6.5 (WP) added 3rd argument $style.
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
            return "{$type}{$classes}";
        }

        // get the map to eventually adjust or override certain icons
        $override_map = self::getOverridesMap();

        if (isset($override_map[$type])) {
            // this type of icon requires a specific class for the current FontAwesome version
            return $override_map[$type] . $classes;
        }

        // build font style (default to solid "fas")
        $fstyle = !empty($style) ? $style : self::getDefaultFontStyle();

        // by default we use the solid style for the current FontAwesome version
        return "{$fstyle} fa-{$type}{$classes}";
    }

    /**
     * Echoes the requested icon string by passing all arguments to i().
     *
     * @return  void
     *
     * @uses    i()
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
     * @param   array   $exclude_html   a list of HTML full i tags to exclude.
     * @param   string  $extra_class    the default extra class for the icon tags.
     * @param   bool    $sort           whether the icons should be sorted by name ASC.
     *
     * @return  array   a list of pre-set icons into associative arrays.
     * 
     * @since   1.13.5
     */
    public static function loadCharacteristicsPreset($exclude_html = [], $extra_class = 'vbo-icn-carat', $sort = true)
    {
        $preset_icons = [
            [
                'name'  => 'Square Meters',
                'class' => self::i('cube', $extra_class),
            ],
            [
                'name'  => 'Swimming Pool',
                'class' => self::i('swimming-pool', $extra_class),
            ],
            [
                'name'  => 'Wi-Fi',
                'class' => self::i('wifi', $extra_class),
            ],
            [
                'name'  => 'TV',
                'class' => self::i('tv', $extra_class),
            ],
            [
                'name'  => 'Air Conditioning',
                'class' => self::i('snowflake', $extra_class),
            ],
            [
                'name'  => 'Mini Bar',
                'class' => self::i('cocktail', $extra_class),
            ],
            [
                'name'  => 'Extra Bed',
                'class' => self::i('bed', $extra_class),
            ],
            [
                'name'  => 'Disabled Access',
                'class' => self::i('wheelchair', $extra_class),
            ],
            [
                'name'  => 'Bath',
                'class' => self::i('bath', $extra_class),
            ],
            [
                'name'  => 'Shower',
                'class' => self::i('shower', $extra_class),
            ],
            [
                'name'  => 'No Smoking',
                'class' => self::i('smoking-ban', $extra_class),
            ],
            [
                'name'  => 'Smoking',
                'class' => self::i('smoking', $extra_class),
            ],
            [
                'name'  => 'Coffee',
                'class' => self::i('coffee', $extra_class),
            ],
            [
                'name'  => 'Tea Mug',
                'class' => self::i('mug-hot', $extra_class),
            ],
            [
                'name'  => 'Kitchen Utensils',
                'class' => self::i('utensils', $extra_class),
            ],
            [
                'name'  => 'Gift',
                'class' => self::i('gift', $extra_class),
            ],
            [
                'name'  => 'Terrace',
                'class' => self::i('umbrella-beach', $extra_class),
            ],
            [
                'name'  => 'Mountain',
                'class' => self::i('mountain', $extra_class),
            ],
            [
                'name'  => 'Landscape',
                'class' => self::i('image', $extra_class),
            ],
            [
                'name'  => 'Nature',
                'class' => self::i('tree', $extra_class),
            ],
            [
                'name'  => 'City',
                'class' => self::i('city', $extra_class),
            ],
            [
                'name'  => 'Sea',
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
     * @return  array
     * 
     * @since   1.16.5 (J) - 1.6.5 (WP).
     */
    private static function getOverridesMap()
    {
        static $icons_map = null;

        if ($icons_map) {
            return $icons_map;
        }

        $icons_map = self::$fa_resolve_map;

        // trigger event to allow third-party plugins to override the icons map
        VBOFactory::getPlatform()->getDispatcher()->trigger('onBuildFontOverridesMap', [&$icons_map]);

        return $icons_map ?: [];
    }

    /**
     * Gets the default font style.
     *
     * @return  string
     * 
     * @since   1.16.5 (J) - 1.6.5 (WP).
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

        return (string) $icons_style;
    }
}
