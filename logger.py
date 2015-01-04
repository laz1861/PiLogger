#!/usr/bin/python

import sqlite3
import sys
import Adafruit_DHT
import time
import math

#Math Constants for Humidity conversion
c1 = -7.85951783
c2 = 1.84408259
c3 = -11.7866497
c4 = 22.6807411
c5 = -15.9618719
c6 = 1.80122502
c7 = 2.16679
Tc = 647.096 # Critical Temp, K
Pc = 22064000 # Critical Pressure, Pa

#Calculate measured/saturation temp ratio
def v(T, p):
    return math.pow(1 - (273.15 + T) / Tc, p)

#Calculate Water Vapor Saturation Pressure, Pws
def Pws(T):
    return Pc * math.exp( Tc * (c1*v(T,1) + c2*v(T,1.5) + c3*v(T,3) + c4*v(T,3.5) + c5*v(T,4) + c6*v(T,7.5)) / (273.15+T) )

#Calculate Water Vapor Pressure, Pw
def Pw(T,RH):
    return Pws(T) * RH / 100

#Calculate Absolute Humidity
def AbsHum(T,RH):
    return c7 * Pw(T,RH) / (273.15 + T)

#first we have to connect to a database
conn=sqlite3.connect('/home/pi/GPIO/tempdata.db')
c = conn.cursor()

#read data from our DHT Sensor
#read_retry 
humidity, temperature = Adafruit_DHT.read_retry(22,2)

if humidity is not None and temperature is not None:
  absh = round(AbsHum(temperature, humidity),2)
  temperature = temperature*9/5+32
  temperature = round(temperature, 1)
  humidity = round(humidity ,1)
  tdate=time.strftime("%y-%m-%d")
  ttime=time.strftime("%H:%M:%S")
  c.execute("INSERT INTO temps VALUES(?,?,?,?,?)",(tdate,ttime,temperature,humidity,absh))
  #print "Data recorded on " + str(tdate) + " at " + str(ttime)
  conn.commit()
else:
  #print "Data read failed"
    
conn.close()

