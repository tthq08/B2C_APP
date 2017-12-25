
$(function(){


    $(".side-box").niceScroll({
        cursorcolor: "#000000",
        zindex: 999999,
        bouncescroll: true,
        cursoropacitymax: 0.4,
        cursorborder: "",
        cursorborderradius: 0,
        cursorwidth: "7px",
        railalign: "left",
        railoffset: {
            top: 45,
            left: 0
        }
    });



     $(".sub-box").niceScroll({
        cursorcolor: "#000000",
        zindex: 999999,
        bouncescroll: true,
        cursoropacitymax: 0.4,
        cursorborder: "",
        cursorborderradius: 0,
        cursorwidth: "5px",
        railalign: "right",
        railoffset: {
            top: 0,
            right: 0,
        }
    });



    $(".content-wrapper").niceScroll({
        cursorcolor: "#000000",
        zindex: 999999,
        bouncescroll: true,
        cursoropacitymax: 0.4,
        cursorborder: "",
        cursorborderradius: 7,
        cursorwidth: "7px",
        background: "rgba(0,0,0,.1)",
        autohidemode: false,
        railpadding: {
            top: 0,
            right: 2,
            left: 2,
            bottom: 0
        }
    });

    $('.nav-item').on('click','li',function(){  //切换侧换目录
        $(this).addClass('active').siblings().removeClass('active');
        $('.side-menu-tab').eq($(this).index()).css('display','block').siblings().css('display','none');
    })


    $('.menu-item').on('click','a',function(){  //切换侧栏二级目录
        //console.log($(this).parent().find('a'),$(this).index());
        $(this).addClass('active').siblings().removeClass('active');

        $(this).parent().siblings('.sub-box').find('.sub-menu').eq($(this).index()).css('display','block').siblings().css('display','none');

    })


    $('.catalog-menu-item span .fa').click(function(){
        if($(this).hasClass('fa-plus')){
            $(this).removeClass('fa-plus').addClass('fa-minus');
            $(this).parent().siblings().addClass('active');
        }else{

            $(this).removeClass('fa-minus').addClass('fa-plus');
            $(this).parent().siblings().removeClass('active');
        }
        
    })


    $('.catalog-retract').click(function(){

        $('.catalog-list .fa').each(function() {

            if($(this).hasClass('fa-minus')){
                $(this).removeClass('fa-minus').addClass('fa-plus');
            }
            
        });

        $('.catalog-list').find('.catalog-dropdown-menu').removeClass('active');

    })





    $('.navbar-brand').click(function(){
        if($('#wrap').hasClass('fullscreen-mode')){

            $('#wrap').removeClass('fullscreen-mode');

        }
    })

    var widthLess1024 = function() {
    if ($(window).width() < 1024) {
        $("#sidebar, #navbar").addClass("collapsed");
        $("#navigation").find(".dropdown.open").removeClass("open");
        $("#navigation").find(".dropdown-menu.animated").removeClass("animated");
        if ($("#sidebar").hasClass("collapsed")) {
            $("#content").animate({
                left: "0px",
                paddingLeft: "40px"
            },
            150)
        } else {
            $("#content").animate({
                paddingLeft: "40px"
            },
            150)
        }
    } else {

        $("#sidebar, #navbar").removeClass("collapsed");
        if ($("#sidebar").hasClass("collapsed")) {
            $("#content").animate({
                left: "210px",
                paddingLeft: "220px"
            },
            150)
            
        } else {
            $("#content").animate({
                paddingLeft: "220px"
            },
            150)
            
        }
    }
};
var widthLess768 = function() {
    if ($(window).width() < 768) {
        if ($(".collapsed-content .search").length === 1) {
            $("#main-search").appendTo(".collapsed-content .search")
        }
        if ($(".collapsed-content li.user").length === 0) {
            $(".collapsed-content li.search").after($("#current-user"))
        }
    } else {
        $("#current-user").show();
        if ($(".collapsed-content .search").length === 2) {
            $(".nav.refresh").after($("#main-search"))
        }
        if ($(".collapsed-content li.user").length === 1) {
            $(".quick-actions >li:last-child").before($("#current-user"))
        }
    }
};


$("#color-schemes li a").click(function() {
        var d = $(this).attr("class");
        var e = $("body").attr("class").split(" ").pop();
        document.cookie="thinkbiz_sys_skin="+d;
        console.log(document.cookie.split(';'));
        $("body").removeClass(e).addClass(d);
    });
      $(".page-refresh").click(function() {
        location.reload()
    });


$(".sidebar-collapse a").on("click",
    function() {
        $("#sidebar, #navbar").toggleClass("collapsed");
        //$("#navigation").find(".dropdown.open").removeClass("open");
        //$("#navigation").find(".dropdown-menu.animated").removeClass("animated");
        //$("#sidebar > li.collapsed").removeClass("collapsed");
        if ($("#sidebar").hasClass("collapsed")) {
            if ($(window).width() < 1024) {
                $("#content").animate({
                    left: "0px"
                },
                150)
            } else {
                $("#content").animate({
                    paddingLeft: "40px"
                },
                150)
            }
            setDesk();
        } else {
            if ($(window).width() < 1024) {
                $("#content").animate({
                    left: "210px"
                },
                150)
            } else {
                $("#content").animate({
                    paddingLeft: "220px"
                },
                150)
            }
            setDesk();
        }
    });


    widthLess1024();
    widthLess768();
    

    $(window).resize(function() {
        setDesk();
        widthLess1024();
        widthLess768()
    });

    var flag;

    $('#sidebar').hover(function(){

        if($(this).hasClass('collapsed')){

            flag = true; //标记是否有collapsed

            $("#sidebar").addClass("open");

        }
    },function(){
        if(flag){
            $("#sidebar").removeClass("open");
            flag= false;

            setDesk();
        }

    })


    // 设置iframe层的宽高和位置  
    function setDesk(){
        //console.log($(window).width(),$('.side-box').width(),$('.side-box').height());
        var winW = $(window).width(),
            deskL = $('.side-box').width(),
            deskW = winW - deskL;
            deskH = $('.side-box').height();

        $('#deskContainer').css({
            'top':0,
            'left':deskL,
            'width':deskW,
            'height':deskH
        })
    } 


    var indexSpeed = 100;
 

    $(document).on('click','.app-btn',function(){
        iframeOpen(this);
    });

    $(document).on('click','#clearCache',function(){
        iframeOpen(this);
    });


    function iframeOpen(obj) {

        setDesk();

        $(this).parent().addClass('active').siblings().removeClass('active');

        $('#wrap').addClass('fullscreen-mode');
        var url = $(obj).attr('data-url');
        var id= $(obj).attr('data-id');
        var iframe_id = 'iframe-'+id;


        if($('#deskContainer').find('#iframe-'+id).length){

            $('#deskContainer').find('#iframe-'+id).show().siblings().hide();

            // $('#deskContainer iframe').each(function(){
            //     //console.log($(this).attr('id'));
            //     if($(this).attr('id') == iframe_id){
            //         $(this).show();
            //     }else{
            //         $(this).hide();
            //     }
            // })

        }else{

            $('#deskContainer').find('iframe').hide();

            $('#deskContainer').append('<iframe id="iframe-'+id+'" name="iframe-'+id+'" src="'+ url +'" frameborder="no" allowtransparency="true" scrolling="auto" hidefocus="" style="width: 100%; height: 100%; left: 0px;"></iframe>')

        }
    }





$(window).load(function() {

    $("#loader").delay(500).fadeOut(300);
    $(".mask").delay(800).fadeOut(300,
    function() {
        widthLess1024();
        widthLess768()
    })
});


   /*
	$('.nav-item').on('click','li',function(){
		//console.log($(this),$(this).index());
        $(this).addClass('active').siblings().removeClass('active');
		$('.sidebar-item').eq($(this).index()).css('display','block').siblings().css('display','none');
	})

    $('.index-item-list').click(function() {
        $(".content-wrapper").find("iframe.J_iframe").hide()
        $(".content-wrapper").find('.index_content').show();

    });

	$('.sidebar-subitem').on('click','li',function(){

		$(this).addClass('active').siblings().removeClass('active');
        var _self = $(this);
		var param = $(this).attr('data-param');

		$.ajax({
            type: "GET",
            async:false,
            url: "../../pages/"+param+".html",
            success: function (data) {

                _self.parents('.sidebar-item').find('.sub-nav-box').html(data);
            }
        })

	})



    var index = 1;

    $('.sub-nav-box').on('click','.menuItem',function(){
        if (!$(this).attr("data-index")) {
            $(this).attr("data-index", index);
            index++;
        }
        $(this).addClass('active').siblings().removeClass('active');
    })





     function c() {

        var o = $(this).attr("data-href"),
            m = $(this).data("index"),
            l = $.trim($(this).text()),
            k = true;
        if (o == undefined || $.trim(o).length == 0) {
            return false
        }

        console.log(o);

        $(".menuTab").each(function () {

            if ($(this).data("id") == o) {
                if (!$(this).hasClass("active")) {
                    $(this).addClass("active").siblings(".menuTab").removeClass("active");
                    //g(this);

                    $(".content-wrapper .J_iframe").each(function () {

                        if ($(this).data("id") == o) {
                            $(this).show().siblings().hide();
                            return false
                        }
                    })
                }
                k = false;
                return false
            }
        });

        if (k) {

            var p = '<a href="javascript:;" class="menuTab active" data-id="' + o + '">' + l + ' <i>&times;</i></a>';
            $(".menuTab").removeClass("active");
            var n = '<iframe class="J_iframe" name="iframe' + m + '" width="100%" height="100%" src="' + o + '" frameborder="0" data-id="' + o + '" seamless></iframe>';
            console.log(n);

            //$(".mainContent").find("iframe.J_iframe").hide().parents(".mainContent").append(n);


            $(".content-wrapper").find('.index_content').hide();

            if($(".content-wrapper").find("iframe.J_iframe").length){

                $(".content-wrapper").find("iframe.J_iframe").hide().parents(".content-wrapper").append(n);

            }else {

               $(".content-wrapper").append(n);
            }

            $(".menuTabs").append(p);
            //g($(".menuTab.active"))
        }
        return false
    }

     $(".sub-nav-box").on("click",'.menuItem', c);


      function h() {

        var m = $(this).parents(".menuTab").data("id");

        var l = $(this).parents(".menuTab").width();

        if ($(this).parents(".menuTab").hasClass("active")) {
            console.log('active');
            console.log($(this).parents(".menuTab").next(".menuTab").size());
            if ($(this).parents(".menuTab").next(".menuTab").size()) {

                var k = $(this).parents(".menuTab").next(".menuTab:eq(0)").data("id");
                $(this).parents(".menuTab").next(".menuTab:eq(0)").addClass("active");
                $(".content-wrapper .J_iframe").each(function () {
                    if ($(this).data("id") == k) {
                        $(this).show().siblings().hide();
                        return false
                    }
                });
                var n = parseInt($(".page-tabs-content").css("margin-left"));
                if (n < 0) {
                    $(".page-tabs-content").animate({
                        marginLeft: (n + l) + "px"
                    }, "fast")
                }
                $(this).parents(".menuTab").remove();
                $(".content-wrapper .J_iframe").each(function () {
                    if ($(this).data("id") == m) {
                        $(this).remove();
                        return false
                    }
                })
            }else if ($(this).parents(".menuTab").prev(".menuTab").size()) {
                console.log(222);
                var k = $(this).parents(".menuTab").prev(".menuTab:last").data("id");
                $(this).parents(".menuTab").prev(".menuTab:last").addClass("active");
                $(".content-wrapper .J_iframe").each(function () {
                    if ($(this).data("id") == k) {
                        $(this).show().siblings().hide();
                        return false
                    }
                });
                $(this).parents(".menuTab").remove();
                $(".content-wrapper .J_iframe").each(function () {
                    if ($(this).data("id") == m) {
                        $(this).remove();
                        return false
                    }
                })
            }else {
                $(this).parents(".menuTab").remove();
                $(".content-wrapper .J_iframe").each(function () {
                    if ($(this).data("id") == m) {
                        $(".content-wrapper").find('.index_content').show();
                        $(this).remove();
                        return false
                    }
                })
            }

        } else {
            console.log(333);
            $(this).parents(".menuTab").remove();
            $(".content-wrapper .J_iframe").each(function () {
                if ($(this).data("id") == m) {
                    $(this).remove();
                    return false
                }
            });
            //g($(".menuTab.active"))
        }
        return false
    }
    $(".menuTabs").on("click", ".menuTab i", h);

    function i() {
        $(".page-tabs-content").children("[data-id]").not(":first").not(".active").each(function () {
            $('.J_iframe[data-id="' + $(this).data("id") + '"]').remove();
            $(this).remove()
        });
        $(".page-tabs-content").css("margin-left", "0")
    }
    $(".J_tabCloseOther").on("click", i);

    function j() {
        //g($(".menuTab.active"))
    }
    $(".J_tabShowActive").on("click", j);

    function e() {
        if (!$(this).hasClass("active")) {
            var k = $(this).data("id");
            $(".content-wrapper .J_iframe").each(function () {
                if ($(this).data("id") == k) {
                    $(this).show().siblings().hide();
                    return false
                }
            });
            $(this).addClass("active").siblings(".menuTab").removeClass("active");
            //g(this)
        }
    }
    $(".menuTabs").on("click", ".menuTab", e);

    function d() {
        var l = $('.J_iframe[data-id="' + $(this).data("id") + '"]');
        var k = l.attr("src");
    }
    $(".menuTabs").on("dblclick", ".menuTab", d);

    */


});

