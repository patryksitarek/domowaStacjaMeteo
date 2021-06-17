#include <WiFi.h>                                    //WIFI
#include <PubSubClient.h>                            //MQTT
#include <Adafruit_BME280.h>
#include <OneWire.h>
#include <JSON_Decoder.h>
#include <OpenWeather.h>
#include <Adafruit_VEML6070.h>
#include <esp_bt.h>

#define netSsid "YOUR NETWORK SSID"                 //network SSID
#define netPass "YOUR NETWORK PASS"                 //network password
IPAddress mqttBroker(192,168,1,22);                 //MQTT broker address 
int QoS = 2;                                        //MQTT Quality of service

WiFiClient espClient;
PubSubClient client(espClient);

OneWire dallas(15);

int peroid = 600;
long lastMsg = ((peroid*1000) + 1000) * -1;
int loopCounter = -1;

unsigned char buff [23];
bool pmsAwake = false;
#define pmsWakeUp 14

#define pin0 12
#define pin1 13
volatile int counter = 0;
volatile unsigned long DebounceTimer1;
volatile unsigned long DebounceTimer2;
volatile unsigned int delayTime = 100;


Adafruit_BME280 bme280;
Adafruit_VEML6070 veml6070 = Adafruit_VEML6070();
bool bmeInitialized = false;
bool vemlInitialized = false;

char temperatures[32];
char pressureHumidity[16];
char uv[8];
char APIdata[32];
char airPollution[16];
char reedSwitch[16];

int humidity;
float tempHeatIndex;
float tempTemperature;
int PM25;
int PM10;

String api_key = "YOUR API KEY";
String latitude =  "YOUR LOCATION LATITUDE";
String longitude = "YOUR LOCATION LONGITUDE";
String units = "metric";
String language = "pl"; //polish
OW_Weather ow;

void setup() {
  btStop();
  esp_bt_controller_disable();
  setCpuFrequencyMhz(80);

  client.setServer(mqttBroker, 1883);

  if (bme280.begin(0x76)) {
    bmeInitialized = true;
    bme280.setSampling(Adafruit_BME280::MODE_FORCED,
                       Adafruit_BME280::SAMPLING_X1, // temperature
                       Adafruit_BME280::SAMPLING_X4, // pressure
                       Adafruit_BME280::SAMPLING_X4, // humidity
                       Adafruit_BME280::FILTER_X4);
  }

  veml6070.begin(VEML6070_1_T);
  vemlInitialized = true;     // pass in the integration time constant
  veml6070.sleep(true);

  pinMode(pin0, INPUT_PULLUP);
  pinMode(pin1, INPUT_PULLUP);
  attachInterrupt(pin0, funct1, FALLING);
  attachInterrupt(pin1, funct2, FALLING);
  
  Serial.begin(9600, SERIAL_8N1, 16, 17);
  pinMode(pmsWakeUp, OUTPUT);
  digitalWrite(pmsWakeUp, LOW);
}

void loop() {
  int now = millis();
  client.loop();

  if ((now - lastMsg > ((peroid * 1000) - 60000)) && !pmsAwake && loopCounter == 0) {
    pmsAwake = true;
    digitalWrite(pmsWakeUp, HIGH);
  }
  
  if (now - lastMsg > (peroid * 1000)) {
    lastMsg = now;
    reconnect();

    //GET PRESSURE AND HUMIDITY
    if (bmeInitialized) {
      for (int i = 0; i < 10; i++) {
        bme280.takeForcedMeasurement();
        int pressure = round(bme280.readPressure() / 100.0F);
        humidity = round(bme280.readHumidity());

        if (800 <= pressure && pressure < 1117 && 0 <= humidity && humidity <= 100) {
          sprintf(pressureHumidity, "%s %s", String(pressure), String(humidity));
          client.publish("weather/pressure_humidity", pressureHumidity, QoS);
          break;
        } else delay(500);
      }
    }

    //GET TEMPERATURE
    for (int i = 0; i < 10; i++) {
      if (getDallas()) {
        sprintf(temperatures, "%s %s %s", String(tempTemperature), String(dewPoint(tempTemperature, humidity)), String(heatIndex(tempTemperature, humidity)));
        client.publish("weather/temperature", temperatures, QoS);
        break;
      } else delay(500);
    }

    //GET UV
    if (vemlInitialized) {
      veml6070.sleep(false);
      int tempUV = veml6070.readUV();
      veml6070.sleep(true);

      if (0 <= tempUV && tempUV < 65535) {
        dtostrf(tempUV, 1, 0, uv);
        client.publish("weather/uv", uv, QoS);
      }
    }
    
    //OPEN WEATHER API
    for (int i = 0; i < 10; i++) {
      if (openWeatherAPI()){
        client.publish("weather/open_weather_API", APIdata, QoS);
        break;
      } else delay(500);
    }

    //EVERY 6TH ITERATION
    if (loopCounter == 0) {

      //SEND RAIN
      client.publish("weather/rain", dtostrf(counter, 1, 0, reedSwitch), QoS); 
      counter = 0;
      
      //SEND AIR POLLUTION
      if (getPMS3003()) {
          sprintf(airPollution, "%s %s", String(PM25), String(PM10));
          client.publish("weather/air_pollution", airPollution, QoS);
      }
      digitalWrite(pmsWakeUp, LOW);
      pmsAwake = false;
    }
    
    ++loopCounter;
    loopCounter %= 6;
  }
  goSleep(now);
}

void reconnect() {
  if (WiFi.status() != 3) {
    WiFi.begin(netSsid, netPass);
    while (WiFi.status() != 3) delay(500);
  }
   
  while (!client.connected()) {
    if (client.connect("ESP32Client")) client.subscribe("weather/temperature");
    else delay(500);
  }
}

