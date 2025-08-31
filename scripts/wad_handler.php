<?php

class Lzss
{
    /**
     * Decompresses a LZSS-compressed stream into a decompressed stream
     * 
     * @param string $input A compressed blob of data
     * @param string &$output A destination string that will be filled with decompressed data
     * @param int $expectedOutputLength The expected length of the output
     * @throws Exception
     */
    public static function decompress($input, &$output, $expectedOutputLength)
    {
        // An 8-wide bitfield that denotes whether the next eight
        // blocks are compressed or stored. The least-significant
        // bit denotes the nearest chunk.
        $nextChunks = 0;
        $chunkFieldCounter = 0;

        $inputPosition = 0;
        $outputPosition = 0;
        $inputLength = strlen($input);
        
        // Initialize output string
        $output = str_repeat("\0", $expectedOutputLength);

        while ($inputPosition < $inputLength) {
            // Get a new bitfield if necessary
            if ($chunkFieldCounter == 0) {
                $nextChunks = ord($input[$inputPosition++]);
            }

            // Roll the field counter over to read a new
            // bitfield every 8 chunks
            $chunkFieldCounter = ($chunkFieldCounter + 1) & 7;

            // If the least-significant bit is set, the next
            // chunk is compressed. Otherwise, it's stored.
            if (($nextChunks & 1) != 0) {
                $firstByte = ord($input[$inputPosition++]);
                $secondByte = ord($input[$inputPosition++]);

                // Construct a 12-bit offset into the current
                // sliding window using this byte as the top
                // 8 bits, and the top 4 bits of the next
                // byte as the bottom 4 bits. The remaining
                // 4 bits are a 1 + [0, 16)-byte length in that
                // window.
                $spanBits = ($firstByte << 8) | $secondByte;
                $spanOffset = $spanBits >> 4;
                $spanLength = ($spanBits & 0xF) + 1;

                if ($spanLength == 1) {
                    break;
                }

                // Calculate source position relative to current output position
                $sourcePosition = $outputPosition - $spanOffset - 1;

                // Copy bytes from the sliding window
                for ($i = 0; $i < $spanLength; $i++) {
                    $sourceByte = $output[$sourcePosition + $i];
                    $output[$outputPosition++] = $sourceByte;
                }
            } else {
                // Copy literal byte
                $output[$outputPosition++] = $input[$inputPosition++];
            }

            // Select the next bitfield
            $nextChunks >>= 1;
        }

        if ($inputPosition != $inputLength) {
            throw new Exception("Did not reach end of input");
        }

        if ($outputPosition != $expectedOutputLength) {
            throw new Exception("Did not reach end of output");
        }
    }
}

class Wad_Handler {
    
    public $txid = null;
    
    public $map_lump_names = ['TEXTMAP', 'THINGS', 'LINEDEFS', 'SIDEDEFS', 'VERTEXES', 'SEGS', 'SSECTORS', 'NODES', 'SECTORS', 'REJECT', 'BLOCKMAP', 'BEHAVIOR', 'SCRIPTS', 'DIALOGUE', 'ZNODES', 'ENDMAP'];

    public $wad_file = null;
    public $identification = null;
    public $numlumps = 0;
    public $infotable_offset = 0;
    public $lumps = [];

    public function __construct($file_name = null, $load_data = true, $parse_map_lumps = false) {
        if ($file_name) {
            $this->load_wad($file_name, $load_data, $parse_map_lumps);
        }
    }

