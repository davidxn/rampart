<?php
/**
 * A Processor is an object that takes a RampMap and its WAD, extracts information from it and adds it to the project.
 */
abstract class Processor
{

    protected Wad_Handler $wad;
    protected RampMap $rampMap;
    protected LumpRegistry $lumpRegistry;

    public function __construct(Wad_Handler $wad, LumpRegistry $lumpRegistry, RampMap $rampMap)
    {
        $this->wad = $wad;
        $this->lumpRegistry = $lumpRegistry;
        $this->rampMap = $rampMap;
    }

    public function process() : bool {
        return false;
    }
}
