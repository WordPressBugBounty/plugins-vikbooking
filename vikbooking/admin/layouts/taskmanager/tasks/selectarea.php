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
 * @var array  $data    The data for rendering the areas/projects for single selection.
 */
extract($displayData);

// determine the JS event to dispatch upon the selection of an area/project
$select_event = $data['select_event'] ?? 'vbo-tm-area-id-selected';

?>
<div class="vbo-tm-areapicker-list">
    <?php
    // iterate over all the existing task areas
    foreach (VBOTaskModelArea::getInstance()->getItems() as $area):
        // wrap the area record into a registry
        $taskArea = VBOTaskArea::getInstance((array) $area);
        ?>
        <div class="selectable-area-container">
            <div class="area-info">
                <div class="area-name">
                    <?php VikBookingIcons::e($taskArea->getIcon()); ?>&nbsp;<?php echo $taskArea->getName(); ?>
                </div>
                <?php if ($comments = $taskArea->get('comments')): ?>
                    <div class="area-comments">
                        <?php echo $comments; ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="area-actions">
                <button type="button" class="btn btn-success" data-area-id="<?php echo $taskArea->getID(); ?>" data-area-name="<?php echo $this->escape($taskArea->getName()); ?>"><?php echo JText::translate('VBO_SELECT'); ?></button>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<script>
    /**
     * Register the click events over the various areas/projects.
     */
    document.querySelectorAll('.vbo-tm-areapicker-list button[data-area-id]')
        .forEach((button) => {
            button.addEventListener('click', (e) => {
                VBOCore.emitEvent('<?php echo $select_event; ?>', {
                    area: {
                        id: button.getAttribute('data-area-id'),
                        name: button.getAttribute('data-area-name'),
                    },
                });
            });
        });
</script>