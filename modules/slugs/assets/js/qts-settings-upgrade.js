jQuery(document).ready(function($) {
	
	function _debug(msg) {
		if(window.console) {
			console.debug(msg);
		}
	}
	var upgrade_box = $('#qts-upgrade-box'),
	upgrade_form = $('#qts-upgrade-form'),
	upgrade_button = $('#qts-upgrade-button'),
	notice_status = function(_status) {
		upgrade_box.removeClass().addClass(_status);
	},
	block_form = function(_block) {
		if (_block) {
			$('#qts-loading').show();
			upgrade_button.attr('disabled', true);			
		} else {
			$('#qts-loading').hide();
			upgrade_button.attr('disabled', false);
		}

	},
	check_types = function(_response) {
		
		block_form(false);
		upgrade_form.find('.message').remove();
		
		switch (_response.status) {
			default:
			case 0:
				upgrade_form.prepend('<p class=\'message ko\'>'+_response.message+'</p>');
			break;
			case 1:
				notice_status('updated success');
				upgrade_form.remove();
				upgrade_box.append('<p><strong>'+_response.message+'</strong></p>');
				upgrade_box.delay(2500).fadeTo(1300, 0, function(){ upgrade_box.remove(); });
			break;
			case 2:
				var data_ = '<p class=\'message\'>'+_response.message+'</p>';
				data_ += '<select id=\'qts-slug-type\' class=\'message\'>';
				
				for (opt in _response.options) 
					data_ += '<option value=\''+opt+'\'>'+_response.options[opt]+'</option>';
				data_ += '</select>';
				
				upgrade_form.prepend(data_);
				
			break;
		}
	},
	start_upgrade = function(_event) {
		_event.preventDefault();
		
		notice_status('updated');
		block_form(true);
		
		var package_ = {};
		package_.action = $('#qts-upgrade-action').val(); 
		package_.nonce = $('#qts-upgrade-nonce').val();
		if ( $('#qts-slug-type').length )
			package_.type = $('#qts-slug-type').val();
			
		$.post(ajaxurl, package_, check_types);
	};
	
	upgrade_button.bind('click', start_upgrade);
});