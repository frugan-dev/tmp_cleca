// @ts-nocheck
export const lazyLoad = () => {
  window['lazyLoadInstance'] = new LazyLoad({
    //https://caniuse.com/loading-lazy-attr
    //https://web.dev/browser-level-image-lazy-loading/
    use_native: true,
  });
  window['lazyLoadBackgroundInstance'] = new LazyLoad({
    // DON'T PASS use_native: true HERE
  });
};
