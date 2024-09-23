<?php
/**
 * @package     VikBooking
 * @subpackage  com_vikbooking
 * @author      E4J srl
 * @copyright   Copyright (C) 2024 E4J srl. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 * @link        https://vikwp.com
 */

defined('ABSPATH') or die('No script kiddies please!');

// redirect to the original URL
JFactory::getApplication()->redirect($this->original_url);
