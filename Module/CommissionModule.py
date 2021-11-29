import json
import re
from bs4 import BeautifulSoup
import requests
import time
import datetime
import mysql.connector
import string

from Entity.Commission import Commission
from Entity.Meeting import Meeting

class CommissionModule:
    COMMISSION_LIST_URL = 'https://titania.saeima.lv/livs/saeimasnotikumi.nsf/ComissionsList?readform'
    COMMISSION_URL_BASE = 'https://titania.saeima.lv/livs/saeimasnotikumi.nsf'
    MEETING_URL_BASE = 'https://titania.saeima.lv/livs/saeimasnotikumi.nsf/0/<UNID>?OpenDocument&prevCat=13'

    def scrapeCommissions(self):
        self.commissions = []

        commissionResponse = requests.get(self.COMMISSION_LIST_URL)
        commissionPageHTML = BeautifulSoup(commissionResponse.text, 'html.parser')
        commissionListHTML = commissionPageHTML.find(id='categoryBody_parl13')
        for commissionHTML in commissionListHTML.find_all('div', 'categoryListEntry'):
            self.commissions.append(Commission(commissionHTML.text, commissionHTML.b.a['href']))

    def updateCommissions(self):
        db = mysql.connector.connect(
            host="localhost",
            user="phpmyadminuser",
            passwd="password",
            database="saeima"
        )
        dbCursor = db.cursor()
        dbCursor.execute("SELECT display_name FROM commission")
        commissionsInDB = dbCursor.fetchall()
        commissionDisplayNamesInDB = []
        for commission in commissionsInDB:
            commissionDisplayNamesInDB.append(commission[0])


        for commission in self.commissions:
            if commission.displayName not in commissionDisplayNamesInDB:
                nameTranslation = commission.displayName.lower().maketrans('āčēģīķļņšūž ', 'acegiklnsuz-', ',./\\')
                dbCursor.execute("INSERT INTO commission (name, display_name, url) VALUES ('" + commission.displayName.lower().translate(nameTranslation) + "','" + commission.displayName + "', '" + commission.url + "')")
                print("Add new commission '" + commission.displayName + "' to the database")

        db.commit()


    def scrapeAllMeetings(self):
        for commission in self.commissions:
            print('...3 second pause')
            time.sleep(3)
            self.scrapeMeetingsByCommission(commission)
            print(commission.displayName + ": " + str(commission.meetingCount) + " meetings found")

    def scrapeMeetingsByCommission(self, commission):
        meetingResponse = requests.get(self.COMMISSION_URL_BASE + commission.url[1:])
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
                commission.meetingCount += 1
                m = Meeting(content[i]['unid'], meetingTimeString, content[i]['title'], content[i]['place'])
                commission.meetings.append(m)
                time.sleep(3)
                self.scrapeMeetingDescription(m)
    
    def scrapeMeetingDescription(self, meeting):
        meetingResponse = requests.get(self.MEETING_URL_BASE.replace('<UNID>', meeting.unid))
        meetingPageHTML = BeautifulSoup(meetingResponse.text, 'html.parser')
        meeting.meetingDescription = meetingPageHTML.find(id='textBody')
        
        print("Description of '" + meeting.title +  "' scraped")
        


    def checkMeetingChanges(self, days):
        o = 0
        #for commission in self.commissions:
        #    for meeting in commission.meetings:
                
    
    def uploadMeetingToDatabase(self):
        o = 0

    
