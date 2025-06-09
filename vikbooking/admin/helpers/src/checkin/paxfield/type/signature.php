<?php
/** 
 * @package     VikBooking
 * @subpackage  core
 * @author      Alessio Gaggii - E4J s.r.l.
 * @copyright   Copyright (C) 2025 E4J s.r.l. All Rights Reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * @link        https://vikwp.com
 */

// No direct access
defined('ABSPATH') or die('No script kiddies please!');

/**
 * Defines the handler for a pax field of type "signature".
 * 
 * @since 	1.17.4 (J) - 1.7.4 (WP)
 */
final class VBOCheckinPaxfieldTypeSignature extends VBOCheckinPaxfieldType
{
	/**
	 * The container of this field should have a precise class.
	 * 
	 * @var 	string
	 */
	protected $container_class_attr = 'vbo-checkinfield-signature-wrap';

	/**
	 * Renders the current pax field HTML.
	 * 
	 * @return 	string 	the HTML string to render the field.
	 */
	public function render()
	{
		if ($this->field->getGuestNumber() > 1) {
			// this field is only for the main guest, and we are parsing the Nth guest
			return '';
		}

		if (VikBooking::isAdmin()) {
			// this field should not be displayed in administrator sections
			return '';
		}

		// register JS lang defs
		JText::script('VBO_PRECHECKIN_SIGN_DOCUMENT');

		// get the booking record involved
		$booking = $this->field->getBooking();

		// get the customer record
		$customer = VikBooking::getCPinInstance()->getCustomerFromBooking($booking['id'] ?? 0);

		if (!$booking || !$customer) {
			// cannot render the field without a booking or customer record
			return '';
		}

		// decode customer pax-data, if available
		$customer['pax_data'] = is_string($customer['pax_data']) && $customer['pax_data'] ? ((array) json_decode($customer['pax_data'], true)) : $customer['pax_data'];

		// get the VBO application
		$vbo_app = VikBooking::getVboApplication();

		// load the VBOCore JS class
		$vbo_app->loadCoreJS([
			'default_loading_body' => '<i class="' . VikBookingIcons::i('circle-notch', 'fa-spin fa-fw') . '"></i>',
		]);

		// load signature pad assets
		$vbo_app->loadSignaturePad();

		// get the field unique ID
		$field_id = $this->getFieldIdAttr();

		// get the guest number
		$guest_number = $this->field->getGuestNumber();

		// get the field class attribute
		$pax_field_class = $this->getFieldClassAttr();
		// push an additional class name for the signature pad
		$all_field_class = explode(' ', $pax_field_class);
		$all_field_class[] = 'vbo-pax-field-signature';
		$pax_field_class = implode(' ', $all_field_class);

		// get field name attribute
		$name = $this->getFieldNameAttr();

		// get the field value attribute
		$value = htmlspecialchars($this->getFieldValueAttr());

		// get the current room index
		$room_index = $this->field->getRoomIndex();

		// get the booking rooms involved
		$booking_rooms = $this->field->getBookingRooms();

		// cut down the rooms involved to just the current one for a more
		// accurate conditional text rules parsing in the check-in document
		$booking_rooms = isset($booking_rooms[$room_index]) ? [$booking_rooms[$room_index]] : $booking_rooms;

		// get and parse check-in document
		list($checkintpl, $pdfparams) = VikBooking::loadCheckinDocTmpl($booking, $booking_rooms, $customer);
		$checkin_body = VikBooking::parseCheckinDocTemplate($checkintpl, $booking, $booking_rooms, $customer);

		// view document label and icon
		$viewdoc_lbl = JText::translate('VBO_PRECHECKIN_VIEW_DOCUMENT');
		$viewdoc_icon = '<i class="' . VikBookingIcons::i('file-contract') . '"></i>';

		// sign document label and icon
		$signdoc_lbl = JText::translate('VBO_PRECHECKIN_SIGN_DOCUMENT');
		$signabove_lbl = JText::translate('VBOSIGNATURESIGNABOVE');
		$signsave_lbl = JText::translate('VBOSIGNATURESAVE');
		$signclear_lbl = JText::translate('VBOSIGNATURECLEAR');
		$signdoc_icon = '<i class="' . VikBookingIcons::i('signature') . '"></i>';
		$clearpad_icon = '<i class="' . VikBookingIcons::i('trash-alt') . '"></i>';
		$savepad_icon = '<i class="' . VikBookingIcons::i('check-circle') . '"></i>';

		// ajax endpoint
		$ajax_endpoint = VikBooking::ajaxUrl('index.php?option=com_vikbooking&task=checkin.saveSignature');

		// booking SID and TS values
		$booking_sid = empty($booking['sid']) && !empty($booking['idorderota']) ? $booking['idorderota'] : $booking['sid'];
		$booking_ts = $booking['ts'];

		// check if a signature exists
		$current_signature = '';
		if ($customer && !empty($customer['signature'])) {
			$current_signature = '<img src="' . VBO_ADMIN_URI . 'resources/idscans/' . $customer['signature'] . '?' . time() . '" />';
		}

		// compose HTML content for the field
		$field_html = <<<HTML
<div class="vbo-pax-field-signature-container">
	<div class="vbo-pax-field-signature-commands">
		<button type="button" class="btn vbo-pref-color-btn-secondary vbo-pax-field-signature-cmd-view" data-id="$field_id">{$viewdoc_icon} {$viewdoc_lbl}</button>
		<button type="button" class="btn vbo-pref-color-btn vbo-pax-field-signature-cmd-sign" data-id="$field_id">{$signdoc_icon} {$signdoc_lbl}</button>
	</div>
	<div class="vbo-pax-field-signature-checkindoc-helper" data-id="$field_id" style="display: none;">
		<div class="vbo-pax-field-signature-checkindoc-wrap" data-id="$field_id">$checkin_body</div>
	</div>
	<div class="vbo-pax-field-signature-pad-helper" data-id="$field_id" style="display: none;">
		<div class="vbo-pax-field-signature-pad-wrap" data-id="$field_id">
			<div class="vbo-signature-container">
				<div class="vbo-signature-pad">
					<div class="vbo-signature-pad-head">&nbsp;</div>
					<div class="vbo-signature-pad-body" data-id="$field_id">
						<canvas data-id="$field_id"></canvas>
					</div>
					<div class="vbo-signature-pad-footer">
						<div class="vbo-signature-signabove">
							<span>{$signdoc_icon} {$signabove_lbl}</span>
						</div>
						<div class="vbo-signature-cmds">
							<div class="vbo-signature-cmd">
								<button type="button" class="btn btn-large vbo-pref-color-btn-secondary vbo-pax-field-signature-cmd-clearpad" data-id="$field_id">{$clearpad_icon} {$signclear_lbl}</button>
							</div>
							<div class="vbo-signature-cmd">
								<button type="button" class="btn btn-large vbo-pref-color-btn vbo-pax-field-signature-cmd-savesign" data-id="$field_id">{$savepad_icon} {$signsave_lbl}</button>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="vbo-signature-currentimg" data-id="$field_id">{$current_signature}</div>
	</div>
</div>
HTML;

		// append necessary JS script tag to render the signature pad
		$field_html .= <<<HTML
<script>
jQuery(function() {

	// define canvas
	const canvas = document.querySelector('canvas[data-id="{$field_id}"]');

	// define signature pad
	const signaturePad = new SignaturePad(canvas, {
		backgroundColor: 'rgba(0, 0, 0, 0)',
	});

	// define canvas size calculation
	const calculateCanvasSize = () => {
		let ratio = Math.max(window.devicePixelRatio || 1, 1);
		canvas.width = canvas.offsetWidth * ratio;
		canvas.height = canvas.offsetHeight * ratio;
		canvas.getContext("2d").scale(ratio, ratio);
		signaturePad.clear();
	};

	jQuery('button.vbo-pax-field-signature-cmd-view[data-id="{$field_id}"]').on('click', function() {
		// view check-in document
		let modal_body = VBOCore.displayModal({
			suffix: 'vbo-pax-field-signature-checkindoc',
			extra_class: 'vbo-modal-large',
			title: Joomla.JText._('VBO_PRECHECKIN_SIGN_DOCUMENT'),
			lock_scroll: true,
			onDismiss: () => {
				jQuery('.vbo-pax-field-signature-checkindoc-wrap[data-id="{$field_id}"]').appendTo(
					jQuery('.vbo-pax-field-signature-checkindoc-helper[data-id="{$field_id}"]')
				);
			},
		});

		jQuery('.vbo-pax-field-signature-checkindoc-wrap[data-id="{$field_id}"]').appendTo(modal_body);
	});

	jQuery('button.vbo-pax-field-signature-cmd-sign[data-id="{$field_id}"]').on('click', function() {
		// sign check-in document
		let modal_body = VBOCore.displayModal({
			suffix: 'vbo-pax-field-signature-padcanvas',
			extra_class: 'vbo-modal-large',
			title: Joomla.JText._('VBO_PRECHECKIN_SIGN_DOCUMENT'),
			lock_scroll: true,
			loading_event: 'vbo-pax-field-signature-padcanvas-loading',
			dismiss_event: 'vbo-pax-field-signature-padcanvas-dismiss',
			onDismiss: () => {
				jQuery('.vbo-pax-field-signature-pad-wrap[data-id="{$field_id}"]').appendTo(
					jQuery('.vbo-pax-field-signature-pad-helper[data-id="{$field_id}"]')
				);
			},
		});

		if (jQuery('.vbo-signature-currentimg[data-id="{$field_id}"]').find('img').length) {
			// a signature exists, hide canvas and display the current signature
			jQuery('.vbo-signature-pad-body[data-id="{$field_id}"]').find('img').remove();
			jQuery('canvas[data-id="{$field_id}"]').hide();
			jQuery('.vbo-signature-currentimg[data-id="{$field_id}"]').find('img').appendTo(
				jQuery('.vbo-signature-pad-body[data-id="{$field_id}"]')
			);
			// set modal body
			jQuery('.vbo-pax-field-signature-pad-wrap[data-id="{$field_id}"]').appendTo(modal_body);
		} else {
			// show canvas for a new signature
			jQuery('.vbo-pax-field-signature-pad-wrap[data-id="{$field_id}"]').appendTo(modal_body);

			setTimeout(() => {
				// adjust canvas size on modal
				calculateCanvasSize();
			}, 0);
		}
	});

	// register canvas size calculation on window resize event
	window.addEventListener('resize', calculateCanvasSize);

	// run canvas size calculation
	calculateCanvasSize();

	// clear pad
	jQuery('button.vbo-pax-field-signature-cmd-clearpad[data-id="{$field_id}"]').on('click', function() {
		// check if we need to present the canvas
		if (jQuery('.vbo-signature-pad-body[data-id="{$field_id}"]').find('img').length) {
			// move back the current signature to its position
			jQuery('.vbo-signature-pad-body[data-id="{$field_id}"]').find('img').appendTo(
				jQuery('.vbo-signature-currentimg[data-id="{$field_id}"]')
			);

			// show canvas
			jQuery('canvas[data-id="{$field_id}"]').show();

			setTimeout(() => {
				// adjust canvas size on modal
				calculateCanvasSize();
			}, 0);
		} else {
			// clear the current signature pad
			signaturePad.clear();
		}
	});

	// save signature
	jQuery('button.vbo-pax-field-signature-cmd-savesign[data-id="{$field_id}"]').on('click', function() {
		if (signaturePad.isEmpty()) {
			alert('Signature is empty');
			return false;
		}

		let ratio = Math.max(window.devicePixelRatio || 1, 1);
		let pad_width = canvas.width;
		let dataURL = signaturePad.toDataURL();

		// show loading
		VBOCore.emitEvent('vbo-pax-field-signature-padcanvas-loading');

		VBOCore.doAjax(
			"$ajax_endpoint",
			{
				sid: '{$booking_sid}',
				ts: '{$booking_ts}',
				pad_ratio: ratio,
				pad_width: pad_width,
				signature: dataURL,
			},
			(res) => {
				// hide modal
				VBOCore.emitEvent('vbo-pax-field-signature-padcanvas-dismiss');

				// add image to DOM
				let img = document.createElement('img');
				img.setAttribute('src', res.signatureFileUri);

				let target = document.querySelector('.vbo-signature-currentimg[data-id="{$field_id}"]');
				target.innerHTML = '';
				target.appendChild(img);

				// clear the current signature pad
				signaturePad.clear();
			},
			(err) => {
				// stop loading
				VBOCore.emitEvent('vbo-pax-field-signature-padcanvas-loading');
				// display error
				alert(err.responseText || 'An error occurred');
			}
		);
	});

});
</script>
HTML;

		// return the necessary HTML string to display the field
		return $field_html;
	}

	/**
	 * @inheritDoc
	 */
	public function validateGuestRegistrationData()
	{
		if ($this->field->getGuestNumber() != 1 || VikBooking::isAdmin()) {
			// no validation needed
			return;
		}

		// get the booking record involved
		$booking = $this->field->getBooking();

		// get the customer record
		$customer = VikBooking::getCPinInstance()->getCustomerFromBooking($booking['id'] ?? 0);

		if (!$booking || !$customer) {
			// do not raise an error when data is missing
			return;
		}

		if (empty($customer['signature'])) {
			// the contract was not signed, raise an error
			throw new Exception(JText::translate('VBO_PRECHECKIN_SIGN_DOCUMENT'), 400);
		}

		// all good
		return;
	}
}
