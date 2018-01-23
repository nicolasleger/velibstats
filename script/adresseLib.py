import urllib.request, json, os
import pymysql
from config import getMysqlConnection

def getAdresse(lat, lon):
    urlBAN = 'https://api-adresse.data.gouv.fr/reverse/?lat='+str(lat)+'&lon='+str(lon)
    tmpFileName = 'adresse.json'
    urllib.request.urlretrieve(urlBAN, tmpFileName)

    data = json.load(open(tmpFileName))

    adresse = data['features'][0]['properties']['label']

    os.remove(tmpFileName)
    return adresse

def getInsee(codeStation):
    mysql = getMysqlConnection()
    requete = mysql.cursor()
    requete.execute('SELECT insee FROM tranche WHERE debut <= '+str(codeStation)+' AND fin >= '+str(codeStation))
    tranche = requete.fetchone()
    return tranche[0]