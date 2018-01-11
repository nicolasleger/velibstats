<?php
require_once('config.php');
include_once('functions.php');

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

//Filtre 24 heures
$hier = new DateTime("-1day");
$filtreDate = $hier->format('Y-m-d H:i:s');

//Filtre 1 heure
$heurePrecedente = new DateTime("-3hours");
$filtreHeure = $heurePrecedente->format('Y-m-d H:i:s');

//Stations
$requete = $pdo->query('SELECT c.date, s.nbBike, s.nbEBike, s.nbFreeEDock, s.nbEDock 
FROM status s 
INNER JOIN statusConso c ON c.id = s.idConso 
WHERE s.code = '.$code.' AND c.date >= "'.$filtreDate.'" 
ORDER BY s.code ASC');
$statusStation = $requete->fetchAll();

//Resume
$requete = $pdo->query('SELECT `date`, nbBikeMin, nbBikeMax, nbBikeMoyenne, nbBikePris, nbBikeRendu, nbEBikeMin, nbEBikeMax, nbEBikeMoyenne, nbEBikePris, nbEBikeRendu
FROM resumeStatus 
WHERE code = '.$code.' AND `date` >= "'.$filtreHeure.'"
ORDER BY date ASC');
$resumeStatusStation = $requete->fetchAll();
?>
<!DOCTYPE html>
<html>
    <head>
        <title>Vélib Stats → Station (site non officiel)</title>
        <script type="application/javascript" src="Chart.min.js"></script>
        <script type="application/javascript" src="jquery-3.2.1.min.js"></script>

        <link rel="stylesheet" type="text/css" href="datatables.min.css"/>
 
        <script type="text/javascript" src="datatables.min.js"></script>

        <style type="text/css">
        table, tr, td, th
        {
            border: 1px solid black;
        }

        td
        {
            text-align: center;
        }
        </style>
    </head>
    <body>
        <nav>
            <a href="index.php">Retour à l'acceuil</a>
        </nav>
        <h1>Vélib Stats (site non officiel) - Station <?php echo displayCodeStation($code); ?></h1>
        <ul>
            <li>Nom : <?php echo $station['name']; ?></li>
            <li>Date d'ouverture : <?php echo $station['dateOuverture']; ?></li>
            <li>Adresse la plus proche (selon <a href="https://adresse.data.gouv.fr/">BAN</a>) : <?php echo $station['adresse']; ?></li>
        </ul>
        <h2>Graphiques temps réel</h2>
        <canvas id="chartBikes" width="1000" height="400"></canvas>
        <canvas id="chartBornesLibres" width="1000" height="400"></canvas>
        <h2>Graphiques résumé (en test)</h2>
        <canvas id="chartBikesResume" width="1000" height="400"></canvas>
        <i>Ce site n'est pas un site officiel de vélib métropole. Les données utilisées proviennent de <a href="http://www.velib-metropole.fr">www.velib-metropole.fr</a> et appartienne à leur propriétaire. - <a href="https://framagit.org/JonathanMM/velibstats">Site du projet</a></i>
        <h2>Stats</h2>
        <table id="stats">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Vélos mécaniques dispo</th>
                    <th>Vélos électriques dispo</th>
                    <th>Bornes libres</th>
                </tr>
            </thead>
            <tbody>
                <?php
                //On définie les points pour les graphs
                $dates = [];
                $nbBikeData = [];
                $nbEbikeData = [];
                $nbFreeEdockData = [];
                foreach($statusStation as $statut)
                {
                    echo '<tr>';
                    echo '<td>'.$statut['date'].'</td>';
                    echo '<td>'.$statut['nbBike'].'</td>';
                    echo '<td>'.$statut['nbEBike'].'</td>';
                    echo '<td>'.$statut['nbFreeEDock'].'/'.$statut['nbEDock'].'</td>';
                    echo '</tr>';

                    $dates[] = $statut['date'];
                    $nbBikeData[] = $statut['nbBike'];
                    $nbEbikeData[] = $statut['nbEBike'];
                    $nbFreeEdockData[] = $statut['nbFreeEDock'];
                }

                //Resume
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
                ?>
            </tbody>
        </table>
        <script type="text/javascript">
        <?php
        echo 'var datesData = ["'.implode('","', $dates).'"];'."\n";
        echo 'var nbBikeData = ['.implode(',', $nbBikeData).'];'."\n";
        echo 'var nbEbikeData = ['.implode(',', $nbEbikeData).'];'."\n";
        echo 'var nbFreeEdockData = ['.implode(',', $nbFreeEdockData).'];'."\n";

        echo 'var datesResumeData = ["'.implode('","', $datesResume).'"];'."\n";
        echo 'var nbBikeMinData = ['.implode(',', $nbBikeMinData).'];'."\n";
        echo 'var nbBikeMaxData = ['.implode(',', $nbBikeMaxData).'];'."\n";
        echo 'var nbBikeMoyenneData = ['.implode(',', $nbBikeMoyenneData).'];'."\n";
        echo 'var nbBikePrisData = ['.implode(',', $nbBikePrisData).'];'."\n";
        echo 'var nbBikeRenduData = ['.implode(',', $nbBikeRenduData).'];'."\n";
        echo 'var nbEBikeMinData = ['.implode(',', $nbEBikeMinData).'];'."\n";
        echo 'var nbEBikeMaxData = ['.implode(',', $nbEBikeMaxData).'];'."\n";
        echo 'var nbEBikeMoyenneData = ['.implode(',', $nbEBikeMoyenneData).'];'."\n";
        echo 'var nbEBikePrisData = ['.implode(',', $nbEBikePrisData).'];'."\n";
        echo 'var nbEBikeRenduData = ['.implode(',', $nbEBikeRenduData).'];'."\n";
        ?>
        </script>
        <script type="application/javascript">
            var options = {
                responsive: false,
                scales: {
                    yAxes: [{
                        stacked: true
                    }]
                }
            };

            var chartBikes = document.getElementById("chartBikes").getContext('2d');
            var dataBike = {
                labels: datesData,
                datasets: [
                    {
                        backgroundColor : "rgba(104,221,46,0.5)",
                        data : nbBikeData,
                        label: 'Vélos mécaniques'
                    },
                    {
                        backgroundColor : "rgba(76, 213, 233, 0.5)",
                        data : nbEbikeData,
                        label: 'Vélos électriques'
                    }
                ]
            };

            new Chart(chartBikes, {
                type: 'line',
                data: dataBike,
                options: options
            });

            var chartBornesLibres = document.getElementById("chartBornesLibres").getContext('2d');
            var data = {
                labels: datesData,
                datasets: [
                    {
                        backgroundColor : "rgba(173,0,130,0.5)",
                        data : nbFreeEdockData,
                        label: 'Nombre de bornes libres'
                    }
                ]
            };
            new Chart(chartBornesLibres, {
                type: 'line',
                data: data,
                options: options
            });

            var chartBikesResume = document.getElementById("chartBikesResume").getContext('2d');

            var optionsResume = {
                responsive: false,
                scales: {
                    yAxes: [{
                        stacked: false
                    }]
                }
            };
            var dataResume = {
                labels: datesResumeData,
                datasets: [
                    {
                        type: 'line',
                        borderColor : "rgba(104,221,46,0.7)",
                        data : nbBikeMoyenneData,
                        fill: false,
                        label: 'Vélos mécaniques (Moyenne)'
                    },
                    {
                        type: 'line',
                        borderColor : "rgba(104,221,46,0)",
                        backgroundColor : "rgba(104,221,46,0.3)",
                        data : nbBikeMinData,
                        fill: "+1",
                        borderDash: [5, 5],
                        label: 'Vélos mécaniques (Min)'
                    },
                    {
                        type: 'line',
                        borderColor : "rgba(104,221,46,0)",
                        backgroundColor : "rgba(104,221,46,0.3)",
                        fill: false,
                        data : nbBikeMaxData,
                        borderDash: [5, 5],
                        label: 'Vélos mécaniques (Max)'
                    },
                    {
                        backgroundColor : "rgba(104,221,46,0.5)",
                        data : nbBikePrisData,
                        label: 'Vélos mécaniques (Pris)'
                    },
                    {
                        backgroundColor : "rgba(104,221,46,0.5)",
                        data : nbBikeRenduData,
                        label: 'Vélos mécaniques (Rendu)'
                    },
                    
                    {
                        type: 'line',
                        borderColor : "rgba(76, 213, 233, 0.5)",
                        data : nbEBikeMoyenneData,
                        fill: false,
                        label: 'Vélos électriques (Moyenne)'
                    },
                    {
                        type: 'line',
                        borderColor : "rgba(76, 213, 233, 0)",
                        backgroundColor : "rgba(76, 213, 233, 0.3)",
                        data : nbEBikeMinData,
                        fill: "+1",
                        borderDash: [5, 5],
                        label: 'Vélos électriques (Min)'
                    },
                    {
                        type: 'line',
                        borderColor : "rgba(76, 213, 233, 0)",
                        backgroundColor : "rgba(76, 213, 233, 0.3)",
                        fill: false,
                        data : nbEBikeMaxData,
                        borderDash: [5, 5],
                        label: 'Vélos électriques (Max)'
                    },
                    {
                        backgroundColor : "rgba(76, 213, 233, 0.5)",
                        data : nbEBikePrisData,
                        label: 'Vélos électriques (Pris)'
                    },
                    {
                        backgroundColor : "rgba(76, 213, 233, 0.5)",
                        data : nbEBikeRenduData,
                        label: 'Vélos électriques (Rendu)'
                    }
                ]
            };
            new Chart(chartBikesResume, {
                type: 'bar',
                data: dataResume,
                options: optionsResume
            });

            $(document).ready( function () {
                $('#stats').DataTable({
                    language: {
                        "sProcessing":     "Traitement en cours...",
                        "sSearch":         "Rechercher&nbsp;:",
                        "sLengthMenu":     "Afficher _MENU_ &eacute;l&eacute;ments",
                        "sInfo":           "Affichage de l'&eacute;l&eacute;ment _START_ &agrave; _END_ sur _TOTAL_ &eacute;l&eacute;ments",
                        "sInfoEmpty":      "Affichage de l'&eacute;l&eacute;ment 0 &agrave; 0 sur 0 &eacute;l&eacute;ment",
                        "sInfoFiltered":   "(filtr&eacute; de _MAX_ &eacute;l&eacute;ments au total)",
                        "sInfoPostFix":    "",
                        "sLoadingRecords": "Chargement en cours...",
                        "sZeroRecords":    "Aucun &eacute;l&eacute;ment &agrave; afficher",
                        "sEmptyTable":     "Aucune donn&eacute;e disponible dans le tableau",
                        "oPaginate": {
                            "sFirst":      "Premier",
                            "sPrevious":   "Pr&eacute;c&eacute;dent",
                            "sNext":       "Suivant",
                            "sLast":       "Dernier"
                        },
                        "oAria": {
                            "sSortAscending":  ": activer pour trier la colonne par ordre croissant",
                            "sSortDescending": ": activer pour trier la colonne par ordre d&eacute;croissant"
                        }
                    }
                });
            } );
        </script>
    </body>
</html>