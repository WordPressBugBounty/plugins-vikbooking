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

$desc = isset($displayData['description']) ? $displayData['description'] : '';

if ($desc) {
	?>
		<span class="vbo-param-setting-comment"><?php echo JText::translate($desc); ?></span>
	<?php
}
?>

	</div>
</div>
