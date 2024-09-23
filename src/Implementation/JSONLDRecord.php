<?php

/*
 * This file is part of Kennisnet\OaiPmh.
 *
 * Kennisnet\OaiPmh is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Kennisnet\OaiPmh is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Kennisnet\OaiPmh.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace Kennisnet\OaiPmh\Implementation;

use Kennisnet\OaiPmh\Interfaces\Record as RecordInterface;
use Kennisnet\OaiPmh\Interfaces\Record\Header;
use Kennisnet\OaiPmh\XmlSaveString;

class JSONLDRecord implements RecordInterface
{

    public function __construct(private readonly Header $header, private readonly XmlSaveString $metadata, private null|XmlSaveString $about = null)
    {
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
