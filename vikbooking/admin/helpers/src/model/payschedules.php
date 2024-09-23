<?php
/** 
 * @package     VikBooking
 * @subpackage  core
 * @author      E4J s.r.l.
 * @copyright   Copyright (C) 2024 E4J s.r.l. All Rights Reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * @link        https://vikwp.com
 */

// No direct access
defined('ABSPATH') or die('No script kiddies please!');

/**
 * VikBooking model Payment Schedules.
 * 
 * @since   1.16.10 (J) - 1.6.10 (WP)
 */
class VBOModelPayschedules
{
    /** @var array */
    protected $booking = [];

    /**
     * Proxy for immediately accessing the object.
     * 
     * @return  VBOModelPayschedules
     */
    public static function getInstance()
    {
        return new static;
    }

    /**
     * Class constructor.
     */
    public function __construct()
    {}

    /**
     * Sets the current booking record details.
     * 
     * @param   array   $booking    The booking record details.
     * 
     * @return  self
     */
    public function setBooking(array $booking)
    {
        $this->booking = $booking;

        return $this;
    }

    /**
     * Stores a new payment schedule record.
     * 
     * @param   array|object    $record     The record to store.
     * 
     * @return  int|null                    The new record ID or null.
     */
    public function save($record)
    {
        $dbo = JFactory::getDbo();

        $record = (object) $record;

        $dbo->insertObject('#__vikbooking_payschedules', $record, 'id');

        return $record->id ?? null;
    }

    /**
     * Updates an existing payment schedule record.
     * 
     * @param   array|object    $record     The record details to update.
     * 
     * @return  bool
     */
    public function update($record)
    {
        $dbo = JFactory::getDbo();

        $record = (object) $record;

        return (bool) $dbo->updateObject('#__vikbooking_payschedules', $record, 'id');
    }

    /**
     * Item loading implementation.
     *
     * @param   mixed  $pk   An optional primary key value to load the row by,
     *                       or an associative array of fields to match.
     *
     * @return  object|null  The record object on success, null otherwise.
     */
    public function getItem($pk)
    {
        $dbo = JFactory::getDbo();

        $q = $dbo->getQuery(true)
            ->select('*')
            ->from($dbo->qn('#__vikbooking_payschedules'));

        if (is_array($pk)) {
            foreach ($pk as $column => $value) {
                $q->where($dbo->qn($column) . ' = ' . $dbo->q($value));
            }
        } else {
            $q->where($dbo->qn('id') . ' = ' . (int) $pk);
        }

        $dbo->setQuery($q, 0, 1);

        $record = $dbo->loadObject();

        if ($record) {
            $this->normalizeObject($record);
        }

        return $record;
    }

    /**
     * Items loading implementation.
     *
     * @param   array   $clauses    List of associative columns to fetch
     *                              (column => [operator, value])
     * @param   int     $start      Query limit start.
     * @param   int     $lim        Query limit value.
     *
     * @return  array               List of record objects.
     */
    public function getItems(array $clauses = [], $start = 0, $lim = 0)
    {
        $dbo = JFactory::getDbo();

        $q = $dbo->getQuery(true)
            ->select('*')
            ->from($dbo->qn('#__vikbooking_payschedules'));

        foreach ($clauses as $column => $data) {
            if (!is_array($data) || !isset($data['value'])) {
                continue;
            }
            $q->where($dbo->qn($column) . ' ' . ($data['operator'] ?? '=') . ' ' . $dbo->q($data['value']));
        }

        $q->order($dbo->qn('status') . ' ASC');
        $q->order($dbo->qn('fordt') . ' ASC');

        $dbo->setQuery($q, $start, $lim);

        $records = $dbo->loadObjectList();

        return array_map([$this, 'normalizeObject'], $records);
    }

