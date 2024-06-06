<?php

/*
 * This file is part of Picturae\Oai-Pmh.
 *
 * Picturae\Oai-Pmh is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Picturae\Oai-Pmh is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Picturae\Oai-Pmh.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace Kennisnet\OaiPmh\Implementation;

use Kennisnet\OaiPmh\Interfaces\Record as RecordInterface;
use Kennisnet\OaiPmh\Interfaces\Record\Header;
use Kennisnet\OaiPmh\XmlSaveString;

/**
 * Class Record
 * Basic implementation of Picturae\OaiPmh\Interfaces\Record
 *
 * @package Picturae\OaiPmh
 */
class Record implements RecordInterface
{
    private Header         $header;

    private ?XmlSaveString $about;

    private XmlSaveString  $metadata;

    public function __construct(Header $header, XmlSaveString $metadata, XmlSaveString $about = null)
    {
        $this->about    = $about;
        $this->header   = $header;
        $this->metadata = $metadata;
    }


    /**
     * contains the unique identifier of the item and properties necessary for selective harvesting.
     */
    public function getHeader(): Header
    {
        return $this->header;
    }

    /**
     * an optional and repeatable container to hold data about the metadata part of the record. The contents of an about
     * container must conform to an XML Schema. Individual implementation communities may create XML Schema that define
     * specific uses for the contents of about containers.
     */
    public function getAbout(): ?XmlSaveString
    {
        return $this->about;
    }

    /**
     * a single manifestation of the metadata from an item
     */
    public function getMetadata(): XmlSaveString
    {
        return $this->metadata;
    }
}
