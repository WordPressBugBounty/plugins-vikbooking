<?php
/** 
 * @package     VikBooking
 * @subpackage  core
 * @author      E4J s.r.l.
 * @copyright   Copyright (C) 2026 E4J s.r.l. All Rights Reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * @link        https://vikwp.com
 */

// No direct access
defined('ABSPATH') or die('No script kiddies please!');

// build layout data
$layoudData = [
    'quote'        => $this->quote,
    'customer'     => $this->customer,
    'inquiry'      => $this->inquiry,
    'session_id'   => $this->chatSessionId,
    'prefMessages' => $this->prefMessages,
];

// render HTML layout
echo JLayoutHelper::render('quote.manage.html', $layoudData);

// render script layout
echo JLayoutHelper::render('quote.manage.script', $layoudData);

?>
<form action="index.php?option=com_vikbooking" method="post" name="adminForm" id="adminForm">
    <input type="hidden" name="option" value="com_vikbooking" />
    <input type="hidden" name="task" value="" />
</form>

<script type="text/javascript">
    Joomla.submitbutton = function(task) {
        if (task == 'quote-apply') {
            // intercept button to scroll to the saving section
            document.querySelector('.vbo-quote-section-buttons')?.scrollIntoView({
                behavior: 'smooth',
                block: 'start',
            });
        } else if (task == 'quote-see-all') {
            // intercept toolbar button to open the quotes admin-widget
            VBOCore.handleDisplayWidgetNotification({widget_id: 'quotes'});
        } else {
            Joomla.submitform(task, document.adminForm);
        }
    }
</script>