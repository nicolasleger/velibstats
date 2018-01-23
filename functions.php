<?php
include_once('carte.php');

function displayCodeStation($code)
{
    if($code < 10000)
        return '0'.$code;
    return $code;
}

function getCommuneStation($codeStation)
{
    global $pdo;

    $res = $pdo->query('SELECT insee FROM tranche WHERE debut <= '.$codeStation.' AND fin >= '.$codeStation);
    $ligne = $res->fetch();

    if(is_null($ligne))
        return null;
    
    return $ligne['insee'];
}

function getStatusByIdConso($idConso, $filtreWhere = null)
{
    global $pdo;
    $requete = $pdo->query('SELECT * FROM status INNER JOIN `stations` ON stations.code = status.code WHERE idConso = '.$idConso.
    (is_null($filtreWhere) ? '' : ' AND ('.$filtreWhere.')').' ORDER BY status.code ASC');
    if($requete === false)
        return null;
    $data = $requete->fetchAll(PDO::FETCH_ASSOC);
    $retour = [];
    foreach($data as $station)
    {
        $retour[] = array(
            'code' => $station['code'],
            'codeStr' => displayCodeStation($station['code']),
            'name' => $station['name'],
            'dateOuverture' => is_null($station['dateOuverture']) ? 'Non ouvert' : $station['dateOuverture'],
            'state' => $station['state'],
            'nbBike' => $station['nbBike'],
            'nbEbike' => $station['nbEBike'],
            'nbFreeEDock' => $station['nbFreeEDock'],
            'nbEDock' => $station['nbEDock'],
            'latitude' => $station['latitude'],
            'longitude' => $station['longitude']
        );
    }
    return $retour;
}
?>