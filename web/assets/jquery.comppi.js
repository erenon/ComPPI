$(document).ready(function(){
	// tooltips
	$( document ).tooltip({
		position: {
			my: "left bottom-10px",
			at: "left top"
		}
	});
	
	// first input autofokus
	$("input[type='text']:eq(0)").focus();

	// striped tables
	$("table.striped_table tbody > tr:odd").addClass("striped_row");
	
	// highlighted list lines
	$(".hovered_list li, .hovered_list tr").hover(
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

// PROTEIN SEARCH
$(function() {
	// search autocomplete
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
	
	// show/hide advanced search
	$("#fProtSearchContainerLL, #fProtSearchContainerLR, #fProtSearchReset").hide();
	
	orig_title = $("#fProtSearchKeyword").attr("title");
	orig_height = $("#fProtSearchKeyword").height();
	textarea_title = $("#fProtSearchKeyword").attr("txttitle");
	
	$("#fProtSearchAdvancedBtn").click(function() {
		var is_hidden = $("#fProtSearchContainerLL, #fProtSearchContainerLR").is(":hidden");
		$("#fProtSearchContainerLL, #fProtSearchContainerLR, #fProtSearchReset").slideToggle(300);
		
		if (is_hidden) {
			$("#fProtSearchKeyword")
				.height(100)
				.attr("title", textarea_title)
				.autocomplete( "option", "disabled", true );
		} else {
			$("#fProtSearchKeyword")
				.height(orig_height)
				.attr("title", orig_title)
				.autocomplete( "option", "disabled", false );
		}
		
		return false;
	});
	
	// maintain user experience:
	// if textarea is in simple search mode (like an input field), then
	// submit when Enter key is pressed
	$("#fProtSearchKeyword").live("keypress", function(e) {
		if (e.keyCode == 13 && $("#fProtSearchReset").is(":hidden")) {
			$("#ProteinSearchForm").submit();
		}
	});
	
	// reset the protein search form
	$("#fProtSearchReset").click(function() {
		$("#fProtSearchKeyword").val("");
		$("#ProteinSearchForm input:checkbox").attr("checked", "checked");
		return false;
	});
	
	// show/hide protein interaction details
	$(".ps-actorBDetails").hide();
	$(".ps-detailsOpener").click(function() {
		$(this).siblings(".ps-actorBDetails:first").slideToggle();
		return false;
	});
});


$(function() {
	
});
