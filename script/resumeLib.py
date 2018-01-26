import pymysql, datetime
from config import getMysqlConnection

def calculerResume(dateConsoDT, dureeConso):
    dateConso = dateConsoDT.strftime("%Y-%m-%d %H:%M:%S")
    minuteAvant = dateConsoDT - datetime.timedelta(minutes=1)
    dateConsoAvant = minuteAvant.strftime("%Y-%m-%d %H:%M:%S")
    finConso = dateConsoDT + datetime.timedelta(minutes=dureeConso)
    dateConsoFin = finConso.strftime("%Y-%m-%d %H:%M:%S")

    proprietes = ['nbBike', 'nbEBike', 'nbFreeEDock', 'nbEDock', 'nbBikeOverflow', 'nbEBikeOverflow', 'maxBikeOverflow']
    mysql = getMysqlConnection()
    #On récupère la minute d'avant pour les bases de conso
    requete = mysql.cursor()
    requete.execute("SELECT s.code, s."+', s.'.join(proprietes)+" \
    FROM status s \
    INNER JOIN `statusConso` c ON c.id = s.idConso \
    WHERE (c.`date` >= '"+dateConsoAvant+"' AND c.`date` < '"+dateConso+"') \
    ORDER BY c.id DESC")
    statusConsoPrecedent = requete.fetchall()
    precedents = {}
    for row in statusConsoPrecedent:
        codeStation = int(row[0])
        for i, cle in enumerate(proprietes):
            if cle in ['nbBike', 'nbEBike']:
                valeurProp = int(row[i+1])
                if not codeStation in precedents:
                    precedents[codeStation] = {}
                precedents[codeStation][cle] = valeurProp

    requete = mysql.cursor()
    requete.execute("SELECT s.code, s."+', s.'.join(proprietes)+" \
    FROM status s \
    INNER JOIN `statusConso` c ON c.id = s.idConso \
    WHERE (c.`date` >= '"+dateConso+"' AND c.`date` < '"+dateConsoFin+"') \
    ORDER BY c.id ASC")
    statusConso = requete.fetchall()
    bikeList = {}
    for row in statusConso:
        codeStation = int(row[0])
        for i, cle in enumerate(proprietes):
            valeurProp = int(row[i+1])
            if cle in ['nbEDock', 'maxBikeOverflow']:
                bikeList[codeStation][cle] = valeurProp
            else:
                if codeStation not in bikeList:
                    bikeList[codeStation] = {cle: {'data': [], 'min': valeurProp, 'max': valeurProp}}
                    if cle in ['nbBike', 'nbEBike']:
                        bikeList[codeStation][cle]['pris'] = 0
                        bikeList[codeStation][cle]['remis'] = 0
                elif cle not in bikeList[codeStation]:
                    bikeList[codeStation][cle] = {'data': [], 'min': valeurProp, 'max': valeurProp}
                    if cle in ['nbBike', 'nbEBike']:
                        bikeList[codeStation][cle]['pris'] = 0
                        bikeList[codeStation][cle]['remis'] = 0
                else:
                    bikeList[codeStation][cle]['max'] = max(bikeList[codeStation][cle]['max'], valeurProp)
                    bikeList[codeStation][cle]['min'] = min(bikeList[codeStation][cle]['min'], valeurProp)
                
                if cle in ['nbBike', 'nbEBike']:
                    if codeStation not in precedents:
                        precedents[codeStation] = {}

                    if cle in precedents[codeStation]:
                        delta = valeurProp - precedents[codeStation][cle]
                        if delta > 0:
                            bikeList[codeStation][cle]['remis'] += delta  
                        if delta < 0:
                            bikeList[codeStation][cle]['pris'] -= delta
                    precedents[codeStation][cle] = valeurProp
            
                bikeList[codeStation][cle]['data'].append(valeurProp)

    for codeStation in bikeList:
        for cle in bikeList[codeStation]:
            info = bikeList[codeStation][cle]
            if type(info) is dict:
                data = info['data']
                bikeList[codeStation][cle]['moyenne'] = sum(data) / max(len(data), 1)

    for codeStation in bikeList:
        valeurs = bikeList[codeStation]
        requete = mysql.cursor()
        requete.execute('INSERT INTO `resumeStatus` (`id`, `code`, `date`, `duree`, `nbBikeMin`, `nbBikeMax`, `nbBikeMoyenne`, `nbBikePris`, `nbBikeRendu`, `nbEBikeMin`, `nbEBikeMax`, `nbEBikeMoyenne`, `nbEBikePris`, `nbEBikeRendu`, `nbFreeEDockMin`, `nbFreeEDockMax`, `nbFreeEDockMoyenne`, `nbEDock`, `nbBikeOverflowMin`, `nbBikeOverflowMax`, `nbBikeOverflowMoyenne`, `nbEBikeOverflowMin`, `nbEBikeOverflowMax`, `nbEBikeOverflowMoyenne`, `maxBikeOverflow`) VALUES \
        (NULL, '+str(codeStation)+', "'+dateConso+'", '+str(dureeConso)+', '+str(valeurs['nbBike']['min'])+', '+str(valeurs['nbBike']['max'])+', '+str(valeurs['nbBike']['moyenne'])+', '+str(valeurs['nbBike']['pris'])+', '+str(valeurs['nbBike']['remis'])+', '+str(valeurs['nbEBike']['min'])+', '+str(valeurs['nbEBike']['max'])+', '+str(valeurs['nbEBike']['moyenne'])+', '+str(valeurs['nbEBike']['pris'])+', '+str(valeurs['nbEBike']['remis'])+', '+str(valeurs['nbFreeEDock']['min'])+', '+str(valeurs['nbFreeEDock']['max'])+', '+str(valeurs['nbFreeEDock']['moyenne'])+', '+str(valeurs['nbEDock'])+', '+str(valeurs['nbBikeOverflow']['min'])+', '+str(valeurs['nbBikeOverflow']['max'])+', '+str(valeurs['nbBikeOverflow']['moyenne'])+', '+str(valeurs['nbEBikeOverflow']['min'])+', '+str(valeurs['nbEBikeOverflow']['max'])+', '+str(valeurs['nbEBikeOverflow']['moyenne'])+', '+str(valeurs['maxBikeOverflow'])+')')

