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

import globals
from queue import Queue
import logging
import bitstring
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
from codecs import decode
from pymodbus.client.sync import ModbusTcpClient
from pymodbus.client.sync import ModbusSerialClient as ModbusClient
from pymodbus.constants import Defaults
from pymodbus.exceptions import *
from pymodbus.utilities import hexlify_packets, ModbusTransactionState
from pymodbus.payload import BinaryPayloadBuilder
from pymodbus.payload import BinaryPayloadDecoder
from pymodbus.constants import Endian
from pymodbus.compat import iteritems
from pymodbus.constants import Defaults
import struct
import serial
from pymodbus.pdu import ModbusRequest
from pymodbus.transaction import ModbusRtuFramer
from threading import Thread, Lock

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

def builderPayloadFunction(wordorder, byteorder):
	builder = ''
	if wordorder == 'bigword' and byteorder == 'bigbyte':
	    builder = BinaryPayloadBuilder(byteorder=Endian.Big, wordorder=Endian.Big)
	elif wordorder == 'bigword' and byteorder == 'littlebyte':
	    builder = BinaryPayloadBuilder(byteorder=Endian.Big, wordorder=Endian.Little)
	elif wordorder == 'littleword' and byteorder == 'bigbyte':
	    builder = BinaryPayloadBuilder(byteorder=Endian.Little, wordorder=Endian.Big)
	elif wordorder == 'littleword' and byteorder == 'littlebyte':
	    builder = BinaryPayloadBuilder(byteorder=Endian.Little, wordorder=Endian.Little)
	return builder

def decoderPayloadFunction(result, wordorder, byteorder):
	decoder = ''
	if wordorder == 'bigword' and byteorder == 'bigbyte':
	    decoder = BinaryPayloadDecoder.fromRegisters(result.registers,byteorder=Endian.Big,wordorder=Endian.Big)
	elif wordorder == 'bigword' and byteorder == 'littlebyte':
	    decoder = BinaryPayloadDecoder.fromRegisters(result.registers,byteorder=Endian.Big,wordorder=Endian.Little)
	elif wordorder == 'littleword' and byteorder == 'bigbyte':
	    decoder = BinaryPayloadDecoder.fromRegisters(result.registers,byteorder=Endian.Little,wordorder=Endian.Big)
	elif wordorder == 'littleword' and byteorder == 'littlebyte':
	    decoder = BinaryPayloadDecoder.fromRegisters(result.registers,byteorder=Endian.Little,wordorder=Endian.Little)
	return decoder

def decoderAdd(results, device, decoder, format, nbregister, isnegatif, startregister, cmdId, cmdName):
    if format == 'floatformat':
            if int(nbregister) == 2:
                decoded = {'float': decoder.decode_32bit_float()}
            elif int(nbregister) == 4:
                decoded = {'float': decoder.decode_64bit_float()}
            else :
                logging.debug('NOMBRE REGISTRE INVALIDE POUR FLOAT : 2 ou 4 registres')
                return
            for name, value in iteritems(decoded):
                arrayBits = {'StartRegister': int(startregister), 'CmdId' : cmdId, 'value' : value}
                results['data'][device][cmdName].append(arrayBits)
    elif format == 'longformat':
        if int(infoCmd['isnegatif']) == 0:
            if int(nbregister) < 1:
                decoded = {'int':decoder.decode_8bit_uint()}
            elif int(nbregister) == 1:
                decoded = {'int':decoder.decode_16bit_uint()}
            elif int(nbregister) == 2:
                decoded = {'int':decoder.decode_32bit_uint()}
            elif int(nbregister) == 4:
                decoded = {'int':decoder.decode_64bit_uint()}
        elif int(infoCmd['isnegatif']) == 1:
            if int(nbregister) < 1:
                decoded = {'int':decoder.decode_8bit_int()}
            elif int(nbregister) == 1:
                decoded = {'int':decoder.decode_16bit_int()}
            elif int(nbregister) == 2:
                decoded = {'int':decoder.decode_32bit_int()}
            elif int(nbregister)== 4:
                decoded = {'int':decoder.decode_64bit_int()}
        for name, value in iteritems(decoded):
            arrayBits = {'StartRegister': int(startregister), 'CmdId' : cmdId, 'value' : value}
            results['data'][device][cmdName].append(arrayBits)
    elif infoCmd['format'] == 'bitsformat':
        decoded = decoder.decode_bits()
        nbiteration = int(nbregister) * 2 - 1
        for x in range(nbiteration):
            decoded += decoder.decode_bits()
        i = 0
        for y in decoded:
            if y == True:
                decoded[i] = "1"
            elif y == False:
                decoded[i] = "0"
            i+= 1
        arrayString = ''.join(decoded)
        arrayBits = {'StartRegister': int(startregister), 'CmdId' : cmdId, 'value' : arrayString}
        results['data'][device][cmdName].append(arrayBits)


