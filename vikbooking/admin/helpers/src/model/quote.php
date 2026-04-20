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
 * VikBooking quote model.
 *
 * @since   1.18.8 (J) - 1.8.8 (WP)
 */
class VBOModelQuote extends VBOMvcModel
{
    /** @var  string */
    protected $tableName = '#__vikbooking_quotations';

    /** @var  int */
    protected $lastRecordsFound = 0;

    /**
     * @inheritDoc
     */
    protected function preflight(array &$data)
    {
        if (empty($data['id'])) {
            // we are saving a new record
            do {
                // always generate a unique secret ID for new records
                $data['uuid'] = VikBooking::uuid();
                // repeat in case a record with the same UUID already exists
            } while ($this->getItem(['uuid' => $data['uuid']]));

            if (empty($data['name'])) {
                // default to current date-time string
                $data['name'] = JFactory::getDate('now')->format('Y-m-d H:i:s');
            }

            // set creation date-time
            $data['created_on'] = JFactory::getDate('now')->toSql();
        }

        // always set the IP address
        $data['ip'] = JFactory::getApplication()->input->server->getString('REMOTE_ADDR', '') ?: null;

        if (empty($data['created_by'])) {
            // always set a name
            $user = JFactory::getUser();
            $data['created_by'] = $user->name ?: 'User';
        }

        if (!empty($data['valid_until'])) {
            // convert the provided date-time string into UTC
            $data['valid_until'] = JFactory::getDate($data['valid_until'], JFactory::getApplication()->get('offset'))->toSql();
        }

        if (!empty($data['country_3_code']) && strlen($data['country_3_code']) !== 3) {
            // attempt to convert a country iso2 code into the corresponding iso3 char code
            $data['country_3_code'] = VikBooking::getCPinInstance()->get3CharCountry($data['country_3_code']);
        }

        return parent::preflight($data);
    }

