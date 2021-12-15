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
require_once __DIR__  . '/../../../../core/php/core.inc.php';

class modbus extends eqLogic {
    /*     * *************************Attributs****************************** */

  /*
   * Permet de définir les possibilités de personnalisation du widget (en cas d'utilisation de la fonction 'toHtml' par exemple)
   * Tableau multidimensionnel - exemple: array('custom' => true, 'custom::layout' => false)
	public static $_widgetPossibility = array();
   */

    /*     * ***********************Methode static*************************** */



      public static function cron() {
        /*foreach (eqLogic::byType('modbus',true) as $modbusEq) {
          $modbusEq->postSave();

        }*/
      }



      public static function cron5() {
      }



      public static function cron10() {
      }



      public static function cron15() {
      }



      public static function cron30() {
      }



      public static function cronHourly() {
      }



      public static function cronDaily() {
      }

     public static function deamon_info() {
     		$return = array();
     		$return['log'] = 'modbus';
     		$return['state'] = 'nok';

     		$pid_file = jeedom::getTmpFolder(__CLASS__) . '/deamon.pid';
     		if (file_exists($pid_file)) {
           if (@posix_getsid(trim(file_get_contents($pid_file)))) {
                $return['state'] = 'ok';
     			  } else {
     				shell_exec(system::getCmdSudo() . 'rm -rf ' . $pid_file . ' 2>&1 > /dev/null');
     			}
     		}
     		$return['launchable'] = 'ok';
     		return $return;
     	}

      public static function dependancy_info() {
                 $return = array();
                 $return['log'] = log::getPathToLog(__CLASS__ . '_update');
                 $return['progress_file'] = jeedom::getTmpFolder(__CLASS__) . '/dependency';
                 if (file_exists(jeedom::getTmpFolder(__CLASS__) . '/dependency')) {
                     $return['state'] = 'in_progress';
                 } else {
                     if (exec(system::getCmdSudo() . system::get('cmd_check') . '-Ec "python3\-pip|python3\-dev|python3\-pyudev|python3\-serial|python3\-requests|python3\-pymodbus"') < 5) {
                         $return['state'] = 'nok';
                     } elseif (exec(system::getCmdSudo() . 'pip3 list | grep -Ewc "pymodbus"') < 1) {
                         $return['state'] = 'nok';
                     } else {
                         $return['state'] = 'ok';
                     }
                 }
                 return $return;
      }


      public static function dependancy_install() {
          log::remove(__CLASS__ . '_update');
          return array('script' => dirname(__FILE__) . '/../../resources/install_#stype#.sh ' . jeedom::getTmpFolder(__CLASS__) . '/dependency', 'log' => log::getPathToLog(__CLASS__ . '_update'));
       }


       public static function deamon_start() {
            self::deamon_stop();
            $deamon_info = self::deamon_info();
            if ($deamon_info['launchable'] != 'ok') {
                throw new Exception(__('Veuillez vérifier la configuration', __FILE__));
            }

            $path = realpath(dirname(__FILE__) . '/../../resources/modbusd');
            $cmd = '/usr/bin/python3 ' . $path . '/modbusd.py';
            $cmd .= ' --loglevel ' . log::convertLogLevel(log::getLogLevel(__CLASS__));
            $cmd .= ' --socketport ' . config::byKey('socketport', __CLASS__, '55030');
            $cmd .= ' --sockethost 127.0.0.1';
            $cmd .= ' --callback ' . network::getNetworkAccess('internal', 'proto:127.0.0.1:port:comp') . '/plugins/modbus/core/php/jeeModbus.php';
            $cmd .= ' --apikey ' . jeedom::getApiKey(__CLASS__);
            $cmd .= ' --pid ' . jeedom::getTmpFolder(__CLASS__) . '/deamon.pid';
            log::add(__CLASS__, 'info', 'Lancement démon '.$cmd);
            $result = exec($cmd . ' >> ' . log::getPathToLog('modbus') . ' 2>&1 &');
            $i = 0;
            while ($i < 5) {
                $deamon_info = self::deamon_info();
                if ($deamon_info['state'] == 'ok') {
                    break;
                }
                sleep(1);
                $i++;
            }
            if ($i >= 5) {
                log::add(__CLASS__, 'error', __('Impossible de lancer le démon, vérifiez le log', __FILE__), 'unableStartDeamon');
                return false;
            }
            message::removeAll(__CLASS__, 'unableStartDeamon');
            return true;
       }


