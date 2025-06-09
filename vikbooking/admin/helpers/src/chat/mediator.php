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
 * Chat mediator class.
 * 
 * @since 1.8
 */
class VBOChatMediator
{
    /**
     * The storage engine used for input/output purposes.
     * 
     * @var VBOChatStorage
     */
    protected $storage;

    /**
     * The currently authenticated user.
     * 
     * @var VBOChatUser
     */
    private $user;

    /**
     * The path where the attachments are internally stored.
     * 
     * @var string
     */
    protected $attachmentsPath;

    /**
     * A string holding all the supported file extensions, separated by a comma.
     * 
     * @var string
     */
    protected $supportedFiles;

    /**
     * Class constructor.
     * 
     * @param  VBOChatStorage  $storage
     */
    public function __construct(VBOChatStorage $storage)
    {
        $this->storage = $storage;

        // create default attachments folder
        $this->attachmentsPath = VBO_MEDIA_PATH . DIRECTORY_SEPARATOR . 'attachments';

        // create default attachments extension filters
        $this->supportedFiles = implode(',', [
            // images
            'png,apng,bmp,gif,ico,jpg,jpeg,svg,heic,webp',
            // videos
            'mp4,mov,ogm,webm,3gp,asf,avi,divx,flv,mkv,mpg,mpeg,wmv,xvid',
            // audios
            'aac,m4a,mp3,opus,wav,wave,ac3,aiff,flac,mid,midi,wma',
            // archives
            'zip,tar,rar,gz,bzip2',
            // documents
            'pdf,doc,docx,rtf,odt,pages,txt,md,markdown',
            // spreedsheets
            'xls,xlsx,csv,ods,numbers',
            // presentations
            'pps,ppsx,odp,keynote',
        ]);
    }

    /**
     * Authenticates as the provided user.
     * When no user is passed, the system will attempt to auto-login according
     * to the client and session data.
     * 
     * @param   VBOChatUser|null  $user
     * 
     * @return  self
     */
    public function authenticate(?VBOChatUser $user = null)
    {
        if ($user === null) {
            // auto-bind the sender only if not provided
            if (JFactory::getApplication()->isClient('administrator')) {
                // authenticate as administrator
                $user = new VBOChatUserAdmin;
            } else {
                // authenticate as operator
                $user = new VBOChatUserOperator;
            }
        }

        $this->user = $user;

        return $this;
    }

    /**
     * Returns the currently logged in user.
     * In case of missing authentication, the system will attempt to 
     * perform an auto-login.
     * 
     * @return  VBOChatUser
     */
    public function getUser()
    {
        if (!$this->user) {
            // no authenticated user, auto-login now
            $this->authenticate();
        }

        return $this->user;
    }

    /**
     * Returns the messages matching the specified search query.
     * 
     * @param   VBOChatSearch  $search
     * 
     * @return  VBOChatMessage[]
     */
    public function getMessages(VBOChatSearch $search)
    {
        $messages = [];

        if (!$search->hasReader()) {
            // forces the current user as the reader
            $search->reader($this->getUser()->getID());
        }

        // pull the messages from the storage
        $rows = $this->storage->getMessages($search);

        foreach ($rows as $raw) {
            // wrap raw record within a message object
            $messages[] = $this->createMessage($raw);
        }

        return $messages;
    }

    /**
     * Sends a new message to all the recipients of the context.
     * 
     * @param   VBOChatMessage  $message
     * 
     * @return  void
     */
    public function send(VBOChatMessage $message)
    {
        $user = $this->getUser();

        // force the sender name and ID according to the details of the logged in user
        $message->setSender($user->getName(), $user->getID());

        // attempt to save the message
        $this->storage->saveMessage($message);

        // iterate all the users that should receive a notification
        foreach ($message->getContext()->getRecipients() as $recipient) {
            if ($message->getSenderID() == $recipient->getID()) {
                // do not notify myself
                continue;
            }

            if ($recipient instanceof VBOChatNotifiable) {
                // schedule message notification
                $recipient->scheduleNotification($message, $user);
            }
        }
    }

    /**
     * Moves the uploaded temporary file onto the server and creates a new attachment.
     * 
     * @param   array  $file  The temporary file under $_FILES.
     * 
     * @return  VBOChatAttachment
     * 
     * @throws  Exception
     */
    public function uploadAttachment(array $file)
    {
        // assert attachments folder first
        if (!JFolder::exists($this->attachmentsPath) && !JFolder::create($this->attachmentsPath)) {
            throw new \RuntimeException('Unable to create the attachments folder: ' . $this->attachmentsPath, 403);
        }

        // create upload attachment
        $attachment = new VBOChatAttachmentUpload($file, $this->attachmentsPath);

        // make sure the file extension is supported
        if (!VikBooking::isFileTypeCompatible($attachment->getExtension(), $this->supportedFiles)) {
            throw new \RuntimeException('File type not supported: ' . $attachment->getExtension(), 400);
        }

        if (!$attachment->upload()) {
            throw new \RuntimeException('Impossible to upload the file: ' . $attachment->getName(), 403);
        }

        return $attachment;
    }

