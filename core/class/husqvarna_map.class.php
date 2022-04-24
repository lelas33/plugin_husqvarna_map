<?php

/* This file is part of Jeedom.
 *
 * Jeedom is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Jeedom is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
 */

/* * ***************************Includes********************************* */
require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';
require_once dirname(__FILE__) . '/../../3rdparty/husqvarna_map_api_amc.class.php';

define("MOWER_LOG_FILE", "/../../data/mower_log.txt");
define("MOWER_IMG_FILE", "/../../data/maison.png");
const DAY_NAMES = ["dim","lun","mar","mer","jeu","ven","sam"];
define("MOWER_LOGD_FILE", "/../../data/mower_log2.txt");

// etats du mode de planification
const MDPLN_IDLE = "Repos";
const MDPLN_ACT1_T = "Actif plage 1:Tonte";
const MDPLN_ACT1_C = "Actif plage 1:Charge";
const MDPLN_ACT2_T = "Actif plage 2:Tonte";
const MDPLN_ACT2_C = "Actif plage 2:Charge";
const MDPLN_WPARKED = "Attente retour base";
// Seuil sur la quantité de pluie dans les 15 mn pour la condition de retour: chiffre entre 3 et 12 (3 pas de pluie, 12 pluie forte sur les 3x5mn)
const METEO_SEUIL_PLUIE_RETOUR = 6;   // retour du robot si pluie > à ce seuil
// Seuil sur la quantité de pluie dans les 60 mn: chiffre entre 12 et 48 (12 pas de pluie, 48 pluie forte sur les 12x5mn)
const METEO_SEUIL_PLUIE_DEPART = 18;  // non départ du robot si pluie > à ce seuil

// ==============================
// Classe du plugin Husqvarna MAP
// ==============================
class husqvarna_map extends eqLogic {
    /*     * *************************Attributs****************************** */
    /*     * ***********************Methode static*************************** */
    // Detection automatique des robots mower declare sur le compte Husqwarna
    public static function force_detect_mowers() {
      // Initialisation de la connexion
      log::add('husqvarna_map','info','force_detect_mowers');
      if (config::byKey('account', 'husqvarna_map') != "" && config::byKey('password', 'husqvarna_map') != "" && config::byKey('application_key', 'husqvarna_map') != "" ) {
        // Init API Husqvarana
        $session_husqvarna = new husqvarna_map_api_amc();
        $session_husqvarna->login(config::byKey('account', 'husqvarna_map'), config::byKey('password', 'husqvarna_map'), config::byKey('application_key', 'husqvarna_map'), NULL);
        // Login a l'API Husqvarana
        $login = $session_husqvarna->amc_api_login();
        if ($login["status"] == "OK") {
          $list_robot = $session_husqvarna->list_robots();
          if ($list_robot["status"] == "OK") {
            foreach ($list_robot["mower"] as $id => $data) {
              $mower_id = $data["id"];
              log::add('husqvarna_map','debug','Find mower : '.$mower_id);
              if (!is_object(self::byLogicalId($mower_id, 'husqvarna_map'))) {
                log::add('husqvarna_map','info','Creation husqvarna : '.$mower_id.' ('.$data["model"].')');
                $eqLogic = new husqvarna_map();
                $eqLogic->setLogicalId($mower_id);
                $eqLogic->setName($data["serialnumber"]);
                $eqLogic->setConfiguration("equip_model", $data["model"]);
                $eqLogic->setConfiguration("equip_sn", $data["serialnumber"]);
                $eqLogic->setEqType_name('husqvarna_map');
                $eqLogic->setIsEnable(1);
                $eqLogic->setIsVisible(1);
                $eqLogic->save();
              }
              else {
                log::add('husqvarna_map','info',"Le robot (".$data["serialnumber"].") existe déjà dans Jeedom");
              }
            }
          }
          else {
            log::add('husqvarna_map','info',"Aucun robot associé au compte Husqvarna");
          }
        }
        else {
          log::add('husqvarna_map','info',"Erreur de connexion à l'API Husqvarna AMC");
        }
      }
    }
    public function postInsert()
    {
        $this->postSave();
    }
    
    public function preSave() {
      // recupation des infos de taille de l'image de localisation => Ajoute 2 valeurs de configuration à l'équipement
      $img_fn = dirname(__FILE__).MOWER_IMG_FILE;
      list($width, $height, $type, $attr) = getimagesize($img_fn);
      log::add('husqvarna_map','debug','preSave:img_info='.$width." / ".$height." / ".$type." / ".$attr);
      $this->setConfiguration('img_loc_width', $width);
      $this->setConfiguration('img_loc_height', $height);
    }

