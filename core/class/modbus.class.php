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
            $cmd .= ' --socketport ' . config::byKey('socketport',__CLASS__, '55030');
            $cmd .= ' --timesleep ' . config::byKey('timerecup', __CLASS__ ,'5');
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
            self::sendDevices();
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
        socket_connect($socket, '127.0.0.1', config::byKey('socketport', 'modbus', 55030));
        socket_write($socket, $value, strlen($value));
        socket_close($socket);
    }
  
  
  
   public static function sendDevices(){
        foreach (self::byType('modbus') as $eqLogic) {
           /*  $eqLogic->updateCmdsParams();*/
			 $eqLogic->updateDevices();
			/* usleep(500);*/
             usleep(500);           
		 }
   }
  
  
  
   public function updateDevices(){
        $cmdsOptions = array();
        $value = array('apikey' => jeedom::getApiKey('modbus'), 'action' => 'updateDeviceToGlobals');
       /* foreach ($this->getCmd('action') as $cmd) { 
              if($cmd->getSubType() == 'slider'){
                      log::add('modbus','debug','UPDATECMD');
                     $stepchoice = $cmd->getConfiguration('stepchoice',0);
                     log::add('modbus','debug','UPDATECMD: ' .$stepchoice);
                     $arr = $cmd->getDisplay('parameters');           
                     $arr['step'] = floatval($stepchoice);
                     $cmd->setDisplay(parameters, $arr);
                     $cmd->setTemplate('dashboard', 'button');
                     $cmd->setTemplate('mobile', 'button');
                     $cmd->save();
              } 
         }*/
        
        foreach ($this->getCmd('info') as $cmd) {
         					  $offset =  $cmd->getConfiguration('offset',0);
                              $cmdsOptions[]= array(
                                					'nameCmd' => $cmd->getName(),
                                                    'cmdId' => $cmd->getId(),
                                                    'format' => $cmd->getConfiguration('formatIO'),
                                                    'functioncode' => $cmd->getConfiguration('choicefunctioncode'),
                                                    'nbregister' => $cmd->getConfiguration('nbbytes'),
                                                    'startregister' => $cmd->getConfiguration('startregister'),
                                                    'wordorder' => $cmd->getConfiguration('wordorder'),
                                                    'byteorder' => $cmd->getConfiguration('byteorder'),
                                                    'offset' => $offset
                                                   );
        }

         $value['deviceInfo'] = array(   'typeDevice' =>  $this->getConfiguration('choicemodbus',0),
                                         'portserial' => $this->getConfiguration('portserial',0), 
                                         'baudrate' => $this->getConfiguration('baudrate',0),
                                         'portrtu' => $this->getConfiguration('portrtu',0),
                                         'parity' => $this->getConfiguration('parity',0),
                                         'stopbits' => $this->getConfiguration('stopbits',0),
                                          'bytesize' => $this->getConfiguration('bytesize',0),
           								 'id' => $this->getId(),
                                         'ipDevice' => $this->getConfiguration('ipuser', 'modbus'),
                                         'registerParams' => $cmdsOptions
                                  );
        $value = json_encode($value);
        self::socketConnection($value);

    }
  
  
  
     public function deleteDevice(){    
          if ($this->getId() == '') {
			return;
		}
        $value = json_encode(array('apikey' => jeedom::getApiKey('modbus'), 'action' => 'deleteDevice', 'deviceInfo' => array('id' => $this->getId())));
        self::socketConnection($value);

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
        /* $this->updateCmdsParams();*/
        $this->creaCoils();
     /*   $this->creaCoils();*/
     
       /* $this->updateDevices();*/

    }
  
  
    public function postAjax() {
        /*$this->updateDevices();
        sleep(1);*/
       $this->creaCoils();
      /* $this->updateCmdsParams();*/
       /* sleep(1);
        self::deamon_start();*/

    }


 // Fonction exécutée automatiquement avant la sauvegarde (création ou mise à jour) de l'équipement
    public function preSave() {
        /* $this->updateCmdsParams();  */
    }

 // Fonction exécutée automatiquement après la sauvegarde (création ou mise à jour) de l'équipement
    public function postSave() {
       /* $this->creaCoils();*/
        sleep(1);
       /* $this->updateCmdsParams();*/
        self::deamon_start();
        sleep(1);
       /* $this->updateCmdsParams();*/
     /* $this->updateDevices();*/
     /* sleep(3);*/
     
      }
  
    public function creaCoils(){
        foreach ($this->getCmd('info') as $cmd) { 
            $formatIO = $cmd->getConfiguration('formatIO');
            $offset =  $cmd->getConfiguration('offset',0);
            $function_code= $cmd->getConfiguration('choicefunctioncode');
            $nbBytes = $cmd->getConfiguration('nbbytes');
    	    $registre_depart = $cmd->getConfiguration('startregister');
            $wordorder = $cmd->getConfiguration('wordorder');
            $byteorder = $cmd->getConfiguration('byteorder');
            $isnegatif = $cmd->getConfiguration('isnegatif');
            if($function_code == 'fc01'){
                      if ( $nbBytes > 1 ) {
                              for ($i = 0; $i < $nbBytes; $i++) {
                                $newAdresseValue = intval($registre_depart) + $i;
                                $cmdName = 'ReadCoil' . '_' . $newAdresseValue;
                                $newCmd = $this->getCmd(null, $cmdName);

                                if (!is_object($newCmd)) {
                                  $newCmd = new modbusCmd();
                                  $newCmd->setLogicalId($cmdName);
                                  $newCmd->setIsVisible(1);
                                  $newCmd->setName(__($cmdName, __FILE__));
                                }
                                $newCmd->setType('info');
                                $newCmd->setSubType('binary');
                                $newCmd->setEqLogic_id($this->getId());
                                $newCmd->setConfiguration('formatIO',$formatIO);
                                $newCmd->setConfiguration('choicefunctioncode',$function_code);
                                $newCmd->setConfiguration('wordorder',$wordorder);
                                $newCmd->setConfiguration('byteorder',$byteorder);
                                $newCmd->setConfiguration('startregister',$newAdresseValue);
                                $newCmd->setConfiguration('nbbytes',1);
                                $newCmd->setConfiguration('offset',$offset);
                                $cmd->remove();
                                $newCmd->save();
                              }
                    }                
             } 
        }    
      
    } 
  
  
  public function updateCmdsParams(){
         foreach ($this->getCmd('action') as $cmd) { 
              if($cmd->getSubType() == 'slider'){
                     
                     $stepchoice = $cmd->getConfiguration('stepchoice', 0);
                     $arr = $cmd->getDisplay('parameters');           
                     $arr['step'] = $stepchoice;
                     $cmd->setDisplay(parameters, $arr);
                     $cmd->setTemplate('dashboard', 'button');
                     $cmd->setTemplate('mobile', 'button');
                     $cmd->save();
              }
         }
  }


 // Fonction exécutée automatiquement avant la suppression de l'équipement
    public function preRemove() {
         $this->deleteDevice();
         sleep(1);
         self::deamon_start();

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

          if ($this->type != 'action') {
			return;
		  }
          $eqLogic = $this->getEqLogic();
          $cmdlogical = $this->getLogicalId();
          $ipDevice = $eqLogic->getConfiguration('ipuser', 'modbus');
          $offset =  $this->getConfiguration('offset',0);
          
          

          $cmdsOptions= array('nameCmd' => $this->getName(),
                                'cmdId' => $this->getId(),
                                'functioncode' => $this->getConfiguration('choicefunctioncode'),
                                'nbregister' => $this->getConfiguration('nbbytes'),
                                'startregister' => $this->getConfiguration('startregister'),
                                'format' => $this->getConfiguration('formatIO'),
                                'wordorder' => $this->getConfiguration('wordorder'),
                                'byteorder' => $this->getConfiguration('byteorder'),
                                'offset' =>  $offset,
                                'valuesrequest' => $requestVal
                               );
 

          if($this->getSubtype() == 'slider'){
                 $stepchoice = $this->getConfiguration('stepchoice', 0);
                 $arr = $this->getDisplay('parameters');           
                 $arr['step'] = $stepchoice;
                 $this->setDisplay(parameters, $arr);
                 $this->setTemplate('dashboard', 'button');
                 $this->setTemplate('mobile', 'button');
                 $this->save();
                 $cmdsOptions['value']= $_options['slider'];
                 $value = json_encode(array('apikey' => jeedom::getApiKey('modbus'), 'ipDevice' => $ipDevice, 'action' => 'writeAction', 'options' => $cmdsOptions));
                 log::add('modbus', 'debug', 'WRITETESTSsssssssSSSSSSTTTTTT : ' .$value);
                 modbus::socketConnection($value);

          }

          if($this->getSubtype() == 'other' && $cmdlogical != 'refresh'){
             $requestVal = array_map('intval', explode(" ", $this->getConfiguration('request',0)));
             foreach($requestVal as $val){
                    if($val != 0 && $val != 1){
                       log::add('modbus', 'debug', 'ERREUR DANS VOS DONNEES COILS : N ECRIRE QUE 0 ou 1');
                       return;
                    }               
               }
              $cmdsOptions['value']= $this->getConfiguration('valeurToAction');
              $cmdsOptions['valuesrequest']= $requestVal;
            
             $value = json_encode(array('apikey' => jeedom::getApiKey('modbus'), 'ipDevice' => $ipDevice, 'action' => 'writeAction', 'options' => $cmdsOptions));
             log::add('modbus','debug', 'kikiii' .json_encode($requestVal));
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