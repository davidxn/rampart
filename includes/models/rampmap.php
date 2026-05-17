<?php

/**
 * Value object representing a map record (previously held in $map_info arrays).
 */
class RampMap implements JsonSerializable
{
    const FLAG_JUMP = "RJUMP";
    const FLAG_PEACE = "RPEACE";
    const FLAG_PUZZLE = "RPUZZLE";
    const FLAG_SCARE = "RSCARE";
    const FLAG_SLAUGHTER = "RSLAUGHT";
    const FLAG_WATER = "RWATER";
    const FLAG_GAME = "RGAME";
    const FLAG_NEW_MONSTERS = "RNEWMON";
    const FLAG_NEW_WEAPONS = "RNEWWEP";
    const FLAG_MOUSELOOK = "RMOUSE";
    const FLAG_SPIDER = "RSPIDER";
    const FLAG_WIP = "RWIP";


    public string $author = '';
    public string $category = '';
    public int $difficulty = 0;
    public bool $disabled = false;
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
    public array $flags = [];

    public function __construct(int $rampId, array $data)
    {
        $this->rampId = $rampId;
        foreach ($data as $property => $value) {
            if (property_exists($this, $property)) {
                if (gettype($this->$property) == 'integer' && !is_numeric($value)) { $value = 0; }
                if (gettype($this->$property) == 'array' && !is_array($value)) { $value = []; }
                $this->$property = $value;
            }
        }
    }

    public function getMapLink(): string
    {
        return "<a href=\"/maplog.php?id=" . $this->rampId . "\">" . strtoupper($this->lump) . ": " . $this->name . "</a>";
    }

    public function hasFlag(String $flag): bool
    {
        return in_array($flag, $this->flags);
    }

    public function jsonSerialize(): array
    {
        return [
            'author' => $this->author,
            'category' => $this->category,
            'difficulty' => $this->difficulty,
            'disabled' => $this->disabled,
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
            'flags' => $this->flags
        ];
    }
}