'use strict';

var serverUrl = './department';
//var serverUrl = './choose';
$('.button').addEventListener('touchstart', function () {
    if ($('.input-content').value.length !== 11) {
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
