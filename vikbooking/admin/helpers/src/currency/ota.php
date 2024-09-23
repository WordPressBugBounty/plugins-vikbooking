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
 * Helper class to allow currency conversion for OTA bookings.
 * Extends JObject and expects to be constructed with the booking record.
 * Used to convert OTA bookings in foreign currencies into the local currency.
 * 
 * @since 	1.15.0 (J) - 1.5.0 (WP)
 */
class VBOCurrencyOta extends JObject
{
	/**
	 * The default currency name.
	 * 
	 * @var  string
	 */
	protected $currency_name = '';

	/**
	 * The default currency number format data.
	 * 
	 * @var  array
	 */
	protected $currency_format = [];

	/**
	 * The list of columns that can be converted for the reservation.
	 * 
	 * @var  array
	 */
	protected $convertable_res_keys = [
		'totpaid',
		'total',
		'tot_taxes',
		'tot_city_taxes',
		'tot_fees',
		'cmms',
	];

	/**
	 * The list of columns that can be converted for the rooms booked.
	 * 
	 * @var  array
	 */
	protected $convertable_room_keys = [
		'cust_cost',
		'room_cost',
	];

	/**
	 * Tells whether the injected reservation array/object
	 * supports and allows a currency conversion.
	 * 
	 * @param 	float 		$def_rate 	the default rate to use to check if conversion works.
	 * 
	 * @return 	bool|float 	false if conversion not supported/allowed or float exchange rate.
	 */
	public function supportsConversion($def_rate = 1)
	{
		$ota_currency = $this->get('chcurrency', '');
		if (empty($ota_currency)) {
			// not an OTA reservation, or empty OTA currency
			return false;
		}
		$ota_currency = strtoupper($ota_currency);

		// import currency converter class
		if (!VikBooking::import('currencyconverter')) {
			return false;
		}

		// make sure the default rate is greater than zero
		$def_rate = $def_rate <= 0 ? 1 : $def_rate;

		// invoke currency converter
		$converter = new VboCurrencyConverter($ota_currency, $this->getCurrencyName(), [$def_rate], $this->getFormatData());

		// make sure the OTA currency is supported for conversion
		if (!$converter->currencyExists($ota_currency)) {
			return false;
		}

		// default currency must be valid as well
		if (!$converter->currencyExists($this->getCurrencyName())) {
			return false;
		}

		// make sure currencies are different
		if ($ota_currency == $this->getCurrencyName()) {
			return false;
		}

		// attempt to get the exchange rate
		$exchange_rate = $converter->getConversionRate();

		if ($exchange_rate === false) {
			// exchanging the currencies was not allowed
			return false;
		}

		// get the default rate exchanged
		$exchanged = $converter->convert(true);

		if (!is_array($exchanged) || !$exchanged) {
			// something went wrong
			return false;
		}

		// return the converted default rate
		return $exchanged[0];
	}

