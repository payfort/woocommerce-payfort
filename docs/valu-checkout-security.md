# valU checkout security and operations

valU remains supported on classic WooCommerce checkout. The Blocks integration is not offered because it has no mobile/OTP/tenure wizard.

## Authorization and retries

The checkout form contains a random, 15-minute capability bound to the WooCommerce session, signed-in user and cart hash. The storefront nonce remains a CSRF check; it is not authorization. Customer verification and OTP generation are single-use. A changed cart or account requires a fresh verification. Reload checkout to restart after a failed customer verification or expired capability. AJAX form refreshes keep the active capability when its cart context has not changed.

OTP verification accepts at most five submissions per capability. A definitive rejected response permits another code, but the same code cannot be replayed. A successful or uncertain verification consumes that step. The normal checkout can submit the OTP with the purchase directly, as before. The unused `valu_set_tenure` and `valu_proceed_tenure` AJAX routes have been removed; tenure is validated against APS's server-side offer during checkout.

Purchase submission is claimed atomically before contacting APS, once per capability and once per order. The order claim is retained even after a timeout or decline: do not clear it or retry the payment until the store has reconciled that attempt with APS. This intentionally favors preventing duplicate submissions over automatic retry. Reconciliation or manual database changes require an authorized operator; this plugin does not expose an unlock endpoint.

## Throttling and infrastructure

The WordPress options table's unique `option_name` index provides atomic claims across PHP workers and nodes. The implementation uses direct database inserts, not a cache-dependent read/increment/write counter. A database failure denies the operation.

Customer verification is limited to five attempts per WooCommerce customer and per Egyptian mobile-number suffix, and twenty per client IP, in a fixed 15-minute window. Phone limits group local, `20` and `0020` spellings by their final ten digits. Fixed windows can allow two windows' quota at the boundary; these application limits are not a replacement for edge abuse controls.

Behind a reverse proxy, configure the web server's trusted-proxy real-IP support so `REMOTE_ADDR` is the validated client address. Otherwise all shoppers behind that proxy share its quota. Alternatively, a deployment-specific `aps_valu_client_ip` filter may return an address verified by trusted infrastructure. Never return an unvalidated `X-Forwarded-For` value. The plugin rejects invalid addresses and does not trust forwarding headers by default.

Multi-node deployments need the same WordPress database and a shared PHP session store, in addition to WooCommerce's session storage. Capabilities must not be served from shared page caches. Expired temporary claim rows are cleaned in bounded batches during checkout rendering. Per-order purchase claims are intentionally durable and should be included in the site's data-retention and reconciliation procedures.

## Release validation

Test guest checkout, account changes and cart recalculation on the merchant's WooCommerce/CURCY versions. Verify the proxy address configuration and shared session behavior before deployment. Local mock-APS tests do not establish genuine APS sandbox or production payment behavior.
