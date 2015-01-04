<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
 <head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <title>Temperature and Humidity</title>
    <link href="layout.css" rel="stylesheet" type="text/css">
    <!--[if lte IE 8]><script language="javascript" type="text/javascript" src="javascript/flot/excanvas.min.js"></script><![endif]-->
   <!-- //<script language="javascript" type="text/javascript" src="javascript/jquery/jquery.js"></script>
    //<script language="javascript" type="text/javascript" src="javascript/flot/jquery.flot.js"></script>
    //<script language="javascript" type="text/javascript" src="javascript/flot/jquery.flot.navigate.js"></script>
    //<script language="javascript" type="text/javascript" src="javascript/flot/jquery.flot.selection.js"></script>
    //<script language="javascript" type="text/javascript" src="javascript/flot/jquery.flot.crosshair.js"></script>
    //<script language="javascript" type="text/javascript" src="javascript/flot/jquery.flot.time.js"></script>-->
    <script language="javascript" type="text/javascript" src="https://code.jquery.com/jquery-2.1.3.min.js"></script>
    <script language="javascript" type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/flot/0.8.3/excanvas.min.js"></script>
    <script language="javascript" type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/flot/0.8.3/jquery.flot.js"></script>
    <script language="javascript" type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/flot/0.8.3/jquery.flot.navigate.js"></script>
    <script language="javascript" type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/flot/0.8.3/jquery.flot.selection.js"></script>
    <script language="javascript" type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/flot/0.8.3/jquery.flot.crosshair.js"></script>
    <script language="javascript" type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/flot/0.8.3/jquery.flot.time.js"></script>

    <style type="text/css"> 
    html, body {
        height: 100%; /* make the percentage height on placeholder work */
    width: 100%;
    }
    #placeholder .button {
        position: absolute;
        cursor: pointer;
    }
    #placeholder div.button {
        font-size: smaller;
        color: #999;
        background-color: #eee;
        padding: 2px;
    }
    .message {
        padding-left: 50px;
        font-size: smaller;
    }
    </style> 
 </head>
 
 <?php
    function Grab($name) {
        if (isset($_GET[$name])){
	    return $_GET[$name];
        }
	else {
    	    return NULL;
	}
    }
    $kwh = Grab('kwh');

    echo "<body>";
    echo "<h1>Temperature and Humidity</h1>";
//    echo $device.", ".$channel.", Units: ".$units.", UTC-4hrs";
    echo "<BR>";

    echo "<div style=\"float:left\"><div id=\"placeholder\" style=\"width:800px;height:600px\"></div></div> ";
    echo "<div id=\"miniature\" style=\"float:left; margin-left:20px\"><div id=\"overview\" style=\"width:166px;height:100px\"></div> ";
    echo "<p id=\"overviewLegend\" style=\"margin-left:10px\"></p></div> ";

    echo "<p style=\"clear: left\">Click any date button to see the data displayed. Mousewheel or click-drag zooms.<br>";
    echo "Click-drag also works in overview window.<BR>";

    echo "<p><input id='setLabels' type='button' value='Markers on' />";
    echo "   <input id='clearLabels' type='button' value='Markers off' /></p>";


    $db = new SQLite3('/home/pi/GPIO/tempdata.db');
    $query="SELECT DISTINCT tdate AS mydate FROM temps ORDER BY mydate DESC";

    $result = $db->query($query) or die('Query failed' );
    if (!$result) {
	echo("<P>Error performing query: " .
	"</P>");
	exit();
    }
    echo "<p>";
    while ( $row = $result->fetchArray() ) {
	echo "<p><input class=\"fetchSeries\" type=\"button\" value=\"".$row["mydate"]."\"> - <a href=\"recorder.php?date=".$row["mydate"]."\">data</a> - <span></span></p>";
    }
    echo "</p>";
?>



<script type="text/javascript">

