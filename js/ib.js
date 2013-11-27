var tid;
	
// Download file with JQuery
jQuery.download = function(url, data, method) {
	//url and data options required
	if( url && data ){ 
		//data can be string of parameters or array/object
		data = typeof data == 'string' ? data : jQuery.param(data);
		//split params into form inputs
		var inputs = '';
		jQuery.each(data.split('&'), function(){ 
			var pair = this.split('=');
			inputs+='<input type="hidden" name="'+ pair[0] +'" value="'+ pair[1] +'" />'; 
		});
		//send request
		jQuery('<form action="'+ url +'" method="'+ (method||'post') +'">'+inputs+'</form>')
		.appendTo('body').submit().remove();
	};
};

// Append event to form_save submit
$(document).ready(function() {
	var action = $('#form_save').attr('action');
	$('#form_save').attr('action','javascript:void(0);');
	$('#form_save').submit(function(){
		$('#but_save').attr('disabled','');
		//$('#chk_meta').attr('disabled','');
		$('#loading').show();
		
		$.download(action, jQuery("#form_save").serialize(), 'post');
		
		setTimeout(updateLoading,2000);
		
		return null;
	});
});

// Check if file has been downloaded to hide loading
updateLoading = function() {
	$.post('index.php' ,{action:'check'}, function(data) {
		if (data == 'false') {
			$('#but_save').removeAttr('disabled');
			//$('#chk_meta').removeAttr('disabled');
			$('#loading').hide();
			clearInterval(tid);
			return false;
		}			
	});
	clearInterval(tid);
	tid = setInterval(updateLoading,2000);
}

signOut = function() {
	$('#iframe_signout').attr('src','http://instagram.com/accounts/logout/');
	setTimeout(logOut,1000);
}

logOut = function() {
	$('#form_logout').submit();
}