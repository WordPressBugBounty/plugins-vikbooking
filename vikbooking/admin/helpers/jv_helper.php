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

/**
 * Extends native application functions.
 *
 * @wponly  the class extends VikApplication and uses different vars.
 * @since   1.0
 * @see     VikApplication
 */
class VboApplication extends VikApplication
{
	/**
	 * Additional commands container for any methods.
	 *
	 * @var array
	 */
	private $commands;

	/**
	 * This method loads an additional CSS file (if available)
	 * for the current CMS, and CMS version.
	 *
	 * @return void
	 **/
	public function normalizeBackendStyles()
	{
		$document = JFactory::getDocument();

		if (is_file(VBO_ADMIN_PATH . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'wp.css')) {
			$document->addStyleSheet(VBO_ADMIN_URI . 'helpers/' . 'wp.css', ['version' => VIKBOOKING_SOFTWARE_VERSION], ['id' => 'vbo-wp-style']);
		}
	}

	/**
	 * Includes a script URI.
	 *
	 * @param   string  $uri  The script URI.
	 *
	 * @return  void
	 */
	public function addScript($uri)
	{
		JHtml::fetch('script', $uri);
	}

	/**
	* Sets additional commands for any methods. Like raise an error if the recipient email address is empty.
	* Returns this object for chainability.
	*/
	public function setCommand($key, $value)
	{
		if (!empty($key)) {
			$this->commands[$key] = $value;
		}
		return $this;
	}
	
	public function sendMail($from_address, $from_name, $to, $reply_address, $subject, $hmess, $is_html = true, $encoding = 'base64', $attachment = null)
	{
		if (!is_array($to) && strpos($to, ',') !== false) {
			$all_recipients = explode(',', $to);
			foreach ($all_recipients as $k => $v) {
				if (empty($v)) {
					unset($all_recipients[$k]);
				}
			}
			if (count($all_recipients) > 0) {
				$to = $all_recipients;
			}
		}

		if (empty($to)) {
			//Prevent Joomla Exceptions that would stop the script execution
			if (isset($this->commands['print_errors'])) {
				VikError::raiseWarning('', 'The recipient email address is empty. Email message could not be sent. Please check your configuration.');
			}
			return false;
		}
		
		if ($from_name == $from_address) {
			$mainframe = JFactory::getApplication();
			$attempt_fromn = $mainframe->get('fromname', '');
			if (!empty($attempt_fromn)) {
				$from_name = $attempt_fromn;
			}
		}

		/**
		 * Conditional text rules may set extra recipients or attachments.
		 * 
		 * @since 	1.16.0 (J) - 1.6.0 (WP)
		 */
		$extra_admin_recipients = VikBooking::addAdminEmailRecipient(null);
		$bcc_addresses 			= VikBooking::addAdminEmailRecipient(null, $bcc = true);
		$extra_attachments 		= VikBooking::addEmailAttachment(null);
		if ($extra_admin_recipients) {
			// cast a possible string to array
			$to = (array) $to;
			// merge additional recipients
			$to = array_merge($to, $extra_admin_recipients);
		}
		if ($extra_attachments) {
			$attachment = $attachment ? (array) $attachment : [];
			$attachment = array_merge($attachment, $extra_attachments);
		}

		/**
		 * We let the internal library process the email sending depending on the platform.
		 * This will allow us to perform the required manipulation of the content, if needed.
		 * 
		 * @since   1.15.2 (J) - 1.5.5 (WP)
		 */
		$mail_data = new VBOMailWrapper([
			'sender'      => [$from_address, $from_name],
			'recipient'   => $to,
			'bcc'         => $bcc_addresses,
			'reply'       => $reply_address,
			'subject'     => $subject,
			'content'     => $hmess,
			'attachments' => $attachment,
		]);

		// unset queues for the next email sending operation
		VikBooking::addAdminEmailRecipient(null, false, $reset = true);
		VikBooking::addEmailAttachment(null, $reset = true);

		// dispatch the email sending command
		return VBOFactory::getPlatform()->getMailer()->send($mail_data);
	}

	/**
	* @param $arr_values array
	* @param $current_key string
	* @param $empty_value string (J3.x only)
	* @param $default
	* @param $input_name string
	* @param $record_id = '' string
	*/
	public function getDropDown($arr_values, $current_key, $empty_value, $default, $input_name, $record_id = '')
	{
		$dropdown = '';
		$dropdown .= '<select name="'.$input_name.'" onchange="document.adminForm.submit();">'."\n";
		$dropdown .= '<option value="">'.$default.'</option>'."\n";
		$list = "\n";
		foreach ($arr_values as $k => $v) {
			$dropdown .= '<option value="'.$k.'"'.($k == $current_key ? ' selected="selected"' : '').'>'.$v.'</option>'."\n";
		}
		$dropdown .= '</select>'."\n";

		return $dropdown;
	}

	public function loadSelect2()
	{
		static $st_loaded = null;

		if ($st_loaded) {
			// loaded flag
			return;
		}

		// cache loaded flag
		$st_loaded = 1;

		// load JS + CSS
		$document = JFactory::getDocument();
		$document->addStyleSheet(VBO_ADMIN_URI.'resources/select2.min.css');
		$this->addScript(VBO_ADMIN_URI.'resources/select2.min.js');
	}

	/**
	 * Returns the HTML code to render a regular dropdown
	 * menu styled through the jQuery plugin Select2.
	 *
	 * @param   $arr_values     array
	 * @param   $current_key    string
	 * @param   $input_name     string
	 * @param   $placeholder    string      used when the select has no selected option (it's empty)
	 * @param   $empty_name     [string]    the name of the option to set an empty value to the field (<option>$empty_name</option>)
	 * @param   $empty_val      [string]    the value of the option to set an empty value to the field (<option>$empty_val</option>)
	 * @param   $onchange       [string]    javascript code for the onchange attribute
	 * @param   $idattr         [string]    the identifier attribute of the select
	 *
	 * @return  string
	 */
	public function getNiceSelect($arr_values, $current_key, $input_name, $placeholder, $empty_name = '', $empty_val = '', $onchange = 'document.adminForm.submit();', $idattr = '')
	{
		//load JS + CSS
		$this->loadSelect2();

		//attribute
		$idattr = empty($idattr) ? rand(1, 999) : $idattr;

		//select
		$dropdown = '<select id="'.$idattr.'" name="'.$input_name.'"'.(!empty($onchange) ? ' onchange="'.$onchange.'"' : '').'>'."\n";
		if (!empty($placeholder) && empty($current_key)) {
			//in order for the placeholder value to appear, there must be a blank <option> as the first option in the select
			$dropdown .= '<option></option>'."\n";
		} else {
			//unset the placeholder to not pass it to the select2 object, or the empty value will not be displayed
			$placeholder = '';
		}
		if (strlen($empty_name) || strlen($empty_val)) {
			$dropdown .= '<option value="'.$empty_val.'">'.$empty_name.'</option>'."\n";
		}
		foreach ($arr_values as $k => $v) {
			$dropdown .= '<option value="'.$k.'"'.($k == $current_key ? ' selected="selected"' : '').'>'.$v.'</option>'."\n";
		}
		$dropdown .= '</select>'."\n";

		//js code
		$dropdown .= '<script type="text/javascript">'."\n";
		$dropdown .= 'jQuery(function() {'."\n";
		$dropdown .= '  jQuery("#'.$idattr.'").select2('.(!empty($placeholder) ? '{placeholder: "'.addslashes($placeholder).'"}' : '').');'."\n";
		$dropdown .= '});'."\n";
		$dropdown .= '</script>'."\n";

		return $dropdown;
	}

	/**
	 * Adds the script declaration to render the Bootstrap JModal window.
	 * The suffix can be passed to generate other JS functions.
	 * Optionally pass JavaScript code for the 'show' and 'hide' events.
	 * For compatibility with the Joomla framework, this method should be
	 * echoed although it does not return anything on WordPress.
	 *
	 * @param   $suffix     string
	 * @param   $hide_js    string
	 * @param   $show_js    string
	 *
	 * @return  void 		should still be echoed for compatibility with J.
	 */
	public function getJmodalScript($suffix = '', $hide_js = '', $show_js = '')
	{
		static $loaded = [];

		$doc = JFactory::getDocument();

		if (!isset($loaded[$suffix]))
		{
			$doc->addScriptDeclaration(
<<<JS
function vboOpenJModal$suffix(id, modal_url, new_title) {

	var on_hide = null;

	if ("$hide_js") {
		on_hide = function() {
			$hide_js
		}
	}

	var on_show = null;

	if ("$show_js") {
		on_show = function() {
			$show_js
		}
	}
	
	wpOpenJModal(id, modal_url, on_show, on_hide);

	if (new_title) {
		jQuery('#jmodal-' + id + ' .modal-header h3').text(new_title);
	}

	return false;
}
JS
			);

			$loaded[$suffix] = 1;
		}
	}

	/**
	 * Returns a safe sub-string string with the requested length, by
	 * avoiding errors for those errors not supporting multi-byte strings.
	 * 
	 * @param   string  $text   the text to apply the substr onto.
	 * @param   string  $len    the length of the sub-string to take.
	 * 
	 * @return  string          the portion of the string.
	 * 
	 * @since   1.15.0 (J) - 1.5.0 (WP)
	 */
	public function safeSubstr($text, $len = 3)
	{
		$mb_supported = function_exists('mb_substr');

		if ($len < 1) {
			return $text;
		}
		
		return $mb_supported ? mb_substr($text, 0, $len, 'UTF-8') : substr($text, 0, $len);
	}

	/**
	 * Renders a date-time locale input element to pick a date and time.
	 * 
	 * @param 	array 	$options 	Associative list of element options.
	 * 
	 * @return 	string 				The HTML string necessary to render the date-time picker.
	 * 
	 * @since 	1.18.0 (J) - 1.8.0 (WP)
	 */
	public function renderDateTimePicker(array $options = [])
	{
		if (!($options['id'] ?? null)) {
			// the ID attribute is mandatory
			$options['id'] = uniqid('dtp_');
		}

		// ensure attributes are set
		if (!($options['attributes'] ?? [])) {
			$options['attributes'] = [];
		}

		// attributes name, value, min and max can also be specified outside the "attributes" key
		if (($options['name'] ?? null) && !($options['attributes']['name'] ?? null)) {
			// resort the attribute inside the apposite key
			$options['attributes']['name'] = $options['name'];
		}
		if (($options['value'] ?? null) && !($options['attributes']['value'] ?? null)) {
			// resort the attribute inside the apposite key
			$options['attributes']['value'] = $options['value'];
		}
		if (($options['min'] ?? null) && !($options['attributes']['min'] ?? null)) {
			// resort the attribute inside the apposite key
			$options['attributes']['min'] = $options['min'];
		}
		if (($options['max'] ?? null) && !($options['attributes']['max'] ?? null)) {
			// resort the attribute inside the apposite key
			$options['attributes']['max'] = $options['max'];
		}

		// check for "min" attribute, required to hide seconds from the time-picker
		if (!($options['attributes']['min'] ?? null)) {
			// default to 10 years in the past
			$options['attributes']['min'] = JFactory::getDate('-10 years')->format('Y-m-d\TH:i');
		}

		// build attributes list
		$attributes = array_merge([
			'id' => $options['id'],
		], $options['attributes']);

		// build attributes string
		$attr_str = implode(' ', array_map(function($name, $value) {
			return $name . '="' . htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8') . '"'; 
		}, array_keys($attributes), array_values($attributes)));

		// build HTML string
		$html = <<<HTML
<input type="datetime-local" {$attr_str} />
HTML;

		// return the HTML string to be displayed
		return $html;
	}

