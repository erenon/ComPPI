var initProteinSearchbars = function() {
	var resourceUrl = 'http://localhost/comppi/web/app_dev.php/stat/search/autocomplete/';
	
	$('.protein-search-input').each(function(index, searchbar) {
		var bar = $(searchbar);
		bar.attr('autocomplete', 'off');
		bar.typeahead({
			'minLength': 3,
			'source': function(query, finishCb) {
				$.getJSON(resourceUrl + query, function(data) {
					finishCb(data.names);
				});
			}
		});
		bar.data('items', 10);
	});
};

$(document).ready(function() {
	initProteinSearchbars();
});