    /**
     * Watches and eventually processes the automatic payment collections scheduled.
     * 
     * @param   int     $lim    the limit of payments to process, defaults to 5 per execution.
     * 
     * @return  int             number of payments processed, where -1 indicates no running.
     */
    public function watch($lim = 5)
    {
        $dbo = JFactory::getDbo();

        // number of schedules processed
        $processed = 0;

        // build date object intervals
        $now_dt = JFactory::getDate('now', new DateTimeZone(date_default_timezone_get()));
        $yesterday_dt = (clone $now_dt)->modify('-1 day');

        // fetch unprocessed payments scheduled within the last 24 hours
        $q = $dbo->getQuery(true)
            ->select('*')
            ->from($dbo->qn('#__vikbooking_payschedules'))
            ->where($dbo->qn('fordt') . ' >= ' . $dbo->q($yesterday_dt->toSql(true)))
            ->where($dbo->qn('fordt') . ' <= ' . $dbo->q($now_dt->toSql(true)))
            ->where($dbo->qn('status') . ' = 0');

        $dbo->setQuery($q, 0, $lim);
        $payschedules = $dbo->loadObjectList();

        foreach ($payschedules as $payschedule) {
            // immediately update the record status to processed (1)
            $dbo->setQuery(
                $dbo->getQuery(true)
                    ->update($dbo->qn('#__vikbooking_payschedules'))
                    ->set($dbo->qn('status') . ' = 1')
                    ->where($dbo->qn('id') . ' = ' . (int) $payschedule->id)
            );
            $dbo->execute();

            // process the automatic payment
            try {
                if ($this->processPaySchedule($payschedule)) {
                    // increase counter
                    $processed++;
                }
            } catch (Exception $e) {
                // append failure execution log and update status (2 = error)
                 $dbo->setQuery(
                    $dbo->getQuery(true)
                        ->update($dbo->qn('#__vikbooking_payschedules'))
                        ->set($dbo->qn('logs') . ' = ' . $dbo->q(ltrim($payschedule->logs . "\n" . $e->getMessage(), "\n")))
                        ->set($dbo->qn('status') . ' = 2')
                        ->where($dbo->qn('id') . ' = ' . (int) $payschedule->id)
                );
                $dbo->execute();
            }
        }

        return $processed;
    }

    /**
     * Parses a payment schedule object to normalize some of its properties.
     * 
     * @param   object  $payschedule    The payment schedule record object.
     * 
     * @return  object
     */
    protected function normalizeObject($payschedule)
    {
        $now_dt = JFactory::getDate('now');
        $target_dt = JFactory::getDate($payschedule->fordt);

        $payschedule->time_hm = $target_dt->format('H:i');
        $payschedule->dt_diff = VBORemindersHelper::getInstance()->relativeDatesDiff($target_dt, $now_dt);

        return $payschedule;
    }