def conversionToBuilder(builder, nbregister, format, isnegatif, value):
	if format == 'floatformat':
		if int(nbregister) == 2:
			builder.add_32bit_float(float(value))
		elif int(nbregister) == 4:
			builder.add_64bit_float(float(value))
	elif format == 'longformat':
		if int(nbregister) == 1:
			if int(isnegatif) == 0:
				builder.add_16bit_uint(int(value))
			else:
				if value > 0 :
				    valueNegative = value * -1
				else:
				    valueNegative = value
				builder.add_16bit_int(int(valueNegative))
		elif int(nbregister) == 2:
			if int(isnegatif) == 0:
				builder.add_32bit_uint(int(value))
			else:
				if value > 0 :
				    valueNegative = value * -1
				else:
				    valueNegative = value
				builder.add_32bit_int(int(valueNegative))
		elif int(nbregister) == 4:
			if int(isnegatif) == 0:
				builder.add_64bit_uint(int(value))
			else:
				if value > 0 :
				    valueNegative = value * -1
				else:
				    valueNegative = value
				builder.add_64bit_int(int(valueNegative))
		else:
			logging.debug('ERREUR DANS LE NOMBRE DE REGISTRE')
			return
	return builder



def writeFunc(deviceInfo, unitID, typeDevice, registerParams):
	logging.debug(registerParams)
	offset = int(registerParams['offset'])
	results = {}
	results['FUNC'] = 'write'
	results['isOk'] = 'yes'
	if typeDevice == 'tcp':
	    varUnitID = 1
	    try:
	        client = ModbusTcpClient(deviceInfo['ipDevice'])
	    except Exception as e:
	        logging.error("Erreur Connexion au Client : " + str(e))
	        results['isOk'] = 'no'
	elif typeDevice == 'rtu':
	    varUnitID = int(unitID)
	    varPortSerial = str(deviceInfo['portserial'])
	    varByteSize = int(deviceInfo['bytesize'])
	    varBaudrate = int(deviceInfo['baudrate'])
	    varParity = str(deviceInfo['parity'])
	    varStopBits = int(deviceInfo['stopbits'])
	    try:
	         client = ModbusClient(method = 'rtu', port=varPortSerial, bytesize = varByteSize, baudrate = varBaudrate, parity = varParity, stopbits = varStopBits, timeout=3, strict=False)
	    except Exception as e:
	        logging.error("Erreur Connexion au Client : " + str(e))
	        results['isOk'] = 'no'
	builder = builderPayloadFunction(registerParams['wordorder'], registerParams['byteorder'])
	if registerParams['functioncode'] == 'fc05':
	    try	:
	        client.write_coil(int(registerParams['startregister']) + (offset), int(registerParams['value']), unit=varUnitID)
	    except Exception as e:
	        logging.error("Erreur Connexion au Client : " + str(e))
	        results['isOk'] = 'no'
	elif registerParams['functioncode'] == 'fc06':
	    try:
	        #client.write_register(int(registerParams['startregister']) + (offset) , int(registerParams['value']), unit=varUnitID)
	        #if registerParams['value'].find('.') != -1:
	            #logging.error(" SINGLE REGISTRE : VALEUR N EST PAS UN ENTIER " + str(e))
	        if int(registerParams['value']) < 0:
	            newNumber = 65535 - ( int(registerParams['value']) * -1 ) + 1
	            client.write_register(int(registerParams['startregister']) + (offset) , newNumber, unit=varUnitID)
	        elif int(registerParams['value']) > 0:
	            client.write_register(int(registerParams['startregister']) + (offset) , int(registerParams['value']), unit=varUnitID)
	    except Exception as e:
	        logging.error("Erreur Ecriture : " + str(e))
	        results['isOk'] = 'no'
	elif registerParams['functioncode'] == 'fc15':
	    try:
	        client.write_coils(int(registerParams['startregister']) + (offset), registerParams['valuesrequest'], unit=varUnitID)
	    except Exception as e:
	        logging.error("Erreur Connextion au client : " + str(e))
	        results['isOk'] = 'no'
	elif registerParams['functioncode'] == 'fc16' and registerParams['isSpecific'] != '1':
	    for x in registerParams['valuesrequest']:
	        logging.debug(x)
	        if x['valeur'].find('.') != -1:
	            if x['nbregister'] == '1':
	                builder.add_16bit_float(float(x['valeur']))
	            elif x['nbregister'] == '2':
	                builder.add_32bit_float(float(x['valeur']))
	            elif x['nbregister'] == '4':
	                builder.add_64bit_float(float(x['valeur']))
	            else:
	                return
	        else:
	            if x['nbregister'] == '1':
	                if int(x['valeur']) < 0:
	                    builder.add_16bit_int(int(x['valeur']))
	                elif 0 <= int(x['valeur']) <= 65535:
	                    logging.debug(x['valeur'])
	                    builder.add_16bit_uint(int(x['valeur']))
	                elif int(x['valeur']) > 65535:
	                    return
	            elif x['nbregister'] == '2':
	                if int(x['valeur']) < 0:
	                    logging.debug('NEGATIF')
	                    builder.add_32bit_int(int(x['valeur']))
	                elif int(x['valeur']) > 0:
	                    logging.debug('POSITIF')
	                    builder.add_32bit_uint(int(x['valeur']))
	            elif x['nbregister'] == '4':
	                if int(x['valeur']) < 0:
	                    builder.add_64bit_int(int(x['valeur']))
	                elif int(x['valeur']) > 0:
	                    builder.add_64bit_uint(int(x['valeur']))
	            else:
	                return
	    registers = builder.to_registers()
	    registers = builder.build()
	    try	:
	        #client.write_registers(int(registerParams['startregister']) - offset, payload, skip_encode=True, unit=varUnitID)
	        client.write_registers(int(registerParams['startregister']) + (offset), registers, unit=varUnitID)
	    except Exception as e:
	        logging.error("Erreur Ecriture : " + str(e))
	        results['isOk'] = 'no'
	elif registerParams['functioncode'] == 'fc16' and registerParams['isSpecific'] == '1' :
	    for x in registerParams['valuesrequest']:
	        logging.debug(x)
	        builder = builderPayloadFunction(registerParams['wordorder'], registerParams['byteorder'])
	        logging.debug(builder)
	        if x['valeur'].find('.') != -1:
	            if x['nbregister'] == '2':
	                builder.add_32bit_float(float(x['valeur']))
	            elif x['nbregister'] == '4':
	                builder.add_64bit_float(float(x['valeur']))
	            else:
	                return
	        else:
	            if x['nbregister'] == '1':
	                if int(x['valeur']) < 0:
	                    builder.add_16bit_int(int(x['valeur']))
	                elif 0 <= int(x['valeur']) <= 65535:
	                    logging.debug(x['valeur'])
	                    builder.add_16bit_uint(int(x['valeur']))
	                elif int(x['valeur']) > 65535:
	                    return
	            elif x['nbregister'] == '2':
	                if int(x['valeur']) < 0:
	                    logging.debug('NEGATIF')
	                    builder.add_32bit_int(int(x['valeur']))
	                elif int(x['valeur']) > 0:
	                    logging.debug('POSITIF')
	                    builder.add_32bit_uint(int(x['valeur']))
	            elif x['nbregister'] == '4':
	                if int(x['valeur']) < 0:
	                    builder.add_64bit_int(int(x['valeur']))
	                elif int(x['valeur']) > 0:
	                    builder.add_64bit_uint(int(x['valeur']))
	            else:
	                return
	        registers = builder.to_registers()
	        #registers= builder.build()
	        try	:
	        #client.write_registers(int(registerParams['startregister']) - offset, payload, skip_encode=True, unit=varUnitID)
	            client.write_registers(int(x['startregister']) + (offset), registers, unit=varUnitID)
	        except Exception as e:
	            logging.error("Erreur Ecriture : " + str(e))
	            results['isOk'] = 'no'
	client.close()
	return results



