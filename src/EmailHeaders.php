<?php

namespace Eden\Mail;

class EmailHeaders extends EdenEmailComponent
{
    protected $headers;

    function __construct(array $rawHeaders)
    {
        $this->headers = $rawHeaders;
    }

    public function getDate()
    {
        return isset($this->headers['date'])
            ? date('Y-m-d H:i:s', $this->headers['date'])
            : null;
    }

    public function getId()
    {
        return $this->headers['id'];
    }

    public function getFromaddress()
    {
        return $this->headers['from']['email'] ?? null;
    }

    public function getToaddress()
    {
        return $this->headers['to'][0]['email'] ?? null;
    }
}
