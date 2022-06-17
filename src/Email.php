<?php

namespace Eden\Mail;

use PhpImap\IncomingMailAttachment;

class Email extends EdenEmailComponent
{
    /**
     * Raw email structire getted by getEmailFormat()
     * @var array
     */
    protected $structure;
    /**
     * Raw email headers
     * @var EmailHeaders
     */
    protected $headers;
    /**
     * @var int
     */
    protected $id;

    protected $body;

    /**
     * @var IncomingMailAttachment[]
     */
    protected $attachments = [];

    /** @var bool */
    protected $hasAttachments = false;

    /** @var string|null */
    private $textPlain;

    /** @var string|null */
    private $textHtml;

    /** @var string|null */
    private $textApplication;

    public function __construct(array $structure = null)
    {
        if($structure){
            $this->parseStructure($structure);
        }
    }

    protected function parseStructure(array $structure)
    {
        $this->createHeadersFromRaw($structure);
        $this->createBodyPartsFromRaw($structure);
        $this->createAttachmentsFromRaw($structure);
    }

    protected function createBodyPartsFromRaw($structure)
    {
        $this->textPlain = $structure['body']['text/plain'] ?? null;
        $this->textHtml = $structure['body']['text/html'] ?? null;
    }

    protected function createAttachmentsFromRaw(array $structure)
    {
        $rawAttachmens = $structure['attachment'] ?? [];

        foreach ($rawAttachmens as $filename => $attachment) {
            $attachment = array_filter($attachment);
            if (empty($attachment)) {
                continue;
            }

            $mime = array_key_first($attachment);
            $incomingMailAttachment = new IncomingMailAttachment();
            $incomingMailAttachment->id = \bin2hex(\random_bytes(20));
            $incomingMailAttachment->name = $filename;
            $incomingMailAttachment->mime = $mime;
            $incomingMailAttachment->fileExtension = pathinfo($filename, PATHINFO_EXTENSION);

            $incomingMailAttachment->setFileContent($attachment[$mime]);

            $this->attachments[] = $incomingMailAttachment;
        }
    }

    protected function createHeadersFromRaw(array $structure)
    {
        $this->headers = new EmailHeaders(
            array_diff_key($structure, array_flip([
                'body',
                'attachment',
                'attachments',
            ]))
        );
    }

    public function setId(int $id) : self
    {
        $this->id = $id;
        return $this;
    }

    public function getId():int
    {
        return $this->id;
    }

    public function getHeaders()
    {
        return $this->headers;
    }

    public function setStructure(array $structure)
    {
        $this->structure = $structure;
    }

    public function getAttachments()
    {
        return $this->attachments;
    }

    public function getTextHtml() : ?string
    {
        return $this->textHtml;
    }

    public function getTextPlain() : ?string
    {
        return $this->textPlain;
    }

    public function getTextApplication() :?string
    {
        return $this->textApplication;
    }

    public function setTrimInfoPartsData(bool $val)
    {
        // mute
    }
}