def readDevices():
	if len(globals.DEVICES) == 0:
	    logging.debug('!!!!!   AUCUN EQUIPEMENT EXISTANT    !!!!!!')
	    return
	results = {}
	results['data'] = {}
	results['FUNC'] = 'readF'
	time.sleep(int(globals.TIMESLEEP))
	for device in globals.DEVICES:
		results['data'][device]= {}
		logging.debug(results)
		if(globals.DEVICES[device]['typeDevice'] == 'rtu'):
		    try:
		        varPortSerial = str(globals.DEVICES[device]['portserial'])
		        varByteSize = int(globals.DEVICES[device]['bytesize'])
		        varBaudrate = int(globals.DEVICES[device]['baudrate'])
		        varParity = str(globals.DEVICES[device]['parity'])
		        varUnitID = int(globals.DEVICES[device]['unitID'])
		        varStopBits = int(globals.DEVICES[device]['stopbits'])
		        client = ModbusClient(method = 'rtu', port=varPortSerial, bytesize = varByteSize, baudrate = varBaudrate, parity = varParity, stopbits = varStopBits, timeout=3, strict=False)
		        conn = client.connect()
		        logging.debug(conn)
		    except Exception as e:
		        logging.error("Erreur Connexion au Client : " + str(e))
		        continue
		elif(globals.DEVICES[device]['typeDevice'] == 'tcp'):
		    try:
		        client = ModbusTcpClient(globals.DEVICES[device]['ipDevice'])
		        varUnitID = 1
		    except Exception as e:
		        logging.error("Erreur Connexion au Client : " + str(e))
		        continue
		for infoCmd in globals.DEVICES[device]['registerParams']:
			logging.debug(infoCmd)
			offset = int(infoCmd['offset'])
			results['data'][device][infoCmd['nameCmd']] = []
			if infoCmd['functioncode'] == 'fc01':
			    try:
			        result = client.read_coils(int(infoCmd['startregister'])+(offset), int(infoCmd['nbregister']),  unit=varUnitID)
			        logging.debug(result)
			    except Exception as e:
			        logging.error("Erreur MODBUS : " + str(e))
			        continue
			    payload = []
			    for y in range(int(infoCmd['nbregister'])):
			        parsedByte = result.bits[y]
			        if (parsedByte == True):
			            valTransf = 1
			        elif (parsedByte == False):
			            valTransf = 0
			        payload.append(parsedByte)
			        arrayBits = {'StartRegister': int(infoCmd['startregister']), 'CmdId' : infoCmd['cmdId'], 'value' : valTransf}
			        results['data'][device][infoCmd['nameCmd']].append(arrayBits)
			elif infoCmd['functioncode'] == 'fc02':
			    try:
			        result = client.read_discrete_inputs(int(infoCmd['startregister'])+ (offset), int(infoCmd['nbregister']),  unit=varUnitID)
			        logging.debug(result)
			    except Exception as e:
			        logging.error("Erreur MODBUS : " + str(e))
			        continue
			    payload = []
			    for y in range(int(infoCmd['nbregister'])):
			        parsedByte = result.bits[y]
			        if (parsedByte == True):
			            valTransf = 1
			        elif (parsedByte == False):
			            valTransf = 0
			        payload.append(parsedByte)
			        arrayBits = {'StartRegister': int(infoCmd['startregister']), 'CmdId' : infoCmd['cmdId'], 'value' : valTransf}
			        results['data'][device][infoCmd['nameCmd']].append(arrayBits)
			elif infoCmd['functioncode'] == 'fc03':
			    try:
			        result = client.read_holding_registers(int(infoCmd['startregister'])+ (offset), int(infoCmd['nbregister']), unit=varUnitID)
			        logging.debug('TEST2')
			        logging.debug(result)
			    except Exception as e:
			        logging.error("Erreur MODBUS : " + str(e))
			        continue
			elif infoCmd['functioncode'] == 'fc04':
			    try:
			        result = client.read_input_registers(int(infoCmd['startregister'])+ (offset), int(infoCmd['nbregister']),  unit=varUnitID)
			        logging.debug(result)
			    except Exception as e:
			        logging.error("Erreur MODBUS : " + str(e))
			        continue
			if infoCmd['functioncode'] == 'fc04' or infoCmd['functioncode'] == 'fc03':
			    try:
			        decoder = decoderPayloadFunction(result,infoCmd['wordorder'],infoCmd['byteorder'])
			    except Exception as e:
			        logging.error("Erreur MODBUS : " + str(e))
			        continue
			    try:
			        decoderAdd(results,device,decoder,infoCmd['format'],infoCmd['nbregister'],infoCmd['isnegatif'],infoCmd['startregister'],infoCmd['cmdId'],infoCmd['nameCmd'])
			    except Exception as e:
			        logging.error("Erreur MODBUS : " + str(e))
			        continue
	#time.sleep(int(globals.TIMESLEEP))
	jeedom_com.send_change_immediate(results)
	client.close()



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
			if message['action'] == 'writeAction':
			    ret = writeFunc(message['deviceInfo'], message['unitID'], message['typeDevice'], message['options'])
			    logging.debug("MESSAGE ENVOYE ACTION WRITE")
			    jeedom_com.send_change_immediate(ret)
			elif message['action'] == 'updateDeviceToGlobals':
			    logging.debug("ADD DEVICE TO GLOBALS :"+str(message['deviceInfo']))
			    globals.DEVICES[message['deviceInfo']['id']] = message['deviceInfo']
			elif message['action'] == 'deleteDevice':
			    del globals.DEVICES[message['deviceInfo']['id']]
		except Exception as e:
			logging.error('Send command to demon error : '+str(e))


