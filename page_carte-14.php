<?php
html_entete('Carte','red');																					//fonction qui appelle tout ce qui va bien pour initialiser la page (liaison BDD, balises HTML jusqu'à <body>, 

/*arguments :
largeur_carte 		:	nombre de pixels de largeur de l'image SVG à générer
hauteur_carte 		:	nombre de pixels de hauteur de l'image SVG à générer
couleur				:	tableau associatif : numéro INSEE de la commune => couleur de la commune
texte				:	tableau associatif : numéro INSEE de la commune => texte additionnel dans l'info-bulle
filtre				:	mode d'affichage : 0 = tout, 01 à 99 = numéro de département, 100 à 999 = epic ; 1000 et + : code insee de commune
titre				:	titre du graphique : tableaux de tableaux, chaque élément comporte 4 paramètres :
														coordonnée du bord gauche du texte (en pixels depuis le bord gauche de la carte)
														coordonnée de la ligne de base du texte (en pixels depuis le bord supérieur de la carte)
														texte à afficher
														taille du texte en pixels
*/

function carte($largeur_carte,$hauteur_carte,$couleur=array(),$texte=array(),$filtre=0,$titre=array())
	{
																												//récupération des coordonnées MIN et MAX des communes (en 1/10 000 000 de degré)
	if($filtre==0)				$resultat=REQ('SELECT MIN(ouest) AS O, MAX(est) AS E, MAX(nord) AS N, MIN(sud) AS S FROM commune');										//pas de filtre
	else if($filtre<=100)		$resultat=REQ('SELECT MIN(ouest) AS O, MAX(est) AS E, MAX(nord) AS N, MIN(sud) AS S FROM commune WHERE dept="'.$filtre.'"');			//filtre sur un département
	else if($filtre<=1000)		$resultat=REQ('SELECT MIN(ouest) AS O, MAX(est) AS E, MAX(nord) AS N, MIN(sud) AS S FROM commune WHERE etab+100="'.$filtre.'"');		//filtre sur un epic
	else 						$resultat=REQ('SELECT MIN(ouest) AS O, MAX(est) AS E, MAX(nord) AS N, MIN(sud) AS S FROM commune WHERE insee="'.$filtre.'"');			//filtre sur une commune

	if($resultat[0]['O']=='')																					//si le filtre renvoie une table vide, affichuge de toute la carte
		{
		$filtre=0;
		$resultat=REQ('SELECT MIN(ouest) AS O, MAX(est) AS E, MAX(nord) AS N, MIN(sud) AS S FROM commune');
		}

	$centreOE=($resultat[0]['O']+$resultat[0]['E'])/2e7;														//calcul des coordonnées du centre de la carte (en degrés)
	$centreNS=($resultat[0]['N']+$resultat[0]['S'])/2e7;

	$km_lat=20003.932/180;																						//écart en km entre deux degrés de latitude
	$km_lon=$km_lat*cos(deg2rad($centreNS));																	//écart en km entre deux degrés de longitude (utilisation de la latitude du centre de la carte)

	$largeur_d=($resultat[0]['E']-$resultat[0]['O'])/1e7;														//calcul des dimensions minimales de la carte (en degrés)
	$hauteur_d=($resultat[0]['N']-$resultat[0]['S'])/1e7;

	$largeur_k=$largeur_d*$km_lon;																				//calcul des dimensions minimales de la carte (en km)
	$hauteur_k=$hauteur_d*$km_lat;

	$pixels_par_km_lon=$largeur_carte/$largeur_k;																//calcul du nombre de pixels utilisés pour afficher un km2
	$pixels_par_km_lat=$hauteur_carte/$hauteur_k;
	$pixels_par_km=min($pixels_par_km_lon,$pixels_par_km_lat)*0.95;												//l'affichage est calculé selon la coordonnée la moins précise (+ une marge de 2,5% autour de la carte)

	$largeur_k=$largeur_carte/$pixels_par_km;																	//calcul des dimensions réelles de la carte (en km)
	$hauteur_k=$hauteur_carte/$pixels_par_km;

	$largeur_d=$largeur_k/$km_lon;																				//calcul des dimensions réelles de la carte (en degrés)
	$hauteur_d=$hauteur_k/$km_lat;

	$largeur_f=$largeur_carte/$largeur_d/1e3;																	//calcul des facteurs de mise à l'échelle
	$hauteur_f=-$hauteur_carte/$hauteur_d/1e3;																	//plus retournement vertical (Nord en haut)

	$dec_lon=-($centreOE-$largeur_d/2)*1000;																	//calcul du décalage de l'affichage
	$dec_lat=-($centreNS+$hauteur_d/2)*1000;

	$carte_ouest=($centreOE-$largeur_d/2)*1e7;																	//détermination des coordonnées extrèmes de la carte (pour recherche des communes voisines y figurant)
	$carte_est=($centreOE+$largeur_d/2)*1e7;
	$carte_nord=($centreNS+$hauteur_d/2)*1e7;
	$carte_sud=($centreNS-$hauteur_d/2)*1e7;

																												//génération de la carte SVG avec la taille prédéfinie
	echo '<svg id="carte" xmlns="http://www.w3.org/2000/svg" version="1.1" width="'.$largeur_carte.'" height="'.$hauteur_carte.'" viewBox="0 0 '.$largeur_carte.' '.$hauteur_carte.'">';
	echo "\n";
	echo '<style type="text/css">';
	echo 'a:focus { outline-style: none;}';																		//désactivation de la ligne en pointillés autour de la zone cliquée
	echo 'path:hover {fill-opacity:1;}';																			//la zone survolée se colore en ble
	echo '</style>';
	echo "\n";

	echo '<rect x="0" y="0" width="'.$largeur_carte.'" height="'.$hauteur_carte.'" fill="white"/>';

	echo '<g transform="scale('.$largeur_f.','.$hauteur_f.') translate('.$dec_lon.','.$dec_lat.')">';			//transformation des coordonnées géographiques (1/1000°) en coordonnées d'affichage
	echo "\n";

	echo '<g style="stroke:black; stroke-width:0.2; fill-opacity:0.5;">';										//formatage commun à toutes les communes
	echo "\n";
																												//récupération des données des communes (et des communes limitrophes)
	$resultat=REQ('SELECT * FROM commune WHERE ouest<="'.$carte_est.'" AND est>="'.$carte_ouest.'" AND nord>="'.$carte_sud.'" AND sud<="'.$carte_nord.'"');

	foreach($resultat AS $res=>$ligne)																			//détermination si les communes sont dans le filtre ou pas
		{
		if($filtre==0)				$resultat[$res]['in']=1;
		else if($filtre<=100)		{ if($ligne['dept']==$filtre)		$resultat[$res]['in']=1; else $resultat[$res]['in']=0;	}
		else if($filtre<=1000)		{ if($ligne['etab']+100==$filtre)	$resultat[$res]['in']=1; else $resultat[$res]['in']=0;	}
		else						{ if($ligne['insee']==$filtre)		$resultat[$res]['in']=1; else $resultat[$res]['in']=0;	}
		}

	foreach($resultat AS $res=>$ligne)	if($ligne['in']==0)														//affichage en premier des communes hors filtre (arrière-plan)
		{
		echo '<a xlink:href="?page=communes&amp;action=2&amp;insee='.$ligne['insee'].'_'.$ligne['nom'].'">';
		echo '<path d="M'.$ligne['contour'].'Z" style="stroke:white; ';											//contour de la commune en blanc

		if(isset($couleur[$ligne['insee']]))	echo 'fill:'.$couleur[$ligne['insee']].'; "/>';			//coloration de la commune en fonction de l'établissement
		else									echo 'fill:#dddddd;"/>';

		if(isset($texte[$ligne['insee']])) echo '<title>'.$ligne['nom_complet'].' '.$texte[$ligne['insee']].'</title>';						//affichage du nom de la commune en infobulle
		else echo '<title>'.$ligne['nom_complet'].'</title>';														//affichage du nom de la commune en infobulle

		echo '</a>'."\n";
		}

	foreach($resultat AS $res=>$ligne)	if($ligne['in']==1)														//affichage en dernier des communes dans le filtre (avant-plan)
		{
		echo '<a xlink:href="?page=communes&amp;action=2&amp;insee='.$ligne['insee'].'_'.$ligne['nom'].'">';
		echo '<path d="M'.$ligne['contour'].'Z" style="';

		if(isset($couleur[$ligne['insee']]))	echo 'fill:'.$couleur[$ligne['insee']].'; "/>';			//coloration de la commune en fonction de l'établissement
		else									echo 'fill:#dddddd;"/>';

		if(isset($texte[$ligne['insee']])) echo '<title>'.$ligne['nom_complet'].' '.$texte[$ligne['insee']].'</title>';						//affichage du nom de la commune en infobulle
		else echo '<title>'.$ligne['nom_complet'].'</title>';														//affichage du nom de la commune en infobulle
		echo '</a>'."\n";
		}
	echo '</g>';
	echo '</g>';

	foreach($titre AS $T)																				//affichage des titres de la carte
		{
		echo '<text x="'.$T[0].'" y="'.$T[1].'" style="font-family:Arial; font-size:'.$T[3].'px;">'.$T[2].'</text>';
		}

	echo '</svg> ';
	}


