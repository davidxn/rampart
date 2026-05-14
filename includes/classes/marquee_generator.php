<?php
require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . '_bootstrap.php');

class Marquee_Generator {
    private string $exo_font = RAMPART_HOME . 'fonts' . DIRECTORY_SEPARATOR . 'EXO2BOLD.OTF';
    private string $upheaval_font = RAMPART_HOME . 'fonts' . DIRECTORY_SEPARATOR . 'upheavtt.ttf';
    private string $bannerTemplateImage = DATA_FOLDER . 'bannertemplate.png';
    private string $tileTemplateImage = DATA_FOLDER . 'tiletemplate.png';
    private string $tileBackgroundPrefix = DATA_FOLDER . 'tilebg';
    private string $tileMaskImage = DATA_FOLDER . 'tilemask.png';
    private string $tileIconPrefix = DATA_FOLDER . 'rzone';
    private string $maskImage = DATA_FOLDER . 'bannermask.png';
    private string $ghostMaskImage = DATA_FOLDER . 'ghostmask.png';
    private string $wedgeDifficulty = DATA_FOLDER . 'wedgedifficulty.png';
    private string $wedgeLength = DATA_FOLDER . 'wedgelength.png';
    private string $marqueePatchesFolder = PK3_FOLDER . 'patches' . DIRECTORY_SEPARATOR . 'marquee' . DIRECTORY_SEPARATOR;
    private string $marqueeGraphicFolder = PK3_FOLDER . 'graphics' . DIRECTORY_SEPARATOR . 'marquee' . DIRECTORY_SEPARATOR;
    private string $marqueeTexturesFile = PK3_FOLDER . "TEXTURES.marquee";

    private string $screenshotTempFolder = WORK_FOLDER . 'screenshots' . DIRECTORY_SEPARATOR;

    private array $rampIdsToMarqueeHashes = [];

    private const LENGTH_START_X = 733;
    private const LENGTH_START_Y = 106;
    private const LENGTH_DIFF_X = 9;
    private const LENGTH_DIFF_Y = 15;
    private const DIFFICULTY_START_X = 733;
    private const DIFFICULTY_START_Y = 398;
    private const DIFFICULTY_DIFF_X = 9;
    private const DIFFICULTY_DIFF_Y = -15;

