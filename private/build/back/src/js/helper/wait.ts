// @ts-nocheck
//https://developer.mozilla.org/en-US/docs/Web/JavaScript/Guide/Using_promises#creating_a_promise_around_an_old_callback_api
//https://advancedweb.hu/how-to-add-timeout-to-a-promise-in-javascript/
//https://stackoverflow.com/a/34255423/3929620
//https://levelup.gitconnected.com/promise-with-timeout-in-javascript-e42911ba23e1
//https://www.educative.io/answers/what-is-the-javascript-alternative-to-the-sleep-function
//https://www.codingninjas.com/codestudio/library/microtask-and-macrotask-in-javascript
//https://stackoverflow.com/a/52408852/3929620
//https://stackoverflow.com/a/1909508/3929620
export const wait = {
  timeoutArr: [],

  getTimeoutId() {
    const timeoutId = this.timeoutArr.length;
    this.timeoutArr[timeoutId] = undefined;
    return timeoutId;
  },

  start(ms: number, timeoutId?: number) {
    const _timeoutId = timeoutId ?? this.getTimeoutId();

    return new Promise((resolve) => {
      this.timeoutArr[_timeoutId] = setTimeout(resolve, ms);
    }).finally(() => {
      this.timeoutArr[_timeoutId] = undefined;
    });
  },

  clear(timeoutId: number) {
    if (timeoutId in this.timeoutArr) {
      clearTimeout(this.timeoutArr[timeoutId]);
    }
  },
};
