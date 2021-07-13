<?
require_once('header.php');
?>
<p>Rules? Not many, really - experiment and create what you like! But I can offer some guidelines:</p>

<h3>How to submit</h3>

<p>Use the <a href="./upload.php"/>Upload</a> page on this site! Submit a WAD containing a single map (of any Doom format), and the site backend will incorporate the map into the complete project so far. With the PIN you'll get the first time you upload, you can submit any further edits to your map you like.</p>

<h3>Custom content</h3>

<p>Custom content is allowed, although bear in mind that it will be easier to upload WADs without it! Feel free to include what you like, as long as you don't overwrite any of the default DOOM2 lumps (unfortunately I can't support DEHACKED either). And if you're just beginning with Doom mapping, it might be easier to stay with the material in DOOM2.WAD for your first venture.</p>

<p>The textures of <b>cc4-tex.wad</b> and <b>OTEX_1.1.WAD</b> will be included in the project, so you don't need to do anything special to use those.</p>

<p>When you upload your WAD through this site, the uploader will recognize and include:</p>

<ul>
<li>The first map (map name doesn't matter)</li>
<li>(Optional) The first MIDI/OGG/MP3 for your background music (lump name doesn't matter)</li>
<li>(Optional) A graphics lump called RSKY1, for your map's sky</li>
</ul>

<p>If you need more custom content, contact DavidN on the Discord to get it into the project.</p>

<h3>How the final compilation will work</h3>

<p>Everyone's submitted WADs will be compiled into a GZDoom game based around a hub level, and the player will be able to more or less choose to complete the maps in any order. In each map, the player will start with only the pistol, so don't rely on the player carrying weapons or ammunition from previous maps.</p>

<p>I will have absolutely <i>no</i> ownership over your map once submitted - you can feel free to re-release it on its own, expand it into an episode and release that later, or whatever you like!</p>

<h3>Other guidelines</h3>

<p>Map length depends on your preference. In general I find Doom levels with about 100-120 monsters that can be completed in 5-10 minutes a satisfying length in a multi-map WAD, but opinions about this vary wildly. Maybe take that as a guideline and don't worry if your map is significantly smaller or larger.</p>

<?
require_once('./footer.php');
?>
