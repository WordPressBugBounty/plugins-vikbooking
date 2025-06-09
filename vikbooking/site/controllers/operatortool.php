<?php
/**
 * @package     VikBooking
 * @subpackage  com_vikbooking
 * @author      Alessio Gaggii - E4J srl
 * @copyright   Copyright (C) 2025 E4J srl. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 * @link        https://vikwp.com
 */

// No direct access to this file
defined('ABSPATH') or die('No script kiddies please!');

/**
 * VikBooking operator-tool controller.
 *
 * @since   1.17.6 (J) - 1.7.6 (WP)
 */
class VikBookingControllerOperatortool extends JControllerAdmin
{
    /**
     * AJAX endpoint for the tool "guest_messaging" to load threads assigned
     * to manageable listing IDs by the currently logged operator account.
     */
    public function loadGuestThreads()
    {
        $app = JFactory::getApplication();

        if (!JSession::checkToken()) {
            // missing CSRF-proof token
            VBOHttpDocument::getInstance($app)->close(403, JText::translate('JINVALID_TOKEN'));
        }

        // the name of the tool to access
        $tool = 'guest_messaging';

        try {
            // obtain data from the validation of the current operator and tool permissions
            list($operator, $permissions, $tool_uri) = VikBooking::getOperatorInstance()->authOperatorToolData($tool);
        } catch (Exception $e) {
            // abort
            VBOHttpDocument::getInstance($app)->close($e->getCode(), $e->getMessage());
        }

        // get listings assigned to the current operator (no listings equals to all listings)
        $listings = array_filter(
            array_map('intval', (array) $permissions->get('rooms', []))
        );

        // attempt to require the chat handler from VCM
        try {
            VikBooking::getVcmChatInstance($oid = 0, $channel = null);
        } catch (Throwable $e) {
            // propagate the error code
            VBOHttpDocument::getInstance()->close($e->getCode(), 'Channel Manager not available.');
        }

        // make sure VCM is available
        if (!class_exists('VCMChatHandler')) {
            // raise an error
            VBOHttpDocument::getInstance()->close(500, 'Channel Manager not available.');
        }

        // current year Y and timestamp
        $current_y  = date('Y');
        $today_ymd  = date('Y-m-d');
        $current_ts = time();
        $nowdf = VikBooking::getDateFormat(true);
        if ($nowdf == "%d/%m/%Y") {
            $df = 'd/m/Y';
        } elseif ($nowdf == "%m/%d/%Y") {
            $df = 'm/d/Y';
        } else {
            $df = 'Y/m/d';
        }

        // load latest threads for the operator manageable listings
        $threads = VCMChatHandler::getLatestThreads([
            'sender'      => 'guest',
            'join_sender' => true,
            'rooms'       => $listings,
            'start'       => $app->input->getUInt('start', 0),
            'limit'       => $app->input->getUInt('limit', 20),
        ]);

        // map the threads by fetching the channel logo, if available
        $threads = array_map(function($thread) {
            if (empty($thread->channel) || !strcasecmp($thread->channel, 'vikbooking')) {
                return $thread;
            }
            $channel_logo = VikChannelManager::getLogosInstance($thread->channel)->getSmallLogoURL();
            if (!empty($channel_logo)) {
                $thread->channel_logo = $channel_logo;
            }
            return $thread;
        }, $threads);

        // map the threads by appending the message date/time and status information
        $threads = array_map(function($thread) use ($df, $current_y, $today_ymd) {
            $str_checkin  = '';
            $str_checkout = '';
            if (!empty($thread->b_checkin)) {
                $stay_info_in  = getdate($thread->b_checkin);
                $stay_info_out = getdate($thread->b_checkout);
                $str_checkin = date('d', $thread->b_checkin);
                $str_checkin .= $stay_info_in['mon'] != $stay_info_out['mon'] ? ' ' . VikBooking::sayMonth($stay_info_in['mon'], $short = true) : '';
                $str_checkout = date('d', $thread->b_checkout) . ' ' . VikBooking::sayMonth($stay_info_out['mon'], $short = true);
                if ($stay_info_in['year'] != $stay_info_out['year'] || $stay_info_in['year'] != $current_y || $stay_info_out['year'] != $current_y) {
                    $str_checkout .= ' ' . $stay_info_in['year'];
                }
            }

            $thread->message_info = [
                'current_ts'   => $current_ts,
                'str_checkin'  => $str_checkin,
                'str_checkout' => $str_checkout,
                'time'         => JHtml::fetch('date', $thread->last_updated, 'H:i'),
                'date'         => JHtml::fetch('date', $thread->last_updated, str_replace('/', VikBooking::getDateSeparator(), $df)),
                'is_today'     => (JHtml::fetch('date', $thread->last_updated, 'Y-m-d') == $today_ymd),
            ];

            return $thread;
        }, $threads);

        // send response to output
        VBOHttpDocument::getInstance($app)->json($threads);
    }

