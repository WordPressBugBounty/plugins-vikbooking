<?php
/**
 * @package     VikBooking
 * @subpackage  mod_vikbooking_chat
 * @author      E4J s.r.l
 * @copyright   Copyright (C) 2026 E4J s.r.l. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 * @link        https://vikwp.com
 */

// No direct access
defined('ABSPATH') or die('No script kiddies please!');

jimport('adapter.module.widget');

/**
 * VikBooking Chat module implementation for WP.
 *
 * @see 	JWidget
 * @since 	1.0
 */
class ModVikbookingChat_Widget extends JWidget
{
	/**
	 * Class constructor.
	 */
	public function __construct()
	{
		// attach the absolute path of the module folder
		parent::__construct(dirname(__FILE__));

		try
		{
			// add support as block
			$this->registerBlockType(
				VIKBOOKING_ADMIN_ASSETS_URI,
				[
					'icon' => 'format-chat',
					'keywords' => [
						__('VikBooking', 'vikbooking'),
						__('Chat', 'vikbooking'),
						__('Widget'),
					],
				]
			);
		}
		catch (Throwable $error)
		{
			// there's a conflict with an outdated plugin
		}
	}

	/**
	 * Sanitize widget form values as they are saved.
	 *
	 * @param 	array 	$new_instance 	Values just sent to be saved.
	 * @param 	array 	$old_instance 	Previously saved values from database.
	 *
	 * @return 	array 	Updated safe values to be saved.
	 */
	public function update($new_instance, $old_instance)
	{
		$new_instance['title'] = !empty($new_instance['title']) ? strip_tags($new_instance['title']) : '';
		
		return $new_instance;
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
		// define a callback to hide the title even if it was specified
		$hideTitle = fn($title) => '';

		add_filter('widget_title', $hideTitle);
		parent::widget($args, $config);
		remove_filter('widget_title', $hideTitle);
	}
}
