// @ts-nocheck

export const selectLocation = () => {
  const selectLocationList = Array.prototype.slice.call(
    document.querySelectorAll('.select-location'),
  );
  selectLocationList.map((selectLocationElement) => {
    //https://www.freecodecamp.org/news/the-difference-between-arrow-functions-and-normal-functions/
    // Arrow functions do not create their own `this` binding
    selectLocationElement.addEventListener('change', function () {
      const value = this.value;
      const option = this.options[this.selectedIndex];

      if (value) {
        const target = option.dataset.target;
        const type = option.dataset.type;

        if (target !== undefined) {
          window.open(value, target);
        } else if (type === undefined) {
          globalThis.location = value;
        } else {
          switch (type) {
            default: {
              globalThis.location = value;
            }
          }
        }
      }
    });
  });
};
