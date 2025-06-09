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
 * @var array  $data    The data for rendering the task management form.
 */
extract($displayData);

// access the task manager object
$taskManager = VBOFactory::getTaskManager();

// wrap the task record, if editing, into a registry
$task = VBOTaskTaskregistry::getRecordInstance(($data['task_id'] ?? 0));
if (!empty($data['task_id']) && !$task->getID()) {
    // raise an error
    throw new RuntimeException('Could not load task record object.', 404);
}

// get the current area/project ID
$areaId = $task->getAreaID() ?: $data['area_id'] ?? 0;

// get the current task area object wrapper
$taskArea = VBOTaskArea::getRecordInstance($areaId);

// inject current area in task registry wrapper
$task->setArea($taskArea);

// get the task area driver
$taskDriver = $taskManager->getDriverInstance($taskArea->getType(), [$taskArea]);

// get the current filters
$filters = (array) ($data['filters'] ?? []);

// get VBO application
$vbo_app = VikBooking::getVboApplication();

// load visual editor assets
$vbo_app->loadVisualEditorAssets();

if (!empty($data['task_id'])) {
    // fetch task activities history
    $history = (new VBOHistoryModelDatabase(
        new VBOTaskHistoryContext((int) $data['task_id'])
    ))->getItems();
} else {
    $history = [];
}

?>

