<?php
require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . '_bootstrap.php');

class Marquee_Generator {
    
    public $LETTER_HEIGHT = 8;
    public $LETTER_WIDTH = 8;
    public $CHARACTER_MAP = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789 -':";
    public $CHARACTER_MAP_MORE = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-=_+:,.\"'!@#$%^&*()/? ";
    public $ZONE_FONTS = [
        "space" => 'fonts/Robofan Free.otf',
        "uac" => 'fonts/DOOM.TTF',
        "castle" => 'fonts/DOMINICA.TTF',
        "hell" => 'fonts/Frightmare.ttf',
        "cave" => 'fonts/Berenika-BoldOblique.ttf',
        "ancient" => 'fonts/MountOlympus.otf',
        "mystery" => 'fonts/Winsconsin.otf',
        "city" => 'fonts/transporth.ttf',
        "none" => 'fonts/F25_Bank_Printer.ttf'
    ];
    public $ZONE_IDS = [
        "space" => 0,
        "uac" => 1,
        "castle" => 2,
        "hell" => 3,
        "cave" => 4,
        "ancient" => 5,
        "mystery" => 6,
        "city" => 7,
        "none" => 9
    ];    
    
    function generate_marquees($catalog_handler) {
        Logger::pg("Generating marquee textures");
        @mkdir($marquee_folder, 0755, true);
        
        $marquee_textures_lump = "";
        
        $marquee_textures_template = "
        Texture \"MARQ@mapnum@\", 620, 620
        {
            Patch \"@zonecap@\", 0, 0
            Patch \"YMARQ@mapnum@\", 0, 173
            Patch \"RAMPSCRE\", 0, 237
            @screenshotpatch@
            Patch \"RAMPSCRB\", 0, 237
        }
        Texture \"MARX@mapnum@\", 620, 620
        {
            Patch \"@zonecap@\", 0, 0
            Patch \"BMARQ@mapnum@\", 0, 173
            Patch \"RAMPSCRE\", 0, 237
            @screenshotpatch@
            Patch \"RAMPSCRB\", 0, 237
            Patch \"RAMPCHEK\", 276, 0
        }
        ";

        $catalog = $catalog_handler->get_catalog();
        foreach ($catalog as $map_data) {
            $map_name = $map_data['map_name'];
            $map_author = $map_data['author'];
            $map_number = $map_data['map_number'];
            $map_category = $map_data['category'];
            $zone_id = $this->ZONE_IDS[$map_category];
            $screenshot_width = $this->generatePatches($map_name, $map_number, $map_author, $map_category);
            $half_screenshot_width = round($screenshot_width / 2);
            Logger::pg("Wrote marquee patches for map " . $map_number);
            
            $screenshot_patch_text = "";            
            if ($half_screenshot_width > 0) {
                $screenshot_patch_text = "Patch \"RSHOT" . $map_number . "\", " . 310 - $half_screenshot_width . ", 237";
            }
            
            $this_patch_data = str_replace("@mapnum@", $map_number, $marquee_textures_template);
            $this_patch_data = str_replace("@screenshotpatch@", $screenshot_patch_text, $this_patch_data);
            $this_patch_data = str_replace("@zonecap@", "RAMPSCR" . $zone_id, $this_patch_data);
            
            $marquee_textures_lump .= $this_patch_data;
        } 
        $marquee_textures_filename = PK3_FOLDER . "TEXTURES.marquee";
        @unlink($marquee_textures_filename);
        file_put_contents($marquee_textures_filename, $marquee_textures_lump);        
    }    

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
    
