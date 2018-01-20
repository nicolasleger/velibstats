{include file='header.tpl'}
<h1>Vélib Stats (site non officiel)</h1>
<ul>
    <li>Nombre de stations ouvertes annoncées (<abbr title="Stations affichées comme étant ouverte">définition</abbr>) : {$nbStation}</li>
    <li>Nombre de stations ouvertes détectées (<abbr title="Stations avec un nombre de bornes positifs avec au moins un vélo ou une borne libre">définition</abbr>) : {$nbStationDetecte}</li>
    <li>Nombre de vélos mécaniques disponible : {$nbBike}</li>
    <li>Nombre de vélos électriques disponible : {$nbEbike}</li>
    <li>Nombre de bornes libres : {$nbFreeEDock}</li>
    <li>Nombre de bornes total : {$nbEDock}</li>
</ul>
<i>Dernière mise à jour : {$dateDerniereConso}</i><br />
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
            ajax: 'api.php?action=getDataConso&idConso={$idConso}',
            columns: [{
                data: 'codeStr',
                render: function(data, type, row, meta)
                {
                    return '<a href="station.php?code='+row.code+'">'+data+'</a>';
                }
            },{
                data: 'name'
            },{
                data: 'dateOuverture',
                render: function(data, type, row, meta)
                {
                    if(data == 'Non ouvert')
                        return data;
                    var date = new Date(data);
                    return putZero(date.getDate()) + '/' + putZero(date.getMonth()+1) + '/' + date.getFullYear();
                }
            },{
                data: 'state'
            },{
                data: 'nbBike'
            },{
                data: 'nbEbike'
            },{
                data: 'nbFreeEDock',
                render: function(data, type, row, meta)
                {
                    return data+'/'+row.nbEDock;
                }
            }],
            language: dtTraduction
        });
        filtreDataTable();
        $("#filtreEtat").change(filtreDataTable);
    } );
</script>
{include file="footer.tpl"}