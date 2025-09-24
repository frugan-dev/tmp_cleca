// @ts-ignore
//https://stackoverflow.com/a/55576119/3929620
//https://stackoverflow.com/a/70040699/3929620
//https://stackoverflow.com/a/52097700/3929620
import Handlebars from 'handlebars/dist/handlebars';
import BootstrapToast from 'bootstrap/js/dist/toast';
import { escapeHTMLAttribute, wait } from '../helper';

const Default: {
  stack: string;
  title: string;
  subTitle: string;
  autohide: boolean;
  delay: number;
  animation: boolean;
  nativeAnimation: boolean;
  animateCssBaseClass: string;
  animateCssInClass: string;
  animateCssOutClass: string;
  position: 'afterbegin' | 'beforeend';
  headerAttr: any;
  bodyAttr: any;
  btnCloseAttr: any;
  attr: any;
  childAttr: any;
} = {
  stack: 'default',
  title: undefined,
  subTitle: undefined,
  autohide: true,
  delay: 8000,
  animation: true,
  nativeAnimation: false,
  animateCssBaseClass: 'animate__animated',
  animateCssInClass: 'animate__bounceInLeft',
  animateCssOutClass: 'animate__bounceOutLeft',
  position: 'afterbegin',
  headerAttr: {
    class: ['toast-header'],
  },
  bodyAttr: {
    class: ['toast-body'],
  },
  btnCloseAttr: {
    class: ['btn-close'],
    'data-bs-dismiss': 'toast',
  },
  attr: {
    class: ['toast'],
  },
  childAttr: {},
};

export class Toast {
  private message: string;
  private type: string;
  private config: any;

  private toastId: number;
  private toastArr: any = [];
  private toastEl: Element;

  private toastObj: BootstrapToast;

  private toastContainerEl: Element =
    document.querySelector('.toast-container');

  private templateToastHeaderEl: Element = document.querySelector(
    '.template-toast-header',
  );
  private templateToastBodyEl: Element = document.querySelector(
    '.template-toast-body',
  );
  private templateToastBtnCloseEl: Element = document.querySelector(
    '.template-toast-btn-close',
  );
  private templateToastEl: Element = document.querySelector('.template-toast');

  //https://github.com/parcel-bundler/parcel/issues/615
  private templateToastHeader: any;
  private templateToastBody: any;
  private templateToastBtnClose: any;
  private templateToast: any;

  constructor() {
    if (
      !this.toastContainerEl ||
      !this.templateToastHeaderEl ||
      !this.templateToastBodyEl ||
      !this.templateToastBtnCloseEl ||
      !this.templateToastEl
    ) {
      throw new Error('Missing html template');
    }
  }

  getId() {
    return this.toastId;
  }

  getArr() {
    return this.toastArr;
  }

  getObj() {
    return this.toastObj;
  }