    /**
     * Loads quote records and related booking information to allow pagination.
     * 
     * @param   int     $offset     The query offset start.
     * @param   int     $limit      The query limit.
     * @param   array   $options    Associative list of optional loading options.
     * 
     * @return  array
     */
    public function loadBookingRecords(int $offset = 0, int $limit = 0, array $options = [])
    {
        $dbo = JFactory::getDbo();

        // extract filters, if any
        $filters = (array) ($options['filters'] ?? []);

        // query the quotation records first
        $q = $dbo->getQuery(true)
            ->select('SQL_CALC_FOUND_ROWS *')
            ->from($dbo->qn('#__vikbooking_quotations'));

        foreach ($filters as $column => $filter) {
            if (is_scalar($filter)) {
                $q->where($dbo->qn($column) . ' = ' . $dbo->q($filter));
            } elseif (is_null($filter)) {
                $q->where($dbo->qn($column) . ' IS NULL');
            } else {
                foreach ($filter as $filter_data) {
                    if (!is_array($filter_data) || !isset($filter_data['operand']) || !isset($filter_data['value'])) {
                        continue;
                    }
                    $q->where($dbo->qn($column) . ' ' . $filter_data['operand'] . ' ' . $dbo->q($filter_data['value']));
                }
            }
        }

        $q->order($dbo->qn('created_on') . ' DESC');

        $dbo->setQuery($q, $offset, $limit);
        $quotes = $dbo->loadObjectList();

        // count the total number of rows found
        $this->lastRecordsFound = 0;
        if ($quotes) {
            $dbo->setQuery(
                $dbo->getQuery(true)
                    ->select('FOUND_ROWS()')
            );
            $this->lastRecordsFound = (int) $dbo->loadResult();
        }

        // obtain the quote IDs
        $quoteIds = array_column($quotes, 'id');

        if ($quoteIds) {
            // query bookings and booking rooms with the involved quotations
            $dbo->setQuery(
                $dbo->getQuery(true)
                    ->select([
                        $dbo->qn('o.id'),
                        $dbo->qn('o.ts'),
                        $dbo->qn('o.status'),
                        $dbo->qn('o.days'),
                        $dbo->qn('o.checkin'),
                        $dbo->qn('o.checkout'),
                        $dbo->qn('o.sid'),
                        $dbo->qn('o.idpayment'),
                        $dbo->qn('o.roomsnum'),
                        $dbo->qn('o.total'),
                        $dbo->qn('o.idquote'),
                        $dbo->qn('or.idroom'),
                        $dbo->qn('or.adults'),
                        $dbo->qn('or.children'),
                        $dbo->qn('or.pets'),
                        $dbo->qn('or.idtar'),
                        $dbo->qn('or.optionals'),
                        $dbo->qn('or.roomindex'),
                        $dbo->qn('or.cust_cost'),
                        $dbo->qn('or.cust_idiva'),
                        $dbo->qn('or.cust_cpolicy_id'),
                        $dbo->qn('or.extracosts'),
                        $dbo->qn('or.room_cost'),
                    ])
                    ->from($dbo->qn('#__vikbooking_orders', 'o'))
                    ->leftJoin($dbo->qn('#__vikbooking_ordersrooms', 'or') . ' ON ' . $dbo->qn('or.idorder') . ' = ' . $dbo->qn('o.id'))
                    ->where($dbo->qn('o.idquote') . ' IN (' . implode(', ', array_map('intval', $quoteIds)) . ')')
                    ->order($dbo->qn('o.idquote') . ' ASC')
                    ->order($dbo->qn('o.id') . ' ASC')
                    ->order($dbo->qn('or.id') . ' ASC')
            );

            $bookingRooms = $dbo->loadObjectList();

            // pre-cache all involved rooms data
            $roomsData = VikBooking::getAvailabilityInstance()->loadRooms(array_values(array_unique(array_column($bookingRooms, 'idroom'))), 0, true);

            // scan all quote records
            foreach ($quotes as &$quote) {
                // build quote solutions
                $quote->solutions = [];

                // get all booking room records for the current quote
                $quoteBookings = array_values(array_filter($bookingRooms, function($bookingRoom) use ($quote) {
                    return $bookingRoom->idquote == $quote->id;
                }));

                // obtain a unique list of booking IDs (solutions) involved
                $bookingIds = array_values(array_unique(array_column($quoteBookings, 'id')));

                // scan all booking solutions
                foreach ($bookingIds as $bookingId) {
                    // get all booking rooms data
                    $bookingRoomsData = array_values(array_filter($quoteBookings, function($bookingRoom) use ($bookingId) {
                        return $bookingRoom->id == $bookingId;
                    }));

                    // build quote solution rooms
                    $quoteSolutionRooms = [];
                    foreach ($bookingRoomsData as $bookingRoomData) {
                        // push booking room data
                        $quoteSolutionRooms[] = (object) [
                            'id'              => $bookingRoomData->idroom,
                            'name'            => $roomsData[$bookingRoomData->idroom]['name'] ?? $bookingRoomData->idroom,
                            'img'             => $roomsData[$bookingRoomData->idroom]['img'] ?? null,
                            'adults'          => $bookingRoomData->adults,
                            'children'        => $bookingRoomData->children,
                            'pets'            => $bookingRoomData->pets,
                            'idtar'           => $bookingRoomData->idtar,
                            'optionals'       => $bookingRoomData->optionals,
                            'roomindex'       => $bookingRoomData->roomindex,
                            'cust_cost'       => $bookingRoomData->cust_cost,
                            'cust_idiva'      => $bookingRoomData->cust_idiva,
                            'cust_cpolicy_id' => $bookingRoomData->cust_cpolicy_id,
                            'extracosts'      => $bookingRoomData->extracosts ? (array) json_decode($bookingRoomData->extracosts, true) : [],
                            'room_cost'       => $bookingRoomData->room_cost,
                        ];
                    }

                    // build quote booking solution
                    $quoteSolution = (object) [
                        'id'        => $bookingId,
                        'checkin'   => $bookingRoomsData[0]->checkin,
                        'checkout'  => $bookingRoomsData[0]->checkout,
                        'nights'    => $bookingRoomsData[0]->days,
                        'status'    => $bookingRoomsData[0]->status,
                        'total'     => $bookingRoomsData[0]->total,
                        'idpayment' => $bookingRoomsData[0]->idpayment,
                        'sid'       => $bookingRoomsData[0]->sid,
                        'ts'        => $bookingRoomsData[0]->ts,
                        'rooms'     => $quoteSolutionRooms,
                    ];

                    // push quote solution
                    $quote->solutions[] = $quoteSolution;
                }
            }

            // unset last reference even if we are dealing with objects
            unset($quote);
        }

        // return the list of quote records
        return $quotes;
    }

    /**
     * Counts the last records found.
     * 
     * @return  int
     */
    public function countRecordsFound()
    {
        return $this->lastRecordsFound;
    }

    /**
     * Should be called after a booking that belongs to a quote gets confirmed.
     * In case of multiple booking solutions, releases the previously locked
     * rooms that were included in other booking solutions.
     * 
     * @param   int     $quoteId      The quote identifier.
     * @param   ?int    $confirmedId  Optional booking ID that was confirmed.
     * 
     * @return  bool    True if any other booking solutions were involved for release.
     */
    public function releaseUnconfirmedSolutions(int $quoteId, ?int $confirmedId = null)
    {
        $dbo = JFactory::getDbo();

        $dbo->setQuery(
            $dbo->getQuery(true)
                ->select($dbo->qn('id'))
                ->from($dbo->qn('#__vikbooking_orders'))
                ->where($dbo->qn('idquote') . ' = ' . $quoteId)
                ->where($dbo->qn('status') . ' != ' . $dbo->q('confirmed'))
                ->where($dbo->qn('id') . ' != ' . (int) $confirmedId)
        );

        $releaseBookingIds = array_map('intval', array_column($dbo->loadAssocList(), 'id'));

        // access temp-lock model
        $tmpLockModel = VBOMvcModel::getInstance('tmplock');

        // scan all booking IDs to release, if any
        foreach ($releaseBookingIds as $releaseBookingId) {
            // delete any previously locked record for this booking
            $tmpLockModel->deleteFromBooking($releaseBookingId);
        }

        // check whether the CM may have locked rooms on OTAs temporarily
        if ($releaseBookingIds && method_exists('VCMRequestAvailability', 'setForRelease')) {
            $vcmAv = VCMRequestAvailability::getInstance();
            // let the CM schedule the release of the involved and unconfirmed booking IDs, if needed
            $vcmAv->setForRelease($releaseBookingIds);
            if ($confirmedId) {
                // delete any release schedule for the confirmed reservation
                $vcmAv->deleteFromBooking($confirmedId);
            }
        }

        return !empty($releaseBookingIds);
    }

