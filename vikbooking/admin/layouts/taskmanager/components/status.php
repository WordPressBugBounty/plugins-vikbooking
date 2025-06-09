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
 * @var VBOTaskStatusInterface  $status
 * @var bool                    $editable
 * @var string                  $class
 */
extract($displayData);

if (!is_array($class)) {
    $class = explode(' ', $class);
}

// inject default classes
$class = array_merge([
    'vbo-tm-task-status-badge',
    'vbo-tm-color',
    $status->getColor(),
], $class);

if ($editable) {
    // inject extra classes to allow editing
    $class = array_merge($class, [
        'change-status-trigger',
        'vik-context-menu-disable-selection',
    ]);
}

?>

<span
    class="<?php echo $this->escape(implode(' ', $class)); ?>"
    data-status="<?php echo $this->escape($status->getEnum()); ?>"
    data-color="<?php echo $this->escape($status->getColor()); ?>"
><?php echo $status->getName(); ?></span>
