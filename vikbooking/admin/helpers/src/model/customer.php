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
 * VikBooking customer model.
 *
 * @since   1.18.8 (J) - 1.8.8 (WP)
 */
class VBOModelCustomer extends VBOMvcModel
{
    /**
     * The database table name.
     * 
     * @var string
     */
    protected $tableName = '#__vikbooking_customers';

    /**
     * @inheritDoc
     */
    protected function preflight(array &$data)
    {
        if (empty($data['id'])) {
            // we are saving a new record

            if (empty($data['first_name']) || empty($data['last_name'])) {
                // missing mandatory information
                $this->setError('Missing first name or last name.');
                return false;
            }

            // check if a customer with the same information exists
            if (!empty($data['email'])) {
                $customer = $this->getItem([
                    'first_name' => $data['first_name'],
                    'last_name'  => $data['last_name'],
                    'email'      => $data['email'],
                ]);

                if ($customer) {
                    // convert the saving operation into an update
                    $data['id'] = $customer->id;
                }
            }

            /**
             * Make sure the customer PIN code does not default to an empty value of 0.
             * 
             * @since   1.18.11 (J) - 1.8.11 (WP)
             */
            if (empty($data['pin'])) {
                do {
                    // always generate a unique PIN code for new records
                    $data['pin'] = VikBooking::getCPinInstance()->generateSerialCode(8);
                    // repeat in case a record with the same PIN already exists
                } while ($this->getItem(['pin' => $data['pin']]));
            }
        }

        // always take care of the customer country
        if (!empty($data['country']) && strlen($data['country']) !== 3) {
            // attempt to convert a country iso2 code or name into the corresponding iso3 char code
            $data['country'] = VikBooking::getCPinInstance()->get3CharCountry($data['country']);
        }

        return parent::preflight($data);
    }
}
