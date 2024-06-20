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
use Kennisnet\OaiPmh\Interfaces\RecordList as RecordListInterface;

class RecordList implements RecordListInterface
{
    /**
     * @param RecordInterface[] $items
     */
    public function __construct(
        private readonly  array   $items,
        private readonly ?string $resumptionToken = null,
        private readonly ?int    $completeListSize = null,
        private readonly  ?int     $cursor = null
    ) {
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
