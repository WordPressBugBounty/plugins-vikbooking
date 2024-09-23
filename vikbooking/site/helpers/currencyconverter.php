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

class VboCurrencyConverter
{	
	/**
	 * The currency code on which the rates are based
	 * 
	 * @var string
	 */
	protected $from_currency;

	/**
	 * The desired currency code for conversion
	 * 
	 * @var string
	 */
	protected $to_currency;

	/**
	 * A list of numbers (rates) to convert
	 * 
	 * @var array
	 */
	protected $prices;

	/**
	 * The formatting values for numbers
	 * 
	 * @var array
	 */
	protected $format;

	/**
	 * A map of symbols and decimals for the various currency codes
	 * 
	 * @var array
	 */
	protected $currencymap;
	
	/**
	 * The error message(s) occurred during the processes
	 * 
	 * @var string
	 */
	protected $error;

	/**
	 * The list of supported currencies.
	 * 
	 * @var 	array
	 * @since 	1.15.4 (J) - 1.5.10 (WP)
	 */
	protected $currency_names = array(
		'AED' => 'AED - United Arab Emirates Dirham',
		'AFN' => 'AFN - Afghan Afghani',
		'ALL' => 'ALL - Albanian Lek',
		'AMD' => 'AMD - Armenian Dram',
		'ANG' => 'ANG - Netherlands Antillean Gulden',
		'AOA' => 'AOA - Angolan Kwanza',
		'ARS' => 'ARS - Argentine Peso',
		'AUD' => 'AUD - Australian Dollar',
		'AWG' => 'AWG - Aruban Florin',
		'AZN' => 'AZN - Azerbaijani Manat',
		'BAM' => 'BAM - Bosnia and Herzegovina Mark',
		'BBD' => 'BBD - Barbadian Dollar',
		'BDT' => 'BDT - Bangladeshi Taka',
		'BGN' => 'BGN - Bulgarian Lev',
		'BHD' => 'BHD - Bahraini Dinar',
		'BIF' => 'BIF - Burundian Franc',
		'BMD' => 'BMD - Bermudian Dollar',
		'BND' => 'BND - Brunei Dollar',
		'BOB' => 'BOB - Bolivian Boliviano',
		'BRL' => 'BRL - Brazilian Real',
		'BSD' => 'BSD - Bahamian Dollar',
		'BWP' => 'BWP - Botswana Pula',
		'BZD' => 'BZD - Belize Dollar',
		'CAD' => 'CAD - Canadian Dollar',
		'CDF' => 'CDF - Congolese Franc',
		'CHF' => 'CHF - Swiss Franc',
		'CLP' => 'CLP - Chilean Peso',
		'CNY' => 'CNY - Chinese Renminbi Yuan',
		'COP' => 'COP - Colombian Peso',
		'CRC' => 'CRC - Costa Rican Colón',
		'CVE' => 'CVE - Cape Verdean Escudo',
		'CZK' => 'CZK - Czech Koruna',
		'DJF' => 'DJF - Djiboutian Franc',
		'DKK' => 'DKK - Danish Krone',
		'DOP' => 'DOP - Dominican Peso',
		'DZD' => 'DZD - Algerian Dinar',
		'EEK' => 'EEK - Estonian Kroon',
		'EGP' => 'EGP - Egyptian Pound',
		'ETB' => 'ETB - Ethiopian Birr',
		'EUR' => 'EUR - Euro',
		'FJD' => 'FJD - Fijian Dollar',
		'FKP' => 'FKP - Falkland Islands Pound',
		'GBP' => 'GBP - British Pound',
		'GEL' => 'GEL - Georgian Lari',
		'GIP' => 'GIP - Gibraltar Pound',
		'GMD' => 'GMD - Gambian Dalasi',
		'GNF' => 'GNF - Guinean Franc',
		'GTQ' => 'GTQ - Guatemalan Quetzal',
		'GYD' => 'GYD - Guyanese Dollar',
		'HKD' => 'HKD - Hong Kong Dollar',
		'HNL' => 'HNL - Honduran Lempira',
		'HRK' => 'HRK - Croatian Kuna',
		'HTG' => 'HTG - Haitian Gourde',
		'HUF' => 'HUF - Hungarian Forint',
		'IDR' => 'IDR - Indonesian Rupiah',
		'ILS' => 'ILS - Israeli New Sheqel',
		'INR' => 'INR - Indian Rupee',
		'ISK' => 'ISK - Icelandic Króna',
		'JMD' => 'JMD - Jamaican Dollar',
		'JPY' => 'JPY - Japanese Yen',
		'JOD' => 'JOD - Jordanian Dinar',
		'KES' => 'KES - Kenyan Shilling',
		'KGS' => 'KGS - Kyrgyzstani Som',
		'KHR' => 'KHR - Cambodian Riel',
		'KMF' => 'KMF - Comorian Franc',
		'KRW' => 'KRW - South Korean Won',
		'KYD' => 'KYD - Cayman Islands Dollar',
		'KZT' => 'KZT - Kazakhstani Tenge',
		'KWD' => 'KWD - Kuwaiti Dinar',
		'LAK' => 'LAK - Lao Kip',
		'LBP' => 'LBP - Lebanese Pound',
		'LKR' => 'LKR - Sri Lankan Rupee',
		'LRD' => 'LRD - Liberian Dollar',
		'LSL' => 'LSL - Lesotho Loti',
		'LTL' => 'LTL - Lithuanian Litas',
		'LVL' => 'LVL - Latvian Lats',
		'MAD' => 'MAD - Moroccan Dirham',
		'MDL' => 'MDL - Moldovan Leu',
		'MGA' => 'MGA - Malagasy Ariary',
		'MKD' => 'MKD - Macedonian Denar',
		'MNT' => 'MNT - Mongolian Tögrög',
		'MOP' => 'MOP - Macanese Pataca',
		'MRU' => 'MRU - Mauritanian Ouguiya',
		'MUR' => 'MUR - Mauritian Rupee',
		'MVR' => 'MVR - Maldivian Rufiyaa',
		'MWK' => 'MWK - Malawian Kwacha',
		'MXN' => 'MXN - Mexican Peso',
		'MYR' => 'MYR - Malaysian Ringgit',
		'MZN' => 'MZN - Mozambican Metical',
		'NAD' => 'NAD - Namibian Dollar',
		'NGN' => 'NGN - Nigerian Naira',
		'NIO' => 'NIO - Nicaraguan Córdoba',
		'NOK' => 'NOK - Norwegian Krone',
		'NPR' => 'NPR - Nepalese Rupee',
		'NZD' => 'NZD - New Zealand Dollar',
		'OMR' => 'OMR - Omani Rial',
		'PAB' => 'PAB - Panamanian Balboa',
		'PEN' => 'PEN - Peruvian Nuevo Sol',
		'PGK' => 'PGK - Papua New Guinean Kina',
		'PHP' => 'PHP - Philippine Peso',
		'PKR' => 'PKR - Pakistani Rupee',
		'PLN' => 'PLN - Polish Złoty',
		'PYG' => 'PYG - Paraguayan Guaraní',
		'QAR' => 'QAR - Qatari Riyal',
		'RON' => 'RON - Romanian Leu',
		'RSD' => 'RSD - Serbian Dinar',
		'RUB' => 'RUB - Russian Ruble',
		'RWF' => 'RWF - Rwandan Franc',
		'SAR' => 'SAR - Saudi Riyal',
		'SBD' => 'SBD - Solomon Islands Dollar',
		'SCR' => 'SCR - Seychellois Rupee',
		'SEK' => 'SEK - Swedish Krona',
		'SGD' => 'SGD - Singapore Dollar',
		'SHP' => 'SHP - Saint Helenian Pound',
		'SLL' => 'SLL - Sierra Leonean Leone',
		'SOS' => 'SOS - Somali Shilling',
		'SRD' => 'SRD - Surinamese Dollar',
		'STD' => 'STD - São Tomé and Príncipe Dobra',
		'SVC' => 'SVC - Salvadoran Colón',
		'SZL' => 'SZL - Swazi Lilangeni',
		'THB' => 'THB - Thai Baht',
		'TJS' => 'TJS - Tajikistani Somoni',
		'TOP' => 'TOP - Tongan Paʻanga',
		'TRY' => 'TRY - Turkish Lira',
		'TTD' => 'TTD - Trinidad and Tobago Dollar',
		'TWD' => 'TWD - New Taiwan Dollar',
		'TZS' => 'TZS - Tanzanian Shilling',
		'UAH' => 'UAH - Ukrainian Hryvnia',
		'UGX' => 'UGX - Ugandan Shilling',
		'USD' => 'USD - United States Dollar',
		'UYU' => 'UYU - Uruguayan Peso',
		'UZS' => 'UZS - Uzbekistani Som',
		'VEF' => 'VEF - Venezuelan Bolívar',
		'VND' => 'VND - Vietnamese Đồng',
		'VUV' => 'VUV - Vanuatu Vatu',
		'WST' => 'WST - Samoan Tala',
		'XAF' => 'XAF - Central African Cfa Franc',
		'XCD' => 'XCD - East Caribbean Dollar',
		'XOF' => 'XOF - West African Cfa Franc',
		'XPF' => 'XPF - Cfp Franc',
		'YER' => 'YER - Yemeni Rial',
		'ZAR' => 'ZAR - South African Rand',
		'ZMW' => 'ZMW - Zambian Kwacha',
		'TND' => 'TND - Tunisian dinar',
	);

