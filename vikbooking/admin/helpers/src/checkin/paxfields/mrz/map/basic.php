<?php
/** 
 * @package     VikBooking
 * @subpackage  core
 * @author      Alessio Gaggii - E4J s.r.l.
 * @copyright   Copyright (C) 2026 E4J s.r.l. All Rights Reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * @link        https://vikwp.com
 */

// No direct access
defined('ABSPATH') or die('No script kiddies please!');

/**
 * Pax fields MRZ mapper for "basic" data collector.
 * 
 * @since   1.18.6 (J) - 1.8.6 (WP)
 */
final class VBOCheckinPaxfieldsMrzMapBasic extends VBOCheckinPaxfieldsMrzMap
{
    /**
     * @inheritDoc
     */
    public function getKnownFields(array $propertyList)
    {
        // build the associative relation with the known and supported fields
        $knownFields = [
            'firstName' => [
                'first_name',
            ],
            'lastName' => [
                'last_name',
            ],
            'dateOfBirth' => [
                'date_birth',
            ],
            'nationality' => [
                'country',
                'country_c',
                'country_b',
                'country_s',
            ],
            'sex' => [
                'gender',
            ],
            'documentCode' => [
                'doctype',
            ],
            'documentNumber' => [
                'docnum',
            ],
            'issuer' => [
                'docplace',
            ],
        ];

        /**
         * The "basic" MRZ map is also used whenever the data collector does not
         * provide a dedicated MRZ mapper, so we include more fields than what's
         * actually supported by the "basic" data collector. Therefore, we ensure
         * the pax fields returned are supported by the current data collector.
         */
        $collectorFieldKeys = array_keys($this->getLabels());
        foreach ($knownFields as $fieldType => $fieldKeys) {
            foreach ($fieldKeys as $fieldIndex => $fieldKey) {
                if (!in_array($fieldKey, $collectorFieldKeys)) {
                    // data collector does not implement this field key
                    unset($knownFields[$fieldType][$fieldIndex]);
                }
            }
            if (!$knownFields[$fieldType]) {
                // no more fields for this type
                unset($knownFields[$fieldType]);
            } else {
                // re-number the possibly modified list
                $knownFields[$fieldType] = array_values($knownFields[$fieldType]);
            }
        }

        // return the filtered list of supported pax fields from MRZ data detection
        return array_filter($knownFields, function($prop) use ($propertyList) {
            return in_array($prop, $propertyList);
        }, ARRAY_FILTER_USE_KEY);
    }
}
