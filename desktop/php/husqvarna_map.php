<?php
if (!isConnect('admin')) {
    throw new Exception('{{401 - Accès non autorisé}}');
}
sendVarToJS('eqType', 'husqvarna_map');
?>

<div class="row row-overflow">
    <div class="col-lg-2 col-md-3 col-sm-4">
        <div class="bs-sidebar">
            <ul id="ul_eqLogic" class="nav nav-list bs-sidenav">
                <?php
                  $eqLogics = eqLogic::byType('husqvarna_map');
                  foreach ($eqLogics as $eqLogic) {
                              echo '<li>'."\n";
                      echo '<a class="cursor li_eqLogic" data-eqLogic_id="' . $eqLogic->getId() . '" data-eqLogic_type="husqvarna_map"><i class="fa fa-download"></i> ' . $eqLogic->getName() . '</a>'."\n";
                    echo '</li>'."\n";
                }
                ?>
            </ul>
        </div>
    </div>
    <div class="col-lg-10 col-md-9 col-sm-8 eqLogicThumbnailDisplay" style="border-left: solid 1px #EEE; padding-left: 25px;">
        <legend><i class="fa fa-cog"></i>  {{Gestion}}</legend>
        <div class="eqLogicThumbnailContainer">
            <div class="cursor eqLogicDetect" style="background-color : #ffffff; height : 120px;margin-bottom : 10px;padding : 5px;border-radius: 2px;width : 160px;margin-left : 10px;" >
                <center>
                    <i class="fas fa-bullseye" style="font-size : 4em;color:#94ca02;"></i>
                </center>
                <span style="font-size : 1.1em;position:relative; top : 15px;word-break: break-all;white-space: pre-wrap;word-wrap: break-word;color:#94ca02"><center>{{Detecter}}</center></span>
            </div>
            <div class="cursor eqLogicAction" data-action="add" style="background-color : #ffffff; height : 120px;margin-bottom : 10px;padding : 5px;border-radius: 2px;width : 160px;margin-left : 10px;" >
              <center>
                <i class="fa fa-plus-circle" style="font-size : 4em;color:#94ca02;"></i>
              </center>
              <span style="font-size : 1.1em;position:relative; top : 15px;word-break: break-all;white-space: pre-wrap;word-wrap: break-word;color:#94ca02"><center>{{Ajouter}}</center></span>
            </div>
            <div class="cursor eqLogicAction" data-action="gotoPluginConf" style="background-color : #ffffff; height : 120px;margin-bottom : 10px;padding : 5px;border-radius: 2px;width : 160px;margin-left : 10px;">
                <center>
                    <i class="fa fa-wrench" style="font-size : 4em;color:#767676;"></i>
                </center>
                <span style="font-size : 1.1em;position:relative; top : 15px;word-break: break-all;white-space: pre-wrap;word-wrap: break-word;color:#767676"><center>{{Configuration}}</center></span>
            </div>
        </div>
        <legend>{{Mes equipements}}</legend>
		  <div class="eqLogicThumbnailContainer">
        <?php
	        if (count($eqLogics) == 0) {
				  echo "<br/><br/><br/><center><span style='color:#767676;font-size:1.2em;font-weight: bold;'>{{Vous n'avez pas encore de Husqvarna, cliquez sur Detecter un équipement pour commencer}}</span></center>";
			    } else {
            foreach ($eqLogics as $eqLogic) {
                echo '<div class="eqLogicDisplayCard cursor" data-eqLogic_id="' . $eqLogic->getId() . '" style="background-color : #ffffff; height : 200px;margin-bottom : 10px;padding : 5px;border-radius: 2px;width : 160px;margin-left : 10px;" >';
                echo "<center>";
                echo '<img src="plugins/husqvarna_map/plugin_info/husqvarna_map_icon.png" height="105" width="95" />';
                echo "</center>";
                echo '<span style="font-size : 0.91em;position:relative; top : 5px;word-break: break-all;white-space: pre-wrap;word-wrap: break-word;"> <center>' . $eqLogic->getHumanName(true, true) . '</center></span>';
                echo '</div>';
            }
            }
            ?>
      </div>
    </div>
    <div class="col-lg-9 eqLogic husqvarna_map" style="border-left: solid 1px #EEE; padding-left: 25px;display: none;">
		<a class="btn btn-success eqLogicAction pull-right" data-action="save"><i class="fa fa-check-circle"></i> {{Sauvegarder}}</a>
		<a class="btn btn-danger eqLogicAction pull-right" data-action="remove"><i class="fa fa-minus-circle"></i> {{Supprimer}}</a>
		<a class="btn btn-default eqLogicAction pull-right" data-action="configure"><i class="fa fa-cogs"></i> {{Configuration avancée}}</a>
		<ul class="nav nav-tabs" role="tablist">
			<li role="presentation"><a href="#" class="eqLogicAction" aria-controls="home" role="tab" data-toggle="tab" data-action="returnToThumbnailDisplay"><i class="fa fa-arrow-circle-left"></i></a></li>
			<li role="presentation" class="active"><a href="#eqlogictab" aria-controls="home" role="tab" data-toggle="tab"><i class="fa fa-tachometer"></i> {{Equipement}}</a></li>
			<li role="presentation"><a href="#planiftab" aria-controls="home" role="tab" data-toggle="tab"><i class="fa fa-tachometer"></i> {{Planification}}</a></li>
			<li role="presentation"><a href="#confmaptab" aria-controls="home" role="tab" data-toggle="tab"><i class="fa fa-tachometer"></i> {{Config.Carte}}</a></li>
			<li role="presentation"><a href="#commandtab" aria-controls="profile" role="tab" data-toggle="tab"><i class="fa fa-list-alt"></i> {{Commandes}}</a></li>
		</ul>
		<div class="tab-content" style="height:calc(100% - 50px);overflow:auto;overflow-x: hidden;">
			<div role="tabpanel" class="tab-pane active" id="eqlogictab">
                <form class="form-horizontal">
                    <fieldset>
                        <legend>
                           <i class="fa fa-arrow-circle-left eqLogicAction cursor" data-action="returnToThumbnailDisplay"></i> {{Général}}
                           <i class='fa fa-cogs eqLogicAction pull-right cursor expertModeVisible' data-action='configure'></i>
                       </legend>
                        <div class="form-group">
                            <label class="col-lg-2 control-label">{{Identifiant équipement}}</label>
                            <div class="col-lg-3">
                                <input type="text" class="eqLogicAttr form-control" data-l1key="logicalId" readonly/>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-2 control-label">{{Modèle équipement}}</label>
                            <div class="col-lg-3">
                              <input type="text" class="eqLogicAttr form-control roundedLeft" data-l1key="configuration" data-l2key="equip_model" readonly/>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-2 control-label">{{N° série équipement}}</label>
                            <div class="col-lg-3">
                              <input type="text" class="eqLogicAttr form-control roundedLeft" data-l1key="configuration" data-l2key="equip_sn" readonly/>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-2 control-label">{{Nom équipement}}</label>
                            <div class="col-lg-3">
                                <input type="text" class="eqLogicAttr form-control" data-l1key="id" style="display : none;" />
                                <input type="text" class="eqLogicAttr form-control" data-l1key="name" placeholder="{{Nom de la husqvarna}}"/>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-2 control-label" >{{Objet parent}}</label>
                            <div class="col-lg-3">
                                <select class="form-control eqLogicAttr" data-l1key="object_id">
                                    <option value="">{{Aucun}}</option>
                                    <?php
                                    foreach (jeeObject::all() as $object) {
                                        echo '<option value="' . $object->getId() . '">' . $object->getName() . '</option>'."\n";
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-2 control-label">{{Catégorie}}</label>
                            <div class="col-lg-8">
                                <?php
                                foreach (jeedom::getConfiguration('eqLogic:category') as $key => $value) {
                                    echo '<label class="checkbox-inline">'."\n";
                                    echo '<input type="checkbox" class="eqLogicAttr" data-l1key="category" data-l2key="' . $key . '" />' . $value['name'];
                                    echo '</label>'."\n";
                                }
                                ?>

                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-md-2 control-label" >{{Activer}}</label>
                            <div class="col-md-1">
                                <input type="checkbox" class="eqLogicAttr" data-l1key="isEnable" checked/>
                            </div>
                            <label class="col-lg-2 control-label" >{{Visible}}</label>
                            <div class="col-lg-1">
                                <input type="checkbox" class="eqLogicAttr" data-l1key="isVisible" checked/>
                            </div>
                        </div>
                    </fieldset>
                </form>
			</div>
			<div role="tabpanel" class="tab-pane" id="planiftab">
                <form class="form-horizontal">
                    <fieldset>
                        <legend>
                          <i class="fa fa-arrow-circle-left eqLogicAction cursor" data-action="returnToThumbnailDisplay"></i> {{Planification du fonctionnement du robot}}
                          <i class='fa fa-cogs eqLogicAction pull-right cursor expertModeVisible' data-action='configure'></i>
                        </legend>
                        <div class="form-group" style="min-height: 10px;">
                        </div>
                        <legend>{{Planification par zones}}</legend>
                        <div class="form-group">
                            <label class="col-sm-3 control-label" >{{Gestion de 2 zones}}</label>
                            <div class="col-md-1">
                                <input type="checkbox" class="eqLogicAttr" data-l1key="configuration" data-l2key="enable_2_areas"/>
                            </div>
                        </div>
                        <div class="form-group">
                          <label class="col-sm-3 control-label" >{{Commande d'activation de la zone 1}}</label>
                          <div class="col-sm-4">
                            <div class="input-group">
                              <input type="text" class="eqLogicAttr form-control roundedLeft" data-l1key="configuration" data-l2key="cmd_set_zone_1"/>
                              <span class="input-group-btn">
                                <a class="btn btn-default listCmdActionOther roundedRight"><i class="fas fa-list-alt"></i></a>
                              </span>
                            </div>
                          </div>
                        </div>
                        <div class="form-group">
                          <label class="col-sm-3 control-label" >{{Commande d'activation de la zone 2}}</label>
                          <div class="col-sm-4">
                            <div class="input-group">
                              <input type="text" class="eqLogicAttr form-control roundedLeft" data-l1key="configuration" data-l2key="cmd_set_zone_2"/>
                              <span class="input-group-btn">
                                <a class="btn btn-default listCmdActionOther roundedRight"><i class="fas fa-list-alt"></i></a>
                              </span>
                            </div>
                          </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-3 control-label" >{{Pourcentage du temps sur la zone 1}}</label>
                            <div class="col-md-1">
                              <input type="text" class="eqLogicAttr form-control roundedLeft" data-l1key="configuration" data-l2key="zone1_ratio" placeholder="50%"/>
                            </div>
                        </div>
                        <legend>{{Utilisation de la météo}}</legend>
                        <div class="form-group">
                          <label class="col-sm-3 control-label" >{{Pluie prévue dans l'heure}}</label>
                          <div class="col-sm-4">
                            <div class="input-group">
                              <input type="text" class="eqLogicAttr form-control roundedLeft" data-l1key="configuration" data-l2key="info_pluie_1h"/>
                              <span class="input-group-btn">
                                <a class="btn btn-default listCmdInfoNumeric roundedRight"><i class="fas fa-list-alt"></i></a>
                              </span>
                            </div>
                          </div>
                        </div>
                        <div class="form-group">
                          <label class="col-sm-3 control-label" >{{Prévision à 0-5 mn}}</label>
                          <div class="col-sm-4">
                            <div class="input-group">
                              <input type="text" class="eqLogicAttr form-control roundedLeft" data-l1key="configuration" data-l2key="info_pluie_5mn"/>
                              <span class="input-group-btn">
                                <a class="btn btn-default listCmdInfoNumeric roundedRight"><i class="fas fa-list-alt"></i></a>
                              </span>
                            </div>
                          </div>
                        </div>
                        <legend>{{Calendrier de fonctionnement}}</legend>
                        <div class="form-group" style="text-align:center">
                            <label class="col-sm-3 control-label"></label>
                            <div class="col-md-4 form-control">
                              <label class="control-label" style="min-width:80px">{{Plage horaire 1}}</label>
                            </div>
                            <div class="col-md-4 form-control">
                              <label class="control-label" style="min-width:80px">{{Plage horaire 2}}</label>
                            </div>
                        </div>
                        <div class="form-group" style="text-align:center">
                            <label class="col-sm-3 control-label" ></label>
                            <div class="col-md-4 form-control">
                              <label class="control-label" style="min-width:80px;">{{Début - Fin - Active - Zone 1 / 2 / 3(Alt)}}</label>
                            </div>
                            <div class="col-md-4 form-control">
                              <label class="control-label" style="min-width:80px;">{{Début - Fin - Active - Zone 1 / 2 / 3(Alt)}}</label>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-3 control-label" >{{Lundi}}</label>
                            <div class="col-md-4">
                              <a style="margin-left:5px;" class="btn btn-info btn-sm tooltips" id='bt_lun_pl1s_m'>{{-}}</a>
                              <input type="text" style="display : inline-block; width: 60px;" class="eqLogicAttr form-control roundedLeft" data-l1key="configuration" data-l2key="lun_ts1_begin"/>                              
                              <a style="margin-right:5px;" class="btn btn-info btn-sm tooltips" id='bt_lun_pl1s_p'>{{+}}</a>
                              <a style="margin-left:5px;" class="btn btn-info btn-sm tooltips" id='bt_lun_pl1e_m'>{{-}}</a>
                              <input type="text" style="display : inline-block; width: 60px;" class="eqLogicAttr form-control roundedLeft" data-l1key="configuration" data-l2key="lun_ts1_end"/>
                              <a style="margin-right:5px;" class="btn btn-info btn-sm tooltips" id='bt_lun_pl1e_p'>{{+}}</a>
                              <input type="checkbox" class="eqLogicAttr" data-l1key="configuration" data-l2key="lun_en_ts1" checked/>
                              <input type="text" style="display : inline-block; width:40px;" class="eqLogicAttr form-control roundedLeft" data-l1key="configuration" data-l2key="lun_ts1_zone"/>
                              <a style="margin-left:5px;" class="btn btn-info btn-sm tooltips" id='bt_lun_zn1_p'>{{+}}</a>
                            </div>
                            <div class="col-md-4">
                              <a style="margin-left:5px;" class="btn btn-info btn-sm tooltips" id='bt_lun_pl2s_m'>{{-}}</a>
                              <input type="text" style="display : inline-block; width: 60px;" class="eqLogicAttr form-control roundedLeft" data-l1key="configuration" data-l2key="lun_ts2_begin"/>
                              <a style="margin-right:5px;" class="btn btn-info btn-sm tooltips" id='bt_lun_pl2s_p'>{{+}}</a>
                              <a style="margin-left:5px;" class="btn btn-info btn-sm tooltips" id='bt_lun_pl2e_m'>{{-}}</a>
                              <input type="text" style="display : inline-block; width: 60px;" class="eqLogicAttr form-control roundedLeft" data-l1key="configuration" data-l2key="lun_ts2_end"/>
                              <a style="margin-right:5px;" class="btn btn-info btn-sm tooltips" id='bt_lun_pl2e_p'>{{+}}</a>
                              <input type="checkbox"  class="eqLogicAttr" data-l1key="configuration" data-l2key="lun_en_ts2" />
                              <input type="text" style="display : inline-block; width:40px;" class="eqLogicAttr form-control roundedLeft" data-l1key="configuration" data-l2key="lun_ts2_zone"/>
                              <a style="margin-left:5px;" class="btn btn-info btn-sm tooltips" id='bt_lun_zn2_p'>{{+}}</a>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-3 control-label" >{{Mardi}}</label>
                            <div class="col-md-4">
                              <a style="margin-left:5px;" class="btn btn-info btn-sm tooltips" id='bt_mar_pl1s_m'>{{-}}</a>
                              <input type="text" style="display : inline-block; width: 60px;" class="eqLogicAttr form-control roundedLeft" data-l1key="configuration" data-l2key="mar_ts1_begin"/>
                              <a style="margin-right:5px;" class="btn btn-info btn-sm tooltips" id='bt_mar_pl1s_p'>{{+}}</a>
                              <a style="margin-left:5px;" class="btn btn-info btn-sm tooltips" id='bt_mar_pl1e_m'>{{-}}</a>
                              <input type="text" style="display : inline-block; width: 60px;" class="eqLogicAttr form-control roundedLeft" data-l1key="configuration" data-l2key="mar_ts1_end"/>
                              <a style="margin-right:5px;" class="btn btn-info btn-sm tooltips" id='bt_mar_pl1e_p'>{{+}}</a>
                              <input type="checkbox" class="eqLogicAttr" data-l1key="configuration" data-l2key="mar_en_ts1" checked/>
                              <input type="text" style="display : inline-block; width:40px;" class="eqLogicAttr form-control roundedLeft" data-l1key="configuration" data-l2key="mar_ts1_zone"/>
                              <a style="margin-left:5px;" class="btn btn-info btn-sm tooltips" id='bt_mar_zn1_p'>{{+}}</a>
                            </div>
                            <div class="col-md-4">
                              <a style="margin-left:5px;" class="btn btn-info btn-sm tooltips" id='bt_mar_pl2s_m'>{{-}}</a>
                              <input type="text" style="display : inline-block; width: 60px;" class="eqLogicAttr form-control roundedLeft" data-l1key="configuration" data-l2key="mar_ts2_begin"/>
                              <a style="margin-right:5px;" class="btn btn-info btn-sm tooltips" id='bt_mar_pl2s_p'>{{+}}</a>
                              <a style="margin-left:5px;" class="btn btn-info btn-sm tooltips" id='bt_mar_pl2e_m'>{{-}}</a>
                              <input type="text" style="display : inline-block; width: 60px;" class="eqLogicAttr form-control roundedLeft" data-l1key="configuration" data-l2key="mar_ts2_end"/>
                              <a style="margin-right:5px;" class="btn btn-info btn-sm tooltips" id='bt_mar_pl2e_p'>{{+}}</a>
                              <input type="checkbox" class="eqLogicAttr" data-l1key="configuration" data-l2key="mar_en_ts2"/>
                              <input type="text" style="display : inline-block; width:40px;" class="eqLogicAttr form-control roundedLeft" data-l1key="configuration" data-l2key="mar_ts2_zone"/>
                              <a style="margin-left:5px;" class="btn btn-info btn-sm tooltips" id='bt_mar_zn2_p'>{{+}}</a>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-3 control-label" >{{Mercredi}}</label>
                            <div class="col-md-4">
                              <a style="margin-left:5px;" class="btn btn-info btn-sm tooltips" id='bt_mer_pl1s_m'>{{-}}</a>
                              <input type="text" style="display : inline-block; width: 60px;" class="eqLogicAttr form-control roundedLeft" data-l1key="configuration" data-l2key="mer_ts1_begin"/>
                              <a style="margin-right:5px;" class="btn btn-info btn-sm tooltips" id='bt_mer_pl1s_p'>{{+}}</a>
                              <a style="margin-left:5px;" class="btn btn-info btn-sm tooltips" id='bt_mer_pl1e_m'>{{-}}</a>
                              <input type="text" style="display : inline-block; width: 60px;" class="eqLogicAttr form-control roundedLeft" data-l1key="configuration" data-l2key="mer_ts1_end"/>
                              <a style="margin-right:5px;" class="btn btn-info btn-sm tooltips" id='bt_mer_pl1e_p'>{{+}}</a>
                              <input type="checkbox" class="eqLogicAttr" data-l1key="configuration" data-l2key="mer_en_ts1" checked/>
                              <input type="text" style="display : inline-block; width:40px;" class="eqLogicAttr form-control roundedLeft" data-l1key="configuration" data-l2key="mer_ts1_zone"/>
                              <a style="margin-left:5px;" class="btn btn-info btn-sm tooltips" id='bt_mer_zn1_p'>{{+}}</a>
                            </div>
                            <div class="col-md-4">
                              <a style="margin-left:5px;" class="btn btn-info btn-sm tooltips" id='bt_mer_pl2s_m'>{{-}}</a>
                              <input type="text" style="display : inline-block; width: 60px;" class="eqLogicAttr form-control roundedLeft" data-l1key="configuration" data-l2key="mer_ts2_begin"/>
                              <a style="margin-right:5px;" class="btn btn-info btn-sm tooltips" id='bt_mer_pl2s_p'>{{+}}</a>
                              <a style="margin-left:5px;" class="btn btn-info btn-sm tooltips" id='bt_mer_pl2e_m'>{{-}}</a>
                              <input type="text" style="display : inline-block; width: 60px;" class="eqLogicAttr form-control roundedLeft" data-l1key="configuration" data-l2key="mer_ts2_end"/>
                              <a style="margin-right:5px;" class="btn btn-info btn-sm tooltips" id='bt_mer_pl2e_p'>{{+}}</a>
                              <input type="checkbox" class="eqLogicAttr" data-l1key="configuration" data-l2key="mer_en_ts2"/>
                              <input type="text" style="display : inline-block; width:40px;" class="eqLogicAttr form-control roundedLeft" data-l1key="configuration" data-l2key="mer_ts2_zone"/>
                              <a style="margin-left:5px;" class="btn btn-info btn-sm tooltips" id='bt_mer_zn2_p'>{{+}}</a>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-3 control-label" >{{Jeudi}}</label>
                            <div class="col-md-4">
                              <a style="margin-left:5px;" class="btn btn-info btn-sm tooltips" id='bt_jeu_pl1s_m'>{{-}}</a>
                              <input type="text" style="display : inline-block; width: 60px;" class="eqLogicAttr form-control roundedLeft" data-l1key="configuration" data-l2key="jeu_ts1_begin"/>
                              <a style="margin-right:5px;" class="btn btn-info btn-sm tooltips" id='bt_jeu_pl1s_p'>{{+}}</a>
                              <a style="margin-left:5px;" class="btn btn-info btn-sm tooltips" id='bt_jeu_pl1e_m'>{{-}}</a>
                              <input type="text" style="display : inline-block; width: 60px;" class="eqLogicAttr form-control roundedLeft" data-l1key="configuration" data-l2key="jeu_ts1_end"/>
                              <a style="margin-right:5px;" class="btn btn-info btn-sm tooltips" id='bt_jeu_pl1e_p'>{{+}}</a>
                              <input type="checkbox" class="eqLogicAttr" data-l1key="configuration" data-l2key="jeu_en_ts1" checked/>
                              <input type="text" style="display : inline-block; width:40px;" class="eqLogicAttr form-control roundedLeft" data-l1key="configuration" data-l2key="jeu_ts1_zone"/>
                              <a style="margin-left:5px;" class="btn btn-info btn-sm tooltips" id='bt_jeu_zn1_p'>{{+}}</a>
                            </div>
                            <div class="col-md-4">
                              <a style="margin-left:5px;" class="btn btn-info btn-sm tooltips" id='bt_jeu_pl2s_m'>{{-}}</a>
                              <input type="text" style="display : inline-block; width: 60px;" class="eqLogicAttr form-control roundedLeft" data-l1key="configuration" data-l2key="jeu_ts2_begin"/>
                              <a style="margin-right:5px;" class="btn btn-info btn-sm tooltips" id='bt_jeu_pl2s_p'>{{+}}</a>
                              <a style="margin-left:5px;" class="btn btn-info btn-sm tooltips" id='bt_jeu_pl2e_m'>{{-}}</a>
                              <input type="text" style="display : inline-block; width: 60px;" class="eqLogicAttr form-control roundedLeft" data-l1key="configuration" data-l2key="jeu_ts2_end"/>
                              <a style="margin-right:5px;" class="btn btn-info btn-sm tooltips" id='bt_jeu_pl2e_p'>{{+}}</a>
                              <input type="checkbox" class="eqLogicAttr" data-l1key="configuration" data-l2key="jeu_en_ts2"/>
                              <input type="text" style="display : inline-block; width:40px;" class="eqLogicAttr form-control roundedLeft" data-l1key="configuration" data-l2key="jeu_ts2_zone"/>
                              <a style="margin-left:5px;" class="btn btn-info btn-sm tooltips" id='bt_jeu_zn2_p'>{{+}}</a>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-3 control-label" >{{Vendredi}}</label>
                            <div class="col-md-4">
                              <a style="margin-left:5px;" class="btn btn-info btn-sm tooltips" id='bt_ven_pl1s_m'>{{-}}</a>
                              <input type="text" style="display : inline-block; width: 60px;" class="eqLogicAttr form-control roundedLeft" data-l1key="configuration" data-l2key="ven_ts1_begin"/>
                              <a style="margin-right:5px;" class="btn btn-info btn-sm tooltips" id='bt_ven_pl1s_p'>{{+}}</a>
                              <a style="margin-left:5px;" class="btn btn-info btn-sm tooltips" id='bt_ven_pl1e_m'>{{-}}</a>
                              <input type="text" style="display : inline-block; width: 60px;" class="eqLogicAttr form-control roundedLeft" data-l1key="configuration" data-l2key="ven_ts1_end"/>
                              <a style="margin-right:5px;" class="btn btn-info btn-sm tooltips" id='bt_ven_pl1e_p'>{{+}}</a>
                              <input type="checkbox" class="eqLogicAttr" data-l1key="configuration" data-l2key="ven_en_ts1" checked/>
                              <input type="text" style="display : inline-block; width:40px;" class="eqLogicAttr form-control roundedLeft" data-l1key="configuration" data-l2key="ven_ts1_zone"/>
                              <a style="margin-left:5px;" class="btn btn-info btn-sm tooltips" id='bt_ven_zn1_p'>{{+}}</a>
                            </div>
                            <div class="col-md-4">
                              <a style="margin-left:5px;" class="btn btn-info btn-sm tooltips" id='bt_ven_pl2s_m'>{{-}}</a>
                              <input type="text" style="display : inline-block; width: 60px;" class="eqLogicAttr form-control roundedLeft" data-l1key="configuration" data-l2key="ven_ts2_begin"/>
                              <a style="margin-right:5px;" class="btn btn-info btn-sm tooltips" id='bt_ven_pl2s_p'>{{+}}</a>
                              <a style="margin-left:5px;" class="btn btn-info btn-sm tooltips" id='bt_ven_pl2e_m'>{{-}}</a>
                              <input type="text" style="display : inline-block; width: 60px;" class="eqLogicAttr form-control roundedLeft" data-l1key="configuration" data-l2key="ven_ts2_end"/>
                              <a style="margin-right:5px;" class="btn btn-info btn-sm tooltips" id='bt_ven_pl2e_p'>{{+}}</a>
                              <input type="checkbox" class="eqLogicAttr" data-l1key="configuration" data-l2key="ven_en_ts2" />
                              <input type="text" style="display : inline-block; width:40px;" class="eqLogicAttr form-control roundedLeft" data-l1key="configuration" data-l2key="ven_ts2_zone"/>
                              <a style="margin-left:5px;" class="btn btn-info btn-sm tooltips" id='bt_ven_zn2_p'>{{+}}</a>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-3 control-label" >{{Samedi}}</label>
                            <div class="col-md-4">
                              <a style="margin-left:5px;" class="btn btn-info btn-sm tooltips" id='bt_sam_pl1s_m'>{{-}}</a>
                              <input type="text" style="display : inline-block; width: 60px;" class="eqLogicAttr form-control roundedLeft" data-l1key="configuration" data-l2key="sam_ts1_begin"/>
                              <a style="margin-right:5px;" class="btn btn-info btn-sm tooltips" id='bt_sam_pl1s_p'>{{+}}</a>
                              <a style="margin-left:5px;" class="btn btn-info btn-sm tooltips" id='bt_sam_pl1e_m'>{{-}}</a>
                              <input type="text" style="display : inline-block; width: 60px;" class="eqLogicAttr form-control roundedLeft" data-l1key="configuration" data-l2key="sam_ts1_end"/>
                              <a style="margin-right:5px;" class="btn btn-info btn-sm tooltips" id='bt_sam_pl1e_p'>{{+}}</a>
                              <input type="checkbox" class="eqLogicAttr" data-l1key="configuration" data-l2key="sam_en_ts1" checked/>
                              <input type="text" style="display : inline-block; width:40px;" class="eqLogicAttr form-control roundedLeft" data-l1key="configuration" data-l2key="sam_ts1_zone"/>
                              <a style="margin-left:5px;" class="btn btn-info btn-sm tooltips" id='bt_sam_zn1_p'>{{+}}</a>
                            </div>
                            <div class="col-md-4">
                              <a style="margin-left:5px;" class="btn btn-info btn-sm tooltips" id='bt_sam_pl2s_m'>{{-}}</a>
                              <input type="text" style="display : inline-block; width: 60px;" class="eqLogicAttr form-control roundedLeft" data-l1key="configuration" data-l2key="sam_ts2_begin"/>
                              <a style="margin-right:5px;" class="btn btn-info btn-sm tooltips" id='bt_sam_pl2s_p'>{{+}}</a>
                              <a style="margin-left:5px;" class="btn btn-info btn-sm tooltips" id='bt_sam_pl2e_m'>{{-}}</a>
                              <input type="text" style="display : inline-block; width: 60px;" class="eqLogicAttr form-control roundedLeft" data-l1key="configuration" data-l2key="sam_ts2_end"/>
                              <a style="margin-right:5px;" class="btn btn-info btn-sm tooltips" id='bt_sam_pl2e_p'>{{+}}</a>
                              <input type="checkbox" class="eqLogicAttr" data-l1key="configuration" data-l2key="sam_en_ts2"/>
                              <input type="text" style="display : inline-block; width:40px;" class="eqLogicAttr form-control roundedLeft" data-l1key="configuration" data-l2key="sam_ts2_zone"/>
                              <a style="margin-left:5px;" class="btn btn-info btn-sm tooltips" id='bt_sam_zn2_p'>{{+}}</a>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-3 control-label" >{{Dimanche}}</label>
                            <div class="col-md-4">
                              <a style="margin-left:5px;" class="btn btn-info btn-sm tooltips" id='bt_dim_pl1s_m'>{{-}}</a>
                              <input type="text" style="display : inline-block; width: 60px;" class="eqLogicAttr form-control roundedLeft" data-l1key="configuration" data-l2key="dim_ts1_begin"/>
                              <a style="margin-right:5px;" class="btn btn-info btn-sm tooltips" id='bt_dim_pl1s_p'>{{+}}</a>
                              <a style="margin-left:5px;" class="btn btn-info btn-sm tooltips" id='bt_dim_pl1e_m'>{{-}}</a>
                              <input type="text" style="display : inline-block; width: 60px;" class="eqLogicAttr form-control roundedLeft" data-l1key="configuration" data-l2key="dim_ts1_end"/>
                              <a style="margin-right:5px;" class="btn btn-info btn-sm tooltips" id='bt_dim_pl1e_p'>{{+}}</a>
                              <input type="checkbox" class="eqLogicAttr" data-l1key="configuration" data-l2key="dim_en_ts1" checked/>
                              <input type="text" style="display : inline-block; width:40px;" class="eqLogicAttr form-control roundedLeft" data-l1key="configuration" data-l2key="dim_ts1_zone"/>
                              <a style="margin-left:5px;" class="btn btn-info btn-sm tooltips" id='bt_dim_zn1_p'>{{+}}</a>
                            </div>
                            <div class="col-md-4">
                              <a style="margin-left:5px;" class="btn btn-info btn-sm tooltips" id='bt_dim_pl2s_m'>{{-}}</a>
                              <input type="text" style="display : inline-block; width: 60px;" class="eqLogicAttr form-control roundedLeft" data-l1key="configuration" data-l2key="dim_ts2_begin"/>
                              <a style="margin-right:5px;" class="btn btn-info btn-sm tooltips" id='bt_dim_pl2s_p'>{{+}}</a>
                              <a style="margin-left:5px;" class="btn btn-info btn-sm tooltips" id='bt_dim_pl2e_m'>{{-}}</a>
                              <input type="text" style="display : inline-block; width: 60px;" class="eqLogicAttr form-control roundedLeft" data-l1key="configuration" data-l2key="dim_ts2_end"/>
                              <a style="margin-right:5px;" class="btn btn-info btn-sm tooltips" id='bt_dim_pl2e_p'>{{+}}</a>
                              <input type="checkbox" class="eqLogicAttr" data-l1key="configuration" data-l2key="dim_en_ts2"/>
                              <input type="text" style="display : inline-block; width:40px;" class="eqLogicAttr form-control roundedLeft" data-l1key="configuration" data-l2key="dim_ts2_zone"/>
                              <a style="margin-left:5px;" class="btn btn-info btn-sm tooltips" id='bt_dim_zn2_p'>{{+}}</a>
                            </div>
                        </div>
                        <div class="form-group" style="min-height: 20px;">
                        </div>
                        <div class="form-group" style="text-align:center">
                            <label class="col-sm-3 control-label"></label>
                            <div class="col-md-4 form-control">
                              <label class="control-label" style="min-width:80px">{{Initialisation plage horaire 1 ou 2}}</label>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-3 control-label" ></label>
                            <div class="col-md-4">
                              <a style="margin-left:5px;" class="btn btn-info btn-sm tooltips" id='bt_inits_m'>{{-}}</a>
                              <input type="text" style="display : inline-block; width: 60px;" class="form-control roundedLeft" id="init_start" value="11:00"/>                              
                              <a style="margin-right:5px;" class="btn btn-info btn-sm tooltips" id='bt_inits_p'>{{+}}</a>

                              <a style="margin-left:5px;" class="btn btn-info btn-sm tooltips" id='bt_inite_m'>{{-}}</a>
                              <input type="text" style="display : inline-block; width: 60px;" class=" form-control roundedLeft" id="init_end" value="20:00"/>
                              <a style="margin-right:5px;" class="btn btn-info btn-sm tooltips" id='bt_inite_p'>{{+}}</a>
                              <input type="checkbox"  id="init_valid" checked/>
                              <input type="text" style="display : inline-block; width:40px;" class="form-control roundedLeft"  id="init_zone" value="1"/>
                              <a style="margin-left:5px;" class="btn btn-info btn-sm tooltips" id='bt_init_zn_p'>{{+}}</a>
                            </div>
                            <div class="col-md-4">
                               <input type="radio" id="rb_init1" name="plage_init" value="1" checked><label for="rb_init1">&nbspPlage 1</label>
                               <input type="radio" id="rb_init2" name="plage_init" value="2"><label for="rb_init2">&nbspPlage 2 &nbsp&nbsp=></label>
                              <a style="margin-right:5px;" class="btn btn-info btn-sm tooltips" id='bt_init'>{{Init.}}</a>
                            </div>
                        </div>
                    </fieldset>
                </form>
			</div>
			<div role="tabpanel" class="tab-pane" id="confmaptab">
                <form class="form-horizontal">
                    <fieldset>
                        <legend>
                          <i class="fa fa-arrow-circle-left eqLogicAction cursor" data-action="returnToThumbnailDisplay"></i> {{Définition de l'image de localisation}}
                          <i class='fa fa-cogs eqLogicAction pull-right cursor expertModeVisible' data-action='configure'></i>
                        </legend>
                        <div class="form-group" style="min-height: 10px;">
                        </div>
                        <legend>{{Image de localisation}}</legend>
                        <div class="form-group">
                          <label class="col-sm-3 control-label">{{Placer un fichier nommé "maison.png" dans le dossier "data" du plugin.<br>Le fichier doit faire autour de 500 x 500 pixels. L'image doit apparaitre à côté}}</label>
                          <div class="col-lg-3">
                            <img class="pull-left" src="plugins/husqvarna_map/data/maison.png" />
                          </div>
                        </div>
                        <div class="form-group" style="min-height: 10px;">
                        </div>
                        <div class="form-group">
                          <label class="col-sm-3 control-label">{{Facteur de taille du widget Dashboard}}</label>
                          <div class="col-lg-3">
                             <input type="text" class="eqLogicAttr configuration form-control" data-l1key="configuration" data-l2key="img_wdg_ratio"/>
                          </div>
                          <label class="col-lg-3">{{Par exemple: 70%}}</label>
                        </div>
                        <div class="form-group">
                          <label class="col-sm-3 control-label">{{Facteur de taille du widget Mobile}}</label>
                          <div class="col-lg-3">
                             <input type="text" class="eqLogicAttr configuration form-control" data-l1key="configuration" data-l2key="img_wdgm_ratio"/>
                          </div>
                          <label class="col-lg-3">{{Par exemple: 30%}}</label>
                        </div>
                        <div class="form-group">
                          <label class="col-sm-3 control-label">{{Facteur de taille pour le pannel}}</label>
                          <div class="col-lg-3">
                             <input type="text" class="eqLogicAttr configuration form-control" data-l1key="configuration" data-l2key="img_pan_ratio"/>
                          </div>
                          <label class="col-lg-3">{{Par exemple: 120%}}</label>
                        </div>
                        <div class="form-group" style="min-height: 20px;">
                        </div>
                        <div class="form-group">
                          <legend>{{Positions GPS de l'image de localisation}}</legend>
                        </div>
                        <div class="form-group">
                          <label class="col-sm-3 control-label">{{Haut/Gauche}}</label>
                          <div class="col-lg-3">
                             <input type="text" class="eqLogicAttr configuration form-control" data-l1key="configuration" data-l2key="gps_tl" placeholder="lat,lon"/>
                          </div>
                        </div>
                        <div class="form-group">
                          <label class="col-sm-3 control-label">{{Bas/Droite}}</label>
                          <div class="col-lg-3">
                             <input type="text" class="eqLogicAttr configuration form-control" data-l1key="configuration" data-l2key="gps_br" placeholder="lat,lon"/>
                          </div>
                        </div>
                    </fieldset>
                </form>
			</div>
			<div role="tabpanel" class="tab-pane" id="commandtab">
                <table id="table_cmd" class="table table-bordered table-condensed">
                    <thead>
                        <tr>
                            <th style="width: 50px;">#</th>
                            <th style="width: 230px;">{{Nom}}</th>
                            <th style="width: 110px;">{{Sous-Type}}</th>
                            <th style="width: 100px;">{{Paramètres}}</th>
                            <th style="width: 200px;"></th>
                        </tr>
                    </thead>
                    <tbody>

                    </tbody>
                </table>
		   </div>
		</div>
    </div>
</div>

<?php include_file('desktop', 'husqvarna_map', 'js', 'husqvarna_map'); ?>
<?php include_file('core', 'plugin.template', 'js'); ?>
