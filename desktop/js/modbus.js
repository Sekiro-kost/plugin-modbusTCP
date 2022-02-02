
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

$("#table_cmd tbody").delegate(".cmdAttr[data-l2key=defCmd]", 'change', function (event) {
  var cmdNb = $(this).value();
  if (cmdNb == '4') {
    $(this).closest('tr').find('.isnegatif').hide();
    $(this).closest('tr').find('.choicefunctioncode').prop('disabled', 'disabled');
  }
  if (cmdNb == '1') {
    $(this).closest('tr').find('.isnegatif').hide();
    $(this).closest('tr').find('.choicefunctioncode').prop('disabled', 'disabled');
    $(this).closest('tr').find('.wordorder').show();
    $(this).closest('tr').find('.byteorder').show();
    $(this).closest('tr').find('.nbbytes').val('1');
    $(this).closest('tr').find('.nbbytes').prop('disabled', 'disabled');
  }
  if (cmdNb == '2') {
    $(this).closest('tr').find('.isnegatif').hide();
    $(this).closest('tr').find('.choicefunctioncode').prop('disabled', 'disabled');
    $(this).closest('tr').find('.wordorder').hide();
    $(this).closest('tr').find('.byteorder').hide();
  }
  if (cmdNb == '3') {
    $(this).closest('tr').find('.isnegatif').hide();
    $(this).closest('tr').find('.choicefunctioncode').hide();
    $(this).closest('tr').find('.offset').hide();
    $(this).closest('tr').find('.stepchoice').hide();
    $(this).closest('tr').find('.formatIO').hide();
    $(this).closest('tr').find('.wordorder').hide();
    $(this).closest('tr').find('.byteorder').hide();
    $(this).closest('tr').find('.nbbytes').hide();
    $(this).closest('tr').find('.startregister').hide();
    $(this).closest('tr').find('.valeurToAction').hide();
  }
});

$("#table_cmd tbody").delegate(".cmdAttr[data-l1key=type]", 'change', function (event) {
   $(this).closest('tr').find('.choicefunctioncode').val('');
   $(this).closest('tr').find('.sendValues').hide();

   var cmdType = $(this).value();


   if (cmdType == 'info') {
     $(this).closest('tr').find('.readOption').show();
     $(this).closest('tr').find('.writeOption').hide();
   }
   else { // action
     $(this).closest('tr').find('.readOption').hide();
     $(this).closest('tr').find('.writeOption').show();
     $(this).closest('tr').find('.valeurToAction').show();
     $(this).closest('tr').find('.request').show();
   }

  //var testing = $(this).closest('tr').find('.defCmd').value();
  //console.log(testing);

});


$("#table_cmd tbody").delegate(".cmdAttr[data-l1key=subType]", 'change', function (event) {
  var subType = $(this).value();
    if (subType == 'message') {
       $(this).closest('tr').find('.readOption').show();


    }
  });

/*
$("#table_cmd tbody").delegate(".cmdAttr[data-l1key=subType]", 'change', function (event) {
   var cmdSubType = $(this).value();

   if (cmdSubType == 'other') {
     $(this).closest('tr').find('.valeurToAction').show();
     $(this).closest('tr').find('.request').show();
   }
   else {
    $(this).closest('tr').find('.valeurToAction').hide();
    $(this).closest('tr').find('.request').hide();
    if (cmdSubType == 'slider') {
      $(this).closest('tr').find('.stepchoice').show();
    }
   }
});*/

/*
$("#table_cmd tbody").delegate(".cmdAttr[data-l1key=configuration][data-l2key=formatIO]", 'change', function (event) {
   var formatIO = $(this).value();
   if (formatIO == 'bitsformat') {
     $(this).closest('tr').find('.byteorder').hide();
     $(this).closest('tr').find('.wordorder').hide();
     $(this).closest('tr').find('.isnegatif').hide();

   }
   else {
     $(this).closest('tr').find('.byteorder').show();
     $(this).closest('tr').find('.wordorder').show();
     $(this).closest('tr').find('.isnegatif').show();
   }
   if (formatIO != 'floatformat') {
     $(this).closest('tr').find('.stepchoice').hide();
   } else {
     $(this).closest('tr').find('.stepchoice').show();
   }
});*/

