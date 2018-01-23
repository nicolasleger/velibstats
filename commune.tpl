{include file='header.tpl'}
<header>
    <h1>Vélib Stats (site non officiel) - {$communeNom}</h1>
    <nav>
        <a href="index.php">&lt; Retour à l'accueil</a>
    </nav>
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
        {foreach $stations as $station}
            <tr>
                <td><a href="station.php?code={$station.code}">{$station.codeStr}</a></td>
                <td><a href="station.php?code={$station.code}">{$station.name}</a></td>
                <td>{$station.dateOuverture}</td>
                <td>{if $station.state eq 'Operative' and $station.nbEDock gt 0}Ouverte{else}En travaux{/if}</td>
                <td>{$station.nbBike}</td>
                <td>{$station.nbEbike}</td>
                <td>{$station.nbFreeEDock}/{$station.nbEDock}</td>
            </tr>
        {/foreach}
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
            recupererCarte();
        });
    </script>

    {include file="credits.tpl"}
</div>
{include file="footer.tpl"}