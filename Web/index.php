<?php
        $latitude = 50.7328787;
        $longitude = 18.4002497;

	date_default_timezone_set("Europe/Warsaw");

	$db = new SQLite3('weather.db');

	$result = $db->query('SELECT date, air, dew, heat FROM temperature ORDER BY DATE DESC LIMIT 1;');
    $row = $result->fetchArray();
	$temperature = $row['air'];
	$dewPoint = $row['dew'];
	$heatIndex = $row['heat'];
	$date = $row['date'];

	$result = $db->query('SELECT pressure, humidity FROM pressure_humidity ORDER BY DATE DESC LIMIT 1;');
    $row = $result->fetchArray();
	$pressure = $row['pressure'];
	$humidity = $row['humidity'];

	$result = $db->query('SELECT pm25, pm10 FROM air_pollution ORDER BY DATE DESC LIMIT 1;');
    $row = $result->fetchArray();
    $pm25 = $row['pm25'];
    $pm10 = $row['pm10'];

	$result = $db->query('SELECT uv FROM uv ORDER BY DATE DESC LIMIT 1;');
    $uv = ($result->fetchArray())['uv'];

	$result = $db->query('SELECT wind_speed, wind_gust FROM open_weather_API ORDER BY DATE DESC LIMIT 1;');
    $row = $result->fetchArray();
    $windSpeed = $row['wind_speed'];
    $windGust = $row['wind_gust'];
?>

<!DOCTYPE HTML>
<html lang="pl">

