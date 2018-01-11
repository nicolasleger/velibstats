from adresseLib import getAdresse
import pymysql
from config import getMysqlConnection

mysql = getMysqlConnection()
requete = mysql.cursor()
requete.execute('SELECT code, latitude, longitude FROM stations')
stations = requete.fetchall()
for station in stations:
    adresse = getAdresse(station[1], station[2])
    requete = mysql.cursor()
    requete.execute('UPDATE stations SET adresse = "'+adresse+'" WHERE code = '+str(station[0]))