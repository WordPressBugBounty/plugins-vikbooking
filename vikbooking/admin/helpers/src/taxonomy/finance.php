<?php
/** 
 * @package     VikBooking
 * @subpackage  core
 * @author      E4J s.r.l.
 * @copyright   Copyright (C) 2022 E4J s.r.l. All Rights Reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * @link        https://vikwp.com
 */

// No direct access
defined('ABSPATH') or die('No script kiddies please!');

/**
 * Taxonomy finance helper class.
 * 
 * @since 	1.16.0 (J) - 1.6.0 (WP)
 */
class VBOTaxonomyFinance
{
	/**
	 * The singleton instance of the class.
	 *
	 * @var VBOTaxonomyFinance
	 */
	protected static $instance = null;

	/**
	 * Class constructor is protected.
	 *
	 * @see 	getInstance()
	 */
	protected function __construct()
	{
		// do nothing
	}

	/**
	 * Returns the global object, either
	 * a new instance or the existing instance
	 * if the class was already instantiated.
	 *
	 * @return 	self 	A new instance of the class.
	 */
	public static function getInstance()
	{
		if (is_null(static::$instance)) {
			static::$instance = new static();
		}

		return static::$instance;
	}

	/**
	 * Returns the number of total units for all rooms, or for a specific room.
	 * By default, the rooms unpublished are skipped, and all rooms are used.
	 * 
	 * @param 	mixed 	$idroom 	int or array.
	 * @param 	bool 	$published 	true or false.
	 *
	 * @return 	int
	 */
	public function countRooms($idroom = 0, $published = true)
	{
		$dbo = JFactory::getDbo();

		$totrooms = 0;
		$clauses = [];

		if (is_int($idroom) && $idroom > 0) {
			$clauses[] = "`id`=" . (int)$idroom;
		} elseif (is_array($idroom) && count($idroom)) {
			$idroom = array_map('intval', $idroom);
			$clauses[] = "`id` IN (" . implode(', ', $idroom) . ")";
		}

		if ($published) {
			$clauses[] = "`avail`=1";
		}

		$q = "SELECT SUM(`units`) FROM `#__vikbooking_rooms`" . (count($clauses) ? " WHERE " . implode(' AND ', $clauses) : "");
		$dbo->setQuery($q);
		$totrooms = (int)$dbo->loadResult();

		return $totrooms;
	}

	/**
	 * Counts the number of nights of difference between two timestamps.
	 * 
	 * @param 	int 	$to_ts 		the target end date timestamp.
	 * @param 	int 	$from_ts 	the starting date timestamp.
	 * 
	 * @return 	int 	the nights of difference between from and to timestamps.
	 */
	public function countNightsTo($to_ts, $from_ts = 0)
	{
		if (empty($from_ts)) {
			$from_ts = time();
		}

		$from_ymd = date('Y-m-d', $from_ts);
		$to_ymd = date('Y-m-d', $to_ts);

		if ($from_ymd == $to_ymd) {
			return 1;
		}

		$from_date = new DateTime($from_ymd);
		$to_date   = new DateTime($to_ymd);
		$daysdiff  = (int)$from_date->diff($to_date)->format('%a');

		if ($to_ts < $from_ts) {
			// we need a negative integer number in this case
			$daysdiff = $daysdiff - ($daysdiff * 2);
		}

		if ($from_ymd != $to_ymd && $daysdiff > 0) {
			// the to date is actually another night of stay
			$daysdiff += 1;
		}

		return $daysdiff;
	}

	/**
	 * Helper method to format long currency values into short numbers.
	 * I.e. 2.600.000 = 2.6M (empty decimals are always removed to keep the string short).
	 * 
	 * @param 	int|float 	$num 		the amount to format.
	 * @param 	int 		$decimals 	the precision to use.
	 * 
	 * @return 	string 					the formatted amount string.
	 */
	public function numberFormatShort($num, $decimals = 1)
	{
		// get global formatting values
		$formatvals  = VikBooking::getNumberFormatData();
		$formatparts = explode(':', $formatvals);

		if ($num < 951) {
			// 0 - 950
			$short_amount = number_format($num, $decimals, $formatparts[1], $formatparts[2]);
			$type_amount  = '';
		} elseif ($num < 900000) {
			// 1k - 950k
			$short_amount = number_format($num / 1000, $decimals, $formatparts[1], $formatparts[2]);
			$type_amount  = 'k';
		} elseif ($num < 900000000) {
			// 0.9m - 950m
			$short_amount = number_format($num / 1000000, $decimals, $formatparts[1], $formatparts[2]);
			$type_amount  = 'm';
		} elseif ($num < 900000000000) {
			// 0.9b - 950b
			$short_amount = number_format($num / 1000000000, $decimals, $formatparts[1], $formatparts[2]);
			$type_amount  = 'b';
		} else {
			// >= 0.9t
			$short_amount = number_format($num / 1000000000000, $decimals, $formatparts[1], $formatparts[2]);
			$type_amount  = 't';
		}

		// unpad zeroes from the right
		if ($decimals > 0) {
			while (substr($short_amount, -1, 1) == '0') {
				$short_amount = substr($short_amount, 0, strlen($short_amount) - 1);
			}

			if (substr($short_amount, -1, 1) == $formatparts[1]) {
				// remove also the decimals separator if no more decimals
				$short_amount = substr($short_amount, 0, strlen($short_amount) - 1);
			}
		}
		
		// return the formatted amount string
		return $short_amount . $type_amount;
	}