<form method="post" action="#" id="<?php echo $data['form_id'] ?? ''; ?>" class="vbo-managetask-modal-form">
    <div class="vbo-admin-container vbo-admin-container-full vbo-admin-container-compact">
        <div class="vbo-params-wrap">
            <div class="vbo-params-container vbo-tm-task-params-container">

            <?php
            if (!empty($data['task_id'])) {
                ?>
                <input type="hidden" name="data[id]" value="<?php echo $task->getID(); ?>" />
                <?php
            }
            ?>
                <input type="hidden" name="data[id_area]" value="<?php echo $task->getAreaID(); ?>" />

                <div class="vbo-param-container" style="margin-top: 0; position: relative;">
                    <div class="vbo-param-setting vbo-param-setting-tm-task-title-wrap">
                        <input
                            type="text"
                            name="data[title]"
                            class="vbo-param-setting-tm-task-title-inp"
                            placeholder="<?php echo JHtml::fetch('esc_attr', JText::translate('VBO_ADD_TITLE')); ?>"
                            value="<?php echo JHtml::fetch('esc_attr', $task->get('title', '')); ?>"
                            maxlength="128"
                        />
                    </div>

                    <?php if (!empty($data['task_id'])): ?>
                        <a href="javascript:void(0)" class="tm-switch-main-panel" data-unread="0">
                            <?php VikBookingIcons::e('comment') ?>
                        </a>
                    <?php endif; ?>
                </div>

                <div class="vbo-tm-panel">

                    <div class="vbo-tm-panel-editor">

                        <div class="vbo-tm-editor-panel vbo-visualeditor-transparent">
                        <?php
                        echo $vbo_app->renderVisualEditor(
                            'data[notes]',
                            $task->get('notes', ''),
                            [
                                'class' => 'vbo-tm-managetask-notes',
                                'style' => 'width: 96%; height: 150px;',
                            ],
                            [
                                'modes' => [
                                    'visual',
                                    'text',
                                ],
                                'hide_modes' => true,
                                'list_check' => true,
                                'unset_buttons' => [
                                    'mailwrapper',
                                    'preview',
                                    'homelogo',
                                ],
                                'gen_ai' => [
                                    'environment' => 'taskmanager',
                                ],
                            ],
                            []
                        );
                        ?>
                        </div>

                        <?php if (!empty($data['task_id'])): ?>
                            <div class="vbo-tm-chat-panel" style="display: none; height: 0;">
                                <?php
                                $chat = VBOFactory::getChatMediator();
                                
                                echo $chat->render($chat->createContext('task', $data['task_id']), [
                                    'assets' => false,
                                    'autoread' => false,
                                ]);
                                ?>
                            </div>
                        <?php endif; ?>

                        <?php if (count($history) > 1): ?>
                            <div class="vbo-tm-history-panel" style="display: none;">
                                <?php
                                echo JLayoutHelper::render('history.timeline', [
                                    'history' => $history,
                                ]);
                                ?>
                            </div>
                        <?php endif; ?>

                    </div>

                    <div class="vbo-tm-panel-controls">

                    <?php
                    if (!empty($data['task_id'])) {
                        ?>
                        <div class="vbo-param-container">
                            <div class="vbo-param-label vbo-param-label-icn-wrap">
                                <span class="vbo-param-label-icn"><?php VikBookingIcons::e('hashtag'); ?></span>
                                <span class="vbo-param-label-txt"><?php echo JText::translate('VBO_TASK'); ?></span>
                            </div>
                            <div class="vbo-param-setting" style="display: flex; justify-content: space-between; align-items: start;">
                                <span class="badge badge-info"><?php echo $task->getID(); ?></span>

                                <?php if (count($history) > 1): ?>
                                    <a href="javascript:void(0)" class="tm-toggle-history-timeline">
                                        <?php VikBookingIcons::e('history') ?>
                                        <span><?php echo JText::translate('VBO_HISTORY_SHOW_ACTIVITIES'); ?></span>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php
                    }
                    ?>

                        <div class="vbo-param-container">
                            <div class="vbo-param-label vbo-param-label-icn-wrap">
                                <span class="vbo-param-label-icn"><?php VikBookingIcons::e('calendar'); ?></span>
                                <span class="vbo-param-label-txt"><?php echo JText::translate('VBO_DUE_DATE'); ?></span>
                            </div>
                            <div class="vbo-param-setting">
                                <?php
                                $activeDtime = ($filters['calendar_day'] ?? '') ?: ($filters['calendar_month'] ?? '');
                                if ($activeDtime && preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}/', (string) $activeDtime)) {
                                    if (!preg_match('/[0-9]{2}:[0-9]{2}$/', $activeDtime)) {
                                        // time not provided
                                        $defaultTime = $filters['calendar_time'] ?? '00:00';
                                        $activeDtime = date('Y-m-d ' . $defaultTime, strtotime($activeDtime));
                                    }
                                } else {
                                    $activeDtime = '';
                                }
                                echo $vbo_app->renderDateTimePicker([
                                    'name'  => 'data[dueon]',
                                    'value' => ($task->getDueDate(true, 'Y-m-d H:i') ?: $activeDtime ?: date('Y-m-d 00:00')),
                                ]);
                                ?>
                            </div>
                        </div>

                    <?php
                    if (!empty($data['task_id']) && ($task->getBeginDate() || !$task->isFuture())) {
                        // allow to edit the began and finished date-time values
                        ?>
                        <div class="vbo-param-container">
                            <div class="vbo-param-label vbo-param-label-icn-wrap">
                                <span class="vbo-param-label-icn"><?php VikBookingIcons::e('stopwatch'); ?></span>
                                <span class="vbo-param-label-txt"><?php echo JText::translate('VBO_BEGIN_DATE'); ?></span>
                            </div>
                            <div class="vbo-param-setting">
                                <?php
                                echo $vbo_app->renderDateTimePicker([
                                    'name'  => 'data[beganon]',
                                    'value' => $task->getBeginDate(true, 'Y-m-d H:i'),
                                ]);
                                ?>
                            </div>
                        </div>

                        <div class="vbo-param-container">
                            <div class="vbo-param-label vbo-param-label-icn-wrap">
                                <span class="vbo-param-label-icn"><?php VikBookingIcons::e('calendar-check'); ?></span>
                                <span class="vbo-param-label-txt"><?php echo JText::translate('VBO_FINISH_DATE'); ?></span>
                            </div>
                            <div class="vbo-param-setting">
                                <?php
                                echo $vbo_app->renderDateTimePicker([
                                    'name'  => 'data[finishedon]',
                                    'value' => $task->getFinishDate(true, 'Y-m-d H:i'),
                                ]);
                                ?>
                            </div>
                        </div>
                        <?php
                    }
                    ?>

                        <div class="vbo-param-container">
                            <div class="vbo-param-label vbo-param-label-icn-wrap">
                                <span class="vbo-param-label-icn"><?php VikBookingIcons::e('clipboard-check'); ?></span>
                                <span class="vbo-param-label-txt"><?php echo JText::translate('VBRENTALORD'); ?></span>
                            </div>
                            <div class="vbo-param-setting">
                                <div class="vbo-singleselect-inline-elems-wrap vbo-search-elems-wrap">
                                <?php
                                echo $vbo_app->renderSearchElementsDropDown([
                                    'id'          => 'vbo-tm-managetask-idorder',
                                    'elements'    => 'bookings',
                                    'placeholder' => JText::translate('VBO_AITOOL_SEARCH_BOOKINGS'),
                                    'allow_clear' => true,
                                    'attributes'  => [
                                        'name' => 'data[id_order]',
                                    ],
                                    'style_selection' => true,
                                    'selected_id'     => true,
                                    'selection_class' => 'vbo-sel2-selected-search-elem-full',
                                    'selected_value'  => $task->buildBookingElement((int) ($filters['id_order'] ?? 0)),
                                    'selection_click_widget' => 'booking_details',
                                ]);
                                ?>
                                </div>
                            </div>
                        </div>

                        <div class="vbo-param-container">
                            <div class="vbo-param-label vbo-param-label-icn-wrap">
                                <span class="vbo-param-label-icn"><?php VikBookingIcons::e('bed'); ?></span>
                                <span class="vbo-param-label-txt"><?php echo JText::translate('VBO_LISTING'); ?></span>
                            </div>
                            <div class="vbo-param-setting">
                                <?php
                                echo $vbo_app->renderElementsDropDown([
                                    'id'          => 'vbo-tm-managetask-idroom',
                                    'elements'    => 'listings',
                                    'element_ids' => $taskDriver->getListingIds(),
                                    'placeholder' => JText::translate('VBO_LISTING'),
                                    'allow_clear' => true,
                                    'attributes'  => [
                                        'name' => 'data[id_room]',
                                    ],
                                    'selected_value' => $task->getRoomId() ?: $filters['id_room'] ?? 0,
                                    'style_selection' => true,
                                ]);
                                ?>
                            </div>
                        </div>

                        <div class="vbo-param-container">
                            <div class="vbo-param-label vbo-param-label-icn-wrap">
                                <span class="vbo-param-label-icn"><?php VikBookingIcons::e('bullseye'); ?></span>
                                <span class="vbo-param-label-txt"><?php echo JText::translate('VBSTATUS'); ?></span>
                            </div>
                            <div class="vbo-param-setting">
                                <div class="vbo-singleselect-inline-elems-wrap vbo-tagcolors-elems-wrap vbo-statuscolors-elems-wrap">
                                <?php
                                echo $vbo_app->renderTagsDropDown([
                                    'id'          => 'vbo-tm-managetask-status',
                                    'placeholder' => JText::translate('VBSTATUS'),
                                    'allow_clear' => false,
                                    'allow_tags'  => false,
                                    'attributes'  => [
                                        'name' => 'data[status_enum]',
                                    ],
                                    'style_selection' => true,
                                    'selected_value'  => $task->getStatus(),
                                ], [], $taskArea->getStatusElements($task->getStatus()));
                                ?>
                                </div>
                            </div>
                        </div>

                        <div class="vbo-param-container vbo-param-container-full-setting">
                            <div class="vbo-param-label vbo-param-label-icn-wrap">
                                <span class="vbo-param-label-icn"><?php VikBookingIcons::e('users'); ?></span>
                                <span class="vbo-param-label-txt"><?php echo JText::translate('VBO_ASSIGNEES'); ?></span>
                            </div>
                            <div class="vbo-param-setting">
                                <div class="vbo-multiselect-inline-elems-wrap">
                                <?php
                                $activeAssignees = ($filters['assignee'] ?? null) ? [(int) $filters['assignee']] : [];
                                echo $vbo_app->renderElementsDropDown([
                                    'id'              => 'vbo-tm-managetask-assignees',
                                    'placeholder'     => JText::translate('VBO_ASSIGNEES'),
                                    'allow_clear'     => false,
                                    'attributes'      => [
                                        'name'     => 'data[assignees][]',
                                        'multiple' => 'multiple',
                                    ],
                                    'style_selection' => true,
                                    'default_selection_icon' => VikBookingIcons::i('user-tie'),
                                    'selected_values' => $task->getAssigneeIds() ?: $activeAssignees,
                                ], $taskDriver->getOperators($elements = true, $activeAssignees));
                                ?>
                                </div>
                            </div>
                        </div>

                        <div class="vbo-param-container vbo-param-container-full-setting">
                            <div class="vbo-param-label vbo-param-label-icn-wrap">
                                <span class="vbo-param-label-icn"><?php VikBookingIcons::e('tags'); ?></span>
                                <span class="vbo-param-label-txt"><?php echo JText::translate('VBO_TAGS'); ?></span>
                                <span class="vbo-inline-genai-btn">
                                    <button type="button" class="btn btn-small vbo-genai-task-tags"><?php echo JText::translate('VBO_AI_LABEL_DEF'); ?></button>
                                </span>
                            </div>
                            <div class="vbo-param-setting">
                                <div class="vbo-multiselect-inline-elems-wrap vbo-tagcolors-elems-wrap">
                                <?php
                                echo $vbo_app->renderTagsDropDown([
                                    'id'          => 'vbo-tm-managetask-tags',
                                    'placeholder' => JText::translate('VBO_TAGS'),
                                    'allow_clear' => false,
                                    'allow_tags'  => false,
                                    'attributes'  => [
                                        'name'     => 'data[tags][]',
                                        'multiple' => 'multiple',
                                    ],
                                    'style_selection' => true,
                                    'selected_values' => $task->getTags(),
                                ], $taskArea->getTagRecords());
                                ?>
                                </div>
                            </div>
                        </div>

                    </div>

                </div>

            </div>
        </div>
    </div>

