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
require_once __DIR__ . '/../../../../core/php/core.inc.php';

class modbus extends eqLogic
{
    /*     * *************************Attributs****************************** */

    /*
    * Permet de définir les possibilités de personnalisation du widget (en cas d'utilisation de la fonction 'toHtml' par exemple)
    * Tableau multidimensionnel - exemple: array('custom' => true, 'custom::layout' => false)
    public static $_widgetPossibility = array();
    */

    /*     * ***********************Methode static*************************** */

    public static function deamon_info()
    {
        $return = array();
        $return['log'] = 'modbus';
        $return['state'] = 'nok';

        $pid_file = jeedom::getTmpFolder(__CLASS__) . '/deamon.pid';
        if (file_exists($pid_file))
        {
            if (@posix_getsid(trim(file_get_contents($pid_file))))
            {
                $return['state'] = 'ok';
            }
            else
            {
                shell_exec(system::getCmdSudo() . 'rm -rf ' . $pid_file . ' 2>&1 > /dev/null');
            }
        }
        $return['launchable'] = 'ok';
        return $return;
    }

    public static function dependancy_info()
    {
        $return = array();
        $return['log'] = log::getPathToLog(__CLASS__ . '_update');
        $return['progress_file'] = jeedom::getTmpFolder(__CLASS__) . '/dependency';
        if (file_exists(jeedom::getTmpFolder(__CLASS__) . '/dependency'))
        {
            $return['state'] = 'in_progress';
        }
        else
        {
            if (exec(system::getCmdSudo() . system::get('cmd_check') . '-Ec "python3\-pip|python3\-dev|python3\-pyudev|python3\-serial|python3\-requests|python3\-pymodbus"') < 5)
            {
                $return['state'] = 'nok';
            }
            elseif (exec(system::getCmdSudo() . 'pip3 list | grep -Ewc "pymodbus"') < 1)
            {
                $return['state'] = 'nok';
            }
            else
            {
                $return['state'] = 'ok';
            }
        }
        return $return;
    }

    public static function dependancy_install()
    {
        log::remove(__CLASS__ . '_update');
        return array(
            'script' => dirname(__FILE__) . '/../../resources/install_#stype#.sh ' . jeedom::getTmpFolder(__CLASS__) . '/dependency',
            'log' => log::getPathToLog(__CLASS__ . '_update')
        );
    }

    public static function deamon_start()
    {
        self::deamon_stop();
        $deamon_info = self::deamon_info();
        if ($deamon_info['launchable'] != 'ok')
        {
            throw new Exception(__('Veuillez vérifier la configuration', __FILE__));
        }

        $path = realpath(dirname(__FILE__) . '/../../resources/modbusd');
        $cmd = '/usr/bin/python3 ' . $path . '/modbusd.py';
        $cmd .= ' --loglevel ' . log::convertLogLevel(log::getLogLevel(__CLASS__));
        $cmd .= ' --socketport ' . config::byKey('socketport', __CLASS__, '55030');
        $cmd .= ' --timesleep ' . config::byKey('timerecup', __CLASS__, '5');
        $cmd .= ' --sockethost 127.0.0.1';
        $cmd .= ' --callback ' . network::getNetworkAccess('internal', 'proto:127.0.0.1:port:comp') . '/plugins/modbus/core/php/jeeModbus.php';
        $cmd .= ' --apikey ' . jeedom::getApiKey(__CLASS__);
        $cmd .= ' --pid ' . jeedom::getTmpFolder(__CLASS__) . '/deamon.pid';
        log::add(__CLASS__, 'info', 'Lancement démon ' . $cmd);
        $result = exec($cmd . ' >> ' . log::getPathToLog('modbus') . ' 2>&1 &');
        $i = 0;
        while ($i < 5)
        {
            $deamon_info = self::deamon_info();
            if ($deamon_info['state'] == 'ok')
            {
                break;
            }
            sleep(1);
            $i++;
        }
        if ($i >= 5)
        {
            log::add(__CLASS__, 'error', __('Impossible de lancer le démon, vérifiez le log', __FILE__) , 'unableStartDeamon');
            return false;
        }
        message::removeAll(__CLASS__, 'unableStartDeamon');
        self::sendDevices();
        return true;
    }

