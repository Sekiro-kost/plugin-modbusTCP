# This file is part of Jeedom.
#
# Jeedom is free software: you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation, either version 3 of the License, or
# (at your option) any later version.
#
# Jeedom is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with Jeedom. If not, see <http://www.gnu.org/licenses/>.

import logging
import string
import sys
import os
import time
import datetime
import re
import signal
from optparse import OptionParser
import argparse
from os.path import join
import json
import pymodbus
from pymodbus.client.sync import ModbusTcpClient
from pymodbus.client.sync import ModbusSerialClient as ModbusClient
from pymodbus.constants import Defaults
from pymodbus.exceptions import *
from pymodbus.utilities import hexlify_packets, ModbusTransactionState
#from struct import *
import struct


try:
	from jeedom.jeedom import *
except ImportError as e:
	print("Error: importing module jeedom.jeedom" + str(e))
	sys.exit(1)

try:
	from ModbusDep.ModbusDep import *
except ImportError:
	print("Error: importing module ModbusDep")
	sys.exit(1)



def testWrite(ipDevice, typeInput, values, nbByte, startRegister):
    client = ModbusTcpClient(ipDevice)
    if typeInput == 'coils':
        if len(values) > 1:
            stringValues = ','.join(values)
            valueDef = list(eval(stringValues))
            client.write_coils(int(startRegister)-1, valueDef)
        elif len(values) == 1:
            stringValues = ''.join(values)
            testVal = eval(stringValues)
            client.write_coil(int(startRegister)-1, testVal)
    elif typeInput == 'holdings':
        if len(values) > 1:
            values = list(map(int, values))
            client.write_registers(int(startRegister)-1, values)
        elif len(values) == 1:
            stringValue = ''.join(values)
            intval = int(stringValue)
            client.write_register(int(startRegister)-1, intval)
    client.close()


def writeFunction(ipDevice, options):
    client = ModbusTcpClient(ipDevice)
    if options['typeIO'] == 'coils':
        if int(options['value']) == 0:
            client.write_coil(int(options['startregister'])-1, False)
        elif int(options['value']) == 1:
            client.write_coil(int(options['startregister'])-1, True)
    elif options['typeIO'] == 'holdingRegisters':
        client.write_register(int(options['startregister'])-1, int(options['value']))

def testAllCmds(ipDevice, registerParams):
	client = ModbusTcpClient(ipDevice)
	results = {}
	results['FUNC'] = 'allCmds'
	results['ipdevice'] = ipDevice
	results['coils'] = {}
	for x in registerParams:
	    x['dataResult'] = {}
	    y = range(int(x['nbregister']))
	    logging.debug(x)
	    if x['typeIO'] == 'coils':
	        if x['functioncode'] == 'fc01':
	            logging.debug("TRAITEMENT COMMANDE : " + x['nameCmd'])
	            result = client.read_coils(int(x['startregister']) - 1, int(x['nbregister']))
	            results['coils'][x['nameCmd']] = []
	            for Byte in y:
	                parsedByte = result.bits[Byte]
	                logging.debug(parsedByte)
	                if (parsedByte == True):
	                    valTransf = 1
	                elif (parsedByte == False):
	                    valTransf = 0
	                arrayBits = {'StartRegister': int(x['startregister']), 'Byte': Byte, 'valueConverse' : valTransf, 'CmdId' : x['cmdId'], 'formatForConversion' :x['format']}
	                results['coils'][x['nameCmd']].append(arrayBits)
	                logging.debug('plop')
	        elif x['functioncode'] != 'fc05' or x['functioncode'] != 'fc15' or x['functioncode'] != 'fc01':
	            logging.debug("ERREUR FUNCTION CODE DE LA COMMMANDE : " + x['nameCmd'] + " >>  MAUVAIS CODE FONCTION SELECTIONNE, LECTEUR SEULE PERMISE")
	    elif x['typeIO'] == 'inputRegisters':
	        if x['functioncode'] == 'fc04':
	            logging.debug("TRAITEMENT COMMANDE : " + x['nameCmd'])
	            result = client.read_input_registers(int(x['startregister'] ) -1, int(x['nbregister']))
	            results['inputRegisters'] = {}
	            results['inputRegisters'][x['nameCmd']] = []
	            hexValue = ''
	            for Byte in y:
	                logging.debug(result.registers[int(Byte)])
	                value = result.registers[int(Byte)]
	                hexValue += hex(value)[2:]
	            if x['format'] == 'floatformat':
	                logging.debug('coucou')
	                logging.debug(hexValue)
	                floatValue = struct.unpack('!f', bytes.fromhex(hexValue))[0]
	                arrayBits = {'StartRegister': int(x['startregister']),'Byte': Byte, 'valueConverse' : floatValue, 'CmdId' : x['cmdId']}
	                results['inputRegisters'][x['nameCmd']].append(arrayBits)
	        else :
	            logging.debug("ERREUR FUNCTION CODE DE LA COMMMANDE : " + x['nameCmd'] + " >>  MAUVAIS CODE FONCTION SELECTIONNE, LECTEUR SEULE PERMISE")
	    elif x['typeIO'] == 'holdingRegisters':
	        if x['functioncode'] != 'fc03' or x['functioncode'] != 'fc06' or x['functioncode'] != 'fc16':
	            logging.debug("ERREUR FUNCTION CODE DE LA COMMMANDE : " + x['nameCmd'] + " >>  MAUVAIS CODE FONCTION SELECTIONNE. CHOISIR FC3,FC6 ou FC16")
	        elif x['functioncode'] == 'fc03':
	            logging.debug("TRAITEMENT COMMANDE : " + x['nameCmd'])
	            result = client.read_holding_registers(int(x['startregister']), int(x['nbregister']))
	            for byte in y:
	                logging.debug(result.registers[byte])
	        elif x['functioncode'] == 'fc06':
	            logging.debug("TRAITEMENT COMMANDE : " + x['nameCmd'])
	            result = client.write_register(int(x['startregister']), int(x['nbregister']))
	        elif x['functioncode'] == 'fc16':
	            logging.debug("TRAITEMENT COMMANDE : " + x['nameCmd'])
	            result = client.write_registers(int(x['startregister']), int(x['nbregister']))
	    else:
	        logging.debug('INPUTREGISTER')
	return results


