<?php

/**
 * Value object representing a map record (previously held in $map_info arrays).
 */
class RampMap implements JsonSerializable
{
    public string $author = '';
    public string $category = '';
    public int $difficulty = 0;
    public bool $disabled = false;
    public bool $jumpCrouch = false;
    public int $length = 0;
    public bool $locked = false;
    public string $lump = '';
    public string $name = '';
    public int $mapnum = 0;
    public int $monsterCount = 0;
    public string $musicCredit = '';
    public string $pin = '';
    public int $rampId = 0;
    public bool $wip = true;
    public string $mapInfoString = '';

    public function __construct(int $rampId, array $data)
    {
        $this->rampId = $rampId;
        foreach ($data as $property => $value) {
            if (property_exists($this, $property)) {
                $this->$property = $value;
            }
        }
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
            'name' => $this->name,
            'mapnum' => $this->mapnum,
            'monsterCount' => $this->monsterCount,
            'musicCredit' => $this->musicCredit,
            'pin' => $this->pin,
            'rampId' => $this->rampId,
            'wip' => $this->wip,
            'mapInfoString' => $this->mapInfoString,
        ];
    }
}