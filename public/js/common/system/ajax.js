Sys.registerNoAccessEvent = function (func) {
    Sys.globalNoAccess = func;
};

Sys.request = function (url, method, params, onDone, onError) {
    method = typeof method !== 'undefined' ? method : 'POST';
    params = typeof params !== 'undefined' ? params : {};
    onDone = typeof onDone !== 'undefined' ? onDone : function () {
    };
    onError = typeof onError !== 'undefined' ? onError : function () {
    };
    if (window.XMLHttpRequest) {
        var httpRequest = new XMLHttpRequest();
    } else {
        var httpRequest = new ActiveXObject("Microsoft.XMLHTTP");
    }
    httpRequest.onreadystatechange = function () {
        if (httpRequest.readyState === 4) {
            if (httpRequest.status === 200) {
                onDone(httpRequest.responseText);
            } else {
                onError(httpRequest.responseText, httpRequest.status);
            }
        }
    };
    var urlParams = this.serializeUrl(params);
    var sendingData = null;
    if (method.toUpperCase() === 'POST') {
        sendingData = urlParams;
    } else {
        if (urlParams) {
            url = url + "?" + urlParams;
        }
    }
    httpRequest.open(method.toUpperCase(), url, true);
    httpRequest.setRequestHeader("X-Requested-With", "XMLHttpRequest");
    httpRequest.setRequestHeader("Accept", "*");
    httpRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    httpRequest.send(sendingData);
};
Sys.post = function (url, params, onDone, onError) {
    this.request(url, 'POST', params, onDone, onError);
};
Sys.get = function (url, params, onDone, onError) {
    this.request(url, 'GET', params, onDone, onError);
};
Sys.delete = function (url, params, onDone, onError) {
    this.request(url, 'DELETE', params, onDone, onError);
};
Sys.put = function (url, params, onDone, onError) {
    this.request(url, 'PUT', params, onDone, onError);
};
Sys.redirectAction = function (controllerName, params, redirectOnSuccessInfo, onError)
{
    var draw_mode = typeof redirectOnSuccessInfo.draw_mode !== 'undefined' ? redirectOnSuccessInfo.draw_mode : Sys.drawModes.REPLACE_INNER;
    params._sys_redirect = redirectOnSuccessInfo;
    Sys.requestController(controllerName, params, redirectOnSuccessInfo.container, draw_mode, function(){}, onError);
}
Sys.action = function (controllerName, params, onSuccess, onError)
{
    Sys.requestController(controllerName, params, null, null, onSuccess, onError);
};
/**
 * 
 * @param {type} controllerName
 * @param {type} params
 * @param {type} containerId
 * @param {type} drawMode
 * @param {type} onInlineSuccess
 * @param {type} onInlineError
 * @returns {undefined}
 */
Sys.requestController = function (controllerName, params, containerId, drawMode, onInlineSuccess, onInlineError)
{
    var controller = Sys.controller(controllerName);
    var onControllerDone = (controller && typeof controller.init !== 'undefined') ? controller.init : null;
    var onControllerErr = (controller && typeof controller.error !== 'undefined') ? controller.error : null;
    drawMode = typeof drawMode !== 'undefined' ? drawMode : Sys.drawModes.REPLACE_INNER;
    var url = '//' + Sys.host + '/_sys_/' + controllerName.replace('.', '/');
    Sys.post(url, params, function (responseText) {
        var responseData;
        try {
            responseData = JSON.parse(responseText);
        } catch (e) {
            responseData = responseText;
        }
        var container = document.getElementById(containerId);
        if (typeof responseData === "object" && responseData !== null) {
            if (typeof responseData.html !== 'undefined' && container !== null)
            {
                var html = responseData['html'];
                Sys._addHtmlInContainer(html, drawMode, container);
                var scripts = container.getElementsByTagName("script");
                if (scripts.length > 0)
                {
                    for (var i = 0; i < scripts.length; i++) {
                        eval(scripts[i].innerHTML);
                    }
                }
            }
            if (responseData.sys_error_code > 0)
            {
                if (typeof onInlineError === 'function') {
                    onInlineError.bind(controller, responseData)();
                    return;
                }
                if (typeof onControllerErr === 'function') {
                    onControllerErr.bind(controller, responseData)();
                    return;
                }
            }

        }
        if (typeof onInlineSuccess === 'function') {
            onInlineSuccess.bind(controller, responseData)();
        }
        if (typeof responseData.controllers !== 'undefined')
        {
            responseData.controllers.forEach(function (controller) {
                var m = Sys.findController(controller['name']);
                if (m) {
                    m.properties = controller['properties'];
                    m.init(controller['params']);
                }
            });
        } else
        {
            if (typeof onControllerDone === 'function') {
                onControllerDone.bind(controller, responseData)();
            }
        }

    }, function (res, status) {
        var responseData;
        try {
            responseData = JSON.parse(res);
        } catch (e) {
            responseData = res;
        }
        var errorCached = false;
        if (typeof onInlineError === 'function') {
            errorCached = true;
            onInlineError.bind(controller, responseData)();
        }
        if (typeof onControllerErr === 'function') {
            errorCached = true;
            onControllerErr.bind(controller, responseData)();
        }
        if (status == 401 && typeof Sys.globalNoAccess === 'function') {
            errorCached = true;
            Sys.globalNoAccess.bind(controller, responseData)();
        }
        if (!errorCached){
            console.error(responseData);
        }
        

    });
};

Sys._addHtmlInContainer = function (html, drawMode, container) {
    switch (drawMode)
    {
        case Sys.drawModes.REPLACE:
            container.outerHTML = html;
            break;
        case Sys.drawModes.APPEND:
            container.insertAdjacentHTML('beforeend', html);
            break;
        case Sys.drawModes.PREPEND:
            container.insertAdjacentHTML('afterbegin', html);
            break;
        case Sys.drawModes.BEFORE:
            container.insertAdjacentHTML('beforebegin', html);
            break;
        case Sys.drawModes.AFTER:
            container.insertAdjacentHTML('afterend', html);
            break;
        case Sys.drawModes.REPLACE_INNER:
            container.innerHTML = html;
            break;
    }
};

Sys.serializeUrl = function (obj, prefix) {
    var str = [], p;
    for (p in obj) {
        if (obj.hasOwnProperty(p)) {
            var k = prefix ? prefix + "[" + p + "]" : p, v = obj[p];
            str.push((v !== null && typeof v === "object") ?
                    Sys.serializeUrl(v, k) :
                    encodeURIComponent(k) + "=" + encodeURIComponent(v));
        }
    }
    return str.join("&");
};