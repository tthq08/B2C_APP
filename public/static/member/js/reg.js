/**
 * 用户注册，登录js
 * 基于jQuery/layer,请在加载文件前先加载jQuery/layer
 * company: 深圳市俊网网络有限公司
 * http: www.junnet.net
 * author: 吴跃忠
 * time: 2017/6/15
 */

/**
 * 获取手机验证码
 * @param url
 */
function getSmsCode(url)
{
    // 获取验证码
    var mobile = $('#mobile').val();
    var code = $('#code').val();
    $.ajax({
        type:"POST",
        url:url,
        data:{mobile:mobile,code:code},
        success:function (data) {
            if( data.code == 1 )
            {
                alert(data.msg);
            }else{
                getImgCode();
                alert(data.msg);
            }
        }
    })
}


/**
 * 提交注册页面
 * @returns {boolean}
 */
function sub() {
    // 数据验证
    var agreement = $("input[name='agreement']:checked").val();
    if( agreement == undefined ){
        layer.msg('请认真阅读并同意《风生水起服务协议》');
        return false;
    }
    var url = $('#reg_sub').attr('action');
    var nickname = $('input[name="nickname"]').val();
    var username = $('input[name="username"]').val();
    var mobile = $('input[name="mobile"]').val();
    var code = $('input[name="code"]').val();
    var sms_code = $('input[name="sms_code"]').val();
    var password = $('input[name="password"]').val();
    var re_password = $('input[name="re_password"]').val();
    $.ajax({
        type:"POST",
        url:url,
        data:{nickname:nickname,username:username,mobile:mobile,code:code,sms_code:sms_code,password:password,re_password:re_password},
        success:function (data) {
            if( data.code == 1 ){
                layer.msg(data.msg);
                setTimeout(function () {
                    location.href=data.url;
                },3000);
            }else{
                layer.msg(data.msg);
            }
        }
    })

}

/**
 * 提交登录
 */
function sub_log() {
    var url = $('#login_sub').attr('action');
    var username = $('input[name="username"]').val();
    var password = $('input[name="password"]').val();
    var code = $('input[name="code"]').val();
    $.ajax({
        type:"POST",
        url:url,
        data:{username:username,password:password,code:code},
        success:function (data) {
            if( data.code == 1 ){
                layer.msg(data.msg);
                setTimeout(function () {
                    location.href=data.url;
                },3000);
            }else{
                getImgCode();
                layer.msg(data.msg);
            }
        }
    })
}

/**
 * 提交登录
 */
function sub_mobile_log() {
    var url = $('#login_mobile_sub').attr('action');
    var mobile = $('input[name="mobile"]').val();
    var sms_code = $('input[name="sms_code"]').val();
    $.ajax({
        type:"POST",
        url:url,
        data:{mobile:mobile,sms_code:sms_code},
        success:function (data) {
            if( data.code == 1 ){
                layer.msg(data.msg);
                setTimeout(function () {
                    location.href=data.url;
                },3000);
            }else{
                getImgCode();
                layer.msg(data.msg);
            }
        }
    })
}


function getImgCode() {
    var url = $('#imgcode').attr('data-src');

    $('#imgcode').attr('src',url+'?time='+Math.random());
}