	/**
	 * The suffix of the method for exchaning the rates
	 * 
	 * @var string
	 */
	public 	$api_provider;

	/**
	 * Class constructor
	 * 
	 * @param 	string 	$from 		current currency
	 * @param 	string 	$to 		desired currency
	 * @param 	array 	$numbers 	list of numbers
	 * @param 	array 	$format 	formatting options
	 */
	public function __construct($from, $to, $numbers, $format)
	{
		$this->from_currency = $from;
		$this->to_currency 	 = $to;
		$this->prices 		 = $numbers;
		$this->format  		 = $format;
		$this->error 		 = '';
		$this->currencymap 	 = array(
			'ALL' => array('symbol' => '76'),
			'AFN' => array('symbol' => '1547'),
			'ARS' => array('symbol' => '36'),
			'AWG' => array('symbol' => '402'),
			'AUD' => array('symbol' => '36'),
			'AZN' => array('symbol' => '1084'),
			'BSD' => array('symbol' => '36'),
			'BBD' => array('symbol' => '36'),
			'BYR' => array('symbol' => '112', 'decimals' => 0),
			'BZD' => array('symbol' => '66'),
			'BMD' => array('symbol' => '36'),
			'BOB' => array('symbol' => '36'),
			'BAM' => array('symbol' => '75'),
			'BWP' => array('symbol' => '80'),
			'BGN' => array('symbol' => '1083'),
			'BHD' => array('decimals' => 3),
			'BRL' => array('symbol' => '82'),
			'BND' => array('symbol' => '36'),
			'KHR' => array('symbol' => '6107'),
			'CAD' => array('symbol' => '36'),
			'KYD' => array('symbol' => '36'),
			'CLP' => array('symbol' => '36', 'decimals' => 0),
			'CNY' => array('symbol' => '165'),
			'COP' => array('symbol' => '36', 'decimals' => 3),
			'CRC' => array('symbol' => '8353'),
			'CVE' => array('symbol' => '36', 'decimals' => 2),
			'HRK' => array('symbol' => '107'),
			'CUP' => array('symbol' => '8369'),
			'CZK' => array('symbol' => '75'),
			'DKK' => array('symbol' => '107'),
			'DOP' => array('symbol' => '82'),
			'XCD' => array('symbol' => '36'),
			'EGP' => array('symbol' => '163'),
			'SVC' => array('symbol' => '36'),
			'EEK' => array('symbol' => '107'),
			'EUR' => array('symbol' => '8364'),
			'FKP' => array('symbol' => '163'),
			'FJD' => array('symbol' => '36'),
			'GHC' => array('symbol' => '162'),
			'GIP' => array('symbol' => '163'),
			'GTQ' => array('symbol' => '81'),
			'GGP' => array('symbol' => '163'),
			'GYD' => array('symbol' => '36'),
			'HNL' => array('symbol' => '76'),
			'HKD' => array('symbol' => '36'),
			'HUF' => array('symbol' => '70', 'decimals' => 0),
			'ISK' => array('symbol' => '107', 'decimals' => 0),
			'IDR' => array('symbol' => '82'),
			'INR' => array('symbol' => '8377'),
			'IRR' => array('symbol' => '65020'),
			'IMP' => array('symbol' => '163'),
			'ILS' => array('symbol' => '8362'),
			'JMD' => array('symbol' => '74'),
			'JPY' => array('symbol' => '165', 'decimals' => 0),
			'JEP' => array('symbol' => '163'),
			'KZT' => array('symbol' => '1083'),
			'KPW' => array('symbol' => '8361'),
			'KRW' => array('symbol' => '8361', 'decimals' => 0),
			'KGS' => array('symbol' => '1083'),
			'KWD' => array('decimals' => 3),
			'LAK' => array('symbol' => '8365'),
			'LVL' => array('symbol' => '76'),
			'LBP' => array('symbol' => '163'),
			'LRD' => array('symbol' => '36'),
			'LTL' => array('symbol' => '76'),
			'MKD' => array('symbol' => '1076'),
			'MYR' => array('symbol' => '82'),
			'MUR' => array('symbol' => '8360'),
			'MXN' => array('symbol' => '36'),
			'MNT' => array('symbol' => '8366'),
			'MZN' => array('symbol' => '77', 'decimals' => 0),
			'NAD' => array('symbol' => '36'),
			'NPR' => array('symbol' => '8360'),
			'ANG' => array('symbol' => '402'),
			'NZD' => array('symbol' => '36'),
			'NIO' => array('symbol' => '67'),
			'NGN' => array('symbol' => '8358'),
			'NOK' => array('symbol' => '107'),
			'OMR' => array('symbol' => '65020', 'decimals' => 3),
			'PKR' => array('symbol' => '8360'),
			'PAB' => array('symbol' => '66'),
			'PYG' => array('symbol' => '71', 'decimals' => 0),
			'PEN' => array('symbol' => '83'),
			'PHP' => array('symbol' => '8369'),
			'PLN' => array('symbol' => '122'),
			'QAR' => array('symbol' => '65020'),
			'RON' => array('symbol' => '108;&#101;&#105'),
			'RUB' => array('symbol' => '1088'),
			'SHP' => array('symbol' => '163'),
			'SAR' => array('symbol' => '65020'),
			'RSD' => array('symbol' => '1044'),
			'SCR' => array('symbol' => '8360'),
			'SGD' => array('symbol' => '36'),
			'SBD' => array('symbol' => '36'),
			'SOS' => array('symbol' => '83'),
			'ZAR' => array('symbol' => '82'),
			'LKR' => array('symbol' => '8360'),
			'SEK' => array('symbol' => '107'),
			'CHF' => array('symbol' => '8355'),
			'SRD' => array('symbol' => '36'),
			'SYP' => array('symbol' => '163'),
			'TWD' => array('symbol' => '78'),
			'THB' => array('symbol' => '3647'),
			'TTD' => array('symbol' => '84'),
			'TRY' => array('symbol' => '8378', 'decimals' => 0),
			'UAH' => array('symbol' => '8372'),
			'GBP' => array('symbol' => '163'),
			'USD' => array('symbol' => '36'),
			'UYU' => array('symbol' => '36'),
			'UZS' => array('symbol' => '1083'),
			'VEF' => array('symbol' => '66'),
			'VND' => array('symbol' => '8363'),
			'YER' => array('symbol' => '65020'),
			'ZWD' => array('symbol' => '90'),
			'TND' => array('decimals' => 3),
		);
		
		/**
		 * This is the API's Provider that the Class will use to retrieve the conversion rate.
		 * The method callProvider{$suffix}() will be called by the getConversionRate() method.
		 * 
		 * Supported Providers: ECB, FloatRates (used as callback by ECB)
		 * Deprecated Providers: fixer, yahoo
		 *
		 * @see 	getConversionRate()
		 */
		$this->api_provider = 'ECB';
	}