    public function load_wad($wad_file, $load_data = true, $parse_map_lumps = false) {
        if (!file_exists($wad_file)) {
            return false;
        }
        $this->wad_file = fopen($wad_file, "r");
        $this->identification = $this->read_bytes(4, 'str');
        $this->numlumps = $this->read_bytes(4, 'int');
        $this->infotable_offset = $this->read_bytes(4, 'int');
        
        //OK, we have the basics - let's take a look at the infotable
        fseek($this->wad_file, $this->infotable_offset);
        for ($i = 0; $i < $this->numlumps*16; $i += 16) {
            $lump_start_pos = $this->read_bytes(4, 'int');
            $lump_size = $this->read_bytes(4, 'int');
            list($lump_compressed, $lump_name) = $this->read_lump_name();
            $this->lumps[] = ['name' => $lump_name, 'size' => $lump_size, 'position' => $lump_start_pos, 'compressed' => $lump_compressed];
        }
        
        //We've got the lumps! Let's try to identify them, then put their bytes in our array
        if ($load_data) {
            for ($i = 0; $i < $this->numlumps; $i++) {
                $nextLump = (isset($this->lumps[$i+1]) ? $this->lumps[$i+1] : null);
                
                try {
                    $this->lumps[$i]['data'] = $this->read_lump($this->lumps[$i], $nextLump);
                    $this->lumps[$i]['load_error'] = false;
                } catch (Exception $e) {
                    // Allow the lump to fail to load, but announce that error in the log
                    $this->lumps[$i]['data'] = "";
                    $this->lumps[$i]['load_error'] = true;
                    continue;
                }

                $type = $this->identify_lump($this->lumps[$i], $nextLump);
                $this->lumps[$i]['type'] = $type;

                if ($parse_map_lumps) {
                    $this->lumps[$i]['parsed'] = $this->parse_lump($this->lumps[$i]);
                }
            }
        }
        fclose($this->wad_file);
        return true;
    }
    
    public function read_lump($lump, $nextLump) {
        fseek($this->wad_file, $lump['position']);

        $size = $lump['size'];

        if ($size == 0) {
            return "";
        }

        if (!$lump['compressed']) {
            return $this->read_bytes($size);
        }

        // The length of a compressed lump is implicit based on
        // the next entry's position. As per the spec, the last
        // lump in a wad cannot be compressed, and is recommended
        // to be a marker of some kind.
        if ($nextLump) {
            $size = $nextLump['position'] - $lump['position'];
        } else {
            throw new Exception("Last compressed lump must be followed by a non-compressed lump (e.g. a marker)");
        }

        // Decompress the lump data. We won't bother re-compressing it,
        // since the resulting data will be DEFLATEd into a PK3, which
        // will be stronger overall compression.
        $compressed = $this->read_bytes($size);
        $decompressed = [];

        Lzss::decompress($compressed, $decompressed, $lump['size']);

        return $decompressed;
    }
    
    public function identify_lump($lump, $nextlump) {
        //Check for map data by name
        if (in_array($lump['name'], $this->map_lump_names)) {
            return 'mapdata';
        }
        //DECORATE or ZSCRIPT?
        if (in_array($lump['name'], ['DECORATE', 'ZSCRIPT'])) {
            return strtolower($lump['name']);
        }            
        //Could be a MAPINFO
        if (in_array($lump['name'], ['MAPINFO', 'ZMAPINFO', 'UMAPINFO'])) {
            return 'mapinfo';
        }
        if (in_array($lump['name'], ['SNDINFO', 'SNDSEQ', 'TEXTURES', 'TEXTURE1', 'TEXTURE2', 'PNAMES', 'GLDEFS', 'LOCKDEFS'])) {
            return strtolower($lump['name']);
        }
        
        //If the length is 0 it's just a marker. If the next lump is either TEXTMAP or THINGS it's a map.
        if ($lump['size'] == 0) {
            $type = 'marker';
            if ($nextlump && in_array($nextlump['name'], ['TEXTMAP', 'THINGS'])) {
                $type = 'mapmarker';
            }
            return $type;
        }

        //If the first four bytes match the music signatures, identify those
        if ($lump['size'] >= 4) {
            fseek($this->wad_file, $lump['position']);
            $string = $this->read_data_str($lump, 0, 4);
            if ($string == 'MThd') {
                return 'midi';
            }
            if ($string == 'OggS') {
                return 'ogg';
            }
            if ($string == 'IMPM' || $string == 'Exte') {
                return 'module';
            }
            if ($string == 'fLaC') {
                return 'flac';
            }
            if ($string == "MUS\x1A") {
                return 'mus';
            }
            if ($string == "\x89\x50\x4E\x47") {
                return 'image';
            }
            if ($string == "FON2") {
                return 'font';
            }
        }
        if ($lump['size'] >= 3) {
            fseek($this->wad_file, $lump['position']);
            $string = $this->read_data_str($lump, 0, 3);
            if ($string == 'ID3' || substr($string, 0, 2) == "\xFF\xFB") {
                return 'mp3';
            }
            if ($string == "\xFF\xD8\xFF") {
                return 'image';
            }
        }
        //S3M files have their signature at position 44...
        if ($lump['size'] >= 48) {
            fseek($this->wad_file, $lump['position']);
            $string = $this->read_data_str($lump, 44, 4);
            if ($string == 'SCRM') {
                return 'module';
            }
        }
        return 'unknown';
    }
    
