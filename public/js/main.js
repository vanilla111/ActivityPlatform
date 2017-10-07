"use strict";

function $(ele) {
    var eles = document.querySelectorAll(ele);
    return eles.length === 1 ? eles[0] : eles;
}

function ajax(conf) {
    var method = conf.method;
    var url = conf.url;
    var success = conf.success;
    var type = conf.type;
    var error = conf.error;
    var data = conf.data;
    var xhr = new XMLHttpRequest();
    var successInfo = new RegExp("2[0-9]{2}");
    var errorInfo = new RegExp("4|5[0-9]{2}");
    xhr.open(method, url, true);
    if (method == 'GET' || method == 'get') {
        xhr.send(null);
    } else if (method == 'POST' || method == 'post') {
        if (type === 'json' || type === 'JSON') {
            xhr.setRequestHeader('content-type', 'application/json');
            xhr.send(JSON.stringify(data));
        } else if (type === 'form' || type === 'FORM') {
            xhr.setRequestHeader('content-type', 'application/x-www-form-urlencoded');
            xhr.send(data);
        }
    }
    xhr.onreadystatechange = function () {
        //console.log(xhr.status)
        if (xhr.readyState == 4 && successInfo.test(xhr.status)) {
            success(JSON.parse(xhr.responseText));
        } else if (xhr.readyState == 4 && errorInfo.test(xhr.status)) {
            if (error !== undefined) {
                error(JSON.parse(xhr.responseText));
            }
        }
    };
};