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
 * @var string  $uri
 * @var array   $context
 * @var array   $messages
 * @var array   $users
 * @var object  $user
 * @var array   $options
 */
extract($displayData);

$id = 'chat-' . $context['alias'] . '-' . $context['id'] . '-' . $options['suffix'];

?>

<div class="vbo-chat-wrapper" id="<?php echo $id; ?>">

    <div class="chat-messages-panel">

        <div class="chat-conversation">

        </div>

        <div class="chat-input-footer">
            <div class="textarea-input"></div>

            <div class="chat-uploads-bar" style="display:none;">
                <div class="chat-progress-wrap"></div>
                <div class="chat-uploads-tab"></div>
            </div>
        </div>

    </div>

</div>

<?php if (isset($messages)): ?>
    <script>
        (function($) {
            'use strict';

            $(function() {
                VBOChat.getInstance({
                    environment: {
                        url: '<?php echo $uri; ?>',
                        messages: <?php echo json_encode($messages) ?>,
                        users: <?php echo json_encode($users); ?>,
                        user: <?php echo json_encode($user); ?>,
                        context: <?php echo json_encode($context); ?>,
                        options: <?php echo json_encode($options); ?>,
                        selector: '#<?php echo $id; ?>',
                    },
                }).prepare();
            });
        })(jQuery);
    </script>
<?php endif; ?>