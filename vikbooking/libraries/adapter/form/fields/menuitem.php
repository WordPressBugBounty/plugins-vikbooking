<?php
/** 
 * @package     VikWP - Libraries
 * @subpackage  adapter.form
 * @author      E4J s.r.l.
 * @copyright   Copyright (C) 2023 E4J s.r.l. All Rights Reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * @link        https://vikwp.com
 */

// No direct access
defined('ABSPATH') or die('No script kiddies please!');

JLoader::import('adapter.form.fields.list');

/**
 * Form field class to handle dropdown fields containing
 * the pages assigned to a specific shortcode of the plugin.
 *
 * @since 10.0
 */
class JFormFieldMenuItem extends JFormFieldList
{
	/**
	 * Cache the available shortcodes to prevent duplicate queries.
	 * 
	 * @var array
	 * @since 10.1.51
	 */
	protected static $shortcodes = [];

	/**
	 * @override
	 * Method to get the options to populate list.
	 *
	 * @return  array  The field option objects.
	 *
	 * @since   10.1.51
	 */
	public function getOptions()
	{
		// obtain the default options defined by the parent
		$options = parent::getOptions();

		if (!$options)
		{
			// placeholder not specified, use the default one
			$options[''] = '--';
		}

		if (!empty($this->prefix))
		{
			// set modowner with custom prefix
			$this->modowner = $this->prefix;
		}

		// check whether the same results have been already cached
		if (!isset(static::$shortcodes[$this->modowner]))
		{
			// init the cache
			static::$shortcodes[$this->modowner] = [];

			/**
			 * Force the client path because we need to load the shortcodes model
			 * always from the back-end folder.
			 *
			 * An issue occurred since WP 5.8, after the refactoring of the widgets 
			 * management page, where the client now results to be "site" in place
			 * of "admin".
			 *
			 * @since 10.1.34
			 */
			$model = JModel::getInstance($this->modowner, 'shortcodes', 'admin');

			if ($model)
			{
				foreach ($model->all() as $item)
				{
					if ($item->post_id)
					{
						static::$shortcodes[$this->modowner][$item->post_id] = get_the_title($item->post_id) . " - {$item->type}";
					}
				}
			}
		}

		// join the default options with the cached values
		return $options + static::$shortcodes[$this->modowner];
	}
}
