<?php
/** 
 * @package     VikBooking
 * @subpackage  core
 * @author      E4J s.r.l.
 * @copyright   Copyright (C) 2026 E4J s.r.l. All Rights Reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * @link        https://vikwp.com
 */

// No direct access
defined('ABSPATH') or die('No script kiddies please!');

/**
 * VikBooking coupon model.
 *
 * @since   1.18.8 (J) - 1.8.8 (WP)
 */
class VBOModelCoupon extends VBOMvcModel
{
    /**
     * The database table name.
     * 
     * @var string
     */
    protected $tableName = '#__vikbooking_coupons';

    /**
     * Runs before saving the given data.
     * 
     * @param   array    &$data  A reference to the data to save.
     * 
     * @return  bool     True on success, false otherwise.
     */
    protected function preflight(array &$data)
    {
        if (empty($data['id']) && empty($data['code'])) {
            // generate a random code when saving a new record
            $data['code'] = VikBooking::getCPinInstance()->generateSerialCode(8);
        }

        return parent::preflight($data);
    }
}