    public static function deamon_stop()
    {
        $pid_file = jeedom::getTmpFolder(__CLASS__) . '/deamon.pid';
        if (file_exists($pid_file))
        {
            $pid = intval(trim(file_get_contents($pid_file)));
            system::kill($pid);
        }
        system::kill('modbusd.py');
        system::fuserk(config::byKey('socketport', 'modbus'));
        sleep(1);
    }

    public static function socketConnection($value)
    {
        try
        {
            $socket = socket_create(AF_INET, SOCK_STREAM, 0);
            socket_set_timeout($socket, 180);
            socket_connect($socket, '127.0.0.1', config::byKey('socketport', 'modbus', 55030));
            socket_write($socket, $value, strlen($value));
            socket_close($socket);
        }
        catch(Exception $e)
        {
            log::add('modbus', 'debug', 'Exception reçue : ' . $e->getMessage());
        }
    }

    public static function sendDevices()
    {
        foreach (eqLogic::byType('modbus', true) as $eqLogic)
        {
            $eqLogic->updateDevices();
            usleep(500);
        }
    }

    public function updateDevices()
    {
        $cmdsOptions = array();
        $value = array(
            'apikey' => jeedom::getApiKey('modbus') ,
            'action' => 'updateDeviceToGlobals'
        );
        foreach ($this->getCmd('info') as $cmd)
        {         
          if($cmd->getConfiguration('infobitbinary') == 'yes'){           
            continue;
          }
            $startInfo = $cmd->getConfiguration('startregister');
            $woInfo =  $cmd->getConfiguration('wordorder', 'bigword');
            $boInfo =  $cmd->getConfiguration('byteorder', 'bigbyte');
            $formatInfo =  $cmd->getConfiguration('formatIO');
            $offset = $cmd->getConfiguration('offset', 0);
            $functionCodeinfo =  $cmd->getConfiguration('choicefunctioncode');
            if($startInfo && $woInfo && $boInfo && $formatInfo){

                  $offset = $cmd->getConfiguration('offset', 0);
                  $cmdsOptions[] = array(
                      'nameCmd' => $cmd->getName() ,
                      'cmdId' => $cmd->getId() ,
                      'format' => $formatInfo ,
                      'functioncode' => $functionCodeinfo ,
                      'nbregister' => $cmd->getConfiguration('nbbytes') ,
                      'startregister' => $startInfo ,
                      'wordorder' => $woInfo ,
                      'byteorder' => $boInfo ,
                      'isnegatif' => $cmd->getConfiguration('isnegatif', 0) ,
                      'offset' => $offset
                  );
           }
        }
        $cmdMessage = $this->getCmd('action', 'ecriturebit');
        if (is_object($cmdMessage))
        {  
            $start = $cmdMessage->getConfiguration('startregister');
            $wo =  $cmdMessage->getConfiguration('wordorder');
            $bo =  $cmdMessage->getConfiguration('byteorder');
            $format =  $cmdMessage->getConfiguration('formatIO');
            $offset = $cmdMessage->getConfiguration('offset', 0);
            if($start && $wo && $bo && $format){
                 $cmdsOptions[] = array(
                'cmdLogical' => 'ecriturebit',
                'nameCmd' => $cmdMessage->getName() ,
                'cmdId' => $cmdMessage->getId() ,
                'format' => $format ,
                'functioncode' => 'fc03' ,
                'nbregister' => 1 ,
                'startregister' => $start ,
                'wordorder' => $wo ,
                'byteorder' => $bo ,
                'isnegatif' => $cmdMessage->getConfiguration('isnegatif', 0) ,
                'offset' => $offset
                 );
              
            }   
        }

        if ($this->getConfiguration('choicemodbus') == 'tcp')
        {
            $value['deviceInfo'] = array(
                'typeDevice' => $this->getConfiguration('choicemodbus', 0) ,
                'id' => $this->getId() ,
                'ipDevice' => $this->getConfiguration('ipuser', 'modbus') ,
                'registerParams' => $cmdsOptions
            );

        }
        else if ($this->getConfiguration('choicemodbus') == 'rtu')
        {
            $value['deviceInfo'] = array(
                'typeDevice' => $this->getConfiguration('choicemodbus', 0) ,
                'portserial' => $this->getConfiguration('portserial', 0) ,
                'baudrate' => intval($this->getConfiguration('baudrate', 0)) ,
                'unitID' => intval($this->getConfiguration('unitID', 0)) ,
                'parity' => $this->getConfiguration('parity', 0) ,
                'stopbits' => intval($this->getConfiguration('stopbits', 0)) ,
                'bytesize' => intval($this->getConfiguration('bytesize', 0)) ,
                'id' => $this->getId() ,
                'registerParams' => $cmdsOptions
            );

        }
        $value = json_encode($value);
        self::socketConnection($value);

    }

