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

class JFormFieldVbcategory extends JFormField { 
	protected $type = 'vbcategory';
	
	function getInput() {
		$categories="";
		$dbo = JFactory::getDBO();
		$q="SELECT * FROM `#__vikbooking_categories` ORDER BY `#__vikbooking_categories`.`name` ASC;";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows() > 0) {
			$allvbc=$dbo->loadAssocList();
			foreach($allvbc as $vbc) {
				$categories.='<option value="'.$vbc['id'].'"'.($this->value == $vbc['id'] ? " selected=\"selected\"" : "").'>'.$vbc['name'].'</option>';
			}
		}
		$html = '<select class="widefat" name="' . $this->name . '" >';
		$html .= '<option value="">--</option>';
		$html .= $categories;
		$html .='</select>';
		return $html;
    }
}
