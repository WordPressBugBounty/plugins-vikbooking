<?php
/** 
 * @package     VikWP - Libraries
 * @subpackage  adapter.module
 * @author      E4J s.r.l.
 * @copyright   Copyright (C) 2023 E4J s.r.l. All Rights Reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * @link        https://vikwp.com
 */

// No direct access
defined('ABSPATH') or die('No script kiddies please!');

JLoader::import('adapter.form.form');

/**
 * Adapter class to extend WP widget functionalities.
 *
 * @see   WP_Widget
 * @since 10.0
 */
class JWidget extends WP_Widget
{
	/**
	 * Absolute module path.
	 *
	 * @var string
	 */
	protected $_path;

	/**
	 * Internal ID.
	 *
	 * @var string
	 */
	protected $_id;

	/**
	 * Widget form data.
	 *
	 * @var JForm
	 */
	protected $_form = null;

	/**
	 * The name of the plugin that owns the module.
	 *
	 * @var string
	 */
	protected $_option = null;

	/**
	 * Whether or not the widget has been registered yet.
	 *
	 * @var   boolean
	 * @since 10.1.38
	 */
	protected $registered = false;

	/**
	 * Class constructor.
	 *
	 * @param 	string 	$path  The widget absolute path.
	 *
	 * @uses 	loadLanguage()
	 * @uses 	loadXml()
	 */
	public function __construct($path)
	{
		// widget ID
		$id = basename($path);

		$this->_path = $path;
		$this->_id 	 = $id;

		/**
		 * Extract component name from path.
		 *
		 * @since 10.1.20
		 */
		if (preg_match("/plugins[\/\\\\]([a-z0-9_]+)[\/\\\\]modules/i", $this->_path, $match))
		{
			$this->_option = end($match);
		}

		// load text domain
		$this->loadLanguage($path, $id);

		/**
		 * @note: translations will be available only from here.
		 */

		// load widget data from XML
		$data = $this->loadXml($path, $id);

		// translate widget name
		$name = JText::translate((string) $data->name);

		// build arguments
		$args = array();
		$args['description'] = JText::translate((string) $data->description);
		// $args['version']	 = $data->version;

		/**
		 * Since the widget description is displayed by escaping HTML tags,
		 * we should strip them in order to display a plain text.
		 *
		 * @since 10.1.21
		 */
		$args['description'] = strip_tags($args['description']);

		parent::__construct($id, $name, $args);

		/**
		 * Add support for jQuery in page head every time 
		 * a widget is instantiated. Proceed only in case the
		 * headers haven't been sent yet.
		 *
		 * @since 10.1.22
		 */
		if (!headers_sent())
		{
			add_filter('wp_enqueue_scripts', function() {
				wp_enqueue_script('jquery', null, [], false, false);
			});
		}
	}