	/**
	 * Deprecated call to the Yahoo Finance APIs (shut down in November 2017).
	 * Retrieve the conversion rate between base currency and symbol currency.
	 *
	 * @return 	mixed 	false in case of errors, float in case of success
	 * 
	 * @deprecated 	since November 2017
	 */
	protected function callProviderYahoo()
	{
		//http://finance.yahoo.com/currency-converter
		$apis_url = 'http://finance.yahoo.com/d/quotes.csv?e=.csv&f=sl1d1t1&s='. $this->from_currency . $this->to_currency .'=X';
		$fp = @fopen($apis_url, 'r');
		if ($fp) {
			$data = '';
			while (!feof($fp)) {
				$data .= fread($fp, 4096);
			}
			if (!empty($data)) {
				$data = str_replace("\"", "", $data);
				$rate_info = explode(',', $data);
				if (strlen($rate_info[1]) > 0 && floatval($rate_info[1]) > 0.00) {
					return (float)$rate_info[1];
				}
			}
		}
		return false;
	}

	/**
	 * Call to the Fixer Foreign exchange rates and currency conversion API.
	 * Retrieve the conversion rate between base currency and symbol currency.
	 *
	 * @return 	mixed 	false in case of errors, float in case of success
	 * 
	 * @deprecated 	since June 2018
	 */
	protected function callProviderFixer()
	{
		//http://fixer.io/
		$apis_url = 'https://api.fixer.io/latest?'.($this->from_currency != 'EUR' ? 'base='.$this->from_currency.'&' : '').'symbols='.$this->to_currency;
		$fp = @fopen($apis_url, 'r');
		if ($fp) {
			$data = '';
			while (!feof($fp)) {
				$data .= fread($fp, 4096);
			}
			$resp = json_decode($data);
			if (is_object($resp) && property_exists($resp, 'rates') && is_object($resp->rates)) {
				$prop = $this->to_currency;
				if (property_exists($resp->rates, $prop) && floatval($resp->rates->$prop) > 0.00) {
					return (float)$resp->rates->$prop;
				}
			}
		}
		return false;
	}

