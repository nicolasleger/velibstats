import urllib.request, json, os

def getAdresse(lat, lon):
    urlBAN = 'https://api-adresse.data.gouv.fr/reverse/?lat='+str(lat)+'&lon='+str(lon)
    tmpFileName = 'adresse.json'
    urllib.request.urlretrieve(urlBAN, tmpFileName)

    data = json.load(open(tmpFileName))

    adresse = data['features'][0]['properties']['label']

    os.remove(tmpFileName)
    return adresse