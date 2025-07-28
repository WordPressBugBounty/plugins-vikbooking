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
 * Wizard report ISPAT VIT Albergatori help instruction.
 * 
 * @since 1.18.2 (J) - 1.8.2 (WP)
 */
class VBOHelpWizardDriverReportsItalyVitAlbergatori extends VBOHelpWizardInstructionaware
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
        $this->reportId = 'istat_vit_albergatori';

        /**
         * @see VBOHelpWizardTraitReportConfigurable::$autoExportFormat
         */
        $this->autoExportFormat = 'uploadPresences';

        /**
         * @see VBOHelpWizardTraitReportConfigurable::$autoExportPayload
         */
        $this->autoExportPayload = [
            'fromdate' => '{Y-m-d -1 day}',
            'todate' => '{Y-m-d -1 day}',
            '_reportAction' => 'uploadPresences',
        ];
    }
    
    /**
     * @inheritDoc
     */
    public function getID()
    {
        return 'reports.italy.vit_albergatori';
    }

    /**
     * @inheritDoc
     */
    public function getTitle()
    {
        return 'ISTAT - VIT Albergatori';
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

        // check whether the main country is Italy and the main province is Aosta
        return $config->get('maincountry') === 'IT' && $config->get('mainstate') === 'AO';
    }

    /**
     * @inheritDoc
     * 
     * @see VBOHelpWizardTraitReportConfigurable::checkSettingsConfigured()
     */
    protected function checkSettingsConfigured()
    {
        $settings = VBOFactory::getConfig()->getArray('report_settings_istat_vit_albergatori', []);

        return !empty($settings['propertyid']) && !empty($settings['endpoint']) && !empty($settings['user']) && !empty($settings['pwd']);
    }

    /**
     * @inheritDoc
     * 
     * @see VBOHelpWizardTraitReportConfigurable::postflight()
     */
    protected function postflight()
    {
        $payload = $this->autoExportPayload;
        $payload['_reportAction'] = 'uploadOccupancy';

        $this->saveAutoExport($payload['_reportAction'], $payload);
    }
}
