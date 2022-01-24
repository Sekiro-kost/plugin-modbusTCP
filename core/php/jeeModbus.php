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

if($result['FUNC'] == 'readF'){
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

}else if($result['FUNC'] == 'write'){
       log::add('modbus','debug','RETURNFUNCTIONWRITE'.json_encode($result));
       if($result['isOk'] == 'no'){
         message::add('modbus','ERREUR ECRITURE', 'plop', 'messageWriteError');
         message::removeAll('modbus', 'messageWriteError');

       }else{
          message::add('modbus','ECRITURE OKI', 'plopr', 'messageWriteOk');
          message::removeAll('modbus', 'messageWriteOk');

       }



}
