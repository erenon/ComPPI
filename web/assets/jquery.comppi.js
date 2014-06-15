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

	// DOWNLOADS
	$("input[name='fDlSet']").change(function() {
		switch(this.value) {
			case "comp":
			case "protnloc":
				$("#DLSpeciesContainer, #DLLocContainer").fadeIn();
				break;
			case "int":
				$("#DLLocContainer").fadeOut();
				// switch back to all localizations,
				// otherwise previously selected loc would be sent
				$("#DLLocContainer input:checked").removeAttr("checked");
				$("#fDlMLocAll").attr("checked", "checked");
				break;
			case "all":
			default:
				$("#DLSpeciesContainer, #DLLocContainer").fadeOut();
		}
		$("#DlSetHelpDisplay").html( $(this).next(".DlSetHelp").html() ).fadeIn();
	});
	$("input[name='fDlSet']:checked").change(); // trigger the event for first time
});

// search autocomplete
$(function() {
	$("#fProtSearchKeyword").autocomplete({
		source: function(request, response){
			// hardcoded URL for live environment: ugly, but it works..
			url = "/protein_search/autocomplete/" + request.term;
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

// tooltips
$(function() {
	$( document ).tooltip({
		position: {
			my: "left bottom-10px",
			at: "left top"
		}
	});
});

// striped tables
$(function() {
	$("table.striped_table tbody > tr:odd").addClass("striped_row");
});

// show/hide protein interaction details
$(function() {
	$(".ps-actorBDetails").hide();
	$(".ps-detailsOpener").click(function() {
		$(this).siblings(".ps-actorBDetails:first").slideToggle();
		return false;
	});
});