//exemples d'utilisation de la fonction

$couleur_etab=array(0=>'#888888',1=>'#6887B3',2=>'#AF732A',3=>'#4EB26E',4=>'#AE335B',5=>'#EE7E70',6=>'#A2CC84',7=>'#3797D3',8=>'#E74B3D',9=>'#C89D67',10=>'#F39D1F',11=>'#577364',12=>'#905CA1');
$coul=array();
$texte=array();
$resultat=REQ('SELECT insee, dept, etab FROM commune');
foreach($resultat AS $ligne)
	{
	$texte[$ligne['insee']]='['.$ligne['dept'].']';
	if(isset($couleur_etab[$ligne['etab']])) $coul[$ligne['insee']]=$couleur_etab[$ligne['etab']];
	else $coul[$ligne['insee']]=$couleur_etab[0];
	}
carte(800,600,$coul,$texte,75101);
carte(400,300,$coul,$texte,0,array(array(16,24,'Métropole du Grand Paris',16),array(16,40,'Établissements',12),array(16,53,'publics',12),array(16,66,'territoriaux',12)));
carte(400,300,$coul,$texte,75,array(array(16,24,'Paris',16)));

$coul=array();
$texte=array();
$resultat=REQ('SELECT insee, dept, etab FROM commune');
foreach($resultat AS $ligne)
	{
	$texte[$ligne['insee']]='['.$ligne['dept'].']';
	if(isset($couleur_etab[$ligne['dept']-90])) $coul[$ligne['insee']]=$couleur_etab[$ligne['dept']-90];
	else $coul[$ligne['insee']]=$couleur_etab[7];
	}
carte(400,300,$coul,$texte,94,array(array(156,292,'Val-de-Marne',16)));
carte(400,300,$coul,$texte,0,array(array(16,24,'Métropole du Grand Paris',16),array(16,40,'Départements',12)));

html_bas(0);																								//fonction ferme la BDD et insère des données de bas de page, de fin de fichier HTML, …)