    private function getListeDefaultCommandes()
    {
        return array( "batteryPercent"     => array('Batterie',               'info',  'numeric', "%",   0, 1, "GENERIC_INFO",   'core::badge', 'core::badge', ''),
                      "connected"          => array('Connecté',               'info',  'binary',   "",   0, 1, "GENERIC_INFO",   'husqvarna_map::connecte', 'husqvarna_map::connecte', ''),
                      "mode"               => array('Mode',                   'info',  'string',   "",   0, 1, "GENERIC_INFO",   'core::badge', 'core::badge', ''),
                      "activity"           => array('Activité',               'info',  'string',   "",   0, 1, "GENERIC_INFO",   'core::badge', 'core::badge', ''),
                      "state"              => array('Etat courant',           'info',  'string',   "",   0, 1, "GENERIC_INFO",   'core::badge', 'core::badge', ''),
                      "state_visual"       => array('Statut visuel',          'info',  'numeric',  "",   0, 1, "GENERIC_INFO",   'husqvarna_map::state_visual', 'husqvarna_map::state_visual', ''),
                      "errorCode"          => array('Code erreur',            'info',  'numeric',  "",   0, 1, "GENERIC_INFO",   'core::badge', 'core::badge', ''),
                      "errorStatus"        => array('Statut erreur',          'info',  'string',   "",   0, 0, "GENERIC_INFO",   'core::badge', 'core::badge', ''),
                      "errorTS"            => array('Date erreur',            'info',  'string',   "",   0, 0, "GENERIC_INFO",   'core::badge', 'core::badge', ''),
                      "commande"           => array('Commande',               'action','select',   "",   0, 0, "GENERIC_ACTION", '',            '',            'Start|Départ pour durée;Pause|Pause;ResumeSchedule|Repartir;Park|Retour base pour durée;ParkUntilNextSchedule|Retour base=>timer suivant;ParkUntilFurtherNotice|Retour base=>départ manuel'),
                      "nextStartTS"        => array('Heure prochain départ',  'info',  'string',   "",   0, 0, "GENERIC_INFO",   'core::badge', 'core::badge', ''),
                      "planning_en"        => array('Planification cmd',      'action','other',    "",   0, 0, "GENERIC_ACTION", 'husqvarna_map::on_off', 'husqvarna_map::on_off', ''),
                      "planning_activ"     => array('Planification',          'info',  'binary',   "",   0, 0, "GENERIC_INFO",   'core::alert', 'core::alert', ''),
                      "planning_state"     => array('Etat planification',     'info',  'string',   "",   0, 0, "GENERIC_INFO",   'core::badge', 'core::badge', ''),
                      "planning_nbcy_tot"  => array('Nombre de cycles total', 'info',  'numeric',  "",   0, 0, "GENERIC_INFO",   'core::line',  'core::line',  ''),
                      "planning_nbcy_z1"   => array('Nombre de cycles zone1', 'info',  'numeric',  "",   0, 0, "GENERIC_INFO",   'core::line',  'core::line',  ''),
                      "meteo_en"           => array('Météo cmd',              'action','other',    "",   0, 0, "GENERIC_ACTION", 'husqvarna_map::on_off', 'husqvarna_map::on_off', ''),
                      "meteo_activ"        => array('Météo',                  'info',  'binary',   "",   0, 0, "GENERIC_INFO",   'core::alert', 'core::alert', ''),
                      "lastLocations"      => array('Position GPS',           'info',  'string',   "",   0, 0, "GENERIC_INFO",   'husqvarna_map::maps_husqvarna', 'husqvarna_map::maps_husqvarna', ''),
                      "gps_posx"           => array('GPS position X',         'info',  'numeric',  "",   0, 0, "GENERIC_INFO",   'core::line', 'core::line', ''),
                      "gps_posy"           => array('GPS position Y',         'info',  'numeric',  "",   0, 0, "GENERIC_INFO",   'core::line', 'core::line', ''),
                      "stat_tps_running"   => array("Temps fonctionnement",   'info',  'numeric', "h",   0, 1, "GENERIC_INFO",   'core::tile', 'core::tile', ''),
                      "stat_tps_cutting"   => array("Temps tonte",            'info',  'numeric', "h",   0, 1, "GENERIC_INFO",   'core::tile', 'core::tile', ''),
                      "stat_tps_charging"  => array("Temps chargement",       'info',  'numeric', "h",   0, 1, "GENERIC_INFO",   'core::tile', 'core::tile', ''),
                      "stat_tps_searching" => array("Temps recherche",        'info',  'numeric', "h",   0, 1, "GENERIC_INFO",   'core::tile', 'core::tile', ''),
                      "stat_nb_collision"  => array("Nombre collisions",      'info',  'numeric',  "",   0, 1, "GENERIC_INFO",   'core::tile', 'core::tile', ''),
                      "stat_nb_charging"   => array("Nombre cycle charge",    'info',  'numeric',  "",   0, 1, "GENERIC_INFO",   'core::tile', 'core::tile', '')
        );
    }

