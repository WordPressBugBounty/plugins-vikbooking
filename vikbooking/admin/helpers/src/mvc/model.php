<?php
/** 
 * @package     VikBooking
 * @subpackage  core
 * @author      E4J s.r.l.
 * @copyright   Copyright (C) 2021 E4J s.r.l. All Rights Reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * @link        https://vikwp.com
 */

// No direct access
defined('ABSPATH') or die('No script kiddies please!');

/**
 * VikBooking MVC base model declaration.
 *
 * @since   1.15.10 (J) - 1.5.10 (WP)
 */
abstract class VBOMvcModel extends JObject
{
    /**
     * Cached used to store the supported columns of the requested database tables.
     * 
     * @var string
     */
    protected static $fields = [];

    /**
     * The database table name.
     * 
     * @var string
     */
    protected $tableName = null;

    /**
     * The database table primary key name.
     * 
     * @var string
     */
    protected $tableKeyName = 'id';

    /**
     * Returns a new instance of the requested model.
     * 
     * @param   string  $name     The model name.
     * @param   array   $options  The model configuration.
     * 
     * @return  VBOMvcModel
     */
    public static function getInstance($name, array $options = [])
    {
        /**
         * Trigger hook to allow third party plugins to extend the default models in VikBooking without
         * altering a single line of code from the core of the plugin.
         * 
         * It is either possible to swap the name or to return a new instance of VBOMvcModel.
         * 
         * @param   string  &$name     A reference to the requested model.
         * @param   array   &$options  A reference to the model configuration.
         * 
         * @return  mixed
         */
        $results = VBOFactory::getPlatform()->getDispatcher()->filter('onBeforeCreateModelVikBooking', [&$name, &$options]);

        // filter the returned values to take only the first valid instance
        $results = array_filter($results, function($data)
        {
            return $data instanceof VBOMvcModel;
        });

        if ($results)
        {
            // class returned by a plugin, directly use it
            return reset($results);
        }

        // fallback to the default classes in VikBooking
        $suffix = str_replace('_', ' ', $name);
        $suffix = str_replace(' ', '', ucwords($suffix));

        $modelClass = 'VBOModel' . $suffix;

        if (!class_exists($modelClass))
        {
            throw new Exception(sprintf('Model [%s] not found', $name), 404);
        }

        return new $modelClass($options);
    }

    /**
     * Returns the model name.
     * 
     * @return  string
     */
    public function getName()
    {
        if (preg_match("/^VBOModel([a-z0-9_]+)$/i", get_class($this), $match))
        {
            return strtolower($match[1]);
        }

        // unable to extract model name from class, use the file name
        return basename(__FILE__, '.php');
    }

    /**
     * Basic item loading implementation.
     *
     * @param   mixed  $pk   An optional primary key value to load the row by, or an array of fields to match.
     *                       If not set the instance property value is used.
     *
     * @return  ?object      The record object on success, null otherwise.
     */
    public function getItem($pk)
    {
        $db = JFactory::getDbo();

        $query = $db->getQuery(true)
            ->select('*')
            ->from($db->qn($this->tableName));

        if (is_array($pk))
        {
            foreach ($pk as $column => $value)
            {
                $query->where($db->qn($column) . ' = ' . $db->q($value));
            }
        }
        else
        {
            $query->where($db->qn($this->tableKeyName) . ' = ' . (int) $pk);
        }

        $db->setQuery($query, 0, 1);

        return $db->loadObject();
    }

