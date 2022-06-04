<?php
require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . '_constants.php');
require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . 'scripts/catalog_handler.php');

class Guide_Dialogue_Writer {
    
    function write($has_existing_dialogue) {

        $catalog_handler = new Catalog_Handler();

        $maps = [];
        foreach($catalog_handler->get_catalog() as $mapdata) {
            $maps[$mapdata['map_number']] = $mapdata['map_name'];
        }

        asort($maps, SORT_FLAG_CASE | SORT_STRING);
        //Now separate them into groups
        $paged_maps = [];
        $maps_this_page = 0;
        $page_number = 0;
        foreach ($maps as $tag => $map) {
            $paged_maps[$page_number][$tag] = $map;
            $maps_this_page++;
            if ($maps_this_page == get_setting("MAPS_PER_PAGE")) {
                $maps_this_page = 0;
                $page_number++;
            }
        }

        $end_page_number = count($paged_maps)+1;

        //Got paged maps! Let's write the conversation script
        $text = "";
        
        //If we didn't have existing data in the lump we'll need to add a namespace first
        if (!$has_existing_dialogue) {
            $text .= "namespace = \"ZDoom\";
            ";
        }

        //Here's the rest
        $text .= "

        conversation {
            actor = \"" . get_setting("GUIDE_CLASS") . "\";
        ";
        if (get_setting("GUIDE_MENU_CLASS")) {
        $text .= "
            class = \"" . get_setting("GUIDE_MENU_CLASS") . "\";
        ";
        }

        $page_number = 1;
        foreach ($paged_maps as $page) {
            $text .= "    page { // page " . $page_number . "
                name = \"" . get_setting("GUIDE_NAME") . "\";
                dialog = \"" . get_setting("GUIDE_TEXT") . "\";
                goodbye = \"" . get_setting("CLOSE_TEXT") . "\";
        ";
            if ($page_number != 1) {
                $text .= "        choice { text = \"<< PREVIOUS <<\"; nextpage = " . ($page_number - 1) . ";
                }
        ";
            } else {
                $text .= "        choice { text = \"-- TOP OF LIST --\"; nextpage = " . ($page_number) . ";
                }
        ";
            }
            foreach($page as $index => $map) {
                $text .= "        choice { text = \" $map\"; special = 80; arg0 = " . get_setting("GUIDE_SCRIPT_NUMBER") . "; arg1 = 0; arg2 = $index; }
        ";
            }
            if ($page_number != $end_page_number-1) {
                $text .= "        choice { text = \">> NEXT >>\"; nextpage = " . ($page_number + 1) . "; }
        ";
            } else { $text .= "        choice { text = \"-- END OF LIST --\"; nextpage = " . ($page_number) . "; }
        ";
            }
                
            $text .= "    }
        ";
            $page_number++;
        }
        $text .= "}";
        return($text);
    }
}