    function generatePatches($levelname, $mapnum, $mapauthor, $category = "none") {
        
        $marquee_folder = PK3_FOLDER . DIRECTORY_SEPARATOR . "patches" . DIRECTORY_SEPARATOR . "marquee";
        
        $bannerImage = $this->generateTextBanner($levelname, $mapauthor, $category);

        $backgroundImage = new Imagick();
        $backgroundImage->readImage(DATA_FOLDER . "RAMPSCRE.png");    

        //Do we have a screenshot? If we do, let's put that on
        $screenshotImage = new Imagick();
        try {
            $hasScreenshot = $screenshotImage->readImage(WORK_FOLDER . DIRECTORY_SEPARATOR . 'screenshots' . DIRECTORY_SEPARATOR . "RSHOT" . $mapnum);
        } catch (Exception $ex) {
            $hasScreenshot = false;
        }
        if ($hasScreenshot) {
            $screenshotImage->resizeImage(0,384, imagick::FILTER_CATROM, 0.9, false);
            $screenshotImage->writeImage($marquee_folder . DIRECTORY_SEPARATOR . "RSHOT" . $mapnum . ".jpg");
        }

        $bannerImage->writeImage($marquee_folder . DIRECTORY_SEPARATOR . "YMARQ" . $mapnum . ".png");
        $bannerImage->modulateImage(100, 100, 0);
        $bannerImage->writeImage($marquee_folder . DIRECTORY_SEPARATOR . "BMARQ" . $mapnum . ".png");
        
        //Return the eventual width of the screenshot, need this to calculate the position for the patch
        return $hasScreenshot ? $screenshotImage->getImageWidth() : 0;
    }
    
    private function generateTextBanner($bannerText, $authorText, $category) {

        $textImage = $this->getSizedTextImage($bannerText, 35, 600, $this->ZONE_FONTS[$category]);
        $authorImage = $this->getSizedTextImage($authorText, 15, 600, $this->ZONE_FONTS["none"]);

        $bannerImage = new Imagick();
        $bannerImage->newImage(620, 64, "black");

        $pointTextCentre = [310, 24];
        $pastePoint = [$pointTextCentre[0] - ($textImage->getImageWidth()/2),
                        $pointTextCentre[1] - ($textImage->getImageHeight()/2)];
        
        $bannerImage->compositeImage($textImage, imagick::COMPOSITE_OVER, intval($pastePoint[0]), intval($pastePoint[1]));
        
        $pointAuthorCentre = [310, 52];
        $pastePoint = [$pointAuthorCentre[0] - ($authorImage->getImageWidth()/2),
                        $pointAuthorCentre[1] - ($authorImage->getImageHeight()/2)];

        $bannerImage->compositeImage($authorImage, imagick::COMPOSITE_OVER, intval($pastePoint[0]), intval($pastePoint[1]));        
        //Add lines across the top and bottom
        $draw = new ImagickDraw();
        $draw->setFontSize(4);
        $draw->setFillColor('#ff0');
        $draw->line(0, 0, 620, 0);
        $draw->line(0, 63, 620, 63);
        $bannerImage->drawImage($draw);
        
        return $bannerImage;
    }
    
    private function getSizedTextImage($text, $height, $maxWidth, $font) {

        $prefixBufferSize = 100;

        $draw = new ImagickDraw();
        $draw->setFont($font);
        $draw->setFontSize(30); // Draw large then reduce
        $draw->setTextAntialias(true);
        $draw->setFillColor('#ff0');

        //Create text
        $textImage = new Imagick();
        $textImage->newImage(6000,500, "transparent");
        $textImage->annotateImage($draw, 0, 50, 0, "yY                 " . $text);
        $textImage->trimImage(0);

        //Crop pixels off the left to remove prefix
        $textImage->cropImage(
            $textImage->getImageWidth() - $prefixBufferSize, 500, //X and Y size of the crop
            $prefixBufferSize, 0 // X and Y coordinates to start on
        );

        //Now we want to trim horizontally but not vertically - measure by trimming a clone
        $trimMeasureImage = clone $textImage;
        $trimMeasureImage->trimImage(0);

        $startX = $textImage->getImageWidth() - $trimMeasureImage->getImageWidth() + $prefixBufferSize;
        $textImage->cropImage(
            $trimMeasureImage->getImageWidth(), 120, //X and Y size of the crop
            $startX, 0 // X and Y coordinates to start on 
        );

        //Resize the image proportionately so that it's the right height for our banner
        $textImage->resizeImage(0, $height, imagick::FILTER_CATROM, 0.9, false);

        //Squish it if it's too long
        if ($textImage->getImageWidth() > $maxWidth) {
            $textImage->resizeImage($maxWidth, $height, imagick::FILTER_CATROM, 0.9, false);
        }

        return $textImage;
    }
    
}
