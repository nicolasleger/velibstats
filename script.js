function getData(url)
{
    return new Promise(
        function(resolve, reject)
        {
            var xhr = new XMLHttpRequest();
            xhr.open('GET', url);
            xhr.onload = function()
            {
                if (xhr.status === 200)
                    resolve(JSON.parse(xhr.response));
                else
                    reject(xhr.statusText);
            };
            
            xhr.send();
        }
    );
}

function changerGraphe()
{
    var choixType = $("#typeGraphiqueSelect").val();
    if(choixType == '_double')
        listeType = ['Conso', 'ConsoBike'];
    else
        listeType = [choixType];
    var choixPeriode = $("#dureeGraphiqueSelect").val();
    for(i in listeType)
    {
        var choixType = listeType[i];
        var choix = 'get' + choixType;
        switch (choixPeriode) {
            case "instantanee":
                $("#displayDetailsArea").hide();
                displayGraph(choix+"Instantane", choixType);
                break;
            case "troisHeures":
                $("#displayDetailsArea").show();
                displayGraph(choix+"ResumeTroisHeures", choixType);
                break;
            case "unJour":
                $("#displayDetailsArea").show();
                displayGraph(choix+"ResumeUnJour", choixType);
                break;
            case "septJours":
                $("#displayDetailsArea").show();
                displayGraph(choix+"ResumeSeptJours", choixType);
                break;
            case "unMois":
                $("#displayDetailsArea").show();
                displayGraph(choix+"ResumeUnMois", choixType);
                break;
        }
    }
}

var graphInstance = {};
var graphData = null;
var graphDataSets = null;
function displayGraph(actionUrl, choixType)
{
    getData('api.php?action='+actionUrl+'&codeStation='+codeStation).then(function(data)
    {
        updateGraph(data, choixType);
    });
}

function getDisplayData(displayBike, displayInstantanne)
{
    var dataDisplay = graphData;
    var displayDetails = $("#displayDetails").prop('checked');
    if(displayInstantanne == undefined)
        displayInstantanne = $("#dureeGraphiqueSelect").val() == 'instantanee';
    if(displayBike == undefined)
        displayBike = $("#typeGraphiqueSelect").val() == 'Bike';
    if(displayInstantanne || !displayDetails)
    {
        if(displayBike) //Vélos
        {
            if(displayInstantanne)
                dataDisplay.data.datasets = graphDataSets;
            else
                dataDisplay.data.datasets = [graphDataSets[0], graphDataSets[5]];
            //On ajuste les couleurs des vélos mécaniques
            dataDisplay.data.datasets[0].backgroundColor = 'rgba(104,221,46,0.5)';
            dataDisplay.data.datasets[0].fill = true;
            delete dataDisplay.data.datasets[0].borderColor;
            //Et électriques
            dataDisplay.data.datasets[1].backgroundColor = 'rgba(76, 213, 233,0.5)';
            dataDisplay.data.datasets[1].fill = true;
            delete dataDisplay.data.datasets[1].borderColor;
            dataDisplay.options.scales.yAxes[0].stacked = true;
        }
        else //Bornes
        {
            dataDisplay.data.datasets = [graphDataSets[0]];
            //On ajuste les couleurs
            dataDisplay.data.datasets[0].backgroundColor = 'rgba(173,0,130,0.5)';
            dataDisplay.data.datasets[0].fill = true;
            dataDisplay.options.scales.yAxes[0].stacked = true;
            delete dataDisplay.data.datasets[0].borderColor;
        }
    } else {
        dataDisplay.data.datasets = graphDataSets;
        if(displayBike) // Vélos
        {
            //On ajuste les couleurs des vélos mécaniques
            dataDisplay.data.datasets[0].borderColor = 'rgba(104,221,46,0.7)';
            dataDisplay.data.datasets[0].fill = false;
            delete dataDisplay.data.datasets[0].backgroundColor;
            //Et électriques
            dataDisplay.data.datasets[5].borderColor = 'rgba(76, 213, 233,0.7)';
            dataDisplay.data.datasets[5].fill = false;
            delete dataDisplay.data.datasets[5].backgroundColor;
            dataDisplay.options.scales.yAxes[0].stacked = false;
        } else { //Bornes
            //On ajuste les couleurs
            dataDisplay.data.datasets[0].borderColor = 'rgba(173,0,130,0.5)';
            dataDisplay.data.datasets[0].fill = false;
            delete dataDisplay.data.datasets[0].backgroundColor;
            dataDisplay.options.scales.yAxes[0].stacked = false;
        }
    }
    return dataDisplay;
}

