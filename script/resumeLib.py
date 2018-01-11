import urllib.request, json, os, pymysql, datetime
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
                if not codeStation in bikeList:
                    bikeList[codeStation] = {cle: {'data': [], 'min': valeurProp, 'max': valeurProp}}
                    if cle in ['nbBike', 'nbEBike']:
                        bikeList[codeStation][cle]['pris'] = 0
                        bikeList[codeStation][cle]['remis'] = 0
                elif not cle in bikeList[codeStation]:
                    bikeList[codeStation][cle] = {'data': [], 'min': valeurProp, 'max': valeurProp}
                    if cle in ['nbBike', 'nbEBike']:
                        bikeList[codeStation][cle]['pris'] = 0
                        bikeList[codeStation][cle]['remis'] = 0
                else:
                    bikeList[codeStation][cle]['max'] = max(bikeList[codeStation][cle]['max'], valeurProp)
                    bikeList[codeStation][cle]['min'] = min(bikeList[codeStation][cle]['min'], valeurProp)
                
                if cle in ['nbBike', 'nbEBike']:
                    if not codeStation in precedents:
                        precedents[codeStation] = {}

                    if len(bikeList[codeStation][cle]['data']) > 0 and cle in precedents[codeStation]:
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