    public function deleteDevice()
    {
        if ($this->getId() == '')
        {
            return;
        }
        $value = json_encode(array(
            'apikey' => jeedom::getApiKey('modbus') ,
            'action' => 'deleteDevice',
            'deviceInfo' => array(
                'id' => $this->getId()
            )
        ));
        self::socketConnection($value);
    }

    /*     * *********************Méthodes d'instance************************* */

    // Fonction exécutée automatiquement avant la création de l'équipement
    public function preInsert()
    {

    }

    // Fonction exécutée automatiquement après la création de l'équipement
    public function postInsert()
    {

    }

    // Fonction exécutée automatiquement avant la mise à jour de l'équipement
    public function preUpdate()
    {

        if ($this->getConfiguration('ipuser', 'modbus') == '')
        {
            throw new Exception(__('Ip du Device non rempli', __FILE__));
            message::add(__CLASS__, 'Ip du Device non rempli');
        }
    }

    // Fonction exécutée automatiquement après la mise à jour de l'équipement
    public function postUpdate()
    {

        $cmd = $this->getCmd(null, 'ecriturebit');
        if (!is_object($cmd))
        {
            $cmd = new modbusCmd();
            $cmd->setLogicalId('ecriturebit');
            $cmd->setIsVisible(1);
            $cmd->setName(__('Ecriture Bit', __FILE__));
            $cmd->setOrder(1);
        }
        $cmd->setType('action');
        $cmd->setSubType('message');
        $cmd->setDisplay('title_placeholder', 'Changement de Bit');
        $cmd->setDisplay('message_placeholder', 'ValeurBit&PositionBit');
        $cmd->setConfiguration('choicefunctioncode', 'fc03');
        $cmd->setConfiguration('defCmd', '1');
        $cmd->setEqLogic_id($this->getId());
        $cmd->save();


        $cmd = $this->getCmd(null, 'multicoils');
        if (!is_object($cmd))
        {
            $cmd = new modbusCmd();
            $cmd->setLogicalId('multicoils');
            $cmd->setIsVisible(1);
            $cmd->setName(__('Ecriture MultiCoils', __FILE__));
            $cmd->setOrder(1);
        }
        $cmd->setType('action');
        $cmd->setSubType('message');
        $cmd->setDisplay('title_placeholder', 'Ecriture MultiCoils');
        $cmd->setDisplay('message_placeholder', 'Valeurs des Coils a la suite');
        $cmd->setEqLogic_id($this->getId());
        $cmd->setConfiguration('choicefunctioncode', 'fc15');
        $cmd->setConfiguration('defCmd', '2');
        $cmd->save();


        $cmd = $this->getCmd(null, 'infobitbinary');
        if (!is_object($cmd))
        {
            $cmd = new modbusCmd();
            $cmd->setLogicalId('infobitbinary');
            $cmd->setIsVisible(1);
            $cmd->setOrder(1);
        }
        $cmd->setName(__('LECTURE COMMANDE BINAIRE', __FILE__));
        $cmd->setType('info');
        $cmd->setSubType('string');
        $cmd->setEqLogic_id($this->getId());
        $cmd->setConfiguration('defCmd', '3');
        $cmd->setConfiguration('infobitbinary','yes');
        $cmd->save();

        $cmd = $this->getCmd(null, 'ecrituremultiRegisters');
        if (!is_object($cmd))
        {
            $cmd = new modbusCmd();
            $cmd->setLogicalId('ecrituremultiRegisters');
            $cmd->setIsVisible(1);
            $cmd->setName(__('Ecriture Multi Registre', __FILE__));
        }
        $cmd->setType('action');
        $cmd->setSubType('message');
        $cmd->setDisplay('title_placeholder', 'Ecriture Multi Registre');
        $cmd->setDisplay('message_placeholder', 'Valeur&NbRegistre|Valeur&NbRegistre');
        $cmd->setConfiguration('choicefunctioncode', 'fc16');
        $cmd->setConfiguration('defCmd', '4');
        $cmd->setEqLogic_id($this->getId());
        $cmd->save();
    }

