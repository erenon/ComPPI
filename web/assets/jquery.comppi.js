$(document).ready(function(){
	// first input autofokus
	$("input[type='text']:eq(0)").focus();

	// highlighted list lines
	$(".hovered_list li:not(.not_hovered_line)").hover(
		function() { $(this).addClass('hovered_listline'); },
		function() { $(this).removeClass('hovered_listline'); }
	);
	$(".hovered_list tr:not(.not_hovered_line)").hover(
		function() { $(this).addClass('hovered_listline'); },
		function() { $(this).removeClass('hovered_listline'); }
	);

	// Search / Download Advanced Parameters
	$("#AdvSearchFrame").hide();
	$("#AdvSearchBtn").click(function() {
		$("#AdvSearchFrame").slideToggle(500);
	});
	
	// ToggleButtons
	// @TODO: create an abstract solution for this
	$(".toggleButton").click(function() {
		var field_name = "#" + $(this).attr("id").replace("Btn", "");
		if ( $(this).hasClass('btn_green') ) {
			$(this).removeClass('btn_green');
			$(field_name).attr('value', 0);
		} else {
			$(this).addClass('btn_green');
			$(field_name).attr('value', 1);
		}
	});
	
	// radio buttons
	$(':radio').each(function(){
		var id = $(this).attr("id");
		var label = $('label[for="' + id + '"]');
		
		$(this).add(label).css("display", "none").after();
		$(this).before('<input type="button" id="'+ id +'Btn" value="'+ $(label).text() +'" class="btn radioButton" />');
		if ($(this).is(':checked')) {
			$('#'+id+'Btn').addClass("btn_green");
		}
		$('#'+id+'Btn').on('click', function(){
			if ($(this).hasClass('btn_green')) {
				$(this).removeClass('btn_green');
				$("#"+id).removeAttr("checked", "checked").attr('value', 0);
			} else {
				$('.radioButton').removeClass('btn_green');
				$(':radio').removeAttr('checked').attr('value', 0);
				$(this).addClass('btn_green');
				$('#'+id).attr('checked', 'checked').attr('value', 1);
			}
		});
	});
	/*radioButtons = $();
	$("#fProtSearchSpecHsBtn, #fProtSearchSpecDmBtn, #fProtSearchSpecCeBtn, #fProtSearchSpecScBtn").click(function() {
		var field_name = "#" + $(this).attr("id").replace("Btn", "");
		current_is_active = $(this).hasClass('btn_green');
		
		switch($(this).attr("id")) {
			case 'fDownloadIntByLoc': value=1; break; // locsbyint
			case 'fDownloadInts': value=2; break; // interactions
			case 'fDownloadLocs': value=3; break; // localizations
			default: value=0;
		}
		
		$("#fDownloadIntByLoc, #fDownloadInts, #fDownloadLocs").removeClass('btn_green');
		
		if ( current_is_active ) {
			$("#fDownloadDataset").attr('value', 0);
		} else {
			$(this).addClass('btn_green');
			$("#fDownloadDataset").attr('value', value);
		}
	});*/
});

// LOCTREE
$(document).ready(function(){
	$("#LocTree li > ul").hide();
	//$(".LocTreeParent").next("input").after('<a href="#" class="CategoryOpener"><img src="arrow_down.gif" width="22" height="22" alt="Open Branch" /></a>');
	
	
	$('#LocTree li a').focus(function() { $(this).blur(); });
	$('#LocTree li a.CategoryOpener').toggle(function() {
		$(this).next('ul').slideDown(500);
		$(this).find('img').attr('src', './web/assets/arrow_up.gif');
		
	}, function() {
		$(this).next('ul').slideUp(500);
		$(this).find('img').attr('src', './web/assets/arrow_down.gif');
		
	});
	
	$('#LocTree input:checkbox:checked').each(function() {
		checkCatTree(this, $(this).is(':checked'));
	})
	$('#LocTree input:checkbox').change(function() {
		checkCatTree(this, $(this).is(':checked'));
	});
	
	function checkCatTree(obj, orig_checked) {
		// felmenok, leszarmazottak ellenorzese
		var parent = $(obj).parent();
		if (parent[0].tagName=='LI') {
			var label = $(parent).find('label').eq(0);
			if (orig_checked) {
				if (!$(label).hasClass('LocTree-has_checked')) {
					$(label).addClass('LocTree-has_checked');
				}
			} else {
				if ($(label).prev('input').is(':checked') || $(parent).find('input').is(':checked')) {
					return; // ha o maga, v. leszarmazottak barmelyike checked
				} else {
					$(label).removeClass('LocTree-has_checked');
				}
			}
			
			if ($(parent).parent().attr('id')!='LocTree') {
				checkCatTree($(parent).parent(), orig_checked);
			} else {
				return;
			}
		}

		// sajat sor ellenorzese
		if (orig_checked) {
			$(obj).next('label').addClass('LocTree-has_checked');
		} else {
			$(obj).next('label').removeClass('LocTree-has_checked');
		}
	}
});