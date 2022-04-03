// Gestion des listes de commandes action ou commandes info
$(".listCmdActionOther").on('click', function () {
  var el = $(this);
  jeedom.cmd.getSelectModal({cmd: {type: 'action',subType : 'other'}}, function (result) {
    el.closest('.input-group').find('input').value(result.human);
  });
});
$(".listCmdInfoNumeric").on('click', function () {
  var el = $(this);
  jeedom.cmd.getSelectModal({cmd: {type: 'info',subType : 'numeric'}}, function (result) {
    el.closest('.input-group').find('input').value(result.human);
  });
});

function addCmdToTable(_cmd) {
   if (!isset(_cmd)) {
        var _cmd = {configuration: {}};
    }
    if (!isset(_cmd.configuration)) {
        _cmd.configuration = {};
    }

    if (init(_cmd.type) == 'info') {
        var tr = '<tr class="cmd" data-cmd_id="' + init(_cmd.id) + '" >';
        if (init(_cmd.logicalId) == 'brut') {
			tr += '<input type="hiden" name="brutid" value="' + init(_cmd.id) + '">';
		}
        tr += '<td>';
        tr += '<span class="cmdAttr" data-l1key="id"></span>';
        tr += '</td>';
        tr += '<td>';
        tr += '<input class="cmdAttr form-control input-sm" data-l1key="name" style="width : 140px;" placeholder="{{Nom}}"></td>';
		tr += '<td class="expertModeVisible">';
        tr += '<input class="cmdAttr form-control type input-sm" data-l1key="type" value="action" disabled style="margin-bottom : 5px;" />';
        tr += '<span class="subType" subType="' + init(_cmd.subType) + '"></span>';
        tr += '</td>';
        tr += '<td>';
        tr += '<span><input type="checkbox" class="cmdAttr" data-l1key="isHistorized"/> {{Historiser}}<br/></span>';
        tr += '<span><input type="checkbox" class="cmdAttr" data-l1key="isVisible" checked/> {{Afficher}}<br/></span>';
		if (init(_cmd.subType) == 'binary') {
			tr += '<span class="expertModeVisible"><input type="checkbox" class="cmdAttr" data-l1key="display" data-l2key="invertBinary" /> {{Inverser}}<br/></span>';
		}
        if (init(_cmd.logicalId) == 'reel') {
			tr += '<span class="expertModeVisible"><input type="checkbox" class="cmdAttr" data-l1key="configuration" data-l2key="minValueReplace" value="1"/> {{Correction Min	 Auto}}<br>';
			tr += '<input type="checkbox" class="cmdAttr" data-l1key="configuration" data-l2key="maxValueReplace" value="1"/> {{Correction Max Auto}}<br></span>';
		}        tr += '</td>';
        tr += '<td>';
        if (is_numeric(_cmd.id)) {
            tr += '<a class="btn btn-default btn-xs cmdAction expertModeVisible" data-action="configure"><i class="fa fa-cogs"></i></a> ';
        }
        tr += '</td>';
		table_cmd = '#table_cmd';
		if ( $(table_cmd+'_'+_cmd.eqType ).length ) {
			table_cmd+= '_'+_cmd.eqType;
		}
        $(table_cmd+' tbody').append(tr);
        $(table_cmd+' tbody tr:last').setValues(_cmd, '.cmdAttr');
    }
    if (init(_cmd.type) == 'action') {
        var tr = '<tr class="cmd" data-cmd_id="' + init(_cmd.id) + '">';
        tr += '<td>';
        tr += '<span class="cmdAttr" data-l1key="id"></span>';
        tr += '</td>';
        tr += '<td>';
        tr += '<input class="cmdAttr form-control input-sm" data-l1key="name" style="width : 140px;" placeholder="{{Nom}}">';
        tr += '</td>';
        tr += '<td>';
        tr += '<input class="cmdAttr form-control type input-sm" data-l1key="type" value="action" disabled style="margin-bottom : 5px;" />';
        tr += '<span class="subType" subType="' + init(_cmd.subType) + '"></span>';
        tr += '<input class="cmdAttr" data-l1key="configuration" data-l2key="virtualAction" value="1" style="display:none;" >';
        tr += '</td>';
        tr += '<td>';
        tr += '<span><input type="checkbox" class="cmdAttr" data-l1key="isVisible" checked/> {{Afficher}}<br/></span>';
        tr += '<input class="tooltips cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="minValue" placeholder="{{Min}}" title="{{Min}}" style="width : 40%;display : none;">';
        tr += '<input class="tooltips cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="maxValue" placeholder="{{Max}}" title="{{Max}}" style="width : 40%;display : none;">';
        tr += '</td>';
        tr += '<td>';
        if (is_numeric(_cmd.id)) {
            tr += '<a class="btn btn-default btn-xs cmdAction expertModeVisible" data-action="configure"><i class="fa fa-cogs"></i></a> ';
            tr += '<a class="btn btn-default btn-xs cmdAction" data-action="test"><i class="fa fa-rss"></i> {{Tester}}</a>';
        }
        tr += '</td>';
        tr += '</tr>';

		table_cmd = '#table_cmd';
		if ( $(table_cmd+'_'+_cmd.eqType ).length ) {
			table_cmd+= '_'+_cmd.eqType;
		}
        $(table_cmd+' tbody').append(tr);
        $(table_cmd+' tbody tr:last').setValues(_cmd, '.cmdAttr');
        var tr = $(table_cmd+' tbody tr:last');
        jeedom.eqLogic.builSelectCmd({
            id: $(".li_eqLogic.active").attr('data-eqLogic_id'),
            filter: {type: 'info'},
            error: function (error) {
                $('#div_alert').showAlert({message: error.message, level: 'danger'});
            },
            success: function (result) {
                tr.find('.cmdAttr[data-l1key=value]').append(result);
                tr.setValues(_cmd, '.cmdAttr');
            }
        });
    }
}

