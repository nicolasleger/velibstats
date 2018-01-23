<?php
require_once('config.php');
include_once('functions.php');
require_once('libs/Smarty.class.php');

if(!isset($_GET['insee']) || intval($_GET['insee']) == 0)
{
    header('location: index.php');
    exit();
}

$insee = intval($_GET['insee']);

//On regarde si la commune existe
$requete = $pdo->query('SELECT * FROM commune WHERE insee = '.$insee);
$commune = $requete->fetch();

if($commune === false)
{
    //Elle n'existe pas
    header('location: index.php');
    exit();    
}

$smarty = new Smarty();

$smarty->assign(array(
    'communeInsee' => $insee,
    'communeNom' => $commune['nom_complet']
));

//On récupère les stations de la tranche
$requete = $pdo->query('SELECT debut, fin FROM tranche WHERE insee = '.$insee.' ORDER BY debut');
$tranches = $requete->fetch();

$debutTranche = $tranches['debut'];
$finTranche = $tranches['fin'];

//Filtre 24 heures
$hier = new DateTime("-1day");
$filtreDate = $hier->format('Y-m-d H:i:s');

//Dernière conso
$requete = $pdo->query('SELECT id, date FROM `statusConso` WHERE nbStation IS NOT NULL AND date >= "'.$filtreDate.'" ORDER BY id DESC LIMIT 0,1');
$conso = $requete->fetch();
$idConso = $conso['id'];

$smarty->assign(array(
    'dateDerniereConso' => (new DateTime($conso['date']))->format('d/m/Y à H:i')
));

//Stations
$stations = getStatusByIdConso($idConso, 'stations.code >= '.$debutTranche.' AND stations.code <= '.$finTranche);
$nbStation = 0;
$nbStationDetecte = 0;
$nbBike = 0;
$nbEbike = 0;
$nbEdock = 0;
$nbFreeEdock = 0;

foreach($stations as $station)
{
    if($station['state'] == 'Operative')
        $nbStation++;
    if($station['nbEDock'] > 0 && ($station['nbBike'] + $station['nbEbike'] + $station['nbFreeEDock']) > 0)
        $nbStationDetecte++;
    
    $nbBike += $station['nbBike'];
    $nbEbike += $station['nbEbike'];
    $nbEdock += $station['nbEDock'];
    $nbFreeEdock += $station['nbFreeEDock'];
}

$smarty->assign(array(
    'nbStation' => $nbStation,
    'nbStationDetecte' => $nbStationDetecte,
    'nbBike' => $nbBike,
    'nbEbike' => $nbEbike,
    'nbEDock' => $nbEdock,
    'nbFreeEDock' => $nbFreeEdock,
    'stations' => $stations
));

$smarty->display('commune.tpl');
exit();
?>