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

namespace Kennisnet\OaiPmh\Interfaces;

interface Set
{

    /**
     * a colon [:] separated list indicating the path from the root of the set hierarchy to the
     * respective node. Each element in the list is a string consisting of any valid URI unreserved characters, which
     * must not contain any colons [:]. Since a setSpec forms a unique identifier for the set within the repository, it
     * must be unique for each set. Flat set organizations have only sets with setSpec that do not contain any colons.
     */
    public function getSpec(): string;

    /**
     *a short human-readable string naming the set.
     */
    public function getName(): string;

    /**
     * an optional and repeatable container that may hold community-specific XML-encoded data about
     * the set; the accompanying Implementation Guidelines document provides suggestions regarding the usage of this
     * container.
     */
    public function getDescription(): ?string;
}
