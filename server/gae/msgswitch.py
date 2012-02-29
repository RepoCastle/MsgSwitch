from google.appengine.ext import webapp
from google.appengine.ext.webapp.util import run_wsgi_app

from mail import Mail

class MsgSwitch(webapp.RequestHandler):
  def get(self):
    ctl = self.request.get('CTL')
    sender = self.request.get('SENDER')
    receiver = self.request.get('RECEIVER')
    content = self.request.get('CONTENT')

    if ctl == '102':
      sender = Mail()
      sender.send(sender, receiver, content)
      self.response.out.write("Done sending mail! " + str(sender) + " " + str(receiver) + " " + str(content))
    elif ctl == '103':
      self.response.out.write(str(sender))
      self.response.out.write(str(receiver))
      self.response.out.write(str(content))

application = webapp.WSGIApplication([('/', MsgSwitch)], debug=True)

def main():
  run_wsgi_app(application)

if __name__ == "__main__":
  main()
