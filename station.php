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

//Stations
$requete = $pdo->query('SELECT c.date, s.nbBike, s.nbEBike, s.nbFreeEDock, s.nbEDock 
FROM status s 
INNER JOIN statusConso c ON c.id = s.idConso 
WHERE s.code = '.$code.' AND c.date >= "'.$filtreDate.'" 
ORDER BY s.code ASC');
$statusStation = $requete->fetchAll();

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
?>
<!DOCTYPE html>
<html>
    <head>
        <title>Vélib Stats → Station (site non officiel)</title>
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
        <nav>
            <a href="index.php">Retour à l'acceuil</a>
        </nav>
        <h1>Vélib Stats (site non officiel) - Station <?php echo displayCodeStation($code); ?></h1>
        <ul>
            <li>Nom : <?php echo $station['name']; ?></li>
            <li>Date d'ouverture : <?php echo $station['dateOuverture']; ?></li>
            <li>Adresse la plus proche (selon <a href="https://adresse.data.gouv.fr/">BAN</a>) : <?php echo $station['adresse']; ?></li>
            <li>Signalement des utilisateurs sur l'état de la station (dernière semaine) : 
            <?php
            if(array_sum($resumeSignalement) == 0)
                echo 'Aucun';
            else
            {
                if($resumeSignalement[true] > 0)
                    echo 'Fonctionne = '.$resumeSignalement[true].'; ';
                if($resumeSignalement[false] > 0)
                    echo 'Ne fonctionne pas = '.$resumeSignalement[false].'; ';
                echo 'Dernier signalement : '.($signalements[0]['estFonctionnel'] == 1 ? 'Fonctionne' : 'Ne fonctionne pas').' à '.(new DateTime($signalements[0]['dateSignalement']))->format('d/m H:i');
            }
            ?>
            <button id="boutonFonctionneOui">La station fonctionne</button>
            <button id="boutonFonctionneNon">La station ne fonctionne pas</button>
            </li>
        </ul>
        <h2>Graphique</h2>
        <select id="typeGraphiqueSelect">
            <option value="Bike">Vélos disponibles</option>
            <option value="FreeDock">Bornes libres</option>
        </select>
        <select id="dureeGraphiqueSelect">
            <option value="instantanee">Une heure - Instantanée</option>
            <option value="troisHeures">Trois heures - Période de 5 minutes</option>
            <option value="unJour" selected>Un jour - Période de 15 minutes</option>
            <option value="septJours">Une semaine - Période d'une heure</option>
            <option value="unMois">Un mois - Période de six heures</option>
        </select>
        <span id="displayDetailsArea"><input type="checkbox" id="displayDetails" /><label for="displayDetails">Afficher les détails</label></span>
        <canvas id="chartBikes" width="1000" height="400"></canvas>
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
                foreach($statusStation as $statut)
                {
                    echo '<tr>';
                    echo '<td>'.$statut['date'].'</td>';
                    echo '<td>'.$statut['nbBike'].'</td>';
                    echo '<td>'.$statut['nbEBike'].'</td>';
                    echo '<td>'.$statut['nbFreeEDock'].'/'.$statut['nbEDock'].'</td>';
                    echo '</tr>';
                }
                ?>
            </tbody>
        </table>
        <script type="text/javascript">
        <?php
        echo 'var codeStation = '.$code.';'."\n";
        ?>
        </script>
        <script type="application/javascript">
            $(document).ready( function () {
                $('#stats').DataTable({
                    language: dtTraduction
                });
            } );
        </script>
    </body>
</html>