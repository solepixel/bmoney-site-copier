jQuery(function($){
	$('#test-connections').click(function(){
		var $btn = $(this);
		if($btn.find('span')){
			$btn.find('span').remove();
		}
		$btn.append('<span class="spinner" style="display:inline-block;float:none;margin:2px 0 0 5px;" />');
		if($('.bmsc-connection-report').length){
			$('.bmsc-connection-report').remove();
		}
		var vars = $btn.parents('form').serialize();
		vars += '&action=bmsc_test_connections'
		$.ajax({
			url: '/wp-admin/admin-ajax.php', // todo use localization for this var
			type: 'post',
			dataType: 'json',
			data: vars,
			success: function(response){
				var $result = $('<span />');
				if(response.success){
					$result.addClass('success').html('&nbsp; Success!');
				} else {
					$result.addClass('error').html('&nbsp; Failed.');
					$('.button-primary').after($(response.report));
				}
				if($btn.find('span')){
					$btn.find('span').remove();
				}
				$btn.append($result);
			}
		});
		return false;
	});
});