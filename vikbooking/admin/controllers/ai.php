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
 * VikBooking AI controller. Designed to work with Vik Channel Manager.
 * Requires an active E4jConnect subscription to access the AI services.
 *
 * @since   1.17.3 (J) - 1.7.3 (WP)
 */
class VikBookingControllerAi extends JControllerAdmin
{
    /**
     * Performs an audit for the current capabilities before using the AI services.
     * 
     * @return  void
     * 
     * @throws  Exception
     */
    protected function auditServiceCapabilities()
    {
        if (!class_exists('VikChannelManager')) {
            throw new Exception('The Channel Manager plugin must be installed and activated.', 400);
        }

        if (!defined('VikChannelManagerConfig::AI')) {
            throw new Exception('The Channel Manager plugin must be updated to the latest version.', 400);
        }

        if (!VikChannelManager::getApiKey()) {
            throw new Exception('The Channel Manager plugin requires an active E4jConnect subscription.', 400);
        }
    }

    /**
     * AJAX endpoint to generate the content for a room.
     * 
     * @return  void
     */
    public function roomContent()
    {
        $app = JFactory::getApplication();

        if (!JSession::checkToken()) {
            VBOHttpDocument::getInstance($app)->close(403, JText::translate('JINVALID_TOKEN'));
        }

        try {
            // validate AI service capabilities
            $this->auditServiceCapabilities();

            // let the AI model service generate the content
            $content = (new VCMAiModelService)->roomContent(
                $app->input->getAlnum('type', 'short'),
                $app->input->getString('information', 'I have a normal room to rent.'),
                $app->input->getString('language', '')
            );
        } catch (Exception $e) {
            VBOHttpDocument::getInstance($app)->close($e->getCode(), $e->getMessage());
        } catch (Throwable $e) {
            VBOHttpDocument::getInstance($app)->close($e->getCode() ?: 500, $e->getMessage() ?: 'Unrecoverable error');
        }

        // output the result content
        VBOHttpDocument::getInstance($app)->json(['content' => $content]);
    }

    /**
     * AJAX endpoint to generate the content for an email message or communication.
     * 
     * @return  void
     */
    public function mailContent()
    {
        $app = JFactory::getApplication();

        if (!JSession::checkToken()) {
            VBOHttpDocument::getInstance($app)->close(403, JText::translate('JINVALID_TOKEN'));
        }

        try {
            // validate AI service capabilities
            $this->auditServiceCapabilities();

            // let the AI model service generate the content
            $content = (new VCMAiModelService)->mailContent(
                $app->input->getString('information', ''),
                $app->input->getString('language', '')
            );
        } catch (Exception $e) {
            VBOHttpDocument::getInstance($app)->close($e->getCode(), $e->getMessage());
        } catch (Throwable $e) {
            VBOHttpDocument::getInstance($app)->close($e->getCode() ?: 500, $e->getMessage() ?: 'Unrecoverable error');
        }

        // output the result content
        VBOHttpDocument::getInstance($app)->json(['content' => $content]);
    }

    /**
     * AJAX endpoint to extract a list of appropriate tags from a task manager task.
     * 
     * @return  void
     * 
     * @since   1.18.0 (J) - 1.8.0 (WP)
     */
    public function extractTaskTags()
    {
        $app = JFactory::getApplication();

        if (!JSession::checkToken()) {
            VBOHttpDocument::getInstance($app)->close(403, JText::translate('JINVALID_TOKEN'));
        }

        try {
            // validate AI service capabilities
            $this->auditServiceCapabilities();

            // let the AI model service extract the tags
            $tags = (new VCMAiModelService)->extractTaskTags([
                'area_id' => $app->input->getUInt('area_id', 0),
                'task_id' => $app->input->getUInt('task_id', 0),
                'task_title' => $app->input->getString('task_title', ''),
                'task_notes' => $app->input->getString('task_notes', '', 'raw'),
                'booking_id' => $app->input->getUInt('booking_id', 0),
                'listing_id' => $app->input->getUInt('listing_id', 0),
            ]);
        } catch (Exception $e) {
            VBOHttpDocument::getInstance($app)->close($e->getCode(), $e->getMessage());
        } catch (Throwable $e) {
            VBOHttpDocument::getInstance($app)->close($e->getCode() ?: 500, $e->getMessage() ?: 'Unrecoverable error');
        }

        // output the result tags
        VBOHttpDocument::getInstance($app)->json(['tags' => $tags]);
    }
}
