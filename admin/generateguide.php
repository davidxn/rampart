<?
require_once('_constants.php');
require_once("scripts/guide_writer.php");
header("Content-Type: text/plain");

$guide_writer = new Guide_Dialogue_Writer();
print($guide_writer->write());