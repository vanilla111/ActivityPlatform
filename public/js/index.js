'use strict';

var serverUrl = './department';
var successInfo = new RegExp('^[1][3-8]\\d{9}$');
//var serverUrl = './choose';
$('.button').addEventListener('touchstart', function () {
    if (!successInfo.test($('.input-content').value)) {
        window.alert('请输入正确的手机号');
    } else {
        sessionStorage.setItem('contact', $('.input-content').value);
        window.location.href = serverUrl;
    }
});



ajax({
    method: 'get',
    url: 'https://wx.idsbllp.cn/activity/wx/userInfo',
    success: function success(res) {
        var data = res.data;
        var contact = data.stu_info.contact
        if (contact) {
            $('.input-content').value = contact;
        }
        sessionStorage.setItem('userInfo', JSON.stringify(data));
    }
});
