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
 * VikBooking maintenance controller.
 *
 * @since   1.17.3 (J) - 1.7.3 (WP)
 */
class VikBookingControllerMaintenance extends JControllerAdmin
{
    /**
     * Optimizes the season records for the given listing, rate plan and dates.
     */
    public function seasons_optimize_db_records()
    {
        $app = JFactory::getApplication();

        if (!JSession::checkToken()) {
            VBOHttpDocument::getInstance($app)->close(403, JText::translate('JINVALID_TOKEN'));
        }

        $is_ajax    = $app->input->getBool('ajax', false);
        $listing_id = $app->input->getUInt('listing_id', 0);
        $id_price   = $app->input->getUInt('id_price', 0);
        $from_date  = $app->input->getString('from_date', '');
        $to_date    = $app->input->getString('to_date', '');

        if (empty($listing_id) || empty($id_price)) {
            VBOHttpDocument::getInstance($app)->close(400, 'Missing command values');
        }

        if (!empty($from_date)) {
            // ensure date format is Y-m-d
            $from_date = date('Y-m-d', VikBooking::getDateTimestamp($from_date));
        }

        if (!empty($to_date)) {
            // ensure date format is Y-m-d
            $to_date = date('Y-m-d', VikBooking::getDateTimestamp($to_date));
        }

        try {
            VBOPerformanceCleaner::setOptions([
                'listing_id' => $listing_id,
                'id_price'   => $id_price,
                'from_date'  => $from_date,
                'to_date'    => $to_date,
            ]);

            $result = VBOPerformanceCleaner::listingSeasonSnapshot();
        } catch (Exception $e) {
            VBOHttpDocument::getInstance($app)->close($e->getCode(), $e->getMessage());
        }

        if ($is_ajax) {
            VBOHttpDocument::getInstance($app)->json($result);
        }

        $app->enqueueMessage(sprintf('Cleaning completed with %d records removed', (int) ($result['records_removed']) ?? 0));
        $app->redirect('index.php?option=com_vikbooking&task=seasons');
        $app->close();
    }
}
