<?php
/**
 * @package     VikBooking
 * @subpackage  mod_vikbooking_horizontalsearch
 * @author      Alessio Gaggii - E4J s.r.l
 * @copyright   Copyright (C) 2018 E4J s.r.l. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 * @link        https://vikwp.com
 */

// No direct access
defined('ABSPATH') or die('No script kiddies please!');

jimport('adapter.module.widget');

/**
 * Horizontal Search Module implementation for WP
 *
 * @see 	JWidget
 * @since 	1.0
 */
class ModVikbookingHorizontalsearch_Widget extends JWidget
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
			/**
			 * Convert this widget into a block.
			 * 
			 * @since 1.6.7
			 */
			$this->registerBlockType(
				VIKBOOKING_ADMIN_ASSETS_URI,
				[
					'icon' => 'calendar-alt',
					'keywords' => [
						__('VikBooking', 'vikbooking'),
						__('Horizontal Search', 'vikbooking'),
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
		$new_instance['title'] 			= !empty($new_instance['title']) ? strip_tags($new_instance['title']) : '';
		$new_instance['defadults'] 		= intval($new_instance['defadults']) > 0 ? (int) $new_instance['defadults'] : 2;
		$new_instance['showcat'] 		= intval($new_instance['showcat']) === 1 ? 1 : 2;
		$new_instance['room_id'] 		= intval($new_instance['room_id']);
		$new_instance['category_id'] 	= intval($new_instance['category_id']);

		return $new_instance;
	}
}
