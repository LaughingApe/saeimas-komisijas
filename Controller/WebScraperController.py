import json
import re
from bs4 import BeautifulSoup
import requests

class WebScraperController: 
    def __init__(self):
        self.url = 'https://titania.saeima.lv/livs/saeimasnotikumi.nsf/webComisCat?OpenView&restrictToCategory=13|%C4%80rlietu%20komisija&count=1000'
    
    def main(self):
        result = requests.get(self.url)
        doc = BeautifulSoup(result.text, 'html.parser')
        doc_text = doc.find(id='viewHolderText').text.strip()
        content = doc_text.splitlines()

        for i in range(len(content)):
            content[i] = content[i][9:-2]
            it = content[i].find("time") + 6
            content[i] = list(content[i])
            if content[i][it]!='"':
                while content[i][it] != ':':
                    it+=1
                content[i][it] = '.'
            content[i] = "".join(content[i])
            content[i] = re.sub("(\w+):", r'"\1":',  content[i])
            content[i] = json.loads(content[i])

            print(content[i]['unid'])