	/**
	 * Renders a select2 component to display existing tags or to add new ones.
	 * 
	 * @param 	array 	$options 	Associative list of dropdown options.
	 * @param 	array 	$elements 	Associative list of element records.
	 * @param 	array 	$groups 	Optional list of element groups to source.
	 * 
	 * @return 	string 				The HTML string necessary to render the dropdown.
	 * 
	 * @since 	1.18.0 (J) - 1.8.0 (WP)
	 */
	public function renderTagsDropDown(array $options = [], array $elements = [], array $groups = [])
	{
		// load select2 assets
		$this->loadSelect2();

		if (!($options['id'] ?? null)) {
			// the ID attribute is mandatory
			$options['id'] = uniqid('tdd_');
		}

		// build attributes list
		$attributes = array_merge([
			'id' => $options['id'],
		], ($options['attributes'] ?? []));

		// build attributes string
		$attr_str = implode(' ', array_map(function($name, $value) {
			return $name . '="' . htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8') . '"'; 
		}, array_keys($attributes), array_values($attributes)));

		// build data sources
		$data_sources  = [];

		foreach ($elements as $element) {
			if (is_object($element)) {
				$element = (array) $element;
			}

			if (empty($element['id'])) {
				continue;
			}

			// build element data source
			$data_source = [
				'id'    => $element['id'],
				'text'  => $element['name'] ?? $element['id'],
				'color' => $element['color'] ?? null,
				'hex'   => $element['hex'] ?? null,
			];

			// check for option selected status
			if (($options['selected_value'] ?? null) && $options['selected_value'] == $element['id']) {
				$data_source['selected'] = true;
			} elseif (is_array($options['selected_values'] ?? null) && in_array($element['id'], $options['selected_values'])) {
				$data_source['selected'] = true;
			}

			// check for option disabled status
			if (($options['disabled_value'] ?? null) && $options['disabled_value'] == $element['id']) {
				$data_source['disabled'] = true;
			} elseif (is_array($options['disabled_values'] ?? null) && in_array($element['id'], $options['disabled_values'])) {
				$data_source['disabled'] = true;
			}

			// push element data source
			$data_sources[] = $data_source;
		}

		// append groups to source as data elements
		foreach ($groups as $group) {
			if (is_object($group)) {
				// always cast to array
				$group = (array) $group;
			}

			if (!is_array($group) || empty($group['text']) || empty($group['elements'])) {
				continue;
			}

			// filter out invalid group elements
			$group['elements'] = array_filter((array) $group['elements'], function($group_element) {
				return is_array($group_element) && isset($group_element['id']) && isset($group_element['text']);
			});

			// check for option selected status
			if (($options['selected_value'] ?? null) || (is_array($options['selected_values'] ?? null) && $options['selected_values'])) {
				foreach ($group['elements'] as $k => $element) {
					if (($options['selected_value'] ?? null)) {
						if ($options['selected_value'] == $element['id']) {
							$group['elements'][$k]['selected'] = true;
						}
					} else {
						if (in_array($element['id'], $options['selected_values'])) {
							$group['elements'][$k]['selected'] = true;
						}
					}
				}
			}

			// check for option disabled status
			if (($options['disabled_value'] ?? null) || (is_array($options['disabled_values'] ?? null) && $options['disabled_values'])) {
				foreach ($group['elements'] as $k => $element) {
					if (($options['disabled_value'] ?? null)) {
						if ($options['disabled_value'] == $element['id']) {
							$group['elements'][$k]['disabled'] = true;
						}
					} else {
						if (in_array($element['id'], $options['disabled_values'])) {
							$group['elements'][$k]['disabled'] = true;
						}
					}
				}
			}

			// push group element data source
			$data_sources[] = [
				'text' => $group['text'],
				'children' => $group['elements'],
			];
		}

		// data sources JSON encoded string
		$data_sources_str = json_encode($data_sources);

		// empty option tag
		$empty_option = '';
		if (!($options['attributes']['multiple'] ?? null)) {
			$empty_option = '<option></option>';
		}

		// clearing allowed
		$clearable = (bool) ($options['allow_clear'] ?? 1);
		$clearable_str = $clearable ? 'true' : 'false';

		// tags allowed (for entering custom values)
		$taggable = (bool) ($options['allow_tags'] ?? 1);
		$taggable_str = $taggable ? 'true' : 'false';

		// placeholder text
		$placeholder = json_encode($options['placeholder'] ?? '');

		// select2 width
		$sel2width = $options['width'] ?? 'resolve';

		// supported tag colors
		$colors = json_encode((array) ($options['colors'] ?? []));

		// build HTML string
		$html = <<<HTML
<select {$attr_str}>{$empty_option}</select>
HTML;

		// build script declaration
		$js_decl = <<<JAVASCRIPT
jQuery(function() {
	const supportedColors = $colors;
	let remainingColors = supportedColors.slice();

	if (remainingColors.length) {
		// internally rearrange tags by ID to preserve the linear color scheme supported by default
		const existingTags = {$data_sources_str}.sort((a, b) => parseInt(a.id) - parseInt(b.id));

		// iterate all the existing tags
		existingTags.forEach((tag) => {
			let index = remainingColors.indexOf(tag.color);
			if (index != -1) {
				// remove the tag color from the remaining ones
				remainingColors.splice(index, 1);

				if (remainingColors.length == 0) {
					// no more remaining colors, reset array
					remainingColors = supportedColors.slice();
				}
			}
		});
	}

	jQuery('select#{$options['id']}').select2({
		width: '$sel2width',
		allowClear: $clearable_str,
		data: $data_sources_str,
		placeholder: $placeholder,
		tags: $taggable_str,
		createTag: function (params) {
			const term = (params.term || '').replace(/:/g, '').trim();

			if (term === '') {
				return null;
			}

			// temporarily assign the first available color
			const color = remainingColors[0];

			return {
				id: term + ':' + color,
				text: term,
				color: color,
				newTag: true,
			};
		},
		templateResult: (element) => {
			if (!element.id) {
				return element.text;
			}

			let tag_class = '';
			let tag_style = '';
			if (element?.color) {
				tag_class = element.color;
			} else if (element?.hex) {
				tag_style = 'background-color: ' + element.hex + ';';
			} else {
				tag_class = (element.id + '').toLowerCase().replace(/[^a-z0-9]/ig, '');
			}
			return jQuery('<span class="vbo-sel2-selectable-tag"><span class="vbo-sel2-selectable-tag-color vbo-colortag-circle' + (tag_class ? ' ' + tag_class : '') + '"' + (tag_style ? ' style="' + tag_style + '"' : '') + '></span><span class="vbo-sel2-selectable-tag-name">' + element.text + '</span></span>');
		},
		templateSelection: (element) => {
			if (!element.id) {
				return element.text;
			}

			let tag_elem = jQuery('<span></span>')
				.addClass('vbo-sel2-selected-tag')
				.text(element.text);

			if (element.newTag) {
				// we can understand here whether a new tag has been officially submitted
				element.newTag = false;

				// permanently detach the last color assigned
				remainingColors.shift();

				if (remainingColors.length == 0) {
					// no more remaining colors, reset array
					remainingColors = supportedColors.slice();
				}
			}

			if (element?.color) {
				tag_elem.addClass(element.color);
			} else if (element?.hex) {
				tag_elem.css('background-color', element.hex);
			} else {
				tag_elem.addClass((element.id + '').toLowerCase().replace(/[^a-z0-9]/ig, ''));
			}

			return tag_elem;
		},
	});
});
JAVASCRIPT;

		if ((VBOPlatformDetection::isWordPress() && wp_doing_ajax()) || (!VBOPlatformDetection::isWordPress() && !strcasecmp((string) JFactory::getApplication()->input->server->get('HTTP_X_REQUESTED_WITH', ''), 'xmlhttprequest'))) {
			// concatenate script to HTML string when doing an AJAX request
			$html .= "\n" . '<script>' . $js_decl . '</script>';
		} else {
			// add script declaration to document
			JFactory::getDocument()->addScriptDeclaration($js_decl);
		}

		// return the HTML string to be displayed
		return $html;
	}

