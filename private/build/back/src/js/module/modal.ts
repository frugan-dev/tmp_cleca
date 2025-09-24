// @ts-ignore
//https://stackoverflow.com/a/55576119/3929620
//https://stackoverflow.com/a/70040699/3929620
//https://stackoverflow.com/a/52097700/3929620
import Handlebars from 'handlebars/dist/handlebars';
import BootstrapModal from 'bootstrap/js/dist/modal';
import { escapeHTMLAttribute } from '../helper';

const Default: {
  animation: boolean;
  staticBackdrop: boolean;
  scrollable: boolean;
  centered: boolean;
  position: 'afterbegin' | 'beforeend';
  headerAttr: any;
  bodyAttr: any;
  footerAttr: any;
  titleAttr: any;
  headerBtnCloseAttr: any;
  footerBtnCloseAttr: any;
  attr: any;
  dialogAttr: any;
  contentAttr: any;
} = {
  animation: true,
  staticBackdrop: false,
  scrollable: false,
  centered: true,
  position: 'afterbegin',
  headerAttr: {
    class: ['modal-header'],
  },
  bodyAttr: {
    class: ['modal-body'],
  },
  footerAttr: {
    class: ['modal-footer'],
  },
  titleAttr: {
    class: ['modal-title'],
  },
  headerBtnCloseAttr: {
    class: ['btn-close'],
    'data-bs-dismiss': 'modal',
  },
  footerBtnCloseAttr: {
    class: ['btn', 'btn-outline-secondary'],
    'data-bs-dismiss': 'modal',
  },
  attr: {
    class: ['modal'],
  },
  dialogAttr: {
    class: ['modal-dialog'],
  },
  contentAttr: {
    class: ['modal-content'],
  },
};

export class Modal {
  private body: string;
  private title: string;
  private footer: string;
  private size: string;
  private type: string;
  private config: any;

  private modalId: number;
  private modalArr: any = [];
  private modalEl: Element;

  private modalObj: BootstrapModal;

  private modalContainerEl: Element =
    document.querySelector('.modal-container');

  private templateModalHeaderEl: Element = document.querySelector(
    '.template-modal-header',
  );
  private templateModalBodyEl: Element = document.querySelector(
    '.template-modal-body',
  );
  private templateModalFooterEl: Element = document.querySelector(
    '.template-modal-footer',
  );
  private templateModalHeaderBtnCloseEl: Element = document.querySelector(
    '.template-modal-header-btn-close',
  );
  private templateModalFooterBtnCloseEl: Element = document.querySelector(
    '.template-modal-footer-btn-close',
  );
  private templateModalEl: Element = document.querySelector('.template-modal');

  //https://github.com/parcel-bundler/parcel/issues/615
  private templateModalHeader: any;
  private templateModalBody: any;
  private templateModalFooter: any;
  private templateModalHeaderBtnClose: any;
  private templateModalFooterBtnClose: any;
  private templateModal: any;

  constructor() {
    if (
      !this.modalContainerEl ||
      !this.templateModalHeaderEl ||
      !this.templateModalBodyEl ||
      !this.templateModalFooterEl ||
      !this.templateModalHeaderBtnCloseEl ||
      !this.templateModalFooterBtnCloseEl ||
      !this.templateModalEl
    ) {
      throw new Error('Missing html template');
    }
  }

  getId() {
    return this.modalId;
  }

  getArr() {
    return this.modalArr;
  }

  getObj() {
    return this.modalObj;
  }