def listen():
	jeedom_socket.open()
	global JEEDOM_SOCKET_MESSAGE
	logging.debug("Start listening...")
	try:
		while 1:
			time.sleep(0.5)
			now = datetime.datetime.utcnow()
			try:
			    read_socket()
			except Exception as e:
			    logging.error("Exception on socket : " + str(e))
			try:
			    if now < (globals.LAST_TIME_READ+datetime.timedelta(milliseconds=globals.TIMESLEEP)):
			        continue
			    else:
			        globals.LAST_TIME_READ = now
			#tp.start()
			        if len(globals.DEVICES) != 0:
			            readDevices()
			except Exception as e:
			    logging.error("Exception on read device : " + str(e))
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
_retrydefault = ''

parser = argparse.ArgumentParser(description='Modbus Daemon for Jeedom plugin')
parser.add_argument("--device", help="Device", type=str)
parser.add_argument("--socketport", help="Socketport for server", type=str)
parser.add_argument("--timesleep", help="Time Sleep", type=str)
parser.add_argument("--sockethost", help="Sockethost for server", type=str)
parser.add_argument("--retrydefault", help="Retry or Not", type=str)
parser.add_argument("--nbretry", help="Nb Tentatives", type=int)
parser.add_argument("--timeoutretries", help="Tiemout entre tentatives", type=int)
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
if args.retrydefault:
    _retrydefault = args.retrydefault