	/**
	 * Renders a select2 component to display elements with thumbnails.
	 * 
	 * @param 	array 	$options 	Associative list of dropdown options.
	 * @param 	array 	$elements 	Associative list of element records.
	 * @param 	array 	$groups 	Optional list of element groups to source.
	 * 
	 * @return 	string 				The HTML string necessary to render the dropdown.
	 * 
	 * @since 	1.17.5 (J) - 1.7.5 (WP)
	 */
	public function renderElementsDropDown(array $options = [], array $elements = [], array $groups = [])
	{
		if (!$elements && ($options['elements'] ?? '') == 'listings') {
			// load listing records
			$filter_listing_ids = (array) ($options['element_ids'] ?? []);
			$elements = VikBooking::getAvailabilityInstance(true)->loadRooms($filter_listing_ids, 0, true);
		}

		if (!$elements) {
			// abort
			return '';
		}

		// load select2 assets
		$this->loadSelect2();

		if (!($options['id'] ?? null)) {
			// the ID attribute is mandatory
			$options['id'] = uniqid('ldd_');
		}

		// build attributes list
		$attributes = array_merge([
			'id' => $options['id'],
		], ($options['attributes'] ?? []));

		// build attributes string
		$attr_str = implode(' ', array_map(function($name, $value) {
			return $name . '="' . htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8') . '"'; 
		}, array_keys($attributes), array_values($attributes)));

		// build data sources
		$data_sources  = [];
		$base_img_path = implode(DIRECTORY_SEPARATOR, [VBO_SITE_PATH, 'resources', 'uploads']) . DIRECTORY_SEPARATOR;
		$base_img_uri  = VBO_SITE_URI . 'resources/uploads/';

		foreach ($elements as $element) {
			if (is_object($element)) {
				$element = (array) $element;
			}

			if (empty($element['id'])) {
				continue;
			}

			// element image
			$element_img_uri = null;

			if (($options['elements'] ?? '') == 'listings') {
				// check for listing mini-thumbnail
				if (!empty($element['img']) && is_file($base_img_path . 'mini_' . $element['img'])) {
					$element_img_uri = $base_img_uri . 'mini_' . $element['img'];
				}
			}

			if (!$element_img_uri && ($options['element_def_img_uri'] ?? '')) {
				// use the provided default element image
				$element_img_uri = $options['element_def_img_uri'];
			} elseif (!$element_img_uri && ($element['img_uri'] ?? '')) {
				// use the provided element image URI
				$element_img_uri = $element['img_uri'];
			}

			// build element data source
			$data_source = [
				'id'   => $element['id'],
				'text' => $element['name'] ?? $element['id'],
				'img'  => $element_img_uri,
			];

			// check for option selected status
			if (($options['selected_value'] ?? null) && $options['selected_value'] == $element['id']) {
				$data_source['selected'] = true;
			} elseif (is_array($options['selected_values'] ?? null) && in_array($element['id'], $options['selected_values'])) {
				$data_source['selected'] = true;
			}

			// check for option disabled status
			if (($options['disabled_value'] ?? null) && $options['disabled_value'] == $element['id']) {
				$data_source['disabled'] = true;
			} elseif (is_array($options['disabled_values'] ?? null) && in_array($element['id'], $options['disabled_values'])) {
				$data_source['disabled'] = true;
			}

			// push element data source
			$data_sources[] = $data_source;
		}

		// check for listing category groups
		if (($options['elements'] ?? '') == 'listings' && ($options['load_categories'] ?? null)) {
			// load listing categories
			$categories = VikBooking::getAvailabilityInstance(true)->loadRoomCategories();

			if (count($categories) > 1) {
				// turn the category IDs into negative
				$categories = array_map(function($cat) {
					return [
						'id'   => ($cat['id'] - ($cat['id'] * 2)),
						'text' => $cat['name'],
					];
				}, $categories);

				// active or disabled listing categories
				foreach ($categories as &$category) {
					// check for option selected status
					if (($options['selected_value'] ?? null) && $options['selected_value'] == $category['id']) {
						$category['selected'] = true;
					} elseif (is_array($options['selected_values'] ?? null) && in_array($category['id'], $options['selected_values'])) {
						$category['selected'] = true;
					}

					// check for option disabled status
					if (($options['disabled_value'] ?? null) && $options['disabled_value'] == $category['id']) {
						$category['disabled'] = true;
					} elseif (is_array($options['disabled_values'] ?? null) && in_array($category['id'], $options['disabled_values'])) {
						$category['disabled'] = true;
					}
				}
				unset($category);

				// push listing category groups
				$groups[] = [
					'text' => $options['categories_lbl'] ?? 'Filter by category',
					'elements' => $categories,
				];
			}
		}

		// append groups to source as data elements
		foreach ($groups as $group) {
			if (is_object($group)) {
				// always cast to array
				$group = (array) $group;
			}

			if (!is_array($group) || empty($group['text']) || empty($group['elements'])) {
				continue;
			}

			// filter out invalid group elements
			$group['elements'] = array_filter((array) $group['elements'], function($group_element) {
				return is_array($group_element) && isset($group_element['id']) && isset($group_element['text']);
			});

			// check for option selected status
			if (($options['selected_value'] ?? null) || (is_array($options['selected_values'] ?? null) && $options['selected_values'])) {
				foreach ($group['elements'] as $k => $element) {
					if (($options['selected_value'] ?? null)) {
						if ($options['selected_value'] == $element['id']) {
							$group['elements'][$k]['selected'] = true;
						}
					} else {
						if (in_array($element['id'], $options['selected_values'])) {
							$group['elements'][$k]['selected'] = true;
						}
					}
				}
			}

			// check for option disabled status
			if (($options['disabled_value'] ?? null) || (is_array($options['disabled_values'] ?? null) && $options['disabled_values'])) {
				foreach ($group['elements'] as $k => $element) {
					if (($options['disabled_value'] ?? null)) {
						if ($options['disabled_value'] == $element['id']) {
							$group['elements'][$k]['disabled'] = true;
						}
					} else {
						if (in_array($element['id'], $options['disabled_values'])) {
							$group['elements'][$k]['disabled'] = true;
						}
					}
				}
			}

			// push group element data source
			$data_sources[] = [
				'text' => $group['text'],
				'children' => $group['elements'],
			];
		}

		// data sources JSON encoded string
		$data_sources_str = json_encode($data_sources);

		// empty option tag
		$empty_option = '';
		if (!($options['attributes']['multiple'] ?? null)) {
			$empty_option = '<option></option>';
		}

		// clearing allowed
		$clearable = (bool) ($options['allow_clear'] ?? 1);
		$clearable_str = $clearable ? 'true' : 'false';

		// placeholder text
		$placeholder = json_encode($options['placeholder'] ?? '');

		// select2 width
		$sel2width = $options['width'] ?? 'resolve';

		// build HTML string
		$html = <<<HTML
<select {$attr_str}>{$empty_option}</select>
HTML;

		// template selection function
		$selectionFn = '';
		if ($options['style_selection'] ?? null) {
			$defaultSelectionIcn = $options['default_selection_icon'] ?? '';
			$selectionFn = <<<JAVASCRIPT
templateSelection: (element) => {
	if (!element.id) {
		return element.text;
	}
	let sel_elem = jQuery('<span></span>')
		.addClass('vbo-sel2-selected-tag')
		.text(element.text);
	if (element.img) {
		let avatar_elem = jQuery('<img/>')
			.addClass('vbo-sel2-selected-tag-avatar')
			.attr('src', element.img);
		sel_elem.prepend(avatar_elem);
	} else if ('$defaultSelectionIcn') {
		let icn_elem = jQuery('<i></i>')
			.addClass('$defaultSelectionIcn')
			.addClass('vbo-sel2-selected-tag-avatar');
		sel_elem.prepend(icn_elem);
	}
	return sel_elem;
}
JAVASCRIPT;
		}

		// build script declaration
		$js_decl = <<<JAVASCRIPT
jQuery(function() {
	jQuery('select#{$options['id']}').select2({
		width: '$sel2width',
		allowClear: $clearable_str,
		data: $data_sources_str,
		placeholder: $placeholder,
		templateResult: (element) => {
			if (!element.img) {
				return element.text;
			}
			return jQuery('<span class="vbo-sel2-element-img"><img src="' + element.img + '" /> <span>' + element.text + '</span></span>');
		},
		$selectionFn
	});
});
JAVASCRIPT;

		if ((VBOPlatformDetection::isWordPress() && wp_doing_ajax()) || (!VBOPlatformDetection::isWordPress() && !strcasecmp((string) JFactory::getApplication()->input->server->get('HTTP_X_REQUESTED_WITH', ''), 'xmlhttprequest'))) {
			// concatenate script to HTML string when doing an AJAX request
			$html .= "\n" . '<script>' . $js_decl . '</script>';
		} else {
			// add script declaration to document
			JFactory::getDocument()->addScriptDeclaration($js_decl);
		}

		// return the HTML string to be displayed
		return $html;
	}

	/**
	 * Renders a select2 component to display searchable elements with thumbnails.
	 * 
	 * @param 	array 	$options 	Associative list of dropdown and search options.
	 * 
	 * @return 	string 				The HTML string necessary to render the dropdown.
	 * 
	 * @since 	1.18.0 (J) - 1.8.0 (WP)
	 */
	public function renderSearchElementsDropDown(array $options = [])
	{
		if (!($options['endpoint'] ?? null) && ($options['elements'] ?? '') == 'bookings') {
			// search bookings endpoint
			$options['endpoint'] = VikBooking::ajaxUrl('index.php?option=com_vikbooking&task=bookings.bookings_search');
		} elseif (!($options['endpoint'] ?? null) && ($options['elements'] ?? '') == 'customers') {
			// search customers endpoint
			$options['endpoint'] = VikBooking::ajaxUrl('index.php?option=com_vikbooking&task=bookings.customer_elements_search');
		}

		if (empty($options['endpoint'])) {
			// abort
			return '';
		}

		// set AJAX endpoint for searching
		$endpoint = $options['endpoint'];

		if ($options['load_assets'] ?? true) {
			// load select2 assets
			$this->loadSelect2();
		}

		if (!($options['id'] ?? null)) {
			// the ID attribute is mandatory
			$options['id'] = uniqid('ldd_');
		}

		// build attributes list
		$attributes = array_merge([
			'id' => $options['id'],
		], ($options['attributes'] ?? []));

		// build attributes string
		$attr_str = implode(' ', array_map(function($name, $value) {
			return $name . '="' . htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8') . '"'; 
		}, array_keys($attributes), array_values($attributes)));

		// build data sources
		$data_sources  = [];

		// check for default option(s) selected status
		$selected_options = [];
		if (is_array($options['selected_values'] ?? null) && $options['selected_values']) {
			$selected_options = $options['selected_values'];
		} elseif (($options['selected_value'] ?? null)) {
			$selected_options[] = $options['selected_value'];
		}
		foreach ($selected_options as $sel_option) {
			if (is_array($sel_option) || is_object($sel_option)) {
				// element object expected
				$sel_option = (array) $sel_option;
				if (isset($sel_option['id']) && isset($sel_option['text'])) {
					// push valid data source
					$sel_option['selected'] = true;
					$data_sources[] = $sel_option;
				}
			} else {
				// selected ID expected, push a data source object with limited information
				$data_sources[] = [
					'id' => $sel_option,
					'text' => $sel_option,
					'selected' => true,
				];
			}
		}

		// data sources JSON encoded string
		$data_sources_str = json_encode($data_sources);

		// empty option tag
		$empty_option = '';
		if (!($options['attributes']['multiple'] ?? null)) {
			$empty_option = '<option></option>';
		}

		// clearing allowed
		$clearable = (bool) ($options['allow_clear'] ?? 1);
		$clearable_str = $clearable ? 'true' : 'false';

		// placeholder text
		$placeholder = json_encode($options['placeholder'] ?? '');

		// minimum input length for searching
		$min_inp_len = 1;

		// search language definitions
		$lang_error_loading = json_encode($options['language']['error'] ?? JText::translate('VBO_ERR_LOAD_RESULTS'));
		$lang_no_results    = json_encode($options['language']['noresults'] ?? JText::translate('VBO_NO_RECORDS_FOUND'));
		$lang_searching     = json_encode($options['language']['searching'] ?? JText::translate('VBO_SEARCHING'));
		// this language definition is NOT quoted, because it is not JSON-encoded
		$lang_inptooshort   = $min_inp_len > 1 && ($options['language']['inptooshort'] ?? '') ? $options['language']['inptooshort'] : '';

		// selection/result with ID
		$selection_with_id = ($options['selected_id'] ?? null) ? 'true' : 'false';

		// selection extra class
		$selection_class = $options['selection_class'] ?? '';

		// selection click open widget
		$selection_click_widget = $options['selection_click_widget'] ?? '';

		// selection dispatch event
		$selection_event = $options['selection_event'] ?? '';

		// select2 width
		$sel2width = $options['width'] ?? 'resolve';

		// build HTML string
		$html = <<<HTML
<select {$attr_str}>{$empty_option}</select>
HTML;

		// template selection function
		$selectionFn = '';
		if ($options['style_selection'] ?? null) {
			$defaultSelectionIcn = $options['default_selection_icon'] ?? '';
			$selectionFn = <<<JAVASCRIPT
templateSelection: (element) => {
	if (!element.id) {
		return element.text;
	}
	let sel_elem = jQuery('<span></span>')
		.addClass(('vbo-sel2-selected-search-elem $selection_class').trim())
		.text(element.text + ($selection_with_id ? ' #' + element.id : ''));
	if (element.img) {
		let avatar_elem = jQuery('<img/>')
			.addClass('vbo-sel2-selected-search-elem-avatar')
			.attr('src', element.img);
		if (element.img_title) {
			avatar_elem.attr('title', element.img_title);
		}
		sel_elem.prepend(avatar_elem);
	} else if (element.icon_class) {
		let icn_wrap = jQuery('<span></span>')
			.addClass('vbo-sel2-selected-search-elem-avatar');
		let icn_elem = jQuery('<i></i>')
			.addClass(element.icon_class);
		icn_wrap.append(icn_elem);
		sel_elem.prepend(icn_wrap);
	} else if ('$defaultSelectionIcn') {
		let icn_elem = jQuery('<i></i>')
			.addClass('$defaultSelectionIcn')
			.addClass('vbo-sel2-selected-search-elem-avatar');
		sel_elem.prepend(icn_elem);
	}
	if ("$selection_click_widget" == 'booking_details') {
		sel_elem.on('click', () => {
			VBOCore.handleDisplayWidgetNotification({
				widget_id: 'booking_details',
			}, {
				booking_id: element.id,
				modal_options: {
					suffix: 'vbo-booking-details-inner',
					body_prepend: false,
					enlargeable:  false,
					minimizeable: false,
				},
			});
		});
	}
	return sel_elem;
}
JAVASCRIPT;
		}

		// build script declaration
		$js_decl = <<<JAVASCRIPT
jQuery(function() {
	jQuery('select#{$options['id']}').select2({
		width: '$sel2width',
		allowClear: $clearable_str,
		data: $data_sources_str,
		placeholder: $placeholder,
		minimumInputLength: $min_inp_len,
		language: {
			errorLoading: () => {
				return $lang_error_loading;
			},
			noResults: () => {
				return $lang_no_results;
			},
			searching: () => {
				return $lang_searching;
			},
			inputTooShort: () => {
				return "$lang_inptooshort";
			},
		},
		ajax: {
			delay: 350,
			url: "$endpoint",
			dataType: 'json',
		},
		templateResult: (element) => {
			if (!element.id) {
				return element.text;
			}
			let search_elem = jQuery('<span></span>')
				.addClass('vbo-sel2-search-elem');

			let search_avatar = jQuery('<span></span>')
				.addClass('vbo-sel2-search-elem-avatar');

			let elem_name = jQuery('<span></span>')
				.addClass('vbo-sel2-search-elem-name')
				.text(element.text + ($selection_with_id ? ' #' + element.id : ''));

			if (element.img) {
				search_avatar.append('<img src="' + element.img + '" ' + (element.img_title ? 'title="' + element.img_title + '" ' : '') + '/>');
			} else if (element.icon_class) {
				search_avatar.append('<i class="' + element.icon_class + '"></i>');
			}

			search_avatar.append(elem_name);
			search_elem.append(search_avatar);

			return search_elem;
		},
		$selectionFn
	});
	if ("$selection_event") {
		jQuery('select#{$options['id']}').on('select2:select', (e) => {
			let element = e?.params?.data || e;
			VBOCore.emitEvent("$selection_event", {
				element: element,
			});
		});
	}
});
JAVASCRIPT;

		if ((VBOPlatformDetection::isWordPress() && wp_doing_ajax()) || (!VBOPlatformDetection::isWordPress() && !strcasecmp((string) JFactory::getApplication()->input->server->get('HTTP_X_REQUESTED_WITH', ''), 'xmlhttprequest'))) {
			// concatenate script to HTML string when doing an AJAX request
			$html .= "\n" . '<script>' . $js_decl . '</script>';
		} else {
			// add script declaration to document
			JFactory::getDocument()->addScriptDeclaration($js_decl);
		}

		// return the HTML string to be displayed
		return $html;
	}

