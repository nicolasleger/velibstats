<?php
/**
 *	arguments :
 *	largeur_carte 		:	nombre de pixels de largeur de l'image SVG à générer
 *	hauteur_carte 		:	nombre de pixels de hauteur de l'image SVG à générer
 *	filtre				:	communes affichées :
 *										0			= tout (métropole gd Paris)
 *										01..99		= numéro de département
 *										100..999	= partie de dept (epic+100)
 *										1000..99999	= commune (numéro insee)
 *										tableau		= liste de numéros INSEE
 *							le code insee et le nom sont ajoutés à la fin
 *	liens				:	début des liens des tracés de commune
 *	info				:	tableau associatif : numéro INSEE => texte infobulle
.*										(ne fonctionne que si lien existe)
 *	couleur				:	tableau associatif : numéro INSEE => couleur
 *	objets				:	liste d'objets ajoutés (tableaux associatifs)
 *
 *	arguments communs aux objets :
 *			nature		:	type d'objet à ajouter : point, texte, ...
 *			x			:	position horizontale en pixels
 *										>=0			= depuis le bord gauche
 *										<0			= depuis le bord droit
 *			y			:	position verticale en pixels
 *										>=0			= depuis le bord supérieur
 *										<			= depuis le bord inférieur
 *			lon			:	longitude de l'objet en degrés (ignoré si x existe)
 *										<0			= Ouest
 *										>0			= Est
 *			lat			:	latitude de l'objet en degrés (ignoré si y existe)
 *										<0			= Sud
 *										>0			= Nord
 *			taille		:	dimension de l'objet en pixels
 *			angle		:	rotation ed l'objet en degrés
 *			couleur		:	couleur de l'objet [#FF0000, red, hsl(0,100%,50%)
 *			lien		:	lien auquel en cas de clic sur l'objet
 *			info		:	texte infobulle (ne fonctionne que si lien existe)
 *
 *	arguments spécifiques aux objets de type "point" :
 *			point		:	forme un point : rond, carre, triangle
 *
 *	arguments spécifiques aux objets de type "texte" :
 *			texte		:	texte à afficher
 *			align		:	alignement du texte
 *										l			= gauche, par défaut)
 *										m			= milieu
 *										r			= droite
 *
 */
