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

use Kennisnet\OaiPmh\Interfaces\Set as SetInterface;

class SetList implements \Kennisnet\OaiPmh\Interfaces\SetList
{
    /**
     * @var string|null
     */
    private $resumptionToken;

    /**
     * @var Set[]
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
     * @param Set[] $items
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
     * @return SetInterface[]
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