	/**
	 * Front-end display of widget.
	 *
	 * @param 	array 	$args    Widget arguments.
	 * @param 	array 	$config  Saved values from database.
	 *
	 * @return 	void
	 */
	public function widget($args, $config)
	{
		// make the module helper accessible
		JLoader::import('adapter.module.helper');
		JModuleHelper::setPath($this->_path);

		$layout = $this->_path . DIRECTORY_SEPARATOR . $this->_id . '.php';

		// check if the widget owns a layout
		if (!JFile::exists($layout))
		{
			return;
		}

		// include system.js file to support JoomlaCore
		JHtml::fetch('system.js');

		/**
		 * Added support for module class suffix.
		 *
		 * @since 10.1.21
		 */
		if (!empty($config['moduleclass_sfx']))
		{
			// extract class from wrapper
			if (preg_match("/class=\"([a-z0-9_\-\s]*)\"/i", $args['before_widget'], $match))
			{
				// replace class attribute with previous classes and the custom suffix
				$args['before_widget'] = str_replace($match[0], 'class="' . $match[1] . ' ' . $config['moduleclass_sfx'] . '"', $args['before_widget']);
			}
		}
		
		// begin widget
		echo $args['before_widget'];

		// display the title if set
		if (!empty($config['title']))
		{
			echo $args['before_title'] . apply_filters('widget_title', $config['title']) . $args['after_title'];
		}

		// wrap the $config in a registry
		$params = new JObject($config);

		/**
		 * Create $module object for accessing the widget ID.
		 *
		 * @since 10.1.30
		 */
		$module = new stdClass;
		$module->id = $this->number;

		/**
		 * Plugins can manipulate the configuration of the widget at runtime.
		 * Fires before dispatching the widget in the front-end.
		 *
		 * @param 	string   $id       The widget ID (path name).
		 * @param 	JObject  &$params  The widget configuration registry.
		 *
		 * @since 	10.1.28
		 */
		do_action_ref_array('vik_widget_before_dispatch_site', array($this->_id, &$params));

		// start buffer
		ob_start();
		// include layout file
		include $layout;
		// get contents
		$html = ob_get_contents();
		// clear buffer
		ob_end_clean();

		/**
		 * Plugins can manipulate here the fetched HTML of the widget.
		 * Fires before displaying the HTML of the widget in the front-end.
		 *
		 * @param 	string  $id     The widget ID (path name).
		 * @param 	string  &$html  The HTML of the widget to display.
		 *
		 * @since   10.1.28
		 */
		do_action_ref_array('vik_widget_after_dispatch_site', array($this->_id, &$html));

		// display the widget HTML
		echo $html;

		// terminate widget
		echo $args['after_widget'];

		// print JSON configuration
		JHtml::fetch('behavior.core');

		// add support for Joomla JS variable
		JFactory::getDocument()->addScriptDeclaration(
<<<JS
if (typeof Joomla === 'undefined') {
	var Joomla = new JoomlaCore();
} else {
	// reload options
	JoomlaCore.loadOptions();
}
JS
		);
	}

	/**
	 * Loads widget text domain.
	 *
	 * @param 	string 	$path  	The widget path.
	 * @param 	string 	$id 	The domain name.
	 *
	 * @return 	void
	 */
	private function loadLanguage($path, $id)
	{
		// init language
		$lang = JFactory::getLanguage();
		
		// search for a language handler (/language/handler.php)
		$handler = $path . DIRECTORY_SEPARATOR . 'language' . DIRECTORY_SEPARATOR . 'handler.php';

		if (!is_file($handler))
		{
			/**
			 * Try also to search within "languages" folder.
			 *
			 * @since 10.1.21
			 */
			$handler = $path . DIRECTORY_SEPARATOR . 'languages' . DIRECTORY_SEPARATOR . 'handler.php';
		}

		if (is_file($handler))
		{
			// attach handler
			$lang->attachHandler($handler, $id);
		}

		/**
		 * @since 10.0.1 	It is no more needed to load the language
		 * 					file (.mo) of the widget as all the translations
		 * 					are contained within the main language file
		 * 					of the plugin.
		 */
	}

	/**
	 * Loads widget data from the XML installation file.
	 *
	 * @param 	string 	$path  	The widget path.
	 * @param 	string 	$id 	The widget name.
	 *
	 * @return 	object 	The XML data.
	 */
	private function loadXml($path, $id)
	{
		$file = $path . DIRECTORY_SEPARATOR . $id . '.xml';

		// make sure the installation file exists
		if (!is_file($file))
		{
			throw new Exception('Missing installation file [' . $id . '.xml].', 404);
		}

		// load form data
		$this->_form = JForm::getInstance($id, $file, array('client' => $this->_option));

		// get XML element
		$xml = $this->_form->getXml();

		$data = new stdClass;

		// iterate the args and assign them to the $data object
		foreach (array('name', 'description') as $k)
		{
			$data->{$k} = (string) $xml->{$k};
		}

		return $data;
	}

