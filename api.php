<?php
require_once('config.php');
include_once('functions.php');

if(!isset($_GET['action']) || strlen($_GET['action']) == 0)
    exit();

if(!isset($_GET['codeStation']) || intval($_GET['codeStation']) == 0)
    exit();

header('Content-Type: application/json');

$codeStation = intval($_GET['codeStation']);

switch($_GET['action'])
{
    case 'getBikeInstantane':
        echo json_encode(getBikeInstantane($codeStation));
        exit();
        break;
}

function getBikeInstantane($codeStation)
{
    $data = getDataBikeInstantane($codeStation);

    $dataReturn = array(
        'labels' => $data['labels'],
        'datasets' => $data['datasets']
    );
        
    $options = 
        array(
            'responsive' => false,
            'scales' => array(
                'yAxes' => array(
                    array('stacked' => true)
                )
            )
        );
    return array(
        'type' => 'line',
        'data' => $dataReturn,
        'options' => $options
    );
}

function getDataBikeInstantane($codeStation)
{
    global $pdo;

    //Filtre 1 heure
    $hier = new DateTime("-1hour");
    $filtreDate = $hier->format('Y-m-d H:i:s');

    $requete = $pdo->query('SELECT c.date, s.nbBike, s.nbEBike, s.nbFreeEDock, s.nbEDock 
    FROM status s 
    INNER JOIN statusConso c ON c.id = s.idConso 
    WHERE s.code = '.$codeStation.' AND c.date >= "'.$filtreDate.'" 
    ORDER BY s.code ASC');
    $statusStation = $requete->fetchAll();

    $dates = [];
    $nbBikeData = [];
    $nbEbikeData = [];
    $nbFreeEdockData = [];
    foreach($statusStation as $statut)
    {
        $dates[] = $statut['date'];
        $nbBikeData[] = $statut['nbBike'];
        $nbEbikeData[] = $statut['nbEBike'];
        $nbFreeEdockData[] = $statut['nbFreeEDock'];
    }

    return array(
        'labels' => $dates,
        'datasets' => array(
            array(
                'label' => 'Vélos mécaniques',
                'backgroundColor' => 'rgba(104,221,46,0.5)',
                'data' => $nbBikeData
            ),
            array(
                'label' => 'Vélos électriques',
                'backgroundColor' => 'rgba(76, 213, 233, 0.5)',
                'data' => $nbEbikeData
            )
        )
            );
}
?>