    /**
     * Removes the specified attachment from the server.
     * 
     * @param   VBOChatAttachment  $attachment
     * 
     * @return  bool
     */
    public function removeAttachment(VBOChatAttachment $attachment)
    {
        if (!$attachment->exists()) {
            return false;
        }

        return JFile::delete($attachment->getPath());
    }

    /**
     * Reads all the messages under the specified context for the currently logged in user.
     * 
     * @param   VBOChatContext  $context  The chat context.
     * @param   string|null     $date     When specified, only the messages with creation date equal or
     *                                    lower than this value will be read.
     * 
     * @return  int[]  A list of read message IDs.
     */
    public function readMessages(VBOChatContext $context, ?string $date = null) {
        $search = (new VBOChatSearch)
            // take the latest 50 unread messages
            ->start(0)->limit(50)->unread()
            // under the specified context
            ->withContext($context)
            // created before the specified threshold date
            ->date($date ?: JFactory::getDate('now')->toSql(), '<=')
            // unread by the currently logged in user
            ->reader($this->getUser()->getID());            

        $read = [];

        // iterate all unread messages
        foreach ($this->storage->getMessages($search) as $message) {
            try {
                // read the message
                $this->storage->readMessage($message->id, $this->getUser()->getID());

                // mark message as read
                $read[] = $message->id;
            } catch (Exception $error) {
                // go ahead silently
            }
        }

        return $read;
    }

    /**
     * Creates a new message object.
     * 
     * @param   object|array  $message  The raw message record.
     * 
     * @return  VBOChatMessage
     */
    public function createMessage($message)
    {
        if (!is_array($message) && !is_object($message)) {
            throw new \InvalidArgumentException('Cannot bind chat message! Array or object expected, ' . gettype($message) . ' given.', 400);
        }

        $message = (object) $message;

        // hold raw data into a message object
        return new VBOChatMessage(
            // create proper context handler
            $this->createContext($message->context ?? '', $message->id_context ?? 0),
            // bind raw information
            $message
        );
    }

    /**
     * Creates a new context object.
     * 
     * @param   string  $alias  The context alias identifier.
     * @param   int     $id     The context foreign key.
     * 
     * @return  VBOChatContext
     */
    public function createContext(string $alias, int $id)
    {
        if ($alias === '') {
            throw new InvalidArgumentException('The context alias cannot be empty.', 400);
        }

        if ($id <= 0) {
            throw new InvalidArgumentException('Invalid context foreign key provided.', 400);
        }

        // build context class name
        $classname = 'VBOChatContext' . ucfirst(strtolower($alias));

        if (!class_exists($classname)) {
            throw new RuntimeException('The class [' . $classname . '] does not exist.', 404);
        }

        // instantiate class by inject the provided ID
        return new $classname($id);
    }

    /**
     * Forces the pre-loading of the resources to make the chat scripts work.
     * 
     * @return  self
     */
    public function useAssets()
    {
        static $loaded = false;

        if ($loaded) {
            // do not load assets again
            return $this;
        }

        $loaded = true;

        // load dependencies first
        JHtml::fetch('jquery.framework');
        VikBooking::getVboApplication()->loadContextMenuAssets();

        // make translations available also for JS scripts
        JText::script('VBO_CHAT_YOU');
        JText::script('VBTODAY');
        JText::script('VBOYESTERDAY');
        JText::script('VBO_CHAT_SENDING_ERR');
        JText::script('VBO_CHAT_TEXTAREA_PLACEHOLDER');
        JText::script('VBO_ATTACH');

        $document = JFactory::getDocument();
        $document->addScript(VBO_SITE_URI . 'resources/chat.js');
        $document->addStyleSheet(VBO_SITE_URI . 'resources/chat.css');

        // load assets for each supported context
        (new VBOChatContextTask(0))->useAssets();

        return $this;
    }

    /**
     * Renders the chat interface.
     * 
     * @param   VBOChatContext  $context  The conversation context.
     * @param   array           $options  A configuration array.
     * 
     * List of supported configuration options.
     * @var bool  assets  Whether the resources should be loaded (true by default).
     * 
     * @return  string  The chat interface output.
     */
    public function render(VBOChatContext $context, array $options = [])
    {
        if ($options['assets'] ?? true) {
            $this->useAssets();
        }

        // load the latest 20 messages of the specified context
        $messages = $this->getMessages(
            (new VBOChatSearch)->limit($options['limit'] ?? 20)->withContext($context)
        );

        $users = [];

        // get all involved users
        foreach ($context->getRecipients() as $recipient) {
            $users[$recipient->getID()] = $recipient;
        }

        // detect AJAX base URI environment depending on the platform
        $ajaxUri = VBOFactory::getPlatform()->getUri()->ajax('index.php?option=com_vikbooking');

        // generate a random suffix in case it has been specified
        $options['suffix'] = $options['suffix'] ?? uniqid();

        // create layout file
        $layout = new JLayoutFile('chat.chat', null, [
            'component' => 'com_vikbooking',
            'client' => 'admin',
        ]);

        // render template
        return $layout->render([
            'uri' => $ajaxUri,
            'messages' => $messages,
            'users' => $users,
            'user' => $this->getUser(),
            'options' => $options,
            'context' => [
                'id' => $context->getID(),
                'alias' => $context->getAlias(),
                'actions' => $context->getActions(),
            ],
        ]);
    }
}