$("#table_cmd").sortable({axis: "y", cursor: "move", items: ".cmd", placeholder: "ui-state-highlight", tolerance: "intersect", forcePlaceholderSize: true});

$('.eqLogicDetect').on('click', function() {
    $.ajax({// fonction permettant de faire de l'ajax
        type: "POST", // methode de transmission des données au fichier php
        url: "plugins/husqvarna_map/core/ajax/husqvarna_map.ajax.php", // url du fichier php
        data: {
            action: "force_detect_mowers",
        },
        dataType: 'json',
        error: function(request, status, error) {
            handleAjaxError(request, status, $('#div_DetectBin'));
        },
        success: function(data) { // si l'appel a bien fonctionné
			if (data.state != 'ok') {
				$('#div_alert').showAlert({message: data.result, level: 'danger'});
                return;
			}
			window.location.reload();
		}
    });
});

// Fonctions de gestions de la config. planification
// =================================================
// fonction de conversion string <=> time
function str_to_date(tm_str) {
	var dtm=0;
  hm = tm_str.split(':');
  dtm = parseInt(hm[0])*60+parseInt(hm[1]); // nombre de minutes
  return dtm;
}
function date_to_str(tm_min) {
	var str="";
  min = tm_min%60;
  hr =  Math.floor(tm_min/60);
  dtm = (min<10)?'0'+min.toString():min.toString();
  dth = (hr<10)?'0'+hr.toString():hr.toString();
  str = dth+":"+dtm;
  return (str);
}

function change_time(id,dir) {
  txt = $('.eqLogicAttr[data-l1key=configuration][data-l2key='+id+']').value();
  dtm = str_to_date(txt);
  if ((dir==1) && (dtm < 24*60))
    dtm = dtm + 30; // + 30mn
  else if ((dir==-1) && (dtm >= 30))
    dtm = dtm - 30; // - 30mn
  txt = date_to_str(dtm);
  $('.eqLogicAttr[data-l1key=configuration][data-l2key='+id+']').val(txt);  
}

function change_zone(id) {
  val = parseInt($('.eqLogicAttr[data-l1key=configuration][data-l2key='+id+']').value());
  if (val == 3)
    val = 1;
  else
    val = val + 1;
  $('.eqLogicAttr[data-l1key=configuration][data-l2key='+id+']').val(val);  
}

function change_time_loc(id,dir) {
  txt = $('#'+id).value();
  dtm = str_to_date(txt);
  if ((dir==1) && (dtm < 24*60))
    dtm = dtm + 30; // + 30mn
  else if ((dir==-1) && (dtm >= 30))
    dtm = dtm - 30; // - 30mn
  txt = date_to_str(dtm);
  $('#'+id).val(txt);  
}

function change_zone_loc(id) {
  val = parseInt($('#'+id).value());
  if (val == 3)
    val = 1;
  else
    val = val + 1;
  $('#'+id).val(val);  
}

