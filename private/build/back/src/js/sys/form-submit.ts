// @ts-nocheck
import axios from 'axios';
import { isInViewport, wait } from '../helper';
import { recaptcha } from './recaptcha';

const dispatchForm = (form, eventType, detail) => {
  form.dispatchEvent(new CustomEvent(eventType, { detail }));
};

const dispatchDocument = (eventType, detail) => {
  document.dispatchEvent(new CustomEvent(eventType, { detail }));
};

// Helper function to get form submit buttons
const getFormButtons = (form) => {
  let btns = [];

  if (form.hasAttribute('id')) {
    const formId = form.getAttribute('id');
    //https://dmitripavlutin.com/javascript-merge-arrays/
    btns = [
      ...document.querySelectorAll('button[form="' + formId + '"]'),
    ].filter((element) => {
      return isInViewport(element);
    });
  }

  if (btns.length === 0) {
    btns = [...form.querySelectorAll('button[type="submit"]')];
  }

  return btns;
};

// Helper function to disable buttons and set loading text
const disableFormButtons = (buttons) => {
  for (const button of buttons) {
    //https://stackoverflow.com/a/54533740
    button.disabled = true;

    if (Object.hasOwn(button.dataset, 'loadingText')) {
      // Store original text if not already stored
      if (!Object.hasOwn(button.dataset, 'originalText')) {
        button.dataset.originalText = button.innerHTML;
      }
      button.innerHTML = button.dataset.loadingText;
      button.classList.add('btn-loading');
    }
  }
};

// Helper function to enable buttons and restore original text
const enableFormButtons = (buttons) => {
  for (const button of buttons) {
    button.disabled = false;

    if (Object.hasOwn(button.dataset, 'originalText')) {
      button.innerHTML = button.dataset.originalText;
      button.classList.remove('btn-loading');
      delete button.dataset.originalText;
    }
  }
};

