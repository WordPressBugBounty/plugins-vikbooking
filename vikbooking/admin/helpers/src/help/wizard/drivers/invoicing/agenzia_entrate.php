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
 * Wizard Agenzia delle Entrate (Italian e-invoicing) help instruction.
 * 
 * @since 1.18.2 (J) - 1.8.2 (WP)
 */
class VBOHelpWizardDriverInvoicingAgenziaEntrate extends VBOHelpWizardInstructionaware
{  
    use VBOHelpWizardTraitInvoicingConfigurable;

    /**
     * Class contructor.
     */
    public function __construct()
    {
        /**
         * @see VBOHelpWizardTraitInvoicingConfigurable::$driverId
         */
        $this->driverId = 'agenzia_entrate';
    }

    /**
     * @inheritDoc
     */
    public function getID()
    {
        return 'invoicing.agenzia_entrate';
    }

    /**
     * @inheritDoc
     */
    public function getTitle()
    {
        return 'Fatturazione Elettronica - Agenzia delle Entrate';
    }

    /**
     * @inheritDoc
     */
    public function isSupported()
    {
        // check whether the main country is Italy
        return VBOFactory::getConfig()->get('maincountry') === 'IT';
    }
}
