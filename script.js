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
    var choixPeriode = $("#dureeGraphiqueSelect").val();
    var choix = 'get' + choixType;
    switch (choixPeriode) {
        case "instantanee":
            $("#displayDetailsArea").hide();
            displayGraph(choix+"Instantane");
            break;
        case "troisHeures":
            $("#displayDetailsArea").show();
            displayGraph(choix+"ResumeTroisHeures");
            break;
        case "unJour":
            $("#displayDetailsArea").show();
            displayGraph(choix+"ResumeUnJour");
            break;
        case "septJours":
            $("#displayDetailsArea").show();
            displayGraph(choix+"ResumeSeptJours");
            break;
        case "unMois":
            $("#displayDetailsArea").show();
            displayGraph(choix+"ResumeUnMois");
            break;
    }
}

var graphInstance = null;
var graphData = null;
var graphDataSets = null;
function displayGraph(actionUrl)
{
    getData('api.php?action='+actionUrl+'&codeStation='+codeStation).then(updateGraph);
}

function getDisplayData()
{
    var dataDisplay = graphData;
    var displayDetails = $("#displayDetails").prop('checked');
    var displayInstantanne = $("#dureeGraphiqueSelect").val() == 'instantanee';
    var displayBike = $("#typeGraphiqueSelect").val() == 'Bike';
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

function updateGraph(data)
{
    if(graphInstance != null)
        graphInstance.destroy();
    var chartBikes = $("#chartBikes")[0].getContext('2d');
    if(data != undefined)
    {
        graphData = data;
        graphDataSets = data.data.datasets;
    }
    graphInstance = new Chart(chartBikes, getDisplayData());
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