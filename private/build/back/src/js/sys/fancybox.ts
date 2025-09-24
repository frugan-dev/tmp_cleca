// @ts-nocheck
export const fancybox = () => {
  //https://github.com/fancyapps/fancybox/issues/457#issuecomment-24537966
  //https://github.com/fancyapps/ui/pull/188
  //https://github.com/fancyapps/ui/issues/170
  // Unlike previous versions, you have to call "Fancybox.bind()" method to enable Fancybox
  Fancybox.bind('[data-fancybox]', {
    //https://fancyapps.com/fancybox/layout-shift/
    hideScrollbar: false,
    autoFocus: false,
    //Thumbs: {
    //    type: 'classic',
    //},
    //https://github.com/fancyapps/ui/issues/425
    //showClass: 'f-slideIn',
    //hideClass: 'f-slideOut',
    //wheel: 'slide',
  });

  //https://stackoverflow.com/a/20925268/3929620
  Fancybox.bind('[data-type="ajax"]', {
    //https://fancyapps.com/fancybox/layout-shift/
    hideScrollbar: true,
    autoFocus: false,
    on: {
      //https://stackoverflow.com/a/61875095/3929620
      // eslint-disable-next-line @typescript-eslint/no-unused-vars
      done: (fancybox, slide) => {
        lazyLoadInstance.update();
        //lazyLoadBackgroundInstance.update()
        //App.Sys.animate();
        App.Sys.obfuscated();
        App.Sys.recaptcha();
        App.Sys.formValidation();
        App.Sys.formSubmit();
      },
    },
  });
};
