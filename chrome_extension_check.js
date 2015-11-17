
function initChromeExtension(){
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
}


var browserDetection = function () {
    var ua = navigator.userAgent, tem,
        M = ua.match(/(opera|opr|OPR(?=\/))\/?\s*([\d\.]+)/i) || []; //for opera especially
    if (M.length <= 0) {
        M = ua.match(/(chrome|safari|firefox|trident(?=\/))\/?\s*([\d\.]+)/i) || [];
        if (M[1] == 'Safari') {
            var version = ua.match(/(version(?=\/))\/?\s*([\d\.]+)/i) || [];
            M[2] = version[2];
        }
    }
    if (/trident/i.test(M[1])) {
        tem = /\brv[ :]+(\d+(\.\d+)?)/g.exec(ua) || [];
        return 'IE ' + (tem[1] || '');
    }

    M = M[2] ? [M[1], M[2]] : [navigator.appName, navigator.appVersion, '-?'];
    if ((tem = ua.match(/version\/([\.\d]+)/i)) != null) {
        M[2] = tem[1];
    }
    // return M.join(' ');
    return M;
}


var vendor = browserDetection();
if(vendor[0] == 'Chrome'){
    initChromeExtension();
}