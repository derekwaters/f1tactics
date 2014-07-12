
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
	var gapIndexes = [];
	for (var key in lastTimingData.driverInfo)
	{
		var driver = lastTimingData.driverInfo[key];

		gapIndexes.push({driverNumber : driver.number, behind : driver.behind, gap : driver.gap});
	}
	gapIndexes.sort(function compare(left, right)
	{
		if (left.behind !== undefined && left.behind.length > 0)
		{
			if (right.behind !== undefined && right.behind.length > 0)
			{
				var leftVal = null;
				var rightVal = null;
				if (left.behind.substr(left.behind.length - 1) == "L")
				{
					leftVal = parseFloat(left.behind.substr(0, left.behind.length - 1)) * 1000.0;
				}
				else
				{
					leftVal = parseFloat(left.behind);
				}
				if (right.behind.substr(right.behind.length - 1) == "L")
				{
					rightVal = parseFloat(right.behind.substr(0, right.behind.length - 1)) * 1000.0;
				}
				else
				{
					rightVal = parseFloat(right.behind);
				}
				return leftVal - rightVal;
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

	var battleOffset = 0;
	var inBattle = false;
	for (var i = 0; i < gapIndexes.length; i++)
	{
		var checkVal = parseFloat(gapIndexes[i].gap);
		if (gapIndexes[i].gap.length > 0 && checkVal < 2.0 && i > 0)
		{
			lastTimingData.driverInfo[gapIndexes[i - 1].driverNumber].battle = battleOffset;
			lastTimingData.driverInfo[gapIndexes[i].driverNumber].battle = battleOffset;
			inBattle = true;
		}
		else
		{
			if (inBattle)
			{
				battleOffset++;
			}
			inBattle = false;
			lastTimingData.driverInfo[gapIndexes[i].driverNumber].battle = null;
		}
	}

	var idMap = { 6 : 809, 44 : 828, 3 : 857, 14 : 30, 77 : 865, 1 : 822, 27 : 840, 22 : 6, 20 : 899, 19 : 18,
		11 : 867, 7 : 12, 25 : 870, 8 : 838, 26 : 906, 17 : 850, 99 : 818, 9 : 862, 13 : 869, 21 : 854, 4 : 887, 10 : 837 };


	$("#battleTable div").remove();
	var battleTable = $("#battleTable");
	var currentBattle = -1;
	var addStuff = "";
	for (var i = 0; i < gapIndexes.length; i++)
	{
		var driverInfo = lastTimingData.driverInfo[gapIndexes[i].driverNumber];

		if (driverInfo.battle != null)
		{
			if (currentBattle != driverInfo.battle)
			{
				if (currentBattle != -1)
				{
					addStuff += "<div class='battleClear'/></div>";
				}
				// Add a new div
				addStuff += "<div class='battleRow'><div class='battleBehind'>" + driverInfo.behind + "</div>";
			}
			else
			{
				addStuff += "<div class='battleGap'>" + driverInfo.gap + "</div>";
			}
			addStuff += "<div class='battleCell'><img src='http://www.formula1.com/photos/teams_and_drivers/driver_index/portrait/portrait_" + idMap[driverInfo.number] + ".jpg'><br/>" + driverInfo.name + "</div>";

			currentBattle = driverInfo.battle;
		}
	}
	if (currentBattle != -1)
	{
		addStuff += "<div class='battleClear'/></div>";
	}
	battleTable.append(addStuff);



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

		var battleColours = [ "#C00", "#AA0", "#008", "#0B0", "#B0B", "#600", "#0BB", "#555", "#B50", "#040", "#C96" ];

		var newLine = "<tr " + backcolor + "><td class='position'>" +
			driver.racePosition + "</td><td>" +
			driver.number + "</td><td class='driverName'>" +
			driver.name + "</td><td>" +
			(lastTimingData.currentLap > 0 ? (lastTimingData.currentLap - 1 - driver.laps_behind) : 0) + "</td><td>" +
			driver.lastlap + "</td><td>" +
			(driver.lastPitstop !== undefined ? (lastTimingData.currentLap - 1 - driver.laps_behind - driver.lastPitstop) : "") + "</td><td>" +
			(driver.gap !== undefined ? driver.gap : "") + "</td><td>" +
			(driver.behind !== undefined ? driver.behind : "") + "</td>" +
			((driver.battle !== undefined && driver.battle !== null) ? ("<td style='background-color : " + battleColours[driver.battle] + "'>" + driver.battle) : "<td>") + "</td><td>" +
			driver.sector1 + "</td><td>" +
			driver.sector2 + "</td><td>" +
			driver.sector3 + "</td><td>" +
			driver.status + "</td></tr>";
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
	getTiming = false;

	$("#timingTab").click(function()
	{
		$("#timingTable").show();
		$("#battleTable").hide();
		$(this).addClass("active");
		$("#battleTab").removeClass("active");
	});

	$("#battleTab").click(function()
	{
		$("#timingTable").hide();
		$("#battleTable").show();
		$(this).addClass("active");
		$("#timingTab").removeClass("active");
	});


	$("#battleTable").hide();
	$("#timingTab").addClass("active");

	initialiseTable();
	$("#timingStatus").click(function() {
		getTiming = !getTiming;
		$(this).text(getTiming ? "Stop Timing" : "Start Timing");
	});
	window.setInterval(updateTable, 1000);
});