    /**
     * Items loading implementation.
     *
     * @param   array   $clauses    List of associative columns to fetch
     *                              (column => [operator, value] or column => value)
     * @param   int     $start      Query limit start.
     * @param   int     $lim        Query limit value.
     * @param   array   $cols       Optional list of columns to fetch.
     * @param   array   $ordering   Optional associative list of ordering columns.
     *
     * @return  array               List of record objects.
     * 
     * @since   1.18.8 (J) - 1.8.8 (WP)
     */
    public function getItems(array $clauses = [], int $start = 0, int $lim = 0, array $cols = [], array $ordering = [])
    {
        $db = JFactory::getDbo();

        $q = $db->getQuery(true);

        if (!$cols)
        {
            $q->select('*');
        }
        else
        {
            $q->select(array_map([$db, 'qn'], $cols));
        }

        $q->from($db->qn($this->tableName));

        foreach ($clauses as $column => $data)
        {
            if (!is_array($data))
            {
                // convert structure
                $data = ['value' => $data];
            }

            if (is_null($data['value']))
            {
                $q->where($db->qn($column) . ' IS NULL');
            }
            else
            {
                $q->where($db->qn($column) . ' ' . ($data['operator'] ?? '=') . ' ' . $db->q($data['value']));
            }
        }

        if ($ordering)
        {
            // sort by given columns
            foreach ($ordering as $orderColumn => $orderSort)
            {
                if (!is_string($orderSort))
                {
                    continue;
                }

                // set ordering column
                $q->order($db->qn($orderColumn) . ' ' . (!strcasecmp($orderSort, 'ASC') ? 'ASC' : 'DESC'));
            }
        }
        else
        {
            // default ordering column
            $q->order($db->qn($this->tableKeyName) . ' ASC');
        }

        $db->setQuery($q, $start, $lim);

        return $db->loadObjectList();
    }

    /**
     * Method to save the form data.
     *
     * @param   mixed  $data  The form data.
     *
     * @return  mixed  The primary key of the saved item, false otherwise.
     */
    public function save($data)
    {
        $data = (array) $data;

        // let the children bind the data array before save
        if (!$this->preflight($data))
        {
            return false;
        }

        // check if we have an empty record
        $isNew = empty($data[$this->tableKeyName]);

        // store the object within the database
        $pk = $this->store((array) $data);

        if (!$pk)
        {
            return false;
        }

        // let the children perform extra actions after saving
        $this->postflight((array) $data, $isNew);

        return $pk;
    }

    /**
     * Method to delete one or more records.
     *
     * @param   mixed    $pks  An array of record primary keys.
     *
     * @return  bool     True if successful, false if an error occurs.
     */
    public function delete($pks)
    {
        if (!$pks)
        {
            // nothing to delete
            return false;
        }

        if (!is_array($pks))
        {
            // wrap in an array
            $pks = [$pks];
        }

        $app = JFactory::getApplication();

        $affected = true;

        foreach ($pks as $pk)
        {
            // fetch item details
            $item = $this->getItem($pk);

            if (!$item)
            {
                continue;
            }

            /**
             * Runs before deleting the given data.
             * It is possible to validate here whether the item should be deleted or not.
             * 
             * @param   object   $data   The record to delete.
             * @param   self     $model  The current model.
             * 
             * @return  bool     False to abort the deleting process.
             * 
             * @since   1.5.10
             */
            $results = $app->triggerEvent('onBeforeDeleteVikBooking' . ucfirst($this->getName()), [$item, $this]);

            // check whether a plugin aborted the deleting process
            if (in_array(false, $results, true))
            {
                continue;
            }

            // remove item
            $result = $this->remove($item);

            /**
             * Runs after deleting the given data.
             * It is possible to perform here some extra actions.
             * 
             * @param   object   $data     The deleted item.
             * @param   bool     $success  True in case of success, false otherwise.
             * @param   self     $model    The current model.
             * 
             * @return  void
             * 
             * @since   1.5.10
             */
            $app->triggerEvent('onAfterDeleteVikBooking' . ucfirst($this->getName()), [$item, $result, $this]);

            $affected = $affected || $result;
        }

        return $affected;
    }

