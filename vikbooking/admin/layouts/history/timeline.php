<?php
/**
 * @package     VikBooking
 * @subpackage  com_vikbooking
 * @author      Alessio Gaggii - E4J srl
 * @copyright   Copyright (C) 2025 E4J srl. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 * @link        https://vikwp.com
 */

defined('ABSPATH') or die('No script kiddies please!');

/**
 * Display data attributes.
 * 
 * @var array  $history
 */
extract($displayData);

?>

<div class="vbo-history-timeline">

    <?php foreach (array_reverse($history) as $change): ?>

        <div class="history-timeline-change">
            
            <div class="change-icon">
                <i class="<?php echo $change->icon; ?>"></i>
            </div>

            <div class="change-details">
                
                <div class="change-events-list">
                    <?php foreach ($change->events as $event): ?>
                        <div class="change-event">
                            <?php echo $event->describe(); ?>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="change-bottom">
                    
                    <span class="change-user">
                        <?php if ($change->user->name): ?>
                            <?php echo $change->user->name; ?>
                        <?php else: ?>
                            <?php VikBookingIcons::e('history'); ?>&nbsp;<?php echo JText::translate('VBO_SCHEDULED_ACTIVITY'); ?>
                        <?php endif; ?>
                    </span>
                    <span class="change-date" title="<?php echo JHtml::fetch('date', $change->date, 'd F Y H:i'); ?>">
                        ﹣
                        <?php echo JHtml::fetch('date.relative', $change->date, null, null, 'd F Y H:i'); ?>
                    </span>

                </div>

            </div>

        </div>

    <?php endforeach; ?>

</div>