    //public function postSave()
    public function postSave()
    {
        foreach( $this->getListeDefaultCommandes() as $id => $data) {
            list($name, $type, $subtype, $unit, $invertBinary, $hist, $generic_type, $template_dashboard, $template_mobile, $listValue) = $data;
            $cmd = $this->getCmd(null, $id);
            if ( ! is_object($cmd) ) {
                $cmd = new husqvarna_mapCmd();
                $cmd->setName($name);
                $cmd->setEqLogic_id($this->getId());
                $cmd->setType($type);
                $cmd->setSubType($subtype);
                $cmd->setUnite($unit);
                $cmd->setLogicalId($id);
                if ($listValue != "") {
                  $cmd->setConfiguration('listValue', $listValue);
                }
                $cmd->setDisplay('invertBinary',$invertBinary);
                $cmd->setDisplay('generic_type', $generic_type);
                $cmd->setTemplate('dashboard', $template_dashboard);
                $cmd->setTemplate('mobile', $template_mobile);
                if ((strpos($id, '_nbcy')!== false) or (strpos($id, '_activ')!== false) or (strpos($id, 'gps_pos')!== false)) {
                  $cmd->setIsVisible(0);
                }
                if (strpos($id, '_en')!== false) {
                  $cmd->setDisplay('parameters', array("type"=>"mode","largeurDesktop"=>"60","largeurMobile"=>"30"));
                }
                // historic
                $cmd->setIsHistorized($hist);
                $cmd->save();
            }
            else {
                $cmd->setType($type);
                $cmd->setSubType($subtype);
                $cmd->setUnite($unit);
                $cmd->setDisplay('invertBinary',$invertBinary);
                $cmd->setDisplay('generic_type', $generic_type);
                $cmd->setTemplate('dashboard', $template_dashboard);
                $cmd->setTemplate('mobile', $template_mobile);
                $cmd->setIsHistorized($hist);
                if ($listValue != "") {
                  $cmd->setConfiguration('listValue', $listValue);
                }
                $cmd->save();
            }
        }
      
      // ajout de la commande refresh data
      $refresh = $this->getCmd(null, 'refresh');
      if (!is_object($refresh)) {
        $refresh = new husqvarna_mapCmd();
        $refresh->setName(__('Rafraichir', __FILE__));
      }
      $refresh->setEqLogic_id($this->getId());
      $refresh->setLogicalId('refresh');
      $refresh->setType('action');
      $refresh->setSubType('other');
      $refresh->save();
      // Couplage des commandes et info "planning_en" et "meteo_en"
      $cmd_act = $this->getCmd(null, 'planning_en');
      $cmd_inf = $this->getCmd(null, 'planning_activ');
      if ((is_object($cmd_act)) and (is_object($cmd_inf))) {
        $cmd_act->setValue($cmd_inf->getid());
        $cmd_act->save();
      }
      $cmd_act = $this->getCmd(null, 'meteo_en');
      $cmd_inf = $this->getCmd(null, 'meteo_activ');
      if ((is_object($cmd_act)) and (is_object($cmd_inf))) {
        $cmd_act->setValue($cmd_inf->getid());
        $cmd_act->save();
      }
      
    }

    public function preRemove() {
    }

    // =====================================================
    // Fonction appelée au rythme de 1 mn par le cron jeedom
    // =====================================================
    public static function pull($rfh = false) {
        // log::add('husqvarna_map','debug','Funcion pull');
        if ( config::byKey('account', 'husqvarna_map') != "" && config::byKey('password', 'husqvarna_map') != "" && config::byKey('application_key', 'husqvarna_map') != "" ) {
            foreach (self::byType('husqvarna_map') as $eqLogic) {
              if ($eqLogic->getIsEnable())
                $eqLogic->scan($rfh);
            }
        }
    }

    // Fonction de configuration de la zone de tonte (1 ou 2)
    public function set_zone($zone, $nb_cyc, $nb_z1) {
      if ($zone == 1) {
        $cmd_zn = cmd::byId(str_replace('#', '', $this->getConfiguration('cmd_set_zone_1')));
        $ret_zn = 1;
      }
      elseif ($zone == 2) {
        $cmd_zn = cmd::byId(str_replace('#', '', $this->getConfiguration('cmd_set_zone_2')));
        $ret_zn = 2;
      }
      elseif ($zone == 3) {
        // defini la zone a tondre selon statistique courante
        $ratio_z1 = ($nb_cyc == 0) ? 0 : ($nb_z1 / $nb_cyc);
        if ($ratio_z1 <= (intval($this->getConfiguration('zone1_ratio'))/100.0)) {
          $cmd_zn = cmd::byId(str_replace('#', '', $this->getConfiguration('cmd_set_zone_1')));
          $ret_zn = 1;
        }
        else {
          $cmd_zn = cmd::byId(str_replace('#', '', $this->getConfiguration('cmd_set_zone_2')));
          $ret_zn = 2;
        }
      }
      // Execution de la commande choisie
      if (!is_object($cmd_zn)) {
        throw new Exception(__('Impossible de trouver la commande Set Zone', __FILE__));
      }
      $cmd_zn->execCmd();
      log::add('husqvarna_map','debug',"Activation de la zone: (".$cmd_zn->getHumanName().")");
      return $ret_zn;
    }
    