	/**
	 * Call to the European Central Bank exchange rates XML data.
	 * Retrieve the conversion rate between base currency and symbol currency.
	 * The exchange rates data is cached by downloading the file every day.
	 *
	 * @return 	mixed 	float exchange rate, or false in case of errors
	 * 
	 * @since 	June 2018
	 */
	protected function callProviderECB()
	{
		// load exchange rates
		$exchange_rates = $this->loadECBRates();
		if (!$exchange_rates) {
			// something went wrong
			return false;
		}

		if (!isset($exchange_rates[$this->to_currency]) && $this->to_currency != 'EUR') {
			
			/**
			 * The ECB only supports a few currencies, so as a fallback we also
			 * use the provider floatrates to see if they support the to_currency
			 * 
			 * @since 	October 2018
			 */
			$exchange_rates = $this->loadFloatRatesDotComRates();
			if (!$exchange_rates) {
				// something went wrong
				return false;
			}
			//
			
			if (!isset($exchange_rates[$this->to_currency])) {
				// we do not have the exchange rate to this currency.
				$this->setError('exchange rates to this currency not supported ('.__LINE__.')');
				return false;
			}
		}
		
		if ($this->from_currency == 'EUR') {
			// converting from EUR to a known currency
			return $this->to_currency == 'EUR' ? 1 : $exchange_rates[$this->to_currency];
		}

		$from_changed = false;
		if (!isset($exchange_rates[$this->from_currency])) {
			/**
			 * The ECB only supports a few currencies, so as a fallback we also
			 * use the provider floatrates to see if they support the from_currency
			 * 
			 * @since 	November 2018
			 */
			$exchange_rates = $this->loadFloatRatesDotComRates(strtolower($this->from_currency));
			if (!$exchange_rates) {
				// something went wrong
				return false;
			}

			if (isset($exchange_rates[$this->to_currency])) {
				// we set the exchange rate for the from_currency to 1
				$exchange_rates[$this->from_currency] = 1;
			}

			// the from_currency has changed
			$from_changed = true;
		}

		if (!isset($exchange_rates[$this->from_currency])) {
			// converting from this currency is not allowed as we do not know it
			$this->setError('converting from this currency not allowed because of missing exchange rates ('.__LINE__.')');
			return false;
		}

		if ($this->from_currency == $this->to_currency) {
			// equal currencies should be stopped before this method
			return 1;
		}

		if ($from_changed && isset($exchange_rates[$this->to_currency])) {
			// we have loaded the exchange rates from the requested from_currency (loadFloatRatesDotComRates())
			return $exchange_rates[$this->to_currency];
		}

		if ($this->to_currency == 'EUR' && !$from_changed) {
			// converting to EUR from a known currency
			return (1 / $exchange_rates[$this->from_currency]);
		}

		// conversion is not involving EUR, but both currencies are known (from : to = 1 : x)
		return ($exchange_rates[$this->to_currency] / $exchange_rates[$this->from_currency]);
	}

