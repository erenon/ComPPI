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
	

	
	// download low-definition locations select the high-definition counterparts
	// @TODO: select the branch below the loc -> feed into the sql query as exact id set (IN())
	/*$("#fDownloadLocCytoplasmBtn, #fDownloadLocMitoBtn, #fDownloadLocNucleusBtn, #fDownloadLocECBtn, #fDownloadLocSecrBtn, #fDownloadLocPlasMembrBtn").click(function() {
		id = $(this).attr("rel");
		if ( $(this).hasClass('btn_green') ) {
			$(this).removeClass('btn_green');
			$('#fDownloadLocFine option[value="' + id + '"]').prop('selected', false);
		} else {
			$(this).addClass('btn_green');
			$('#fDownloadLocFine option[value="' + id + '"]').prop('selected', true);
		}
	});*/
	
	// dataset buttons can be toggled: only one button can be active, and 0 means no active button
	/*$("#fDownloadIntByLoc, #fDownloadInts, #fDownloadLocs").click(function() {
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
	
	$("#fDownloadSubmit").click(function(){
		// no dataset or no species is selected -> error
		/*window.alert( $("#fDownloadSpecHs").attr('value') );
		if ( !$("#fDownloadSpecHs").attr('value') && !$("#fDownloadSpecDm").attr('value') && !$("#fDownloadSpecCe").attr('value') && !$("#fDownloadSpecSc").attr('value') ) {
			window.alert("Please select at least one genus!");
			return false;
		}
		if ( !$("#fDownloadDataset").attr('value') ) {
			window.alert("Please select a dataset by clicking on one button in the second row!");
			return false;
		}*/
		return true;
	});
	
	$("#fCommentSubmit").click(function(){
		window.alert("Thank you for your contribution!");
		$("#fCommentForm").reset();
		return false;
	});
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