    // Gestion periodique d'un robot (MAJ statut, planification)
    public function scan($rfh = false) {

        $minute = intval(date("i"));
        $heure  = intval(date("G"));
        $cur_hm = $heure*60 + $minute;

        if ((($minute %5) == 0) || ($rfh == true)) {
          // MAJ du statut robot uniquement toutes les 5 mn. (Quota de 10000 request par mois => limitation API AMC)
          $cmd_connected = $this->getCmd(null, "connected");
          
          // Login a l'API Husqvarna AMC
          $last_login = $cmd_connected->getConfiguration('save_auth');
          if ((!isset($last_login)) || ($last_login == "") || ($rfh == 1))
            $last_login = NULL;
          $session_husqvarna = new husqvarna_map_api_amc();
          $session_husqvarna->login(config::byKey('account', 'husqvarna_map'), config::byKey('password', 'husqvarna_map'), config::byKey('application_key', 'husqvarna_map'), $last_login);
          
          if ($last_login == NULL) {
            $login = $session_husqvarna->amc_api_login();   // Authentification
            if ($login["status"] == "KO") {
              log::add('husqvarna_map','error',"Erreur Login API Husqvarna AMC");
              $login = NULL;
              $cmd_connected->setConfiguration ('save_auth', $login);   // revocation token
              $cmd_connected->save();
              return;  // Erreur de login API Husqvarna AMC
            }
            $cmd_connected->setConfiguration ('save_auth', $login);
            $cmd_connected->save();
            log::add('husqvarna_map','info',"Pas de session en cours => New login");
          }
          else if ($session_husqvarna->state_login() == 0) {
            $login = $session_husqvarna->amc_api_login();   // Authentification
            if ($login["status"] == "KO") {
              log::add('husqvarna_map','error',"Erreur Login API Husqvarna AMC");
              $login = NULL;
              $cmd_connected->setConfiguration ('save_auth', $login);   // revocation token
              $cmd_connected->save();
              return;  // Erreur de login API Husqvarna AMC
            }
            $cmd_connected->setConfiguration ('save_auth', $login);
            $cmd_connected->save();
            log::add('husqvarna_map','info',"Session expirée => New login");
          }
          
          // mise à jour des infos du plugin et historique GPS
          // =================================================
          $mower_id = $this->getLogicalId();
          $mower_ret = $session_husqvarna->get_status($mower_id);
          if ($mower_ret["status"] != "OK")
            return;
          log::add('husqvarna_map','debug','MAJ Statut du robot');
          // Infos "battery"
          $cmd = $this->getCmd(null, "batteryPercent");
          $battery = intval($mower_ret["battery"]->batteryPercent);
          $cmd->event($battery);

          // Infos "mower" (current state)
          $cmd = $this->getCmd(null, "mode");
          $mower_mode = $mower_ret["mower"]->mode;
          $cmd->event($mower_mode);
          $cmd = $this->getCmd(null, "activity");
          $mower_activity = $mower_ret["mower"]->activity;
          $cmd->event($mower_activity);
          $cmd = $this->getCmd(null, "state");
          $mower_state = $mower_ret["mower"]->state;
          $cmd->event($mower_state);
          $cmd = $this->getCmd(null, "errorCode");
          $prev_error_code = $cmd->execCmd();
          $error_code = intval($mower_ret["mower"]->errorCode);
          $cmd->event($error_code);
          // Update corresponding error message
          $cmd_sts = $this->getCmd(null, "errorStatus");
          $cmd_ts  = $this->getCmd(null, "errorTS");
          $error_status = "Pas d'erreur en cours";
          $localTimeStamp = "--";
          if ($error_code != 0) {
            $error_status = $session_husqvarna->get_error_code($error_code);
            $err_ts = intval($mower_ret["mower"]->errorCodeTimestamp)/1000;
            $localTimeStamp = date('d M Y H:i', $err_ts);   // Date:Time erreur => Time conversion
            if ($prev_error_code == 0) {
              log::add('husqvarna_map','error',"Erreur robot: code=".$error_code." / Erreur = ".$error_status." / Date = ".$localTimeStamp);
            }
          }
          $cmd_sts->event($error_status);
          $cmd_ts->event($localTimeStamp );
          
          // Next start date => Time conversion
          $cmd = $this->getCmd(null, "nextStartTS");
          $next_start_ts = $mower_ret["planner"]->nextStartTimestamp;
          $offsetTimeStamp = date("Z");
          $localTimeStamp = date('d M Y H:i', (intval($next_start_ts)/1000) - $offsetTimeStamp );
          if (intval($next_start_ts) == 0)
            $cmd->event("--");
          else
            $cmd->event($localTimeStamp);

          // Infos "metadata"
          $cmd_connected->event(intval($mower_ret["metadata"]->connected));

          // Infos Etat visuel
          $cmd = $this->getCmd(null, "state_visual");
          $state_visual = 1;
          if     ($mower_activity == AC_PARKED_IN_CS)      $state_visual = 1;
          elseif ($mower_activity == AC_CHARGING)          $state_visual = 2;
          elseif ($mower_activity == AC_MOWING)            $state_visual = 3;
          elseif ($mower_activity == AC_GOING_HOME)        $state_visual = 4;
          elseif ($mower_activity == AC_STOPPED_IN_GARDEN) $state_visual = 5;
          elseif (($mower_state == ST_STOPPED) or ($mower_state == ST_ERROR) or ($mower_state == ST_FATAL_ERROR) or ($mower_state == ST_ERROR_AT_POWER_UP)) $state_visual = 5;
          $cmd->event($state_visual);

          // Infos "positions"
          $cmd = $this->getCmd(null, "lastLocations");
          if ((($mower_state == ST_IN_OPERATION) && ($mower_activity != AC_PARKED_IN_CS)) || ($rfh == 1)) {  // GPS logging done if mode is not PARKED
            log::add('husqvarna_map','debug','MAJ Positions du robot');
            // compute GPS position for each point on image
            $map_tl = $this->getConfiguration('gps_tl');
            $map_br = $this->getConfiguration('gps_br');
            $map_wd_ratio = $this->getConfiguration('img_wdg_ratio');
            $map_wdm_ratio= $this->getConfiguration('img_wdgm_ratio');
            $map_wd = round($this->getConfiguration('img_loc_width'));
            $map_he = round($this->getConfiguration('img_loc_height'));
            log::add('husqvarna_map','debug',"Refresh DBG:image pos=".$map_tl." / ".$map_br);
            log::add('husqvarna_map','debug',"Refresh DBG:image size=".$map_wd." / ".$map_he);
            list($map_t, $map_l) = explode(",", $map_tl);
            list($map_b, $map_r) = explode(",", $map_br);
            $lat_height = $map_b - $map_t;
            $lon_width  = $map_r - $map_l;
            $gps_pos = $map_wd.",".$map_he.'/'.$map_wd_ratio.",".$map_wdm_ratio.'/';  // passe la taille de l'image au widget, ainsi que les ratios dashboard et mobile
            $gps_log_full = "";
            for ($i=0; $i<50; $i++) {
                $gps_lat = floatval($mower_ret["positions"][$i]->{"latitude"});
                $gps_lon = floatval($mower_ret["positions"][$i]->{"longitude"});
                $xpos = round($map_wd * ($gps_lon-$map_l)/$lon_width);
                $ypos = round($map_he * ($gps_lat-$map_t)/$lat_height);
                $gps_pos = $gps_pos.$xpos.",".$ypos.'/';
                if ($i < 49)
                  $gps_log_full = $gps_log_full.$gps_lat.",".$gps_lon.'/';
                else
                  $gps_log_full = $gps_log_full.$gps_lat.",".$gps_lon."\n";
                if ($i == 0) {
                  // state encoding for compatibility with previous log format
                  $mw_state = STS_UNKNOWN;
                  if     ($mower_activity == AC_LEAVING)    $mw_state = OK_LEAVING;
                  elseif ($mower_activity == AC_MOWING)     $mw_state = OK_CUTTING;
                  elseif ($mower_activity == AC_GOING_HOME) $mw_state = OK_SEARCHING;
                  elseif ($mower_activity == AC_CHARGING)   $mw_state = OK_CHARGING;
                  elseif (($mower_state == ST_ERROR) || ($mower_state == ST_FATAL_ERROR) || ($mower_state == ST_ERROR_AT_POWER_UP)) $mw_state = ERROR;
                  $gps_log_dt = time().",".$mw_state.",".$gps_lat.",".$gps_lon."\n";
                  $gps_posx = $xpos;
                  $gps_posy = $ypos;
                }
            }
            $log_fn = dirname(__FILE__).MOWER_LOGD_FILE;
            file_put_contents($log_fn, $gps_log_full, FILE_APPEND | LOCK_EX);

            // log::add('husqvarna_map','debug',"Refresh DBG:Gps_pos=".$gps_pos);
            $cmd->event($gps_pos);
            // Stores mower position on map
            $cmd = $this->getCmd(null, "gps_posx");
            $cmd->event($gps_posx);
            $cmd = $this->getCmd(null, "gps_posy");
            $cmd->event($gps_posy);
            // Log GPS position for statistics (if valid)
            if (($mower_state == ST_IN_OPERATION) && ($mower_activity != AC_PARKED_IN_CS)) {
              log::add('husqvarna_map','debug',"Refresh log recording Gps_dt=".$gps_log_dt);
              $log_fn = dirname(__FILE__).MOWER_LOG_FILE;
              file_put_contents($log_fn, $gps_log_dt, FILE_APPEND | LOCK_EX);
            }
          }
        }
        
        // mise à jour des infos de statistiques
        // =====================================
        if ((($heure == 0) && ($minute == 0)) || ($rfh == 1)) {  // Si minuit, ou demande refresh
          // Lopin API app
          $log_ok = $session_husqvarna->login_app(config::byKey('account', 'husqvarna_map'), config::byKey('password', 'husqvarna_map'));
          if ($log_ok) {
            log::add('husqvarna_map','debug',"MAJ statistiques");
            $stat = $session_husqvarna->get_statistics_app($mower_id);
            $totalRunningTime   = intval($stat->totalRunningTime);
            $totalCuttingTime   = intval($stat->totalCuttingTime);
            $totalChargingTime  = intval($stat->totalChargingTime);
            $totalSearchingTime = intval($stat->totalSearchingTime);

            $totalRunningTime_nbh   = round($totalRunningTime/3600);
            $totalCuttingTime_nbh   = round($totalCuttingTime/3600);
            $totalChargingTime_nbh  = round($totalChargingTime/3600);
            $totalSearchingTime_nbh = round($totalSearchingTime/3600);

            $cmd = $this->getCmd(null, "stat_tps_running");
            $cmd->event($totalRunningTime_nbh);
            $cmd = $this->getCmd(null, "stat_tps_cutting");
            $cmd->event($totalCuttingTime_nbh);
            $cmd = $this->getCmd(null, "stat_tps_charging");
            $cmd->event($totalChargingTime_nbh);
            $cmd = $this->getCmd(null, "stat_tps_searching");
            $cmd->event($totalSearchingTime_nbh);
            $cmd = $this->getCmd(null, "stat_nb_collision");
            $cmd->event($stat->numberOfCollisions);
            $cmd = $this->getCmd(null, "stat_nb_charging");
            $cmd->event($stat->numberOfChargingCycles);
            // Logout API app              
            $session_husqvarna->logout_app();              
          }
        }

        // Gestion de la planification du robot
        // ====================================
        // recuperation des parametres de planification
        $cmd = $this->getCmd(null, 'planning_activ');
        $pl_on = $cmd->execCmd();
        $mower_id = $this->getLogicalId();
        $cmd_connected = $this->getCmd(null, "connected");
        $last_login = $cmd_connected->getConfiguration('save_auth');
        // if ($pl_on == 1) {
          $planning_state_cmd = $this->getCmd(null, "planning_state");
          $pln_state = $planning_state_cmd->execCmd();
          log::add('husqvarna_map','debug',"MAJ Planification: planning_state=".$pln_state."/Heure courante:".$cur_hm);
          $cmd = $this->getCmd(null, 'meteo_activ');
          $pl_meteo = $cmd->execCmd();
          $multizone = $this->getConfiguration("enable_2_areas");
          if ($pl_meteo == 1) {
            // recuperation de la pluie dans les 15mn et dans l'heure
            $cmd_name = str_replace('#', '', $this->getConfiguration('info_pluie_5mn'));
            $info_pluie_5m  = cmd::byId($cmd_name);
            $info_pluie_10m = cmd::byId(str_replace('0-5', '5-10', $cmd_name));
            $info_pluie_15m = cmd::byId(str_replace('0-5', '10-15', $cmd_name));
            $info_pluie_1h  = cmd::byId(str_replace('#', '', $this->getConfiguration('info_pluie_1h')));
            if (!is_object($info_pluie_5m) or !is_object($info_pluie_10m) or !is_object($info_pluie_15m) or !is_object($info_pluie_1h)) {
              throw new Exception(__('Impossible de trouver les commandes Info pluie', __FILE__));
            }
            $pluie_15m = $info_pluie_5m->execCmd() + $info_pluie_10m->execCmd() + $info_pluie_15m->execCmd();
            $pluie_1h  = $info_pluie_1h->execCmd();
            log::add('husqvarna_map','debug',"Pluie dans les 15mn:".$pluie_15m." / 1h:".$pluie_1h);
          }

          // recuperation de la definition des plages horaires
          $day = DAY_NAMES[intval(date("w"))];
          $pl_start = $day."_ts1_begin";
          $pl_end   = $day."_ts1_end";
          $pl_zone  = $day."_ts1_zone";
          $pl_enable= $day."_en_ts1";
          $pl1_ts = $this->getConfiguration($pl_start);
          $pl1_te = $this->getConfiguration($pl_end);
          if (($pl1_ts) == "")
            $pl1_ts = 0;
          else {
            list($hr,$mn) = explode(":", $pl1_ts);
            $pl1_ts = intval($hr*60)+intval($mn);
          }
          if (($pl1_te) == "")
            $pl1_te = 0;
          else {
            list($hr,$mn) = explode(":", $pl1_te);
            $pl1_te = intval($hr*60)+intval($mn);
          }
          $pl1_zn = intval($this->getConfiguration($pl_zone));
          $pl1_en = intval($this->getConfiguration($pl_enable));
          log::add('husqvarna_map','debug',"Planing: plage1=".$pl1_ts."/".$pl1_te."/".$pl1_zn."/".$pl1_en);
          $pl_start = str_replace("1", "2", $pl_start);
          $pl_end   = str_replace("1", "2", $pl_end);
          $pl_zone  = str_replace("1", "2", $pl_zone);
          $pl_enable= str_replace("1", "2", $pl_enable);
          $pl2_ts = $this->getConfiguration($pl_start);
          $pl2_te = $this->getConfiguration($pl_end);
          if (($pl2_ts) == "")
            $pl2_ts = 0;
          else {
            list($hr,$mn) = explode(":", $pl2_ts);
            $pl2_ts = intval($hr*60)+intval($mn);
          }
          if (($pl2_te) == "")
            $pl2_te = 0;
          else {
            list($hr,$mn) = explode(":", $pl2_te);
            $pl2_te = intval($hr*60)+intval($mn);
          }
          $pl2_zn = intval($this->getConfiguration($pl_zone));
          $pl2_en = intval($this->getConfiguration($pl_enable));
          log::add('husqvarna_map','debug',"Planing: plage2=".$pl2_ts."/".$pl2_te."/".$pl2_zn."/".$pl2_en);
          // gestion de la panification du robot
          $nb_clycle_tot_cmd = $this->getCmd(null, "planning_nbcy_tot");
          $nb_clycle_tot = $nb_clycle_tot_cmd->execCmd();
          $nb_clycle_z1_cmd = $this->getCmd(null, "planning_nbcy_z1");
          $nb_clycle_z1 = $nb_clycle_z1_cmd->execCmd();
          $mode_changed = 0;
          $stat_changed = 0;
          if (($day == "lun") and ($cur_hm == 0)) {
            // Clear stat sur zone1 le lundi à 0h00
            $nb_clycle_tot = 0;
            $nb_clycle_z1 = 0;
            $stat_changed = 1;
          }
          if ($pln_state == "") {
            $pln_state = MDPLN_IDLE;
            $mode_changed = 1;
          }
          else {
            switch ($pln_state) {
              case MDPLN_IDLE: // mode repos (en attente d'une plage horaire active)  METEO_SEUIL_PLUIE_DEPART
                  if (($pl_on == 1) and ($pl1_en == 1) and ($cur_hm>=$pl1_ts) and ($cur_hm<$pl1_te) and
                     (($pl_meteo == 0) or (($pl_meteo == 1) and ($pluie_1h<=METEO_SEUIL_PLUIE_DEPART) and ($pluie_15m<METEO_SEUIL_PLUIE_RETOUR)))) {
                    $pln_state = MDPLN_ACT1_T;
                    $mode_changed = 1;
                    // Sélection de la zone choisie
                    if ($multizone == 1) {
                      $zone = $this->set_zone($pl1_zn, $nb_clycle_tot, $nb_clycle_z1);
                    }
                    // départ tondeuse sur plage horaire 1
                    // $order = $session_husqvarna->control($mower_id, CMD_START, 20*60);  // 20h maxi
                    $this->mower_command($mower_id, CMD_START, $last_login);

                    log::add('husqvarna_map','info',"Départ tonte sur plage horaire 1. (Ret=".$order->info["http_code"].")");
                    $nb_clycle_tot += 1;
                    if ($zone == 1) {
                      $nb_clycle_z1 += 1;
                    }
                    $stat_changed = 1;
                  }
                  elseif (($pl_on == 1) and ($pl2_en == 1) and ($cur_hm>=$pl2_ts) and ($cur_hm<$pl2_te) and 
                         (($pl_meteo == 0) or (($pl_meteo == 1) and ($pluie_1h<=METEO_SEUIL_PLUIE_DEPART) and ($pluie_15m<METEO_SEUIL_PLUIE_RETOUR)))) {
                    $pln_state = MDPLN_ACT2_T;
                    $mode_changed = 1;
                    // Sélection de la zone choisie
                    if ($multizone == 1) {
                      $zone = $this->set_zone($pl2_zn, $nb_clycle_tot, $nb_clycle_z1);
                    }
                    // départ tondeuse sur plage horaire 2
                    // $order = $session_husqvarna->control($mower_id, CMD_START, 20*60);  // 20h maxi
                    $this->mower_command($mower_id, CMD_START, $last_login);
                    log::add('husqvarna_map','info',"Départ tonte sur plage horaire 2. (Ret=".$order->info["http_code"].")");
                    $nb_clycle_tot += 1;
                    if ($zone == 1) {
                      $nb_clycle_z1 += 1;
                    }
                    $stat_changed = 1;
                  }
                  break;
              case MDPLN_ACT1_T: // Robot en action sur la plage horaire 1 (phase tonte)
                  if ((($pl1_en == 1) and ($cur_hm>$pl1_te)) or ($pl_on == 0) or (($pl_meteo == 1) and ($pluie_15m>=METEO_SEUIL_PLUIE_RETOUR))) {
                    $pln_state = MDPLN_WPARKED;
                    $mode_changed = 1;
                    // Park de la tondeuse
                    // $order = $session_husqvarna->control($mower_id, CMD_PARKUNTILFURTHERNOTICE);
                    $this->mower_command($mower_id, CMD_PARKUNTILFURTHERNOTICE, $last_login);

                    log::add('husqvarna_map','info',"Fin de tonte sur plage horaire 1. (Ret=".$order->info["http_code"].")");
                    if (($pl_meteo == 1) and ($pluie_15m>=METEO_SEUIL_PLUIE_RETOUR))
                      log::add('husqvarna_map','info',"... Retour à la base pour raison de pluie sur 15 minutes:".$pluie_15m);
                  }
                  elseif (($mower_activity == AC_CHARGING) and ($battery <= 50)) {
                    $pln_state = MDPLN_ACT1_C;
                    $mode_changed = 1;
                    if ($multizone == 1) {
                      $this->set_zone(1, 0, 0);  // Mise au repot du sélecteur de zone
                    }
                    log::add('husqvarna_map','info',"Phase de chargement sur la plage horaire 1.");
                  }
                  break;
              case MDPLN_ACT1_C: // Robot en action sur la plage horaire 1 (phase chargement)
                  if ($pl_on == 0) { // arret de la planification
                    // Park de la tondeuse
                    // $order = $session_husqvarna->control($mower_id, CMD_PARKUNTILFURTHERNOTICE);
                    $this->mower_command($mower_id, CMD_PARKUNTILFURTHERNOTICE, $last_login);
                    $pln_state = MDPLN_IDLE;
                    $mode_changed = 1;
                  }
                  elseif ($battery >= 80) {
                    $pln_state = MDPLN_ACT1_T;
                    $mode_changed = 1;
                    if ($multizone == 1) {
                      $zone = $this->set_zone($pl1_zn, $nb_clycle_tot, $nb_clycle_z1);
                    }
                    log::add('husqvarna_map','info',"Prochain départ de tonte de la plage horaire 1.");
                    $nb_clycle_tot += 1;
                    if ($zone == 1) {
                      $nb_clycle_z1 += 1;
                    }
                    $stat_changed = 1;
                  }
                  break;
              case MDPLN_ACT2_T: // Robot en action sur la plage horaire 2 (phase tonte)
                  if ((($pl2_en == 1) and ($cur_hm>$pl2_te)) or ($pl_on == 0) or (($pl_meteo == 1) and ($pluie_15m>=METEO_SEUIL_PLUIE_RETOUR))) {
                    $pln_state = MDPLN_WPARKED;
                    $mode_changed = 1;
                    // Park de la tondeuse
                    // $order = $session_husqvarna->control($mower_id, CMD_PARKUNTILFURTHERNOTICE);
                    $this->mower_command($mower_id, CMD_PARKUNTILFURTHERNOTICE, $last_login);
                    log::add('husqvarna_map','info',"Fin de tonte sur plage horaire 2. (Ret=".$order->info["http_code"].")");
                    if (($pl_meteo == 1) and ($pluie_15m>=METEO_SEUIL_PLUIE_RETOUR))
                      log::add('husqvarna_map','info',"... Retour à la base pour raison de pluie sur 15 minutes:".$pluie_15m);
                  }
                  elseif (($mower_activity == AC_CHARGING) and ($battery <= 50)) {
                    $pln_state = MDPLN_ACT2_C;
                    $mode_changed = 1;
                    if ($multizone == 1) {
                      $this->set_zone(1, 0, 0);  // Mise au repot du sélecteur de zone
                    }
                    log::add('husqvarna_map','info',"Phase de chargement sur la plage horaire 2.");
                  }
                  break;
              case MDPLN_ACT2_C: // Robot en action sur la plage horaire 2 (phase chargement)
                  if ($pl_on == 0) { // arret de la planification
                    // Park de la tondeuse
                    // $order = $session_husqvarna->control($mower_id, CMD_PARKUNTILFURTHERNOTICE);
                    $this->mower_command($mower_id, CMD_PARKUNTILFURTHERNOTICE, $last_login);
                    $pln_state = MDPLN_IDLE;
                    $mode_changed = 1;
                  }
                  elseif ($battery >= 80) {
                    $pln_state = MDPLN_ACT2_T;
                    $mode_changed = 1;
                    if ($multizone == 1) {
                      $zone = $this->set_zone($pl2_zn, $nb_clycle_tot, $nb_clycle_z1);
                    }
                    log::add('husqvarna_map','info',"Prochain départ de tonte de la plage horaire 2.");
                    $nb_clycle_tot += 1;
                    if ($zone == 1) {
                      $nb_clycle_z1 += 1;
                    }
                    $stat_changed = 1;
                  }
                  break;
              case MDPLN_WPARKED: // Attente retour base
                  if ($mower_activity == AC_PARKED_IN_CS) {
                    $pln_state = MDPLN_IDLE;
                    $mode_changed = 1;
                    if ($multizone == 1) {
                      $this->set_zone(1, 0, 0);  // Mise au repot du sélecteur de zone
                    }
                    log::add('husqvarna_map','info',"Robot rentré à la base. (Activity=".$mower_activity.")");
                  }
                  elseif (($minute %10) == 0) { // Rappel de la commande toutes les 10 ms
                    // Park de la tondeuse
                    // $order = $session_husqvarna->control($mower_id, CMD_PARKUNTILFURTHERNOTICE);
                    $this->mower_command($mower_id, CMD_PARKUNTILFURTHERNOTICE, $last_login);
                    log::add('husqvarna_map','info',"Rappel retour à la base");
                  }
                  elseif ($pl_on == 0) { // arret de la planification
                    // Park de la tondeuse
                    // $order = $session_husqvarna->control($mower_id, CMD_PARKUNTILFURTHERNOTICE);
                    $this->mower_command($mower_id, CMD_PARKUNTILFURTHERNOTICE, $last_login);
                    $pln_state = MDPLN_IDLE;
                    $mode_changed = 1;
                  }
                  break;
            }
          }
          if ($mode_changed == 1) {
            $planning_state_cmd->event($pln_state);              
          }
          if ($stat_changed == 1) {
            $nb_clycle_tot_cmd->event($nb_clycle_tot);
            $nb_clycle_z1_cmd->event($nb_clycle_z1);
          }
        // }

        //$session_husqvarna->logOut();
    }
    
