<?php

namespace Eden\Mail;

class Email
{
    /**
     * Raw email structire getted by getEmailFormat()
     * @var array
     */
    protected $structure;

    public function __get($name)
    {
        $methodCandidate = 'get'.ucfirst($name);
        if(method_exists($this, $methodCandidate)){
            return $this->$methodCandidate();
        }
        return null;
    }

    public function setStructure(array $structure)
    {
        $this->structure = $structure;
    }

    public function getAttachments()
    {

    }

    public function getTextHtml()
    {

    }

    public function getTextApplication()
    {

    }

    public function setTrimInfoPartsData(bool $val)
    {
        return null;
    }
}