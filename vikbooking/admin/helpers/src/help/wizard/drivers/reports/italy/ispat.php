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
 * Wizard report ISPAT help instruction.
 * 
 * @since 1.18.2 (J) - 1.8.2 (WP)
 */
class VBOHelpWizardDriverReportsItalyIspat extends VBOHelpWizardInstructionaware
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
        $this->reportId = 'istat_ispat';

        /**
         * @see VBOHelpWizardTraitReportConfigurable::$autoExportFormat
         */
        $this->autoExportFormat = 'uploadC59Full';

        /**
         * @see VBOHelpWizardTraitReportConfigurable::$autoExportPayload
         */
        $this->autoExportPayload = [
            'fromdate' => '{Y-m-d -1 day}',
            'listings' => [],
            '_reportAction' => 'uploadC59Full',
        ];
    }
    
    /**
     * @inheritDoc
     */
    public function getID()
    {
        return 'reports.italy.ispat';
    }

    /**
     * @inheritDoc
     */
    public function getTitle()
    {
        return 'Istituto di statistica della Provincia di Trento (ISPAT)';
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
        $settings = VBOFactory::getConfig()->getArray('report_settings_istat_ispat', []);

        return !empty($settings['propertyid']) && !empty($settings['user']) && !empty($settings['pwd']);
    }
}
