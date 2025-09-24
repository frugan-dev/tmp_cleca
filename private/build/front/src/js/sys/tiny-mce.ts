// @ts-nocheck

let tinymceLoaded = false;
let tinymcePromise;

// Dynamic script loader for TinyMCE
const loadTinyMCEScript = () => {
  if (tinymceLoaded || tinymcePromise) {
    return tinymcePromise;
  }

  tinymcePromise = new Promise((resolve, reject) => {
    if (typeof tinymce !== 'undefined') {
      tinymceLoaded = true;
      resolve(tinymce);
      return;
    }

    if (!jsObj.tinymceApiKey) {
      reject(new Error('TinyMCE API key not configured'));
      return;
    }

    const script = document.createElement('script');
    script.src = `//cdn.tiny.cloud/1/${jsObj.tinymceApiKey}/tinymce/5/tinymce.min.js`;
    script.referrerPolicy = 'origin';
    script.defer = true;

    script.addEventListener('load', () => {
      tinymceLoaded = true;
      resolve(tinymce);
    });

    script.addEventListener('error', () => {
      tinymcePromise = undefined;
      reject(new Error('Failed to load TinyMCE script'));
    });

    document.head.append(script);
  });

  return tinymcePromise;
};

// Create overlay element
const createOverlay = (textarea) => {
  const wrapper = document.createElement('div');
  wrapper.className =
    'tinymce-overlay-wrapper position-relative invalid-feedback-sibling';

  const overlay = document.createElement('div');
  overlay.className =
    'tinymce-overlay position-absolute top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center bg-light bg-opacity-75 border rounded cursor-pointer';
  overlay.dataset.textareaId =
    textarea.id || Math.random().toString(36).slice(2, 11);

  const content = document.createElement('div');
  content.className = 'text-center text-muted';
  content.innerHTML = `
    <i class="fas fa-edit fa-2x mb-2"></i>
    <div class="fw-semibold">${jsObj.tinymceClickToLoadMessage || 'Click to load rich text editor'}</div>
    <small class="d-block">${jsObj.tinymceWillInitializeMessage || 'TinyMCE will initialize when you click here'}</small>
  `;

  overlay.append(content);

  // Insert wrapper before textarea
  textarea.parentNode.insertBefore(wrapper, textarea);
  // Move textarea inside wrapper
  wrapper.append(textarea);
  // Add overlay to wrapper
  wrapper.append(overlay);

  return { wrapper, overlay };
};

// Remove overlay
const removeOverlay = (textarea) => {
  const wrapper = textarea.closest('.tinymce-overlay-wrapper');
  if (wrapper) {
    const overlay = wrapper.querySelector('.tinymce-overlay');
    if (overlay) {
      overlay.remove();
    }
  }
  // Show textarea content again
  textarea.classList.remove('tinymce-textarea-hidden');
};

