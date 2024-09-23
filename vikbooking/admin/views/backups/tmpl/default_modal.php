<?php
/** 
 * @package     VikBooking
 * @subpackage  core
 * @author      E4J s.r.l.
 * @copyright   Copyright (C) 2021 E4J s.r.l. All Rights Reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * @link        https://vikwp.com
 */

// No direct access
defined('ABSPATH') or die('No script kiddies please!');

?>

<div style="padding: 10px;">

	<div class="vbo-admin-container vbo-params-container-wide">

		<div class="vbo-params-container">

			<!-- ACTION - Select -->

			<div class="vbo-param-container">
				<div class="vbo-param-label"><?php echo JText::translate('VBO_BACKUP_ACTION_LABEL'); ?> <sup>*</sup></div>
				<div class="vbo-param-setting">
					<select name="action" id="vbo-create-action-sel">
						<?php
						$options = [
							JHtml::fetch('select.option', 'create', JText::translate('VBO_BACKUP_ACTION_CREATE')),
							JHtml::fetch('select.option', 'upload', JText::translate('VBO_BACKUP_ACTION_UPLOAD')),
						];
						
						echo JHtml::fetch('select.options', $options);
						?>
					</select>
				</div>
			</div>

			<!-- TYPE - Select -->

			<div class="vbo-param-container backup-action-create">
				<div class="vbo-param-label"><?php echo JText::translate('VBO_CONFIG_BACKUP_TYPE'); ?> <sup>*</sup></div>
				<div class="vbo-param-setting">
					<select name="type" id="vbo-create-type-sel">
						<?php
						$options = [];
			
						foreach ($this->exportTypes as $id => $type)
						{
							$options[] = JHtml::fetch('select.option', $id, $type->getName());
						}

						echo JHtml::fetch('select.options', $options, 'value', 'text', VBOFactory::getConfig()->get('backuptype'));
						?>
					</select>
				</div>
			</div>

			<div class="backup-action-upload" style="display: none;">
				<div class="vbo-dropfiles-target" style="position: relative;">
					<p class="icon">
						<i class="fas fa-upload" style="font-size: 48px;"></i>
					</p>

					<div class="lead">
						<a href="javascript: void(0);" id="upload-file"><?php echo JText::translate('VBOMANUALUPLOAD'); ?></a>&nbsp;<?php echo JText::translate('VBODROPFILES'); ?>
					</div>

					<p class="maxsize">
						<?php echo JText::sprintf('JGLOBAL_MAXIMUM_UPLOAD_SIZE_LIMIT', JHtml::fetch('vikbooking.maxuploadsize')); ?>
					</p>

					<input type="file" id="legacy-upload" multiple style="display: none;"/>

					<div class="vbo-selected-archives" style="position: absolute; bottom: 6px; left: 6px; display: none;">
					
					</div>

					<div class="vbo-upload-progress" style="position: absolute; bottom: 6px; right: 6px; display: flex; visibility: hidden;">
						<progress value="0" max="100">0%</progress>
					</div>
				</div>
			</div>

		</div>

	</div>

</div>

