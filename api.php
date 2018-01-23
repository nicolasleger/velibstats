<?php
require_once('config.php');
include_once('functions.php');

error_reporting(E_ALL);

if(!isset($_GET['action']) || strlen($_GET['action']) == 0)
    exit();

if((!isset($_GET['codeStation']) || intval($_GET['codeStation']) == 0) && (!isset($_GET['idConso']) || intval($_GET['idConso']) == 0))
    exit();

header('Content-Type: application/json');

if(isset($_GET['codeStation']))
    $codeStation = intval($_GET['codeStation']);

if(isset($_GET['idConso']))
    $idConso = intval($_GET['idConso']);

if(isset($_GET['lat']))
    $latitude = floatval($_GET['lat']);
else
    $latitude = null;

if(isset($_GET['long']))
    $longitude = floatval($_GET['long']);
else
    $longitude = null;

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
    case 'vote':
        echo json_encode(compterVote($codeStation, $_GET['statut']));
        exit();
        break;
    case 'getConsoInstantane':
        echo json_encode(getConsoInstantane());
        exit();
        break;
    case 'getConsoResumeTroisHeures':
        echo json_encode(getConsoResume("-3hours", 5));
        exit();
        break;
    case 'getConsoResumeUnJour':
        echo json_encode(getConsoResume("-1day", 15));
        exit();
        break;
    case 'getConsoResumeSeptJours':
        echo json_encode(getConsoResume("-7days", 60));
        exit();
        break;
    case 'getConsoResumeUnMois':
        echo json_encode(getConsoResume("-1month", 360));
        exit();
        break;
    case 'getConsoBikeInstantane':
        echo json_encode(getConsoBikeInstantane());
        exit();
        break;
    case 'getConsoBikeResumeTroisHeures':
        echo json_encode(getConsoBikeResume("-3hours", 5));
        exit();
        break;
    case 'getConsoBikeResumeUnJour':
        echo json_encode(getConsoBikeResume("-1day", 15));
        exit();
        break;
    case 'getConsoBikeResumeSeptJours':
        echo json_encode(getConsoBikeResume("-7days", 60));
        exit();
        break;
    case 'getConsoBikeResumeUnMois':
        echo json_encode(getConsoBikeResume("-1month", 360));
        exit();
        break;
    case 'getDataConso':
        echo json_encode(getDataConso($idConso, $longitude, $latitude));
        exit();
        break;
    case 'getDataStation':
        echo json_encode(getDataStation($codeStation));
        exit();
        break;
    case 'getCommunesCarte':
        echo json_encode(getCommunesCarte($idConso));
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

function compterVote($codeStation, $statut)
{
    global $pdo;

    $vote = null;
    if($statut == 'oui')
        $vote = 1;
    elseif($statut == 'non')
        $vote = 0;
    else
        return false;
    
    $pdo->exec('INSERT INTO signalement (code, estFonctionnel) VALUES ('.$codeStation.', '.$vote.')');
    return true;
}

