<?php
require_once("./_constants.php");

class Wad_Handler {
    
    public $txid = null;
    
    public $map_lump_names = ['TEXTMAP', 'THINGS', 'LINEDEFS', 'SIDEDEFS', 'VERTEXES', 'SEGS', 'SSECTORS', 'NODES', 'SECTORS', 'REJECT', 'BLOCKMAP', 'BEHAVIOR', 'SCRIPTS', 'DIALOGUE', 'ZNODES', 'ENDMAP'];

    public $wad_file = null;
    public $identification = null;
    public $numlumps = 0;
    public $infotable_offset = 0;
    public $lumps = [];

    public function __construct($file_name = null) {
        if ($file_name) {
            $this->load_wad($file_name);
        }
    }

    public function load_wad($wad_file) {
        $this->wad_file = fopen($wad_file, "r");
        $this->identification = $this->read_bytes(4, 'str');
        $this->numlumps = $this->read_bytes(4, 'int');
        $this->infotable_offset = $this->read_bytes(4, 'int');
        
        //OK, we have the basics - let's take a look at the infotable
        fseek($this->wad_file, $this->infotable_offset);
        for ($i = 0; $i < $this->numlumps*16; $i += 16) {
            $lump_start_pos = $this->read_bytes(4, 'int');
            $lump_size = $this->read_bytes(4, 'int');
            $lump_name = $this->read_bytes(8, 'str');
            $this->lumps[] = ['name' => $lump_name, 'size' => $lump_size, 'position' => $lump_start_pos];
        }
        
        //We've got the lumps! Let's try to identify them, then put their bytes in our array
        for ($i = 0; $i < $this->numlumps; $i++) {
            $type = $this->identify_lump($this->lumps[$i]);
            $this->lumps[$i]['type'] = $type;
            $this->lumps[$i]['data'] = $this->read_lump($this->lumps[$i]);
        }
        fclose($this->wad_file);
    }
    
    public function read_lump($lump) {
        fseek($this->wad_file, $lump['position']);
        return $this->read_bytes($lump['size']);
    }
    
    public function identify_lump($lump) {
        //Check for map data by name
        if (in_array($lump['name'], $this->map_lump_names)) {
            return 'mapdata';
        }
        //DECORATE or ZSCRIPT?
        if (in_array($lump['name'], ['DECORATE', 'ZSCRIPT'])) {
            return strtolower($lump['name']);
        }            
        //Could be a MAPINFO
        if (in_array($lump['name'], ['MAPINFO', 'ZMAPINFO'])) {
            return 'mapinfo';
        }
        //If the length is 0 it's just a marker
        if ($lump['size'] == 0) {
            $type = 'marker';
            if (substr($lump['name'], 0, 3) == 'MAP') { //There must be a better way to do this
                $type = 'mapmarker';
            }
            return $type;
        }
        //If the first four bytes match the music signatures, identify those
        if ($lump['size'] >= 4) {
            fseek($this->wad_file, $lump['position']);
            $string = $this->read_bytes(4, 'str');
            if ($string == 'MThd') {
                return 'midi';
            }
            if ($string == 'OggS') {
                return 'ogg';
            }
        }
        if ($lump['size'] >= 3) {
            fseek($this->wad_file, $lump['position']);
            $string = $this->read_bytes(3, 'str');
            if ($string == 'ID3' || $string == "\xFF\xFB\x90") {
                return 'mp3';
            }
        }
        return 'unknown';
    }

    public function read_bytes($num, $type = null) {
        if (!$num) {
            return "";
        }
        $bytes = fread($this->wad_file, $num);
        switch ($type) {
            case 'int':
                $int = unpack("L", $bytes);
                return $int[1];
            case 'str':
                $str = "";
                $chars = unpack("C" . strlen($bytes), $bytes);
                $i = 1;
                while ($i <= count($chars) && $chars[$i] != 0) {
                    $str .= chr($chars[$i]);
                    $i++;
                }
                return $str;
            default: return $bytes;
        }
    }
    
    public function wad_info() {
        $info = ($this->identification . ' with ' . $this->numlumps . ' lumps, and directory at ' . $this->infotable_offset . PHP_EOL);
        foreach ($this->lumps as $lump) {
            $info .= sprintf("%' 9s", $lump['name']) . sprintf("%' 10s", $lump['type']) . sprintf("%' 12d", $lump['position']) . sprintf("%' 12d", $lump['size']);
            $info .= PHP_EOL;
        }
        return $info;
    }
    
    public function add_lump($lump) {
        $this->lumps[] = $lump;
        $this->numlumps++;
    }
    
    public function write_wad($location) {
        $this->wad_file = fopen($location, "w");
        $bytes_written = 0;
        
        //Calculate the number of bytes our lumps are going to need
        $total_lump_size = 0;
        $lumps_bytes = "";
        $directory_bytes = "";
        foreach($this->lumps as $lump) {
            $lumps_bytes .= $lump['data'];
            $lump['size'] = strlen($lump['data']); //Recalculate size of lump
            $new_directory_bytes = pack("L", (12 + $total_lump_size)) . pack("L", $lump['size']) . str_pad($lump['name'], 8, "\x00");
            $directory_bytes .= $new_directory_bytes;
            $total_lump_size += $lump['size'];
        }
        
        //Create our header!
        $bytes_written += fwrite($this->wad_file, "PWAD");
        $bytes_written += fwrite($this->wad_file, pack("L", $this->numlumps));
        $bytes_written += fwrite($this->wad_file, pack("L", 12 + $total_lump_size));
        
        //Now the lumps
        $bytes_written += fwrite($this->wad_file, $lumps_bytes);
        
        //And the directory
        $bytes_written += fwrite($this->wad_file, $directory_bytes);
        
        fclose($this->wad_file);
        return $bytes_written;
    }
    
    public function get_lump($lumpname) {
        foreach ($this->lumps as $lump) {
            if ($lump['name'] == $lumpname) {
                return $lump;
            }
        }
        return null;
    }
}
