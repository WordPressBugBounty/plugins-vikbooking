<?php
/** 
 * @package     VikBooking
 * @subpackage  core
 * @author      E4J s.r.l.
 * @copyright   Copyright (C) 2021 E4J s.r.l. All Rights Reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * @link        https://vikwp.com
 */

// No direct access
defined('ABSPATH') or die('No script kiddies please!');

/**
 * Parses the email content for special elements.
 *
 * @since 	1.5.5
 */
final class VBOMailParser
{
	/**
	 * @var  string  the default mail wrapper regex pattern (HTML tag in visual editor)
	 */
	private static $wrapper_pattern = "/<hr class=\"vbo-editor-hl-mailwrapper\"\s*\/?>/";

	/**
	 * Applies a common method for replacing the special tags within a content.
	 * This will NOT include the conditional text rule tags.
	 * 
	 * @param 	string 	$content 		The content to parse.
	 * @param 	array 	$booking 		Booking record.
	 * @param 	array 	$booking_rooms 	List of rooms booked.
	 * @param 	array 	$customer 		Optional customer record.
	 * 
	 * @return 	string
	 * 
	 * @since 	1.17.6 (J) - 1.7.6 (WP)
	 */
	public static function replaceSpecialTags(string $content, array $booking, array $booking_rooms, array $customer = [])
	{
		if (!$booking || empty($booking['id'])) {
			return $content;
		}

		if (!$booking_rooms) {
			$booking_rooms = VikBooking::loadOrdersRoomsData($booking['id']);
		}

		if (!$customer) {
			$customer = VikBooking::getCPinInstance()->getCustomerFromBooking($booking['id']);
		}

		// build and set customer name
		$booking['customer_name'] = implode(' ', array_filter([($customer['first_name'] ?? ''), ($customer['last_name'] ?? '')]));

		$vbo_df = VikBooking::getDateFormat();
		$datesep = VikBooking::getDateSeparator();
		$df = $vbo_df == "%d/%m/%Y" ? 'd/m/Y' : ($vbo_df == "%m/%d/%Y" ? 'm/d/Y' : 'Y-m-d');

		$content = str_replace('{customer_name}', $booking['customer_name'], $content);
		$content = str_replace('{booking_id}', $booking['id'], $content);
		$content = str_replace('{checkin_date}', date(str_replace("/", $datesep, $df), $booking['checkin']), $content);
		$content = str_replace('{checkout_date}', date(str_replace("/", $datesep, $df), $booking['checkout']), $content);
		$content = str_replace('{num_nights}', $booking['days'], $content);

		$rooms_booked = [];
		$tot_adults = 0;
		$tot_children = 0;
		$tot_guests = 0;
		foreach ($booking_rooms as $broom) {
			if (isset($rooms_booked[$broom['room_name']])) {
				$rooms_booked[$broom['room_name']] += 1;
			} else {
				$rooms_booked[$broom['room_name']] = 1;
			}
			$tot_adults += (int) $broom['adults'];
			$tot_children += (int) $broom['children'];
			$tot_guests += ((int) $broom['adults'] + (int) $broom['children']);
		}
		$content = str_replace('{tot_adults}', $tot_adults, $content);
		$content = str_replace('{tot_children}', $tot_children, $content);
		$content = str_replace('{tot_guests}', $tot_guests, $content);

		$rooms_booked_quant = [];
		foreach ($rooms_booked as $rname => $quant) {
			$rooms_booked_quant[] = ($quant > 1 ? $quant.' ' : '').$rname;
		}
		$content = str_replace('{rooms_booked}', implode(', ', $rooms_booked_quant), $content);

		$content = str_replace('{currency}', VikBooking::getCurrencySymb(), $content);
		if (isset($booking['total'])) {
			$content = str_replace('{total}', VikBooking::numberFormat($booking['total']), $content);
		}
		if (isset($booking['totpaid'])) {
			$content = str_replace('{total_paid}', VikBooking::numberFormat($booking['totpaid']), $content);
		}
		if (isset($booking['total']) && isset($booking['totpaid'])) {
			$remaining_bal = $booking['total'] - $booking['totpaid'];
			$content = str_replace('{remaining_balance}', VikBooking::numberFormat($remaining_bal), $content);
		}

		if ($customer['pin'] ?? '') {
			$content = str_replace('{customer_pin}', $customer['pin'], $content);
		}

		$use_sid = empty($booking['sid']) && !empty($booking['idorderota']) ? $booking['idorderota'] : $booking['sid'];
		$bestitemid  = VikBooking::findProperItemIdType(['booking'], (!empty($booking['lang']) ? $booking['lang'] : null));
		$lang_suffix = $bestitemid && !empty($booking['lang']) ? '&lang=' . $booking['lang'] : '';
		$book_link 	 = VikBooking::externalroute("index.php?option=com_vikbooking&view=booking&sid=" . $use_sid . "&ts=" . $booking['ts'] . $lang_suffix, false, (!empty($bestitemid) ? $bestitemid : null));

		// access the model for shortening URLs
		$model = VBOModelShortenurl::getInstance($onlyRouted = true)->setBooking($booking);

		$content = str_replace('{booking_link}', $model->getShortUrl($book_link), $content);

		// rooms distinctive features parsing
		preg_match_all('/\{roomfeature ([a-zA-Z0-9 ]+)\}/U', $content, $matches);
		if (isset($matches[1]) && $matches[1]) {
			foreach ($matches[1] as $reqf) {
				$rooms_features = [];
				foreach ($booking_rooms as $broom) {
					$distinctive_features = [];
					$rparams = !empty($broom['params']) && is_string($broom['params']) ? ((array) json_decode($broom['params'], true)) : [];
					if (($rparams['features'] ?? []) && ($broom['roomindex'] ?? '') && ($rparams['features'][$broom['roomindex']] ?? [])) {
						$distinctive_features = $rparams['features'][$broom['roomindex']];
					}
					if (!$distinctive_features) {
						continue;
					}
					$feature_found = false;
					foreach ($distinctive_features as $dfk => $dfv) {
						if (stripos($dfk, $reqf) !== false) {
							$feature_found = $dfk;
							if (strlen(trim($dfk)) == strlen(trim($reqf))) {
								break;
							}
						}
					}
					if ($feature_found !== false && strlen((string) $distinctive_features[$feature_found])) {
						$rooms_features[] = $distinctive_features[$feature_found];
					}
				}
				if ($rooms_features) {
					$rpval = implode(', ', $rooms_features);
				} else {
					$rpval = '';
				}
				$content = str_replace("{roomfeature " . $reqf . "}", $rpval, $content);
			}
		}

		return $content;
	}