	/**
	 * Back-end widget form.
	 *
	 * @param 	array 	$instance 	Previously saved values from database.
	 *
	 * @return 	void
	 */
	public function form($instance)
	{
		// get form fields
		$fields = $this->_form->getFields();

		/**
		 * Add support for title field by creating a custom XML field,
		 * only if the XML of the module doesn't declare it.
		 *
		 * @since 10.1.21
		 */
		if (!$this->_form->getField('title'))
		{
			// create title field
			$title = simplexml_load_string('<field name="title" type="text" default="" label="TITLE" />');
			// push title at the beginning of the list
			array_unshift($fields, $title);
		}

		/**
		 * Filter the fields by removing useless settings.
		 *
		 * @since 10.1.31
		 */
		$fields = array_filter($fields, function($field)
		{
			// exclude field in case it starts with "loadjquery"
			return preg_match("/^loadjquery/", (string) $field->attributes()->name) == false;
		});

		// create layout file
		$file = new JLayoutFile('html.widget.fieldset.open');

		if ($this->_option)
		{
			// we found an option, add an include path to make sure layouts are accessible
			$file->addIncludePath(implode(DIRECTORY_SEPARATOR, array(WP_PLUGIN_DIR, $this->_option, 'libraries')));
		}

		// open fieldset
		echo $file->render();

		foreach ($fields as $field)
		{
			$attrs  = $field->attributes();
			$name 	= (string) $attrs->name;

			$data = array();
			$data['id'] 		 = $this->get_field_id($name);
			$data['label'] 		 = (string) $attrs->label;
			$data['description'] = (string) $attrs->description;
			$data['name']		 = $this->get_field_name($name);
			$data['required'] 	 = ((string) $attrs->required) === 'true';

			/**
			 * Open control only in case the input shouldn't be hidden.
			 *
			 * @since 10.1.21
			 */
			if ($attrs->type != 'hidden' && $attrs->type != 'spacer' && empty($attrs->hidden))
			{
				// open control
				$file->setLayoutId('html.widget.control.open');
				echo $file->render($data);
			}

			if (isset($instance[$name]))
			{
				$data['value'] = $instance[$name];
			}

			// attach module path (useful to obtain the available layouts)
			$data['modpath']  = $this->_path;
			$data['modowner'] = $this->_option;

			// obtain field class and display input layout
			echo $this->_form->renderField($field, $data);

			/**
			 * Close control only in case the input shouldn't be hidden.
			 *
			 * @since 10.1.21
			 */
			if ($attrs->type != 'hidden' && $attrs->type != 'spacer' && empty($attrs->hidden))
			{
				// close control
				$file->setLayoutId('html.widget.control.close');
				echo $file->render();
			}
		}

		// close fieldset
		$file->setLayoutId('html.widget.fieldset.close');
		echo $file->render();

		// include form scripts
		// $this->useScript();
	}

