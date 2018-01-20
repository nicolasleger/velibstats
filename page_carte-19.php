<?php
html_entete('Carte','red');																					//fonction qui appelle tout ce qui va bien pour initialiser la page (liaison BDD, balises HTML jusqu'à <body>, 

/*arguments :
largeur_carte 		:	nombre de pixels de largeur de l'image SVG à générer
hauteur_carte 		:	nombre de pixels de hauteur de l'image SVG à générer
couleur				:	tableau associatif : numéro INSEE de la commune => couleur de la commune
texte				:	tableau associatif : numéro INSEE de la commune => texte additionnel dans l'info-bulle
filtre				:	communes affichées : 0 = tout, 01 à 99 = numéro de département, 100 à 999 = epic ; 1000 et + : code insee de commune ou tableau contenant la liste des numéros INSEE
liens				:	format des liens vers lesquels pointe chaque tracé de commune (le code commune et son nom sont ajoutés à la fin)
objets				:	liste d'objets à ajouter à la carte (chaque objet est un tableau associatif)

		arguments communs :
						nature : type d'objet à ajouter : texte, point, …
						x : position de l'objet en pixels (positif ou nul : depuis le bord gauche / négatif : depuis le bord droit)
						y : position de l'objet en pixels (positif ou nul : depuis le bord supérieur / négatif : depuis le bord inférieur)
						lon : longitude de l'objet en degrés (négatif à l'Ouest, positif à l'Est), ignoré si la position x est définie
						lat : latitude de l'objet en degrés (négatif au Sud, positif au Nord), ignoré si la position y est définie
						taille : taille de l'objet en pixels
						angle : angle de rotation de l'objet
						couleur : couleur de l'objet
						lien : lien auquel renvoie un clic sur l'objet
						info : infobulle associée à l'objet (seulement si un lien est défini)

		arguments spécifiques à "texte" :
						texte : texte à afficher
						align : alignement : l (gauche, par défaut), m (milieu) ou r (droite)

		arguments spécifiques à "point" :
						point : type de point : rond, carré, triangle, …
*/

