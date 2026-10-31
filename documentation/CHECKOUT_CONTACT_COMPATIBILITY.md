# Consent-based cart contact capture for supported checkout templates

The standard and fast checkouts load their contact forms asynchronously. A generic, opt-in contact bridge also handles supported AJAX checkout forms and the existing native consent checkbox; it does not add duplicate checkboxes.

With abandoned-cart analytics and guest capture explicitly enabled, the shop offers an unchecked-by-default recovery consent option. An optional email for reminder purposes does not become mandatory to place a regular order. Names, email and telephone are saved only after opt-in and a valid email. If the buyer has not reached checkout or has not consented, existing anonymous cart rows contain no contact details that could be reconstructed.

The JavaScript bridge loads only on eligible checkout routes. It observes AJAX form rendering, finds configured contact selectors or known standard field names, and sends contact data to a nonce-checked, same-origin endpoint after consent. It also binds existing native consent checkboxes, so the user can opt in before navigating to the next checkout step. Revoking consent removes the saved record. For other checkout routes the administrator can specify a custom checkout URL and contact selectors; this is not a promise of universal third-party template compatibility.

Contacts are stored in a persistent sidecar table scoped to the current store and session. No changes are made to customer account or order creation logic. Guest and registered consent retention follows the configured cart history (1–30 days, default 7). Recovery links expire seven days after issuance and work once. Reminder delivery is independent, disabled by default, and requires explicit consent, an email and the configured delivery delay.

Always back up the complete site and database. Test normal checkout, quick checkout, registered customer flow, alternate templates, permission revocation, mail sending and actual order completion on a staging shop before production deployment.
