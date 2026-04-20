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

// import Joomla view library
jimport('joomla.application.component.view');

/**
 * VikBooking manage quotation admin View.
 *
 * @since   1.18.8 (J) - 1.8.8 (WP)
 */
class VikBookingViewManagequote extends JViewVikBooking
{
    public function display($tpl = null)
    {
        // set the toolbar
        $this->addToolBar();

        $app = JFactory::getApplication();

        // access quote model
        $quoteModel = VBOMvcModel::getInstance('quote');

        // get environment data
        $quoteId   = $app->input->getUInt('quote_id', 0);
        $sessionId = $app->input->getUInt('session_id', 0);
        $chatSessionId = null;

        // recover existing values
        $quote    = $quoteId ? ($quoteModel->loadBookingRecords(0, 0, ['filters' => ['id' => $quoteId]])[0] ?? null) : null;
        $customer = (array) $app->input->get('customer', [], 'array');
        $inquiry  = (array) $app->input->get('inquiry', [], 'array');

        if ($sessionId && ($session = (new VBOChatSessionModel)->getItem($sessionId))) {
            // recover customer and inquiry data from chat session
            $customer = array_merge($customer, [
                'name'  => $session->name,
                'email' => $session->email,
                'phone' => $session->phone,
            ]);

            // set chat session ID
            $chatSessionId = $session->id;

            if (empty($customer['first_name']) && !empty($customer['name'])) {
                // normalize customer name
                $nameParts = explode(' ', $customer['name']);
                $customer['first_name'] = (string) array_shift($nameParts);
                $customer['last_name']  = implode(' ', $nameParts);
            }

            if (!empty($customer['phone']) && !preg_match('/^\+/', $customer['phone'])) {
                // pre-pend plus character to phone number
                $customer['phone'] = '+' . $customer['phone'];
            }

            // recover inquiry information
            $inquiry = $session->metadata['query'] ?? [];
        }

        // load preferred quote message templates
        $prefMessages = $quoteModel->getItems(
            // clauses
            [
                'preferred' => 1,
            ],
            // start
            0,
            // lim
            30,
            // cols
            [
                'id',
                'name',
                'subject',
                'message',
                'notes',
                'created_on',
            ],
            // ordering
            [
                'created_on' => 'DESC',
            ]
        );

        // set template properties
        $this->quote    = $quote;
        $this->customer = $customer;
        $this->inquiry  = $inquiry;
        $this->chatSessionId = $chatSessionId;
        $this->prefMessages  = $prefMessages;

        // display the template
        parent::display($tpl);
    }

    /**
     * Sets the toolbar.
     */
    protected function addToolBar()
    {
        JToolBarHelper::title(JText::translate('VBO_QUOTE_MNG_TITLE'), 'vikbooking');
        JToolBarHelper::custom('quote-apply', 'save', 'save', JText::translate('VBSAVE'), false);
        JToolBarHelper::custom('quote-see-all', 'list', 'list', JText::translate('VBO_SEE_ALL'), false);
        JToolBarHelper::cancel('canceldash', JText::translate('VBANNULLA'));
    }
}
