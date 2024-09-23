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
 * Class handler for admin widget "forecast".
 * 
 * @since 	1.4.0
 */
class VikBookingAdminWidgetForecast extends VikBookingAdminWidget
{
	/**
	 * Class constructor will define the widget name and identifier.
	 */
	public function __construct()
	{
		// call parent constructor
		parent::__construct();

		$this->widgetName = JText::translate('VBO_W_OCCFORECAST_TITLE');
		$this->widgetDescr = JText::translate('VBO_W_OCCFORECAST_DESCR');
		$this->widgetId = basename(__FILE__, '.php');

		/**
		 * Define widget and icon and style name.
		 * 
		 * @since 	1.15.0 (J) - 1.5.0 (WP)
		 */
		$this->widgetIcon = '<i class="' . VikBookingIcons::i('cloud-sun-rain') . '"></i>';
		$this->widgetStyleName = 'yellow';
	}

	public function render(VBOMultitaskData $data = null)
	{
		$vbo_auth_pricing = JFactory::getUser()->authorise('core.vbo.pricing', 'com_vikbooking');
		$vbo_auth_bookings = JFactory::getUser()->authorise('core.vbo.bookings', 'com_vikbooking');

		if (!$vbo_auth_pricing || !$vbo_auth_bookings) {
			// base permissions are not met
			return;
		}

		$layout_data = array(
			'vbo_page' => 'dashboard',
		);
		?>
		<div class="vbo-admin-widget-wrapper">
			<div class="vbo-admin-widget-head">
				<h4><?php echo $this->widgetIcon; ?> <span><?php echo JText::translate('VBOFORECAST'); ?></span></h4>
			</div>
			<div class="vbo-dashboard-forecast-inner">
				<?php echo JLayoutHelper::render('reports.occupancy', $layout_data); ?>
			</div>
		</div>
		<?php
	}
}
