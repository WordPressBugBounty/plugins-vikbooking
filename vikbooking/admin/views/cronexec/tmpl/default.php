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

?>
<style>
	body {
		padding: 0 !important;
	}
</style>

<div class="vbo-shell-wrap">
	<p class="vbo-shell-top-bar">
		<?php echo $this->cron->cron_name; ?> - <span><?php echo JText::translate('VBCRONEXECRESULT'); ?>:</span>
		<?php var_dump($this->response); ?>
	</p>
	<div class="vbo-shell-body" style="min-height: 400px;">
		<?php echo $this->cronModel->get('output'); ?>

		<?php if (strlen($this->cronModel->get('log', ''))): ?>
			<p>---------- LOG ----------</p>

			<div class="vbo-cronexec-log">
				<pre><?php echo $this->cronModel->get('log'); ?></pre>
			</div>
		<?php endif; ?>
	</div>
</div>

<script>
	(function($) {
		'use strict';

		const checkShellHeight = () => {
			let pageHeight  = $(window).height();
			let shellHeight = $('.vbo-shell-wrap').height();
			let bot_offset 	= 10;
			if (shellHeight < pageHeight) {
				let diff = pageHeight - shellHeight - bot_offset;
				$('.vbo-shell-body').css('height', '+=' + diff + 'px');
			}
		}

		$(function() {
			setTimeout(checkShellHeight, 300);
		});
	})(jQuery);
</script>
