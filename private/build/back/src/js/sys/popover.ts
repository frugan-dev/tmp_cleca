// @ts-nocheck
import { getNextSibling } from '../helper';

//https://stackoverflow.com/a/55814396
//https://stackoverflow.com/a/21638591
//https://stackoverflow.com/a/15990198
export const popover = () => {
  const popoverList = Array.prototype.slice.call(
    document.querySelectorAll('[data-bs-toggle="popover"]'),
  );
  popoverList.map(function (popoverElement) {
    let sanitize = true;

    //https://www.freecodecamp.org/news/javascript-string-to-boolean/
    //https://www.samanthaming.com/tidbits/19-2-ways-to-convert-to-boolean/
    if (popoverElement.dataset.bsSanitize === 'false') {
      sanitize = false;
    }

    const popover = new Popover(popoverElement, {
      sanitize: sanitize,
      content: (element) => {
        if (Object.hasOwn(element.dataset, 'bsContent')) {
          return element.dataset.bsContent;
        }

        //https://stackoverflow.com/a/8318442/3929620
        const content = getNextSibling(element, '.popover-content');
        if (content) {
          return content.innerHTML;
        }
      },
    });

    popoverElement.addEventListener('show.bs.popover', () => {
      popoverList
        .filter((element) => {
          return element !== popoverElement;
        })
        .map(function (popoverElement) {
          const popover = Popover.getInstance(popoverElement);
          if (popover) {
            popover.hide();
          }
        });
    });

    return popover;
  });
};
