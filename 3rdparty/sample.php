<?php
require_once("husqvarna_map_api.class.php");
$account = "xx.yy@zz.fr";
$passwd = "pp";
$session_husqvarna = new husqvarna_map_api();
$session_husqvarna->login($account, $passwd);

// print("list_robot :\n");
// var_dump($session_husqvarna->list_robots());
// print("\n");

// print("control :\n");
// var_dump($session_husqvarna->control("191510477-191232162", 'START'));
// print("\n");

print("get_status :\n");
var_dump($session_husqvarna->get_status("191510477-191232162"));
print("\n");

// print("get_geofence :<pre>");
// var_dump($session_husqvarna->get_geofence("191510477-191232162"));
// print("</pre>");

// print("get_settings :\n");
// var_dump($session_husqvarna->get_settings("191510477-191232162"));
// print("\n");

print("get_statistics :\n");
var_dump($session_husqvarna->get_statistics_app("191510477-191232162"));
print("\n");

print("get_timers :\n");
var_dump($session_husqvarna->get_timers("191510477-191232162"));
print("\n");
var_dump($session_husqvarna->get_timers_app("191510477-191232162"));
print("\n");

$session_husqvarna->logout();
?>