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

$document = JFactory::getDocument();
// load jQuery lib e jQuery UI
if (VikBooking::loadJquery()) {
	JHtml::fetch('jquery.framework', true, true);
}
$document->addStyleSheet(VBO_SITE_URI.'resources/jquery-ui.min.css');
JHtml::fetch('script', VBO_SITE_URI.'resources/jquery-ui.min.js');

$currencysymb = VikBooking::getCurrencySymb();
$datesep = VikBooking::getDateSeparator();
$nowdf = VikBooking::getDateFormat();
if ($nowdf == "%d/%m/%Y") {
	$df = 'd/m/Y';
	$juidf = 'dd/mm/yy';
} elseif ($nowdf == "%m/%d/%Y") {
	$df = 'm/d/Y';
	$juidf = 'mm/dd/yy';
} else {
	$df = 'Y/m/d';
	$juidf = 'yy/mm/dd';
}
$wdays_map 	= array(
	JText::translate('VBWEEKDAYZERO'),
	JText::translate('VBWEEKDAYONE'),
	JText::translate('VBWEEKDAYTWO'),
	JText::translate('VBWEEKDAYTHREE'),
	JText::translate('VBWEEKDAYFOUR'),
	JText::translate('VBWEEKDAYFIVE'),
	JText::translate('VBWEEKDAYSIX')
);
$ts_info = getdate($this->order['ts']);
$checkin_info = getdate($this->order['checkin']);
$checkout_info = getdate($this->order['checkout']);
$pitemid = VikRequest::getInt('Itemid', 0, 'request');

// compose the calculation for pax_data
list($pax_fields, $pax_fields_attributes) = VikBooking::getPaxFields(true);
$pax_data = array();
$count_pax_data = 1;
if (!empty($this->customer['pax_data'])) {
	$pax_data = json_decode($this->customer['pax_data'], true);
}

$arrpeople = array();
foreach ($this->orderrooms as $ind => $or) {
	$num = $ind + 1;
	$arrpeople[$num]['adults'] = $or['adults'];
	$arrpeople[$num]['children'] = $or['children'];
	$arrpeople[$num]['children_age'] = $or['childrenage'];
	$arrpeople[$num]['t_first_name'] = $or['t_first_name'];
	$arrpeople[$num]['t_last_name'] = $or['t_last_name'];
}

// countries list
$all_countries = VikBooking::getCountriesArray();

// access the active back-end driver instance also for pre-checkin
$pax_fields_obj = VBOCheckinPax::getInstance();

// load langs for JS
JText::script('VBO_UPLOAD_FAILED');
JText::script('VBO_REMOVEF_CONFIRM');
JText::script('VBO_PRECHECKIN_TOAST_HELP');
?>

<h3 class="vbo-booking-details-intro"><?php echo JText::sprintf('VBOYOURBOOKCONFAT', VikBooking::getFrontTitle()); ?></h3>