    /**
     * Runs before saving the given data.
     * It is possible to inject here additional properties to save.
     * 
     * @param   array    &$data  A reference to the data to save.
     * 
     * @return  bool     True on success, false otherwise.
     */
    protected function preflight(array &$data)
    {
        $app = JFactory::getApplication();

        /**
         * Runs before saving the given data.
         * It is possible to inject here additional properties to save.
         * 
         * @param   array    &$data  A reference to the data to save.
         * @param   self     $model  The current model.
         * 
         * @return  bool     False to abort the saving process.
         * 
         * @since   1.5.10
         */
        $results = $app->triggerEvent('onBeforeSaveVikBooking' . ucfirst($this->getName()), [&$data, $this]);

        // check whether a plugin aborted the saving process
        if (in_array(false, $results, true))
        {
            return false;
        }

        return true;
    }

    /**
     * Converts the array data into an object compatible with the database
     * structure of the specified table.
     * 
     * @param   array   $data  The requested data.
     * 
     * @return  object  The object ready to be saved.
     */
    protected function prepareSaveData(array $data)
    {
        $table = new stdClass;

        // bind only the columns supported by the database table
        foreach ($this->getTableColumns() as $field => $type)
        {
            if (isset($data[$field]))
            {
                // always encode in JSON format non-scalar values
                if (!is_scalar($data[$field]))
                {
                    $data[$field] = json_encode($data[$field]);
                }

                $table->{$field} = $data[$field];
            }
        }

        return $table;
    }

    /**
     * Saves the specified data into the database.
     * 
     * @param   array  $data  The requested data.
     * 
     * @return  mixed  The primary key value of the saved record (true if missing).
     *                 Returns false in case of failure. 
     */
    final protected function store(array $data)
    {
        // construct the object that will be saved
        $table = $this->prepareSaveData($data);

        if (!$table)
        {
            return false;
        }

        $db = JFactory::getDbo();

        if (!empty($table->{$this->tableKeyName}))
        {
            // update needed
            $res = $db->updateObject($this->tableName, $table, $this->tableKeyName);
        }
        else
        {
            // insert needed
            $res = $db->insertObject($this->tableName, $table, $this->tableKeyName);
        }

        if (!$res)
        {
            // something went wrong while saving the record
            return false;
        }

        // check again whether the PK has been filled
        if (empty($table->{$this->tableKeyName}))
        {
            // no PK, just return true
            return true;
        }

        return $table->{$this->tableKeyName};
    }

    /**
     * Runs after saving the given data.
     * It is possible to perform here some extra actions.
     * 
     * @param   array    $data  The saved data.
     * @param   bool     True in case of insert, false in case of update.
     * 
     * @return  void
     */
    protected function postflight(array $data, $isNew)
    {
        $app = JFactory::getApplication();

        /**
         * Runs after saving the given data.
         * It is possible to perform here some extra actions.
         * 
         * @param   array    $data   The saved data.
         * @param   bool     $isNew  True in case of insert, false in case of update.
         * @param   self     $model  The current model.
         * 
         * @return  void
         * 
         * @since   1.5.10
         */
        $app->triggerEvent('onAfterSaveVikBooking' . ucfirst($this->getName()), [$data, $isNew, $this]);
    }

    /**
     * Removes the specified item from the database.
     * 
     * @param   object   $item  The record to delete.
     * 
     * @return  bool     True if deleted, false otherwise.
     */
    protected function remove($item)
    {
        $db = JFactory::getDbo();

        $query = $db->getQuery(true)
            ->delete($db->qn($this->tableName))
            ->where($db->qn($this->tableKeyName) . ' = ' . $db->q($item->{$this->tableKeyName}));

        $db->setQuery($query);
        $db->execute();

        return (bool) $db->getAffectedRows();
    }

    /**
     * Returns all the columns supported by the database table of this model.
     * 
     * @return  array
     */
    public function getTableColumns()
    {
        if (!isset(static::$fields[$this->tableName]))
        {
            try
            {
                static::$fields[$this->tableName] = JFactory::getDbo()->getTableColumns($this->tableName);
            }
            catch (Exception $e)
            {
                static::$fields[$this->tableName] = [];
            }
        }
        
        return static::$fields[$this->tableName];
    }
}