	/**
	 * Loads the assets for setting up the VBOCore JS in the site section.
	 * 
	 * @param 	array 	$options 	Associative list of loading options.
	 * 
	 * @return 	void
	 * 
	 * @since 	1.17.4 (J) - 1.7.4 (WP)
	 */
	public function loadCoreJS(array $options = [])
	{
		static $corejs_loaded = null;

		if ($corejs_loaded) {
			// loaded flag
			return;
		}

		// cache loaded flag
		$corejs_loaded = 1;

		// add script
		$this->addScript(VBO_ADMIN_URI . 'resources/vbocore.js', ['version' => VIKBOOKING_SOFTWARE_VERSION]);

		if ($options) {
			$core_options = json_encode((object) $options, JSON_PRETTY_PRINT);

			// add script declaration to document
			JFactory::getDocument()->addScriptDeclaration(
<<<JS
jQuery(function() {
	VBOCore.setOptions($core_options);
});
JS
			);
		}
	}

	/**
	 * Loads the assets for rendering the signature pad.
	 * 
	 * @param 	array 	$options 	Associative list of loading options.
	 * 
	 * @return 	void
	 * 
	 * @since 	1.17.4 (J) - 1.7.4 (WP)
	 */
	public function loadSignaturePad(array $options = [])
	{
		static $signpad_loaded = null;

		if ($signpad_loaded) {
			// loaded flag
			return;
		}

		// cache loaded flag
		$signpad_loaded = 1;

		// add script
		$this->addScript(VBO_SITE_URI . 'resources/signature_pad.js', ['version' => VIKBOOKING_SOFTWARE_VERSION]);
	}

	/**
	 * Loads the assets solely needed to render the DRP calendar.
	 * 
	 * @param 	array 	$options 	Associative list of loading options.
	 * 
	 * @return 	void
	 * 
	 * @since 	1.17.3 (J) - 1.7.3 (WP)
	 */
	public function loadDatesRangePicker(array $options = [])
	{
		static $drp_loaded = null;

		if ($drp_loaded) {
			// loaded flag
			return;
		}

		// cache loaded flag
		$drp_loaded = 1;

		// add DRP script
		$this->addScript(VBO_SITE_URI . 'resources/datesrangepicker.js', ['version' => VIKBOOKING_SOFTWARE_VERSION]);

		// load JS lang defs
		JText::script('VBPICKUPROOM');
		JText::script('VBRETURNROOM');
		JText::script('VBO_MIN_STAY_NIGHTS');
		JText::script('VBO_CLEAR_DATES');
		JText::script('VBO_CLOSE');
	}

	/**
	 * Loads the necessary JS and CSS assets to render the jQuery UI Datepicker calendar.
	 * It is also possible to load the assets for the DatesRangePicker extension.
	 * 
	 * @param 	array 	$options 	Associative list of loading options.
	 * 
	 * @return 	void
	 * 
	 * @since   1.1.0
	 * @since   1.15.0 (J) - 1.5.0 (WP) the lang definitions work for both front and back -ends.
	 * @since 	1.17.3 (J) - 1.7.3 (WP) added support for the DatesRangePicker extension.
	 */
	public function loadDatePicker(array $options = [])
	{
		static $datepicker_loaded = null;

		if ($datepicker_loaded) {
			// loaded flag
			return;
		}

		$document = JFactory::getDocument();
		$document->addStyleSheet(VBO_SITE_URI . 'resources/jquery-ui.min.css');
		
		JHtml::fetch('jquery.framework', true, true);
		$this->addScript(VBO_SITE_URI . 'resources/jquery-ui.min.js');

		if (!strcasecmp(($options['type'] ?? ''), 'dates_range')) {
			// load DRP calendar assets
			$this->loadDatesRangePicker($options);
		}

		$vbo_df = VikBooking::getDateFormat();
		$juidf = $vbo_df == "%d/%m/%Y" ? 'dd/mm/yy' : ($vbo_df == "%m/%d/%Y" ? 'mm/dd/yy' : 'yy/mm/dd');

		$is_rtl_lan = false;
		$day_names_min_len = 2;
		$now_lang = JFactory::getLanguage();
		if (method_exists($now_lang, 'isRtl')) {
			$is_rtl_lan = $now_lang->isRtl();
			if ($is_rtl_lan) {
				// for most RTL languages, 2 chars for the week-days would not make sense
				$day_names_min_len = 3;
			}
		}

		// build default regional values for datepicker
		$vbo_dp_regional_vals = [
			'closeText'   => JText::translate('VBJQCALDONE'),
			'prevText'    => JText::translate('VBJQCALPREV'),
			'nextText'    => JText::translate('VBJQCALNEXT'),
			'currentText' => JText::translate('VBJQCALTODAY'),
			'monthNames'  => [
				JText::translate('VBMONTHONE'),
				JText::translate('VBMONTHTWO'),
				JText::translate('VBMONTHTHREE'),
				JText::translate('VBMONTHFOUR'),
				JText::translate('VBMONTHFIVE'),
				JText::translate('VBMONTHSIX'),
				JText::translate('VBMONTHSEVEN'),
				JText::translate('VBMONTHEIGHT'),
				JText::translate('VBMONTHNINE'),
				JText::translate('VBMONTHTEN'),
				JText::translate('VBMONTHELEVEN'),
				JText::translate('VBMONTHTWELVE'),
			],
			'monthNamesShort' => [
				$this->safeSubstr(JText::translate('VBMONTHONE')),
				$this->safeSubstr(JText::translate('VBMONTHTWO')),
				$this->safeSubstr(JText::translate('VBMONTHTHREE')),
				$this->safeSubstr(JText::translate('VBMONTHFOUR')),
				$this->safeSubstr(JText::translate('VBMONTHFIVE')),
				$this->safeSubstr(JText::translate('VBMONTHSIX')),
				$this->safeSubstr(JText::translate('VBMONTHSEVEN')),
				$this->safeSubstr(JText::translate('VBMONTHEIGHT')),
				$this->safeSubstr(JText::translate('VBMONTHNINE')),
				$this->safeSubstr(JText::translate('VBMONTHTEN')),
				$this->safeSubstr(JText::translate('VBMONTHELEVEN')),
				$this->safeSubstr(JText::translate('VBMONTHTWELVE')),
			],
			'dayNames' => [
				JText::translate('VBWEEKDAYZERO'),
				JText::translate('VBWEEKDAYONE'),
				JText::translate('VBWEEKDAYTWO'),
				JText::translate('VBWEEKDAYTHREE'),
				JText::translate('VBWEEKDAYFOUR'),
				JText::translate('VBWEEKDAYFIVE'),
				JText::translate('VBWEEKDAYSIX'),
			],
			'dayNamesShort' => [
				$this->safeSubstr(JText::translate('VBWEEKDAYZERO')),
				$this->safeSubstr(JText::translate('VBWEEKDAYONE')),
				$this->safeSubstr(JText::translate('VBWEEKDAYTWO')),
				$this->safeSubstr(JText::translate('VBWEEKDAYTHREE')),
				$this->safeSubstr(JText::translate('VBWEEKDAYFOUR')),
				$this->safeSubstr(JText::translate('VBWEEKDAYFIVE')),
				$this->safeSubstr(JText::translate('VBWEEKDAYSIX')),
			],
			'dayNamesMin' => [
				$this->safeSubstr(JText::translate('VBWEEKDAYZERO'), $day_names_min_len),
				$this->safeSubstr(JText::translate('VBWEEKDAYONE'), $day_names_min_len),
				$this->safeSubstr(JText::translate('VBWEEKDAYTWO'), $day_names_min_len),
				$this->safeSubstr(JText::translate('VBWEEKDAYTHREE'), $day_names_min_len),
				$this->safeSubstr(JText::translate('VBWEEKDAYFOUR'), $day_names_min_len),
				$this->safeSubstr(JText::translate('VBWEEKDAYFIVE'), $day_names_min_len),
				$this->safeSubstr(JText::translate('VBWEEKDAYSIX'), $day_names_min_len),
			],
			'weekHeader'         => JText::translate('VBJQCALWKHEADER'),
			'dateFormat'         => $juidf,
			'firstDay'           => VikBooking::getFirstWeekDay(),
			'isRTL'              => $is_rtl_lan,
			'showMonthAfterYear' => false,
			'yearSuffix'         => '',
		];

		$ldecl = '
jQuery(function($) {' . "\n" . '
	$.datepicker.regional["vikbooking"] = ' . json_encode($vbo_dp_regional_vals) . ';
	$.datepicker.setDefaults($.datepicker.regional["vikbooking"]);' . "\n" . '
});';

		/**
		 * Trigger event to allow third party plugins to overwrite the JS declaration for the datepicker.
		 * 
		 * @since 	1.16.0 (J) - 1.6.0 (WP)
		 */
		VBOFactory::getPlatform()->getDispatcher()->trigger('onBeforeDeclareDatepickerRegionalVikBooking', [$is_rtl_lan, $now_lang->getTag(), &$ldecl]);

		// add script declaration
		$document->addScriptDeclaration($ldecl);

		// cache loaded flag
		$datepicker_loaded = 1;
	}

	/**
	 * Loads the CMS's native datepicker calendar.
	 *
	 * @since   1.10
	 */
	public function getCalendar($val, $name, $id = null, $df = null, array $attributes = array())
	{
		if ($df === null)
		{
			$df = VikBooking::getDateFormat();
		}

		return parent::calendar($val, $name, $id, $df, $attributes);
	}

	/**
	 * Returns a masked e-mail address. The e-mail are masked using 
	 * a technique to encode the bytes in hexadecimal representation.
	 * The chunk of the masked e-mail will be also encoded to be HTML readable.
	 *
	 * @param   string   $email     The e-mail to mask.
	 * @param   boolean  $reverse   True to reverse the e-mail address.
	 *                              Only if the e-mail is not contained into an attribute.
	 *
	 * @return  string   The masked e-mail address.
	 */
	public function maskMail($email, $reverse = false)
	{
		if ($reverse)
		{
			// reverse the e-mail address
			$email = strrev($email);
		}

		// converts the e-mail address from bin to hex
		$email = bin2hex($email);
		// append ;&#x sequence after every chunk of the masked e-mail
		$email = chunk_split($email, 2, ";&#x");
		// prepend &#x sequence before the address and trim the ending sequence
		$email = "&#x" . substr($email, 0, -3);

		return $email;
	}

