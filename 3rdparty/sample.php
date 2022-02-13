<?php
require_once("husqvarna_map_api.class.php");
define("MOWER_ID", "xxxxxxxxx-yyyyyyyyy");

$account = "xx.yy@zz.fr";
$passwd = "pp";

$session_husqvarna = new husqvarna_map_api();
$session_husqvarna->login($account, $passwd);

// print("list_robot :\n");
// var_dump($session_husqvarna->list_robots());
// print("\n");

// print("control :\n");
// var_dump($session_husqvarna->control(MOWER_ID, 'START'));
// print("\n");

print("get_status :\n");
var_dump($session_husqvarna->get_status(MOWER_ID));
print("\n");

// print("get_geofence :<pre>");
// var_dump($session_husqvarna->get_geofence(MOWER_ID));
// print("</pre>");

// print("get_settings :\n");
// var_dump($session_husqvarna->get_settings(MOWER_ID));
// print("\n");

print("get_statistics :\n");
var_dump($session_husqvarna->get_statistics_app(MOWER_ID));
print("\n");

print("get_timers :\n");
var_dump($session_husqvarna->get_timers(MOWER_ID));
print("\n");
var_dump($session_husqvarna->get_timers_app(MOWER_ID));
print("\n");

$session_husqvarna->logout();
?>