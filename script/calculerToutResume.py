from resumeLib import debuterCalculResume, debuterCalculResumeOfResume
import pymysql, datetime
from config import getMysqlConnection

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
    dateCourante = dateMin
    print("-> Début période "+ str(periode))
    while dateCourante + datetime.timedelta(minutes=periode) <= dateMax:
        print("Période " + str(periode)+'; date = '+str(dateCourante))
        if periode == 5:
            debuterCalculResume(periode, dateCourante)
        else:
            debuterCalculResumeOfResume(periodePrecedente, periode, dateCourante)
        dateCourante = dateCourante + datetime.timedelta(minutes=periode)
    periodePrecedente = periode
    print("-> Fin période "+ str(periode))