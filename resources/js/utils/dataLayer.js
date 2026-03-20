/**
 * Push a custom event to GTM dataLayer.
 *
 * @param {string} event  - GTM event name, e.g. 'contact_form_submitted'
 * @param {object} params - additional key-value pairs merged into the dataLayer push
 */
export function pushEvent(event, params = {}) {
    window.dataLayer = window.dataLayer || [];
    window.dataLayer.push({ event, ...params });
}
