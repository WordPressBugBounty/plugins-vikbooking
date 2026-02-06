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
 * Pax fields MRZ mapper for "spain" data collector.
 * 
 * @since   1.18.6 (J) - 1.8.6 (WP)
 */
final class VBOCheckinPaxfieldsMrzMapSpain extends VBOCheckinPaxfieldsMrzMap
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
                return 'P';

            case 'I':
                return 'D';

            case 'A':
                return 'I';

            case 'C':
                return 'I';

            case 'V':
                return 'P';
            
            default:
                // do nothing
                break;
        }

        // default to identity document
        return 'I';
    }

    /**
     * @inheritDoc
     */
    protected function mapGender(string $value)
    {
        return $value === '2' || strtoupper($value) === 'F' ? '2' : '1';
    }
}
