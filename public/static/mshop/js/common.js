
var win = $(window);
var body = $('body');
var doc = $(document);
var winH = win.height()/2;
var winW = win.width();

win.scroll(function() {
	if(doc.scrollTop() > winH) {
		$('.backtop').addClass('on')
	} else {
		$('.backtop').removeClass('on')
	}
})

function toggleUser() {
	$('.fd-user').toggleClass('on');
}











