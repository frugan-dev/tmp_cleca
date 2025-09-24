// @ts-nocheck
//https://stackoverflow.com/a/67227887/3929620
import 'ts-replace-all';

//FIXED
//https://github.com/KreutzerCode/configurable-date-input-polyfill/issues/10
//https://gist.github.com/RobertAKARobin/850a408e04d5414e67d308a2b5847378
// Not supported: Safari < 5 (Mobile), Safari < 14.1 (Desktop)
// Partial support: Safari >= 5 (Mobile), Safari >= 14.1 (Desktop)
import 'configurable-date-input-polyfill';

//https://stackoverflow.com/a/71411937/3929620
import smoothscroll from 'smoothscroll-polyfill';
smoothscroll.polyfill();

import axios from 'axios';
globalThis.axios = axios;

globalThis.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

//import UAParser from 'ua-parser-js';
//globalThis.UAParser = UAParser;
//globalThis.parser = new UAParser();

//import { compare } from 'compare-versions';
//globalThis.compare = compare;

//https://github.com/browser-update/browser-update/wiki/Details-on-configuration
import browserUpdate from 'browser-update';
globalThis.browserUpdate = browserUpdate;

//https://github.com/alexpalombaro/modernizr-webpack-plugin/issues/42#issuecomment-816955365
//https://stackoverflow.com/a/53439363/3929620
//https://github.com/Modernizr/Modernizr/issues/2590#issuecomment-676207797
//https://github.com/Modernizr/Modernizr/blob/master/lib/config-all.json
import './../../.modernizrrc';

import Cookies from 'js-cookie';
globalThis.Cookies = Cookies;

import {
  Alert,
  //    Button,
  //    Carousel,
  Collapse,
  //    Dropdown,
  Modal,
  //    Offcanvas,
  //    Popover,
  //    ScrollSpy,
  //    Tab,
  Toast,
  Tooltip,
} from 'bootstrap';
globalThis.Alert = Alert;
//globalThis.Button = Button;
//globalThis.Carousel = Carousel;
globalThis.Collapse = Collapse;
//globalThis.Dropdown = Dropdown;
globalThis.Modal = Modal;
//globalThis.Offcanvas = Offcanvas;
//globalThis.Popover = Popover;
//globalThis.ScrollSpy = ScrollSpy;
//globalThis.Tab = Tab;
globalThis.Toast = Toast;
globalThis.Tooltip = Tooltip;

import Handlebars from 'handlebars/dist/handlebars';
globalThis.Handlebars = Handlebars;

//TODO
//https://github.com/hyperform/hyperform
//https://github.com/hapijs/joi
//https://github.com/jquense/yup
//https://github.com/ajv-validator/ajv
//https://github.com/ianstormtaylor/superstruct
//https://github.com/horprogs/Just-validate
//https://github.com/jaywcjlove/validator.js
//https://github.com/upjs/facile-validator
//https://github.com/cferdinandi/bouncer/
//https://github.com/bootstrap-validate/bootstrap-validate
//import Pristine from 'pristinejs/dist/pristine';
//globalThis.Pristine = Pristine;

import { Uppy, debugLogger } from '@uppy/core';
globalThis.Uppy = Uppy;
globalThis.debugLogger = debugLogger;
globalThis.Uppy.locales = [];

import ProgressBar from '@uppy/progress-bar';
globalThis.ProgressBar = ProgressBar;

import XHR from '@uppy/xhr-upload';
globalThis.XHR = XHR;

//https://stackoverflow.com/a/53580347/3929620
//https://fontawesome.com/how-to-use/on-the-web/advanced/css-pseudo-elements
//https://stackoverflow.com/a/47723704/3929620
/*window.FontAwesomeConfig = {
    searchPseudoElements: true
}*/
import '@fortawesome/fontawesome-free/js/all';

//FIXME - https://github.com/fancyapps/ui/issues/397
// Not supported: Safari <= 14 (Desktop)
import { Fancybox } from '@fancyapps/ui';
globalThis.Fancybox = Fancybox;

import LazyLoad from 'vanilla-lazyload';
globalThis.LazyLoad = LazyLoad;

import 'vanilla-cookieconsent';

import * as Sentry from '@sentry/browser';
globalThis.Sentry = Sentry;
