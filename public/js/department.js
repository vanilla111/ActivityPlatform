'use strict';

function $(ele) {
    var eles = document.querySelectorAll(ele);
    return eles.length === 1 ? eles[0] : eles;
}

var query = location.search;
var now = new Date();
var startTime = new Date('2017/10/11 03:00:00');

if (/ref=.*joinus/.test(query) || now < startTime) {
    document.title = '红岩网校工作站招新啦';
    location.href = 'https://wx.idsbllp.cn/activity/wx/index';
}

var department = [
    {
        name: '红岩网校工作站',
        logo: 'redrock',
        H5url: 'https://wx.idsbllp.cn/joinus2017/?ref='+location.href
    },
    {
        name: '学生社团联合会',
        logo: 'shelian',
        H5url: 'http://u5073382.viewer.maka.im/pcviewer/UCLQV5O9'
    },
    {
        name: '学生科技联合会',
        logo: 'kelian',
        H5url: 'http://www.cqupt-sstu.cn/'
    },
    {
        name: '团委办公室',
        logo: 'tuanwei',
        H5url: 'http://u4956478.viewer.maka.im/k/TZIIQ653'
    },
    {
        name: '大学生艺术团',
        logo: 'yishutuan',
        H5url: 'http://u5193531.viewer.maka.im/pcviewer/G5J532W2'
    },
    {
        name: '青年志愿者协会',
        logo: 'qingxie',
        H5url: 'http://u5084437.viewer.maka.im/pcviewer/FW8FHQK0'
    },
    {
        name: '学生会',
        logo: 'xueshenghui',
        H5url: 'https://s.wcd.im/v/2bpk8Z36/'
    },
    {
        name: '团委宣传部',
        logo: 'tuanxuan',
        H5url: 'http://u5152512.viewer.maka.im/k/IXQEF5RJ'
    },
    {
        name: '团委组织部',
        logo: 'tuanzu',
        H5url: 'http://u5276013.viewer.maka.im/pcviewer/EHC3F64W'
    },
];

department.sort(function(a, b) {
    return Math.random() > 0.2 ? 1 : -1;
});


var departmentNumber = department.length;
var departmentsHTML = '';

department.forEach(function(val) {
    departmentsHTML += `
        <a href="${val.H5url}" class="asdasda">
            <img src="../imgs/pic_${val.logo}.png" class="asdasda-img">
            <span class="asdasda-name">${val.name}</span>
        </a>
    `
});

$('.departments').innerHTML = departmentsHTML;


// var department = ['redrock', 'kelian', 'qingxie', 'shelian', 'tuanwei', 'xueshenghui', 'yishutuan'];
// var H5url = ['https://wx.idsbllp.cn/joinus2017/?ref='+location.href, 'http://www.cqupt-sstu.cn/', 'http://u5084437.viewer.maka.im/pcviewer/FW8FHQK0', 'http://u5073382.viewer.maka.im/pcviewer/UCLQV5O9', 'http://u4956478.viewer.maka.im/k/TZIIQ653', 'https://s.wcd.im/v/2bpk8Z36/', 'http://u5193531.viewer.maka.im/pcviewer/G5J532W2'];

// showDetail(departmentIndex, 'right');

// $('.arrow-right').addEventListener('click', function (e) {
//     departmentIndex++;
//     if (departmentIndex >= departmentNumber) {
//         departmentIndex = 0;
//     }
//     showDetail(departmentIndex, 'right');
// });
// $('.arrow-left').addEventListener('click', function (e) {
//     departmentIndex--;
//     if (departmentIndex < 0) {
//         departmentIndex = departmentNumber - 1;
//     }
//     showDetail(departmentIndex, 'left');
// });

// function showDetail() {
//     var index = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : departmentNumber;
//     var direction = arguments[1];

//     if (index < 0 || index >= departmentNumber) {
//         return;
//     }

//     var bannerImg = $('.carousel-bannerimg');
//     var bannerImgCopy = bannerImg.cloneNode(true);

//     var frame = $('.carousel-frame');
//     var frameCopy = frame.cloneNode(true);

//     bannerImg.remove();
//     frame.remove();

//     bannerImgCopy.src = '../imgs/banner_' + department[departmentIndex] + '.png';
//     $('.carousel-banner').insertBefore(bannerImgCopy, $('.arrow-right'));

//     frameCopy.className = 'carousel-frame asdasd rotate-' + direction;
//     console.log(frameCopy);
//     frameCopy.firstElementChild.src = '../imgs/pic_' + department[departmentIndex] + '.png';
//     carousel.insertBefore(frameCopy, $('.msg'));

//     $('.carousel-frameimg').addEventListener('click', function (e) {
//         location.href = H5url[departmentIndex];
//     });
// }
