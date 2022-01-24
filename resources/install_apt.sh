PROGRESS_FILE=/tmp/jeedom/modbus/dependency
if [ ! -z $1 ]; then
	PROGRESS_FILE=$1
fi
touch ${PROGRESS_FILE}
echo 0 > ${PROGRESS_FILE}
echo "********************************************************"
echo "*             Installation des dépendances             *"
echo "********************************************************"
echo $(date)
echo 5 > ${PROGRESS_FILE}
sudo apt-get clean
echo 10 > ${PROGRESS_FILE}
sudo apt-get update  -y -q
echo 20 > ${PROGRESS_FILE}

echo "*****************************"
echo "Install modules using apt-get"
echo "*****************************"

sudo apt install -y python3-pip python3-dev
echo 30 > ${PROGRESS_FILE}
sudo apt-get install -y python3-setuptools
sudo python3 -m pip install pyudev
sudo python3 -m pip install pyserial
sudo python3 -m pip install requests
sudo python3 -m pip install pymodbus
sudo python3 -m pip install bitstring
sudo python3 -m pip install minimalmodbus
echo 100 > ${PROGRESS_FILE}
echo "********************************************************"
echo "*             Installation terminée                    *"
echo "********************************************************"
rm ${PROGRESS_FILE}