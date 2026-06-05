<?php
/*
 * A LumpProcessor is a processor that deals with a single lump. process() does the preparation, accept() adds it to the project.
 */
class LumpProcessor extends Processor
{
    protected int $index;
    protected Lump $lump;

    public function __construct(Wad_Handler $wad, LumpRegistry $lumpRegistry, RampMap $rampMap, int $index, Lump $lump)
    {
        parent::__construct($wad, $lumpRegistry, $rampMap);
        $this->index = $index;
        $this->lump = $lump;
    }

    public function process() : bool {
        $this->accept();
        return true;
    }

    public function accept(): void
    {
        Logger::pg("💾 Processed " . $this->lump->name . " lump", $this->rampMap->rampId);
        @mkdir(PK3_FOLDER, 0755, true);
        $data_path = PK3_FOLDER . DIRECTORY_SEPARATOR . $this->lump->name . "." . $this->rampMap->lump . "-" . $this->index;
        file_put_contents($data_path, $this->lump->data);
        Logger::pg("Wrote " . strlen($this->lump->data) . " bytes to " . $data_path, $this->rampMap->rampId);
    }
}
