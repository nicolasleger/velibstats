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

function changerDateGraphe()
{
    var choix = $("#dureeGraphiqueSelect").val();
    switch (choix) {
        case "instantanee":
            $("#displayDetailsArea").hide();
            displayGraph("getBikeInstantane");
            break;
        case "troisHeures":
            $("#displayDetailsArea").show();
            displayGraph("getBikeResumeTroisHeures");
            break;
        case "unJour":
            $("#displayDetailsArea").show();
            displayGraph("getBikeResumeUnJour");
            break;
        case "septJours":
            $("#displayDetailsArea").show();
            displayGraph("getBikeResumeSeptJours");
            break;
        case "unMois":
            $("#displayDetailsArea").show();
            displayGraph("getBikeResumeUnMois");
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
    if(!displayDetails && graphDataSets.length > 2)
    {
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
    } else {
        dataDisplay.data.datasets = graphDataSets;
        if(graphDataSets.length > 2)
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

$(document).ready( function () {
    $("#dureeGraphiqueSelect").change(changerDateGraphe);
    $("#displayDetails").change(displayDetails);
    changerDateGraphe();
});