// capture du radio button actif dans le groupe "plage_init"
function rb_get_value() {
  var rb_list = document.getElementsByName('plage_init');;
  //alert(rb_list.length);
  for (i=0; i<rb_list.length; i++) {
    if (rb_list[i].checked == true) {
      //alert(rb_list[i].value + ' you got a value');     
      return rb_list[i].value;
    }
  }
}
// Initialisation des données de la semaine à partir de la plage horaire "init"
function init_plage() {
  //alert("init_plage:"+rb_get_value());
  init_plage_nb = rb_get_value();
  var day_names = ["lun","mar","mer","jeu","ven","sam","dim"];
  for (day=0; day<7; day++) {
    id = day_names[day]+'_ts'+init_plage_nb+'_begin';
    $('.eqLogicAttr[data-l1key=configuration][data-l2key='+id+']').val($('#init_start').value());
    id = day_names[day]+'_ts'+init_plage_nb+'_end';
    $('.eqLogicAttr[data-l1key=configuration][data-l2key='+id+']').val($('#init_end').value());
    id = day_names[day]+'_ts'+init_plage_nb+'_zone';
    $('.eqLogicAttr[data-l1key=configuration][data-l2key='+id+']').val($('#init_zone').value());
    id = day_names[day]+'_en_ts'+init_plage_nb;
    $('.eqLogicAttr[data-l1key=configuration][data-l2key='+id+']').prop("checked", $('#init_valid').prop('checked'));
  }
}

// param Lundi
$('#bt_lun_pl1s_p').on('click',function(){ change_time('lun_ts1_begin', 1); });
$('#bt_lun_pl1s_m').on('click',function(){ change_time('lun_ts1_begin',-1); });
$('#bt_lun_pl1e_p').on('click',function(){ change_time('lun_ts1_end'  , 1); });
$('#bt_lun_pl1e_m').on('click',function(){ change_time('lun_ts1_end'  ,-1); });
$('#bt_lun_pl2s_p').on('click',function(){ change_time('lun_ts2_begin', 1); });
$('#bt_lun_pl2s_m').on('click',function(){ change_time('lun_ts2_begin',-1); });
$('#bt_lun_pl2e_p').on('click',function(){ change_time('lun_ts2_end'  , 1); });
$('#bt_lun_pl2e_m').on('click',function(){ change_time('lun_ts2_end'  ,-1); });
$('#bt_lun_zn1_p').on('click',function(){ change_zone('lun_ts1_zone'); });
$('#bt_lun_zn2_p').on('click',function(){ change_zone('lun_ts2_zone'); });

// param Mardi
$('#bt_mar_pl1s_p').on('click',function(){ change_time('mar_ts1_begin', 1); });
$('#bt_mar_pl1s_m').on('click',function(){ change_time('mar_ts1_begin',-1); });
$('#bt_mar_pl1e_p').on('click',function(){ change_time('mar_ts1_end'  , 1); });
$('#bt_mar_pl1e_m').on('click',function(){ change_time('mar_ts1_end'  ,-1); });
$('#bt_mar_pl2s_p').on('click',function(){ change_time('mar_ts2_begin', 1); });
$('#bt_mar_pl2s_m').on('click',function(){ change_time('mar_ts2_begin',-1); });
$('#bt_mar_pl2e_p').on('click',function(){ change_time('mar_ts2_end'  , 1); });
$('#bt_mar_pl2e_m').on('click',function(){ change_time('mar_ts2_end'  ,-1); });
$('#bt_mar_zn1_p').on('click',function(){ change_zone('mar_ts1_zone'); });
$('#bt_mar_zn2_p').on('click',function(){ change_zone('mar_ts2_zone'); });

// param Mercredi
$('#bt_mer_pl1s_p').on('click',function(){ change_time('mer_ts1_begin', 1); });
$('#bt_mer_pl1s_m').on('click',function(){ change_time('mer_ts1_begin',-1); });
$('#bt_mer_pl1e_p').on('click',function(){ change_time('mer_ts1_end'  , 1); });
$('#bt_mer_pl1e_m').on('click',function(){ change_time('mer_ts1_end'  ,-1); });
$('#bt_mer_pl2s_p').on('click',function(){ change_time('mer_ts2_begin', 1); });
$('#bt_mer_pl2s_m').on('click',function(){ change_time('mer_ts2_begin',-1); });
$('#bt_mer_pl2e_p').on('click',function(){ change_time('mer_ts2_end'  , 1); });
$('#bt_mer_pl2e_m').on('click',function(){ change_time('mer_ts2_end'  ,-1); });
$('#bt_mer_zn1_p').on('click',function(){ change_zone('mer_ts1_zone'); });
$('#bt_mer_zn2_p').on('click',function(){ change_zone('mer_ts2_zone'); });

