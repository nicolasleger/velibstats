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
if(is_null($conso['id']))
    $statusStation = array();
else
{
    $requete = $pdo->query('SELECT * FROM status inner join `stations` on stations.code = status.code WHERE idConso = '.$conso['id'].' order by status.code asc');
    $statusStation = $requete->fetchAll();
}
?>
<!DOCTYPE html>
<html>
    <head>
        <title>Vélib Stats (site non officiel)</title>
        <script type="application/javascript" src="Chart.min.js"></script>
        <script type="application/javascript" src="jquery-3.2.1.min.js"></script>
        <link rel="stylesheet" type="text/css" href="datatables.min.css"/>
 
        <script type="text/javascript" src="datatables.min.js"></script>
        <script type="text/javascript" src="script.js"></script>

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
            <li>Nombre de vélos électriques disponible : <?php echo $conso['nbEbike']; ?></li>
            <li>Nombre de bornes libres : <?php echo $conso['nbFreeEDock']; ?></li>
            <li>Nombre de bornes total : <?php echo $conso['nbEDock']; ?></li>
        </ul>
        <i>Dernière mise à jour : <?php echo $conso['date']; ?></i><br />
        <select id="typeGraphiqueSelect" style="display: none">
            <option value="_double">Conso</option>
        </select>
        <select id="dureeGraphiqueSelect">
            <option value="instantanee">Une heure - Instantanée</option>
            <option value="troisHeures">Trois heures - Période de 5 minutes</option>
            <option value="unJour">Un jour - Période de 15 minutes</option>
            <option value="septJours" selected>Une semaine - Période d'une heure</option>
            <option value="unMois">Un mois - Période de six heures</option>
        </select>
        <canvas id="chartNbStations" width="1000" height="400"></canvas>
        <canvas id="chartBikes" width="1000" height="400"></canvas>
        <i>Ce site n'est pas un site officiel de vélib métropole. Les données utilisées proviennent de <a href="http://www.velib-metropole.fr">www.velib-metropole.fr</a> et appartienne à leur propriétaire. - <a href="https://framagit.org/JonathanMM/velibstats">Site du projet</a> - 
        Auteur : JonathanMM (<a href="https://twitter.com/Jonamaths">@Jonamaths</a>)</i>
        <h2>Stations</h2>
        Fitrer : État 
        <select id="filtreEtat">
            <option value="toutes">Toutes</option>
            <option value="ouverte" selected>Ouverte</option>
            <option value="travaux">En travaux</option>
        </select>
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
                    echo '<td>'.(($station['state'] == 'Operative' && $station['nbEDock'] != 0) ? 'Ouverte' : 'En travaux').'</td>';
                    echo '<td>'.$station['nbBike'].'</td>';
                    echo '<td>'.$station['nbEBike'].'</td>';
                    echo '<td>'.$station['nbFreeEDock'].'/'.$station['nbEDock'].'</td>';
                    echo '</tr>';
                }
                ?>
            </tbody>
        </table>
        <script type="text/javascript">
        var codeStation = -1;
        </script>
        <script type="application/javascript">
            function filtreDataTable()
            {
                var valeur = $("#filtreEtat").val();
                var dt = $('#stations').DataTable();
                switch (valeur) {
                    case "ouverte":
                        dt.column(3).search("Ouverte").draw();
                        break;
                    case "travaux":
                        dt.column(3).search("En travaux").draw();
                        break;
                    default:
                        dt.column(3).search("").draw();
                        break;
                }
            }

            $(document).ready( function () {
                var dt = $('#stations').DataTable();
                filtreDataTable();
                $("#filtreEtat").change(filtreDataTable);
            } );
        </script>
    </body>
</html>