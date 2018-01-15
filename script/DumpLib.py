import sqlite3, pymysql, datetime, os
from config import getMysqlConnection

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
    c.execute('''
    CREATE TABLE `stations` (
    `code` int(11) NOT NULL,
    `name` varchar(256) NOT NULL,
    `latitude` decimal(16,14) NOT NULL,
    `longitude` decimal(16,14) NOT NULL,
    `type` varchar(256) NOT NULL,
    `dateOuverture` date NOT NULL,
    `adresse` text
    );
    ''')
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
    `nbBike` int(11) DEFAULT NULL,
    `nbEbike` int(11) DEFAULT NULL,
    `nbFreeEDock` int(11) DEFAULT NULL,
    `nbEDock` int(11) DEFAULT NULL
    );
    ''')
    conn.commit()

    #On va récupérer chaque donnée de chaque table
    c = conn.cursor()
    requete = mysql.cursor()
    requete.execute('''
    SELECT code, name, latitude, longitude, type, dateOuverture, adresse
    FROM stations
    ''')
    stations = requete.fetchall()
    for station in stations:
        c.execute('INSERT INTO stations (code, name, latitude, longitude, type, dateOuverture, adresse) VALUES \
        ('+str(station[0])+', "'+str(station[1])+'", '+str(station[2])+', '+str(station[3])+', "'+str(station[4])+'", "'+str(station[5])+'", "'+str(station[6])+'")')
    conn.commit()

    c = conn.cursor()
    requete = mysql.cursor()
    requete.execute('SELECT id, date, nbStation, nbBike, nbEbike, nbFreeEDock, nbEDock \
    FROM statusConso \
    WHERE date >= "'+dateDebutStr+'" AND date < "'+dateFinStr+'"')
    stations = requete.fetchall()
    for station in stations:
        values = []
        for cell in station:
            if cell is None:
                values.append('NULL')
            else:
                values.append(str(cell))
        c.execute('INSERT INTO statusConso (id, date, nbStation, nbBike, nbEbike, nbFreeEDock, nbEDock) VALUES \
        ('+values[0]+', "'+values[1]+'", '+values[2]+', '+values[3]+', '+values[4]+', '+values[5]+', '+values[6]+')')
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
            if cell is None:
                values.append('NULL')
            else:
                values.append(str(cell))
        c.execute('INSERT INTO status (id, code, idConso, state, nbBike, nbEBike, nbFreeEDock, nbEDock, nbBikeOverflow, nbEBikeOverflow, maxBikeOverflow) VALUES \
        ("'+'", "'.join(values)+'")')
    conn.commit()

    #Et on ferme le fichier
    c.close()