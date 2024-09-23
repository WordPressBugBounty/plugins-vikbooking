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

$label   = isset($displayData['label'])       ? $displayData['label']       : '';
$desc  	 = isset($displayData['description']) ? $displayData['description'] : '';
$id 	 = isset($displayData['id'])          ? $displayData['id']          : null;
$req 	 = isset($displayData['required'])    ? $displayData['required']    : 0;
$class   = isset($displayData['class'])       ? ' ' . $displayData['class'] : '';
$style   = isset($displayData['style'])       ? $displayData['style']       : '';

$label = JText::translate($label);

// remove trailing "colon" if already specified by the translation
$label = rtrim($label, ':');
// remove trailing "*" if already specified by the translation (only if required)
$label = $req ? rtrim($label, '*') : $label;

if ($label)
{
	$label .= ($req ? '*' : '') . ':';
}

?>
<div class="vbo-param-container<?php echo $class; ?>"<?php echo ($style ? ' style="' . $style . '"' : ''); ?>>
	<div class="vbo-param-label">
		<label
			<?php echo $id ? 'for="' . esc_attr($id) . '"' : ''; ?>
		><?php echo $label; ?></label>
	</div>
	<div class="vbo-param-setting">
