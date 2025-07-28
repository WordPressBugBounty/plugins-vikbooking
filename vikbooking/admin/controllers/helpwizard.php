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
 * Help wizard controller.
 * 
 * @since 1.18.2 (J) - 1.8.2 (WP)
 */
class VikBookingControllerHelpwizard extends JControllerAdmin
{
    /**
     * Show the details of the first eligible instruction.
     * 
     * @return  void
     */
    public function show()
    {
        $app = JFactory::getApplication();

        if (!JSession::checkToken()) {
            // missing CSRF-proof token
            VBOHttpDocument::getInstance($app)->close(403, JText::translate('JINVALID_TOKEN'));
        }

        /** @var VBOHelpWizard */
        $helpWizard = VBOFactory::getHelpWizard();

        /** @var VBOHelpWizardInstruction|null */
        $instruction = $helpWizard->getNextInstruction();

        if ($instruction)
        {
            $resp = [
                'has' => true,
                'id' => $instruction->getID(),
                'title' => $instruction->getTitle(),
                'icon' => $instruction->getIcon(),
                'html' => $instruction->show(),
                'dismissible' => $instruction->isDismissible(),
                'processable' => $instruction->isProcessable($btnText),
                'processtext' => $btnText,
            ];
        }
        else
        {
            $resp = [
                'has' => false,
            ];
        }

        // send the response to output
        VBOHttpDocument::getInstance($app)->json($resp);
    }

    /**
     * Dismisses the specified instruction.
     * 
     * @return  void
     */
    public function dismiss()
    {
        $app = JFactory::getApplication();

        if (!JSession::checkToken()) {
            // missing CSRF-proof token
            VBOHttpDocument::getInstance($app)->close(403, JText::translate('JINVALID_TOKEN'));
        }

        $id = $app->input->get('instruction', '');
        $datetime = $app->input->getString('datetime');

        /** @var VBOHelpWizard */
        $helpWizard = VBOFactory::getHelpWizard();

        /** @var VBOHelpWizardInstruction|null */
        $instruction = $helpWizard->getInstruction($id);

        if (!$instruction) {
            VBOHttpDocument::getInstance($app)->close(404, 'Help wizard instruction not found.');
        }

        $helpWizard->dismiss($instruction, $datetime);

        $app->close();
    }

    /**
     * Processes the specified instruction.
     * 
     * @return  void
     */
    public function process()
    {
        $app = JFactory::getApplication();

        if (!JSession::checkToken()) {
            // missing CSRF-proof token
            VBOHttpDocument::getInstance($app)->close(403, JText::translate('JINVALID_TOKEN'));
        }

        $id = $app->input->get('instruction', '');
        $args = $app->input->get('args', [], 'array');

        /** @var VBOHelpWizard */
        $helpWizard = VBOFactory::getHelpWizard();

        /** @var VBOHelpWizardInstruction|null */
        $instruction = $helpWizard->getInstruction($id);

        if (!$instruction) {
            VBOHttpDocument::getInstance($app)->close(404, 'Help wizard instruction not found.');
        }

        try {
            if (!$instruction->isProcessable()) {
                throw new RuntimeException('The instruction [' . $id . '] cannot be processed!', 403);
            }

            $return = $instruction->process($args);

            if ($return) {
                VBOHttpDocument::getInstance($app)->json($return);    
            }
        } catch (Exception $error) {
            VBOHttpDocument::getInstance($app)->close($error->getCode() ?: 500, $error->getMessage() ?: 'An error has occurred.');
        }

        $app->close();
    }
}