	/**
	 * Includes the scripts used by the form.
	 *
	 * @return 	void
	 */
	protected function useScript()
	{
		if (wp_doing_ajax())
		{
			return;
		}

		$document = JFactory::getDocument();

		/**
		 * Include system.js file to support JFormValidator.
		 * 
		 * Since WP 5.9, the widgets resources must be loaded through the
		 * _register_one method, which seems to be invoked on every page.
		 * So, we should load them only if we are under widgets.php.
		 */
		global $pagenow;
		if ($pagenow === 'widgets.php')
		{
			JHtml::fetch('system.js');
		}

		JHtml::fetch('formbehavior.chosen');

		static $loaded = 0;

		// load only once
		if (!$loaded)
		{
			// override getLabel() method to attach invalid
			// class to the correct form structure
			$document->addScriptDeclaration(
<<<JS
if (typeof JFormValidator !== 'undefined') {
	JFormValidator.prototype.getLabel = function(input) {
		var name = jQuery(input).attr('name');	

		if (this.labels.hasOwnProperty(name)) {
			return jQuery(this.labels[name]);
		}

		return jQuery(input).parent().find('label').first();
	}
}
JS
			);
		}

		// load form validation
		$document->addScriptDeclaration(
<<<JS
if (typeof VIK_WIDGET_SAVE_LOOKUP === 'undefined') {
	var VIK_WIDGET_SAVE_LOOKUP = {};
}

(function($) {
	$(document).on('widget-added', function(event, control) {
		registerWidgetScripts($(control).find('form'));
	});

	function registerWidgetScripts(form) {
		if (!form) {
			// if the form was not provided, find it using the widget ID (before WP 5.8)
			form = $('div[id$="{$this->id}"] form');
		}

		if (typeof JFormValidator !== 'undefined') {
			// init internal validator
			var validator = new JFormValidator(form);

			// validate fields every time the SAVE button is clicked
			form.find('input[name="savewidget"]').on('click', function(event) {
				return validator.validate();
			});
		}

		// init select2 on dropdown with multiple selection
		if (jQuery.fn.select2) {
			form.find('select[multiple]').select2({
				width: '100%'
			});
		}

		// initialize popover within the form
		if (jQuery.fn.popover) {
			form.find('.inline-popover').popover({sanitize: false, container: 'body'});
		}
	}

	$(function() {
		// If the widget is not a template, register the scripts.
		// A widget template ID always ends with "__i__"
		if (!"{$this->id}".match(/__i__$/)) {
			registerWidgetScripts();
		}

		// Attach event to the "ADD WIDGET" button
		$('.widgets-chooser-add').on('click', function(e) {
			// find widget parent of the clicked button
			var parent = this.closest('div[id$="{$this->id}"]');

			if (!parent) {
				return;
			}

			// extract ID from the template parent (exclude "__i__")
			var id = $(parent).attr('id').match(/(.*?)__i__$/);

			if (!id) {
				return;
			}

			// register scripts with a short delay to make sure the
			// template has been moved on the right side
			setTimeout(function() {
				// obtain the box that has been created
				var createdForm = $('div[id^="' + id.pop() + '"]').last();

				// find form within the box
				var _form = $(createdForm).find('form');

				// register scripts at runtime
				registerWidgetScripts(_form);
			}, 32);
		});

		// register save callback for this kind of widget only once
		if (!VIK_WIDGET_SAVE_LOOKUP.hasOwnProperty('{$this->_id}')) {
			// flag as loaded
			VIK_WIDGET_SAVE_LOOKUP['{$this->_id}'] = 1;

			// Attach event to SAVE callback
			$(document).ajaxSuccess(function(event, xhr, settings) {
				// make sure the request was used to save the widget settings
				if (!settings.data || typeof settings.data !== 'string' || settings.data.indexOf('action=save-widget') === -1) {
					// wrong request
					return;
				}

				// extract widget ID from request
				var widget_id = settings.data.match(/widget-id=([a-z0-9_-]+)(?:&|$)/i);

				// make sure this is the widget that was saved
				if (!widget_id) {
					// wrong widget
					return;
				}

				// get cleansed widget ID
				widget_id = widget_id.pop();

				// make sure the widget starts with this ID
				if (widget_id.indexOf('{$this->_id}') !== 0) {
					// wrong widget
					return;
				}

				// obtain the box that has been updated
				var updatedForm = $('div[id$="' + widget_id + '"]').find('form');

				// register scripts at runtime
				registerWidgetScripts(updatedForm);
			});
		}
	});
})(jQuery);
JS
		);
	}

	/**
	 * Sanitize widget form values as they are saved.
	 *
	 * @param 	array 	$new_instance 	Values just sent to be saved.
	 * @param 	array 	$old_instance 	Previously saved values from database.
	 *
	 * @return 	array 	Updated safe values to be saved.
	 *
	 * @since 	10.1.21
	 */
	public function update($new_instance, $old_instance)
	{
		if (!empty($new_instance['moduleclass_sfx']))
		{
			// make mod class suffix safe
			$new_instance['moduleclass_sfx'] = preg_replace("/[^a-zA-Z0-9_\-\s]+/", '', $new_instance['moduleclass_sfx']);
		}

		return $new_instance;
	}

	/**
	 * Add hooks for enqueueing assets when registering all widget instances of this widget class.
	 *
	 * @param 	integer  $number  Optional. The unique order number of this widget instance
	 *                            compared to other instances of the same class. Default -1.
	 * 
	 * @return 	void
	 * 
	 * @since 	10.1.38
	 */
	public function _register_one($number = -1)
	{
		// invoke parent
		parent::_register_one($number);

		if (!$this->registered)
		{
			// load required resources
			$this->useScript();

			// flag as already registered
			$this->registered = true;
		}		
	}

