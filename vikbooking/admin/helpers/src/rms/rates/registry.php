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
 * RMS Rates Registry implementation.
 * 
 * @since   1.18.6 (J) - 1.8.6 (WP)
 */
final class VBORmsRatesRegistry
{
    /**
     * @var  array
     */
    private array $options = [];

    /**
     * @var  array
     */
    private array $flowRecords = [];

    /**
     * @var  array
     */
    private array $ratePlansList = [];

    /**
     * @var  int
     */
    private $mainRatePlanId = 0;

    /**
     * Construct the registry by binding the options.
     * 
     * @param   array   $options    Registry options to bind.
     */
    public function __construct(array $options)
    {
        // bind options
        $this->options = $options;

        // identify the main rate plan ID across all listings
        $this->ratePlansList = VikBooking::getAvailabilityInstance(true)->loadRatePlans(true);
        foreach ($this->ratePlansList as $rplan) {
            $this->mainRatePlanId = $rplan['id'] ?? 0;
            break;
        }
    }

    /**
     * Preloads the rates flow records according to the options.
     * 
     * @return  self
     */
    public function preloadFlowRecords(): VBORmsRatesRegistry
    {
        $dbo = JFactory::getDbo();

        $q = $dbo->getQuery(true)
            ->select([
                $dbo->qn('day_from'),
                $dbo->qn('day_to'),
                $dbo->qn('vbo_room_id'),
                $dbo->qn('nightly_fee'),
                $dbo->qn('created_on'),
            ])
            ->from($dbo->qn('#__vikchannelmanager_rates_flow'))
            // filter by channel/platform (website)
            ->where($dbo->qn('channel_id') . ' = -1')
            // filter by rate plan ID to ensure accurate values
            ->where($dbo->qn('vbo_price_id') . ' = ' . $this->mainRatePlanId)
            // filter by pickup date
            ->where($dbo->qn('created_on') . ' <= ' . $dbo->q(date('Y-m-d 23:59:59', strtotime($this->options['pickup']['date'] ?? date('Y-m-d')))))
            // filter by target (stay) dates
            ->where($dbo->qn('day_from') . ' <= ' . $dbo->q(date('Y-m-d', $this->options['target']['to_ts'] ?? 0)))
            ->where($dbo->qn('day_to') . ' >= ' . $dbo->q(date('Y-m-d', $this->options['target']['from_ts'] ?? 0)))
            // sort records by creation date and range start date
            ->order($dbo->qn('created_on') . ' DESC')
            ->order($dbo->qn('day_from') . ' ASC');

        if (!($this->options['no_nightly_fee'] ?? 0)) {
            // make sure the records fetched will have a nightly fee value set (exclude restrictions update-only)
            $q->where($dbo->qn('nightly_fee') . ' IS NOT NULL');
        }

        if ($this->options['listings'] ?? []) {
            // filter by specific listing IDs
            $q->where($dbo->qn('vbo_room_id') . ' IN (' . implode(', ', array_map('intval', $this->options['listings'])) . ')');
        }

        try {
            // attempt to load records from database
            $dbo->setQuery($q);
            $this->flowRecords = $dbo->loadAssocList();
        } catch (Exception $e) {
            // do nothing
        }

        return $this;
    }

