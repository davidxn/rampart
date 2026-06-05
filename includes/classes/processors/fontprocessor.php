<?php
class FontProcessor extends LumpProcessor {
    public function process() : bool {
        if (!$this->lumpRegistry->reserveLump($this->lump, $this->rampMap->rampId)) {
            return false;
        }
        $this->accept();
        return true;
    }

    public function accept() : void {
        Logger::pg("💾 Including " . $this->lump->name . " lump as font", $this->rampMap->rampId);
        @mkdir(PK3_FOLDER . DIRECTORY_SEPARATOR . 'fonts', 0755, true);
        $data_path = PK3_FOLDER . DIRECTORY_SEPARATOR . 'fonts' . DIRECTORY_SEPARATOR . $this->lump->name . '.' . $this->rampMap->rampId;
        file_put_contents($data_path, $this->lump->data);
        Logger::pg("Wrote " . strlen($this->lump->data) . " bytes to " . $data_path, $this->rampMap->rampId);
    }
}