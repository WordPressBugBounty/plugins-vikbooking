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
 * Helper Class to define some constants for the XML generation
 */
class VikBookingMydataAadeConstants
{
	/**
	 * Namespace attributes of main node <InvoicesDoc>.
	 * Namespace attributes will affect the incomeClassification
	 * sub nodes. Options available are, for example, the followings:
	 * 
	 * <icls:classificationType>
	 * or
	 * <N1:classificationType>
	 * 
	 * Old/alternative namespaces were:
	 * 	xmlns="http://www.aade.gr/myDATA/invoice/v1.0"
	 *	xmlns:icls="https://www.aade.gr/myDATA/incomeClassification/v1.0"
	 *	xmlns:ecls="https://www.aade.gr/myDATA/expensesClassification/v1.0"
	 *	xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
	 *	xsi:schemaLocation="http://www.aade.gr/myDATA/invoice/v1.0 schema.xsd"
	 * 
	 * @see 	https://mydata-dev.portal.azure-api.net/issues/5f3c411ac75730207831ead4
	 * 
	 * @see 	!!!!IMPORTANT!!!!
	 * 			https://www.aade.gr/sites/default/files/2020-04/invoicesDoc-v0.6.xsd
	 * 			There is probably a typo in the official schema importing the namespaces
	 * 			for income and expense classifications. The official nodes are actually
	 * 			spelling the schemas namespaces as "https://www.aade.gr/myDATA/incomeClassificaton/v1.0"
	 * 			and "https://www.aade.gr/myDATA/expensesClassificaton/v1.0". It says "incomeClassificaTON"
	 * 			instead of "incomeClassificaTION". One "i" is missing, not sure if this is a typo, but the
	 * 			attributes below must spell the namespace like the official schema, so "classificaTON".
	 */
	const INVOICESDOC_NMSP_ATTR_N1 = '
		xmlns="http://www.aade.gr/myDATA/invoice/v1.0"
		xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
		xmlns:N1="https://www.aade.gr/myDATA/incomeClassificaton/v1.0"
		xsi:schemaLocation="http://www.aade.gr/myDATA/invoice/v1.0 schema.xsd"';

	/**
	 * Another possible list of namespace attributes for the incomeClassification
	 */
	const INVOICESDOC_NMSP_ATTR_ICLS = '
		xmlns="http://www.aade.gr/myDATA/invoice/v1.0"
		xmlns:icls="https://www.aade.gr/myDATA/incomeClassificaton/v1.0"
		xmlns:ecls="https://www.aade.gr/myDATA/expensesClassificaton/v1.0"
		xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
		xsi:schemaLocation="http://www.aade.gr/myDATA/invoice/v1.0 schema.xsd"';

	/**
	 * Node invoiceType
	 */
	const DEFAULT_INVOICE_TYPE = '1.1';

	/**
	 * Node measurementUnit (1 for "Pieces")
	 */
	const DEFAULT_MEAS_UNIT = '1';

	/**
	 * Package description
	 */
	const DESCRPACKAGENIGHTS = 'Packagage for a stay of %d nights';

	/**
	 * Room nights description
	 */
	const DESCRSTAYROOMNIGHTS = '%d night-stay in room %s';

	/**
	 * Room Option description
	 */
	const DESCRROOMOPTION = 'Service %s';

	/**
	 * Tourist tax description
	 */
	const DESCRTOURISTTAX = 'Tourist tax';

	/**
	 * Room Extra Cost description
	 */
	const DESCRROOMEXTRACOST = 'Extra fee %s';

	/**
	 * XSD schema file name
	 */
	const EINVSCHEMAFNAME = 'invoicesDoc-v0.6.xsd';

	/**
	 * Development endpoint for myDATA. That's all we know.
	 * The live-mode endpoint is a driver setting because it's
	 * not mentioned anywhere in the docs, hence we cannot know it.
	 * Before 2024 it used to be https://mydata-dev.azure-api.net/.
	 */
	const MYDATA_DEV_ENDPOINT_BASE = 'https://mydataapidev.aade.gr/';

	/**
	 * QR Code PNG default width (points indicating pixels per cell).
	 * 1 ~= 49px/69px.
	 * 
	 * @since 	1.16.7 (J) - 1.6.7 (WP)
	 */
	const QRCODE_PNG_WIDTH = 4;

