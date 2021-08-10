<?php
require_once('header.php');
?>
                <div id="settings_1">
                    <div id="settings_1" class="lightbox">
                        <table class="upload_table"><tbody>
                        
                        <tr><td colspan="2"><h3>Project settings</h3></td></tr>
                        <tr>
                            <td width="200">Project format:</td>
                            <td>
                            <div>
                                <input type="radio" id="radio-format-pk3"
                                 name="PROJECT_FORMAT" value="PK3" <?=get_setting("PROJECT_FORMAT")=="PK3"?"checked=\"checked\"":""?>>
                                <label for="radio-format-pk3">PK3</label>
                                <input type="radio" id="radio-format-wad"
                                 name="PROJECT_FORMAT" value="WAD" <?=get_setting("PROJECT_FORMAT")=="WAD"?"checked=\"checked\"":""?>>
                                <label for="radio-format-wad">WAD</label>
                            </td>
                        </tr>
                        <tr>
                            <td width="200">Project file name:</td>
                            <td><input type="text" id="PROJECT_FILE_NAME" value="<?=htmlspecialchars(get_setting("PROJECT_FILE_NAME"))?>"></input></td>
                        </tr>
                        <tr>
                            <td width="200">Project output folder:</td>
                            <td><input type="text" id="PROJECT_OUTPUT_FOLDER" value="<?=htmlspecialchars(get_setting("PROJECT_OUTPUT_FOLDER"))?>"></input></td>
                        </tr>
                        <tr>
                            <td>Allow users to add new map slots:</td>
                            <td>
                                <label class="checkmarkcontainer">
                                    <input type="checkbox" id="ALLOW_NEW_UPLOADS" <?=get_setting("ALLOW_NEW_UPLOADS", "checkbox")?"checked=\"checked\"":""?>>
                                    <span class="checkmark"></span>
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <td>Allow users to update maps:</td>
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

                        <td width="200">Allow jump and crouch:</td>
                        <td>
                        <div>
                            <input type="radio" id="radio-jump-user"
                             name="ALLOW_GAMEPLAY_JUMP" value="true" <?=get_setting("ALLOW_GAMEPLAY_JUMP")==="user"?"checked=\"checked\"":""?>>
                            <label for="radio-jump-user">Allow uploader to choose</label>
                            <input type="radio" id="radio-jump-yes"
                             name="ALLOW_GAMEPLAY_JUMP" value="true" <?=get_setting("ALLOW_GAMEPLAY_JUMP")===true?"checked=\"checked\"":""?>>
                            <label for="radio-jump-yes">Yes</label>
                            <input type="radio" id="radio-jump-no"
                             name="ALLOW_GAMEPLAY_JUMP" value="false" <?=get_setting("ALLOW_GAMEPLAY_JUMP")==false?"checked=\"checked\"":""?>>
                            <label for="radio-jump-no">No</label>
                        </td>
                        
                        <tr>
                            <td width="200">Apply this lump as map sky if present:</td>
                            <td><input type="text" id="DEFAULT_SKY_LUMP" value="<?=htmlspecialchars(get_setting("DEFAULT_SKY_LUMP"))?>"></input></td>
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
                                <input type="radio" id="radio-pin-random"
                                 name="PIN_MANAGER_CLASS" value="Pin_Manager_Random" <?=get_setting("PIN_MANAGER_CLASS")=="Pin_Manager_Random"?"checked=\"checked\"":""?>>
                                <label for="radio-pin-random">Randomly</label>
                                <input type="radio" id="radio-pin-preset"
                                 name="PIN_MANAGER_CLASS" value="Pin_Manager_Preset" <?=get_setting("PIN_MANAGER_CLASS")=="Pin_Manager_Preset"?"checked=\"checked\"":""?>>
                                <label for="radio-pin-preset">From list file</label>
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
