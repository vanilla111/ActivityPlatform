'use strict';

function $(ele) {
    var eles = document.querySelectorAll(ele);
    return eles.length === 1 ? eles[0] : eles;
}

var department = ['redrock', 'kelian', 'qingxie', 'shelian', 'tuanwei', 'xueshenghui', 'yishutuan'];
var H5url = ['https://wx.idsbllp.cn/joinus2017/', 'http://www.cqupt-sstu.cn/', 'http://u5084437.viewer.maka.im/pcviewer/FW8FHQK0', 'http://u5073382.viewer.maka.im/pcviewer/UCLQV5O9', 'http://u4956478.viewer.maka.im/k/TZIIQ653', 'https://s.wcd.im/v/2bpk8Z36/', 'http://u5193531.viewer.maka.im/pcviewer/G5J532W2'];

var departmentNumber = department.length;
var departmentIndex = Math.floor(Math.random() * departmentNumber);
var carousel = $('.carousel');

showDetail(departmentIndex, 'right');

$('.arrow-right').addEventListener('click', function (e) {
    departmentIndex++;
    if (departmentIndex >= departmentNumber) {
        departmentIndex = 0;
    }
    showDetail(departmentIndex, 'right');
});
$('.arrow-left').addEventListener('click', function (e) {
    departmentIndex--;
    if (departmentIndex < 0) {
        departmentIndex = departmentNumber - 1;
    }
    showDetail(departmentIndex, 'left');
});

function showDetail() {
    var index = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : departmentNumber;
    var direction = arguments[1];

    if (index < 0 || index >= departmentNumber) {
        return;
    }

    var bannerImg = $('.carousel-bannerimg');
    var bannerImgCopy = bannerImg.cloneNode(true);

    var frame = $('.carousel-frame');
    var frameCopy = frame.cloneNode(true);

    bannerImg.remove();
    frame.remove();

    bannerImgCopy.src = '../imgs/banner_' + department[departmentIndex] + '.png';
    $('.carousel-banner').insertBefore(bannerImgCopy, $('.arrow-right'));

    frameCopy.className = 'carousel-frame asdasd rotate-' + direction;
    console.log(frameCopy);
    frameCopy.firstElementChild.src = '../imgs/pic_' + department[departmentIndex] + '.png';
    carousel.insertBefore(frameCopy, $('.msg'));

    $('.carousel-frameimg').addEventListener('click', function (e) {
        location.href = H5url[departmentIndex];
    });
}