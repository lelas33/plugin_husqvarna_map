
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
 var globalEqLogic = $("#eqlogic_select option:selected").val();
 var isCoutVisible = false;
$(".in_datepicker").datepicker();

// Liste des états possible du robot
var STATE_PARKED_TIMER    	           =  0; 
var STATE_OK_LEAVING    	             =  1; 
var STATE_OK_CUTTING    	             =  2; 
var STATE_PARKED_PARKED_SELECTED    	 =  3; 
var STATE_OK_SEARCHING    	           =  4; 
var STATE_OK_CHARGING    	             =  5; 
var STATE_PAUSED    	                 =  6; 
var STATE_PARKED_AUTOTIMER    	       =  7; 
var STATE_COMPLETED_CUTTING_TODAY_AUTO =  8; 
var STATE_OK_CUTTING_NOT_AUTO          =  9;
var STATE_OFF_HATCH_OPEN               = 10; 

// Variables partagées
var mower_dtlog = [];
var map_t_lat;
var map_l_lon;
var map_b_lat;
var map_r_lon;
var map_width;
var map_height;

// Fonctions realisées au chargement de la page: charger les données sur la période par défaut,
// et afficher les infos correspondantes
// ============================================================================================
loadData();

// capturer les donnees depuis le serveur
// ======================================
function loadData(){
    var param = [];
    param[0]= (Date.parse($('#in_startDate').value())/1000);  // Time stamp en seconde
    param[1]= (Date.parse($('#in_endDate').value())/1000);
    $.ajax({
        type: 'POST',
        url: 'plugins/husqvarna_map/core/ajax/husqvarna_map.ajax.php',
        data: {
            action: 'getLogData',
            eqLogic_id: globalEqLogic,
            param: param
        },
        dataType: 'json',
        error: function (request, status, error) {
            alert("loadData:Error"+status+"/"+error);
            handleAjaxError(request, status, error);
        },
        success: function (data) {
            console.log("[loadData] Objet husqvarna récupéré : " + globalEqLogic);
            if (data.state != 'ok') {
                $('#div_alert').showAlert({message: data.result, level: 'danger'});
                return;
            }
            dt_log = JSON.parse(data.result);
            nb_dt = dt_log.log.length;
            //alert("getLogData:data nb="+nb_dt);
            // Capture les donnees de position
            mower_dtlog = [];
            for (p=0; p<nb_dt; p++) {
              mower_dtlog[p] = dt_log.log[p];
            }
            //alert("getLogData:"+mower_dtlog);
            // Capture les donnees de configuration
            //alert("getConfData:"+dt_log.config.map_tl);
            dt = dt_log.config.map_tl.split(',');
            map_t_lat = dt[0];
            map_l_lon = dt[1];
            dt = dt_log.config.map_br.split(',');
            map_b_lat = dt[0];
            map_r_lon = dt[1];
            map_wd = dt_log.config.map_wd;
            map_he = dt_log.config.map_he;
            map_wr = parseFloat(dt_log.config.map_wr);
            map_pr = parseFloat(dt_log.config.map_pr);
            map_width = Math.round((map_wd*map_pr)/100);
            map_height = Math.round((map_he*map_pr)/100);
            // Trace les positions sur la carte
            draw_lines(rb_get_mode_value());
            stat_usage ();
            
        }
    });
}

