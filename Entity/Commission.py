import json
import re
from bs4 import BeautifulSoup
import requests

from Entity.Meeting import Meeting

class Commission:

    def __init__(self, displayName, url):
        self.displayName = displayName
        self.url = url
        self.meetingCount = 0
        self.meetings = []