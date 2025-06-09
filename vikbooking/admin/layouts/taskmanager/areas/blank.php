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

?>

<style>
    .vbo-tm-areas-blank-pane {
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        max-width: 600px;
        margin: 50px auto;
        gap: 20px;
        text-align: center;
    }
    .vbo-tm-areas-blank-pane .blank-icon {
        font-size: 128px;
        margin-bottom: 25px;
    }
    .vbo-tm-areas-blank-pane .blank-title {
        font-size: 2.5em;
        line-height: 1em;
        font-weight: 500;
        margin-bottom: 10px;
    }
    .vbo-tm-areas-blank-pane .blank-subtitle {
        font-size: 1.5em;
        line-height: 1.5em;
    }
</style>

<div class="vbo-tm-areas-blank-pane">
    
    <div class="blank-icon">
        <?php VikBookingIcons::e('tasks'); ?>
    </div>

    <div class="blank-title">
        <?php echo JText::translate('VBO_TM_AREAS_BLANK_TITLE'); ?>
    </div>

    <div class="blank-subtitle">
        <?php echo JText::translate('VBO_TM_AREAS_BLANK_SUBTITLE'); ?>
    </div>

    <div class="blank-actions">
        <button type="button" class="btn btn-success" id="blank-area-new-trigger">
            <?php echo JText::translate('VBO_TM_AREAS_BLANK_ADD_BTN'); ?>
        </button>
    </div>

</div>

<script>
    (function($) {
        'use strict';

        $('#blank-area-new-trigger').on('click', () => {
            VBOCore.emitEvent('vbo-tm-area-create-trigger');
        });
    })(jQuery);
</script>