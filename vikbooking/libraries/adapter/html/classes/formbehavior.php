<?php
/** 
 * @package     VikWP - Libraries
 * @subpackage  adapter.html
 * @author      E4J s.r.l.
 * @copyright   Copyright (C) 2023 E4J s.r.l. All Rights Reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * @link        https://vikwp.com
 */

// No direct access
defined('ABSPATH') or die('No script kiddies please!');

/**
 * Utility class for form related behaviors.
 *
 * @since 10.1.16
 */
abstract class JHtmlFormbehavior
{
	/**
	 * Method to load the Chosen JavaScript framework and supporting CSS into the document head.
	 *
	 * @param   string  $selector  Class for Chosen elements.
	 * @param   mixed   $debug     Is debugging mode on? [optional].
	 * @param   array   $options   The possible Chosen options as name => value [optional].
	 *
	 * @return  void
	 */
	public static function chosen($selector = '.btn-toolbar', $debug = null, $options = array())
	{
		// load always select2 plugin
		// JHtml::fetch('select2');
		JText::script('JGLOBAL_SELECT_AN_OPTION');
			
		static $loaded = 0;

		// register only once
		if (!$loaded)
		{
			$loaded = 1;

			/**
			 * Use select2 rendering only in case of WordPress 5.2 or lower.
			 * Starting from WordPress 5.3 the dropdowns are rendered properly
			 * and there is no need to use a different layout.
			 * Contrarily, we will lose the possibility of searching the options.
			 *
			 * @since 10.1.30
			 */
			// $use_select2 = version_compare((new JVersion)->getShortVersion(), '5.3', '<') ? 1 : 0;
			$use_select2 = 0;

			// add support for chosen plugin
			JFactory::getDocument()->addScriptDeclaration(
<<<JAVASCRIPT
(function($) {
	'use strict';

	if ($.fn.chosen === undefined) {
		$.fn.chosen = function(data) {
			// iterate all selected elements
			$(this).each(function() {
				// check is we have a multiple select
				var isMultiple = $(this).prop('multiple');

				if (!$use_select2 && !isMultiple) {
					// do not go ahead in case we don't need a custom plugin
					// to handle standard dropdowns
					return this;
				}

				if (data !== undefined) {
					// invoke requested method (e.g. destroy)
					$(this).select2(data);
				} else {
					data = {};
					data.width       = isMultiple ? 300 : 200;
					data.allowClear  = $(this).hasClass('required') ? false : true;
					data.placeholder = Joomla.JText._('JGLOBAL_SELECT_AN_OPTION');

					var firstOption = $(this).find('option').first();

					// in case we don't have an empty option, unset placeholder
					if (!isMultiple && firstOption.length && firstOption.val().length > 0) {
						data.allowClear  = false;
						data.placeholder = null;
					}

					// turn off search when there are 5 options or less
					if ($(this).find('option').length <= 5) {
						data.minimumResultsForSearch = -1;
					}

					// init select2 plugin
					$(this).select2(data);
				}
			});

			return this;
		}
	}
})(jQuery);
JAVASCRIPT
			);
		}

		static $selectors = [];

		/**
		 * The system is now able to initialize multiple selectors as a result of multiple calls.
		 * 
		 * @since 10.1.63
		 */
		if (!isset($selectors[$selector])) {
			$selectors[$selector] = 1;

			// init requested selectors
			JFactory::getDocument()->addScriptDeclaration(
<<<JAVASCRIPT
(function($) {
	'use strict';

	$(function() {
		$('{$selector}')
			.find('select')
				.chosen()
					.on('chosen:updated', function() {
						// refresh select2 value when triggered
						// $(this).select2('val', $(this).val());
					});
	});
})(jQuery);
JAVASCRIPT
			);
		}
	}
}
