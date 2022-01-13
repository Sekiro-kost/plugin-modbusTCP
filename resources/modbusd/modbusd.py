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
#from struct import *
import struct
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



def writeFunc(ipdevice , registerParams):
	logging.debug('WRITE FUNCTION PYTHON')  
	logging.debug(registerParams)
	offset = int(registerParams['offset'])
	results = {}
	results['FUNC'] = 'write'
	client = ModbusTcpClient(ipdevice)    
	builder = ''
	if registerParams['wordorder'] == 'bigword' and registerParams['byteorder'] == 'bigbyte':
	    builder = BinaryPayloadBuilder(byteorder=Endian.Big, wordorder=Endian.Big)
	elif registerParams['wordorder'] == 'bigword' and registerParams['byteorder'] == 'littlebyte':
	    builder = BinaryPayloadBuilder(byteorder=Endian.Big, wordorder=Endian.Big)
	elif registerParams['wordorder'] == 'littleword' and registerParams['byteorder'] == 'bigbyte':
	    builder = BinaryPayloadBuilder(byteorder=Endian.Big, wordorder=Endian.Big)
	elif registerParams['wordorder'] == 'littleword' and registerParams['byteorder'] == 'littlebyte':
	    builder = BinaryPayloadBuilder(byteorder=Endian.Big, wordorder=Endian.Big)
	count = registerParams['nbregister']
	if registerParams['functioncode'] == 'fc05':
	    client.write_coil(int(registerParams['startregister']) - offset, int(registerParams['value']))
	elif registerParams['functioncode'] == 'fc06':
	    if registerParams['format'] == 'floatformat':
	        if int(registerParams['nbregister']) == 1:
	            builder.add_16bit_float(float(registerParams['value']))
	        elif int(registerParams['nbregister']) == 2:
	            builder.add_32bit_float(float(registerParams['value']))
	        elif int(registerParams['nbregister']) == 4:
	            builder.add_64bit_float(float(registerParams['value']))            
	        #logging.debug(registerParams['value'])
	        #builder.add_32bit_float(float(registerParams['value']))
	    elif registerParams['format'] == 'longformat':
	        if int(registerParams['nbregister']) == 1:
	            if int(registerParams['isnegatif']) == 0:
	                builder.add_16bit_uint(int(registerParams['value']))                  
	            else:
	                builder.add_16bit_int(hex(registerParams['value']))                  
	        elif int(registerParams['nbregister']) == 2:
	            if int(registerParams['isnegatif']) == 0:
	                builder.add_32bit_uint(int(registerParams['value']))                  
	            else:
	                builder.add_32bit_int(hex(registerParams['value']))                  
	        elif int(registerParams['nbregister']) == 4:
	            if int(registerParams['isnegatif']) == 0:
	                builder.add_64bit_uint(hex(registerParams['value']))                  
	            else:
	                builder.add_64bit_int(hex(registerParams['value']))                  
	        #builder.add_32bit_int(int(registerParams['value']))
	    payload = builder.build()
	    client.write_registers(int(registerParams['startregister']) - 1, payload, unit=1)
	elif registerParams['functioncode'] == 'fc15':
	    #builder.add_bits()
	    #payload = builder.build()
	    logging.debug('FC15')       
	    client.write_coils(int(registerParams['startregister']) - offset, registerParams['valuesrequest'], unit=1)
	elif registerParams['functioncode'] == 'fc16':
	    if registerParams['format'] == 'floatformat':
	        logging.debug(float(registerParams['value']))
	        builder.add_32bit_float(float(registerParams['value']))
	    elif registerParams['format'] == 'longformat':
	        builder.add_32bit_int(int(registerParams['value']))
	    payload = builder.build()
	    logging.debug('===PAYLOAD===')
	    logging.debug(payload)
	    client.write_registers(int(registerParams['startregister']) - offset, payload, skip_encode=True, unit=1)
	client.close()