    /**
     * AJAX endpoint for the tool "guest_messaging" to render the chat for a booking made
     * for at least one manageable listing ID by the currently logged operator account.
     */
    public function renderGuestBookingChat()
    {
        $app = JFactory::getApplication();

        if (!JSession::checkToken()) {
            // missing CSRF-proof token
            VBOHttpDocument::getInstance($app)->close(403, JText::translate('JINVALID_TOKEN'));
        }

        // the name of the tool to access
        $tool = 'guest_messaging';

        try {
            // obtain data from the validation of the current operator and tool permissions
            list($operator, $permissions, $tool_uri) = VikBooking::getOperatorInstance()->authOperatorToolData($tool);
        } catch (Exception $e) {
            // abort
            VBOHttpDocument::getInstance($app)->close($e->getCode(), $e->getMessage());
        }

        // get listings assigned to the current operator (no listings equals to all listings)
        $listings = array_filter(
            array_map('intval', (array) $permissions->get('rooms', []))
        );

        // the booking ID for which the chat should be rendered
        $bid = $app->input->getUInt('bid', 0);

        if (!$bid) {
            VBOHttpDocument::getInstance($app)->close(400, 'Missing booking ID.');
        }

        $booking = VikBooking::getBookingInfoFromID($bid);
        if (!$booking) {
            VBOHttpDocument::getInstance($app)->close(404, 'Booking record not found.');
        }

        $booking_rooms = VikBooking::loadOrdersRoomsData($booking['id']);
        $booking_room_ids = array_column($booking_rooms, 'idroom');
        if (!$booking_rooms || !$booking_room_ids) {
            VBOHttpDocument::getInstance($app)->close(404, 'No booking rooms found.');
        }

        // ensure at least one listing of this booking is manageable by the operator permissions
        if ($listings && !array_intersect($listings, $booking_room_ids)) {
            VBOHttpDocument::getInstance($app)->close(403, 'Cannot access booking conversation.');
        }

        // initialize chat instance by getting the proper channel name
        if (empty($booking['channel'])) {
            // front-end reservation chat handler
            $chat_channel = 'vikbooking';
        } else {
            $channelparts = explode('_', $booking['channel']);
            // check if this is a meta search channel
            $is_meta_search = false;
            if (preg_match("/(customer).*[0-9]$/", $channelparts[0]) || !strcasecmp($channelparts[0], 'googlehotel') || !strcasecmp($channelparts[0], 'googlevr') || !strcasecmp($channelparts[0], 'trivago')) {
                $is_meta_search = empty($booking['idorderota']);
            }
            if ($is_meta_search) {
                // customer of type sales channel should use front-end reservation chat handler
                $chat_channel = 'vikbooking';
            } else {
                // let the getInstance method validate the channel chat handler
                $chat_channel = $booking['channel'];
            }
        }

        $messaging = VikBooking::getVcmChatInstance($booking['id'], $chat_channel);

        if (is_null($messaging)) {
            VBOHttpDocument::getInstance($app)->close(500, 'Could not render chat.');
        }

        // send response to output
        $chat = $messaging->renderChat([
            'hideThreads' => 1,
        ], $load_assets = false);

        VBOHttpDocument::getInstance($app)->json(['html' => $chat]);
    }

