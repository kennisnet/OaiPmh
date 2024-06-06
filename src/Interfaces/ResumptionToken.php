<?php

namespace Kennisnet\OaiPmh\Interfaces;

use DateTimeInterface;

interface ResumptionToken
{
    public function from(): ?DateTimeInterface;

    public function until(): ?DateTimeInterface;
}