// Initialize TinyMCE on specific textarea
const initializeTinyMCE = async (textarea) => {
  if (!textarea) {
    return;
  }

  // Check if TinyMCE is already properly initialized and active
  if (
    textarea.dataset.tinymceInitialized &&
    typeof tinymce !== 'undefined' &&
    textarea.id &&
    tinymce.get(textarea.id) &&
    !tinymce.get(textarea.id).removed
  ) {
    return;
  }

  try {
    // Show loading state
    const overlay = textarea
      .closest('.tinymce-overlay-wrapper')
      ?.querySelector('.tinymce-overlay');
    if (overlay) {
      const content = overlay.querySelector('div');
      content.innerHTML = `
        <div class="spinner-border mb-2" role="status">
          <span class="visually-hidden">${jsObj.loadingMessage || 'Loading&hellip;'}</span>
        </div>
        <small class="d-block">${jsObj.waitMessage || 'Please wait&hellip;'}</small>
      `;
    }

    const tinymce = await loadTinyMCEScript();

    // Check if TinyMCE instance with this ID already exists and remove it
    if (textarea.id && tinymce.get(textarea.id)) {
      tinymce.get(textarea.id).remove();
    }

    // Mark as initialized to prevent double initialization
    textarea.dataset.tinymceInitialized = 'true';

    let config = {
      target: textarea,
      language: jsObj.lang,
      document_base_url: jsObj.baseUrl + '/',
      relative_urls: false,
      remove_script_host: false,
      extended_valid_elements: 'iframe[src|class|allowfullscreen]',
      setup: function (editor) {
        editor.on('change', function () {
          editor.save();
          // Trigger keyup to notify form validation
          textarea.dispatchEvent(new Event('keyup', { bubbles: true }));
        });
        editor.on('init', function () {
          // Remove overlay after TinyMCE is fully initialized
          removeOverlay(textarea);
        });
        // On any keystroke after a delay
        editor.on('keyup', function () {
          // Trigger keyup to notify form validation
          textarea.dispatchEvent(new Event('keyup', { bubbles: true }));
        });
      },
      content_css:
        typeof getPreferredTheme === 'function' ? getPreferredTheme() : '',
    };

    // Configure based on textarea class
    if (textarea.classList.contains('richedit-empty')) {
      config = {
        ...config,
        plugins: [],
        toolbar: false,
        height: 37,
        valid_elements: 'sup',
        content_style: 'body, p { margin: 0; padding: 0; }',
        menubar: false,
        statusbar: false,
        branding: false,
      };
    } else if (textarea.classList.contains('richedit-simple')) {
      config = {
        ...config,
        plugins: [
          'advlist autolink lists link charmap print preview anchor',
          'searchreplace visualblocks code fullscreen',
          'insertdatetime paste code',
        ],
        toolbar:
          'insertfile undo redo | styleselect | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link',
        height: 500,
      };
    } else if (textarea.classList.contains('richedit-advanced')) {
      config = {
        ...config,
        plugins: [
          'advlist autolink lists link image charmap print preview anchor',
          'searchreplace visualblocks code fullscreen',
          'insertdatetime media table paste code imagetools',
        ],
        toolbar:
          'insertfile undo redo | styleselect | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image',
        height: 500,
      };
    }

    await tinymce.init(config);
  } catch (error) {
    console.error('TinyMCE initialization failed:', error);
    // Remove the flag so user can try again
    delete textarea.dataset.tinymceInitialized;
    // Remove overlay and show error
    removeOverlay(textarea);
  }
};

// Handle click events using event delegation
const handleDelegatedClick = (event) => {
  // Handle overlay click
  if (event.target.closest('.tinymce-overlay')) {
    const overlay = event.target.closest('.tinymce-overlay');
    const wrapper = overlay.closest('.tinymce-overlay-wrapper');
    const textarea = wrapper?.querySelector('textarea');
    if (textarea) {
      initializeTinyMCE(textarea);
    }
    return;
  }

  // Handle textarea click
  const textarea = event.target;
  if (
    textarea.tagName === 'TEXTAREA' &&
    (textarea.classList.contains('richedit-empty') ||
      textarea.classList.contains('richedit-simple') ||
      textarea.classList.contains('richedit-advanced'))
  ) {
    // Check if it already has TinyMCE initialized
    if (textarea.dataset.tinymceInitialized === 'true') {
      return; // Already initialized, let TinyMCE handle the click
    }

    // Check if it already has an overlay
    if (textarea.closest('.tinymce-overlay-wrapper')) {
      return; // Overlay will handle the click
    }

    // Initialize directly if no overlay exists
    initializeTinyMCE(textarea);
  }
};

// Set up textarea for lazy loading
const setupTextarea = (textarea) => {
  // Skip if already initialized or already has wrapper
  if (
    textarea.dataset.tinymceInitialized === 'true' ||
    textarea.closest('.tinymce-overlay-wrapper')
  ) {
    return;
  }

  // Create overlay and hide textarea content
  createOverlay(textarea);
  textarea.classList.add('tinymce-textarea-hidden');
};

// Main function to set up TinyMCE lazy loading
export const tinyMce = () => {
  // Set up event delegation on document body (only once)
  if (!document.body.dataset.tinymceEventDelegationSetup) {
    document.body.addEventListener('click', handleDelegatedClick);
    document.body.dataset.tinymceEventDelegationSetup = 'true';
  }

  // Find all TinyMCE textareas and set them up
  const textareas = document.querySelectorAll(
    'textarea.richedit-empty, textarea.richedit-simple, textarea.richedit-advanced',
  );

  for (const textarea of textareas) {
    setupTextarea(textarea);
  }
};

// Function to manually initialize TinyMCE on specific elements (for programmatic use)
export const initTinyMCEOn = (selector) => {
  const textareas = document.querySelectorAll(selector);
  for (const textarea of textareas) {
    initializeTinyMCE(textarea);
  }
};
