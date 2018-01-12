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
    case 'getBikeResumeTroisHeures':
        echo json_encode(getBikeResume($codeStation, "-3hours", 5));
        exit();
        break;
    case 'getBikeResumeUnJour':
        echo json_encode(getBikeResume($codeStation, "-1day", 15));
        exit();
        break;
    case 'getBikeResumeSeptJours':
        echo json_encode(getBikeResume($codeStation, "-7days", 60));
        exit();
        break;
    case 'getBikeResumeUnMois':
        echo json_encode(getBikeResume($codeStation, "-1month", 360));
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

function getBikeResume($codeStation, $filtreDate, $periode)
{
    $data = getDataBikeResume($codeStation, $filtreDate, $periode);

    $dataReturn = array(
        'labels' => $data['labels'],
        'datasets' => $data['datasets']
    );
        
    $options = 
        array(
            'responsive' => false,
            'scales' => array(
                'yAxes' => array(
                    array('stacked' => false)
                )
            )
        );
    return array(
        'type' => 'bar',
        'data' => $dataReturn,
        'options' => $options
    );
}

function getDataBikeResume($codeStation, $filtre, $periode)
{
    global $pdo;

    //Filtre date
    $date = new DateTime($filtre);
    $filtreDate = $date->format('Y-m-d H:i:s');

    $requete = $pdo->query('SELECT `date`, nbBikeMin, nbBikeMax, nbBikeMoyenne, nbBikePris, nbBikeRendu, nbEBikeMin, nbEBikeMax, nbEBikeMoyenne, nbEBikePris, nbEBikeRendu
    FROM resumeStatus 
    WHERE code = '.$codeStation.' AND `date` >= "'.$filtreDate.'" AND duree = '.$periode.'
    ORDER BY date ASC');
    $resumeStatusStation = $requete->fetchAll();

    $datesResume = [];
    $nbBikeMinData = [];
    $nbBikeMaxData = [];
    $nbBikeMoyenneData = [];
    $nbBikePrisData = [];
    $nbBikeRenduData = [];
    $nbEBikeMinData = [];
    $nbEBikeMaxData = [];
    $nbEBikeMoyenneData = [];
    $nbEBikePrisData = [];
    $nbEBikeRenduData = [];
    foreach($resumeStatusStation as $statut)
    {
        $datesResume[] = $statut['date'];
        $nbBikeMinData[] = $statut['nbBikeMin'];
        $nbBikeMaxData[] = $statut['nbBikeMax'];
        $nbBikeMoyenneData[] = $statut['nbBikeMoyenne'];
        $nbBikePrisData[] = -1*$statut['nbBikePris'];
        $nbBikeRenduData[] = $statut['nbBikeRendu'];
        $nbEBikeMinData[] = $statut['nbEBikeMin'];
        $nbEBikeMaxData[] = $statut['nbEBikeMax'];
        $nbEBikeMoyenneData[] = $statut['nbEBikeMoyenne'];
        $nbEBikePrisData[] = -1*$statut['nbEBikePris'];
        $nbEBikeRenduData[] = $statut['nbEBikeRendu'];
    }

    return array(
        'labels' => $datesResume,
        'datasets' => array(
            array(
                'type' => 'line',
                'label' => 'Vélos mécaniques (Moyenne)',
                'borderColor' => 'rgba(104,221,46,0.7)',
                'fill' => false,
                'data' => $nbBikeMoyenneData
            ),
            array(
                'type' => 'line',
                'label' => 'Vélos mécaniques (Min)',
                'borderColor' => 'rgba(104,221,46,0)',
                'backgroundColor' => 'rgba(104,221,46,0.3)',
                'fill' => "+1",
                'borderDash' => [5, 5],
                'data' => $nbBikeMinData
            ),
            array(
                'type' => 'line',
                'label' => 'Vélos mécaniques (Max)',
                'borderColor' => 'rgba(104,221,46,0)',
                'backgroundColor' => 'rgba(104,221,46,0.3)',
                'fill' => false,
                'borderDash' => [5, 5],
                'data' => $nbBikeMaxData
            ),
            array(
                'label' => 'Vélos mécaniques (Pris)',
                'borderColor' => 'rgba(104,221,46,0.5)',
                'data' => $nbBikePrisData
            ),
            array(
                'label' => 'Vélos mécaniques (Rendu)',
                'borderColor' => 'rgba(104,221,46,0.5)',
                'data' => $nbBikeRenduData
            ),
            array(
                'type' => 'line',
                'label' => 'Vélos électriques (Moyenne)',
                'borderColor' => 'rgba(76, 213, 233, 0.7)',
                'fill' => false,
                'data' => $nbEBikeMoyenneData
            ),
            array(
                'type' => 'line',
                'label' => 'Vélos électriques (Min)',
                'borderColor' => 'rgba(76, 213, 233, 0)',
                'backgroundColor' => 'rgba(76, 213, 233, 0.3)',
                'fill' => "+1",
                'borderDash' => [5, 5],
                'data' => $nbEBikeMinData
            ),
            array(
                'type' => 'line',
                'label' => 'Vélos électriques (Max)',
                'borderColor' => 'rgba(76, 213, 233, 0)',
                'backgroundColor' => 'rgba(76, 213, 233, 0.3)',
                'fill' => false,
                'borderDash' => [5, 5],
                'data' => $nbEBikeMaxData
            ),
            array(
                'label' => 'Vélos électriques (Pris)',
                'borderColor' => 'rgba(76, 213, 233, 0.5)',
                'data' => $nbEBikePrisData
            ),
            array(
                'label' => 'Vélos électriques (Rendu)',
                'borderColor' => 'rgba(76, 213, 233, 0.5)',
                'data' => $nbEBikeRenduData
            )
        )
            );
}
?>