    /**
     * AJAX endpoint for the tool "guest_messaging" to load the listing details for a booking
     * made for at least one manageable listing ID by the currently logged operator account.
     */
    public function loadGuestBookingListings()
    {
        $app = JFactory::getApplication();

        if (!JSession::checkToken()) {
            // missing CSRF-proof token
            VBOHttpDocument::getInstance($app)->close(403, JText::translate('JINVALID_TOKEN'));
        }

        // the name of the tool to access
        $tool = 'guest_messaging';

        try {
            // obtain data from the validation of the current operator and tool permissions
            list($operator, $permissions, $tool_uri) = VikBooking::getOperatorInstance()->authOperatorToolData($tool);
        } catch (Exception $e) {
            // abort
            VBOHttpDocument::getInstance($app)->close($e->getCode(), $e->getMessage());
        }

        // get listings assigned to the current operator (no listings equals to all listings)
        $listings = array_filter(
            array_map('intval', (array) $permissions->get('rooms', []))
        );

        // the booking ID for which the listing details should be fetched
        $bid = $app->input->getUInt('bid', 0);

        $booking_rooms = VikBooking::loadOrdersRoomsData($bid);
        $booking_room_ids = array_column($booking_rooms, 'idroom');
        if (!$booking_rooms || !$booking_room_ids) {
            VBOHttpDocument::getInstance($app)->close(404, 'No booking rooms found.');
        }

        // ensure at least one listing of this booking is manageable by the operator permissions
        if ($listings && !array_intersect($listings, $booking_room_ids)) {
            VBOHttpDocument::getInstance($app)->close(403, 'Cannot access booking or listing details.');
        }

        // send response to output
        VBOHttpDocument::getInstance($app)->json(['listings' => array_column($booking_rooms, 'room_name')]);
    }

    /**
     * AJAX endpoint for the tool "guest_messaging" to toggle the no-reply-needed thread status for a
     * booking made for at least one manageable listing ID by the currently logged operator account.
     */
    public function toggleGuestThreadNoReplyNeeded()
    {
        $app = JFactory::getApplication();
        $dbo = JFactory::getDbo();

        if (!JSession::checkToken()) {
            // missing CSRF-proof token
            VBOHttpDocument::getInstance($app)->close(403, JText::translate('JINVALID_TOKEN'));
        }

        // the name of the tool to access
        $tool = 'guest_messaging';

        try {
            // obtain data from the validation of the current operator and tool permissions
            list($operator, $permissions, $tool_uri) = VikBooking::getOperatorInstance()->authOperatorToolData($tool);
        } catch (Exception $e) {
            // abort
            VBOHttpDocument::getInstance($app)->close($e->getCode(), $e->getMessage());
        }

        // get listings assigned to the current operator (no listings equals to all listings)
        $listings = array_filter(
            array_map('intval', (array) $permissions->get('rooms', []))
        );

        // the booking ID for which the listing details should be fetched
        $bid = $app->input->getUInt('bid', 0);

        $booking_rooms = VikBooking::loadOrdersRoomsData($bid);
        $booking_room_ids = array_column($booking_rooms, 'idroom');
        if (!$booking_rooms || !$booking_room_ids) {
            VBOHttpDocument::getInstance($app)->close(404, 'No booking rooms found.');
        }

        // ensure at least one listing of this booking is manageable by the operator permissions
        if ($listings && !array_intersect($listings, $booking_room_ids)) {
            VBOHttpDocument::getInstance($app)->close(403, 'Cannot access booking or listing details.');
        }

        // gather thread ID and toggle status
        $id_thread = $app->input->getUInt('id_thread', 0);
        $status    = $app->input->getUInt('status', 0);

        if (empty($id_thread)) {
            VBOHttpDocument::getInstance($app)->close(400, 'Missing booking thread.');
        }

        // update the information on the database
        $dbo->setQuery(
            $dbo->getQuery(true)
                ->update($dbo->qn('#__vikchannelmanager_threads'))
                ->set($dbo->qn('no_reply_needed') . ' = ' . (!$status ? 1 : 0))
                ->where($dbo->qn('id') . ' = ' . $id_thread)
                ->where($dbo->qn('idorder') . ' = ' . $bid)
        );
        $dbo->execute();

        // send response to output
        VBOHttpDocument::getInstance($app)->json(['status' => (!$status ? 1 : 0)]);
    }

