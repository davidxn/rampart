<?php
require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . '_constants.php');
require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . '_functions.php');
require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . 'header.php');
?>
<div class="errorpage">
<h3>General errors</h3>
<h4><?=get_error_link('ERR_WAD_MISSING', ['RM01.WAD'])?></h4>

<p>The file that was uploaded for this map slot couldn't be found. The map slot is still there and can be reuploaded by using your PIN, but the upload might have been removed temporarily if it caused other errors in the project. Contact the project owner to check.</p>

<h4><?=get_error_link('ERR_WAD_NO_LUMPS')?></h4>
<h4><?=get_error_link('ERR_WAD_BAD_LUMPS')?></h4>

<p>RAMPART can't find a valid map in your WAD. Maps consist of a map marker with a data length of 0, followed by a set of lumps that define the map. Your map lumps might be out of order, include lumps that aren't meant to be part of the map definition, or your map marker may have a non-zero length.</p>

<p>You can use <a href="https://slade.mancubus.net/index.php?page=downloads">SLADE3</a> to open your WAD and inspect it. Make sure the lump order is correct, and move any lumps that aren't part of the map definition. To replace a map marker with a length above zero, create a new marker, put it at the top of your map definition and delete the old marker.</p>



<h3>Texture errors</h3>
<h4><?=get_error_link('ERR_TEX_DEFINITION_NOT_NEEDED', ['s3dummy'])?></h4>

<p>Your WAD defines one of the dummy textures used by Doom-engine games to fill out the 0th texture slot. This definition won't break anything but it isn't needed.</p>

<h4><?=get_error_link('ERR_TEX_REDEFINITION_BASE', ['SKIN2', 'patchskin2,0,0', 'patch"hell8_2",64,0patch"hell8_4",0,0', 'patch"hell8_2",0,0'])?></h4>
<h4><?=get_error_link('ERR_TEX_REDEFINITION_OTHER', ['SKIN2', 'patchskin2,0,0', 'patch"hell8_2",64,0patch"hell8_4",0,0', 32, 'patch"hell8_2",0,0'])?></h4>

<p>Your WAD has a definition in a TEXTURES lump with a name that's already been defined, either by another map or in the project's base resources. Your definition has been ignored and this texture is likely to look different in your map from what you expected. Rename the image lump or the definition in your TEXTURES lump and change references to it in your map.</p>

<h4><?=get_error_link('WARN_TEX_REDEFINITION', ['MIDFENC1', 'patch"midfenc1",0,0', '33'])?></h4>

<p>Your WAD has a definition in a TEXTURES lump with a name that's already been defined, either by another map or in the project's base resources. Your definition is identical to the existing one, so your definition has been skipped but there should be no difference in your map.</p>

<h4><?=get_error_link('ERR_LUMP_NEEDS_CONVERTED', ['[LUMP]', '[TEXTURES/ANIMDEFS]'])?></h4>

<p>Your WAD includes a lump that's unsupported by RAMPART because they replace the entire table of texture definitions or animation definitions project-wide. You can use <a href="https://slade.mancubus.net/index.php?page=downloads">SLADE3</a> to open your WAD and convert the lump automatically into a TEXTURES/ANIMDEFS lump, which will work with RAMPART - you only need to include the definitions for things you've added, not definitions from the project's base resources.</p>

<h4><?=get_error_link('ERR_TEX_CONFLICTS')?></h4>

<p>A TEXTURES lump in your WAD couldn't be imported fully because of conflicts with other maps or in the project's base data. Check the errors above this one in the log for details.</p>



<h3>Other resource errors</h4>

<h4><?=get_error_link('ERR_LUMP_DUPLICATE_BASE', ['BCRATEL1'])?></h4>
<h4><?=get_error_link('ERR_LUMP_DUPLICATE_OTHER', ['LILDV1', '38'])?></h4>

<p>A data lump in your WAD has the same name as a lump in the project's base resources, or another map. This could be a patch, texture, music, and so on. If it's already in the project's resources, you don't need to include this lump. If it's custom content, rename it and update your map's references to it to ensure it works as expected.</p>

<h4><?=get_error_link('WARN_LUMP_DUPLICATE_OTHER', ['METAL_C', '32'])?></h4>