function carte($largeur_carte=800,$hauteur_carte=600,$couleur=array(),$texte=array(),$filtre=0,$lien='',$objets=array())
	{
																												//récupération des coordonnées MIN et MAX des communes (en 1/10 000 000 de degré)
	if(is_array($filtre))
		{
		$liste='';
		foreach($filtre AS $E=>$F)																																		//liste de communes
			{
			if($E) $liste.=',';
			$liste.='"'.floor($F).'"';
			}
		$resultat=REQ('SELECT MIN(ouest) AS O, MAX(est) AS E, MAX(nord) AS N, MIN(sud) AS S FROM commune WHERE insee IN ('.$liste.')');
		}
	else if($filtre==0)			$resultat=REQ('SELECT MIN(ouest) AS O, MAX(est) AS E, MAX(nord) AS N, MIN(sud) AS S FROM commune');										//pas de filtre
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

	$stroke=round(pow($hauteur_f*$hauteur_f,-0.4)/2,4);															//épaisseur des traits (en fontion du zoom)

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
	echo '<g style="stroke:black; stroke-width:'.$stroke.'; fill-opacity:0.5;">';										//formatage commun à toutes les communes
	echo "\n";
																												//récupération des données des communes (et des communes limitrophes)
	$resultat=REQ('SELECT * FROM commune WHERE ouest<="'.$carte_est.'" AND est>="'.$carte_ouest.'" AND nord>="'.$carte_sud.'" AND sud<="'.$carte_nord.'"');

	foreach($resultat AS $res=>$ligne)																			//détermination si les communes sont dans le filtre ou pas
		{
		if(is_array($filtre))		{ if(in_array($ligne['insee'],$filtre))	$resultat[$res]['in']=1; else $resultat[$res]['in']=0;	}
		else if($filtre==0)			$resultat[$res]['in']=1;
		else if($filtre<=100)		{ if($ligne['dept']==$filtre)			$resultat[$res]['in']=1; else $resultat[$res]['in']=0;	}
		else if($filtre<=1000)		{ if($ligne['etab']+100==$filtre)		$resultat[$res]['in']=1; else $resultat[$res]['in']=0;	}
		else						{ if($ligne['insee']==$filtre)			$resultat[$res]['in']=1; else $resultat[$res]['in']=0;	}
		}

	foreach($resultat AS $res=>$ligne)	if($ligne['in']==0)														//affichage en premier des communes hors filtre (arrière-plan)
		{
		if($lien!='') echo '<a xlink:href="'.$lien.$ligne['insee'].'_'.$ligne['nom'].'">';
		echo '<path d="M'.$ligne['contour'].'Z" style="stroke:white; ';											//contour de la commune en blanc

		if(isset($couleur[$ligne['insee']]))	echo 'fill:'.$couleur[$ligne['insee']].'; "/>';			//coloration de la commune en fonction de l'établissement
		else									echo 'fill:#dddddd;"/>';

		if($lien!='')
			{
			if(isset($texte[$ligne['insee']])) echo '<title>'.$ligne['nom_complet'].' '.$texte[$ligne['insee']].'</title>';						//affichage du nom de la commune en infobulle
			else echo '<title>'.$ligne['nom_complet'].'</title>';														//affichage du nom de la commune en infobulle
			}

		echo '</a>'."\n";
		}

	foreach($resultat AS $res=>$ligne)	if($ligne['in']==1)														//affichage en dernier des communes dans le filtre (avant-plan)
		{
		if($lien!='') echo '<a xlink:href="'.$lien.$ligne['insee'].'_'.$ligne['nom'].'">';
		echo '<path d="M'.$ligne['contour'].'Z" style="';

		if(isset($couleur[$ligne['insee']]))	echo 'fill:'.$couleur[$ligne['insee']].'; "/>';			//coloration de la commune en fonction de l'établissement
		else									echo 'fill:#dddddd;"/>';

		if($lien!='')
			{
			if(isset($texte[$ligne['insee']])) echo '<title>'.$ligne['nom_complet'].' '.$texte[$ligne['insee']].'</title>';						//affichage du nom de la commune en infobulle
			else echo '<title>'.$ligne['nom_complet'].'</title>';														//affichage du nom de la commune en infobulle
			echo '</a>'."\n";
			}
		}
	echo '</g>';
	echo '</g>';

	foreach($objets AS $objet)																				//affichage des objets sur de la carte
		{
																											//calcul des coordonnées x et y
		if(isset($objet['x']))																				//récupération de la coordonnée x si elle est définie
			{	if($objet['x']<0) $x=$largeur_carte+$objet['x']; else $x=$objet['x'];	}					//position négative : affichage à partir du bord droit
		else if(isset($objet['lon'])) $x=round(($objet['lon']*1000+$dec_lon)*$largeur_f,2);					//calcul de position d'après la longitude
		else $x=$largeur_carte/2;																			//sinon position au milieu de la carte

		if(isset($objet['y']))																				//récupération de la coordonnée y si elle est définie
			{	if($objet['y']<0) $y=$hauteur_carte+$objet['y']; else $y=$objet['y'];	}					//position négative : affichage à partir du bord inférieur
		else if(isset($objet['lat'])) $y=round(($objet['lat']*1000+$dec_lat)*$hauteur_f,2);				//calcul de position d'après la latutude
		else $y=$hauteur_carte/2;																			//sinon position au milieu de la carte

		if(!isset($objet['nature'])) $objet['nature']='point';												//valeurs par défaut des arguments
		if(!isset($objet['taille'])) $objet['taille']=8;
		if(!isset($objet['angle'])) $objet['angle']=0;
		if(!isset($objet['couleur'])) $objet['couleur']='#000000';
		if(!isset($objet['lien'])) $objet['lien']='';
		if(!isset($objet['info'])) $objet['info']='';

		switch($objet['nature'])
			{
			case 'texte':																					//objet de type texte
					if(!isset($objet['texte'])) $objet['texte']='Hello!';									//valeurs par défaut des arguments spécifiques
					if(!isset($objet['align'])) $objet['align']='l';

					if($objet['lien']!='')
						{
						echo '<a xlink:href="'.$objet['lien'].'">';											//si un lien est défini
						if($objet['info']!='') echo '<title>'.$objet['info'].'</title>';
						}

					echo '<text x="'.$x.'" y="'.$y.'" style="font-family:Arial; font-size:'.$objet['taille'].'px;" fill="'.$objet['couleur'].'"';

					switch($objet['align'])																	//alignement (si non à gauche)
						{
						case 'm': echo ' text-anchor="middle"'; break;
						case 'r': echo ' text-anchor="end"'; break;
						}
					if($objet['angle']!=0) echo ' transform="rotate('.$objet['angle'].','.$x.','.$y.')"';							//angle (si non nul)
					echo '>'.$objet['texte'].'</text>';

					if($objet['lien']!='')	echo '</a>';													//fin de la balise a si un lien est défini

					break;
			case 'point':																					//objet de type point
					if(!isset($objet['point'])) $objet['point']='rond';									//valeurs par défaut des arguments spécifiques

					if($objet['lien']!='')
						{
						echo '<a xlink:href="'.$objet['lien'].'">';											//si un lien est défini
						if($objet['info']!='') echo '<title>'.$objet['info'].'</title>';
						}

					switch($objet['point'])
						{
						case 'rond':		$r=$objet['taille']/2;
											echo '<circle cx="'.$x.'" cy="'.$y.'" r="'.$r.'" style="fill:'.$objet['couleur'].'; stroke:none;"/>'; break;
						case 'triangle':	$y1=$y-$objet['taille']*0.5774;		$y2=$y+$objet['taille']*0.2887;
											$x2=$x+$objet['taille']*0.5;		$x3=$x-$objet['taille']*0.5;
											echo '<polygon points="'.$x.' '.$y1.','.$x2.' '.$y2.','.$x3.' '.$y2.'" style="fill:'.$objet['couleur'].'; stroke:none;"';
											if($objet['angle']!=0) echo ' transform="rotate('.$objet['angle'].','.$x.','.$y.')"';							//angle (si non nul)
											echo '/>'; break;
						case 'carre':		$y1=$y-$objet['taille']*0.5;		$y2=$y+$objet['taille']*0.5;
											$x1=$x-$objet['taille']*0.5;		$x2=$x+$objet['taille']*0.5;
											echo '<rect x="'.$x1.'" y="'.$y1.'" width="'.$objet['taille'].'" height="'.$objet['taille'].'" style="fill:'.$objet['couleur'].'; stroke:none;"';
											if($objet['angle']!=0) echo ' transform="rotate('.$objet['angle'].','.$x.','.$y.')"';							//angle (si non nul)
											echo '/>'; break;
						}

					if($objet['lien']!='')	echo '</a>';													//fin de la balise a si un lien est défini
					break;
			}
		echo "\n";
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

$objets=array(
array('nature'=>'texte','x'=>300,'y'=>30,'texte'=>'Stations Vélib’ dans la','taille'=>24,'couleur'=>'blue','align'=>'m','angle'=>0),
array('nature'=>'texte','x'=>300,'y'=>60,'texte'=>'Métropole du Grand Paris','taille'=>24,'couleur'=>'blue','align'=>'m','angle'=>0),

array('point'=>'carre','x'=>-18,'y'=>10,'taille'=>8,'couleur'=>'#008000','angle'=>45),
array('point'=>'triangle','x'=>-38,'y'=>10,'taille'=>8,'couleur'=>'#800000','angle'=>90),

array('nature'=>'texte','x'=>-20,'y'=>20,'texte'=>'Stations ouvertes','taille'=>12,'angle'=>90,'align'=>'l'),
array('nature'=>'texte','x'=>-40,'y'=>20,'texte'=>'Stations fermées','taille'=>12,'angle'=>90,'align'=>'l'))
;

$resultat=REQ('SELECT code, latitude, longitude,type FROM stations');
foreach($resultat AS $ligne)
	{
	if($ligne['type']=='yes') $objets[]=array('point'=>'carre','lat'=>$ligne['latitude'],'lon'=>$ligne['longitude'],'couleur'=>'#008000','info'=>'Station '.$ligne['code'],'lien'=>'http://velib.nocle.fr/station.php?code='.$ligne['code'],'angle'=>45);
	else $objets[]=array('point'=>'triangle','lat'=>$ligne['latitude'],'lon'=>$ligne['longitude'],'couleur'=>'#800000','info'=>'Station '.$ligne['code'],'lien'=>'http://velib.nocle.fr/station.php?code='.$ligne['code']);
	}

carte(800,600,$coul,$texte,75,'',$objets);

html_bas(0);																								//fonction ferme la BDD et insère des données de bas de page, de fin de fichier HTML, …)
