<?php
/** 
 * @package     VikBooking - Libraries
 * @subpackage  html.vcm.version
 * @author      E4J s.r.l.
 * @copyright   Copyright (C) 2026 E4J s.r.l. All Rights Reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * @link        https://vikwp.com
 */

// No direct access
defined('ABSPATH') or die('No script kiddies please!');

// obtain the current page from arguments
$page = (string) ($displayData['page'] ?? null);

?>
<style>
    #vbo-vcm-outdated-notice {
        display: block !important;
    }

    #vbo-vcm-outdated-notice > div {
        display: flex;
        align-items: center;
        gap: 10px;
    }
</style>
<div class="notice notice-warning page-<?php echo esc_attr(basename($page, '.php')); ?>" id="vbo-vcm-outdated-notice">
    <div>
        <span><?php _e('Your Channel Manager version is outdated. Please update it to not lose functionalities.', 'vikbooking'); ?></span>
        <a class="<?php echo strpos($page, 'vik') === 0 ? 'btn btn-primary' : 'button'; ?>" href="admin.php?page=vikchannelmanager&task=update_program&force_check=1" target="_blank"><?php _e('Update', 'vikbooking'); ?></a>
    </div>
</div>