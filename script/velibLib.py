import urllib.request, json, os, pymysql
from datetime import datetime
from config import getMysqlConnection, getURLVelib
from adresseLib import getAdresse

def getAllStation():
    mysql = getMysqlConnection()

    #On récupère la liste des stations déjà en base
    requete = mysql.cursor()
    requete.execute('SELECT code, dateOuverture FROM stations')
    rep = requete.fetchall()
    aujourdhui = datetime.today().date()
    stations = []
    stationsFutur = []
    for row in rep:
        stations.append(row[0])
        if(row[1] is None or row[1] > aujourdhui):
            stationsFutur.append(row[0])

    nbTotalBike = 0
    nbTotalEBike = 0
    nbTotalFreeEDock = 0
    nbTotalEDock = 0

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
            if infoStation['state'] != 'Operative' :
                strDateOuverture = 'NULL'
            else:
                strDateOuverture = 'CURDATE()'
            requete = mysql.cursor()
            requete.execute('INSERT INTO stations (code, name, longitude, latitude, type, dateOuverture, adresse) VALUES \
            ('+str(codeStation)+', "'+str(infoStation['name'])+'", '+str(longitude)+', '+str(latitude)+', "'+str(infoStation['type'])+'", '+strDateOuverture+', "'+getAdresse(latitude, longitude)+'")')
            stations.append(codeStation)
        
        #Et on prend les infos de l'état actuel de la station
        ok = True
        if infoStation['state'] == 'Work in progress' and codeStation == 10006:
            #Exception pour le moment sur cette station
            ok = False

        if ok:
            nbEDock = etatStation['nbDock']+etatStation['nbEDock']
            requete = mysql.cursor()
            requete.execute('INSERT INTO status (code, idConso, state, nbBike, nbEBike, nbFreeEDock, nbEDock, nbBikeOverflow, nbEBikeOverflow, maxBikeOverflow) VALUES \
            ('+str(codeStation)+', '+strIdConso+', "'+str(infoStation['state'])+'", '+str(etatStation['nbBike'])+', '+str(etatStation['nbEbike'])+', '+str((etatStation['nbFreeDock']+etatStation['nbFreeEDock']))+', '+str(nbEDock)+', '+str(etatStation['nbBikeOverflow'])+', '+str(etatStation['nbEBikeOverflow'])+', '+str(etatStation['maxBikeOverflow'])+')')

            #On met à jour la station au besoin
            if codeStation in stationsFutur and nbEDock > 0:
                requete = mysql.cursor()
                requete.execute('UPDATE stations \
                SET dateOuverture = CURDATE() WHERE code = '+str(codeStation))

            #On ajoute a la conso
            nbTotalBike += int(etatStation['nbBike'])
            nbTotalEBike += int(etatStation['nbEbike'])
            nbTotalFreeEDock += int(etatStation['nbFreeDock'])+int(etatStation['nbFreeEDock'])
            nbTotalEDock += int(etatStation['nbDock'])+int(etatStation['nbEDock'])
            if infoStation['state'] == "Operative":
                nbStationsOuvertes += 1
    os.remove(tmpFileName)

    #On insert tout dans le statut
    requete = mysql.cursor()
    requete.execute('UPDATE statusConso SET nbStation = '+str(nbStationsOuvertes)+', \
    nbBike = '+str(nbTotalBike)+', \
    nbEbike = '+str(nbTotalEBike)+', \
    nbFreeEDock = '+str(nbTotalFreeEDock)+', \
    nbEDock = '+str(nbTotalEDock)+' \
    WHERE id = '+strIdConso)

    mysql.close()