    public function postAjax()
    {
        $this->updateSliders();
        $this->updateDevices();
        $this->creaCoils();
        message::removeAll(__CLASS__, 'updateslider');
        self::deamon_start();
    }

    // Fonction exécutée automatiquement avant la sauvegarde (création ou mise à jour) de l'équipement
    public function preSave()
    {

    }

    // Fonction exécutée automatiquement après la sauvegarde (création ou mise à jour) de l'équipement
    public function postSave()
    {
 
    }

    public function creaCoils()
    {

        foreach ($this->getCmd('info') as $cmd)
        {
            if (is_object($cmd))
            {

                $function_code = $cmd->getConfiguration('choicefunctioncode');

                if ($function_code != 'fc01' && $function_code != 'fc02')
                {
                    continue;
                }

                $formatIO = $cmd->getConfiguration('formatIO', 'bitsformat');
                $offset = $cmd->getConfiguration('offset', 0);
                $subtype = $cmd->getSubType();
                $nbBytes = $cmd->getConfiguration('nbbytes','');
                $registre_depart = $cmd->getConfiguration('startregister','');
                $wordorder = $cmd->getConfiguration('wordorder', 'bigword');
                $byteorder = $cmd->getConfiguration('byteorder', 'bigbyte');
                $isnegatif = $cmd->getConfiguration('isnegatif', 0);

                if ($nbBytes >= 1 && $cmd->getConfiguration('alreadycreate') != 'yes')
                {
                    for ($i = 0;$i < $nbBytes;$i++)
                    {
                        $newAdresseValue = intval($registre_depart) + $i;
                        if ($function_code == 'fc01')
                        {
                            $cmdName = 'ReadCoil' . '_' . $newAdresseValue;
                        }
                        elseif ($function_code == 'fc02')
                        {
                            $cmdName = 'ReadDiscrete' . '_' . $newAdresseValue;
                        }
                        $newCmd = $this->getCmd(null, $cmdName);
                        if (!is_object($newCmd))
                        {
                            $newCmd = new modbusCmd();
                            $newCmd->setLogicalId($cmdName);
                            $newCmd->setIsVisible(1);
                            $newCmd->setName(__($cmdName, __FILE__));
                        }
                        $newCmd->setType('info');
                        $newCmd->setSubType($subtype);
                        $newCmd->setEqLogic_id($this->getId());
                        $newCmd->setConfiguration('formatIO', $formatIO);
                        $newCmd->setConfiguration('choicefunctioncode', $function_code);
                        $newCmd->setConfiguration('wordorder', $wordorder);
                        $newCmd->setConfiguration('byteorder', $byteorder);
                        $newCmd->setConfiguration('startregister', $newAdresseValue);
                        $newCmd->setConfiguration('nbbytes', 1);
                        $newCmd->setConfiguration('offset', $offset);
                        $newCmd->setConfiguration('alreadycreate', 'yes');
                        $cmd->remove();
                        $newCmd->save();
                    }
                }
            }
            else
            {
                return;
            }
        }
    }

