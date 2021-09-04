<?php
require_once('header.php');
?>
                <div id="settings_1">
                    <div id="settings_1" class="lightbox">
                        <table class="upload_table"><tbody>
                        
                        <tr><td colspan="2"><h3>Project settings</h3></td></tr>
                        <tr>
                            <td width="200">Project title:</td>
                            <td><input type="text" id="PROJECT_TITLE" value="<?=htmlspecialchars(get_setting("PROJECT_TITLE"))?>"></input></td>
                        </tr>
                        <tr>
                            <td width="200">Project file name:</td>
                            <td><input type="text" style="width: 200px" id="PROJECT_FILE_NAME" value="<?=htmlspecialchars(get_setting("PROJECT_FILE_NAME"))?>">                                <?
                                echo html_radio_button('PROJECT_FORMAT', '.PK3', 'PK3');
                                echo html_radio_button('PROJECT_FORMAT', '.WAD', 'WAD');
                                ?></input></td>
                        </tr>
                        <tr>
                            <td width="200">Project output folder:</td>
                            <td><input type="text" id="PROJECT_OUTPUT_FOLDER" value="<?=htmlspecialchars(get_setting("PROJECT_OUTPUT_FOLDER"))?>"></input></td>
                        </tr>
                        <tr>
                            <td>Allow users to add new map slots:</td>
                            <td>
                                <div class="smallnote">Use this if you're making a project with a hub that anyone can submit a level to. Turn it off if you want to manage the available level slots yourself.</div>
                                <label class="checkmarkcontainer">
                                    <input type="checkbox" id="ALLOW_NEW_UPLOADS" <?=get_setting("ALLOW_NEW_UPLOADS", "checkbox")?"checked=\"checked\"":""?>>
                                    <span class="checkmark"></span>
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <td>Allow users to submit maps:</td>
                            <td>
                                <label class="checkmarkcontainer">
                                    <input type="checkbox" id="ALLOW_EDIT_UPLOADS" <?=get_setting("ALLOW_EDIT_UPLOADS", "checkbox")?"checked=\"checked\"":""?>>
                                    <span class="checkmark"></span>
                                </label>
                            </td>
                        </tr>
                        
                        <tr><td colspan="2"><h3>Content settings</h3></td></tr>
                        <tr>
                            <td>Include uploaded maps:</td>
                            <td>
                                <label class="checkmarkcontainer">
                                    <input type="checkbox" id="ALLOW_CONTENT_MAPS" <?=get_setting("ALLOW_CONTENT_MAPS", "checkbox")?"checked=\"checked\"":""?>>
                                    <span class="checkmark"></span>
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <td>Include uploaded DECORATE and ZSCRIPT:</td>
                            <td>
                                <label class="checkmarkcontainer">
                                    <input type="checkbox" id="ALLOW_CONTENT_SCRIPTS" <?=get_setting("ALLOW_CONTENT_SCRIPTS", "checkbox")?"checked=\"checked\"":""?>>
                                    <span class="checkmark"></span>
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <td>Include uploaded music:</td>
                            <td>
                                <label class="checkmarkcontainer">
                                    <input type="checkbox" id="ALLOW_CONTENT_MUSIC" <?=get_setting("ALLOW_CONTENT_MUSIC", "checkbox")?"checked=\"checked\"":""?>>
                                    <span class="checkmark"></span>
                                </label>
                            </td>
                        </tr>

                        <tr>
                            <td width="200">Allow jump and crouch:</td>
                            <td>
                            <div>
                                <?
                                    echo html_radio_button('ALLOW_GAMEPLAY_JUMP', 'Uploader choice', 'user');
                                    echo html_radio_button('ALLOW_GAMEPLAY_JUMP', 'Yes', 'always');
                                    echo html_radio_button('ALLOW_GAMEPLAY_JUMP', 'No', 'never');
                                ?>
                            </td>
                        </tr>
                        
                        <tr>
                            <td width="200">Apply this lump as map sky if present:</td>
                            <td><input type="text" id="DEFAULT_SKY_LUMP" value="<?=htmlspecialchars(get_setting("DEFAULT_SKY_LUMP"))?>"></input></td>
                        </tr>
                        
                        <tr><td colspan="2"><h3>MAPINFO settings</h3></td></tr>
                        <tr>
                            <td width="200">MAPINFO behaviour:</td>
                            <td>
                            <div>
                                <?
                                    echo html_radio_button('PROJECT_WRITE_MAPINFO', 'Generate MAPINFO from uploads', true);
                                    echo html_radio_button('PROJECT_WRITE_MAPINFO', 'No MAPINFO, or I\'ll write my own', false);
                                ?>
                            </div>
                            </td>
                        </tr>
                        <tr>
                            <td width="200">Use these MAPINFO properties from uploads:</td>
                            <td><textarea class="code" id="PROJECT_MAPINFO_PROPERTIES"><?=htmlspecialchars(get_setting("PROJECT_MAPINFO_PROPERTIES"))?></textarea></td>
                        </tr>
                        <tr>
                            <td width="200">Quick music recognition:</td>
                            <td>
                            <div>
                                <?
                                    echo html_radio_button('PROJECT_QUICK_MUSIC', 'Use first music lump', true);
                                    echo html_radio_button('PROJECT_QUICK_MUSIC', 'Only use music in MAPINFO', false);
                                ?>
                            </div>
                            </td>
                        </tr>
                        
                        <tr><td colspan="2"><h3>Site settings</h3></td></tr>
                        <tr>
                            <td width="200">Banner message:</td>
                            <td><input type="text" id="BANNER_MESSAGE" value="<?=htmlspecialchars(get_setting("BANNER_MESSAGE"))?>"></input></td>
                        </tr>
                        <tr>
                            <td width="200">Seconds between PIN attempts:</td>
                            <td><input type="number" id="PIN_ATTEMPT_GAP" value="<?=htmlspecialchars(get_setting("PIN_ATTEMPT_GAP"))?>"></input></td>
                        </tr>
                        <tr>
                            <td width="200">Seconds between upload attempts:</td>
                            <td><input type="number" id="UPLOAD_ATTEMPT_GAP" value="<?=htmlspecialchars(get_setting("UPLOAD_ATTEMPT_GAP"))?>"></input></td>
                        </tr>
                        <tr>
                            <td width="200">Generate PINs:</td>
                            <td>
                            <div>
                            <?
                                echo html_radio_button('PIN_MANAGER_CLASS', 'Randomly', 'Pin_Manager_Random');
                                echo html_radio_button('PIN_MANAGER_CLASS', 'From list file', 'Pin_Manager_Preset');
                            ?>
                            </td>
                        </tr>
                        
                        <tr><td colspan="2"><h3>Notification settings</h3></td></tr>
                        <tr>
                            <td width="200">Email address:</td>
                            <td><input type="text" id="NOTIFY_EMAIL" value="<?=htmlspecialchars(get_setting("NOTIFY_EMAIL"))?>"></input></td>
                        </tr>
                        <tr>
                            <td width="200">Notify on:</td>
                            <td>
                            <div>
                                <?
                                    echo html_radio_button('NOTIFY_ON_MAPS', 'None', 'never');
                                    echo html_radio_button('NOTIFY_ON_MAPS', 'All map uploads', 'all');
                                    echo html_radio_button('NOTIFY_ON_MAPS', 'New map slots only', 'new');
                                ?>
                            </td>
                        </tr>
                        
                        <tr><td colspan="2"><h3>Guide settings</h3>
                        <div class="smallnote">RAMPART can create a DIALOGUE tree for a hub level that lists the available maps. When the player selects a map name, it will call the given ACS script number with the mapnum as a parameter - you can code this script to add a marker to their map, or transport them there directly.</div>
                        </td></tr>
                        <tr>
                            <td>Enable guide:</td>
                            <td>
                                <label class="checkmarkcontainer">
                                    <input type="checkbox" id="GUIDE_ENABLED" <?=get_setting("GUIDE_ENABLED", "checkbox")?"checked=\"checked\"":""?>>
                                    <span class="checkmark"></span>
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <td width="200">Hub map file path:</td>
                            <td><input type="text" id="HUB_MAP_FILE" value="<?=htmlspecialchars(get_setting("HUB_MAP_FILE"))?>"></input></td>
                        </tr>
                        <tr>
                            <td width="200">Name for conversation:</td>
                            <td><input type="text" id="GUIDE_NAME" value="<?=htmlspecialchars(get_setting("GUIDE_NAME"))?>"></input></td>
                        </tr>
                        <tr>
                            <td width="200">Greeting text:</td>
                            <td><input type="text" id="GUIDE_TEXT" value="<?=htmlspecialchars(get_setting("GUIDE_TEXT"))?>"></input></td>
                        </tr>
                        <tr>
                            <td width="200">Goodbye text:</td>
                            <td><input type="text" id="GUIDE_CLOSE_TEXT" value="<?=htmlspecialchars(get_setting("GUIDE_CLOSE_TEXT"))?>"></input></td>
                        </tr>
                        <tr>
                            <td width="200">Menu class:</td>
                            <td><input type="text" id="GUIDE_MENU_CLASS" value="<?=htmlspecialchars(get_setting("GUIDE_MENU_CLASS"))?>"></input></td>
                        </tr>
                        <tr>
                            <td width="200">Script number for map selection:</td>
                            <td><input type="number" id="GUIDE_SCRIPT_NUMBER" value="<?=htmlspecialchars(get_setting("GUIDE_SCRIPT_NUMBER"))?>"></input></td>
                        </tr>
                        <tr>
                            <td width="200">Maps per page:</td>
                            <td><input type="number" id="MAPS_PER_PAGE" value="<?=htmlspecialchars(get_setting("MAPS_PER_PAGE"))?>"></input></td>
                        </tr>
                        </tbody></table>
                        <button type="button" id="save_settings">Save settings!</button>
                    </div>
                </div>
                <script src="settings.js" type="text/javascript"></script>
<?php
require_once('footer.php');

function html_radio_button($setting, $text, $value = null) {
    if ($value === null) {
        $value = $text;
    }
    $id = $setting . "__" . strtoupper(str_replace(" ", "", $value));
    return '<input type="radio" id="'.$id.'"' . PHP_EOL .
            'name="'.$setting.'" value="'.$value.'"' . (get_setting($setting)==$value?" checked=\"checked\"":"") . '">' . PHP_EOL .
            '<label for="'.$id.'">'.$text.'</label>' . PHP_EOL;
}