<?php
require_once("husqvarna_map_api_amc.class.php");

$account = "xx.yy@zz.fr";
$passwd  = "xx";
$app_key = "xxxxxxxx-yyyy-zzzz-yyyy-xxxxxxxxxxxx";

$session_husqvarna = new husqvarna_map_api_amc();
$session_husqvarna->login($account, $passwd, $app_key, NULL);


// Login pour l'API AMC: authentification
// ======================================
$login = $session_husqvarna->amc_api_login();
var_dump($login);
print("\n");

// check login state
$sl = $session_husqvarna->state_login();
print("State login : ".$sl."\n");

print("list_robot :\n");
$ret = $session_husqvarna->list_robots();
var_dump($ret);
print("\n");

if ($ret["status"] != "OK") {
  print("Error : No mower found\n");
  // $session_husqvarna->logout();  
}

$mower_id = $ret["mower"][0]["id"];
print("Mower ID:".$mower_id."\n");

print("Mower status :\n");
$ret = $session_husqvarna->get_status($mower_id);
var_dump($ret);
print("\n");


$session_husqvarna->set_debug_api();

// Mower command
  // * Start + Duration
  // * ResumeSchedule
  // * Pause
  // * Park + Duration
  // * ParkUntilNextSchedule
  // * ParkUntilFurtherNotice
// print("Mower command:\n");
// $ret = $session_husqvarna->control($mower_id, "Start", 20);
// $ret = $session_husqvarna->control($mower_id, "ResumeSchedule");
// $ret = $session_husqvarna->control($mower_id, "Pause");
// $ret = $session_husqvarna->control($mower_id, "Park", 400);
// $ret = $session_husqvarna->control($mower_id, "ParkUntilNextSchedule");
// $ret = $session_husqvarna->control($mower_id, "ParkUntilFurtherNotice");
// var_dump($ret);
// print("\n");

// Statistics
// ==========
print("Login statistics\n");
$session_husqvarna->login_app($account, $passwd);

print("get_statistics :\n");
var_dump($session_husqvarna->get_statistics_app($mower_id));
print("\n");

$session_husqvarna->logout_app();
?>