    /**
     * Returns a list of booking IDs assigned to the given quote ID.
     * 
     * @param   int     $quoteId    The quote identifier.
     * 
     * @return  array   List of related booking IDs or empty array.
     */
    public function getRelatedBookingIds(int $quoteId)
    {
        $dbo = JFactory::getDbo();

        $dbo->setQuery(
            $dbo->getQuery(true)
                ->select($dbo->qn('id'))
                ->from($dbo->qn('#__vikbooking_orders'))
                ->where($dbo->qn('idquote') . ' = ' . $quoteId)
        );

        return array_map('intval', array_column($dbo->loadAssocList(), 'id'));
    }

    /**
     * Sends the quotation details via email to the customer.
     * 
     * @param   int     $quoteId    The quote identifier.
     * 
     * @return  true
     * 
     * @throws  Exception
     */
    public function sendEmail(int $quoteId)
    {
        // get record
        $quote = $this->getItem($quoteId);

        if (!$quote) {
            throw new Exception('Could not send quote via email: quote ID not found.', 404);
        }

        if (empty($quote->email)) {
            throw new Exception('Could not send quote via email: quote customer email address is empty.', 400);
        }

        if (empty($quote->message)) {
            throw new Exception('Could not send quote via email: quote message is empty.', 400);
        }

        // get sender e-mail
        $adsendermail = VBOFactory::getConfig()->get('senderemail');

        // init mail data
        $mail = new VBOMailWrapper([
            'sender'    => [$adsendermail, VikBooking::getFrontTitle()],
            'recipient' => $quote->email,
            'bcc'       => VikBooking::addAdminEmailRecipient(null, true),
            'reply'     => $adsendermail,
            'subject'   => $quote->subject ?: sprintf('%s - %s', VikBooking::getFrontTitle(), JText::translate('VBO_QUOTE_DETAILS')),
            'content'   => $this->parseMessageContent($quote),
        ]);

        /**
         * Trigger event to allow third party plugins to overwrite any aspect of the mail message.
         */
        VBOFactory::getPlatform()->getDispatcher()->trigger('onBeforeSendQuoteMail', [$mail]);

        // send e-mail
        if (!VBOFactory::getPlatform()->getMailer()->send($mail)) {
            // throw an error
            throw new Exception('Sending the email message to the customer failed.', 500);
        }

        // update "sent" flag
        $this->save([
            'id'   => $quote->id,
            'sent' => 1,
        ]);

        // always return true on success
        return true;
    }

    /**
     * Parses the content of the quote message.
     * 
     * @param   object  $quote  The quote record.
     * 
     * @return  string
     */
    public function parseMessageContent(object $quote)
    {
        // get raw message content
        $message = $quote->message ?? '';

        // get all quote related booking IDs
        $bookingIds = $this->getRelatedBookingIds($quote->id);

        // access booking registry for the first related booking (if any)
        $bookingRegistry = $bookingIds ? VBOBookingRegistry::getInstance(['id' => $bookingIds[0] ?? 0]) : null;

        // build the list of known tag parameters
        $tagParameters = [
            '{first_name}'    => $quote->first_name ?? '',
            '{last_name}'     => $quote->last_name ?? '',
            '{checkin_date}'  => $bookingRegistry ? VikBooking::formatDateTs($bookingRegistry->getStayTimestamps()[0]) : '----',
            '{checkout_date}' => $bookingRegistry ? VikBooking::formatDateTs($bookingRegistry->getStayTimestamps()[1]) : '----',
            '{num_nights}'    => $bookingRegistry ? $bookingRegistry->getTotalNights() : 0,
            '{tot_adults}'    => $bookingRegistry ? $bookingRegistry->countTotalAdults() : 0,
            '{tot_children}'  => $bookingRegistry ? $bookingRegistry->countTotalChildren() : 0,
            '{tot_guests}'    => $bookingRegistry ? $bookingRegistry->countTotalGuests() : 0,
            '{quote_link}'    => VikBooking::externalroute("index.php?option=com_vikbooking&view=quote&ref={$quote->uuid}", false),
        ];

        /**
         * Trigger event to allow third party plugins to manipulate the tag parameters.
         */
        VBOFactory::getPlatform()->getDispatcher()->trigger('onParseQuoteMailMessage', [&$tagParameters]);

        // apply replacement values for tag parameters
        foreach ($tagParameters as $tag => $value) {
            // replace tag with calculated value
            $message = str_replace($tag, $value, $message);
        }

        // return the parsed message content
        return $message;
    }
}
