from google.appengine.api import mail

class Mail:
  def __init__(self):
    pass

  def send(self, snum, vendor, rnum, content):
    mailAddr = str(rnum) + "@139.com"
    if (vendor == "CM"):
        mailAddr = str(rnum) + "@139.com"
    elif (vendor == "CT"):
        mailAddr = str(rnum) + "@189.cn"
    elif (vendor =="CU"):
        mailAddr = str(rnum) + "@wo.com.cn"
    else:
        return

    subj = "[msgs]" + str(snum) + "[/msgs]"
    mail.send_mail(sender="msgswitch@gmail.com",
                   to=mailAddr,
                   subject=subj,
                   body=str(content))

if __name__ == "__main__":
  mail = Mail()
  print "OK"
