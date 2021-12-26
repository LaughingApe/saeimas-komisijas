from Module.CommissionModule import CommissionModule
import configparser

from Module.EmailDispatchingModule import EmailDispatchingModule

config = configparser.ConfigParser()
config.read('config.ini')

emailDispatchingModule = EmailDispatchingModule(config)
commissionModule = CommissionModule(config, emailDispatchingModule)

commissionModule.scrapeCommissions()
commissionModule.updateCommissions()
commissionModule.scrapeAllMeetings()

commissionModule.checkMeetingChanges()