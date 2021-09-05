<?php
require_once($SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . '_constants.php');
require_once($SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . 'scripts/catalog_handler.php');

class Guide_Dialogue_Writer {
    
    function write() {

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
            if ($maps_this_page == MAPS_PER_PAGE) {
                $maps_this_page = 0;
                $page_number++;
            }
        }

        $end_page_number = count($paged_maps)+1;

        //Got paged maps! Let's write the conversation script

        $text = "

        conversation {
            actor = \"" . GUIDE_NAME . "\";
        ";
        if (GUIDE_MENU_CLASS) {
        $text .= "
            class = \"" . GUIDE_MENU_CLASS . "\";
        ";
        }

        $page_number = 1;
        foreach ($paged_maps as $page) {
            $text .= "    page { // page " . $page_number . "
                name = \"" . GUIDE_NAME . "\";
                dialog = \"" . GUIDE_TEXT . "\";
                goodbye = \"" . CLOSE_TEXT . "\";
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
                $text .= "        choice { text = \" $map\"; special = 80; arg0 = " . GUIDE_SCRIPT_NUMBER . "; arg1 = 0; arg2 = $index; }
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