function getConsoInstantane()
{
    $data = getDataConsoInstantane();

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

function getDataConsoInstantane()
{
    global $pdo;

    //Filtre 1 heure
    $hier = new DateTime("-1hour");
    $filtreDate = $hier->format('Y-m-d H:i:s');

    $requete = $pdo->query('SELECT * FROM `statusConso` Where date >= "'.$filtreDate.'" Order by id asc');
    $allConso = $requete->fetchAll();
    $dates = [];
    $nbStationsData = [];
    $nbStationsDetecteData = [];
    foreach($allConso as $i => $c)
    {
        if($c['nbStation'] > 0)
        {
            $dates[] = (new DateTime($c['date']))->format("d/m H\hi");
            $nbStationsData[] = $c['nbStation'];
            $nbStationsDetecteData[] = $c['nbStationDetecte'];
        }
    }

    return array(
        'labels' => $dates,
        'datasets' => array(
            array(
                'label' => 'Stations annoncées',
                'backgroundColor' => 'rgba(173,0,130,0.5)',
                'data' => $nbStationsData
            ),
            array(
                'label' => 'Stations détectées',
                'backgroundColor' => 'rgba(208,74,5,0.5)',
                'data' => $nbStationsDetecteData
            )
        )
            );
}

function getConsoBikeInstantane()
{
    $data = getDataConsoBikeInstantane();

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

function getDataConsoBikeInstantane()
{
    global $pdo;

    //Filtre 1 heure
    $hier = new DateTime("-1hour");
    $filtreDate = $hier->format('Y-m-d H:i:s');

    $requete = $pdo->query('SELECT * FROM `statusConso` Where date >= "'.$filtreDate.'" Order by id asc');
    $allConso = $requete->fetchAll();
    $dates = [];
    $nbBikeData = [];
    $nbEbikeData = [];
    foreach($allConso as $i => $c)
    {
        if($c['nbStation'] > 0)
        {
            $dates[] = (new DateTime($c['date']))->format("d/m H\hi");
            $nbBikeData[] = $c['nbBike'];
            $nbEbikeData[] = $c['nbEbike'];
        }
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

function getConsoResume($filtreDate, $periode)
{
    $data = getDataConsoResume($filtreDate, $periode);

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

function getDataConsoResume($filtre, $periode)
{
    global $pdo;

    //Filtre date
    $date = new DateTime($filtre);
    $filtreDate = $date->format('Y-m-d H:i:s');

    $requete = $pdo->query('SELECT *
    FROM resumeConso
    WHERE `date` >= "'.$filtreDate.'" AND duree = '.$periode.'
    ORDER BY date ASC');
    $resumeStatusStation = $requete->fetchAll();

    $datesResume = [];
    $nbStationsData = [];
    $nbStationsDetecteData = [];
    foreach($resumeStatusStation as $statut)
    {
        $datesResume[] = (new DateTime($statut['date']))->format("d/m H\hi");
        $nbStationsData[] = $statut['nbStation'];
        $nbStationsDetecteData[] = $statut['nbStationDetecte'];
    }

    return array(
        'labels' => $datesResume,
        'datasets' => array(
            array(
                'label' => 'Stations annoncées',
                'backgroundColor' => 'rgba(173,0,130,0.5)',
                'data' => $nbStationsData
            ),
            array(
                'label' => 'Stations détectées',
                'backgroundColor' => 'rgba(208,74,5,0.5)',
                'data' => $nbStationsDetecteData
            )
        )
            );
}

function getConsoBikeResume($filtreDate, $periode)
{
    $data = getDataConsoBikeResume($filtreDate, $periode);

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

function getDataConsoBikeResume($filtre, $periode)
{
    global $pdo;

    //Filtre date
    $date = new DateTime($filtre);
    $filtreDate = $date->format('Y-m-d H:i:s');

    $requete = $pdo->query('SELECT *
    FROM resumeConso
    WHERE `date` >= "'.$filtreDate.'" AND duree = '.$periode.'
    ORDER BY date ASC');
    $resumeStatusStation = $requete->fetchAll();

    $datesResume = [];
    $nbBikeData = [];
    $nbEbikeData = [];
    foreach($resumeStatusStation as $i => $c)
    {
        if($c['nbStation'] > 0)
        {
            $datesResume[] = (new DateTime($c['date']))->format("d/m H\hi");
            $nbBikeData[] = $c['nbBikeMoyenne'];
            $nbEbikeData[] = $c['nbEBikeMoyenne'];
        }
    }

    return array(
        'labels' => $datesResume,
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

function getDataConso($idConso, $longitude = null, $latitude = null)
{
    global $pdo;
    $whereCoord = null;
    if(!is_null($longitude) && !is_null($latitude))
    {
        $whereCoord = implode(' AND ', array(
            'stations.longitude >= '.($longitude - 0.015),
            'stations.longitude <= '.($longitude + 0.015),
            'stations.latitude >= '.($latitude - 0.01),
            'stations.latitude <= '.($latitude + 0.01)
        ));
    }

    return array('data' => getStatusByIdConso($idConso, $whereCoord));
}

function getDataStation($codeStation)
{
    global $pdo;
    //Filtre 24 heures
    $hier = new DateTime("-1day");
    $filtreDate = $hier->format('Y-m-d H:i:s');

    //Stations
    $requete = $pdo->query('SELECT c.id, c.date, s.nbBike, s.nbEBike, s.nbFreeEDock, s.nbEDock 
    FROM status s 
    INNER JOIN statusConso c ON c.id = s.idConso 
    WHERE s.code = '.$codeStation.' AND c.date >= "'.$filtreDate.'" 
    ORDER BY c.id ASC');
    $statusStation = $requete->fetchAll(PDO::FETCH_ASSOC);
    $retour = [];
    foreach($statusStation as $statut)
    {
        $retour[] = array(
            'idConso' => $statut['id'],
            'date' => $statut['date'],
            'nbBike' => $statut['nbBike'],
            'nbEbike' => $statut['nbEBike'],
            'nbFreeEDock' => $statut['nbFreeEDock'],
            'nbEDock' => $statut['nbEDock']
        );
    }
    return array('data' => $retour);
}

function getCommunesCarte($idConso)
{
    global $pdo;

    $dataConso = getDataConso($idConso);
    $statusStation = $dataConso['data'];

    $liste_communes = array();
    $objets = array();

    define('ETAT_INCONNU', 0);
    define('ETAT_TRAVAUX', 1);
    define('ETAT_OUVERTE', 2);

    define('BORNES_NON', 10);
    define('BORNES_OUI', 20);

    $couleur_etat=array(
        BORNES_NON+ETAT_INCONNU=>'mediumvioletred',
        BORNES_NON+ETAT_TRAVAUX=>'darkred',
        BORNES_NON+ETAT_OUVERTE=>'slateblue',
        BORNES_OUI+ETAT_INCONNU=>'indigo',
        BORNES_OUI+ETAT_TRAVAUX=>'darkorange',
        BORNES_OUI+ETAT_OUVERTE=>'darkgreen'
    );

    $nombre_etat=array(
        BORNES_NON+ETAT_INCONNU=>0,
        BORNES_NON+ETAT_TRAVAUX=>0,
        BORNES_NON+ETAT_OUVERTE=>0,
        BORNES_OUI+ETAT_INCONNU=>0,
        BORNES_OUI+ETAT_TRAVAUX=>0,
        BORNES_OUI+ETAT_OUVERTE=>0
    );

    foreach($statusStation as $station)
    {
        //interprétation de l'état de la station 0=état non prévu
        switch($station['state'])
        {
            case 'Work in progress':
                $etat = ETAT_TRAVAUX;
                break;
            case 'Operative': 
                $etat = ETAT_OUVERTE;
                break;
            default: 
                $etat = ETAT_INCONNU;
        }

        //forme en fonction de l'état annoncé
        if($etat == ETAT_INCONNU)
        {
            $s_point='rond';
            $s_taille=6;
            $s_angle=0;
            $s_info1='État inconnu';
        }
        else if($etat == ETAT_TRAVAUX)
        {
            $s_point='triangle';
            $s_taille=8;
            $s_angle=0;
            $s_info1='En travaux';
        }
        else if($etat == ETAT_OUVERTE)
        {
            $s_point='carre';
            $s_taille=6;
            $s_angle=45;
            $s_info1='En service';
        }

        //présence de bornes actives ?
        if($station['nbEDock']!=0)
            $etat+=BORNES_OUI;
        else
            $etat+=BORNES_NON;

        //comptage du nombre de stations par etat
        $nombre_etat[$etat]++;

        //récupération de la commune correspondant à la station (d'après son numéro)
        $inseeCommune = getCommuneStation($station['code']);

        //ajout à la liste des communes
        if(!is_null($inseeCommune) && !in_array($inseeCommune,$liste_communes))
        {
            $liste_communes[] = $inseeCommune;
        }

        //texte en fonction du nombre de bornes
        switch($station['nbEDock'])
        {
            case 0:     $s_info2='aucune borne';
                        break;
            case 1:     $s_info2='1 borne';
                        break;
            default:    $s_info2=$station['nbEDock'].' bornes';
                        break;
        }

        $objets[]=array(
            'nature'=>'point',
            'point'=>$s_point,
            'taille'=>$s_taille,
            'angle'=>$s_angle,
            'couleur'=>$couleur_etat[$etat],
            'lon'=>$station['longitude'],
            'lat'=>$station['latitude'],
            'lien'=>'station.php?code='.$station['code'],
            'info'=>'Station '.sprintf('%05d',$station['code'])."\n".$station['name']."\n".$s_info1.' - '.$s_info2
            );

    }

    //légende (point et texte, affiché uniquement pour les états ayant au moins une station
    if($nombre_etat[BORNES_NON+ETAT_INCONNU])
    {
        $objets[]=array('point'=>'rond','x'=>-58,'y'=>10,'taille'=>6,'angle'=>0,
                        'couleur'=>$couleur_etat[BORNES_NON+ETAT_INCONNU]);
        $objets[]=array('nature'=>'texte','x'=>-60,'y'=>20,'taille'=>12,'angle'=>90,'align'=>'l',
                        'texte'=>'Inconnu sans borne ('.$nombre_etat[BORNES_NON+ETAT_INCONNU].')');
    }
    if($nombre_etat[BORNES_NON+ETAT_TRAVAUX])
    {
        $objets[]=array('point'=>'triangle','x'=>-38,'y'=>10,'taille'=>8,'angle'=>90,
                        'couleur'=>$couleur_etat[BORNES_NON+ETAT_TRAVAUX]);
        $objets[]=array('nature'=>'texte','x'=>-40,'y'=>20,'taille'=>12,'angle'=>90,'align'=>'l',
                        'texte'=>'Stations en travaux ('.$nombre_etat[BORNES_NON+ETAT_TRAVAUX].')');
    }
    if($nombre_etat[BORNES_NON+ETAT_OUVERTE])
    {
        $objets[]=array('point'=>'carre','x'=>-18,'y'=>210,'taille'=>6,'angle'=>45,
                        'couleur'=>$couleur_etat[BORNES_NON+ETAT_OUVERTE]);
        $objets[]=array('nature'=>'texte','x'=>-20,'y'=>220,'taille'=>12,'angle'=>90,'align'=>'l',
                        'texte'=>'En service sans borne ('.$nombre_etat[BORNES_NON+ETAT_OUVERTE].')');
    }
    if($nombre_etat[BORNES_OUI+ETAT_INCONNU])
    {
        $objets[]=array('point'=>'rond','x'=>-58,'y'=>210,'taille'=>6,'angle'=>0,
                        'couleur'=>$couleur_etat[BORNES_OUI+ETAT_INCONNU]);
        $objets[]=array('nature'=>'texte','x'=>-60,'y'=>220,'taille'=>12,'angle'=>90,'align'=>'l',
                        'texte'=>'Inconnu avec bornes ('.$nombre_etat[BORNES_OUI+ETAT_INCONNU].')');
    }
    if($nombre_etat[BORNES_OUI+ETAT_TRAVAUX])
    {
        $objets[]=array('point'=>'triangle','x'=>-38,'y'=>210,'taille'=>8,'angle'=>90,
                        'couleur'=>$couleur_etat[BORNES_OUI+ETAT_TRAVAUX]);
        $objets[]=array('nature'=>'texte','x'=>-40,'y'=>220,'taille'=>12,'angle'=>90,'align'=>'l',
                        'texte'=>'En travaux avec bornes ('.$nombre_etat[BORNES_OUI+ETAT_TRAVAUX].')');
    }
    if($nombre_etat[BORNES_OUI+ETAT_OUVERTE])
    {
        $objets[]=array('point'=>'carre','x'=>-18,'y'=>10,'taille'=>6,'angle'=>45,
                        'couleur'=>$couleur_etat[BORNES_OUI+ETAT_OUVERTE]);
        $objets[]=array('nature'=>'texte','x'=>-20,'y'=>20,'taille'=>12,'angle'=>90,'align'=>'l',
                        'texte'=>'Stations en service ('.$nombre_etat[BORNES_OUI+ETAT_OUVERTE].')');
    }

    $svg = genererCarteSVG(800, 500, $liste_communes, 'commune.php?insee=', '', '', $objets);

    return $svg;
}
?>