	/**
	 * Calculate the absolute percent amount between the current and previous financial stat.
	 * This method will use the following calculation: ((A1 - A2) / A2) * 100.
	 * 
	 * @param 	float 	$stat 		the current (previously calculated) financial amount.
	 * @param 	float 	$compare 	the previous period financial amount.
	 * @param 	int 	$precision 	the precision for apply rounding.
	 * 
	 * @return 	int|float 			the absolute percent amount calculated.
	 */
	public function calcAbsPercent($current, $compare, $precision = 1)
	{
		if ($current == $compare) {
			return 0;
		}

		if ($current > $compare && $compare < 1) {
			return 100;
		}

		if ($current < $compare && $current < 1) {
			return 100;
		}

		return round(abs(($current - $compare) / $compare * 100), $precision);
	}

	/**
	 * Obtain booking statistics from a range of dates and an
	 * optional list of room types. The data obtained will be
	 * based on the effective nights of stay within the range,
	 * unless type "booking_dates" to obtain different data.
	 * 
	 * @param 	string 	$from 	the Y-m-d (or website) date from.
	 * @param 	string 	$to 	the Y-m-d (or website) date to.
	 * @param 	array 	$rooms 	list of room IDs to filter.
	 * @param 	string 	$type 	either "stay_dates" or "booking_dates".
	 * 
	 * @return 	array 			associative list of information.
	 * 
	 * @throws 	Exception
	 */
	public function getStats($from, $to, $rooms = [], $type = 'stay_dates')
	{
		$dbo = JFactory::getDbo();

		// access the availability helper
		$av_helper = VikBooking::getAvailabilityInstance();

		$from_ts = VikBooking::getDateTimestamp($from, 0, 0);
		$to_ts = VikBooking::getDateTimestamp($to, 23, 59, 59);
		if (empty($from) || empty($from_ts) || empty($to_ts) || $to_ts < $from_ts) {
			throw new Exception('Invalid dates provided', 500);
		}

		// total number of days (nights) in the range
		$total_range_nights = $this->countNightsTo($to_ts, $from_ts);
		if ($total_range_nights < 1) {
			throw new Exception('Invalid number of days provided', 500);
		}

		// total number of room units
		$total_room_units = $this->countRooms($rooms);

		if ($total_room_units < 1) {
			throw new Exception('Having no rooms published may lead to divisions by zero, hence errors.', 500);
		}

		// the associative array of statistics to collect and return
		$stats = [
			// booking ids involved
			'bids' 			=> [],
			// total number of room units counted for stats
			'room_units' 	=> $total_room_units,
			// total number of room units times the number of nights in the range
			'tot_inventory' => ($total_room_units * $total_range_nights),
			// number of rooms booked
			'rooms_booked' 	=> 0,
			// number of bookings found
			'tot_bookings' 	=> 0,
			// total number of nights booked (proportionally adjusted according to affected dates)
			'nights_booked' => 0,
			// percent value
			'occupancy' 	=> 0,
			// average length of stay
			'avg_los' 		=> 0,
			// points of sale revenue (revenue divided by each individual ota and ibe)
			'pos_revenue' 	=> [],
			// list of countries with ranking
			'country_ranks' => [],
			// ibe revenue net
			'ibe_revenue' 	=> 0,
			// otas revenue net (no matter if commissions were applied)
			'ota_revenue' 	=> 0,
			// total refunded amounts (never deducted)
			'tot_refunds' 	=> 0,
			// average daily rate
			'adr' 			=> 0,
			// revenue per available room
			'revpar' 		=> 0,
			// amount of taxes
			'taxes' 		=> 0,
			// commissions (amount)
			'cmms' 			=> 0,
			// net revenue before tax (otas + ibe)
			'revenue' 		=> 0,
			// gross revenue after tax
			'gross_revenue' => 0,
			// otas total before tax (same as ota_revenue, but only if ota commissions were applied)
			'ota_tot_net' 	=> 0,
			// otas commissions
			'ota_cmms' 		=> 0,
			// percent value for average ota commissions amount
			'ota_avg_cmms' 	=> 0,
			// commission savings amount
			'cmm_savings' 	=> 0,
		];

		// get all (real/completed) bookings in the given range of dates
		$q = $dbo->getQuery(true);
		$q->select($dbo->qn([
			'o.id',
			'o.ts',
			'o.days',
			'o.checkin',
			'o.checkout',
			'o.totpaid',
			'o.roomsnum',
			'o.total',
			'o.idorderota',
			'o.channel',
			'o.country',
			'o.tot_taxes',
			'o.tot_city_taxes',
			'o.tot_fees',
			'o.cmms',
			'o.refund',
			'or.idorder',
			'or.idroom',
			'or.optionals',
			'or.cust_cost',
			'or.cust_idiva',
			'or.extracosts',
			'or.room_cost',
			'ob.idbusy',
			'c.country_name',
		]));
		$q->select($dbo->qn('b.checkin', 'room_checkin'));
		$q->select($dbo->qn('b.checkout', 'room_checkout'));
		$q->from($dbo->qn('#__vikbooking_orders', 'o'));
		$q->leftjoin($dbo->qn('#__vikbooking_ordersrooms', 'or') . ' ON ' . $dbo->qn('or.idorder') . ' = ' . $dbo->qn('o.id'));
		$q->leftjoin($dbo->qn('#__vikbooking_ordersbusy', 'ob') . ' ON ' . $dbo->qn('ob.idorder') . ' = ' . $dbo->qn('o.id'));
		$q->leftjoin($dbo->qn('#__vikbooking_busy', 'b') . ' ON ' . $dbo->qn('b.id') . ' = ' . $dbo->qn('ob.idbusy'));
		$q->leftjoin($dbo->qn('#__vikbooking_countries', 'c') . ' ON ' . $dbo->qn('c.country_3_code') . ' = ' . $dbo->qn('o.country'));
		$q->where($dbo->qn('o.status') . ' = ' . $dbo->q('confirmed'));
		$q->where($dbo->qn('o.total') . ' > 0');
		$q->where($dbo->qn('o.closure') . ' = 0');
		if ($type == 'stay_dates') {
			// regular calculation based on stay dates
			$q->where($dbo->qn('o.checkout') . ' >= ' . $from_ts);
			$q->where($dbo->qn('o.checkin') . ' <= ' . $to_ts);
		} else {
			// calculation based on booked dates
			$q->where($dbo->qn('o.ts') . ' >= ' . $from_ts);
			$q->where($dbo->qn('o.ts') . ' <= ' . $to_ts);
		}

		if (is_array($rooms) && $rooms) {
			$rooms = array_map('intval', $rooms);
			$q->where($dbo->qn('or.idroom') . ' IN (' . implode(', ', $rooms) . ')');
		}

		$q->order($dbo->qn('o.checkin') . ' ASC');
		$q->order($dbo->qn('o.id') . ' ASC');

		$dbo->setQuery($q);
		$records = $dbo->loadAssocList();

		if (!$records) {
			// no bookings found, do not proceed
			return $stats;
		}

		// nest records with multiple rooms booked inside sub-arrays
		$bookings = [];
		foreach ($records as $b) {
			if (!isset($bookings[$b['id']])) {
				$bookings[$b['id']] = [];
			}
			// calculate the effective from and to stay timestamps for this room (by supporting split stays or early departures/late arrivals)
			$room_checkin  = !empty($b['room_checkin']) && $b['room_checkin'] != $b['checkin'] ? $b['room_checkin'] : $b['checkin'];
			$room_checkout = !empty($b['room_checkout']) && $b['room_checkout'] != $b['checkout'] ? $b['room_checkout'] : $b['checkout'];
			$in_info  = getdate($room_checkin);
			$out_info = getdate($room_checkout);
			$b['stay_from_ts'] = mktime(0, 0, 0, $in_info['mon'], $in_info['mday'], $in_info['year']);
			$b['stay_to_ts']   = mktime(23, 59, 59, $out_info['mon'], ($out_info['mday'] - 1), $out_info['year']);
			$b['stay_nights']  = $room_checkin != $b['checkin'] || $room_checkout != $b['checkout'] ? $av_helper->countNightsOfStay($room_checkin, $room_checkout) : $b['days'];
			$b['stay_nights']  = $b['stay_nights'] < 1 ? 1 : $b['stay_nights'];
			// push room-booking
			$bookings[$b['id']][] = $b;
		}

		// free memory up
		unset($records);

		// counters and pools
		$los_counter = 0;
		$pos_pool 	 = [];
		$pos_counter = [];
		$countries 	 = [];
		$country_map = [];

		// parse all bookings
		foreach ($bookings as $bid => $booking) {
			// push booking ID
			$stats['bids'][] = $bid;

			// increase tot bookings and los counter
			$stats['tot_bookings']++;
			$los_counter += $booking[0]['days'];

			// use default total values
			$booking_rooms 		 = count($booking);
			$room_total 		 = $booking[0]['total'];
			$room_cmms 			 = $booking[0]['cmms'];
			$room_refund 		 = $booking[0]['refund'];
			$room_tot_taxes 	 = $booking[0]['tot_taxes'];
			$room_tot_city_taxes = $booking[0]['tot_city_taxes'];
			$room_tot_fees 		 = $booking[0]['tot_fees'];

			// point of sale name
			$pos_name = null;

			// parse all rooms booked
			foreach ($booking as $room_booking) {
				// increase rooms booked
				$stats['rooms_booked']++;

				// number of nights affected
				$los_affected = $room_booking['stay_nights'];

				// check if amounts must be calculated proportionally for the range of dates requested
				if ($type == 'stay_dates' && ($room_booking['stay_from_ts'] < $from_ts || $room_booking['stay_to_ts'] > $to_ts)) {
					// calculate number of nights of stay affected
					$los_affected = $this->countNightsAffected($from_ts, $to_ts, $room_booking['stay_from_ts'], $room_booking['stay_to_ts']);

					// adjust the amounts proportionally
					$room_total 		 = $room_total * $los_affected / $room_booking['stay_nights'];
					$room_cmms 			 = $room_cmms * $los_affected / $room_booking['stay_nights'];
					$room_refund 		 = $room_refund * $los_affected / $room_booking['stay_nights'];
					$room_tot_taxes 	 = $room_tot_taxes * $los_affected / $room_booking['stay_nights'];
					$room_tot_city_taxes = $room_tot_city_taxes * $los_affected / $room_booking['stay_nights'];
					$room_tot_fees 		 = $room_tot_fees * $los_affected / $room_booking['stay_nights'];
				}

				// apply average values per room booked
				$room_total 		 /= $booking_rooms;
				$room_cmms 			 /= $booking_rooms;
				$room_refund 		 /= $booking_rooms;
				$room_tot_taxes 	 /= $booking_rooms;
				$room_tot_city_taxes /= $booking_rooms;
				$room_tot_fees 		 /= $booking_rooms;

				// calculate and sum average values per room booked
				$tot_net = $room_total - (float)$room_tot_taxes - (float)$room_tot_city_taxes - (float)$room_tot_fees - (float)$room_cmms;
				$stats['revenue'] += $tot_net;
				$stats['gross_revenue'] += $room_total;
				$stats['nights_booked'] += $los_affected;

				// increase country stats
				$country_code = !empty($room_booking['country']) ? $room_booking['country'] : 'unknown';
				if (!isset($countries[$country_code])) {
					$countries[$country_code] = 0;
					if (!empty($room_booking['country_name'])) {
						$country_map[$country_code] = $room_booking['country_name'];
					}
				}
				$countries[$country_code] += $tot_net;

				if (!empty($room_booking['idorderota']) && !empty($room_booking['channel'])) {
					$stats['ota_revenue'] += $tot_net;
					if ($room_cmms > 0) {
						$stats['ota_tot_net'] += $tot_net;
						$stats['ota_cmms'] += $room_cmms;
					}
					// set pos name
					$channel_parts = explode('_', $room_booking['channel']);
					$pos_name = trim($channel_parts[0]);
				} else {
					$stats['ibe_revenue'] += $tot_net;
					// set pos name
					$pos_name = 'website';
				}

				// set pos net revenue
				if (!isset($pos_pool[$pos_name])) {
					$pos_pool[$pos_name] = 0;
				}
				$pos_pool[$pos_name] += $tot_net;

				$stats['taxes'] += (float)$room_tot_taxes + (float)$room_tot_city_taxes + (float)$room_tot_fees;
				$stats['cmms'] += (float)$room_cmms;
				$stats['tot_refunds'] += $room_refund;
			}

			if ($pos_name) {
				// increase number of bookings for this pos
				if (!isset($pos_counter[$pos_name])) {
					$pos_counter[$pos_name] = 0;
				}
				$pos_counter[$pos_name]++;
			}
		}

		// count the average length of stay (no proportional data for the dates requested)
		$stats['avg_los'] = $stats['tot_bookings'] > 0 ? round($los_counter / $stats['tot_bookings'], 1) : 0;

		// calculate occupancy percent value
		$stats['occupancy'] = round(($stats['nights_booked'] * 100 / ($total_room_units * $total_range_nights)), 2);

		// count the average daily rate (ADR)
		$stats['adr'] = $stats['rooms_booked'] > 0 ? $stats['revenue'] / $stats['rooms_booked'] / $total_range_nights : 0;

		// count the revenue per available room (RevPAR)
		$stats['revpar'] = $stats['revenue'] / $total_room_units;

		// count OTAs average commission amount
		if ($stats['ota_tot_net'] > 0 && $stats['ota_cmms'] > 0) {
			// find the average percent value of OTA commissions (tot_net : tot_cmms = 100 : x)
			$stats['ota_avg_cmms'] = round(($stats['ota_cmms'] * 100 / $stats['ota_tot_net']), 2);

			if ($stats['ibe_revenue'] > 0) {
				// calculate the commission savings amount
				$stats['cmm_savings'] = $stats['ibe_revenue'] * $stats['ota_avg_cmms'] / 100;
			}
		}

		// get channel logos helper
		$vcm_logos = VikBooking::getVcmChannelsLogo('', true);

		// sort and build pos revenues
		if (count($pos_pool) && $stats['revenue'] > 0) {
			// apply sorting descending
			arsort($pos_pool);
			// build readable pos values
			foreach ($pos_pool as $pos_name => $pos_revenue) {
				$pos_data = [
					'name' 	   => $pos_name,
					'revenue'  => $pos_revenue,
					'pcent'    => round(($pos_revenue * 100 / $stats['revenue']), 2),
					'logo' 	   => null,
					'bookings' => isset($pos_counter[$pos_name]) ? $pos_counter[$pos_name] : 0,
					'ibe' 	   => false,
					'ota' 	   => false,
				];
				if (!strcasecmp($pos_name, 'website')) {
					// ibe revenue
					$pos_data['name'] = JText::translate('VBORDFROMSITE');
					$pos_data['ibe']  = true;
				} else {
					// ota revenue
					$pos_data['ota'] = true;
					if (is_object($vcm_logos)) {
						$ota_logo_img = $vcm_logos->setProvenience($pos_name)->getSmallLogoURL();
						if ($ota_logo_img !== false) {
							$pos_data['logo'] = $ota_logo_img;
						}
					}
				}
				// push pos data
				$stats['pos_revenue'][] = $pos_data;
			}
		}

		// sort countries revenue
		if (count($countries) && $stats['revenue'] > 0) {
			// apply sorting descending
			arsort($countries);
			// build readable values
			foreach ($countries as $country_code => $country_revenue) {
				// push country data
				$stats['country_ranks'][] = [
					'code' 	  => $country_code,
					'name' 	  => (isset($country_map[$country_code]) ? $country_map[$country_code] : $country_code),
					'revenue' => $country_revenue,
					'pcent'   => round(($country_revenue * 100 / $stats['revenue']), 2),
				];
			}
		}

		// return the statistics information
		return $stats;
	}

	/**
	 * Counts the number of nights involved in a range of dates. This is
	 * useful to proportionally calculate the amounts to be used.
	 * 
	 * @param 	int 	$from_ts 	the 00:00:00 timestamp of the range start date.
	 * @param 	int 	$to_ts 		the 23:59:59 timestamp of the range end date.
	 * @param 	int 	$in_ts 		the 00:00:00 timestamp of the check-in date.
	 * @param 	int 	$out_ts 	the 23:59:59 timestamp of the last night of stay (check-out -1).
	 * 
	 * @return 	int 				the number of stay nights involved in the range.
	 */
	protected function countNightsAffected($from_ts, $to_ts, $in_ts, $out_ts)
	{
		$nights_affected = 0;

		if ($from_ts > $to_ts) {
			return $nights_affected;
		}

		$range_from_info = getdate($from_ts);
		while ($range_from_info[0] < $to_ts) {
			if ($range_from_info[0] >= $in_ts && $range_from_info[0] <= $out_ts) {
				$nights_affected++;
			}
			// next day iteration
			$range_from_info = getdate(mktime(0, 0, 0, $range_from_info['mon'], ($range_from_info['mday'] + 1), $range_from_info['year']));
		}

		return $nights_affected;
	}
}