    // Envoi d'une commande au robot
    public function mower_command($mower_id, $command, $last_login) {
      
      log::add('husqvarna_map','info',"Commande executé: ".$mower_id." => ".$command);

      // Parametre de duree pour les commandes Start et Park
      $duration = 20*60;   // 20*60 mn => TODO : ajouter un curseur dans le widget pour ajuster cette valeur
      
      // Login a l'API Husqvarna AMC
      if ((!isset($last_login)) || ($last_login == ""))
        $last_login = NULL;
      $session_husqvarna = new husqvarna_map_api_amc();
      $session_husqvarna->login(config::byKey('account', 'husqvarna_map'), config::byKey('password', 'husqvarna_map'), config::byKey('application_key', 'husqvarna_map'), $last_login);
      
      if (($last_login == NULL) || ($session_husqvarna->state_login() == 0)) {
        $login = $session_husqvarna->amc_api_login();   // Authentification
        if ($login["status"] == "KO") {
          log::add('husqvarna_map','error',"Erreur Login API Husqvarna AMC");
          return;  // Erreur de login API Husqvarna AMC
        }
        log::add('husqvarna_map','info',"Pas de session en cours ou Session expirée => New login");
      }
      log::add('husqvarna_map','info',"Commande:".$command." / mower_id:".$mower_id." / duration:".$duration);
      $ret = $session_husqvarna->control($mower_id, $command, $duration);
      if ($ret["info"]["http_code"] == "202")
        log::add('husqvarna_map','info',"Commande:".$command." traitée");
      else
        log::add('husqvarna_map','error',"Commande:".$command." non traitée");
    }
}

