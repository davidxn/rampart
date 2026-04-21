<?php

/**
 * Value object representing a reserved ID number (spawn or doomed) that has been rejected
 */
class RejectedIdentifier extends ReservedIdentifier
{
    public int $attemptRampId;

    public function __construct(int $number, int $ownerRampId, int $attemptRampId, string $className) {
        parent::__construct($number, $ownerRampId, $className);
        $this->attemptRampId = $attemptRampId;
    }

    public function jsonSerialize(): array
    {
        return [
            "number" => $this->number,
            "ownerRampId" => $this->ownerRampId,
            "className" => $this->className,
            "attemptRampId" => $this->attemptRampId
        ];
    }
}