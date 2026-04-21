<?php

/**
 * Value object representing a reserved lump and its owner
 */
class RejectedLump extends ReservedLump
{
    public const LUMP_OWNER_IWAD = 0;

    public int $attemptRampId;

    public function __construct(string $lumpName, int $ownerRampId, int $attemptRampId, string $definition) {
        parent::__construct($lumpName, $ownerRampId, $definition);
        $this->attemptRampId = $attemptRampId;
    }

    public function jsonSerialize(): array
    {
        return [
            "lumpName" => $this->lumpName,
            "ownerRampId" => $this->ownerRampId,
            "definition" => $this->definition,
            "attemptRampId" => $this->attemptRampId
        ];
    }
}