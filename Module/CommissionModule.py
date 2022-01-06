import json
import re
from bs4 import BeautifulSoup
import requests
import time
import datetime
import mysql.connector
import copy
import logging

from requests.api import request

from Entity.Commission import Commission
from Entity.Meeting import Meeting
from Module.EmailDispatchingModule import EmailDispatchingModule

class CommissionModule:

    # Initializer takes app configuration and Email dispatching module
    def __init__(self, config, emailDispatchingModule):
        self.config = config
        self.emailDispatchingModule = emailDispatchingModule

    # Read the commission list from Titania
    def scrapeCommissions(self):
        self.commissions = []

        requestSuccessful = False
        for i in range(int(self.config['OTHERS']['FAILED_REQUEST_REPEATED_ATTEMPTS'])): # Try to repeat the request in case of request failure
            try:
                commissionResponse = requests.get(self.config['SCRAPER']['COMMISSION_LIST_URL'])
                requestSuccessful = True
            except requests.exceptions as errh:
                logging.warning('scrapeCommissions request failed (attempt ' + str(i+1) + '/' + self.config['OTHERS']['FAILED_REQUEST_REPEATED_ATTEMPTS'] + ') ' + errh)
                time.sleep(3*(i+2)) # Increase the delay after every unsuccessful attempt

        if requestSuccessful == False:
            logging.error('scrapeCommissions failed to scrape the commission list')
            return False

        commissionPageHTML = BeautifulSoup(commissionResponse.text, 'html.parser')
        commissionListHTML = commissionPageHTML.find(id='categoryBody_parl13')

        for commissionHTML in commissionListHTML.find_all('div', 'categoryListEntry'):
            self.commissions.append(Commission(commissionHTML.text, commissionHTML.b.a['href'])) # Put all commissions in a list
        
        return True
        
    # Update the commission list in the database
    def updateCommissions(self):
        db = mysql.connector.connect(
            host=self.config['DATABASE']['HOST'],
            user=self.config['DATABASE']['USERNAME'],
            passwd=self.config['DATABASE']['PASSWORD'],
            database=self.config['DATABASE']['DATABASE']
        )
        dbCursor = db.cursor()
        dbCursor.execute("SELECT display_name FROM commissions")
        commissionsInDB = dbCursor.fetchall()
        commissionDisplayNamesInDB = []
        for commission in commissionsInDB:
            commissionDisplayNamesInDB.append(commission[0]) # Make a list of commission display names


        for commission in self.commissions:
            if commission.displayName not in commissionDisplayNamesInDB: # If commission not in database, insert it
                nameTranslation = commission.displayName.lower().maketrans('āčēģīķļņšūž ', 'acegiklnsuz-', ',./\\') # Create commission technical name from display name
                dbCursor.execute("INSERT INTO commissions (name, display_name, url) VALUES ('" + commission.displayName.lower().translate(nameTranslation) + "','" + commission.displayName + "', '" + commission.url + "')")
                db.commit()
                logging.info('Stored new commission in database: ' + commission.displayName)
            
            # Get and store the commission database ID
            dbCursor.execute("SELECT id FROM commissions WHERE display_name='" + commission.displayName + "'")
            dbResponse = dbCursor.fetchall()
            commission.id = dbResponse[0][0]


    # Scrape all current meetings commission by commission
    def scrapeAllMeetings(self):
        for commission in self.commissions:
            time.sleep(3) # Pause so as to not overwhelm Titania with requests
            self.scrapeMeetingsByCommission(commission) # Scrape the meetings of this commission

    # Scrape all meetings of one commission
    def scrapeMeetingsByCommission(self, commission):
        requestSuccessful = False
        for i in range(int(self.config['OTHERS']['FAILED_REQUEST_REPEATED_ATTEMPTS'])): # Try to repeat the request in case of request failure
            try:
                meetingResponse = requests.get(self.config['SCRAPER']['COMMISSION_URL_BASE'] + commission.url[1:])
                requestSuccessful = True
            except requests.exceptions as errh:
                logging.warning('scrapeMeetingsByCommission request failed (attempt ' + str(i+1) + '/'  + self.config['OTHERS']['FAILED_REQUEST_REPEATED_ATTEMPTS'] + ') ' + errh)
                time.sleep(3*(i+2)) # Increase the delay after every unsuccessful attempt
            
        if requestSuccessful == False:
            logging.error('scrapeMeetingsByCommission failed to scrape the meetings of commission "' + commission.displayName + '"')
            return False

        meetingPageHTML = BeautifulSoup(meetingResponse.text, 'html.parser')
        meetingListHTML = meetingPageHTML.find(id='viewHolderText').text.strip() # Get data of all meetings
        content = meetingListHTML.splitlines()

        for i in range(len(content)): # Go through every meeting
            content[i] = content[i][9:-2] # Remove parts that are not in the same json
            it = content[i].find("time") + 6 # Find Time attribute
            content[i] = list(content[i])
            if content[i][it]!='"': # If time field is not empty, substitute ':' with '.'
                while content[i][it] != ':':
                    it+=1
                content[i][it] = '.'
            content[i] = "".join(content[i])
            content[i] = re.sub("(\w+):\"", r'"\1":"',  content[i])
            content[i] = json.loads(content[i])

            # For no time given, let's assume it's midnight (will be stored as date with zeros for time)
            if content[i]['time'] == '':
                content[i]['time'] = '00:00'

            # Save meeting string in standard format "YYYY-MM-DD HH:MM:SS"
            meetingTimeString =  content[i]['eDate'].replace('.', '-') + ' ' + content[i]['time'].replace('.', ':')
            
            meetingTime = datetime.datetime.strptime(meetingTimeString, '%d-%m-%Y %H:%M')
            lookupLimit = datetime.datetime.today() - datetime.timedelta(days = int(self.config['OTHERS']['MEETING_LOOKUP_DAYS'])) # Calculate limit of how old meetings will be scraped

            if meetingTime > lookupLimit: # If this meeting is not too old, get it's description and save it
                commission.meetingCount += 1
                m = Meeting(content[i]['unid'], meetingTimeString, content[i]['title'], content[i]['place'])
                commission.meetings.append(m)
                time.sleep(3)
                self.scrapeMeetingDescription(m)

        logging.info('Commission "' + commission.displayName + '" and its ' + str(len(commission.meetings)) + ' meeting(s) scraped.')

        return True
                
    # Scrape meeting description (agenda) of one meeting
    def scrapeMeetingDescription(self, meeting):
        requestSuccessful = False
        for i in range(int(self.config['OTHERS']['FAILED_REQUEST_REPEATED_ATTEMPTS'])): # Try to repeat the request in case of request failure
            try:
                meetingResponse = requests.get(self.config['SCRAPER']['MEETING_URL_BASE'].replace('<UNID>', meeting.unid))
                requestSuccessful = True
            except requests.exceptions as errh:
                logging.warning('scrapeMeetingDescription request failed (attempt ' + str(i+1) + '/'  + self.config['OTHERS']['FAILED_REQUEST_REPEATED_ATTEMPTS'] + ') ' + errh)
                time.sleep(3*(i+2)) # Increase the delay after every unsuccessful attempt
            
        if requestSuccessful == False:
            logging.error('scrapeMeetingDescription failed to scrape the meeting description of meeting ' + meeting.unid)
            return False
        
        meetingPageHTML = BeautifulSoup(meetingResponse.text, 'html.parser')
        meeting.description = str(meetingPageHTML.find(id='textBody'))
        
        logging.info('Scraped description of meeting ' + meeting.title + ' (' + meeting.unid + ')')

    # Check what has changed, order updates in database and email dispatching, if necessary
    def checkMeetingChanges(self):
        db = mysql.connector.connect(
            host=self.config['DATABASE']['HOST'],
            user=self.config['DATABASE']['USERNAME'],
            passwd=self.config['DATABASE']['PASSWORD'],
            database=self.config['DATABASE']['DATABASE']
        )
        dbCursor = db.cursor()

        newMeetingCounter = 0
        updatedMeetingCounter = 0

        # Go through every commissions every meeting (of those, which are saved in memory/relevant)
        for commission in self.commissions:
            for meeting in commission.meetings:
                
                dbCursor.execute("SELECT * FROM meetings WHERE unid = '" + meeting.unid + "'")
                res = dbCursor.fetchall()

                if not res: # If meeting not in database, upload it and send notifications about new meeting
                    newMeetingCounter += 1
                    self.uploadMeetingToDatabase(meeting, commission.id)
                    self.emailDispatchingModule.notifyMeetingAdded(meeting, commission.displayName)
                else: # If meeting already is in database, upload it and send notifications about new meeting
                    updateObject = copy.deepcopy(meeting) # Update object with everything that has changed
                    oldMeeting = Meeting(res[0][1], res[0][3], res[0][2], res[0][4], res[0][5]) # Save a copy of old meeting; will be needed for email dispatching
                    
                    # Check changes attribute by attribute
                    if updateObject.title == res[0][2]:
                        updateObject.title = None
                    if updateObject.meetingTime == res[0][3]:
                        updateObject.meetingTime = None
                    if updateObject.place == res[0][4]:
                        updateObject.place = None
                    if updateObject.description == res[0][5].decode("utf-8"):
                        updateObject.description = None

                    # If anything changed, update meeting in database, send email notifications
                    if updateObject.title != None or updateObject.meetingTime != None or updateObject.place != None or updateObject.description != None:
                        updatedMeetingCounter += 1
                        self.updateMeetingInDatabase(updateObject)
                        self.emailDispatchingModule.notifyMeetingChanged(oldMeeting, meeting, commission.displayName)

        logging.info('Changes checked. ' + str(newMeetingCounter) + ' new meeting(s) found, ' + str(updatedMeetingCounter) + ' updated meetings found')

    # Upload a new meeting to the database
    def uploadMeetingToDatabase(self, meeting, commission_id):
        db = mysql.connector.connect(
            host=self.config['DATABASE']['HOST'],
            user=self.config['DATABASE']['USERNAME'],
            passwd=self.config['DATABASE']['PASSWORD'],
            database=self.config['DATABASE']['DATABASE']
        )
        dbCursor = db.cursor()

        dbCursor.execute("INSERT INTO meetings (unid, title, meeting_time, place, description, commission_id) VALUES ('" + meeting.unid +  "','" + meeting.title + "', '" + meeting.meetingTime.strftime('%Y-%m-%d %H:%M:%S') + "', '" + meeting.place + "', '" + meeting.description + "', " + str(commission_id) + ")")
        db.commit()

        logging.info('Stored new meeting in database: ' + meeting.title +  ' at ' + meeting.meetingTime.strftime('%Y-%m-%d %H:%M:%S') + ' (' + meeting.unid + ')')

    # Update an existing meeting in the database
    def updateMeetingInDatabase(self, updateObject):
        db = mysql.connector.connect(
            host=self.config['DATABASE']['HOST'],
            user=self.config['DATABASE']['USERNAME'],
            passwd=self.config['DATABASE']['PASSWORD'],
            database=self.config['DATABASE']['DATABASE']
        )
        dbCursor = db.cursor()

        setlist = []

        # Write update command for every attribute that has changed
        if updateObject.title != None:
            setlist.append("title = '" + updateObject.title  + "'")
        if updateObject.meetingTime != None:
            setlist.append("meeting_time = '" + updateObject.meetingTime.strftime('%Y-%m-%d %H:%M:%S')  + "'")
        if updateObject.place != None:
            setlist.append("place = '" + updateObject.place  + "'")
        if updateObject.description != None:
            setlist.append("description = '" + updateObject.description  + "'")

        dbCursor.execute("UPDATE meetings SET " + ', '.join(setlist) + " WHERE unid = '" + updateObject.unid + "'")
        db.commit()

        logging.info('Updated a meeting in database with UNID = ' + updateObject.unid)