	/**
	 * Returns a safemail tag to avoid the bots spoof a plain address.
	 *
	 * @param   string   $email     The e-mail address to mask.
	 * @param   boolean  $mail_to   True if the address should be wrapped
	 *                              within a "mailto" link.
	 *
	 * @return  string   The HTML tag containing the masked address.
	 *
	 * @uses    maskMail()
	 */
	public function safeMailTag($email, $mail_to = false)
	{
		// include the CSS declaration to reverse the text contained in the <safemail> tags
		JFactory::getDocument()->addStyleDeclaration('safemail {direction: rtl;unicode-bidi: bidi-override;}');

		// mask the reversed e-mail address
		$masked = $this->maskMail($email, true);

		// include the address into a custom <safemail> tag
		$tag = "<safemail>$masked</safemail>";

		if ($mail_to)
		{
			// mask the address for mailto command (do not use reverse)
			$mailto = $this->maskMail($email);

			// wrap the safemail tag within a mailto link
			$tag = "<a href=\"mailto:$mailto\" class=\"mailto\">$tag</a>";
		}

		return $tag;
	}

	/**
	 * Loads and echoes the script necessary to render the Fancybox
	 * plugin for jQuery to open images or iframes within a modal box.
	 * This resolves conflicts with some Bootstrap or Joomla (4) versions
	 * that do not support the old-native CSS class .modal with "behavior.modal".
	 * Mainly made to open pictures in a modal box, so the default "type" is set to "image".
	 * By passing a custom $opts string, the "type" property could be set to "iframe", but
	 * in this case it's better to use the other method of this class (Jmodal).
	 * The base jQuery library should be already loaded when using this method.
	 *
	 * @param   string      $selector   The jQuery selector to trigger Fancybox.
	 * @param   string      $opts       The options object for the Fancybox setup.
	 * @param   boolean     $reloadfunc If true, an additional function is included in the script
	 *                                  to apply again Fancybox to newly added images to the DOM (via Ajax).
	 *
	 * @return  void
	 *
	 * @uses    addScript()
	 */
	public function prepareModalBox($selector = '.vbomodal', $opts = '', $reloadfunc = false)
	{
		if (empty($opts)) {
			$opts = '{
				"helpers": {
					"overlay": {
						"locked": false
					}
				},
				"width": "70%",
				"height": "75%",
				"autoScale": true,
				"transitionIn": "none",
				"transitionOut": "none",
				"padding": 0,
				"type": "image"
			}';
		}
		$document = JFactory::getDocument();
		$document->addStyleSheet(VBO_SITE_URI.'resources/jquery.fancybox.css');
		$this->addScript(VBO_SITE_URI.'resources/jquery.fancybox.js');

		$reloadjs = '
		function reloadFancybox() {
			jQuery("'.$selector.'").fancybox('.$opts.');
		}
		';
		$js = '
		<script type="text/javascript">
		jQuery(function() {
			jQuery("'.$selector.'").fancybox('.$opts.');
		});'.($reloadfunc ? $reloadjs : '').'
		</script>';