def calculerResumeOfResume(dateConsoDT, dureeConsoOrigine, dureeConsoFinale):
    dateConso = dateConsoDT.strftime("%Y-%m-%d %H:%M:%S")
    finConso = dateConsoDT + datetime.timedelta(minutes=dureeConsoFinale)
    dateConsoFin = finConso.strftime("%Y-%m-%d %H:%M:%S")

    #On récupère les conso
    proprietes = ['nbBikeMin', 'nbBikeMax', 'nbBikeMoyenne', 'nbBikePris', 'nbBikeRendu', 'nbEBikeMin', 'nbEBikeMax', 'nbEBikeMoyenne', 'nbEBikePris', 'nbEBikeRendu', 'nbFreeEDockMin', 'nbFreeEDockMax', 'nbFreeEDockMoyenne', 'nbEDock', 'nbBikeOverflowMin', 'nbBikeOverflowMax', 'nbBikeOverflowMoyenne', 'nbEBikeOverflowMin', 'nbEBikeOverflowMax', 'nbEBikeOverflowMoyenne', 'maxBikeOverflow']
    
    mysql = getMysqlConnection()
    requete = mysql.cursor()
    requete.execute("SELECT code, "+', '.join(proprietes)+" FROM `resumeStatus` WHERE duree = "+str(dureeConsoOrigine)+" and date >= '"+dateConso+"' and date < '"+dateConsoFin+"' order by code")
    consos = requete.fetchall()
    precedentCode = None
    infosCourantes = {}
    for row in consos:
        codeStation = int(row[0])

        if precedentCode != None and precedentCode != codeStation: #On change de station
            #On enregistre la conso
            enregistrerConso(mysql, proprietes, infosCourantes, precedentCode, dateConso, dureeConsoFinale)
            infosCourantes = {}

        for i, cle in enumerate(proprietes):
            if cle[-7:] == 'Moyenne':
                valeurProp = float(row[i+1])
            else:
                valeurProp = int(row[i+1])
            
            if cle in infosCourantes:
                if cle[-7:] == 'Moyenne':
                    infosCourantes[cle].append(valeurProp)
                elif cle[-3:] == 'Min':
                    infosCourantes[cle] = min(infosCourantes[cle], valeurProp)
                elif cle[-3:] == 'Max' or cle == 'maxBikeOverflow' or cle == 'nbEDock':
                    infosCourantes[cle] = max(infosCourantes[cle], valeurProp) 
                else:
                    infosCourantes[cle] += valeurProp 
            else:
                if cle[-7:] == 'Moyenne':
                    infosCourantes[cle] = [valeurProp]
                else:
                    infosCourantes[cle] = valeurProp

        precedentCode = codeStation
    #Et on oublie pas le dernier !
    enregistrerConso(mysql, proprietes, infosCourantes, precedentCode, dateConso, dureeConsoFinale)