</form>

<script>
    (function($) {
        'use strict';

        <?php if (!empty($data['task_id'])): ?>

            const refreshBadgeCount = (chat) => {
                // observe only the current chat
                if (chat.data.environment.context.alias != 'task' || chat.data.environment.context.id != <?php echo (int) $data['task_id']; ?>) {
                    return;
                }

                $('.tm-switch-main-panel').attr('data-unread', chat.getUnreadMessagesCount());
            }

            window.addEventListener('chat.sync', (event) => {
                refreshBadgeCount(event.detail.chat);
            });

            window.addEventListener('chat.read', (event) => {
                refreshBadgeCount(event.detail.chat);
            });

            $(function() {
                $('.vbo-tm-chat-panel .chat-input-footer').css('padding', '0 10px');

                refreshBadgeCount(VBOChat.getInstance());

                $('.tm-switch-main-panel').on('click', function() {
                    $('.vbo-tm-history-panel').hide();
                    $('.tm-toggle-history-timeline').find('span').text(<?php echo json_encode(JText::translate('VBO_HISTORY_SHOW_ACTIVITIES')); ?>);

                    const chat = VBOChat.getInstance();

                    if ($('.vbo-tm-editor-panel').is(':visible')) {
                        $('.vbo-tm-editor-panel').hide();
                        $('.vbo-tm-chat-panel').show();

                        $(this).find('i').attr('class', '<?php echo VikBookingIcons::i('comment-slash'); ?>');

                        $('.vbo-tm-chat-panel').css('max-height', ($('.vbo-tm-panel-controls').outerHeight()) + 'px');
                        $('.vbo-tm-chat-panel').css('height', ($('.vbo-tm-panel-controls').outerHeight()) + 'px');

                        // scroll chat
                        chat.scrollToBottom();

                        // auto-read messages again
                        chat.data.environment.options.autoread = true;
                        chat.readNotifications();

                        // auto-scroll modal too
                        $('.vbo-modal-overlay-content-body-scroll').scrollTop($('.vbo-modal-overlay-content-body-scroll')[0].scrollHeight + 200);
                    } else {
                        $('.vbo-tm-editor-panel').show();
                        $('.vbo-tm-chat-panel').hide();

                        $(this).find('i').attr('class', '<?php echo VikBookingIcons::i('comment'); ?>');

                        // stop auto-reading messages when the chat hasn't the focus
                        chat.data.environment.options.autoread = false;
                    }
                });

                $('.tm-toggle-history-timeline').on('click', function() {
                    if ($('.vbo-tm-history-panel').is(':visible')) {
                        $(this).find('span').text(<?php echo json_encode(JText::translate('VBO_HISTORY_SHOW_ACTIVITIES')); ?>);
                        $('.tm-switch-main-panel').trigger('click');
                    } else {
                        $(this).find('span').text(<?php echo json_encode(JText::translate('VBO_HISTORY_HIDE_ACTIVITIES')); ?>);
                        $('.vbo-tm-editor-panel, .vbo-tm-chat-panel').hide();
                        $('.vbo-tm-history-panel').show();
                    }
                });
                
                document.addEventListener('vbo-modal-dismissed', (event) => {
                    VBOChat.getInstance().destroy();
                }, {
                    once: true,
                });

            });

        <?php endif; ?>

        $(function() {
            <?php if (!$task->get('title', '')): ?>
                $('.vbo-param-setting-tm-task-title-inp').focus();
            <?php endif; ?>

            document.querySelector('.vbo-genai-task-tags').addEventListener('click', (e) => {
                let btn = e.target;

                // get button default text
                let btnDefText = btn.innerText;

                // prevent double submissions
                btn.disabled = true;

                // set icon loading animation
                btn.innerHTML = '<?php VikBookingIcons::e('circle-notch', 'fa-spin fa-fw'); ?>';

                VBOCore.doAjax(
                    "<?php echo VikBooking::ajaxUrl('index.php?option=com_vikbooking&task=ai.extractTaskTags'); ?>",
                    {
                        area_id: '<?php echo $areaId; ?>',
                        task_id: '<?php echo $data['task_id'] ?: 0; ?>',
                        task_title: document.querySelector('.vbo-param-setting-tm-task-title-inp')?.value,
                        task_notes: document.querySelector('.vbo-tm-managetask-notes')?.value,
                        booking_id: document.querySelector('#vbo-tm-managetask-idorder')?.value,
                        listing_id: document.querySelector('#vbo-tm-managetask-idroom')?.value,
                    },
                    (resp) => {
                        let tagsElement = document.querySelector('#vbo-tm-managetask-tags');

                        if (Array.isArray(resp?.tags)) {
                            resp.tags.forEach((tag) => {
                                Array.from(tagsElement.options).forEach((tagOpt) => {
                                    if (tagOpt.value == tag?.id) {
                                        tagOpt.selected = true;
                                    }
                                });
                            });

                            tagsElement.dispatchEvent(new Event('change'));
                        }

                        // restore button text
                        btn.innerHTML = '';
                        btn.innerText = btnDefText;

                        // restore the button
                        btn.disabled = false;
                    },
                    (error) => {
                        // display the error
                        alert(error.responseText || 'An error occurred');

                        // restore button text
                        btn.innerHTML = '';
                        btn.innerText = btnDefText;

                        // restore the button
                        btn.disabled = false;
                    }
                );
            });
        });
    })(jQuery);
</script>