	/**
	 * Call to the European Central Bank exchange rates XML data.
	 * Retrieves the conversion rates of all currencies by caching the results,
	 * and by returning an array map with key-value pairs of currencycode-rate from EUR.
	 *
	 * @return 	mixed 	false in case of errors, array in case of success
	 * 
	 * @since 	June 2018
	 */
	protected function loadECBRates()
	{
		$rates_doc = 'https://www.ecb.europa.eu/stats/eurofxref/eurofxref-daily.xml';
		$cache_rates = dirname(__FILE__) . DIRECTORY_SEPARATOR . date('Y-m-d') . '_ecb.xml';
		$xml_data = '';

		if (!is_file($cache_rates)) {
			// cached rates are not available, attempt to download the information
			$expcache = glob(dirname(__FILE__) . DIRECTORY_SEPARATOR . '*_ecb.xml');
			// remove old expired cache files
			foreach ($expcache as $expf) {
				if (is_file($expf)) {
					@unlink($expf);
				}
			}

			/**
			 * Remote files should be fetched using CURL rather than through fopen($foo, 'r');
			 * 
			 * @since 	1.15.2 (J) - 1.5.5 (WP)
			 */
			$http = new JHttp;

			try {
				$http_resp = $http->get($rates_doc);
			} catch (Exception $e) {
				// request failed
				$this->setError($e->getMessage());
				return false;
			}

			if ($http_resp->code != 200) {
				// cannot read exchange rates from external XML file
				$this->setError('cannot read exchange rates from external XML file ('.__LINE__.')');
				return false;
			}

			$xml_data = $http_resp->body;

			// store cache file
			$fp = @fopen($cache_rates, 'w+');
			if (!$fp) {
				// cannot open cache file for writing. Exit.
				$this->setError('cannot open cache file for writing ('.__LINE__.')');
				return false;
			}
			fwrite($fp, $xml_data);
			fclose($fp);
		}

		if (!is_file($cache_rates)) {
			// could not load rates
			$this->setError('could not load rates ('.__LINE__.')');
			return false;
		}

		if (empty($xml_data)) {
			// read cached rates from XML file
			$fp = @fopen($cache_rates, 'r');
			if (!$fp) {
				// cannot read exchange rates from cached XML file
				$this->setError('cannot read exchange rates from cached XML file ('.__LINE__.')');
				return false;
			}
			$xml_data = '';
			while (!feof($fp)) {
				$xml_data .= fread($fp, 4096);
			}
			fclose($fp);
		}

		$exchange_obj = simplexml_load_string($xml_data);
		if (!$exchange_obj instanceof SimpleXMLElement) {
			// this file does not contain correct XML
			$this->setError('the file does not contain valid XML ('.__LINE__.')');
			return false;
		}
		
		$exchange_rates = array();
		foreach ($exchange_obj->Cube->Cube->Cube as $exrate) {
			$attr = $exrate->attributes();
			$currency = (string)$attr->currency;
			$rate = (float)$attr->rate;
			if ($rate > 0.00) {
				$exchange_rates[$currency] = $rate;
			}
		}

		return $exchange_rates;
	}

