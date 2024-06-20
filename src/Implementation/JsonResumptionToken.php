<?php

namespace Kennisnet\OaiPmh\Implementation;

use DateTime;
use DateTimeInterface;
use Kennisnet\OaiPmh\Exception\BadResumptionTokenException;
use Kennisnet\OaiPmh\Interfaces\ResumptionToken;

class JsonResumptionToken implements ResumptionToken
{

    public function __construct(
        private readonly int $offset = 0,
        private readonly int $limit = 100,
        private readonly null|DateTimeInterface $from = null,
        private readonly null|DateTimeInterface $until = null,
        private readonly ?string $metadataPrefix = null,
        private readonly ?string $set = null
    )
    {
    }

    public static function createFromString(string $jsonResumptionToken): self
    {
        $tokenData = self::decode($jsonResumptionToken);

        return new self(
            (int)$tokenData['offset'] ?? 0,
            (int)$tokenData['limit'] ?? 100,
            isset($tokenData['from']) ? new DateTime('@' . $tokenData['from']) : null,
            isset($tokenData['until']) ? new DateTime('@' . $tokenData['until']) : null,
            $tokenData['metadataPrefix'] ?? null,
            $tokenData['set'] ?? null
        );
    }

    public function from(): ?DateTimeInterface
    {
        return $this->from;
    }

    public function until(): ?DateTimeInterface
    {
        return $this->until;
    }

    /**
     * @return string
     */
    public function metadataPrefix(): string
    {
        return $this->metadataPrefix ?? '';
    }

    /**
     * @return array<string,string>
     */
    protected static function decode(string $token): array
    {
        $decoded = base64_decode($token);
        if (!$decoded) {
            throw new BadResumptionTokenException('Failed to decode token, resumption token is not a valid base64 string');
        }

        $tokenData = json_decode($decoded);
        if (!$tokenData) {
            throw new BadResumptionTokenException('Failed to decode json token');
        }

        return (array)$tokenData;
    }

    public function encode(): string
    {
        $tokenData = [
            'from' => $this->from?->getTimestamp(),
            'limit' => $this->limit(),
            'until' => $this->until?->getTimestamp(),
            'set' => $this->set,
            'offset' => $this->offset,
            'metadataPrefix' => $this->metadataPrefix,
        ];

        return base64_encode(json_encode($tokenData) ?: '') ?: '';
    }

    public function set(): ?string
    {
        return $this->set;
    }

    public function offset(): int
    {
        return $this->offset;
    }

    public function limit(): int
    {
        return $this->limit;
    }

    public function __toString(): string
    {
        return $this->encode();
    }
}
