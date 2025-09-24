// @ts-nocheck
//https://www.typescriptlang.org/docs/handbook/namespaces-and-modules.html
//https://bobbyhadz.com/blog/typescript-import-class-from-another-file
//https://stackoverflow.com/a/40416826/3929620
//https://medium.com/@heshramsis/understanding-the-difference-between-export-default-and-export-with-named-exports-in-javascript-f0569c221a3
//https://stackoverflow.com/a/29722646/3929620
//https://dev.to/zirkelc/read-all-files-of-directory-and-subdirectories-with-recursive-generators-in-javascript-2pbd
//https://bobbyhadz.com/blog/module-not-found-cant-resolve-fs
//http://www.matthiassommer.it/programming/expose-global-variables-methods-modules-javascript/
//https://properprogramming.com/tools/jquery-to-javascript-converter/
//https://thejs.dev/jmitchell/its-time-to-let-go-of-lodash-nqc
//https://www.quora.com/Are-Lodash-and-jQuery-outdated/answer/Corey-Butler
//https://medium.com/voobans-tech-stories/10-lodash-functions-everyone-should-know-334b372aec5d
//https://www.reddit.com/r/javascript/comments/110fkf3/comment/j8a1fo3/
globalThis.App = {};

// helper
import {
  escapeHTMLAttribute,
  getNextSibling,
  getPreviousSibling,
  //getRandomString,
  //getScrollingElement,
  //isInViewport,
  //scrollCallback,
  //rot13,
  wait,
} from './helper';
globalThis.App.Helper = {
  escapeHTMLAttribute: escapeHTMLAttribute,
  getNextSibling: getNextSibling,
  getPreviousSibling: getPreviousSibling,
  //getRandomString: getRandomString,
  //getScrollingElement: getScrollingElement,
  //isInViewport: isInViewport,
  //scrollCallback: scrollCallback,
  //rot13: rot13,
  wait: wait,
};

// sys
import {
  //animate,
  browser,
  buttonScrollTop,
  fancybox,
  formSubmit,
  formValidation,
  lazyLoad,
  obfuscated,
  //popover,
  //recaptcha,
  scrollTo,
  //selectLocation,
  serviceWorker,
  //tinyMce,
  tooltip,
} from './sys';
globalThis.App.Sys = {
  //animate: animate,
  browser: browser,
  buttonScrollTop: buttonScrollTop,
  fancybox: fancybox,
  formSubmit: formSubmit,
  formValidation: formValidation,
  lazyLoad: lazyLoad,
  obfuscated: obfuscated,
  //popover: popover,
  //recaptcha: recaptcha,
  scrollTo: scrollTo,
  //selectLocation: selectLocation,
  serviceWorker: serviceWorker,
  //tinyMce: tinyMce,
  tooltip: tooltip,
};

// mod
import { Modal, Toast } from './module';
globalThis.App.Mod = {
  Modal: new Modal(),
  Toast: new Toast(),
};

(() => {
  serviceWorker();
  browser();
  lazyLoad();
  fancybox();

  globalThis.addEventListener('online', () => {
    if (jsObj.onlineMessage !== undefined) {
      App.Mod.Toast.hideAll('network');
      App.Mod.Toast.add(jsObj.onlineMessage, 'success', {
        stack: 'network',
      }).show();
    }
  });
  globalThis.addEventListener('offline', () => {
    if (jsObj.offlineMessage !== undefined) {
      App.Mod.Toast.hideAll('network');
      App.Mod.Toast.add(jsObj.offlineMessage, 'warning', {
        stack: 'network',
        autohide: false,
      }).show();
    }
  });

  //https://thisthat.dev/dom-content-loaded-vs-load/
  //https://web.dev/critical-rendering-path-measure-crp/
  globalThis.addEventListener('DOMContentLoaded', () => {
    obfuscated();
    //animate();
    //selectLocation();
    tooltip();
    //popover();
    buttonScrollTop();
    scrollTo();
    formValidation();
    formSubmit();
    //tinyMce();
  });
  /*document.onreadystatechange = (event) => {
        if (document.readyState === 'complete') {

        }
    }*/
  /*window.addEventListener('load', (event) => {

    })*/
})();
