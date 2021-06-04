<?
require_once('./_constants.php');

class Guide_Dialogue_Writer {
    
    function write() {

        $catalog = @json_decode(file_get_contents(CATALOG_FILE), true);
        if (empty($catalog)) {
            $catalog = [];
        }

        $maps = [];
        foreach($catalog as $mapdata) {
            $maps[$mapdata['map_number']] = $mapdata['map_name'];
        }

        asort($maps, SORT_FLAG_CASE | SORT_STRING);
        //Now separate them into groups
        $paged_maps = [];
        $firstround = true;
        $maps_this_page = 0;
        $page_number = 0;
        foreach ($maps as $tag => $map) {
            $paged_maps[$page_number][$tag] = $map;
            $maps_this_page++;
            if ($maps_this_page == MAPS_PER_PAGE) {
                $maps_this_page = 0;
                $page_number++;
                $firstround = false;
            }
        }

        //print_r($paged_maps);
        $end_page_number = count($paged_maps)+1;

        //Got paged maps! Let's write the conversation script

        $text = "namespace = \"ZDoom\";

        conversation {
            actor = \"RAMPO\";
        ";

        $page_number = 1;
        foreach ($paged_maps as $page) {
            $text .= "    page { // page " . $page_number . "
                name = \"RAMPO\";
                dialog = \"Hello! I'm RAMPO (which stands for RAMP Assistant for Map Pointing-Out). Which map can I help you navigate to today?\";
                goodbye = \"Close (ESC)\";
        ";
            if ($page_number != 1) {
                $text .= "        choice {
                    text = \"<< PREVIOUS <<\";
                    nextpage = " . ($page_number - 1) . ";
                }
        ";
            } else {
                $text .= "        choice {
                    text = \"-- TOP OF LIST --\";
                    nextpage = " . ($page_number) . ";
                }
        ";
            }
            foreach($page as $index => $map) {
                $text .= "        choice {
                    text = \"$map\";
                    special = 80;
                    arg0 = 1;
                    arg1 = 0;
                    arg2 = $index;
                }
        ";
            }
            if ($page_number != $end_page_number-1) {
                $text .= "        choice {
                    text = \">> NEXT >>\";
                    nextpage = " . ($page_number + 1) . ";
                }
        ";
            } else {
                $text .= "        choice {
                    text = \"-- END OF LIST --\";
                    nextpage = " . ($page_number) . ";
                }
        ";
            }
                
            $text .= "    }
        ";
            $page_number++;
        }
        $text .= "    page {
                name = \"Map altered\";
                dialog = \"OK! I've added a marker to your map. Have a nice day.\";
                choice {
                    text = \"Look up another map\";
                    nextpage = 1;
                }
            }
        }
        ";
        return($text);
    }
}