// Affiche les positions du mower_dtlog
// ====================================
function draw_lines(mode_value) {
    //alert("init('object_id'):",init('object_id'));

    // mise en forme des données
    nb_pts = mower_dtlog.length;
    var dtlog_ts  = [];
    var dtlog_st  = [];
    var dtlog_lat = [];
    var dtlog_lon = [];
    var idx=0;
    var mode = 0;
    if (mode_value == "lines")
      mode = 0;
    else if (mode_value == "circles")
      mode = 1;
    for (i=0; i<nb_pts; i++) {
      tmp = mower_dtlog[i].split(',');
      if (tmp[1] == STATE_OK_CUTTING) {  //si etat = "OK_CUTTING"
        dtlog_ts[idx]  = tmp[0];
        dtlog_lat[idx] = tmp[2];
        dtlog_lon[idx] = tmp[3];
        idx++;
      }
    }
    // tracé de l'image de fond
    var canvas = document.querySelector('.myCanvas');
    var ctx = canvas.getContext('2d');
    ctx.canvas.width = map_width;
    ctx.canvas.height= map_height;
    ctx.globalAlpha = 1.0;
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    //var backgroundImage = new Image(); 
    //backgroundImage.src = 'plugins/husqvarna_map/ressources/maison.png'; 
    ctx.drawImage(document.getElementById('img_loc'), 0, 0, map_width, map_height);
    // tracé des points
    var lat_height = map_b_lat - map_t_lat;
    var lon_width  = map_r_lon - map_l_lon;
    ctx.globalAlpha = 1.0;
    ctx.setLineDash([5,5]);
    ctx.lineWidth = 2;
    //alert("draw_lines:dtlog_lat="+dtlog_lat);
    rnb_pts = dtlog_ts.length;
    var prev_ts=0;
    for (i=0; i<rnb_pts; i++) {
      // calcul de la position du point sur la carte
      xpos = Math.round(map_width  * (dtlog_lon[i]-map_l_lon) / lon_width);
      ypos = Math.round(map_height * (dtlog_lat[i]-map_t_lat) / lat_height);
      //alert("draw_lines:xpos="+xpos);
      if (mode == 0) {
        if ((i==0) || (dtlog_ts[i] - prev_ts>100)) {
          ctx.beginPath();
          ctx.strokeStyle = 'red';
          ctx.moveTo(xpos,ypos);
          prev_ts = dtlog_ts[i];
        }
        else {
          ctx.lineTo(xpos,ypos);
          ctx.stroke();
          ctx.beginPath();
          ctx.strokeStyle = 'red';
          ctx.moveTo(xpos,ypos);
          prev_ts = dtlog_ts[i];
        }
      }
      else if (mode == 1) {
        // trace cercle autour du point courant
        ctx.beginPath();
        ctx.globalAlpha = 0.4;
        ctx.fillStyle='red';  //"#FF4422"
        ctx.arc(xpos, ypos, 8, 0, 2 * Math.PI);
        ctx.fill();
      
      }
    }
    // trace cercle autour du dernier point (position courante) si moins de 2 mn
    if (((Date.now()/1000)-dtlog_ts[i-1]) < 120) {
      ctx.beginPath();
      ctx.strokeStyle = 'GreenYellow';
      ctx.globalAlpha = 1.0;
      ctx.lineWidth = 4;
      ctx.setLineDash([]);
      ctx.arc(xpos,ypos, 8, 0, 2 * Math.PI);
      ctx.stroke();      
    }

}


