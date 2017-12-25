$(document).ready(function() {
	var win = $(window);
	var winH = win.height();
	var winW = win.width();
	var body = $('body');

	var panelBox = $('.panel-right-function');

	// 初始化panel content高度
	$('.cmall-f-panel-list .cmall-panel-content').css('height', winH - 40 + 'px')
	$('.panel-toolbar-box').on('click','.toolbar-tabs .p-tabs',function() {
		if(!panelBox.hasClass("open")) {
			panelBox.addClass('open');
		}
		$(this).addClass('on').siblings('.p-tabs').removeClass('on');
		slidePanel($(this).attr('panel-num'));
	})

	// 收进panel
	panelBox.on('click','.cmall-f-panel-list .close-panel',function() {
		panelBox.removeClass('open');
		$('.panel-toolbar-box .toolbar-tabs .p-tabs.on').removeClass('on')
	})
})

function slidePanel(num) {
	console.log(num);
	$('.cmall-f-panel-list').each(function() {
		if($(this).attr('panel-num') == num) {
			$(this).removeClass('zoom-out').addClass('zoom-in');
		} else {
			$(this).removeClass('zoom-in').addClass('zoom-out');
		}
	})
}

function backTop() {
	$('body').animate({'scrollTop': '0px'},300)
}



