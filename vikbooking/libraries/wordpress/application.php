<?php
/** 
 * @package   	VikBooking - Libraries
 * @subpackage 	wordpress
 * @author    	E4J s.r.l.
 * @copyright 	Copyright (C) 2018 E4J s.r.l. All Rights Reserved.
 * @license  	http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * @link 		https://vikwp.com
 */

// No direct access
defined('ABSPATH') or die('No script kiddies please!');

// do nothing if the class already exists
if (!class_exists('VikApplication'))
{
	VikBookingLoader::import('wordpress.version');
	
	/**
	 * Helper class to adapt the application to the requirements 
	 * of the installed Wordpress version.
	 *
	 * @since  	1.0
	 */
	class VikApplication
	{
		/**
		 * The instance to handle the singleton.
		 *
		 * @var self
		 */
		protected static $instance = null;

		/**
		 * Class constructor.
		 *
		 * @param 	integer  $id 	The identifier of the wordpress version (@unused).
		 */
		public function __construct($id = null)
		{

		}

		/**
		 * Used to keep a single instance of the object.
		 *
		 * @return 	self 	The class singleton.
		 */
		public static function getInstance()
		{
			if (static::$instance === null)
			{
				static::$instance = new static();
			}

			return static::$instance;
		}
		
		/**
		 * Backward compatibility for admin list <table> class.
		 *
		 * @return 	string 	The class selector to use.
		 */
		public function getAdminTableClass()
		{
			return 'wp-list-table widefat fixed striped';
		}
		
		/**
		 * Backward compatibility for admin list <table> head opening.
		 *
		 * @return 	string 	The <thead> tag to use.
		 */
		public function openTableHead()
		{
			return '<thead>';
		}
		
		/**
		 * Backward compatibility for admin list <table> head closing.
		 *
		 * @return 	string 	The </thead> tag to use.
		 */
		public function closeTableHead()
		{
			return '</thead>';
		}
		
		/**
		 * Backward compatibility for admin list <th> class.
		 *
		 * @param 	string 	$h_align 	The additional class to use for horizontal alignment.
		 *								Accepted rules should be: left, center or right.
		 *
		 * @return 	string 	The class selector to use.
		 */
		public function getAdminThClass($h_align = 'center')
		{
			return 'manage-column ' . $h_align;
		}
		
		/**
		 * Backward compatibility for admin list checkAll JS event.
		 *
		 * @param 	integer  $count  The total count of rows in the table.	
		 *
		 * @return 	string 	The check all checkbox input to use.
		 */
		public function getAdminToggle($count)
		{
			return '<input type="checkbox" onclick="Joomla.checkAll(this)" value="" name="checkall-toggle" />';
		}
		
		/**
		 * Backward compatibility for admin list isChecked JS event.
		 *
		 * @return 	string 	The JS function to use.
		 */
		public function checkboxOnClick()
		{
			return 'Joomla.isChecked(this.checked);';
		}

		/**
		 * Includes a script framework.
		 *
		 * @param 	string 	$fw  The framework name.
		 *
		 * @return 	void
		 */
		public function loadFramework($fw)
		{
			JHtml::fetch($fw);
		}

		/**
		 * Includes a script URI.
		 *
		 * @param 	string 	$uri  The script URI.
		 *
		 * @return 	void
		 */
		public function addScript($uri)
		{
			JHtml::fetch('script', $uri);
		}

		/**
		 * Helper method to send e-mails.
		 *
		 * @param 	string 		$from_address	The e-mail address of the sender.
		 * @param 	string 		$from_name 		The name of the sender.
		 * @param 	string 		$to 			The e-mail address of the receiver.
		 * @param 	string 		$reply_address 	The reply to e-mail address.
		 * @param 	string 		$subject 		The subject of the e-mail.
		 * @param 	string 		$hmess 			The body of the e-mail (HTML is supported).
		 * @param 	array 		$attachments 	The list of the attachments to include.
		 * @param 	boolean 	$is_html 		True to support HTML body, otherwise false for plain text.
		 * @param 	string 		$encoding 		The encoding to use.
		 *
		 * @return 	boolean 	True if the e-mail was sent successfully, otherwise false.
		 */
		public function sendMail($from_address, $from_name, $to, $reply_address, $subject, $hmess, $attachments = null, $is_html = true, $encoding = 'base64')
		{
			// $subject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
			
			if ($is_html)
			{
				$hmess = "<html>\n<head><meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\"></head>\n<body>{$hmess}</body>\n</html>";
			}
			
			$mailer = JFactory::getMailer();
			$sender = array($from_address, $from_name);
			$mailer->setSender($sender);
			$mailer->addRecipient($to);
			$mailer->addReplyTo($reply_address);
			$mailer->setSubject($subject);
			$mailer->setBody($hmess);
			$mailer->isHTML($is_html);
			$mailer->Encoding = $encoding;

			if ($attachments !== null)
			{
				if (!is_array($attachments))
				{
					$attachments = array($attachments);
				}
				
				foreach ($attachments as $attach)
				{
					if (!empty($attach) && file_exists($attach))
					{
						$mailer->addAttachment($attach);
					}
				}
			}

			return $mailer->Send();
		}
		
		/**
		 * Backward compatibility for punycode conversion.
		 *
		 * @param 	string 	$mail 	The e-mail to convert in punycode.
		 *
		 * @return 	string 	The punycode conversion of the e-mail.
		 */
		public function emailToPunycode($email = '')
		{
			return $email;
		}
		
		/**
		 * Helper method to build a tiny YES/NO radio button.
		 *
		 * @param 	string 		$name 		The name of the input.
		 * @param 	object 		$elem_1 	The first input object.
		 * @param 	object 		$elem_2 	The second input object.
		 * @param 	boolean 	wrapped 	True if the input is wrapped in a control class, otherwise false..
		 *
		 * @return 	string 		The html to display.
		 */
		public function radioYesNo($name, $elem_1, $elem_2, $wrapped = true, $layout = null)
		{
			/**
			 * @todo
			 */	

			return '';
		}
		
		/**
		 * Backward compatibility for fieldset opening.
		 *
		 * @param 	string 	$legend  The title of the fieldset.
		 * @param 	string 	$class 	 The class attribute for the fieldset.
		 * @param 	string 	$id 	 The ID attribute for the fieldset.
		 *
		 * @return 	string 	The html to display.
		 */
		public function openFieldset($legend, $class = '', $id = '')
		{
			$data = array();
			$data['name'] 	= $legend;
			$data['class'] 	= $class;
			$data['id']     = $id;

			return JHtml::fetch('layoutfile', 'html.form.fieldset.open')->render($data);
		}
		
		/**
		 * Backward compatibility for fieldset closing.
		 *
		 * @return 	string 	The html to display.
		 */
		public function closeFieldset()
		{
			return JHtml::fetch('layoutfile', 'html.form.fieldset.close')->render();
		}

		/**
		 * Backward compatibility for empty fieldset opening.
		 *
		 * @param 	string 	$class 	An additional class to use for the fieldset.
		 * @param 	string 	$id 	The ID attribute for the fieldset.
		 *
		 * @return 	string 	The html to display.
		 */
		public function openEmptyFieldset($class = '', $id = '')
		{
			return $this->openFieldset('', $class, $id);
		}
		
		/**
		 * Backward compatibility for empty fieldset opening.
		 *
		 * @return 	string 	The html to display.
		 */
		public function closeEmptyFieldset()
		{
			return $this->closeFieldset();
		}
		
		/**
		 * Backward compatibility for control opening.
		 *
		 * @param 	string 	$label 	The label of the control field.
		 * @param 	string 	$class 	The class of the control field.
		 * @param 	array 	$attr 	The additional attributes to add.
		 *
		 * @return 	string 	The html to display.
		 */
		public function openControl($label, $class = '', $attr = array())
		{
			$data = array();

			foreach ($attr as $k => $v)
			{
				$data[$k] = $v;
			}

			$data['label'] = $label;
			$data['class'] = $class;

			return JHtml::fetch('layoutfile', 'html.form.control.open')->render($data);
		}
		
		/**
		 * Backward compatibility for control closing.
		 *
		 * @return 	string 	The html to display.
		 */
		public function closeControl()
		{
			return JHtml::fetch('layoutfile', 'html.form.control.close')->render();
		}
		
		/**
		 * Returns the codemirror editor in 3.x, otherwise a simple textarea.
		 *
		 * @param 	string 	$name 	The name of the textarea.
		 * @param 	string 	$value 	The value of the textarea.
		 *
		 * @return 	string 	The html to display.
		 */
		public function getCodeMirror($name, $value)
		{
			return '<textarea name="' . $name . '" style="width: 100%;height: 520px;">' . $value . '</textarea>';
		}
		
		/**
		 * Backward compatibility for Bootstrap tabset opening.
		 *
		 * @param 	string 	$group 	The group of the tabset.
		 * @param 	string 	$attr 	The attributes to use.
		 *
		 * @return 	string 	The html to display.
		 */
		public function bootStartTabSet($group, $attr = array())
		{
			/**
			 * @todo
			 */

			return '';
		}
		
		/**
		 * Backward compatibility for Bootstrap tabset closing.
		 *
		 * @return 	string 	The html to display.
		 */
		public function bootEndTabSet()
		{
			/**
			 * @todo
			 */

			return '';
		}
		
		/**
		 * Backward compatibility for Bootstrap add tab.
		 *
		 * @param 	string 	$group 	The tabset parent group.
		 * @param 	string 	$id 	The id of the tab.
		 * @param 	string 	$label 	The title of the tab.
		 *
		 * @return 	string 	The html to display.
		 */
		public function bootAddTab($group, $id, $label)
		{
			/**
			 * @todo
			 */

			return '';
		}
		
		/**
		 * Backward compatibility for Bootstrap end tab.
		 *
		 * @return 	string 	The html to display.
		 */
		public function bootEndTab()
		{
			/**
			 * @todo
			 */

			return '';
		}
		
		/**
		 * Backward compatibility for Bootstrap open modal JS event.
		 *
		 * @param 	string 	$onclose 	The javascript function to call on close event.
		 *
		 * @return 	string 	The javascript function.
		 */
		public function bootOpenModalJS($onclose = '')
		{
			/**
			 * @todo
			 */

			return '';
		}
		
		/**
		 * Backward compatibility for Bootstrap dismiss modal JS event.
		 *
		 * @param 	string 	$selector 	The selector to identify the modal box.
		 *
		 * @return 	string 	The javascript function.
		 */
		public function bootDismissModalJS($selector)
		{
			/**
			 * @todo
			 */

			return '';
		}

		/**
		 * Returns the HTML used to render a Bootstrap modal.
		 *
		 * @param 	string 	$id 		The modal ID.
		 * @param 	string 	$title 		The modal title.
		 * @param 	string  $body 		The modal body (if static HTML).
		 * @param 	mixed 	$options 	An array of attributes or the inline style string.
		 *
		 * @return 	string 	The modal HTML.
		 */
		public function getJModalHtml($id, $title, $body = '', $options = null)
		{
			if (is_array($options))
			{
				$width 	= isset($options['width']) 	? abs($options['width']) 	: 90;
				$height = isset($options['height']) ? abs($options['height']) 	: 80;
				$left 	= isset($options['left']) 	? abs($options['left']) 	: $width / 2;

				$style = "width:$width%;height:$height%;margin-left:-$left%;";

				if (isset($options['top']))
				{
					if ($options['top'] === true)
					{
						$top = (100 - $height) / 2;
					}
					else
					{
						$top = $options['top'];
					}

					$style .= "top:$top%;";
				}
			}
			else if (is_string($options))
			{
				$style = $options;
			}
			else
			{
				$style = "width:90%;height:80%;margin-left:-45%";
			}

			$options = array();
			$options['id'] 		= $id;
			$options['title'] 	= $title;
			$options['body'] 	= $body;
			$options['style']	= $style;

			$layout = new JLayoutFile('html.plugins.modal', null, array('component' => 'com_vikbooking'));

			return $layout->render($options);
		}

		/**
		 * Adds javascript support for Bootstrap popovers.
		 *
		 * @param 	string 	$selector   Selector for the popover.
		 * @param 	array 	$options     An array of options for the popover.
		 * 					Options for the popover can be:
		 * 						animation  boolean          apply a css fade transition to the popover
		 *                      html       boolean          Insert HTML into the popover. If false, jQuery's text method will be used to insert
		 *                                                  content into the dom.
		 *                      placement  string|function  how to position the popover - top | bottom | left | right
		 *                      selector   string           If a selector is provided, popover objects will be delegated to the specified targets.
		 *                      trigger    string           how popover is triggered - hover | focus | manual
		 *                      title      string|function  default title value if `title` tag isn't present
		 *                      content    string|function  default content value if `data-content` attribute isn't present
		 *                      delay      number|object    delay showing and hiding the popover (ms) - does not apply to manual trigger type
		 *                                                  If a number is supplied, delay is applied to both hide/show
		 *                                                  Object structure is: delay: { show: 500, hide: 100 }
		 *                      container  string|boolean   Appends the popover to a specific element: { container: 'body' }
		 */
		public function attachPopover($selector = '.wpPopover', array $options = array())
		{
			static $loaded = array();

			$sign = serialize(array($selector, $options));

			if (!isset($loaded[$sign]))
			{
				$options['sanitize'] = false;
				$data = $options ? json_encode($options) : '{}';
				JFactory::getDocument()->addScriptDeclaration(
<<<JS
jQuery(document).ready(function() {
	jQuery('$selector').popover($data);
});
JS
				);

				$loaded[$sign] = 1;
			}
		}

		/**
		 * Creates a standard tag and attach a popover event.
		 * NOTE. FontAwesome framework MUST be loaded in order to work.
		 *
		 * @param 	array 	$options  An array of options for the popover.
		 *
		 * @return 	string 	The popover HTML.
		 *
		 * @uses 	_popover()
		 * @see 	attachPopover() for further details about options keys.
		 */
		public function createPopover(array $options = array())
		{
			$icon = isset($options['icon_class']) ? $options['icon_class'] : 'fas fa-question-circle';

			$icon = isset($options['icon']) ? 'fas fa-'.$options['icon'] : $icon;

			$template = "<i class=\"{$icon} wp-quest-popover\" {popover}></i>";

			return $this->_popover($template, $options);
		}

		/**
		 * Creates a text span and attach a popover event.
		 *
		 * @param 	array 	$options  An array of options for the popover.
		 *
		 * @return 	string 	The popover HTML.
		 *
		 * @uses 	_popover()
		 * @see 	attachPopover() for further details about options keys.
		 */
		public function textPopover(array $options = array())
		{
			$title 		= isset($options['title']) ? $options['title'] : '[MISSING TITLE]';
			$template 	= "<span class=\"inline-popover wp-quest-popover\" {popover}>{$title}</span>";

			return $this->_popover($template, $options);
		}

		/**
		 * Creates a popover using the provided template.
		 *
		 * @param 	string 	$template 	The popover template.
		 * @param 	array 	$options    An array of options for the popover.
		 *
		 * @return 	string 	The popover HTML.
		 *
		 * @see 	attachPopover() for further details about options keys.
		 */
		protected function _popover($template, array $options)
		{
			$layout = new JLayoutFile('html.plugins.popover', null, array('component' => 'com_vikbooking'));

			$options['html'] 		= true;
			$options['title']		= isset($options['title'])	 	? $options['title']		: '';
			$options['content'] 	= isset($options['content']) 	? $options['content']  	: '';
			$options['trigger'] 	= isset($options['trigger']) 	? $options['trigger']  	: 'hover focus';
			$options['placement'] 	= isset($options['placement']) 	? $options['placement'] : 'right';
			$options['template']	= isset($options['template'])	? $options['template']	: $layout->render();

			// attach an empty array option so that the data will be recovered 
			// directly from the tag during the runtime
			$this->attachPopover(".wp-quest-popover", array());

			$attr = '';
			foreach ($options as $k => $v)
			{
				$attr .= "data-{$k}=\"" . str_replace('"', '&quot;', $v) . "\" ";
			}

			return str_replace('{popover}', $attr, $template);
		}

		/**
		 * Return the WP date format specs.
		 *
		 * @param 	string 	$format 	The format to use.
		 *
		 * @return 	string 	The adapted date format.
		 */
		public function jdateFormat($format = null)
		{
			// strip % from date format string, which was required in Joomla
			return str_replace('%', '', $format);
		}

		/**
		 * Provides support to handle the wordpress calendar across different frameworks.
		 *
		 * @param 	string 	$value 		 The date to fill.
		 * @param 	string 	$name 		 The input name.
		 * @param 	string 	$id 		 The input id attribute.
		 * @param 	string 	$format 	 The date format.
		 * @param 	array 	$attributes  Some attributes to use.
		 * 
		 * @return 	string 	The calendar field.
		 */
		public function calendar($value, $name, $id = null, $format = null, array $attributes = array())
		{
			$format = $this->jdateFormat($format);

			JHtml::fetch('behavior.calendar');

			return JHtml::fetch('calendar', $value, $name, $id, $format, $attributes);
		}

		/**
		 * Returns a masked e-mail address. The e-mail are masked using 
		 * a technique to encode the bytes in hexadecimal representation.
		 * The chunk of the masked e-mail will be also encoded to be HTML readable.
		 *
		 * @param 	string 	 $email 	The e-mail to mask.
		 * @param 	boolean  $reverse 	True to reverse the e-mail address.
		 * 								Only if the e-mail is not contained into an attribute.
		 *
		 * @return 	string 	 The masked e-mail address.
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
		 * @param 	string 	 $email 	The e-mail address to mask.
		 * @param 	boolean  $mail_to 	True if the address should be wrapped
		 * 								within a "mailto" link.
		 *
		 * @return 	string 	 The HTML tag containing the masked address.
		 *
		 * @uses 	maskMail()
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
		 * Returns the list of all the installed languages.
		 *
		 * @return 	array 	The installed languages.
		 */
		public function getKnownLanguages()
		{
			/**
			 * Use JLanguage::getKnownLanguages() native method.
			 *
			 * @since 1.1.4
			 */
			return JLanguage::getKnownLanguages();
		}

		/**
		 * Returns the HTML code to display a toggle button in iOS style through a checkbox element.
		 * 
		 * @param 	string 	$name 		 the checkbox element name.
		 * @param 	string 	$label_yes 	 label for the checked status.
		 * @param 	string 	$label_no 	 label for the un-checked status.
		 * @param 	string 	$cur_value 	 the default checkbox value state.
		 * @param 	string 	$yes_value 	 the checkbox value when checked.
		 * @param 	string 	$no_value 	 the checkbox value when un-checked (unsupported).
		 * @param 	string 	$onclick 	 optional onclick attribute to fire a JS callback.
		 * @param 	array 	$class_list  optional list of additional CSS classes to add ('blue', 'orange', 'red', 'gold', 'purple').
		 * 
		 * 
		 * @return 	string  The HTML code to render the radio buttons.
		 * 
		 * @since 	1.16.9 (J) - 1.6.9 (WP)  introduced argument $class_list.
		 */
		public function printYesNoButtons($name, $label_yes, $label_no, $cur_value = '1', $yes_value = '1', $no_value = '0', $onclick = '', $class_list = [])
		{
			$html = '';
			$id_yes = $name . '-on';
			$id_no = $name . '-off';

			/**
			 * New toggle button in iOS style.
			 * 
			 * @since 	1.3.0
			 */
			$show_labels = ($label_yes != JText::translate('JYES') && $label_yes != JText::translate('VBYES'));
			$html = '<span class="vik-iostoggle-wrap vbo-iostoggle-wrap' . ($class_list ? ' ' . implode(' ', $class_list) : '') . '">
				<input type="checkbox" name="' . $name . '" class="vik-iostoggle-elem vbo-iostoggle-elem" id="' . $id_yes . '" value="' . $yes_value . '"' . (!empty($onclick) ? ' onclick="' . $onclick . '"' : '') . ($cur_value === $yes_value ? ' checked' : '').'>
				<label for="' . $id_yes . '">' . ($show_labels ? '<span class="vik-iostoggle-lbls vbo-iostoggle-lbls" data-on="' . addslashes($label_yes) . '" data-off="' . addslashes($label_no) . '"></span>' : '') . '</label>
			</span>';

			return $html;
		}

		/**
		 * Returns the HTML code for displaying an input field for a phone number.
		 * Adds to the documents the necessary style, script and JS code.
		 * 
		 * @param 	array 	$attrs 			array of attributes for the input field.
		 * @param 	array 	$opts 			array of options for the input field.
		 * @param 	bool 	$load_assets 	whether to load the CSS/JS assets.
		 * 
		 * @return 	string 					the plain HTML code to be printed for the input field.
		 * 
		 * @since 	1.3.0
		 * @since 	1.6.0 	added argument $load_assets
		 */
		public function printPhoneInputField($attrs = array(), $opts = array(), $load_assets = true)
		{
			if (!empty($attrs['id'])) {
				$selector = $attrs['id'];
			} elseif (!empty($attrs['name'])) {
				$selector = $attrs['name'];
			} else {
				$selector = time() . rand();
			}

			// input default's attributes
			$default_attrs = array(
				'type' => 'tel',
				'name' => (!empty($attrs['name']) ? $attrs['name'] : $selector),
				'id' => $selector,
				'value' => '',
				'size' => '30',
			);

			// merge arguments attributes with the default ones
			$final_attrs = array_merge($default_attrs, $attrs);

			// format attributes
			$attrs_cont = array();
			foreach ($final_attrs as $k => $v) {
				array_push($attrs_cont, "{$k}=\"{$v}\"");
			}

			/**
			 * Get the preferred countries with correct ordering.
			 * 
			 * @wponly 	if multiple plugins are installed, we may have this method defined by another plugin.
			 * 
			 * @since 	1.3.11
			 */
			$preferred_countries = array();
			$plugin_name = str_replace('com_', '', JFactory::getApplication()->input->getString('option', ''));
			if (!empty($plugin_name) && class_exists($plugin_name) && method_exists($plugin_name, 'preferredCountriesOrdering')) {
				$preferred_countries = $plugin_name::preferredCountriesOrdering();
			} elseif (class_exists('VikBooking')) {
				$preferred_countries = VikBooking::preferredCountriesOrdering();
			}
			//

			// build default config object properties
			$default_opts = array(
				'nationalMode' => true,
				'preferredCountries' => $preferred_countries,
				'formatOnDisplay' => true,
				'utilsScript' => VBO_SITE_URI . 'resources/intlTelInput_utils.js',
				/**
				 * This option is not a valid property of intlTelInput plugin.
				 * If set to true, when the input gets blurred, the full phone
				 * number inclusive of prefix will be immediately replaced and
				 * set into the selector input field. Defaults to false when
				 * the form containing the input field gets submitted. It should
				 * be set to true when the form does not get submitted.
				 */
				'fullNumberOnBlur' => false,
			);
			
			// merge config object with user's specified properties
			$final_opts = array_merge($default_opts, $opts);
			$data = json_encode($final_opts);

			// apply set full number on blur
			$full_number_on_blur = (int)$final_opts['fullNumberOnBlur'];

			$document = JFactory::getDocument();

			if ($load_assets) {
				$document->addStyleSheet(VBO_SITE_URI . 'resources/intlTelInput.css');
				$document->addScript(VBO_SITE_URI . 'resources/intlTelInput.js');
			}

			$render_script = <<<JS
jQuery(function() {
	jQuery('#$selector').intlTelInput($data);
	jQuery('#$selector').on('blur', function() {
		// set or format phone number on blur
		var cur_phone = jQuery('#$selector').intlTelInput('getNumber');
		if (!cur_phone || !cur_phone.length) {
			return;
		}
		if ($full_number_on_blur) {
			jQuery('#$selector').val(cur_phone);
		} else {
			jQuery('#$selector').intlTelInput('setNumber', cur_phone);
		}
	});
	jQuery('#$selector').closest('form').on('submit', function() {
		// always make sure the input field contains the complete phone number
		jQuery('#$selector').val(jQuery('#$selector').intlTelInput('getNumber'));
	});
	jQuery('#$selector').on('vboupdatephonenumber', function(e, country) {
		if (country && country.length == 2 && !jQuery('#$selector').val().length) {
			jQuery('#$selector').intlTelInput('setCountry', country.toLowerCase());
		}
		// make sure the input field contains the complete phone number
		jQuery('#$selector').val(jQuery('#$selector').intlTelInput('getNumber'));
	});
});
JS;

			$node_elem = '<input ' . implode(' ', $attrs_cont) . ' />';

			if ($load_assets) {
				$document->addScriptDeclaration($render_script);
			} else {
				$node_elem .= "<script>{$render_script}</script>";
			}

			return $node_elem;
		}
	}
}
