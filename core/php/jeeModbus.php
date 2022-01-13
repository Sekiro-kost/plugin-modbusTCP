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

require_once __DIR__  . '/../../../../core/php/core.inc.php';
/*
 * Non obligatoire mais peut être utilisé si vous voulez charger en même temps que votre
 * plugin des librairies externes (ne pas oublier d'adapter plugin_info/info.xml).
 *
 *
 */
 if (!jeedom::apiAccess(init('apikey'), 'modbus')) {
   echo __('Vous n\'etes pas autorisé à effectuer cette action', __FILE__);
   die();
 }


 /*
if (init('test') != '') {
	echo 'OK';
	die();
}*/

$result = json_decode(file_get_contents("php://input"), true);

log::add('modbus','debug','Result:' . json_encode($result));


if (!is_array($result)) {
	die();
}

if($result['FUNC'] == 'readOne'){
    $dataCorrespond = $result['data'];
    log::add('modbus','debug','DATACORRESPOND : '.json_encode($dataCorrespond));
    $eqlogicId = $result['eqlogicId'];
    $cmdId = $result['cmdId'];
    $cmdsearch = cmd::byId($cmdId);
    if(is_object($cmdsearch)){
        if($result['multiple'] == 'yes'){
            modbus::creaCommandsRead($result['data'], $result['adresse'], $cmdsearch);
            $cmds = cmd::byEqLogicId($eqlogicId);
            foreach($cmds as $cmd){
                $logicalId = $cmd->getLogicalId();
                if(strpos($logicalId, 'ReadCoil') !== FALSE){
  						        $adresse = $cmd->getConfiguration('adresseIO');
                      foreach ($result['result'] as $val) {
                           $cmdsearch->event($val);
                      }
                 }
            }
          }else{
              $val = $result['result'][0];
              $cmdsearch->event($val);
      }
      log::add('modbus','debug','JEE RETURN : '.json_encode($result['result'][0]));
     }
}elseif($result['FUNC'] == 'readCron'){
  log::add('modbus','debug','READCRON : '.json_encode($result['data']));
     $eqLogic = eqLogic::byId(intval($result['eqlogicId']));
     foreach($result['data'] as $k => $v){
           foreach($v as $cmdid => $val){
                   $cmd = cmd::byId($cmdid);
                   if(is_object($cmd)){
                      $cmd->event($val);
                  }
            }
      }
}elseif($result['FUNC'] == 'allCmds'){
        modbus::conversionFunction($result);
        log::add('modbus','debug','ALLCMDS : '.json_encode($result));
}elseif($result['FUNC'] == 'decoder'){
    log::add('modbus','debug','DECODER : '.json_encode($result));
    foreach($result['inputRegisters'] as $key => $value){
               foreach($value as $k => $v){
                      $valueToEvent = $v['valueConverse'];
                      $cmdId = intval($v['CmdId']);
                       log::add('modbus', 'debug', 'iCMDID: ' . $cmdId);
                        log::add('modbus', 'debug', 'VALUEEVENT: ' . $valueToEvent);
                      $cmdsearch = cmd::byId($cmdId);

               }

    }
}elseif($result['FUNC'] == 'readF'){
    $dataCorrespond = $result['data'];
    log::add('modbus','debug','DATACORRESPOND : '.json_encode($dataCorrespond));
     foreach($result['data'] as $id => $data){
            $eqLogicId = $id;
            foreach($data as $nameCmd => $infos){
              foreach($infos as $info){
                  $cmdId = intval($info['CmdId']);
                  $value = floatval($info['value']);
                  $cmdsearch = cmd::byId($cmdId);
                  if(is_object($cmdsearch)){
                    log::add('modbus','debug','CMDTOEVENT >>>>>>> ' .$cmdsearch->getName());
                      log::add('modbus','debug','VALUETOEVENT >>>>>>> ' .$value);
                      $cmdsearch->event($value);
                 }
               }
            }
      }
    log::add('modbus','debug','DECODER : '.json_encode($result));

}