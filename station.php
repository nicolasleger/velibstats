<?php
require_once('config.php');
include_once('functions.php');
require_once('libs/Smarty.class.php');

$smarty = new Smarty();

if(!isset($_GET['code']) || intval($_GET['code']) <= 0)
{
    header('location: index.php');
    exit();
}

//On récupère la station
$code = intval($_GET['code']);
$requete = $pdo->query('SELECT * FROM stations WHERE code = '.$code);
if($requete === false)
{
    header('location: index.php');
    exit();
}
$station = $requete->fetch();
if($station === false)
{
    header('location: index.php');
    exit();
}
$smarty->assign(array(
    'stationCode' => $code,
    'stationCodeDisplay' => displayCodeStation($code),
    'stationNom' => $station['name'],
    'stationDateOuverture' => (new DateTime($station['dateOuverture']))->format('d/m/Y'),
    'stationAdresse' => $station['adresse']
));

//Filtre semaine
$hier = new DateTime("-1week");
$filtreSemaine = $hier->format('Y-m-d H:i:s');

//Signalements
$requete = $pdo->query('SELECT * 
FROM signalement 
WHERE code = '.$code.' AND dateSignalement >= "'.$filtreSemaine.'" 
ORDER BY dateSignalement DESC');
$signalements = $requete->fetchAll();
$resumeSignalement = array(true => 0, false => 0);
foreach($signalements as $sign)
{
    $resumeSignalement[$sign['estFonctionnel'] == 1]++;
}
$smarty->assign(array(
    'signalementOui' => $resumeSignalement[true],
    'signalementNon' => $resumeSignalement[false],
    'signalementTotal' => array_sum($resumeSignalement)
));
if(count($signalements) > 0)
{
    $smarty->assign(array(
        'dernierSignalementType' => $signalements[0]['estFonctionnel'] == 1,
        'dernierSignalementDate' => (new DateTime($signalements[0]['dateSignalement']))->format('d/m H:i')
    ));
}

$smarty->display('station.tpl');
exit();
?>