#!/usr/bin/env python3
#-*- coding: utf-8 -*-
import socket
import logging
import json
import time
import math


try:
	from jeedom.jeedom import *
except ImportError as error:
	print(error.__class__.__name__ + ": " + str(error))
	print("Error: importing module jeedom.jeedom")
	sys.exit(1)

class ModbusDep():


    def __init__(self,ip, port):
        self._ip = ip
        self._port = port
        self._socket = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)
        self._server=(self._ip, self._port)
