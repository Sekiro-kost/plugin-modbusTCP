
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


/* Permet la réorganisation des commandes dans l'équipement */
$("#table_cmd").sortable({
  axis: "y",
  cursor: "move",
  items: ".cmd",
  placeholder: "ui-state-highlight",
  tolerance: "intersect",
  forcePlaceholderSize: true
});




/* Fonction permettant l'affichage des commandes dans l'équipement */
function addCmdToTable(_cmd) {
  if (!isset(_cmd)) {
     var _cmd = {configuration: {}};
   }
   if (!isset(_cmd.configuration)) {
     _cmd.configuration = {};
   }
   var tr = '<tr class="cmd" data-cmd_id="' + init(_cmd.id) + '">';
   tr += '<td style="width:60px;">';
   tr += '<span class="cmdAttr" data-l1key="id"></span>';
   tr += '</td>';
   tr += '<td style="min-width:300px;width:350px;">';
   tr += '<div class="row">';
   tr += '<div class="col-xs-7">';
   tr += '<input class="cmdAttr form-control input-sm" data-l1key="name" placeholder="{{Nom de la commande}}">';
   tr += '<select class="cmdAttr form-control input-sm" data-l1key="value" style="display : none;margin-top : 5px;" title="{{Commande information liée}}">';
   tr += '<option value="">{{Aucune}}</option>';
   tr += '</select>';
   tr += '</div>';
   tr += '<div class="col-xs-5">';
   tr += '<a class="cmdAction btn btn-default btn-sm" data-l1key="chooseIcon"><i class="fas fa-flag"></i> {{Icône}}</a>';
   tr += '<span class="cmdAttr" data-l1key="display" data-l2key="icon" style="margin-left : 10px;"></span>';
   tr += '</div>';
   tr += '</div>';
   tr += '</td>';
   tr += '<td>';
   tr += '<span class="type" type="' + init(_cmd.type) + '">' + jeedom.cmd.availableType() + '</span>';
   tr += '<span class="subType" subType="' + init(_cmd.subType) + '"></span>';
   tr += '</td>';

   tr += '<td>';
   tr += '<select class="cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="choiceIO" id="choiceIO" style="margin-top : 5px;" title="{{Choisir Type}}">';
   tr += '<option value="discrete">{{Discrete Inputs}}</option>';
   tr += '<option value="coils">{{Coils}}</option>';
   tr += '<option value="inputRegisters">{{Input Registers}}</option>';
   tr += '<option value="holdingRegisters">{{Holding Registers}}</option>';
   tr += '</select>';
   tr += '</td>';
   tr += '<td>';
   tr += '<select class="cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="formatIO" style="margin-top : 5px;">';
   tr += '<option value="normalformat">{{Normal}}</option>';
   tr += '<option value="longformat">{{LongInteger}}</option>';
   tr += '<option value="floatformat">{{Float (Real4)}}</option>';
   tr += '</select>';
   tr += '<input class="cmdAttr form-control tooltips input-sm" data-l1key="configuration" data-l2key="startregister" placeholder="{{Registre départ}}" style="width:100%;"/>';
   tr += '<input class="cmdAttr form-control tooltips input-sm" data-l1key="configuration" data-l2key="nbbytes" placeholder="{{Nb de bytes}}" style="width:100%"/>';
   tr += '<select class="cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="choicefunctioncode" id="choicefunctioncode" style="margin-top : 5px;">';
   tr += '<option value="" selected>{{FONCTION CODE}}</option>';
   tr += '<option value="fc01" id="fc01">{{Fc1 Read Coils}}</option>';
   tr += '<option value="fc02" id="fc02">{{Fc2 Read Discrete}}</option>';
   tr += '<option value="fc03" id="fc03">{{Fc3 Read Holding Registers}}</option>';
   tr += '<option value="fc04" id="fc04">{{Fc4 Read Input Registers}}</option>';
   tr += '<option value="fc05" id="fc05">{{Fc5 Write Single Coil}}</option>';
   tr += '<option value="fc06" id="fc06">{{Fc6 Write Single Register}}</option>';
   tr += '<option value="fc15" id="fc15">{{Fc15 Write Multiple Coils}}</option>';
   tr += '<option value="fc16" id="fc16">{{Fc16 Write Multiple Registers}}</option>';
    tr += '</select>';
   tr += '</td>';
   tr += '<td style="min-width:80px;width:350px;">';
   tr += '<label class="checkbox-inline"><input type="checkbox" class="cmdAttr" data-l1key="isVisible" checked/>{{Afficher}}</label>';
   tr += '<label class="checkbox-inline"><input type="checkbox" class="cmdAttr" data-l1key="isHistorized" checked/>{{Historiser}}</label>';
   tr += '<label class="checkbox-inline"><input type="checkbox" class="cmdAttr" data-l1key="display" data-l2key="invertBinary"/>{{Inverser}}</label>';
   tr += '</td>';
   tr += '<td style="min-width:80px;width:200px;">';
   if (is_numeric(_cmd.id)) {
     tr += '<a class="btn btn-default btn-xs cmdAction" data-action="configure"><i class="fas fa-cogs"></i></a> ';
     tr += '<a class="btn btn-default btn-xs cmdAction" data-action="test"><i class="fas fa-rss"></i> Tester</a>';
   }
   tr += '<i class="fas fa-minus-circle pull-right cmdAction cursor" data-action="remove"></i></td>';
   tr += '</tr>';
   $('#table_cmd tbody').append(tr);
   var tr = $('#table_cmd tbody tr').last();
   jeedom.eqLogic.builSelectCmd({
     id:  $('.eqLogicAttr[data-l1key=id]').value(),
     filter: {type: 'info'},
     error: function (error) {
       $('#div_alert').showAlert({message: error.message, level: 'danger'});
     },
     success: function (result) {
       tr.find('.cmdAttr[data-l1key=value]').append(result);
       tr.setValues(_cmd, '.cmdAttr');
       jeedom.cmd.changeType(tr, init(_cmd.subType));
     }
   });
 }


/*
 $("#choiceIO").on('change', function() {
   console.log('coucou');
   var choiceIO = $('#choiceIO option:selected').val();
  console.log(choiceIO);
   if(choiceIO == "inputRegisters"){
      $("#choicefunctioncode option[value='fc01']").hide();
      $("#fc02").hide();
      $("#fc03").hide();
      $("#fc05").hide();
      $("#fc06").hide();
      $("#fc15").hide();
      $("#fc16").hide();

   }else if(choiceIO == ""){


   }
 });
*/
