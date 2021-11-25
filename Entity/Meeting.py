import json
import re
from bs4 import BeautifulSoup
import requests
import datetime

class Meeting:
    MEETING_URL_BASE = 'https://titania.saeima.lv/livs/saeimasnotikumi.nsf/0/<UNID>?OpenDocument&prevCat=13'

    def __init__(self, unid, meetingTime, title, place):
        self.unid = unid
        self.meetingTime = datetime.datetime.strptime(meetingTime, '%d-%m-%Y %H:%M')
        self.title = title
        self.place = place
        print(meetingTime + ' created')

    def scrapeSelf(self):
        meetingResponse = requests.get(self.MEETING_URL_BASE.replace('<UNID>', self.unid))
        meetingPageHTML = BeautifulSoup(meetingResponse.text, 'html.parser')
        meetingDescription = meetingPageHTML.find(id='textBody')
        
        print(self.unid)
        print(self.meetingTime)
        print(self.title)
        print(self.place)
        print(meetingDescription)

