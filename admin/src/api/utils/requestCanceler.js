/**
 * Request Canceler Utility
 * Provides simple AbortController lifecycle management to cancel stale
 * in-flight requests (such as rapid search input or page switching).
 */

export class RequestCanceler {
  constructor() {
    this.controller = null;
  }

  /**
   * Creates a new signal and cancels any active previous request.
   */
  getSignal() {
    if (this.controller) {
      this.controller.abort();
    }
    this.controller = new AbortController();
    return this.controller.signal;
  }

  /**
   * Aborts active request manually.
   */
  cancel() {
    if (this.controller) {
      this.controller.abort();
      this.controller = null;
    }
  }
}
