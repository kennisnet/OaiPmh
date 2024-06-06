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


namespace Kennisnet\OaiPmh\Interfaces\Record;

use DateTimeInterface;

interface Header
{

    /**
     * the unique identifier of this record
     */
    public function getIdentifier(): string;

    /**
     * @return DateTimeInterface
     * the date of creation, modification or deletion of the record for the purpose of selective harvesting.
     */
    public function getDatestamp(): DateTimeInterface;

    /**
     * @return array<int,string>
     * the set memberships of the item for the purpose of selective harvesting.
     */
    public function getSetSpecs(): array;

    /**
     * indicator if the record is deleted, will be converted to status
     */
    public function isDeleted(): bool;
}
