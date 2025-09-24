// @ts-nocheck
//https://stackoverflow.com/a/74810616/3929620
//https://stackoverflow.com/a/32677672/3929620
//https://stackoverflow.com/a/69614783/3929620
export const animate = () => {
  const animateList = Array.prototype.slice.call(
    document.querySelectorAll('.__animate__animated'),
  );
  animateList.map(function (animateElement) {
    if (typeof Waypoint === 'undefined') {
      animateElement.className = animateElement.className.replaceAll(
        '__animate__',
        'animate__',
      );
    } else {
      new Waypoint({
        element: animateElement,
        offset: '95%', // bottom-in-view
        //https://stackoverflow.com/a/61875095/3929620
        // eslint-disable-next-line @typescript-eslint/no-unused-vars
        handler: function (direction) {
          //https://stackoverflow.com/a/56914528/3929620
          animateElement.className = animateElement.className.replaceAll(
            '__animate__',
            'animate__',
          );
          this.destroy();
        },
      });
    }
  });
};