		echo $js;
	}

	/**
	 * Method used to handle the reCAPTCHA events.
	 *
	 * @param   string  $event      The reCAPTCHA event to trigger.
	 *                              Here's the list of the accepted events:
	 *                              - display   Returns the HTML used to 
	 *                                          display the reCAPTCHA input.
	 *                              - check     Validates the POST data to make sure
	 *                                          the reCAPTCHA input was checked.
	 * @param   array   $options    A configuration array.
	 *
	 * @return  mixed   The event response.
	 *
	 * @since   1.2.3
	 * @wponly  the Joomla integration differs
	 */
	public function reCaptcha($event = 'display', array $options = array())
	{
		$response = null;
		// an optional configuration array (just leave empty)
		$options = array();
		// trigger reCAPTCHA display event to fill $response var
		do_action_ref_array('vik_recaptcha_' . $event, array(&$response, $options));
		// display reCAPTCHA by echoing it (empty in case reCAPTCHA is not available)
		return $response;
	}

	/**
	 * Checks if the com_user captcha is configured.
	 * In case the parameter is set to global, the default one
	 * will be retrieved.
	 * 
	 * @param   string   $plugin  The plugin name to check ('recaptcha' by default).
	 *
	 * @return  boolean  True if configured, otherwise false.
	 *
	 * @since   1.2.3
	 * @wponly  the Joomla integration differs
	 */
	public function isCaptcha($plugin = 'recaptcha')
	{
		return apply_filters('vik_' . $plugin . '_on', false);
	}

	/**
	 * Checks if the global captcha is configured.
	 * 
	 * @param   string   $plugin  The plugin name to check ('recaptcha' by default).
	 *
	 * @return  boolean  True if configured, otherwise false.
	 *
	 * @since   1.2.3
	 */
	public function isGlobalCaptcha($plugin = 'recaptcha')
	{
		return $this->isCaptcha($plugin);
	}

	/**
	 * Method used to obtain a WordPress media form field.
	 *
	 * @return  string  The media in HTML.
	 *
	 * @since   1.3.0
	 */
	public function getMediaField($name, $value = null, array $data = array())
	{
		// check if WordPress is installed
		if (VBOPlatformDetection::isWordPress())
		{
			add_action('admin_enqueue_scripts', function() {
				wp_enqueue_media();
			});

			// import form field class
			JLoader::import('adapter.form.field');

			// create XML field manifest
			$xml = "<field name=\"$name\" type=\"media\" modowner=\"vikbooking\" />";

			// instantiate field
			$field = JFormField::getInstance(simplexml_load_string($xml));

			// overwrite name and value within data
			$data['name']  = $name;
			$data['value'] = $value;

			// inject display data within field instance
			foreach ($data as $k => $v)
			{
				$field->bind($v, $k);
			}

			// render field
			return $field->render();
		}

		// fallback to Joomla

		// init media field
		$field = new JFormFieldMedia(null, $value);
		// setup an empty form as placeholder
		$field->setForm(new JForm('vikbooking.media'));

		// force field attributes
		$data['name']  = $name;
		$data['value'] = $value;

		if (empty($data['previewWidth']))
		{
			// there is no preview width, set a defualt value
			// to make the image visible within the popover
			$data['previewWidth'] = 480;
		}

		// render the field	
		return $field->render('joomla.form.field.media', $data);
	}

	/**
	 * Displays a multi-state toggle switch element with unlimited buttons.
	 * Custom values, contents, labels, attributes and JS events can be attached
	 * to each button. VCM will use this same method.
	 * 
	 * @param   string  $name   the input name equal for all radio buttons.
	 * @param   string  $value  the current input field value to be pre-selected.
	 * @param   array   $values list of radio buttons with each value.
	 * @param   array   $labels list of contents for each button trigger.
	 * @param   array   $attrs  list of associative array attributes for each button.
	 * @param   array   $wrap   list of associative array attributes for the wrapper.
	 * 
	 * @return  string  the necessary HTML to render the multi-state toggle switch.
	 * 
	 * @since   1.15.0 (J) - 1.5.0 (WP)
	 */
	public function multiStateToggleSwitchField($name, $value, $values = array(), $labels = array(), $attrs = array(), $wrap = array())
	{
		static $tooltip_js_declared = null;

		// whether tooltip for titles is needed
		$needs_tooltip = false;

		// HTML container
		$multi_state_switch = '';

		if (!is_array($values) || !count($values)) {
			// values must be set or we don't know what buttons to display
			return $multi_state_switch;
		}

		// build default classes for the tri-state toggle switch (with 3 buttons)
		$def_tristate_cls = array(
			'vik-multiswitch-radiobtn-on',
			'vik-multiswitch-radiobtn-def',
			'vik-multiswitch-radiobtn-off',
		);

		// start wrapper
		$multi_state_switch .= "\n" . '<div class="vik-multiswitch-wrap' . (isset($wrap['class']) ? (' ' . $wrap['class']) : '') . '">' . "\n";

		foreach ($values as $btn_k => $btn_val) {
			// build default classes for button label
			$btn_classes = array('vik-multiswitch-radiobtn');
			if (isset($def_tristate_cls[$btn_k])) {
				// push default class for a 3-state toggle switch
				array_push($btn_classes, $def_tristate_cls[$btn_k]);
			}
			// check if additional custom classes have been defined for this button
			if (isset($attrs[$btn_k]) && isset($attrs[$btn_k]['label_class']) && !empty($attrs[$btn_k]['label_class'])) {
				if (is_array($attrs[$btn_k]['label_class'])) {
					// list of additional classes for this button
					$btn_classes = array_merge($btn_classes, $attrs[$btn_k]['label_class']);
				} elseif (is_string($attrs[$btn_k]['label_class'])) {
					// multiple classes should be space-separated
					array_push($btn_classes, $attrs[$btn_k]['label_class']);
				}
			}

			// check title as first thing, even though this is passed along with the labels
			$label_title = '';
			if (isset($labels[$btn_k]) && !is_scalar($labels[$btn_k]) && isset($labels[$btn_k]['title'])) {
				$needs_tooltip = true;
				$label_title = ' title="' . addslashes(htmlentities($labels[$btn_k]['title'])) . '"';
			}

			// start button label
			$multi_state_switch .= "\t" . '<label class="' . implode(' ', $btn_classes) . '"' . $label_title . '>' . "\n";

			// check button input radio
			$radio_attributes = array();
			if (($value !== null && $value == $btn_val) || ($value === null && $btn_k === 0)) {
				// this radio button must be checked (pre-selected)
				$radio_attributes['checked'] = true;
			}
			// check if custom attributes were specified for this input
			if (isset($attrs[$btn_k]) && isset($attrs[$btn_k]['input'])) {
				// must be an associative array with key = attribute name, value = attribute value
				foreach ($attrs[$btn_k]['input'] as $attr_name => $attr_val) {
					// javascript events could be attached like 'onchange'=>'myCallback(this.value)'
					$radio_attributes[$attr_name] = $attr_val;
				}
			}
			$radio_attr_string = '';
			foreach ($radio_attributes as $attr_name => $attr_val) {
				if ($attr_val === true) {
					// short-attribute name, like "checked"
					$radio_attr_string .= $attr_name . ' ';
					continue;
				}
				$radio_attr_string .= $attr_name . '="' . $attr_val . '" ';
			}
			$multi_state_switch .= "\t\t" . '<input type="radio" name="' . $name . '" value="' . $btn_val . '" ' . $radio_attr_string . '/>' . "\n";

			// add button trigger
			$multi_state_switch .= "\t\t" . '<span class="vik-multiswitch-trigger"></span>' . "\n";

			// check button label text
			if (isset($labels[$btn_k])) {
				/**
				 * By default, the buttons of the toggle switch use an animation,
				 * which requires an absolute positioning of the "label-text".
				 * For this reason, there cannot be a minimum width for these texts
				 * and so the content should fit the default width. Usually, using
				 * a font-awesome icon is the best content. For using literal texts,
				 * like "Dark", "Light" etc.. the class "vik-multiswitch-noanimation"
				 * should be passed to the button label text.
				 */
				$label_txt = '';
				$label_class = '';
				if (!is_scalar($labels[$btn_k])) {
					// with an associative array we accept value, title and custom classes
					if (isset($labels[$btn_k]['value'])) {
						$label_txt = $labels[$btn_k]['value'];
					}
					if (isset($labels[$btn_k]['class'])) {
						$label_class = ' ' . ltrim($labels[$btn_k]['class']);
					}
				} else {
					// just a string, maybe with text or HTML mixed content
					$label_txt = $labels[$btn_k];
				}
				if (strlen($label_txt)) {
					// append button label text only if some text has been defined
					$multi_state_switch .= "\t\t" . '<span class="vik-multiswitch-txt' . $label_class . '">' . $label_txt . '</span>' . "\n";
				}
			}

			// end button label
			$multi_state_switch .= "\t" . '</label>' . "\n";
		}

		// end wrapper
		$multi_state_switch .= '</div>' . "\n";

		// check tooltip JS rendering
		if (!$tooltip_js_declared && $needs_tooltip) {
			// turn static flag on
			$tooltip_js_declared = 1;

			// add script declaration for JS rendering of tooltips
			$doc = JFactory::getDocument();
			$doc->addScriptDeclaration(
<<<JS
jQuery(function() {
	if (typeof jQuery.fn.tooltip === 'function') {
		jQuery('.vik-multiswitch-wrap label').tooltip();
	}
});
JS
			);
		}

		return $multi_state_switch;
	}

	/**
	 * Returns a list of supported fonts for the third-party visual editor (Quill).
	 * 
	 * @param   bool    $short_names    whether to return short font names.
	 * 
	 * @return  array   list of font family names or short names.
	 * 
	 * @since   1.15.0 (J) - 1.5.0 (WP)
	 */
	public function getVisualEditorFonts($short_names = false)
	{
		// supported fonts
		$font_families = ['Sans Serif', 'Arial', 'Courier', 'Garamond', 'Tahoma', 'Times New Roman', 'Verdana', 'Inconsolata', 'Sailec Light', 'Monospace'];

		if (!$short_names) {
			// return the regular names to be displayed
			return $font_families;
		}

		// return the "short" names of the supported fonts
		return array_map(function($font) {
			return str_replace(' ', '-', strtolower($font));
		}, $font_families);
	}

	/**
	 * Loads the necessary language definitions for the third-party visual editor (Quill).
	 * 
	 * @return  void
	 * 
	 * @since   1.17.3 (J) - 1.7.3 (WP)  added support to Generative AI text functions.
	 */
	public function loadVisualEditorDefinitions()
	{
		static $loaded = null;

		if ($loaded) {
			return;
		}

		$loaded = 1;

		// load language definitions for JS
		JText::script('VBO_CONT_WRAPPER');
		JText::script('VBO_CONT_WRAPPER_HELP');
		JText::script('VBO_GEN_CONTENT');
		JText::script('VBO_AI_LABEL_DEF');
		JText::script('VBO_AITOOL_WRITER_DEF_PROMPT');
		JText::script('VBANNULLA');
	}

	/**
	 * Loads the necessary assets for the third-party visual editor (Quill).
	 * 
	 * @return  void
	 * 
	 * @since   1.15.0 (J) - 1.5.0 (WP)
	 * @since   1.17.3 (J) - 1.7.3 (WP)  added support to Generative AI text functions.
	 */
	public function loadVisualEditorAssets()
	{
		static $loaded = null;

		if ($loaded) {
			return;
		}

		$loaded = 1;

		// access the document
		$doc = JFactory::getDocument();

		// load JS langs
		$this->loadVisualEditorDefinitions();

		// build the list of font families
		$font_families = $this->getVisualEditorFonts();
		$font_shortfam = $this->getVisualEditorFonts(true);
		$js_font_names = json_encode($font_shortfam);

		// build inline CSS styles
		$css_font_decl = '';
		foreach ($font_families as $k => $font_name) {
			$font_val = $font_shortfam[$k];
			$css_font_decl .= '.ql-snow .ql-picker.ql-font .ql-picker-label[data-value="' . $font_val . '"]::before,';
			$css_font_decl .= '.ql-snow .ql-picker.ql-font .ql-picker-item[data-value="' . $font_val . '"]::before {' . "\n";
			$css_font_decl .= 'content: "' . $font_name . '";' . "\n";
			$css_font_decl .= 'font-family: "' . $font_name . '";' . "\n";
			$css_font_decl .= '}' . "\n";
		}

		$css_font_decl .= '
			.ql-picker.ql-specialtags .ql-picker-label {
				padding-right: 18px;
			}
			.ql-picker.ql-specialtags .ql-picker-label:before {
				content: "' . htmlspecialchars(JText::translate('VBO_CONDTEXT_TKN')) . '";
			}
			.ql-formats .ql-genai {
				width: auto !important;
				font-weight: bold;
			}
		';

		/**
		 * Cache the pre-configuration CSS onto a file to allow AJAX requests
		 * to properly load the asset inline within the response. Before this
		 * change the styles used to be added as an inline style declaration.
		 * 
		 * @since 	1.16.7 (J) - 1.6.7 (WP)
		 * @since 	1.17.3 (J) - 1.7.3 (WP)  cached file is related to software version.
		 */
		$cached_preconfig_suffix   = defined('VIKBOOKING_SOFTWARE_VERSION') ? '-' . VIKBOOKING_SOFTWARE_VERSION : '';
		$cached_preconfig_css_path = implode(DIRECTORY_SEPARATOR, [VBO_ADMIN_PATH, 'resources', 'quill', 'vik-quill-preconfig-cache' . $cached_preconfig_suffix . '.css']);
		$cached_preconfig_css_uri  = VBO_ADMIN_URI . 'resources/quill/vik-quill-preconfig-cache' . $cached_preconfig_suffix . '.css';
		$cached_preconfig_css_ok   = is_file($cached_preconfig_css_path);

		if (!$cached_preconfig_css_ok) {
			// attempt to create the file
			$cached_preconfig_css_ok = JFile::write($cached_preconfig_css_path, $css_font_decl);
		}

		if ($cached_preconfig_css_ok) {
			// load cached script file
			$doc->addStyleSheet($cached_preconfig_css_uri);
		} else {
			// revert to append CSS style declaration to document
			$doc->addStyleDeclaration($css_font_decl);
		}

		// append theme CSS to document
		$doc->addStyleSheet(VBO_ADMIN_URI . 'resources/quill/quill.snow.css');

		// append JS assets to document
		$this->addScript(VBO_ADMIN_URI . 'resources/quill/quill.js');
		$this->addScript(VBO_ADMIN_URI . 'resources/quill/quill-image-resize.min.js');
		$this->addScript(VBO_ADMIN_URI . 'resources/quill/vik-content-builder.js');

		// icon for mail wrapper
		$mail_wrapper_icn = '<i class="' . VikBookingIcons::i('minus-square') . '" title="' . htmlspecialchars(JText::translate('VBO_INSERT_CONT_WRAPPER')) . '"></i>';

		// icon for mail preview
		$mail_preview_icn = '<i class="' . VikBookingIcons::i('eye') . '" title="' . htmlspecialchars(JText::translate('VBOPREVIEW')) . '"></i>';

		// icon for property logo (home icon)
		$mail_homelogo_icn = '<i class="' . VikBookingIcons::i('hotel') . '" title="' . htmlspecialchars(JText::translate('VBCONFIGFOURLOGO'), ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401) . '"></i>';

		// text for generating content through AI
		$mail_genai_icn = htmlspecialchars(JText::translate('VBO_AI_LABEL_DEF'), ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401);

		// build script pre-configuration string
		$quill_preconfig_script = 
<<<JS
// Quill pre-configuration
(function() {
	// configure Quill to use inline styles rather than classes
	
	var AlignClass = Quill.import('attributors/class/align');
	Quill.register(AlignClass, true);

	var BackgroundClass = Quill.import('attributors/class/background');
	Quill.register(BackgroundClass, true);

	var ColorClass = Quill.import('attributors/class/color');
	Quill.register(ColorClass, true);

	var FontClass = Quill.import('attributors/class/font');
	Quill.register(FontClass, true);

	var SizeClass = Quill.import('attributors/class/size');
	Quill.register(SizeClass, true);

	var AlignStyle = Quill.import('attributors/style/align');
	Quill.register(AlignStyle, true);

	var BackgroundStyle = Quill.import('attributors/style/background');
	Quill.register(BackgroundStyle, true);

	var ColorStyle = Quill.import('attributors/style/color');
	Quill.register(ColorStyle, true);

	var SizeStyle = Quill.import('attributors/style/size');
	Quill.register(SizeStyle, true);

	var FontStyle = Quill.import('attributors/style/font');
	Quill.register(FontStyle, true);

	// set additional fonts
	var Font = Quill.import('formats/font');
	Font.whitelist = $js_font_names;
	Quill.register(Font, true);

	// register custom Blot for special tags
	var Inline = Quill.import('blots/inline');
	class Specialtag extends Inline {
		static create(value) {
			var node = super.create(value);
			if (value) {
				node.setAttribute('class', value);
			}
			return node;
		}

		static formats(domNode) {
			return domNode.getAttribute("class");
		}

		format(name, value) {
			if (name !== this.statics.blotName || !value) {
				return super.format(name, value);
			}
			if (value) {
				this.domNode.setAttribute('class', value);
			}
		}
	}
	Specialtag.blotName = 'specialtag';
	Specialtag.tagName = 'strong';
	Quill.register(Specialtag);

	// register bold tag names in the proper order to avoid conflicts
	var Bold = Quill.import('formats/bold');
	Bold.tagName = ['B', 'STRONG'];
	Quill.register(Bold, true);

	// register custom Blot for mail-wrapper
	var BlockEmbed = Quill.import('blots/block/embed');
	class MailWrapper extends BlockEmbed { }
	MailWrapper.blotName = 'mailwrapper';
	MailWrapper.className = 'vbo-editor-hl-mailwrapper';
	MailWrapper.tagName = 'hr';
	Quill.register(MailWrapper);

	// register custom Blot for preview
	class Preview extends Inline { }
	Preview.blotName = 'preview';
	Preview.tagName = 'span';
	Quill.register(Preview);

	// register custom icons for mail-wrapper and preview
	var icons = Quill.import('ui/icons');
	icons['mailwrapper'] = '$mail_wrapper_icn';
	icons['preview'] = '$mail_preview_icn';
	icons['homelogo'] = '$mail_homelogo_icn';
	icons['genai'] = '$mail_genai_icn';
})();
JS
		;

		/**
		 * Cache the pre-configuration script onto a file to allow AJAX requests
		 * to properly load the script inline within the response. Before this
		 * change the script used to be added as an inline script declaration.
		 * 
		 * @since 	1.16.7 (J) - 1.6.7 (WP)
		 * @since 	1.17.3 (J) - 1.7.3 (WP)  cached file is related to software version.
		 */
		$cached_preconfig_suffix      = defined('VIKBOOKING_SOFTWARE_VERSION') ? '-' . VIKBOOKING_SOFTWARE_VERSION : '';
		$cached_preconfig_script_path = implode(DIRECTORY_SEPARATOR, [VBO_ADMIN_PATH, 'resources', 'quill', 'vik-quill-preconfig-cache' . $cached_preconfig_suffix . '.js']);
		$cached_preconfig_script_uri  = VBO_ADMIN_URI . 'resources/quill/vik-quill-preconfig-cache' . $cached_preconfig_suffix . '.js';
		$cached_preconfig_script_ok   = is_file($cached_preconfig_script_path);

		if (!$cached_preconfig_script_ok) {
			// attempt to create the file
			$cached_preconfig_script_ok = JFile::write($cached_preconfig_script_path, $quill_preconfig_script);
		}

		if ($cached_preconfig_script_ok) {
			// load cached script file
			$this->addScript($cached_preconfig_script_uri);
		} else {
			// revert to append JS script declaration to document
			$doc->addScriptDeclaration($quill_preconfig_script);
		}

		/**
		 * Load Context Menu assets.
		 * 
		 * @since 	1.17.6 (J) - 1.7.6 (WP)
		 * @since 	1.18.0 (J) - 1.8.0 (WP) loaded only if not during an AJAX request.
		 */
		if ((VBOPlatformDetection::isWordPress() && !wp_doing_ajax()) || (!VBOPlatformDetection::isWordPress() && strcasecmp((string) JFactory::getApplication()->input->server->get('HTTP_X_REQUESTED_WITH', ''), 'xmlhttprequest'))) {
			$this->loadContextMenuAssets();
		}
	}

	/**
	 * Renders a third-party visual editor (Quill).
	 * 
	 * @param   string  $name   the input name of the textarea field.
	 * @param   string  $value  the current value of the textarea field/editor.
	 * @param   array   $attrs  list of associative array attributes for the textarea.
	 * @param   array   $opts   associative array of options for the editor.
	 * @param   array   $btns   associative array of custom buttons for the editor (special tags).
	 * 
	 * @return  string  the necessary HTML to render the visual editor.
	 * 
	 * @since   1.15.0 (J) - 1.5.0 (WP)
	 * @since   1.17.3 (J) - 1.7.3 (WP)  added support to Generative AI text functions.
	 */
	public function renderVisualEditor($name, $value, array $attrs = [], array $opts = [], array $btns = [])
	{
		if (empty($name)) {
			return '';
		}

		// build the AJAX endpoints for uploading files, to preview the message and more
		$upload_endpoint   = VikBooking::ajaxUrl('index.php?option=com_vikbooking&task=upload_media_file');
		$ajax_preview_mess = VikBooking::ajaxUrl('index.php?option=com_vikbooking&task=mail.preview_visual_editor');
		$ajax_logo_url     = VikBooking::ajaxUrl('index.php?option=com_vikbooking&task=mail.get_default_logo');

		// the HTML to build
		$editor = "\n";

		// static editor counter
		static $editor_counter = 0;

		// increase counter for this instance
		$editor_counter++;

		// build id attributes for text and visual editors
		$editor_id = 'vik-contentbuilder-editor-' . $editor_counter;
		if (!isset($attrs['id'])) {
			$attrs['id'] = 'vik-contentbuilder-tarea-' . $editor_counter;
		}

		// labels for modes and editor
		$text_html_lbl   = JText::translate('VBO_MODE_TEXTHTML');
		$visual_mode_lbl = JText::translate('VBO_MODE_VISUAL');

		// allowed modes to display the visual editor
		$allowed_modes = [
			'text'         => $text_html_lbl,
			'visual'       => $visual_mode_lbl,
			'modal-visual' => $visual_mode_lbl,
		];

		// define the default buttons to display for mode switching
		$modes = [
			'text'         => $text_html_lbl,
			'modal-visual' => $visual_mode_lbl,
		];

		// overwrite modes to display
		if (isset($opts['modes']) && is_array($opts['modes']) && $opts['modes']) {
			// check if the given array is associative, hence inclusive of texts
			if (array_keys($opts['modes']) != range(0, (count($opts['modes']) - 1))) {
				// replace modes
				$modes = $opts['modes'];
			} else {
				// only the allowed keys must have been passed
				$new_modes = [];
				foreach ($opts['modes'] as $mode_type) {
					if (!isset($allowed_modes[$mode_type])) {
						continue;
					}
					$new_modes[$mode_type] = $allowed_modes[$mode_type];
				}
				$modes = $new_modes ?: $modes;
			}
		}

		// resolve conflicts for both visual modes
		if (($modes['visual'] ?? '') && ($modes['modal-visual'] ?? '') && $modes['visual'] == $modes['modal-visual']) {
			$modes['modal-visual'] .= ' <i class="' . VikBookingIcons::i('window-restore') . '"></i>';
		}

		// ensure the text mode is set
		if (!isset($modes['text'])) {
			$modes['text'] = $allowed_modes['text'];
		}

		// overwrite default mode
		if (isset($opts['def_mode']) && isset($modes[$opts['def_mode']]) && count($modes) > 1) {
			$def_mode_val = $modes[$opts['def_mode']];
			unset($modes[$opts['def_mode']]);
			// sort modes accordingly
			$modes = array_merge([$opts['def_mode'] => $def_mode_val], $modes);
			// reset the array pointer
			reset($modes);
		}

		// set default mode
		$default_mode = key($modes);

		// ensure text-area styles are merged or set when not the default mode
		if ($default_mode != 'text') {
			if (!($attrs['style'] ?? '')) {
				// set style attribute to hide the text-area
				$attrs['style'] = 'display: none;';
			} else {
				// append the style instruction to hide the text-area
				$attrs['style'] .= ' display: none;';
			}
		}

		// textarea attributes
		$ta_attributes = [];
		foreach ($attrs as $aname => $aval) {
			if ($aname == 'name') {
				// skip reserved attribute name
				continue;
			}
			$ta_attributes[] = $aname . '="' . JHtml::fetch('esc_attr', $aval) . '"';
		}

		// visual editor JS options for list buttons
		$js_editor_opts_list_btns = [
			// ordered list (ol)
			['list' => 'ordered'],
			// un-ordered list (ul)
			['list' => 'bullet'],
		];

		if ($opts['list_check'] ?? null) {
			// push the un-ordered list (ul) with checked data-attribute, simulating a check-boxes list
			$js_editor_opts_list_btns[] = ['list' => 'check'];
		}

		// build visual editor JS options
		$js_editor_opts = [
			'snippetsyntax' => true,
			'modules' => [
				'toolbar' => [
					'container' => [
						[
							[
								'font' => $this->getVisualEditorFonts(true)
							],
						],
						[
							[
								'header' => [1, 2, 3, 4, 5, 6, false]
							]
						],
						[
							'bold',
							'italic',
							'underline',
							'strike',
							'blockquote',
						],
						[
							['align'  => []],
							['indent' => '-1'],
							['indent' => '+1'],
						],
						[
							[
								'color' => []
							],
							[
								'background' => []
							],
						],
						$js_editor_opts_list_btns,
						[
							'link',
							'image',
							'homelogo',
						],
						[
							'mailwrapper',
							'preview',
						],
					],
				],
				'imageResize' => [
					'displaySize' => true,
				],
			],
			'theme'   => 'snow',
		];

		// check for Generative AI support through Vik Channel Manager and E4jConnect
		if (class_exists('VikChannelManager') && defined('VikChannelManagerConfig::AI')) {
			// add editor button for Gen-AI
			$js_editor_opts['modules']['toolbar']['container'][] = ['genai'];
		}

		// build the list of special tags to be added to the editor
		$special_tags_btns = [];
		foreach ($btns as $tag_val) {
			$special_tags_btns[] = $tag_val;
		}

		if ($special_tags_btns) {
			// add custom buttons to the editor to manage special tags
			$js_editor_opts['modules']['toolbar']['container'][] = [
				['specialtags' => $special_tags_btns]
			];
			// append CSS inline styles
			$editor .= '<style type="text/css">' . "\n";
			foreach ($special_tags_btns as $tag_val) {
				$editor .= '.ql-picker.ql-specialtags .ql-picker-item[data-value="' . $tag_val . '"]:before {
					content: "' . $tag_val . '";
				}' . "\n";
			}
			$editor .= '</style>' . "\n";
		}

		if ($opts['unset_buttons'] ?? null) {
			// unset toolbar container main-level buttons
			$opts['unset_buttons'] = (array) $opts['unset_buttons'];
			foreach ($js_editor_opts['modules']['toolbar']['container'] as $tc_index => $tc_buttons) {
				if (!is_array($tc_buttons)) {
					continue;
				}
				foreach ($opts['unset_buttons'] as $unset_btn) {
					if (!is_string($unset_btn)) {
						continue;
					}
					foreach ($tc_buttons as $tc_btn_index => $tc_btn) {
						if (is_string($tc_btn) && $tc_btn === $unset_btn) {
							// unset toolbar container button
							unset($js_editor_opts['modules']['toolbar']['container'][$tc_index][$tc_btn_index]);
						}
					}
				}
				if (!$js_editor_opts['modules']['toolbar']['container'][$tc_index]) {
					// unset the whole container
					unset($js_editor_opts['modules']['toolbar']['container'][$tc_index]);
				} else {
					// restore numeric (non-associative) list
					$js_editor_opts['modules']['toolbar']['container'][$tc_index] = array_values($js_editor_opts['modules']['toolbar']['container'][$tc_index]);
				}
			}
			// restore numeric (non-associative) list
			$js_editor_opts['modules']['toolbar']['container'] = array_values($js_editor_opts['modules']['toolbar']['container']);
		}

		// attept to pretty print a JSON encoded string for the editor options
		$editor_opts_str = defined('JSON_PRETTY_PRINT') ? json_encode($js_editor_opts, JSON_PRETTY_PRINT) : json_encode($js_editor_opts);

		// safe default value for editor (HTML tags should not be converted to entities)
		$safe_value = preg_replace("/(<\/ ?textarea>)+/i", '', $value);

		// the HTML to render the visual editor
		$html_visual_editor = '<div class="vik-contentbuilder-editor-container" id="' . $editor_id . '"></div>';

		// build the actual HTML content
		$editor .= '<div class="vik-contentbuilder-wrapper">' . "\n";
		if (count($modes) > 1) {
			// display buttons to switch mode only if more than one mode available
			$editor .= "\t" . '<div class="vik-contentbuilder-switcher"' . (($opts['hide_modes'] ?? null) ? ' style="display: none;"' : '') . '>' . "\n";
			foreach ($modes as $key => $val) {
				if (!isset($allowed_modes[$key])) {
					continue;
				}
				$editor .= '<button type="button" class="btn vik-contentbuilder-switcher-btn' . ($default_mode == $key ? ' vik-contentbuilder-switcher-btn-active' : '') . '" data-switch="' . $key . '" onclick="VikContentBuilder.switchMode(this);">' . $val . '</button>';
			}
			$editor .= "\t" . '</div>' . "\n";
		}
		$editor .= "\t" . '<div class="vik-contentbuilder-inner">' . "\n";
		foreach ($modes as $key => $val) {
			if ($key == 'text') {
				// if text is not the default mode, the text-area will be always hid through the style attribute
				$editor .= "\t\t" . '<textarea name="' . $name . '" data-switch="text" ' . implode(' ', $ta_attributes) . '>' . $safe_value . '</textarea>' . "\n";
			} elseif ($key == 'visual') {
				$editor .= "\t\t" . '<div class="vik-contentbuilder-container" data-switch="visual" style="' . ($default_mode != 'visual' ? 'display: none;' : '') . '">' . "\n";
				$editor .= "\t\t\t" . '<div class="vik-contentbuilder-editor-wrap">' . "\n";
				$editor .= "\t\t\t\t" . $html_visual_editor . "\n";
				$editor .= "\t\t\t" . '</div>' . "\n";
				$editor .= "\t\t" . '</div>' . "\n";
			} elseif ($key == 'modal-visual') {
				$editor .= "\t\t" . '<div class="vik-contentbuilder-modal-container" data-switch="modal-visual" data-container="' . (isset($modes['visual']) ? 'visual' : 'modal-visual') . '" style="display: none;">' . "\n";
				$editor .= "\t\t\t" . '<div class="vik-contentbuilder-editor-wrap">' . "\n";
				$editor .= "\t\t\t\t" . (!isset($modes['visual']) ? $html_visual_editor : '') . "\n";
				$editor .= "\t\t\t" . '</div>' . "\n";
				$editor .= "\t\t" . '</div>' . "\n";
			}
		}
		$editor .= "\t" . '</div>' . "\n";
		$editor .= '</div>' . "\n";
		$editor .= "\n";

		// default prompt for Gen-AI
		$gen_ai_use_prompt = $opts['gen_ai']['prompt'] ?? null;
		if (!$gen_ai_use_prompt && ($opts['gen_ai']['customer'] ?? [])) {
			$guest_name = trim(($opts['gen_ai']['customer']['first_name'] ?? '') . ' ' . ($opts['gen_ai']['customer']['last_name'] ?? ''));
			if ($guest_name) {
				// set proper prompt message with the guest name
				$gen_ai_use_prompt = JText::sprintf('VBO_AI_DISC_WRITER_FN_TEXT_GEN_MESS_EXA', $guest_name);
				if (!empty($opts['gen_ai']['booking']['lang']) && JFactory::getLanguage()->getTag() != $opts['gen_ai']['booking']['lang']) {
					// write the message in the guest language
					$guest_lang = $opts['gen_ai']['booking']['lang'];
					$known_langs = $this->getKnownLanguages();
					if ($known_langs[$guest_lang]['nativeName'] ?? '') {
						$guest_lang = $known_langs[$guest_lang]['nativeName'];
					}
					$gen_ai_use_prompt .= ' ' . JText::sprintf('VBO_AI_GEN_MESS_LANG', $guest_lang);
				}
			}
		}

		if (!$gen_ai_use_prompt && !strcasecmp(($opts['gen_ai']['environment'] ?? ''), 'cron')) {
			// use default prompt for cron messages
			$gen_ai_use_prompt = JText::translate('VBO_AITOOL_WRITER_CRON_DEF_PROMPT');
		} elseif (!$gen_ai_use_prompt && !strcasecmp(($opts['gen_ai']['environment'] ?? ''), 'taskmanager')) {
			// use default prompt for the task manager
			$gen_ai_use_prompt = JText::translate('VBO_AITOOL_WRITER_TM_DEF_PROMPT');
		}

		if ($gen_ai_use_prompt && ($opts['gen_ai']['placeholders'] ?? 0) && $btns) {
			// add prompt text for using the placeholder tags
			$placeholders = array_filter($btns, function($tag) {
				// remove conditional text rule tags
				return !preg_match('/^\{condition\:\s?.+$/i', $tag);
			});
			if ($placeholders) {
				$gen_ai_use_prompt .= ' ' . JText::translate('VBO_AITOOL_WRITER_USE_PLACEHOLDERS') . "\n" . implode(', ', $placeholders);
			}
		}

		// sanitize default prompt
		$gen_ai_use_prompt = json_encode((string) $gen_ai_use_prompt);

		// add JS script to HTML content
		$toast_icon = VikBookingIcons::i('minus-square');
		$envelope_icon = VikBookingIcons::i('envelope');
		$booking_icon = VikBookingIcons::i('address-card');
		$preview_lbl = htmlspecialchars(JText::translate('VBOPREVIEW'));
		$booking_lbl = htmlspecialchars(JText::translate('VBDASHBOOKINGID'));
		$editor .= <<<HTML
