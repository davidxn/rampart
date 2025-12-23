<?php

/**
 * Value object representing a map record (previously held in $map_info arrays).
 */
class RampMap implements JsonSerializable
{
    public string $author;
    public string $category;
    public int $difficulty;
    public bool $disabled;
    public bool $jumpCrouch;
    public bool $length;
    public bool $locked;
    public string $lump;
    public string $name;
    public string $mapnum;
    public int $monsterCount;
    public string $musicCredit;
    public string $pin;
    public string $rampId;
    public bool $wip;

    public function __construct(string $pin, array $data)
    {
        $this->author = $data['author'] ?? '';
        $this->category = $data['category'] ?? '';
        $this->difficulty = $data['difficulty'] ?? 0;
        $this->disabled = isset($data['disabled']) && (bool)$data['disabled'];
        $this->jumpCrouch = isset($data['jumpcrouch']) && (bool)$data['jumpcrouch'];
        $this->length = isset($data['length']) && (bool)$data['length'];
        $this->locked = isset($data['locked']) && (bool)$data['locked'];
        $this->lump = $data['lumpname'] ?? '';
        $this->name = $data['map_name'] ?? '';
        $this->mapnum = $data['map_number'] ?? '';
        $this->monsterCount = $data['monsters'] ?? 0;
        $this->musicCredit = $data['music_credit'] ?? 0;
        $this->pin = $pin;
        $this->rampId = $data['ramp_id'] ?? $data['map_number'] ?? 0;
        $this->wip = isset($data['wip']) && (bool)$data['wip'];
    }

    public function jsonSerialize(): mixed
    {
        return [
            'author' => $this->author,
            'category' => $this->category,
            'difficulty' => $this->difficulty,
            'disabled' => $this->disabled,
            'jumpCrouch' => $this->jumpCrouch,
            'length' => $this->length,
            'locked' => $this->locked,
            'lump' => $this->lump,
            'map_name' => $this->name,
            'map_number' => $this->mapnum,
            'monsters' => $this->monsterCount,
            'music_credit' => $this->musicCredit,
            'pin' => $this->pin,
            'ramp_id' => $this->rampId,
            'wip' => $this->wip,
        ];
    }
}