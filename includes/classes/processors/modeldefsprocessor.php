<?php
class ModelDefsProcessor extends LumpProcessor {

    public function process(): bool
    {
        Logger::pg("💾 Including " . $this->lump->name . " lump index " . $this->index, $this->rampMap->rampId);

        $modeldef_lines = explode(PHP_EOL, $this->lump->data);
        $modeldef_data = "";

        foreach ($modeldef_lines as $line) {

            if(preg_match('/MODEL\s+0/i', $line)){
                //For each model definition in a legal model definition there will always be one file starting "Model 0"
                //We need to add one Path definition line to each model definition, matching the path to models for the map being imported
                //So it makes sense to add it immediately prior to the "Model 0" line
                $modeldef_data .= 'Path "models' . DIRECTORY_SEPARATOR . $this->rampMap->lump . DIRECTORY_SEPARATOR . '"' . PHP_EOL;
            }

            //Since RAMPART needs to add its own Path definition line, we ignore one the mapper has already included
            //note that allowing the mapper to include a Path line which is ignored makes it easier for the
            //mapper to run their map both locally for testing and as part of the built project
            if (!preg_match('/^\s*PATH/i', $line)) {
                $modeldef_data .= $line . PHP_EOL;
            }
        }
        $this->accept();
        return true;
    }
}
