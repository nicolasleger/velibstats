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
    case 'getFreeDockInstantane':
        echo json_encode(getFreeDockInstantane($codeStation));
        exit();
        break;
    case 'getFreeDockResumeTroisHeures':
        echo json_encode(getFreeDockResume($codeStation, "-3hours", 5));
        exit();
        break;
    case 'getFreeDockResumeUnJour':
        echo json_encode(getFreeDockResume($codeStation, "-1day", 15));
        exit();
        break;
    case 'getFreeDockResumeSeptJours':
        echo json_encode(getFreeDockResume($codeStation, "-7days", 60));
        exit();
        break;
    case 'getFreeDockResumeUnMois':
        echo json_encode(getFreeDockResume($codeStation, "-1month", 360));
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

    $requete = $pdo->query('SELECT c.date, s.nbBike, s.nbEBike, s.nbEDock 
    FROM status s 
    INNER JOIN statusConso c ON c.id = s.idConso 
    WHERE s.code = '.$codeStation.' AND c.date >= "'.$filtreDate.'" 
    ORDER BY c.date ASC');
    $statusStation = $requete->fetchAll();

    $dates = [];
    $nbBikeData = [];
    $nbEbikeData = [];
    foreach($statusStation as $statut)
    {
        $dates[] = (new DateTime($statut['date']))->format("d/m H\hi");
        $nbBikeData[] = $statut['nbBike'];
        $nbEbikeData[] = $statut['nbEBike'];
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
        $datesResume[] = (new DateTime($statut['date']))->format("d/m H\hi");
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
                'backgroundColor' => 'rgba(104,221,46,0.5)',
                'data' => $nbBikePrisData
            ),
            array(
                'label' => 'Vélos mécaniques (Rendu)',
                'backgroundColor' => 'rgba(104,221,46,0.5)',
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
                'backgroundColor' => 'rgba(76, 213, 233, 0.5)',
                'data' => $nbEBikePrisData
            ),
            array(
                'label' => 'Vélos électriques (Rendu)',
                'backgroundColor' => 'rgba(76, 213, 233, 0.5)',
                'data' => $nbEBikeRenduData
            )
        )
            );
}

function getFreeDockInstantane($codeStation)
{
    $data = getDataFreeDockInstantane($codeStation);

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

function getDataFreeDockInstantane($codeStation)
{
    global $pdo;

    //Filtre 1 heure
    $hier = new DateTime("-1hour");
    $filtreDate = $hier->format('Y-m-d H:i:s');

    $requete = $pdo->query('SELECT c.date, s.nbFreeEDock, s.nbEDock 
    FROM status s 
    INNER JOIN statusConso c ON c.id = s.idConso 
    WHERE s.code = '.$codeStation.' AND c.date >= "'.$filtreDate.'" 
    ORDER BY c.date ASC');
    $statusStation = $requete->fetchAll();

    $dates = [];
    $nbFreeEdockData = [];
    foreach($statusStation as $statut)
    {
        $dates[] = (new DateTime($statut['date']))->format("d/m H\hi");
        $nbFreeEdockData[] = $statut['nbFreeEDock'];
    }

    return array(
        'labels' => $dates,
        'datasets' => array(
            array(
                'label' => 'Nombre de bornes libres',
                'backgroundColor' => 'rgba(173,0,130,0.5)',
                'data' => $nbFreeEdockData
            )
        )
            );
}

function getFreeDockResume($codeStation, $filtreDate, $periode)
{
    $data = getDataFreeDockResume($codeStation, $filtreDate, $periode);

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
        'type' => 'line',
        'data' => $dataReturn,
        'options' => $options
    );
}

function getDataFreeDockResume($codeStation, $filtre, $periode)
{
    global $pdo;

    //Filtre date
    $date = new DateTime($filtre);
    $filtreDate = $date->format('Y-m-d H:i:s');

    $requete = $pdo->query('SELECT `date`, nbFreeEDockMin, nbFreeEDockMax, nbFreeEDockMoyenne
    FROM resumeStatus 
    WHERE code = '.$codeStation.' AND `date` >= "'.$filtreDate.'" AND duree = '.$periode.'
    ORDER BY date ASC');
    $resumeStatusStation = $requete->fetchAll();

    $datesResume = [];
    $nbFreeEDockMinData = [];
    $nbFreeEDockMaxData = [];
    $nbFreeEDockMoyenneData = [];
    foreach($resumeStatusStation as $statut)
    {
        $datesResume[] = (new DateTime($statut['date']))->format("d/m H\hi");
        $nbFreeEDockMinData[] = $statut['nbFreeEDockMin'];
        $nbFreeEDockMaxData[] = $statut['nbFreeEDockMax'];
        $nbFreeEDockMoyenneData[] = $statut['nbFreeEDockMoyenne'];
    }

    return array(
        'labels' => $datesResume,
        'datasets' => array(
            array(
                'label' => 'Nombre de bornes libres (Moyenne)',
                'borderColor' => 'rgba(173,0,130,0.7)',
                'fill' => false,
                'data' => $nbFreeEDockMoyenneData
            ),
            array(
                'label' => 'Nombre de bornes libres (Min)',
                'borderColor' => 'rgba(173,0,130,0)',
                'backgroundColor' => 'rgba(173,0,130,0.3)',
                'fill' => "+1",
                'borderDash' => [5, 5],
                'data' => $nbFreeEDockMinData
            ),
            array(
                'label' => 'Nombre de bornes libres (Max)',
                'borderColor' => 'rgba(173,0,130,0)',
                'backgroundColor' => 'rgba(173,0,130,0.3)',
                'fill' => false,
                'borderDash' => [5, 5],
                'data' => $nbFreeEDockMaxData
            )
        )
            );
}
?>