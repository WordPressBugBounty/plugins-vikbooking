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
 * Database history model implementor.
 * 
 * @since 1.18 (J) - 1.8 (WP)
 */
class VBOHistoryModelDatabase implements VBOHistoryModel
{
    /** @var VBOHistoryContext */
    protected $context;

    /** @var JDatabaseDriver */
    protected $db;

    /**
     * Class constructor.
     * 
     * @param  VBOHistoryContext  $context
     */
    public function __construct(VBOHistoryContext $context, $db = null)
    {
        if (!$db) {
            $db = JFactory::getDbo();
        }

        $this->context = $context;
        $this->db = $db;
    }

    /**
     * @inheritDoc
     */
    public function getItems()
    {
        $query = $this->db->getQuery(true);

        // history changes
        $query->select($this->db->qn(['h.id', 'h.id_user', 'h.username', 'h.date']));
        $query->from($this->db->qn('#__vikbooking_record_history', 'h'));

        // history change events
        $query->select($this->db->qn(['e.event', 'e.payload']));
        $query->leftjoin($this->db->qn('#__vikbooking_record_history_event', 'e') . ' ON ' . $this->db->qn('e.id_change') . ' = ' . $this->db->qn('h.id'));

        // filter changes by context
        $query->where($this->db->qn('h.id_context') . ' = ' . (int) $this->context->getID());
        $query->where($this->db->qn('h.context') . ' = ' . $this->db->q($this->context->getAlias()));

        $this->db->setQuery($query);
        
        $changes = [];

        foreach ($this->db->loadObjectList() as $row) {
            if (!isset($changes[$row->id])) {
                // construct parent change
                $change = new stdClass;
                $change->id = $row->id;
                $change->date = $row->date;
                $change->icon = '';
                $change->user = new stdClass;
                $change->user->id = $row->id_user;
                $change->user->name = $row->username;
                $change->events = [];

                // track parent change
                $changes[$row->id] = $change;
            }

            if (!empty($row->event)) {
                try {
                    // attempt to unserialize object
                    $event = @unserialize($row->payload);

                    if ($event instanceof VBOHistoryDetector) {
                        $changes[$row->id]->events[] = $event;
                    }
                } catch (Exception $err) {
                    // ignore unserialization errors
                }
            }
        }

        // fetch a summary icon for each change
        foreach ($changes as $change) {
            // take each icon for all the events under this change
            $icons = array_values(array_unique(array_map(function($event) {
                return $event->getIcon();
            }, $change->events)));

            if (count($icons) == 1) {
                // use the only icon found
                $change->icon = $icons[0];
            } else {
                // multiple icons, use a generic one
                $change->icon = VikBookingIcons::i('pencil-alt');
            }
        }

        return array_values($changes);
    }

    /**
     * @inheritDoc
     */
    public function save(array $events, VBOHistoryCommitter $committer)
    {
        $change = new stdClass;
        $change->context = $this->context->getAlias();
        $change->id_context = $this->context->getID();
        $change->date = JFactory::getDate()->toSql();
        $change->id_user = $committer->getID();
        $change->username = $committer->getName();

        // register parent change
        $result = $this->db->insertObject('#__vikbooking_record_history', $change, 'id');

        if (!$result || empty($change->id)) {
            // query failed or record ID empty
            throw new UnexpectedValueException('Unable to save the specified record changes.', 500);
        }

        // iterate all the detected change details
        foreach ($events as $event) {
            if (!$event instanceof VBOHistoryDetector) {
                continue;
            }

            $data = new stdClass;
            $data->id_change = $change->id;
            $data->event = $event->getEvent();
            $data->payload = serialize($event);

            // register detected changes
            $this->db->insertObject('#__vikbooking_record_history_event', $data, 'id');
        }
    }
}
