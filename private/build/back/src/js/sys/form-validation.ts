// @ts-nocheck
import Tab from 'bootstrap/js/dist/tab';
import { getNextSibling } from '../helper';

//https://stackoverflow.com/a/50319997
//https://jsfiddle.net/trixta/T29Kx/
//https://www.html5rocks.com/en/tutorials/forms/constraintvalidation/
//https://stackoverflow.com/a/59732941
export const formValidation = () => {
  // Fetch all the forms we want to apply custom Bootstrap validation styles to
  const forms = document.querySelectorAll('.needs-validation');

  // Loop over them and prevent submission
  Array.prototype.filter.call(forms, function (form) {
    form.addEventListener(
      'submit',
      function (event) {
        /*let tot_files = 0
            let $files = $(form).find('input[type="file"]')

            if(typeof $files !== 'undefined') {

                $.each($files, function(index, element) {

                    if (typeof $(element)[0].files[0] !== 'undefined') {

                        tot_files++
                    }
                })

                if(tot_files > 0) {

                    let max_filesize = (jsObj.uploadMaxFilesize / tot_files)

                    $.each($files, function (index, element) {

                        if (typeof $(element)[0].files[0] !== 'undefined') {

                            let error = false

                            if ($(element)[0].files[0].size > max_filesize) {

                                element.setCustomValidity((tot_files > 1 ? jsObj.textErrorFilesizes : jsObj.textErrorFilesize))

                                error = true
                            }

                            //https://developer.mozilla.org/en-US/docs/Web/HTML/Element/input/file
                            if ($(element).attr('accept')) {

                                let allowed_mime_types = $(element).attr('accept').split(',')

                                if ($.inArray($(element)[0].files[0].type, allowed_mime_types) === -1) {

                                    element.setCustomValidity(jsObj.textErrorMimetype)

                                    error = true
                                }
                            }

                            if(!error) {
                                element.setCustomValidity('')
                            }
                        }
                    })
                }
            }*/

        if (form.checkValidity() === false) {
          event.preventDefault();
          event.stopPropagation();

          const errorElements = [
            ...form.querySelectorAll(
              '.form-control:invalid, .form-check-input:invalid, .custom-control-input:invalid, .custom-file-input:invalid, .form-select:invalid',
            ),
          ];
          for (const element of errorElements) {
            let sibling;
            if (
              element.parentNode.classList.contains('invalid-feedback-sibling')
            ) {
              sibling = getNextSibling(element.parentNode, '.invalid-feedback');

              if (sibling) {
                element.parentNode.classList.add('is-invalid');
              }
            } else {
              sibling = getNextSibling(element, '.invalid-feedback');
            }
            if (sibling) {
              sibling.innerHTML = element.validationMessage;
            }
          }

          const tabContentList = Array.prototype.slice.call(
            document.querySelectorAll('.tab-content'),
          );
          tabContentList.map(function (tabContentElement) {
            const tabPaneList = Array.prototype.slice.call(
              tabContentElement.querySelectorAll('.tab-pane'),
            );
            for (const tabPaneElement of tabPaneList) {
              const errorElements = [
                ...tabPaneElement.querySelectorAll(':invalid'),
              ];
              if (errorElements.length > 0) {
                const triggerElement = document.querySelector(
                  '.nav-tabs #' +
                    tabPaneElement.getAttribute('aria-labelledby'),
                );
                if (triggerElement) {
                  Tab.getOrCreateInstance(triggerElement).show();
                  break;
                }
              }
            }
          });
        }

        form.classList.add('was-validated');

        const event_ = new CustomEvent('form-validation', {
          detail: [form],
        });
        document.dispatchEvent(event_);
      },
      false,
    );

    // before submit
    // Use event delegation for form controls
    form.addEventListener('keyup', function (event) {
      const element = event.target;
      if (
        element.matches(
          '.form-control, .custom-control-input, .custom-file-input',
        ) &&
        element.willValidate === true
      ) {
        if (element.checkValidity() === true) {
          element.classList.remove('is-invalid');
          element.classList.add('is-valid');

          if (
            element.parentNode.classList.contains('invalid-feedback-sibling')
          ) {
            const sibling = getNextSibling(
              element.parentNode,
              '.invalid-feedback',
            );

            if (sibling) {
              element.parentNode.classList.remove('is-invalid');
              sibling.innerHTML = '';
            }
          }
        } else {
          element.classList.remove('is-valid');
          element.classList.add('is-invalid');

          let sibling;
          if (
            element.parentNode.classList.contains('invalid-feedback-sibling')
          ) {
            sibling = getNextSibling(element.parentNode, '.invalid-feedback');

            if (sibling) {
              element.parentNode.classList.add('is-invalid');
            }
          } else {
            sibling = getNextSibling(element, '.invalid-feedback');
          }
          if (sibling) {
            sibling.innerHTML = element.validationMessage;
          }
        }

        const event_ = new CustomEvent('form-element-validation', {
          detail: [form, element],
        });
        document.dispatchEvent(event_);
      }
    });

    // Use event delegation for form selects
    form.addEventListener('change', function (event) {
      const element = event.target;
      if (element.matches('.form-select') && element.willValidate === true) {
        if (element.checkValidity() === true) {
          element.classList.remove('is-invalid');
          element.classList.add('is-valid');

          if (
            element.parentNode.classList.contains('invalid-feedback-sibling')
          ) {
            const sibling = getNextSibling(
              element.parentNode,
              '.invalid-feedback',
            );

            if (sibling) {
              element.parentNode.classList.remove('is-invalid');
            }
          }
        } else {
          element.classList.remove('is-valid');
          element.classList.add('is-invalid');

          let sibling;
          if (
            element.parentNode.classList.contains('invalid-feedback-sibling')
          ) {
            sibling = getNextSibling(element.parentNode, '.invalid-feedback');

            if (sibling) {
              element.parentNode.classList.add('is-invalid');
            }
          } else {
            sibling = getNextSibling(element, '.invalid-feedback');
          }
          if (sibling) {
            sibling.innerHTML = element.validationMessage;
          }
        }

        const event_ = new CustomEvent('form-element-validation', {
          detail: [form, element],
        });
        document.dispatchEvent(event_);
      }
    });

    // Use event delegation for radio buttons
    form.addEventListener('change', function (event) {
      const element = event.target;
      if (
        element.matches('.form-check-input[type="radio"]') &&
        element.willValidate === true
      ) {
        if (element.checkValidity() === true) {
          element.classList.remove('is-invalid');
          element.classList.add('is-valid');

          if (
            element.parentNode.classList.contains('invalid-feedback-sibling')
          ) {
            const sibling = getNextSibling(
              element.parentNode,
              '.invalid-feedback',
            );

            if (sibling) {
              element.parentNode.classList.remove('is-invalid');
            }
          }
        } else {
          element.classList.remove('is-valid');
          element.classList.add('is-invalid');

          let sibling;
          if (
            element.parentNode.classList.contains('invalid-feedback-sibling')
          ) {
            sibling = getNextSibling(element.parentNode, '.invalid-feedback');

            if (sibling) {
              element.parentNode.classList.add('is-invalid');
            }
          } else {
            sibling = getNextSibling(element, '.invalid-feedback');
          }
          if (sibling) {
            sibling.innerHTML = element.validationMessage;
          }
        }

        const event_ = new CustomEvent('form-element-validation', {
          detail: [form, element],
        });
        document.dispatchEvent(event_);
      }
    });

    //https://stackoverflow.com/q/6218494
    //https://bobbyhadz.com/blog/select-at-least-one-checkbox-javascript
    //https://vyspiansky.github.io/2019/07/13/javascript-at-least-one-checkbox-must-be-selected/
    // Use event delegation for checkboxes
    form.addEventListener('change', function (event) {
      const element = event.target;
      if (
        element.matches(
          '.form-check-input[type="checkbox"][required], .form-check-input[type="checkbox"][data-required]',
        ) &&
        element.willValidate === true
      ) {
        const name = element.getAttribute('name');

        if (
          form.querySelectorAll(
            '.form-check-input[type="checkbox"][name="' + name + '"]:checked',
          ).length > 0
        ) {
          for (const element of form.querySelectorAll(
            '.form-check-input[type="checkbox"][name="' + name + '"]',
          )) {
            if (element.checked) {
              element.setAttribute('required', '');
            } else {
              element.removeAttribute('required');
            }

            element.classList.remove('is-invalid');
            element.classList.add('is-valid');

            if (
              element.parentNode.classList.contains('invalid-feedback-sibling')
            ) {
              const sibling = getNextSibling(
                element.parentNode,
                '.invalid-feedback',
              );

              if (sibling) {
                element.parentNode.classList.remove('is-invalid');
              }
            }
          }
        } else {
          for (const element of form.querySelectorAll(
            '.form-check-input[type="checkbox"][name="' + name + '"]',
          )) {
            element.setAttribute('required', '');

            element.classList.remove('is-valid');
            element.classList.add('is-invalid');

            let sibling;
            if (
              element.parentNode.classList.contains('invalid-feedback-sibling')
            ) {
              sibling = getNextSibling(element.parentNode, '.invalid-feedback');

              if (sibling) {
                element.parentNode.classList.add('is-invalid');
              }
            } else {
              sibling = getNextSibling(element, '.invalid-feedback');
            }
            if (sibling) {
              sibling.innerHTML = element.validationMessage;
            }
          }
        }

        const event_ = new CustomEvent('form-element-validation', {
          detail: [form, element],
        });
        document.dispatchEvent(event_);
      }
    });

    /*for (const element of form.querySelectorAll(
      '.form-check-input[type="checkbox"]:not([required])',
    )) {
      if (element.willValidate === true) {
        const name = element.getAttribute('name');

        element.addEventListener('change', function () {
          if (element.checkValidity() === true) {
            for (const element of form.querySelectorAll(
              '.form-check-input[type="checkbox"][name="' + name + '"]',
            )) {
              element.classList.remove('is-invalid');
              element.classList.add('is-valid');

              if (
                element.parentNode.classList.contains(
                  'invalid-feedback-sibling',
                )
              ) {
                const sibling = getNextSibling(
                  element.parentNode,
                  '.invalid-feedback',
                );

                if (sibling) {
                  element.parentNode.classList.remove('is-invalid');
                }
              }
            }
          } else {
            for (const element of form.querySelectorAll(
              '.form-check-input[type="checkbox"][name="' + name + '"]',
            )) {
              element.classList.remove('is-valid');
              element.classList.add('is-invalid');

              let sibling;
              if (
                element.parentNode.classList.contains(
                  'invalid-feedback-sibling',
                )
              ) {
                sibling = getNextSibling(
                  element.parentNode,
                  '.invalid-feedback',
                );

                if (sibling) {
                  element.parentNode.classList.add('is-invalid');
                }
              } else {
                sibling = getNextSibling(element, '.invalid-feedback');
              }
              if (sibling) {
                sibling.innerHTML = element.validationMessage;
              }
            }
          }

          const event_ = new CustomEvent('form-element-validation', {
            detail: [form, element],
          });
          document.dispatchEvent(event_);
        });
      }
    }*/

    //https://stackoverflow.com/a/41486636/3929620
    //https://stackoverflow.com/a/12267350/3929620
    // Use event delegation for readonly checkboxes
    form.addEventListener('click', function (event) {
      const element = event.target;
      if (element.matches('.form-check-input[type="checkbox"][readonly]')) {
        const event_ = new CustomEvent('form-element-validation', {
          detail: [form, element],
        });
        document.dispatchEvent(event_);

        event.preventDefault();
        return false;
      }
    });

    form.addEventListener('keydown', function (event) {
      const element = event.target;
      if (element.matches('.form-check-input[type="checkbox"][readonly]')) {
        const event_ = new CustomEvent('form-element-validation', {
          detail: [form, element],
        });
        document.dispatchEvent(event_);

        if (event.key !== undefined && event.key !== 9) {
          event.preventDefault();
          return false;
        }
      }
    });

    /*var elements = $(form).find('input[type="file"]')
        $.each(elements, function(index, element) {
            if (element.willValidate === true) {
                //https://stackoverflow.com/a/5670938/3929620
                $(element).change(() => {

                    if (typeof $(element)[0].files[0] !== 'undefined') {

                        let error = false

                        if ($(element)[0].files[0].size > jsObj.uploadMaxFilesize) {

                            element.setCustomValidity(jsObj.textErrorFilesize)

                            $(element).removeClass('is-valid').addClass('is-invalid')
                            $(element).siblings('.invalid-feedback').html( $(element)[0].validationMessage )

                            error = true
                        }

                        //https://developer.mozilla.org/en-US/docs/Web/HTML/Element/input/file
                        if ($(element).attr('accept')) {

                            let allowed_mime_types = $(element).attr('accept').split(',')

                            if ($.inArray($(element)[0].files[0].type, allowed_mime_types) === -1) {

                                element.setCustomValidity(jsObj.textErrorMimetype)

                                $(element).removeClass('is-valid').addClass('is-invalid')
                                $(element).siblings('.invalid-feedback').html( $(element)[0].validationMessage )

                                error = true
                            }
                        }

                        if(!error) {
                            element.setCustomValidity('')

                            $(element).removeClass('is-invalid').addClass('is-valid')
                        }

                        //https://stackoverflow.com/a/23419286/3929620
                        $( document ).trigger('form-element-validation', [ form, element ] )
                    }
                })
            }
        })*/
  });
};
