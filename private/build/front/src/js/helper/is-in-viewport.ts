// @ts-nocheck
//https://vanillajstoolkit.com/helpers/isinviewport/
//https://www.javascripttutorial.net/dom/css/check-if-an-element-is-visible-in-the-viewport/
//https://stackoverflow.com/a/72717388/3929620
//https://caniuse.com/mdn-api_element_checkvisibility
export const isInViewport = (element) => {
  const rect = element.getBoundingClientRect();
  return (
    rect.top >= 0 &&
    rect.left >= 0 &&
    rect.bottom <=
      (window.innerHeight || document.documentElement.clientHeight) &&
    rect.right <= (window.innerWidth || document.documentElement.clientWidth)
  );
};
