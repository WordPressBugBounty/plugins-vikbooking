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
 * Helper class to support custom pax fields data collection types for Spain (SES Hospedajes).
 * 
 * @since 	1.17.2 (J) - 1.7.2 (WP)
 */
final class VBOCheckinPaxfieldsSpainseshospedajes extends VBOCheckinAdapter
{
	/**
	 * The ID of this pax data collector class.
	 * 
	 * @var 	string
	 */
	protected $collector_id = 'spainseshospedajes';

	/**
	 * Returns the name of the current pax data driver.
	 * 
	 * @return 	string 	the name of this driver.
	 */
	public function getName()
	{
		return '"España" (SES Hospedajes)';
	}

	/**
	 * Tells whether children should be registered.
	 * 
	 * @override 		this driver requires children to be registered (back-end only).
	 * 
	 * @param 	bool 	$precheckin 	true if requested for front-end pre check-in.
	 * 
	 * @return 	bool    true to also register the children.
	 */
	public function registerChildren($precheckin = false)
	{
		// children are always registered, no matter if it's front-end pre-checkin
		return true;
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
			'parentesco' => JText::translate('VBO_SPAIN_CHILD_RELATION'),
			'date_birth' => JText::translate('ORDER_DBIRTH'),
			'address'    => JText::translate('ORDER_ADDRESS'),
			'municipio'  => JText::translate('VBO_SPAIN_MUNICIPIO_CODE'),
			'city'       => JText::translate('ORDER_CITY'),
			'postalcode' => JText::translate('ORDER_ZIP'),
			'country_s'	 => JText::translate('ORDER_STATE'),
			'country_c'  => JText::translate('VBOCUSTNATIONALITY'),
			'doctype'  	 => JText::translate('VBCUSTOMERDOCTYPE'),
			'docnum' 	 => JText::translate('VBCUSTOMERDOCNUM'),
			'docsoporte' => JText::translate('VBO_SPAIN_DOC_SUPPORT_NUMBER'),
			'direccion'  => sprintf('%s/%s', JText::translate('ORDER_PHONE'), JText::translate('ORDER_EMAIL')),
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
			'gender' 	 => 'spain_genderhospedajes',
			'parentesco' => 'spain_parentesco',
			'date_birth' => 'calendar',
			'address' 	 => 'text',
			'municipio'  => 'spain_municipio',
			'city' 	     => 'text',
			'postalcode' => 'text',
			'country_s'	 => 'country',
			'country_c'	 => 'country',
			'doctype'  	 => 'spain_doctype',
			'docnum' 	 => 'text',
			'docsoporte' => 'text',
			'direccion'  => 'text',
			'extranotes' => 'textarea',
		];
	}

	/**
	 * @inheritDoc
	 */
	public function listPrecheckinFields(array $def_fields)
	{
		$labels = $this->getLabels();
		$attributes = $this->getAttributes();

		// for pre-checkin we keep any default field of type "file" for uploading IDs
		foreach (($def_fields[1] ?? []) as $field_key => $field_type) {
			if (!is_string($field_type)) {
				// not looking for a list of options
				continue;
			}
			if (!strcasecmp($field_type, 'file') && ($def_fields[0][$field_key] ?? null)) {
				// append this pax field of type "file" for uploading IDs
				$labels[$field_key] = $def_fields[0][$field_key];
				$attributes[$field_key] = $field_type;
			}
		}

		// return the list of pre-checkin pax fields
		return [$labels, $attributes];
	}

	/**
	 * Returns the associative list of ID types for Spain (SES Hospedajes).
	 * The pax field "spain_doctype" will call this method with this name.
	 * 
	 * @return 	array 	associative list of doc types.
	 */
	public function loadDocumenti()
	{
		return [
			// Número de pasaporte
			'PAS'  => JText::translate('VBO_PASSPORT'),
			// NIF
			'NIF'  => 'NIF - Número de Identificación Fiscal',
			// NIE
			'NIE'  => 'NIE - Número de Identidad de Extranjero',
			// Otro
			'OTRO' => JText::translate('VBO_OTHER'),
		];
	}

	/**
	 * Returns the associative list of relationships with children (SES Hospedajes "parentesco").
	 * The pax field "spain_parentesco" will call this method with this name.
	 * 
	 * @return 	array 	associative list of "parentesco" (relationship) types.
	 */
	public function loadChildrenRelationships()
	{
		return [
			// Abuelo/a
			'AB' => JText::translate('VBO_SPAIN_CHILD_REL_AB'),
			// Bisabuelo/a
			'BA' => JText::translate('VBO_SPAIN_CHILD_REL_BA'),
			// Bisnieto/a
			'BN' => JText::translate('VBO_SPAIN_CHILD_REL_BN'),
			// Cuñado/a
			'CD' => JText::translate('VBO_SPAIN_CHILD_REL_CD'),
			// Cónyuge
			'CY' => JText::translate('VBO_SPAIN_CHILD_REL_CY'),
			// Hijo/a
			'HJ' => JText::translate('VBO_SPAIN_CHILD_REL_HJ'),
			// Hermano/a
			'HR' => JText::translate('VBO_SPAIN_CHILD_REL_HR'),
			// Nieto/a
			'NI' => JText::translate('VBO_SPAIN_CHILD_REL_NI'),
			// Padre o madre
			'PM' => JText::translate('VBO_SPAIN_CHILD_REL_PM'),
			// Sobrino/a
			'SB' => JText::translate('VBO_SPAIN_CHILD_REL_SB'),
			// Suegro/a
			'SG' => JText::translate('VBO_SPAIN_CHILD_REL_SG'),
			// Tío/a
			'TI' => JText::translate('VBO_SPAIN_CHILD_REL_TI'),
			// Yerno o nuera
			'YN' => JText::translate('VBO_SPAIN_CHILD_REL_YN'),
			// Tutor/a
			'TU' => JText::translate('VBO_SPAIN_CHILD_REL_TU'),
			// Otro
			'OT' => JText::translate('VBO_OTHER'),
		];
	}
}
