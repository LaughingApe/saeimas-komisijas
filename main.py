from Module.CommissionModule import CommissionModule
import configparser

from Module.EmailDispatchingModule import EmailDispatchingModule

# Read the configuration file
config = configparser.ConfigParser()
config.read('config.ini')

# Create the modules
emailDispatchingModule = EmailDispatchingModule(config)
commissionModule = CommissionModule(config, emailDispatchingModule)

# Process commissions
commissionModule.scrapeCommissions()
commissionModule.updateCommissions()

# Process meetings
commissionModule.scrapeAllMeetings()
commissionModule.checkMeetingChanges()