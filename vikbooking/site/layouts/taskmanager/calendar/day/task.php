<?php
/** 
 * @package     VikBooking
 * @subpackage  core
 * @author      E4J s.r.l.
 * @copyright   Copyright (C) 2025 E4J s.r.l. All Rights Reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * @link        https://vikwp.com
 */

// No direct access
defined('ABSPATH') or die('No script kiddies please!');

/**
 * Obtain vars from arguments received in the layout file.
 * 
 * @var VBOTaskTaskregistry  $task  The task registry to render.
 */
extract($displayData);

// access the task manager object
$taskManager = VBOFactory::getTaskManager();

// get task status details
$statusColor = '';
$statusName = '';
if ($taskManager->statusTypeExists($task->getStatus())) {
    $status = $taskManager->getStatusTypeInstance($task->getStatus());
    $statusColor = $status->getColor();
    $statusName = $status->getName();
}

// get task assignee details
$assignees = $task->getAssigneeDetails();

?>
<div class="vbo-tm-calendar-day-task vbo-tm-color <?php echo $statusColor; ?>" data-task-id="<?php echo $task->getID(); ?>" data-area-id="<?php echo $task->getAreaID(); ?>">
    <div class="vbo-tm-calendar-day-task-wrap">
        <div class="vbo-tm-calendar-day-task-head">
            <span class="vbo-tm-calendar-day-task-title">
                <?php echo $task->getTitle(); ?>
                
                <?php if ($task->get('hasUnreadMessages', false)): ?>
                    <span class="unread-message-dot">
                        <?php VikBookingIcons::e('comment'); ?>
                    </span>
                <?php endif; ?>        
            </span>
        </div>
        <div class="vbo-tm-calendar-day-task-footer">
    <?php
    if ($assignees) {
        ?>
        <span class="vbo-tm-calendar-task-assignees">
        <?php
        foreach ($assignees as $operator) {
            ?>
            <span class="vbo-tm-calendar-task-assignee vbo-tm-task-assignee">
                <span class="vbo-tm-calendar-task-assignee-avatar vbo-tm-task-assignee-avatar" title="<?php echo JHtml::fetch('esc_attr', $operator['name']); ?>">
                <?php
                if (!empty($operator['img_uri'])) {
                    ?>
                    <img src="<?php echo $operator['img_uri']; ?>" alt="<?php echo JHtml::fetch('esc_attr', $operator['initials']); ?>" decoding="async" loading="lazy" />
                    <?php
                } else {
                    ?>
                    <span><?php echo $operator['initials']; ?></span>
                    <?php
                }
                ?>
                </span>
            </span>
            <?php
        }
        ?>
        </span>
        <?php
    }
    ?>
        </div>
    </div>
</div>