def read_socket():
	global JEEDOM_SOCKET_MESSAGE
	global ret
	if not JEEDOM_SOCKET_MESSAGE.empty():
		logging.debug("Message received in socket JEEDOM_SOCKET_MESSAGE")
		message = json.loads(JEEDOM_SOCKET_MESSAGE.get())
		if message['apikey'] != _apikey:
			logging.error("Invalid apikey from socket : " + str(message))
			return
		try:
			if message['action'] == 'read':
			    ret = testRead(message['eqlogicid'], message['cmdId'], message['ipDevice'], message['typeOfCmd'], message['nbByte'],message['adresse'])
			    logging.debug("MESSAGE ENVOYE ACTION READ")
			    jeedom_com.send_change_immediate(ret)
			elif message['action'] == 'writeAction':
			    #testWrite(message['ipDevice'], message['typeOfCmd'],message['values'], message['registers'], message['startRegister'])
			    writeFunction(message['ipDevice'], message['options'])
			    logging.debug("MESSAGE ENVOYE ACTION WRITE")
			elif message['action'] == 'readCron':
			    ret = testReadCron(message['eqlogicid'], message['data'], message['ipDevice'])
			    jeedom_com.send_change_immediate(ret)
			elif message['action'] == 'readCronEssai':
			    ret = testReadEssai(message['eqlogicid'], message['adresse'], message['nbbyte'], message['ipDevice'])
			    jeedom_com.send_change_immediate(ret)
			elif message['action'] == 'newCmds':
			    ret = testAllCmds(message['modbusDevice']['ipDevice'], message['modbusDevice']['registerParams'])
			    jeedom_com.send_change_immediate(ret)
			#logging.debug(ret)
			#jeedom_com.send_change_immediate(ret)
		except Exception as e:
			logging.error('Send command to demon error : '+str(e))


def listen():
	jeedom_socket.open()
	global JEEDOM_SOCKET_MESSAGE
	logging.debug("Start listening...")
	try:
		while 1:
			time.sleep(0.5)
			read_socket()
	except KeyboardInterrupt:
		shutdown()


# ----------------------------------------------------------------------------

def handler(signum=None, frame=None):
	logging.debug("Signal %i caught, exiting..." % int(signum))
	shutdown()


def shutdown():
	logging.debug("Shutdown")
	logging.debug("Removing PID file " + str(_pidfile))
	try:
		os.remove(_pidfile)
	except:
		pass
	try:
		jeedom_socket.close()
	except:
		pass
	try:
		jeedom_serial.close()
	except:
		pass
	logging.debug("Exit 0")
	sys.stdout.flush()
	os._exit(0)

# ----------------------------------------------------------------------------

_log_level = "error"
_socket_port = 55030
_socket_host = '127.0.0.1'
_device = 'auto'
_pidfile = '/tmp/modbusd.pid'
_apikey = ''
_callback = ''

parser = argparse.ArgumentParser(description='Modbus Daemon for Jeedom plugin')
parser.add_argument("--device", help="Device", type=str)
parser.add_argument("--socketport", help="Socketport for server", type=str)
parser.add_argument("--sockethost", help="Sockethost for server", type=str)
parser.add_argument("--loglevel", help="Log Level for the daemon", type=str)
parser.add_argument("--callback", help="Callback", type=str)
parser.add_argument("--apikey", help="Apikey", type=str)
parser.add_argument("--pid", help="Pid file", type=str)
args = parser.parse_args()

if args.device:
    _device = args.device
if args.socketport:
    _socket_port = int(args.socketport)
if args.sockethost:
    _socket_host = args.sockethost
if args.loglevel:
    _log_level = args.loglevel
if args.callback:
    _callback = args.callback
if args.apikey:
    _apikey = args.apikey
if args.pid:
    _pidfile = args.pid

#_socket_port = int(_socket_port)

jeedom_utils.set_log_level(_log_level)

logging.info('Start demond')
logging.info('Log level : '+str(_log_level))
logging.info('Socket port : '+str(_socket_port))
logging.info('Socket host : '+str(_socket_host))
logging.info('PID file : '+str(_pidfile))
logging.info('Apikey : '+str(_apikey))
logging.info('Device : '+str(_device))

signal.signal(signal.SIGINT, handler)
signal.signal(signal.SIGTERM, handler)


try:
	jeedom_utils.write_pid(str(_pidfile))
	jeedom_socket = jeedom_socket(port=_socket_port,address=_socket_host)
	jeedom_com = jeedom_com(apikey = _apikey,url = _callback)
	listen()
except Exception as e:
	logging.error('Fatal error : '+str(e))
	shutdown()
