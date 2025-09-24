// @ts-nocheck
//https://www.htmlgoodies.com/javascript/javascript-object-string/
//https://www.freecodecamp.org/news/how-to-iterate-over-objects-in-javascript/
//https://github.com/auraphp/Aura.Html/blob/2.x/src/Escaper/AttrEscaper.php#L93
export const escapeHTMLAttribute = (object: any) => {
  let esc = '';

  if (object) {
    esc += ' ';
  }

  for (let [key, value] of Object.entries(object)) {
    if (!value) {
      continue;
    }

    key = key.trim();

    if (Array.isArray(value)) {
      value = value.join(' ');
    }

    //https://benborgers.com/posts/escape-quotes-html-attributes
    //https://stackoverflow.com/a/7754054/3929620
    esc +=
      value === true
        ? key
        : key + '="' + value.toString().replaceAll('"', '&quot;') + '"';

    esc += ' ';
  }

  return esc.trimEnd();
};
