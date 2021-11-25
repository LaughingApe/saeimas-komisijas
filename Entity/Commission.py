import json
import re
from bs4 import BeautifulSoup
import requests
import datetime

class Commission:
    COMMISSION_LIST_URL = 'https://titania.saeima.lv/livs/saeimasnotikumi.nsf/ComissionsList?readform'
    COMMISSION_URL_BASE = 'https://titania.saeima.lv/livs/saeimasnotikumi.nsf'

    def __init__(self, display_name, url):
        self.display_name = display_name
        self.url = url
        self.meetingCount = 0

    def scrapeMeetings(self):
        meetingResponse = requests.get(self.COMMISSION_URL_BASE + self.url[1:])
        meetingPageHTML = BeautifulSoup(meetingResponse.text, 'html.parser')
        meetingListHTML = meetingPageHTML.find(id='viewHolderText').text.strip()
        content = meetingListHTML.splitlines()

        for i in range(len(content)):
            content[i] = content[i][9:-2]
            it = content[i].find("time") + 6
            content[i] = list(content[i])
            if content[i][it]!='"':
                while content[i][it] != ':':
                    it+=1
                content[i][it] = '.'
            content[i] = "".join(content[i])
            content[i] = re.sub("(\w+):\"", r'"\1":"',  content[i])
            content[i] = json.loads(content[i])

            if content[i]['time'] == '':
                content[i]['time'] = '00:00'

            meetingTimeString =  content[i]['eDate'].replace('.', '-') + ' ' + content[i]['time'].replace('.', ':')
            
            meetingTime = datetime.datetime.strptime(meetingTimeString, '%d-%m-%Y %H:%M')
            lookupLimit = datetime.datetime.today() - datetime.timedelta(days = 28)


            if meetingTime > lookupLimit:
                self.meetingCount += 1