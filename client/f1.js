
var lastTimingData;
var getTiming;

function initialiseTable ()
{

	$.getJSON("http://localhost/f1/timingData.php",function(result)
	{
		lastTimingData = result;
		refreshTable();
	});
}

function refreshTable ()
{
	$("#timingTable tbody tr").remove();
	var timingTableBody = $("#timingTable tbody");

	var updateSpan = $("#timingLastUpdate");
	updateSpan.text(lastTimingData.timestamp);
	for (var i = 0; i < lastTimingData.driverInfo.length; i++)
	{
		var driver = lastTimingData.driverInfo[i];
		// Lazy dodgy way
		var newLine = "<tr><td>" + driver.number + "</td><td>" + driver.name + "</td><td>" +
			driver.lastlap + "</td><td>" + "M" + "</td><td>" + "0" + "</td><td>" + driver.gap + "</td><td>" +
			driver.behind + "</td><td>" + "   " + "</td></tr>";
		timingTableBody.append(newLine);
	}
}

function updateTable ()
{
	if (getTiming)
	{
		var stateData = JSON.stringify(lastTimingData);
		$.post("http://localhost/f1/timingData.php",
			{ currentState : stateData },
			function(result)
			{
				lastTimingData = result;
				refreshTable();
			},
			"json"
		);
	}
}

$(document).ready(function()
{
	getTiming = true;
	initialiseTable();
	$("#timingStatus").click(function() {
		getTiming = !getTiming;
		$(this).text(getTiming ? "Stop Timing" : "Start Timing");
	});
	window.setInterval(updateTable, 10000);
});