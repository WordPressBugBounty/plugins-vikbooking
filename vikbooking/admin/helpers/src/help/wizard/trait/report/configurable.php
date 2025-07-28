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
 * Wizard help instruction configurable report trait.
 * 
 * @since 1.18.2 (J) - 1.8.2 (WP)
 */
trait VBOHelpWizardTraitReportConfigurable
{
    /**
     * The real class name of the report implementor (see reports folder).
     * 
     * @var string
     */
    protected $reportId;

    /**
     * Checks whether the report supports an auto-export feature.
     * 
     * @var bool
     */
    protected $isAutoExportSupported = true;

    /**
     * The format that will be used to auto-export the report.
     * 
     * @var string
     */
    protected $autoExportFormat;

    /**
     * The payload that will be used to auto-export the report.
     * 
     * @var array
     */
    protected $autoExportPayload;

    /**
     * Flag used to check whether the report already owns a basic setup.
     * 
     * @var bool
     */
    private $hasSettingsConfigured = null;

    /**
     * Flag used to check whether the report already owns a configured cron job to auto-transmit the contents.
     * 
     * @var bool
     */
    private $hasAutoExport = null;

    /**
     * @inheritDoc
     * 
     * @see VBOHelpWizardInstruction::isProcessable()
     */
    public function isProcessable(?string &$btnText = null)
    {
        if (!$this->hasSettingsConfigured()) {
            $btnText = JText::translate('VBCONFIGURETASK');
        }

        if ($this->isAutoExportSupported && !$this->hasAutoExport()) {
            $btnText = JText::translate('VBO_SCHEDULE_CRONJOB');
        }

        return true;
    }

    /**
     * @inheritDoc
     * 
     * @see VBOHelpWizardInstruction::isConfigured()
     */
    public function isConfigured()
    {
        // check default configuration first
        if (!$this->hasSettingsConfigured()) {
            return false;
        }

        // in case the report supports an auto-export feature, check whether a cron job has been created
        if ($this->isAutoExportSupported && !$this->hasAutoExport()) {
            return false;
        }

        return true;
    }

    /**
     * @inheritDoc
     * 
     * @see VBOHelpWizardInstruction::process()
     */
    public function process(array $args = [])
    {
        if (!$this->hasSettingsConfigured()) {
            return [
                // open the page to configure the report settings
                'redirect' => VBOFactory::getPlatform()->getUri()->admin('index.php?option=com_vikbooking&task=pmsreports&report=' . $this->reportId . '#settings', false),
            ];
        }

        if ($this->isAutoExportSupported && !$this->hasAutoExport()) {

            $this->preflight();

            // create a new auto-export cron job for this report
            $cronId = $this->saveAutoExport($this->autoExportFormat, $this->autoExportPayload);

            $this->postflight();

            return [
                // redirect to the page of the newly created cron job
                'redirect' => VBOFactory::getPlatform()->getUri()->admin('index.php?option=com_vikbooking&task=cronjob.edit&cid[]=' . $cronId, false),
            ];
        }
    }

    /**
     * @inheritDoc
     * 
     * @see VBOHelpWizardInstructionaware::getLayoutData()
     */
    protected function getLayoutData()
    {
        return [
            'configured' => $this->hasSettingsConfigured(),
            'autoexport' => $this->isAutoExportSupported,
        ];
    }

    /**
     * Checks whether the report already owns a basic setup.
     * 
     * @return  bool
     */
    protected function hasSettingsConfigured()
    {
        if (is_null($this->hasSettingsConfigured)) {
            $this->hasSettingsConfigured = $this->checkSettingsConfigured();
        }

        return $this->hasSettingsConfigured;
    }

    /**
     * Checks whether the report already owns a configured cron job to auto-transmit the contents.
     * 
     * @return  bool
     */
    protected function hasAutoExport()
    {
        if (is_null($this->hasAutoExport)) {
            $this->hasAutoExport = (bool) VBOHelpWizardHelperCron::getAutoExport($this->reportId);
        }

        return $this->hasAutoExport;
    }

    /**
     * Saves the auto-export report cron job.
     * 
     * @param   string  $format
     * @param   array   $payload
     * 
     * @return  int  The cron ID.
     * 
     * @throws  RuntimeException
     */
    protected function saveAutoExport(string $format, array $payload)
    {
        if (empty($this->autoExportFormat)) {
            throw new UnexpectedValueException('Missing auto-export format', 400);
        }

        // extract name from report ID
        $name = ucwords(str_replace('_', ' ', $this->reportId)) . ' - ';

        if (preg_match("/^[a-z]{2,2}\s/i", $name)) {
            // get rid of the local country
            $name = substr($name, 3);
        }

        // append the format type to the name
        $name .= ucfirst(preg_replace("/([a-z])([A-Z])/", '$1 $2', $format));

        // create the cron job
        $cron = new stdClass;
        $cron->cron_name = $name;
        $cron->class_file = 'report_auto_exporter';
        $cron->published = 1;
        $cron->params = json_encode([
            'report' => $this->reportId,
            'format' => $format,
            'payload' => json_encode($payload, JSON_PRETTY_PRINT),
            'test_mode' => 0,
            'recipient_email' => JFactory::getUser()->email,
            'save_path' => JFactory::getApplication()->get('tmp_path'),
            'rm_file' => 1,
        ]);

        $cronId = (new VBOModelCronjob)->save($cron);

        // insert the cron job
        if (!$cronId) {
            throw new RuntimeException('Unable to save the pre-checkin cron!', 500);
        }

        return $cronId;
    }

    /**
     * Runs some extra code before saving the auto-export cron job.
     * Classes that use this trait can implement this method to execute
     * custom preflight code.
     * 
     * @return  void
     */
    protected function preflight()
    {
        // do nothing by default
    }

    /**
     * Runs some extra code after saving the auto-export cron job.
     * Classes that use this trait can implement this method to execute
     * custom postflight code.
     * 
     * @return  void
     */
    protected function postflight()
    {
        // do nothing by default
    }

    /**
     * Concrete implementation to check whether the report has configuration settings
     * properly set up.
     * 
     * @return  bool
     */
    abstract protected function checkSettingsConfigured();
}