<script>
jQuery(function() {

	const message_preview_fn = (content, bid) => {
		let use_bid = bid || (typeof window['vbo_current_bid'] !== 'undefined' ? window['vbo_current_bid'] : null);
		VBOCore.doAjax('$ajax_preview_mess', {
			content: content,
			bid: use_bid,
		}, (resp) => {
			var pop_win = window.open('', '', 'width=800, height=600, scrollbars=yes');
			pop_win.document.body.innerHTML = resp[0];
		}, (err) => {
			console.log(err);
			alert(err.responseText);
		});
	};

	var vbo_toast_mailwrapper = null;

	var visual_editor_handlers = {
		specialtags: function(tag) {
			if (tag) {
				var cursorPosition = this.quill.getSelection().index;
				this.quill.insertText(cursorPosition, tag, 'specialtag', 'vbo-editor-hl-specialtag');
				cursorPosition += tag.length + 1;
				this.quill.setSelection(cursorPosition, 'silent');
				this.quill.insertText(cursorPosition, ' ');
				this.quill.setSelection(cursorPosition + 1, 'silent');
				this.quill.deleteText(cursorPosition - 1, 1);
			}
		},
		image: function(clicked) {
			var img_handler = new VikContentBuilderImageHandler(this.quill);
			img_handler.setEndpoint('$upload_endpoint').present();
		},
		mailwrapper: function(clicked) {
			var range = this.quill.getSelection(true);
			this.quill.insertText(range.index, "\\n", 'user');
			this.quill.insertEmbed(range.index + 1, 'mailwrapper', true, 'user');
			this.quill.setSelection(range.index + 2, 'silent');
			if (!vbo_toast_mailwrapper) {
				vbo_toast_mailwrapper = 1;
				VBOToast.enqueue(new VBOToastMessage({
					title:  Joomla.JText._('VBO_CONT_WRAPPER'),
					body:   Joomla.JText._('VBO_CONT_WRAPPER_HELP'),
					icon:   '$toast_icon',
					delay:  {
						min: 6000,
						max: 20000,
						tolerance: 4000,
					},
					action: () => {
						VBOToast.dispose(true);
					}
				}));
			}
		},
		preview: function(clicked) {
			let content = this.quill.root.innerHTML;
			try {
				let preview_btn = this.quill.container.closest('.vik-contentbuilder-editor-wrap').querySelector('button.ql-preview');
				jQuery(preview_btn).vboContextMenu({
					placement: 'bottom-right',
					buttons: [
						{
							icon: '$envelope_icon',
							text: '$preview_lbl',
							separator: true,
							action: (root, config) => {
								message_preview_fn.call(clicked, content);
								setTimeout(() => {
									jQuery(preview_btn).vboContextMenu('destroy');
								}, 500);
							},
						},
						{
							icon: '$booking_icon',
							text: '$booking_lbl',
							action: (root, config) => {
								let bid = prompt('$preview_lbl - $booking_lbl');
								message_preview_fn.call(clicked, content, bid);
								setTimeout(() => {
									jQuery(preview_btn).vboContextMenu('destroy');
								}, 500);
							},
						},
					],
				});
				jQuery(preview_btn).vboContextMenu('show');
			} catch(e) {
				// fallback on regular preview
				console.error(e);
				message_preview_fn.call(clicked, content);
			}
		},
		homelogo: function(clicked) {
			VBOCore.doAjax('$ajax_logo_url', {}, (resp) => {
				try {
					this.quill.insertEmbed(this.quill.getSelection().index, 'image', resp.url);
				} catch(e) {
					alert('Generic logo image error');
				}
			}, (err) => {
				console.log(err);
				alert(err.responseText);
			});
		},
		genai: function(clicked) {
			let visualEditor = this.quill;
			let cursorPosition = visualEditor.getSelection().index;

			const vboVisualEditorGenaiGetContentFn = (e) => {
				// check if any data was sent within the event
				if (e && e.detail?.content) {
					// set the generated and picked content to editor
					let ai_content = e.detail.content;
					if ((e.detail?.type || '') == 'html') {
						// convert HTML content into Delta for the Visual Editor
						let delta = visualEditor.clipboard.convert(ai_content);
						// set (replace) editor HTML content (2nd argument "source" should be "api" so that the "text-change" event will fire)
						visualEditor.setContents(delta, 'api');
					} else {
						// default to plain text
						visualEditor.insertText(cursorPosition, ai_content, 'user');
						cursorPosition += ai_content.length + 1;
						visualEditor.setSelection(cursorPosition, 'silent');
					}
				}
			};

			// register event to receive the Gen-AI content picked
			document.addEventListener('vbo-ai-tools-writer-content-picked', vboVisualEditorGenaiGetContentFn);

			// register listener to un-register the needed events
			document.addEventListener('vbo-ai-tools-writer-content-dismissed', function vboVisualEditorGenaiDismissedFn(e) {
				// un-register the events asynchronously to avoid unexpected behaviors
				setTimeout(() => {
					// make sure the same event will not trigger again
					e.target.removeEventListener(e.type, vboVisualEditorGenaiDismissedFn);

					// unregister the event for getting content data
					document.removeEventListener('vbo-ai-tools-writer-content-picked', vboVisualEditorGenaiGetContentFn);
				});
			});

			// default prompt
			let writer_prompt = $gen_ai_use_prompt;

			// render modal widget
			VBOCore.handleDisplayWidgetNotification({
				widget_id: 'aitools',
			}, {
				scope: 'writer',
				prompt: {
					message: (writer_prompt || Joomla.JText._('VBO_AITOOL_WRITER_DEF_PROMPT')),
					submit: 0,
				},
				modal_options: {
					suffix: 'vbo-ai-tools-writer-inner',
					title: Joomla.JText._('VBO_GEN_CONTENT') + ' - ' + Joomla.JText._('VBO_AI_LABEL_DEF'),
					lock_scroll: false,
					enlargeable: false,
					minimizeable: false,
					dismiss_event: 'vbo-ai-tools-writer-content-picked',
					dismissed_event: 'vbo-ai-tools-writer-content-dismissed',
				},
			});
		}
	};
	var visual_editor_ext_opts = $editor_opts_str;
	visual_editor_ext_opts['modules']['toolbar']['handlers'] = visual_editor_handlers;
	var visual_editor = new Quill('#$editor_id', visual_editor_ext_opts);
	var editor_content = jQuery('textarea#{$attrs['id']}').val();
	if (editor_content && editor_content.length) {
		if (editor_content.indexOf('<') >= 0) {
			// replace special tags
			editor_content = editor_content.replace(/([^"']|^)({(?:condition: ?)?[a-z0-9_]{5,64}})([^"']|$)/g, function(match, before, tag, after) {
				return before + '<strong class="vbo-editor-hl-specialtag">' + tag + '</strong>' + after;
			});
			var editor_delta = visual_editor.clipboard.convert(editor_content);
			// set editor HTML content
			visual_editor.setContents(editor_delta, 'api');
		} else {
			// set text content
			visual_editor.setText(editor_content, 'silent');
		}
	}
	visual_editor.on('text-change', function(delta, source) {
		jQuery('textarea#{$attrs['id']}').val(visual_editor.root.innerHTML);
	});
	jQuery('textarea#{$attrs['id']}').on('change', function() {
		var editor_content = jQuery(this).val();
		var editor_delta = visual_editor.clipboard.convert(editor_content);
		visual_editor.setContents(editor_delta, 'silent');
	});
	try {
		// push editor instance to the pool
		VikContentBuilder.pushEditor(visual_editor);
	} catch(e) {
		console.error('Could not push new visual editor instance', e);
	}
	setTimeout(() => {
		jQuery('.vik-contentbuilder-switcher-btn-active').trigger('click');
	});
});
</script>
HTML;

		// return the necessary HTML string to be displayed
		return $editor;
	}

	/**
	 * Loads the necessary assets to render context menus.
	 * 
	 * @return  void
	 * 
	 * @since   1.16.0 (J) - 1.6.0 (WP)
	 */
	public function loadContextMenuAssets()
	{
		static $loaded = null;

		if ($loaded) {
			return;
		}

		$dark_mode = 'null';

		if (JFactory::getApplication()->isClient('administrator')) {
			// get appearance preference
			$app_pref = VikBooking::getAppearancePref();

			if ($app_pref == 'light') {
				$dark_mode = 'false';
			} elseif ($app_pref == 'dark') {
				$dark_mode = 'true';
			}
		}

		$this->addScript(VBO_ADMIN_URI . 'resources/contextmenu.js');

		$doc = JFactory::getDocument();

		$doc->addStyleSheet(VBO_ADMIN_URI . 'resources/contextmenu.css');
		$doc->addScriptDeclaration(
<<<JS
(function($) {
	'use strict';

	$(function() {
		$.vboContextMenu.defaults.darkMode = {$dark_mode};
		$.vboContextMenu.defaults.class    = 'vbo-dropdown-cxmenu';
	});
})(jQuery);
JS
		);

		$loaded = 1;
	}

	/**
	 * Loads the assets necessary to render a phone input field.
	 * 
	 * @return 	void
	 * 
	 * @since 	1.16.0 (J) - 1.6.0 (WP)
	 */
	public function loadPhoneInputFieldAssets()
	{
		static $loaded = null;

		if ($loaded) {
			return;
		}

		$loaded = 1;

		$document = JFactory::getDocument();

		$document->addStyleSheet(VBO_SITE_URI . 'resources/intlTelInput.css');
		$document->addScript(VBO_SITE_URI . 'resources/intlTelInput.js');
	}
}
