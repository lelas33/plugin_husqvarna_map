<?php

// Liste de constante à partager avec l'utilisation
define ('PARKED_TIMER',     0);
define ('OK_LEAVING',       1);
define ('OK_CUTTING',       2);
define ('PARKED_PARKED_SELECTED', 3);
define ('OK_SEARCHING',     4);
define ('OK_CHARGING',      5);
define ('PAUSED',           6);
define ('PARKED_AUTOTIMER', 7);
define ('COMPLETED_CUTTING_TODAY_AUTO', 8);
define ('OK_CUTTING_NOT_AUTO', 9);
define ('OFF_HATCH_OPEN',     10);
define ('OFF_HATCH_CLOSED',   11);
define ('ERROR',              12);
define ('EXECUTING_PARK',     13);
define ('EXECUTING_START',    14);
define ('EXECUTING_STOP',     15);
define ('STS_UNKNOWN',        99);

class husqvarna_map_api {

	protected $url_api_im =    'https://iam-api.dss.husqvarnagroup.net/api/v3/';
	protected $url_api_track = 'https://amc-api.dss.husqvarnagroup.net/v1/';
	protected $url_api_app   = 'https://amc-api.dss.husqvarnagroup.net/app/v1/';
	protected $username;
	protected $password;
	protected $token;
	protected $provider;

  // List of error codes
	protected $error_codes = [
   0 => "Aucun",
   1 => "Tondeuse en dehors zone de tonte",
   2 => "Pas de signal boucle",
   4 => "Problème capteur boucle avant",
   5 => "Problème capteur boucle arrière",
   6 => "Problème capteur de boucle",
   7 => "Problème capteur de boucle",
   8 => "Code PIN incorrect",
   9 => "Tondeuse coincée",
  10 => "Tondeuse à l'envers (sur le dos)",
  11 => "Batterie faible",
  12 => "Batterie vide",
  13 => "Robot bloqué (Erreur de propulsion)",
  15 => "Robot soulevé",
  16 => "Coincée dans station charge",
  17 => "Station de charge inaccessible",
  18 => "Problème capteur collision AR",
  19 => "Problème capteur collision AV",
  20 => "Moteur de roue droite bloqué",
  21 => "Moteur de roue gauche bloqué",
  22 => "Problème moteur de roue droite",
  23 => "Problème moteur de roue gauche",
  24 => "Problème moteur de coupe",
  25 => "Disque de coupe bloqué",
  26 => "Combinaison de sous dispositifs non valide",
  27 => "Réglages restaurés",
  28 => "Problème du circuit de mémoire",
  30 => "Problème de batterie",
  31 => "Problème bouton STOP",
  32 => "Problème de capteur d’inclinaison",
  33 => "Tondeuse inclinée",
  35 => "Moteur de roue droite surchargé",
  36 => "Moteur de roue gauche surchargé",
  37 => "Courant de charge trop élevé",
  38 => "Problème de communication entre la carte MMI et la carte électronique principale",
  42 => "Plage hauteur de coupe limitée",
  43 => "Ajustement hauteur coupe imprévu",
  44 => "Problème hauteur de coupe",
  45 => "Problème entraînement coupe",
  46 => "Plage hauteur de coupe limitée",
  47 => "Problème entraînement coupe",
  69 => "Arrêt manuel de l'interrupteur",
  74 => "En dehors de la zone de protection virtuelle",
  78 => "Défaut d’entrainement"
  ];
  
  
  // List of mower modes
  protected $state_codes = [
   PARKED_TIMER             => "PARKED_TIMER",
   OK_LEAVING               => "OK_LEAVING",
   OK_CUTTING               => "OK_CUTTING",
   PARKED_PARKED_SELECTED   => "PARKED_PARKED_SELECTED",
   OK_SEARCHING             => "OK_SEARCHING",
   OK_CHARGING              => "OK_CHARGING",
   PAUSED                   => "PAUSED",
   PARKED_AUTOTIMER         => "PARKED_AUTOTIMER",
   COMPLETED_CUTTING_TODAY_AUTO  => "COMPLETED_CUTTING_TODAY_AUTO",
   OK_CUTTING_NOT_AUTO      => "OK_CUTTING_NOT_AUTO",
   OFF_HATCH_OPEN           => "OFF_HATCH_OPEN",
   OFF_HATCH_CLOSED         => "OFF_HATCH_CLOSED_DISABLED",
   ERROR                    => "ERROR",
   EXECUTING_PARK           => "EXECUTING_PARK",
   EXECUTING_START          => "EXECUTING_START",
   EXECUTING_STOP           => "EXECUTING_STOP"
  ];


  function login($username, $password)
	{
    $this->username = $username;
    $this->password = $password;
		$fields["data"]["attributes"]["username"] = $this->username;
		$fields["data"]["attributes"]["password"] = $this->password;
		$fields["data"]["type"] = "token";
		$result = $this->post_api("token", $fields);
		if ( $result !== false )
		{
			$this->token = $result->data->id;
			$this->provider = $result->data->attributes->provider;
			return true;
		}
		return false;
	}