	/**
	 * Call to Floatrates.com exchange rates XML data.
	 * Retrieve the conversion rate between base currency and symbol currency.
	 * The exchange rates data is cached by downloading the file every day.
	 *
	 * If this was used as default and only provider, then the from currency would
	 * be passed to the rates loading method to fetch the exact XML feed for the
	 * from currency. If the feed was not available, false would be returned.
	 *
	 * @return 	mixed 	float exchange rate, or false in case of errors
	 * 
	 * @since 	October 2018
	 */
	protected function callProviderFloatRates()
	{
		// load exchange rates
		$exchange_rates = $this->loadFloatRatesDotComRates($this->from_currency);
		if (!$exchange_rates) {
			// something went wrong
			return false;
		}

		if (!isset($exchange_rates[$this->to_currency])) {
			// we do not have the exchange rate to this currency.
			$this->setError('exchange rates to this currency not supported ('.__LINE__.')');
			return false;
		}

		if ($this->from_currency == $this->to_currency) {
			// equal currencies should be stopped before this method
			return 1;
		}

		// conversion is not involving EUR, but both currencies are known (from : to = 1 : x)
		return $exchange_rates[$this->to_currency];
	}

	/**
	 * Call to the Floatrates.com XML feed.
	 * Retrieves the conversion rates of all currencies by caching the results, and
	 * by returning an array map with key-value pairs of currencycode-rate from EUR.
	 *
	 * @param 	[string] $currency 		the base currency 3 chars code, if specified
	 * 									attempt to load the exact $currency.xml feed
	 *
	 * @return 	mixed 	false in case of errors, array in case of success
	 * 					key (currency code) = value (exchange rate)
	 * 
	 * @since 	October 2018
	 */
	protected function loadFloatRatesDotComRates($currency = '')
	{
		$feed_name 	 = empty($currency) ? 'eur' : substr(strtolower($currency), 0, 3);
		$rates_doc 	 = "http://www.floatrates.com/daily/{$feed_name}.xml";
		$cache_rates = dirname(__FILE__) . DIRECTORY_SEPARATOR . date('Y-m-d') . "{$feed_name}" . '_floatrates.xml';
		$xml_data  	 = '';

		if (!is_file($cache_rates)) {
			// cached rates are not available, attempt to download the information
			$expcache = glob(dirname(__FILE__) . DIRECTORY_SEPARATOR . '*_floatrates.xml');
			// remove old expired cache files
			foreach ($expcache as $expf) {
				if (is_file($expf)) {
					@unlink($expf);
				}
			}

			/**
			 * Remote files should be fetched using CURL rather than through fopen($foo, 'r');
			 * 
			 * @since 	1.15.2 (J) - 1.5.5 (WP)
			 */
			$http = new JHttp;

			try {
				$http_resp = $http->get($rates_doc);
			} catch (Exception $e) {
				// request failed
				$this->setError($e->getMessage());
				return false;
			}

			if ($http_resp->code != 200) {
				// cannot read exchange rates from external XML file
				$this->setError('cannot read exchange rates from external XML file ('.__LINE__.')');
				return false;
			}

			$xml_data = $http_resp->body;

			// store cache file
			$fp = @fopen($cache_rates, 'w+');
			if (!$fp) {
				// cannot open cache file for writing. Exit.
				$this->setError('cannot open cache file for writing ('.__LINE__.')');
				return false;
			}
			fwrite($fp, $xml_data);
			fclose($fp);
		}

		if (!is_file($cache_rates)) {
			// could not load rates
			$this->setError('could not load rates ('.__LINE__.')');
			return false;
		}

		if (empty($xml_data)) {
			// read cached rates from XML file
			$fp = @fopen($cache_rates, 'r');
			if (!$fp) {
				// cannot read exchange rates from cached XML file
				$this->setError('cannot read exchange rates from cached XML file ('.__LINE__.')');
				return false;
			}
			$xml_data = '';
			while (!feof($fp)) {
				$xml_data .= fread($fp, 4096);
			}
			fclose($fp);
		}

		$exchange_obj = simplexml_load_string($xml_data);
		if (!$exchange_obj instanceof SimpleXMLElement) {
			// this file does not contain correct XML
			$this->setError('this file does not contain correct XML ('.__LINE__.')');
			return false;
		}
		
		$exchange_rates = array();
		foreach ($exchange_obj->item as $exrate) {
			if (!property_exists($exrate, 'targetCurrency') || !property_exists($exrate, 'exchangeRate')) {
				continue;
			}
			$currency = (string)$exrate->targetCurrency;
			/**
			 * All conversion rates exceeding 1 thousand are formatted as strings, with a thousand separator
			 * character (comma ","), and so we must get rid of the formatting to obtain a true float value.
			 * 
			 * @since 	1.14.3 (J) - 1.4.3 (WP)
			 */
			$rate = floatval(str_replace(',', '', (string)$exrate->exchangeRate));
			//
			if ($rate > 0.00) {
				$exchange_rates[$currency] = $rate;
			}
		}

		if (!count($exchange_rates)) {
			// invalid XML structure
			$this->setError('invalid exchange rates structure ('.__LINE__.')');
			return false;
		}

		return $exchange_rates;
	}
	