	/**
	 * If currency conversion is supported, converts all the amounts of
	 * a reservation into the ones of the default website currency.
	 * 
	 * @return 	bool 	true on success, false otherwise.
	 */
	public function convertReservationCurrency()
	{
		$dbo = JFactory::getDbo();

		// make sure the booking ID is valid
		$vbo_bid = $this->get('id');
		if (empty($vbo_bid)) {
			return false;
		}

		// load the reservation record from Vik Booking
		$reservation = new JObject(VikBooking::getBookingInfoFromID($vbo_bid));
		if (!$reservation->get('id')) {
			// booking not found
			return false;
		}

		// import currency converter class
		if (!VikBooking::import('currencyconverter')) {
			return false;
		}

		// load rooms data array
		$reservation_rooms = VikBooking::loadOrdersRoomsData($vbo_bid);

		// import currency converter and make sure conversion is allowed
		if ($this->supportsConversion() === false) {
			return false;
		}

		// the OTA (supported) currency
		$ota_currency = strtoupper($this->get('chcurrency'));

		// build the associative list of the rates to exchange for the reservation
		$raw_rates = [];
		foreach ($this->convertable_res_keys as $res_key) {
			$raw_rates[$res_key] = $reservation->get($res_key, 0);
		}

		// invoke the converter class to actually exchange the rates
		$converter = new VboCurrencyConverter($ota_currency, $this->getCurrencyName(), $raw_rates, $this->getFormatData());

		// get the raw rates exchanged
		$exchanged = $converter->convert(true);

		if (!$exchanged || $exchanged === $raw_rates) {
			// converting was not possible
			return false;
		}

		// apply converted rates for the reservation
		$res_record = new stdClass;
		$res_record->id = $vbo_bid;
		foreach ($exchanged as $key => $excrate) {
			$res_record->{$key} = $excrate;
		}
		// make sure to update the original OTA currency so that 
		// this reservation will no longer require conversion
		$res_record->chcurrency = $this->getCurrencyName();

		// update reservation record with the new information
		$dbo->updateObject('#__vikbooking_orders', $res_record, 'id');

		// loop through all room records
		foreach ($reservation_rooms as $order_room) {
			// build the list of raw rates to exchange for this room
			$raw_rates = [];
			foreach ($this->convertable_room_keys as $room_key) {
				if (!isset($order_room[$room_key])) {
					continue;
				}
				$raw_rates[$room_key] = $order_room[$room_key];
			}
			if (!$raw_rates) {
				// all null or invalid values that do not need a conversion
				continue;
			}

			// update rates to convert in converter object
			$converter->setPrices($raw_rates);

			// get the raw rates exchanged
			$exchanged = $converter->convert(true);

			if (!$exchanged || $exchanged === $raw_rates) {
				// converting did not produce results
				continue;
			}

			// apply converted rates for this room record
			$room_record = new stdClass;
			$room_record->id = $order_room['id'];
			foreach ($exchanged as $key => $excrate) {
				$room_record->{$key} = $excrate;
			}

			/**
			 * We need to statically take care of another room-reservation property
			 * which requires a JSON structure: "extracosts" for the extra services.
			 * 
			 * @since 	1.16.3 (J) - 1.6.3 (WP)
			 */
			if (!empty($order_room['extracosts'])) {
				$room_extra_costs = is_string($order_room['extracosts']) ? json_decode($order_room['extracosts'], true) : $order_room['extracosts'];
				if (is_array($room_extra_costs) && $room_extra_costs) {
					// build a map for converting the extra cost amounts
					$room_extra_costs_assoc = [];
					foreach ($room_extra_costs as $rec_k => $room_ec) {
						if (!is_array($room_ec) || empty($room_ec['cost'])) {
							continue;
						}
						$room_extra_costs_assoc[$rec_k] = (float)$room_ec['cost'];
					}

					// check if some costs need to be converted
					if ($room_extra_costs_assoc) {
						// update rates to convert in converter object
						$converter->setPrices($room_extra_costs_assoc);

						// get the raw rates exchanged
						$exchanged = $converter->convert(true);

						if ($exchanged) {
							// apply back the converted costs for the custom extras
							foreach ($exchanged as $rec_k => $excrate) {
								$room_extra_costs[$rec_k]['cost'] = $excrate;
							}

							// set the new object property for this room record for the update
							$room_record->extracosts = json_encode($room_extra_costs);
						}
					}
				}
			}

			// update room record with the new information
			$dbo->updateObject('#__vikbooking_ordersrooms', $room_record, 'id');
		}

		// update booking history
		VikBooking::getBookingHistoryInstance()->setBid($vbo_bid)->store('CM', JText::sprintf('VBO_CONV_RES_OTA_CURRENCY_HISTORY', $ota_currency, $this->getCurrencyName()));

		// process completed with success
		return true;
	}

	/**
	 * Returns the default currency name for the website.
	 * 
	 * @return 	string 	the default currency name.
	 */
	public function getCurrencyName()
	{
		if (!empty($this->currency_name)) {
			return $this->currency_name;
		}

		// load default currency from VikBooking
		$this->currency_name = VikBooking::getCurrencyName();

		return $this->currency_name;
	}

	/**
	 * Returns the OTA currency name for the current reservation.
	 * 
	 * @return 	string 	the OTA currency name to convert from.
	 */
	public function getOTACurrency()
	{
		return strtoupper($this->get('chcurrency', ''));
	}

	/**
	 * Returns the array information for formatting the numbers.
	 * 
	 * @return 	array 	the exploded formatting data
	 */
	public function getFormatData()
	{
		if (!empty($this->currency_format)) {
			return $this->currency_format;
		}

		// load default currency from VikBooking
		$this->currency_format = explode(':', VikBooking::getNumberFormatData());

		return $this->currency_format;
	}
}
