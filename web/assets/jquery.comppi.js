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
	
	//  fields 
	// general toggler for any button
	$(".btn").toggle(function() {
		$(this).addClass('btn_green');
		field_name = $(this).attr('id');
		field_name = 1;
	}, function() {
		$(this).removeClass('btn_green');
		field_name = $(this).attr('id');
		field_name = 0;
	});
	
	// Protein Advanced Search
	$("#AdvSearchFrame").hide();
	$("#AdvSearchBtn").click(function() {
		$("#AdvSearchFrame").slideToggle(500);
	});
	
	// protein search data collection
	/*$.post("/protein_search",
             { name: "Zara" },
             function(data) {
                $('#stage').html(data);
             }

          );*/
});