    /**
     * AJAX endpoint for the tool "guest_messaging" to check for new thread messages.
     */
    public function checkNewGuestThreads()
    {
        $app = JFactory::getApplication();

        if (!JSession::checkToken()) {
            // missing CSRF-proof token
            VBOHttpDocument::getInstance($app)->close(403, JText::translate('JINVALID_TOKEN'));
        }

        // the name of the tool to access
        $tool = 'guest_messaging';

        try {
            // obtain data from the validation of the current operator and tool permissions
            list($operator, $permissions, $tool_uri) = VikBooking::getOperatorInstance()->authOperatorToolData($tool);
        } catch (Exception $e) {
            // abort
            VBOHttpDocument::getInstance($app)->close($e->getCode(), $e->getMessage());
        }

        // get listings assigned to the current operator (no listings equals to all listings)
        $listings = array_filter(
            array_map('intval', (array) $permissions->get('rooms', []))
        );

        // attempt to require the chat handler from VCM
        try {
            VikBooking::getVcmChatInstance($oid = 0, $channel = null);
        } catch (Throwable $e) {
            // propagate the error code
            VBOHttpDocument::getInstance()->close($e->getCode(), 'Channel Manager not available.');
        }

        // make sure VCM is available
        if (!class_exists('VCMChatHandler')) {
            // raise an error
            VBOHttpDocument::getInstance()->close(500, 'Channel Manager not available.');
        }

        // load the latest thread message for the operator manageable listings
        $threads = VCMChatHandler::getLatestThreads([
            'sender'      => 'guest',
            'join_sender' => true,
            'rooms'       => $listings,
            'start'       => $app->input->getUInt('start', 0),
            'limit'       => $app->input->getUInt('limit', 1),
        ]);

        if (!$threads) {
            // no thread messages at all
            VBOHttpDocument::getInstance()->json(['newThreads' => []]);
        }

        // get the latest date requested
        $last_date = $app->input->getString('last_date');

        if (!$last_date) {
            // all thread messages are considered as new
            VBOHttpDocument::getInstance()->json(['newThreads' => $threads]);
        }

        // filter threads based on the given last date
        $threads = array_filter($threads, function($thread) use ($last_date) {
            return isset($thread->last_updated) && strtotime($thread->last_updated) > strtotime($last_date);
        });

        // reset keys
        $threads = array_values($threads);

        // send response to output
        VBOHttpDocument::getInstance()->json(['newThreads' => $threads]);
    }

