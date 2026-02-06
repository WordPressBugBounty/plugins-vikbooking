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
 * Pax fields MRZ mapper for "italy" data collector.
 * 
 * @since   1.18.6 (J) - 1.8.6 (WP)
 */
final class VBOCheckinPaxfieldsMrzMapItaly extends VBOCheckinPaxfieldsMrzMap
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
                return 'PASOR';

            case 'I':
                return 'IDENT';

            case 'A':
                return 'TESEA';

            case 'C':
                if ($this->getRawMrzProperty('issuer') === 'ITA') {
                    // this is an Italian electronic identity document
                    return 'IDELE';
                }
                // fallback to closest document type for Italy
                return 'CERID';

            case 'V':
                return 'PASOR';
            
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

    /**
     * @inheritDoc
     */
    protected function mapNationality(string $value)
    {
        // attempt to find the nationality code
        $nationalityCode = $this->matchItalianCountryCode($value);

        if ($nationalityCode) {
            // match found
            return $nationalityCode;
        }

        // try to rely on the document issuer country value
        $issuerValue = $this->convertCountryValue((string) $this->getRawMrzProperty('issuer'));
        if ($issuerValue && $issuerValue != $value) {
            // attempt to find the nationality code from document issuer country
            return $this->matchItalianCountryCode($issuerValue);
        }

        // no matches found
        return '';
    }

    /**
     * @inheritDoc
     */
    protected function mapDocumentIssuer(string $value)
    {
        // attempt to find the document issuer country code
        $issuerCode = $this->matchItalianCountryCode($value);

        if ($issuerCode) {
            // match found
            return $issuerCode;
        }

        // try to rely on the nationality code
        $nationalityValue = $this->convertCountryValue((string) $this->getRawMrzProperty('nationality'));
        if ($nationalityValue && $nationalityValue != $value) {
            // attempt to find the document issuer country code from nationality
            return $this->matchItalianCountryCode($nationalityValue);
        }

        // no matches found
        return '';
    }

    /**
     * Calls the pax-fields data collector for Italy to obtain the
     * list of country codes to match the current country code.
     * 
     * @param   string  $countryCode    The country code to match.
     * 
     * @return  string                  The Italy country identifier, if any.
     */
    private function matchItalianCountryCode(string $countryCode)
    {
        // upper-case expected country code
        $countryCode = strtoupper($countryCode);

        // get the countries list from Italy pax fields data collector
        $italyCountriesList = $this->callCollector('loadNazioni');

        // attempt to find the requested country code
        foreach ($italyCountriesList as $countryId => $countryData) {
            if (strtoupper($countryData['three_code'] ?? '') === $countryCode) {
                // return the match found
                return $countryId;
            }
        }

        return '';
    }
}
