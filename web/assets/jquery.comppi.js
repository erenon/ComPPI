$(document).ready(function(){
	// tooltips
	$( document ).tooltip({
		position: {
			my: "left bottom-10px",
			at: "left top"
		},
		show: {delay: 250}
	});
	
	// first input autofokus
	//$("input[type='text']:eq(0)").focus();

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
	if ($("#fProtSearchKeyword").length) {
		$("#fProtSearchContainerLL, #fProtSearchContainerLR").hide();
		
		// remove comment to enable multiline advanced search
		//orig_title = $("#fProtSearchKeyword").attr("title");
		//orig_height = $("#fProtSearchKeyword").height();
		//orig_height = orig_height.toString() + 'px';
		//textarea_title = $("#fProtSearchKeyword").attr("txttitle");
		
		$("#fProtSearchAdvancedBtn").click(function() {
			//var is_hidden = $("#fProtSearchContainerLL, #fProtSearchContainerLR").is(":hidden");
			$("#fProtSearchContainerLL, #fProtSearchContainerLR").slideToggle(300);
			
			// remove comment to enable multiline advanced search
			//if (is_hidden) {
			//	$("#fProtSearchKeyword")
			//		.animate({height:'110px'})
			//		.attr("title", textarea_title)
			//		.autocomplete( "option", "disabled", true );
			//} else {
			//	$("#fProtSearchKeyword")
			//		.animate({height:orig_height})
			//		.attr("title", orig_title)
			//		.autocomplete( "option", "disabled", false );
			//}
			
			return false;
		});
				
		// maintain user experience:
		// if textarea is in simple search mode (like an input field), then
		// submit when Enter key is pressed instead of inserting new line
		//$("#fProtSearchKeyword").on("keydown", function(event) {
		//	if (event.keyCode == 13 && $("#fProtSearchReset").is(":hidden")) {
		//		//window.alert('IGEN')
		//		$("#ProteinSearchForm").submit();
		//		return false;
		//	}
		//});
		
		// localization score treshold slider for protein search
		$("#fProtSearchLocScoreSlider").slider({
			min: 0.00,
			max: 1.00,
			step: 0.1,
			range: "max",
			value: $("#fProtSearchLocScore").val(),
			slide: function( event, ui ) {
				$("#fProtSearchLocScore" ).val( ui.value );
			},
			change: function(event, ui) {
				//
			},
		});
		$("#fProtSearchLocScore").val( $("#fProtSearchLocScoreSlider").slider("value") );
		// slider should follow the typed in value
		$("#fProtSearchLocScore").on("keyup", function(event) {
			$("#fProtSearchLocScoreSlider").slider("value", $("#fProtSearchLocScore").val());
		});
	}
	
	// confidence score treshold slider for interactor filtering
	$("#fIntFiltConfScoreSlider").slider({
		min: 0.00,
		max: 1.00,
		step: 0.1,
		range: "max",
		value: $("#fIntFiltConfScore").val(),
		slide: function( event, ui ) {
			$("#fIntFiltConfScore" ).val( ui.value );
		},
		change: function(event, ui) {
			//
		},
	});
	$("#fIntFiltConfScore").val( $("#fIntFiltConfScoreSlider").slider("value") );
	// slider should follow the typed in value
	$("#fIntFiltConfScore").on("keyup", function(event) {
		$("#fIntFiltConfScoreSlider").slider("value", $("#fIntFiltConfScore").val());
	});
	
	// localization score treshold slider for interactor filtering
	$("#fIntFiltLocScoreSlider").slider({
		min: 0.00,
		max: 1.00,
		step: 0.1,
		range: "max",
		value: $("#fIntFiltLocScore").val(),
		slide: function( event, ui ) {
			$("#fIntFiltLocScore" ).val( ui.value );
		},
		change: function(event, ui) {
			//
		},
	});
	$("#fIntFiltLocScore").val( $("#fIntFiltLocScoreSlider").slider("value") );
	// slider should follow the typed in value
	$("#fIntFiltLocScore").on("keyup", function(event) {
		$("#fIntFiltLocScoreSlider").slider("value", $("#fIntFiltLocScore").val());
	});
	
	// show/hide protein interaction details
	display_all_details = false;
	$("#ps-allDetailsOpener").click(function() {
		display_all_details = !display_all_details;
		
		if (display_all_details) {
			$(".ps-actorBDetails").show();
		} else {
			$(".ps-actorBDetails").hide();
		}
		
		return false;
	});
	
	$(".ps-actorBDetails").hide();
	$(".ps-detailsOpener").click(function() {
		$(this).siblings(".ps-actorBDetails:first").slideToggle();
		return false;
	});

	function longSearchWarning() {

	}
	
	// display warning if protein search lasts too long
	$("#fProtSearchSubmit").click(function(){
		setTimeout(
			function() {
				$.magnificPopup.open({
					items: {
						src: '<div class="white-popup">Exceptionally this search lasts longer than 3 seconds, the results will appear soon on the screen.</div>',
						type: 'inline'
					}
				});
			},
			3000 // time of the timeout
		)
	});
});

