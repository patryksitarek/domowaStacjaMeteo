<?php
	$db = new SQLite3('weather.db');
	
	date_default_timezone_set("Europe/Warsaw");
    $lastDay = date("Y-m-d H:i:s",strtotime("-1 days"));
?>

<!DOCTYPE HTML>
<html lang="pl">

<head>
	<meta charset="utf-8" />
	<title>METEO</title>
	<meta name="description" content="domowa stacja meteorologiczna/>
	<meta name="keywords" content="domowa stacja meteorologiczna, home weather station, pogoda" />
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
	<link rel="stylesheet" href="styleCharts.css" type="text/css" />
	<link rel="stylesheet" href="css/fontello.css" type="text/css" />
	<link href="https://fonts.googleapis.com/css?family=Josefin+Sans|Lato&amp;subset=latin-ext" rel="stylesheet">
	<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>

	<!--[if lt IE 9]>
		<script src="//cdnjs.cloudflare.com/ajax/libs/html5shiv/3.7.3/html5shiv.min.js"></script>
	<![endif]-->
</head>

<body>
	<header class="mainHeader">
		<button class="menubutton" onclick="bar()">&#9776;</button>
		<p>
			domowa stacja meteorologiczna
		</p>
		<nav id="menubar">
			<div class="navigate" id="firstNavigate"><a href="index.php#title">aktualne warunki</a></div>
			<div class="navigate"><a href="24h.php">ostatnia doba</a></div>
			<div class="navigate"><a href="month.php">ostatni miesiąc</a></div>
		</nav>
	</header>

	<main>
		<div class="charts" id="firstChart">
			<article>
				<header>
					<p class="chartTitle">temperatura</p>
				</header>
				<section>
					<div class="temperature-dashboard">
						<div id="temperature-chart" class="chartHolder"></div>
						<div id="temperature-range" class="rangeHolder"></div>
					</div>

					<script>
						isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry/i.test(navigator.userAgent);

						google.charts.load('current', {'packages':['corechart', 'controls'], 'language': 'pl'});
						google.charts.setOnLoadCallback(drawDashboard);

						function drawDashboard() {
							temperatures = google.visualization.arrayToDataTable([
									['data', 'temperatura', 'punkt rosy', 'indeks ciepła'],
									<?php
										$result = $db->query("SELECT date, air, dew, heat FROM temperature WHERE date > '" . $lastDay . "' ORDER BY date;");
										while($row = $result->fetchArray()) {
											$date = str_replace(array(":", " "), "-", $row["date"]);
											list($year, $month, $day, $hour, $minute, $second) = explode("-", $date);

											echo "[new Date(" . $year . "," . ($month - 1) . "," . $day . "," . $hour . "," . $minute . "," . $second . "), "
												. $row["air"] . ", "
												. $row["dew"] . ", "
												. $row["heat"] . "],";
										}
									?>
							]);

							temperatureDashboard = new google.visualization.Dashboard(document.getElementById('temperature-dashboard'));

							if (isMobile) {
								var chartRangeSlider = new google.visualization.ControlWrapper({
									controlType: 'DateRangeFilter',
									containerId: 'temperature-range',
									options: {
										filterColumnIndex: 0,
										ui: {
											showRangeValues: false,
											label: ''
										}
									}
								});
							}
							else {
								var chartRangeSlider = new google.visualization.ControlWrapper({
									controlType: 'ChartRangeFilter',
									containerId: 'temperature-range',
									options: {
										filterColumnIndex: 0,
										ui: {
											chartType: 'LineChart',
											chartOptions: {
												height: 90,
												chartArea: {width: '40%'},
												colors: ['#dc3c14', '#14941c', '#fc9c04'],
												hAxis: {format: 'HH:mm'}
											}
										}
									}
								});
							}

							var chart = new google.visualization.ChartWrapper({
								chartType: 'LineChart',
								containerId: 'temperature-chart',
								options: {
									legend: {position: 'top'},
									chartArea: {width: '90%'},
									series: {0: {targetAxisIndex: 0}},
									vAxes: {0: {title: 'temperatura (°C)'}},
									hAxes: {0: {format: 'HH:mm'}},
									colors: ['#dc3c14', '#14941c', '#fc9c04'],
									animation: {
										startup: true,
										duration: 500,
										easing: 'out'
									}
								}
							});

							var columns = [];
							var series = {};
							for (var i = 0; i < temperatures.getNumberOfColumns(); i++) {
								columns.push(i);
								if (i > 0) series[i-1] = {};
							}

							google.visualization.events.addListener(chart, 'select', function (target) {
								var sel = target.getSelection();
								if (sel.length > 0) {
									//row undefined when clicked on legend
									if (sel[0].row === null) {
										var col = sel[0].column;

										var countTypes = 0;
										for (var i = 0; i < columns.length; i++) {
											if (typeof columns[i] == "number") countTypes++;
										}
										console.log(countTypes);
										if (columns[col] == col) {
											if (countTypes <= 2) return null;
											//hide series
											columns[col] = {
												label: temperatures.getColumnLabel(col),
												type: temperatures.getColumnType(col),
											}
											series[col-1].color = '#CCCCCC';
										} else {
											//show series
											columns[col] = col;
											series[col-1].color = null;
										}

										var view = new google.visualization.DataView(temperatures);
										view.setColumns(columns);
										temperatureDashboard.draw(view);
									}
								}
							});

							temperatureDashboard.bind(chartRangeSlider, chart);
							temperatureDashboard.draw(temperatures);
						}
					</script>
				</section>
			</article>
		</div>

		<div class="charts">
			<article>
				<header>
					<p class="chartTitle">ciśnienie i wilgotność</p>
				</header>
				<section>
					<div class="pressureHumidity-dashboard">
						<div id="pressureHumidity-chart" class="chartHolder"></div>
						<div id="pressureHumidity-range" class="rangeHolder"></div>
					</div>
					<script>
						google.charts.load('current', {'packages':['corechart', 'controls'], 'language': 'pl'});
						google.charts.setOnLoadCallback(drawDashboard);
						
						function drawDashboard() {
							pressureHumidity = google.visualization.arrayToDataTable([
									['data', 'ciśnienie', 'wilgotność względna'],
									<?php
										$result = $db->query("SELECT date, pressure, humidity FROM pressure_humidity WHERE date > '" . $lastDay . "' ORDER BY date;");
										while($row = $result->fetchArray()) {
											$date = str_replace(array(":", " "), "-", $row["date"]);
											list($year, $month, $day, $hour, $minute, $second) = explode("-", $date);

											echo "[new Date(" . $year . "," . ($month - 1) . "," . $day . "," . $hour . "," . $minute . "," . $second . "), "
												. $row["pressure"] . ", "
												. $row["humidity"] . "],";
										}
									?>
							]);

							pressureHumidityDashboard = new google.visualization.Dashboard(document.getElementById('pressureHumidity-dashboard'));

							if (isMobile) {
								var chartRangeSlider = new google.visualization.ControlWrapper({
									controlType: 'DateRangeFilter',
									containerId: 'pressureHumidity-range',
									options: {
										filterColumnIndex: 0,
										ui: {
											showRangeValues: false,
											label: ''
										}
									}
								});
							}
							else {
								var chartRangeSlider = new google.visualization.ControlWrapper({
									controlType: 'ChartRangeFilter',
									containerId: 'pressureHumidity-range',
									options: {
										filterColumnIndex: 0,
										ui: {
											chartType: 'LineChart',
											chartOptions: {
												height: 90,
												chartArea: {width: '40%'},
												series: {
													0: {targetAxisIndex: 0},
													1: {targetAxisIndex: 1}
												},
												
												hAxis: {format: 'HH:mm'}
											}
										}
									}
								});
							}

							var chart = new google.visualization.ChartWrapper({
								chartType: 'LineChart',
								containerId: 'pressureHumidity-chart',
								options: {
									legend: {position: 'top'},
									chartArea: {width: '90%'},
									series: {
										0: {targetAxisIndex: 0},
										1: {targetAxisIndex: 1}
									},
									vAxes: {
										0: {title: 'ciśnienie (hPa)'},
										1: {title: 'wilgotność względna (%)'}
									},
									hAxis: {0: {format: 'HH:mm'}},
									animation: {
										startup: true,
										duration: 500,
										easing: 'out'
									}
								}
							});
							
							var columns = [];
							var series = {};
							for (var i = 0; i < pressureHumidity.getNumberOfColumns(); i++) {
								columns.push(i);
								if (i > 0) series[i-1] = {};
							}

							google.visualization.events.addListener(chart, 'select', function (target) {
								var sel = target.getSelection();
								if (sel.length > 0) {
									//row undefined when clicked on legend
									if (sel[0].row === null) {
										var col = sel[0].column;

										var countTypes = 0;
										for (var i = 0; i < columns.length; i++) {
											if (typeof columns[i] == "number") countTypes++;
										}
										console.log(countTypes);
										if (columns[col] == col) {
											if (countTypes <= 2) return null;
											//hide series
											columns[col] = {
												label: pressureHumidity.getColumnLabel(col),
												type: pressureHumidity.getColumnType(col),
											}
											series[col-1].color = '#CCCCCC';
										} else {
											//show series
											columns[col] = col;
											series[col-1].color = null;
										}

										var view = new google.visualization.DataView(pressureHumidity);
										view.setColumns(columns);
										pressureHumidityDashboard.draw(view);
									}
								}
							});


							pressureHumidityDashboard.bind(chartRangeSlider, chart);
							pressureHumidityDashboard.draw(pressureHumidity);
						}
					</script>
				</section>
			</article>
		</div>

		<div class="charts">
			<article>
				<header>
					<p class="chartTitle">zanieczyszczenie powietrza</p>
				</header>
				<section>
					<div class="air-dashboard">
						<div id="air-chart" class="chartHolder"></div>
						<div id="air-range" class="rangeHolder"></div>
					</div>

					<script>
						google.charts.load('current', {'packages':['corechart', 'controls'], 'language': 'pl'});
						google.charts.setOnLoadCallback(drawDashboard);

						function drawDashboard() {
							air = google.visualization.arrayToDataTable([
									['data', 'PM25', 'PM10'],
									<?php
										$result = $db->query("SELECT date, pm25, pm10 FROM air_pollution WHERE date > '" . $lastDay . "' ORDER BY date;");
										while($row = $result->fetchArray()) {
											$date = str_replace(array(":", " "), "-", $row["date"]);
											list($year, $month, $day, $hour, $minute, $second) = explode("-", $date);

											echo "[new Date(" . $year . "," . ($month - 1) . "," . $day . "," . $hour . "," . $minute . "," . $second . "), "
												. $row["pm25"] . ", "
												. $row["pm10"] . "],";
										}
									?>
							]);

							airDashboard = new google.visualization.Dashboard(document.getElementById('air-dashboard'));

							if (isMobile) {
								var chartRangeSlider = new google.visualization.ControlWrapper({
									controlType: 'DateRangeFilter',
									containerId: 'air-range',
									options: {
										filterColumnIndex: 0,
										ui: {
											showRangeValues: false,
											label: ''
										}
									}
								});
							}
							else {
								var chartRangeSlider = new google.visualization.ControlWrapper({
									controlType: 'ChartRangeFilter',
									containerId: 'air-range',
									options: {
										filterColumnIndex: 0,
										ui: {
											chartType: 'LineChart',
											chartOptions: {
												height: 90,
												chartArea: {width: '40%'},
												colors: ['#000000', '#dc3c14'],
												hAxis: {format: 'HH:mm'}
											}
										}
									}
								});
							}

							var chart = new google.visualization.ChartWrapper({
								chartType: 'LineChart',
								containerId: 'air-chart',
								options: {
									legend: {position: 'top'},
									chartArea: {width: '90%'},
									series: {0: {targetAxisIndex: 0}},
									vAxes: {0: {title: 'cząsteczki (µg/m³)'}},
									hAxes: {0: {format: 'HH:mm'}},
									colors: ['#000000', '#dc3c14'],
									animation: {
										startup: true,
										duration: 500,
										easing: 'out'
									}
								}
							});

							var columns = [];
							var series = {};
							for (var i = 0; i < air.getNumberOfColumns(); i++) {
								columns.push(i);
								if (i > 0) series[i-1] = {};
							}

							google.visualization.events.addListener(chart, 'select', function (target) {
								var sel = target.getSelection();
								if (sel.length > 0) {
									//row undefined when clicked on legend
									if (sel[0].row === null) {
										var col = sel[0].column;

										var countTypes = 0;
										for (var i = 0; i < columns.length; i++) {
											if (typeof columns[i] == "number") countTypes++;
										}
										console.log(countTypes);
										if (columns[col] == col) {
											if (countTypes <= 2) return null;
											//hide series
											columns[col] = {
												label: air.getColumnLabel(col),
												type: air.getColumnType(col),
											}
											series[col-1].color = '#CCCCCC';
										} else {
											//show series
											columns[col] = col;
											series[col-1].color = null;
										}

										var view = new google.visualization.DataView(air);
										view.setColumns(columns);
										airDashboard.draw(view);
									}
								}
							});

							airDashboard.bind(chartRangeSlider, chart);
							airDashboard.draw(air);
						}
					</script>
				</section>
			</article>
		</div>
		
		<div class="charts">
			<article>
				<header>
					<p class="chartTitle">wiatr</p>
				</header>
				<section>
					<div class="wind-dashboard">
						<div id="wind-chart" class="chartHolder"></div>
						<div id="wind-range" class="rangeHolder"></div>
					</div>

					<script>
						google.charts.load('current', {'packages':['corechart', 'controls'], 'language': 'pl'});
						google.charts.setOnLoadCallback(drawDashboard);

						function drawDashboard() {
							wind = google.visualization.arrayToDataTable([
									['data', 'prędkość wiatru', 'porywy wiatru'],
									<?php
										$result = $db->query("SELECT date, wind_speed, wind_gust FROM open_weather_API WHERE date > '" . $lastDay . "' ORDER BY date;");
										while($row = $result->fetchArray()) {
											$date = str_replace(array(":", " "), "-", $row["date"]);
											list($year, $month, $day, $hour, $minute, $second) = explode("-", $date);

											echo "[new Date(" . $year . "," . ($month - 1) . "," . $day . "," . $hour . "," . $minute . "," . $second . "), "
												. $row["wind_speed"] . ", "
												. $row["wind_gust"] . "],";
										}
									?>
							]);

							windDashboard = new google.visualization.Dashboard(document.getElementById('wind-dashboard'));

							if (isMobile) {
								var chartRangeSlider = new google.visualization.ControlWrapper({
									controlType: 'DateRangeFilter',
									containerId: 'wind-range',
									options: {
										filterColumnIndex: 0,
										ui: {
											showRangeValues: false,
											label: ''
										}
									}
								});
							}
							else {
								var chartRangeSlider = new google.visualization.ControlWrapper({
									controlType: 'ChartRangeFilter',
									containerId: 'wind-range',
									options: {
										filterColumnIndex: 0,
										ui: {
											chartType: 'LineChart',
											chartOptions: {
												height: 90,
												chartArea: {width: '40%'},
												colors: ['#14941c', '#9c049c'],
												hAxis: {format: 'HH:mm'}
											}
										}
									}
								});
							}

							var chart = new google.visualization.ChartWrapper({
								chartType: 'LineChart',
								containerId: 'wind-chart',
								options: {
									legend: {position: 'top'},
									chartArea: {width: '90%'},
									series: {0: {targetAxisIndex: 0}},
									vAxes: {0: {title: 'prędkość (m/s)'}},
									hAxes: {0: {format: 'HH:mm'}},
									colors: ['#14941c', '#9c049c'],
									animation: {
										startup: true,
										duration: 500,
										easing: 'out'
									}
								}
							});

							var columns = [];
							var series = {};
							for (var i = 0; i < wind.getNumberOfColumns(); i++) {
								columns.push(i);
								if (i > 0) series[i-1] = {};
							}

							google.visualization.events.addListener(chart, 'select', function (target) {
								var sel = target.getSelection();
								if (sel.length > 0) {
									//row undefined when clicked on legend
									if (sel[0].row === null) {
										var col = sel[0].column;

										var countTypes = 0;
										for (var i = 0; i < columns.length; i++) {
											if (typeof columns[i] == "number") countTypes++;
										}
										console.log(countTypes);
										if (columns[col] == col) {
											if (countTypes <= 2) return null;
											//hide series
											columns[col] = {
												label: wind.getColumnLabel(col),
												type: wind.getColumnType(col),
											}
											series[col-1].color = '#CCCCCC';
										} else {
											//show series
											columns[col] = col;
											series[col-1].color = null;
										}

										var view = new google.visualization.DataView(wind);
										view.setColumns(columns);
										windDashboard.draw(view);
									}
								}
							});

							windDashboard.bind(chartRangeSlider, chart);
							windDashboard.draw(wind);
						}
					</script>
				</section>
			</article>
		</div>
		
		<div class="charts">
			<article>
				<header>
					<p class="chartTitle">UV</p>
				</header>
				<section>
					<div class="uv-dashboard">
						<div id="uv-chart" class="chartHolder"></div>
						<div id="uv-range" class="rangeHolder"></div>
					</div>

					<script>
						google.charts.load('current', {'packages':['corechart', 'controls'], 'language': 'pl'});
						google.charts.setOnLoadCallback(drawDashboard);

						function drawDashboard() {
							uv = google.visualization.arrayToDataTable([
									['data', 'UV'],
									<?php
										$result = $db->query("SELECT date, uv FROM uv WHERE date > '" . $lastDay . "' ORDER BY date;");
										while($row = $result->fetchArray()) {
											$date = str_replace(array(":", " "), "-", $row["date"]);
											list($year, $month, $day, $hour, $minute, $second) = explode("-", $date);

											echo "[new Date(" . $year . "," . ($month - 1) . "," . $day . "," . $hour . "," . $minute . "," . $second . "), "
												. $row["uv"] . "],";
										}
									?>
							]);

							uvDashboard = new google.visualization.Dashboard(document.getElementById('uv-dashboard'));

							if (isMobile) {
								var chartRangeSlider = new google.visualization.ControlWrapper({
									controlType: 'DateRangeFilter',
									containerId: 'uv-range',
									options: {
										filterColumnIndex: 0,
										ui: {
											showRangeValues: false,
											label: ''
										}
									}
								});
							}
							else {
								var chartRangeSlider = new google.visualization.ControlWrapper({
									controlType: 'ChartRangeFilter',
									containerId: 'uv-range',
									options: {
										filterColumnIndex: 0,
										ui: {
											chartType: 'LineChart',
											chartOptions: {
												height: 90,
												chartArea: {width: '40%'},
												hAxis: {format: 'HH:mm'},
												colors: ['#fb9e0a']
											}
										}
									}
								});
							}

							var chart = new google.visualization.ChartWrapper({
								chartType: 'LineChart',
								containerId: 'uv-chart',
								options: {
									legend: {position: 'top'},
									chartArea: {width: '90%'},
									series: {0: {targetAxisIndex: 0}},
									vAxes: {0: {title: 'promieniowanie (W/m²)'}},
									hAxes: {0: {format: 'HH:mm'}},
									colors: ['#fb9e0a'],
									animation: {
										startup: true,
										duration: 500,
										easing: 'out'
									}
								}
							});

							uvDashboard.bind(chartRangeSlider, chart);
							uvDashboard.draw(uv);
						}
					</script>
				</section>
			</article>
		</div>
		
		<div class="charts">
			<article>
				<header>
					<p class="chartTitle">opady deszczu</p>
				</header>
				<section>
					<div class="rain-dashboard">
						<div id="rain-chart" class="chartHolder"></div>
						<div id="rain-range" class="rangeHolder"></div>
					</div>

					<script>
						google.charts.load('current', {'packages':['corechart', 'controls'], 'language': 'pl'});
						google.charts.setOnLoadCallback(drawDashboard);

						function drawDashboard() {
							rain = google.visualization.arrayToDataTable([
									['data', 'deszcz'],
									<?php
										$result = $db->query("SELECT date, rain FROM rain WHERE date > '" . $lastDay . "' ORDER BY date;");
										while($row = $result->fetchArray()) {
											$date = str_replace(array(":", " "), "-", $row["date"]);
											list($year, $month, $day, $hour, $minute, $second) = explode("-", $date);

											echo "[new Date(" . $year . "," . ($month - 1) . "," . $day . "," . $hour . "," . $minute . "," . $second . "), "
												. ($row["rain"] * 0.625) . "],";
										}
									?>
							]);

							rainDashboard = new google.visualization.Dashboard(document.getElementById('rain-dashboard'));

							if (isMobile) {
								var chartRangeSlider = new google.visualization.ControlWrapper({
									controlType: 'DateRangeFilter',
									containerId: 'rain-range',
									options: {
										filterColumnIndex: 0,
										ui: {
											showRangeValues: false,
											label: ''
										}
									}
								});
							}
							else {
								var chartRangeSlider = new google.visualization.ControlWrapper({
									controlType: 'ChartRangeFilter',
									containerId: 'rain-range',
									options: {
										filterColumnIndex: 0,
										ui: {
											chartType: 'LineChart',
											chartOptions: {
												height: 90,
												chartArea: {width: '40%'},
												hAxis: {format: 'HH:mm'}
											}
										}
									}
								});
							}

							var chart = new google.visualization.ChartWrapper({
								chartType: 'ColumnChart',
								containerId: 'rain-chart',
								options: {
									legend: {position: 'top'},
									chartArea: {width: '90%'},
									series: {0: {targetAxisIndex: 0}},
									vAxes: {0: {title: 'opad (mm)'}},
									hAxes: {0: {format: 'HH:mm'}},
									bar: {groupWidth: '100%'},
									animation: {
										startup: true,
										duration: 500,
										easing: 'out'
									}
								}
							});

							rainDashboard.bind(chartRangeSlider, chart);
							rainDashboard.draw(rain);
						}
					</script>
				</section>
			</article>
		</div>
	</main>

	<script>
		openBar();
		if (window.screen.availWidth < 1441) closeBar();

		function bar(){
			if (state == 0) openBar();
			else closeBar();
		}

		function openBar() {
			document.getElementById("menubar").style.display = "block";
			state = 1;
		}

		function closeBar() {
			document.getElementById("menubar").style.display = "none";
			state = 0;
		}
		
		function reloadCharts() {
			pressureHumidityDashboard.draw(pressureHumidity);
			temperatureDashboard.draw(temperatures);
			airDashboard.draw(air);
			windDashboard.draw(wind);
			rainDashboard.draw(rain);
			uvDashboard.draw(uv);
		}
		window.onresize = reloadCharts;
	</script>
</body>
</html>
