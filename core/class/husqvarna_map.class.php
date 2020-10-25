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
require_once dirname(__FILE__) . '/../../3rdparty/husqvarna_map_api.class.php';

define("MOWER_LOG_FILE", "/../../data/mower_log.txt");
define("MOWER_IMG_FILE", "/../../ressources/maison.png");
const DAY_NAMES = ["dim","lun","mar","mer","jeu","ven","sam"];

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

class husqvarna_map extends eqLogic {
    /*     * *************************Attributs****************************** */
    /*     * ***********************Methode static*************************** */
    //public static function postConfig_password() {
    //    husqvarna_map::force_detect_movers();
    //}

    public static function force_detect_movers() {
        // Initialisation de la connexion
        log::add('husqvarna_map','info','force_detect_movers');
        if ( config::byKey('account', 'husqvarna_map') != "" || config::byKey('password', 'husqvarna_map') != "" )
        {
            $session_husqvarna = new husqvarna_map_api();
            $session_husqvarna->login(config::byKey('account', 'husqvarna_map'), config::byKey('password', 'husqvarna_map'));
            foreach ($session_husqvarna->list_robots() as $id => $data)
            {
                log::add('husqvarna_map','debug','Find mover : '.$id);
                if ( ! is_object(self::byLogicalId($id, 'husqvarna_map')) ) {
                    log::add('husqvarna_map','info','Creation husqvarna : '.$id.' ('.$data->name.')');
                    $eqLogic = new husqvarna_map();
                    $eqLogic->setLogicalId($id);
                    $eqLogic->setName($data->name);
                    $eqLogic->setEqType_name('husqvarna_map');
                    $eqLogic->setIsEnable(1);
                    $eqLogic->setIsVisible(1);
                    $eqLogic->save();
                }
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
        return array( "batteryPercent"     => array('Batterie',               'h', 'info',  'numeric', "%", 0, "GENERIC_INFO",   'core::badge', 'core::badge', ''),
                      "connected"          => array('Connecté',               'h', 'info',  'binary',   "", 0, "GENERIC_INFO",   'core::alert', 'core::alert', ''),
                      "lastErrorCode"      => array('Code erreur',            'h', 'info',  'numeric',  "", 0, "GENERIC_INFO",   'core::badge', 'core::badge', ''),
                      "commande"           => array('Commande',               'h', 'action','select',   "", 0, "GENERIC_ACTION", '',      '',      'START|'.__('Démarrer',__FILE__).';STOP|'.__('Arrêter',__FILE__).';PARK|'.__('Ranger',__FILE__)),
                      "mowerStatus"        => array('Etat robot',             'h', 'info',  'string',   "", 0, "GENERIC_INFO",   'core::badge', 'core::badge', ''),
                      "operatingMode"      => array('Mode de fonctionnement', 'h', 'info',  'string',   "", 0, "GENERIC_INFO",   'core::badge', 'core::badge', ''),
                      "nextStartSource"    => array('Prochain départ',        'h', 'info',  'string',   "", 0, "GENERIC_INFO",   'core::badge', 'core::badge', ''),
                      "nextStartTimestamp" => array('Heure prochain départ',  'h', 'info',  'string',  "ut2", 0, "GENERIC_INFO", 'core::badge', 'core::badge', ''),
                      "storedTimestamp"    => array('Heure dernier rapport',  'h', 'info',  'string',  "ut1", 0, "GENERIC_INFO", 'core::badge', 'core::badge', ''),
                      "errorStatus"        => array('Statut erreur',          'p', 'info',  'string',   "", 0, "GENERIC_INFO",   'core::badge', 'core::badge', ''),
                      "planning_en"        => array('Planification cmd',      'p', 'action','other',    "", 0, "GENERIC_ACTION", 'custom::IconActionNt', 'custom::IconActionNt',      ''),
                      "planning_activ"     => array('Planification',          'p', 'info',  'binary',   "", 0, "GENERIC_INFO",   'core::alert', 'core::alert', ''),
                      "planning_state"     => array('Etat planification',     'p', 'info',  'string',   "", 0, "GENERIC_INFO",   'core::badge', 'core::badge', ''),
                      "planning_nbcy_tot"  => array('Nombre de cycles total', 'p', 'info',  'numeric',  "", 0, "GENERIC_INFO",   'core::line', 'core::line', ''),
                      "planning_nbcy_z1"   => array('Nombre de cycles zone1', 'p', 'info',  'numeric',  "", 0, "GENERIC_INFO",   'core::line', 'core::line', ''),
                      "meteo_en"           => array('Météo cmd',              'p', 'action','other',    "", 0, "GENERIC_ACTION", 'custom::IconActionNt', 'custom::IconActionNt',      ''),
                      "meteo_activ"        => array('Météo',                  'p', 'info',  'binary',   "", 0, "GENERIC_INFO",   'core::alert', 'core::alert', ''),
                      "lastLocations"      => array('Position GPS',           'h', 'info',  'string',   "", 0, "GENERIC_INFO",   'husqvarna_map::maps_husqvarna', 'husqvarna_map::maps_husqvarna', ''),
                      "gps_posx"           => array('GPS position X',         'p', 'info',  'numeric',  "", 0, "GENERIC_INFO",   'core::line', 'core::line', ''),
                      "gps_posy"           => array('GPS position Y',         'p', 'info',  'numeric',  "", 0, "GENERIC_INFO",   'core::line', 'core::line', '')
        );
    }

    //public function postSave()
    public function postSave()
    {
        foreach( $this->getListeDefaultCommandes() as $id => $data)
        {
            list($name, $husqcmd, $type, $subtype, $unit, $invertBinary, $generic_type, $template_dashboard, $template_mobile, $listValue) = $data;
            $cmd = $this->getCmd(null, $id);
            if ( ! is_object($cmd) ) {
                $cmd = new husqvarna_mapCmd();
                $cmd->setName($name);
                $cmd->setEqLogic_id($this->getId());
                $cmd->setType($type);
                $cmd->setSubType($subtype);
                $cmd->setUnite($unit);
                $cmd->setLogicalId($id);
                if ( $listValue != "" )
                {
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
                $cmd->save();
            }
            else
            {
                $cmd->setType($type);
                $cmd->setSubType($subtype);
                $cmd->setUnite($unit);
                $cmd->setDisplay('invertBinary',$invertBinary);
                $cmd->setDisplay('generic_type', $generic_type);
                if ( $listValue != "" )
                {
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

    // Fonction appelée au rythme de 1 mn
    public static function pull() {
        log::add('husqvarna_map','debug','Funcion pull');
        if ( config::byKey('account', 'husqvarna_map') != "" || config::byKey('password', 'husqvarna_map') != "" )
        {
            log::add('husqvarna_map','debug','scan movers info');
            foreach (self::byType('husqvarna_map') as $eqLogic) {
                $eqLogic->scan();
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

    public function scan() {
        $session_husqvarna = new husqvarna_map_api();
        $session_husqvarna->login(config::byKey('account', 'husqvarna_map'), config::byKey('password', 'husqvarna_map'));
        if ($this->getIsEnable()) {
            // mise à jour des infos du plugin et historique GPS
            // =================================================
            $status = $session_husqvarna->get_status($this->getLogicalId());
            $min = intval(date('i'));
            foreach( $this->getListeDefaultCommandes() as $id => $data)
            {
                list($name, $husqcmd, $type, $subtype, $unit, $invertBinary, $generic_type, $template_dashboard, $template_mobile, $listValue) = $data;
                if ($id == "lastLocations") {
                  $cmd = $this->getCmd(null, $id);
                  // get state code value for logging
                  $state_code = $session_husqvarna->get_state_code($status->{"mowerStatus"});
                  // GPS logging done if mode is not PARKED, or every 5 mins
                  if (($state_code != PARKED_PARKED_SELECTED) or (($min%5)==0)) {
                    // compute GPS position for each point on image
                    $map_tl = $this->getConfiguration('gps_tl');
                    $map_br = $this->getConfiguration('gps_br');
                    $map_wd_ratio = $this->getConfiguration('img_wdg_ratio');
                    $map_wdm_ratio= $this->getConfiguration('img_wdgm_ratio');
                    $map_wd = round($this->getConfiguration('img_loc_width'));
                    $map_he = round($this->getConfiguration('img_loc_height'));
                    // log::add('husqvarna_map','debug',"Refresh DBG:image pos=".$map_tl." / ".$map_br);
                    // log::add('husqvarna_map','debug',"Refresh DBG:image size=".$map_wd." / ".$map_he);
                    list($map_t, $map_l) = explode(",", $map_tl);
                    list($map_b, $map_r) = explode(",", $map_br);
                    $lat_height = $map_b - $map_t;
                    $lon_width  = $map_r - $map_l;
                    $gps_pos = $map_wd.",".$map_he.'/'.$map_wd_ratio.",".$map_wdm_ratio.'/';  // passe la taille de l'image au widget, ainsi que les ratios dashboard et mobile
                    for ($i=0; $i<50; $i++) {
                        $gps_lat = floatval($status->{$id}[$i]->{"latitude"});
                        $gps_lon = floatval($status->{$id}[$i]->{"longitude"});
                        $xpos = round($map_wd * ($gps_lon-$map_l)/$lon_width);
                        $ypos = round($map_he * ($gps_lat-$map_t)/$lat_height);
                        $gps_pos = $gps_pos.$xpos.",".$ypos.'/';
                        if ($i == 0) {
                          $gps_log_dt = time().",".$state_code.",".$gps_lat.",".$gps_lon."\n";
                          $gps_posx = $xpos;
                          $gps_posy = $ypos;
                        }
                    }
                    // log::add('husqvarna_map','debug',"Refresh DBG:Gps_pos=".$gps_pos);
                    $cmd->event($gps_pos);
                    // Stores mower position on map
                    $cmd = $this->getCmd(null, "gps_posx");
                    $cmd->event($gps_posx);
                    $cmd = $this->getCmd(null, "gps_posy");
                    $cmd->event($gps_posy);
                    // Log GPS position for statistics (if valid)
                    if ($state_code != STS_UNKNOWN) {
                      // log::add('husqvarna_map','debug',"Refresh log recording Gps_dt=".$gps_log_dt);
                      $log_fn = dirname(__FILE__).MOWER_LOG_FILE;
                      file_put_contents($log_fn, $gps_log_dt, FILE_APPEND | LOCK_EX);
                    }
                  }
                  
                }
                elseif (($type != "action") and ($husqcmd != "p"))
                {
                    $cmd = $this->getCmd(null, $id);
                    // Values are stored if new or every 5 mins
                    if (($cmd->execCmd() != $cmd->formatValue($status->{$id})) or (($min%5)==0))
                    {
                        $cmd->setCollectDate('');
                        if (substr($unit,0,2) != "ut")
                        {
                            log::add('husqvarna_map','debug',"Refresh ".$id." : ".$status->{$id});
                            if ($id == "lastErrorCode")
                            {
                                $error_code = $status->{$id};
                                // Update corresponding error message
                                $error_status = $session_husqvarna->get_error_code($error_code);
                                $cmds = $this->getCmd(null, "errorStatus");
                                $cmds->event($error_status);
                                $cmd->event($error_code);
                                if ($error_code != 0)
                                  log::add('husqvarna_map','info',"Erreur robot: code=".$error_code." / Erreur = ".$error_status);
                            }
                            else 
                            {
                                $cmd->event($status->{$id});
                            }
                        }
                        else
                        {
                            if ($status->{$id} == 0)
                            {
                                $cmd->event(__('Inconnue',__FILE__));
                            } else {
                                if ($unit == "ut1") {
                                    $localTimeStamp = date('d M Y H:i', intval(substr($status->{$id},0,10)));
                                    log::add('husqvarna_map','debug',"Refresh ".$id." : ".$status->{$id}.", localtime : ". $localTimeStamp);
                                    $cmd->event($localTimeStamp );
                                } else if ($unit == "ut2") {
                                    $offsetTimeStamp = date("Z");
                                    $localTimeStamp = date('d M Y H:i', intval(substr($status->{$id},0,10)) - $offsetTimeStamp );
                                    log::add('husqvarna_map','debug',"Refresh ".$id." : ".$status->{$id}.", localtime : ". $localTimeStamp.", offset : ". $offsetTimeStamp);
                                    $cmd->event($localTimeStamp );
                                }
                            }
                        }
                    }
                }
            }
            // Gestion de la planification du robot
            // ====================================
            // recuperation des parametres de planification
            $cmd = $this->getCmd(null, 'planning_activ');
            $pl_on = $cmd->execCmd();
            $cmd = $this->getCmd(null, 'meteo_activ');
            $pl_meteo = $cmd->execCmd();
            $multizone = $this->getConfiguration("enable_2_areas");
            $battery = intval($status->{"batteryPercent"});
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
            list($hr,$mn) = explode(":", $pl1_ts);
            $pl1_ts = intval($hr*60)+intval($mn);
            list($hr,$mn) = explode(":", $pl1_te);
            $pl1_te = intval($hr*60)+intval($mn);
            $pl1_zn = intval($this->getConfiguration($pl_zone));
            $pl1_en = intval($this->getConfiguration($pl_enable));
            log::add('husqvarna_map','debug',"Planing: plage1=".$pl1_ts."/".$pl1_te."/".$pl1_zn."/".$pl1_en);
            $pl_start = str_replace("1", "2", $pl_start);
            $pl_end   = str_replace("1", "2", $pl_end);
            $pl_zone  = str_replace("1", "2", $pl_zone);
            $pl_enable= str_replace("1", "2", $pl_enable);
            $pl2_ts = $this->getConfiguration($pl_start);
            $pl2_te = $this->getConfiguration($pl_end);
            list($hr,$mn) = explode(":", $pl2_ts);
            $pl2_ts = intval($hr*60)+intval($mn);
            list($hr,$mn) = explode(":", $pl2_te);
            $pl2_te = intval($hr*60)+intval($mn);
            $pl2_zn = intval($this->getConfiguration($pl_zone));
            $pl2_en = intval($this->getConfiguration($pl_enable));
            log::add('husqvarna_map','debug',"Planing: plage2=".$pl2_ts."/".$pl2_te."/".$pl2_zn."/".$pl2_en);
            // gestion de la panification du robot
            $planning_state_cmd = $this->getCmd(null, "planning_state");
            $pln_state = $planning_state_cmd->execCmd();
            $nb_clycle_tot_cmd = $this->getCmd(null, "planning_nbcy_tot");
            $nb_clycle_tot = $nb_clycle_tot_cmd->execCmd();
            $nb_clycle_z1_cmd = $this->getCmd(null, "planning_nbcy_z1");
            $nb_clycle_z1 = $nb_clycle_z1_cmd->execCmd();
            $mode_changed = 0;
            $stat_changed = 0;
            list($hr,$mn) = explode(":",date("G:i"));
            $cur_hm = intval($hr*60)+intval($mn);
            if (($day == "lun") and ($cur_hm == 0)) {
              // Clear stat sur zone1 le lundi à 0h00
              $nb_clycle_tot = 0;
              $nb_clycle_z1 = 0;
              $stat_changed = 1;
            }
            log::add('husqvarna_map','debug',"Planing: planning_state=".$pln_state."/Heure courante:".$cur_hm);
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
                      $order = $session_husqvarna->control($this->getLogicalId(), 'START');
                      log::add('husqvarna_map','info',"Départ tonte sur plage horaire 1. (Ret=".$order->status.")");
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
                      $order = $session_husqvarna->control($this->getLogicalId(), 'START');
                      log::add('husqvarna_map','info',"Départ tonte sur plage horaire 2. (Ret=".$order->status.")");
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
                      $order = $session_husqvarna->control($this->getLogicalId(), 'PARK');
                      log::add('husqvarna_map','info',"Fin de tonte sur plage horaire 1. (Ret=".$order->status.")");
                      if (($pl_meteo == 1) and ($pluie_15m>=METEO_SEUIL_PLUIE_RETOUR))
                        log::add('husqvarna_map','info',"... Retour à la base pour raison de pluie sur 15 minutes:".$pluie_15m);
                    }
                    elseif (($state_code == OK_CHARGING) and ($battery <= 50)) {
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
                      $order = $session_husqvarna->control($this->getLogicalId(), 'PARK');
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
                      $order = $session_husqvarna->control($this->getLogicalId(), 'PARK');
                      log::add('husqvarna_map','info',"Fin de tonte sur plage horaire 2. (Ret=".$order->status.")");
                      if (($pl_meteo == 1) and ($pluie_15m>=METEO_SEUIL_PLUIE_RETOUR))
                        log::add('husqvarna_map','info',"... Retour à la base pour raison de pluie sur 15 minutes:".$pluie_15m);
                    }
                    elseif (($state_code == OK_CHARGING) and ($battery <= 50)) {
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
                      $order = $session_husqvarna->control($this->getLogicalId(), 'PARK');
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
                    if ($state_code == PARKED_PARKED_SELECTED) {
                      $pln_state = MDPLN_IDLE;
                      $mode_changed = 1;
                      if ($multizone == 1) {
                        $this->set_zone(1, 0, 0);  // Mise au repot du sélecteur de zone
                      }
                      log::add('husqvarna_map','info',"Robot rentré à la base. (state_code=".$state_code.")");
                    }
                    elseif ($pl_on == 0) { // arret de la planification
                      // Park de la tondeuse
                      $order = $session_husqvarna->control($this->getLogicalId(), 'PARK');
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

        }
        $session_husqvarna->logOut();
    }
}

class husqvarna_mapCmd extends cmd 
{
    /*     * *************************Attributs****************************** */
    public function execute($_options = null) {
        if ( $this->getLogicalId() == 'commande' && $_options['select'] != "" )
        {
          log::add('husqvarna_map','info',"Commande execute ".$this->getLogicalId()." ".$_options['select']);
          $session_husqvarna = new husqvarna_map_api();
          $session_husqvarna->login(config::byKey('account', 'husqvarna_map'), config::byKey('password', 'husqvarna_map'));
          $eqLogic = $this->getEqLogic();

          $order = $session_husqvarna->control($eqLogic->getLogicalId(), $_options['select']);
          log::add('husqvarna_map','debug',"Commande traitée : Code = ".$order->status);
        }
        elseif ( $this->getLogicalId() == 'refresh')
        {
          log::add('husqvarna_map','info',"Refresh data");
          husqvarna_map::pull();
        }
        elseif ( $this->getLogicalId() == 'planning_en')
        {
          $eqLogic = $this->getEqLogic();
          $cmd_ret = $eqLogic->getCmd(null, 'planning_activ');
          if (is_object($cmd_ret)) {
            $value = $cmd_ret->execCmd();
            $cmd_ret->setCollectDate('');
            $cmd_ret->event($value xor 1);
          }

        }
        elseif ( $this->getLogicalId() == 'meteo_en')
        {
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
