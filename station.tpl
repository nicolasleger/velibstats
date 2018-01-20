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
            <a href="index.php">Retour à l'accueil</a>
        </nav>
        <h1>Vélib Stats (site non officiel) - Station {$stationCode}</h1>
        <ul>
            <li>Nom : {$stationNom}</li>
            <li>Date d'ouverture : {$stationDateOuverture}</li>
            <li>Adresse la plus proche (selon <a href="https://adresse.data.gouv.fr/">BAN</a>) : {$stationAdresse}</li>
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
        Graphique issu du site velib.nocle.fr
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
        </table>
        <script type="text/javascript">
            var codeStation = {$stationCode};
        </script>
        <script type="application/javascript">
            $(document).ready( function () {
                $('#stats').DataTable({
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
                    language: dtTraduction
                });
            } );
        </script>
    </body>
</html>