    public function updateSliders()
    {
        foreach ($this->getCmd('action') as $cmd)
        {
            if ($cmd->getSubType() == 'slider')
            {
                $stepchoice = $cmd->getConfiguration('stepchoice', 1);
                $arr = $cmd->getDisplay('parameters');
                if ($cmd->getConfiguration('formatIO') == 'floatformat')
                {
                    $arr['step'] = $stepchoice;
                    $cmd->setDisplay(parameters, $arr);
                    $cmd->setTemplate('dashboard', 'button');
                    $cmd->setTemplate('mobile', 'button');
                    $cmd->setConfiguration('minValue', $cmd->getConfiguration('minValue', 0));
                    $cmd->setConfiguration('maxValue', $cmd->getConfiguration('maxValue', 100));
                    $cmd->save();
                }
                elseif ($cmd->getConfiguration('formatIO') == 'longformat')
                {
                    $arr['step'] = 1;
                    $cmd->setDisplay(parameters, $arr);
                    $cmd->setTemplate('dashboard', 'default');
                    $cmd->setTemplate('mobile', 'default');
                    $cmd->save();
                }
                message::add(__CLASS__, 'Les commandes Sliders ont été mises à jour', 'slider', 'updateslider');
            }

        }

    }

    // Fonction exécutée automatiquement avant la suppression de l'équipement
    public function preRemove()
    {
        $this->deleteDevice();
        sleep(1);
        self::deamon_start();

    }

