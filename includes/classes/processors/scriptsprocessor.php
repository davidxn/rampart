<?php
class ScriptsProcessor extends LumpProcessor
{
    public function process() : bool {
        if (stripos($this->lump->data, "replaces") !== false) { //Okay, I don't have time to write a proper parser
            Logger::pg("❌ Found " . $this->lump->name . " lump but refusing it as it performs replacements!", $this->rampMap->rampId, true);
            $this->lumpRegistry->addRejectedScript($this->rampMap->rampId);
            return false;
        }

        //If this script is DECORATE, watch out for DoomEd number definitions
        //Should I just drop this? It would serve to poke people into ZScript
        if (strtoupper($this->lump->name == 'DECORATE')) {
            $matches = [];
            //Oh dear god - this gets the class name and DoomEd number out of an actor definition
            preg_match_all('/(*ANYCRLF)^\s*?actor\s+([a-zA-Z0-9_]*)\s*(?::\s*[a-zA-Z0-9_]*)?\s+([0-9]+)/im', $this->lump->data, $matches);
            if (isset($matches[1])) {
                //We have some matching DoomEd numbers - attempt to add them to the global list
                for ($i = 0; $i < count($matches[1]); $i++) {
                    $classname = $matches[1][$i];
                    $doomed_number = $matches[2][$i];
                    $result = $this->lumpRegistry->reserveDoomEdNumber($doomed_number, $this->rampMap->rampId, Project_Compiler::$decorate_id_number_prefix . $classname);
                    if (!$result) {
                        Logger::pg("❌ Found " . $this->lump->name . " lump but got a DoomEdNum conflict, not including this script", $this->rampMap->rampId, true);
                        $this->lumpRegistry->addRejectedScript($this->rampMap->rampId);
                        return false;
                    }
                }
            }

            //Same for spawn nums
            $matches = [];
            preg_match_all('/(*ANYCRLF)\s*?actor\s+([a-zA-Z0-9_]*)[^{]*?{[^}]*?spawnid[\s]*?([0-9]+)[\s\S]*?}/im', $this->lump->data, $matches);
            if (isset($matches[1])) {
                //We have some matching spawn numbers - attempt to add them to the global list
                for ($i = 0; $i < count($matches[1]); $i++) {
                    $classname = $matches[1][$i];
                    $spawn_number = $matches[2][$i];
                    $result = $this->lumpRegistry->reserveSpawnNumber($spawn_number, $this->rampMap->rampId, Project_Compiler::$decorate_id_number_prefix . $classname);
                    if (!$result) {
                        Logger::pg("❌ Found " . $this->lump->name . " lump but got a spawnnum conflict, not including this script", $this->rampMap->rampId, true);
                        $this->lumpRegistry->addRejectedScript($this->rampMap->rampId);
                        return false;
                    }
                }
            }
        }

        //If this is a ZSCRIPT file and it begins with a version declaration, we have to strip that out
        if ($this->lump->name == 'ZSCRIPT' && strtolower(substr($this->lump->data, 0, 7)) == "version") {
            Logger::pg("Taking version declaration out of ZSCRIPT lump");
            $first_newline_position = strpos($this->lump->data, PHP_EOL);
            $this->lump->data = substr($this->lump->data, $first_newline_position);
        }
        $this->accept();
        return true;
    }

    public function accept(): void
    {
        Logger::pg("📜 Found " . $this->lump->name . " script, adding it to our script folder", $this->rampMap->rampId);
        $script_folder = PK3_FOLDER . DIRECTORY_SEPARATOR . strtoupper($this->lump->name);
        @mkdir($script_folder, 0755, true);
        $script_file_name = strtoupper($this->lump->name) . "-" . $this->rampMap->lump . "-" . $this->index . ".txt";
        $script_file_path = $script_folder . DIRECTORY_SEPARATOR . $script_file_name;

        file_put_contents($script_file_path, $this->lump->data);
        Logger::pg("Wrote " . strlen($this->lump->data) . " bytes to " . $script_file_path, $this->rampMap->rampId);

        //If this is our first ZSCRIPT inclusion, we need to prepend the version declaration to the include file
        $script_include_file_path = PK3_FOLDER . strtoupper($this->lump->name) . ".custom";
        if (strtoupper($this->lump->name) == "ZSCRIPT" && !file_exists($script_include_file_path)) {
            file_put_contents($script_include_file_path, "version \"" . get_setting("ZSCRIPT_VERSION") . "\"" . PHP_EOL . PHP_EOL);
        }
        file_put_contents($script_include_file_path, "#include \"" . strtoupper($this->lump->name) . "/" . $script_file_name . "\"" . PHP_EOL, FILE_APPEND);
    }
}
