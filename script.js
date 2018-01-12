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
            displayGraph("getBikeInstantane");
            break;
        case "troisHeures":
            displayGraph("getBikeResumeTroisHeures");
            break;
        case "unJour":
            displayGraph("getBikeResumeUnJour");
            break;
        case "septJours":
            displayGraph("getBikeResumeSeptJours");
            break;
        case "unMois":
            displayGraph("getBikeResumeUnMois");
            break;
    }
}

var graphInstance = null;
function displayGraph(actionUrl)
{
    getData('api.php?action='+actionUrl+'&codeStation='+codeStation).then(
        function(data)
        {
            if(graphInstance != null)
                graphInstance.destroy();
            var chartBikes = $("#chartBikes")[0].getContext('2d');
            graphInstance = new Chart(chartBikes, data);
        }
    );
}

$(document).ready( function () {
    $("#dureeGraphiqueSelect").change(changerDateGraphe);
    changerDateGraphe();
});