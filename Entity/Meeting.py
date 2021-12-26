import json
import re
from bs4 import BeautifulSoup
import requests
import datetime

class Meeting:

    def __init__(self, unid, meetingTime, title, place, description = None):
        self.unid = unid
        if isinstance(meetingTime, str):
            self.meetingTime = datetime.datetime.strptime(meetingTime, '%d-%m-%Y %H:%M')
        else:
            self.meetingTime = meetingTime
        self.title = title
        self.place = place
        self.description = description
