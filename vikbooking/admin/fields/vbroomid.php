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

jimport('joomla.form.formfield');

class JFormFieldVbroomid extends JFormField { 
	protected $type = 'vbroomid';
	
	function getInput() {
		$rooms="";
		$dbo = JFactory::getDBO();
		$q="SELECT `id`,`name` FROM `#__vikbooking_rooms` ORDER BY `#__vikbooking_rooms`.`name` ASC;";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows() > 0) {
			$allvbr=$dbo->loadAssocList();
			foreach($allvbr as $vbr) {
				$rooms.='<option value="'.$vbr['id'].'"'.($this->value == $vbr['id'] ? " selected=\"selected\"" : "").'>'.$vbr['name'].'</option>';
			}
		}
		$html = '<select class="widefat" name="' . $this->name . '" >';
		$html .= '<option value="">--</option>';
		$html .= $rooms;
		$html .='</select>';
		return $html;
	}
}
