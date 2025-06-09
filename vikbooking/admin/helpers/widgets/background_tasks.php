<?php
/**
 * @package     VikBooking
 * @subpackage  com_vikbooking
 * @author      Alessio Gaggii - E4J srl
 * @copyright   Copyright (C) 2024 E4J srl. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 * @link        https://vikwp.com
 */

defined('ABSPATH') or die('No script kiddies please!');

/**
 * Class handler for admin widget "Background tasks".
 * 
 * @since 1.17 (J) - 1.7 (WP)
 */
class VikBookingAdminWidgetBackgroundTasks extends VikBookingAdminWidget
{
	/** @var VBOCrontabSimulator */
	private $crontab;

	/**
	 * @inheritDOc
	 */
	public function __construct()
	{
		// call parent constructor
		parent::__construct();

		$this->widgetName = 'Background Tasks';
		$this->widgetDescr = 'Displays all the scheduled background tasks.';
		$this->widgetId = basename(__FILE__, '.php');

		$this->widgetIcon = '<i class="' . VikBookingIcons::i('server') . '"></i>';
		$this->widgetStyleName = 'red';

		try {
			$this->crontab = VBOFactory::getCrontabSimulator();
		} catch (Throwable $e) {
			$this->crontab = [];
		}
	}

	/**
	 * @inheritDoc
	 */
	public function getPriority()
	{
		// always display as last widget
		return 1;
	}

	/**
	 * @inheritDoc
	 */
	public function preflight()
	{
		// can be used only if we have at least a scheduled task
		return count($this->crontab);
	}

	/**
	 * @inheritDoc
	 */
	public function render(?VBOMultitaskData $data = null)
	{
		$schedules = [];

		foreach ($this->crontab as $runner) {
			$lastExecution = $this->crontab->getSemaphore()->getLastExecution($runner);
			$nextExecution = $runner->getNextExecution($lastExecution);

			$schedules[] = [
				'name' => ucwords(str_replace('_', ' ', $runner->getID())),
				'last_execution' => JHtml::fetch('date', $lastExecution, 'Y-m-d H:i:s'),
				'next_execution' => JHtml::fetch('date', $nextExecution, 'Y-m-d H:i:s'),
			];
		}

		$log = '';

		try {
			// assume we are using a file logger
			$path = $this->crontab->getLogger()->getPath();

			if (!JFile::exists($path)) {
				throw new Exception('Log file not found', 404);
			}

			$fp = fopen($path, 'r');

			while (!feof($fp)) {
				$log .= fread($fp, 8192);
			}

			fclose($fp);
		} catch (Throwable $error) {
			// ignore log buffer
		}

		// display by using a specific layout provided by VCM
		echo JLayoutHelper::render(
			'sidepanel.widgets.crontab',
			[
				'schedules' => $schedules,
				'log' => htmlentities($log),
			]
		);
	}
}
