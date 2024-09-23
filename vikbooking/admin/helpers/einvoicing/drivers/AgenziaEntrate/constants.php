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
class VikBookingAgenziaEntrateConstants
{
	/**
	 * XML declaration
	 */
	const XMLOPENINGTAG = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';

	/**
	 * XML stylesheet node with wildcard
	 */
	const XMLXLSNODE = '<?xml-stylesheet type="text/xsl" href="%s"?>';

	/**
	 * The name of the local XML stylesheet
	 */
	const XLSNAME = 'fatturapa_v1.2.xsl';
	
	/**
	 * Attribute xmlns:ds
	 */
	const XMLNS_DS = 'http://www.w3.org/2000/09/xmldsig#';

	/**
	 * Attribute xmlns:p
	 */
	const XMLNS_P = 'http://ivaservizi.agenziaentrate.gov.it/docs/xsd/fatture/v1.2';

	/**
	 * Attribute versione
	 */
	const XMLNS_V = 'FPR12';

	/**
	 * Node IdPaese (Trasmittente)
	 */
	const TRASMITTENTEIDPAESE = 'IT';

	/**
	 * Node FormatoTrasmissione ("Fattura verso privati")
	 */
	const FORMATOTRASMISSIONE = 'FPR12';

	/**
	 * Node TipoDocumento ("Fattura")
	 */
	const TIPODOCUMENTO_DEFAULT = 'TD01';

	/**
	 * Node TipoDocumento ("Acconto / anticipo su fattura")
	 */
	const TIPODOCUMENTO_ACCONTOFATTURA = 'TD02';

	/**
	 * Node TipoDocumento ("Acconto / anticipo su parcella")
	 */
	const TIPODOCUMENTO_ACCONTOPARCELLA = 'TD03';

	/**
	 * Node TipoDocumento ("Nota di credito")
	 */
	const TIPODOCUMENTO_NOTACREDITO = 'TD04';

	/**
	 * Node TipoDocumento ("Nota di debito")
	 */
	const TIPODOCUMENTO_NOTADEBITO = 'TD05';

	/**
	 * Node TipoDocumento ("Parcella")
	 */
	const TIPODOCUMENTO_PARCELLA = 'TD06';

	/**
	 * Node Divisa
	 */
	const DIVISA = 'EUR';

	/**
	 * ScontoMaggiorazione - Tipo
	 */
	const SCONTO = 'SC';

	/**
	 * Descrizione Package
	 */
	const DESCRPACKAGENIGHTS = 'SOGGIORNO CON PACCHETTO NOTTI %d';

	/**
	 * Descrizione Room name with nights
	 */
	const DESCRSTAYROOMNIGHTS = 'SOGGIORNO NOTTI %d CAMERA %s';

	/**
	 * Descrizione Room Option
	 */
	const DESCRROOMOPTION = 'SERVIZIO %s';

	/**
	 * Descrizione Room Option tourist tax
	 */
	const DESCRTOURISTTAX = 'TASSA DI SOGGIORNO';

	/**
	 * Descrizione Room Extra Cost
	 */
	const DESCRROOMEXTRACOST = 'COSTO EXTRA %s';

	/**
	 * Mail subject
	 */
	const MAILSUBJECT = 'Trasmissione fatture elettroniche %s';

	/**
	 * Mail body
	 */
	const MAILBODY = "%s\n%s\n%s\n%s\n%s";

	/**
	 * The name of the official XML Schema
	 */
	const AESCHEMANAME = 'FatturaPA_versione_1.2.xsd';

	/**
	 * Returns the subject of the PEC for the SdI.
	 * For the moment the settings are not used.
	 * 
	 * @param 	array 	$settings 	the driver settings
	 *
	 * @return 	string 	the subject for the email
	 */
	public static function getTransmissionMailSubject($settings)
	{
		return sprintf(self::MAILSUBJECT, date('Y-m-d H:i:s'));
	}

	/**
	 * Returns the body of the PEC for the SdI.
	 * 
	 * @param 	array 	$settings 	the driver settings
	 *
	 * @return 	string 	the body for the email
	 */
	public static function getTransmissionMailBody($settings)
	{
		$denominazione = !empty($settings['params']['companyname']) ? $settings['params']['companyname'] : $settings['params']['name'].' '.$settings['params']['lname'];
		$piva = $settings['params']['vatid'];
		$address = $settings['params']['address'] . ', ' . $settings['params']['nciv'];
		$city = $settings['params']['zip'] . ' ' . $settings['params']['city'] . '('.$settings['params']['province'].')';
		$phone = $settings['params']['phone'];

		return sprintf(self::MAILBODY, $denominazione, $piva, $address, $city, $phone);
	}

	/**
	 * Returns the XML node to use the stylesheet.
	 *
	 * @return 	string 	the XML node
	 */
	public static function getXlsNode()
	{
		return sprintf(self::XMLXLSNODE, VBO_ADMIN_URI.'helpers/einvoicing/drivers/AgenziaEntrate/'.self::XLSNAME);
	}

	/**
	 * Returns the full path to the schema.
	 * URL is https://www.fatturapa.gov.it/export/fatturazione/sdi/fatturapa/v1.2/Schema_del_file_xml_FatturaPA_versione_1.2.xsd
	 *
	 * @return 	string 	the XML Schema path
	 */
	public static function getSchemaPath()
	{
		return VBO_ADMIN_PATH.DIRECTORY_SEPARATOR.'helpers'.DIRECTORY_SEPARATOR.'einvoicing'.DIRECTORY_SEPARATOR.'drivers'.DIRECTORY_SEPARATOR.'AgenziaEntrate'.DIRECTORY_SEPARATOR.self::AESCHEMANAME;
	}
}
