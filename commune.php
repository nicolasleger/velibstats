<?php
require_once('config.php');
include_once('functions.php');

//récupération de l'argument "commune", 0 par défaut
if(!isset($_GET['commune'])) $commune=0;
else $commune=intval($_GET['commune']);

//récupération doe la liste des tranches de numéros associés à la commune
$requete=$pdo->query('SELECT debut, fin FROM tranche WHERE insee="'.$commune.'" ORDER BY debut');
$tranches=$requete->fetchAll(PDO::FETCH_ASSOC);

//si aucune tranche n'est trouvée, commune 0
if(!count($tranches))
	{
	$commune=0;
	$requete=$pdo->query('SELECT debut, fin FROM tranche WHERE insee="0" ORDER BY debut');
	$tranches=$requete->fetchAll(PDO::FETCH_ASSOC);
	}

//titre de la page
if($commune==0) echo '<h1>Stations Vélib non associées à une commune</h1>';
else
	{
	$requete=$pdo->query('SELECT nom_complet FROM commune WHERE insee="'.$commune.'"');
	$ligne=$requete->fetch(PDO::FETCH_ASSOC);
	$nom=$ligne['nom_complet'];
	echo '<h1>Stations Vélib — Commune '.$commune.' — '.$nom.'</h1>';
	}

//Filtre 24 heures
$hier = new DateTime("-1day");
$filtreDate = $hier->format('Y-m-d H:i:s');

//Dernière conso
$requete = $pdo->query('SELECT * FROM `statusConso` where nbStation Is not null and date >= "'.$filtreDate.'" Order by id desc limit 0,1');
$conso = $requete->fetch();

$liste_stations=array();
//récupération des stations de chaque tranche
foreach($tranches AS $tr)
	{
	echo '<h2>Stations dans la tranche '.$tr['debut'].' à '.$tr['fin'].'</h2>';
	$requete=$pdo->query('SELECT stations.code, name, latitude, longitude, state, nbBike, nbEBike, nbFreeEDock, nbEDock, adresse FROM status inner join `stations` on stations.code = status.code WHERE idConso = '.$conso['id'].' AND stations.code BETWEEN "'.$tr['debut'].'" AND "'.$tr['fin'].'" ORDER BY stations.code');
	$resultat=$requete->fetchAll(PDO::FETCH_ASSOC);

//s'il y a des stations, affichage des infos sous forme de tableau
	if(count($resultat))
		{
		echo '<table border="1"><tr><th>Station</th><th>Nom</th><th>Latitude</th><th>Longitude</th><th>État</th><th>Vélos</th><th>VAE</th><th>Libres</th><th>Bornes</th><th>Adresse</th></tr>';
		foreach($resultat AS $ligne)
			{
			echo '<tr>';
			foreach ($ligne AS $value)
				{
				echo '<td>'.$value.'</td>';
				}
			echo '</tr>';
//ajout de la station à la liste;
			$liste_stations[$ligne['code']]=$ligne;
			}
		echo '</table>';
		}
	else echo '<p>Aucune station dans cette tranche</p>';
	}

$couleur_dept=array(75=>'Khaki',92=>'SkyBlue',93=>'LightCoral',94=>'GreenYellow',
77=>'SkyBlue',91=>'LightCoral',78=>'Khaki',95=>'GreenYellow');

$dept=floor($commune/1000);
if(isset($couleur_dept[$dept]))	$couleur_commune=$couleur_dept[$dept];
else $couleur_commune='gray';

$couleur_etat=array('mediumvioletred','darkred','slateblue',10=>'indigo','darkorange','darkgreen');
$objets=array();

