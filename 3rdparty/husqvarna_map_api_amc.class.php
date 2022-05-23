<?php

// Liste de constante à partager avec l'utilisation
// State values for previous API : Kept for compatibility
define ('PARKED_TIMER',                 0);
define ('OK_LEAVING',                   1); // used for panel => activities = LEAVING
define ('OK_CUTTING',                   2); // used for panel => activities = MOWING
define ('PARKED_PARKED_SELECTED',       3);
define ('OK_SEARCHING',                 4); // used for panel => activities = GOING_HOME
define ('OK_CHARGING',                  5); // used for panel => activities = CHARGING
define ('PAUSED',                       6);
define ('PARKED_AUTOTIMER',             7);
define ('COMPLETED_CUTTING_TODAY_AUTO', 8);
define ('OK_CUTTING_NOT_AUTO',          9);
define ('OFF_HATCH_OPEN',              10);
define ('OFF_HATCH_CLOSED',            11);
define ('ERROR',                       12); // used for panel => states = ERROR
define ('EXECUTING_PARK',              13);
define ('EXECUTING_START',             14);
define ('EXECUTING_STOP',              15);
define ('STS_UNKNOWN',                 99);

// List of "modes" for Mower
define ('MD_MAIN_AREA',         'MAIN_AREA'        );    // Mower will mow until low battery. Go home and charge. Leave and continue mowing. Week schedule is used. Schedule can be overridden with forced park or forced mowing.
define ('MD_DEMO',              'DEMO'             );    // Same as main area, but shorter times. No blade operation.
define ('MD_SECONDARY_AREA',    'SECONDARY_AREA'   );    // Mower is in secondary area. Schedule is overridden with forced park or forced mowing. Mower will mow for request time or untill the battery runs out.
define ('MD_HOME',              'HOME'             );    // Mower goes home and parks forever. Week schedule is not used. Cannot be overridden with forced mowing.
define ('MD_UNKNOWN',           'UNKNOWN'          );    // Unknown mode

// List of "activities" for Mower
define ('AC_UNKNOWN',           'UNKNOWN'          );    // Unknown activity.
define ('AC_NOT_APPLICABLE',    'NOT_APPLICABLE'   );    // Manual start required in mower.
define ('AC_MOWING',            'MOWING'           );    // Mower is mowing lawn. If in demo mode the blades are not in operation.
define ('AC_GOING_HOME',        'GOING_HOME'       );    // Mower is going home to the charging station.
define ('AC_CHARGING',          'CHARGING'         );    // Mower is charging in station due to low battery.
define ('AC_LEAVING',           'LEAVING'          );    // Mower is leaving the charging station.
define ('AC_PARKED_IN_CS',      'PARKED_IN_CS'     );    // Mower is parked in charging station.
define ('AC_STOPPED_IN_GARDEN', 'STOPPED_IN_GARDEN');    // Mower has stopped. Needs manual action to resume.

// List of "states" for Mower
define ('ST_UNKNOWN',           'UNKNOWN'          );    // Unknown state.
define ('ST_NOT_APPLICABLE',    'NOT_APPLICABLE'   );    //
define ('ST_PAUSED',            'PAUSED'           );    // Mower has been paused by user.
define ('ST_IN_OPERATION',      'IN_OPERATION'     );    // See value in activity for status.
define ('ST_WAIT_UPDATING',     'WAIT_UPDATING'    );    // Mower is downloading new firmware.
define ('ST_WAIT_POWER_UP',     'WAIT_POWER_UP'    );    // Mower is performing power up tests.
define ('ST_RESTRICTED',        'RESTRICTED'       );    // Mower can currently not mow due to week calender, or override park.
define ('ST_OFF',               'OFF'              );    // Mower is turned off.
define ('ST_STOPPED',           'STOPPED'          );    // Mower is stopped requires manual action.
define ('ST_ERROR',             'ERROR'            );    // An error has occurred. Check errorCode. Mower requires manual action.
define ('ST_FATAL_ERROR',       'FATAL_ERROR'      );    // An error has occurred. Check errorCode. Mower requires manual action.
define ('ST_ERROR_AT_POWER_UP', 'ERROR_AT_POWER_UP');    // An error has occurred. Check errorCode. Mower requires manual action.