// =============================================
// Classe des commandes du plugin Husqvarna MAP
// =============================================
class husqvarna_mapCmd extends cmd 
{
    /* *************************Attributs****************************** */
    public function execute($_options = null) {
        if ($this->getLogicalId() == 'commande' && $_options['select'] != "") {
          $eqLogic = $this->getEqLogic();
          $cmd_connected = $eqLogic->getCmd(null, "connected");
          $last_login = $cmd_connected->getConfiguration('save_auth');
          $mower_id = $eqLogic->getLogicalId();
          $command  = $_options['select'];
          husqvarna_map::mower_command($mower_id, $command, $last_login);
        }
        elseif ( $this->getLogicalId() == 'refresh') {
          log::add('husqvarna_map','info',"Refresh data");
          husqvarna_map::pull(true);
        }
        elseif ( $this->getLogicalId() == 'planning_en') {
          $eqLogic = $this->getEqLogic();
          $cmd_ret = $eqLogic->getCmd(null, 'planning_activ');
          if (is_object($cmd_ret)) {
            $value = $cmd_ret->execCmd();
            $cmd_ret->setCollectDate('');
            $cmd_ret->event($value xor 1);
          }

        }
        elseif ( $this->getLogicalId() == 'meteo_en') {
          $eqLogic = $this->getEqLogic();
          $cmd_ret = $eqLogic->getCmd(null, 'meteo_activ');
          if (is_object($cmd_ret)) {
            $value = $cmd_ret->execCmd();
            $cmd_ret->setCollectDate('');
            $cmd_ret->event($value xor 1);
          }

        }
        
    }


    /*     * ***********************Methode static*************************** */


    /*     * *********************Methode d'instance************************* */

    /*     * **********************Getteur Setteur*************************** */
}
?>
