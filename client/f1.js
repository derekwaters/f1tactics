
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
	// Analyse gaps


	// Sort into "fastest last lap" order.
	var indexes = [];
	for (var key in lastTimingData.driverInfo)
	{
		var driver = lastTimingData.driverInfo[key];

		indexes.push({driverNumber : driver.number, lastlap : driver.lastlap});
	}
	indexes.sort(function compare(left, right)
		{
			if (left.lastlap !== undefined && left.lastlap.length > 0)
			{
				if (right.lastlap !== undefined && right.lastlap.length > 0)
				{
					return left.lastlap.localeCompare(right.lastlap);
				}
				else
				{
					return -1;
				}
			}
			else
			{
				return 1;
			}
		});

	// Refresh the data fields
	$("#timingTable tbody tr").remove();
	var timingTableBody = $("#timingTable tbody");

	var updateSpan = $("#timingLastUpdate");
	updateSpan.text(lastTimingData.timestamp);

	var currentLapSpan = $("#timingCurrentLap");
	currentLapSpan.text(lastTimingData.currentLap);

	// Refresh the driver table
	for (var i = 0; i < indexes.length; i++)
	{
		var driver = lastTimingData.driverInfo[indexes[i].driverNumber];

		var backcolor = "";
		if (driver.racePosition - i > 10)
		{
			 backcolor = "style='background-color: #A00'";
		}
		else if (driver.racePosition - i > 5)
		{
			backcolor = "style='background-color: #600'";
		}

		var newLine = "<tr " + backcolor + "><td>" + driver.number + "</td><td>" + driver.name + "</td><td>" + driver.racePosition + "</td><td>" +
			driver.lastlap + "</td><td>" +
			(driver.lastPitstop !== undefined ? (lastTimingData.currentLap - 1 - driver.laps_behind - driver.lastPitstop) : "") +
			"</td><td>" + (lastTimingData.currentLap > 0 ? (lastTimingData.currentLap - 1 - driver.laps_behind) : 0) + "</td><td>" +
			(driver.gap !== undefined ? driver.gap : "") + "</td><td>" +
			(driver.behind !== undefined ? driver.behind : "") + "</td><td>" + driver.status + "</td></tr>";
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
	window.setInterval(updateTable, 1000);
});