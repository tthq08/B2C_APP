
$(document).ready(function() {
	// 点击批量操作按钮显示内容
	$('.batch-shop-btn').click(function() {
		$('.batch-check-shop .batch-show').show();
		$(this).hide();
		$('.cmall-shop-check').addClass('check');
		$('.cmall-shop-check .item-check').addClass('check');
	})
	// 批量操作全选功能按钮
	$('.batch-check-shop .batch-show').on('click','.u-check',function() {
		var me = $(this);
		me.toggleClass('on');
		if(me.attr('all-check') == '1') {
			me.attr('all-check','0');
			batchCancelAllCheck();
		} else {
			me.attr('all-check','1');
			batchAllCheck();
		}
	})
	// 完成批量操作按钮
	$('.cmall-shop-check').on('click','.item-check.check',function() {
		var me = $(this);
		me.find('.i-check').toggleClass('on');
		var input = me.find('.input-check');
		if(input.prop('checked')) {
			input.prop('checked',false);
		} else {
			input.prop('checked',true);
		}
	})
})

function batchAllCheck() {
	$('.cmall-shop-check .item-check').find('input').prop('checked',true);
	$('.cmall-shop-check .item-check').find('.i-check').addClass('on');
}
function batchCancelAllCheck() {
	$('.cmall-shop-check .item-check').find('input').prop('checked',false);
	$('.cmall-shop-check .item-check').find('.i-check').removeClass('on');
}






















