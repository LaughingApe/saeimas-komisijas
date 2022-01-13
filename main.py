from Module.CommissionModule import CommissionModule
import configparser
import logging
import os

from Module.EmailDispatchingModule import EmailDispatchingModule

# Read the configuration file
config = configparser.ConfigParser()
config.read(os.path.dirname(__file__) + '/config.ini')

# Set up logger
logging.basicConfig(handlers=[
        logging.FileHandler(config['APP']['LOG']),  # Log file
        logging.StreamHandler()                     # Command line
    ], 
    format='[%(asctime)s] %(levelname)s: %(message)s', 
    level=logging.INFO)

# Create the modules
emailDispatchingModule = EmailDispatchingModule(config)
commissionModule = CommissionModule(config, emailDispatchingModule)

# Process commissions
commissionModule.scrapeCommissions()
commissionModule.updateCommissions()

# Process meetings
commissionModule.scrapeAllMeetings()
commissionModule.checkMeetingChanges()