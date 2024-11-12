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
 * Cron file logger.
 * 
 * @since 1.7
 */
class VBOCrontabLoggerFile implements VBOCrontabLogger
{
    /**
     * The destination folder of the log file.
     * In case the destination folder is not passed, the system temporary folder will be used.
     * As last resort the current folder will be used.
     * 
     * @var string
     */
    protected $folder;

    /**
     * The name of the log file "crons.log.php" by default.
     * 
     * @var string
     */
    protected $filename;

    /**
     * The maximum size (in bytes) that the log file can assume.
     * After exceeding the specified threshold, a temporary backup file is created
     * and the target file is flushed.
     * 
     * The maximum default size is equal to 1MB.
     * 
     * @var int
     */
    protected $maxSize;

    /**
     * The file pointer for bytes streaming.
     * 
     * @var mixed
     */
    private $stream;

    /**
     * Class constructor.
     * 
     * @param  array  $options  An array of settings.
     */
    public function __construct(array $options = [])
    {
        $this->folder = $options['folder'] ?? null;
        $this->filename = $options['filename'] ?? 'crons.log.php';
        $this->maxSize = abs($options['maxsize'] ?? (1024 * 1024));

        if (!$this->folder) {
            // in case the folder is empty, use the default one
            $this->folder = JFactory::getApplication()->get('tmp_path', dirname(__FILE__));
        }
    }

    /**
     * Class destructor.
     */
    public function __destruct()
    {
        $this->closeStream();
    }

    /**
     * @inheritDoc
     */
    public function log(string $message, string $status = 'info')
    {
        try {
            // open the stream
            $stream = $this->openStream();

            // add heading before the message
            $log = JFactory::getDate()->format('Y-m-d H:i:s') . ' ' . str_pad(strtoupper($status), 9) . ' ' . trim($message);

            // append log to file
            fwrite($stream, $log . "\n");
        } catch (Exception $error) {
            // unable to log, go ahead silently
        }
    }

    /**
     * Returns the full path of the target file.
     * 
     * @return  string
     * 
     * @throws  Exception
     */
    public function getPath()
    {
        // in case the folder does not exist, create it
        if (!JFolder::exists($this->folder)) {
            $created = JFolder::create($this->folder);

            if (!$created) {
                throw new RuntimeException('Unable to create the folder: ' . $this->folder, 500);
            }
        }

        // construct the full path
        return JPath::clean($this->folder . '/' . $this->filename);
    }

    /**
     * Opens the file stream.
     * 
     * @return  mixed
     */
    protected function openStream()
    {
        if ($this->stream) {
            // do not open again
            return $this->stream;
        }

        // construct the full path
        $path = $this->getPath();

        // check whether the file already exists
        $exists = JFile::exists($path);

        // in case the file already exists, make sure its size doesn't exceed the maximum threshold
        if ($exists && $this->maxSize && $this->maxSize < filesize($path)) {
            // extract folder and name from file path
            $folder = dirname($path);
            $file = basename($path);

            // rename file to support a temporary backup
            rename($path, JPath::clean($folder . '/backup_' . $file));

            // create from scratch
            $exists = false;
        }

        if (!$exists) {
            // create a heading for the log file
            $heading  = 'Date' . str_repeat(' ', 16) . 'Level' . str_repeat(' ', 5) . "Message";
            $heading .= "\n" . str_repeat('-', strlen($heading)) . "\n";

            if (preg_match("/\.php$/", $path)) {
                // safely hide the contents of the file
                $heading = '<?php exit; ?>' . "\n\n" . $heading;
            }

            // add heading to log file
            JFile::write($path, $heading);
        }

        // open the stream in append mode
        $this->stream = fopen($path, 'a');

        return $this->stream;
    }

    /**
     * Closes the file stream.
     * 
     * @return  void
     */
    protected function closeStream()
    {
        if ($this->stream) {
            fclose($this->stream);
        }
    }
}
