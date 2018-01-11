from resumeLib import calculerResume
import datetime

dureeConso = 5 #En minutes
#for i in range(0, 11):
    #dateConsoDT = datetime.datetime(2018,1,9,11,i * dureeConso,0)
dateConsoDT = datetime.datetime.now() - datetime.timedelta(minutes=dureeConso)
dateConsoDT = dateConsoDT.replace(second = 0)
calculerResume(dateConsoDT, dureeConso)