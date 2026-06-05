<?php
class RampShotProcessor extends LumpProcessor {

    public function accept() : void {
        $lump_folder = WORK_FOLDER . DIRECTORY_SEPARATOR . 'screenshots' . DIRECTORY_SEPARATOR;
        @mkdir($lump_folder, 0755, true);
        $output_file = $lump_folder . 'RAMPSHOTRAW' . $this->rampMap->rampId;
        file_put_contents($output_file, $this->lump->data);
        Logger::pg("📷 Exported RAMPSHOT picture as " . $output_file, $this->rampMap->rampId);
    }
}
