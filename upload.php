<?php
require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . 'header.php');
require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . '_constants.php');
?>
                <p>This page lets contributors submit a map to your project!</p>
                
                <div class="lightbox">
                <p>As well as a map, you can include these resources:</p>
                <ul>
                <li>The first MIDI/OGG/MP3 file in the WAD will be used as your map's music, no matter what its name is.</li>
                <li>For skies, you can do one of two things:
                    <ul><li>Include a lump called RSKY1 and it'll automatically be used as the sky</li>
                    <li>OR Include your sky texture(s) under other names and point to them with SKY1 and SKY2 in a MAPINFO</li></ul>
                </li>
                </ul>
                <p>Take a look at the <a href="./rules.php">rules page</a> for a complete list of accepted data. If what you want isn't there, ask!</p>
                </div>

                <div id="upload-question-type">
                    <p>I want to...</p>
                    <?php if (get_setting("ALLOW_NEW_UPLOADS")) { ?>
                    <button type="button" id="uploadtype_first">Add a new map</button>
                    <?php } ?>
                    <?php if (get_setting("ALLOW_EDIT_UPLOADS")) { ?>
                    <button type="button" id="uploadtype_update">Update a map I submitted before</button>
                    <?php } ?>
                </div>
                <div id="upload-question-pin">
                    <div id="pin_form" class="lightbox">
                        <p>Please enter the PIN for the slot you want to update. (If you don't know it, contact the project owner)</p>
                        <div id="pin_status">&nbsp;</div>
                        <input type="text" id="input_pin_to_reupload"></input>
                        <button type="button" id="confirm_pin">That's my PIN</button>
                    </div>
                </div>

                <div id="upload-question-details">
                    <div id="upload_form" class="lightbox">
                        <p id="upload_prompt">OK - add your details here and click Upload to submit the map.</p>
                        <table class="upload_table"><tbody><tr>
                            <td width="200">Map name:</td>
                            <td><input type="text" id="input_map_name"></input></td>
                        </tr><tr>
                            <td>Author name:</td>
                            <td><input type="text" id="input_author_name"></input></td>
                        </tr><tr>
                            <td>Music credit:</td>
                            <td><input type="text" id="input_music_credit"></input>
                            <div class="smallnote">Not needed, just if you want to add a "Rondo in D# by Flynn Taggart" note on level start</div></td>
                        </tr><tr
                        <?php if (!(get_setting('ALLOW_GAMEPLAY_JUMP') == 'user')) {
                            echo(' style="display: none"');
                        } ?>
                            ><td>Allow jump and crouch:</td>
                            <td>
                                <label class="checkmarkcontainer">
                                    <input type="checkbox" id="input_map_jumpcrouch">
                                    <span class="checkmark"></span>
                                </label>
                            </td>
                        </tr><tr>
                            <td>Map is a work-in-progress:</td>
                            <td>
                                <label class="checkmarkcontainer">
                                    <input type="checkbox" id="input_map_wip">
                                    <span class="checkmark"></span>
                                </label>
                                <div class="smallnote">Check this box to indicate a map isn't ready to be fully played yet. You'll still be able to upload new versions even if this box isn't checked</div>
                            </td>
                        </tr><tr>
                        <td>Map file:</td><td>
                        <input type="file" name="file" id="file">
                        
                        <!-- Drag and Drop container-->
                        <div class="upload-area"  id="uploadfile">
                            <h1>Drag and drop your WAD here!<br/>(Or click to select a file)</h1>
                        </div>
                        <br/>
                        </td></tr>
                        </tbody></table>
                        <button type="button" id="upload_wad">Submit WAD!</button>
                    </div>
                    <div id="upload_status">&nbsp;</div>
                </div>
<?php
require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . 'footer.php');
?>
