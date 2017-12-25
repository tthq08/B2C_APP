 /**
 * addcart 将商品加入购物车
 * @goods_id  商品id
 * @num   商品数量
 * @form_id  商品详情页所在的 form表单
 * @to_catr 加入购物车后再跳转到 购物车页面 默认不跳转 1 为跳转
 */
function AjaxAddCart(goods_id,num,to_catr)
{                                                
    // 如果有商品规格 说明是商品详情页提交
    if($("#buy_goods_form").length > 0){
    	if ($('#goods_stock').val()<num) {
    		layer.msg('Sorry,商品库存不足~');
    		return false;
    	}
        $.ajax({
            type : "POST",
            url:"/index.php/shop/Cart/ajaxAddCart",
            data : $('#buy_goods_form').serialize(),// 你的formid 搜索表单 序列化提交                        
			dataType:'json',
            success: function(data){
				if(data.code < 0)
				{
					layer.alert(data.msg, {icon: 2});
					return false;
				}
				getsCart = false;
				layer.confirm('加入购物车成功',{btn:['继续购物','购物车结算']},function () {
					cart_num = parseInt($('#cart_quantity').html())+parseInt($('input[name="goods_num"]').val());
				    $('#cart_quantity').html(cart_num);
					$('#cart_goods_num').html(cart_num);

					layer.closeAll();
				},function () {
					location.href = "/shop/Cart/cart";
				});					
			   // 加入购物车后再跳转到 购物车页面
			  //  if(to_catr == 1)  //直接购买
			  //  {
			  //  		layer.msg('加入购物车成功',{time:1000},function() {
				 //   		location.href = "/index.php/shop/Cart/cart";   
			  //  		});
			  //  }
			  //  else
			  //  {
				 //    cart_num = parseInt($('#cart_quantity').html())+parseInt($('input[name="goods_num"]').val());
				 //    $('#cart_quantity').html(cart_num);
					// $('#cart_goods_num').html(cart_num);
					// layer.msg('加入购物车成功');
			  //  }
            }
        });     
    }else{ // 否则可能是商品列表页 收藏页 等点击加入购物车的
        $.ajax({
            type : "POST",
            url:"/index.php/shop/Cart/ajaxAddCart",
            data :{goods_id:goods_id,goods_num:num} ,
			dataType:'json',
            success: function(data){   							   							   
			   if(data.code == -1)
			   {
			   		layer.msg(data.msg,{time:2000},function() {
						location.href = "/index.php/goods/"+goods_id;   
			   		});
			   }
			   else
			   {
				    // 加入购物车有误
				    if(data.code < 0)
					{
						layer.alert(data.msg, {icon: 2});
						return false;
					}
					layer.confirm('加入购物车成功',{btn:['继续购物','购物车结算']},function () {
						cart_num = parseInt($('#cart_quantity').html())+parseInt(num);
					    // console.log(cart_num);
					    $('#cart_quantity').html(cart_num);
						$('#cart_goods_num').html(cart_num);
						layer.closeAll();
					},function () {
						location.href = "/public/index.php/shop/Cart/cart";
					});	
				 //    cart_num = parseInt($('#cart_quantity').html())+parseInt(num);
				 //    console.log(cart_num);
				 //    $('#cart_quantity').html(cart_num);
					// $('#cart_goods_num').html(cart_num);
					// layer.msg('加入购物车成功');					   
			   }							   							   
            }
        });            
    }
}
