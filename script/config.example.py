import pymysql

def getMysqlConnection():
    return pymysql.connect(host="", user="",passwd="",db="")

def getURLVelib():
    return ''