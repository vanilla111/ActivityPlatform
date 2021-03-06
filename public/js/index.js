'use strict';

var serverUrl = './department';
var successInfo = new RegExp('^[1][3-8]\\d{9}$');

var query = location.search;
var now = new Date();
var startTime = new Date('2017/10/10 03:00:00');

if (/ref=.*joinus/.test(query) || sessionStorage.getItem('joinus') || now < startTime) {
    sessionStorage.setItem('joinus', true);
    document.title = '红岩网校工作站招新啦';
    // serverUrl = 'https://wx.idsbllp.cn/aboutus/mobile/';
    serverUrl = './choose';
}

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
    // url: './userInfo',
    success: function success(res) {
        if (res.status == 0) {
            layer.confirm('请先绑定微信公众号 ^_^!', {
                btn :['确定'],
                btn1: function (index, layero) {
                    location.href = res.redirectUrl;
                }
            });
            return;
        }
        var data = res.data;
        var contact = data.stu_info.contact;
        if (contact) {
            $('.input-content').value = contact;
        }
        sessionStorage.setItem('userInfo', JSON.stringify(data));
    }
});
