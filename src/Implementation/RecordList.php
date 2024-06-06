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
use Kennisnet\OaiPmh\Interfaces\RecordList as RecordListInterface;

/**
 * Class RecordList
 * Basic implementation of Picturae\OaiPmh\Interfaces\RecordList
 *
 * @package Picturae\OaiPmh
 */
class RecordList implements RecordListInterface
{
    /**
     * @var string|null
     */
    private $resumptionToken;

    /**
     * @var RecordInterface[]
     */
    private $items;

    /**
     * @var int|null
     */
    private $completeListSize;

    /**
     * @var int
     */
    private $cursor;

    /**
     * @param RecordInterface[] $items
     */
    public function __construct(
        array   $items,
        ?string $resumptionToken = null,
        ?int    $completeListSize = null,
        int     $cursor = 0
    ) {
        $this->items            = $items;
        $this->resumptionToken  = $resumptionToken;
        $this->completeListSize = $completeListSize;
        $this->cursor           = $cursor;
    }

    public function getResumptionToken(): ?string
    {
        return $this->resumptionToken;
    }

    /**
     * @return RecordInterface[]
     */
    public function getItems(): array
    {
        return $this->items;
    }

    public function getCompleteListSize(): ?int
    {
        return $this->completeListSize;
    }

    public function getCursor(): int
    {
        return $this->cursor;
    }
}
