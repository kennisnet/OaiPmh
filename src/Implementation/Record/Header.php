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

namespace Kennisnet\OaiPmh\Implementation\Record;

use DateTime;
use DateTimeInterface;
use Kennisnet\OaiPmh\Interfaces\Record\Header as HeaderInterface;

/**
 * Class Header
 * Basic implementation of \Picturae\OaiPmh\Interfaces\Record\Header
 *
 * @package Picturae\OaiPmh\Record
 */
class Header implements HeaderInterface
{
    /**
     * @var string
     */
    private $identifier;

    /**
     * @var DateTimeInterface|null
     */
    private $datestamp;

    /**
     * @var string[]
     */
    private $setSpecs;

    /**
     * @var boolean
     */
    private $deleted;

    /**
     * Spec values must validate to regex ([A-Za-z0-9\-_\.!~\*'\(\)])+(:[A-Za-z0-9\-_\.!~\*'\(\)]+)*
     * check values with the SetSpecValidator
     *
     * @param string[] $setSpecs
     */
    public function __construct(string $identifier, ?DateTimeInterface $datestamp, array $setSpecs = [], bool $deleted = false)
    {
        $this->identifier = $identifier;
        $this->datestamp  = $datestamp;
        $this->setSpecs   = $setSpecs;
        $this->deleted    = $deleted;
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    /**
     * the date of creation, modification or deletion of the record for the purpose of selective harvesting.
     */
    public function getDatestamp(): DateTimeInterface
    {
        return $this->datestamp ?? new DateTime();
    }

    /**
     * the set memberships of the item for the purpose of selective harvesting.
     */
    public function getSetSpecs(): array
    {
        return $this->setSpecs;
    }

    /**
     * indicator if the record is deleted, will be converted to status
     */
    public function isDeleted(): bool
    {
        return $this->deleted;
    }
}
