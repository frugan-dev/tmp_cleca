// @ts-nocheck
//https://stackoverflow.com/a/58906226/3929620
//https://stackoverflow.com/a/56588804/3929620
//https://github.com/nuxt/nuxt/issues/2512
export const getScrollingElement = () => {
  if (document.scrollingElement) {
    return document.scrollingElement;
  }

  const documentElement = document.documentElement;
  const documentElementRect = documentElement.getBoundingClientRect();

  return {
    scrollHeight: Math.ceil(documentElementRect.height),
    scrollTop: Math.abs(documentElementRect.top),
  };
};
