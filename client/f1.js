
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
	var indexes = [];
	for (var key in lastTimingData.driverInfo)
	{
		var driver = lastTimingData.driverInfo[key];

		indexes.push({driverNumber : driver.number, behind : driver.behind});
	}
	indexes.sort(function compare(left, right)
		{
			if (left.behind !== undefined && left.behind.length > 0)
			{
				if (right.behind !== undefined && right.behind.length > 0)
				{
					var leftVal = parseFloat(left.behind);
					var rightVal = parseFloat(right.behind);
					if (leftVal < rightVal)
					{
						return -1;
					}
					else if (leftVal > rightVal)
					{
						return 1;
					}
					else
					{
						return 0;
					}
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

		/*
		if (left.lastLap !== undefined && left.lastlap.length > 0)
		{
			if (right.lastLap !== undefined && right.lastlap.length > 0)
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
		*/
	// });

	$("#timingTable tbody tr").remove();
	var timingTableBody = $("#timingTable tbody");

	var updateSpan = $("#timingLastUpdate");
	updateSpan.text(lastTimingData.timestamp);

	var currentLapSpan = $("#timingCurrentLap");
	currentLapSpan.text(lastTimingData.currentLap);

	for (var i = 0; i < indexes.length; i++)
	{
		var driver = lastTimingData.driverInfo[indexes[i].driverNumber];
		// Lazy dodgy way
		var newLine = "<tr><td>" + driver.number + "</td><td>" + driver.name + "</td><td>" +
			driver.lastlap + "</td><td>" + "M" + "</td><td>" + (lastTimingData.currentLap > 0 ? (lastTimingData.currentLap - 1 - driver.laps_behind) : 0) + "</td><td>" +
			(driver.gap !== undefined ? driver.gap : "") + "</td><td>" +
			(driver.behind !== undefined ? driver.behind : "") + "</td><td>" + "   " + "</td></tr>";
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