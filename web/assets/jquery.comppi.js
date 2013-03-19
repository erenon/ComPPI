$(document).ready(function(){
	// first input autofokus
	$("input[type='text']:eq(0)").focus();
	
	// outlinks
	//$('a[target="_blank"]').append(' <img src="outlink.png" alt="Link goes out from comppi." />');
	// this does not work - what the FUCK, Symfony?!

	// highlighted list lines
	$(".hovered_list li, .hovered_list tr").not(":first").hover(
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
	
	// Protein Search - Interaction Details
	$("#ProteinSearchResultsTbl > tbody > tr:gt(0)").not(".protSearchDetailsBox").filter(":odd").addClass("striped_tbl_row");
	$("#ProteinSearchResultsTbl > tbody > tr.protSearchDetailsBox").filter(":odd").addClass("striped_tbl_row");
	$(".protSearchDetailsBox > td").hide();
	$(".protSearchDetailsLink").click(function(){
		$(this).parents("tr").next("tr").find("td:first").slideToggle(600);
		if ($(this).parents("tr").hasClass("protSearchRowOpened")) {
			$(this).parents("tr").removeClass("protSearchRowOpened").next("tr").removeClass("protSearchDetailsOpened");
		} else {
			$(this).parents("tr").addClass("protSearchRowOpened").next("tr").addClass("protSearchDetailsOpened");
		}
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

// search autocomplete
$(function() {
	$("#fProtSearchKeyword").autocomplete({
		source: function(request, response){
			url = "/comppi/ComPPI_dualon/web/app_dev.php/protein_search/autocomplete/" + request.term;
			$.getJSON(url, function(data){
				response(data);
			});
		},
		minLength: 2,
		select: function( event, ui ) {
			// submit?
		}
	});
});
