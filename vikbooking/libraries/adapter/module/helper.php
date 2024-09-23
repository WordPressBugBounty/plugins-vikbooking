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

/**
 * Helper class for WP widgets.
 *
 * @since 10.0
 */
class JModuleHelper
{
	/**
	 * Absolute path of the module.
	 * Since this class is located in the adapter package, it doesn't
	 * know the base path in which all the modules are located.
	 * This means that the path should be changed every time a module 
	 * is going to be rendered.
	 *
	 * @var string
	 */
	protected static $path = null;

	/**
	 * Sets the base path in which the module is contained.
	 * The path should include also the module name.
	 *
	 * @param 	string 	$path 	The module path.
	 *
	 * @return 	void
	 */
	public static function setPath($path)
	{
		static::$path = rtrim($path, DIRECTORY_SEPARATOR);
	}

	/**
	 * Gets the current base path.
	 *
	 * @param 	string 	$name 	The module name (optional). 
	 *
	 * @return 	string 	The module path
	 */
	public static function getPath($name = null)
	{
		// return null if no path
		if (!static::$path)
		{
			return null;
		}

		// if the name is provided, make sure the path already contains it
		if ($name)
		{
			// get the path chunks
			$parts = explode(DIRECTORY_SEPARATOR, static::$path);

			// if the last chunk doesn't match the module name, append it
			if (end($parts) != $name)
			{
				$parts[] = $name;
			}

			// implode the chunks with the DS
			return implode(DIRECTORY_SEPARATOR, $parts);
		}

		return static::$path;
	}

	/**
	 * Returns the layout path of the module.
	 *
	 * @param 	string 	$module 	The module name.
	 * @param 	string 	$layout 	The module layout name.
	 *
	 * @return 	string 	The module layout path (relative).
	 */
	public static function getLayoutPath($module, $layout = null)
	{
		if (!$layout)
		{
			$layout = 'default';
		}
		else if (strpos($layout, DIRECTORY_SEPARATOR) !== false)
		{
			// make sure the given layout is a file
			if (is_file($layout))
			{
				/**
				 * The layout is an absolute path that points to an existing
				 * override file. Use it instead of the default one.
				 *
				 * @since 10.1.2
				 */
				return $layout;
			}

			/**
			 * Fallback to default layout and trigger error
			 * to inform the user that the configuration of
			 * the widget doesn't work as expected.
			 *
			 * @since 10.1.32
			 */
			if (WP_DEBUG)
			{
				// the warning won't be displayed in production
				trigger_error(
					sprintf(
						'Widget layout [%s] not found! The default one will be used',
						$layout
					),
					E_USER_WARNING
				);
			}

			$layout = 'default';
		}

		// construct the layout path of the default module
		$parts = array();
		$parts[] = static::getPath($module);
		$parts[] = 'tmpl';
		$parts[] = $layout . '.php';

		return implode(DIRECTORY_SEPARATOR, array_filter($parts));
	}

	/**
	 * Get module by element.
	 *
	 * @param   string  $name   The name of the module.
	 *
	 * @return  stdClass  The Module object.
	 *
	 * @since   10.1.30
	 */
	public static function getModule($name)
	{
		global $wp_widget_factory;

		// build classname of the widget
		$classname = str_replace('_', ' ', $name);
		$classname = preg_replace("/\s+/", '', ucwords($classname)) . '_Widget';

		// prepare result
		$result         = new stdClass;
		$result->id     = 0;
		$result->module = $name;
		$result->params = '';

		if (isset($wp_widget_factory->widgets[$classname]))
		{
			// get settings of all the widgets of this type
			$settings = $wp_widget_factory->widgets[$classname]->get_settings();

			if ($settings)
			{
				// take only the first one
				$result->id     = key($settings);
				$result->params = reset($settings);

				// JSON encode params for Joomla compatibility
				$result->params = json_encode($result->params);
			}
		}

		/**
		 * If we are not able to find a widget, then we should look into the
		 * posts and pages in search of a Gutenberg block and extract the
		 * settings from there.
		 * 
		 * @since 10.1.52
		 */
		if (!$result->id)
		{
			// split the module name in chunks:
			// [MOD] | [COMPONENT] | ...
			$block = explode('_', $name);
			// construct gutenberg block identifier
			$block = $block[1] . '/' . implode('-', array_slice($block, 2)) . '-widget-block';

			// look for a post/page that contains the block name of the requested module
			$query = new WP_Query([
				's' => '<!-- wp:' . $block,
			]);

			// fetch all the matching posts, if any
			foreach ($query->get_posts() as $post)
			{
				// use the same ID of the post
				$result->id = $post->ID;

				// escape characters of the module block name for being safely used in a regex
				$regex = preg_quote($block, '/');

				// extract parameters from block name
				if (preg_match("/<!-- wp:$regex\s+({(?:.*?)})(?:.*?)-->/", $post->post_content, $match))
				{
					// keep the parameters encoded in JSON format for Joomla compatibility
					$result->params = $match[1];
				}
			}
		}

		return $result;
	}
}