def readDevices():
	if len(globals.DEVICES) == 0:
	    logging.debug('!!!!!   AUCUN EQUIPEMENT EXISTANT    !!!!!!')      
	    return      
	results = {}
	logging.debug(globals.TIMESLEEP)    
	results['data'] = {}
	results['FUNC'] = 'readF'    
	for device in globals.DEVICES:       
		results['data'][device]= {}
		logging.debug(results)
		logging.debug(globals.DEVICES[device]['ipDevice'])
		if(globals.DEVICES[device]['typeDevice'] == 'rtu'):        
		    client = ModbusTcpClient(method='rtu', port=str(globals.DEVICES[device]['portmodbus']), baudrate=int(globals.DEVICES[device]['baudrate']), stopbits = int(globals.DEVICES[device]['stopbits']), parity=str(globals.DEVICES[device]['parity']), bytesize=int(globals.DEVICES[device]['bytesize']))
		elif(globals.DEVICES[device]['typeDevice'] == 'tcp'):
		    client = ModbusTcpClient(globals.DEVICES[device]['ipDevice'])          
		for infoCmd in globals.DEVICES[device]['registerParams']:
			logging.debug(infoCmd)
			offset = int(infoCmd['offset'])          
			results['data'][device][infoCmd['nameCmd']] = []
			if infoCmd['functioncode'] == 'fc01':
			    result = client.read_coils(int(infoCmd['startregister'])-offset, int(infoCmd['nbregister']),  unit=1)
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
			    result = client.read_discrete(int(infoCmd['startregister'])- offset, int(infoCmd['nbregister']),  unit=1)
			elif infoCmd['functioncode'] == 'fc03':
			    result = client.read_holding_registers(int(infoCmd['startregister'])- offset, int(infoCmd['nbregister']),  unit=1)
			elif infoCmd['functioncode'] == 'fc04':
			    result = client.read_input_registers(int(infoCmd['startregister'])- offset, int(infoCmd['nbregister']),  unit=1)
			if infoCmd['functioncode'] == 'fc04' or infoCmd['functioncode'] == 'fc03':
			    if infoCmd['wordorder'] == 'bigword' and infoCmd['byteorder'] == 'bigbyte':
			       decoder = BinaryPayloadDecoder.fromRegisters(result.registers,byteorder=Endian.Big,wordorder=Endian.Big)
			    elif infoCmd['wordorder'] == 'bigword' and infoCmd['byteorder'] == 'littlebyte':
			        decoder = BinaryPayloadDecoder.fromRegisters(result.registers,byteorder=Endian.Little,wordorder=Endian.Big)
			    elif infoCmd['wordorder'] == 'littleword' and infoCmd['byteorder'] == 'bigbyte':
			        decoder = BinaryPayloadDecoder.fromRegisters(result.registers,byteorder=Endian.Big,wordorder=Endian.Little)
			    elif infoCmd['wordorder'] == 'littleword' and infoCmd['byteorder'] == 'littlebyte':
			        logging.debug('LITTLE LITTLE')
			        logging.debug(result.registers)
			        decoder = BinaryPayloadDecoder.fromRegisters(result.registers,byteorder=Endian.Little,wordorder=Endian.Little)
			    logging.debug('TEST ORDER =========')
			    logging.debug(decoder)
			    if infoCmd['format'] == 'floatformat':
			            if int(infoCmd['nbregister']) == 2:                   
			                decoded = {'float': decoder.decode_32bit_float()}
			            elif int(infoCmd['nbregister']) == 1:
			                decoded = {'float': decoder.decode_16bit_float()}
			            elif int(infoCmd['nbregister']) == 4:
			                decoded = {'float': decoder.decode_64bit_float()}                              
			            else :
			                logging.debug('NOMBRE REGISTRE INVALIDE POUR FLOAT : 1,2 ou 4 registres')                             
			                return                             
			            logging.debug(results)
			            for name, value in iteritems(decoded):
			                logging.debug('FLOATFORMAT')
			                logging.debug(value)
			                arrayBits = {'StartRegister': int(infoCmd['startregister']), 'CmdId' : infoCmd['cmdId'], 'value' : value}
			                results['data'][device][infoCmd['nameCmd']].append(arrayBits)
			    elif infoCmd['format'] == 'longformat':
			        if int(infoCmd['isnegatif']) == 0:
			            if int(infoCmd['nbregister']) < 1:
			                decoded = {'int':decoder.decode_8bit_uint()}                              
			            elif int(infoCmd['nbregister']) == 1:
			                decoded = {'int':decoder.decode_16bit_uint()}                              
			            elif int(infoCmd['nbregister']) == 2:
			                decoded = {'int':decoder.decode_32bit_uint()}                              
			            elif int(infoCmd['nbregister']) == 4:
			                decoded = {'int':decoder.decode_64bit_uint()}                              
			        elif int(infoCmd['isnegatif']) == 1:
			            if int(infoCmd['nbregister']) < 1:
			                decoded = {'int':decoder.decode_8bit_int()}                              
			            elif int(infoCmd['nbregister']) == 1:
			                decoded = {'int':decoder.decode_16bit_int()}                              
			            elif int(infoCmd['nbregister']) == 2:
			                decoded = {'int':decoder.decode_32bit_int()}                              
			            elif int(infoCmd['nbregister'])== 4:
			                decoded = {'int':decoder.decode_64bit_int()}
			        logging.debug(decoded)
			        for name, value in iteritems(decoded):
			            logging.debug('INTFORMAT')
			            logging.debug(value)
			            arrayBits = {'StartRegister': int(infoCmd['startregister']), 'CmdId' : infoCmd['cmdId'], 'value' : value}
			            results['data'][device][infoCmd['nameCmd']].append(arrayBits)
			    elif infoCmd['format'] == 'bitsformat':
			        decoded = {'bits':decoder.decode_bits()}
			        logging.debug('=====BITS======')
			        logging.debug(decoded)
			elif infoCmd['functioncode'] == 'fc01':
			    if infoCmd['wordorder'] == 'bigword' and infoCmd['byteorder'] == 'bigbyte':
			        decoder = BinaryPayloadDecoder.fromCoils(payload)
			        logging.debug(decoder)
			    elif infoCmd['wordorder'] == 'bigword' and infoCmd['byteorder'] == 'littlebyte':
			        decoder = BinaryPayloadDecoder.fromCoils(payload)
			        logging.debug(decoder)
			    elif infoCmd['wordorder'] == 'littleword' and infoCmd['byteorder'] == 'bigbyte':
			        decoder = BinaryPayloadDecoder.fromCoils(payload)
			        logging.debug(decoder)
			    elif infoCmd['wordorder'] == 'littleword' and infoCmd['byteorder'] == 'littlebyte':
			        decoder = BinaryPayloadDecoder.fromCoils(payload)
			        logging.debug(decoder)
	time.sleep(int(globals.TIMESLEEP))
	#time.sleep(1)    
	jeedom_com.send_change_immediate(results)    
	client.close()
	#jeedom_com.send_change_immediate(results)    
	#return results



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
			    writeFunc(message['ipDevice'] , message['options'])
			    logging.debug("MESSAGE ENVOYE ACTION WRITE")
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
	#tp = threading.Thread(target=readDevices())    
	try:
		while 1:          
			time.sleep(0.5)
			now = datetime.datetime.utcnow()
			try:
			    read_socket()
			except Exception as e:
			    logging.error("Exception on socket : %s" % str(e))
			try:            
			    if now < (globals.LAST_TIME_READ+datetime.timedelta(milliseconds=globals.TIMESLEEP)):
			        continue
			    else:                    
			        globals.LAST_TIME_READ = now           
			#tp.start()            
			        readDevices()
			except Exception as e:
			    logging.error("Exception on read device : %s" % str(e))              
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
parser.add_argument("--timesleep", help="Time Sleep", type=str)
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
	jeedom_socket = jeedom_socket(port=_socket_port,address=_socket_host)        
	listen()
except Exception as e:
	logging.error('Fatal error : '+str(e))
	shutdown()