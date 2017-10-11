'use strict';

var _typeof = typeof Symbol === "function" && typeof Symbol.iterator === "symbol" ? function (obj) { return typeof obj; } : function (obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; };

 // var serverUrl = '../';
var serverUrl = 'https://wx.idsbllp.cn/activity';
// 'http://hongyan.cqupt.edu.cn/activity';
var closeHeight = '1.06666667rem',
    oneRank = 2,
    twoRank = 102,
    phone = sessionStorage.getItem('contact'),
    department = [],
    beforeOpen = void 0,
    nowData = [],
    postInfo = true;

var query = location.search;
var now = new Date();
var startTime = new Date('2017/10/10 03:00:00');

if (/ref=.*joinus/.test(query) || sessionStorage.getItem('joinus') || now < startTime) {
    sessionStorage.setItem('joíinus', true);
    document.title = '红岩网校工作站招新啦';

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
}

var data = JSON.parse(sessionStorage.getItem('userInfo'));

if (data) data = data.act_info;

if (sessionStorage.getItem('joinus')) {
    $('.content-choose').innerHTML = `
        <div class="choose">
            <div class="select select-one" rank="0">
                <div class="select-sure"><em>综合</em><i class="select-icon iconfont icon-xiala"></i></div>
                <div class="select-more">
                    <p class="select-part">红岩网校工作站</p>
                </div>
            </div>
            <div class="select select-two" rank="100">
                <div class="select-sure"><em>综合</em><i class="select-icon iconfont icon-xiala"></i></div>
                <div class="select-more">
                    <p class="select-part">综合</p>
                </div>
            </div>                                
        </div>
        <div class="choose">
            <div class="select select-one" rank="1">
                <div class="select-sure"><em>综合</em><i class="select-icon iconfont icon-xiala"></i></div>
                <div class="select-more">
                    <p class="select-part">红岩网校工作站</p>
                </div>
            </div>
            <div class="select select-two" rank="101">
                <div class="select-sure"><em>综合</em><i class="select-icon iconfont icon-xiala"></i></div>
                <div class="select-more">
                    <p class="select-part">综合</p>
                </div>
            </div>
        </div>
    `
}

//data = [[[{id: 1024,name:'web'},{id:111,name:'移动'},{id:2323,name:'视觉'}],[{id:123,name:'香梨'},{id:23,name:'红富士'}]]];

$('.content-choose').addEventListener('click', function (e) {
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
        beforeOpen.querySelector('em').setAttribute('activity_id', target.getAttribute('activity_id'));
        // if (target.getAttribute('activity_id') !== null) {
        //     department.push(parseInt(target.getAttribute('activity_id')));
        // }
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
$('.more').addEventListener('click', function () {
    if (oneRank > 4) return;
    var div = document.createElement('div');
    div.setAttribute('class', 'choose');
    if (sessionStorage.getItem('joinus')) {
        div.innerHTML = `
            <div class="select select-one" rank="1">
                <div class="select-sure"><em>综合</em><i class="select-icon iconfont icon-xiala"></i></div>
                <div class="select-more">
                    <p class="select-part">红岩网校工作站</p>
                </div>
            </div>
            <div class="select select-two" rank="101">
                <div class="select-sure"><em>综合</em><i class="select-icon iconfont icon-xiala"></i></div>
                <div class="select-more">
                    <p class="select-part">综合</p>
                </div>
            </div>
        `;
    } else {
        div.innerHTML = `
            <div class="select select-one" rank="1">
                <div class="select-sure"><em>综合</em><i class="select-icon iconfont icon-xiala"></i></div>
                <div class="select-more">
                    <p class="select-part">红岩网校工作站</p>
                    <p class="select-part">校学生会</p>
                    <p class="select-part">科技联合会</p>
                    <p class="select-part">青年志愿者协会</p>
                    <p class="select-part">社团联合会</p>
                    <p class="select-part">大学生艺术团</p>
                    <p class="select-part">团委办公室</p>
                    <p class="select-part">团委组织部</p>
                    <p class="select-part">团委宣传部</p>
                </div>
            </div>
            <div class="select select-two" rank="101">
                <div class="select-sure"><em>综合</em><i class="select-icon iconfont icon-xiala"></i></div>
                <div class="select-more">
                    <p class="select-part">综合</p>
                </div>
            </div>
        `;
    }
    $('.content-choose').appendChild(div);
    oneRank++;
    twoRank++;
});
$('.sure').addEventListener('click', function () {
    if (postInfo) {
        Array.prototype.slice.call($('.select-two .select-sure em')).forEach(function(val) {
            var activity_id = val.getAttribute("activity_id");
            activity_id && department.push(activity_id);
        });
        postInfo = false;
        ajax({
            method: 'post',
            url: serverUrl + '/wx/enroll',
            type: 'form',
            data: 'act_key=' + department + '&contact=' + phone,
            success: function success(res) {
                //window.alert(res.message);
                layer.open({
                    content : '恭喜你报名成功！',
                    btn : ['报名历史', '确定'],
                    yes : function (index, layero) {
                        var message = '';
                        var userId = JSON.parse(sessionStorage.getItem('userInfo')).stu_info.user_id;
                        ajax({
                            method: 'get',
                            url: serverUrl + '/api/user/applydata?' + 'user_id=' + userId,
                            success:function success(res) {
                                jQuery.each(res.data.data, function (key, value) {
                                   message += value.act_name + '&nbsp' + value.created_at + "<br>";
                                });
                                layer.open({
                                    title: '报名历史(最近十条)',
                                    content: message
                                });
                            },
                            error: function error(res) {
                                message = '获取失败';
                                layer.open({
                                    title: '报名历史(最近十条)',
                                    content: message
                                });
                            }
                        });
                    },
                    btn2 : function (index ,layero) {
                        //
                    }

                });
                postInfo = true;
                department.length = 0;
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
                department.length = 0;
            }
        });
    } else {
        window.alert('你点的太快了');
    }

    // console.log(phone,department);
});