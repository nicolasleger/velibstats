function getData(url)
{
    return new Promise(function(resolve, reject) {
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
    });
  }