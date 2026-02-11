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
 * Wizard report schedine alloggiati (Polizia) help instruction.
 * 
 * @since 1.18.2 (J) - 1.8.2 (WP)
 */
class VBOHelpWizardDriverReportsItalySchedineAlloggiati extends VBOHelpWizardInstructionaware
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
        $this->reportId = 'alloggiati_polizia';

        /**
         * @see VBOHelpWizardTraitReportConfigurable::$autoExportFormat
         */
        $this->autoExportFormat = 'transmitCards';

        /**
         * @see VBOHelpWizardTraitReportConfigurable::$autoExportPayload
         */
        $this->autoExportPayload = [
            'fromdate' => '{Y-m-d}',
            'todate' => '{Y-m-d}',
            'listings' => [],
            '_reportAction' => 'transmitCards',
            '_allReportProfiles' => true,
        ];
    }
    
    /**
     * @inheritDoc
     */
    public function getID()
    {
        return 'reports.italy.schedine_alloggiati';
    }

    /**
     * @inheritDoc
     */
    public function getTitle()
    {
        return 'Questura - Schedine Alloggiati';
    }

    /**
     * @inheritDoc
     */
    public function getIcon()
    {
        return VikBookingIcons::i('user-secret');
    }

    /**
     * @inheritDoc
     */
    public function getPriority()
    {
        return 999;
    }

    /**
     * @inheritDoc
     */
    public function isSupported()
    {
        return VBOFactory::getConfig()->get('maincountry') === 'IT';
    }

    /**
     * @inheritDoc
     * 
     * @see VBOHelpWizardTraitReportConfigurable::checkSettingsConfigured()
     */
    protected function checkSettingsConfigured()
    {
        return VBOFactory::getConfig()->getBool('report_settings_alloggiati_polizia');
    }

    /**
     * @inheritDoc
     * 
     * @see VBOHelpWizardTraitReportConfigurable::postflight()
     */
    protected function postflight()
    {
        // schedule first "test transmit cards"
        $payload = $this->autoExportPayload;
        $payload['fromdate'] = '{Y-m-d +1 day}';
        $payload['todate'] = '{Y-m-d +1 day}';
        $payload['_reportAction'] = 'testTransmitCards';

        $this->saveAutoExport($payload['_reportAction'], $payload);

        // schedule "download receipt" of previous day
        $payload = $this->autoExportPayload;
        $payload['fromdate'] = '{Y-m-d -1 day}';
        $payload['todate'] = '{Y-m-d -1 day}';
        $payload['_reportAction'] = 'downloadReceipt';
        $payload['_reportData'] = [
            'receipt_date' => '{Y-m-d -1 day}',
        ];

        $this->saveAutoExport($payload['_reportAction'], $payload);
    }
}
