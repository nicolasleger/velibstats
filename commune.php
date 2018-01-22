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

$svg=genererCarteSvg(800,600,$commune,'','',array($commune=>$couleur_commune),$objets);
echo $svg;