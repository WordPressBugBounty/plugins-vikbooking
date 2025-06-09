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
 * This class holds the information of a generic chat attachment.
 * 
 * @since 1.8
 */
class VBOChatAttachment implements  JsonSerializable
{
    /**
     * The relative source path of the attachment.
     * 
     * @var string
     */
    private $path;

    /**
     * The original name of the attachment.
     * 
     * @var string
     */
    private $name;

    /**
     * The real name of the attachment.
     * 
     * @var string
     */
    private $filename;

    /**
     * The file extension.
     * 
     * @var string
     */
    private $extension;

    /**
     * The file mime type.
     * 
     * @var string
     */
    private $type;

    /**
     * Class constructor.
     * 
     * @param  array|object  $data  The file information.
     * 
     * The data array/object supports the following details.
     * 
     * @var string path (required)
     * @var string name (required)
     * @var string filename (optional)
     * @var string extension (optional)
     * @var string type (optional)
     */
    public function __construct($data)
    {
        if (!is_array($data) && !is_object($data)) {
            throw new \InvalidArgumentException('Cannot bind chat attachment! Array or object expected, ' . gettype($data) . ' given.', 400);
        }

        $data = (array) $data;

        if (empty($data['path'])) {
            throw new \InvalidArgumentException('Missing path attribute.', 400);
        }

        $root = VBOFactory::getPlatform()->getUri()->getAbsolutePath();

        // preserve only the relative path of the path
        $this->path = trim(str_replace($root, '', $data['path']), '\\/');

        if (empty($data['name'])) {
            throw new \InvalidArgumentException('Missing file name.', 400);
        }

        if (!isset($data['extension'])) {
            // extract file extension from original file name
            if (preg_match("/\.([a-z0-9]{2,})$/i", $data['name'], $match)) {
                $data['extension'] = end($match);
            } else {
                // we have a file w/o extension
                $data['extension'] = '';
            }
        }

        $this->extension = $data['extension'];
        
        // keep name without file extension
        $this->name = preg_replace("/\.{$this->extension}$/", '', $data['name']);

        if (empty($data['filename'])) {
            do {
                // use a random (probable) unique ID as file name
                $data['filename'] = VBOPerformanceIndicator::uuid() . ($this->extension ? '.' . $this->extension : '');
                // repeat in case we have been so unlucky
            } while (JFile::exists($root . '/' . $this->path . '/' . $data['filename']));
        }

        $this->filename = $data['filename'];

        // register file mime type
        $this->type = $data['type'] ?? null;
    }

    /**
     * Returns the original file name.
     * 
     * @param   bool    $extension  Whether the file extension should be included.
     * 
     * @return  string
     */
    public function getName(bool $extension = true)
    {
        if ($extension) {
            return $this->name . ($this->extension ? '.' . $this->extension : '');    
        }

        return $this->name;
    }

    /**
     * Returns the real file name.
     * 
     * @return  string
     */
    public function getFilename()
    {   
        return $this->filename;
    }

    /**
     * Returns the file extension.
     * 
     * @return  string
     */
    public function getExtension()
    {
        return $this->extension;
    }

    /**
     * Returns the file MIME type.
     * 
     * @return  string
     */
    public function getMimeType()
    {
        if (!$this->type) {
            // fetch MIME type at runtime
            $this->type = $this->exists() ? mime_content_type($this->getPath()) : '';
        }

        return $this->type;
    }

    /**
     * Returns the full path of the attachment.
     * 
     * @return  string
     */
    public function getPath()
    {
        return JPath::clean(
            VBOFactory::getPlatform()->getUri()->getAbsolutePath() . '/' . $this->path . '/' . $this->getFilename()
        );
    }

    /**
     * Returns the file URI.
     * 
     * @return  string
     */
    public function getUrl()
    {
        return JUri::root() . preg_replace("/\\\\+/", '/', $this->path) . '/' . $this->getFilename();
    }

    /**
     * Returns the file size.
     * 
     * @return  int
     */
    public function getSize()
    {
        if (!$this->exists()) {
            return 0;
        }

        return filesize($this->getPath());
    }

    /**
     * Checks whether the attachment actually exists.
     * 
     * @return  bool
     */
    public function exists()
    {
        return JFile::exists($this->getPath());
    }

    /**
     * @inheritDoc
     *
     * @see JsonSerializable
     */
    #[ReturnTypeWillChange]
    public function jsonSerialize()
    {
        $size = $this->getSize();

        return [
            'name' => $this->getName(true),
            'filename' => $this->getFilename(),
            'extension' => $this->getExtension(),
            'type' => $this->getMimeType(),
            'url' => $this->getUrl(),
            'path' => $this->path,
            'size' => [
                'value' => $size,
                'text' => JHtml::fetch('number.bytes', $size, 'auto', 0),
            ],
        ];
    }
}
