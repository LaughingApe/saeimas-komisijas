import json
import re
from bs4 import BeautifulSoup
import requests
import time

from Entity.Commission import Commission

class WebScraperController: 

    def scrapeCommissions(self):
        self.commissions = []

        commissionResponse = requests.get(Commission.COMMISSION_LIST_URL)
        commissionPageHTML = BeautifulSoup(commissionResponse.text, 'html.parser')
        commissionListHTML = commissionPageHTML.find(id='categoryBody_parl13')
        for commissionHTML in commissionListHTML.find_all('div', 'categoryListEntry'):
            self.commissions.append(Commission(commissionHTML.text, commissionHTML.b.a['href']))


    def scrapeAllMeetings(self):
        for commission in self.commissions:
            print('...be polite, wait 3 seconds')
            time.sleep(3)
            print(commission.display_name)
            commission.scrapeMeetings()
            print(str(commission.meetingCount) + " meetings found")