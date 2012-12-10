var
initDynamicLinks = function() {
	var template = twig({
		id: 'interactionDetails',
		href: $('#jstemplate-interactionDetails').attr('content')
	});
	$('a.trow-dynamic-expander-link').each(function(index) {
		var link = $(this); 
		link.data('remoteUrl', link.attr('href'));
		link.data('ready', false);
		link.removeAttr('href');
		link.css('cursor', 's-resize');
		
		link.click(function() {
			$.get(
				link.data('remoteUrl'),
				function(data) {
					link.parents('tr').after(
						'<tr class="dynamic">' 
						+  twig({ ref: "interactionDetails" }).render(data)
						+ '</tr>'
					);
					
					link.data('ready', true);
//					link.css('cursor', 'n-resize');
					link.unbind();
				},
				'json'
			);
		});
	});
}
;

$(document).ready(function() {
	initDynamicLinks();
});