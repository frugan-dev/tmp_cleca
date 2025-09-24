// @ts-nocheck
//https://stackoverflow.com/a/55686711/3929620
export const scrollCallback = (offset, callback) => {
  const fixedOffset = offset.toFixed(0);
  const onScroll = () => {
    if (window.scrollY.toFixed(0) === fixedOffset) {
      window.removeEventListener('scroll', onScroll);
      callback();
    }
  };

  window.addEventListener('scroll', onScroll);
  onScroll();
  // https://stackoverflow.com/a/70696298/3929620
  window.scroll({
    top: offset,
    behavior: 'smooth',
  });
};