<div class="vbo-booking-details-topcontainer vbo-booking-details-precheckin">
	
	<div class="vbo-booking-details-head vbo-booking-details-head-confirmed">
		<h4><?php echo JText::translate('VBOPRECHECKIN'); ?></h4>
	</div>

	<div class="vbo-booking-details-midcontainer">
		<div class="vbo-booking-details-bookinfos">
			<span class="vbvordudatatitle"><?php echo JText::translate('VBORDERDETAILS'); ?></span>
			<div class="vbo-booking-details-bookinfo">
				<span class="vbo-booking-details-bookinfo-lbl"><?php echo JText::translate('VBORDEREDON'); ?></span>
				<span class="vbo-booking-details-bookinfo-val"><?php echo $wdays_map[$ts_info['wday']].', '.date(str_replace("/", $datesep, $df).' H:i', $this->order['ts']); ?></span>
			</div>
			<div class="vbo-booking-details-bookinfo">
				<span class="vbo-booking-details-bookinfo-lbl"><?php echo JText::translate('VBCONFIRMNUMB'); ?></span>
				<span class="vbo-booking-details-bookinfo-val"><?php echo $this->order['confirmnumber']; ?></span>
			</div>
			<div class="vbo-booking-details-bookinfo">
				<span class="vbo-booking-details-bookinfo-lbl"><?php echo JText::translate('VBDAL'); ?></span>
				<span class="vbo-booking-details-bookinfo-val"><?php echo $wdays_map[$checkin_info['wday']].', '.date(str_replace("/", $datesep, $df).' H:i', $this->order['checkin']); ?></span>
			</div>
			<div class="vbo-booking-details-bookinfo">
				<span class="vbo-booking-details-bookinfo-lbl"><?php echo JText::translate('VBAL'); ?></span>
				<span class="vbo-booking-details-bookinfo-val"><?php echo $wdays_map[$checkout_info['wday']].', '.date(str_replace("/", $datesep, $df).' H:i', $this->order['checkout']); ?></span>
			</div>
			<div class="vbo-booking-details-bookinfo">
				<span class="vbo-booking-details-bookinfo-lbl"><?php echo JText::translate('VBDAYS'); ?></span>
				<span class="vbo-booking-details-bookinfo-val"><?php echo $this->order['days']; ?></span>
			</div>
		</div>
	</div>

	<div class="vbo-precheckin-container">
		<div class="info vbo-precheckin-disclaimer"><?php echo JText::translate('VBO_PRECHECKIN_DISCLAIMER'); ?></div>
		<form action="<?php echo JRoute::rewrite('index.php?option=com_vikbooking'.(!empty($pitemid) ? '&Itemid='.$pitemid : '')); ?>" method="post">
			<input type="hidden" name="option" value="com_vikbooking" />
			<input type="hidden" name="task" value="storeprecheckin" />
			<input type="hidden" name="sid" value="<?php echo empty($this->order['sid']) && !empty($this->order['idorderota']) ? $this->order['idorderota'] : $this->order['sid']; ?>" />
			<input type="hidden" name="ts" value="<?php echo $this->order['ts']; ?>" />
			<input type="hidden" name="Itemid" value="<?php echo $pitemid; ?>" />
		<?php
		foreach ($this->orderrooms as $ind => $or) {
			$num = $ind + 1;
			// total guests details available
			$count_pax_data = $num < 2 ? $count_pax_data : 0;
			if (isset($pax_data[$ind])) {
				$count_pax_data = count($pax_data[$ind]);
			}

			// calculate the number of guests to register depending on current driver settings for pre-checkin (plugin only)
			$guests_to_register = $pax_fields_obj->registerChildren($precheckin = true) ? ($arrpeople[$num]['adults'] + $arrpeople[$num]['children']) : $arrpeople[$num]['adults'];
			?>
			<div class="vbo-precheckin-room-wrapper">
				<div class="vbo-precheckin-room-wrap <?php echo $count_pax_data >= $guests_to_register ? 'vbo-precheckin-guestscount-complete' : 'vbo-precheckin-guestscount-incomplete'; ?>">
					<div class="vbo-precheckin-room-head">
						<span><?php echo $or['name']; ?></span>
					</div>
					<div class="vbo-precheckin-adults-cont">
					<?php
					for ($g = 1; $g <= $guests_to_register; $g++) {
						$current_guest = array();
						if (count($pax_data) && isset($pax_data[$ind]) && isset($pax_data[$ind][$g])) {
							$current_guest = $pax_data[$ind][$g];
						} elseif ($ind < 1 && $g == 1) {
							$current_guest = $this->customer;
						}
						?>
						<div class="vbo-precheckin-adult-wrap">
							<div class="vbo-precheckin-adult-num">
								<span><?php echo JText::sprintf('VBOGUESTNUM', $g); ?></span>
							</div>
						<?php
						/**
						 * Overrides tip: to add and collect custom details from each guest it is possible to push more
						 * pairs of key-values to $pax_fields. For example, a custom value could be pushed like this:
						 * 
						 * $pax_fields['custom_field_key'] = 'Custom Field Name';
						 * $pax_fields_attributes['custom_field_key'] = 'text';
						 * 
						 * @see 	the best way to customize the guest fields is to use the apposite plugin hooks/events.
						 */
						$pax_field_ind = 1;
						foreach ($pax_fields as $paxk => $paxv) {
							?>
							<div class="vbo-precheckin-guest-detail<?php echo isset($pax_fields_attributes[$paxk]) && $pax_fields_attributes[$paxk] == 'file' ? ' vbo-precheckin-guest-detail-files' : ''; ?>">
								<label for="pax-field-<?php echo $paxv . '-' . $ind . '-' . $g . '-' . $paxk; ?>"><?php echo $paxv; ?></label>
							<?php
							if (isset($pax_fields_attributes[$paxk]) && is_array($pax_fields_attributes[$paxk])) {
								// select with multiple choices
								?>
								<select name="guests[<?php echo $ind; ?>][<?php echo $g; ?>][<?php echo $paxk; ?>]">
									<option></option>
								<?php
								$opt_ind = 1;
								foreach ($pax_fields_attributes[$paxk] as $attrk => $attrv) {
									$paxv_selected = isset($current_guest[$paxk]) && ($current_guest[$paxk] == $attrv || (!is_numeric($attrk) && $current_guest[$paxk] == $attrk));
									if (!$paxv_selected && isset($current_guest[$paxk]) && is_numeric($current_guest[$paxk])) {
										/**
										 * It is safe to compare such select tags, which usually refer to "gender",
										 * to even numeric values in order to attempt to pre-select the current value.
										 */
										$paxv_selected = ($opt_ind == $current_guest[$paxk]);
									}
									?>
									<option value="<?php echo !is_numeric($attrk) ? $attrk : $attrv; ?>"<?php echo ($paxv_selected ? ' selected="selected"' : ''); ?>><?php echo $attrv; ?></option>
									<?php
									$opt_ind++;
								}
								?>
								</select>
								<?php
							} elseif (isset($pax_fields_attributes[$paxk]) && $pax_fields_attributes[$paxk] == 'country') {
								// field of type country
								echo VikBooking::getCountriesSelect('guests['.$ind.']['.$g.']['.$paxk.']', $all_countries, (isset($current_guest[$paxk]) ? $current_guest[$paxk] : ''), $paxv);
							} elseif (isset($pax_fields_attributes[$paxk]) && $pax_fields_attributes[$paxk] == 'calendar') {
								// datepicker
								?>
								<input type="text" autocomplete="off" data-gind="<?php echo $g; ?>" class="vbo-paxfield vbo-paxfield-datepicker" name="guests[<?php echo $ind; ?>][<?php echo $g; ?>][<?php echo $paxk; ?>]" value="<?php echo (isset($current_guest[$paxk]) ? $this->escape($current_guest[$paxk]) : ''); ?>" />
								<?php
							} elseif (isset($pax_fields_attributes[$paxk]) && $pax_fields_attributes[$paxk] == 'file') {
								// file upload (multiple files allowed)
								?>
								<input type="hidden" id="vbo-paxfield-curfiles-<?php echo $ind . '-' . $g . '-' . $paxk; ?>" name="guests[<?php echo $ind; ?>][<?php echo $g; ?>][<?php echo $paxk; ?>]" value="<?php echo (isset($current_guest[$paxk]) ? $this->escape($current_guest[$paxk]) : ''); ?>" />

								<div class="vbo-paxfield-upload-container">
									<button type="button" class="btn vbo-pref-color-btn-secondary vbo-precheckin-uploadfile" data-roomi="<?php echo $ind; ?>" data-guesti="<?php echo $g; ?>" data-paxk="<?php echo $paxk; ?>"><?php VikBookingIcons::e('camera'); ?> <?php echo JText::translate('VBOUPSELLADD'); ?></button>
									<div class="vbo-paxfield-upload-progress-wrap" id="vbo-paxfield-upload-progress-<?php echo $ind . '-' . $g . '-' . $paxk; ?>" style="display: none;">
										<div class="vbo-paxfield-upload-progress">&nbsp;</div>
									</div>
								</div>
								
								<div class="vbo-paxfield vbo-paxfield-files" id="vbo-paxfield-files-<?php echo $ind . '-' . $g . '-' . $paxk; ?>" data-gselector="<?php echo $ind . '-' . $g . '-' . $paxk; ?>">
								<?php
								if (isset($current_guest[$paxk]) && !empty($current_guest[$paxk])) {
									$guest_files = explode('|', $current_guest[$paxk]);
									foreach ($guest_files as $gfk => $guest_file) {
										if (empty($guest_file) || strpos($guest_file, 'http') !== 0) {
											continue;
										}
										$furl_segments = explode('/', $guest_file);
										$guest_fname = $furl_segments[(count($furl_segments) - 1)];
										$read_fname = substr($guest_fname, (strpos($guest_fname, '_') + 1));
										?>
									<div class="vbo-paxfield-file-uploaded">
										<span class="vbo-paxfield-file-uploaded-rm"><?php VikBookingIcons::e('times-circle'); ?></span>
										<a href="<?php echo $guest_file; ?>" target="_blank">
											<?php VikBookingIcons::e('image'); ?>
											<span><?php echo $read_fname; ?></span>
										</a>
									</div>
										<?php
									}
								}
								?>
								</div>
								<?php
							} elseif (isset($pax_fields_attributes[$paxk]) && $pax_fields_attributes[$paxk] == 'text') {
								// text
								?>
								<input type="text" autocomplete="off" data-gind="<?php echo $g; ?>" class="vbo-paxfield" name="guests[<?php echo $ind; ?>][<?php echo $g; ?>][<?php echo $paxk; ?>]" value="<?php echo (isset($current_guest[$paxk]) ? $this->escape($current_guest[$paxk]) : ''); ?>" />
								<?php
							} else {
								/**
								 * Attempt to render a custom field previously installed through a third party plugin.
								 * If no such field type is found, the object will default to the "text" type.
								 * 
								 * @since 	1.16.3 (J) - 1.6.3 (WP)
								 */

								// get an instance of the VBOCheckinPaxfield object
								$pax_field_obj = $pax_fields_obj->getField($paxk);

								// detect the current type of guest
								$guest_type = $g > $arrpeople[$num]['adults'] ? 'child' : 'adult';

								// set object data
								$pax_field_obj->setGuestType($guest_type)
									->setFieldNumber($pax_field_ind)
									->setGuestNumber($g)
									->setGuestData($current_guest)
									->setRoomIndex($ind)
									->setRoomGuests($arrpeople[$num]['adults'], $arrpeople[$num]['children'])
									->setTotalRooms(count($arrpeople));

								// render input field
								$pax_field_html = $pax_fields_obj->render($pax_field_obj);
								if (empty($pax_field_html)) {
									// nothing to display, increase the counter and continue
									$pax_field_ind++;
									continue;
								}

								// access the implementor object
								$implementor = $pax_fields_obj->getFieldTypeImplementor($pax_field_obj);

								// check if a particular CSS class has been defined
								$field_container_class = $implementor->getContainerClassAttr();
								$field_container_class = !empty($field_container_class) ? " $field_container_class" : '';

								// display the custom field type
								?>
								<div class="vbo-precheckin-custom-field<?php echo $field_container_class; ?>">
									<?php echo $pax_field_html; ?>
								</div>
								<?php
							}
							?>
							</div>
							<?php
							$pax_field_ind++;
						}
						?>
						</div>
						<?php
					}
					?>
					</div>
				</div>
			</div>
			<?php
		}
		?>
			<div class="vbo-precheckin-submit">
				<button type="submit" class="btn btn-large vbo-pref-color-btn"><?php echo JText::translate('VBOSUBMITPRECHECKIN'); ?></button>
			</div>
		</form>
	</div>

