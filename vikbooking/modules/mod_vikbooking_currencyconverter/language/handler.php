<?php

// No direct access
defined('ABSPATH') or die('No script kiddies please!');

JLoader::import('adapter.language.handler');

/**
 * Switcher class to translate the VikBooking Currency Converter widget languages.
 *
 * @since 	1.0
 */
class Mod_VikBooking_CurrencyconverterLanguageHandler implements JLanguageHandler
{
	/**
	 * Checks if exists a translation for the given string.
	 *
	 * @param 	string 	$string  The string to translate.
	 *
	 * @return 	string 	The translated string, otherwise null.
	 */
	public function translate($string)
	{
		$result = null;

		/**
		 * Translations go here.
		 * @tip Use 'TRANSLATORS:' comment to attach a description of the language.
		 */

		switch ($string)
		{
			/**
			 * Name, Description and Parameters
			 */

			case 'MOD_VIKBOOKING_CURRENCYCONVERTER':
				$result = __('VikBooking Currency Converter', 'vikbooking');
				break;
			case 'MOD_VIKBOOKING_CURRENCYCONVERTER_DESC':
				$result = __('Live conversion of room rates.', 'vikbooking');
				break;
			case 'PARAMALLOWEDCURRENCIES':
				$result = __('Allowed Currencies for conversion', 'vikbooking');
				break;
			case 'PARAMVBONLY':
				$result = __('Show conversion form only within VikBooking', 'vikbooking');
				break;
			case 'PARAMCURNAME':
				$result = __('Currency Name Format', 'vikbooking');
				break;
			case 'PARAMCURNAMEHELP':
				$result = __('i.e. the Three Letters format will print USD, the Full Name format will print United States Dollar while the Long format will print United States Dollar (USD)', 'vikbooking');
				break;
			case 'PARAMCURNAMEFORMATONE':
				$result = __('Three Letters', 'vikbooking');
				break;
			case 'PARAMCURNAMEFORMATTWO':
				$result = __('Full Name', 'vikbooking');
				break;
			case 'PARAMCURNAMEFORMATTHREE':
				$result = __('Long', 'vikbooking');
				break;
			case 'JNO':
				$result = __('No', 'vikbooking');
				break;
			case 'JYES':
				$result = __('Yes', 'vikbooking');
				break;
			case 'JLAYOUT':
				$result = __('Layout', 'vikbooking');
				break;
			case 'JLAYOUT_DESC':
				$result = __('The layout of the module to use. The available layouts are contained within the <b>tmpl</b> folder of the module.', 'vikbooking');
				break;
		}

		return $result;
	}
}
