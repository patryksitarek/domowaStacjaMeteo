import paho.mqtt.client as mqtt
import sqlite3

from datetime import datetime

topic = 'weather/#'
subtopics = ['temperature', 'pressure_humidity', 'uv', 'open_weather_API', 'air_pollution', 'rain']
db_file = 'weather.db'


def on_connect(client, userdata, flags, rc):
    print('Connected with result code ' + str(rc))
    client.subscribe(topic)
    #client.subscribe(onemoretopic)

def on_message(client, userdata, msg):
    try:
        message_topic = msg.topic.split('/')

        if (message_topic[0] == topic.split('/')[0] and message_topic[1] in subtopics):
            recived_data = str(msg.payload, 'utf-8').split()
            write_to_database(db_file, message_topic[1], recived_data)

    except UnicodeDecodeError as e:
        print('Error! ', e)


def write_to_database(db_file, topic, data):
    connection = sqlite3.connect(db_file)
    cursor = connection.cursor()

    if topic == 'temperature':
        query = 'INSERT INTO {} (air, dew, heat) VALUES ({}, {}, {})'.format(topic, data[0], data[1], data[2])
    elif topic == 'air_pollution':
        query = 'INSERT INTO {} (pm25, pm10) VALUES ({}, {})'.format(topic, data[0], data[1])
    elif topic == 'uv':
        query = 'INSERT INTO {} (uv) VALUES ({})'.format(topic, data[0])
    elif topic == 'open_weather_API':
        query = 'INSERT INTO {} (wind_speed, wind_gust, wind_deg, clouds) VALUES ({}, {}, {}, {})'.format(topic, data[0], data[1], data[2], data[3])
    elif topic == 'pressure_humidity':
        query = 'INSERT INTO {} (pressure, humidity) VALUES ({}, {})'.format(topic, data[0], data[1])
    elif topic == 'rain':
        query = 'INSERT INTO {} (rain) VALUES ({})'.format(topic, data[0])

    cursor.execute(query)
    connection.commit()
    cursor.close()
    connection.close()



def initialize_database(db_file, main_topic, subtopics):
    connection = sqlite3.connect(db_file)
    cursor = connection.cursor()

    for tablename in subtopics:
        if tablename == 'temperature':
            query = "CREATE TABLE IF NOT EXISTS {} (date DATE DEFAULT (datetime('now', 'localtime')), air REAL, dew REAL, heat REAL)".format(tablename)
        elif tablename == 'air_pollution':
            query = "CREATE TABLE IF NOT EXISTS {} (date DATE DEFAULT (datetime('now', 'localtime')), pm25 INT, pm10 INT)".format(tablename)
        elif tablename == 'uv':
            query = "CREATE TABLE IF NOT EXISTS {} (date DATE DEFAULT (datetime('now', 'localtime')), uv INT)".format(tablename)
        elif tablename == 'open_weather_API':
            query = "CREATE TABLE IF NOT EXISTS {} (date DATE DEFAULT (datetime('now', 'localtime')), wind_speed REAL, wind_gust REAL, wind_deg INT, clouds INT)".format(tablename)
        elif tablename == 'pressure_humidity':
            query = "CREATE TABLE IF NOT EXISTS {} (date DATE DEFAULT (datetime('now', 'localtime')), pressure INT, humidity INT)".format(tablename)
        elif tablename == 'rain':
            query = "CREATE TABLE IF NOT EXISTS {} (date DATE DEFAULT (datetime('now', 'localtime')), rain INT)".format(tablename)

        cursor.execute(query)



    connection.commit()
    cursor.close()
    connection.close()
    print('database initialized')


initialize_database(db_file, topic.split('/')[0], subtopics)

client = mqtt.Client()
client.on_connect = on_connect
client.on_message = on_message

client.connect("127.0.0.1", 1883, 60)

# Blocking call that processes network traffic, dispatches callbacks and
# handles reconnecting.
# Other loop*() functions are available that give a threaded interface and a
# manual interface.


client.loop_forever()
