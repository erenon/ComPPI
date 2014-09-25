$(document).ready(function(){
	// tooltips
	$( document ).tooltip({
		position: {
			my: "left bottom-10px",
			at: "left top"
		},
		show: {delay: 250}
	});
	
	// first input autofocus
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
	width = 950,
	height = 600,
	tick_count = 100;

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
	
	var draw = function() {
		var force = d3.layout.force()
					.charge(-170)
					.linkDistance(50)
					.linkStrength(0.2)
					.nodes(graph.nodes)
					.links(graph.links)
					.size([width, height]);
		
		var vis = d3.select("#ps-networkVisContainer")
			.append("svg:svg")
			.attr("width", width)
			.attr("height", height)
			.attr("pointer-events", "all")
			.append("svg:g")
			.call(d3.behavior.zoom().on("zoom", redraw));
		
		// graph will be drawn on a canvas instead of the root svg object to be scalable
		vis.append('svg:rect')
				.attr("class", "network_canvas")
				.attr("width", width)
				.attr("height", height)
				.attr("fill", $("#ps-networkVisContainer").css("background-color"));
		 
		vis.style("opacity", 1e-6)
			.transition()
			.duration(1000)
			.style("opacity", 1);
		 
		var loading = vis.append("text")
			.attr("x", width / 2)
			.attr("y", height / 2)
			.attr("dy", ".35em")
			.style("text-anchor", "middle")
			.text("Loading, please wait…");
		
		force.start();
		for (var i = tick_count * tick_count; i > 0; --i) force.tick();
		force.stop();
		
		var link = vis.selectAll(".link")
			.data(graph.links)
			.enter().append("svg:line")
				.attr("class", "link")
			.style("stroke-width", function(d) { return weightscale(d.weight); })
				.attr("x1", function(d) { return d.source.x; })
				.attr("y1", function(d) { return d.source.y; })
				.attr("x2", function(d) { return d.target.x; })
				.attr("y2", function(d) { return d.target.y; });
		
		var node = vis.selectAll(".node")
			.data(graph.nodes)
			.enter().append("g")
			.attr("class", "node");
		
		node.append("circle")
			.attr("cx", function(d) { return d.x; })
			.attr("cy", function(d) { return d.y; })
			.attr("r", 10);
		
		node.append("text")
			.attr("x", 12)
			.attr("class", "label")
			.attr("transform", function(d) { return "translate(" + d.x + "," + d.y + ")"; })
			.text(function(d) { return d.name; });
		
		function redraw() {
			vis.attr(
				"transform",
				"translate(" + d3.event.translate + ")" + " scale(" + d3.event.scale + ")"
			);
		}
		
		loading.remove();
		
		//	force.on("tick", function() {
		//		link.attr("x1", function(d) { return d.source.x; })
		//			.attr("y1", function(d) { return d.source.y; })
		//			.attr("x2", function(d) { return d.target.x; })
		//			.attr("y2", function(d) { return d.target.y; });
		//		
		//		node.attr("transform", function(d) {
		//			return "translate(" + d.x + "," + d.y + ")";
		//		});
		//	});
	}
	
	var showNotice = function() {
		$("#ps-networkVisBody").append(
			"<p class=\"center\" id=\"ps-networkVisBodyLargeNetworkNote\">The network contains "
			+ graph.nodes.length
			+ " nodes, its rendering may slow down or temporarily hang your browser. <br><b>Click on 'Toggle Display' to start the rendering.</b></p>");
	}
	
	var removeNotice = function() {
		$("#ps-networkVisBodyLargeNetworkNote").remove();
	}
	
	if (graph.nodes.length>100) {
		showNotice();
		$("#ps-networkVisHelp").hide();
	} else {
		removeNotice();
		draw(graph);
	}
	
	// toggle network visualization
	$(".ps-networkVisOpener").click(function() {
		if ( $("svg").length == 0 ) {
			removeNotice();
			draw(graph);
			$("#ps-networkVisContainer, #ps-networkVisHelp").show();
		} else {
			$("#ps-networkVisBody").slideToggle();
		}
		
		return false;
	});
});