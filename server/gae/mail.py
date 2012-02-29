from google.appengine.api import mail

class Mail:
  def __init__(self):
    pass

  def send(self, snum, rnum, content):
    mailAddr = str(rnum) + "@139.com"
    subj = "Mail From "# + snum
    mail.send_mail(sender="msgswitch@gmail.com",
                   to=mailAddr,
                   subject=subj,
                   body=str(content))

if __name__ == "__main__":
  mail = Mail()
  print "OK"