  add(
    body: string,
    title?: string,
    footer?: string,
    size?: string,
    type?: string,
    config?: any,
  ) {
    //https://jerickson.net/reference-value-javascript-changing-copy-change-original/
    //https://www.reddit.com/r/javascript/comments/110fkf3/comment/j8a1fo3/
    //https://caniuse.com/mdn-api_structuredclone
    this.config = structuredClone({
      ...Default,
      ...(typeof config === 'object' ? config : {}),
    });

    this.body = body;
    this.title = title;
    this.footer = footer;
    this.size = size;
    this.type = type;

    if (this.size) {
      this.config.dialogAttr['class'].push('modal-' + this.size);
    }

    if (this.type) {
      this.config.headerAttr['class'].push('text-bg-' + this.type);
      this.config.headerBtnCloseAttr['class'].push('btn-close-white');
    }

    if (this.config.animation) {
      this.config.attr['class'].push('fade');
    }

    if (this.config.staticBackdrop) {
      this.config.attr['data-bs-backdrop'] = 'static';
    }

    if (this.config.scrollable) {
      this.config.dialogAttr['class'].push('modal-dialog-scrollable');
    }

    if (this.config.centered) {
      this.config.dialogAttr['class'].push('modal-dialog-centered');
    }

    this.templateModalHeader = Handlebars.compile(
      this.templateModalHeaderEl.innerHTML,
    );
    this.templateModalBody = Handlebars.compile(
      this.templateModalBodyEl.innerHTML,
    );
    this.templateModalFooter = Handlebars.compile(
      this.templateModalFooterEl.innerHTML,
    );
    this.templateModalHeaderBtnClose = Handlebars.compile(
      this.templateModalHeaderBtnCloseEl.innerHTML,
    );
    this.templateModalFooterBtnClose = Handlebars.compile(
      this.templateModalFooterBtnCloseEl.innerHTML,
    );
    this.templateModal = Handlebars.compile(this.templateModalEl.innerHTML);

    const modalHeaderButtonClose = this.templateModalHeaderBtnClose({
      headerBtnCloseAttr: escapeHTMLAttribute(this.config.headerBtnCloseAttr),
    });

    const modalHeader = this.templateModalHeader({
      title: this.title,
      headerBtnClose: modalHeaderButtonClose,
      headerAttr: escapeHTMLAttribute(this.config.headerAttr),
      titleAttr: escapeHTMLAttribute(this.config.titleAttr),
    });

    const modalBody = this.templateModalBody({
      body: this.body,
      bodyAttr: escapeHTMLAttribute(this.config.bodyAttr),
    });

    let modalFooter: string | any = '';
    if (this.footer) {
      const modalFooterButtonClose = this.templateModalFooterBtnClose({
        footerBtnCloseAttr: escapeHTMLAttribute(this.config.footerBtnCloseAttr),
      });

      modalFooter = this.templateModalFooter({
        footer: this.footer,
        footerBtnClose: modalFooterButtonClose,
        footerAttr: escapeHTMLAttribute(this.config.footerAttr),
      });
    }

    const modalContent = this.templateModal({
      content: modalHeader + modalBody + modalFooter,
      attr: escapeHTMLAttribute(this.config.attr),
      dialogAttr: escapeHTMLAttribute(this.config.dialogAttr),
      contentAttr: escapeHTMLAttribute(this.config.contentAttr),
    });

    //https://stackoverflow.com/a/22260849/3929620
    this.modalContainerEl.insertAdjacentHTML(
      this.config.position,
      modalContent,
    );

    //https://stackoverflow.com/a/41448446/3929620
    this.modalEl =
      this.config.position === 'afterbegin'
        ? this.modalContainerEl.firstElementChild
        : this.modalContainerEl.lastElementChild;
    if (this.modalEl) {
      this.modalObj = new BootstrapModal(this.modalEl);

      const modalArrayLength: number = this.modalArr.push({
        modalEl: this.modalEl,
        modalObj: this.modalObj,
        config: this.config,
      });
      this.modalId = modalArrayLength - 1;

      this.modalEl.setAttribute('id', 'modal-' + this.modalId);
    }

    return this;
  }

  show(modalId?: number): void {
    const _modalId = modalId ?? this.modalId;

    if (this.modalArr[_modalId]) {
      this.modalArr[_modalId].modalObj.show();
    }
  }

  hide(modalId?: number): void {
    const _modalId = modalId ?? this.modalId;

    if (this.modalArr[_modalId]) {
      this.modalArr[_modalId].modalObj.hide();
    }
  }

  showAll(): void {
    if (this.modalArr.length > 0) {
      for (const [index] of this.modalArr.entries()) {
        this.show(index);
      }
    }
  }

  hideAll(): void {
    if (this.modalArr.length > 0) {
      for (const [index] of this.modalArr.entries()) {
        this.hide(index);
      }
    }
  }
}
