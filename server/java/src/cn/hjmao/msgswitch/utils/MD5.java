
package cn.hjmao.msgswitch.utils;

import java.security.MessageDigest;
import java.security.NoSuchAlgorithmException;

public class MD5 {
	public static String md5sum(String origStr) {
		
		String res = "";
        try {
            MessageDigest algorithm = MessageDigest.getInstance("MD5");
            algorithm.reset();
            algorithm.update(origStr.getBytes());
            byte[] md5 = algorithm.digest();
            String tmp = "";
            for (int i = 0; i < md5.length; i++) {
                tmp = (Integer.toHexString(0xFF & md5[i]));
                if (tmp.length() == 1) {
                    res += "0" + tmp;
                } else {
                    res += tmp;
                }
            }
        } catch (NoSuchAlgorithmException e) {
        	e.printStackTrace();
        }
        return res;
	}
}
