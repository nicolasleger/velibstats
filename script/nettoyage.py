import datetime
from nettoyageLib import nettoyerInstantanne, nettoyerConso

date = datetime.datetime.today() - datetime.timedelta(days=2)
dateLimite = date.replace(microsecond = 0, second = 0, minute=0, hour=0)
nettoyerInstantanne(dateLimite)
nettoyerConso(dateLimite, "duree = 5 OR duree = 15")
dateHuitJours = datetime.datetime.today() - datetime.timedelta(days=8)
dateLimiteHuitJours = dateHuitJours.replace(microsecond = 0, second = 0, minute=0, hour=0)
nettoyerConso(dateLimiteHuitJours, "duree = 60")