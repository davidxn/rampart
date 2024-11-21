<?php
require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . '_constants.php');
require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . 'header.php');
?>
<h3>How RAMP works</h3>
<p>RAMP is a project built by everyone who chooses to contribute! Everyone's submitted WADs will be compiled into a GZDoom game based around a hub level, and the player will be able to more or less choose to complete the maps in any order. In each map, the player will start with only the pistol, so don't rely on the player carrying weapons or ammunition from previous maps.</p>

<h3>Mapping guidelines</h3>

<ul>
<li>Maps can be of any theme or approach. I recommend taking a couple of weeks for a map and uploading it <b>some time in mid-June</b>. The last date for alterations is <b>the end of June 30th</b>, after which I'll help with any bugfixing caused by merging the project together.</li>
<li>You're welcome to try more than one idea, but I need to limit it to three map submissions per person. If you'd like to do more, recruit a friend and help them make theirs!</li>
<li>I recommend that maps take <b>up to ten minutes to complete</b>, but there is no strict size restriction. Remember that a player might get fatigued by a single map in a collection that's too long, and due to the high number of submissions I'll only be able to provide about ten minutes of feedback per map.</li>
<li>While all comers are truly welcome to the project, I've got to reserve the right to kick maps that are excessively broken or unplayable on purpose, or people who don't treat other community members with respect. This isn't a quality control rule and maps won't be rejected for mistakes - we all make them!</li>
<li>Please make something original for this, instead of something that's already been released elsewhere.</li>
<li>However, after the project, I have no ownership of your map - I actively encourage you to take what you've made, build on it and release it independently as part of something bigger, like a whole custom episode!</li>
</ul>

<h3>Resources</h3>

<p>RAMP will run on DOOM2.WAD in GZDoom. Textures from the resource packs 32in24 and OTEX_1.1.WAD are included in the project, so you don't need to do anything special if you want to use those - you can download a resource pack containing both of them in <a href="./rampstarterpack.zip">the starter pack</a> (or <a href="./ramp2024resources.pk3">here</a> for just the resources PK3).</p>

<p>Custom content is also supported, as long as you don't overwrite any of the default DOOM2 lumps or make changes that affect the entire game (such as DEHACKED). If you'd like to include anything, see the Custom Content section below.</p>

<h3>How to submit</h3>

<p>Use the <a href="./upload.php">Upload</a> page on this site! Submit a WAD containing a single map of any Doom format, and the site backend will incorporate the map into the complete project so far. You can download a snapshot version after uploading to compile the project including your latest changes - check the console icon next to your map in the Download page to see a report on what the compiler included.

<p>You can submit changes to your map by going to the upload page and using the PIN that you'll be given the first time you upload. If you've lost it, the project owner can retrieve it.</p>

<h3>Details on custom content</h3>

<p>If you want to include custom Things (game objects), be aware of the DoomEd numbers you're using - each unique type of object in a Doom WAD needs to have a unique number, and this list is global across all maps. Check the <a href="./info.php">Build Info page</a> to have a look at the current list of DoomEd numbers that are occupied, and choose a range of numbers that won't interfere with others. The recommended formula is to use [(your map number) * 100] as a starting point, but this won't be possible for all maps due to the numbers of existing Doom objects.</p>

<p>When you upload your WAD through this site, the uploader will recognize and include:</p>

<ul>
<li>The first present Doom or UDMF map, including any ACS scripts and dialogue attached to it</li>
<li>ZSCRIPT and DECORATE lumps <i>(they will be rejected if they use the </i>replaces<i> keyword)</i></li>
<li>The SNDINFO lump(s) and any sound lumps named in them</li>
<li>Patches between P_START and P_END markers <i>(alternatively PP_START or PPSTART, PP_END or PPEND)</i></li>
<li>Textures between TX_START and TX_END markers </li>
<li>Sprites between S_START and S_END markers <i>(alternatively SS_START, SS_END)</i></li>
<li>Flats between F_START and F_END markers <i>(alternatively FF_START, FF_END)</i></li>
<li>These lumps will be imported directly, with an extension matching your map's number:
<ul>
<li>TEXTURES to define textures made from patches</li>
<li>GLDEFS</li>
<li>ANIMDEFS</li>
<li>LOCKDEFS unless the clearlocks command is used (make sure you don't overwrite any previously defined locks)</li>
<li>SNDSEQ (make sure your defined sound sequence numbers don't collide with other maps)</li>
<li>README</li>
<li>MANUAL</li>
</ul></li>
<li>Music:
<ul>
<li>Name a lump D_RUNNIN to make it your map's background music.</li>
<li>If you need multiple music files, include them between MS_START and MS_END tags and refer to them directly in your MAPINFO or scripts.</li>
</ul></li>
<li>Certain allowed properties in MAPINFO:
<ul>
<?php
foreach (ALLOWED_MAPINFO_PROPERTIES as $prop) {
    echo ("<li>" . $prop . "</li>" . PHP_EOL);
} ?>
</ul>
</li>
</ul>

<p>If you need more custom content, contact DavidN on <a href="https://discord.gg/afFGdGNhW2">the Discord</a> to get it into the project.</p>

<?php
require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . './footer.php');
