var labelType, useGradients, nativeTextSupport, animate, initHistogram = function() {
    var dataPath = $('#histogram-path').attr('content'), createChart = function(
        data, targetIdx) {
        // init BarChart
        var barChart = new $jit.BarChart({
            // id of the visualization container
            injectInto : 'interaction-histogram-' + targetIdx,
            // whether to add animations
            animate : true,
            // horizontal or vertical barcharts
            orientation : 'vertical',
            // bars separation
            barsOffset : 20,
            // visualization offset
            Margin : {
                top : 5,
                left : 5,
                right : 5,
                bottom : 5
            },
            // labels offset position
            labelOffset : 5,
            // bars style
            type : useGradients ? 'stacked:gradient' : 'stacked',
            // whether to show the aggregation of the values
            showAggregates : true,
            // whether to show the labels for the bars
            showLabels : true,
            // labels style
            Label : {
                type : labelType, // Native or HTML
                size : 15,
                family : 'Arial',
                color : 'black'
            },
            // add tooltips
            Tips : {
                enable : true,
                onShow : function(tip, elem) {
                    tip.innerHTML = "<b>Protein count</b>: " + elem.value;
                }
            }
        });

        barChart.loadJSON({
            values : data
        });
    };

    $
        .getJSON(dataPath, function(data) {
            $.each(data, function(index, speciesData) {
                createChart(speciesData, index);
            });
        })
        .error(
            function() {
                $('.interaction-histogram-container')
                    .after(
                        '<div class="message message-notice">Failed to load chart data</div>');
                $('.interaction-histogram-container').remove();
            });
};

(function() {
    var ua = navigator.userAgent, iStuff = ua.match(/iPhone/i)
        || ua.match(/iPad/i), typeOfCanvas = typeof HTMLCanvasElement, nativeCanvasSupport = (typeOfCanvas == 'object' || typeOfCanvas == 'function'), textSupport = nativeCanvasSupport
        && (typeof document.createElement('canvas').getContext('2d').fillText == 'function');
    // I'm setting this based on the fact that ExCanvas provides text support
    // for IE
    // and that as of today iPhone/iPad current text support is lame
    labelType = (!nativeCanvasSupport || (textSupport && !iStuff)) ? 'Native'
        : 'HTML';
    nativeTextSupport = labelType == 'Native';
    useGradients = nativeCanvasSupport;
    animate = !(iStuff || !nativeCanvasSupport);
})();

$(document).ready(function() {
    initHistogram();
});