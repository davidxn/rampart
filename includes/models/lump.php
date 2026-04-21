<?php
/**
 * Value object representing a Doom engine lump
 */
class Lump
{
    public string $name;
    public int $size;
    public int $position;
    public bool $compressed;
    public string $data;
    public bool $hasLoadError = false;
    public string $type;
    public array $parsedData;

    public function __construct(string $name, int $size, int $position, bool $compressed)
    {
        $this->name = $name;
        $this->size = $size;
        $this->position = $position;
        $this->compressed = $compressed;
        $this->data = '';
    }
}