<head>
	<meta charset="utf-8" />
	<title>METEO</title>
	<meta name="description" content="domowa stacja meteorologiczna/>
	<meta name="keywords" content="domowa stacja meteorologiczna, home weather station, pogoda" />
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta http-equiv="refresh" content="180">
	<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
	<link rel="stylesheet" href="style.css" type="text/css" />
	<link rel="stylesheet" href="css/fontello.css" type="text/css" />
	<link href="https://fonts.googleapis.com/css?family=Josefin+Sans|Lato&amp;subset=latin-ext" rel="stylesheet">
	
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
			<div class="navigate"><a href="#title">aktualne warunki</a></div>
			<div class="navigate"><a href="24h.php">ostatnia doba</a></div>
			<div class="navigate"><a href="month.php">ostatni miesiąc</a></div>
		</nav>
	</header>
	<main>
		<div id="title">
			<div class="center">
				<div id="date" class="temp">
					<?php
						$dzien=array('Monday' => 'poniedziałek',
								 'Tuesday' => 'wtorek',
								 'Wednesday' => 'środa',
								 'Thursday' => 'czwartek',
								 'Friday' => 'piątek',
								 'Saturday' => 'sobota',
								 'Sunday' => 'niedziela');

						$miesiac=array('Jan' => 'stycznia',
								   'Feb' => 'lutego',
								   'Mar' => 'marca',
								   'Apr' => 'kwietnia',
								   'May' => 'maja',
								   'Jun' => 'czerwca',
								   'Jul' => 'lipca',
								   'Aug' => 'sierpnia',
								   'Sep' => 'września',
								   'Oct' => 'października',
								   'Nov' => 'listopada',
								   'Dec' => 'grudnia');

						$dtyg = date("l");
						$nmiesiaca = date("M");
						$dmiesiaca = date("j");

						echo $dzien[$dtyg];
						echo ',&nbsp;';
						echo $dmiesiaca;
						echo '.&nbsp;';
						echo $miesiac[$nmiesiaca];
					?>
				</div>
				<div id="read" class="temp">
					<p><?php
							echo "odczyt: ";
							echo substr($date, 11);
					?></p>
				</div>
				<div style="clear:both;"></div>

				<div id="temperature" class="temp">
					<p class="value">
						<?php
							echo round($temperature, 1);
							echo "&#176;C";
						?>
					</p>
					<p class="describe">
						<?php
							echo "indeks ciepła ";
							echo round($heatIndex, 1);
							echo "°C";
						?>
					</p>
				</div>
				<div id="temp">
				<div id="pressure" class="temp">
					<p class="value">
						<?php
							echo $pressure;
							echo "&nbsp;hPa";
						?>
					</p>
					<p class="describe">ciśnienie</p>
				</div>

				<div id="humidity" class="temp">
					<p class="value">
						<?php
							echo $humidity;
							echo "%";
						?>
					</p>
					<p class="describe">wilgotność</p>
				</div>
				</div>
				<div style="clear:both;"></div>
				<div id="wind" class="temp">
					<p class="value">
						<?php
							echo round($windSpeed, 1);
							echo "&nbsp;";
							echo "<span class='fractup'>m</span>";
							echo "<span class='fractline'>&#8260;</span>";
							echo "<span class='fractdn'>s</span>";
						?>
					</p>
					<p class="describe">wiatr</p>
				</div>

				<div id="windgust" class="temp">
					<p class="value">
						<?php
							echo round($windGust, 1);
							echo "&nbsp;";
							echo "<span class='fractup'>m</span>";
							echo "<span class='fractline'>&#8260;</span>";
							echo "<span class='fractdn'>s</span>";
						?>
					</p>
					<p class="describe">porywy wiatru</p>
				</div>
				<div id="air25" class="temp">
					<p class="value">
						<?php
							echo $pm25;
							echo "&nbsp;";
							echo "<span class='fractup'>&#181;g</span>";
							echo "<span class='fractline'>&#8260;</span>";
							echo "<span class='fractdn'>m<sup>3</sup></span>";
						?>
					</p>
					<p class="describe">PM2.5</p>
				</div>
				<div id="air10" class="temp">
					<p class="value">
						<?php
							echo $pm10;
							echo "&nbsp;";
							echo "<span class='fractup'>&#181;g</span>";
							echo "<span class='fractline'>&#8260;</span>";
							echo "<span class='fractdn'>m<sup>3</sup></span>";
						?>
					</p>
					<p class="describe">PM10</p>
				</div>
				<div style="clear:both;"></div>
				<div id="uv" class="temp">
					<p class="value">
						<?php
							echo ((int) ($uv/100));
						?>
					</p>
					<p class="describe">UV</p>
				</div>
				<div id="sunrise" class="temp">
					<p class="value">
						<?php
										echo(date_sunrise(time(),SUNFUNCS_RET_STRING,50.7328787,18.4002497,90,1));
						?>
					</p>
					<p class="describe">wschód Słońca</p>
				</div>
				<div id="sunset" class="temp">
					<p class="value">
						<?php
										echo(date_sunset(time(),SUNFUNCS_RET_STRING,50.7328787,18.4002497,90,1));
						?>
					</p>
					<p class="describe">zachód Słońca</p>
				</div>
				<div id="dew_point" class="temp">
					<p class="value">
						<?php
							echo round($dewPoint, 1);
							echo "°C";
						?>
					</p>
					<p class="describe">punkt rosy</p>
				</div>
				<div style="clear:both;"></div>
			</div>
		</div>
	</main>
		<div id="about">
			<article>
				<header>
					<p id="aboutHeader">o stacji</p>
				</header>
				<section>
					<div id="text">
						<p>
							Stacja znajduje się w miejscowości Dobrodzień, woj. opolskie. Prezentowane dane zostały zmierzone za pomocą:
						</p>
						<ul>
							<li>Data: aktualny czas serwera</li>
							<li>Temperatura: DS18B20</li>
							<li>Ciśnienie: BME280</li>
							<li>Wilgotność: BME280</li>
							<li>Zanieczyszczenie powietrza pyłami PM2,5 i PM10: PMS3003</li>
							<li>Prędkość i porywy wiatru: OpenWeather API</li>
							<li>Promieniowanie UV: VEML6070</li>
							<li>Czas wschodu i zachodu Słońca: na podstawie współrzędnych geograficznych miejscowości i daty</li>
							<li>Indeks ciepła: obliczane na podstawie temperatury i wilgotności</li>
							<li>Temperatura punktu rosy: obliczane na podstawie temperatury i wilgotności</li>
							<li>Opady atmosferyczne: własny miernik opadów</li>
						</ul>
					</div>
					<div id="pic">
						<img src="img/dobrodzien.jpg" alt="dobrodzien" />
					</div>
					<div style="clear:both;"></div>
				</section>
			</article>
		</div>
	</main>

	<script>
		openBar();
		if (window.screen.availWidth < 1441) closeBar();

		function bar(){
			if (state == 1) closeBar();
			else openBar();
		}

		function openBar() {
		  document.getElementById("menubar").style.display = "block";
		  state = 1;
		}

		function closeBar() {
		  document.getElementById("menubar").style.display = "none";
		  state = 0;
		}
	</script>
</body>
</html>
