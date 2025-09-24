// @ts-nocheck
//https://stackoverflow.com/a/14553231/3929620
//https://dev.to/gulcanc/rot13-decryption-algorithm-in-javascript-2c59
//http://jsfromhell.com/string/rot13
//https://stackoverflow.com/a/10198189/3929620
export const rot13 = (s) => {
  return s.replaceAll(/[A-Za-z]/g, function (c) {
    return String.fromCodePoint(
      c.codePointAt(0) + (c.toUpperCase() <= 'M' ? 13 : -13),
    );
  });
};
