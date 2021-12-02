import json
import re
from bs4 import BeautifulSoup
import requests
import time
import datetime
import mysql.connector
import copy

from Entity.Commission import Commission
from Entity.Meeting import Meeting

class CommissionModule:
    COMMISSION_LIST_URL = 'https://titania.saeima.lv/livs/saeimasnotikumi.nsf/ComissionsList?readform'
    COMMISSION_URL_BASE = 'https://titania.saeima.lv/livs/saeimasnotikumi.nsf'
    MEETING_URL_BASE = 'https://titania.saeima.lv/livs/saeimasnotikumi.nsf/0/<UNID>?OpenDocument&prevCat=13'

    def __init__(self, config):
        self.config = config

    def scrapeCommissions(self):
        self.commissions = []

        commissionResponse = requests.get(self.COMMISSION_LIST_URL)
        commissionPageHTML = BeautifulSoup(commissionResponse.text, 'html.parser')
        commissionListHTML = commissionPageHTML.find(id='categoryBody_parl13')

        limit = 3
        for commissionHTML in commissionListHTML.find_all('div', 'categoryListEntry'):
            self.commissions.append(Commission(commissionHTML.text, commissionHTML.b.a['href']))
            limit -= 1
            if limit == 0: break

    def updateCommissions(self):
        db = mysql.connector.connect(
            host=self.config['DATABASE']['HOST'],
            user=self.config['DATABASE']['USERNAME'],
            passwd=self.config['DATABASE']['PASSWORD'],
            database=self.config['DATABASE']['DATABASE']
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
            lookupLimit = datetime.datetime.today() - datetime.timedelta(days = int(self.config['OTHERS']['MEETING_LOOKUP_DAYS']))

            if meetingTime > lookupLimit:
                commission.meetingCount += 1
                m = Meeting(content[i]['unid'], meetingTimeString, content[i]['title'], content[i]['place'])
                commission.meetings.append(m)
                time.sleep(3)
                self.scrapeMeetingDescription(m)
                


    def scrapeMeetingDescription(self, meeting):
        meetingResponse = requests.get(self.MEETING_URL_BASE.replace('<UNID>', meeting.unid))
        meetingPageHTML = BeautifulSoup(meetingResponse.text, 'html.parser')
        meeting.description = str(meetingPageHTML.find(id='textBody'))
        
        print("Description of '" + meeting.title +  "' scraped")


    def checkMeetingChanges(self):
        db = mysql.connector.connect(
            host=self.config['DATABASE']['HOST'],
            user=self.config['DATABASE']['USERNAME'],
            passwd=self.config['DATABASE']['PASSWORD'],
            database=self.config['DATABASE']['DATABASE']
        )
        dbCursor = db.cursor()


        for commission in self.commissions:
            for meeting in commission.meetings:
                
                print("SELECT * FROM meeting WHERE unid = '" + meeting.unid + "'")
                dbCursor.execute("SELECT * FROM meeting WHERE unid = '" + meeting.unid + "'")
                res = dbCursor.fetchall()

                if not res:
                    self.uploadMeetingToDatabase(meeting)
                else:
                    updateObject = copy.deepcopy(meeting)
                    
                    if updateObject.title == res[0][2]:
                        updateObject.title = None
                    if updateObject.meetingTime == res[0][3]:
                        updateObject.meetingTime = None
                    if updateObject.place == res[0][4]:
                        updateObject.place = None
                    if updateObject.description == res[0][5].decode("utf-8"):
                        updateObject.description = None                        

                    if updateObject.title != None or updateObject.meetingTime != None or updateObject.place != None or updateObject.description != None:
                        self.updateMeetingInDatabase(updateObject)
                    

    
    def uploadMeetingToDatabase(self, meeting):
        db = mysql.connector.connect(
            host=self.config['DATABASE']['HOST'],
            user=self.config['DATABASE']['USERNAME'],
            passwd=self.config['DATABASE']['PASSWORD'],
            database=self.config['DATABASE']['DATABASE']
        )
        dbCursor = db.cursor()

        dbCursor.execute("INSERT INTO meeting (unid, title, meeting_time, place, description) VALUES ('" + meeting.unid +  "','" + meeting.title + "', '" + meeting.meetingTime.strftime('%Y-%m-%d %H:%M:%S') + "', '" + meeting.place + "', '" + meeting.description + "')")
        db.commit()

        print("Database: add meeting '" + meeting.title +  "' at " + meeting.meetingTime.strftime('%Y-%m-%d %H:%M:%S'))

    def updateMeetingInDatabase(self, updateObject):
        db = mysql.connector.connect(
            host=self.config['DATABASE']['HOST'],
            user=self.config['DATABASE']['USERNAME'],
            passwd=self.config['DATABASE']['PASSWORD'],
            database=self.config['DATABASE']['DATABASE']
        )
        dbCursor = db.cursor()

        setlist = []

        if updateObject.title != None:
            setlist.append("title = '" + updateObject.title  + "'")
        if updateObject.meetingTime != None:
            setlist.append("meeting_time = '" + updateObject.meetingTime.strftime('%Y-%m-%d %H:%M:%S')  + "'")
        if updateObject.place != None:
            setlist.append("place = '" + updateObject.place  + "'")
        if updateObject.description != None:
            setlist.append("description = '" + updateObject.description  + "'")

        dbCursor.execute("UPDATE meeting SET " + ', '.join(setlist) + " WHERE unid = '" + updateObject.unid + "'")
        db.commit()

        print("Database: updated meeting '" + updateObject.unid +  "' as " + ', '.join(setlist))
