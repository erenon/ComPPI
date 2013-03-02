var initDynamicLinks = function() {
    twig({
        id : 'interactionDetails',
        href : $('#jstemplate-interactionDetails').attr('content')
    });

    twig({
        id : 'localizationDetails',
        href : $('#jstemplate-localizationDetails').attr('content')
    });

    $('a.trow-dynamic-expander-link').each(function(index) {
        var link = $(this), 
            template = link.data('jstemplate');
        
        link.data('remoteUrl', link.attr('href'));
        link.data('ready', false);
        link.removeAttr('href');
        link.css('cursor', 's-resize');

        link.click(function() {
            $.get(link.data('remoteUrl'), function(data) {
                link.parents('tr').after('<tr class="dynamic">' + twig({
                    ref : template
                }).render(data) + '</tr>');

                link.data('ready', true);
                link.unbind();
            }, 'json');
        });
    });
};

$(document).ready(function() {
    initDynamicLinks();
});