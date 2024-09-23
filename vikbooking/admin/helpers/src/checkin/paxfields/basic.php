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
 * Helper class to support the default pax fields data collection.
 * 
 * @since 	1.15.0 (J) - 1.5.0 (WP)
 */
final class VBOCheckinPaxfieldsBasic extends VBOCheckinAdapter
{
	/**
	 * The ID of this pax data collector class.
	 * 
	 * @var 	string
	 */
	protected $collector_id = 'basic';

	/**
	 * Returns the name of the current pax data driver.
	 * 
	 * @return 	string 	the name of this driver.
	 */
	public function getName()
	{
		return JText::translate('VBO_CONF_CHECKIN_DATA_BASIC');
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
			'country' 	 => JText::translate('VBCUSTOMERCOUNTRY'),
			'docnum' 	 => JText::translate('VBCUSTOMERDOCNUM'),
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
			'gender' 	 => [
				'M' => JText::translate('VBOCUSTGENDERM'),
				'F' => JText::translate('VBOCUSTGENDERF'),
			],
			'country' 	 => 'country',
			'docnum' 	 => 'text',
			'extranotes' => 'text',
		];
	}
}