	/**
	 * Checks for any content wrapper HTML tags applied through
	 * the Visual Editor during the composing of an email message.
	 * 
	 * @param 	string 	$content 	the full email content.
	 * 
	 * @return 	string
	 */
	public static function checkWrapperSymbols($content)
	{
		$chunks = preg_split(static::$wrapper_pattern, (string)$content);

		if (count($chunks) < 2) {
			// no content wrapper used in email message
			return $content;
		}

		$layout_opening = self::getWrapperLayout(true);
		$layout_closing = self::getWrapperLayout(false);

		$final_content = '';
		foreach ($chunks as $piece => $part) {
			$is_even = ($piece === 0 || (($piece % 2) === 0));
			if (strlen(trim($part)) && !$is_even) {
				$final_content .= $layout_opening . $part . $layout_closing;
			} else {
				$final_content .= $part;
			}
		}

		return $final_content;
	}

	/**
	 * Returns the current mail wrapper layout HTML code.
	 * 
	 * @param 	bool 	$opening 	whether to get the opening or closing HTML.
	 * 
	 * @return 	string
	 */
	public static function getWrapperLayout($opening = true)
	{
		// configuration field name
		$opt_name = 'mail_wrapper_layout_' . ($opening ? 'opening' : 'closing');

		// access the configuration object
		$config = VBOFactory::getConfig();

		// get the current configuration value
		$layout = $config->get($opt_name);

		if (!$layout) {
			// set and get default layout
			$layout = self::getWrapperDefaultLayout($opening);
			$config->set($opt_name, $layout);
		}

		return $layout;
	}

	/**
	 * Returns the default mail wrapper layout HTML code.
	 * 
	 * @param 	bool 	$opening 	whether to get the opening or closing HTML.
	 * 
	 * @return 	string
	 */
	private static function getWrapperDefaultLayout($opening = true)
	{
		$layout_opening = "\n";
		$layout_opening .= "<div style=\"background: #fdfdfd;padding: 30px 0;\">";
		$layout_opening .= "\n\t";
		$layout_opening .= "<div style=\"max-width: 600px;margin: 0 auto;background: #fff;padding: 30px;border: 1px solid #eee;border-radius: 6px\">";
		$layout_opening .= "\n";

		$layout_closing = "\n";
		$layout_closing .= "\t</div>\n";
		$layout_closing .= "</div>\n";

		return $opening ? $layout_opening : $layout_closing;
	}
}
