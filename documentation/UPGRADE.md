CodeCart PRO 3.0.6.0 Build 1.8.3 UPDATE

Create and verify a full files/database backup. Preserve config.php, admin/config.php, image/ and the real DIR_STORAGE. Upload the contents of upload/ over the store, open /install/, choose UPDATE and finish all steps. The previous shared vendor is retained at DIR_STORAGE/codecart/vendor-previous/ for legacy compatibility.

Same-build repair
If Build 1.8.3 is already installed and files were damaged, upload the same package again and choose UPDATE with “Repair current build”. Repair force-resynchronizes the packaged Composer/vendor to the active DIR_STORAGE, clears template cache and invalidates the previous OCMOD build marker. Completed database migrations are not reset or reapplied destructively.

File replacement policy
Core application files in the package are expected to replace same-name Core files during an update. This is not an UPDATE blocker. The preflight blocks only conditions that make migration unsafe, such as an unsupported PHP/runtime profile, unreadable configuration, missing required source schema or unavailable writable storage. Active OCMOD overlaps and foreign Composer packages are reported as warnings so they can be reviewed after the update.

After UPDATE
Open Extensions > Modifications and refresh OCMOD. The header status dot turns orange when refresh is pending. The Modifications page shows which modifications were fully applied, which used optional skips and which were rolled back because of a blocking incompatibility. The Problems action opens the exact failed operation, target file/search and reason. Extension Installer also provides an optional automatic refresh setting; it runs only after a successful extension transaction and is disabled by default.

Build 1.8.3 is a compatibility-hardening update. It adds no mandatory external services or frontend framework. Existing OpenCart/ocStore routes, Events, OCMOD, themes and standard catalog search remain the fallback path.

Branding and favicon behaviour in Build 1.8.3
Visible system branding is standardised as CodeCart PRO. UPDATE does not overwrite the merchant store name, configured storefront logo or configured store favicon. Administration reuses the same configured store favicon as the storefront; Installer 2.0 keeps the CodeCart PRO system favicon because it is a system/upgrade surface. The bundled admin/installer system logo has the obsolete lower tagline removed.


Build 1.8.3 compatibility fixes

Wider legacy text columns are treated as compatible when they can safely store the expected data. Explicit safe schema repair may widen legacy VARCHAR/text columns; destructive narrowing is never automatic. OCMOD results can be sorted by compatibility state, and compact copy controls are available for the OCMOD log and error diagnostics. Carrier directory synchronization cleans PHP warning output from JSON responses and tolerates common legacy response encodings. Delivery reference directories do not require an API key.

### Theme preservation during upgrade
An upgrade installs/updates CodeCart Theme as an additional selectable theme. It does **not** change `config_theme` for the main store or any additional store, so UniShop2 and other active third-party themes remain active. Switch to CodeCart Theme manually only after reviewing it on a staging store.