def enregistrerConso(mysql, proprietes, infosCourantes, codeStation, dateConso, dureeConso):
    #On traite les moyennes et on prépare la requête
    if infosCourantes != {}:
        strValeurs = ""
        for i, cle in enumerate(proprietes):
            if cle[-7:] == 'Moyenne':
                data = infosCourantes[cle]
                moyenne = sum(data) / max(len(data), 1)
                infosCourantes[cle] = moyenne
            if i != 0:
                strValeurs += ', '
            strValeurs += str(infosCourantes[cle])
        
        requete = mysql.cursor()
        requete.execute('INSERT INTO `resumeStatus` (`id`, `code`, `date`, `duree`, '+', '.join(proprietes)+') VALUES \
        (NULL, '+str(codeStation)+', "'+dateConso+'", '+str(dureeConso)+', '+strValeurs+')')

def debuterCalculResume(periode, dateCourante = datetime.datetime.now()):
    dateConsoDT = dateCourante - datetime.timedelta(minutes=periode)
    debutPeriodeMinute = dateConsoDT.minute % periode
    dateConsoDT = dateConsoDT.replace(microsecond = 0, second = 0, minute=dateConsoDT.minute - debutPeriodeMinute)
    calculerResume(dateConsoDT, periode)

def debuterCalculResumeOfResume(periodeOrigine, periodeFinale, dateCourante = datetime.datetime.now()):
    dateConsoDT = dateCourante - datetime.timedelta(minutes=periodeFinale)
    debutPeriodeMinute = (dateConsoDT.hour * 60 + dateConsoDT.minute) % periodeFinale
    dateConsoDT = dateConsoDT.replace(microsecond = 0, second = 0) - datetime.timedelta(minutes=debutPeriodeMinute)
    calculerResumeOfResume(dateConsoDT, periodeOrigine, periodeFinale)

#StatutConso
def calculerResumeConso(dateConsoDT, dureeConso):
    dateConso = dateConsoDT.strftime("%Y-%m-%d %H:%M:%S")
    minuteAvant = dateConsoDT - datetime.timedelta(minutes=1)
    dateConsoAvant = minuteAvant.strftime("%Y-%m-%d %H:%M:%S")
    finConso = dateConsoDT + datetime.timedelta(minutes=dureeConso)
    dateConsoFin = finConso.strftime("%Y-%m-%d %H:%M:%S")

    proprietes = ['nbStation', 'nbStationDetecte', 'nbBike', 'nbEBike', 'nbFreeEDock', 'nbEDock']
    mysql = getMysqlConnection()

    requete = mysql.cursor()
    requete.execute("SELECT "+', '.join(proprietes)+" \
    FROM statusConso \
    WHERE (`date` >= '"+dateConso+"' AND `date` < '"+dateConsoFin+"') \
    ORDER BY id ASC")
    statusConso = requete.fetchall()
    bikeList = {}
    for row in statusConso:
        for i, cle in enumerate(proprietes):
            if not row[i] is None:
                valeurProp = int(row[i])
                if cle in ['nbStation', 'nbStationDetecte', 'nbEDock']:
                    if not cle in bikeList:
                        bikeList[cle] = valeurProp
                    else:
                        bikeList[cle] = max(bikeList[cle], valeurProp)
                else:
                    if not cle in bikeList:
                        bikeList[cle] = {'data': [], 'min': valeurProp, 'max': valeurProp}
                    else:
                        bikeList[cle]['max'] = max(bikeList[cle]['max'], valeurProp)
                        bikeList[cle]['min'] = min(bikeList[cle]['min'], valeurProp)

                    bikeList[cle]['data'].append(valeurProp)

    for cle in bikeList:
        info = bikeList[cle]
        if type(info) is dict:
            data = info['data']
            bikeList[cle]['moyenne'] = sum(data) / max(len(data), 1)

    if len(bikeList) > 0:
        valeurs = bikeList
        requete = mysql.cursor()
        #nbEDock peut être null
        nbEDock = 0
        if nbEDock in valeurs:
            nbEDock = valeurs['nbEDock']
        requete.execute('INSERT INTO `resumeConso` (`id`, `date`, `duree`, `nbStation`, nbStationDetecte, `nbBikeMin`, `nbBikeMax`, `nbBikeMoyenne`, `nbEBikeMin`, `nbEBikeMax`, `nbEBikeMoyenne`, `nbFreeEDockMin`, `nbFreeEDockMax`, `nbFreeEDockMoyenne`, `nbEDock`) VALUES \
        (NULL, "'+dateConso+'", '+str(dureeConso)+', '+str(valeurs['nbStation'])+', '+str(valeurs['nbStationDetecte'])+', '+str(valeurs['nbBike']['min'])+', '+str(valeurs['nbBike']['max'])+', '+str(valeurs['nbBike']['moyenne'])+', '+str(valeurs['nbEBike']['min'])+', '+str(valeurs['nbEBike']['max'])+', '+str(valeurs['nbEBike']['moyenne'])+', '+str(valeurs['nbFreeEDock']['min'])+', '+str(valeurs['nbFreeEDock']['max'])+', '+str(valeurs['nbFreeEDock']['moyenne'])+', '+str(nbEDock)+')')

