import sqlite3, pymysql, datetime, os
from config import getMysqlConnection

def creerStationTable(c):
    c.execute('''
    CREATE TABLE `stations` (
    `code` int(11) NOT NULL,
    `name` varchar(256) NOT NULL,
    `latitude` decimal(16,14) NOT NULL,
    `longitude` decimal(16,14) NOT NULL,
    `type` varchar(256) NOT NULL,
    `dateOuverture` date DEFAULT NULL,
    `adresse` text
    );
    ''')

def val(valeur):
    if valeur is None:
        return 'NULL'
    return '"'+str(valeur)+'"'

def creerStationData(conn, mysql):
    c = conn.cursor()
    requete = mysql.cursor()
    requete.execute('''
    SELECT code, name, latitude, longitude, type, dateOuverture, adresse
    FROM stations
    ''')
    stations = requete.fetchall()
    for station in stations:
        c.execute('INSERT INTO stations (code, name, latitude, longitude, type, dateOuverture, adresse) VALUES \
        ('+str(station[0])+', "'+str(station[1])+'", '+str(station[2])+', '+str(station[3])+', "'+str(station[4])+'", '+val(station[5])+', "'+str(station[6])+'")')
    conn.commit()

def creerDumpData(dateDebut):
    dateDebut = dateDebut.replace(microsecond = 0, second = 0, minute=0, hour=0)
    dateDebutStr = dateDebut.strftime("%Y-%m-%d %H:%M:%S")
    dateDebutNomFichier = dateDebut.strftime("%Y-%m-%d")
    dateFin = dateDebut + datetime.timedelta(days=1)
    dateFinStr = dateFin.strftime("%Y-%m-%d %H:%M:%S")

    nomFichier = '../dump/'+dateDebutNomFichier + '-data.db'
    conn = sqlite3.connect(nomFichier)

    mysql = getMysqlConnection()

    #Creation des tables
    c = conn.cursor()
    creerStationTable(c)
    c.execute('''
    CREATE TABLE `status` (
    `id` int(11) NOT NULL,
    `code` int(11) NOT NULL,
    `idConso` int(11) NOT NULL,
    `state` varchar(32) NOT NULL,
    `nbBike` int(3) NOT NULL,
    `nbEBike` int(3) NOT NULL,
    `nbFreeEDock` int(3) NOT NULL,
    `nbEDock` int(3) NOT NULL,
    `nbBikeOverflow` int(3) NOT NULL,
    `nbEBikeOverflow` int(3) NOT NULL,
    `maxBikeOverflow` int(3) NOT NULL
    );
    ''')
    c.execute('''
    CREATE TABLE `statusConso` (
    `id` int(11) NOT NULL,
    `date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `nbStation` int(11) DEFAULT NULL,
    `nbStationDetecte` int(11) DEFAULT NULL,
    `nbBike` int(11) DEFAULT NULL,
    `nbEbike` int(11) DEFAULT NULL,
    `nbFreeEDock` int(11) DEFAULT NULL,
    `nbEDock` int(11) DEFAULT NULL
    );
    ''')
    conn.commit()

    #On va récupérer chaque donnée de chaque table
    creerStationData(conn, mysql)

    c = conn.cursor()
    requete = mysql.cursor()
    requete.execute('SELECT id, date, nbStation, nbStationDetecte, nbBike, nbEbike, nbFreeEDock, nbEDock \
    FROM statusConso \
    WHERE date >= "'+dateDebutStr+'" AND date < "'+dateFinStr+'"')
    stations = requete.fetchall()
    for station in stations:
        values = []
        for cell in station:
            values.append(val(cell))
        c.execute('INSERT INTO statusConso (id, date, nbStation, nbStationDetecte, nbBike, nbEbike, nbFreeEDock, nbEDock) VALUES \
        ('+values[0]+', '+values[1]+', '+values[2]+', '+values[3]+', '+values[4]+', '+values[5]+', '+values[6]+', '+values[7]+')')
    conn.commit()

    c = conn.cursor()
    requete = mysql.cursor()
    requete.execute('SELECT s.id, s.code, s.idConso, s.state, s.nbBike, s.nbEBike, s.nbFreeEDock, s.nbEDock, s.nbBikeOverflow, s.nbEBikeOverflow, s.maxBikeOverflow \
    FROM status s \
    INNER JOIN `statusConso` c ON c.id = s.idConso \
    WHERE (c.`date` >= "'+dateDebutStr+'" AND c.`date` < "'+dateFinStr+'")')
    statuts = requete.fetchall()
    for statut in statuts:
        values = []
        for cell in statut:
            values.append(val(cell))
        c.execute('INSERT INTO status (id, code, idConso, state, nbBike, nbEBike, nbFreeEDock, nbEDock, nbBikeOverflow, nbEBikeOverflow, maxBikeOverflow) VALUES \
        ('+', '.join(values)+')')
    conn.commit()

    #Et on ferme le fichier
    c.close()