$("#table_cmd tbody").delegate(".cmdAttr[data-l1key=configuration][data-l2key=choicefunctioncode]", 'change', function (event) {
   var fctCode = $(this).value();

  if(fctCode == 'fc01'){
     $(this).closest('tr').find('.wordorder').hide();
     $(this).closest('tr').find('.byteorder').hide();
     $(this).closest('tr').find('.isnegatif').hide();
     $(this).closest('tr').find('.formatIO').hide();
     $(this).closest('tr').find('.valeurToAction').hide();
     $(this).closest('tr').find('.stepchoice').hide();
	   $(this).closest('tr').find('.sendValues').hide();
     $(this).closest('tr').find('.isVisible').show();
     $(this).closest('tr').find('.nbbytes').show();
     $(this).closest('tr').find('.isVisible').prop('checked', true);
  }

  if(fctCode == 'fc02'){
     $(this).closest('tr').find('.wordorder').hide();
     $(this).closest('tr').find('.byteorder').hide();
     $(this).closest('tr').find('.isnegatif').hide();
     $(this).closest('tr').find('.formatIO').hide();
     $(this).closest('tr').find('.valeurToAction').hide();
     $(this).closest('tr').find('.stepchoice').hide();
	   $(this).closest('tr').find('.sendValues').hide();
     $(this).closest('tr').find('.isVisible').show();
     $(this).closest('tr').find('.nbbytes').show();
     $(this).closest('tr').find('.isVisible').prop('checked', true);
  }

  if(fctCode == 'fc03'){
     $(this).closest('tr').find('.wordorder').show();
     $(this).closest('tr').find('.byteorder').show();
     $(this).closest('tr').find('.isnegatif').show();
     $(this).closest('tr').find('.formatIO').show();
     $(this).closest('tr').find('.bitsformat').hide();
     $(this).closest('tr').find('.valeurToAction').hide();
     $(this).closest('tr').find('.stepchoice').hide();
	   $(this).closest('tr').find('.sendValues').show();
     $(this).closest('tr').find('.isVisible').show();
     $(this).closest('tr').find('.nbbytes').show();
     $(this).closest('tr').find('.isVisible').prop('checked', true);
  }

  if(fctCode == 'fc04'){
     $(this).closest('tr').find('.wordorder').show();
     $(this).closest('tr').find('.byteorder').show();
     $(this).closest('tr').find('.isnegatif').show();
     $(this).closest('tr').find('.formatIO').show();
     $(this).closest('tr').find('.bitsformat').hide();
     $(this).closest('tr').find('.valeurToAction').hide();
     $(this).closest('tr').find('.stepchoice').hide();
	   $(this).closest('tr').find('.sendValues').hide();
     $(this).closest('tr').find('.isVisible').show();
     $(this).closest('tr').find('.nbbytes').show();
     $(this).closest('tr').find('.isVisible').prop('checked', true);
  }

  if(fctCode == 'fc05'){
     $(this).closest('tr').find('.wordorder').hide();
     $(this).closest('tr').find('.byteorder').hide();
     $(this).closest('tr').find('.isnegatif').hide();
     $(this).closest('tr').find('.formatIO').hide();
     $(this).closest('tr').find('.valeurToAction').show();
     $(this).closest('tr').find('.stepchoice').hide();
	   $(this).closest('tr').find('.sendValues').hide();
     $(this).closest('tr').find('.isVisible').show();
     $(this).closest('tr').find('.nbbytes').hide();
     $(this).closest('tr').find('.isVisible').prop('checked', true);
  }

  if(fctCode == 'fc06'){
     $(this).closest('tr').find('.wordorder').show();
     $(this).closest('tr').find('.byteorder').show();
     $(this).closest('tr').find('.isnegatif').show();
     $(this).closest('tr').find('.formatIO').show();
     $(this).closest('tr').find('.bitsformat').hide();
     $(this).closest('tr').find('.valeurToAction').hide();
    /* $(this).closest('tr').find('.stepchoice').hide();*/
	   $(this).closest('tr').find('.sendValues').hide();
     $(this).closest('tr').find('.isVisible').show();
     $(this).closest('tr').find('.nbbytes').hide();
     $(this).closest('tr').find('.isVisible').prop('checked', true);
  }

  if(fctCode == 'fc15'){
     $(this).closest('tr').find('.wordorder').hide();
     $(this).closest('tr').find('.byteorder').hide();
     $(this).closest('tr').find('.isnegatif').hide();
     $(this).closest('tr').find('.formatIO').hide();
     $(this).closest('tr').find('.valeurToAction').hide();
     $(this).closest('tr').find('.stepchoice').hide();
     $(this).closest('tr').find('.sendValues').show();
     $(this).closest('tr').find('.isVisible').hide();
     $(this).closest('tr').find('.nbbytes').hide();
     $(this).closest('tr').find('.isVisible').prop('checked', false);
  }

  if(fctCode == 'fc16'){
     $(this).closest('tr').find('.wordorder').show();
     $(this).closest('tr').find('.byteorder').show();
     $(this).closest('tr').find('.isnegatif').show();
     $(this).closest('tr').find('.formatIO').hide();
     $(this).closest('tr').find('.bitsformat').hide();
     $(this).closest('tr').find('.valeurToAction').hide();
     $(this).closest('tr').find('.stepchoice').hide();
     $(this).closest('tr').find('.sendValues').show();
     $(this).closest('tr').find('.isVisible').show();
     $(this).closest('tr').find('.nbbytes').hide();
     $(this).closest('tr').find('.isVisible').prop('checked', false);
  }

});
   /*if(fctCode == 'fc01' || fctCode == 'fc02' || fctCode == 'fc05' || fctCode == 'fc15'){
     $(this).closest('tr').find('.formatIO').hide();
     $(this).closest('tr').find('.wordorder').hide();
     $(this).closest('tr').find('.byteorder').hide();
     $(this).closest('tr').find('.isnegatif').hide();
   }else{
     $(this).closest('tr').find('.formatIO').show();
     $(this).closest('tr').find('.wordorder').show();
     $(this).closest('tr').find('.byteorder').show();
     $(this).closest('tr').find('.isnegatif').show();
   }

   if(fctCode == 'fc03'){
     $(this).closest('tr').find('.sendValues').show();
     $(this).closest('tr').find('.isVisible').show();
   }
   if (fctCode == 'fc15') {
     $(this).closest('tr').find('.sendValues').show();
     $(this).closest('tr').find('.isVisible').hide();
     $(this).closest('tr').find('.nbbytes').hide();
     $(this).closest('tr').find('.isVisible').prop('checked', false);
   }
   if(fctCode == 'fc16'){
     $(this).closest('tr').find('.sendValues').show();
     $(this).closest('tr').find('.isVisible').hide();
     $(this).closest('tr').find('.isVisible').prop('checked', false);
     $(this).closest('tr').find('.formatIO').hide();
   }
   if(fctCode != 'fc15'){
     $(this).closest('tr').find('.isVisible').show();
     $(this).closest('tr').find('.nbbytes').show();
     $(this).closest('tr').find('.isVisible').prop('checked', true);
   }
   if(fctCode != 'fc16'){
     $(this).closest('tr').find('.isVisible').show();
     $(this).closest('tr').find('.isVisible').prop('checked', true);
     $(this).closest('tr').find('.formatIO').show();
   }
   else {
     $(this).closest('tr').find('.sendValues').hide();
     $(this).closest('tr').find('.isVisible').show();
   }
  */




