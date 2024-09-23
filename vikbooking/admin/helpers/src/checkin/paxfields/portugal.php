<?php
/** 
 * @package     VikBooking
 * @subpackage  core
 * @author      Alessio Gaggii - E4J s.r.l.
 * @copyright   Copyright (C) 2023 E4J s.r.l. All Rights Reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * @link        https://vikwp.com
 */

// No direct access
defined('ABSPATH') or die('No script kiddies please!');

/**
 * Helper class to support custom pax fields data collection types for Portugal.
 * 
 * @since 	1.16.2 (J) - 1.6.2 (WP)
 */
final class VBOCheckinPaxfieldsPortugal extends VBOCheckinAdapter
{
	/**
	 * The ID of this pax data collector class.
	 * 
	 * @var 	string
	 */
	protected $collector_id = 'portugal';

	/**
	 * Returns the name of the current pax data driver.
	 * 
	 * @return 	string 	the name of this driver.
	 */
	public function getName()
	{
		return '"Portugal"';
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
			'first_name'  => JText::translate('VBCUSTOMERFIRSTNAME'),
			'last_name'   => JText::translate('VBCUSTOMERLASTNAME'),
			'country_c'   => JText::translate('VBCUSTOMERCOUNTRY'),
			'place_birth' => JText::translate('VBOCUSTPLACEBIRTH'),
			'date_birth'  => JText::translate('ORDER_DBIRTH'),
			'docnum' 	  => JText::translate('VBCUSTOMERDOCNUM'),
			'doctype'  	  => JText::translate('VBCUSTOMERDOCTYPE'),
			'docplace' 	  => 'Issuing Country (País Emissor)',
			'country_s'	  => 'Country of Residence (País Residência)',
			'place_s'	  => 'Place of Residence (Local Residência)',
			'extranotes'  => JText::translate('VBOGUESTEXTRANOTES'),
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
			'first_name'  => 'text',
			'last_name'   => 'text',
			'country_c'   => 'country',
			'place_birth' => 'text',
			'date_birth'  => 'calendar',
			'docnum' 	  => 'text',
			'doctype'  	  => 'portugal_doctype',
			'docplace'    => 'country',
			'country_s'   => 'country',
			'place_s' 	  => 'text',
			'extranotes'  => 'textarea',
		];
	}

	/**
	 * Returns the associative list of ID types for Portugal.
	 * 
	 * @return 	array 	associative list of doc types.
	 */
	public function loadIdTypes()
	{
		return [
			"B" => "Identity card (Bilhete de Identidade)",
			"P" => "Passport (Passaporte)",
			"O" => "Other (Outro documento de identificação)",
		];
	}
}