// List of commands for Mower
define ('CMD_START',                  'Start'                 );  // Mower start for mowing (+ duration)
define ('CMD_RESUMESCHEDULE',         'ResumeSchedule'        );  // Restart after pause
define ('CMD_PAUSE',                  'Pause'                 );  // Pause mower
define ('CMD_PARK',                   'Park'                  );  // Park mower (+ duration)
define ('CMD_PARKUNTILNEXTSCHEDULE',  'ParkUntilNextSchedule' );  // Park mower until next schedule (internal timer)
define ('CMD_PARKUNTILFURTHERNOTICE', 'ParkUntilFurtherNotice');  // Park mower until new start command


class husqvarna_map_api_amc {

  // For AMP API (Mower status and commands)
  protected $url_api_amc_auth = 'https://api.authentication.husqvarnagroup.dev/v1/';
	protected $url_api_amc      = 'https://api.amc.husqvarna.dev/v1/';
	protected $username;        // Account name
	protected $password;        // Account password
  protected $client_id;       // Application Key (to obtain on https://developer.husqvarnagroup.cloud/)
  protected $access_token = [];

  // For APP API (Statistics)
	protected $url_api_im =    'https://iam-api.dss.husqvarnagroup.net/api/v3/';
	// protected $url_api_track = 'https://amc-api.dss.husqvarnagroup.net/v1/';
	protected $url_api_app   = 'https://amc-api.dss.husqvarnagroup.net/app/v1/';
	protected $username_app;   // Account name
	protected $password_app;   // Account password
	protected $token_app;
	protected $provider_app;
  protected $debug_api;
  