def calculerResumeOfResumeConso(dateConsoDT, dureeConsoOrigine, dureeConsoFinale):
    dateConso = dateConsoDT.strftime("%Y-%m-%d %H:%M:%S")
    finConso = dateConsoDT + datetime.timedelta(minutes=dureeConsoFinale)
    dateConsoFin = finConso.strftime("%Y-%m-%d %H:%M:%S")

    #On récupère les conso
    proprietes = ['nbStation', 'nbStationDetecte', 'nbBikeMin', 'nbBikeMax', 'nbBikeMoyenne', 'nbEBikeMin', 'nbEBikeMax', 'nbEBikeMoyenne', 'nbFreeEDockMin', 'nbFreeEDockMax', 'nbFreeEDockMoyenne', 'nbEDock']
    
    mysql = getMysqlConnection()
    requete = mysql.cursor()
    requete.execute("SELECT "+', '.join(proprietes)+" FROM `resumeConso` WHERE duree = "+str(dureeConsoOrigine)+" and date >= '"+dateConso+"' and date < '"+dateConsoFin+"' order by date")
    consos = requete.fetchall()
    precedentCode = None
    infosCourantes = {}
    for row in consos:
        for i, cle in enumerate(proprietes):
            if cle[-7:] == 'Moyenne':
                valeurProp = float(row[i])
            else:
                valeurProp = int(row[i])
            
            if cle in infosCourantes:
                if cle[-7:] == 'Moyenne':
                    infosCourantes[cle].append(valeurProp)
                elif cle[-3:] == 'Min':
                    infosCourantes[cle] = min(infosCourantes[cle], valeurProp)
                else:
                    infosCourantes[cle] = max(infosCourantes[cle], valeurProp)
            else:
                if cle[-7:] == 'Moyenne':
                    infosCourantes[cle] = [valeurProp]
                else:
                    infosCourantes[cle] = valeurProp

    #Et on enregistre
    if infosCourantes != {}:
        strValeurs = ""
        for i, cle in enumerate(proprietes):
            if cle[-7:] == 'Moyenne':
                data = infosCourantes[cle]
                moyenne = sum(data) / max(len(data), 1)
                infosCourantes[cle] = moyenne
            if i != 0:
                strValeurs += ', '
            strValeurs += str(infosCourantes[cle])
        
        requete = mysql.cursor()
        requete.execute('INSERT INTO `resumeConso` (`id`, `date`, `duree`, '+', '.join(proprietes)+') VALUES \
        (NULL, "'+dateConso+'", '+str(dureeConsoFinale)+', '+strValeurs+')')

def debuterCalculResumeConso(periode, dateCourante = datetime.datetime.now()):
    dateConsoDT = dateCourante - datetime.timedelta(minutes=periode)
    debutPeriodeMinute = dateConsoDT.minute % periode
    dateConsoDT = dateConsoDT.replace(microsecond = 0, second = 0, minute=dateConsoDT.minute - debutPeriodeMinute)
    calculerResumeConso(dateConsoDT, periode)

def debuterCalculResumeOfResumeConso(periodeOrigine, periodeFinale, dateCourante = datetime.datetime.now()):
    dateConsoDT = dateCourante - datetime.timedelta(minutes=periodeFinale)
    debutPeriodeMinute = (dateConsoDT.hour * 60 + dateConsoDT.minute) % periodeFinale
    dateConsoDT = dateConsoDT.replace(microsecond = 0, second = 0) - datetime.timedelta(minutes=debutPeriodeMinute)
    calculerResumeOfResumeConso(dateConsoDT, periodeOrigine, periodeFinale)