var mylength = 0;
$(function () {
    var options = {
	legend: { show: false },
	lines: { show: true, lineWidth: 2 },
	points: { show: false },
	shadowSize: 0,
	selection: { mode: "xy" },
	zoom: { interactive: true },
	xaxis: { mode: "time" },
	yaxis: {tickDecimals:2, min: 25, max: 80  },
	y2axis: {tickDecimals:2, min: 3, max: 10 },
	crosshair: {mode: "x" },
	grid: { hoverable: true, autoHighlight: false },
	colors: ["#FF7070", "#0022FF"]
    };
    var data = [];
    var placeholder = $("#placeholder");
    var overviewholder = $("#overview");
    var plot = $.plot(placeholder, data, options);
    var updateLegendTimeout = null;
    var latestPosition = null;
    var legends = $("#overviewLegend  .legendLabel");
    legends.each(function () {$(this).css('width', $(this).width());}); // fix the widths so they don't jump around
    var overview = $.plot(overviewholder, data, {
        legend: { show: true, container: $("#overviewLegend") },
        series: { lines: { show: true, lineWidth: 3 }, shadowSize: 0 },
        xaxis: { ticks: 0 },
        yaxis: { ticks: 0, min: 20, max: 80 },
        y2axis: { ticks: 0, min: 0, max: 10 },
        grid: { color: "#999" },
        selection: { mode: "xy" }
    });

    var alreadyFetched = {};
    var mytemp = new Array();
    var myrelhum = new Array();
    var myabshum = new Array();
    // fetch one series, adding to what we got
    $("input.fetchSeries").click(function () {
        var button = $(this);
        // find the URL in the link right next to us 
        var dataurl = button.siblings('a').attr('href');
        // then fetch the data with jQuery
        function onDataReceived(series) {
            series.label = series.label+": ";
	    mylength = mytemp.length;
           // for (var j=0; j<series.data.length; j++) { 
	   //	mytemp[mylength+j] = [series.data[j][0],series.data[j][1]];
	   //	myrelhum[mylength+j]  = [series.data[j][0],series.data[j][2]];
	   //	myabshum[mylength+j] = [series.data[j][0],series.data[j][3]];
	   // }
            for (var j=0; j<series.data.length; j++) {
                mytemp[mylength+j] = [series.data[j][0],series.data[j][1]];
                myrelhum[mylength+j] = [series.data[j][0],series.data[j][2]];
                myabshum[mylength+j] = [series.data[j][0],series.data[j][3]];
            }
	    //make sure the data is all in order, regardless of how it was added
	    mytemp.sort(function(a,b){return a[0] > b[0] ? 1 : a[0] < b[0] ? -1 : 0});
	    myrelhum.sort(function(a,b){return a[0] > b[0] ? 1 : a[0] < b[0] ? -1 : 0});
	    myabshum.sort(function(a,b){return a[0] > b[0] ? 1 : a[0] < b[0] ? -1 : 0});
            button.siblings('span').text('Fetched ' + series.label);
            if (!alreadyFetched[series.label]) {
                alreadyFetched[series.label] = true;
		data=[];
		data.push({
		    data: myrelhum,
		    label: "Relative Humid (%)",
		    yaxis: 1,
		    lines: { show: true, order: 2 },
		    color: ['blue']
		});

		data.push({
		    data: mytemp,
		    label: "Temperature (F)",
		    yaxis: 1,
		    lines: { show: true, order: 1 },
		    color: ['red']
		});

		data.push({
		    data: myabshum,
		    label: "Abs Humid (g/m^3)",
		    yaxis: 2,
		    lines: { show: true, order: 1 },
		    color: ['green']
		});
            }
            // and plot all we got
    	    plot = $.plot(placeholder, data, options);
    	    overview.setData(data);
    	    overview.setupGrid();
	    overview.draw();
	    legends = $("#overviewLegend  .legendLabel");
	}

        $.ajax({
            url: dataurl,
    	    async: true,
    	    processData: false,
    	    type: "GET",
            dataType: "json",
            success: onDataReceived,
    	    error : function(XMLHttpRequest, textStatus, errorThrown) {
                button.siblings('span').text('Fetched nothing: '+errorThrown);
    	    }
        });
    });

    //Show individual data points
    $("#setLabels").click(function () {
    options.points.show = true;
    plot = $.plot(placeholder, data, options);
    legends = $("#overviewLegend  .legendLabel");
    });

    //Hide individual data points
    $("#clearLabels").click(function () {
    options.points.show = false;
    plot = $.plot(placeholder, data, options);
    legends = $("#overviewLegend  .legendLabel");
    });

    //Determine mouse position, update legend with data point
    placeholder.bind("plothover",  function (event, pos, item) {
        latestPosition = pos;
        if (!updateLegendTimeout)
            updateLegendTimeout = setTimeout(updateLegend, 50);
    });

    //Selectable zoom for main graph
    placeholder.bind("plotselected", function (event, ranges) {
    options.xaxis.min = ranges.xaxis.from;
    options.xaxis.max = ranges.xaxis.to;
    options.yaxis.min = ranges.yaxis.from;
    options.yaxis.max = ranges.yaxis.to;
    options.y2axis.min = ranges.y2axis.from;
    options.y2axis.max = ranges.y2axis.to;
    plot = $.plot(placeholder, data, options);
    legends = $("#overviewLegend  .legendLabel");
    overview.setSelection(ranges, true);
    });

    //Selectable zoom for overview graph
    overviewholder.bind("plotselected", function (event, ranges) {
    plot.setSelection(ranges, true);
    options.xaxis.min = ranges.xaxis.from;
    options.xaxis.max = ranges.xaxis.to;
    options.yaxis.min = ranges.yaxis.from;
    options.yaxis.max = ranges.yaxis.to;
    options.y2axis.min = ranges.y2axis.from;
    options.y2axis.max = ranges.y2axis.to;
    plot = $.plot(placeholder, data, options);
    legends = $("#overviewLegend  .legendLabel");
    });

    placeholder.bind('plotzoom', function (event, plot) {
    legends = $("#overviewLegend  .legendLabel");
    });

    function updateLegend() {
	updateLegendTimeout = null;
	var pos = latestPosition;
	var axes = plot.getAxes();
	if (pos.x < axes.xaxis.min || pos.x > axes.xaxis.max || pos.y < axes.yaxis.min || pos.y > axes.yaxis.max) return;
	var i, j, dataset = plot.getData();
	for (i = 0; i < dataset.length; ++i) {
	    var series = dataset[i];
	    // find the nearest points, x-wise
	    for (j = 0; j < series.data.length; ++j)
		if (series.data[j][0] > pos.x) break;
	    // now interpolate
	    var y, p1 = series.data[j - 1], p2 = series.data[j];
	    if (j==0) y=null;	//display nothing instead of last value when mouse left of dataset
	    else if (j>=series.data.length) y=null;	//display nothing instead of last value when moust right of dataset
	    else if (p1 == null) y = p2[1];
	    else if (p2 == null) y = p1[1];
	    else y = p1[1] + (p2[1] - p1[1]) * (pos.x - p1[0]) / (p2[0] - p1[0]);
	    var xdate = new Date(pos.x);
	    var normMonth = xdate.getMonth()+1; //For txt display, add 1 to 0-11 base month output
	    var xtime =
		(xdate.getFullYear() < 10 ? '0' : '')+xdate.getFullYear() + "-"+ 
		(normMonth < 10 ? '0' : '')+normMonth + "-"+
		(xdate.getDate() < 10 ? '0' : '')+xdate.getDate() + " "+
		(xdate.getHours() < 10 ? '0' : '')+xdate.getHours() +":"+
		(xdate.getMinutes() < 10 ? '0' : '')+xdate.getMinutes()+":"+
		(xdate.getSeconds() < 10 ? '0' : '')+xdate.getSeconds();
	    if (y==null) legends.eq(i).text(series.label + " " + xtime);
	    else legends.eq(i).text(series.label + " " + xtime + ", " + y.toFixed(2));
	}
    }
});
</script>
</body>
</html>