function carte($largeur_carte=800,$hauteur_carte=600,$filtre=0,
				$liens='',$info=array(),$couleur=array(),$objets=array())
{
	global $pdo;

/****** FILTRAGE DES COMMUNES DEMANDEES ***************************************/

//détermination de la liste des communes en fonction de l'argument "filtre"
	if(is_array($filtre))
		{
		$liste_des_communes=implode(',',array_map('intval',$filtre));
		$where=' WHERE insee IN ('.$liste_des_communes.')';
		}
	else if($filtre==0)
		$where='';
	else if($filtre<100)
		$where=' WHERE dept="'.$filtre.'"';
	else if($filtre<1000)
		$where=' WHERE etab+100="'.$filtre.'"';
	else
		$where=' WHERE insee="'.$filtre.'"';

//récupération des coordonnées extremes de l'ensemble communes demandées
//entiers en 1/10 000 000 °de degré (précision d'environ 1cm)
//longitude : 180°W = -1 800 000 000 ; 180°E = 1 800 000 000
//latitude : 90°S = -900 000 000 ; 90°N = 900 000 000
	$requete=$pdo->query('SELECT MIN(ouest) AS W, MAX(est) AS E, MAX(nord) AS N,
							MIN(sud) AS S FROM commune'.$where);
	$ligne=$requete->fetch(PDO::FETCH_ASSOC);

//si le filtre ne renvoie aucune commune, suppression du filtre
	if(!$ligne['W'])
		{
		$filtre=0;
		$requete=$pdo->query('SELECT MIN(ouest) AS W, MAX(est) AS E,
							MAX(nord) AS N,	MIN(sud) AS S FROM commune');
		$ligne=$requete->fetch(PDO::FETCH_ASSOC);
		}

//récupération des coordonnées extremes des communes (en 10^-7°)
	$data_W=$ligne['W'];
	$data_E=$ligne['E'];
	$data_N=$ligne['N'];
	$data_S=$ligne['S'];

/****** CALCUL DES COORDONNEES DE LA CARTE ET DE LA TRANSFORMATION EN PIXELS **/

//calculs en degrés
	$carte_W=$data_W/1e7;
	$carte_E=$data_E/1e7;
	$carte_N=$data_N/1e7;
	$carte_S=$data_S/1e7;

//détermination du centre de la carte (en degrés)
	$centreOE=($carte_W+$carte_E)/2;
	$centreNS=($carte_N+$carte_S)/2;

//calcul de la taille d'un degré (en km) au centre de la carte
	$km_lat=20003.932/180;
	$km_lon=$km_lat*cos(deg2rad($centreNS));

//calcul des dimensions minimales de la carte (en degrés)
	$largeur_d=($carte_E-$carte_W);
	$hauteur_d=($carte_N-$carte_S);

//calcul des dimensions minimales de la carte (en km)
	$largeur_k=$largeur_d*$km_lon;
	$hauteur_k=$hauteur_d*$km_lat;

//calcul du nombre de pixels utilisés pour afficher un km
	$pixels_par_km_lon=$largeur_carte/$largeur_k;
	$pixels_par_km_lat=$hauteur_carte/$hauteur_k;

//nombre retenu : coordonnée la moins précise + marge de 2,5% autour de la carte
	$pixels_par_km=min($pixels_par_km_lon,$pixels_par_km_lat)/1.05;

//calcul des dimensions réelles de la carte (en km)
	$largeur_k=$largeur_carte/$pixels_par_km;
	$hauteur_k=$hauteur_carte/$pixels_par_km;

//calcul des dimensions réelles de la carte (en degrés)
	$largeur_d=$largeur_k/$km_lon;
	$hauteur_d=$hauteur_k/$km_lat;
//calcul des coordonnées extrèmes de la carte (en degrés)
	$carte_W=$centreOE-$largeur_d/2;
	$carte_E=$centreOE+$largeur_d/2;
	$carte_N=$centreNS+$hauteur_d/2;
	$carte_S=$centreNS-$hauteur_d/2;

//valeurs extrèmes des points de la carte (en 10^-7°)
	$data_W=$carte_W*1e7;
	$data_E=$carte_E*1e7;
	$data_N=$carte_N*1e7;
	$data_S=$carte_S*1e7;

//les tracés des communes sont en 1/1000 de degré pour des raisons techniques
//en degré, les points sont trop rapprochés pour le moteur SVG
//en 1/10 000 000 de degré, les nombres sont trop grands pour SVG

//facteurs de mise à l'échelle entre données des communes et carte
//le facteur comprend un retournnement vertical pour que le Nord soit en haut
	$largeur_f=$largeur_carte/$largeur_d/1e3;
	$hauteur_f=-$hauteur_carte/$hauteur_d/1e3;

//translation de la carte (coordonnées du coin supérieur gauche)
	$dec_lon=-$carte_W*1000;
	$dec_lat=-$carte_N*1000;

//épaisseur des traits (en fonction du zoom)
	$stroke=round(pow($hauteur_f*$hauteur_f,-0.4)/2,4);

/****** RECUPERATION DE LA LISTE DES COMMUNES VISIBLES SUR LA CARTE ***********/

//récupération des communes au moins partiellement sur la carte
	$requete=$pdo->query('SELECT * FROM commune WHERE ouest<="'.$data_E.'" 
		AND est>="'.$data_W.'" AND nord>="'.$data_S.'" AND sud<="'.$data_N.'"');
	$resultat=$requete->fetchAll(PDO::FETCH_ASSOC);

//détermination si les communes affichées sont dans la sélection ou pas (in)
	foreach($resultat AS $res=>$ligne)
		{
		$resultat[$res]['in']=(
			($filtre==0) ||
			($filtre<=100 && $ligne['dept']==$filtre) ||
			($filtre<=1000 && $ligne['etab']+100==$filtre) ||
			($ligne['insee']==$filtre) ||
			(is_array($filtre) && in_array($ligne['insee'],$filtre))
		);
		}

/****** INITIALISATION DE L'AFFICHAGE SVG *************************************/

	$svg="\n\n";
	$svg.='<svg id="carte" xmlns="http://www.w3.org/2000/svg" version="1.1" width="'.$largeur_carte.'" height="'.$hauteur_carte.'" viewBox="0 0 '.$largeur_carte.' '.$hauteur_carte.'">'."\n";

//liste des styles
	$svg.='<style type="text/css">'."\n";
//pas de pointillés autor des zones cliquables
	$svg.="\t".'a:focus {outline-style:none;}'."\n";
//la commune survolée est en surbrillance
	$svg.="\t".'path:hover {fill-opacity:1;}'."\n";
	$svg.='</style>'."\n";

//fond de carte
	$svg.='<rect x="0" y="0" width="'.$largeur_carte.'" height="'.$hauteur_carte.'" fill="white"/>'."\n";

/****** TRACÉ DES COMMUNES ****************************************************/

//tracés des communes (transformation de 1/1000 de degré en pixels)
	$svg.='<g id="communes" transform="scale('.$largeur_f.','.$hauteur_f.') translate('.$dec_lon.','.$dec_lat.')">'."\n";

//style commun à tous les tracés de communes
	$svg.="\t".'<g id="contour" style="stroke-width:'.$stroke.'; fill:darkgray;">'."\n";

//affichage en premier (arrière-plan) des communes hors sélection (in=false)
	$svg.="\t\t".'<g id="out" style="stroke:white; fill-opacity:0.4;">'."\n";

	foreach($resultat AS $ligne)
		{
		if($ligne['in']==false)
			{
//ajout du lien s'il est défini
			if($liens!='')
				{
				$svg.="\t\t\t".'<a xlink:href="'.$liens.'_'.$ligne['insee'].'_'.$ligne['nom'].'">'."\n";
//ajout de l'info-bulle si elle est définie (il faut que le lien soit défini)
				if(isset($info[$ligne['insee']]))
					$svg.="\t\t\t".'<title>'.$ligne['nom_complet'].' '.$info[$ligne['insee']].'</title>'."\n";
				}
//ajout du tracé de la commune
			$svg.="\t\t\t".'<path ';
			if(isset($couleur[$ligne['insee']]))
				$svg.='style="fill:'.$couleur[$ligne['insee']].';" ';
			$svg.='d="M'.$ligne['contour'].'Z"/>'."\n";
//fermeture du lien s'il a été ouvert
			if($liens!='')
				$svg.="\t\t\t".'</a>'."\n";
			}
		}
	$svg.="\t\t".'</g>'."\n";

//affichage en dernier (avant-plan) des communes en sélection (in=true)
	$svg.="\t\t".'<g id="in" style="stroke:black; fill-opacity:0.6;">'."\n";

	foreach($resultat AS $ligne)
		{
		if($ligne['in']==true)
			{
//ajout du lien s'il est défini
			if($liens!='')
				{
				$svg.="\t\t\t".'<a xlink:href="'.$liens.'_'.$ligne['insee'].'_'.$ligne['nom'].'">'."\n";
//ajout de l'info-bulle si elle est définie (il faut que le lien soit défini)
				if(isset($info[$ligne['insee']]))
					$svg.="\t\t\t".'<title>'.$ligne['nom_complet'].' '.$info[$ligne['insee']].'</title>'."\n";
				}
//ajout du tracé de la commune
			$svg.="\t\t\t".'<path ';
			if(isset($couleur[$ligne['insee']]))
				$svg.='style="fill:'.$couleur[$ligne['insee']].';" ';
			$svg.='d="M'.$ligne['contour'].'Z"/>'."\n";
//fermeture du lien s'il a été ouvert
			if($liens!='')
				$svg.="\t\t\t".'</a>'."\n";
			}
		}
	$svg.="\t\t".'</g>'."\n";

	$svg.="\t".'</g>'."\n";

	$svg.='</g>'."\n";

/****** AFFICHAGE DES OBJETS : CARACTERISTIQUES COMMUNES **********************/

	if(is_array($objets)&&count($objets)>0)
		{
		$svg.='<g id="objets" style="stroke:none;">'."\n";

		foreach($objets AS $objet)
			{
//valeurs par défaut des attributs de l'objet
			if(!isset($objet['nature'])) $objet['nature']='point';
			if(!isset($objet['taille'])) $objet['taille']=8;
			if(!isset($objet['angle'])) $objet['angle']=0;
			if(!isset($objet['couleur'])) $objet['couleur']='#000000';
			if(!isset($objet['lien'])) $objet['lien']='';
			if(!isset($objet['info'])) $objet['info']='';

//calcul de la coordonnée x (en fonction de l'argument x ou de la longitude)
			if(isset($objet['x']))
				{
//coordonnée négative : affichage par rapport au bord droit de la carte
				if($objet['x']<0)
					$x=$largeur_carte+$objet['x'];
				else
					$x=$objet['x'];
				}
			else if(isset($objet['lon']))
				{
//transformation de la longitude en coordonnée
				$x=round(($objet['lon']*1e3+$dec_lon)*$largeur_f,3);
				}
//par défaut : milieu de la carte
			else $x=$largeur_carte/2;

//calcul de la coordonnée y (en fonction de l'argument y ou de la latitude)
			if(isset($objet['y']))
				{
//coordonnée négative : affichage par rapport au bord inférieur de la carte
				if($objet['y']<0)
					$y=$hauteur_carte+$objet['y'];
				else
					$y=$objet['y'];
				}
			else if(isset($objet['lat']))
				{
//transformation de la latitude en coordonnée
				$y=round(($objet['lat']*1e3+$dec_lat)*$hauteur_f,3);
				}
//par défaut : milieu de la carte
			else $y=$hauteur_carte/2;

//ajout du lien s'il est défini
			if($objet['lien']!='')
				{
				$svg.="\t".'<a xlink:href="'.$objet['lien'].'">'."\n";
//ajout de l'info-bulle si elle est définie (il faut que le lien soit défini)
				if($objet['info']!='')
				$svg.="\t".'<title>'.$objet['info'].'</title>'."\n";
				}

			switch($objet['nature'])
				{

/****** AFFICHAGE DES OBJETS TEXTE ********************************************/
				case 'texte':
//valeurs par défaut des attributs des objets texte
					if(!isset($objet['texte'])) $objet['texte']='Hello!';
					if(!isset($objet['align'])) $objet['align']='l';

					$svg.="\t".'<text x="'.$x.'" y="'.$y.'" style="font-family:Arial; font-size:'.$objet['taille'].'px;" fill="'.$objet['couleur'].'"';
//alignement du texte
					switch($objet['align'])
						{
						case 'm': $svg.=' text-anchor="middle"'; break;
						case 'r': $svg.=' text-anchor="end"'; break;
						}
//angle d'inclinaison du texte
					if($objet['angle']!=0)
						$svg.=' transform="rotate('.$objet['angle'].','.$x.','.$y.')"';
					$svg.='>'.$objet['texte'].'</text>'."\n";
					break;

/****** AFFICHAGE DES OBJETS POINT (objet par défaut)**************************/
				default:
//valeurs par défaut des attributs des objets point
					if(!isset($objet['point'])) $objet['point']='rond';

					switch($objet['point'])
						{
						case 'triangle':	$y1=$y-$objet['taille']*0.5774;
											$y2=$y+$objet['taille']*0.2887;
											$x2=$x+$objet['taille']*0.5;
											$x3=$x-$objet['taille']*0.5;
											$svg.="\t".'<polygon points="'.$x.' '.$y1.','.$x2.' '.$y2.','.$x3.' '.$y2.'" style="fill:'.$objet['couleur'].';"';
											if($objet['angle']!=0) $svg.=' transform="rotate('.$objet['angle'].','.$x.','.$y.')"';
											$svg.='/>'."\n";
											break;
						case 'carre':		$y1=$y-$objet['taille']*0.5;
											$x1=$x-$objet['taille']*0.5;
											$svg.="\t".'<rect x="'.$x1.'" y="'.$y1.'" width="'.$objet['taille'].'" height="'.$objet['taille'].'" style="fill:'.$objet['couleur'].';"';
											if($objet['angle']!=0) $svg.=' transform="rotate('.$objet['angle'].','.$x.','.$y.')"';
											$svg.='/>'."\n";
											break;
//objet de type diagramme en camembert
						case 'pie':			$r=$objet['taille']*0.5;
//si le diagramme n'a pas le format attendu, diagramme par défaut
											if(!isset($objet['pie'])||count($objet['pie'])!=4||$objet['pie'][0]==0) $objet_pie=array(3,1,1,1);

//initialisation
											$r=$objet['taille']*0.5;
											$a1=deg2rad(90);
											$x1=$x;
											$y1=$y-$r;

											$couleur=array(1=>'DodgerBlue','LimeGreen','chocolate');
//pour chaque partie du diagramme (non vide)
											for($i=1;$i<=3;$i++)
												{
												if($objet['pie'][$i])
													{
													$prop=$objet['pie'][$i]/$objet['pie'][0];
													$a2=$a1-deg2rad(360*$prop);
													$x2=$x+cos($a2)*$r;
													$y2=$y-sin($a2)*$r;
													if($prop<=0.5)
														{
														$svg.="\t".'<path d="M'.$x.' '.$y.'L'.$x1.' '.$y1.'A'.$r.' '.$r.' 0 0 1 '.$x2.' '.$y2.'Z" style="fill:'.$couleur[$i].';"/>'."\n";
														}
													else if($prop<1)
														{
														$svg.="\t".'<path d="M'.$x.' '.$y.'L'.$x1.' '.$y1.'A'.$r.' '.$r.' 0 1 1 '.$x2.' '.$y2.'Z" style="fill:'.$couleur[$i].';"/>'."\n";
														}
													else $svg.="\t".'<circle cx="'.$x.'" cy="'.$y.'" r="'.$r.'" style="fill:'.$couleur[$i].';"/>'."\n";
													$svg.="\t".'<circle cx="'.$x.'" cy="'.$y.'" r="'.$r.'" style="stroke:black; stroke-width:0.3; fill:none;"/>'."\n";

													$a1=$a2;
													$x1=$x2;
													$y1=$y2;
													}
												}
											break;

						default:			$r=$objet['taille']*0.5;
											$svg.="\t".'<circle cx="'.$x.'" cy="'.$y.'" r="'.$r.'" style="fill:'.$objet['couleur'].';"/>'."\n";
											break;
						}
					break;
				}

/****** FIN DE L'AFFICHAGE DES OBJETS *****************************************/
			if($objet['lien']!='')
				{
				$svg.="\t".'</a>'."\n";
				}
			}
		$svg.='</g>'."\n";
		}
/****** FIN DU SVG ET SORTIE DE LA FONCTION ***********************************/
	$svg.='</svg>'."\n\n";

	return($svg);
	}

?>
