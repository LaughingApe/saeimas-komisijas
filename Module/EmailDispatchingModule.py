import json
import re
from bs4 import BeautifulSoup
import requests
import time
import datetime
import mysql.connector
import copy
import smtplib
from email.mime.multipart import MIMEMultipart
from email.mime.text import MIMEText

from Entity.Commission import Commission
from Entity.Meeting import Meeting

class EmailDispatchingModule:

    def __init__(self, config):
        self.config = config

    def notifyMeetingAdded(self, meeting, commissionDisplayName): 
        db = mysql.connector.connect(
            host=self.config['DATABASE']['HOST'],
            user=self.config['DATABASE']['USERNAME'],
            passwd=self.config['DATABASE']['PASSWORD'],
            database=self.config['DATABASE']['DATABASE']
        )
        dbCursor = db.cursor()
        dbCursor.execute('SELECT * FROM emails JOIN subscriptions ON emails.id=subscriptions.email_id JOIN commissions ON subscriptions.commission_id=commissions.id WHERE commissions.display_name="' + commissionDisplayName + '" AND emails.email_verification_time IS NOT NULL')
        emails = dbCursor.fetchall()
        emailAddresses = []
        for e in emails:
            emailAddresses.append(e[1])
        
        try:
            server = smtplib.SMTP_SSL(self.config['MAIL']['MAIL_HOST'], self.config['MAIL']['MAIL_PORT'])
            server.ehlo()
            server.login(self.config['MAIL']['MAIL_USERNAME'], self.config['MAIL']['MAIL_PASSWORD'])

        except:
            print('Neizdevās savienoties ar e-pasta lietotni.')

        msg = MIMEMultipart('alternative')
        msg['Subject'] = 'Jauna sēde: ' + commissionDisplayName
        msg['From'] = self.config['MAIL']['MAIL_FROM_ADDRESS']

        body = '''
Sistēmā <i>Titania</i> pievienota jauna komisijas sēde. Lai skatītu sistēmā <i>Titania</i>, spied <a href="''' + self.config['SCRAPER']['MEETING_URL_BASE'].replace('<UNID>', meeting.unid) + '''" target="_blank">šeit</a>!<br/><br/>

<b>Nosaukums</b>: ''' + meeting.title + '''<br/>
<b>Vieta</b>: ''' + meeting.place + '''<br/>
<b>Laiks</b>: ''' + meeting.meetingTime.strftime('%d.%m.%Y %H:%M') + '''<br/><br/>
<b>Darba kārtība</b>:<br/>''' + meeting.description

        for e in emailAddresses:

            msg['To'] = e
            msg.attach(MIMEText(body, 'html'))

            server.sendmail(msg['From'], e, msg.as_string())
        
        server.close()


        

    def notifyMeetingChanged(self, oldMeeting, newMeeting, commissionDisplayName): 
        db = mysql.connector.connect(
            host=self.config['DATABASE']['HOST'],
            user=self.config['DATABASE']['USERNAME'],
            passwd=self.config['DATABASE']['PASSWORD'],
            database=self.config['DATABASE']['DATABASE']
        )
        dbCursor = db.cursor()
        dbCursor.execute('SELECT * FROM emails JOIN subscriptions ON emails.id=subscriptions.email_id JOIN commissions ON subscriptions.commission_id=commissions.id WHERE commissions.display_name="' + commissionDisplayName + '" AND emails.email_verification_time IS NOT NULL')
        emails = dbCursor.fetchall()
        emailAddresses = []
        for e in emails:
            emailAddresses.append(e[1])
        
        try:
            server = smtplib.SMTP_SSL(self.config['MAIL']['MAIL_HOST'], self.config['MAIL']['MAIL_PORT'])
            server.ehlo()
            server.login(self.config['MAIL']['MAIL_USERNAME'], self.config['MAIL']['MAIL_PASSWORD'])

        except:
            print('Neizdevās savienoties ar e-pasta lietotni.')

        msg = MIMEMultipart('alternative')
        msg['Subject'] = 'Izmaiņas komisijas sēdē: ' + commissionDisplayName
        msg['From'] = self.config['MAIL']['MAIL_FROM_ADDRESS']

        body = '''
        Sistēmā <i>Titania</i> labota komsiijas sēde. Lai skatītu sistēmā <i>Titania</i>, spied <a href="''' + self.config['SCRAPER']['MEETING_URL_BASE'].replace('<UNID>',newMeeting.unid) + '''" target="_blank">šeit</a>!<br/><br/>

        Notikušās izmaiņas:<br/><br/>'''

        if oldMeeting.title != newMeeting.title:
            body = body + '''
            <b>Vecais nosaukums</b>: ''' + oldMeeting.title + '''<br/>
            <b>Jaunais nosaukums</b>: ''' + newMeeting.title + '''<br/><br/>
            '''
        
        if oldMeeting.place != newMeeting.place:
            body = body + '''
            <b>Vecā sēdes norises vieta</b>: ''' + oldMeeting.place + '''<br/>
            <b>Jaunā sēdes norises vieta</b>: ''' + newMeeting.place + '''<br/><br/>
            '''
        
        if oldMeeting.meetingTime != newMeeting.meetingTime:
            body = body + '''
            <b>Vecais sēdes laiks</b>: ''' + oldMeeting.meetingTime.strftime('%d.%m.%Y %H:%M') + '''<br/>
            <b>Jaunais sēdes laiks</b>: ''' + newMeeting.meetingTime.strftime('%d.%m.%Y %H:%M') + '''<br/><br/>
            '''
        if oldMeeting.description.decode('utf-8') != newMeeting.description:
            body = body + '''
            <b>Vecā sēdes darba kartība</b>: ''' + oldMeeting.description.decode('utf-8')  + '''<br/>
            <b>Jaunā sēdes darba kārtība</b>: ''' + newMeeting.description  + '''<br/><br/>
            '''
        for e in emailAddresses:

            msg['To'] = e
            msg.attach(MIMEText(body, 'html'))

            server.sendmail(msg['From'], e, msg.as_string())
        
        server.close()