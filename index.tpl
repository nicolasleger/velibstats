{include file='header.tpl'}
<header>
    <h1>Vélib Stats (site non officiel)</h1>
</header>
<div id="content">
    <div id="statsConsoArea">
        <table id="statsConso">
            <tr>
                <th colspan="2">Stations ouvertes</th>
                <th colspan="2">Vélos disponibles</th>
                <th colspan="2">Bornes</th>
            </tr>
            <tr>
                <td>{$nbStation}</td>
                <td>{$nbStationDetecte}</td>
                <td>{$nbBike}</td>
                <td>{$nbEbike}</td>
                <td>{$nbFreeEDock}</td>
                <td>{$nbEDock}</td>
            </tr>
            <tr>
                <td><abbr title="Stations affichées comme étant ouverte">annoncées</abbr></td>
                <td><abbr title="Stations avec un nombre de bornes positifs avec au moins un vélo ou une borne libre">détectées</abbr></td>
                <td>mécaniques</td>
                <td>électriques</td>
                <td>libres</td>
                <td>totales</td>
            </tr>
        </table>
        <i>Dernière mise à jour : {$dateDerniereConso}</i>
    </div>
    <select id="typeGraphiqueSelect" style="display: none">
        <option value="_double">Conso</option>
    </select>
    <select id="dureeGraphiqueSelect">
        <option value="instantanee">Une heure - Instantanée</option>
        <option value="troisHeures">Trois heures - Période de 5 minutes</option>
        <option value="unJour">Un jour - Période de 15 minutes</option>
        <option value="septJours" selected>Une semaine - Période d'une heure</option>
        <option value="unMois">Un mois - Période de six heures</option>
    </select> - Graphique issu du site velib.nocle.fr
    <div id="chartArea">
        <canvas id="chartNbStations" height="500px" width="800px"></canvas>
        <canvas id="chartBikes" height="500px" width="800px"></canvas>
    </div>
    {include file="credits.tpl"}
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
                    data: 'name',
                    render: function(data, type, row, meta)
                    {
                        return '<a href="station.php?code='+row.code+'">'+data+'</a>';
                    }
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
</div>
{include file="footer.tpl"}