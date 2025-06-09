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
 * @var string $tool         The operator tool identifier calling this layout file.
 * @var array  $operator     The operator record accessing the tool.
 * @var object $permissions  The operator-tool permissions registry.
 * @var string $tool_uri     The base URI for rendering this tool.
 * @var array  $data         The data for rendering the tasks of a given month within a calendar.
 */
extract($displayData);

if (!$operator || empty($operator['id'])) {
    throw new Exception('Missing operator details', 400);
}

// get task details
$taskId = $data['filters']['task_id'] ?? 0;

if (empty($taskId)) {
    throw new Exception('Missing task details', 400);
}

// get the current operator permissions
$accept_tasks = (bool) ($permissions ? $permissions->get('accept_tasks', 0) : 0);

// get a registry of the requested task record
$taskRegistry = VBOTaskTaskregistry::getRecordInstance((int) $taskId);

if (!$taskRegistry->getID()) {
    // task record not found
    throw new Exception('Cannot find task details', 404);
}

// get the task assignee IDs
$assingeeIds = $taskRegistry->getAssigneeIds();

if ((!$assingeeIds && !$accept_tasks) || ($assingeeIds && !in_array($operator['id'], $assingeeIds))) {
    // task record not accessible by the current operator
    throw new Exception('Cannot access task details', 403);
}

// get the current task area object wrapper
$taskArea = VBOTaskArea::getRecordInstance($taskRegistry->getAreaID());

if ($taskArea->isPrivate()) {
    // the area of this task seems to be private
    throw new Exception('Cannot access this area', 403);
}

// inject current area in task registry wrapper
$taskRegistry->setArea($taskArea);

// access data for backward navigation
$calendar_back_type = $data['filters']['calendar_back_type'] ?? 'month';
$calendar_back_type = in_array($calendar_back_type, ['month', 'day']) ? $calendar_back_type : 'month';

// access the task manager object
$taskManager = VBOFactory::getTaskManager();

// load task assignees
$assignees = $taskRegistry->getAssigneeDetails();

// load task tags
$tags = $taskRegistry->getTags() ? $taskRegistry->getTagRecords() : [];

// get task status object
$status = $taskManager->statusTypeExists($taskRegistry->getStatus()) ? $taskManager->getStatusTypeInstance($taskRegistry->getStatus()) : null;

// load all the available status types
$statusTypes = [];

foreach ($taskManager->getStatusGroupElements($taskArea->getStatuses()) as $groupId => $group) {
    // push group button
    $statusTypes[] = [
        'id' => null,
        'text' => $group['text'],
    ];

    // iterate over the statuses of this group
    foreach ($group['elements'] as $statusType) {
        // push status button
        $statusTypes[] = [
            'id' => $statusType['id'],
            'text' => $statusType['text'],
            'color' => $statusType['color'],
        ];
    }
}

$roomInfo = null;

?>

