<?php
$SKIP_SETTINGS_CHECK = true;
$GLOBALS['auth'] = true;
require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . 'header.php');
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
                            <td><input type="text" style="width: 200px" id="PROJECT_FILE_NAME" value="<?=htmlspecialchars(get_setting("PROJECT_FILE_NAME"))?>">
                            <?php
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
                                <div class="smallnote">Turn this off if you want to freeze the project and disallow uploads and edits.</div>
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
                            <td width="200">ZSCRIPT version:</td>
                            <td>
                                <div class="smallnote">This version number will be declared on the ZSCRIPT includes file</div>
                                <input type="text" id="ZSCRIPT_VERSION" value="<?=htmlspecialchars(get_setting("ZSCRIPT_VERSION"))?>"></input>
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
                            <td>Include SNDINFO and uploaded sounds:</td>
                            <td>
                                <label class="checkmarkcontainer">
                                    <input type="checkbox" id="ALLOW_CONTENT_SOUND" <?=get_setting("ALLOW_CONTENT_SOUND", "checkbox")?"checked=\"checked\"":""?>>
                                    <span class="checkmark"></span>
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <td width="200">Hub map name:</td>
                            <td>
                                <div class="smallnote">Lump name for the hub map, if players are to be returned to a hub after each level. Otherwise leave blank</div>
                                <input type="text" id="HUB_MAP_NAME" value="<?=htmlspecialchars(get_setting("HUB_MAP_NAME"))?>"></input>
                            </td>
                        </tr>

                        <tr>
                            <td width="200">Allow jump and crouch:</td>
                            <td>
                            <div>
                                <?php
                                    echo html_radio_button('ALLOW_GAMEPLAY_JUMP', 'Uploader choice', 'user');
                                    echo html_radio_button('ALLOW_GAMEPLAY_JUMP', 'Yes', 'always');
                                    echo html_radio_button('ALLOW_GAMEPLAY_JUMP', 'No', 'never');
                                ?>
                            </td>
                        </tr>
                        
                        <tr><td colspan="2"><h3>MAPINFO settings</h3></td></tr>
                        <tr>
                            <td width="200">MAPINFO behaviour:</td>
                            <td>
                            <div>
                                <?php
                                    echo html_radio_button('PROJECT_WRITE_MAPINFO', 'Do not use MAPINFO (or I\'ll write my own)', false);
                                    echo html_radio_button('PROJECT_WRITE_MAPINFO', 'Create a MAPINFO in this project', true);
                                ?>
                            </div>
                            </td>
                        </tr>
                        <tr>
                            <td width="200">MAPINFO properties allowed in uploads:</td>
                            <td>
                                <div class="smallnote">If a contributor includes a MAPINFO in their WAD, allow these properties to transfer into the final project's MAPINFO</div>
                                <textarea class="code" id="PROJECT_MAPINFO_PROPERTIES"><?=htmlspecialchars(get_setting("PROJECT_MAPINFO_PROPERTIES"))?></textarea>
                            </td>
                        </tr>
                        <tr>
                            <td width="200">Use music from upload:</td>
                            <td>
                            <div class="smallnote">You can set RAMPART to use the first music lump in the WAD as the map's background music, or only include music if it's pointed to by the MAPINFO.</div>
                            <div>
                                <?php
                                    echo html_radio_button('PROJECT_QUICK_MUSIC', 'Use first music lump', true);
                                    echo html_radio_button('PROJECT_QUICK_MUSIC', 'Only use music in MAPINFO', false);
                                ?>
                            </div>
                            </td>
                        </tr>
                        <tr>
                            <td width="200">Generate marquee textures:</td>
                            <td>
                                <div class="smallnote">This option will generate textures with the names of the maps, for placing in a hub</div>
                                <label class="checkmarkcontainer">
                                    <input type="checkbox" id="GENERATE_MARQUEES" <?=get_setting("GENERATE_MARQUEES", "checkbox")?"checked=\"checked\"":""?>>
                                    <span class="checkmark"></span>
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <td width="200">Sky lump:</td>
                            <td>
                                <div class="smallnote">If a lump by this name is present in an uploaded WAD, it will be included as the map's sky for the project.</div>
                                <input type="text" id="DEFAULT_SKY_LUMP" value="<?=htmlspecialchars(get_setting("DEFAULT_SKY_LUMP"))?>"></input>
                            </td>
                        </tr>
                        <tr>
                            <td width="200">Default music lump:</td>
                            <td>
                                <div class="smallnote">If no music is specified for a map, it will use this lump instead.</div>
                                <input type="text" id="DEFAULT_MUSIC_LUMP" value="<?=htmlspecialchars(get_setting("DEFAULT_MUSIC_LUMP"))?>"></input>
                            </td>
                        </tr>
                        <tr>
                            <td width="200">Assign vanilla music lump names:</td>
                            <td>
                                <div class="smallnote">Music uploaded to slots with map lumps in the chosen vanilla game will be written as that map's corresponding music</div>
                                <div>
                                    <?php
                                        echo html_radio_button('MUSIC_LUMP_MAP', 'None', "none");
                                        echo html_radio_button('MUSIC_LUMP_MAP', 'Ultimate Doom', "udoom");
                                        echo html_radio_button('MUSIC_LUMP_MAP', 'Doom 2', "doom2");
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
                            <div class="smallnote">If using the list option, provide a file called 'pins-master.txt' in the RAMPART work folder with one PIN on each line.</div>
                            <div>
                            <?php
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
                                <?php
                                    echo html_radio_button('NOTIFY_ON_MAPS', 'None', 'never');
                                    echo html_radio_button('NOTIFY_ON_MAPS', 'All map uploads', 'all');
                                    echo html_radio_button('NOTIFY_ON_MAPS', 'New map slots only', 'new');
                                ?>
                            </td>
                        </tr>
                        
                        <tr><td colspan="2"><h3>Guide settings</h3>
                        <div class="smallnote">If you're using a hub level, RAMPART can create a DIALOGUE tree that lists the available maps. When the player selects a map name, it will call the given ACS script number with the mapnum as a parameter - you can code this script to add a marker to their map, or transport them there directly.</div>
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
                            <td width="200">Actor class for guide:</td>
                            <td><input type="text" id="GUIDE_CLASS" value="<?=htmlspecialchars(get_setting("GUIDE_CLASS"))?>"></input></td>
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
require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . 'footer.php');