	private function get_headers($fields = null)
	{
		if ( isset($this->token) )
		{
			$generique_headers = array(
			   'Content-type: application/json',
			   'Accept: application/json',
				'Authorization: Bearer '.$this->token,
				'Authorization-Provider: '.$this->provider
			);
		}
		else
		{
			$generique_headers = array(
			   'Content-type: application/json',
			   'Accept: application/json'
			   );
		}
		if ( isset($fields) )
		{
			$custom_headers = array('Content-Length: '.strlen(json_encode ($fields)));
		}
		else
		{
			$custom_headers = array();
		}
		return array_merge($generique_headers, $custom_headers);
	}

	private function post_api($page, $fields = null)
	{
		$session = curl_init();

		curl_setopt($session, CURLOPT_URL, $this->url_api_im . $page);
		curl_setopt($session, CURLOPT_HTTPHEADER, $this->get_headers($fields));
		curl_setopt($session, CURLOPT_POST, true);
		curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
		if ( isset($fields) )
		{
			curl_setopt($session, CURLOPT_POSTFIELDS, json_encode ($fields));
		}
		$json = curl_exec($session);
		curl_close($session);
//		throw new Exception(__('La livebox ne repond pas a la demande de cookie.', __FILE__));
		return json_decode($json);
	}

	private function get_api($page, $fields = null)
	{
		$session = curl_init();

		curl_setopt($session, CURLOPT_URL, $this->url_api_track . $page);
		curl_setopt($session, CURLOPT_HTTPHEADER, $this->get_headers($fields));
		curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
		if ( isset($fields) )
		{
			curl_setopt($session, CURLOPT_POSTFIELDS, json_encode($fields));
		}
		$json = curl_exec($session);
		curl_close($session);
//		throw new Exception(__('La livebox ne repond pas a la demande de cookie.', __FILE__));
		return json_decode($json);
	}

	private function get_api_app($page, $fields = null)
	{
		$session = curl_init();

		curl_setopt($session, CURLOPT_URL, $this->url_api_app . $page);
		curl_setopt($session, CURLOPT_HTTPHEADER, $this->get_headers($fields));
		curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
		if ( isset($fields) )
		{
			curl_setopt($session, CURLOPT_POSTFIELDS, json_encode($fields));
		}
		$json = curl_exec($session);
		curl_close($session);
//		throw new Exception(__('La livebox ne repond pas a la demande de cookie.', __FILE__));
		return json_decode($json);
	}

	private function del_api($page)
	{
		$session = curl_init();

		curl_setopt($session, CURLOPT_URL, $this->url_api_im . $page);
		curl_setopt($session, CURLOPT_HTTPHEADER, $this->get_headers());
		curl_setopt($session, CURLOPT_CUSTOMREQUEST, "DELETE");
		curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
		$json = curl_exec($session);
		curl_close($session);
//		throw new Exception(__('La livebox ne repond pas a la demande de cookie.', __FILE__));
		return json_decode($json);
	}

  function logout()
	{
		$result = $this->del_api("token/".$this->token);
		if ( $result !== false )
		{
			unset($this->token);
			unset($this->provider);
			return true;
		}
		return false;
	}
	
	function list_robots()
	{
		$list_robot = array();
		foreach ($this->get_api("mowers") as $robot)
		{
			$list_robot[$robot->id] = $robot;
		}
		return $list_robot;
	}

	// get status of mower
	function get_status($mover_id)
	{		
		return $this->get_api("mowers/".$mover_id."/status");
	}

	// get geofence information
  function get_geofence($mover_id)
	{		
		return $this->get_api("mowers/".$mover_id."/geofence");
	}

	// get settings of mower
	function get_settings($mover_id)
	{		
		return $this->get_api("mowers/".$mover_id."/settings");
	}

	// get statistics of mower
	function get_statistics_app($mover_id)
	{		
		return $this->get_api_app("mowers/".$mover_id."/statistics");
	}

	// get timers of mower
	function get_timers($mover_id)
	{		
		return $this->get_api("mowers/".$mover_id."/timers");
	}
	function get_timers_app($mover_id)
	{		
		return $this->get_api_app("mowers/".$mover_id."/timers");
	}

  // Send a command to mower
	function control($mover_id, $command)
	{
		if ( in_array($command, array('PARK', 'STOP', 'START') ) )
		{
			return $this->get_api("mowers/".$mover_id."/control", array("action" => $command));
		}
	}
  
  // Return text for error code
  function get_error_code($code)
	{
		return $this->error_codes[$code];
	}
	
  // Encoding of mower state
  function get_state_code($state)
	{
		foreach($this->state_codes as $st_idx => $data)
        {
			if ($data == $state)
				return($st_idx);
		}
		return(99); // state unknown
	}

}
?>