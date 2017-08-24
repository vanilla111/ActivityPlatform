'use strict';

var closeHeight = '1.06666667rem',
    oneRank = 2,
    twoRank = 102,
    phone = window.location.search.split('=')[1],
    department = [],
    beforeOpen = void 0,
    nowData = [],
    data = [[{ id: 1024, name: 'web' }, { id: 111, name: '移动' }, { id: 2323, name: '视觉' }], [{ id: 123, name: '香梨' }, { id: 23, name: '红富士' }]];
$('.content-choose').addEventListener('touchend', function (e) {
    var target = e.target;
    if (target.classList.contains('select-sure') || target.classList.contains('select-icon')) {
        target = target.parentElement.classList.contains('select') ? target : target.parentElement;
        if (beforeOpen != undefined) {
            beforeOpen.style.height = closeHeight;
            beforeOpen.classList.remove('add-height');
            if (beforeOpen.getAttribute('rank') === target.parentElement.getAttribute('rank')) {
                target.querySelector('i').classList.remove('icon-xiala-copy');
                target.querySelector('i').classList.add('icon-xiala');
                beforeOpen = undefined;
            } else {
                changeIcon(target.querySelector('i'), beforeOpen.querySelector('i'));
                target.parentElement.style.height = parseInt(closeHeight) * (target.nextElementSibling.children.length + 1) + 'rem';
                target.parentElement.classList.add('add-height');
                beforeOpen = target.parentElement;
            }
        } else {
            target.parentElement.style.height = parseInt(closeHeight) * (target.nextElementSibling.children.length + 1) + 'rem';
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
        beforeOpen.nextElementSibling.querySelector('em').innerText = '综合';
        beforeOpen.querySelector('i').classList.remove('icon-xiala-copy');
        beforeOpen.querySelector('i').classList.add('icon-xiala');
        nowData = data[parseInt(target.getAttribute('arr'))];
        beforeOpen.nextElementSibling.children[1].innerHTML = createSelectTwo(nowData);
        beforeOpen = undefined;
    } else if (target.classList.contains('select-part') && target.parentElement.parentElement.classList.contains('select-two')) {
        beforeOpen.querySelector('em').innerText = target.innerText;
        console.log(target.innerText);
        department.push(parseInt(target.getAttribute('id')));
        beforeOpen.classList.remove('add-height');
        beforeOpen.style.height = closeHeight;
        beforeOpen.querySelector('i').classList.remove('icon-xiala-copy');
        beforeOpen.querySelector('i').classList.add('icon-xiala');
        beforeOpen = undefined;
    }
});

function createSelectTwo(data) {
    var ele = '';
    data.forEach(function (element) {
        ele += '<p class="select-part" id="' + element.id + '">' + element.name + '</p>';
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
    var div = document.createElement('div');
    div.setAttribute('class', 'choose');
    div.innerHTML = '<div class="select select-one" rank="' + oneRank + '">\n                        <div class="select-sure"><em>\u7EFC\u5408</em><i class="select-icon iconfont icon-xiala"></i></div>\n                        <div class="select-more">\n                            <p class="select-part" arr="0">\u7EA2\u5CA9</p>\n                            <p class="select-part" arr="1">\u6C34\u679C</p>\n                        </div>\n                    </div>\n                    <div class="select select-two" rank="' + twoRank + '">\n                        <div class="select-sure"><em>\u7EFC\u5408</em><i class="select-icon iconfont icon-xiala"></i></div>\n                        <div class="select-more">\n                            <p class="select-part">\u7EFC\u5408</p>\n                        </div>\n                    </div> ';
    $('.content-choose').appendChild(div);
    oneRank++;
    twoRank++;
});
$('.sure').addEventListener('touchstart', function () {
    ajax({
        method: 'get',
        url: '/activity/public/wx/userInfo',
        success: function success(res) {
            //console.log(res)
        }
    });
    ajax({
        method: 'post',
        url: '/activity/public/wx/enroll',
        type: 'form',
        data: 'act_key=' + [1000] + '&contact=' + phone,
        success: function success(res) {
            window.alert(res.message);
        }

    });
    // console.log(phone,department);
});