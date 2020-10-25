<?php
require_once("husqvarna_api.class.php");
$account = "xx@yyy.com";
$passwd = "ABCDEFGH";
$session_husqvarna = new husqvarna_api();
$session_husqvarna->login($account, $passwd);
print("list_robot :<pre>");
var_dump($session_husqvarna->list_robots());
print("</pre>");
echo "<p>";
print("control :<pre>");
var_dump($session_husqvarna->control("1708XXXX-17013YYYY", 'START'));
print("</pre>");
print("get_status :<pre>");
var_dump($session_husqvarna->get_status("1708XXXX-17013YYYY"));
print("</pre>");
print("get_status :<pre>");
var_dump($session_husqvarna->get_geofence("170811841-170130242"));
print("</pre>");
$session_husqvarna->logout();
?>