import urllib.request, json, os, pymysql
from datetime import datetime
from config import getMysqlConnection, getURLVelib
from adresseLib import getAdresse, getInsee

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
    nbStationsOuvertesDetectees = 0
    now = datetime.now()

    urlVelib = getURLVelib()
    tmpFileName = 'detailsStations.json'
    urllib.request.urlretrieve(urlVelib, tmpFileName)
    data = json.load(open(tmpFileName))
    for etatStation in data:
        infoStation = etatStation['station']
        codeStation = int(infoStation['code'])
        if codeStation > 100:
            if codeStation not in stations:
                longitude = infoStation['gps']['longitude']
                latitude = infoStation['gps']['latitude']
                if infoStation['state'] != 'Operative' :
                    strDateOuverture = 'NULL'
                else:
                    strDateOuverture = 'CURDATE()'
                requete = mysql.cursor()
                requete.execute('INSERT INTO stations (code, name, longitude, latitude, type, dateOuverture, adresse, insee) VALUES \
                ('+str(codeStation)+', "'+str(infoStation['name'])+'", '+str(longitude)+', '+str(latitude)+', "'+str(infoStation['type'])+'", '+strDateOuverture+', "'+getAdresse(latitude, longitude)+'", '+str(getInsee(codeStation))+')')
                stations.append(codeStation)

            nbBike = int(etatStation['nbBike'])
            nbEbike = int(etatStation['nbEbike'])
            nbFreeEDock = int(etatStation['nbFreeDock'])+int(etatStation['nbFreeEDock'])
            nbEDock = int(etatStation['nbDock'])+int(etatStation['nbEDock'])
            requete = mysql.cursor()
            requete.execute('INSERT INTO status (code, idConso, state, nbBike, nbEBike, nbFreeEDock, nbEDock, nbBikeOverflow, nbEBikeOverflow, maxBikeOverflow) VALUES \
            ('+str(codeStation)+', '+strIdConso+', "'+str(infoStation['state'])+'", '+str(nbBike)+', '+str(nbEbike)+', '+str(nbFreeEDock)+', '+str(nbEDock)+', '+str(etatStation['nbBikeOverflow'])+', '+str(etatStation['nbEBikeOverflow'])+', '+str(etatStation['maxBikeOverflow'])+')')

            #On met à jour la station au besoin
            if codeStation in stationsFutur and nbEDock > 0:
                requete = mysql.cursor()
                requete.execute('UPDATE stations \
                SET dateOuverture = CURDATE() WHERE code = '+str(codeStation))

            #On ajoute a la conso
            nbTotalBike += nbBike
            nbTotalEBike += nbEbike
            nbTotalFreeEDock += nbFreeEDock
            nbTotalEDock += nbEDock
            if infoStation['state'] == "Operative":
                nbStationsOuvertes += 1
            if nbEDock > 0 and nbBike + nbEbike + nbFreeEDock > 0:
                nbStationsOuvertesDetectees += 1
    os.remove(tmpFileName)

    #On insert tout dans le statut
    requete = mysql.cursor()
    requete.execute('UPDATE statusConso SET \
    nbStation = '+str(nbStationsOuvertes)+', \
    nbStationDetecte = '+str(nbStationsOuvertesDetectees)+' ,\
    nbBike = '+str(nbTotalBike)+', \
    nbEbike = '+str(nbTotalEBike)+', \
    nbFreeEDock = '+str(nbTotalFreeEDock)+', \
    nbEDock = '+str(nbTotalEDock)+' \
    WHERE id = '+strIdConso)

    mysql.close()