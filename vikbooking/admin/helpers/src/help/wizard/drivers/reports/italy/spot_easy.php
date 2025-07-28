<?php
/** 
 * @package     VikBooking
 * @subpackage  core
 * @author      E4J s.r.l.
 * @copyright   Copyright (C) 2021 E4J s.r.l. All Rights Reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * @link        https://vikwp.com
 */

// No direct access
defined('ABSPATH') or die('No script kiddies please!');

/**
 * Wizard report ISTAT Spot Easy (Puglia) help instruction.
 * 
 * @since 1.18.2 (J) - 1.8.2 (WP)
 */
class VBOHelpWizardDriverReportsItalySpotEasy extends VBOHelpWizardInstructionaware
{
    /**
     * @inheritDoc
     */
    public function getID()
    {
        return 'reports.italy.spot_easy';
    }

    /**
     * @inheritDoc
     */
    public function getTitle()
    {
        return 'ISTAT - SPOT Easy';
    }

    /**
     * @inheritDoc
     */
    public function getIcon()
    {
        return VikBookingIcons::i('id-card');
    }

    /**
     * @inheritDoc
     */
    public function getPriority()
    {
        return 998;
    }

    /**
     * @inheritDoc
     */
    public function isSupported()
    {
        $config = VBOFactory::getConfig();

        $cities = [
            'BA', // Bari
            'BT', // Barletta-Andria-Trani
            'BR', // Brindisi
            'FG', // Foggia
            'LE', // Lecce
            'TA', // Taranto
        ];

        // check whether the main country is Italy and the main province is included within the list of supported cities
        if ($config->get('maincountry') !== 'IT' || !in_array($config->get('mainstate'), $cities)) {
            return false;
        }

        $db = JFactory::getDbo();

        // count the total number of confirmed (non-closure) bookings
        $query = $db->getQuery(true)
            ->select('COUNT(1)')
            ->from($db->qn('#__vikbooking_orders'))
            ->where($db->qn('status') . ' = ' . $db->q('confirmed'))
            ->where($db->qn('closure') . ' = 0');
        
        $db->setQuery($query);
        $confirmedBookings = (int) $db->loadResult();

        // make sure the website received at least 3 confirmed bookings
        return $confirmedBookings >= 3;
    }

    /**
     * @inheritDoc
     */
    public function isConfigured()
    {
        return VBOFactory::getConfig()->getBool('helpwizard.' . $this->getID());
    }

    /**
     * @inheritDoc
     */
    public function isProcessable(?string &$btnText = null)
    {
        $btnText = JText::translate('VBO_TRY_IT');

        return true;
    }

    /**
     * @inheritDoc
     */
    public function process(array $args = [])
    {
        VBOFactory::getConfig()->set('helpwizard.' . $this->getID(), 1);

        return [
            'redirect' => VBOFactory::getPlatform()->getUri()->admin('index.php?option=com_vikbooking&task=pmsreports&report=istat_spot', false),
        ];
    }
}
