// @ts-nocheck
export const browser = () => {
  browserUpdate({
    required: {
      c: -3, // Chrome
      f: -3, // Firefox
      s: -2, // Safari
      e: -3, // Edge - Falls back to value specified in "i" when omitted
      //i:11, // Internet Explorer - Falls back to value specified in "e" when omitted
      ios: -3, // iOS browser - Falls back to "s" when omitted
      samsung: -3, // Samsung Internet (Android)
      o: -3, // Opera
      o_a: -3, // Opera (Android)
      y: -3, // Yandex Browser
      v: -3, // Vivaldi
      uc: -3, // UC Browser
      a: -3, // Android
    },
    //https://browser-update.org/browsers-marked-as-insecure.html
    //insecure: true,
    //unsupported: true
    //reminder: 0,
    //style:'corner', // top, bottom corner
    //test: true,
  });
};
