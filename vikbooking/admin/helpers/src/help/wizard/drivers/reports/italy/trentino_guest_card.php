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
 * Wizard report Trentino Guest Card help instruction.
 * 
 * @since 1.18.2 (J) - 1.8.2 (WP)
 */
class VBOHelpWizardDriverReportsItalyTrentinoGuestCard extends VBOHelpWizardInstructionaware
{
    use VBOHelpWizardTraitReportConfigurable;

    /**
     * Class contructor.
     */
    public function __construct()
    {
        /**
         * @see VBOHelpWizardTraitReportConfigurable::$reportId
         */
        $this->reportId = 'it_trentino_guest_card';

        /**
         * @see VBOHelpWizardTraitReportConfigurable::$autoExportFormat
         */
        $this->autoExportFormat = 'emissioneGuestCards';

        /**
         * @see VBOHelpWizardTraitReportConfigurable::$autoExportPayload
         */
        $this->autoExportPayload = [
            'fromdate' => '{Y-m-d +1 day}',
            'todate' => '{Y-m-d +1 day}',
            'listings' => [],
            '_reportAction' => 'emissioneGuestCards',
        ];
    }
    
    /**
     * @inheritDoc
     */
    public function getID()
    {
        return 'reports.italy.trentino_guest_card';
    }

    /**
     * @inheritDoc
     */
    public function getTitle()
    {
        return 'Trentino Guest Card';
    }

    /**
     * @inheritDoc
     */
    public function getIcon()
    {
        return VikBookingIcons::i('id-badge');
    }

    /**
     * @inheritDoc
     */
    public function getPriority()
    {
        return 997;
    }

    /**
     * @inheritDoc
     */
    public function isSupported()
    {        
        $config = VBOFactory::getConfig();

        // check whether the main country is Italy and the main province is Trento
        return $config->get('maincountry') === 'IT' && $config->get('mainstate') === 'TN';
    }

    /**
     * @inheritDoc
     * 
     * @see VBOHelpWizardTraitReportConfigurable::checkSettingsConfigured()
     */
    protected function checkSettingsConfigured()
    {
        $settings = VBOFactory::getConfig()->getArray('report_settings_it_trentino_guest_card', []);

        return !empty($settings['username_auth_live']) && !empty($settings['password_auth_live'])&& !empty($settings['username_account_live']) && !empty($settings['password_account_live']);
    }
}
