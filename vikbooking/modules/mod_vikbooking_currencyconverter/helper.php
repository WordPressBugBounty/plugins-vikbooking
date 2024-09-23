<?php
/**
 * @package     VikBooking
 * @subpackage  mod_vikbooking_currencyconverter
 * @author      Alessio Gaggii - E4J s.r.l
 * @copyright   Copyright (C) 2018 E4J s.r.l. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 * @link        https://vikwp.com
 */

// No direct access
defined('ABSPATH') or die('No script kiddies please!');

class modVikbooking_CurConvHelper {

	public static function getAllCurrencies($params) {
		$pcur = $params->get('currencies', array());
		$currencies = array();
		if (is_array($pcur) && count($pcur)) {
			foreach ($pcur as $c) {
				if (!in_array($c, $currencies))
					$currencies[] = $c;
			}
		}

		return $currencies;
	}
	
	public static function getCurrencyName() {
		$dbo = JFactory::getDBO();
		$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='currencyname';";
		$dbo->setQuery($q);
		$dbo->execute();

		return $dbo->loadResult();
	}
	
}