<p>A data lump in your WAD has the same name as a lump in another submission, but the data in each lump is identical. This could be a patch, texture, music, and so on. Your map will still work correctly. (Lumps with the same names as those in the base resources are never allowed, even if they're identical)</p>

<h4><?=get_error_link('ERR_LUMP_COLORMAP_UNSUPPORTED', ['C_START'])?></h4>

<p>Your WAD contains markers indicating the use of colormaps, which are not supported by RAMPART. Effects that rely on these being there won't appear correctly.</p>

<h4><?=get_error_link('ERR_LUMP_LOCKDEFS_CLEARLOCKS')?></h4>
<p>Your WAD contains a LOCKDEF that uses the clearlocks command, which would erase all other previously defined locks. Reupload your WAD without using this command.</p>

<h4><?=get_error_link('ERR_LUMP_LOCKDEFS_CONFLICTS')?></h4>
<p>Your WAD contains a LOCKDEF that overwrites vanilla doom locks, which would change them in all maps. Change the lock numbers, preferably around <code>your map number * 100</code> and reupload your WAD</p>

<h3>Sound errors</h3>

<h4><?=get_error_link('ERR_SOUND_SNDINFO_REDEFINITION', ['growl2', 'TMGROWL2', 'GROWL2'])?></h4>

<p>Your WAD contains a SNDINFO lump that uses a logical name for a sound that's already taken by another map or the project's base resources. Rename the logical name (the left side of the SNDINFO definition) and update any references to it.</p>

<h4><?=get_error_link('WARN_SOUND_SNDINFO_REDEFINITION', ['cimpsit1', 'cimpcit1'])?></h4>

<p>Your WAD contains a SNDINFO lump that uses a logical name for a sound that's already taken by another map or the project's base resources. Your definition is identical to the existing one, so your definition has been skipped but there should be no difference in your map.</p>

<h4><?=get_error_link('WARN_SOUND_SNDINFO_NOT_IMPORTED')?></h4>

<p>An SNDINFO lump in your WAD has been rejected due to errors, and won't be included in the project - check above this message in the log for more information.</p>

<h4><?=get_error_link('ERR_MUSIC_TOO_BIG', ['ECHOES'])?></h4>
<p>A lump identified as music in your map is over 10MB, so it's been skipped and will not play in your map. Try re-encoding the music at a lower bitrate.</p>



<h3>Script errors</h3>

<h4><?=get_error_link('ERR_DOOMEDNUM_CONFLICT', ['14304', 'pyrodemon', 'DECORATE class: PyroDemon', '63'])?></h4>

<h4><?=get_error_link('ERR_DOOMEDNUM_CONFLICT_BASE', ['8', 'Snake'])?></h4>

<p>Your WAD defines a DoomEd number that has already been used by a Thing or class in another map, or that lands in a range reserved for game objects. This will make an unexpected object appear on your map in place of the object with the conflicted number. Check the Build Info page for a list of conflicts and the currently assigned DoomEd numbers across the project, and use one that hasn't been taken.</p>

<p>If you get this error pointing to your own map, make sure you haven't defined a class's DoomEd number in both the MAPINFO and DECORATE lumps.</p>

<h4><?=get_error_link('ERR_SCRIPT_REPLACEMENTS', ['ZSCRIPT'])?></h4>

<p>Your WAD contains a script file that tries to automatically replace classes using the replaces keyword. This is unsupported by RAMPART because the replacement would take place across all maps. Define a new DoomEd number for your class instead and place it in your map directly.</p>

<p>The check for this is very crude, only searching for the word "replaces" in the text of the script - if this error is unexpected, check that "replaces" doesn't appear in strings or comments either.</p>

<h4><?=get_error_link('ERR_DECORATE_DOOMEDNUM_CONFLICT')?></h4>

<p>Your DECORATE lump defines a DoomEd number that has already been used by a Thing or class in another map. This will make an unexpected object appear on your map in place of the object with the conflicted number. Check the Build Info page for a list of conflicts and the currently assigned DoomEd numbers across the project, and use one that hasn't been taken.</p>

<p>If you get this error pointing to your own map, make sure you haven't defined a class's DoomEd number in both the MAPINFO and DECORATE lumps.</p>

<h4><?=get_error_link('ERR_IDENTIFIERS_NOT_IMPORTED')?></h4>

<p>DoomEd numbers and spawn numbers defined in this WAD won't be included because the WAD contained scripts that were rejected - this protects against the project being unstartable due to a DoomEd number pointing to a class that doesn't exist. Check for why the WAD's scripts were rejected to fix this error.</p>
</div>
<?php
require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . './footer.php');
?>