    public function parse_lump($lump) {
        $parsed_array = [];
        $bytes = $lump['data'];
        if ($lump['name'] == 'THINGS') {
            for ($i = 0; $i < strlen($bytes); $i += 10) {
                $thing = [];
                $entry = substr($bytes, $i, 10);
                $thing['x'] = unpack("s", substr($entry, 0, 2))[1];
                $thing['y'] = unpack("s", substr($entry, 2, 2))[1];
                $thing['angle'] = unpack("s", substr($entry, 4, 2))[1];
                $thing['type'] = unpack("s", substr($entry, 6, 2))[1];
                $thing['doomflags'] = unpack("s", substr($entry, 8, 2))[1];
                $parsed_array[] = $thing;
            }
        }
        return $parsed_array;
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

    public function read_data_str($lump, $start, $num) {
        if (!$num) {
            return "";
        }
        
        return substr($lump['data'], $start, $num);
    }

    public function read_lump_name() {
        $bytes = fread($this->wad_file, 8);

        // Compressed lumps set the high bit of the
        // first character to denote LZSS compression
        $compressed = false;
        if (ord($bytes[0]) > 127) {
            $compressed = true;

            // Clear the high bit
            $bytes[0] = chr(ord($bytes[0]) - 128);
        }
        
        $str = "";
        $chars = unpack("C" . strlen($bytes), $bytes);
        $i = 1;
        while ($i <= count($chars) && $chars[$i] != 0) {
            $str .= chr($chars[$i]);
            $i++;
        }

        return array($compressed, $str);
    }
    
    public function wad_info() {
        $info = ($this->identification . ' with ' . $this->numlumps . ' lumps, and directory at ' . $this->infotable_offset . PHP_EOL);
        foreach ($this->lumps as $lump) {
            $sizeStr = sprintf("%' 12d", $lump['size']);

            if ($lump['compressed']) {
                $sizeStr = sprintf("%' 11d", $lump['size']) . "*";
            }

            $info .= sprintf("%' 9s", $lump['name']) . sprintf("%' 10s", $lump['type']) . sprintf("%' 12d", $lump['position']) . $sizeStr;
            $info .= PHP_EOL;
        }
        return $info;
    }
    
    public function add_lump($lump) {
        $this->lumps[] = $lump;
        $this->numlumps++;
    }
    
    public function count_lumps() {
        return count($this->lumps);
    }
    
    public function get_lump_for_map($mapname, $lumpname) {
        $i = 0;
        $foundmap = "";
        $foundlump = null;
        while ($foundmap == "") {
            $examinedlump = $this->lumps[$i]['name'];
            if ($examinedlump == $mapname) {
                $foundmap = $mapname;
            }
            $i++;
        }
        if (!$foundmap) {
            return false;
        }

        while ($foundlump == null) {
            $examinedlump = $this->lumps[$i]['name'];
            if ($examinedlump == $lumpname) {
                return $this->lumps[$i];
            }
            if (!in_array($examinedlump, $this->map_lump_names)) {
                return false;
            }
        }
        //Didn't find a map lump
        return false;
    }
    
    public function get_map_markers() {
        $mapmarkers = [];
        foreach ($this->lumps as $lump) {
            if ($lump['type'] == 'mapmarker') {
                $mapmarkers[] = $lump['name'];
            }
        }
        return $mapmarkers;
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
        $lumpname = strtolower($lumpname);
        foreach ($this->lumps as $lump) {
            if (strtolower($lump['name']) == $lumpname) {
                return $lump;
            }
        }
        return null;
    }
    
    public function get_lumps($lumpname) {
        $lumps = [];
        $lumpname = strtolower($lumpname);
        foreach ($this->lumps as $lump) {
            if (strtolower($lump['name']) == $lumpname) {
                $lumps[] = $lump;
            }
        }
        return $lumps;
    }
}
