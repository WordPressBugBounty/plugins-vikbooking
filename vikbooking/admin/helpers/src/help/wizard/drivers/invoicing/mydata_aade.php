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
 * Wizard myDATA - ΑΑΔΕ (Greek e-invoicing) help instruction.
 * 
 * @since 1.18.2 (J) - 1.8.2 (WP)
 */
class VBOHelpWizardDriverInvoicingMydataAade extends VBOHelpWizardInstructionaware
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
        $this->driverId = 'mydata_aade';
    }

    /**
     * @inheritDoc
     */
    public function getID()
    {
        return 'invoicing.mydata_aade';
    }

    /**
     * @inheritDoc
     */
    public function getTitle()
    {
        return 'myDATA - ΑΑΔΕ';
    }

    /**
     * @inheritDoc
     */
    public function isSupported()
    {
        // check whether the main country is Greece
        return VBOFactory::getConfig()->get('maincountry') === 'GR';
    }
}