foreach($liste_stations AS $station)
	{
	switch($station['state'])
		{
		case 'Work in progress': $etat=1; break;
		case 'Operative': $etat=2; break;
		default: echo $station['state']; $etat=0;
		}
	if($station['nbEDock']!=0) $etat+=10;

//texte en fonction du nombre de bornes
	switch($station['nbEDock'])
		{
		case 0:		$s_info2='aucune borne';				break;
		case 1:		$s_info2='1 borne';						break;
		default:	$s_info2=$station['nbEDock'].' bornes';	break;
		}

//forme en fonction de l'état annoncé 0=inconnu, 1=WIP, 2=Active
	$s_pie='';
	$s_info3='';
	switch($etat)
		{
		case 0:
		case 10:
			$s_point='rond';
			$s_taille=6;
			$s_angle=0;
			$s_info1='État inconnu';
			break;
		case 1:
		case 11:
			$s_point='triangle';
			$s_taille=8;
			$s_angle=0;
			$s_info1='En travaux';
			break;
		case 2:
		case 12:
			if($station['nbEDock']) 
				{
//si station ouverte avec bornes, diagramme en camembert
				$s_point='pie';
				$s_pie=array($station['nbEDock'],$station['nbBike'],$station['nbEBike'],$station['nbFreeEDock']);
//la taille dépend du nombre de bornes
				$s_taille=6*sqrt($station['nbEDock']);
//ajout dans l'info-bulle du nombre de places par nature
				if($station['nbBike']>1) $s_info3.="\n".$station['nbBike'].' vélos disponibles';
				else if($station['nbBike']==1) $s_info3.="\n".$station['nbBike'].' vélo disponible';

				if($station['nbEBike']>1) $s_info3.="\n".$station['nbEBike'].' électriques disponibles';
				else if($station['nbEBike']==1) $s_info3.="\n".$station['nbEBike'].' électrique disponible';

				if($station['nbFreeEDock']>1) $s_info3.="\n".$station['nbFreeEDock'].' places libres';
				else if($station['nbFreeEDock']==1) $s_info3.="\n".$station['nbFreeEDock'].' place libre';
				}
			else
				{
				$s_point='carre';
				$s_taille=6;
				}
			$s_angle=45;
			$s_info1='En service';
			break;
		}

	$objets[]=array(
			'nature'=>'point',
			'point'=>$s_point,
			'pie'=>$s_pie,
			'taille'=>$s_taille,
			'angle'=>$s_angle,
			'couleur'=>$couleur_etat[$etat],
			'lon'=>$station['longitude'],
			'lat'=>$station['latitude'],
			'lien'=>'station.php?code='.$station['code'],
			'info'=>'Station '.sprintf('%05d',$station['code'])."\n".$station['name']."\n".$s_info1.' - '.$s_info2.$s_info3
			);
	}

include('carte.php');
$svg=carte(800,600,$commune,'','',array($commune=>$couleur_commune),$objets);
echo $svg;


/*
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
            <li>Nombre de stations ouvertes annoncées (<abbr title="Stations affichées comme étant ouverte">définition</abbr>) : <?php echo $conso['nbStation']; ?></li>
            <li>Nombre de stations ouvertes détectées (<abbr title="Stations avec un nombre de bornes positifs avec au moins un vélo ou une borne libre">définition</abbr>) : <?php echo $conso['nbStationDetecte']; ?></li>
            <li>Nombre de vélos mécaniques disponible : <?php echo $conso['nbBike']; ?></li>
            <li>Nombre de vélos électriques disponible : <?php echo $conso['nbEbike']; ?></li>
            <li>Nombre de bornes libres : <?php echo $conso['nbFreeEDock']; ?></li>
            <li>Nombre de bornes total : <?php echo $conso['nbEDock']; ?></li>
        </ul>
        <i>Dernière mise à jour : <?php echo $conso['date']; ?></i><br />

		<p>
		<?php
		include('carte.php');
		carte(800,600,array(),array(),0,'a');
		?>
		</p>

        <select id="typeGraphiqueSelect" style="display: none">
            <option value="_double">Conso</option>
        </select>
        <select id="dureeGraphiqueSelect">
            <option value="instantanee">Une heure - Instantanée</option>
            <option value="troisHeures">Trois heures - Période de 5 minutes</option>
            <option value="unJour">Un jour - Période de 15 minutes</option>
            <option value="septJours" selected>Une semaine - Période d'une heure</option>
            <option value="unMois">Un mois - Période de six heures</option>
        </select> Graphique issu du site velib.nocle.fr
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
                var dt = $('#stations').DataTable({
                    language: dtTraduction
                });
                filtreDataTable();
                $("#filtreEtat").change(filtreDataTable);
            } );
        </script>
    </body>
</html>
*/