    /**
     * Loads the OTA rates flow records and data according to the options.
     * 
     * @return  array
     * 
     * @throws  Exception
     */
    public function loadOtaFlowRecords(): array
    {
        if (empty($this->options['from_date'])) {
            $this->options['from_date'] = date('Y-m-d');
        }

        if (empty($this->options['to_date'])) {
            // calculate the end date by number of days
            $this->options['to_date'] = date('Y-m-d', strtotime(sprintf('+%d days', (int) ($this->options['days'] ?? 2) - 1), strtotime($this->options['from_date'])));
        }

        $dbo = JFactory::getDbo();

        $q = $dbo->getQuery(true)
            ->select([
                $dbo->qn('day_from'),
                $dbo->qn('day_to'),
                $dbo->qn('channel_id'),
                $dbo->qn('vbo_room_id'),
                $dbo->qn('vbo_price_id'),
                $dbo->qn('nightly_fee'),
                $dbo->qn('created_on'),
            ])
            ->from($dbo->qn('#__vikchannelmanager_rates_flow'))
            // filter by channel/platform (no website)
            ->where($dbo->qn('channel_id') . ' > 0')
            // filter by stay dates
            ->where($dbo->qn('day_from') . ' <= ' . $dbo->q($this->options['to_date']))
            ->where($dbo->qn('day_to') . ' >= ' . $dbo->q($this->options['from_date']))
            // sort records by creation date and range start date
            ->order($dbo->qn('created_on') . ' DESC')
            ->order($dbo->qn('day_from') . ' ASC');

        if ($this->options['id_price'] ?? 0) {
            // filter by exact rate plan ID
            $q->where($dbo->qn('vbo_price_id') . ' = ' . (int) $this->options['id_price']);
        } elseif ($this->options['use_main_rate'] ?? 0) {
            // filter by main rate plan ID
            $q->where($dbo->qn('vbo_price_id') . ' = ' . $this->mainRatePlanId);
        }

        if (!($this->options['no_nightly_fee'] ?? 0)) {
            // make sure the records fetched will have a nightly fee value set (exclude restrictions update-only)
            $q->where($dbo->qn('nightly_fee') . ' IS NOT NULL');
        }

        if ($this->options['id_rooms'] ?? []) {
            // filter by specific listing IDs
            $q->where($dbo->qn('vbo_room_id') . ' IN (' . implode(', ', array_map('intval', (array) $this->options['id_rooms'])) . ')');
        }

        try {
            // attempt to load records from database
            $dbo->setQuery($q);
            $flowRecords = $dbo->loadAssocList();
        } catch (Exception $e) {
            // propagate the error
            throw $e;
        }

        if (!$flowRecords) {
            // no records found
            return [];
        }

        // get the list of listings involved
        $involvedListingIds = array_map('intval', array_values(array_unique(array_column($flowRecords, 'vbo_room_id'))));

        // get the list of rate plan IDs involved
        $involvedRateIds = array_map('intval', array_values(array_unique(array_column($flowRecords, 'vbo_price_id'))));

        // get the list of channel IDs involved
        $involvedChannelIds = array_values(array_unique(array_column($flowRecords, 'channel_id')));

        // sort channels by importance and alphabetically
        $otasImportance = [
            VikChannelManagerConfig::AIRBNBAPI,
            VikChannelManagerConfig::BOOKING,
            VikChannelManagerConfig::EXPEDIA,
            VikChannelManagerConfig::VRBOAPI,
        ];
        usort($involvedChannelIds, function($a, $b) use ($otasImportance) {
            $aRank = in_array($a, $otasImportance) ? (int) array_search($a, $otasImportance) : 100;
            $bRank = in_array($b, $otasImportance) ? (int) array_search($b, $otasImportance) : 100;
            return $aRank <=> $bRank;
        });

        // list of channels requiring net rates (pricing before tax)
        $netRateOtas = [
            VikChannelManagerConfig::AIRBNBAPI,
            VikChannelManagerConfig::VRBOAPI,
        ];

        // pricing tax policy (included or excluded)
        $pricingTaxInclusive = VikBooking::ivaInclusa();
        $handleVat = $this->options['handle_vat'] ?? 1;

        // obtain the iterable dates period
        $datePeriod = VBORmsPace::getInstance()->getDatePeriodInterval(strtotime($this->options['from_date']), strtotime($this->options['to_date']), 'P1D');

        // build the list of OTA rates flow records
        $otaflowRecords = [];

        // scan the involved listing IDs
        foreach ($involvedListingIds as $listingId) {
            // scan the involved rate plan IDs
            foreach ($involvedRateIds as $ratePlanId) {
                // tell whether the rate plan is tax eligible
                $taxEligible = !empty($this->ratePlansList[$ratePlanId]['idiva']);
                // scan the involved channel IDs
                foreach ($involvedChannelIds as $channelId) {
                    // build listing flow container
                    $listingRecords = [
                        'id_room'    => $listingId,
                        'id_price'   => $ratePlanId,
                        'id_channel' => $channelId,
                        'rates'      => [],
                    ];

                    // count matches for the current listing, rate plan and channel
                    $totMatches = 0;

                    // iterate all stay date intervals
                    foreach ($datePeriod as $period) {
                        // match the last flow record for the current date, listing, rate plan and channel
                        $matchRecord = $this->matchPeriodLastFlowRecord($flowRecords, $period, 'DAY', $listingId, function($record) use ($ratePlanId, $channelId) {
                            if ($record['vbo_price_id'] != $ratePlanId) {
                                // rate plan mismatch
                                return false;
                            }
                            if ($record['channel_id'] != $channelId) {
                                // channel mismatch
                                return false;
                            }
                            // record is valid
                            return true;
                        });

                        if ($matchRecord) {
                            // increase counter
                            $totMatches++;
                        }

                        // calculate OTA nightly rate, if any
                        $otaNightlyRate = $matchRecord ? (floatval($matchRecord['nightly_fee'] ?? 0)) : null;
                        if ($otaNightlyRate && $taxEligible && $handleVat) {
                            // check if rate needs to be adjusted by VAT/GST
                            if ($pricingTaxInclusive && in_array($channelId, $netRateOtas)) {
                                // add VAT/GST to nightly rate
                                $otaNightlyRate = VikBooking::sayPackagePlusIva($otaNightlyRate, ($this->ratePlansList[$ratePlanId]['idiva'] ?? 0), true);
                            } elseif (!$pricingTaxInclusive && !in_array($channelId, $netRateOtas)) {
                                // deduct VAT/GST from nightly rate
                                $otaNightlyRate = VikBooking::sayPackageMinusIva($otaNightlyRate, ($this->ratePlansList[$ratePlanId]['idiva'] ?? 0), true);
                            }
                        }

                        // set OTA nightly rate (null if no matching records found)
                        $listingRecords['rates'][$period->format('Y-m-d')] = $otaNightlyRate;
                    }

                    if ($totMatches) {
                        // it is safe to push the current listing flow container
                        $otaflowRecords[] = $listingRecords;
                    }
                }
            }
        }

        if (!$otaflowRecords) {
            // no records found
            return [];
        }

        // obtain the OTAs data
        $otasData = [];
        $vcm_logos = VikBooking::getVcmChannelsLogo('', true);
        foreach ($involvedChannelIds as $channelId) {
            // build channel default data
            $channelData = [
                'id'   => $channelId,
                'name' => $channelId,
                'logo' => null,
            ];

            // get channel details
            $channelInfo = VikChannelManager::getChannel($channelId);

            if ($channelInfo) {
                // update proper name
                $channelData['name'] = (string) ($channelInfo['name'] ?? '') ?: $channelData['name'];
                $channelData['name'] = preg_replace('/api$/', '', $channelData['name']);
                $channelData['name'] = preg_replace('/^(google)(hotel|vr)$/', '$1 $2', $channelData['name']);
                $channelData['name'] = ucwords($channelData['name']);

                // attempt to find the channel logo URL
                $ch_logo_url = $vcm_logos ? $vcm_logos->setProvenience($channelInfo['name'], $channelInfo['name'])->getTinyLogoURL() : '';
                $channelData['logo'] = $ch_logo_url ?: null;
            }

            // set channel data
            $otasData[$channelId] = $channelData;
        }

        // return the list of OTA flow records and data, if any
        return [
            'data'    => $otasData,
            'records' => $otaflowRecords,
        ];
    }

