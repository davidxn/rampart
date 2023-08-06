<?php
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

<p>RAMP will run on DOOM2.WAD. Custom content is allowed, although bear in mind that it will be easier to upload WADs without it! Feel free to include what you like, as long as you don't overwrite any of the default DOOM2 lumps (unfortunately I can't support DEHACKED either). And if you're just beginning with Doom mapping, it might be easier to stay with the material in DOOM2.WAD for your first venture.</p>

<p>The textures of <a href="https://esselfortium.net/wasd/32in24-15_tex_v2.zip"><b>32in24-15_tex_v2.wad</b></a> and <a href="https://www.doomworld.com/idgames/graphics/otex_1_1"><b>OTEX_1.1.WAD</b></a> will be included in the project, so you don't need to do anything special to use those.</p>

<h3>How to submit</h3>

<p>Use the <a href="./upload.php">Upload</a> page on this site! Submit a WAD containing a single map (of any Doom format), and the site backend will incorporate the map into the complete project so far. With the PIN you'll get the first time you upload, you can submit any further edits to your map you like.</p>

<h3>Details on custom content</h3>

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
<li>If multiple MIDIs are included, they will all be included under their original names and can be named in MAPINFO or scripts.</li>
<li>If one MIDI is provided, it will be assumed to be the map's music and will be set up automatically with no need for a MAPINFO definition.</li>
</ul></li>
<li>Certain allowed properties in MAPINFO</li>
</ul>

<p>If you need more custom content, contact DavidN on <a href="https://discord.gg/afFGdGNhW2">the Discord</a> to get it into the project.</p>

<?php
require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . './footer.php');
?>