	/**
	 * Makes a float number from a given string number.
	 * 
	 * @param 	string 	$num 	the formatted number to exchange.
	 * 
	 * @return 	float 			the number to exchange.
	 */
	protected function makeFloat($num)
	{
		$floated = $num;
		if (is_array($this->format) && count($this->format) == 3) {
			$decimals = '';
			if (strstr($num, $this->format[1]) !== false) {
				$decimals = substr($num, ((int)$this->format[0] - ((int)$this->format[0] * 2)));
			}
			$nosep = str_replace($this->format[1], '', $num);
			$nosep = str_replace($this->format[2], '', $nosep);
			$newdecimals = '';
			if ((int)$this->format[0] > 0 && !empty($decimals)) {
				$nosep = substr_replace($nosep, '', (strlen($decimals) - (strlen($decimals) * 2)));
				$decimalsabs = abs($decimals);
				if ($decimalsabs > 0) {
					$newdecimals = $decimals;
				}
			}
			$floated = floatval($nosep.(!empty($newdecimals) ? '.'.$newdecimals : ''));
		}

		return $floated;
	}
	
	/**
	 * Finds and returns an appropriate symbol for the currency converted
	 * 
	 * @return 	string 	the symbol to return to the controller for the exchanged rates
	 */
	protected function currencySymbol()
	{
		/**
		 * Trigger event to allow third party plugins to return specific information for a currency.
		 * 
		 * @since 	1.16.0 (J) - 1.6.0 (WP)
		 */
		$custom_conv = VBOFactory::getPlatform()->getDispatcher()->filter('onGetCurrencyDataVikBooking', [$this->to_currency]);
		if (is_array($custom_conv) && !empty($custom_conv[0]) && is_array($custom_conv[0]) && !empty($custom_conv[0]['symbol'])) {
			return $custom_conv[0]['symbol'];
		}

		if (isset($this->currencymap[$this->to_currency]) && isset($this->currencymap[$this->to_currency]['symbol'])) {
			$symbol = '&#'.$this->currencymap[$this->to_currency]['symbol'].';';	
		} else {
			$symbol = $this->to_currency;
		}

		return $symbol;
	}
	
	/**
	 * Formats a number according to the values passed to the constructor
	 * 
	 * @param 	mixed 	$num 	the number to format
	 * 
	 * @return 	string 	the formatted number for display
	 */
	protected function currencyFormat($num)
	{
		$num_decimals = (int)$this->format[0];
		if (array_key_exists($this->to_currency, $this->currencymap)) {
			if (array_key_exists('decimals', $this->currencymap[$this->to_currency])) {
				$num_decimals = $this->currencymap[$this->to_currency]['decimals'];
			} else {
				$num_decimals = 2;
			}
		}

		// decimals and thousands separators for this currency
		$decimals_sep  = $this->format[1];
		$thousands_sep = $this->format[2];

		/**
		 * Trigger event to allow third party plugins to return specific information for a currency.
		 * 
		 * @since 	1.16.0 (J) - 1.6.0 (WP)
		 */
		$custom_conv = VBOFactory::getPlatform()->getDispatcher()->filter('onGetCurrencyDataVikBooking', [$this->to_currency]);
		if (is_array($custom_conv) && !empty($custom_conv[0]) && is_array($custom_conv[0])) {
			// override currency information for formatting the amounts
			$num_decimals  = isset($custom_conv[0]['decimals']) && is_int($custom_conv[0]['decimals']) ? $custom_conv[0]['decimals'] : $num_decimals;
			$decimals_sep  = isset($custom_conv[0]['decimals_separator']) && is_string($custom_conv[0]['decimals_separator']) ? $custom_conv[0]['decimals_separator'] : $decimals_sep;
			$thousands_sep = isset($custom_conv[0]['thousands_separator']) && is_string($custom_conv[0]['thousands_separator']) ? $custom_conv[0]['thousands_separator'] : $thousands_sep;
		}

		return number_format($num, $num_decimals, $decimals_sep, $thousands_sep);
	}
	
	/**
	 * Main method called by the controller after invoking the class. The same method is also
	 * called by other classes of VBO, and keeps associative arrays of rates to exchange.
	 * 
	 * @param 	bool 	$get_floats 	true to only return the plain exchanged rates.
	 * 
	 * @return 	array 					the array of rates converted or an empty array in case of error
	 * 
	 * @since 	1.15.0 (J) - 1.5.0 (WP) added argument to return plain exchanged floats.
	 */
	public function convert($get_floats = false)
	{
		$conversion = [];

		if (!is_array($this->prices) || !$this->prices) {
			return $conversion;
		}

		// get the conversion rate between the currencies
		$conv_rate = $this->getConversionRate();

		if ($conv_rate === false) {
			// return the original array
			return $conversion;
		}

		// get the currency symbol of the exchanged currency
		$conv_symbol = $this->currencySymbol();

		// exchange values
		foreach ($this->prices as $k => $price) {
			// get the base price to exchange
			if ($get_floats) {
				// given price is expected to be already a raw float
				$base_price = (float)$price;
			} else {
				// given price has been formatted already
				$base_price = $this->makeFloat($price);
			}

			// apply conversion
			$exchanged = $base_price * $conv_rate;

			if ($get_floats) {
				// inject just the exchanged rate
				$conversion[$k] = $exchanged;
			} else {
				// build the exchanged information
				$conversion[$k]['symbol'] = $conv_symbol;
				$conversion[$k]['price'] = $this->currencyFormat($exchanged);
			}
		}
		
		return $conversion;
	}