  // List of error codes for Mower
	protected $error_codes = [
    0   => "Erreur inattendue",
    1   => "Hors zone de travail",
    2   => "Pas de signal de boucle",
    3   => "Mauvais signal de boucle",
    4   => "Problème de capteur de boucle, avant",
    5   => "Problème de capteur de boucle, arrière",
    6   => "Problème de capteur de boucle, gauche",
    7   => "Problème de capteur de boucle, droite",
    8   => "Code PIN erroné",
    9   => "Piégé",
    10  => "À l'envers",
    11  => "Batterie faible",
    12  => "Batterie vide",
    13  => "Pas d'entrainement",
    14  => "Tondeuse relevée",
    15  => "Levé",
    16  => "Coincé dans la borne de recharge",
    17  => "Borne de recharge bloquée",
    18  => "Problème de capteur de collision, arrière",
    19  => "Problème de capteur de collision, avant",
    20  => "Moteur de roue bloqué, droite",
    21  => "Moteur de roue bloqué, gauche",
    22  => "Problème de roue motrice droite",
    23  => "Problème de roue motrice gauche",
    24  => "Système de coupe bloqué",
    25  => "Système de coupe bloqué",
    26  => "Combinaison de sous-appareil non valide",
    27  => "Paramètres restaurés",
    28  => "Problème de circuit mémoire",
    29  => "Pente trop raide",
    30  => "Problème de système de charge",
    31  => "Problème bouton STOP",
    32  => "Problème de capteur d'inclinaison",
    33  => "Tondeuse inclinée",
    34  => "Coupe arrêtée - pente trop raide",
    35  => "Moteur de roue surchargé, droit",
    36  => "Moteur de roue surchargé, gauche",
    37  => "Courant de charge trop élevé",
    38  => "Problème électronique",
    39  => "Problème de moteur de coupe",
    40  => "Plage de hauteur de coupe limitée",
    41  => "Réglage de la hauteur de coupe inattendu",
    42  => "Plage de hauteur de coupe limitée",
    43  => "Problème de hauteur de coupe, roulez",
    44  => "Problème de hauteur de coupe, curr",
    45  => "Problème de hauteur de coupe, dir",
    46  => "Hauteur de coupe bloquée",
    47  => "Problème de hauteur de coupe",
    48  => "Pas de réponse du chargeur",
    49  => "Problème d'ultrasons",
    50  => "Guide 1 introuvable",
    51  => "Guide 2 introuvable",
    52  => "Guide 3 introuvable",
    53  => "Problème de navigation GPS",
    54  => "Signal GPS faible",
    55  => "Difficile de trouver sa maison",
    56  => "Étalonnage du guide effectué",
    57  => "L'étalonnage du guide a échoué",
    58  => "Problème de batterie temporaire",
    59  => "Problème de batterie temporaire",
    60  => "Problème de batterie temporaire",
    61  => "Problème de batterie temporaire",
    62  => "Problème de batterie temporaire",
    63  => "Problème de batterie temporaire",
    64  => "Problème de batterie temporaire",
    65  => "Problème de batterie temporaire",
    66  => "Problème de batterie",
    67  => "Problème de batterie",
    68  => "Problème de batterie temporaire",
    69  => "Alarme! Tondeuse éteinte",
    70  => "Alarme! Tondeuse arrêtée",
    71  => "Alarme! Tondeuse relevée",
    72  => "Alarme! Tondeuse inclinée",
    73  => "Alarme! Tondeuse en mouvement",
    74  => "Alarme! Clôture géographique extérieure",
    75  => "Connexion modifiée",
    76  => "Connexion NON modifiée",
    77  => "Carte com non disponible",
    78  => "Glissé - La tondeuse a glissé.Situation non résolue avec un motif en mouvement",
    79  => "Combinaison de batterie invalide - Combinaison invalide de différents types de batterie",
    80  => "Déséquilibre du système de coupe Avertissement",
    81  => "Fonction de sécurité défectueuse",
    82  => "Moteur de roue bloqué, arrière droit",
    83  => "Moteur de roue bloqué, arrière gauche",
    84  => "Problème de roue motrice arrière droite",
    85  => "Problème de roue motrice arrière gauche",
    86  => "Moteur de roue surchargé, arrière droit",
    87  => "Moteur de roue surchargé, arrière gauche",
    88  => "Problème de capteur angulaire",
    89  => "Configuration système invalide",
    90  => "Pas de courant dans la borne de recharge",
    91  => "Problème de cordon d'interrupteur",
    92  => "Zone de travail non valide",
    93  => "Pas de position précise des satellites",
    94  => "Problème de communication avec la station de référence",
    95  => "Capteur de pliage activé",
    96  => "Moteur de la brosse droite surchargé",
    97  => "Moteur de brosse gauche surchargé",
    98  => "Défaut du capteur à ultrasons 1",
    99  => "Défaut du capteur à ultrasons 2",
    100 => "Défaut du capteur à ultrasons 3",
    101 => "Défaut du capteur à ultrasons 4",
    102 => "Moteur d'entraînement de coupe 1 défectueux",
    103 => "Moteur d'entraînement de coupe 2 défectueux",
    104 => "Défaut du moteur d'entraînement de coupe 3",
    105 => "Défaut du capteur de levage",
    106 => "Défaut du capteur de collision",
    107 => "Défaut du capteur d'amarrage",
    108 => "Défaut du capteur du plateau de coupe repliable",
    109 => "Défaut du capteur de boucle",
    110 => "Erreur du capteur de collision",
    111 => "Pas de poste confirmé",
    112 => "Déséquilibre majeur du système de coupe",
    113 => "Zone de travail complexe",
    114 => "Courant de décharge trop élevé",
    115 => "Courant interne trop élevé",
    116 => "Perte de puissance de charge élevée",
    117 => "Perte de puissance interne élevée",
    118 => "Problème de système de charge",
    119 => "Problème de générateur de zone",
    120 => "Erreur de tension interne",
    121 => "Température interne élevée",
    122 => "Erreur CAN",
    123 => "Destination non joignable"
  ];