	/**
	 * Converts a WordPress widget into a WordPress block.
	 * The usage of this method requires an external script with a
	 * path built as the following one:
	 * /modules/mod_[PLUGIN]_[NAME]/[PLUGIN]-[NAME]-widget-block.js
	 * 
	 * @param   string  $blockScriptUri  The relative URI of the script declaring the tools
	 *                                   that will be actually used by the block.
	 * @param   array   $data            The widget manifest data (@see WP_Block_Type).
	 * 
	 * @return  void
	 * 
	 * @since   10.1.51
	 */
	protected function registerBlockType(string $blockScriptUri, array $data)
	{
		/**
		 * Make sure Gutenberg is up and running to avoid
		 * any fatal errors, as the register_block_type()
		 * function may be not available on old instances.
		 */
		if (!function_exists('register_block_type'))
		{
			return;
		}

		// create a block identifier for this widget
		$block_id = preg_replace("/^mod_{$this->_option}_/", '', $this->_id);
		$block_id = preg_replace("/_/", '-', $block_id) . '-widget-block';

		// define the block manifest
		$data = array_merge([
			'id' => $this->_option . '/' . $block_id,
			'title' => $this->name,
			'description' => $this->widget_options['description'],
			'textdomain' => $this->_option,
			'category' => 'widgets',
			'attributes' => [],
			'supports' => [
				// do not edit as HTML
				'html' => false,
				// use the block just once per post
				'multiple' => true,
				// don't allow the block to be converted into a reusable block
				'reusable' => false,
			],
		], $data);

		$form = [];

		// get form fieldsets
		foreach ($this->_form->getFieldset() as $fieldset)
		{
			$set = [];
			$set['name']   = (string) $fieldset->attributes()->name;
			$set['title']  = JText::translate((string) $fieldset->attributes()->label ?: 'COM_MENUS_' . strtoupper($set['name']) . '_FIELDSET_LABEL');
			$set['fields'] = [];

			// take only the fields that belong to this fieldset
			$fields = $fieldset->xpath('//fieldset[@name="' . $set['name'] . '"] //field');

			/**
			 * Add support for title field by creating a custom XML field,
			 * only if the XML of the module doesn't declare it.
			 *
			 * @since 10.1.21
			 */
			if ($set['name'] === 'basic' && !$this->_form->getField('title'))
			{
				// create title field
				$title = simplexml_load_string('<field name="title" type="text" default="" label="COM_MODULES_FIELD_TITLE_LABEL" description="COM_MODULES_FIELD_TITLE_DESC" />');
				// push title at the beginning of the list
				array_unshift($fields, $title);
			}

			// get form fields
			foreach ($fields as $field)
			{
				// get form field
				$field = JFormField::getInstance($field);

				// skip the module class suffix field as it is supported by default by Gutenberg blocks
				if ($field->name === 'moduleclass_sfx')
				{
					continue;
				}
				
				// attach module path (useful to obtain the available layouts)
				$field->bind($this->_path, 'modpath');
				$field->bind($this->_option, 'modowner');

				// obtain field layout data
				$displayData = array_merge(
					[
						'type'        => $field->type,
						'layout'      => $field->layoutId,
						'label'       => JText::translate($field->label ?? ''),
						'description' => strip_tags(JText::translate($field->description ?? '')),
						'showon'      => $field->showon,
					],
					$field->getLayoutData()
				);

				// in case the field does not provide the layout, use the HTML type
				// and render here the input data
				if (!$field->layoutId)
				{
					// convert a HTML document into a JSON-compatible structure
					$json = $this->createElementsFromHtml($field->getInput());
					
					// overwrite type and inject the converted structure within the layout attribute
					$displayData['type']   = 'html';
					$displayData['layout'] = $json;
				}

				// fetch default value
				$default = $displayData['value'] ?? '';
				$default = ($default !== '' && $default !== null) ? $default : ($displayData['default'] ?? ($field->multiple ? [] : ''));	

				// normalize options structure
				if (isset($displayData['options']) && is_array($displayData['options']))
				{
					/**
					 * In case the default value is not a valid option, use the first available one.
					 * 
					 * @todo In case the option is not an associative array, the following condition
					 *       will not work. If we want to extend this compatibility we should manually
					 *       iterate the normalized array in search of an option with matching value.
					 */
					if (is_scalar($default) && !isset($displayData['options'][$default]))
					{
						$default = key($displayData['options']);
					}

					$options = [];

					foreach ($displayData['options'] as $value => $label)
					{
						if (is_object($label) || is_array($label))
						{
							$label = (object) $label;

							$options[] = [
								'label' => JText::translate($label->text),
								'value' => $label->value,
							];
						}
						else
						{
							$options[] = [
								'label' => JText::translate($label),
								'value' => $value,
							];
						}
					}

					$displayData['options'] = $options;
				}

				if ($field->multiple)
				{
					$attrType = 'array';
				}
				else if ($field->type === 'radio' && $field->class === 'btn-group btn-group-yesno')
				{
					$attrType = 'integer';
				}
				else
				{
					$attrType = 'string';
				}

				if (!empty($displayData['name']))
				{
					// bind field attributes
					$data['attributes'][$displayData['name']] = [
						'type'    => $attrType,
						'default' => $default,
					];
				}

				// enqueue form field
				$set['fields'][] = $displayData;
			}

			$form[] = $set;
		}

		// register the script declaring the reusable functions for Gutenberg
		wp_register_script(
			$this->_option . '-gutenberg-tools',
			$blockScriptUri . 'js/gutenberg-tools.js',
			['wp-blocks', 'wp-element', 'wp-i18n'],
			constant(strtoupper($this->_option . '_software_version'))
		);

		// register the script that contains all the JS functions used
		// to implement a new block for Gutenberg editor
		wp_register_script(
			$this->_option . '-gutenberg-widgets',
			$blockScriptUri . 'js/gutenberg-widgets.js',
			['wp-blocks', 'wp-element', 'wp-i18n'],
			constant(strtoupper($this->_option . '_software_version'))
		);

		// register the script that will be used to support this widget
		// as Gutenberg block editor
		wp_register_script(
			$this->_option . '-' . $block_id,
			plugin_dir_url($this->_path) . $this->_id . '/' . $this->_option . '-' . $block_id . '.js',
			['wp-blocks', 'wp-element', 'wp-i18n'],
			constant(strtoupper($this->_option . '_software_version'))
		);

		// Pass the manifest data to the script previously loaded.
		// The object variable will be named as:
		// MOD_[PLUGIN]_[NAME]_BLOCK_DATA
		wp_localize_script(
			$this->_option . '-' . $block_id,
			strtoupper($this->_id . '_block_data'),
			array_merge(
				$data,
				[
					'form' => $form,
				]
			)
		);

		// create a new block type, which must provide the scripts previously loaded
		register_block_type($this->_option . '/' . $block_id, array_merge($data, [
			'render_callback' => function($config) {
				// prepare widget arguments
				$args = [
					'before_widget' => '<div class="widget widget_' . $this->_id . '" id="' . $this->_id . '_' . $this->number . '">',
					'before_title'  => '<h3 class="widget-title">',
					'after_title'   => '</h3>',
					'after_widget'  => '</div>',
				];

				// adjust widget configuration
				$config['moduleclass_sfx'] = $config['className'] ?? '';

				$requestUri = JFactory::getApplication()->input->server->getString('REQUEST_URI', '');

				$is_rest_api = strpos($requestUri, trailingslashit(rest_get_url_prefix())) !== false
					|| JUri::getInstance($requestUri)->hasVar('rest_route');

				// define a callback to include a placeholder in case the widget is not able to render any contents
				$previewPlaceholderCallback = function($id, &$html) {
					// get rid of any script and style declared by the widget layout
					$test = preg_replace("/<script(?:.*?)>(?:.*?)<\/script>/s", '', $html);
					$test = preg_replace("/<style(?:.*?)>(?:.*?)<\/style>/s", '', $test);

					// check whether the test var contains some texts
					if (!trim(strip_tags($test))) {
						$html = '<div style="padding: 10px; background: #eee; border: 2px solid #ddd;">'
							. JText::translate('COM_MODULES_PREVIEW_NOT_AVAIL')
							. '</div>';
					}
				};

				// start buffer
				ob_start();

				if ($is_rest_api || is_admin())
				{
					// overwrite the callback to register an asset declaration at runtime
					JFactory::getDocument()->attachToHeadCustomCallback = function($callback) {
						// prevent the system document from displaying the asset
					};

					// register a callback to display a placeholder when the widget contents are empty
					add_action('vik_widget_after_dispatch_site', $previewPlaceholderCallback, 10, 2);
				}

				// render the widget for the front-end
				$this->widget($args, $config);

				// if we are under a REST API, the block is probably
				// requesting a server-side rendering of the widget
				if ($is_rest_api)
				{
					// force WordPress to include the styles and the scripts
					// within the rendered HTML
					wp_print_styles();

					// DO NOT print the scripts to prevent JS errors
					// wp_print_head_scripts();
				}

				// get contents
				$html = ob_get_contents();
				// clear buffer
				ob_end_clean();

				if ($is_rest_api || is_admin())
				{
					// get rid of any script declared by the widget layout
					$html = preg_replace("/<script(?:.*?)>(?:.*?)<\/script>/s", '', $html);

					// restore the original callback used to register the assets
					JFactory::getDocument()->attachToHeadCustomCallback = null;

					// unregister the callback used to display a placeholder when the widget contents are empty
					remove_action('vik_widget_after_dispatch_site', $previewPlaceholderCallback);
				}

				return $html;
			},
			'editor_script_handles' => [
				$this->_option . '-gutenberg-tools',
				$this->_option . '-gutenberg-widgets',
				$this->_option . '-' . $block_id,
			],
		]));
	}

