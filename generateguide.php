<?php
require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . './_constants.php');
require_once("./scripts/guide_writer.php");

$guide_writer = new Guide_Dialogue_Writer();
print($guide_writer->write());