$(function() {
	// reset the protein search form
	$("#fProtSearchReset").click(function() {
		$("#fProtSearchKeyword").attr("value", ""); // .val("") does not work - jQuery bug?
		$("#fProtSearchLocScore").attr("value", 0);
		$("#fProtSearchLocScoreSlider").slider("value", 0);
		$("#ProteinSearchForm input:checkbox").attr("checked", "checked");
	});
});

// visualization of the protein search result network
$(document).ready(function(){
	// graph = see the embedded graph in interactors.html.twig

	var w = 950,
		h = 600,
		fill = d3.scale.category20();
	
	var vis = d3.select("#ps-networkVisContainer")
		.append("svg:svg")
			.attr("width", w)
			.attr("height", h)
			.attr("pointer-events", "all")
		.append("svg:g")
			.call(d3.behavior.zoom().on("zoom", redraw));
	
	// graph will be drawn on a canvas instead of the root svg object to be scalable
	vis.append('svg:rect')
		.attr("class", "network_canvas")
		.attr("width", w)
		.attr("height", h)
		.attr("fill", $("#ps-networkVisContainer").css("background-color"));
	
	// scale the nodes according to their scores
	/*var nodescale = d3.scale.linear()
		.domain([
			d3.min(graph.nodes, function(d) { return d.score; }),
			d3.max(graph.nodes, function(d) { return d.score; })
		])
		.range([5, 25]);*/
	// scale the edges according to their weights
	var weightscale = d3.scale.linear()
		.domain([
			d3.min(graph.links, function(d) { return d.weight; }),
			d3.max(graph.links, function(d) { return d.weight; })
		])
		.range([1, 7]);
	
	function redraw() {
		vis.attr("transform",
			"translate(" + d3.event.translate + ")" + " scale(" + d3.event.scale + ")"
		);
	}
	
	var draw = function(graph) {
		var force = d3.layout.force()
			.charge(-500)
			.linkDistance(80)
			.linkStrength(0.2)
			.nodes(graph.nodes)
			.links(graph.links)
			.size([w, h])
			.start();
		
		var link = vis.selectAll(".link")
			.data(graph.links)
			.enter().append("svg:line")
				.attr("class", "link")
			.style("stroke-width", function(d) { return weightscale(d.weight); })
				.attr("x1", function(d) { return d.source.x; })
				.attr("y1", function(d) { return d.source.y; })
				.attr("x2", function(d) { return d.target.x; })
				.attr("y2", function(d) { return d.target.y; });
		
		link.append("title").text(function(d) { return d.weight; });
		
		var node = vis.selectAll(".node")
			.data(graph.nodes)
			.enter().append("g")
				.attr("class", "node")
				.call(force.drag);
		
		node.append("circle")
			.attr("r", 10/*function(d) { return nodescale(d.score) }*/);
		
		node.append("text")
			.attr("x", 12)
			/*.attr("dy", ".5em")*/
			.attr("class", "label")
			.text(function(d) { return d.name; });
		
		vis.style("opacity", 1e-6)
			.transition()
			.duration(1000)
			.style("opacity", 1);
		
		force.on("tick", function() {
			link.attr("x1", function(d) { return d.source.x; })
				.attr("y1", function(d) { return d.source.y; })
				.attr("x2", function(d) { return d.target.x; })
				.attr("y2", function(d) { return d.target.y; });
			
			node.attr("transform", function(d) {
				return "translate(" + d.x + "," + d.y + ")";
			});
		});
	};
		
	draw(graph)
	
});