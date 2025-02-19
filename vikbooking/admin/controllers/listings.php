<?php
/** 
 * @package     VikBooking
 * @subpackage  core
 * @author      E4J s.r.l.
 * @copyright   Copyright (C) 2025 E4J s.r.l. All Rights Reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * @link        https://vikwp.com
 */

// No direct access
defined('ABSPATH') or die('No script kiddies please!');

/**
 * VikBooking listings controller.
 *
 * @since 	1.17.6 (J) - 1.7.6 (WP)
 */
class VikBookingControllerListings extends JControllerAdmin
{
	/**
	 * AJAX endpoint to check the listings with a missing mini-thumbnail.
	 * 
	 * @return 	void
	 */
	public function check_mini_thumbnails()
	{
		if (!JSession::checkToken()) {
			VBOHttpDocument::getInstance()->close(403, JText::translate('JINVALID_TOKEN'));
		}

		$result = [
			'processed' => 0,
			'generated' => 0,
		];

		$dbo = JFactory::getDbo();

		$dbo->setQuery(
			$dbo->getQuery(true)
				->select([
					$dbo->qn('id'),
					$dbo->qn('img'),
				])
				->from($dbo->qn('#__vikbooking_rooms'))
				->where($dbo->qn('avail') . ' = 1')
		);

		$listings = $dbo->loadAssocList();

		$updpath = VBO_SITE_PATH . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR;

		foreach ($listings as $listing) {
			// increase counter
			$result['processed']++;

			if (is_file($updpath . $listing['img']) && !is_file($updpath . 'mini_' . $listing['img'])) {
				// generate thumbnail
				try {
					// resize the original image
					(new VikResizer)->proportionalImage($updpath . $listing['img'], $updpath . 'mini_' . $listing['img'], 96, 96);

					// increase counter
					$result['generated']++;
				} catch (Throwable $e) {
					// silently catch any PHP GD error and continue
				}
			}
		}

		// send result to output
		VBOHttpDocument::getInstance()->json($result);
	}
}