$("#choicemodbus").on('change', function() {
     var typeModbus = $("#choicemodbus").val();
     if(typeModbus == 'tcp'){
         $("#div_paramsrtu").show();
         $("#ipuser").show();
         $("#div-bytesize").hide();
         $("#div-baudrate").hide();
         $("#div-stopbits").hide();
         $("#div-parity").hide();
         $("#div-portserial").hide();
         $("#div-unitid").hide();


        /* $("#paramsrtu").hide(); */
     }else if(typeModbus == 'rtu'){
            /* $("#paramsrtu").show();*/
         $("#div_paramsrtu").show();
         $("#ipuser").hide();
         $("#div-bytesize").show();
         $("#div-baudrate").show();
         $("#div-stopbits").show();
         $("#div-parity").show();
         $("#div-portserial").show();
         $("#div-unitid").show();
     }else if(typeModbus == 'ascii'){
       $("#div_paramsrtu").show();
       $("#ipuser").hide();
       $("#div-bytesize").show();
       $("#div-baudrate").show();
       $("#div-stopbits").show();
       $("#div-parity").show();
       $("#div-portserial").show();
       $("#div-unitid").show();
     }
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
   tr += '<input class="cmdAttr form-control input-sm defCmd" data-l1key="configuration" data-l2key="defCmd" style="display : none;" placeholder="{{defCmd}}">';
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
   tr += '<span class="type" id="' + init(_cmd.type) + '" type="' + init(_cmd.type) + '">' + jeedom.cmd.availableType() + '</span>';
   tr += '<span class="subType" subType="' + init(_cmd.subType) + '"></span>';
   tr += '</td>';
   tr += '<td style="min-width:150px;width:350px;">';
   tr += '<input class="cmdAttr form-control input-sm minVal" data-l1key="configuration" data-l2key="minValue" placeholder="{{Min.}}" title="{{Min.}}" style="width:30%;display:inline-block;"/> ';
   tr += '<input class="cmdAttr form-control input-sm maxVal" data-l1key="configuration" data-l2key="maxValue" placeholder="{{Max.}}" title="{{Max.}}" style="width:30%;display:inline-block;"/> ';
   tr += '<input class="cmdAttr form-control input-sm unitVal" data-l1key="unite" placeholder="{{Unité}}" title="{{Unité}}" style="width:30%;display:inline-block;"/>';
   tr += '</td>';
   tr += '<td>';
   tr += '<label class="checkbox isnegatif"><input type="checkbox" class="cmdAttr isnegatif" data-l1key="configuration" data-l2key="isnegatif">{{Valeur negative}}</label>';
   tr += '<label class="checkbox offset"><input type="checkbox" class="cmdAttr offset" data-l1key="configuration" data-l2key="offset">{{Offset}}</label>';
   tr += '<input class="cmdAttr form-control tooltips input-sm stepchoice" data-l1key="configuration" data-l2key="stepchoice" placeholder="{{Choisir le pas du slider (0.1, 0.5 etc..)}}" style="width:100%;"/>';
   tr += '</td>';
   tr += '<td>';
   tr += '<select class="cmdAttr form-control input-sm cmdAction choicefunctioncode" data-l1key="configuration" data-action="testFC" data-l2key="choicefunctioncode" id="choicefunctioncode" style="margin-top : 5px;font-weight:bold;">';
   tr += '<option value="" selected id="test" disabled>{{FONCTION CODE}}</option>';
   tr += '<option value="fc01" class="readOption" id="fc01">{{Fc1 Read Coils}}</option>';
   tr += '<option value="fc02" class="readOption" id="fc02">{{Fc2 Read Discrete}}</option>';
   tr += '<option value="fc03" class="readOption" id="fc03">{{Fc3 Read Holding Registers}}</option>';
   tr += '<option value="fc04" class="readOption" id="fc04">{{Fc4 Read Input Registers}}</option>';
   tr += '<option value="fc05" class="writeOption" id="fc05">{{Fc5 Write Single Coil}}</option>';
   tr += '<option value="fc06" class="writeOption" id="fc06">{{Fc6 Write Single Register}}</option>';
   tr += '<option value="fc15" class="writeOption" id="fc15">{{Fc15 Write Multiple Coils}}</option>';
   tr += '<option value="fc16" class="writeOption" id="fc16">{{Fc16 Write Multiple Registers}}</option>';
   tr += '</select>';
   tr += '<select class="cmdAttr form-control input-sm formatIO" data-l1key="configuration" data-l2key="formatIO" id="formatIO" style="margin-top : 5px;font-weight:bold;">';
   tr += '<option value="other" selected disabled>{{Format données}}</option>';
   tr += '<option value="bitsformat" class="bitsformat">{{Bits}}</option>';
   tr += '<option value="longformat" class="longformat">{{Long / Integer}}</option>';
   tr += '<option value="floatformat" class="floatformat">{{Float (Real4)}}</option>';
   //tr += '<option value="bcd" class="bcd">{{BCD}}</option>';
   tr += '</select>';
   tr += '<select class="cmdAttr form-control input-sm wordorder" data-l1key="configuration" data-l2key="wordorder" style="margin-top : 5px;font-weight:bold;">';
   tr += '<option value="" selected disabled>{{== Word Order ==}}</option>';
   tr += '<option value="littleword">{{Little First}}</option>';
   tr += '<option value="bigword">{{Big First}}</option>';
   tr += '</select>';
   tr += '<select class="cmdAttr form-control input-sm byteorder" data-l1key="configuration" data-l2key="byteorder" style="margin-top : 5px;font-weight:bold;">';
   tr += '<option value="" selected disabled>{{== Byte Order ==}}</option>';
   tr += '<option value="littlebyte">{{Little First}}</option>';
   tr += '<option value="bigbyte">{{Big First}}</option>';
   tr += '</select>';
   tr += '<input class="cmdAttr form-control tooltips input-sm startregister" data-l1key="configuration" data-l2key="startregister" placeholder="{{Registre départ}}" style="width:100%;"/>';
   tr += '<input class="cmdAttr form-control tooltips input-sm nbbytes" data-l1key="configuration" data-l2key="nbbytes" placeholder="{{Nb de bytes}}" style="width:100%"/>';

   tr += '<input type="number" class="cmdAttr form-control tooltips input-sm valeurToAction" data-l1key="configuration" data-l2key="valeurToAction" placeholder="{{Valeur à envoyer pour WriteCoil (0 ou 1)}}" style="width:100%; display: inline-block;"/>';
   //tr += '<textarea style="height: 35px; margin-top: 5px; margin-bottom: 0px; display: none;" class="cmdAttr form-control input-sm request" data-l1key="configuration" placeholder="Valeurs a envoyer aux coils" data-l2key="request"></textarea>';
   tr += '</td>';
   tr += '<td style="min-width:80px;width:350px;">';
   tr += '<label class="checkbox-inline isVisible"><input type="checkbox" class="cmdAttr isVisible" data-l1key="isVisible" checked/>{{Afficher}}</label>';
   tr += '<label class="checkbox-inline"><input type="checkbox" class="cmdAttr" data-l1key="isHistorized" checked/>{{Historiser}}</label>';
   tr += '<label class="checkbox-inline"><input type="checkbox" class="cmdAttr" data-l1key="display" data-l2key="invertBinary"/>{{Inverser}}</label>';
   tr += '</td>';
   tr += '<td style="min-width:80px;width:200px;">';
   if (is_numeric(_cmd.id)) {
    test = $('.cmdAction[data-action=testFC]').value();

     tr += '<a class="btn btn-default btn-xs cmdAction" data-action="configure"><i class="fas fa-cogs"></i></a> ';
     tr += '<a class="btn btn-default btn-xs cmdAction" data-action="test"><i class="fas fa-rss"></i> Tester</a>';
   /*  tr += '<a class="btn btn-primary btn-xs cmdAction sendValues" data-action="sendValues">{{Envoyer Valeurs}}</a>';*/
   }
   tr += '<i class="fas fa-minus-circle pull-right cmdAction cursor" data-action="remove"></i></td>';
   tr += '</tr>';
   $('#table_cmd tbody').append(tr);
  /*$('#table_cmd tbody tr:last').setValues(_cmd, '.cmdAttr');*/

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


/*
   $('.cmdAction[data-action=sendValues]').off().on('click', function() {
       console.log('dada');
         var sendValue = '';
      	 var sendValuePrev = '';
         var titleBootBox = '';
         var cmdtest = $(this).closest('tr');
         var choicefunctioncode = $(this).closest('tr').find('.choicefunctioncode').value();
         var maxlengthvalue = '';
         if (choicefunctioncode == 'fc03') {
           maxlengthvalue = 16;
           titleBootBox = 'Modifier Valeur';

          jeedom.cmd.execute({
           id: cmdtest.attr('data-cmd_id'),
           cache: 0,
           notify: false,
           async: false,
           success: function(result) {
              sendValuePrev = result.toString(2);
              sendValue = sendValuePrev.padStart(8, '0');

           }
           })
         }else if(choicefunctioncode == 'fc15'){
             var isMultipleCoil = '1';
             maxlengthvalue = 999;
             titleBootBox = 'Ecriture Multiple Coils';
             sendValue = 'Ecrire vos multiples Coils ( 0 ou 1 seulement)';
         }else if(choicefunctioncode == 'fc16'){
             var isMultipleRegisters = '1';
             maxlengthvalue = 999;
             titleBootBox = 'Ecriture Multiple Registers';
             sendValue = 'Ecrire vos multiples Registres';
       }
        console.log(cmdtest.attr('data-cmd_id'));
      if(sendValue != ''){

                        bootbox.prompt({
                        title: titleBootBox,
                        size: 'small',
                        maxlength: maxlengthvalue,
                        value :  sendValue,
                        callback: function (result) {

                          if (result) {
                            if(sendValue.length == result.length || isMultipleCoil == '1'  || isMultipleRegisters == '1'){
                              console.log(result)
                              if($.isNumeric(result) || isMultipleCoil == '1'  || isMultipleRegisters == '1'){
                                    let pattern = /[2-9]/g;
                                    let resultRegex = result.match(pattern);
                                    if(resultRegex == null || isMultipleRegisters == '1'){
                                          $('#div_alert').showAlert({message: '{{Envoi des valeurs en cours...}}', level: 'warning'})
                                          if (choicefunctioncode == 'fc03') {
                                            valueVar = parseInt(result,2)

                                          } else if(choicefunctioncode == 'fc15'){
                                            valueVar = result
                                          }else if(choicefunctioncode == 'fc16'){
                                            valueVar = result
                                          }
                                          $.ajax({
                                            type: "POST",
                                            url: "plugins/modbus/core/ajax/modbus.ajax.php",
                                            data: {
                                              action: "sendValues",
                                              functioncode : choicefunctioncode,
                                              cmd_id: cmdtest.attr('data-cmd_id'),
                                              id:  $('.eqLogicAttr[data-l1key=id]').value(),
                                              value: valueVar

                                            },
                                            dataType: 'json',
                                            error: function (request, status, error) {
                                              handleAjaxError(request, status, error, $('#div_alert'))
                                            },
                                            success: function (data) {
                                                      $('#div_alert').showAlert({message: '{{Tentative d ecriture......}}', level: 'success'})
                                                      $('#div_alert').fadeOut(10000)
                                            }
                                          })
                                    }else{
                                       $('#div_alert').showAlert({message: '{{Valeurs souhaitées : 0 ou 1 ...}}', level: 'warning'})
                                    }
                              }
                            else{
                              $('#div_alert').showAlert({message: '{{Caractere Invalide...}}', level: 'warning'})
                            }}
                              else{
                                $('#div_alert').showAlert({message: '{{Taille differente de l original...}}', level: 'warning'})


                             }

                          }
                        }

            })

      }else{

          $('#div_alert').showAlert({message: '{{La lecture n est pas encore faite, veuillez reactualiser la page}}', level: 'warning'})



      }



   })*/



















$('.cmdAttr[data-l1key=type]').on('change', function() {
/*
  var cmdtest = $(this).closest('tr');
  console.log(cmdtest.attr('data-cmd_id'));
  var plop = $('.cmdAction[data-action=testFC]').value();
  console.log(plop)
  var type = $(this).value();
  console.log(type);
    if(type == 'info'){
       var o = new Option('option text', 'value');
       o.innerHTML = 'option text';
       document.getElementById('selectList').appendChild(o);
        $('.cmdAction[data-action=testFC]')

    }else if(type == 'action'){
    $('.cmdAction[data-action=testFC]')

    }
*/


});

/*
$('#choicefunctioncode').on('change', function() {
  console.log('test')
  var plop = $('.cmdAction[data-action=testFC]').value();
  console.log(plop)

});*/


}