	/**
	 * QR Code PNG default height (points indicating pixels per cell).
	 * 1 ~= 49px/69px.
	 * 
	 * @since 	1.16.7 (J) - 1.6.7 (WP)
	 */
	const QRCODE_PNG_HEIGHT = 4;

	/**
	 * QR Code PNG default color (RGB) (black)
	 * 
	 * @since 	1.16.7 (J) - 1.6.7 (WP)
	 */
	const QRCODE_PNG_COLOR_RGB = '0, 0, 0';

	/**
	 * Returns the path to the local XSD schema. Official URL is:
	 * https://www.aade.gr/sites/default/files/2020-04/invoicesDoc-v0.6.xsd.
	 *
	 * @return 	string 	the XML Schema path
	 */
	public static function getSchemaPath()
	{
		$path_parts = [VBO_ADMIN_PATH, 'helpers', 'einvoicing', 'drivers', 'MydataAade', self::EINVSCHEMAFNAME];

		return implode(DIRECTORY_SEPARATOR, $path_parts);
	}

	/**
	 * Returns either the base path or base URI for the QR Code PNG files.
	 * 
	 * @param 	string 	$base 	either "path" or "uri".
	 * @param 	string 	$fname 	optional file name to get a full path or URI.
	 * 
	 * @return 	string 			either the base path or base URI.
	 * 
	 * @since 	1.16.7 (J) - 1.6.7 (WP)
	 */
	public static function getQRCodeBase($base = 'path', $fname = '')
	{
		if (!strcasecmp($base, 'path')) {
			// base path
			$path_parts = [VBO_MEDIA_PATH, 'AADE'];

			if ($fname) {
				$path_parts[] = $fname;
			}

			return implode(DIRECTORY_SEPARATOR, $path_parts);
		}

		// base URI
		return VBO_MEDIA_URI . 'AADE/' . $fname;
	}

	/**
	 * Given a percentage aliquote, returns the corresponding category int.
	 * 
	 * @param 	int 	$aliquote 	the tax rate to parse.
	 * 
	 * @return 	int 	the corresponding Vat Category.
	 * 
	 * @see 	page 42 of https://www.aade.gr/sites/default/files/2020-04/myDATA%20API%20Documentation%20v0%206b_eng.pdf
	 */
	public static function getVatCategory($aliquote)
	{
		$aliquote = (int)$aliquote;

		if ($aliquote === 0) {
			// without VAT 0%
			return 7;
		} elseif ($aliquote == 4) {
			// VAT rate 4%
			return 6;
		} elseif ($aliquote == 9) {
			// VAT rate 9%
			return 5;
		} elseif ($aliquote == 17) {
			// VAT rate 17%
			return 4;
		} elseif ($aliquote == 6) {
			// VAT rate 6%
			return 3;
		} elseif ($aliquote == 13 || $aliquote == 10) {
			// VAT rate 13% (or 10% for testing)
			return 2;
		} elseif ($aliquote == 24) {
			// VAT rate 24%
			return 1;
		} else {
			// Records without VAT (which is not 0%, hence should never occur)
			return 8;
		}
	}

	/**
	 * Returns the myDATA development endpoint URL.
	 * 
	 * @return 	string 	the base endpoint URL to be used.
	 */
	public static function getDevEndpointBaseUrl()
	{
		return self::MYDATA_DEV_ENDPOINT_BASE;
	}

	/**
	 * This method will decide the namespace attributes to use
	 * for the main node <InvoicesDoc>. Change this method as
	 * well as the method below to switch between N1/ICLS.
	 * 
	 * @return 	string
	 * 
	 * @see 	getInvoiceChildrenNamespace() for children namespace.
	 */
	public static function getInvoiceNamespaceAttributes()
	{
		return self::INVOICESDOC_NMSP_ATTR_N1;
	}

	/**
	 * This method will decide the namespace attribute to use
	 * for the children nodes of <incomeClassification>. Change
	 * this method as well as the method above to switch between N1/ICLS.
	 * 
	 * @return 	string 	if null or an empty string, no namespaces will be used.
	 * 
	 * @see 	getInvoiceNamespaceAttributes() for XML namespaces.
	 */
	public static function getInvoiceChildrenNamespace()
	{
		return 'N1';
	}
}
