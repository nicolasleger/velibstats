import pymysql
from config import getMysqlConnection

def nettoyerInstantanne(date):
    dateStr = date.strftime("%Y-%m-%d %H:%M:%S")
    mysql = getMysqlConnection()
    requete = mysql.cursor()
    requete.execute('DELETE FROM status WHERE idConso IN ( \
    SELECT id FROM statusConso \
    WHERE date < "'+dateStr+'")')
    requete = mysql.cursor()
    requete.execute('DELETE FROM statusConso WHERE date < "'+dateStr+'"')

def nettoyerConso(date, filtre):
    dateStr = date.strftime("%Y-%m-%d %H:%M:%S")
    mysql = getMysqlConnection()
    requete = mysql.cursor()
    requete.execute('DELETE FROM resumeStatus WHERE date < "'+dateStr+'" AND ('+filtre+')')
    requete = mysql.cursor()
    requete.execute('DELETE FROM resumeConso WHERE date < "'+dateStr+'" AND ('+filtre+')')

def optimiserBDD():
    mysql = getMysqlConnection()
    requete = mysql.cursor()
    requete.execute('OPTIMIZE TABLE resumeConso, resumeStatus, status, statusConso')