void goSleep(int now) {
  delay(1000);
  WiFi.disconnect(true);
  WiFi.mode(WIFI_OFF);
  if (digitalRead(12)) esp_sleep_enable_ext0_wakeup(GPIO_NUM_13, 1);
  else esp_sleep_enable_ext0_wakeup(GPIO_NUM_12, 1);

  esp_sleep_enable_timer_wakeup(((peroid * 1000) - (now - lastMsg)) * 1000);
  esp_light_sleep_start();
}

bool getDallas() {
  byte i;
  byte present = 0;
  byte data[12];
  byte addr[8];

  if ( !dallas.search(addr)) {
    dallas.reset_search();
    delay(250);
    return false;
  }

  if (OneWire::crc8(addr, 7) != addr[7]) return false; //CRC not valid

  dallas.reset();
  dallas.select(addr);
  dallas.write(0x44, 1); //start parasite powerds

  delay(750); //750 ms in documentation for 12 bit

  present = dallas.reset();
  dallas.select(addr);    
  dallas.write(0xBE);

  for ( i = 0; i < 9; i++) { // read 9 bytes
    data[i] = dallas.read();
  }

  int16_t raw = (data[1] << 8) | data[0];
  
  byte cfg = (data[4] & 0x60);

  //for lower resolution, 12 bit default
  if (cfg == 0x00) raw = raw & ~7;  //9 bit 93.75 ms
  else if (cfg == 0x20) raw = raw & ~3; //10 bit 187.5 ms
  else if (cfg == 0x40) raw = raw & ~1; //11 bit 375 ms

  tempTemperature = (float) raw / 16.0;
  if (-40 <= tempTemperature && tempTemperature <= 60) return true;
  else return false;
}


float dewPoint(float temperature, float humidity) {
  //temperature in Celsius degree, humidity in percent
  float RATIO = 373.15 / (273.15 + temperature);
  float SUM = -7.90298 * (RATIO - 1);
  SUM += 5.02808 * log10(RATIO);
  SUM += -1.3816e-7 * (pow(10, (11.344 * (1 - 1/RATIO ))) - 1) ;
  SUM += 8.1328e-3 * (pow(10, (-3.49149 * (RATIO - 1))) - 1) ;
  SUM += log10(1013.246);
  float VP = pow(10, SUM - 3) * humidity;
  float T = log(VP/0.61078);

  return (241.88 * T) / (17.558 - T);
}

float heatIndex(float temperature, float humidity) {
  //Rothfusz&Steadman's equations http://www.wpc.ncep.noaa.gov/html/heatindex_equation.shtml

  temperature = (temperature * 1.8) + 32;
  float hi = 0.5 * (temperature + 61.0 + ((temperature - 68.0) * 1.2) + (humidity * 0.094));

  if (hi > 79) {
    hi = -42.379 +
       2.04901523 * temperature +
       10.14333127 * humidity +
       -0.22475541 * temperature * humidity +
       -0.00683783 * pow(temperature, 2) +
       -0.05481717 * pow(humidity, 2) +
       0.00122874 * pow(temperature, 2) * humidity +
       0.00085282 * temperature * pow(humidity, 2) +
       -0.00000199 * pow(temperature, 2) * pow(humidity, 2);

    if ((humidity < 13) && (temperature >= 80.0) && (temperature <= 112.0))
      hi -= ((13.0 - humidity) * 0.25) * sqrt((17.0 - abs(temperature - 95.0)) * 0.05882);

    else if ((humidity > 85.0) && (temperature >= 80.0) && (temperature <= 87.0))
      hi += ((humidity - 85.0) * 0.1) * ((87.0 - temperature) * 0.2);
  }
  return (hi - 32) / 1.8;
}

bool openWeatherAPI() {
  OW_current *current = new OW_current;
  ow.getForecast(current, NULL, NULL, api_key, latitude, longitude, units, language);
  delay(5000);
  if (current && current->wind_gust != 0) {
    sprintf(APIdata, "%s %s %s %s", String(current->wind_speed), String(current->wind_gust), String(current->wind_deg), String(current->clouds));
    delete current;
    return true;
  }
  return false;
}

bool getPMS3003() {
  int sumPM25 = 0;
  int sumPM10 = 0;
  int counter = 0;
  
  for (int i = 0; i < 4; i++) {
    unsigned long startAttemptTime = millis();
    while (!Serial.find(0x42) && (millis() - startAttemptTime <= 15000));
    Serial.readBytes(buff, 23);
    if (buff[0] == 0x4d && checkString(buff)) {
      sumPM25 += (buff[11]<<8) + buff[12];
      sumPM10 += (buff[13]<<8) + buff[14];
      counter++;
      break;
    } else return false;
  }
  PM25 = sumPM25 / counter;
  PM10 = sumPM10 / counter;
  return true;
}

bool checkString(unsigned char *buf) {
  int sum = 0;
  int len = 23;

  for (int i = 0; i < (len-2); i++) sum += buf[i];
  sum += 0x42;
  if (sum == (buf[len - 2] << 8) + buf[len - 1]) return true;
  return false;
}

void funct1() {
 if (counter += (millis() - DebounceTimer1) >= (delayTime )) DebounceTimer1 = millis();
 //esp_sleep_enable_ext0_wakeup(GPIO_NUM_12, 1);
 goSleep(millis());
}

void funct2() {
 if (counter += (millis() - DebounceTimer2) >= (delayTime )) DebounceTimer2 = millis();
 //esp_sleep_enable_ext0_wakeup(GPIO_NUM_13, 1);
 goSleep(millis());
}
