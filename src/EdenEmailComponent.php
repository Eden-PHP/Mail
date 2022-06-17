<?php

namespace Eden\Mail;

abstract class EdenEmailComponent
{

    public function __get($name)
    {
        $methodCandidate = 'get'.ucfirst($name);
        if (method_exists($this, $methodCandidate)) {
            return $this->$methodCandidate();
        }
        return null;
    }
}