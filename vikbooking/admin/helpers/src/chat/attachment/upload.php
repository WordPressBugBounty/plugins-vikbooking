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
 * This class holds the information of a chat attachment as uploaded file.
 * 
 * @since 1.8
 */
class VBOChatAttachmentUpload extends VBOChatAttachment
{
    /**
     * The temporary uploaded file name ($_FILES).
     * 
     * @var string
     */
    private $tmpFile;

    /**
     * Class constructor.
     * 
     * @param  array   $file  The uploaded file details.
     * @param  string  $path  The path where the attachment should be uploaded.
     */
    public function __construct(array $file, string $path)
    {
        parent::__construct([
            'path' => $path,
            'name' => $file['name'],
            'type' => $file['type'],
        ]);

        $this->tmpFile = $file['tmp_name'];
    }

    /**
     * Uploads the file, only if doesn't exist yet.
     * 
     * @return  bool
     */
    public function upload()
    {
        if ($this->exists()) {
            // the file already exists, we don't need to upload it
            return true;
        }

        // attempt file upload
        return JFile::upload($this->tmpFile, $this->getPath(), $useStreams = false, $allowUnsafe = true);
    }
}
