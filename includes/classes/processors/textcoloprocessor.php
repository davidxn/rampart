<?php
class TextColoProcessor extends LumpProcessor {

    private array $reservedColours = ['Brick', 'Tan', 'Grey', 'Gray', 'Green', 'Brown', 'Gold', 'Red', 'Blue', 'Orange',
        'White', 'Yellow', 'Untranslated', 'Black', 'LightBlue', 'Light Blue', 'Cream', 'Olive', 'DarkGreen',
        'Dark Green', 'DarkRed', 'Dark Red', 'DarkBrown', 'Dark Brown', 'Purple', 'DarkGrey', 'DarkGray', 'Dark Grey',
        'Dark Gray', 'Cyan', 'Ice', 'Fire', 'Sapphire', 'Teal'
    ];

    public function process(): bool
    {
        $attemptedColours = [];

        $lineRegex = "/^(.*)\n{/m";
        $colourRegex = "/(\"[^\"]+\"|\S+)/";
        $matchingLines = [];
        preg_match_all($lineRegex, $this->lump->data, $matchingLines);

        foreach ($matchingLines[1] as $matchingLine) {
            $colourDefinitions = [];
            preg_match_all($colourRegex, $matchingLine, $colourDefinitions);
            $attemptedColours = array_merge($attemptedColours, $colourDefinitions[1]);
        }

        //If any of the attempted colours are in our reserved colours, we fail
        $clashingColours = array_intersect($attemptedColours, $this->reservedColours);
        if (count($clashingColours) > 0) {
            Logger::pg(get_error_link('ERR_LUMP_TEXTCOLO_OVERRIDE', [join(", ", $clashingColours)]), $this->rampMap->rampId, true);
            return false;
        }
        $this->accept();
        return true;
    }
}