<script>
	(function($) {
		'use strict';

		let dragCounter = 0;
		let file = 0;

		const addFile = (files) => {
			const bar = $('.vbo-selected-archives');

			if (files && files.length) {
				file = files[0];
				const badge = $('<span class="badge badge-info"></span>').text(file.name);
				bar.html(badge).show();
			} else {
				file = null;
				bar.hide().html('');
			}
		}

		const fileUpload = (formData, progressCallback) => {
			return new Promise((resolve, reject) => {
				$.ajax({
					xhr: () => {
						let xhrobj = $.ajaxSettings.xhr();
						if (xhrobj.upload) {
							xhrobj.upload.addEventListener('progress', (event) => {
								let percent = 0;
								let position = event.loaded || event.position;
								let total = event.total;
								if (event.lengthComputable) {
									percent = Math.ceil(position / total * 100);
								}
								
								if (progressCallback) {
									progressCallback(percent);
								}
							}, false);
						}
						return xhrobj;
					},
					url: '<?php echo VBOFactory::getPlatform()->getUri()->ajax('index.php?option=com_vikbooking&task=backup.save'); ?>',
					type: 'POST',
					contentType: false,
					processData: false,
					cache: false,
					data: formData,
					success: (resp) => {
						resolve(resp);
					},
					error: (err) => {
						reject(err);
					}, 
				});
			});
		}

		const openLoadingOverlay = () => {
			$('body').append('<div class="vbo-info-overlay-block" style="display:block;"><div class="vbo-info-overlay-loading" style="display:block;"><?php VikBookingIcons::e('circle-notch', 'fa-spin fa-fw fa-3x'); ?></div></div>');
		}

		const closeLoadingOverlay = () => {
			$('.vbo-info-overlay-block').remove();
		}

		const saveBackup = (btn) => {
			const formData = new FormData();

			const action = $('#vbo-create-action-sel').val();
			formData.append('ajax', 1);
			formData.append('backup_action', action);

			if (action === 'create') {
				formData.append('type', $('#vbo-create-type-sel').val());
			} else {
				formData.append('file', file);
			}

			const progressBox = $('.vbo-upload-progress');
			progressBox.css('visibility', 'visible');

			$(btn).prop('disabled', true);

			fileUpload(formData, (progress) => {
				// update progress
				progressBox.find('progress').val(progress).text(progress + '%');

				if (progress >= 100) {
					// Open loading overlay when reaching the 100% progress.
					// In case of export, the overlay should immediately appear.
					// In case of import, the overlay should appear after completing the upload
					// of the backup file, meaning that the system still have to decompress the
					// archive and perform all the registered rules. It's ok for us because the
					// upload status is already tracked by the apposite progress bar.
					openLoadingOverlay();
				}
			}).then((data) => {
				// auto-close the modal
				vboCloseJModal('newbackup');

				// then schedule an auto-refresh
				setTimeout(() => {
					document.adminForm.submit();
				}, 1000);
			}).catch((error) => {
				$(btn).prop('disabled', false);

				progressBox.css('visibility', 'hidden');

				// delay alert to properly close the overlay first
				setTimeout(() => {
					alert(error.responseText || 'Error');
				}, 128);
			}).finally(() => {
				// close overlay at the end of the process
				closeLoadingOverlay();
			});
		}

		$(function() {
			$('#vbo-create-action-sel').on('change', function() {
				if ($(this).val() === 'create') {
					$('.backup-action-upload').hide();
					$('.backup-action-create').show();
				} else {
					$('.backup-action-create').hide();
					$('.backup-action-upload').show();
				}
			});

			// drag&drop actions on target div

			$('.vbo-dropfiles-target').on('drag dragstart dragend dragover dragenter dragleave drop', (e) => {
				e.preventDefault();
				e.stopPropagation();
			});

			$('.vbo-dropfiles-target').on('dragenter', function(e) {
				// increase the drag counter because we may
				// enter into a child element
				dragCounter++;

				$(this).addClass('drag-enter');
			});

			$('.vbo-dropfiles-target').on('dragleave', function(e) {
				// decrease the drag counter to check if we 
				// left the main container
				dragCounter--;

				if (dragCounter <= 0) {
					$(this).removeClass('drag-enter');
				}
			});

			$('.vbo-dropfiles-target').on('drop', function(e) {
				$(this).removeClass('drag-enter');
				
				addFile(e.originalEvent.dataTransfer.files);
			});

			$('.vbo-dropfiles-target #upload-file').on('click', function() {
				// unset selected files before showing the dialog
				$('input#legacy-upload').val(null).trigger('click');
			});

			$('input#legacy-upload').on('change', function() {
				addFile($(this)[0].files);
			});

			$('button[data-role="backup.save"]').on('click', function() {
				saveBackup(this);
			});
		});
	})(jQuery);
</script>
