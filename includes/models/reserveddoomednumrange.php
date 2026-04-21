<?php

/**
 * Value object representing a reserved DoomEdNum range
 */
class ReservedDoomEdNumRange implements JsonSerializable
{
    public string $name;
    public int $start;
    public int $end;

    public function __construct(int $start, int $end, string $name)
    {
        $this->name = $name;
        $this->start = $start;
        $this->end = $end;
    }

    public function containsDoomEdNum(int $number): bool
    {
        return $this->start <= $number && $this->end >= $number;
    }

    public function toString(): string
    {
        return $this->name . " (" . $this->start . " - " . $this->end . ")";
    }

    public function jsonSerialize(): array
    {
        return [
            "name" => $this->name,
            "start" => $this->start,
            "end" => $this->end
        ];
    }
}