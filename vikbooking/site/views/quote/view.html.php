<?php
/**
 * @package     VikBooking
 * @subpackage  com_vikbooking
 * @author      E4J srl
 * @copyright   Copyright (C) 2026 E4J srl. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 * @link        https://vikwp.com
 */

defined('ABSPATH') or die('No script kiddies please!');

jimport('joomla.application.component.view');

class VikbookingViewQuote extends JViewVikBooking
{
	public function display($tpl = null)
	{
		$app = JFactory::getApplication();

		// set noindex instruction for robots
		JFactory::getDocument()->setMetaData('robots', 'noindex, nofollow');

		// get quote reference value
		$ref = $app->input->getString('ref', '');

		// access quote model
		$quoteModel = VBOMvcModel::getInstance('quote');

		// load requested quote and related booking solutions
		$quoteData = null;
		if ($ref) {
			// load quote data
			$quoteData = $quoteModel->loadBookingRecords(0, 0, [
				'filters' => [
					'uuid' => $ref,
				],
			])[0] ?? null;
		}

		// detect quote "viewed" status
		if (!empty($quoteData->ip) && $app->input->server->get('REMOTE_ADDR') != $quoteData->ip) {
			// silently update quote record for internal purposes
			$quoteModel->save([
				'id' => $quoteData->id,
				'viewed' => 1,
			]);
		}

		// load the details for the involved rooms
		$roomsData = [];
		if ($quoteData) {
			// collect a unique list of room IDs involved
			$involvedRoomIds = [];
			foreach ($quoteData->solutions as $solution) {
				$involvedRoomIds = array_merge($involvedRoomIds, array_column($solution->rooms, 'id'));
			}
			$involvedRoomIds = array_values(array_unique($involvedRoomIds));
			// map the information
			$roomsData = array_map(function($roomId) {
				return VikBooking::getRoomInfo($roomId, [], true);
			}, $involvedRoomIds);
			// turn the list into associative
			$roomsData = array_combine(array_column($roomsData, 'id'), array_values($roomsData));
		}

		// set template properties
		$this->quoteData = $quoteData;
		$this->roomsData = $roomsData;

		// display template
		parent::display($tpl);
	}
}
