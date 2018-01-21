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
            <li>Nombre de stations ouvertes annoncées (<abbr title="Stations affichées comme étant ouvertes">définition</abbr>) : <?php echo $conso['nbStation']; ?></li>
            <li>Nombre de stations ouvertes détectées (<abbr title="Stations avec un nombre de bornes positifs avec au moins un vélo ou une borne libre">définition</abbr>) : <?php echo $conso['nbStationDetecte']; ?></li>
            <li>Nombre de vélos mécaniques disponible : <?php echo $conso['nbBike']; ?></li>
            <li>Nombre de vélos électriques disponible : <?php echo $conso['nbEbike']; ?></li>
            <li>Nombre de bornes libres : <?php echo $conso['nbFreeEDock']; ?></li>
            <li>Nombre de bornes total : <?php echo $conso['nbEDock']; ?></li>
        </ul>
        <i>Dernière mise à jour : <?php echo $conso['date']; ?></i><br />

		<p>
		<?php

//liste des communes ayant des stations
$liste_communes=array();

//tableaux décrivant les états des stations
$couleur_etat=array('mediumvioletred','darkred','slateblue',10=>'indigo','darkorange','darkgreen');
$nombre_etat=array(0,0,0,10=>0,0,0);
$objets=array();

foreach($statusStation as $station)
	{
//interprétation de l'état de la station 0=état non prévu
//état 0..2 : pas de borne active ; état 10..12 : bornes actives
	switch($station['state'])
		{
		case 'Work in progress': $etat=1; break;
		case 'Operative': $etat=2; break;
		default: echo $station['state']; $etat=0;
		}
	if($station['nbEDock']!=0) $etat+=10;
	$nombre_etat[$etat]++;

//récupération de la commune correspondant à la station (d'après son numéro)
	$res=$pdo->query('SELECT insee FROM tranche WHERE debut<="'.$station['code'].'" AND fin>="'.$station['code'].'"');
	$ligne=$res->fetch();
//ajout à la liste des communes
	if(!in_array($ligne['insee'],$liste_communes))
		{
		$liste_communes[]=$ligne['insee'];
		}

//texte en fonction du nombre de bornes
	switch($station['nbEDock'])
		{
		case 0:		$s_info2='aucune borne';				break;
		case 1:		$s_info2='1 borne';						break;
		default:	$s_info2=$station['nbEDock'].' bornes';	break;
		}

//forme en fonction de l'état annoncé 0=inconnu, 1=WIP, 2=Active
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
			$s_point='carre';
			$s_taille=6;
			$s_angle=45;
			$s_info1='En service';
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
			'info'=>'Station '.sprintf('%05d',$station['code'])."\n".$station['name'].'<br/>'."\n".$s_info1.' - '.$s_info2
			);
	}

$objets[]=array('nature'=>'texte','x'=>400,'y'=>30,'texte'=>'Stations Vélib’','taille'=>24,'couleur'=>'blue','align'=>'m','angle'=>0);

//légende (point et texte, affiché uniquement pour les états ayant au moins une station
if($nombre_etat[12])
	{
	$objets[]=array('point'=>'carre','x'=>-18,'y'=>10,'taille'=>6,'couleur'=>$couleur_etat[12],'angle'=>45);
	$objets[]=array('nature'=>'texte','x'=>-20,'y'=>20,'texte'=>'Stations en service ('.$nombre_etat[12].')','taille'=>12,'angle'=>90,'align'=>'l');
	}
if($nombre_etat[1])
	{
	$objets[]=array('point'=>'triangle','x'=>-38,'y'=>10,'taille'=>8,'couleur'=>$couleur_etat[1],'angle'=>90);
	$objets[]=array('nature'=>'texte','x'=>-40,'y'=>20,'texte'=>'Stations en travaux ('.$nombre_etat[1].')','taille'=>12,'angle'=>90,'align'=>'l');
	}
if($nombre_etat[2])
	{
	$objets[]=array('point'=>'carre','x'=>-18,'y'=>210,'taille'=>6,'couleur'=>$couleur_etat[2],'angle'=>45);
	$objets[]=array('nature'=>'texte','x'=>-20,'y'=>220,'texte'=>'en service sans borne ('.$nombre_etat[2].')','taille'=>12,'angle'=>90,'align'=>'l');
	}
if($nombre_etat[11])
	{
	$objets[]=array('point'=>'triangle','x'=>-38,'y'=>210,'taille'=>8,'couleur'=>$couleur_etat[11],'angle'=>90);
	$objets[]=array('nature'=>'texte','x'=>-40,'y'=>220,'texte'=>'en travaux avec bornes ('.$nombre_etat[11].')','taille'=>12,'angle'=>90,'align'=>'l');
	}
if($nombre_etat[0])
	{
	$objets[]=array('point'=>'rond','x'=>-18,'y'=>410,'taille'=>6,'couleur'=>$couleur_etat[0],'angle'=>45);
	$objets[]=array('nature'=>'texte','x'=>-20,'y'=>420,'texte'=>'état inconnu sans borne ('.$nombre_etat[2].')','taille'=>12,'angle'=>90,'align'=>'l');
	}
if($nombre_etat[10])
	{
	$objets[]=array('point'=>'rond','x'=>-38,'y'=>410,'taille'=>6,'couleur'=>$couleur_etat[10],'angle'=>90);
	$objets[]=array('nature'=>'texte','x'=>-40,'y'=>420,'texte'=>'état inconnu avec bornes ('.$nombre_etat[10].')','taille'=>12,'angle'=>90,'align'=>'l');
	}

//couleurs associées aux communes
$couleur_dept=array(75=>'Khaki',92=>'SkyBlue',93=>'LightCoral',94=>'GreenYellow',
77=>'SkyBlue',91=>'LightCoral',78=>'Khaki',95=>'GreenYellow');

$couleur_commune=array();
foreach($liste_communes AS $commune)
	{
	$dept=floor($commune/1000);
	if(isset($couleur_dept[$dept]))
		$couleur_commune[$commune]=$couleur_dept[$dept];
	}

include('carte.php');

$svg=carte(800,600,$liste_communes,'',array(),$couleur_commune,$objets);

echo $svg;

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
