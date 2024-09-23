<?php
/**
 * @package     VikBooking
 * @subpackage  mod_vikbooking_horizontalsearch
 * @author      Alessio Gaggii - E4J s.r.l
 * @copyright   Copyright (C) 2018 E4J s.r.l. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 * @link        https://vikwp.com
 */

// No direct access
defined('ABSPATH') or die('No script kiddies please!');

require_once VBO_SITE_PATH . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'lib.vikbooking.php';

// require helper class
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'helper.php';

// module main file

VikBooking::loadFontAwesome();
if (method_exists('VikBooking', 'getTracker')) {
	// invoke the Tracker Class
	VikBooking::getTracker();
}
if (method_exists('VikBooking', 'loadPreferredColorStyles')) {
	VikBooking::loadPreferredColorStyles();
}

// get widget id
$randid = str_replace('mod_vikbooking_horizontalsearch-', '', $params->get('widget_id', rand(1, 999)));
// get widget base URL
$baseurl = VBO_MODULES_URI;

// module layout file
require JModuleHelper::getLayoutPath('mod_vikbooking_horizontalsearch', $params->get('layout', 'default'));
