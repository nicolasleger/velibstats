from DumpLib import creerDumpData, creerDumpConso
import pymysql, datetime
from config import getMysqlConnection

#On commence par avoir la date min et max
mysql = getMysqlConnection()
requete = mysql.cursor()
requete.execute("SELECT MIN(date), MAX(date) FROM `statusConso`")
bornesDate = requete.fetchone()
dateMin = bornesDate[0].replace(microsecond = 0, second = 0, minute = 0, hour = 0)
dateMax = bornesDate[1].replace(microsecond = 0, second = 0, minute = 0, hour = 0)
date = dateMin
print("******* Bornes *******")
print("Début = "+str(dateMin))
print("Fin = "+str(dateMax))
print("******* Bornes *******")
while date < dateMax:
    print("Calcul de : " + str(date))
    creerDumpData(date)
    creerDumpConso(date)
    date = date + datetime.timedelta(days=1)