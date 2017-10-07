'use strict';

var _typeof = typeof Symbol === "function" && typeof Symbol.iterator === "symbol" ? function (obj) { return typeof obj; } : function (obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; };

// let serverUrl = '/activity';
var serverUrl = 'http://wx.idsbllp.cn/activity';
// 'http://hongyan.cqupt.edu.cn/activity';
var closeHeight = '1.06666667rem',
    oneRank = 2,
    twoRank = 102,
    phone = window.location.search.split('=')[1],
    department = [],
    beforeOpen = void 0,
    nowData = [],
    postInfo = true,
    data = {};

//data = [[[{id: 1024,name:'web'},{id:111,name:'移动'},{id:2323,name:'视觉'}],[{id:123,name:'香梨'},{id:23,name:'红富士'}]]];
ajax({
    method: 'get',
    url: serverUrl + '/wx/userInfo',
    success: function success(res) {
        data = res.data.act_info;
        console.log(data);
    }
});
$('.content-choose').addEventListener('touchend', function (e) {
    var target = e.target;
    if (target.classList.contains('select-sure') || target.classList.contains('select-icon')) {
        target = target.parentElement.classList.contains('select') ? target : target.parentElement;
        if (beforeOpen != undefined) {
            beforeOpen.style.height = closeHeight;
            console.log(target);
            target.style.backgroundColor = '#fffcf0';
            beforeOpen.classList.remove('add-height');
            if (beforeOpen.getAttribute('rank') === target.parentElement.getAttribute('rank')) {
                target.querySelector('i').classList.remove('icon-xiala-copy');
                target.querySelector('i').classList.add('icon-xiala');
                beforeOpen = undefined;
            } else {
                changeIcon(target.querySelector('i'), beforeOpen.querySelector('i'));
                target.parentElement.style.height = parseFloat(closeHeight) * (target.nextElementSibling.children.length + 1) + 'rem';
                target.style.backgroundColor = '#faf60';
                target.parentElement.classList.add('add-height');
                beforeOpen = target.parentElement;
            }
        } else {
            target.parentElement.style.height = parseFloat(closeHeight) * (target.nextElementSibling.children.length + 1) + 'rem';
            //console.log(target.nextElementSibling.children.length + 1)
            target.style.backgroundColor = '#ffbb77';
            target.parentElement.classList.add('add-height');
            target.querySelector('i').classList.remove('icon-xiala');
            target.querySelector('i').classList.add('icon-xiala-copy');
            beforeOpen = target.parentElement;
        }
    }

    if (target.classList.contains('select-part') && target.parentElement.parentElement.classList.contains('select-one')) {
        beforeOpen.querySelector('em').innerText = target.innerText;
        beforeOpen.classList.remove('add-height');
        beforeOpen.style.height = closeHeight;
        beforeOpen.children[0].style.backgroundColor = '#fffcf0';
        beforeOpen.nextElementSibling.querySelector('em').innerText = '综合';
        beforeOpen.querySelector('i').classList.remove('icon-xiala-copy');
        beforeOpen.querySelector('i').classList.add('icon-xiala');
        nowData = data[target.innerText];
        beforeOpen.nextElementSibling.children[1].innerHTML = createSelectTwo(nowData);
        beforeOpen = undefined;
    } else if (target.classList.contains('select-part') && target.parentElement.parentElement.classList.contains('select-two')) {
        beforeOpen.querySelector('em').innerText = target.innerText;
        if (target.getAttribute('activity_id') !== null) {
            department.push(parseInt(target.getAttribute('activity_id')));
        }
        //console.log(department)
        beforeOpen.classList.remove('add-height');
        beforeOpen.style.height = closeHeight;
        beforeOpen.children[0].style.backgroundColor = '#fffcf0';
        beforeOpen.querySelector('i').classList.remove('icon-xiala-copy');
        beforeOpen.querySelector('i').classList.add('icon-xiala');
        beforeOpen = undefined;
    }
});

function createSelectTwo(data) {
    var ele = '';
    data.forEach(function (element) {
        ele += '<p class="select-part" activity_id="' + element.activity_id + '">' + element.activity_name + '</p>';
    }, this);
    return ele;
}

function changeIcon(nowEle, beforeEle) {
    nowEle.classList.remove('icon-xiala');
    nowEle.classList.add('icon-xiala-copy');
    beforeEle.classList.remove('icon-xiala-copy');
    beforeEle.classList.add('icon-xiala');
}
$('.more').addEventListener('touchstart', function () {
    if (oneRank > 8) return;
    var div = document.createElement('div');
    div.setAttribute('class', 'choose');
    div.innerHTML = '<div class="select select-one" rank="' + oneRank + '">\n                        <div class="select-sure"><em>\u7EFC\u5408</em><i class="select-icon iconfont icon-xiala"></i></div>\n                        <div class="select-more">\n                            <p class="select-part">\u7EA2\u5CA9\u7F51\u6821\u5DE5\u4F5C\u7AD9</p>\n                            <p class="select-part">\u6821\u5B66\u751F\u4F1A</p>\n                            <p class="select-part">\u79D1\u6280\u8054\u5408\u4F1A</p>\n                            <p class="select-part">\u6821\u56E2\u59D4\u5404\u90E8\u5BA4</p>\n                            <p class="select-part">\u9752\u5E74\u5FD7\u613F\u8005\u534F\u4F1A</p>\n                            <p class="select-part">\u793E\u56E2\u8054\u5408\u4F1A</p>\n                            <p class="select-part">\u5927\u5B66\u751F\u827A\u672F\u56E2</p>\n                        </div>\n                    </div>\n                    <div class="select select-two" rank="' + twoRank + '">\n                        <div class="select-sure"><em>\u7EFC\u5408</em><i class="select-icon iconfont icon-xiala"></i></div>\n                        <div class="select-more">\n                            <p class="select-part">\u7EFC\u5408</p>\n                        </div>\n                    </div> ';
    $('.content-choose').appendChild(div);
    oneRank++;
    twoRank++;
});
$('.sure').addEventListener('touchstart', function () {
    if (postInfo) {
        postInfo = false;
        ajax({
            method: 'post',
            url: serverUrl + '/wx/enroll',
            type: 'form',
            data: 'act_key=' + department + '&contact=' + phone,
            success: function success(res) {
                window.alert(res.message);
                postInfo = true;
            },
            error: function error(res) {
                if (_typeof(res.message) === "object") {
                    for (var mes in res.message) {
                        window.alert(mes + res.message[mes]);
                    }
                } else {
                    window.alert('操作不对哦~');
                }

                postInfo = true;
            }
        });
    } else {
        window.alert('你点的太快了');
    }

    // console.log(phone,department);
});