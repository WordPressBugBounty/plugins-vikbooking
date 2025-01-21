<?php
/**
 * @package     VikBooking
 * @subpackage  com_vikbooking
 * @author      Alessio Gaggii - E4J srl
 * @copyright   Copyright (C) 2024 E4J srl. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 * @link        https://vikwp.com
 */

defined('ABSPATH') or die('No script kiddies please!');

/**
 * Obtain vars from arguments received in the layout file.
 * 
 * @var 	string 	$caller 	the name of the page invoking the layout file (i.e. "view" or "widget").
 * @var 	array 	$customer 	the customer record involved.
 */
extract($displayData);

if (!is_array($customer) || !$customer) {
	return;
}

$caller = strtolower($caller ?? 'view');

$files = VikBooking::getCustomerDocuments($customer['id']);

if ($caller === 'view') {
?>
<fieldset class="adminform">
	<div class="vbo-params-wrap">
		<legend class="adminlegend"><?php echo JText::translate('VBOCUSTOMERDOCUMENTS'); ?></legend>
		<div class="vbo-params-container">
<?php
}
?>
			<div class="vbo-dropfiles-target">
				<div class="vbo-uploaded-files" id="vbo-uploaded-files">
					
					<?php
					foreach ($files as $file)
					{
						?>
						<div class="file-elem" data-file="<?php echo $file->basename; ?>">
							<div class="file-elem-inner">
								<a href="<?php echo $file->url; ?>" target="_blank" class="file-link">
									<?php VikBookingIcons::e('file'); ?>
									<span class="file-extension"><?php echo $file->ext; ?></span>
								</a>

								<div class="file-summary">
									<div class="filename"><?php echo $file->name; ?></div>
									<div class="filesize"><?php echo JHtml::fetch('number.bytes', $file->size, 'auto', 0); ?></div>
								</div>

								<a href="javascript:void(0);" class="delete-file"><?php VikBookingIcons::e('times-circle'); ?></a>
							</div>
						</div>
						<?php
					}
					?>

				</div>

				<p class="icon">
					<i class="<?php echo VikBookingIcons::i('upload'); ?>" style="font-size: 48px;"></i>
				</p>

				<div class="lead">
					<a href="javascript: void(0);" id="upload-file"><?php echo JText::translate('VBOMANUALUPLOAD'); ?></a>&nbsp;<?php echo JText::translate('VBODROPFILES'); ?>
				</div>

				<p class="maxsize">
					<?php echo JText::sprintf('JGLOBAL_MAXIMUM_UPLOAD_SIZE_LIMIT', JHtml::fetch('vikbooking.maxuploadsize')); ?>
				</p>

				<input type="file" id="legacy-upload" style="display: none;" multiple="multiple">
			</div>

			<?php
			if ($caller === 'view'): 
			?>
			<div class="drop-files-hint">
				<?php
				echo VikBooking::getVboApplication()->createPopover(array(
					'title'     => 'Drop Files',
					'content'   => JText::translate('VBODROPFILESHINT'),
					'placement' => 'left',
				));
				?>
			</div>
			<?php
			endif;
			?>
<?php
if ($caller === 'view') {
	?>
		</div>
	</div>
</fieldset>
<?php
}
?>

<div class="stop-managing-files-hint"><?php echo JText::translate('VBODROPFILESSTOPREMOVING'); ?></div>

