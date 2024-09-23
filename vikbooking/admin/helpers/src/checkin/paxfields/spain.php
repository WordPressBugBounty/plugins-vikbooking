<?php
/** 
 * @package     VikBooking
 * @subpackage  core
 * @author      Alessio Gaggii - E4J s.r.l.
 * @copyright   Copyright (C) 2022 E4J s.r.l. All Rights Reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * @link        https://vikwp.com
 */

// No direct access
defined('ABSPATH') or die('No script kiddies please!');

/**
 * Helper class to support custom pax fields data collection types for Spain.
 * 
 * @since 	1.15.2 (J) - 1.5.5 (WP)
 */
final class VBOCheckinPaxfieldsSpain extends VBOCheckinAdapter
{
	/**
	 * The ID of this pax data collector class.
	 * 
	 * @var 	string
	 */
	protected $collector_id = 'spain';

	/**
	 * Returns the name of the current pax data driver.
	 * 
	 * @return 	string 	the name of this driver.
	 */
	public function getName()
	{
		return '"España"';
	}

	/**
	 * Returns the list of field labels. The count and keys
	 * of the labels should match with the attributes.
	 * 
	 * @return 	array 	associative list of field labels.
	 */
	public function getLabels()
	{
		return [
			'first_name' => JText::translate('VBCUSTOMERFIRSTNAME'),
			'last_name'  => JText::translate('VBCUSTOMERLASTNAME'),
			'gender' 	 => JText::translate('VBOCUSTGENDER'),
			'date_birth' => JText::translate('ORDER_DBIRTH'),
			'country' 	 => JText::translate('VBCUSTOMERCOUNTRY'),
			'doctype'  	 => JText::translate('VBCUSTOMERDOCTYPE'),
			'docnum' 	 => JText::translate('VBCUSTOMERDOCNUM'),
			'docissuedt' => JText::translate('VBCUSTOMERDOCISSUE'),
			'extranotes' => JText::translate('VBOGUESTEXTRANOTES'),
		];
	}

	/**
	 * Returns the list of field attributes. The count and keys
	 * of the attributes should match with the labels.
	 * 
	 * @return 	array 	associative list of field attributes.
	 */
	public function getAttributes()
	{
		return [
			'first_name' => 'text',
			'last_name'  => 'text',
			'gender' 	 => 'spain_gender',
			'date_birth' => 'calendar',
			'country' 	 => 'country',
			'doctype'  	 => 'spain_doctype',
			'docnum' 	 => 'text',
			'docissuedt' => 'calendar',
			'extranotes' => 'textarea',
		];
	}

	/**
	 * Returns the associative list of ID types for Spain.
	 * 
	 * @return 	array 	associative list of doc types.
	 */
	public function loadDocumenti()
	{
		return [
			"D" => "Documento Nacional de Identidad",
			"P" => "Pasaporte",
			"C" => "Permiso de Conducir",
			"I" => "Carta o Documento de Identidad",
			"N" => "Permiso de Residencia Español",
			"X" => "Permiso de Residencia de otro Estado Miembro de la Unión Europea",
		];
	}
}
