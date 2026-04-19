<?php

/**
 * Value object representing a reserved ID number (spawn or doomed))
 */
class ReservedIdentifier implements JsonSerializable
{
    public int $number;
    public int $ownerRampId;
    public string $className;

    public function __construct(int $number, int $ownerRampId, string $className) {
        $this->number = $number;
        $this->ownerRampId = $ownerRampId;
        $this->className = $className;
    }

    public function jsonSerialize(): array
    {
        return [
            "number" => $this->number,
            "ownerRampId" => $this->ownerRampId,
            "className" => $this->className
        ];
    }
}