  public static function deamon_stop() {
        $pid_file = jeedom::getTmpFolder(__CLASS__) . '/deamon.pid';
        if (file_exists($pid_file)) {
            $pid = intval(trim(file_get_contents($pid_file)));
            system::kill($pid);
        }
        system::kill('modbusd.py');
        system::fuserk(config::byKey('socketport', 'modbus'));
        sleep(1);
    }



    public static function socketConnection($value){
        $socket = socket_create(AF_INET, SOCK_STREAM, 0);
        socket_set_timeout($socket,180);
        socket_connect($socket, '127.0.0.1', config::byKey('socketport', 'modbus'));
        socket_write($socket, $value, strlen($value));
        socket_close($socket);
    }




  public static function creaCommandsRead($type, $resultats, $adresseStart, $cmd){
          log::add(__CLASS__, 'debug', 'RESULTATSTATIC :' . json_encode($resultats));
          $eqLogic = $cmd->getEqLogic();
          /*$cmd->remove();*/
          foreach($resultats as $result){
             $adresse = intval($result[0]);
              $cmd = $eqLogic->getCmd(null, 'ReadCoil_'.$adresse);
              if (!is_object($cmd)) {
                  $cmd = new modbusCmd();
                  $cmd->setLogicalId('ReadCoil_'.$adresse);
                  $cmd->setIsVisible(1);
                  $cmd->setName(__('Readcoil_'.$adresse, __FILE__));
              }
              $cmd->setType('info');
              $cmd->setSubType('binary');
              $cmd->setEqLogic_id($eqLogic->getId());
              $cmd->setConfiguration('choiceIO', 'coils');
              $cmd->setConfiguration('adresseIO', $adresse);
              $cmd->save();
              $cmd->event($result[1]);


          }

   }


   public static function creaCommandsByUser($choice, $adresse, $cmdId, $nbBytes){
            $cmdOriginal = cmd::byId($cmdId);
            if(is_object($cmdOriginal)){
              $eqLogic = $cmdOriginal->getEqLogic();
                  switch($choice){
                      case 'coils':
                                          $cmd = $eqLogic->getCmd(null, 'ReadCoil_'.$adresse);
                                          $logId = 'ReadCoil_'.$adresse;
                                          $subtype = 'binary';
                                          break;
                      case 'discrete':
                                          $cmd = $eqLogic->getCmd(null, 'ReadDiscreteInput_'.$adresse);
                                          $logId = 'ReadDiscreteInput_'.$adresse;
                                          $subtype = 'binary';
                                          break;
                      case 'inputRegisters':
                                              $cmd = $eqLogic->getCmd(null, 'ReadInputRegister_'.$adresse);
                                              $logId = 'ReadInputRegister_'.$adresse;
                                              $subtype = 'numeric';
                                              break;
                      case 'holdingRegisters':
                                              $cmd = $eqLogic->getCmd(null, 'ReadHoldingRegister_'.$adresse);
                                              $logId = 'ReadHoldingRegister_'.$adresse;
                                              $subtype = 'numeric';
                                              break;
                    }
                  if (!is_object($cmd)) {
                      $cmd = new modbusCmd();
                      $cmd->setLogicalId($logId);
                      $cmd->setIsVisible(1);
                      $cmd->setName(__($logId, __FILE__));
                  }
                  $cmd->setType('info');
                  $cmd->setSubType($subtype);
                  $cmd->setEqLogic_id($eqLogic->getId());
                  $cmd->setConfiguration('choiceIO', $choice);
                  $cmd->setConfiguration('adresseIO', $adresse);
                  $cmd->save();
                  $cmdOriginal->remove();
            }
    }


    /*     * *********************Méthodes d'instance************************* */

 // Fonction exécutée automatiquement avant la création de l'équipement
    public function preInsert() {

    }

 // Fonction exécutée automatiquement après la création de l'équipement
    public function postInsert() {

    }

 // Fonction exécutée automatiquement avant la mise à jour de l'équipement
    public function preUpdate() {

        if ($this->getConfiguration('ipuser', 'modbus') == '') {
              throw new Exception(__('Ip du Device non rempli', __FILE__));
              message::add(__CLASS__, 'Ip du Device non rempli');
       }
    }