    /**
     * Base endpoint to generate an iCal calendar file for the task manager events (tasks)
     * of an operator. The authentication is performed through GET via the iCal URL.
     * 
     * @since   1.18.0 (J) - 1.8.0 (WP)
     */
    public function tm_ical()
    {
        $app = JFactory::getApplication();

        // gather request values for authentication (base64(id:md5(auth_code)))
        $operator_signature = base64_decode(urldecode((string) $app->input->getBase64('opsid', '')));

        // validate signature syntax
        if (!preg_match('/^([0-9]+):([0-9a-f]{32})$/i', $operator_signature, $matches)) {
            VBOHttpDocument::getInstance($app)->close(400, 'Bad URL.');
        }

        $operatorId = (int) $matches[1];
        $operatorHashedCode = $matches[2];

        // get the operator record by ID
        $record = VikBooking::getOperatorInstance()->getOne($operatorId);

        // attempt to perform a manual authentication
        if (!$record || md5((string) $record['code']) != $operatorHashedCode || !VikBooking::getOperatorInstance()->authOperator($record['code'])) {
            VBOHttpDocument::getInstance($app)->close(401, 'Unauthorized');
        }

        // the name of the tool to access
        $tool = 'task_manager';

        try {
            // obtain data from the validation of the current operator and tool permissions
            list($operator, $permissions, $tool_uri) = VikBooking::getOperatorInstance()->authOperatorToolData($tool);
        } catch (Exception $e) {
            // abort
            VBOHttpDocument::getInstance($app)->close($e->getCode(), $e->getMessage());
        }

        // check from the permissions whether tasks can be accepted by the operator,
        // hence null assigness should be included - use a different filter otherwise
        $tasksFilterName = ((bool) $permissions->get('accept_tasks', 0)) ? 'operator' : 'assignee';

        // get operator (future) tasks list for one year worth of dates
        $tasks = VBOTaskModelTask::getInstance()->filterItems([
            $tasksFilterName => $operator['id'],
            'dates' => sprintf('%s:%s', date('Y-m-d'), date('Y-m-d', strtotime('+1 year'))),
        ]);

        // send headers to output the calendar
        header('Content-type: text/calendar; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . sprintf('%d-%s-%s', $operator['id'], (string) $operator['first_name'], date('Y-m-d')) . '.ics');

        // build and send to output the calendar content
        echo VBOTaskOperatorIcal::getInstance()
            ->setOperator($operator)
            ->setPermissions($permissions)
            ->setTool($tool)
            ->setToolUri($tool_uri)
            ->setEvents($tasks)
            ->toString();

        // close the application
        $app->close();
    }

    /**
     * AJAX endpoint to render a layout file from an operator tool.
     * 
     * @return  void
     */
    public function renderLayout()
    {
        $app = JFactory::getApplication();

        if (!JSession::checkToken()) {
            // missing CSRF-proof token
            VBOHttpDocument::getInstance($app)->close(403, JText::translate('JINVALID_TOKEN'));
        }

        $tool = $app->input->getString('tool', '');
        $type = $app->input->getString('type', '');
        $data = (array) $app->input->get('data', [], 'array');

        try {
            // obtain data from the validation of the current operator and tool permissions
            list($operator, $permissions, $tool_uri) = VikBooking::getOperatorInstance()->authOperatorToolData($tool);
        } catch (Exception $e) {
            // abort
            VBOHttpDocument::getInstance($app)->close($e->getCode(), $e->getMessage());
        }

        if (empty($type)) {
            // invalid layout requested
            VBOHttpDocument::getInstance($app)->close(404, sprintf('Could not find the layout [%s] to render.', $type));
        }

        // fetch the requested layout
        $layout_data = [
            'tool'        => $tool,
            'operator'    => $operator,
            'permissions' => $permissions,
            'tool_uri'    => $tool_uri,
            'data'        => $data,
        ];

        try {
            $layout_html = JLayoutHelper::render($type, $layout_data, null, [
                'component' => 'com_vikbooking',
                'client'    => 'site',
            ]);
        } catch (Exception $e) {
            // raise the error caught
            VBOHttpDocument::getInstance($app)->close($e->getCode() ?: 500, $e->getMessage());
        }

        // send the response to output
        VBOHttpDocument::getInstance($app)->json([
            'html' => $layout_html,
        ]);
    }
}
