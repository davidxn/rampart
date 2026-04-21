<?php

/**
 * Value object representing a reserved lump and its owner
 */
class ReservedLump implements JsonSerializable
{
    public const LUMP_OWNER_IWAD = 0;

    public string $lumpName;
    public int $ownerRampId;
    public string $definition;

    public function __construct(string $lumpName, int $ownerRampId, string $definition) {
        $this->lumpName = $lumpName;
        $this->ownerRampId = $ownerRampId;
        $this->definition = $definition;
    }

    public function jsonSerialize(): array
    {
        return [
            "lumpName" => $this->lumpName,
            "ownerRampId" => $this->ownerRampId,
            "definition" => $this->definition
        ];
    }
}