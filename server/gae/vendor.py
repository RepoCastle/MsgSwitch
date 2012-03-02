
class Vendor:

    def __init__(self):
        self.CMPREFIX = ['134', '135', '136', '137', '138', '139', '150', '151', '152', '157', '158', '159', '188'];
        self.CUPREFIX = ['130', '131', '132', '155', '156', '185', '186'];
        self.CTPREFIX = ['133', '153', '180', '189'];

    def parse(self, receiver):
        prefix = receiver[0:3]

        print prefix

        if (prefix in self.CMPREFIX):
            self.vendor = "CM"
        elif (prefix in self.CTPREFIX):
            self.vendor = "CT"
        elif (prefix in self.CUPREFIX):
            self.vendor = "CU"
        else:
            self.vendor = "VENDOR_NOT_SUPPORT"

        return self.vendor
