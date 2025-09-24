// @ts-nocheck
import { getScrollingElement } from '../helper/get-scrolling-element';

//https://dev.to/ljcdev/scroll-to-top-button-in-vanilla-js-beginners-2nc
//https://codepen.io/completewebco/pen/Powwxbd
export const buttonScrollTop = () => {
  const buttonScrollTopElement = document.querySelector('.btn-scroll-top');
  if (buttonScrollTopElement) {
    const GOLDEN_RATIO = 0.5;

    window.addEventListener('scroll', () => {
      const scrollableHeight =
        document.documentElement.scrollHeight -
        document.documentElement.clientHeight;
      const scrollTop =
        document.documentElement.scrollTop > 0
          ? document.documentElement.scrollTop
          : getScrollingElement().scrollTop;

      buttonScrollTopElement.style.display =
        scrollTop / scrollableHeight > GOLDEN_RATIO ? 'block' : 'none';
    });
  }
};
