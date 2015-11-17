var baseTime = 500;
setTimeout(
    function () {
        window.postMessage({type: 'isInstalled', id: 1}, '*');
        console.log('Chrome Extension:- Check');
        setTimeout(
            function (){
                if(typeof chromeExt == 'undefined' || !chromeExt){
                    var joinVcElem = document.querySelector('#vc');
                    var chromeExtButton = document.createElement('button');
                    chromeExtButton.id = 'chromeExtTrigger';
                    chromeExtButton.title = "For share screen share" ;
                    chromeExtButton.innerHTML = 'Add Desktop Selector'; 
                    chromeExtButton.addEventListener('click', function (){
                        readyToInstallExt();
                    });
                    insertAfter(chromeExtButton, joinVcElem);
                    console.log('Chrome Extension:- Button ready');
                }
            },
            500
        );
    },
    baseTime
);

var readyToInstallExt = function (){
    var url = 'https://chrome.google.com/webstore/detail/' + 'ijhofagnokdeoghaohcekchijfeffbjl';
    chrome.webstore.install(url, function () {
       console.log('Installed Chrmoe Extension ');
       var chromeExtTrigger = document.getElementById('chromeExtTrigger');
       chromeExtTrigger.parentNode.removeChild(chromeExtTrigger);
    }, function (error){
        console.log('Chrome Extension:- Error ' + error);
    });
}



window.addEventListener('message', function (event) {
    if (event.data.type == 'yes') {
        chromeExt = true;
    }
    console.log('Chrome Extension:- is available');
});


function insertAfter(newNode, referenceNode) {
    referenceNode.parentNode.insertBefore(newNode, referenceNode.nextSibling);
}
