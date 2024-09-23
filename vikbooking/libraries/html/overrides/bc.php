<?php
/** 
 * @package     VikBooking - Libraries
 * @subpackage  html.overrides
 * @author      E4J s.r.l.
 * @copyright   Copyright (C) 2023 E4J s.r.l. All Rights Reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * @link        https://vikwp.com
 */

// No direct access
defined('ABSPATH') or die('No script kiddies please!');

$files = isset($displayData['files']) ? (array) $displayData['files'] : [];
$plain = '';

?>

<div class="breaking-changes">

	<!-- heading -->

	<h3 style="margin-top: 0;">
		<?php _e('Breaking Changes', 'vikbooking'); ?>
	</h3>

	<!-- what's this warning? -->

	<p>
		<?php
		_e(
			'The following overrides have been automatically unpublished in order to avoid possible errors due to deprecated code.',
			'vikbooking'
		);
		?>
	</p>

	<!-- unpublished files list -->

	<?php
	foreach ($files as $client => $list)
	{
		$plain .= ($plain ? "\n\n" : '') . "### " . strtoupper($client) . "\n";

		?>
		<h4 style="margin: 0;"><?php echo ucwords($client); ?></h4>

		<ul style="padding-left: 10px;">
			<?php
			foreach ($list as $file)
			{
				$plain .= "\n- " . $file;

				?>
				<li style="display: flex; align-items: center;">
					<input
						type="checkbox"
						id="override-<?php echo md5($file); ?>"
						class="breaking-changes-override-check"
						data-file="<?php echo $this->escape($file); ?>"
						style="margin: 0;"
					/>

					<label for="override-<?php echo md5($file); ?>" style="margin: 0 6px;">
						<code><?php echo str_replace(ABSPATH, '', (string) $file); ?></code>
					</label>

					<a
						href="admin.php?page=vikbooking&view=overrides&client=<?php echo $client; ?>&overridefile=<?php echo base64_encode($file); ?>"
						target="_blank"
						style="margin: 0;"
					><i class="<?php echo VikBookingIcons::i('external-link-square'); ?>" style="font-size: 18px;"></i></a>
				</li>
				<?php
			}
			?>
		</ul>
		<?php
	}
	?>

	<ul>
		
	</ul>

	<!-- what should I do? -->

	<p>
		<?php
		_e(
			'Please review the unpublished overrides if you wish to keep the changes you made. You can tick the box of the reviewed files to remove them from the list.',
			'vikbooking'
		);
		?>
	</p>

	<!--
		Create CSS rule to "hide" the textarea by keeping it
		active to allow the browser to copy its contents.
	-->

	<style>
		.breaking-changes textarea.keep-active-but-hidden {
			width: 0 !important;
			height: 0 !important;
			opacity: 0 !important;
			float: right;
			cursor: default;
		}
	</style>

	<textarea class="keep-active-but-hidden"><?php echo $plain; ?></textarea>

	<!-- define button to copy the files -->

	<button class="button" id="breaking-changes-copy"><?php _e('Copy'); ?></button>

	<!-- define button to dismiss the warning -->

	<button class="button" id="breaking-changes-dismiss"><?php _e('Dismiss', 'vikbooking'); ?></button>

</div>

<script>
	(function($) {
		'use strict';

		const unregisterBreakingChanges = (files) => {
			doAjax(
				'admin-ajax.php?action=vikbooking&task=override.dismissbc',
				{
					// can be an array of files, a single file (string) or null/undefined to dismiss all the files
					files: files,
				}
			);
		}

		$(function($) {
			// implement copy callback
			$('#breaking-changes-copy').on('click', function() {
				// copy path within the clipboard
				VBOCore.copyToClipboard($(this).prev('textarea')).then(() => {
					// display successful message on copy
					VBOToast.dispatch(new VBOToastMessage({
						body: '<?php echo addslashes(__('Copied!')); ?>',
						status: VBOToast.SUCCESS_STATUS,
						delay: 'auto',
					}));
				}).catch(() => {
					// cannot copy
					VBOToast.dispatch(new VBOToastMessage({
						body: '<?php echo addslashes(__('Cannot copy the files! Please proceed manually.')); ?>',
						status: VBOToast.ERROR_STATUS,
						delay: 'auto',
					}));
				});
			});

			// implement dismiss callback
			$('#breaking-changes-dismiss').on('click', function() {
				// hide the alert
				$(this).closest('.notice').find('.notice-dismiss').trigger('click');

				// dismiss breaking changes in background
				unregisterBreakingChanges();
			});

			// auto-hide only the selected file from the list of the breaking changes
			$('.breaking-changes-override-check').on('change', function() {
				$(this).prop('disabled', true);

				if ($('.breaking-changes-override-check').not(':disabled').length > 0) {
					// dismiss only this file
					unregisterBreakingChanges($(this).data('file'));	
				} else {
					// this is the last file, we can dismiss the whole warning
					$('#breaking-changes-dismiss').trigger('click');
				}
			});
		});
	})(jQuery);
</script>