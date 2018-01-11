<?php
require_once('config.php');
include_once('functions.php');

//Filtre 24 heures
$hier = new DateTime("-1day");
$filtreDate = $hier->format('Y-m-d H:i:s');

//Dernière conso
$requete = $pdo->query('SELECT * FROM `statusConso` where nbStation Is not null and date >= "'.$filtreDate.'" Order by id desc limit 0,1');
$conso = $requete->fetch();

//Stations
$requete = $pdo->query('SELECT * FROM status inner join `stations` on stations.code = status.code WHERE idConso = '.$conso['id'].' order by status.code asc');
$statusStation = $requete->fetchAll();
?>
<!DOCTYPE html>
<html>
    <head>
        <title>Vélib Stats (site non officiel)</title>
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
        <h1>Vélib Stats (site non officiel)</h1>
        <ul>
            <li>Nombre de stations : <?php echo $conso['nbStation']; ?></li>
            <li>Nombre de vélos mécaniques disponible : <?php echo $conso['nbBike']; ?></li>
            <li>Nombre de vélos électiques disponible : <?php echo $conso['nbEbike']; ?></li>
            <li>Nombre de bornes libres : <?php echo $conso['nbFreeEDock']; ?></li>
        </ul>
        <i>Dernière mise à jour : <?php echo $conso['date']; ?></i>
        <canvas id="chartNbStations" width="1000" height="400"></canvas>
        <canvas id="chartBikes" width="1000" height="400"></canvas>
        <i>Ce site n'est pas un site officiel de vélib métropole. Les données utilisées proviennent de <a href="http://www.velib-metropole.fr">www.velib-metropole.fr</a> et appartienne à leur propriétaire. - <a href="https://framagit.org/JonathanMM/velibstats">Site du projet</a></i>
        <h2>Stations</h2>
        <table id="stations">
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Nom</th>
                    <th>Date d'ouverture</th>
                    <th>Statut</th>
                    <th>Vélos mécaniques dispo</th>
                    <th>Vélos électriques dispo</th>
                    <th>Bornes libres</th>
                </tr>
            </thead>
            <tbody>
                <?php
                foreach($statusStation as $station)
                {
                    echo '<tr>';
                    echo '<td><a href="station.php?code='.$station['code'].'">'.displayCodeStation($station['code']).'</a></td>';
                    echo '<td>'.$station['name'].'</td>';
                    echo '<td>'.$station['dateOuverture'].'</td>';
                    echo '<td>'.(($station['state'] == 'Operative' && $station['nbEDock'] != 0) ? 'OK' : 'KO').'</td>';
                    echo '<td>'.$station['nbBike'].'</td>';
                    echo '<td>'.$station['nbEBike'].'</td>';
                    echo '<td>'.$station['nbFreeEDock'].'/'.$station['nbEDock'].'</td>';
                    echo '</tr>';
                }
                ?>
            </tbody>
        </table>
        <script type="text/javascript">
        <?php
        //On définit les points
        $requete = $pdo->query('SELECT * FROM `statusConso` Where date >= "'.$filtreDate.'" Order by id asc');
        $allConso = $requete->fetchAll();
        $dates = [];
        $nbStationsData = [];
        $nbBikeData = [];
        $nbEbikeData = [];
        $nbFreeEdockData = [];
        foreach($allConso as $i => $c)
        {
            if($c['nbStation'] > 0)
            {
                $dates[] = $c['date'];
                $nbStationsData[] = $c['nbStation'];
                $nbBikeData[] = $c['nbBike'];
                $nbEbikeData[] = $c['nbEbike'];
                $nbFreeEdockData[] = $c['nbFreeEDock'];
            }
        }
        echo 'var datesData = ["'.implode('","', $dates).'"];';
        echo 'var nbStationsData = ['.implode(',', $nbStationsData).'];';
        echo 'var nbBikeData = ['.implode(',', $nbBikeData).'];';
        echo 'var nbEbikeData = ['.implode(',', $nbEbikeData).'];';
        echo 'var nbFreeEdockData = ['.implode(',', $nbFreeEdockData).'];';
        ?>
        </script>
        <script type="application/javascript">
            var chartNbStations = document.getElementById("chartNbStations").getContext('2d');
            var data = {
                labels: datesData,
                datasets: [
                    {
                        backgroundColor : "rgba(173,0,130,0.5)",
                        data : nbStationsData,
                        label: 'Nombre de stations'
                    }
                ]
            };
            var options = {
                responsive: false,
                scales: {
                    yAxes: [{
                        stacked: true
                    }]
                }
            };
            new Chart(chartNbStations, {
                type: 'line',
                data: data,
                options: options
            });

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

            $(document).ready( function () {
                $('#stations').DataTable();
            } );
        </script>
    </body>
</html>