// Calcul des statistiques d'utilisation du robot sur la période
// =============================================================
// Nombre de cycle de tonte => Si plus de 5 points consécutifs pour lesquels state = CUTTING : +1 cycle de tonte
// Nombre de cycle de charge => Si plus de 5 points consécutifs pour lesquels state = CHARGING : +1 cycle de charge
// Nombre d'heure de fonctionnement en coupe => Somme des temps entre 2 points pour lesquels state = CUTTING
// Nombre d'heure de recharge => Somme des temps entre 2 points pour lesquels state = CHARGING
// Durée en phase de recherche => Somme des temps entre 2 points pour lesquels state = SEARCHING
// Durée en phase de départ => Somme des temps entre 2 points pour lesquels state = LEAVING
// Durée moyenne des cycles de tonte => Nombre d'heure de fonctionnement en coupe / Nombre de cycle de tonte
// Durée moyenne des cycles de charge => Nombre d'heure de recharge / Nombre de cycle de charge
function stat_usage () {
  var nb_cycle_charging = 0;
  var nb_cycle_cutting = 0;
  var duration_charging = 0;
  var duration_cutting = 0;
  var duration_searching = 0;
  var duration_leaving = 0;
  var nb_consecutive_cycle_charging = 0;
  var nb_consecutive_cycle_cutting = 0;
  
  var nb_pts = mower_dtlog.length;
  if (nb_pts <1) {
    $("#div_hist_usage").empty();
    $("#div_hist_usage").append("Pas de données");
    return;
  }
  // analyse des données
  tmp = mower_dtlog[0].split(',');
  prev_ts = tmp[0];  // time stamp
  prev_st = tmp[1];  // state
  for (i=1; i<nb_pts; i++) {
    tmp = mower_dtlog[i].split(',');
    cur_ts = tmp[0];  // time stamp
    cur_st = tmp[1];  // state
    if (cur_st == STATE_OK_CHARGING)
      duration_charging += cur_ts - prev_ts;
    if (cur_st == STATE_OK_CUTTING)
      duration_cutting += cur_ts - prev_ts;
    if (cur_st == STATE_OK_SEARCHING)
      duration_searching += cur_ts - prev_ts;
    if (cur_st == STATE_OK_LEAVING)
      duration_leaving += cur_ts - prev_ts;

    if ((cur_st == STATE_OK_CHARGING)&&(prev_st == STATE_OK_CHARGING))
      nb_consecutive_cycle_charging += 1;
    else {
      if (nb_consecutive_cycle_charging > 5)
        nb_cycle_charging += 1;
      nb_consecutive_cycle_charging = 0;
    }
    if ((cur_st == STATE_OK_CUTTING)&&(prev_st == STATE_OK_CUTTING))
      nb_consecutive_cycle_cutting += 1;
    else {
      if (nb_consecutive_cycle_cutting > 5)
        nb_cycle_cutting += 1;
      nb_consecutive_cycle_cutting = 0;
    }
    prev_ts = cur_ts;
    prev_st = cur_st;
  }
  // calculs supplémentaires:
  charging_mean_duration = duration_charging/nb_cycle_charging;
  cutting_mean_duration = duration_cutting/nb_cycle_cutting;
  // Affichage des résultats dans le DIV:"div_hist_usage"
  $("#div_hist_usage").empty();
  $("#div_hist_usage").append("Temps de recharge: "+Math.round(duration_charging/60)+" mn<br>");
  $("#div_hist_usage").append("Temps de départ: "+Math.round(duration_leaving/60)+" mn<br>");
  $("#div_hist_usage").append("Temps de fonctionnement en coupe: "+Math.round(duration_cutting/60)+" mn<br>");
  $("#div_hist_usage").append("Temps de recherche: "+Math.round(duration_searching/60)+" mn<br><br>");
  $("#div_hist_usage").append("Nombre de cycle de recharge: "+nb_cycle_charging+"<br>");
  $("#div_hist_usage").append("Nombre de cycle de coupe: "+nb_cycle_cutting+"<br>");
  $("#div_hist_usage").append("Durée moyenne des cycles de recharge: "+((nb_cycle_charging==0)?"--":Math.round(charging_mean_duration/60))+" mn<br>");
  $("#div_hist_usage").append("Durée moyenne des cycles de coupe: "+((nb_cycle_cutting==0)?"--":Math.round(cutting_mean_duration/60))+" mn<br><br>");
  $("#div_hist_usage").append("(Nombre de points utilisés pour les statistiques: "+nb_pts+")<br>");
  
}

// gestion des radio buttons : mode d'historique
// =============================================
var rb_lines = document.querySelector('#rb_lines');
var rb_circles = document.querySelector('#rb_circles');

rb_lines.addEventListener('change', update_GPS_History);
rb_circles.addEventListener('change', update_GPS_History);

function update_GPS_History(e) {
  //alert("update_GPS_History:"+e.target.value);
  draw_lines(e.target.value);
}

function rb_get_mode_value() {
  var rb_list = document.getElementsByName('hist_mode');
  //alert(rb_list.length);
  for (i=0; i<rb_list.length; i++) {
    if (rb_list[i].checked == true) {
      //alert(rb_list[i].value + ' you got a value');     
      return rb_list[i].value;
    }
  }
}