  add(message: string, type?: string, config?: any) {
    //https://jerickson.net/reference-value-javascript-changing-copy-change-original/
    //https://www.reddit.com/r/javascript/comments/110fkf3/comment/j8a1fo3/
    //https://caniuse.com/mdn-api_structuredclone
    this.config = structuredClone({
      ...Default,
      ...(typeof config === 'object' ? config : {}),
    });

    this.message = message;
    this.type = type;

    if (this.type) {
      this.config.headerAttr['class'].push('text-bg-' + this.type);
      this.config.attr['class'].push('text-bg-' + this.type);
      this.config.btnCloseAttr['class'].push('btn-close-white');
    }

    if (!this.config.title) {
      this.config.childAttr = {
        ...this.config.childAttr,
        class: ['d-flex'],
      };

      this.config.btnCloseAttr['class'].push('mt-2', 'me-2', 'ms-auto');
    }

    this.config.attr['data-bs-autohide'] = this.config.autohide
      ? 'true'
      : 'false';

    if (this.config.delay) {
      this.config.attr['data-bs-delay'] = this.config.delay;
    }

    this.config.attr['data-bs-animation'] = this.config.nativeAnimation
      ? 'true'
      : 'false';

    if (this.config.animation) {
      this.config.attr['class'].push(
        this.config.animateCssBaseClass,
        this.config.animateCssInClass,
      );
      this.config.attr['data-bs-animation'] = 'false';
      this.config.attr['data-bs-autohide'] = 'false';

      this.config.btnCloseAttr['data-bs-dismiss'] = false;
    }

    this.templateToastHeader = Handlebars.compile(
      this.templateToastHeaderEl.innerHTML,
    );
    this.templateToastBody = Handlebars.compile(
      this.templateToastBodyEl.innerHTML,
    );
    this.templateToastBtnClose = Handlebars.compile(
      this.templateToastBtnCloseEl.innerHTML,
    );
    this.templateToast = Handlebars.compile(this.templateToastEl.innerHTML);

    const toastButtonClose = this.templateToastBtnClose({
      btnCloseAttr: escapeHTMLAttribute(this.config.btnCloseAttr),
    });

    let toastHeader: string | any = '';
    if (this.config.title) {
      toastHeader = this.templateToastHeader({
        title: this.config.title,
        subTitle: this.config.subTitle,
        btnClose: toastButtonClose,
        headerAttr: escapeHTMLAttribute(this.config.headerAttr),
      });
    }

    const toastBody = this.templateToastBody({
      body: this.message,
      bodyAttr: escapeHTMLAttribute(this.config.bodyAttr),
    });

    const toastContent = this.templateToast({
      content:
        toastHeader + toastBody + (this.config.title ? '' : toastButtonClose),
      attr: escapeHTMLAttribute(this.config.attr),
      childAttr: escapeHTMLAttribute(this.config.childAttr),
    });

    //https://stackoverflow.com/a/22260849/3929620
    this.toastContainerEl.insertAdjacentHTML(
      this.config.position,
      toastContent,
    );

    //https://stackoverflow.com/a/41448446/3929620
    this.toastEl =
      this.config.position === 'afterbegin'
        ? this.toastContainerEl.firstElementChild
        : this.toastContainerEl.lastElementChild;
    if (this.toastEl) {
      this.toastObj = new BootstrapToast(this.toastEl);

      const toastArrayLength: number = this.toastArr.push({
        toastEl: this.toastEl,
        toastObj: this.toastObj,
        message: this.message,
        type: this.type,
        config: this.config,
      });
      this.toastId = toastArrayLength - 1;

      this.toastEl.setAttribute('id', 'toast-' + this.toastId);
    }

    return this;
  }

  show(toastId?: number): void {
    const _toastId = toastId ?? this.toastId;

    if (this.toastArr[_toastId]) {
      this.toastArr[_toastId].toastObj.show();

      if (this.toastArr[_toastId].config.animation) {
        if (this.toastArr[_toastId].config.autohide) {
          wait.start(this.toastArr[_toastId].config.delay).then(() => {
            this.hide(_toastId);
          });
        }

        const buttonCloseElement: Element =
          this.toastArr[_toastId].toastEl.querySelector('.btn-close');
        if (buttonCloseElement) {
          buttonCloseElement.addEventListener('click', (): void => {
            this.hide(_toastId);
          });
        }
      }
    }
  }

  hide(toastId?: number): void {
    const _toastId = toastId ?? this.toastId;

    if (this.toastArr[_toastId] && this.toastArr[_toastId].toastObj.isShown()) {
      if (this.toastArr[_toastId].config.animation) {
        //https://github.com/lodash/lodash/issues/3897#issuecomment-411929721
        this.toastArr[_toastId].toastEl.classList.replace(
          this.toastArr[_toastId].config.animateCssInClass,
          this.toastArr[_toastId].config.animateCssOutClass,
        );

        //https://stackoverflow.com/a/41725782/3929620
        wait
          .start(
            Number.parseInt(
              getComputedStyle(document.body).getPropertyValue(
                '--animate-duration',
              ),
            ),
          )
          .then(() => {
            //https://github.com/lodash/lodash/issues/3897#issuecomment-411929721
            this.toastArr[_toastId].toastEl.classList.replace('show', 'hide');
            this.toastArr[_toastId].toastEl.classList.replace(
              this.toastArr[_toastId].config.animateCssOutClass,
              this.toastArr[_toastId].config.animateCssInClass,
            );
          });
      } else {
        this.toastArr[_toastId].toastObj.hide();
      }
    }
  }

  showAll(stack?: string): void {
    if (this.toastArr.length > 0) {
      for (const [index, element] of this.toastArr.entries()) {
        if (stack) {
          if (element.config.stack === stack) {
            this.show(index);
          }
        } else {
          this.show(index);
        }
      }
    }
  }

  hideAll(stack?: string): void {
    if (this.toastArr.length > 0) {
      for (const [index, element] of this.toastArr.entries()) {
        if (stack) {
          if (element.config.stack === stack) {
            this.hide(index);
          }
        } else {
          this.hide(index);
        }
      }
    }
  }
}
