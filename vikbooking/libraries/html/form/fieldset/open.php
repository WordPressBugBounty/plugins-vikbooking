<?php
/** 
 * @package   	VikBooking - Libraries
 * @subpackage 	html.form
 * @author    	E4J s.r.l.
 * @copyright 	Copyright (C) 2018 E4J s.r.l. All Rights Reserved.
 * @license  	http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * @link 		https://vikwp.com
 */

// No direct access
defined('ABSPATH') or die('No script kiddies please!');

$name 	= !empty($displayData['name'])   ? $displayData['name'] 			: '';
$class 	= !empty($displayData['class'])  ? ' ' . $displayData['class'] 		: '';
$id 	= !empty($displayData['id']) 	 ? "id=\"{$displayData['id']}\"" 	: '';

?>

<fieldset class="adminform">
	<div class="vbo-params-wrap">
		<?php if (!empty($name)) { ?>
			<legend class="adminlegend"><?php echo JText::translate($name); ?></legend>
		<?php } ?>
		<div class="vbo-params-container<?php echo $class; ?>" <?php echo $id; ?>>
