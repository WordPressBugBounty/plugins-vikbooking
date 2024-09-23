<?php
/** 
 * @package     VikBooking
 * @subpackage  core
 * @author      Alessio Gaggii - E4J s.r.l.
 * @copyright   Copyright (C) 2021 E4J s.r.l. All Rights Reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * @link        https://vikwp.com
 */

// No direct access
defined('ABSPATH') or die('No script kiddies please!');

/**
 * Declares the methods that a custom pax data field driver should provide.
 * 
 * @since 	1.15.0 (J) - 1.5.0 (WP)
 */
interface VBOCheckinPaxfields
{
	/**
	 * Returns the name of the current pax data collection driver.
	 * 
	 * @return 	string    the name of this driver.
	 */
	public function getName();

	/**
	 * Builds the list pax fields labels.
	 * 
	 * @return 	array 	the list of pax fields labels.
	 */
	public function getLabels();

	/**
	 * Builds the list pax fields attributes.
	 * 
	 * @return 	array 	the list of pax fields attributes.
	 */
	public function getAttributes();

	/**
	 * Returns a list of pax fields to collect through this driver.
	 * 
	 * @return 	array    the list of pax fields.
	 */
	public function listFields();

	/**
	 * Tells whether children should be registered.
	 * 
	 * @param 	bool 	$precheckin 	true if requested for front-end pre check-in.
	 * 
	 * @return 	bool    true to also register the children.
	 * 
	 * @since 	1.16.3 (J) - 1.6.3 (WP) added $precheckin argument.
	 */
	public function registerChildren($precheckin = false);

	/**
	 * Returns the instance of the given pax field key.
	 * 
	 * @param 	string 	$key 		the field key identifier.
	 * 
	 * @return 	VBOCheckinPaxfield 	the requested pax field object.
	 */
	public function getField($key);

	/**
	 * Renders a specific pax field type.
	 * 
	 * @param 	VBOCheckinPaxfield 	$field 	the instance of the pax field to render.
	 * 
	 * @return 	string  			the HTML string to display the field.
	 */
	public function render(VBOCheckinPaxfield $field);
}
