<?php
/** 
 * @package     VikBooking - Libraries
 * @subpackage  html.managetos
 * @author      E4J s.r.l.
 * @copyright   Copyright (C) 2022 E4J s.r.l. All Rights Reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * @link        https://vikwp.com
 */

// No direct access
defined('ABSPATH') or die('No script kiddies please!');

$field = $displayData['field'];

?>

<div style="padding: 10px;">

	<form id="tos-form-<?php echo (int) $field['id']; ?>">

		<div class="vbo-admin-container vbo-params-container-wide">

			<div class="vbo-params-container">

				<!-- Name - Textarea -->

				<div class="vbo-param-container">
					<div class="vbo-param-label"><?php echo JText::translate('VBPVIEWCUSTOMFONE'); ?> <sup>*</sup></div>
					<div class="vbo-param-setting">
						<textarea name="name" style="resize: vertical;height: 80px;"><?php echo JText::translate($field['name']); ?></textarea>
					</div>
				</div>

				<!-- Popup Link - Textarea -->

				<div class="vbo-param-container">
					<div class="vbo-param-label"><?php echo JText::translate('VBNEWCUSTOMFEIGHT'); ?> <sup>*</sup></div>
					<div class="vbo-param-setting">
						<textarea name="poplink" style="resize: vertical;height: 80px;"><?php echo JText::translate($field['poplink']); ?></textarea>
					</div>
				</div>

			</div>

		</div>

		<input type="hidden" name="id" value="<?php echo (int) $field['id']; ?>" />

	</form>

</div>