</div>

<input type="file" id="vbo-precheckin-upload-field" accept="image/*,.pdf" multiple="multiple" style="display: none;" />

<script type="text/javascript">
	/**
	 * Declare global score variables
	 */
	var vbo_prechin_current_room  = null,
		vbo_prechin_current_guest = null,
		vbo_prechin_current_paxk  = null,
		vbo_typingtoast_displayed = false,
		vbo_typingtoast_changes   = 0;

	/**
	 * Displays a toast message
	 */
	function vboPresentToast(content, duration, clickcallback) {
		// remove any other previous toast from the document
		jQuery('.vbo-toast-message').remove();
		// build toast
		var toast = jQuery('<div>').addClass('vbo-toast-message vbo-toast-message-presented');
		// onclick function
		var onclickfunc = function() {
			// hide toast when clicked
			jQuery(this).removeClass('vbo-toast-message-presented').addClass('vbo-toast-message-dimissed');
		};
		if (typeof clickcallback === 'function') {
			onclickfunc = function() {
				// launch callback
				clickcallback.call(this);
				// hide toast either way
				jQuery(this).removeClass('vbo-toast-message-presented').addClass('vbo-toast-message-dimissed');
			};
		}
		// register click event on toast
		toast.on('click', onclickfunc);
		// build toast content
		var inner = jQuery('<div>').addClass('vbo-toast-message-content');
		toast.append(inner.append(content));
		// present toast
		jQuery('body').append(toast);
		// set timeout for auto-dismiss
		if (typeof duration === 'undefined') {
			duration = 4000;
		}
		if (!isNaN(duration) && duration > 0) {
			// if duration NaN or <= 0, the toast won't be dismissed automatically
			setTimeout(function() {
				jQuery('.vbo-toast-message').removeClass('vbo-toast-message-presented').addClass('vbo-toast-message-dimissed');
			}, duration);
		}
	}

	/**
	 * Some older smartphones or tablets may not support files uploading
	 */
	function vboIsUploadSupported() {
		if (!navigator || !navigator.userAgent) {
			return false;
		}
		if (navigator.userAgent.match(/(Android (1.0|1.1|1.5|1.6|2.0|2.1))|(Windows Phone (OS 7|8.0))|(XBLWP)|(ZuneWP)|(w(eb)?OSBrowser)|(webOS)|(Kindle\/(1.0|2.0|2.5|3.0))/)) {
			return false;
		} else {
			return true;
		}
	}

	/**
	 * Checks wether a jQuery XHR response object was due to a connection error.
	 * Property readyState 0 = Network Error (UNSENT), 4 = HTTP error (DONE).
	 * Property responseText may not be set in some browsers.
	 * This is what to check to determine if a connection error occurred.
	 */
	function vboIsConnectionLostError(err) {
		if (!err || !err.hasOwnProperty('status')) {
			return false;
		}

		return (
			err.statusText == 'error'
			&& err.status == 0
			&& (err.readyState == 0 || err.readyState == 4)
			&& (!err.hasOwnProperty('responseText') || err.responseText == '')
		);
	}

	/**
	 * Ensures AJAX requests that fail due to connection errors are retried automatically.
	 * This function is made specifically to work with AJAX uploads.
	 */
	function vboDoAjaxUpload(url, data, success, failure, progress, attempt) {
		var VBO_AJAX_MAX_ATTEMPTS = 3;

		if (attempt === undefined) {
			attempt = 1;
		}

		var settings = {
			type: 		 'post',
			contentType: false,
			processData: false,
			cache: 		 false,
		};

		// register event for upload progress
		settings.xhr = function() {
			var xhrobj = jQuery.ajaxSettings.xhr();
			if (xhrobj.upload) {
				// attach progress event
				xhrobj.upload.addEventListener('progress', function(event) {
					// calculate percentage
					var percent  = 0;
					var position = event.loaded || event.position;
					var total 	 = event.total;
					if (event.lengthComputable) {
						percent = Math.ceil(position / total * 100);
					}
					// trigger callback
					progress(percent);
				}, false);
			}
			return xhrobj;
		};

		if (typeof url === 'object') {
			// configuration object passed
			Object.assign(settings, url);
		} else {
			// use the default settings
			settings.url  = url;
		}

		// set request data
		settings.data = data;

		return jQuery.ajax(
			settings
		).done(function(resp) {
			if (success !== undefined) {
				// launch success callback function
				success(resp);
			}
		}).fail(function(err) {
			/**
			 * If the error is caused by a site connection lost, and if the number
			 * of retries is lower than max attempts, retry the same AJAX request.
			 */
			if (attempt < VBO_AJAX_MAX_ATTEMPTS && vboIsConnectionLostError(err)) {
				// delay the retry by half second
				setTimeout(function() {
					// relaunch same request and increase number of attempts
					console.log('Retrying previous AJAX request');
					vboDoAjaxUpload(url, data, success, failure, progress, (attempt + 1));
				}, 500);
			} else {
				// launch the failure callback otherwise
				if (failure !== undefined) {
					failure(err);
				}
			}

			// always log the error in console
			console.log('AJAX request failed' + (err.status == 500 ? ' (' + err.responseText + ')' : ''), err);
		});
	}

	/**
	 * Updates the progress bar for the current uploading process
	 */
	function vboUploadSetProgress(selector_suffix, progress) {
		progress = Math.max(0, progress);
		progress = Math.min(100, progress);

		var progress_wrap = jQuery('#vbo-paxfield-upload-progress-' + selector_suffix);
		if (!progress_wrap.length) {
			return;
		}
		progress_wrap.find('.vbo-paxfield-upload-progress').width(progress + '%').html(progress + '%');
	}

	/**
	 * Uploads the selected document(s)
	 */
	function vboUploadDocuments(files) {
		// create form data object
		var formData = new FormData();

		// set booking and pre-checkin information
		formData.append('sid', '<?php echo empty($this->order['sid']) && !empty($this->order['idorderota']) ? $this->order['idorderota'] : $this->order['sid']; ?>');
		formData.append('ts', '<?php echo $this->order['ts']; ?>');
		formData.append('room_index', vbo_prechin_current_room);
		formData.append('guest_index', vbo_prechin_current_guest);
		formData.append('pax_index', vbo_prechin_current_paxk);

		// selector suffix is composed of room, guest and pax field indexes
		var selector_suffix = vbo_prechin_current_room + '-' + vbo_prechin_current_guest + '-' + vbo_prechin_current_paxk;

		// iterate files selected and append them to the form data
		for (var i = 0; i < files.length; i++) {
			formData.append('docs[]', files[i]);
		}

		// display progress wrap
		jQuery('#vbo-paxfield-upload-progress-' + selector_suffix).show();

		// AJAX request to upload the files selected
		vboDoAjaxUpload(
			// url
			'<?php echo VikBooking::ajaxUrl(JRoute::rewrite('index.php?option=com_vikbooking&task=precheckin_upload_docs&tmpl=component'.(!empty($pitemid) ? '&Itemid='.$pitemid : ''), false)); ?>',
			// form data
			formData,
			// success callback
			function(response) {
				// hide progress wrap
				jQuery('#vbo-paxfield-upload-progress-' + selector_suffix).hide();
				// unset progress
				vboUploadSetProgress(selector_suffix, 0);

				// parse response
				try {
					var obj_res = JSON.parse(response),
						uploaded_urls = [];

					for (var i in obj_res) {
						if (!obj_res.hasOwnProperty(i) || !obj_res[i].hasOwnProperty('url')) {
							continue;
						}
						uploaded_urls.push(obj_res[i]['url']);
					}
					if (!uploaded_urls.length) {
						console.log('no valid URLs returned', response);
						return false;
					}
					// update hidden field
					var hidden_inp = jQuery('#vbo-paxfield-curfiles-' + selector_suffix);
					var current_guest_files = hidden_inp.val().split('|');
					if (!current_guest_files.length || !current_guest_files[0].length) {
						current_guest_files = [];
					}
					// merge current files with new ones uploaded
					var new_guest_files = current_guest_files.concat(uploaded_urls);
					// update hidden input field to contain all files
					hidden_inp.val(new_guest_files.join('|'));
					// display links for the newly uploaded files
					var uploaded_content = '';
					for (var i = 0; i < uploaded_urls.length; i++) {
						var furl_segments = uploaded_urls[i].split('/');
						var guest_fname = furl_segments[(furl_segments.length - 1)];
						var read_fname = guest_fname.substr((guest_fname.indexOf('_') + 1));

						uploaded_content += '<div class="vbo-paxfield-file-uploaded">';
						uploaded_content += '	<span class="vbo-paxfield-file-uploaded-rm"><?php VikBookingIcons::e('times-circle'); ?></span>';
						uploaded_content += '	<a href="' + uploaded_urls[i] + '" target="_blank">';
						uploaded_content += '		<?php VikBookingIcons::e('image'); ?>';
						uploaded_content += '		<span>' + read_fname + '</span>';
						uploaded_content += '	</a>';
						uploaded_content += '</div>';
					}
					// append the new content
					jQuery('#vbo-paxfield-files-' + selector_suffix).append(uploaded_content);

					// display toast message
					vboPresentToast(Joomla.JText._('VBO_PRECHECKIN_TOAST_HELP'), 4000, function() {
						jQuery('html,body').animate({scrollTop: jQuery('.vbo-precheckin-submit').offset().top - 100}, {duration: 400});
					});
				} catch(err) {
					console.error('could not parse JSON response for uploading documents', err, response);
				}
			},
			// failure callback
			function(error) {
				alert(Joomla.JText._('VBO_UPLOAD_FAILED'));
				// hide progress wrap
				jQuery('#vbo-paxfield-upload-progress-' + selector_suffix).hide();
				// unset progress
				vboUploadSetProgress(selector_suffix, 0);

				console.error(error);
			},
			// progress callback
			function(progress) {
				// update progress bar
				vboUploadSetProgress(selector_suffix, progress);
			}
		);
	}

	/**
	 * Declare functions for DOM ready
	 */
	jQuery(function() {

		/**
		 * Datepicker for birth date
		 */
		jQuery('.vbo-paxfield-datepicker').datepicker({
			dateFormat: "<?php echo $juidf; ?>",
			changeMonth: true,
			changeYear: true,
			yearRange: "<?php echo (date('Y') - 100) . ':' . date('Y'); ?>"
		});

		/**
		 * Click event on file-upload button
		 */
		jQuery('.vbo-precheckin-uploadfile').click(function() {
			var elem = jQuery(this);

			var room_index 	= elem.data('roomi'),
				guest_index = elem.data('guesti'),
				pax_index 	= elem.data('paxk');

			// check if device supports file upload
			if (!vboIsUploadSupported()) {
				alert('Your device may not support files uploading');
				return false;
			}

			// update global vars
			vbo_prechin_current_room  = room_index;
			vbo_prechin_current_guest = guest_index;
			vbo_prechin_current_paxk  = pax_index;

			// trigger the click event on the hidden input field for the files upload
			jQuery('#vbo-precheckin-upload-field').trigger('click');
		});

		/**
		 * Change event on global file-upload hidden field
		 */
		jQuery('#vbo-precheckin-upload-field').on('change', function(e) {
			// get files selected
			var files = jQuery(this)[0].files;

			if (!files || !files.length) {
				console.error('no files selected for upload');
				return false;
			}

			// upload selected files
			vboUploadDocuments(files);

			// make the input value empty
			jQuery(this).val(null);
		});

		/**
		 * Click event on the button to remove an uploaded file
		 */
		jQuery(document.body).on('click', '.vbo-paxfield-file-uploaded-rm', function() {
			var file_container = jQuery(this).closest('.vbo-paxfield-file-uploaded');
			if (!file_container.length) {
				return false;
			}
			var file_url = file_container.find('a').attr('href');
			var files_selector = file_container.closest('.vbo-paxfield-files').attr('data-gselector');
			if (confirm(Joomla.JText._('VBO_REMOVEF_CONFIRM'))) {
				var pax_elem = jQuery('#vbo-paxfield-curfiles-' + files_selector);
				var pax_urls = pax_elem.val();
				if (pax_urls.indexOf(file_url + '|') >= 0) {
					pax_urls = pax_urls.replace(file_url + '|', '');
				} else if (pax_urls.indexOf('|' + file_url) >= 0) {
					pax_urls = pax_urls.replace('|' + file_url, '');
				} else {
					pax_urls = pax_urls.replace(file_url, '');
				}
				// update hidden input value
				pax_elem.val(pax_urls);
				// remove the file container
				file_container.remove();
				// display toast message
				vboPresentToast(Joomla.JText._('VBO_PRECHECKIN_TOAST_HELP'), 4000, function() {
					jQuery('html,body').animate({scrollTop: jQuery('.vbo-precheckin-submit').offset().top - 100}, {duration: 400});
				});
			}
		});

		/**
		 * Change event on input fields to trigger the toast message
		 */
		jQuery('.vbo-precheckin-room-wrapper').find('input[type="text"], select').on('change', function() {
			// if toast displayed and clicked, unset listener
			if (vbo_typingtoast_displayed === true) {
				jQuery('.vbo-precheckin-room-wrapper').find('input[type="text"], select').off('change');
			}
			// increase counter
			vbo_typingtoast_changes++;
			if (vbo_typingtoast_changes > 2) {
				// reset counter
				vbo_typingtoast_changes = 0;
				// display toast message
				vboPresentToast(Joomla.JText._('VBO_PRECHECKIN_TOAST_HELP'), 4000, function() {
					jQuery('html,body').animate({scrollTop: jQuery('.vbo-precheckin-submit').offset().top - 100}, {duration: 400});
					// if toast is clicked, we never display it again
					vbo_typingtoast_displayed = true;
				});
			}
		});

	});
</script>
