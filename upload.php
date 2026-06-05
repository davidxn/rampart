<?php
require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . 'header.php');
?>
                <p>Submit or adjust a RAMP 2026 map here!</p>
                
                <div class="lightbox">
                <p>As well as a map, you can include these resources:</p>
                <ul>
                    <li>Name a lump <b>D_RUNNIN</b> to include it as the background music for your map.</li>
                    <li>Include a 688x426 image lump in PNG/JPG format under the name <b>RAMPSHOT</b> to display it as the thumbnail image for your map in the hub level - use this <a href="./img/2026bannertemplate.png">template</a> to see how it will be cut.</li>
                    <li>For skies, you can do one of two things:
                    <ul>
                        <li>Include a lump called <b>SKY1</b> and it'll automatically be used as the sky</li>
                        <li>OR Include your sky texture(s) under other names and point to them with SKY1 and SKY2 in a MAPINFO</li>
                    </ul>
                </li>
                </ul>
                <p>Take a look at the <a href="./rules.php">rules page</a> for a complete list of accepted data. If what you want isn't there, ask!</p>
                </div>

                <div class="spacer" style="height: 32px">&nbsp;</div>

                <div id="upload-question-type">
                    <p>I want to...</p>
                    <?php if (get_setting("ALLOW_NEW_UPLOADS") == 'direct') { ?>
                    <button type="button" id="uploadtype_first">Add a new map</button>
                    <?php } ?>
                    <?php if (get_setting("ALLOW_NEW_UPLOADS") == 'request') { ?>
                        <button type="button" id="uploadtype_request">Request a map slot</button>
                    <?php } ?>
                    <?php if (get_setting("ALLOW_EDIT_UPLOADS")) { ?>
                    <button type="button" id="uploadtype_update">Use a PIN to submit or update a map</button>
                    <?php } ?>
                </div>
                <div id="upload-question-pin">
                    <div id="pin_form" class="lightbox">
                        <p>Please enter the PIN for the slot you want to update. (If you don't know it, contact the project owner)</p>
                        <div id="pin_status">&nbsp;</div>
                        <input type="text" style="width: 100px;" id="input_pin_to_reupload"/>
                        <button type="button" id="confirm_pin">That's my PIN</button>
                    </div>
                </div>

                <div id="upload-question-email">
                    <div id="email_form" class="lightbox">
                        <p>Enter your email address and RAMPART will send you a PIN you can use to upload your map!</p>
                        <div id="email_status">&nbsp;</div>
                        <input type="text" style="width: 300px;" id="input_email_address"/>
                        <button type="button" id="confirm_email">That's my email</button>
                    </div>
                </div>

                <div id="upload-question-details">
                    <div id="upload_form" class="lightbox">
                        <p id="upload_prompt">OK - add your details here and click Upload to submit the map.</p>
                        <table class="upload_table"><tbody><tr>
                            <td width="200">Map name</td>
                            <td><input type="text" id="input_map_name"/></td>
                        </tr><tr>
                            <td>Author name</td>
                            <td><input type="text" id="input_author_name"/></td>
                        </tr><tr>
                            <td>Music credit</td>
                            <td><input type="text" id="input_music_credit"/>
                            <div class="smallnote">Not needed, just if you want to add a "Rondo in D# by Flynn Taggart" note on level start</div></td>
                        </tr><tr>
                                <td>Map file:</td>
                                <td>
                                    <input type="file" name="file" id="file">
                                    <!-- Drag and Drop container-->
                                    <div class="upload-area"  id="uploadfile">
                                        <h1>Drag and drop your WAD here!<br/>(Or click to select a file)</h1>
                                    </div>
                                    <br/>
                                </td>
                            </tr>
                            <tr
                        <?php if (!(get_setting('ALLOW_GAMEPLAY_JUMP') == 'user')) {
                            echo(' style="display: none"');
                        } ?>
                            ><td>Allow jump and crouch</td><td><?php echo html_flag_checkbox("rjump")?></td>
                        </tr><tr>
                            <td>Map is a work-in-progress</td>
                            <td>
                                <?php echo html_flag_checkbox("rwip")?>
                                <div class="smallnote">Check this box to indicate a map isn't ready to be fully played yet. You'll still be able to upload new versions even if this box isn't checked</div>
                            </td>
                        </tr>

                        <tr>
                            <td>Map theme</td>
                            <td>
                            <div class="smallnote">You don't have to be exact, just the general setting of the map.</div>
                            <div>
                                <?php
                                    echo html_radio_button('input_map_category', 'Abstract or minigame', 'mystery', true, 'uac');
                                    echo html_radio_button('input_map_category', 'Ancient', 'ancient', true, 'uac');
                                    echo html_radio_button('input_map_category', 'Castle', 'castle', true, 'uac');
                                    echo html_radio_button('input_map_category', 'Techbase (classic Doom)', 'uac', true, 'uac');
                                    echo html_radio_button('input_map_category', 'Other future or space', 'space', true, 'uac');
                                    echo html_radio_button('input_map_category', 'Hell', 'hell', true, 'uac');
                                    echo html_radio_button('input_map_category', 'Outdoor or caves', 'cave', true, 'uac');
                                    echo html_radio_button('input_map_category', 'Urban (towns and cities)', 'city', true, 'uac');
                                ?>
                            </div>
                            </td>
                        <tr>
                        
                        <tr>
                            <td>Map difficulty level</td>
                            <td>
                            <div class="smallnote">How tough is this map?</div>
                            <div>
                                <?php
                                    echo html_radio_button('input_map_difficulty', '1. No real chance of failure', 1, true, 3);
                                    echo html_radio_button('input_map_difficulty', '2. Pretty gentle', 2, true, 3);
                                    echo html_radio_button('input_map_difficulty', '3. About the same as Ultra-Violence in Doom', 3, true, 3);
                                    echo html_radio_button('input_map_difficulty', '4. You\'re going to die a lot', 4, true, 3);
                                    echo html_radio_button('input_map_difficulty', '5. Densely packed monster-o-rama', 5, true, 3);
                                ?>
                            </div>
                            </td>
                        <tr>

                        <tr>
                            <td>Map length</td>
                            <td>
                            <div class="smallnote">Aim for maps to last 10 minutes or less.</div>
                            <div>
                                <?php
                                    echo html_radio_button('input_map_length', '1. A&nbsp;couple&nbsp;of&nbsp;minutes', 1, true, 3);
                                    echo html_radio_button('input_map_length', '2. 5 minutes or less', 2, true, 3);
                                    echo html_radio_button('input_map_length', '3. 10 minutes or so', 3, true, 3);
                                    echo html_radio_button('input_map_length', '4. A bit more than 10 minutes', 4, true, 3);
                                    echo html_radio_button('input_map_length', '5. Actually a lot more than 10 minutes', 5, true, 3);
                                ?>
                            </div>
                            </td>
                        <tr>
                            <td>Map labels</td>
                            <td>
                                <?php
                                    echo("<div class='\"checkboxseparator\"'>About</div>");
                                    echo html_flag_checkbox("rfirst", "I'm a Doom mapping beginner!");
                                    echo html_flag_checkbox("rnewmon", "Includes custom monsters");
                                    echo html_flag_checkbox("rnewwep", "Includes custom weapons or pickups");
                                    echo("<div class='\"checkboxseparator\"'>Special configuration</div>");
                                    echo html_flag_checkbox("rmouse", "Requires vertical mouselook");
                                    echo html_flag_checkbox("rnomouse", "Vertical mouselook shouldn't be used");
                                    echo html_flag_checkbox("rlight", "Requires dynamic lighting");
                                    echo("<div class='\"checkboxseparator\"'>Map genre</div>");
                                    echo html_flag_checkbox("rslaught", "This is a slaughtermap");
                                    echo html_flag_checkbox("rpuzzle", "Contains puzzles");
                                    echo html_flag_checkbox("rpeace", "No combat in this map");
                                    echo html_flag_checkbox("rgame", "This is a minigame unlike normal Doom gameplay");
                                    echo html_flag_checkbox("rstory", "Story-focused");
                                    echo("<div class='\"checkboxseparator\"'>Phobias</div>");
                                    echo html_flag_checkbox("rscare", "Contains jumpscares!");
                                    echo html_flag_checkbox("rspider", "Might be bad for arachnophobics");
                                    echo html_flag_checkbox("rwater", "Involves swimming underwater");
                                ?>
                            </td>
                        </tr>
                        </tbody></table>
                        <button type="button" id="upload_wad">Submit WAD!</button>
                    </div>
                    <div id="upload_status">&nbsp;</div>
                </div>
<?php
require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . 'footer.php');
