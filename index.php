<?php
require_once('config.php');
include_once('functions.php');
require_once('libs/Smarty.class.php');

$smarty = new Smarty();

//Filtre 24 heures
$hier = new DateTime("-1day");
$filtreDate = $hier->format('Y-m-d H:i:s');

//Dernière conso
$requete = $pdo->query('SELECT * FROM `statusConso` where nbStation Is not null and date >= "'.$filtreDate.'" Order by id desc limit 0,1');
$conso = $requete->fetch();

$smarty->assign(array(
    'idConso' => $conso['id'],
    'nbStation' => $conso['nbStation'],
    'nbStationDetecte' => $conso['nbStationDetecte'],
    'nbBike' => $conso['nbBike'],
    'nbEbike' => $conso['nbEbike'],
    'nbEDock' => $conso['nbEDock'],
    'nbFreeEDock' => $conso['nbFreeEDock'],
    'dateDerniereConso' => (new DateTime($conso['date']))->format('d/m/Y à H:i')
));

$smarty->display('index.tpl');
exit();
?>