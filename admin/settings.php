<?php
require_once('header.php');
?>
                <div id="settings_1">
                    <div id="settings_1" class="lightbox">
                        <table class="upload_table"><tbody>
                        <tr>
                            <td width="200">Banner message:</td>
                            <td><input type="text" id="BANNER_MESSAGE" value="<?=htmlspecialchars(get_setting("BANNER_MESSAGE"))?>"></input></td>
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
                        <tr>
                            <td width="200">Seconds between PIN attempts:</td>
                            <td><input type="number" id="PIN_ATTEMPT_GAP" value="<?=htmlspecialchars(get_setting("PIN_ATTEMPT_GAP"))?>"></input></td>
                        </tr>
                        <tr>
                            <td width="200">Seconds between upload attempts:</td>
                            <td><input type="number" id="UPLOAD_ATTEMPT_GAP" value="<?=htmlspecialchars(get_setting("UPLOAD_ATTEMPT_GAP"))?>"></input></td>
                        </tr>
                        <tr>
                            <td width="200">PIN manager class:</td>
                            <td><input type="text" id="PIN_MANAGER_CLASS" value="<?=htmlspecialchars(get_setting("PIN_MANAGER_CLASS"))?>"></input></td>
                        </tr>
                        <tr>
                            <td width="200">Default sky lump:</td>
                            <td><input type="text" id="DEFAULT_SKY_LUMP" value="<?=htmlspecialchars(get_setting("DEFAULT_SKY_LUMP"))?>"></input></td>
                        </tr>
                        <tr>
                            <td width="200">Project output folder:</td>
                            <td><input type="text" id="PROJECT_OUTPUT_FOLDER" value="<?=htmlspecialchars(get_setting("PROJECT_OUTPUT_FOLDER"))?>"></input></td>
                        </tr>
                        <tr>
                            <td width="200">Project file name:</td>
                            <td><input type="text" id="PROJECT_FILE_NAME" value="<?=htmlspecialchars(get_setting("PROJECT_FILE_NAME"))?>"></input></td>
                        </tr>
                        </tbody></table>
                        <button type="button" id="save_settings">Save settings!</button>
                    </div>
                </div>
                <script src="settings.js" type="text/javascript"></script>
<?php
require_once('footer.php');