export const formSubmit = () => {
  //https://stackoverflow.com/a/41694352
  const formsSync = [...document.querySelectorAll('form[data-sync]')];
  for (const form of formsSync) {
    form.addEventListener('submit', async function (event) {
      const recaptchaField = form.querySelector(
        'input[name="g-recaptcha-response"]',
      );

      // If reCAPTCHA is needed and token is not already set
      if (recaptchaField && !recaptchaField.value) {
        event.preventDefault(); // Block form submission

        // Check form validity FIRST before any processing
        if (!form.checkValidity()) {
          // Show validation errors immediately
          form.reportValidity();
          return;
        }

        // Form is valid, proceed with reCAPTCHA and loading state
        dispatchDocument('submit-form-sync', [form]);
        const btns = getFormButtons(form);
        disableFormButtons(btns);

        try {
          // Handle reCAPTCHA for sync forms
          const token = await recaptcha();
          if (token) {
            recaptchaField.value = token;
          }

          // Use requestSubmit() to maintain native form validation
          form.requestSubmit();
        } catch (error) {
          console.error('reCAPTCHA error:', error);
          enableFormButtons(btns);
        }
      } else {
        // No reCAPTCHA needed or already has token, proceed normally
        if (!event.defaultPrevented) {
          dispatchDocument('submit-form-sync', [form]);
          const btns = getFormButtons(form);
          disableFormButtons(btns);
        }
      }
    });
  }

  const formsAsynch = [...document.querySelectorAll('form[data-async]')];
  for (const form of formsAsynch) {
    form.addEventListener('submit', async function (event) {
      if (!event.defaultPrevented) {
        event.preventDefault();

        dispatchDocument('submit-form-async', [form]);

        const btns = getFormButtons(form);
        disableFormButtons(btns);

        const dismiss =
          '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="' +
          jsObj.textClose +
          '"></button>';

        let response;
        const responses = [...form.querySelectorAll('.response')];
        for (const element of responses) {
          response = element;
          response.classList.add('d-none');
          response.innerHTML = '';
        }

        try {
          const formData = new FormData(form);

          // Handle reCAPTCHA for async forms
          const recaptchaField = form.querySelector(
            'input[name="g-recaptcha-response"]',
          );
          if (recaptchaField) {
            const token = await recaptcha();
            if (token) {
              formData.append('g-recaptcha-response', token);
            }
          }

          let requestData = formData;
          const headers = {
            // header already set globally in vendor.ts
            // 'X-Requested-With': 'XMLHttpRequest',
          };

          // Wordpress requires application/x-www-form-urlencoded or multipart/form-data
          if (jsObj.xhrContentType === 'application/json') {
            requestData = Object.fromEntries(formData.entries());
            headers['Content-Type'] = jsObj.xhrContentType;
          }

          // Use axios for async requests
          const response = await axios.post(jsObj.apiEndpoint, requestData, {
            headers,
          });

          const responseJson = response.data;

          const targetSelector =
            responseJson.target === undefined
              ? form.dataset.target
              : responseJson.target; // used with .modal-body
          // eslint-disable-next-line unicorn/prefer-query-selector
          const target = document.getElementById(targetSelector);

          const status =
            responseJson.status === undefined ? 'danger' : responseJson.status;
          let message =
            responseJson.message === undefined ? '' : responseJson.message;

          if (message) {
            //FIXED - https://stackoverflow.com/a/16171353
            message = message.toString().replaceAll('\\', '');

            const message_html =
              '<div class="alert alert-' +
              status +
              ' fade show alert-dismissible mb-0" role="alert">' +
              dismiss +
              message +
              '</div>';
            const response_html =
              responseJson.html === undefined
                ? '<div class="alert alert-' +
                  status +
                  ' fade show mb-0" role="alert">' +
                  message +
                  '</div>'
                : responseJson.html;

            if (target) {
              target.innerHTML = response_html;
              target.classList.remove('d-none');

              //https://stackoverflow.com/a/18794913/3929620
              //https://stackoverflow.com/a/48750639/3929620
              const rect = target.getBoundingClientRect();
              const win = target.ownerDocument.defaultView;

              scroll({
                top: rect.top + win.pageYOffset - 120,
                behavior: 'smooth',
              });
            } else {
              response.innerHTML = message_html;
              response.classList.remove('d-none');
            }
          }

          if (responseJson.redirect !== undefined) {
            dispatchForm(form, 'async-form-success', {
              response: responseJson,
            });

            if (responseJson.timeout === undefined) {
              parent.location.href = responseJson.redirect;
            } else {
              wait.start(Number.parseInt(responseJson.timeout)).then(() => {
                parent.location.href = responseJson.redirect;
              });
            }
          } else if (responseJson.reload === undefined) {
            dispatchForm(form, 'async-form-success', {
              response: responseJson,
            });

            // Only restore button state if not redirecting/reloading
            // The finally block will handle button state restoration
          } else {
            dispatchForm(form, 'async-form-success', {
              response: responseJson,
            });

            if (responseJson.timeout === undefined) {
              //https://stackoverflow.com/a/55127750
              globalThis.location.reload();
            } else {
              wait.start(Number.parseInt(responseJson.timeout)).then(() => {
                //https://stackoverflow.com/a/55127750
                globalThis.location.reload();
              });
            }
          }
        } catch (error) {
          console.error('Form submission error:', error);

          let errorMessage = 'An error occurred while submitting the form.';

          // error.response exists when the server responded with an error status code (4xx, 5xx)
          // error.request exists when the request was made but no response was received
          if (error.response) {
            errorMessage = error.response.data.message || errorMessage;
          } else if (error.request) {
            errorMessage = 'An error occurred while submitting the form.';
          }

          const message_html =
            '<div class="alert alert-danger fade show alert-dismissible mb-0" role="alert">' +
            dismiss +
            errorMessage +
            '</div>';

          response.innerHTML = message_html;
          response.classList.remove('d-none');

          dispatchForm(form, 'async-form-error', { error: error });
        } finally {
          enableFormButtons(btns);
        }
      }
    });
  }
};
