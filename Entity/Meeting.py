import json
import re
from bs4 import BeautifulSoup
import requests
import datetime

class Meeting:

    def __init__(self, unid, meetingTime, title, place):
        self.unid = unid
        self.meetingTime = datetime.datetime.strptime(meetingTime, '%d-%m-%Y %H:%M')
        self.title = title
        self.place = place
        print(meetingTime + ' created')
