// @ts-nocheck
import { wait } from '../helper';

//https://dev.to/ljcdev/scroll-to-top-button-in-vanilla-js-beginners-2nc
//https://codepen.io/completewebco/pen/Powwxbd
export const scrollTo = () => {
  const scrollToList = Array.prototype.slice.call(
    document.querySelectorAll('.scroll-to'),
  );
  scrollToList.map((scrollToElement) => {
    //https://www.freecodecamp.org/news/the-difference-between-arrow-functions-and-normal-functions/
    // Arrow functions do not create their own `this` binding
    scrollToElement.addEventListener('click', function (event) {
      const url = this.getAttribute('href');

      //https://stackoverflow.com/a/64334419/3929620
      //https://stackoverflow.com/a/74768629/3929620
      const urlObject = new URL(url, jsObj.baseUrl);
      if (!urlObject.hash) {
        return;
      }

      const element = document.querySelector(urlObject.hash);
      if (!element) {
        return;
      }

      event.preventDefault();

      //element.parentNode.style.position = 'inherit';

      Fancybox.close();

      //FIXED - data-bs-dismiss="offcanvas" doesn't work
      [...document.querySelectorAll('.offcanvas')].map((offcanvasElement) => {
        const offcanvas = Offcanvas.getInstance(offcanvasElement);
        if (offcanvas) {
          offcanvas.hide();
        }
      });

      document.body.classList.add('scrolling');

      if (Modernizr.intersectionobserver) {
        //https://github.com/w3c/csswg-drafts/issues/3744#issuecomment-1426956633
        //https://github.com/w3c/csswg-drafts/issues/3744#issuecomment-685683932
        const callback = (entries, observer) => {
          for (const entry of entries) {
            if (entry.target === element && entry.intersectionRatio > 0) {
              // Stop listening for intersection changes
              observer.disconnect();

              wait.start(500).then(() => {
                document.body.classList.remove('scrolling');
              });
            }
          }
        };

        const observer = new IntersectionObserver(callback, {
          //root: null,
          //rootMargin: '0px',
          //threshold: 0.9,
        });

        observer.observe(element);
      }

      //https://stackoverflow.com/a/67923821/3929620
      element.scrollIntoView({
        block: 'start',
        behavior: 'smooth',
      });

      //FIXED - Safari <= 12.x
      wait.start(1000).then(() => {
        document.body.classList.remove('scrolling');
      });
    });
  });
};
