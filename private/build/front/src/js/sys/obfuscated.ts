// @ts-nocheck
import { rot13 } from '../helper';

//https://stackoverflow.com/a/64673305/3929620
export const obfuscated = () => {
  const elementArray = [...document.querySelectorAll('.obfuscated')];
  if (elementArray) {
    for (const element of elementArray) {
      element.innerHTML = rot13(element.innerHTML);
      element.style.display = '';
    }
  }
};
