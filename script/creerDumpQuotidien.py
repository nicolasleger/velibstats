from DumpLib import creerDumpData
import datetime

date = datetime.datetime.today() - datetime.timedelta(days=2)
creerDumpData(date)