if args.nbretry:
    globals.NBRETRY = int(args.nbretry)
if args.timeoutretries:
    globals.TIMEOUTRETRIES = int(args.timeoutretries)
if args.loglevel:
    _log_level = args.loglevel
if args.timesleep:
    globals.TIMESLEEP = int(args.timesleep)
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
logging.info('RetryDefault : '+str(globals.RETRYDEFAULT))
logging.info('nbRetry : '+str(globals.NBRETRY))
logging.info('Timeout : '+str(globals.TIMEOUTRETRIES))
logging.info('Socket port : '+str(_socket_port))
logging.info('Socket host : '+str(_socket_host))
logging.info('PID file : '+str(_pidfile))
logging.info('Apikey : '+str(_apikey))
logging.info('Device : '+str(_device))
logging.info('TimeSleep : '+str(globals.TIMESLEEP))

signal.signal(signal.SIGINT, handler)
signal.signal(signal.SIGTERM, handler)


try:
	jeedom_utils.write_pid(str(_pidfile))
	jeedom_com = jeedom_com(apikey = _apikey,url = _callback)
	if not jeedom_com.test():
	    logging.error('Network communication issues. Please fixe your Jeedom network configuration.')
	    shutdown()
	if _retrydefault == 'True':
	    Defaults.RetryOnEmpty = True
	    Defaults.Timeout = globals.TIMEOUTRETRIES
	    Defaults.Retries = globals.NBRETRY
	else:
	    Defaults.RetryOnEmpty = False
	jeedom_socket = jeedom_socket(port=_socket_port,address=_socket_host)
	listen()
except Exception as e:
	logging.error('Fatal error : '+str(e))
	shutdown()
