'use strict';

var serverUrl = './department';
//var serverUrl = './choose';
$('.button').addEventListener('touchstart', function () {
    if ($('.input-content').value.length !== 11) {
        window.alert('请输入正确的手机号');
    } else {
        window.location.href = serverUrl + '?phone=' + $('.input-content').value;
    }
});