<div class="vbo-tm-calendar-wrap">

    <div class="vbo-tm-task-details" data-task-id="<?php echo (int) $taskRegistry->getID(); ?>">

        <div class="vbo-tm-task-head">
            <div class="vbo-tm-calendar-head">
                <div class="vbo-tm-calendar-info">
                    <a href="javascript:void(0)" class="vbo-tm-calendar-day-back" data-month="<?php echo $data['filters']['calendar_month'] ?? ''; ?>" data-day="<?php echo $data['filters']['calendar_day'] ?? ''; ?>">
                        <?php echo VikBookingIcons::e('chevron-left'); ?>
                    </a>
                    <h4 class="vbo-tm-task-title"><?php echo $taskRegistry->getTitle(); ?></h4>
                </div>
            </div>

            <div class="vbo-tm-task-status-snapshot">

                <?php if ($status): ?>
                    <?php echo JHtml::fetch('vbohtml.taskmanager.status', $status); ?>
                <?php endif; ?>

                <?php if ($dueDate = $taskRegistry->getDueDate()): ?>
                    <div class="vbo-tm-task-due date">
                        <?php VikBookingIcons::e('calendar-day'); ?>
                        <span><?php echo JHtml::fetch('date', $dueDate, 'd F Y'); ?></span>
                    </div>
                    <div class="vbo-tm-task-due time">
                        <?php VikBookingIcons::e('stopwatch'); ?>
                        <?php if ($assignees): ?>
                            <input type="time" class="task-time-picker" value="<?php echo JHtml::fetch('date', $dueDate, 'H:i'); ?>" />
                        <?php else: ?>
                            <span><?php echo JHtml::fetch('date', $dueDate, 'H:i'); ?></span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

            </div>
        </div>

        <div class="vbo-tm-task-body">

            <div class="vbo-tm-task-info">

                <div class="vbo-tm-task-summary">

                    <div class="vbo-tm-task-summary-block vbo-tm-task-assignees">
                        <div class="vbo-tm-task-summary-lbl">
                            <span><?php echo JText::translate('VBO_ASSIGNEES'); ?></span>
                        </div>
                        <div class="vbo-tm-task-summary-cont">
                        <?php
                        foreach ($assignees as $operator) {
                            ?>
                            <span class="vbo-tm-list-area-task-assignee vbo-tm-task-assignee">
                                <span class="vbo-tm-list-area-task-assignee-avatar vbo-tm-task-assignee-avatar" title="<?php echo JHtml::fetch('esc_attr', $operator['name']); ?>">
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

                        if (!$assignees) {
                            ?>
                            <span class="vbo-tm-task-no-assignees"><?php echo JText::translate('VBO_TASK_NO_ASSIGNEES'); ?></span>
                            <?php
                        }
                        ?>
                        </div>
                    </div>

                <?php
                if ($taskRegistry->getListingId()) {
                    $roomInfo = VikBooking::getRoomInfo($taskRegistry->getListingId(), $columns = ['name', 'params'], $no_cache = true);
                    $roomInfo['params'] = json_decode($roomInfo['params']);
                    $geo = $roomInfo['params']->geo ?? null;
                    ?>
                    <div class="vbo-tm-task-summary-block vbo-tm-task-listing-info">
                        <div class="vbo-tm-task-summary-lbl">
                            <span><?php echo JText::translate('VBO_LISTING'); ?></span>
                        </div>
                        <div class="vbo-tm-task-summary-cont">
                            <?php if (!empty($geo->enabled) && !empty($geo->latitude) && !empty($geo->longitude)): ?>
                                <a href="javascript:void(0)" class="listing-name open-map"><?php VikBookingIcons::e('home'); ?><?php echo $roomInfo['name']; ?></a>
                            <?php else: ?>
                                <span class="listing-name"><?php VikBookingIcons::e('home'); ?> <?php echo $roomInfo['name']; ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php
                }

                if ($taskRegistry->getBookingId() && $bookingElement = $taskRegistry->buildBookingElement()) {
                    if (!empty($bookingElement['img'])) {
                        $img = '<img src="' . $bookingElement['img'] . '" class="vbo-booking-badge-avatar" decoding="async" loading="lazy" />';
                    } else {
                        $img = '<span class="vbo-booking-badge-avatar"><i class="' . VikBookingIcons::i($bookingElement['icon_class'] ?? 'hotel') . '"></i></span>';
                    }

                    $bookingText = $bookingElement['text'] . ' #' . $bookingElement['id'];
                    ?>
                    <div class="vbo-tm-task-summary-block vbo-tm-task-booking-info">
                        <div class="vbo-tm-task-summary-lbl">
                            <span><?php echo JText::translate('VBORDNOL'); ?></span>
                        </div>
                        <div class="vbo-tm-task-summary-cont">
                            <?php echo $img; ?>
                            <span class="guest-name"><?php echo $bookingText; ?></span>
                        </div>
                    </div>
                    <?php
                }
                ?>

                </div>

                <?php if ($tags): ?>
                <div class="vbo-tm-task-tags">
                <?php
                foreach ($tags as $tag) {
                    $tag = (array) $tag;
                    ?>
                    <span class="vbo-tm-task-tag vbo-tm-color <?php echo $tag['color']; ?>"><?php echo $tag['name']; ?></span>
                    <?php
                }
                ?>
                </div>
                <?php endif; ?>

                <?php if ($status && ($statusDisplay = $status->display($taskRegistry))): ?>
                    <div class="vbo-tm-task-status-notes"><?php echo $statusDisplay; ?></div>
                <?php endif; ?>

                <?php if ($notes = $taskRegistry->getNotes()): ?>
                    <div class="vbo-tm-task-notes"><?php echo $notes; ?></div>
                <?php endif; ?>

                <div class="vbo-tm-task-listing-map" style="display: none; width: 100%; height: 500px; margin-top: 10px;">
                    
                </div>

            </div>

            <div class="vbo-tm-task-chat">
            <?php
            // get the chat mediator
            $chat = VBOFactory::getChatMediator();
            // build chat for the given context
            $chat_context = $chat->createContext('task', $taskRegistry->getID());
            // display the chat for the current task context
            echo $chat->render($chat_context, [
                'assets' => false,
            ]);
            ?>
            </div>

        </div>

    </div>

</div>

<script type="text/javascript">
    VBOCore.DOMLoaded(() => {
        // register click event on calendar backward navigation
        document.querySelectorAll('.vbo-tm-calendar-day-back').forEach((backBtn) => {
            backBtn.addEventListener('click', () => {
                let navMonth = backBtn.getAttribute('data-month');
                let navDay = backBtn.getAttribute('data-day');
                let navType = navDay ? 'day' : 'month';
                VBOCore.emitEvent('vbo-tm-apply-filters', {
                    filters: {
                        calendar_type: navType,
                        calendar_month: navMonth || '<?php echo date('Y-m-01'); ?>',
                        calendar_day: navDay,
                    },
                });
            });
        });

        // register change event on due time picker
        document.querySelectorAll('.task-time-picker').forEach((timePicker) => {
            let currentTime = timePicker.value;

            timePicker.addEventListener('blur', () => {
                if (timePicker.value == currentTime) {
                    // nothing has change, do not proceed with the update
                    return;
                }

                timePicker.disabled = true;

                // make the request
                VBOCore.doAjax(
                    '<?php echo VikBooking::ajaxUrl('index.php?option=com_vikbooking&task=taskmanager.updateTask'); ?>',
                    {
                        data: {
                            id: <?php echo $taskId ?>,
                            dueon: '<?php echo JHtml::fetch('date', $dueDate, 'Y-m-d'); ?> ' + timePicker.value,
                        }
                    },
                    (resp) => {
                        timePicker.disabled = false;
                        currentTime = timePicker.value;
                    },
                    (error) => {
                        // display error message
                        alert(error.responseText);

                        timePicker.disabled = false;
                    }
                );
            });
        });

        let checklistQueue = [], isUpdatingChecklist = false;

        const updateChecklist = (checklistTask) => {
            // update checklist task
            jQuery(checklistTask.node).attr('data-checked', checklistTask.status ? 'true' : 'false');

            if (isUpdatingChecklist) {
                checklistQueue.push(checklistTask);
                return;
            }

            isUpdatingChecklist = true;

            // make the request
            VBOCore.doAjax(
                '<?php echo VikBooking::ajaxUrl('index.php?option=com_vikbooking&task=taskmanager.updateChecklist'); ?>',
                {
                    id: <?php echo $taskId ?>,
                    index: checklistTask.index + 1,
                    status: checklistTask.status ? 1 : 0,
                },
                (resp) => {
                    isUpdatingChecklist = false;

                    if (checklistQueue.length) {
                        // move to the next one
                        updateChecklist(checklistQueue.shift());
                    }
                },
                (error) => {
                    // display error message
                    alert(error.responseText);

                    // restore previous value
                    jQuery(checklistTask.node).attr('data-checked', checklistTask.status ? 'false' : 'true');

                    isUpdatingChecklist = false;

                    if (checklistQueue.length) {
                        // move to the next one
                        updateChecklist(checklistQueue.shift());
                    }
                }
            );
        }

        jQuery('.vbo-tm-task-notes ul[data-checked]').on('click', function(event) {
            if (event.originalEvent.offsetX > 16) {
                // simulate click only on the pseudo-element
                return false;
            }

            // fetch the nth-index of the clicked checkbox
            const index = jQuery('.vbo-tm-task-notes ul[data-checked]').index(this);
            const status = jQuery(this).attr('data-checked') === 'true' ? false : true;

            updateChecklist({
                index: index,
                status: status,
                node: this,
            });
        });

        <?php if (!empty($roomInfo)): ?>
            jQuery('.listing-name.open-map').on('click', () => {
                const mapTarget = jQuery('.vbo-tm-task-listing-map');

                if (mapTarget.is(':visible')) {
                    mapTarget.hide();
                    jQuery('.vbo-tm-task-notes').show();
                } else {
                    jQuery('.vbo-tm-task-notes').hide();
                    mapTarget.show();
                }

                initializeListingMap(mapTarget[0]);
            });

            let mapInitialized = false;

            const initializeListingMap = async (target) => {
                if (mapInitialized) {
                    return;
                }

                mapInitialized = true;

                // init libraries
                const { Map, InfoWindow } = await google.maps.importLibrary('maps');
                const { AdvancedMarkerElement } = await google.maps.importLibrary('marker');

                const position = {
                    lat: <?php echo floatval($roomInfo['params']->geo->latitude ?? null); ?>,
                    lng: <?php echo floatval($roomInfo['params']->geo->longitude ?? null); ?>,
                };

                const map = new Map(target, {
                    zoom: 14,
                    center: position,
                    mapId: 'listing_location',
                });

                const popup = new InfoWindow({
                    content: <?php echo json_encode($roomInfo['params']->geo->address ?? $roomInfo['name']); ?>,
                    ariaLabel: <?php echo json_encode($roomInfo['name']); ?>,
                });

                const marker = new AdvancedMarkerElement({
                    position: position,
                    map: map,
                    title: <?php echo json_encode($roomInfo['name']); ?>,
                });

                marker.addListener('gmp-click', () => {
                    popup.open({
                        anchor: marker,
                        map: map,
                    });
                });
            }
        <?php endif; ?>

        VBOCore.emitEvent('vbo-tm-contents-loaded', {
            element: document.querySelector('.vbo-tm-task-details'),
            statuses: <?php echo json_encode($taskArea->getStatuses()); ?>,
        });
    });
</script>