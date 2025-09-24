// @ts-nocheck

// Note: reCAPTCHA tokens expire after two minutes. If you're protecting an action with reCAPTCHA,
// make sure to call execute when the usertakes the action rather than on page load.
// The hidden input withname="g-recaptcha-response" is only used as anindicator
// that this form requires reCAPTCHAverification. The actual token value will be
// added directly to the FormData and not set on this field to avoid issues with form.reset().

let recaptchaLoaded = false;
let recaptchaPromise;

// Dynamic script loader for reCAPTCHA
const loadRecaptchaScript = () => {
  if (recaptchaLoaded || recaptchaPromise) {
    return recaptchaPromise;
  }

  recaptchaPromise = new Promise((resolve, reject) => {
    if (typeof grecaptcha !== 'undefined') {
      recaptchaLoaded = true;
      resolve(grecaptcha);
      return;
    }

    const script = document.createElement('script');
    script.src = `//www.google.com/recaptcha/api.js?hl=${jsObj.lang}&render=${jsObj.recaptchaPublicKey}`;
    script.async = true;
    script.defer = true;

    script.addEventListener('load', () => {
      recaptchaLoaded = true;
      resolve(grecaptcha);
    });

    script.addEventListener('error', () => {
      recaptchaPromise = undefined;
      reject(new Error('Failed to load reCAPTCHA script'));
    });

    document.head.append(script);
  });

  return recaptchaPromise;
};

// Load reCAPTCHA and execute token generation
export const recaptcha = async () => {
  if (!jsObj.recaptchaPublicKey) {
    console.warn('reCAPTCHA public key not configured');
    return;
  }

  try {
    const grecaptcha = await loadRecaptchaScript();

    return new Promise((resolve, reject) => {
      grecaptcha.ready(() => {
        grecaptcha
          .execute(jsObj.recaptchaPublicKey, { action: jsObj.recaptchaAction })
          .then((token) => {
            resolve(token);
          })
          .catch((error) => {
            console.error('reCAPTCHA execution failed:', error);
            reject(error);
          });
      });
    });
  } catch (error) {
    console.error('reCAPTCHA loading failed:', error);
    return;
  }
};