 // Fonction exécutée automatiquement après la mise à jour de l'équipement
    public function postUpdate() {
        $cmd = $this->getCmd(null, 'refresh');
         if (!is_object($cmd)) {
             $cmd = new modbusCmd();
             $cmd->setLogicalId('refresh');
             $cmd->setIsVisible(1);
             $cmd->setName(__('Rafraichir', __FILE__));
         }
         $cmd->setType('action');
         $cmd->setSubType('other');
         $cmd->setEqLogic_id($this->getId());
         $cmd->save();
      /* $cmd = $this->getCmd(null, 'writeCoils');
        if (!is_object($cmd)) {
            $cmd = new modbusCmd();
            $cmd->setLogicalId('writeCoils');
            $cmd->setIsVisible(1);
            $cmd->setName(__('WriteCoils', __FILE__));
        }
        $cmd->setType('action');
        $cmd->setSubType('message');
        $cmd->setDisplay('title_placeholder', __('Registres ( séparés par - )', __FILE__));
        $cmd->setDisplay('message_placeholder', __('Values ( séparées par - )', __FILE__));
        $cmd->setEqLogic_id($this->getId());
        $cmd->save();

        $cmd = $this->getCmd(null, 'writeHoldings');
          if (!is_object($cmd)) {
              $cmd = new modbusCmd();
              $cmd->setLogicalId('writeHoldings');
              $cmd->setIsVisible(1);
              $cmd->setName(__('WriteHoldings', __FILE__));
          }
          $cmd->setType('action');
          $cmd->setSubType('message');
          $cmd->setDisplay('title_placeholder', __('Registres ( séparés par - )', __FILE__));
          $cmd->setDisplay('message_placeholder', __('Values ( séparées par - ) ', __FILE__));
          $cmd->setEqLogic_id($this->getId());
          $cmd->save();*/

    }

 // Fonction exécutée automatiquement avant la sauvegarde (création ou mise à jour) de l'équipement
    public function preSave() {

    }

 // Fonction exécutée automatiquement après la sauvegarde (création ou mise à jour) de l'équipement
    public function postSave() {
       $this->configsCmds();
      /* modbus::testC(231.2);*/

      /*  $ipDevice = $this->getConfiguration('ipuser', 'modbus');
        $value = array('apikey' => jeedom::getApiKey('modbus'), 'action' => 'payload');
        $value = json_encode($value);
        self::socketConnection($value);*/





      }

      public static function testC($f){

             $ar = unpack("c*", pack("f", $f));
             log::add(__CLASS__, 'debug', 'TESTCONVERSE :' . json_encode($ar));

      }



      public function configsCmds(){
        $cmdsOptions = array();
        $value = array('apikey' => jeedom::getApiKey('modbus'), 'action' => 'newCmds');

        foreach ($this->getCmd('info') as $cmd) {
              $cmdsOptions[]= array('nameCmd' => $cmd->getName(),
                                    'cmdId' => $cmd->getId(),
                                    'typeIO' => $cmd->getConfiguration('choiceIO'),
                                    'format' => $cmd->getConfiguration('formatIO'),
                                    'functioncode' => $cmd->getConfiguration('choicefunctioncode'),
                                    'nbregister' => $cmd->getConfiguration('nbbytes'),
                                    'startregister' => $cmd->getConfiguration('startregister'),
                                    'wordorder' => $cmd->getConfiguration('wordorder'),
                                    'byteorder' => $cmd->getConfiguration('byteorder')
                                   );
        }

        $value['modbusDevice'] = array(
                                      'id' => $this->getId(),
                                      'ipDevice' => $this->getConfiguration('ipuser', 'modbus'),
                                      'registerParams' => $cmdsOptions
        );
        log::add(__CLASS__, 'debug', 'CONFIGSCMDS :' . json_encode($value));
        $value = json_encode($value);
        self::socketConnection($value);
      }