    public array $ZONE_IDS = [
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

    private Catalog_Handler $catalog;

    public function __construct(Catalog_Handler $catalog) {
        $this->catalog = $catalog;
        if (file_exists(MARQUEE_HASH_FILE)) {
            $this->rampIdsToMarqueeHashes = json_decode(file_get_contents(MARQUEE_HASH_FILE), true);
        }
    }

    public function getMapMarqueeDataHash(RampMap $map): string {
        $screenshotFile = $this->screenshotTempFolder . 'RAMPSHOTRAW' . $map->rampId;
        $screenshotFileHash = "";
        if (file_exists($screenshotFile)) {
            $screenshotFileHash = md5_file($screenshotFile);
        }
        return md5($map->name . $map->lump . $map->author . $map->mapnum . $map->category . $map->length . $map->difficulty . $screenshotFileHash);
    }

    public function includeMarquees(): void {
        Logger::pg("Assembling marquee graphics");
        foreach ($this->catalog->get_catalog() as $map_data) {
            $marqueeDataMatchesCache = (
                isset($this->rampIdsToMarqueeHashes[$map_data->rampId])
                && $this->getMapMarqueeDataHash($map_data) == $this->rampIdsToMarqueeHashes[$map_data->rampId]);
            if (!$marqueeDataMatchesCache) {
                Logger::pg("Regenerating textures for {$map_data->rampId}");
                try {
                    $marqueeImages = $this->generateMarquee($map_data->rampId);
                    $marqueeImages[0]->writeImage("{$this->screenshotTempFolder}RSHOT{$map_data->mapnum}.png");
                    $marqueeImages[1]->writeImage("{$this->screenshotTempFolder}RNAME{$map_data->mapnum}.png");
                    $marqueeImages[2]->writeImage("{$this->screenshotTempFolder}RAUTH{$map_data->mapnum}.png");
                    $marqueeImages[3]->writeImage("{$this->screenshotTempFolder}RBACK{$map_data->mapnum}.png");

                    $tileImage = $this->generateTile($map_data->rampId);
                    $tileImage->writeImage("{$this->screenshotTempFolder}RTILE{$map_data->mapnum}.png");

                    $this->rampIdsToMarqueeHashes[$map_data->rampId] = $this->getMapMarqueeDataHash($map_data);
                }
                catch (Exception $e) {
                    Logger::pg("Failed to write a marquee image for {$map_data->getMapLink()}: {$e->getMessage()}");
                    continue;
                }
            }
            $names = [
                "RSHOT" => $this->marqueePatchesFolder,
                "RNAME" => $this->marqueeGraphicFolder,
                "RAUTH" => $this->marqueeGraphicFolder,
                "RTILE" => $this->marqueeGraphicFolder,
                "RBACK" => $this->marqueeGraphicFolder,
            ];
            foreach ($names as $name => $destination) {
                $filename = "{$name}{$map_data->mapnum}.png";
                copy("{$this->screenshotTempFolder}{$filename}", "{$destination}{$filename}");
            }
            Logger::pg("Copied marquees for {$map_data->rampId}");
        }
        file_put_contents(MARQUEE_HASH_FILE, json_encode($this->rampIdsToMarqueeHashes));
        $this->writeMarqueeTexturesFile();
    }

    public function writeMarqueeTexturesFile(): void {
        Logger::pg("Writing marquee texture file");
        $texturesFile = "";
        foreach ($this->catalog->get_catalog() as $map_data) {
            for ($i = 0; $i < 9; $i++) {
                $isPlain = ($i == 8);
                $texturesFile .= "Texture \"RSHO{$i}{$map_data->mapnum}\", 909, 547" . PHP_EOL;
                $texturesFile .= "{" . PHP_EOL;
                $texturesFile .= "  Patch \"RSHOT{$map_data->mapnum}\", 0, 0" . PHP_EOL;
                if (!$isPlain) {
                    $texturesFile .= "  Patch \"R2026BIG\", 279, 85" . PHP_EOL;
                    if ($i & 1) { $texturesFile .= "  Patch \"RMONTHM\", 486, 362" . PHP_EOL; }
                    if ($i & 2) { $texturesFile .= "  Patch \"RITMTHM\", 534, 333" . PHP_EOL; }
                    if ($i & 4) { $texturesFile .= "  Patch \"RSECTHM\", 582, 304" . PHP_EOL; }
                }
                $texturesFile .= "}" . PHP_EOL . PHP_EOL;
            }
        }

        file_put_contents($this->marqueeTexturesFile, $texturesFile);
    }

    public function generateTile(int $rampId): ?IMagick {
        $rampMap = $this->catalog->get_map_by_ramp_id($rampId);

        try {
            $frameImage = new Imagick();
            $frameImage->readImage($this->tileTemplateImage);
            $bgImage = new Imagick();
            $bgImage->readImage($this->tileBackgroundPrefix . $this->ZONE_IDS[$rampMap->category] . '.png');
            $iconImage = new Imagick();
            $iconImage->readImage($this->tileIconPrefix . $this->ZONE_IDS[$rampMap->category] . '.png');
            $maskImage = new Imagick();
            $maskImage->readImage($this->tileMaskImage);

            $mapTitleImage = $this->getSizedTextImage($rampMap->name, 11, 460, $this->upheaval_font, "#fff", 0.0);
            $mapTitleImageBlack = $this->getSizedTextImage($rampMap->name, 11, 460, $this->upheaval_font, "#000", 0.0);
            $mapAuthorImage = $this->getSizedTextImage($rampMap->author, 11, 460, $this->upheaval_font, "#fff", 0.0);
            $mapAuthorImageBlack = $this->getSizedTextImage($rampMap->author, 11, 460, $this->upheaval_font, "#000", 0.0);
            $mapLumpImage = $this->getSizedTextImage($rampMap->lump, 11, 460, $this->upheaval_font, "#ffd800", 0.0);
            $mapLumpImageBlack = $this->getSizedTextImage($rampMap->lump, 11, 460, $this->upheaval_font, "#000", 0.0);

            $screenshotImage = new Imagick();
            try {
                $screenshotImage->readImage(WORK_FOLDER . DIRECTORY_SEPARATOR . 'screenshots' . DIRECTORY_SEPARATOR . "RAMPSHOTRAW" . $rampId);
            } catch (Exception $ex) {
                $screenshotImage->readImage(DATA_FOLDER . 'defaultscreenshot.png');
            }
            $screenshotImage->setImageFormat('png32');
            $screenshotImage->setImageAlphaChannel(Imagick::ALPHACHANNEL_SET);
            $screenshotImage->resizeImage(0,106, imagick::FILTER_CATROM, 0.9);
            $screenshotImage->compositeImage($maskImage, Imagick::COMPOSITE_DSTIN, 0, 0, Imagick::CHANNEL_ALPHA);

            $mapLumpPos = [122, 9];
            $mapTitlePos = [129, 28];
            $mapAuthorPos = [136, 47];
            $mapIconPos = [570, 40];

            $bgImage->compositeImage($screenshotImage, imagick::COMPOSITE_OVER, 0, 0);
            $bgImage->compositeImage($frameImage, imagick::COMPOSITE_OVER, 0, 0);

            $iconImage->resizeImage(0,21, imagick::FILTER_POINT, 0);
            $bgImage->compositeImage($iconImage, imagick::COMPOSITE_OVER, $mapIconPos[0], $mapIconPos[1]);

            $bgImage->compositeImage($mapTitleImageBlack, imagick::COMPOSITE_OVER, $mapTitlePos[0] + 2, $mapTitlePos[1] + 2);
            $bgImage->compositeImage($mapTitleImage, imagick::COMPOSITE_OVER, $mapTitlePos[0], $mapTitlePos[1]);

            $bgImage->compositeImage($mapLumpImageBlack, imagick::COMPOSITE_OVER, $mapLumpPos[0] + 2, $mapLumpPos[1] + 2);
            $bgImage->compositeImage($mapLumpImage, imagick::COMPOSITE_OVER, $mapLumpPos[0], $mapLumpPos[1]);

            $bgImage->compositeImage($mapAuthorImageBlack, imagick::COMPOSITE_OVER, $mapAuthorPos[0] + 2, $mapAuthorPos[1] + 2);
            $bgImage->compositeImage($mapAuthorImage, imagick::COMPOSITE_OVER, $mapAuthorPos[0], $mapAuthorPos[1]);

            return $bgImage;

        } catch (Exception $e) {
            print $e->getMessage();
            return null;
        }
    }

    public function generateMarquee(int $rampId): array
    {
        $rampMap = $this->catalog->get_map_by_ramp_id($rampId);

        try {
            $baseImage = new Imagick();
            $baseImage->readImage($this->bannerTemplateImage);

            $mapTitleImage = $this->getSizedTextImage($rampMap->name, 32, 712, $this->upheaval_font);
            $mapAuthorImage = $this->getSizedTextImage($rampMap->author, 26, 712, $this->upheaval_font);
            $mapLengthImage = $this->getSizedTextImage($rampMap->length, 50, 712, $this->upheaval_font, '#88f');
            $mapDifficultyImage = $this->getSizedTextImage($rampMap->difficulty, 50, 712, $this->upheaval_font, '#f33');
            $mapNumberImage = $this->getSizedTextImage($rampMap->mapnum, 44, 120, $this->upheaval_font);

            $baseImage->compositeImage($mapTitleImage, imagick::COMPOSITE_OVER, 180, 13);
            $baseImage->compositeImage($mapAuthorImage, imagick::COMPOSITE_OVER, 666 - $mapAuthorImage->getImageWidth(), 510);
            $baseImage->compositeImage($mapLengthImage, imagick::COMPOSITE_OVER, 770 - intval($mapLengthImage->getImageWidth()/2), 88);
            $baseImage->compositeImage($mapDifficultyImage, imagick::COMPOSITE_OVER, 770 - intval($mapDifficultyImage->getImageWidth()/2), 426);
            $baseImage->compositeImage($mapNumberImage, imagick::COMPOSITE_OVER, 94 - intval($mapNumberImage->getImageWidth()/2), 68);

            //Do we have a screenshot? If we do, let's put that on
            $screenshotImage = new Imagick();
            try {
                $screenshotImage->readImage(WORK_FOLDER . DIRECTORY_SEPARATOR . 'screenshots' . DIRECTORY_SEPARATOR . "RAMPSHOTRAW" . $rampId);
            } catch (Exception $ex) {
                $screenshotImage->readImage(DATA_FOLDER . 'defaultscreenshot.png');
            }

            $screenshotImage->setImageFormat('png32');
            $screenshotImage->setImageAlphaChannel(Imagick::ALPHACHANNEL_SET);
            $screenshotImage->resizeImage(0,426, imagick::FILTER_CATROM, 0.9);
            if ($screenshotImage->getImageWidth() < 686) {
                $screenshotImage->resizeImage(686, 426, imagick::FILTER_CATROM, 0.9);
            }
            $ghostImage = clone $screenshotImage;

            $maskImage = new Imagick();
            $maskImage->readImage($this->maskImage);
            $screenshotImage->compositeImage($maskImage, Imagick::COMPOSITE_DSTIN, 0, 0, Imagick::CHANNEL_ALPHA);

            $baseImage->compositeImage($screenshotImage, imagick::COMPOSITE_OVER, 95, 69);

            //Add wedges for length and difficulty
            $lengthCounterImage = new Imagick();
            $lengthCounterImage->readImage($this->wedgeLength);
            $difficultyCounterImage = new Imagick();
            $difficultyCounterImage->readImage($this->wedgeDifficulty);

            $mapLength = $rampMap->length;
            $mapDifficulty = $rampMap->difficulty;

            while ($mapLength > 0) {
                $baseImage->compositeImage($lengthCounterImage, imagick::COMPOSITE_OVER, self::LENGTH_START_X + (self::LENGTH_DIFF_X * $mapLength), self::LENGTH_START_Y + (self::LENGTH_DIFF_Y * $mapLength));
                $mapLength--;
            }
            while ($mapDifficulty > 0) {
                $baseImage->compositeImage($difficultyCounterImage, imagick::COMPOSITE_OVER, self::DIFFICULTY_START_X + (self::DIFFICULTY_DIFF_X * $mapDifficulty), self::DIFFICULTY_START_Y + (self::DIFFICULTY_DIFF_Y * $mapDifficulty));
                $mapDifficulty--;
            }

            $ghostMaskImage = new Imagick();
            $ghostMaskImage->readImage($this->ghostMaskImage);
            $ghostImage->compositeImage($ghostMaskImage, Imagick::COMPOSITE_DSTIN, 0, 0, Imagick::CHANNEL_ALPHA);

            return [$baseImage, $mapTitleImage, $mapAuthorImage, $ghostImage];

        } catch (Exception $e) {
            print $e->getMessage();
            return [];
        }
    }

    /**
     * @throws ImagickDrawException
     * @throws ImagickException|ImagickPixelException
     */
    private function getSizedTextImage($text, $height, $maxWidth, $font, $colour = '#fff', $strokeWidth = 1.0): Imagick
    {
        $prefixBufferSize = 100;

        $draw = new ImagickDraw();
        $draw->setFont($font);
        $draw->setFontSize(60); // Draw large then reduce
        $draw->setTextAntialias(true);
        $draw->setFillColor(new ImagickPixel($colour));
        $draw->setStrokeColor(new ImagickPixel('#000'));
        $draw->setStrokeAntialias(true);
        $draw->setStrokeWidth($strokeWidth);

        //Create text
        $textImage = new Imagick();
        $textImage->newImage(6000,500, "transparent");
        $textImage->annotateImage($draw, 0, 50, 0, "yY                 " . "$text");

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