    /**
     * Returns the current flow records.
     * 
     * @param   bool    $ascending  True for ascending ordering.
     * 
     * @return  array
     */
    public function getFlowRecords(bool $ascending = false): array
    {
        if ($ascending) {
            return array_reverse($this->flowRecords);
        }

        return $this->flowRecords;
    }

    /**
     * Returns the main rate plan ID under evaluation.
     * 
     * @return  int
     */
    public function getMainRatePlanId(): int
    {
        return $this->mainRatePlanId;
    }

    /**
     * Updates the current options through merging.
     * 
     * @param   array   $options    Associative list to merge.
     * 
     * @return  self
     */
    public function setOptions(array $options): VBORmsRatesRegistry
    {
        $this->options = array_merge($this->options, $options);

        return $this;
    }

    /**
     * Given a period date and interval type, matches the last flow
     * record and returns its creation date and time object.
     * 
     * @param   DateTimeInterface   $period         The data period under evaluation.
     * @param   string              $intervalType   The data evaluation interval type.
     * @param   ?array              $listingIds     Optional list of listing IDs to filter.
     * 
     * @return  ?DateTimeInterface
     */
    public function matchPeriodLastFlowDate(DateTimeInterface $period, string $intervalType, ?array $listingIds = null)
    {
        // get the timestamps at midnight for the evaluation date
        $dt = clone $period;
        $dt->modify('00:00:00');
        $tsFrom = $dt->format('U');
        $tsTo = $tsFrom;

        if ($intervalType === 'MONTH') {
            $tsTo = strtotime(date('Y-m-t', $tsFrom));
        }

        foreach ($this->getFlowRecords() as $flowRecord) {
            if ($listingIds && !in_array($flowRecord['vbo_room_id'], $listingIds)) {
                // ignore flow record
                continue;
            }

            if (strtotime($flowRecord['day_from']) <= $tsTo && strtotime($flowRecord['day_to']) >= $tsFrom) {
                // intersection found, return the date object for the record creation
                return new DateTime($flowRecord['created_on'], new DateTimeZone(date_default_timezone_get()));
            }
        }

        return null;
    }

