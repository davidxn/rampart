<?php

//error_reporting(E_ALL);
//ini_set('display_errors', 'On');

class Marquee_Generator {
    
    public $LETTER_HEIGHT = 8;
    public $LETTER_WIDTH = 8;
    public $CHARACTER_MAP = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789 -':";
    public $CHARACTER_MAP_MORE = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-=_+:,.\"'!@#$%^&*()/? ";

    function generateImage($levelname, $imagetype, $font_name) {
        
        $font_info = $this->getFontInfo($font_name);
        
        $image_width = (strlen($levelname)) * $font_info['width'];
        //Create image
        $final_image = imagecreatetruecolor($image_width, $font_info['height']);
        $source_image = $imagetype ? imagecreatefrompng("./img/marquee/" . $font_info['source']) : imagecreatefrompng("./img/marquee/" . $font_info['source2']);

        //Fill with black background
        $background = imagecolorallocatealpha($final_image, 0, 0, 0, 0);
        imagefill($final_image, 0, 0, $background);

        $character_index = 0;
        while ($character_index < strlen($levelname)) {
            $char = substr($levelname, $character_index, 1);
            //Convert to uppercase if we're using the basic character map
            if ($font_info['charmap'] == $this->CHARACTER_MAP) {
                $char = strtoupper($char);
            }
            $char_position = strpos($font_info['charmap'], $char);
            
            imagecopy(
                $final_image, $source_image, //Dest, source
                $character_index * $font_info['width'], 0, //Top left pixel of destination
                $char_position * $font_info['width'], 0, //Top left pixel of source
                $font_info['width'], $font_info['height'] //Width and height of rectangle to copy
            );
            $character_index++;
        }
        imagesavealpha($final_image, true);
        
        return $final_image;
    }
    
    function getFontInfo($font_name) {
        switch ($font_name) {
            case 'dos':
                return ['width' => '9', 'height' => '16', 'source' => 'dos-y.png', 'source2' => 'dos-b.png', 'charmap' => $this->CHARACTER_MAP_MORE];
            case 'dosborder':
                return ['width' => '9', 'height' => '18', 'source' => 'dosborder-y.png', 'source2' => 'dosborder-b.png', 'charmap' => $this->CHARACTER_MAP_MORE];                
            case 'visitor':
            default:
                return ['width' => '8', 'height' => '8', 'source' => 'source.png', 'source2' => 'source2.png', 'charmap' => $this->CHARACTER_MAP];
        }
        return [];
    }
}
