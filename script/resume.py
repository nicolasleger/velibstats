from resumeLib import calculerResume
import datetime

dureeConso = 5 #En minutes
#for i in range(0, 11):
    #dateConsoDT = datetime.datetime(2018,1,9,11,i * dureeConso,0)
dateConsoDT = datetime.datetime.now() - datetime.timedelta(minutes=dureeConso)
debutPeriodeMinute = dateConsoDT.minute % dureeConso
dateConsoDT = dateConsoDT.replace(microsecond = 0, second = 0, minute=dateConsoDT.minute - debutPeriodeMinute)
calculerResume(dateConsoDT, dureeConso)

# dateConsoDT = datetime.datetime(2018,1,11,18,15,0)
# calculerResumeOfResume(dateConsoDT, 5, 15)