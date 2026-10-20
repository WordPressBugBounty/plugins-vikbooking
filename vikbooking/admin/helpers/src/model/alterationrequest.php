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
 * VikBooking reservation alteration request model.
 *
 * @since   1.18.17 (J) - 1.8.17 (WP)
 */
class VBOModelAlterationrequest extends VBOMvcModel
{
    /**
     * The database table name.
     * 
     * @var string
     */
    protected $tableName = '#__vikbooking_res_alteration_requests';

    /**
     * @inheritDoc
     */
    public function getItem($pk)
    {
        $item = parent::getItem($pk);

        if ($item) {
            // auto-unserialize record data
            $item->data = !empty($item->data) ? unserialize($item->data) : null;
        }

        return $item;
    }

    /**
     * @inheritDoc
     */
    public function getItems(array $clauses = [], int $start = 0, int $lim = 0, array $cols = [], array $ordering = [])
    {
        $items = parent::getItems($clauses, $start, $lim, $cols, $ordering);

        return array_map(function($item) {
            if (isset($item->data)) {
                $item->data = unserialize($item->data);
            }

            return $item;
        }, $items);
    }

    /**
     * Updates the status for a given reservation alteration request record.
     * 
     * @param   VBOBookingRegistry  $registry   Current booking registry.
     * @param   object              $item       Existing reservation alteration record (item).
     * @param   string              $status     The status enum to set for the alteration request.
     * @param   ?array              $options    Optional associative list of operation options.
     * 
     * @return  true
     * 
     * @throws  Exception
     */
    public function updateStatus(VBOBookingRegistry $registry, object $item, string $status, ?array $options = null)
    {
        // make sure the status is known
        if (!in_array(strtoupper($status), ['ACCEPTED', 'DECLINED', 'CANCELLED', 'CANCELED'])) {
            throw new Exception('Invalid status to set for the alteration request.', 400);
        }

        // check if OTA needs to be notified before updating the internal record
        if ($registry->isFromOTA() && !empty($item->ota_alteration_id)) {
            if (!class_exists('VCMAlterationRequestor')) {
                throw new Exception('Please update the Channel Manager.', 500);
            }

            // update OTA first, without catching any errors
            (new VCMAlterationRequestor($item))->updateStatus($status);
        }

        // update internal alteration request record
        $result = $this->save([
            'id'     => $item->id,
            // set new status on record
            'status' => strtolower($status),
            // set new status on registry blob
            'data'   => $item->data->setAlterationRequest(
                array_merge(
                    (array) $item->data->getAlterationRequest(),
                    ['status' => $status]
                )
            ),
        ]);

        if (!$result) {
            throw new Exception('Could not update reservation alteration request record.', 500);
        }

        // get who executed the operation
        $user = JFactory::getUser();
        $author = ($options['author'] ?? null) ?: $user->name;

        // save booking history event
        VikBooking::getBookingHistoryInstance($registry->getID())
            ->setBookingData($registry->getData(), $registry->getRooms())
            ->setExtraData([
                'status' => $status,
                'by'     => 'host',
            ])
            ->store('AU', sprintf('%s %s', $status, $author ? "({$author})" : '(---)'));

        return true;
    }

    /**
     * Runs before saving the given data.
     * 
     * @param   array    &$data  A reference to the data to save.
     * 
     * @return  bool     True on success, false otherwise.
     */
    protected function preflight(array &$data)
    {
        if (isset($data['data']) && ($data['data'] instanceof VBOBookingRegistry)) {
            // serialize blob
            $data['data'] = serialize($data['data']);
        }

        if (empty($data['id'])) {
            // force the creation date
            $data['created_on'] = JFactory::getDate('now', JFactory::getApplication()->get('offset'))->toSql();
        }

        if (!empty($data['updated_on'])) {
            // convert the given date-time to UTC
            $data['updated_on'] = JFactory::getDate($data['updated_on'], JFactory::getApplication()->get('offset'))->toSql();
        }

        if (!empty($data['id']) && empty($data['updated_on'])) {
            // set update date when updating the record
            $data['updated_on'] = JFactory::getDate('now', JFactory::getApplication()->get('offset'))->toSql();
        }

        if (!empty($data['status'])) {
            $data['status'] = strtolower((string) $data['status']);
        }

        if (!empty($data['initiated_by'])) {
            $data['initiated_by'] = strtolower((string) $data['initiated_by']);
        }

        if (empty($data['id'])) {
            // make sure we don't have other alteration requests for the same booking when adding a new record
            if (empty($data['idorder']) || $this->getItem(['idorder' => $data['idorder']])) {
                return false;
            }
        }

        return parent::preflight($data);
    }
}
