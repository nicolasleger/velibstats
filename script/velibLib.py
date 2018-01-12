import urllib.request, json, os, pymysql
from datetime import datetime
from config import getMysqlConnection, getURLVelib
from adresseLib import getAdresse

def getAllStation():
    mysql = getMysqlConnection()

    #On récupère la liste des stations déjà en base
    requete = mysql.cursor()
    requete.execute('SELECT code FROM stations')
    rep = requete.fetchall()
    stations = []
    for code in rep:
        stations.append(code[0])

    nbTotalBike = 0
    nbTotalEBike = 0
    nbTotalFreeEDock = 0

    #On créer une conso pour avoir son id
    requete = mysql.cursor()
    requete.execute('INSERT INTO statusConso (date) VALUES (NOW())')
    idConso = requete.lastrowid
    strIdConso = str(idConso)
    nbStationsOuvertes = 0
    now = datetime.now()

    urlVelib = getURLVelib()
    tmpFileName = 'detailsStations.json'
    urllib.request.urlretrieve(urlVelib, tmpFileName)
    data = json.load(open(tmpFileName))
    for etatStation in data:
        infoStation = etatStation['station']
        codeStation = int(infoStation['code'])
        if codeStation not in stations:
            longitude = infoStation['gps']['longitude']
            latitude = infoStation['gps']['latitude']
            if infoStation['state'] != 'Operative' and 'dueDate' in infoStation and infoStation['dueDate'] != None:
                dateOuverture = datetime.fromtimestamp(infoStation['dueDate'])
                strDateOuverture = '"'+dateOuverture.strftime("%Y-%m-%d")+'"'
            else:
                strDateOuverture = 'CURDATE()'
            requete = mysql.cursor()
            requete.execute('INSERT INTO stations (code, name, longitude, latitude, type, dateOuverture, adresse) VALUES \
            ('+str(codeStation)+', "'+str(infoStation['name'])+'", '+str(longitude)+', '+str(latitude)+', "'+str(infoStation['type'])+'", '+strDateOuverture+', "'+getAdresse(latitude, longitude)+'")')
            stations.append(codeStation)
        
        #Et on prend les infos de l'état actuel de la station
        ok = True
        if infoStation['state'] == 'Operative':
            #Exception pour le moment sur cette station
            if codeStation == 10006:
                ok = False

        if ok:
            requete = mysql.cursor()
            requete.execute('INSERT INTO status (code, idConso, state, nbBike, nbEBike, nbFreeEDock, nbEDock, nbBikeOverflow, nbEBikeOverflow, maxBikeOverflow) VALUES \
            ('+str(codeStation)+', '+strIdConso+', "'+str(infoStation['state'])+'", '+str(etatStation['nbBike'])+', '+str(etatStation['nbEbike'])+', '+str((etatStation['nbFreeDock']+etatStation['nbFreeEDock']))+', '+str((etatStation['nbDock']+etatStation['nbEDock']))+', '+str(etatStation['nbBikeOverflow'])+', '+str(etatStation['nbEBikeOverflow'])+', '+str(etatStation['maxBikeOverflow'])+')')

            #On ajoute a la conso
            nbTotalBike += int(etatStation['nbBike'])
            nbTotalEBike += int(etatStation['nbEbike'])
            nbTotalFreeEDock += int(etatStation['nbFreeEDock'])
            if infoStation['state'] == "Operative":
                nbStationsOuvertes += 1
    os.remove(tmpFileName)

    #On insert tout dans le statut
    requete = mysql.cursor()
    requete.execute('UPDATE statusConso SET nbStation = '+str(nbStationsOuvertes)+', \
    nbBike = '+str(nbTotalBike)+', \
    nbEbike = '+str(nbTotalEBike)+', \
    nbFreeEDock = '+str(nbTotalFreeEDock)+' \
    WHERE id = '+strIdConso)

    mysql.close()