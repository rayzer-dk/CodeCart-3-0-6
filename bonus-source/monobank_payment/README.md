Monobank Payment Modern v1.1.0

Bonus demonstration extension for CodeCart PRO 3.0.6.

Installation:
1. Make a full backup of files and database.
2. In Extensions -> Installer upload Monobank_Payment_Modern_v1.1.0.ocmod.zip.
3. Refresh Modern Extensions registry if needed.
4. Open Extensions -> Extensions -> Payments.
5. Install Monobank Payment Modern. It is OFF after installation.
6. Open settings, enter X-Token, choose order statuses, test connection, then enable and save.

Architecture:
- OpenCart-compatible bridge controllers/models/templates make the method visible in Extensions -> Payments.
- Business logic is under system/extension/monobank_payment and is loaded by Modern Extension Registry.
- Webhook x-sign is verified with Monobank ECDSA public key.
- Webhook events are ordered by modifiedDate and order status updates use CodeCart idempotency.
- UAH only in this demonstration module.
- No token is written to module logs.

Author: CodeCart PRO
https://codecartpro.com


Security/commerce hardening in v1.1.0: one invoice per order, advisory lock against double Confirm, currency_value-aware UAH amount, and secrets are never rendered back into admin HTML.
