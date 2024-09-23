<?php
/**
 * @package     VikBooking
 * @subpackage  com_vikbooking
 * @author      Alessio Gaggii - E4J srl
 * @copyright   Copyright (C) 2024 E4J srl. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 * @link        https://e4jconnect.com | https://vikwp.com
 */

defined('ABSPATH') or die('No script kiddies please!');

extract($displayData);

$modal = json_encode([
    'title' => 'Background Task Logs',
    'body' => '<pre>' . $log . '</pre>',
    'extra_class' => 'vbo-modal-rounded vbo-modal-tall vbo-modal-nofooter',
]);

?>    

<div class="vbo-admin-widget-head">
    <div class="vbo-admin-widget-head-inline">
        <h4><?php VikBookingIcons::e('server'); ?> <span>Background Tasks</span></h4>
        <div class="vbo-admin-widget-head-commands">
            <div class="vbo-reportwidget-commands">
                <button type="button" class="btn vbo-config-btn" onclick="displayBgTasksLogFile()">See Logs</button>
            </div>
        </div>
    </div>
</div>

<div>

    <?php foreach ($schedules as $schedule): ?>

        <div style="padding: 10px 15px; border-bottom: 1px solid var(--vbo-basic-btn);">

            <div><strong><?php echo $schedule['name']; ?></strong></div>

            <div style="display: flex; justify-content: space-between; align-items: start; margin-top: 10px;">
                <span class="badge badge-info vbo-tooltip vbo-tooltip-top" data-tooltiptext="Last execution"><?php echo $schedule['last_execution']; ?></span>
                <span class="badge badge-success vbo-tooltip vbo-tooltip-top" data-tooltiptext="Next execution"><?php echo $schedule['next_execution']; ?></span>
            </div>
            
        </div>

    <?php endforeach; ?>

</div>

<script>
    (function(w) {
        'use strict';

        w.displayBgTasksLogFile = () => {
            VBOCore.displayModal(<?php echo $modal; ?>);
        }
    })(window);
</script>