function updateGraph(data, choixType)
{
    if(graphInstance != null && choixType != 'ConsoBike')
    {
        for(i in graphInstance)
        {
            var instance = graphInstance[i];
            instance.destroy();
        }
        graphInstance = {};
    }
    var idChart = "#chartBikes";
    var isBike;
    var isInstantanne;
    if(choixType == 'Conso')
    {
        idChart = '#chartNbStations';
        isBike = false;
        isInstantanne = $("#dureeGraphiqueSelect").val() == 'instantanee';
    } else if(choixType == 'ConsoBike')
    {
        isBike = true;
        isInstantanne = true;
    }
    else
    {
        isBike = $("#typeGraphiqueSelect").val() == 'Bike';
        isInstantanne = $("#dureeGraphiqueSelect").val() == 'instantanee';
    }
    var chartBikes = $(idChart)[0].getContext('2d');
    if(data != undefined)
    {
        graphData = data;
        graphDataSets = data.data.datasets;
    }

    graphInstance[choixType] = new Chart(chartBikes, getDisplayData(isBike, isInstantanne));
}

function displayDetails()
{
    if(graphInstance != null)
        updateGraph();
}

function voteOui()
{
    vote(true);
}

function voteNon()
{
    vote(false);
}

function vote(statut)
{

    if(localStorage.vote == undefined)
        localStorage.vote = JSON.stringify({});
    var vote = JSON.parse(localStorage.vote);
    if(vote[codeStation] == undefined || ((new Date()) - new Date(vote[codeStation])) > 24*60*60*1000) //Pas plus d'un vote par jour
    {
        getData('api.php?action=vote&statut='+(statut ? 'oui' : 'non')+'&codeStation='+codeStation).then(function(data) {
            if(data)
            {
                alert('Votre vote a été pris en compte');
                if(localStorage.vote == undefined)
                    localStorage.vote = JSON.stringify({});
                var vote = JSON.parse(localStorage.vote);
                vote[codeStation] = new Date();
                localStorage.vote = JSON.stringify(vote);
                hideBoutonsVote();
            } else {
                alert('Erreur durant le vote');
            }
        });
    } else {
        hideBoutonsVote();
    }
}

function hideBoutonsVote()
{
    $("#boutonFonctionneOui").hide();
    $("#boutonFonctionneNon").hide();
}

function initBoutonsVote()
{
    if(localStorage.vote == undefined)
    localStorage.vote = JSON.stringify({});
    var vote = JSON.parse(localStorage.vote);
    if(vote[codeStation] == undefined || ((new Date()) - new Date(vote[codeStation])) > 24*60*60*1000) //Pas plus d'un vote par jour
    {
        $("#boutonFonctionneOui").click(voteOui);
        $("#boutonFonctionneNon").click(voteNon);
    } else {
        hideBoutonsVote();
    }
}

$(document).ready( function () {
    $("#dureeGraphiqueSelect").change(changerGraphe);
    $("#typeGraphiqueSelect").change(changerGraphe);
    $("#displayDetails").change(displayDetails);
    initBoutonsVote();
    changerGraphe();
});

var dtTraduction = {
    "sProcessing":     "Traitement en cours...",
    "sSearch":         "Rechercher&nbsp;:",
    "sLengthMenu":     "Afficher _MENU_ &eacute;l&eacute;ments",
    "sInfo":           "Affichage de l'&eacute;l&eacute;ment _START_ &agrave; _END_ sur _TOTAL_ &eacute;l&eacute;ments",
    "sInfoEmpty":      "Affichage de l'&eacute;l&eacute;ment 0 &agrave; 0 sur 0 &eacute;l&eacute;ment",
    "sInfoFiltered":   "(filtr&eacute; de _MAX_ &eacute;l&eacute;ments au total)",
    "sInfoPostFix":    "",
    "sLoadingRecords": "Chargement en cours...",
    "sZeroRecords":    "Aucun &eacute;l&eacute;ment &agrave; afficher",
    "sEmptyTable":     "Aucune donn&eacute;e disponible dans le tableau",
    "oPaginate": {
        "sFirst":      "Premier",
        "sPrevious":   "Pr&eacute;c&eacute;dent",
        "sNext":       "Suivant",
        "sLast":       "Dernier"
    },
    "oAria": {
        "sSortAscending":  ": activer pour trier la colonne par ordre croissant",
        "sSortDescending": ": activer pour trier la colonne par ordre d&eacute;croissant"
    }
};