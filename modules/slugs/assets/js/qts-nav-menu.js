jQuery(document).ready(function($){
	var qts_menuitemlang = [];
	// Change titles (and values) when user add new item to the menu:
	var oldAddMenuItemToBottom = wpNavMenu.addMenuItemToBottom;
	wpNavMenu.addMenuItemToBottom = function( menuMarkup, req ) {
		oldAddMenuItemToBottom( menuMarkup, req );
		saveLang();
		changeTitles();
	};
	var oldAddMenuItemToTop = wpNavMenu.addMenuItemToTop;
	wpNavMenu.addMenuItemToTop = function( menuMarkup, req ) {
		oldAddMenuItemToTop( menuMarkup, req );
		saveLang();
		changeTitles();
	};
	
	// Change titles (and values) when document is ready:
	var lang = $('#qt-languages :radio:checked').val();
	saveLang();
	changeTitles();
        
  /**
  * Change titles when there is a click on pagination
  * on show all pages tab.
  * 
  * It happens when there are a large number of pages.
  */
  $( document ).ajaxComplete(function() {
      lang = $('#qt-languages :radio:checked').val();
      changeTitles();
  });
	
	// Change titles (and values) when language is changed:
	$('#qt-languages :radio').change( function() {
		lang = $('#qt-languages :radio:checked').val();
		
		changeTitles();
	});
	
	// Change titles (and values) when new menu is added:
	$('.submit-add-to-menu').click( function() {
		lang = $('#qt-languages :radio:checked').val();
		changeTitles();
	});
		
	// Update original value when user changed a value:
	$(document.body).on('change', 'input.edit-menu-item-title', null, function() {
		regexp = new RegExp('<!--:' + lang + '-->(.*?)<!--:-->', 'i');
		
		
		var qts_old_value = qts_menuitemlang[ $(this).closest("li").attr("id") ][ $(this).attr("id")],
		    qts_new_value = $(this).val();
		
		  if( regexp.test(qts_old_value ) ) {
		    qts_menuitemlang[ $(this).closest("li").attr("id") ][  $(this).attr("id")] = qts_old_value.replace( regexp,'<!--:' + lang + '-->' + qts_new_value + '<!--:-->');  
		  }
			
	});
	
	
	
	
	// Update original title="" value when user changed a value:
	$(document.body).on('change', 'input.edit-menu-item-attr-title', null, function() {
		regexp = new RegExp('<!--:' + lang + '-->(.*?)<!--:-->', 'i');
		var qts_old_value = qts_menuitemlang[ $(this).closest("li").attr("id") ][ $(this).attr("id")],
		    qts_new_value = $(this).val();
		    qts_new_string = '<!--:' + lang + '-->'+ qts_new_value + '<!--:-->';
    if(typeof qts_old_value === "undefined"  || qts_old_value == "" ) { 
      qts_menuitemlang[ $(this).closest("li").attr("id") ][ $(this).attr("id")] =  qts_new_string;
      $(this).val( qts_new_string );
	  } else if( regexp.test(qts_old_value ) ) {
	    qts_menuitemlang[ $(this).closest("li").attr("id") ][  $(this).attr("id")] = qts_old_value.replace( regexp,qts_new_string);
	  } else {
	    $(this).val( qts_old_value + qts_new_string );
	    qts_menuitemlang[ $(this).closest("li").attr("id") ][  $(this).attr("id")] = qts_old_value + qts_new_string;
	  }
			
	});
	
	
	
	// Change titles (and values):
	function saveLang() {
	    $('.item-title').each( function() {
	      qts_menuitemlang[ $(this).closest("li").attr("id") ] = [];
	      qts_menuitemlang[ $(this).closest("li").attr("id") ]['Title'] =  $(this).find("span").html();
	      
	      if( typeof qts_menuitemlang[ $(this).closest("li").attr("id") ]['Original'] === "undefined" ) {
	        qts_menuitemlang[ $(this).closest("li").attr("id") ]['Original'] =  qts_menuitemlang[ $(this).closest("li").attr("id") ]['Title'];
	      }
      });
      $('.menu-item').each( function() {
        
        this_label = $(this).find('input.edit-menu-item-title');
        this_title = $(this).find('input.edit-menu-item-attr-title');
        
        qts_menuitemlang[ $(this).attr("id") ][this_label.attr("id") ] = this_label.val();
        qts_menuitemlang[ $(this).attr("id") ][this_title.attr("id") ] = this_title.val();
        
      });
      
	}
	// Change titles (and values):
	function changeTitles() {
		// Change menu item titles and links (on the right side):
		// TODO: review, and dump
		regexp = new RegExp('<!--:' + lang + '-->(.*?)<!--:-->', 'i');
		$('.item-title').each( function() {
			  if (matches = qts_menuitemlang[ $(this).closest("li").attr("id") ]['Original'].match(regexp)) {
          $(this).closest('li').find('.link-to-original a').text( matches[1] );		
			  }
			
		});
		// Change menu item title inputs (on the right side): Navigation Label, Title Attribute...
		regexp2 = new RegExp('<!--:' + lang + '-->(.*?)<!--:-->', 'i');
		$('.menu-item').each( function() {
		  this_label = $(this).find('input.edit-menu-item-title');
      this_title = $(this).find('input.edit-menu-item-attr-title');
		  if( lang === "all") {
          this_label.val(qts_menuitemlang[ $(this).attr("id") ][this_label.attr("id") ] );
          this_title.val(qts_menuitemlang[ $(this).attr("id") ][this_title.attr("id") ] );
      } else {
               
        if (matches = qts_menuitemlang[ $(this).attr("id") ][this_label.attr("id") ].match(regexp2)) {
            this_label.val( matches[1] );
        }
		 
        if (matches2 = qts_menuitemlang[ $(this).attr("id") ][this_title.attr("id") ].match(regexp2)) {
            this_title.val( matches2[1] ); 
        }
			}
		});
		
		// Change menu item checkbox labels (on the left side):
		// TODO: fix this, not a show stopper
		/*$('label.menu-item-title').each( function() {
			var textNode = $(this).contents().get(1);
		});*/
	}

	// Restore the original input values:
	function restoreValues(){ 
		  
		  $('.menu-item').each( function() {
		      
		    this_label = $(this).find('input.edit-menu-item-title');
        this_title = $(this).find('input.edit-menu-item-attr-title');
        
        this_label.val( qts_menuitemlang[ $(this).attr("id") ][this_label.attr("id") ] );
        this_title.val( qts_menuitemlang[ $(this).attr("id") ][this_title.attr("id") ] );
          $(this).find('.link-to-original a').text( qts_menuitemlang[ $(this).closest("li").attr("id") ]['Original']);
      });
	}
	
	// Just before saving restore the original input values:
	$('.menu-save').click(function() {
		restoreValues();
	});

	// Just before leaving the page (or refresh) restore the original input values:
	window.onbeforeunload = function(){ 
		restoreValues();		
		return;
	};

});