	/**
	 * Converts an HTML string into a JSON-compatible structure.
	 * 
	 * @param   string    $html  The HTML to convert.
	 * 
	 * @return  object[]  A list of nodes.
	 * 
	 * @since   10.1.51
	 */
	private function createElementsFromHtml(string $html)
	{
		// wrap the HTML into a dom document
		$dom = new DOMDocument;
		$dom->loadHTML($html);

		// set up root
		$root = new stdClass;
		$root->tag = 'html';
		$root->children = [];

		// recursively extract the nodes from the document
		$this->extractHtmlElements($dom, $root);

		// take only the children of the root ("html")
		return $root->children;
	}

	/**
	 * Extracts the HTML tags from the provided node.
	 * 
	 * @param   DOMNode  $domNode  The current DOM node to scan.
	 * @param   object   &$parent  When the extracted tags should be attached.
	 * 
	 * @return  void
	 * 
	 * @since   10.1.51
	 */
	private function extractHtmlElements(DOMNode $domNode, &$parent)
	{
		foreach ($domNode->childNodes as $node)
		{
			$tag = $parent;

			if (!in_array($node->nodeName, ['html', 'body']))
			{
				$tag = new stdClass;
				$tag->tag = $node->nodeName;
				$tag->children = [];

				if ($node->hasAttributes())
				{
					$tag->attributes = [];

					foreach ($node->attributes as $attr)
					{
						if ($attr->nodeName === 'style')
						{
							$tag->attributes['style'] = [];

							// convert style string into an associative array
							if (preg_match_all("/([a-z0-9-]+)\s*:\s*([^;]+);/i", (string) $attr->nodeValue, $matches))
							{
								for ($i = 0; $i < count($matches[0]); $i++)
								{
									$propertyName  = $matches[1][$i];
									$propertyValue = $matches[2][$i];

									$tag->attributes['style'][$propertyName] = $propertyValue;
								}
							}
						}
						else
						{
							$tag->attributes[$attr->nodeName] = $attr->nodeValue;
						}
					}
				}

				if ($node->nodeName === '#text')
				{
					$parent->content = trim((string) $node->nodeValue);
				}
				else
				{
					$parent->children[] = $tag;
				}
			}

			if ($node->hasChildNodes())
			{
				$this->extractHtmlElements($node, $tag);
			}
		}
	}
}
