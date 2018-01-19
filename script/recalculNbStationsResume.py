from resumeLib import debuterCalculResume, debuterCalculResumeConso, debuterCalculResumeOfResume, debuterCalculResumeOfResumeConso
import pymysql, datetime
from config import getMysqlConnection

def recalculNbStationsResume(periode, dateCourante):
    dateConso = dateCourante.strftime("%Y-%m-%d %H:%M:%S")
    finConso = dateCourante + datetime.timedelta(minutes=periode)
    dateConsoFin = finConso.strftime("%Y-%m-%d %H:%M:%S")

    proprietes = ['state', 'nbEDock', 'nbBike', 'nbEBike', 'nbFreeEDock']
    mysql = getMysqlConnection()

    requete = mysql.cursor()
    requete.execute("SELECT s."+', s.'.join(proprietes)+", c.date, c.id \
    FROM status s \
    INNER JOIN `statusConso` c ON c.id = s.idConso \
    WHERE (c.`date` >= '"+dateConso+"' AND c.`date` < '"+dateConsoFin+"') \
    ORDER BY c.id ASC")
    statusConso = requete.fetchall()
    nbStations = 0
    nbStationsDetecte = 0
    nbStationsMax = 0
    nbStationsDetecteMax = 0
    precedenteDate = None
    precedenteConso = None
    for row in statusConso:
        if precedenteDate != None and precedenteDate != row[5]:
            #On met à jour la conso
            requete = mysql.cursor()
            requete.execute('UPDATE statusConso SET nbStation = '+str(nbStations) + ', nbStationDetecte = '+str(nbStationsDetecte) + ' WHERE id = '+str(precedenteConso))
            
            #On calcule le resume
            nbStationsMax = max(nbStationsMax, nbStations)
            nbStationsDetecteMax = max(nbStationsDetecteMax, nbStationsDetecte)
            
            #Et on prépare la suivante
            nbStations = 0
            nbStationsDetecte = 0
        
        if row[0] is None or row[0] == 'Operative':
            nbStations += 1
        if row[1] > 0 and (row[2] + row[3] + row[4]) > 0:
            nbStationsDetecte += 1

        precedenteDate = row[5]
        precedenteConso = row[6]

    if precedenteDate != None:
        #on oublie pas la dernière
        requete = mysql.cursor()
        requete.execute('UPDATE statusConso SET nbStation = '+str(nbStations) + ', nbStationDetecte = '+str(nbStationsDetecte) + ' WHERE id = '+str(precedenteConso))

        #On calcule le resume
        nbStationsMax = max(nbStationsMax, nbStations)
        nbStationsDetecteMax = max(nbStationsDetecteMax, nbStationsDetecte)

    requete = mysql.cursor()
    requete.execute('UPDATE resumeConso SET nbStation = '+str(nbStations) + ', nbStationDetecte = '+str(nbStationsDetecte) + ' WHERE date = "'+str(dateConso)+'" AND duree = '+str(periode))

def recalculNbStationsResumeOfResume(periodeOrigine, periodeFinale, dateCourante):
    dateConso = dateCourante.strftime("%Y-%m-%d %H:%M:%S")
    finConso = dateCourante + datetime.timedelta(minutes=periodeFinale)
    dateConsoFin = finConso.strftime("%Y-%m-%d %H:%M:%S")

    #On récupère les conso
    proprietes = ['nbStation', 'nbStationDetecte']
    
    mysql = getMysqlConnection()
    requete = mysql.cursor()
    requete.execute("SELECT "+', '.join(proprietes)+" FROM `resumeConso` WHERE duree = "+str(periodeOrigine)+" and date >= '"+dateConso+"' and date < '"+dateConsoFin+"' order by date")
    consos = requete.fetchall()
    infosCourantes = {}
    for row in consos:
        for i, cle in enumerate(proprietes):
            if not row[i] is None:
                valeurProp = int(row[i])
                
                if cle in infosCourantes:
                    infosCourantes[cle] = max(infosCourantes[cle], valeurProp)
                else:
                    infosCourantes[cle] = valeurProp

    #Et on met à jour
    if infosCourantes != {} and 'nbStation' in infosCourantes and 'nbStationDetecte' in infosCourantes:
        nbStations = infosCourantes['nbStation']
        nbStationsDetecte = infosCourantes['nbStationDetecte']
        requete = mysql.cursor()
        requete.execute('UPDATE `resumeConso` SET nbStation = '+str(nbStations) + ', nbStationDetecte = '+str(nbStationsDetecte) + ' WHERE \
        date = "'+dateConso+'" and duree = '+str(periodeFinale))

#On commence par avoir la date min et max
mysql = getMysqlConnection()
requete = mysql.cursor()
requete.execute("SELECT MIN(date), MAX(date) FROM `statusConso`")
bornesDate = requete.fetchone()
dateMin = bornesDate[0].replace(microsecond = 0, second = 0)
dateMax = bornesDate[1].replace(microsecond = 0, second = 0)

print("******* Bornes *******")
print("Début = "+str(dateMin))
print("Fin = "+str(dateMax))
print("******* Bornes *******")

#Périodes à calculer
periodes = [5, 15, 60, 360]
periodePrecedente = 0
for periode in periodes:
    dateCourante = dateMin - datetime.timedelta(minutes=((dateMin.hour * 60 + dateMin.minute) % periode))
    print("-> Début période "+ str(periode))
    while dateCourante + datetime.timedelta(minutes=periode) <= dateMax:
        print("Période " + str(periode)+'; date = '+str(dateCourante))
        if periode == 5:
            recalculNbStationsResume(periode, dateCourante)
        else:
            recalculNbStationsResumeOfResume(periodePrecedente, periode, dateCourante)
        dateCourante = dateCourante + datetime.timedelta(minutes=periode)
    periodePrecedente = periode
    print("-> Fin période "+ str(periode))