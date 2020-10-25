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
global $mower_dt;
global $mower_settings;
define("MOWER_LOG_FILENAME", "/../../data/mower_log.txt");

// ==========================================================
// Fonction de recuperation des donnees de log de la tondeuse
// ==========================================================
function get_mower_dt_log($ts_start, $ts_end)
{
  global $mower_dt;
  
  // ouverture du fichier de log
  $log_fn = dirname(__FILE__).MOWER_LOG_FILENAME;
  $flog = fopen($log_fn, "r");

  // lecture des donnees
  $line = 0;
  $mower_dt["log"] = [];
  if ($flog) {
    while (($buffer = fgets($flog, 4096)) !== false) {
      // extrait le timestamp du log courant
      list($ts, $st, $lat, $lon) = explode(",", $buffer);
      $tsi = intval($ts);
      if (($tsi>=$ts_start) && ($tsi<=$ts_end) && ($st != 99)) {
        $mower_dt["log"][$line] = $buffer;
        $line = $line + 1;
      }
    }
  }
  fclose($flog);
  return;
}

// =================================================================
// Fonction de recuperation des donnees de configurations du plugin
// =================================================================
function get_mower_dt_config()
{
  global $mower_dt;
  
  // Recuperation des parametres de configuration du plugin
  $eqLogics = eqLogic::byType('husqvarna_map');
  $eqLogic = $eqLogics[0]; // Gestion uniquement du premier élément
  $map_tl = $eqLogic->getConfiguration('gps_tl');
  $map_br = $eqLogic->getConfiguration('gps_br');
  $map_wd = $eqLogic->getConfiguration('img_loc_width');
  $map_he = $eqLogic->getConfiguration('img_loc_height');
  $map_wr = $eqLogic->getConfiguration('img_wdg_ratio');
  $map_pr = $eqLogic->getConfiguration('img_pan_ratio');

  // formatage des donnees
  $mower_dt["config"] = [];
  $mower_dt["config"]["map_tl"] = $map_tl;
  $mower_dt["config"]["map_br"] = $map_br;
  $mower_dt["config"]["map_wd"] = $map_wd;
  $mower_dt["config"]["map_he"] = $map_he;
  $mower_dt["config"]["map_wr"] = $map_wr;
  $mower_dt["config"]["map_pr"] = $map_pr;

  return;
}

// =================================================================
// Fonction de recuperation des donnees de configurations du robot
// =================================================================
function get_mower_settings($eqLogic_id)
{
  global $mower_settings;
  
  // identification de l'objet eqLogic correspondant
  $eqLogics = eqLogic::byType('husqvarna_map');
	foreach ($eqLogics as $eqLogic) {
    if ($eqLogic->getId() == $eqLogic_id) {
      // Recuperation des parametres de configuration du robot à partir de son API
      $session_husqvarna = new husqvarna_map_api();
      $session_husqvarna->login(config::byKey('account', 'husqvarna_map'), config::byKey('password', 'husqvarna_map'));
      if ($eqLogic->getIsEnable()) {
        $settings = $session_husqvarna->get_settings($eqLogic->getLogicalId());
        log::add('husqvarna_map','debug',"Get mower settings:".$eqLogic->getLogicalId());
        $nb_settings = count($settings->{"settings"});
        log::add('husqvarna_map','debug',"Get mower nb_settings:".$nb_settings);
        $mower_settings = [];
        for ($i=0; $i<$nb_settings; $i++) {
          $id  = $settings->{"settings"}[$i]->{"id"};
          $val = $settings->{"settings"}[$i]->{"value"};
          $mower_settings[$i] = $id."=>".$val;
        }
        sort($mower_settings);
        //foreach ($mower_settings as $val) {
        //  log::add('husqvarna_map','debug',"Get mower settings:".$val);
        //}
      }
    }
  }

  return;
}


// =====================================
// Gestion des commandes recues par AJAX
// =====================================
try {
    require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';
    include_file('core', 'authentification', 'php');

    if (!isConnect('admin')) {
        throw new Exception(__('401 - Accès non autorisé', __FILE__));
    }

	ajax::init();

  if (init('action') == 'force_detect_movers') {
		$husqvarna_mapCmd = husqvarna_map::force_detect_movers();
		ajax::success($husqvarna_mapCmd);
    }
  else if (init('action') == 'getLogData') {
    log::add('husqvarna_map', 'debug', 'get_mower_dt_log - Ajax:getLogData');
    //log::add('husqvarna_map', 'debug', 'param0:'.init('param')[0]);
    //log::add('husqvarna_map', 'debug', 'param1:'.init('param')[1]);
    // Param 0 et 1 sont les timestamp de debut et fin de la periode de log demandée
    get_mower_dt_log(intval (init('param')[0]), intval (init('param')[1]));
    get_mower_dt_config();
    //log::add('husqvarna_map', 'debug', 'get_mower_dt_log - Ajax: nb_elem='.count($mower_dt["log"]));
    $ret_json = json_encode ($mower_dt);
    ajax::success($ret_json);
  }
  else if (init('action') == 'getSettings') {
    log::add('husqvarna_map', 'debug', 'get_mower_dt_log - Ajax:getSettings');
    log::add('husqvarna_map', 'debug', 'get_mower_dt_log - Ajax:eqLogic_id:'.init('eqLogic_id'));
    get_mower_settings(init('eqLogic_id'));
    $ret_json = json_encode ($mower_settings);
    //log::add('husqvarna_map', 'debug', 'get_mower_settings - Ajax: nb_elem='.count($mower_settings));
    //log::add('husqvarna_map', 'debug', 'get_mower_settings - Ajax: ret_json='.$ret_json);
    ajax::success($ret_json);
  }

    throw new Exception(__('Aucune methode correspondante à : ', __FILE__) . init('action'));
    /*     * *********Catch exeption*************** */
} catch (Exception $e) {
    ajax::error(displayExeption($e), $e->getCode());
}
?>