def creerDumpConso(dateDebut):
    dateDebut = dateDebut.replace(microsecond = 0, second = 0, minute=0, hour=0)
    dateDebutStr = dateDebut.strftime("%Y-%m-%d %H:%M:%S")
    dateDebutNomFichier = dateDebut.strftime("%Y-%m-%d")
    dateFin = dateDebut + datetime.timedelta(days=1)
    dateFinStr = dateFin.strftime("%Y-%m-%d %H:%M:%S")

    nomFichier = '../dump/'+dateDebutNomFichier + '-conso.db'
    conn = sqlite3.connect(nomFichier)

    mysql = getMysqlConnection()

    #Creation des tables
    c = conn.cursor()
    creerStationTable(c)
    c.execute('''
    CREATE TABLE `resumeConso` (
  `id` int(11) NOT NULL,
  `date` datetime NOT NULL,
  `duree` int(4) NOT NULL,
  `nbStation` int(11) NOT NULL,
  `nbStationDetecte` int(11) DEFAULT NULL,
  `nbBikeMin` int(11) NOT NULL,
  `nbBikeMax` int(11) NOT NULL,
  `nbBikeMoyenne` int(11) NOT NULL,
  `nbEBikeMin` int(11) NOT NULL,
  `nbEBikeMax` int(11) NOT NULL,
  `nbEBikeMoyenne` int(11) NOT NULL,
  `nbFreeEDockMin` int(11) NOT NULL,
  `nbFreeEDockMax` int(11) NOT NULL,
  `nbFreeEDockMoyenne` int(11) NOT NULL,
  `nbEDock` int(11) NOT NULL
);
    ''')
    c.execute('''
    CREATE TABLE `resumeStatus` (
  `id` int(11) NOT NULL,
  `code` int(11) NOT NULL,
  `date` datetime NOT NULL,
  `duree` int(4) NOT NULL,
  `nbBikeMin` int(3) NOT NULL,
  `nbBikeMax` int(3) NOT NULL,
  `nbBikeMoyenne` decimal(5,2) NOT NULL,
  `nbBikePris` int(3) NOT NULL,
  `nbBikeRendu` int(3) NOT NULL,
  `nbEBikeMin` int(3) NOT NULL,
  `nbEBikeMax` int(3) NOT NULL,
  `nbEBikeMoyenne` decimal(5,2) NOT NULL,
  `nbEBikePris` int(3) NOT NULL,
  `nbEBikeRendu` int(3) NOT NULL,
  `nbFreeEDockMin` int(3) NOT NULL,
  `nbFreeEDockMax` int(3) NOT NULL,
  `nbFreeEDockMoyenne` decimal(5,2) NOT NULL,
  `nbEDock` int(3) NOT NULL,
  `nbBikeOverflowMin` int(3) NOT NULL,
  `nbBikeOverflowMax` int(3) NOT NULL,
  `nbBikeOverflowMoyenne` decimal(5,2) NOT NULL,
  `nbEBikeOverflowMin` int(3) NOT NULL,
  `nbEBikeOverflowMax` int(3) NOT NULL,
  `nbEBikeOverflowMoyenne` decimal(5,2) NOT NULL,
  `maxBikeOverflow` int(3) NOT NULL
);
    ''')
    conn.commit()

    #On va récupérer chaque donnée de chaque table
    creerStationData(conn, mysql)
    c = conn.cursor()
    requete = mysql.cursor()
    requete.execute('SELECT id, date, duree, nbStation, nbStationDetecte, nbBikeMin, nbBikeMax, nbBikeMoyenne, nbEBikeMin, nbEBikeMax, nbEBikeMoyenne, nbFreeEDockMin, nbFreeEDockMax, nbFreeEDockMoyenne, nbEDock \
    FROM resumeConso \
    WHERE date >= "'+dateDebutStr+'" AND date < "'+dateFinStr+'"')
    stations = requete.fetchall()
    for station in stations:
        values = []
        for cell in station:
            values.append(val(cell))
        c.execute('INSERT INTO resumeConso (id, date, duree, nbStation, nbStationDetecte, nbBikeMin, nbBikeMax, nbBikeMoyenne, nbEBikeMin, nbEBikeMax, nbEBikeMoyenne, nbFreeEDockMin, nbFreeEDockMax, nbFreeEDockMoyenne, nbEDock) VALUES \
        ('+', '.join(values)+')')
    conn.commit()

    c = conn.cursor()
    requete = mysql.cursor()
    requete.execute('SELECT	id, code, date, duree, nbBikeMin, nbBikeMax, nbBikeMoyenne, nbBikePris, nbBikeRendu, nbEBikeMin, nbEBikeMax, nbEBikeMoyenne, nbEBikePris, nbEBikeRendu, nbFreeEDockMin, nbFreeEDockMax, nbFreeEDockMoyenne, nbEDock, nbBikeOverflowMin, nbBikeOverflowMax, nbBikeOverflowMoyenne, nbEBikeOverflowMin, nbEBikeOverflowMax, nbEBikeOverflowMoyenne, maxBikeOverflow \
    FROM resumeStatus \
    WHERE date >= "'+dateDebutStr+'" AND date < "'+dateFinStr+'"')
    stations = requete.fetchall()
    for station in stations:
        values = []
        for cell in station:
            values.append(val(cell))
        c.execute('INSERT INTO resumeStatus (id, code, date, duree, nbBikeMin, nbBikeMax, nbBikeMoyenne, nbBikePris, nbBikeRendu, nbEBikeMin, nbEBikeMax, nbEBikeMoyenne, nbEBikePris, nbEBikeRendu, nbFreeEDockMin, nbFreeEDockMax, nbFreeEDockMoyenne, nbEDock, nbBikeOverflowMin, nbBikeOverflowMax, nbBikeOverflowMoyenne, nbEBikeOverflowMin, nbEBikeOverflowMax, nbEBikeOverflowMoyenne, maxBikeOverflow) VALUES \
        ('+', '.join(values)+')')
    conn.commit()

    #Et on ferme le fichier
    c.close()