    /**
     * Processes the scheduled automatic payment object by attempting
     * to collect the money from the CC/VCC and by updating all data.
     * 
     * @param   object  $payschedule    The payment schedule record object.
     * 
     * @return  bool
     * 
     * @throws  Exception
     */
    protected function processPaySchedule($payschedule)
    {
        $dbo = JFactory::getDbo();

        $booking_info = VikBooking::getBookingInfoFromID($payschedule->idorder);
        if (!$booking_info) {
            throw new Exception('Reservation not found.', 404);
        }

        // currency code
        $currency_code = !empty($booking_info['chcurrency']) ? $booking_info['chcurrency'] : VikBooking::getCurrencyName();
        if (empty($currency_code) || strlen((string) $currency_code) != 3) {
            // fallback to currency transaction code
            $currency_code = VikBooking::getCurrencyCodePp();
        }

        // access the reservation model
        $model = VBOModelReservation::getInstance($booking_info, true);

        try {
            // get the card details associated with the booking
            $card = $model->getCardValuePairs();

            if (!$card) {
                throw new Exception('No credit card details found for the reservation.', 500);
            }

            // set transaction values within the card array
            $card['currency']   = $currency_code;
            $card['amount']     = $payschedule->amount;
            $card['cardholder'] = $card['cardholder'] ?? $card['name'] ?? null;
            $card['expiry']     = $card['expiry'] ?? $card['expiration_date'] ?? null;

            // get the payment processor with the card found
            $processor = $model->getPaymentProcessor($card);
        } catch (Exception $e) {
            // propagate the error
            throw $e;
        }

        if (!method_exists($processor, 'isDirectChargeSupported') || !$processor->isDirectChargeSupported()) {
            throw new Exception('The payment method does not allow to directly charge credit cards.', 500);
        }

        // get the processor name
        $payment_name = $model->getPaymentName();

        // default transaction response
        $array_result = [
            'verified' => 0,
        ];

        try {
            // perform the transaction
            $array_result = $processor->directCharge();
        } catch (Exception $e) {
            // set error message
            $array_result['log'] = sprintf(JText::translate('VBO_CC_TN_ERROR') . " \n%s", $e->getMessage());
        }

        if ($array_result['verified'] != 1) {
            // erroneous response
            if (!empty($array_result['log']) && is_string($array_result['log'])) {
                throw new Exception($array_result['log'], 500);
            } else {
                throw new Exception('Operation failed.', 500);
            }
        }

        // valid transaction response!
        // update booking details

        // get the amount paid
        $tn_amount = isset($array_result['tot_paid']) ? (float) $array_result['tot_paid'] : null;

        // get the log string, if any
        $tn_log = !empty($array_result['log']) ? $array_result['log'] : '';

        // update record
        $upd_record = new stdClass;
        $upd_record->id = $booking_info['id'];
        if ($tn_amount) {
            // update amount paid
            $upd_record->totpaid = $booking_info['totpaid'] + $tn_amount;
            // update payable amount (if needed)
            $new_payable = $booking_info['payable'] - $tn_amount;
            $new_payable = $new_payable < 0 ? 0 : $new_payable;
            $upd_record->payable = $new_payable;
        }
        if ($tn_log) {
            $upd_record->paymentlog = $booking_info['paymentlog'] . "\n\n" . date('c') . "\n" . $tn_log;
        }
        $upd_record->paymcount = ((int) $booking_info['paymcount'] + 1);

        // update reservation record
        $dbo->updateObject('#__vikbooking_orders', $upd_record, 'id');

        // payment processor name
        $pay_process_name = $payment_name ?: 'CC Scheduled Charge';

        // handle transaction data to eventually support a later transaction of type refund
        $tn_data = isset($array_result['transaction']) ? $array_result['transaction'] : null;
        if ($tn_amount) {
            // check event data payload to store
            if (is_array($tn_data)) {
                // set key
                $tn_data['amount_paid'] = $tn_amount;
            } elseif (is_object($tn_data)) {
                // set property
                $tn_data->amount_paid = $tn_amount;
            } elseif (!$tn_data) {
                // build an array (we add the payment name because we know there is no other transaction data)
                $tn_data = [
                    'amount_paid'    => $tn_amount,
                    'payment_method' => $pay_process_name,
                ];
            }
        }

        /**
         * Check if the payment processor returned the information about the amount of processing fees.
         */
        if ($tn_data && isset($array_result['tot_fees']) && $array_result['tot_fees']) {
            // check event data payload to store
            if (is_array($tn_data)) {
                // set key
                $tn_data['processing_fees'] = (float)$array_result['tot_fees'];
            } elseif (is_object($tn_data)) {
                // set property
                $tn_data->processing_fees = (float)$array_result['tot_fees'];
            }
        }

        // add an extra data to identify the transaction as automatic, from a schedule
        if ($tn_data) {
            if (is_array($tn_data)) {
                // set key
                $tn_data['pay_schedule'] = $payschedule->id ?? 1;
            } elseif (is_object($tn_data)) {
                // set property
                $tn_data->pay_schedule = $payschedule->id ?? 1;
            }
        }

        // Booking History
        $main_descr = JText::translate('VBO_AUTOPAY_SCHEDULED');
        $main_descr = $main_descr != 'VBO_AUTOPAY_SCHEDULED' ? $main_descr : 'Automatic payment collection';
        $ev_descr = "{$main_descr} - {$pay_process_name}";
        VikBooking::getBookingHistoryInstance()->setBid($booking_info['id'])->setExtraData($tn_data)->store('P' . ($booking_info['paymcount'] > 0 ? 'N' : '0'), $ev_descr);

        return true;
    }
}
