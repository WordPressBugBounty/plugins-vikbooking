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
 * Pax fields MRZ mapper for "Spain (SES Hospedajes)" data collector.
 * 
 * @since   1.18.6 (J) - 1.8.6 (WP)
 */
final class VBOCheckinPaxfieldsMrzMapSpainseshospedajes extends VBOCheckinPaxfieldsMrzMap
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
                'country_s',
                'country_c',
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
        ];

        // return the filtered list of supported pax fields from MRZ data detection
        return array_filter($knownFields, function($prop) use ($propertyList) {
            return in_array($prop, $propertyList);
        }, ARRAY_FILTER_USE_KEY);
    }

    /**
     * @inheritDoc
     */
    protected function mapDocumentCode(string $value)
    {
        $value = strtoupper($value);

        switch ($value) {
            case 'P':
                return 'PAS';

            case 'I':
                return 'NIF';

            case 'A':
                return 'OTRO';

            case 'C':
                return 'NIF';

            case 'V':
                return 'PAS';
            
            default:
                // do nothing
                break;
        }

        // default to "other"
        return 'OTRO';
    }

    /**
     * @inheritDoc
     */
    protected function mapGender(string $value)
    {
        if ($value === '2' || strtoupper($value) === 'M') {
            // female
            return 'M';
        }

        if ($value === '1' || strtoupper($value) === 'H') {
            // male (hombre)
            return 'H';
        }

        // other
        return 'O';
    }
}
