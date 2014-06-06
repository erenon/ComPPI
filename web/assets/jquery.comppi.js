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
	/*$("#AdvSearchFrame").hide();
	$("#AdvSearchBtn").click(function() {
		$("#AdvSearchFrame").slideToggle(500);
	});*/
	
	// ToggleButtons
	// @TODO: create an abstract solution for this
	/*$(".toggleButton").click(function() {
		var field_name = "#" + $(this).attr("id").replace("Btn", "");
		if ( $(this).hasClass('btn_green') ) {
			$(this).removeClass('btn_green');
			$(field_name).attr('value', 0);
		} else {
			$(this).addClass('btn_green');
			$(field_name).attr('value', 1);
		}
	});*/
	
	// radio buttons
	
	/*$("#ProteinSearchSpecies").buttonset();
	$("#DlSetButtons").buttonset();
	$("#DlSpeciesButtons").buttonset();
	$("#DlLocButtons").buttonset();*/
	
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
	
	//$(":radio.btn").hide();
	/*$(":radio.btn + label").addClass("btn").click(function(){
		if ( $(this).hasClass('btn_green') ) {
			$(this).removeClass('btn_green').prev(":radio").prop("checked", false);
		} else {
			$(":radio.btn_green").removeClass('btn_green').prev(":radio").prop("checked", false);
			$(this).addClass('btn_green').prev(":radio").prop("checked", true);
			$(this).siblings(":radio").removeClass('btn_green').prop("checked", false);
		}
	});*/
	//$(":radio.btn:checked + label").addClass("btn_green");
	
	
	/*$('#ProteinSearchForm:radio').each(function(){
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
	});*/
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

// show/hide protein interaction details
$(function() {
	$(".ps-actorBDetails").hide();
	$(".ps-detailsOpener").click(function() {
		$(this).siblings(".ps-actorBDetails:first").slideToggle();
		return false;
	});
});
