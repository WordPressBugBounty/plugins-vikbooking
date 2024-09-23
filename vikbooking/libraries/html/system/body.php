<?php
/** 
 * @package   	VikBooking - Libraries
 * @subpackage 	html.system
 * @author    	E4J s.r.l.
 * @copyright 	Copyright (C) 2018 E4J s.r.l. All Rights Reserved.
 * @license  	http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * @link 		https://vikwp.com
 */

// No direct access
defined('ABSPATH') or die('No script kiddies please!');

// fix [tmpl component] and [admin.php] issues on wordpress
JHtml::fetch('behavior.component');

$html  = !empty($displayData['html'])  ? $displayData['html'] : '';
$class = !empty($displayData['class']) ? ' ' . $displayData['class'] : '';

?>

<div class="wrap plugin-container<?php echo $class; ?>">

	<?php VikBookingLayoutHelper::renderToolbar(); ?>

	<?php
	/**
	 * In order to avoid issues with the PHP Session, we no longer render the system messages here,
	 * we rather do it in the process() method of the VikBookingBody class.
	 * 
	 * @since 	1.3.5
	 */
	// VikBookingLayoutHelper::renderSystemMessages();
	?>

	<?php echo $html; ?>

</div>