  // ==============================
  // General function : login
  // ==============================
  function login($username, $password, $app_key, $token)
	{
    $this->username     = $username;
    $this->password     = $password;
    $this->client_id    = $app_key;
    $this->access_token = $token;  // Etat des token des appels précédents
    $this->debug_api    = false;
	}

  // Check login state (Tokens still allowed)
  function state_login()
  {
    if (isset($this->access_token["access_token"]) && isset($this->access_token["access_token_ts"]) && isset($this->access_token["access_token_dur"])) {
      $ctime = time();
      // printf("login:ctime=".$ctime."\n");
      if (($ctime >= $this->access_token["access_token_ts"]) && ($ctime < ($this->access_token["access_token_ts"] + $this->access_token["access_token_dur"] - 15))) {
        return(1);  // no need for new login
      }
    }
    else
      return (0);
  }

  // Set debug mode
  function set_debug_api()
  {
    $this->debug_api = true;
  }


  // ============================================================
  // Functions dedicated to API Husqvarna AMC : Authentification
  // ============================================================
  // GET HTTP command : unsused
  
  // POST HTTP command
  private function post_api_amc_auth2($param, $fields = null)
  {
    $session = curl_init();
    $url = $this->url_api_amc_auth;
    curl_setopt($session, CURLOPT_URL, $url.$param);
    curl_setopt($session, CURLOPT_HTTPHEADER, array(
       'Content-Type: application/x-www-form-urlencoded',
       'Accept: application/json'));
    curl_setopt($session, CURLOPT_POST, true);
    curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
    if (isset($fields)) {
      curl_setopt($session, CURLOPT_POSTFIELDS, $fields);
    }
    $json = curl_exec($session);
    // Vérifie si une erreur survient
    if(curl_errno($session)) {
      $info = [];
      echo 'Erreur Curl : ' . curl_error($session);
    }
    else {
      $info = curl_getinfo($session);
    }
    curl_close($session);
//    throw new Exception(__('La livebox ne repond pas a la demande de cookie.', __FILE__));
    $ret_array["info"] = $info;
    $ret_array["result"] = json_decode($json);
    return $ret_array;
  }

  // =================================
  // Connection a l'API Husqvarna AMC
  // =================================
  // Login pour l'API: authentification
  function amc_api_login()
  {
    $form = "grant_type=password&client_id=".$this->client_id."&username=".urlencode($this->username)."&password=".urlencode($this->password);
    $param = "oauth2/token";
    $ret = $this->post_api_amc_auth2($param, $form);
    // var_dump($ret["info"]);
    // var_dump($ret["result"]);
    $this->access_token = [];
    if ($ret["info"]["http_code"] == "200") {
      $this->access_token["access_token"]     = $ret["result"]->access_token;
      $this->access_token["token_type"]       = $ret["result"]->token_type;
      $this->access_token["refresh_token"]    = $ret["result"]->refresh_token;
      $this->access_token["provider"]         = $ret["result"]->provider;
      $this->access_token["user_id"]          = $ret["result"]->user_id;
      $this->access_token["access_token_ts"]  = time();  // token consented on
      $this->access_token["access_token_dur"] = intval($ret["result"]->expires_in);
      $this->access_token["scope"]            = $ret["result"]->scope;
      $this->access_token["status"] = "OK";
    }
    else {
      $this->access_token["status"] = "KO";
    }
    return($this->access_token);  // new login performed
  }

  // ============================================================
  // Functions dedicated to API Husqvarna AMC : Automower access
  // ============================================================
  private function get_api_amc($param, $fields = null)
  {
    $session = curl_init();
    curl_setopt($session, CURLOPT_URL, $this->url_api_amc.$param);
    curl_setopt($session, CURLOPT_HTTPHEADER, array(
      'accept: application/vnd.api+json',
      'Authorization: Bearer '  .$this->access_token["access_token"],
      'X-Api-Key: '.$this->client_id,
      'Authorization-Provider: '.$this->access_token["provider"]));
    curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
    if (isset($fields)) {
      curl_setopt($session, CURLOPT_POSTFIELDS, json_encode ($fields));
    }
    $json = curl_exec($session);
    // Vérifie si une erreur survient
    if(curl_errno($session)) {
      $info = [];
      echo 'Erreur Curl : ' . curl_error($session);
    }
    else {
      $info = curl_getinfo($session);
    }
    curl_close($session);
    $ret_array["info"] = $info;
    $ret_array["result"] = json_decode($json);
    return $ret_array;
  }
  
