<?php

namespace Kennisnet\OaiPmh;

class XmlSaveString implements \Stringable
{
    protected string $value;

    public function __construct(string $record)
    {
        $this->value = str_replace(
            ["&", "<", ">",],
            ["&amp;", "&lt;", "&gt;",],
            $record
        );
    }

    public function __toString(): string
    {
        return $this->value;
    }

}