<script>

	jQuery(function() {

		var dragCounter = 0;

		// drag&drop actions on target div

		jQuery('.vbo-dropfiles-target').on('drag dragstart dragend dragover dragenter dragleave drop', function(e) {
			e.preventDefault();
			e.stopPropagation();
		});

		jQuery('.vbo-dropfiles-target').on('dragenter', function(e) {
			// increase the drag counter because we may
			// enter into a child element
			dragCounter++;

			jQuery(this).addClass('drag-enter');
		});

		jQuery('.vbo-dropfiles-target').on('dragleave', function(e) {
			// decrease the drag counter to check if we 
			// left the main container
			dragCounter--;

			if (dragCounter <= 0) {
				jQuery(this).removeClass('drag-enter');
			}
		});

		jQuery('.vbo-dropfiles-target').on('drop', function(e) {

			jQuery(this).removeClass('drag-enter');
			
			var files = e.originalEvent.dataTransfer.files;
			
			execUploads(files);
			
		});

		jQuery('.vbo-dropfiles-target #upload-file').on('click', function() {

			jQuery('input#legacy-upload').trigger('click');

		});

		jQuery('input#legacy-upload').on('change', function() {
			
			execUploads(jQuery(this)[0].files);

		});

		// make all current files removable by pressing them
		makeFileRemovable();

		jQuery(window).keyup(function(event) {
			if (event.keyCode == 27) {
				VBO_DROP_FILES_CAN_REMOVE = false;
				jQuery('#vbo-uploaded-files .file-elem').removeClass('do-shake');

				jQuery('.stop-managing-files-hint').hide();
			}
		});

		jQuery('#vbo-uploaded-files .file-elem a.delete-file').on('click', fileRemoveThread);

	});
	
	// upload
	
	function execUploads(files) {
		if (VBO_DROP_FILES_CAN_REMOVE) {
			return false;
		}

		for (var i = 0; i < files.length; i++) {
			if (isFileSupported(files[i].name)) {
				var status = new UploadingFile();
				status.setFileNameSize(files[i].name, files[i].size);
				status.setProgress(0);
				
				jQuery('#vbo-uploaded-files').prepend(status.getHtml());

				makeFileRemovable(jQuery('#vbo-uploaded-files .file-elem:first-child a'));
				
				fileUploadThread(status, files[i]);
			} else {
				alert('File ' + files[i].name + ' not supported');
			}
		}
	}
	
	function UploadingFile() {
		// create parent
		this.fileBlock = jQuery('<div class="file-elem uploading"></div>');

		// create file link and append it to parent block
		this.fileUrl = jQuery('<a href="javascript:void(0);" target="_blank"><?php VikBookingIcons::e('file'); ?></a>').appendTo(this.fileBlock);
		// create file extension
		this.fileExt = jQuery('<span class="file-extension"></span>').appendTo(this.fileUrl);

		// create file summary
		this.fileSummary = jQuery('<div class="file-summary"></div>').appendTo(this.fileBlock);

		// create file name
		this.fileName = jQuery('<div class="filename"></div>').appendTo(this.fileSummary);
		// create file size
		this.fileSize = jQuery('<div class="filesize"></div>').appendTo(this.fileSummary);

		// create remove link
		this.removeLink = jQuery('<a href="javascript:void(0);" class="delete-file"><?php VikBookingIcons::e('times'); ?></a>').appendTo(this.fileBlock);

		this.removeLink.on('click', fileRemoveThread);
	 
		this.setFileNameSize = function(name, size) {
			// fetch name
			var match = name.match(/(.*?)\.([a-z0-9]{2,})$/i);

			if (match && match.length) {
				this.fileName.html(match[1]);
				this.fileExt.html(match[2]);
			} else {
				this.fileName.html(name);
			}

			// fetch size
			var sizeStr = "";

			if (size > 1024*1024) {
				var sizeMB = size/(1024*1024);
				sizeStr = sizeMB.toFixed(2)+" MB";
			} else if (size > 1024) {
				var sizeKB = size/1024;
				sizeStr = sizeKB.toFixed(2)+" kB";
			} else {
				sizeStr = size.toFixed(2)+" B";
			}

			this.fileSize.html(sizeStr);
		}
		
		this.setProgress = function(progress) {       
			var opacity = parseFloat(progress / 100);

			this.fileBlock.css('opacity', opacity);
		}
		
		this.complete = function(file) {
			this.setProgress(100);
			
			this.fileUrl.attr('href', file.url);
			this.fileName.html(file.name);
			this.fileExt.html(file.ext);
			this.fileSize.html(file.size);
			this.fileBlock.removeClass('uploading');
			this.fileBlock.attr('data-file', file.filename);
		}
		
		this.getHtml = function() {
			return this.fileBlock;
		}
	}

	var vboDropFilesFormData = null;
	
	function fileUploadThread(status, file) {
		jQuery.noConflict();
		
		vboDropFilesFormData = new FormData();
		vboDropFilesFormData.append('file', file);
		vboDropFilesFormData.append('customer', <?php echo (int) $customer['id']; ?>);
		
		var jqxhr = jQuery.ajax({
			xhr: function() {
				var xhrobj = jQuery.ajaxSettings.xhr();
				if (xhrobj.upload) {
					xhrobj.upload.addEventListener('progress', function(event) {
						var percent = 0;
						var position = event.loaded || event.position;
						var total = event.total;
						if (event.lengthComputable) {
							percent = Math.ceil(position / total * 100);
						}
						// update progress
						status.setProgress(percent);
					}, false);
				}
				return xhrobj;
			},
			url: '<?php echo VikBooking::ajaxUrl('index.php?option=com_vikbooking&task=upload_customer_document'); ?>',
			type: 'POST',
			contentType: false,
			processData: false,
			cache: false,
			data: vboDropFilesFormData,
			success: function(resp) {
				try {
					resp = JSON.parse(resp);

					if (resp.status == 1) {
						status.complete(resp);
					} else {
						throw resp.error ? resp.error : 'An error occurred! Please try again.';
					}
				} catch (err) {
					console.warn(err, resp);

					alert(err);

					status.fileBlock.remove();
				}
			},
			error: function(err) {
				console.error(err.responseText);

				status.fileBlock.remove();

				alert('An error occurred! Please try again.');
			}, 
		});
	}
	
	function isFileSupported(name) {
		return name.match(/\.(jpe?g|png|bmp|heic|pdf|zip|rar|txt|md|docx?|odt|rtf|pages|xlsx?|ods|numbers|csv)$/i);
	}

	var VBO_DROP_FILES_CAN_REMOVE = false;

	function makeFileRemovable(selector) {
		if (!selector) {
			selector = '#vbo-uploaded-files .file-elem a';
		}

		jQuery(selector).each(function() {
			var timeout = null;

			jQuery(this).on('mousedown', function(event) {
				timeout = setTimeout(function() {
					VBO_DROP_FILES_CAN_REMOVE = true;

					jQuery('#vbo-uploaded-files .file-elem').addClass('do-shake');

					jQuery('.stop-managing-files-hint').show();
				}, 1000);
			}).on('mouseup mouseleave', function(event) {
				clearTimeout(timeout);
			}).on('click', function(event) {
				if (VBO_DROP_FILES_CAN_REMOVE) {
					event.preventDefault();
					event.stopPropagation();
					return false;
				}
			});
		});
	}

	function fileRemoveThread() {
		var elem = jQuery(this).closest('.file-elem');
		var file = jQuery(elem).attr('data-file');

		if (!file.length) {
			return false;
		}

		elem.addClass('removing');

		jQuery.ajax({
			url: '<?php echo VikBooking::ajaxUrl('index.php?option=com_vikbooking&task=delete_customer_document'); ?>',
			type: 'post',
			data: {
				file: file,
				customer: <?php echo (int) $customer['id']; ?>,
			},
		}).done(function(resp) {

			elem.remove();

			if (jQuery('#vbo-uploaded-files .file-elem').length == 0) {
				var esc = jQuery.Event('keyup', { keyCode: 27 });
				jQuery(window).trigger(esc);
			}

		}).fail(function(resp) {

			console.error(err.responseText);

			elem.removeClass('removing');

			alert('An error occurred! Please try again.');

		});
	}

</script>