  // POST HTTP command
  private function post_api_amc($param, $fields = null)
  {
    $session = curl_init();
    curl_setopt($session, CURLOPT_URL, $this->url_api_amc.$param);
    curl_setopt($session, CURLOPT_HTTPHEADER, array(
      'Content-Type: application/vnd.api+json',
      'Authorization: Bearer '  .$this->access_token["access_token"],
      'X-Api-Key: '.$this->client_id,
      'Authorization-Provider: '.$this->access_token["provider"]));
    curl_setopt($session, CURLOPT_POST, true);
    curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
    if (isset($fields)) {
      curl_setopt($session, CURLOPT_POSTFIELDS, json_encode ($fields));
    }
    $json = curl_exec($session);
    // Vérifie si une erreur survient
    if(curl_errno($session)) {
      $info = [];
      echo 'Erreur Curl : ' . curl_error($session);
    }
    else {
      $info = curl_getinfo($session);
    }
    curl_close($session);
//    throw new Exception(__('La livebox ne repond pas a la demande de cookie.', __FILE__));
    $ret_array["info"] = $info;
    $ret_array["result"] = json_decode($json);
    return $ret_array;
  }

	
  // =======================================
  // Access to mower functions using API AMC
  // ======================================= 
  // Return the list of type mower in items associated to the account
  // ================================================================
	function list_robots()
	{
    $res = $this->get_api_amc("mowers");
		$list_robot = $res["result"];

		$list_robot = [];
    $list_robot["status"] = "KO";
    $num_mower = 0;
    if (count($res["result"]) >= 1) {
      foreach ($res["result"]->data as $num => $data) {
        if ($data->type == "mower") {
          // First element is a mower => extract its informations
          $list_robot["mower"][$num_mower] = [];
          $list_robot["mower"][$num_mower]["id"]           = $data->id;
          $list_robot["mower"][$num_mower]["model"]        = $data->attributes->system->model;
          $list_robot["mower"][$num_mower]["serialnumber"] = $data->attributes->system->serialNumber;
          $num_mower = $num_mower + 1;
          $list_robot["status"] = "OK";
        }
      }
    }
		return $list_robot;
	}

  // Return the current status of mower
  // ==================================
	function get_status($mower_id)
	{		
    $res = $this->get_api_amc("mowers/".$mower_id);
    $ret = [];
    $ret["status"] = "KO";
    if ($res["info"]["http_code"] == 200) {
      $ret["status"]   = "OK";
		  $ret["system"]   = $res["result"]->data->attributes->system;
		  $ret["battery"]  = $res["result"]->data->attributes->battery;
		  $ret["mower"]    = $res["result"]->data->attributes->mower;
		  $ret["calendar"] = $res["result"]->data->attributes->calendar;
		  $ret["planner"]  = $res["result"]->data->attributes->planner;
		  $ret["planner"]  = $res["result"]->data->attributes->planner;
		  $ret["metadata"]  = $res["result"]->data->attributes->metadata;
		  $ret["positions"] = $res["result"]->data->attributes->positions;
		  $ret["settings"]  = $res["result"]->data->attributes->settings;
      
    }
    return $ret;
	}


