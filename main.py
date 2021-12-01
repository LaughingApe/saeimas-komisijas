from Module.CommissionModule import CommissionModule
import configparser

config = configparser.ConfigParser()
config.read('config.ini')

commissionModule = CommissionModule(config)
commissionModule.scrapeCommissions()
commissionModule.updateCommissions()
commissionModule.scrapeAllMeetings()