// param Jeudi
$('#bt_jeu_pl1s_p').on('click',function(){ change_time('jeu_ts1_begin', 1); });
$('#bt_jeu_pl1s_m').on('click',function(){ change_time('jeu_ts1_begin',-1); });
$('#bt_jeu_pl1e_p').on('click',function(){ change_time('jeu_ts1_end'  , 1); });
$('#bt_jeu_pl1e_m').on('click',function(){ change_time('jeu_ts1_end'  ,-1); });
$('#bt_jeu_pl2s_p').on('click',function(){ change_time('jeu_ts2_begin', 1); });
$('#bt_jeu_pl2s_m').on('click',function(){ change_time('jeu_ts2_begin',-1); });
$('#bt_jeu_pl2e_p').on('click',function(){ change_time('jeu_ts2_end'  , 1); });
$('#bt_jeu_pl2e_m').on('click',function(){ change_time('jeu_ts2_end'  ,-1); });
$('#bt_jeu_zn1_p').on('click',function(){ change_zone('jeu_ts1_zone'); });
$('#bt_jeu_zn2_p').on('click',function(){ change_zone('jeu_ts2_zone'); });

// param Vendredi
$('#bt_ven_pl1s_p').on('click',function(){ change_time('ven_ts1_begin', 1); });
$('#bt_ven_pl1s_m').on('click',function(){ change_time('ven_ts1_begin',-1); });
$('#bt_ven_pl1e_p').on('click',function(){ change_time('ven_ts1_end'  , 1); });
$('#bt_ven_pl1e_m').on('click',function(){ change_time('ven_ts1_end'  ,-1); });
$('#bt_ven_pl2s_p').on('click',function(){ change_time('ven_ts2_begin', 1); });
$('#bt_ven_pl2s_m').on('click',function(){ change_time('ven_ts2_begin',-1); });
$('#bt_ven_pl2e_p').on('click',function(){ change_time('ven_ts2_end'  , 1); });
$('#bt_ven_pl2e_m').on('click',function(){ change_time('ven_ts2_end'  ,-1); });
$('#bt_ven_zn1_p').on('click',function(){ change_zone('ven_ts1_zone'); });
$('#bt_ven_zn2_p').on('click',function(){ change_zone('ven_ts2_zone'); });

// param Samedi
$('#bt_sam_pl1s_p').on('click',function(){ change_time('sam_ts1_begin', 1); });
$('#bt_sam_pl1s_m').on('click',function(){ change_time('sam_ts1_begin',-1); });
$('#bt_sam_pl1e_p').on('click',function(){ change_time('sam_ts1_end'  , 1); });
$('#bt_sam_pl1e_m').on('click',function(){ change_time('sam_ts1_end'  ,-1); });
$('#bt_sam_pl2s_p').on('click',function(){ change_time('sam_ts2_begin', 1); });
$('#bt_sam_pl2s_m').on('click',function(){ change_time('sam_ts2_begin',-1); });
$('#bt_sam_pl2e_p').on('click',function(){ change_time('sam_ts2_end'  , 1); });
$('#bt_sam_pl2e_m').on('click',function(){ change_time('sam_ts2_end'  ,-1); });
$('#bt_sam_zn1_p').on('click',function(){ change_zone('sam_ts1_zone'); });
$('#bt_sam_zn2_p').on('click',function(){ change_zone('sam_ts2_zone'); });

// param Dimanche
$('#bt_dim_pl1s_p').on('click',function(){ change_time('dim_ts1_begin', 1); });
$('#bt_dim_pl1s_m').on('click',function(){ change_time('dim_ts1_begin',-1); });
$('#bt_dim_pl1e_p').on('click',function(){ change_time('dim_ts1_end'  , 1); });
$('#bt_dim_pl1e_m').on('click',function(){ change_time('dim_ts1_end'  ,-1); });
$('#bt_dim_pl2s_p').on('click',function(){ change_time('dim_ts2_begin', 1); });
$('#bt_dim_pl2s_m').on('click',function(){ change_time('dim_ts2_begin',-1); });
$('#bt_dim_pl2e_p').on('click',function(){ change_time('dim_ts2_end'  , 1); });
$('#bt_dim_pl2e_m').on('click',function(){ change_time('dim_ts2_end'  ,-1); });
$('#bt_dim_zn1_p').on('click',function(){ change_zone('dim_ts1_zone'); });
$('#bt_dim_zn2_p').on('click',function(){ change_zone('dim_ts2_zone'); });

// param Init zones
$('#bt_inits_p').on('click',function(){ change_time_loc('init_start', 1); });
$('#bt_inits_m').on('click',function(){ change_time_loc('init_start',-1); });
$('#bt_inite_p').on('click',function(){ change_time_loc('init_end'  , 1); });
$('#bt_inite_m').on('click',function(){ change_time_loc('init_end'  ,-1); });
$('#bt_init_zn_p').on('click',function(){ change_zone_loc('init_zone'); });
$('#bt_init').on('click',function(){ init_plage(); });