    public static function readCmds($eqLogic){
        $eqlogicId =  $eqLogic->getId();
        $ipDevice =  $eqLogic->getConfiguration('ipuser', 'modbus');
        $value = array('apikey' => jeedom::getApiKey('modbus'), 'ipDevice' => $ipDevice, 'eqlogicid' => $eqlogicId,  'action' => 'readCron');
        $cmds = cmd::byEqLogicId($eqlogicId);
        foreach($cmds as $cmd){
                $typeCmdJeedom = $cmd->getType();
                $logicalId = $cmd->getLogicalId();
                $cmdId = $cmd->getId();
                if( ($typeCmdJeedom == 'info')/* && (strpos($logicalId, 'Read') !== FALSE)*/ ){
                    $adresseCmd =  $cmd->getConfiguration('adresseIO');
                    $choiceIO =  $cmd->getConfiguration('choiceIO');
                    switch($choiceIO){
                        case 'coils':
                                          $cmdInfos = array('cmdId' => $cmdId, 'adresse' => $adresseCmd);
                                          $value['data']['coils'][] = $cmdInfos;
                                          break;
                        case 'discrete':
                                          $cmdInfos = array('cmdId' => $cmdId, 'adresse' => $adresseCmd);
                                          $value['data']['discrete'][] = $cmdInfos;
                                          break;
                        case 'inputRegisters':
                                                $cmdInfos = array('cmdId' => $cmdId, 'adresse' => $adresseCmd);
                                                $value['data']['inputRegisters'][] = $cmdInfos;
                                                break;
                        case 'holdingRegisters':
                                                $cmdInfos = array('cmdId' => $cmdId, 'adresse' => $adresseCmd);
                                                $value['data']['holdingRegisters'][] = $cmdInfos;
                                                break;
                    }
                }
        }
        log::add(__CLASS__, 'debug', 'READCMDS :' . json_encode($value));
        $value = json_encode($value);
        self::socketConnection($value);

    }


    public static function conversionFunction($result){

         log::add(__CLASS__, 'debug', 'ALLRESULT: ' . json_encode($result));

        foreach($result as $key => $value){
             if($key == 'inputRegisters'){
                   foreach($value as $k => $v){
                          $valueToEvent = $v[0]['valueConverse'];
                          $cmdId = intval($v[0]['CmdId']);
                           log::add(__CLASS__, 'debug', 'iCMDID: ' . $cmdId);
                          $cmdsearch = cmd::byId($cmdId);
                          if(is_object($cmdsearch)){
                               $nameC = $cmdsearch->getName();
                               $cmdsearch->event($valueToEvent);
                          }
                   }
              }elseif($key == 'coils'){
                  foreach($value as $cmdName => $data){
                        log::add(__CLASS__, 'debug', 'cmdname: ' . json_encode($cmdName));
                        log::add(__CLASS__, 'debug', 'data: ' . json_encode($data));
                        $cmdOrigin = cmd::byId($data[0]['CmdId']);
                        $cmdOriginName = $cmdOrigin->getName();
                        $eqlogic = $cmdOrigin->getEqLogic();
                          foreach($data as $k => $v){

                                $valueEvent = $v['valueConverse'];
                                $adresseStart = $v['Byte'];
                                $newCmdLogical = $cmdOriginName.'_'.$adresseStart;
                                log::add(__CLASS__, 'debug', 'NEWLOGICAL ' . $newCmdLogical);
                                $cmd = $eqlogic->getCmd(null, $newCmdLogical);
                              /*  $nbbyte = $cmd->getConfiguration('nbbytes', 'modbus', 0);*/
                               if(is_object($cmd)){
                                    $cmd->event($valueEvent);

                               }else{
                                    if($cmdOrigin->getConfiguration('alreadycreate') == 'yes'){

                                    }else{
                                      if (!is_object($cmd)) {
                                          $cmd = new modbusCmd();
                                          $cmd->setLogicalId($newCmdLogical);
                                          $cmd->setIsVisible(1);
                                          $cmd->setName(__($newCmdLogical, __FILE__));
                                          $cmd->setType('info');
                                          $cmd->setSubType('binary');
                                          $cmd->setEqLogic_id($eqlogic->getId());
                                          $cmd->setConfiguration('choiceIO', 'coils');
                                          $cmd->setConfiguration('choicefunctioncode', 'fc01');
                                          $cmd->setConfiguration('startregister', $adresseStart + 1);
                                          $cmd->setConfiguration('nbbytes', 1);
                                          $cmd->setConfiguration('alreadycreate', 'yes');
                                          $cmd->save();
                                          $cmd->event($valueEvent);
                                       }
                                    }
                               }
                          }
                  }
                }
            }
      }
    public static function hexTo32Float($strHex) {
        $v = hexdec($strHex);
        $x = ($v & ((1 << 23) - 1)) + (1 << 23) * ($v >> 31 | 1);
        $exp = ($v >> 23 & 0xFF) - 127;
        $float32 =  $x * pow(2, $exp - 23);
        log::add(__CLASS__, 'debug', 'FLOAT32 : ' .$float32);
        return $float32;
    }