	/**
	 * Generic method called by convert() to invoke a provider and get
	 * the necessary exchange rates for applying the conversions.
	 * 
	 * @see 	convert()
	 * 
	 * @return 	mixed 	float, the exchange rate for the given from and to currencies
	 * 					in case of success. Boolean false otherwise.
	 * 
	 * @since 	1.15.0 (J) - 1.5.0 (WP) the visibility is public, but used to be private before.
	 */
	public function getConversionRate()
	{
		$session = JFactory::getSession();
		$ses_conversions = $session->get('vboCurrencyConversions', '');
		$conversions_made = array();
		$data = '';
		$conv_rate = false;

		/**
		 * Trigger event to allow third party plugins to return a specific conversion rate for these currencies.
		 * 
		 * @since 	1.16.0 (J) - 1.6.0 (WP)
		 */
		$custom_conv = VBOFactory::getPlatform()->getDispatcher()->filter('onGetConversionRateVikBooking', [$this->from_currency, $this->to_currency]);
		if (is_array($custom_conv) && !empty($custom_conv[0]) && (is_int($custom_conv[0]) || is_float($custom_conv[0]))) {
			return $custom_conv[0];
		}
		
		if (is_array($ses_conversions) && count($ses_conversions)) {
			$conversions_made = $ses_conversions;
			if (isset($ses_conversions[$this->from_currency.'_'.$this->to_currency])) {
				if (strlen($ses_conversions[$this->from_currency.'_'.$this->to_currency]) && floatval($ses_conversions[$this->from_currency.'_'.$this->to_currency]) > 0.00) {
					$conv_rate = $ses_conversions[$this->from_currency.'_'.$this->to_currency];
				}
			}
		}

		if ($conv_rate === false) {
			// use an external provider for getting the conversion rate
			$api_method = 'callProvider'.str_replace(' ', '', ucwords($this->api_provider));
			if (!method_exists($this, $api_method)) {
				return false;
			}
			$conv_rate = $this->{$api_method}();
			if ($conv_rate !== false) {
				// cache conversion rate into the session
				$conversions_made[$this->from_currency.'_'.$this->to_currency] = $conv_rate;
				$session->set('vboCurrencyConversions', $conversions_made);
			}

		}
		
		return $conv_rate;
	}

	/**
	 * Tells whether a currency name exists in the map-list.
	 * 
	 * @param 	string 	$currency_name 	the currency name to check.
	 * 
	 * @return 	bool 					true if available, or false.
	 * 
	 * @since 	1.15.0 (J) - 1.5.0 (WP)
	 */
	public function currencyExists($currency_name)
	{
		if (isset($this->currencymap[$currency_name])) {
			return true;
		}

		/**
		 * Trigger event to allow third party plugins to register a custom currency code.
		 * 
		 * @since 	1.16.0 (J) - 1.6.0 (WP)
		 */
		$existing = VBOFactory::getPlatform()->getDispatcher()->filter('onCheckCurrencyExistsVikBooking', [$currency_name]);
		if (is_array($existing) && in_array(true, $existing, true)) {
			return true;
		}

		return false;
	}

	/**
	 * Overrides the prices to convert.
	 * 
	 * @param 	array 	$prices 	the list of prices to convert.
	 * 
	 * @return 	array 				the current list of prices to convert.
	 * 
	 * @since 	1.15.0 (J) - 1.5.0 (WP)
	 */
	public function setPrices(array $prices = [])
	{
		$this->prices = $prices;

		return $this->prices;
	}

	/**
	 * Returns an associative list of currencies with code and name.
	 * 
	 * @return 	array
	 * 
	 * @since 	1.15.4 (J) - 1.5.10 (WP)
	 */
	public function getCurrencyNames()
	{
		return $this->currency_names;
	}

	/**
	 * Concatenates errors occurred during the execution
	 * 
	 * @param 	string 	$err 	the error message
	 * 
	 * @return 	void
	 */
	protected function setError($err)
	{
		$this->error .= $err . "\n";
	}

	/**
	 * Returns the errors occurred during the execution
	 * 
	 * @return 	string 	the error message(s)
	 */
	public function getError()
	{
		return rtrim($this->error, "\n");
	}
}
