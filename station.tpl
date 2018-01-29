{include file='header.tpl'}
<header>
    <h1>Vélib Stats (site non officiel) - Station {$stationCodeDisplay}</h1>
    <nav>
        <a href="index.php">&lt; Retour à l'accueil</a>
    </nav>
</header>
<div id="content">
    <ul>
        <li>Code : {$stationCodeDisplay}</li>
        <li>Nom : {$stationNom}</li>
        <li>Date d'ouverture : {$stationDateOuverture}</li>
        <li>Adresse la plus proche (selon <a href="https://adresse.data.gouv.fr/" title="la Base Adresse Nationale">BAN</a>) : {$stationAdresse}</li>
        <li>Signalement des utilisateurs sur l'état de la station (dernière semaine) : 
        {if $signalementTotal eq 0}
            Aucun
        {else}
            {if $signalementOui gt 0}Fonctionne = {$signalementOui}; {/if}
            {if $signalementNon gt 0}Ne fonctionne pas = {$signalementNon}; {/if}
            Dernier signalement : {if $dernierSignalementType}Fonctionne{else}Ne fonctionne pas{/if} à {$dernierSignalementDate}.
        {/if}
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
     - Graphique issu du site velib.nocle.fr
    <canvas id="chartBikes" width="1000" height="400"></canvas>
    {include file="credits.tpl"}
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
    </table>
    <h2>Stations à proximité</h2>
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
        var codeStation = {$stationCode};
    </script>
    <script type="application/javascript">
        $(document).ready( function () {
            var dt = $('#stats').DataTable({
                ajax: 'api.php?action=getDataStation&codeStation={$stationCode}',
                columns: [{
                    data: 'date',
                    render: function(data, type, row, meta)
                    {
                        var date = new Date(data);
                        return putZero(date.getDate()) + '/' + putZero(date.getMonth()+1) + '/' + date.getFullYear() + ' ' + date.getHours() + ':' + date.getMinutes();
                    }
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
                language: dtTraduction,
                initComplete: function (settings, dataAjax)
                {
                    var data = dataAjax['data'];
                    if(data.length > 0)
                        chargerListeStationProximite(data[data.length - 1]['idConso']);
                }
            });
        } );

        function chargerListeStationProximite(idConso)
        {
            var dtStation = $('#stations').DataTable({
                ajax: 'api.php?action=getDataConso&idConso='+idConso+'&lat={$stationLat}&long={$stationLong}',
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
                    data: 'state',
                    render: function(data, type, row, meta)
                    {
                        if(data == 'Operative' && row.nbEDock > 0)
                            return 'Ouverte';
                        else
                            return 'En travaux';
                    }
                },{
                    data: 'nbBike'
                },{
                    data: 'nbEbike'
                },{
                    data: 'nbFreeEDock',
                    render: function(data, type, row, meta)
                    {
                        if(type == 'sort') //Pour le tri, on utilise le nombre de bornes libres directement
                        {
                            if(data < 10)
                                return '00' + data.toString();
                            if(data < 100)
                                return '0' + data.toString();
                            return data;
                        }
                        return data+'/'+row.nbEDock;
                    }
                }],
                language: dtTraduction
            });
        }
    </script>
</div>
{include file="footer.tpl"}