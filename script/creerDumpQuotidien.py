from DumpLib import creerDumpData, creerDumpConso
import datetime

date = datetime.datetime.today() - datetime.timedelta(days=2)
creerDumpData(date)
creerDumpConso(date)