 // Fonction exécutée automatiquement avant la suppression de l'équipement
    public function preRemove() {

    }

 // Fonction exécutée automatiquement après la suppression de l'équipement
    public function postRemove() {

    }

    /*
     * Non obligatoire : permet de modifier l'affichage du widget (également utilisable par les commandes)
      public function toHtml($_version = 'dashboard') {

      }
     */

    /*
     * Non obligatoire : permet de déclencher une action après modification de variable de configuration
    public static function postConfig_<Variable>() {
    }
     */

    /*
     * Non obligatoire : permet de déclencher une action avant modification de variable de configuration
    public static function preConfig_<Variable>() {
    }
     */

    /*     * **********************Getteur Setteur*************************** */
}

class modbusCmd extends cmd {
    /*     * *************************Attributs****************************** */

    /*
      public static $_widgetPossibility = array();
    */

    /*     * ***********************Methode static*************************** */


    /*     * *********************Methode d'instance************************* */

    /*
     * Non obligatoire permet de demander de ne pas supprimer les commandes même si elles ne sont pas dans la nouvelle configuration de l'équipement envoyé en JS
      public function dontRemoveCmd() {
      return true;
      }
     */

  // Exécution d'une commande
     public function execute($_options = array()) {
          $eqLogic = $this->getEqLogic();
          $cmdlogical = $this->getLogicalId();
          $ipDevice = $eqLogic->getConfiguration('ipuser', 'modbus');

          $cmdsOptions= array('nameCmd' => $this->getName(),
                                'cmdId' => $this->getId(),
                                'typeIO' => $this->getConfiguration('choiceIO'),
                                'functioncode' => $this->getConfiguration('choicefunctioncode'),
                                'nbregister' => $this->getConfiguration('nbbytes'),
                                'startregister' => $this->getConfiguration('startregister'),
                                'format' => $this->getConfiguration('formatIO')
                               );
          if($cmdlogical == 'refresh'){
              /*  modbus::readCmds($eqLogic);*/
                $eqLogic->configsCmds();
          }


          if($this->getSubtype() == 'slider'){
               if($this->getConfiguration('decimal', 0) == 1){
                 log::add('modbus', 'debug', 'IHIHIHIHIH : ');
                 $arr = $this->getDisplay('parameters');
                 $arr['step'] = 0.1;
                 $this->setDisplay(parameters, $arr);
                 $this->save();
               }
               $cmdsOptions['value']= $_options['slider'];
               $value = json_encode(array('apikey' => jeedom::getApiKey('modbus'), 'ipDevice' => $ipDevice, 'action' => 'writeAction', 'options' => $cmdsOptions));
               log::add('modbus', 'debug', 'WRITETEST : ' .$value);
               modbus::socketConnection($value);

          }


          if($this->getSubtype() == 'other'){

            $cmdsOptions['value']= $this->getConfiguration('valeurToAction');
             $value = json_encode(array('apikey' => jeedom::getApiKey('modbus'), 'ipDevice' => $ipDevice, 'action' => 'writeAction', 'options' => $cmdsOptions));
             log::add('modbus', 'debug', 'WRITETEST : ' .$value);
             modbus::socketConnection($value);

          }






         if($this->getSubtype() == 'message'){
             $arrayVal = explode('-', $_options['message']);
             $trimValues = array_map('trim', $arrayVal);
             $nbValues = count($trimValues);

             $arrayRegisters = explode('-', $_options['title']);
             $trimRegisters = array_map('trim', $arrayRegisters);
             $startRegister = $trimRegisters[0];
             $nbRegisters = count($trimRegisters);

             if($nbValues == $nbRegisters){
                   if($cmdlogical == 'writeCoils'){
                         $trimValues = array_map('ucfirst', $trimValues);
                         $typeCmd = 'coils';
                   }elseif($cmdlogical == 'writeHoldings'){
                          $typeCmd = 'holdings';
                   }

                   $value = json_encode(array('apikey' => jeedom::getApiKey('modbus'), 'ipDevice' => $ipDevice, 'action' => 'write', 'typeOfCmd' => $typeCmd,'values' => $trimValues, 'registers' => $trimRegisters, 'startRegister' => $startRegister));
                   log::add('modbus', 'debug', 'WRITETEST : ' .$value);
                   modbus::socketConnection($value);
             }
          }

     }

    /*     * **********************Getteur Setteur*************************** */
}
