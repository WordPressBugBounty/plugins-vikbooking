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
 * Class handler for admin widget "rooms locked".
 * 
 * @since 	1.4.0
 */
class VikBookingAdminWidgetRoomsLocked extends VikBookingAdminWidget
{
	/**
	 * Class constructor will define the widget name and identifier.
	 */
	public function __construct()
	{
		// call parent constructor
		parent::__construct();

		$this->widgetName = JText::translate('VBO_W_ROOMSLOCK_TITLE');
		$this->widgetDescr = JText::translate('VBO_W_ROOMSLOCK_DESCR');
		$this->widgetId = basename(__FILE__, '.php');

		/**
		 * Define widget and icon and style name.
		 * 
		 * @since 	1.15.0 (J) - 1.5.0 (WP)
		 */
		$this->widgetIcon = '<i class="' . VikBookingIcons::i('lock') . '"></i>';
		$this->widgetStyleName = 'orange';
	}

	public function render(VBOMultitaskData $data = null)
	{
		$dbo = JFactory::getDbo();
		
		$rooms_locked = array();
		
		// clean up the expired pending records
		$q = "DELETE FROM `#__vikbooking_tmplock` WHERE `until`<" . time() . ";";
		$dbo->setQuery($q);
		$dbo->execute();

		// get all future rooms locked
		$q = "SELECT `lock`.*,`r`.`name`,(SELECT CONCAT_WS(' ',`or`.`t_first_name`,`or`.`t_last_name`) FROM `#__vikbooking_ordersrooms` AS `or` WHERE `lock`.`idorder`=`or`.`idorder` LIMIT 1) AS `nominative` FROM `#__vikbooking_tmplock` AS `lock` LEFT JOIN `#__vikbooking_rooms` `r` ON `lock`.`idroom`=`r`.`id` WHERE `lock`.`until`>".time()." ORDER BY `lock`.`id` DESC;";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows() > 0) {
			$rooms_locked = $dbo->loadAssocList();
		}

		if (!count($rooms_locked) && is_null($data)) {
			// do not display any contents in the dashboard, but do it in the multitask panel
			return;
		}
		?>
		<div class="vbo-admin-widget-wrapper">
			<div class="vbo-admin-widget-head">
				<h4><?php echo $this->widgetIcon; ?> <span><?php echo JText::translate('VBDASHROOMSLOCKED'); ?> (<?php echo count($rooms_locked); ?>)</span></h4>
			</div>
		<?php
		if (count($rooms_locked)) {
			?>
			<div class="vbo-dashboard-rooms-locked table-responsive">
				<table class="table">
					<tr class="vbo-dashboard-rooms-locked-firstrow">
						<td class="center"><?php echo JText::translate('VBDASHBOOKINGID'); ?></td>
						<td class="center"><?php echo JText::translate('VBDASHROOMNAME'); ?></td>
						<td class="center"><?php echo JText::translate('VBCUSTOMERNOMINATIVE'); ?></td>
						<td class="center"><?php echo JText::translate('VBDASHLOCKUNTIL'); ?></td>
						<td class="center">&nbsp;</td>
					</tr>
				<?php
				foreach ($rooms_locked as $lock) {
					?>
					<tr class="vbo-dashboard-rooms-locked-rows">
						<td class="center">
							<a href="index.php?option=com_vikbooking&amp;task=editorder&amp;cid[]=<?php echo $lock['idorder']; ?>" class="vbo-bookingid" target="_blank"><?php echo $lock['idorder']; ?></a>
						</td>
						<td class="center"><?php echo $lock['name']; ?></td>
						<td class="center"><?php echo $lock['nominative']; ?></td>
						<td class="center"><?php echo date(str_replace("/", $this->datesep, $this->df).' H:i', $lock['until']); ?></td>
						<td class="center">
							<button type="button" class="btn btn-danger" onclick="if (confirm('<?php echo addslashes(JText::translate('VBDELCONFIRM')); ?>')) location.href='index.php?option=com_vikbooking&amp;task=unlockrecords&amp;cid[]=<?php echo $lock['id']; ?>';"><?php echo JText::translate('VBDASHUNLOCK'); ?></button>
						</td>
					</tr>
					<?php
				}
				?>
				</table>
			</div>
		<?php
		}
		?>
		</div>
		<?php
	}
}
