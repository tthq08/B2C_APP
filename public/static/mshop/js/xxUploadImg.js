/**
 * Created by xx on 15-05-28.
 * 基于html5 canvas 的客户端异步上传画片的插件
 * 在实际应用中，常常要用于上传图片的功能．在现在越来越多的手机webapp应用中，上传图片功能的问题尤为实出，
 * 主要表显为：１　手机摄象头太牛B,随便拍拍，照片都有几Ｍ
 * 　　　　　　２　在没有wifi的情况下，移动网络上线照片还是有点慢的
 * 解决以上问题，主要的思路还是在客户端压缩图片尺寸，这就用到这个插件了
 *
 * 插件中的核心代码参照网络，自己整理了一下
 *
 * 使用方法
 * $("#xxfile").xxUploadImg({
                url: 'upload.php', //上传服务器url
                max: 100, // 上传图片的高或宽(大的那个)的最大值　,当此值为0时，不压缩
                fileType: 'image/png', //文件格式: image/png image/jpeg   经测试在微信中 jpeg无效
                param: false, //因为上传是异步的，这里是　需要传递的参数　
                callbackFun: function (ret, param) { // 上传成功后的回调函数
                    $("#show_img").attr("src", ret);
                }
            })
 */


(function ($) {
    $.fn.xxUploadImg = function (options) {
        if (typeof options == "string") {
            options = {"fileId": options};
        }
        // build main options before element iteration
        var opts = $.extend({}, $.fn.xxUploadImg.defaults, options);
        return this.each(function () {
            var $this = $(this);
            // build element specific options
            var o = $.meta ? $.extend({}, opts, $this.data()) : opts;
            o.fileObj = $this[0].files[0];

            // 获取 canvas DOM 对象
            o.canvas = document.getElementById(o.canvasId);
            if (!o.canvas) {
                o.canvas = document.createElement("canvas");
                o.canvas.style.display = "none";
            }

            // 获取 canvas的 2d 环境对象,
            // 可以理解Context是管理员，canvas是房子
            o.ctx = o.canvas.getContext("2d");

            loadImage(o);
        });
    }

    // 加载 图像文件(url路径)
    function loadImage(o) {
        //   var src = document.getElementById(o.fileId).files[0];
        // 过滤掉 非 image 类型的文件
        if (!o.fileObj.type.match(/image.*/)) {
            if (window.console) {
                console.log("选择的文件类型不是图片: ", o.fileObj.type);
            } else {
                window.confirm("只能选择图片文件");
            }

            return;
        }

        // 创建 FileReader 对象 并调用 render 函数来完成渲染.
        var reader = new FileReader();
        // 绑定load事件自动回调函数
        reader.onload = function (e) {
            // 调用前面的 render 函数
            render(e.target.result, o);
        };
        // 读取文件内容
        reader.readAsDataURL(o.fileObj);
    }


    // 渲染
    function render(src, o) {
        // 创建一个 Image 对象
        var image = new Image();
        // 绑定 load 事件处理器，加载完成后执行
        image.onload = function () {

            if (o.max > 0) {
                if (image.height > image.width) {
                    // 如果高度超标
                    if (image.height > o.max) {
                        // 宽度等比例缩放 *=
                        image.width *= o.max / image.height;
                        image.height = o.max;
                    }
                } else {
                    if (image.width > o.max) {
                        // 宽度等比例缩放 *=
                        image.height *= o.max / image.width;
                        image.width = o.max;
                    }
                }
            }

            // canvas清屏
            o.ctx.clearRect(0, 0, o.canvas.width, o.canvas.height);
            // 重置canvas宽高
            // 这里是使用canvas一个坑,就是先要给canvas设置宽高,然后才可以调用旋转等操作
            o.canvas.width = image.width;
            o.canvas.height = image.height;
            // 将图像绘制到canvas上
            o.ctx.drawImage(image, 0, 0, image.width, image.height);
            // !!! 注意，image 没有加入到 dom之中


            upload(o);
        };
        // 设置src属性，浏览器会自动加载。
        // 记住必须先绑定事件，才能设置src属性，否则会出同步问题。
        image.src = src;
    };


    function upload(o) {
        //上传
        var dataurl = o.canvas.toDataURL(o.fileType);
        // 为安全 对URI进行编码
        // data%3Aimage%2Fpng%3Bbase64%2C 开头
        var imagedata = encodeURIComponent(dataurl);
        $.post(o.url,
            {
                img: dataurl
            },
            function (ret) {
                o.callbackFun(ret, o.param);
            })
    }


    $.fn.xxUploadImg.defaults = {
        fileObj: false, //file对象

        canvasId: 'xxcanvas', //canvas标签的ID
        canvas: false, //canvas标签的ID
        ctx: false, //canvas标签的ID

        url: '', //上传服务器url
        max: 0, //压缩图片尺寸大小
        fileType: 'image/png', //文件格式 image/png image/jpeg   经测试在微信中 jpeg无效
        param: false, //需要传递的参数
        callbackFun: function (ret, param) {
        } //回调函数
    }
})(jQuery);