    /**
     * Given a period date, interval type and listing, matches the last nightly rate applied.
     * 
     * @param   DateTimeInterface   $period         The data period under evaluation.
     * @param   string              $intervalType   The data evaluation interval type.
     * @param   int                 $listingId      Listing IDs to match.
     * 
     * @return  ?float
     */
    public function matchPeriodLastNightlyRate(DateTimeInterface $period, string $intervalType, int $listingId)
    {
        // get the timestamps at midnight for the evaluation date
        $dt = clone $period;
        $dt->modify('00:00:00');
        $tsFrom = $dt->format('U');
        $tsTo = $tsFrom;

        if ($intervalType === 'MONTH') {
            $tsTo = strtotime(date('Y-m-t', $tsFrom));
        }

        foreach ($this->getFlowRecords() as $flowRecord) {
            if ($flowRecord['vbo_room_id'] != $listingId) {
                // ignore flow record
                continue;
            }

            if (strtotime($flowRecord['day_from']) <= $tsTo && strtotime($flowRecord['day_to']) >= $tsFrom) {
                // intersection found, return the record nightly rate
                return (float) ($flowRecord['nightly_fee'] ?? 0);
            }
        }

        return null;
    }

    /**
     * Given a list of records, period date, interval type and listing, matches the last flow record applied.
     * 
     * @param   array               $records        The flow records list to evaluate.
     * @param   DateTimeInterface   $period         The data period under evaluation.
     * @param   string              $intervalType   The data evaluation interval type.
     * @param   int                 $listingId      Listing IDs to match.
     * @param   ?callable           $callFilter     Optional callback to filter the record.
     * 
     * @return  ?array
     */
    public function matchPeriodLastFlowRecord(array $records, DateTimeInterface $period, string $intervalType, int $listingId, ?callable $callFilter = null)
    {
        // get the timestamps at midnight for the evaluation date
        $dt = clone $period;
        $dt->modify('00:00:00');
        $tsFrom = $dt->format('U');
        $tsTo = $tsFrom;

        if ($intervalType === 'MONTH') {
            $tsTo = strtotime(date('Y-m-t', $tsFrom));
        }

        foreach ($records as $flowRecord) {
            if ($flowRecord['vbo_room_id'] != $listingId) {
                // ignore flow record
                continue;
            }

            if (strtotime($flowRecord['day_from']) <= $tsTo && strtotime($flowRecord['day_to']) >= $tsFrom) {
                // intersection found, check for custom filter validation (i.e. specific channel ID)
                if ($callFilter && !call_user_func_array($callFilter, [$flowRecord])) {
                    // ignore flow record due to negative filter validation
                    continue;
                }

                // return the record found
                return $flowRecord;
            }
        }

        return null;
    }
}