  // Send a command to mower
  // Commands available are:
  // * Start + Duration
  // * ResumeSchedule
  // * Pause
  // * Park + Duration
  // * ParkUntilNextSchedule
  // * ParkUntilFurtherNotice
	function control($mover_id, $command, $duration = null)
	{
    if (in_array($command, array('Start', 'ResumeSchedule', 'Pause', 'Park', 'ParkUntilNextSchedule', 'ParkUntilFurtherNotice')) == 0)
      return;  // Commande non valide
    
    $params = [];
    $params["data"] = [];
    $params["data"]["type"] = $command;
    if (isset($duration)) {
      $params["data"]["attributes"]["duration"] = $duration;
    }
    if ($this->debug_api) {
      var_dump($params);
      print(json_encode ($params).'\n');
    }
    $ret = $this->post_api_amc("mowers/".$mover_id."/actions", $params);
    return $ret;
	}
  
  // Return text for error code
  function get_error_code($code)
	{
		return $this->error_codes[$code];
	}
	
  // Encoding of mower state
  // function get_state_code($state)
	// {
		// foreach($this->state_codes as $st_idx => $data) {
			// if ($data == $state)
				// return($st_idx);
		// }
		// return(99); // state unknown
	// }
  


  // ============================================================
  // Functions dedicated to API APP : used for statistics
  // ============================================================
  // Login
  function login_app($username, $password)
	{
    $this->username_app = $username;
    $this->password_app = $password;
		$fields["data"]["attributes"]["username"] = $this->username_app;
		$fields["data"]["attributes"]["password"] = $this->password_app;
		$fields["data"]["type"] = "token";
		$result = $this->post_api_app("token", $fields);
		if ($result !== false) {
			$this->token_app    = $result->data->id;
			$this->provider_app = $result->data->attributes->provider;
      print("Login OK\n");
			return true;
		}
    print("Login KO\n");
		return false;
	}
  
	private function get_headers_app($fields = null)
	{
		if (isset($this->token_app)) {
			$generique_headers = array(
			   'Content-type: application/json',
			   'Accept: application/json',
				 'Authorization: Bearer '.$this->token_app,
				 'Authorization-Provider: '.$this->provider_app);
		}
		else {
			$generique_headers = array(
			   'Content-type: application/json',
			   'Accept: application/json');
		}
		if (isset($fields))	{
			$custom_headers = array('Content-Length: '.strlen(json_encode ($fields)));
		}
		else {
			$custom_headers = array();
		}
		return array_merge($generique_headers, $custom_headers);
	}

	private function post_api_app($page, $fields = null)
	{
		$session = curl_init();
		curl_setopt($session, CURLOPT_URL, $this->url_api_im.$page);
		curl_setopt($session, CURLOPT_HTTPHEADER, $this->get_headers_app($fields));
		curl_setopt($session, CURLOPT_POST, true);
		curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
		if (isset($fields)) {
			curl_setopt($session, CURLOPT_POSTFIELDS, json_encode ($fields));
		}
		$json = curl_exec($session);
		curl_close($session);
		return json_decode($json);
	}

  // GET HTTP command
	private function get_api_app($page, $fields = null)
	{
		$session = curl_init();
		curl_setopt($session, CURLOPT_URL, $this->url_api_app.$page);
		curl_setopt($session, CURLOPT_HTTPHEADER, $this->get_headers_app($fields));
		curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
		if (isset($fields))	{
			curl_setopt($session, CURLOPT_POSTFIELDS, json_encode($fields));
		}
		$json = curl_exec($session);
		curl_close($session);
		return json_decode($json);
	}

	private function del_api_app($page)
	{
		$session = curl_init();
		curl_setopt($session, CURLOPT_URL, $this->url_api_im.$page);
		curl_setopt($session, CURLOPT_HTTPHEADER, $this->get_headers_app());
		curl_setopt($session, CURLOPT_CUSTOMREQUEST, "DELETE");
		curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
		$json = curl_exec($session);
		curl_close($session);
		return json_decode($json);
	}

  function logout_app()
	{
		$result = $this->del_api_app("token/".$this->token_app);
		if ($result !== false) {
			unset($this->token_app);
			unset($this->provider_app);
			return true;
		}
		return false;
	}

	// get statistics of mower
  // =======================
	function get_statistics_app($mover_id)
	{		
		return $this->get_api_app("mowers/".$mover_id."/statistics");
	}


}
?>