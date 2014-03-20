<?php
//REQUIRE php 5.3 or above
if(version_compare(PHP_VERSION, '5.3.0') < 0){
	throw new Exception("REQUIRE PHP 5.3 OR ABOVE", 1);
}
include 'lite.class.php';