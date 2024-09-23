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
 * Class handler for admin widget "last reservations".
 * 
 * @since 	1.14 (J) - 1.4.0 (WP)
 */
class VikBookingAdminWidgetLastReservations extends VikBookingAdminWidget
{
	/**
	 * Class constructor will define the widget name and identifier.
	 */
	public function __construct()
	{
		// call parent constructor
		parent::__construct();

		$this->widgetName = JText::translate('VBO_W_LASTRES_TITLE');
		$this->widgetDescr = JText::translate('VBO_W_LASTRES_DESCR');
		$this->widgetId = basename(__FILE__, '.php');

		/**
		 * Define widget and icon and style name.
		 * 
		 * @since 	1.15.0 (J) - 1.5.0 (WP)
		 */
		$this->widgetIcon = '<i class="' . VikBookingIcons::i('concierge-bell') . '"></i>';
		$this->widgetStyleName = 'pink';
	}

	public function render(VBOMultitaskData $data = null)
	{
		$dbo = JFactory::getDbo();
		
		$q = "SELECT `o`.`id`,`o`.`custdata`,`o`.`status`,`o`.`days`,`o`.`checkin`,`o`.`checkout`,`o`.`roomsnum`,`o`.`country`,`o`.`type`,(SELECT CONCAT_WS(' ',`or`.`t_first_name`,`or`.`t_last_name`) FROM `#__vikbooking_ordersrooms` AS `or` WHERE `or`.`idorder`=`o`.`id` LIMIT 1) AS `nominative`,(SELECT SUM(`or`.`adults`) FROM `#__vikbooking_ordersrooms` AS `or` WHERE `or`.`idorder`=`o`.`id`) AS `tot_adults`,(SELECT SUM(`or`.`children`) FROM `#__vikbooking_ordersrooms` AS `or` WHERE `or`.`idorder`=`o`.`id`) AS `tot_children` FROM `#__vikbooking_orders` AS `o` WHERE `o`.`closure`=0 ORDER BY `o`.`id` DESC LIMIT 10;";

		$dbo->setQuery($q);
		$lastreservations = $dbo->loadAssocList();

		if (!$lastreservations) {
			return;
		}
		?>
		<div class="vbo-admin-widget-wrapper">
			<div class="vbo-admin-widget-head">
				<h4><?php VikBookingIcons::e('far fa-bell'); ?> <span><?php echo JText::translate('VBDASHLASTRES'); ?></span></h4>
			</div>
			<div class="vbo-dashboard-next-bookings table-responsive">
				<table class="table">
					<thead>
						<tr class="vbo-dashboard-today-checkout-firstrow">
							<th class="left"><?php echo JText::translate('VBDASHUPRESONE'); ?></th>
							<th class="left"><?php echo JText::translate('VBCUSTOMERNOMINATIVE'); ?></th>
							<th class="left"><?php echo JText::translate('VBDASHUPRESTWO'); ?></th>
							<th class="left"><?php echo JText::translate('VBDASHUPRESTHREE'); ?></th>
							<th class="center"><?php echo JText::translate('VBDASHUPRESFIVE'); ?></th>
						</tr>
					</thead>
					<tbody>
					<?php
					foreach ($lastreservations as $nbk => $next) {
						$totpeople_str = $next['tot_adults']." ".($next['tot_adults'] > 1 ? JText::translate('VBMAILADULTS') : JText::translate('VBMAILADULT')).($next['tot_children'] > 0 ? ", ".$next['tot_children']." ".($next['tot_children'] > 1 ? JText::translate('VBMAILCHILDREN') : JText::translate('VBMAILCHILD')) : "");
						$room_names = array();
						foreach (VikBooking::loadOrdersRoomsData($next['id']) as $rr) {
							$room_names[] = $rr['room_name'];
						}
						if ($next['roomsnum'] == 1) {
							$roomstr = '<span class="vbo-smalltext">'.$room_names[0].'</span>';
						} else {
							$roomstr = '<span class="vbo-tooltip vbo-tooltip-top" data-tooltiptext="' . JHtml::fetch('esc_attr', implode(', ', $room_names)) . '">'.$next['roomsnum'].'</span>';
						}
						if ($next['status'] == 'confirmed') {
							$ord_status = '<span class="label label-success vbo-status-label">'.JText::translate('VBCONFIRMED').'</span>';
						} elseif ($next['status'] == 'standby') {
							$ord_status = '<span class="label label-warning vbo-status-label">'.JText::translate('VBSTANDBY').'</span>';
						} else {
							$ord_status = '<span class="label label-error vbo-status-label">'.JText::translate('VBCANCELLED').'</span>';
						}
						if (!empty($next['type']) && !strcasecmp($next['type'], 'overbooking')) {
							$ord_status .= '<div class="vbo-orders-substatus"><span class="label label-error">' . JText::translate('VBO_BTYPE_OVERBOOKING') . '</span></div>';
						}
						$nominative = strlen($next['nominative']) > 1 ? $next['nominative'] : VikBooking::getFirstCustDataField($next['custdata']);
						$country_flag = '';
						if (is_file(VBO_ADMIN_PATH . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'countries' . DIRECTORY_SEPARATOR . $next['country'] . '.png')) {
							$country_flag = '<img src="'.VBO_ADMIN_URI.'resources/countries/'.$next['country'].'.png'.'" title="'.$next['country'].'" class="vbo-country-flag vbo-country-flag-left"/>';
						}
						?>
						<tr class="vbo-dashboard-today-checkout-rows">
							<td class="left"><a href="index.php?option=com_vikbooking&amp;task=editorder&amp;cid[]=<?php echo $next['id']; ?>"><?php echo $next['id']; ?></a></td>
							<td class="left"><?php echo $country_flag.$nominative; ?></td>
							<td class="left"><?php echo $roomstr; ?></td>
							<td class="left"><?php echo date(str_replace("/", $this->datesep, $this->df), $next['checkin']) . ' - &times;' . $next['days']; ?></td>
							<td class="center"><?php echo $ord_status; ?></td>
						</tr>
						<?php
					}
					?>
					</tbody>
				</table>
			</div>
		</div>
		<?php
	}
}