// gestion du bouton de definition et de mise à jour de la période 
// ===============================================================
$('#bt_validChangeDate').on('click',function(){
  loadData();
});

// Aujourd'hui
$('#bt_per_today').on('click',function(){
  $('#in_startDate').datepicker( "setDate", "+0" );
  $('#in_endDate').datepicker( "setDate", "+1" );
  loadData();
});
// Hier
$('#bt_per_yesterday').on('click',function(){
  $('#in_startDate').datepicker( "setDate", "-1" );
  $('#in_endDate').datepicker( "setDate", "+0" );
  loadData();
});
// Les 7 derniers jours
$('#bt_per_last_week').on('click',function(){
  $('#in_startDate').datepicker( "setDate", "-6" );
  $('#in_endDate').datepicker( "setDate", "+1" );
  loadData();
});
// Tout
$('#bt_per_all').on('click',function(){
  $('#in_startDate').datepicker( "setDate", "-730" );  // - 2 ans
  $('#in_endDate').datepicker( "setDate", "+1" );
  loadData();
});

// gestion du bouton get settings
// ==============================
$('#bt_getSettings').on('click',function(){
  mower_get_settings();
});


// Interrogation du robot sur sa configuration
// ===========================================
function mower_get_settings(){
    $.ajax({
        type: 'POST',
        url: 'plugins/husqvarna_map/core/ajax/husqvarna_map.ajax.php',
        data: {
            action: 'getSettings',
            eqLogic_id: globalEqLogic
        },
        dataType: 'json',
        error: function (request, status, error) {
            alert("loadData:Error"+status+"/"+error);
            handleAjaxError(request, status, error);
        },
        success: function (data) {
            console.log("[loadData] Objet husqvarna récupéré : " + globalEqLogic);
            if (data.state != 'ok') {
                $('#div_alert').showAlert({message: data.result, level: 'danger'});
                return;
            }
            dt_settings = JSON.parse(data.result);
            nb_settings = dt_settings.length;
            //alert("nb_settings="+nb_settings);
            // Mise à jour des settings sur le DIV:"div_settings1&2&3"
            $("#div_settings1").empty();
            $("#div_settings2").empty();
            $("#div_settings3").empty();
            // section accessoires
            $("#div_settings1").append("<b>Accessoires</b><br>");             
            for (i=0; i<nb_settings; i++) {
              if (dt_settings[i].includes("accessories"))
                $("#div_settings1").append(dt_settings[i].replace("accessories.", "")+"<br>");                             
            }
            // section general
            $("#div_settings1").append("<b>Général</b><br>");             
            for (i=0; i<nb_settings; i++) {
              if ((dt_settings[i].includes("general")) || dt_settings[i].includes("cuttingHeight"))
                $("#div_settings1").append(dt_settings[i].replace("general.", "")+"<br>");                             
            }
            // section weatherTimer
            $("#div_settings1").append("<b>Minuterie adaptative</b><br>");             
            for (i=0; i<nb_settings; i++) {
              if (dt_settings[i].includes("weatherTimer"))
                $("#div_settings1").append(dt_settings[i].replace("weatherTimer.", "")+"<br>");                             
            }
            // sections installation area[i]
            for (iarea=1; iarea<6;iarea++) {
              var keyw = "installation.area"+iarea;
              $("#div_settings2").append("<b>Installation zone"+iarea+"</b><br>");             
              for (i=0; i<nb_settings; i++) {
                if (dt_settings[i].includes(keyw))
                  $("#div_settings2").append(dt_settings[i].replace(keyw+'.', "")+"<br>");                             
              }
            }
            // section installation autres
            $("#div_settings3").append("<b>Autres paramètres d'installation</b><br>");             
            for (i=0; i<nb_settings; i++) {
              if ((dt_settings[i].includes("installation")) && (dt_settings[i].split('.').length==2))
                $("#div_settings3").append(dt_settings[i].replace("installation.", "")+"<br>");                             
            }
            
        }
    });
}