    // Fonction exécutée automatiquement après la suppression de l'équipement
    public function postRemove()
    {

    }
/*
    public static function sendValues($functioncode, $cmdId, $eqId, $valCmd)
    {
        $eqLogic = eqLogic::byId(intval($eqId));
        try
        {
            if (is_object($eqLogic))
            {

                $typeDevice = $eqLogic->getConfiguration('choicemodbus', 0);
                $unitID = $eqLogic->getConfiguration('unitID', 0);
                $ipDevice = $eqLogic->getConfiguration('ipuser', 'modbus');

                $value = (array(
                    'apikey' => jeedom::getApiKey('modbus') ,
                    'unitID' => $unitID,
                    'typeDevice' => $typeDevice,
                    'ipDevice' => $ipDevice,
                    'action' => 'writeAction'
                ));
                if ($functioncode == 'fc15')
                {
                    $arrayMultipleCoils = array_map('intval', $arrayMultipleCoils = str_split($valCmd, 1));
                    foreach ($arrayMultipleCoils as $val)
                    {
                        if ($val != 0 && $val != 1)
                        {
                            log::add('modbus', 'info', 'ERREUR DANS VOS DONNEES COILS : N ECRIRE QUE 0 ou 1');
                            return;
                        }
                    }
                    $nbregister = sizeof($arrayMultipleCoils);
                } else if($functioncode == 'fc16'){
                      $arrayMultipleRegisters  = explode(' ', $valCmd);
                      foreach ($arrayMultipleRegisters as $k => $v) {
                          list($val, $nbregister)   = explode('&', $v);
                          $result[$k]['valeur'] = $val;
                          $result[$k]['nbregister'] = $nbregister;
                      }
                       log::add('modbus','debug' ,'result '.json_encode($result));

                      foreach($result as $registerData){
                                 if(preg_match("/[a-z]/i", $registerData['valeur'])){
                                log::add('modbus', 'info', 'ERREUR DANS VOS DONNEES REGISTERS : N ECRIRE QUE DES NUMERIQUES');
                                return;
                              }
                       }
                    $arrayMultipleRegisters = $result;
                    }
                else if ($functioncode == 'fc03')
                {
                    $nbregister = 1;
                    $functioncode = 'fc06';
                }
                $cmd = cmd::byId(intval($cmdId));
                if (is_object($cmd))
                {

                    $offset = $cmd->getConfiguration('offset', 0);
                    $value['options'] = array(
                        'nameCmd' => $cmd->getName() ,
                        'cmdId' => $cmd->getId() ,
                        'functioncode' => $functioncode,
                        'nbregister' => $nbregister,
                        'isnegatif' => intval($cmd->getConfiguration('isnegatif')) ,
                        'startregister' => intval($cmd->getConfiguration('startregister')) ,
                        'format' => $cmd->getConfiguration('formatIO') ,
                        'wordorder' => $cmd->getConfiguration('wordorder') ,
                        'byteorder' => $cmd->getConfiguration('byteorder') ,
                        'offset' => $offset,
                        'value' => $valCmd
                    );
                    if ($arrayMultipleCoils)
                    {
                        $value['options']['valuesrequest'] = $arrayMultipleCoils;
                    }
                    if ($arrayMultipleRegisters)
                    {
                        $value['options']['valuesrequest'] = $arrayMultipleRegisters;
                    }

                }

                $value['deviceInfo'] = array(
                    'typeDevice' => $eqLogic->getConfiguration('choicemodbus', 0) ,
                    'portserial' => $eqLogic->getConfiguration('portserial', 0) ,
                    'baudrate' => intval($eqLogic->getConfiguration('baudrate', 0)) ,
                    'unitID' => intval($eqLogic->getConfiguration('unitID', 0)) ,
                    'parity' => $eqLogic->getConfiguration('parity', 0) ,
                    'stopbits' => intval($eqLogic->getConfiguration('stopbits', 0)) ,
                    'bytesize' => intval($eqLogic->getConfiguration('bytesize', 0)) ,
                    'id' => $eqLogic->getId() ,
                    'ipDevice' => $eqLogic->getConfiguration('ipuser', 'modbus') ,
                    'registerParams' => $cmdsOptions
                );

                $value = json_encode($value);
                modbus::socketConnection($value);
            }

            return $isOk;

        }
        catch(Exception $e)
        {
            log::add('modbus', 'info', 'Exception reçue : ' . $e->getMessage());
            return false;
        }
    }*/

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

class modbusCmd extends cmd
{
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
    public function execute($_options = array())
    {

        if ($this->type != 'action')
        {
            return;
        }

		$cmdlogical = $this->getLogicalId();
        $eqLogic = $this->getEqLogic();
        $offset = $this->getConfiguration('offset', 0);
        $unitId = intval($eqLogic->getConfiguration('unitID', 0));

        $cmdsOptions = array(
            'nameCmd' => $this->getName() ,
            'cmdId' => $this->getId() ,
            'format' => $this->getConfiguration('formatIO', 0) ,
            'functioncode' => $this->getConfiguration('choicefunctioncode', 0) ,
            'nbregister' => $this->getConfiguration('nbbytes', 0) ,
            'startregister' => $this->getConfiguration('startregister', 0) ,
            'wordorder' => $this->getConfiguration('wordorder', 0) ,
            'byteorder' => $this->getConfiguration('byteorder', 0) ,
            'isnegatif' => $this->getConfiguration('isnegatif', 0) ,
            'offset' => $offset
        );

        $typeDevice = $eqLogic->getConfiguration('choicemodbus', 0);

        $value = (array(
            'apikey' => jeedom::getApiKey('modbus') ,
            'typeDevice' => $typeDevice,
            'action' => 'writeAction',
            'unitID' => $unitId

        ));

        $value['options'] = $cmdsOptions;

        if ($typeDevice == 'rtu')
        {
            $value['deviceInfo'] = array(
                'typeDevice' => $eqLogic->getConfiguration('choicemodbus', 0) ,
                'portserial' => $eqLogic->getConfiguration('portserial', 0) ,
                'baudrate' => intval($eqLogic->getConfiguration('baudrate', 0)) ,
                'unitID' => $unitId ,
                'parity' => $eqLogic->getConfiguration('parity', 0) ,
                'stopbits' => intval($eqLogic->getConfiguration('stopbits', 0)) ,
                'bytesize' => intval($eqLogic->getConfiguration('bytesize', 0)) ,
                'id' => $eqLogic->getId()
            );

        }
        elseif ($typeDevice == 'tcp')
        {
            $value['deviceInfo'] = array(
                'typeDevice' => $typeDevice,
                'id' => $eqLogic->getId() ,
                'unitID' => $unitId,
                'ipDevice' => $eqLogic->getConfiguration('ipuser', 'modbus')
            );

        }


        if ($this->getSubtype() == 'slider')
        {

            $value['options']['value'] = $_options['slider'];

        }

        if ($this->getSubtype() == 'other')
        {
            $toverif = $this->getConfiguration('valeurToAction');
            if (strlen($toverif) == 1 && ($toverif == '0' || $toverif == '1'))
            {
                $value['options']['value'] = $this->getConfiguration('valeurToAction');
            }
            else
            {
                log::add('modbus', 'info', 'VALEUR POUR ECRITURE COIL NON VALIDE');
                return;
            }
        }

        if ($cmdlogical == 'ecriturebit')
        {
            if ($this->getConfiguration('choicefunctioncode', 0) == 'fc03')
            {
                $infoBinary = cmd::byEqLogicIdAndLogicalId($eqLogic->getId() , 'infobitbinary');
                if (is_object($infoBinary))
                {
                    $valueInfo = intval($infoBinary->execCmd());

                }
                $arrayMessageBit = explode('&', $_options['message']);
                $valueBit = $arrayMessageBit[0];
                $position = $arrayMessageBit[1];
                $arrayTemp = array_map('intval', $array = str_split($valueInfo));
                $toReplace = count($arrayTemp) - $position;
                $arrayTemp[$toReplace] = $valueBit;
                $stringToConvert = implode($arrayTemp);
                $stringDecimal = bindec($stringToConvert);
                log::add('modbus', 'debug', 'STRINGDECIMAL  ' . $stringDecimal);
                $value['options']['value'] = $stringDecimal;
                $value['options']['functioncode'] = 'fc06';
            }

        }

        if ($cmdlogical == 'ecrituremultiRegisters')
        {
            if ($this->getConfiguration('choicefunctioncode', 0) == 'fc16')
            {
                $arrayMultipleRegisters = explode('|', $_options['message']);
                foreach ($arrayMultipleRegisters as $k => $v)
                {
                    list($val, $nbregister) = explode('&', $v);
                    $result[$k]['valeur'] = $val;
                    $result[$k]['nbregister'] = $nbregister;
                }
                $arrayMultipleRegisters = $result;
                $value['options']['valuesrequest'] = $arrayMultipleRegisters;
            }
        }


        if ($cmdlogical == 'multicoils')
        {
            if ($this->getConfiguration('choicefunctioncode', 0) == 'fc15')
            {
                $arrayMultipleCoils = array_map('intval', $arrayMultipleCoils = str_split($_options['message'], 1));
                foreach ($arrayMultipleCoils as $val)
                {
                    if ($val != 0 && $val != 1)
                    {
                        log::add('modbus', 'info', 'ERREUR DANS VOS DONNEES COILS : N ECRIRE QUE 0 ou 1');
                        return;
                    }
                }
                $value['options']['valuesrequest'] = $arrayMultipleCoils;
            }
        }

        $value = json_encode($value);
        log::add('modbus', 'debug', 'WRITETEST : ' . $value);
        modbus::socketConnection($value);

    }

    /*     * **********************Getteur Setteur*************************** */
}