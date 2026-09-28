<?php
namespace CodeCart;

class Migration {
    const VERSION = '3.0.6.0';
    const PRESENTATION_SCHEMA_VERSION = '32';

    private $db;
    private $config;
    private $log;
    private $currentStep = 'bootstrap';

    public function __construct($registry) {
        $this->db = $registry->get('db');
        $this->config = $registry->get('config');
        $this->log = $registry->get('log');
    }

    public function run() {
        // Database schema and package filesystem state have separate versions. A maintenance
        // package can refresh Composer/vendor without changing the DB schema version.
        $schemaCurrent = (string)$this->config->get('codecart_core_schema_version') === self::VERSION
            && (string)$this->config->get('codecart_presentation_schema_version') === self::PRESENTATION_SCHEMA_VERSION;
        $forceRepair = defined('CODECART_FORCE_REPAIR') && CODECART_FORCE_REPAIR;
        $filesystemPending = $forceRepair || $this->hasPendingFilesystemUpdate();

        // Zero-work steady state: no advisory lock or DB reconciliation when both are current.
        if ($schemaCurrent && !$filesystemPending) {
            return;
        }

        $lock_name = 'codecart_core_migration_' . substr(hash('sha256', DB_DATABASE . '|' . DB_PREFIX), 0, 32);
        $lock_acquired = false;

        try {
            $lock_query = $this->db->query("SELECT GET_LOCK('" . $this->db->escape($lock_name) . "', 0) AS acquired");
            $lock_acquired = !empty($lock_query->row['acquired']);

            if (!$lock_acquired) {
                return;
            }

            // Filesystem/vendor activation is part of the same serialized upgrade
            // transaction boundary as schema migration. Concurrent update requests
            // must never race while renaming the active vendor tree.
            $this->currentStep = 'filesystem.vendor_sync';
            $this->finalizeStorageRelocation();
            $this->removePublicComposerMetadata();
            $this->syncStagedVendorToExternalStorage($forceRepair);
            $this->cleanupObsoleteLegacyCoreFiles();
            $this->removeObsoleteCookiePresetPngs();

            // Package-only maintenance update: filesystem work is complete; do not rerun DB migration.
            if ($schemaCurrent) {
                $this->currentStep = 'template_cache';
                $this->clearTemplateCache();
                $this->invalidateModificationCacheMarker($forceRepair ? 'core_repair' : 'core_updated');
                $this->clearFailureMarker();
                return;
            }

            // OpenCart/ocStore legacy schemas may define setting.serialized as NOT NULL without a default.
            // Under STRICT_TRANS_TABLES that makes third-party scalar INSERTs fail with MySQL 1364.
            $this->currentStep = 'core_setting_serialized';
            $this->ensureSettingSerializedDefault();
            $this->currentStep = 'relation_layer';
            $this->ensureRelationInfrastructure();
            $this->currentStep = 'layout_integrity';
            $this->cleanupLegacyLayoutNoops();

            $this->currentStep = 'core_columns';
            $this->ensureVarchar('user', 'password', 255);
            $this->ensureVarchar('customer', 'password', 255);
            $this->ensureVarchar('session', 'session_id', 64);
            $this->ensureVarchar('cart', 'session_id', 64);
            $this->ensureVarchar('api_session', 'session_id', 64);
            $this->ensureCustomerOnlineUserAgent();
            $this->ensureCustomerOnlineVisitorKey();
            $this->ensureCouponCustomerGroupTable();
            $this->ensureOrderProductSkuSnapshot();
            $this->ensureVarchar('voucher', 'code', 32);
            $this->ensureVarchar('order_voucher', 'code', 32);
            $this->currentStep = 'ocstore_compatibility';
            $this->ensureOcStoreCompatibilityTables();
            $this->ensureOcStoreSeoCompatibilityColumns();
            $this->ensureExtensionInstallerCompatibility();
            $this->currentStep = 'commerce_columns';
            $this->ensureMainCategoryCompatibility();
            $this->ensureProductTaxDisplayMode();
            $this->ensureProductExtraTabTable();
            $this->ensurePurchaseBlockTable();
            $this->ensureFormBuilderTables();
            $this->ensureTaxDisplayDefault();
            $this->ensureCurrencyDisplayDefaults();
            $this->ensureManufacturerImageDefaults();
            $this->currentStep = 'language_and_seo';
            $this->ensureLanguageUrlPrefix();
            // Preserve existing aliases but fill genuinely missing entity SEO URLs from names.
            // This is additive only: non-empty merchant keywords are never overwritten.
            $this->backfillMissingSeoUrls();
            $this->ensureModernSeoDefaults();
            $this->ensureCodeCartCoreDefaults();
            // Legacy/third-party SEO settings are preserved on UPDATE.
            $this->currentStep = 'currency_providers';
            $this->ensureUahCurrencySymbol();
            $this->ensureCurrencyProviders();
            // Legacy/regional extensions are intentionally preserved on UPDATE.
            // FULL omits obsolete bundled components, but migration must never uninstall a payment/shipping/integration chosen by an existing store.

            // Keep automatic UPDATE migrations bounded. Large customer/order history
            // tables are already modernized in fresh-install SQL, but are not rebuilt
            // implicitly on a live store where ALTER may cause a long lock.
            foreach (array(
                array('api_ip', 'ip'),
                array('api_session', 'ip'),
                array('user', 'ip')
            ) as $column) {
                $this->ensureVarchar($column[0], $column[1], 45);
            }

            $this->currentStep = 'core_indexes';
            $this->ensureIndex('api_ip', 'api_id_ip', array('api_id', 'ip'));
            $this->ensureIndex('api_session', 'session_id', array('session_id'));
            $this->ensureIndex('api_session', 'date_modified', array('date_modified'));
            $this->ensureIndex('api_session', 'api_id_ip', array('api_id', 'ip'));
            $this->ensureIndex('session', 'expire', array('expire'));
            $this->ensureIndex('setting', 'store_code', array('store_id', 'code'));
            $this->ensureIndex('setting', 'store_key', array('store_id', 'key'));
            $this->ensureIndex('user', 'username', array('username'));
            $this->ensureIndex('user', 'email', array('email'));
            // Upload tokens are resolved by code throughout cart/order/account/admin flows.
            // Index the lookup key so performance does not degrade with accumulated upload history.
            $this->ensureIndex('upload', 'code', array('code'), array('code' => 191));
            $this->ensureUniqueIndexIfNoDuplicates('voucher', 'code', array('code'));
            $this->ensureIndex('product_option', 'product_id', array('product_id'));
            $this->ensureIndex('product_option_value', 'product_id', array('product_id'));
            $this->ensureIndex('product_option_value', 'product_option_id', array('product_option_id'));
            $this->ensureIndex('filter', 'filter_group_id', array('filter_group_id'));
            $this->ensureIndex('option_value', 'option_id', array('option_id'));
            $this->ensureIndex('layout_route', 'store_route', array('store_id', 'route'));
            $this->ensureIndex('layout_module', 'layout_position', array('layout_id', 'position', 'sort_order'));
            $this->ensureIndex('module', 'code', array('code'));
            $this->ensureIndex('article_to_blog_category', 'blog_category_id', array('blog_category_id'));
            $this->ensureIndex('product_related', 'related_id', array('related_id'));
            $this->ensureIndex('article_related', 'related_id', array('related_id'));
            $this->ensureIndex('article_related_wb', 'category_id', array('category_id'));
            $this->ensureIndex('article_related_mn', 'manufacturer_id', array('manufacturer_id'));
            $this->ensureIndex('coupon_product', 'coupon_id', array('coupon_id'));
            $this->ensureIndex('coupon_product', 'product_id', array('product_id'));
            $this->ensureIndex('coupon_category', 'category_id', array('category_id'));
            $this->ensureIndex('product_to_download', 'download_id', array('download_id'));
            $this->ensureIndex('order_download', 'product_id', array('product_id'));
            $this->ensureIndex('product_related_wb', 'category_id', array('category_id'));
            $this->ensureIndex('product_related_mn', 'manufacturer_id', array('manufacturer_id'));
            $this->ensureIndex('product_to_store', 'store_product', array('store_id', 'product_id'));
            $this->ensureIndex('category_to_store', 'store_category', array('store_id', 'category_id'));
            $this->ensureIndex('manufacturer_to_store', 'store_manufacturer', array('store_id', 'manufacturer_id'));
            $this->ensureIndex('information_to_store', 'store_information', array('store_id', 'information_id'));
            $this->ensureIndex('article_to_store', 'store_article', array('store_id', 'article_id'));
            $this->ensureIndex('blog_category_to_store', 'store_blog_category', array('store_id', 'blog_category_id'));
            $this->ensureUniqueIndexIfNoDuplicates('manufacturer_description', 'manufacturer_language', array('manufacturer_id', 'language_id'));
            $this->ensureUniqueIndexIfNoDuplicates('product_related_wb', 'product_category', array('product_id', 'category_id'));
            $this->ensureUniqueIndexIfNoDuplicates('product_related_mn', 'product_manufacturer', array('product_id', 'manufacturer_id'));
            $this->ensureSeoUrlCompositeIndexes();
            // The composite SEO URL indexes have the same leading prefix and fully
            // cover the legacy single-column indexes; keeping both only adds write cost.
            $this->dropExactIndex('seo_url', 'query', array('query'));
            $this->dropExactIndex('seo_url', 'keyword', array('keyword'));

            $this->currentStep = 'scheduler_infrastructure';
            $this->ensureSchedulerInfrastructure();
            $this->currentStep = 'core_feature_infrastructure';
            $this->ensureCoreFeatureInfrastructure();
            $this->currentStep = 'core_schema_reconcile';
            $this->ensureSecuritySchemaParity();
            $this->currentStep = 'core_feature_defaults';
            // Existing merchant branding is never replaced during UPDATE.
            // Fresh-install SQL already uses WebP defaults; UPDATE preserves existing image paths.
            $this->ensureAdaptiveCaptchaDefaults();
            // Existing administrator profile data is never rebranded during UPDATE.
            // Existing storefront presentation is preserved on UPDATE.
            $this->ensureCoreFeatureDefaults();
            $this->ensureSeoModeConsistency();
            $this->ensureBundledDemoPresentationData();
            $this->enableConfiguredPurchaseBlocksFromRc88();
            $this->localizeBundledUkrainianDefaults();
            $this->ensureCodeCartThemeExtension();
            $this->ensureBundledModuleExtensions();
            $this->ensureCheckoutPaymentExtensions();
            $this->ensureQuickCheckoutDefaults();
            $this->ensureEditorDefault();
            $this->ensureStockNotifyInfrastructure();
            $this->ensureMailCampaignInfrastructure();
            $this->ensureCoreSchedulerTasks();
            $this->ensureSchedulerAdminPermission();
            $this->ensureCoreToolsAdminPermission();
            $this->ensureFormBuilderAdminPermission();
            $this->ensureAdministratorExtensionPermissions();
            $this->currentStep = 'shipping.carrier_choice';
            $this->ensureCarrierChoiceShipping();
            $this->currentStep = 'module.google_login';
            $this->ensureGoogleLoginModule();
            $this->currentStep = 'dashboard_health';
            $this->ensureDashboardHealthInfrastructure();
            $this->currentStep = 'dashboard.legacy_domovoy';
            $this->migrateLegacyDomovoyDashboard();
            $this->currentStep = 'module.invalid_json_settings';
            $this->repairInvalidModuleSettings();
            $this->currentStep = 'forms.entity_encoded_texts';
            $this->repairEntityEncodedFormTexts();
            $this->currentStep = 'catalog.category_path_consistency';
            $this->repairInconsistentCategoryPaths();
            // Existing SEO/blog rows are never cleaned implicitly during UPDATE.
            $this->updateDatabaseModernizationFlag();
            $this->currentStep = 'legacy_extra_email';
            $this->removeLegacyExtraEmailModification();
            $this->currentStep = 'events.order_history';
            $this->ensureOrderHistoryEventsAfter();
            $this->currentStep = 'events.voucher';
            $this->ensureVoucherEvent();
            $this->currentStep = 'events.structured_data';
            $this->ensureCodeCartStructuredDataEvent();
            $this->currentStep = 'events.cookie_consent';
            $this->ensureCodeCartCookieConsentEvent();
            $this->currentStep = 'filesystem.preserve';
            // .htaccess and robots.txt are deployment/merchant files. UPDATE never rewrites them.
            // Do not rewrite config.php or relocate storage automatically during UPDATE.
            // Fresh installs can place storage outside the web root; existing stores are
            // diagnosed by Preflight and can be migrated explicitly after backup.
            $this->currentStep = 'migration_history';
            // Record the successful migration before publishing the schema version.
            // A failed journal write must never leave a false-success version marker.
            $this->recordMigration('core', 'core-' . self::VERSION, self::VERSION, hash('sha256', self::VERSION));
            $this->saveVersion();
            $this->config->set('codecart_core_schema_version', self::VERSION);
            $this->currentStep = 'template_cache';
            $this->clearTemplateCache();
            $this->invalidateModificationCacheMarker($forceRepair ? 'core_repair' : 'core_updated');
            $this->clearFailureMarker();
        } catch (\Throwable $e) {
            $this->writeFailureMarker($e);

            if ($this->log) {
                $detail = $e->getPrevious() ? $e->getPrevious()->getMessage() : '';
                $detail = trim(preg_replace('/\s+/', ' ', (string)$detail));
                if (strlen($detail) > 500) { $detail = substr($detail, 0, 500) . '...'; }
                $this->log->write('CodeCart PRO core migration failed at [' . $this->currentStep . ']: ' . $e->getMessage() . ($detail !== '' ? ' DB: ' . $detail : ''));
            }
        } finally {
            if ($lock_acquired) {
                try {
                    $this->db->query("SELECT RELEASE_LOCK('" . $this->db->escape($lock_name) . "')");
                } catch (\Throwable $e) {
                    // MySQL/MariaDB releases advisory locks automatically when the connection closes.
                }
            }
        }
    }



    private function ensureCodeCartThemeExtension() {
        if (!$this->tableExists('extension') || !$this->tableExists('setting')) {
            return;
        }

        // Theme policy:
        // - CLEAN install: CodeCart Theme is the only registered system theme.
        // - UPDATE from OpenCart/ocStore: preserve every previously installed/active theme and
        //   register CodeCart Theme only as an additional option. Never switch config_theme.
        // Persist the installation origin so the admin UI can distinguish a native CodeCart
        // install from an upgraded legacy store without guessing from the currently active theme.
        $origin = $this->db->query("SELECT value FROM `" . DB_PREFIX . "setting` WHERE store_id='0' AND `key`='codecart_install_origin' LIMIT 1");
        if (!$origin->num_rows) {
            $legacyDefault = $this->db->query("SELECT extension_id FROM `" . DB_PREFIX . "extension` WHERE `type`='theme' AND `code`='default' LIMIT 1");
            $activeTheme = $this->db->query("SELECT value FROM `" . DB_PREFIX . "setting` WHERE store_id='0' AND `code`='config' AND `key`='config_theme' LIMIT 1");
            $activeThemeCode = $activeTheme->num_rows ? (string)$activeTheme->row['value'] : '';
            $originValue = ($legacyDefault->num_rows || ($activeThemeCode !== '' && $activeThemeCode !== 'codecart')) ? 'upgrade' : 'fresh';
            $this->db->query("INSERT INTO `" . DB_PREFIX . "setting` SET store_id='0', `code`='codecart_core', `key`='codecart_install_origin', `value`='" . $this->db->escape($originValue) . "', serialized='0'");
        }

        $exists = $this->db->query("SELECT extension_id FROM `" . DB_PREFIX . "extension` WHERE `type`='theme' AND `code`='codecart' LIMIT 1");
        if (!$exists->num_rows) {
            $this->db->query("INSERT INTO `" . DB_PREFIX . "extension` SET `type`='theme', `code`='codecart'");
        }

        $rows = $this->db->query("SELECT store_id, `key`, `value`, serialized FROM `" . DB_PREFIX . "setting` WHERE `code`='theme_default'");
        foreach ($rows->rows as $row) {
            $oldKey = (string)$row['key'];
            if (strpos($oldKey, 'theme_default_') !== 0) {
                continue;
            }
            $newKey = 'theme_codecart_' . substr($oldKey, 14);
            $value = (string)$row['value'];
            if ($newKey === 'theme_codecart_directory') {
                $value = 'codecart';
            }
            $present = $this->db->query("SELECT setting_id FROM `" . DB_PREFIX . "setting` WHERE store_id='" . (int)$row['store_id'] . "' AND `key`='" . $this->db->escape($newKey) . "' LIMIT 1");
            if (!$present->num_rows) {
                $this->db->query("INSERT INTO `" . DB_PREFIX . "setting` SET store_id='" . (int)$row['store_id'] . "', `code`='theme_codecart', `key`='" . $this->db->escape($newKey) . "', `value`='" . $this->db->escape($value) . "', serialized='" . (int)$row['serialized'] . "'");
            }
        }

        // A legacy database can have no theme_default rows for secondary stores. Ensure
        // the CodeCart theme is at least installable for the primary store without activating it.
        $status = $this->db->query("SELECT setting_id FROM `" . DB_PREFIX . "setting` WHERE store_id='0' AND `key`='theme_codecart_status' LIMIT 1");
        if (!$status->num_rows) {
            $this->db->query("INSERT INTO `" . DB_PREFIX . "setting` SET store_id='0', `code`='theme_codecart', `key`='theme_codecart_status', `value`='1', serialized='0'");
        }
        $directory = $this->db->query("SELECT setting_id FROM `" . DB_PREFIX . "setting` WHERE store_id='0' AND `key`='theme_codecart_directory' LIMIT 1");
        if (!$directory->num_rows) {
            $this->db->query("INSERT INTO `" . DB_PREFIX . "setting` SET store_id='0', `code`='theme_codecart', `key`='theme_codecart_directory', `value`='codecart', serialized='0'");
        }


        // Never uninstall or hide a legacy theme record during UPDATE. The old Default Theme,
        // UniShop2 and any other merchant theme remain intact even after CodeCart Theme is selected later.
    }

    private function ensureSeoModeConsistency() {
        if (!$this->tableExists('setting')) { return; }
        $seoPro = $this->db->query("SELECT value FROM `" . DB_PREFIX . "setting` WHERE store_id='0' AND `key`='config_seo_pro' LIMIT 1");
        if (!$seoPro->num_rows || !(int)$seoPro->row['value']) { return; }
        $seoUrl = $this->db->query("SELECT setting_id, value FROM `" . DB_PREFIX . "setting` WHERE store_id='0' AND `key`='config_seo_url' LIMIT 1");
        if ($seoUrl->num_rows) {
            if (!(int)$seoUrl->row['value']) {
                $this->db->query("UPDATE `" . DB_PREFIX . "setting` SET value='1' WHERE setting_id='" . (int)$seoUrl->row['setting_id'] . "'");
            }
        } else {
            $this->db->query("INSERT INTO `" . DB_PREFIX . "setting` SET store_id='0', `code`='config', `key`='config_seo_url', `value`='1', serialized='0'");
        }
    }

    private function ensureBundledDemoPresentationData() {
        $storeName = '';
        if ($this->tableExists('setting')) {
            $store = $this->db->query("SELECT value FROM `" . DB_PREFIX . "setting` WHERE store_id='0' AND `key`='config_name' LIMIT 1");
            if ($store->num_rows) { $storeName = (string)$store->row['value']; }
        }
        if (!in_array($storeName, array('CodeCart Demo Store', 'CodeCart PRO Demo Store'), true)) {
            $this->setPresentationSchemaMarker();
            return;
        }

        // Demo-only manufacturer refresh. Never inject demo brands into merchant stores.
        if ($this->tableExists('manufacturer')) {
            $this->db->query("UPDATE `" . DB_PREFIX . "manufacturer` SET image='catalog/demo/manufacturer/htc.webp' WHERE manufacturer_id='5' AND image IN ('','catalog/demo/htc_touch_hd_1.webp')");
            $this->db->query("UPDATE `" . DB_PREFIX . "manufacturer` SET image='catalog/demo/manufacturer/hp.webp' WHERE manufacturer_id='7' AND image IN ('','catalog/demo/hp_banner.webp')");
            $demoManufacturers = array(
                'Dell' => array('image'=>'catalog/demo/manufacturer/dell.webp','languages'=>array(1=>array('Dell — демонстраційний виробник для перевірки сторінок брендів, фільтрації та пов’язаного асортименту.','Dell — товари виробника'),2=>array('Dell is included as a demo manufacturer for testing brand pages, filtering and related catalog content.','Dell manufacturer products'))),
                'Samsung' => array('image'=>'catalog/demo/manufacturer/samsung.webp','languages'=>array(1=>array('Samsung — демонстраційний виробник для перевірки сторінок брендів і товарів електроніки.','Samsung — товари виробника'),2=>array('Samsung is included as a demo manufacturer for testing brand and electronics catalog pages.','Samsung manufacturer products'))),
                'Nintendo' => array('image'=>'catalog/demo/manufacturer/nintendo.webp','languages'=>array(1=>array('Nintendo — демонстраційний виробник для презентації сторінок брендів і мультимедійного асортименту.','Nintendo — товари виробника'),2=>array('Nintendo is included as a demo manufacturer for showcasing brand pages and multimedia catalog content.','Nintendo manufacturer products'))),
                'Nikon' => array('image'=>'catalog/demo/nikon_d300_1.webp','languages'=>array(1=>array('Nikon — демонстраційний виробник для перевірки сторінок брендів і фототехніки.','Nikon — товари виробника'),2=>array('Nikon is included as a demo manufacturer for testing brand and camera catalog pages.','Nikon manufacturer products'))),
                'CodeCart PRO' => array('image'=>'catalog/codecartpro.webp','languages'=>array(1=>array('Службовий демонстраційний бренд для тестових товарів CodeCart PRO Demo Store.','CodeCart PRO — демонстраційні товари'),2=>array('Internal demo brand used for test products in CodeCart PRO Demo Store.','CodeCart PRO demo products')))
            );
            foreach ($demoManufacturers as $manufacturerName => $manufacturerRow) {
                $exists = $this->db->query("SELECT manufacturer_id FROM `" . DB_PREFIX . "manufacturer` WHERE name='" . $this->db->escape($manufacturerName) . "' LIMIT 1");
                if ($exists->num_rows) {
                    $manufacturerId = (int)$exists->row['manufacturer_id'];
                    if ($manufacturerName === 'Samsung') {
                        $this->db->query("UPDATE `" . DB_PREFIX . "manufacturer` SET image='" . $this->db->escape($manufacturerRow['image']) . "' WHERE manufacturer_id='" . $manufacturerId . "' AND image IN ('','catalog/demo/samsung_banner.webp')");
                    } else {
                        $this->db->query("UPDATE `" . DB_PREFIX . "manufacturer` SET image='" . $this->db->escape($manufacturerRow['image']) . "' WHERE manufacturer_id='" . $manufacturerId . "' AND image=''");
                    }
                } else {
                    $this->db->query("INSERT INTO `" . DB_PREFIX . "manufacturer` SET name='" . $this->db->escape($manufacturerName) . "', image='" . $this->db->escape($manufacturerRow['image']) . "', sort_order='0', noindex='1'");
                    $manufacturerId = (int)$this->db->getLastId();
                }
                if ($manufacturerId < 1) { continue; }
                if ($this->tableExists('manufacturer_to_store')) {
                    $this->db->query("INSERT INTO `" . DB_PREFIX . "manufacturer_to_store` (manufacturer_id,store_id) VALUES ('" . $manufacturerId . "','0') ON DUPLICATE KEY UPDATE manufacturer_id=VALUES(manufacturer_id)");
                }
                if ($this->tableExists('manufacturer_description')) {
                    foreach ($manufacturerRow['languages'] as $languageId => $row) {
                        $description = '&lt;h2&gt;' . $manufacturerName . '&lt;/h2&gt;&lt;p&gt;' . $row[0] . '&lt;/p&gt;';
                        $this->db->query("REPLACE INTO `" . DB_PREFIX . "manufacturer_description` SET manufacturer_id='" . $manufacturerId . "', language_id='" . (int)$languageId . "', description='" . $this->db->escape($description) . "', description3='', meta_description='" . $this->db->escape($row[1]) . "', meta_keyword='', meta_title='" . $this->db->escape($row[1]) . "', meta_h1='" . $this->db->escape($manufacturerName) . "'");
                    }
                }
                if ($this->tableExists('seo_url') && in_array($manufacturerName, array('Nikon','CodeCart PRO'), true)) {
                    $slug = $manufacturerName === 'Nikon' ? 'nikon' : 'codecart-pro';
                    foreach (array(1,2) as $languageId) {
                        $seo = $this->db->query("SELECT seo_url_id, keyword FROM `" . DB_PREFIX . "seo_url` WHERE store_id='0' AND language_id='" . (int)$languageId . "' AND query='manufacturer_id=" . (int)$manufacturerId . "' LIMIT 1");
                        if (!$seo->num_rows) {
                            $this->db->query("INSERT INTO `" . DB_PREFIX . "seo_url` SET store_id='0', language_id='" . (int)$languageId . "', query='manufacturer_id=" . (int)$manufacturerId . "', keyword='" . $this->db->escape($slug) . "'");
                        } elseif (trim((string)$seo->row['keyword']) === '') {
                            $this->db->query("UPDATE `" . DB_PREFIX . "seo_url` SET keyword='" . $this->db->escape($slug) . "' WHERE seo_url_id='" . (int)$seo->row['seo_url_id'] . "'");
                        }
                    }
                }
            }
        }

        // Presentation schema v16: keep the bundled demo catalog complete enough for
        // dashboard SEO/Merchant diagnostics. Do not invent GTINs: demo products use
        // explicit SKU/MPN identifiers, while real brand assignments are restored.
        // This runs only for the exact CodeCart PRO Demo Store because of the guard above.
        if ($this->tableExists('product')) {
            $manufacturerIds = array();
            foreach (array('Nikon','CodeCart PRO','Samsung') as $demoBrand) {
                $brandRow = $this->db->query("SELECT manufacturer_id FROM `" . DB_PREFIX . "manufacturer` WHERE name='" . $this->db->escape($demoBrand) . "' LIMIT 1");
                if ($brandRow->num_rows) { $manufacturerIds[$demoBrand] = (int)$brandRow->row['manufacturer_id']; }
            }
            $demoProductData = array(
                28=>array('HTC-T8282','DEMO-HTC-T8282','T8282',5),
                29=>array('TREO-PRO','DEMO-PALM-TREO-PRO','TREO-PRO',6),
                30=>array('EOS-5D','DEMO-CANON-EOS5D','EOS-5D',9),
                31=>array('D300','DEMO-NIKON-D300','D300',isset($manufacturerIds['Nikon'])?$manufacturerIds['Nikon']:0),
                32=>array('IPOD-TOUCH','DEMO-APPLE-IPOD-TOUCH','IPOD-TOUCH-DEMO',8),
                33=>array('941BW','DEMO-SAMSUNG-941BW','941BW',isset($manufacturerIds['Samsung'])?$manufacturerIds['Samsung']:12),
                34=>array('IPOD-SHUFFLE','DEMO-APPLE-IPOD-SHUFFLE','IPOD-SHUFFLE-DEMO',8),
                35=>array('DEMO-NOIMAGE','DEMO-NOIMAGE-35','CCP-DEMO-NOIMAGE-35',isset($manufacturerIds['CodeCart PRO'])?$manufacturerIds['CodeCart PRO']:0),
                36=>array('IPOD-NANO','DEMO-APPLE-IPOD-NANO','IPOD-NANO-DEMO',8),
                40=>array('IPHONE','DEMO-APPLE-IPHONE','IPHONE-DEMO',8),
                41=>array('IMAC','DEMO-APPLE-IMAC','IMAC-DEMO',8),
                42=>array('CINEMA-30','DEMO-APPLE-CINEMA30','CINEMA-30-DEMO',8),
                43=>array('MACBOOK','DEMO-APPLE-MACBOOK','MACBOOK-DEMO',8),
                44=>array('MACBOOK-AIR','DEMO-APPLE-MBAIR','MACBOOK-AIR-DEMO',8),
                45=>array('MACBOOK-PRO','DEMO-APPLE-MBPRO','MACBOOK-PRO-DEMO',8),
                46=>array('VAIO','DEMO-SONY-VAIO','VAIO-DEMO',10),
                47=>array('LP3065','DEMO-HP-LP3065','LP3065',7),
                48=>array('IPOD-CLASSIC','DEMO-APPLE-IPOD-CLASSIC','IPOD-CLASSIC-DEMO',8),
                49=>array('GT-P7500','DEMO-SAMSUNG-GTAB101','GT-P7500',isset($manufacturerIds['Samsung'])?$manufacturerIds['Samsung']:12)
            );
            foreach ($demoProductData as $productId => $row) {
                $sets = array(
                    "model='" . $this->db->escape($row[0]) . "'",
                    "sku='" . $this->db->escape($row[1]) . "'",
                    "mpn='" . $this->db->escape($row[2]) . "'"
                );
                if ((int)$row[3] > 0) { $sets[] = "manufacturer_id='" . (int)$row[3] . "'"; }
                if ((int)$productId === 48) { $sets[] = "location=CASE WHEN location IN ('','test 2') THEN 'DEMO-WAREHOUSE' ELSE location END"; }
                if ((int)$productId === 49) { $sets[] = "quantity=CASE WHEN quantity <= 5 THEN 50 ELSE quantity END"; }
                $this->db->query("UPDATE `" . DB_PREFIX . "product` SET " . implode(',', $sets) . " WHERE product_id='" . (int)$productId . "'");
            }
        }

        // Presentation schema v18: demonstrate the two checkout buyer types without
        // changing customer groups in merchant stores. Company requisites are a
        // standard account custom field, so the normal OpenCart visibility/required
        // rules continue to apply.
        $this->ensureBundledDemoCheckoutCustomerGroups();

        // The bundled demo should open in the light storefront theme by default.
        // Change only the old bundled/system default; never override an explicit merchant choice.
        if ($this->tableExists('setting')) {
            $this->db->query("UPDATE `" . DB_PREFIX . "setting` SET value='light' WHERE store_id='0' AND code='theme_default' AND `key`='theme_default_dark_mode_default' AND value='system'");
            $this->config->set('theme_default_dark_mode_default', 'light');
        }

        if (!$this->tableExists('layout_route') || !$this->tableExists('layout_module') || !$this->tableExists('module')) {
            $this->setPresentationSchemaMarker();
            return;
        }

        $home = $this->db->query("SELECT layout_id FROM `" . DB_PREFIX . "layout_route` WHERE store_id='0' AND route='common/home' LIMIT 1");
        if (!$home->num_rows) {
            $layout = $this->db->query("SELECT lm.layout_id FROM `" . DB_PREFIX . "layout_module` lm INNER JOIN `" . DB_PREFIX . "module` m ON (m.module_id=SUBSTRING_INDEX(lm.code,'.',-1)) WHERE lm.code IN ('slideshow.27','featured.28','category_wall.37') ORDER BY lm.layout_id ASC LIMIT 1");
            if ($layout->num_rows) {
                $this->db->query("INSERT INTO `" . DB_PREFIX . "layout_route` SET layout_id='" . (int)$layout->row['layout_id'] . "', store_id='0', route='common/home'");
                $home = $this->db->query("SELECT layout_id FROM `" . DB_PREFIX . "layout_route` WHERE store_id='0' AND route='common/home' LIMIT 1");
            }
        }

        // The homepage must show the module that administrators actually see in Design > Layouts.
        // Keep the generic Carousel extension/module intact for compatibility, but stop using its hidden banner source
        // as the homepage manufacturer wall. The dedicated Brands module is configured and assigned instead.
        $manufacturerWall = $this->db->query("SELECT module_id, setting FROM `" . DB_PREFIX . "module` WHERE code='manufacturer_wall' ORDER BY module_id ASC LIMIT 1");
        if ($manufacturerWall->num_rows) {
            $setting = json_decode((string)$manufacturerWall->row['setting'], true);
            if (!is_array($setting)) { $setting = array(); }
            $setting = array_merge($setting, array(
                'name' => 'Головна — виробники',
                'heading' => array('1' => 'Бренди та виробники', '2' => 'Brands & Manufacturers'),
                'source' => 'all', 'limit' => '12', 'display_mode' => 'carousel',
                'columns_desktop' => '6', 'columns_tablet' => '4', 'columns_mobile' => '2',
                'autoplay' => '1', 'autoplay_delay' => '2000', 'show_arrows' => '1', 'show_dots' => '1',
                'loop' => '1', 'carousel_step' => 'item', 'width' => '180', 'height' => '90',
                'image_fit' => 'contain', 'show_name' => '1', 'show_image' => '1', 'show_count' => '0',
                'hide_empty' => '0', 'hide_without_image' => '0', 'show_heading' => '1',
                'sort' => 'sort_order', 'status' => '1'
            ));
            $manufacturerWallId = (int)$manufacturerWall->row['module_id'];
            $this->db->query("UPDATE `" . DB_PREFIX . "module` SET name='Головна — виробники', setting='" . $this->db->escape(json_encode($setting, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) . "' WHERE module_id='" . $manufacturerWallId . "'");

            if ($home->num_rows) {
                $homeLayoutId = (int)$home->row['layout_id'];
                // Remove only generic Carousel instances whose source is the demo Manufacturers banner.
                $legacyCarousels = $this->db->query("SELECT module_id, setting FROM `" . DB_PREFIX . "module` WHERE code='carousel'");
                foreach ($legacyCarousels->rows as $legacyCarousel) {
                    $legacySetting = json_decode((string)$legacyCarousel['setting'], true);
                    if (is_array($legacySetting) && (int)($legacySetting['banner_id'] ?? 0) === 8) {
                        $this->db->query("DELETE FROM `" . DB_PREFIX . "layout_module` WHERE layout_id='" . $homeLayoutId . "' AND code='carousel." . (int)$legacyCarousel['module_id'] . "'");
                    }
                }
                $wallCode = 'manufacturer_wall.' . $manufacturerWallId;
                $exists = $this->db->query("SELECT layout_module_id FROM `" . DB_PREFIX . "layout_module` WHERE layout_id='" . $homeLayoutId . "' AND code='" . $this->db->escape($wallCode) . "' LIMIT 1");
                if ($exists->num_rows) {
                    $this->db->query("UPDATE `" . DB_PREFIX . "layout_module` SET position='content_top', sort_order='6' WHERE layout_module_id='" . (int)$exists->row['layout_module_id'] . "'");
                } else {
                    $this->db->query("INSERT INTO `" . DB_PREFIX . "layout_module` SET layout_id='" . $homeLayoutId . "', code='" . $this->db->escape($wallCode) . "', position='content_top', sort_order='6'");
                }
            }
        }

        // Two additional homepage slideshow frames, idempotently added only to the bundled demo store.
        if ($this->tableExists('banner') && $this->tableExists('banner_image') && $this->tableExists('language')) {
            $slideshow = $this->db->query("SELECT banner_id FROM `" . DB_PREFIX . "banner` WHERE name='Слайдшоу головної сторінки' ORDER BY banner_id ASC LIMIT 1");
            if ($slideshow->num_rows) {
                $bannerId = (int)$slideshow->row['banner_id'];
                $languages = $this->db->query("SELECT language_id, code FROM `" . DB_PREFIX . "language` WHERE code IN ('uk-ua','en-gb')");
                foreach ($languages->rows as $language) {
                    $languageId = (int)$language['language_id'];
                    $slides = array(
                        array('Samsung','index.php?route=product/manufacturer/info&amp;manufacturer_id=12','catalog/demo/banners/Samsung.webp',2),
                        array('Hewlett-Packard','index.php?route=product/manufacturer/info&amp;manufacturer_id=7','catalog/demo/banners/HewlettPackard.webp',3)
                    );
                    $this->db->query("UPDATE `" . DB_PREFIX . "banner_image` SET sort_order='0' WHERE banner_id='" . $bannerId . "' AND language_id='" . $languageId . "' AND image='catalog/demo/banners/iPhone6.webp'");
                    $this->db->query("UPDATE `" . DB_PREFIX . "banner_image` SET sort_order='1' WHERE banner_id='" . $bannerId . "' AND language_id='" . $languageId . "' AND image='catalog/demo/banners/MacBookAir.webp'");
                    foreach ($slides as $slide) {
                        $existingSlide = $this->db->query("SELECT banner_image_id FROM `" . DB_PREFIX . "banner_image` WHERE banner_id='" . $bannerId . "' AND language_id='" . $languageId . "' AND image='" . $this->db->escape($slide[2]) . "' LIMIT 1");
                        if ($existingSlide->num_rows) {
                            $this->db->query("UPDATE `" . DB_PREFIX . "banner_image` SET title='" . $this->db->escape($slide[0]) . "', link='" . $this->db->escape($slide[1]) . "', sort_order='" . (int)$slide[3] . "' WHERE banner_image_id='" . (int)$existingSlide->row['banner_image_id'] . "'");
                        } else {
                            $this->db->query("INSERT INTO `" . DB_PREFIX . "banner_image` SET banner_id='" . $bannerId . "', language_id='" . $languageId . "', title='" . $this->db->escape($slide[0]) . "', link='" . $this->db->escape($slide[1]) . "', image='" . $this->db->escape($slide[2]) . "', sort_order='" . (int)$slide[3] . "'");
                        }
                    }
                }
            }
        }

        // Presentation schema: the native category page already renders child categories with images.
        // Remove only the bundled demo Category Wall from Category layout to avoid duplicate subcategory blocks.
        if ($this->tableExists('layout_module')) {
            $this->db->query("DELETE FROM `" . DB_PREFIX . "layout_module` WHERE layout_id='3' AND code='category_wall.42' AND position='content_top'");
        }
        if ($this->tableExists('setting')) {
            $this->db->query("UPDATE `" . DB_PREFIX . "setting` SET value='1' WHERE store_id='0' AND `key`='config_seo_url'");
        }

        // Presentation-only article branding refresh. Merchant stores are excluded by the store-name guard above.
        if ($this->tableExists('article')) {
            $this->db->query("UPDATE `" . DB_PREFIX . "article` SET image='catalog/codecart-pro-system.webp', date_added='2026-09-27 12:00:00', date_modified='2026-09-27 12:00:00' WHERE article_id='120' AND image IN ('catalog/cart.webp','catalog/codecart-pro-system.webp')");
        }
        if ($this->tableExists('article_description')) {
            foreach (array('name','description','meta_description','meta_title','meta_h1') as $column) {
                $this->db->query("UPDATE `" . DB_PREFIX . "article_description` SET `" . $column . "`=REPLACE(`" . $column . "`,CONCAT('CodeCart PRO 3.0.6.0 ','Be','ta'),'CodeCart PRO 3.0.6.0') WHERE article_id='120'");
            }
        }
        if ($this->tableExists('article_description')) {
            $this->db->query("UPDATE `" . DB_PREFIX . "article_description` SET name='CodeCart PRO 3.0.6.0 — сучасна основа для інтернет-магазину', description='&lt;h2 id=&quot;about&quot;&gt;CodeCart PRO 3.0.6.0 — сучасна основа для інтернет-магазину&lt;/h2&gt;
&lt;p&gt;&lt;a href=&quot;#about&quot;&gt;Про систему&lt;/a&gt; · &lt;a href=&quot;#difference&quot;&gt;Відмінності&lt;/a&gt; · &lt;a href=&quot;#commerce&quot;&gt;Комерція&lt;/a&gt; · &lt;a href=&quot;#compatibility&quot;&gt;Сумісність&lt;/a&gt; · &lt;a href=&quot;#seo&quot;&gt;SEO&lt;/a&gt; · &lt;a href=&quot;#security&quot;&gt;Безпека&lt;/a&gt; · &lt;a href=&quot;#result&quot;&gt;Для кого&lt;/a&gt;&lt;/p&gt;
&lt;p&gt;&lt;strong&gt;CodeCart PRO 3.0.6.0&lt;/strong&gt; — це модернізована e-commerce платформа на основі екосистеми OpenCart 3.x. Її мета — зберегти сумісність зі звичними модулями, OCMOD, Events і MVC-L, але додати сучасний шар для безпечних оновлень, черг, планувальника, API, Modern Extensions, Compatibility Framework, продуктивності та стабільної роботи магазину.&lt;/p&gt;
&lt;p&gt;CodeCart не намагається замінити робочу екосистему радикально новим стеком. Замість цього використовується принцип &lt;strong&gt;Legacy Core + Modern Core&lt;/strong&gt;: старі розширення продовжують працювати у знайомому середовищі, а нові можуть використовувати namespace, PSR-4, сервіси, manifests і стабільні точки розширення.&lt;/p&gt;

&lt;h3 id=&quot;difference&quot;&gt;Чим CodeCart відрізняється від OpenCart та ocStore&lt;/h3&gt;
&lt;div class=&quot;table-responsive&quot;&gt;
&lt;table class=&quot;table table-bordered table-striped&quot;&gt;
&lt;thead&gt;&lt;tr&gt;&lt;th&gt;Можливість&lt;/th&gt;&lt;th&gt;OpenCart 3.0.5.x&lt;/th&gt;&lt;th&gt;ocStore 3.0.5.x&lt;/th&gt;&lt;th&gt;CodeCart PRO 3.0.6.x&lt;/th&gt;&lt;/tr&gt;&lt;/thead&gt;
&lt;tbody&gt;
&lt;tr&gt;&lt;td&gt;PHP&lt;/td&gt;&lt;td&gt;PHP 8.0–8.4&lt;/td&gt;&lt;td&gt;PHP 8.0–8.5&lt;/td&gt;&lt;td&gt;&lt;strong&gt;PHP 8.1–8.5&lt;/strong&gt;&lt;/td&gt;&lt;/tr&gt;
&lt;tr&gt;&lt;td&gt;Архітектура розширень&lt;/td&gt;&lt;td&gt;MVC-L, OCMOD, Events&lt;/td&gt;&lt;td&gt;MVC-L, OCMOD, Events&lt;/td&gt;&lt;td&gt;&lt;strong&gt;Legacy + Modern Extensions, PSR-4, Services, manifests&lt;/strong&gt;&lt;/td&gt;&lt;/tr&gt;
&lt;tr&gt;&lt;td&gt;Оновлення системи&lt;/td&gt;&lt;td&gt;Класичний installer&lt;/td&gt;&lt;td&gt;Класичний installer&lt;/td&gt;&lt;td&gt;&lt;strong&gt;Installer 2.0, preflight, контрольована міграція та повторний запуск&lt;/strong&gt;&lt;/td&gt;&lt;/tr&gt;
&lt;tr&gt;&lt;td&gt;Надійність checkout&lt;/td&gt;&lt;td&gt;Стандартна логіка&lt;/td&gt;&lt;td&gt;Стандартна логіка&lt;/td&gt;&lt;td&gt;&lt;strong&gt;Транзакції, блокування, idempotency, захист повторних callback&lt;/strong&gt;&lt;/td&gt;&lt;/tr&gt;
&lt;tr&gt;&lt;td&gt;Черги та автоматизація&lt;/td&gt;&lt;td&gt;Переважно через модулі&lt;/td&gt;&lt;td&gt;Переважно через модулі&lt;/td&gt;&lt;td&gt;&lt;strong&gt;Queue, Scheduler, CLI Worker та Cron layer&lt;/strong&gt;&lt;/td&gt;&lt;/tr&gt;
&lt;tr&gt;&lt;td&gt;Сумісність сторонніх тем&lt;/td&gt;&lt;td&gt;Нативна для своєї версії&lt;/td&gt;&lt;td&gt;Нативна для ocStore&lt;/td&gt;&lt;td&gt;&lt;strong&gt;Compatibility Framework і встановлювані adapters&lt;/strong&gt;&lt;/td&gt;&lt;/tr&gt;
&lt;tr&gt;&lt;td&gt;SEO URL&lt;/td&gt;&lt;td&gt;Стандартні SEO URL&lt;/td&gt;&lt;td&gt;SEO URL + SeoPro&lt;/td&gt;&lt;td&gt;&lt;strong&gt;Стандартні SEO URL + SeoPro + динамічні мовні префікси&lt;/strong&gt;&lt;/td&gt;&lt;/tr&gt;
&lt;tr&gt;&lt;td&gt;Зображення&lt;/td&gt;&lt;td&gt;Базова обробка&lt;/td&gt;&lt;td&gt;Базова обробка&lt;/td&gt;&lt;td&gt;&lt;strong&gt;WebP/AVIF-ready pipeline, сучасні image hooks&lt;/strong&gt;&lt;/td&gt;&lt;/tr&gt;
&lt;tr&gt;&lt;td&gt;Адмінка та діагностика&lt;/td&gt;&lt;td&gt;Класична&lt;/td&gt;&lt;td&gt;Класична&lt;/td&gt;&lt;td&gt;&lt;strong&gt;Core diagnostics, compatibility scanner, глобальний пошук, системні повідомлення&lt;/strong&gt;&lt;/td&gt;&lt;/tr&gt;
&lt;tr&gt;&lt;td&gt;Безпека&lt;/td&gt;&lt;td&gt;Базові механізми&lt;/td&gt;&lt;td&gt;Розширені локальні правки&lt;/td&gt;&lt;td&gt;&lt;strong&gt;Security headers, upload guard, rate limits, secret handling, аудит критичних дій&lt;/strong&gt;&lt;/td&gt;&lt;/tr&gt;
&lt;/tbody&gt;&lt;/table&gt;
&lt;/div&gt;

&lt;h3 id=&quot;commerce&quot;&gt;Commerce-first: надійність продажів&lt;/h3&gt;
&lt;p&gt;Для магазину важливо не лише швидко показати сторінку, а й гарантовано створити одне замовлення, один раз списати залишок і не застосувати купон або платіжний callback повторно. У CodeCart посилено критичні ділянки checkout: транзакції, блокування конкурентних змін, idempotency, повернення залишків, ваучери, купони та журналювання помилок.&lt;/p&gt;
&lt;ul&gt;
&lt;li&gt;захист від подвійного натискання Confirm;&lt;/li&gt;
&lt;li&gt;захист від повторного webhook або callback;&lt;/li&gt;
&lt;li&gt;контроль залишків при паралельних замовленнях;&lt;/li&gt;
&lt;li&gt;безпечне скасування і повернення зарезервованих даних;&lt;/li&gt;
&lt;li&gt;постійні черги для фонових задач.&lt;/li&gt;
&lt;/ul&gt;

&lt;h3 id=&quot;compatibility&quot;&gt;Сумісність без повернення старого ядра&lt;/h3&gt;
&lt;p&gt;CodeCart підтримує звичайні OpenCart 3.x модулі та одночасно має &lt;strong&gt;Compatibility Framework&lt;/strong&gt;. Якщо популярна тема очікує старі внутрішні контракти, для неї можна підключити окремий adapter без переписування Core. Практичний приклад — UniShop2: адаптер відновлює необхідні контракти меню, категорій, опцій та банерів, але не повертає старі N+1 алгоритми.&lt;/p&gt;
&lt;p&gt;При оновленні існуючого OpenCart або ocStore активна тема магазину зберігається. CodeCart Theme додається окремо і не повинна самовільно замінювати оформлення чинного магазину.&lt;/p&gt;

&lt;h3 id=&quot;seo&quot;&gt;SEO, ЧПУ та мультимовність&lt;/h3&gt;
&lt;p&gt;У CodeCart є два рівні URL. &lt;strong&gt;ЧПУ&lt;/strong&gt; — базовий механізм красивих SEO URL. &lt;strong&gt;SeoPro&lt;/strong&gt; — розширений маршрутизатор, який додає роботу зі шляхами категорій, canonical-логікою, суфіксами та додатковими правилами. Для мультимовного магазину мовні префікси визначаються динамічно: головна мова може працювати без префікса, а інші — з довільними папками.&lt;/p&gt;
&lt;p&gt;Система також орієнтована на canonical URL, sitemap, структуровані дані, коректну індексацію, SEO URL для товарів, категорій, виробників, інформаційних сторінок і блогу.&lt;/p&gt;

&lt;h3 id=&quot;performance&quot;&gt;Швидкість і сучасна вітрина&lt;/h3&gt;
&lt;p&gt;CodeCart зберігає легку серверну модель OpenCart, але оптимізує типові вузькі місця: пакетне завантаження категорій, контрольоване кешування, lazy-loading, сучасні формати зображень і підключення CSS/JavaScript лише там, де вони потрібні. Вітрина залишається сумісною з Bootstrap 3-модулями, але отримує сучасні адаптивні компоненти.&lt;/p&gt;

&lt;h3 id=&quot;security&quot;&gt;Безпека та контроль&lt;/h3&gt;
&lt;ul&gt;
&lt;li&gt;валідація admin/AJAX/API дій і user_token;&lt;/li&gt;
&lt;li&gt;безпечна робота з SQL та whitelist для динамічних полів;&lt;/li&gt;
&lt;li&gt;UploadGuard і перевірка типів файлів;&lt;/li&gt;
&lt;li&gt;rate limiting для публічних endpoint;&lt;/li&gt;
&lt;li&gt;захищене зберігання секретів без повернення API-ключів у DOM;&lt;/li&gt;
&lt;li&gt;діагностика без показу відвідувачу абсолютних шляхів і raw PHP errors.&lt;/li&gt;
&lt;/ul&gt;

&lt;h3 id=&quot;extensions&quot;&gt;Modern Extensions&lt;/h3&gt;
&lt;p&gt;Нові розширення можуть використовувати Modern Extension Registry. Це дозволяє встановлювати окремі пакети з manifest, власним namespace, permissions і compatibility adapters без постійного патчування ядра. При цьому звичайні OpenCart-модулі залишаються підтримуваними.&lt;/p&gt;

&lt;h3 id=&quot;result&quot;&gt;Для кого CodeCart&lt;/h3&gt;
&lt;p&gt;CodeCart підходить магазинам, яким потрібна знайома екосистема OpenCart 3.x, але з більш сучасною основою для довготривалої роботи. Це не повний розрив із OpenCart, а контрольована еволюція: старі модулі можуть продовжувати працювати, а нові функції отримують сучасні контракти, діагностику, автоматизацію та безпечніше оновлення.&lt;/p&gt;
&lt;p&gt;&lt;strong&gt;Головна ідея:&lt;/strong&gt; магазин має залишатися легким, сумісним і передбачуваним, але при цьому бути готовим до сучасних вимог SEO, безпеки, автоматизації, API та масштабування.&lt;/p&gt;', meta_description='CodeCart PRO 3.0.6.0 — сучасна основа OpenCart 3.x з безпечними оновленнями, Modern Extensions, SEO, Compatibility Framework та надійним checkout.', meta_keyword='codecart, opencart, ocstore, ecommerce, seo, modern extensions', meta_title='CodeCart PRO 3.0.6.0 — сучасна основа для інтернет-магазину', meta_h1='CodeCart PRO 3.0.6.0 — сучасна основа для інтернет-магазину', tag='codecart, opencart, ocstore, ecommerce, seo' WHERE article_id='120' AND language_id='1'");
            $this->db->query("UPDATE `" . DB_PREFIX . "article_description` SET name='CodeCart PRO 3.0.6.0 — a modern foundation for online stores', description='&lt;h2 id=&quot;about&quot;&gt;CodeCart PRO 3.0.6.0 — a modern foundation for online stores&lt;/h2&gt;
&lt;p&gt;&lt;a href=&quot;#about&quot;&gt;About&lt;/a&gt; · &lt;a href=&quot;#difference&quot;&gt;Differences&lt;/a&gt; · &lt;a href=&quot;#commerce&quot;&gt;Commerce&lt;/a&gt; · &lt;a href=&quot;#compatibility&quot;&gt;Compatibility&lt;/a&gt; · &lt;a href=&quot;#seo&quot;&gt;SEO&lt;/a&gt; · &lt;a href=&quot;#security&quot;&gt;Security&lt;/a&gt; · &lt;a href=&quot;#result&quot;&gt;Who it is for&lt;/a&gt;&lt;/p&gt;
&lt;p&gt;&lt;strong&gt;CodeCart PRO 3.0.6.0&lt;/strong&gt; is a modernized e-commerce platform built on the OpenCart 3.x ecosystem. Its goal is to preserve compatibility with familiar modules, OCMOD, Events and MVC-L while adding a modern layer for safer upgrades, queues, scheduling, APIs, Modern Extensions, compatibility adapters, performance and commerce reliability.&lt;/p&gt;
&lt;p&gt;The core principle is &lt;strong&gt;Legacy Core + Modern Core&lt;/strong&gt;: existing extensions keep the environment they expect, while new extensions can use namespaces, PSR-4, services, manifests and stable extension contracts.&lt;/p&gt;

&lt;h3 id=&quot;difference&quot;&gt;How CodeCart differs from OpenCart and ocStore&lt;/h3&gt;
&lt;div class=&quot;table-responsive&quot;&gt;
&lt;table class=&quot;table table-bordered table-striped&quot;&gt;
&lt;thead&gt;&lt;tr&gt;&lt;th&gt;Capability&lt;/th&gt;&lt;th&gt;OpenCart 3.0.5.x&lt;/th&gt;&lt;th&gt;ocStore 3.0.5.x&lt;/th&gt;&lt;th&gt;CodeCart PRO 3.0.6.x&lt;/th&gt;&lt;/tr&gt;&lt;/thead&gt;
&lt;tbody&gt;
&lt;tr&gt;&lt;td&gt;PHP&lt;/td&gt;&lt;td&gt;PHP 8.0–8.4&lt;/td&gt;&lt;td&gt;PHP 8.0–8.5&lt;/td&gt;&lt;td&gt;&lt;strong&gt;PHP 8.1–8.5&lt;/strong&gt;&lt;/td&gt;&lt;/tr&gt;
&lt;tr&gt;&lt;td&gt;Extension architecture&lt;/td&gt;&lt;td&gt;MVC-L, OCMOD, Events&lt;/td&gt;&lt;td&gt;MVC-L, OCMOD, Events&lt;/td&gt;&lt;td&gt;&lt;strong&gt;Legacy + Modern Extensions, PSR-4, services, manifests&lt;/strong&gt;&lt;/td&gt;&lt;/tr&gt;
&lt;tr&gt;&lt;td&gt;System upgrades&lt;/td&gt;&lt;td&gt;Classic installer&lt;/td&gt;&lt;td&gt;Classic installer&lt;/td&gt;&lt;td&gt;&lt;strong&gt;Installer 2.0, preflight, controlled migration and idempotent reruns&lt;/strong&gt;&lt;/td&gt;&lt;/tr&gt;
&lt;tr&gt;&lt;td&gt;Checkout reliability&lt;/td&gt;&lt;td&gt;Standard flow&lt;/td&gt;&lt;td&gt;Standard flow&lt;/td&gt;&lt;td&gt;&lt;strong&gt;Transactions, locking, idempotency and duplicate-callback protection&lt;/strong&gt;&lt;/td&gt;&lt;/tr&gt;
&lt;tr&gt;&lt;td&gt;Queues and automation&lt;/td&gt;&lt;td&gt;Mainly extensions&lt;/td&gt;&lt;td&gt;Mainly extensions&lt;/td&gt;&lt;td&gt;&lt;strong&gt;Queue, Scheduler, CLI Worker and Cron layer&lt;/strong&gt;&lt;/td&gt;&lt;/tr&gt;
&lt;tr&gt;&lt;td&gt;Third-party theme compatibility&lt;/td&gt;&lt;td&gt;Native version compatibility&lt;/td&gt;&lt;td&gt;ocStore compatibility&lt;/td&gt;&lt;td&gt;&lt;strong&gt;Compatibility Framework and installable adapters&lt;/strong&gt;&lt;/td&gt;&lt;/tr&gt;
&lt;tr&gt;&lt;td&gt;SEO URLs&lt;/td&gt;&lt;td&gt;Standard SEO URLs&lt;/td&gt;&lt;td&gt;SEO URL + SeoPro&lt;/td&gt;&lt;td&gt;&lt;strong&gt;Standard SEO URL + SeoPro + dynamic language prefixes&lt;/strong&gt;&lt;/td&gt;&lt;/tr&gt;
&lt;tr&gt;&lt;td&gt;Images&lt;/td&gt;&lt;td&gt;Basic image processing&lt;/td&gt;&lt;td&gt;Basic image processing&lt;/td&gt;&lt;td&gt;&lt;strong&gt;WebP/AVIF-ready pipeline and modern image hooks&lt;/strong&gt;&lt;/td&gt;&lt;/tr&gt;
&lt;tr&gt;&lt;td&gt;Admin diagnostics&lt;/td&gt;&lt;td&gt;Classic&lt;/td&gt;&lt;td&gt;Classic&lt;/td&gt;&lt;td&gt;&lt;strong&gt;Core diagnostics, compatibility scanner, global search and system notices&lt;/strong&gt;&lt;/td&gt;&lt;/tr&gt;
&lt;tr&gt;&lt;td&gt;Security&lt;/td&gt;&lt;td&gt;Core mechanisms&lt;/td&gt;&lt;td&gt;Regional enhancements&lt;/td&gt;&lt;td&gt;&lt;strong&gt;Security headers, upload guard, rate limits, secret handling and critical-action checks&lt;/strong&gt;&lt;/td&gt;&lt;/tr&gt;
&lt;/tbody&gt;&lt;/table&gt;
&lt;/div&gt;

&lt;h3 id=&quot;commerce&quot;&gt;Commerce-first reliability&lt;/h3&gt;
&lt;p&gt;A store must create exactly one order, decrement stock exactly once and avoid applying coupons or payment callbacks twice. CodeCart hardens checkout-critical operations with transactions, locking, idempotency, stock restoration, voucher/coupon safety and technical logging.&lt;/p&gt;

&lt;h3 id=&quot;compatibility&quot;&gt;Compatibility without reverting the modernized core&lt;/h3&gt;
&lt;p&gt;CodeCart keeps ordinary OpenCart 3.x extension compatibility and adds a &lt;strong&gt;Compatibility Framework&lt;/strong&gt;. A theme that depends on older internal contracts can use an adapter rather than forcing legacy algorithms back into Core. UniShop2 is the first practical example.&lt;/p&gt;

&lt;h3 id=&quot;seo&quot;&gt;SEO URLs and multilingual routing&lt;/h3&gt;
&lt;p&gt;&lt;strong&gt;SEO URLs&lt;/strong&gt; provide the basic clean-address layer. &lt;strong&gt;SeoPro&lt;/strong&gt; is the advanced router for category paths, canonical rules, postfixes and additional URL policies. Language prefixes are dynamic: the primary language may use no prefix while additional languages can use arbitrary folders.&lt;/p&gt;

&lt;h3 id=&quot;performance&quot;&gt;Performance and storefront&lt;/h3&gt;
&lt;p&gt;CodeCart keeps OpenCart&#x27;s lightweight server model while improving common bottlenecks through batched category loading, controlled caching, lazy loading, modern image formats and scoped asset loading.&lt;/p&gt;

&lt;h3 id=&quot;security&quot;&gt;Security and control&lt;/h3&gt;
&lt;ul&gt;
&lt;li&gt;admin/AJAX/API permission and token validation;&lt;/li&gt;
&lt;li&gt;safe SQL handling and whitelisted dynamic identifiers;&lt;/li&gt;
&lt;li&gt;UploadGuard and file validation;&lt;/li&gt;
&lt;li&gt;rate limits for public endpoints;&lt;/li&gt;
&lt;li&gt;secret values are not rendered back into admin DOM;&lt;/li&gt;
&lt;li&gt;technical errors stay in protected logs instead of exposing server paths.&lt;/li&gt;
&lt;/ul&gt;

&lt;h3 id=&quot;extensions&quot;&gt;Modern Extensions&lt;/h3&gt;
&lt;p&gt;Modern Extension Registry allows installable packages with manifests, namespaces, permissions and compatibility adapters without repeatedly patching Core, while traditional OpenCart modules remain supported.&lt;/p&gt;

&lt;h3 id=&quot;result&quot;&gt;Who CodeCart is for&lt;/h3&gt;
&lt;p&gt;CodeCart is intended for stores that want to remain in the OpenCart 3.x ecosystem while gaining a more modern foundation for long-term operation. It is an evolutionary path rather than a disruptive rewrite.&lt;/p&gt;', meta_description='CodeCart PRO 3.0.6.0 is a modern OpenCart 3.x foundation with safer upgrades, Modern Extensions, SEO, compatibility adapters and reliable checkout.', meta_keyword='codecart, opencart, ocstore, ecommerce, seo, modern extensions', meta_title='CodeCart PRO 3.0.6.0 — a modern foundation for online stores', meta_h1='CodeCart PRO 3.0.6.0 — a modern foundation for online stores', tag='codecart, opencart, ocstore, ecommerce, seo' WHERE article_id='120' AND language_id='2'");
        }
        $this->db->query("UPDATE `" . DB_PREFIX . "setting` SET value=REPLACE(value,CONCAT('CodeCart PRO 3.0.6.0 ','Be','ta'),'CodeCart PRO 3.0.6.0') WHERE store_id='0' AND `key` IN ('config_meta_description','config_comment')");

        // Ukraine-first defaults are presentation data only; merchant UPDATEs are protected by the store-name guard.
        if ($this->tableExists('geo_zone') && $this->tableExists('zone_to_geo_zone')) {
            $this->db->query("UPDATE `" . DB_PREFIX . "geo_zone` SET name='Україна — ПДВ', description='Україна: зона оподаткування ПДВ', date_modified=NOW() WHERE geo_zone_id='3'");
            $this->db->query("UPDATE `" . DB_PREFIX . "geo_zone` SET name='Україна — доставка', description='Вся територія України для доставки', date_modified=NOW() WHERE geo_zone_id='4'");
            $this->db->query("DELETE FROM `" . DB_PREFIX . "zone_to_geo_zone` WHERE geo_zone_id IN (3,4)");
            $this->db->query("INSERT INTO `" . DB_PREFIX . "zone_to_geo_zone` SET country_id='220', zone_id='0', geo_zone_id='3', date_added=NOW(), date_modified=NOW()");
            $this->db->query("INSERT INTO `" . DB_PREFIX . "zone_to_geo_zone` SET country_id='220', zone_id='0', geo_zone_id='4', date_added=NOW(), date_modified=NOW()");
        }
        if ($this->tableExists('tax_class')) {
            $this->db->query("UPDATE `" . DB_PREFIX . "tax_class` SET title='Оподатковувані товари', description='Товари, що оподатковуються', date_modified=NOW() WHERE tax_class_id='9'");
            $this->db->query("UPDATE `" . DB_PREFIX . "tax_class` SET title='Цифрові товари', description='Цифрові та завантажувані товари', date_modified=NOW() WHERE tax_class_id='10'");
        }
        if ($this->tableExists('tax_rate')) {
            $this->db->query("UPDATE `" . DB_PREFIX . "tax_rate` SET name='ПДВ (20%)', date_modified=NOW() WHERE tax_rate_id='86'");
            if ($this->tableExists('tax_rule')) { $this->db->query("DELETE FROM `" . DB_PREFIX . "tax_rule` WHERE tax_rate_id='87'"); }
            $this->db->query("DELETE FROM `" . DB_PREFIX . "tax_rate` WHERE tax_rate_id='87'");
        }
        if ($this->tableExists('product')) {
            $this->db->query("UPDATE `" . DB_PREFIX . "product` SET date_modified='2026-09-01 12:00:00' WHERE product_id BETWEEN 28 AND 49 AND date_modified<'2026-01-01 00:00:00'");
            $this->db->query("UPDATE `" . DB_PREFIX . "product` SET tax_class_id='9' WHERE product_id='45' AND tax_class_id='100'");
            if ($this->tableExists('category')) { $this->db->query("UPDATE `" . DB_PREFIX . "category` SET date_modified='2026-09-01 12:00:00' WHERE date_modified<'2026-01-01 00:00:00'"); }
        }

        // Google Product Category mappings are demo presentation data. Apply them only
        // to CodeCart PRO Demo Store and only when the field is empty, so explicit admin values win.
        if ($this->tableExists('category')) {
            $columns = $this->columnMap('category');
            if (isset($columns['google_product_category_id'])) {
                $demoGoogleCategories = array(
                    17=>'313',18=>'328',20=>'325',24=>'267',25=>'285',26=>'325',27=>'325',28=>'305',29=>'304',
                    30=>'500106',31=>'306',32=>'312',33=>'152',34=>'233',35=>'305',36=>'305',43=>'222',44=>'232',
                    45=>'328',46=>'328',53=>'232',54=>'232',57=>'4745',58=>'4745'
                );
                foreach ($demoGoogleCategories as $categoryId => $googleCategoryId) {
                    $this->db->query("UPDATE `" . DB_PREFIX . "category` SET google_product_category_id='" . $this->db->escape($googleCategoryId) . "' WHERE category_id='" . (int)$categoryId . "' AND google_product_category_id=''");
                }
            }
        }

        // A dedicated Specials layout makes the demo layout list complete and predictable.
        $specialLayoutId = 0;
        if ($this->tableExists('layout')) {
            $special = $this->db->query("SELECT layout_id FROM `" . DB_PREFIX . "layout` WHERE name='Акції' LIMIT 1");
            if ($special->num_rows) { $specialLayoutId = (int)$special->row['layout_id']; }
            else { $this->db->query("INSERT INTO `" . DB_PREFIX . "layout` SET name='Акції'"); $specialLayoutId = (int)$this->db->getLastId(); }
            if ($specialLayoutId > 0) {
                $route = $this->db->query("SELECT layout_route_id FROM `" . DB_PREFIX . "layout_route` WHERE store_id='0' AND route='product/special' LIMIT 1");
                if (!$route->num_rows) { $this->db->query("INSERT INTO `" . DB_PREFIX . "layout_route` SET layout_id='" . $specialLayoutId . "', store_id='0', route='product/special'"); }
            }
        }

        // Reusable demo forms. Existing merchant forms are never touched because this branch only runs for CodeCart PRO Demo Store.
        $formIds = array();
        if ($this->tableExists('codecart_form') && $this->tableExists('codecart_form_description')) {
            $demoForms = array(
                'Зворотний дзвінок' => array(
                    1 => array('title'=>'Замовити зворотний дзвінок','description'=>'<p>Залиште контакт — менеджер зв’язується для консультації. Це демонстраційна форма CodeCart.</p>','submit'=>'Надіслати','success'=>'Дякуємо! Запит прийнято. Менеджер зв’яжеться з вами.','fields'=>array(array('key'=>'name','type'=>'text','label'=>'Ваше ім’я','placeholder'=>'Ім’я','required'=>1),array('key'=>'phone','type'=>'tel','label'=>'Телефон','placeholder'=>'+380…','required'=>1),array('key'=>'time','type'=>'select','label'=>'Зручний час','required'=>0,'options'=>array('Якнайшвидше','09:00–12:00','12:00–15:00','15:00–18:00')),array('key'=>'comment','type'=>'textarea','label'=>'Коментар','placeholder'=>'Що потрібно уточнити?','required'=>0))),
                    2 => array('title'=>'Request a callback','description'=>'<p>Leave your contact details and a manager can call you back. This is a CodeCart PRO demo form.</p>','submit'=>'Send request','success'=>'Thank you. Your request has been received.','fields'=>array(array('key'=>'name','type'=>'text','label'=>'Your name','placeholder'=>'Name','required'=>1),array('key'=>'phone','type'=>'tel','label'=>'Phone','placeholder'=>'+380…','required'=>1),array('key'=>'time','type'=>'select','label'=>'Preferred time','required'=>0,'options'=>array('As soon as possible','09:00–12:00','12:00–15:00','15:00–18:00')),array('key'=>'comment','type'=>'textarea','label'=>'Comment','placeholder'=>'How can we help?','required'=>0)))
                ),
                'Поставити питання' => array(
                    1 => array('title'=>'Поставити питання про товар','description'=>'<p>Приклад консультації прямо зі сторінки товару.</p>','submit'=>'Поставити питання','success'=>'Повідомлення надіслано. Дякуємо за звернення.','fields'=>array(array('key'=>'name','type'=>'text','label'=>'Ваше ім’я','required'=>1),array('key'=>'email','type'=>'email','label'=>'E-mail','required'=>1),array('key'=>'question','type'=>'textarea','label'=>'Питання','required'=>1),array('key'=>'consent','type'=>'checkbox','label'=>'Погоджуюся на обробку даних','required'=>1))),
                    2 => array('title'=>'Ask a product question','description'=>'<p>Demonstrates consultation directly on the product page.</p>','submit'=>'Ask a question','success'=>'Your message has been sent. Thank you.','fields'=>array(array('key'=>'name','type'=>'text','label'=>'Your name','required'=>1),array('key'=>'email','type'=>'email','label'=>'E-mail','required'=>1),array('key'=>'question','type'=>'textarea','label'=>'Question','required'=>1),array('key'=>'consent','type'=>'checkbox','label'=>'I agree to data processing','required'=>1)))
                ),
                'Допомога з підбором' => array(
                    1 => array('title'=>'Допомога з підбором товару','description'=>'<p>Опишіть задачу й бюджет — форма демонструє консультацію в категорії.</p>','submit'=>'Отримати консультацію','success'=>'Запит прийнято. Ми підготуємо рекомендацію.','fields'=>array(array('key'=>'name','type'=>'text','label'=>'Ваше ім’я','required'=>1),array('key'=>'contact','type'=>'text','label'=>'Телефон або E-mail','required'=>1),array('key'=>'budget','type'=>'select','label'=>'Бюджет','required'=>0,'options'=>array('До 5 000 ₴','5 000–15 000 ₴','15 000–30 000 ₴','Понад 30 000 ₴')),array('key'=>'needs','type'=>'textarea','label'=>'Що потрібно підібрати','required'=>1))),
                    2 => array('title'=>'Product selection help','description'=>'<p>Describe the task and budget to demonstrate category-level consultation.</p>','submit'=>'Get advice','success'=>'Request received. We will prepare a recommendation.','fields'=>array(array('key'=>'name','type'=>'text','label'=>'Your name','required'=>1),array('key'=>'contact','type'=>'text','label'=>'Phone or e-mail','required'=>1),array('key'=>'budget','type'=>'select','label'=>'Budget','required'=>0,'options'=>array('Up to 5,000 UAH','5,000–15,000 UAH','15,000–30,000 UAH','Over 30,000 UAH')),array('key'=>'needs','type'=>'textarea','label'=>'What do you need?','required'=>1)))
                )
            );
            foreach ($demoForms as $formName => $descriptions) {
                $fq = $this->db->query("SELECT form_id FROM `" . DB_PREFIX . "codecart_form` WHERE name='" . $this->db->escape($formName) . "' LIMIT 1");
                if ($fq->num_rows) { $formId = (int)$fq->row['form_id']; }
                else { $this->db->query("INSERT INTO `" . DB_PREFIX . "codecart_form` SET name='" . $this->db->escape($formName) . "', kind='request', recipient='info@codecartpro.com', status='1', date_added=NOW(), date_modified=NOW()"); $formId = (int)$this->db->getLastId(); }
                $formIds[$formName] = $formId;
                foreach ($descriptions as $languageId => $row) {
                    $fields = json_encode(\CodeCart\Core\FormBuilder::sanitizeFields($row['fields']), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    $this->db->query("REPLACE INTO `" . DB_PREFIX . "codecart_form_description` SET form_id='" . $formId . "', language_id='" . (int)$languageId . "', title='" . $this->db->escape($row['title']) . "', description='" . $this->db->escape($row['description']) . "', submit_text='" . $this->db->escape($row['submit']) . "', success_text='" . $this->db->escape($row['success']) . "', fields='" . $this->db->escape($fields) . "'");
                }
            }
        }

        if ($formIds && $this->tableExists('module')) {
            $moduleSpecs = array(
                array('name'=>'Форма — зворотний дзвінок','form'=>'Зворотний дзвінок','mode'=>'inline','buttons'=>array(1=>'Замовити дзвінок',2=>'Request callback'),'layout'=>8,'position'=>'content_bottom','sort'=>0),
                array('name'=>'Форма — питання про товар','form'=>'Поставити питання','mode'=>'button','buttons'=>array(1=>'Поставити питання',2=>'Ask a question'),'layout'=>2,'position'=>'content_bottom','sort'=>3,'direct_product'=>1),
                array('name'=>'Форма — допомога з підбором','form'=>'Допомога з підбором','mode'=>'button','buttons'=>array(1=>'Допомога з підбором',2=>'Selection help'),'layout'=>3,'position'=>'content_bottom','sort'=>2)
            );
            foreach ($moduleSpecs as $spec) {
                if (empty($formIds[$spec['form']])) { continue; }
                $mq = $this->db->query("SELECT module_id FROM `" . DB_PREFIX . "module` WHERE code='codecart_form' AND name='" . $this->db->escape($spec['name']) . "' LIMIT 1");
                $setting = json_encode(array('name'=>$spec['name'],'form_id'=>(int)$formIds[$spec['form']],'mode'=>$spec['mode'],'button_text'=>$spec['buttons'],'status'=>1), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                if ($mq->num_rows) { $moduleId=(int)$mq->row['module_id']; $this->db->query("UPDATE `" . DB_PREFIX . "module` SET setting='" . $this->db->escape($setting) . "' WHERE module_id='" . $moduleId . "'"); }
                else { $this->db->query("INSERT INTO `" . DB_PREFIX . "module` SET name='" . $this->db->escape($spec['name']) . "', code='codecart_form', setting='" . $this->db->escape($setting) . "'"); $moduleId=(int)$this->db->getLastId(); }
                $code='codecart_form.' . $moduleId;
                if (!empty($spec['direct_product'])) {
                    // Product-question form is rendered next to the purchase actions by product/product.
                    // Remove the legacy content_bottom assignment to prevent a duplicate near the footer.
                    $this->db->query("DELETE FROM `" . DB_PREFIX . "layout_module` WHERE code='" . $this->db->escape($code) . "' AND layout_id='" . (int)$spec['layout'] . "'");
                } else {
                    $lm=$this->db->query("SELECT layout_module_id FROM `" . DB_PREFIX . "layout_module` WHERE layout_id='".(int)$spec['layout']."' AND code='".$this->db->escape($code)."' AND position='".$this->db->escape($spec['position'])."' LIMIT 1");
                    if (!$lm->num_rows) { $this->db->query("INSERT INTO `" . DB_PREFIX . "layout_module` SET layout_id='".(int)$spec['layout']."', code='".$this->db->escape($code)."', position='".$this->db->escape($spec['position'])."', sort_order='".(int)$spec['sort']."'"); }
                }
            }
        }
        if ($specialLayoutId > 0) {
            foreach (array(array('featured_product.35',0),array('featured_article.34',1)) as $spec) {
                $lm=$this->db->query("SELECT layout_module_id FROM `" . DB_PREFIX . "layout_module` WHERE layout_id='".$specialLayoutId."' AND code='".$this->db->escape($spec[0])."' AND position='content_bottom' LIMIT 1");
                if (!$lm->num_rows) { $this->db->query("INSERT INTO `" . DB_PREFIX . "layout_module` SET layout_id='".$specialLayoutId."', code='".$this->db->escape($spec[0])."', position='content_bottom', sort_order='".(int)$spec[1]."'"); }
            }
        }

        // Presentation schema v5: keep the bundled demo aligned with current mobile/layout defaults.
        if ($this->tableExists('setting')) {
            $mapUrl = 'https://www.google.com/maps?q=50.4501%2C30.5234&z=15&output=embed';
            $mapRow = $this->db->query("SELECT setting_id FROM `" . DB_PREFIX . "setting` WHERE store_id='0' AND `key`='config_map_url' LIMIT 1");
            if ($mapRow->num_rows) {
                $this->db->query("UPDATE `" . DB_PREFIX . "setting` SET code='config', value='" . $this->db->escape($mapUrl) . "', serialized='0' WHERE setting_id='" . (int)$mapRow->row['setting_id'] . "'");
            } else {
                $this->db->query("INSERT INTO `" . DB_PREFIX . "setting` SET store_id='0', code='config', `key`='config_map_url', value='" . $this->db->escape($mapUrl) . "', serialized='0'");
            }
            foreach (array('theme_default_accent_color','theme_default_button_color','theme_default_buy_button_color','theme_default_cart_button_color') as $colorKey) {
                $this->db->query("UPDATE `" . DB_PREFIX . "setting` SET value='#0B6FD3' WHERE store_id='0' AND `key`='" . $this->db->escape($colorKey) . "'");
            }
            foreach (array('theme_default_accent_hover','theme_default_button_hover','theme_default_buy_button_hover','theme_default_cart_button_hover') as $hoverKey) {
                $this->db->query("UPDATE `" . DB_PREFIX . "setting` SET value='#095EB4' WHERE store_id='0' AND `key`='" . $this->db->escape($hoverKey) . "'");
            }
            // The bundled demo should open the native DateTimePicker in the light theme by default.
            $this->db->query("UPDATE `" . DB_PREFIX . "setting` SET value='light', serialized='0' WHERE store_id='0' AND `key`='theme_default_datetimepicker_theme' AND value IN ('system','')");
            $legacyReleaseLabel = 'CodeCart PRO 3.0.6.0 ' . 'Be' . 'ta';
            $this->db->query("UPDATE `" . DB_PREFIX . "setting` SET value=REPLACE(value, '" . $this->db->escape($legacyReleaseLabel) . "', 'CodeCart PRO 3.0.6.0') WHERE store_id='0' AND code='config' AND `key` IN ('config_comment','config_meta_title','config_meta_description')");
        }
        if ($this->tableExists('module')) {
            $mods = $this->db->query("SELECT module_id, name, setting FROM `" . DB_PREFIX . "module` WHERE name IN ('Головна сторінка','Останні статті','Рекомендовані статті','Рекомендовані статті у товарі, категорії та виробнику','Рекомендовані товари в категорії та виробники','Головна — категорії','Головна — виробники','Головна — нові товари','Головна — популярні товари','Категорія — підкатегорії','Товар — нещодавно переглянуті')");
            foreach ($mods->rows as $mod) {
                $settings = json_decode((string)$mod['setting'], true);
                if (!is_array($settings)) { continue; }
                // CodeCart PRO demo defaults use carousel for recommendation/latest/popular/content blocks.
                // The category-page subcategory selector is intentionally a compact static grid.
                // This branch is guarded by the exact demo store name above, so merchant module choices are not overwritten.
                if ((string)$mod['name'] === 'Категорія — підкатегорії') {
                    $settings['display_mode'] = 'grid';
                    $settings['display_mode_mobile'] = 'inherit';
                    $settings['columns_desktop'] = '6';
                    $settings['columns_tablet'] = '3';
                    $settings['columns_mobile'] = '2';
                    $settings['mobile_peek'] = '0';
                    $settings['autoplay'] = '0';
                    $settings['show_arrows'] = '0';
                    $settings['show_dots'] = '0';
                    $settings['loop'] = '0';
                    $settings['width'] = '320';
                    $settings['height'] = '220';
                    $settings['width_mobile'] = '220';
                    $settings['height_mobile'] = '150';
                    $settings['image_fit'] = 'contain';
                } else {
                    $settings['display_mode'] = 'carousel';
                    $settings['columns_mobile'] = isset($settings['columns_mobile']) ? (string)$settings['columns_mobile'] : '2';
                    if ((string)$mod['name'] === 'Головна — категорії') { $settings['mobile_peek'] = '0'; }
                }
                $this->db->query("UPDATE `" . DB_PREFIX . "module` SET setting='" . $this->db->escape(json_encode($settings, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) . "' WHERE module_id='" . (int)$mod['module_id'] . "'");
            }
        }
        if ($this->tableExists('layout_module')) {
            $this->db->query("UPDATE `" . DB_PREFIX . "layout_module` SET sort_order='1' WHERE layout_id='1' AND code='category_wall.37' AND position='content_top'");
            $this->db->query("UPDATE `" . DB_PREFIX . "layout_module` SET sort_order='2' WHERE layout_id='1' AND code='html.36' AND position='content_top'");
        }

        if (!$this->tableExists('codecart_purchase_block')) { $this->setPresentationSchemaMarker(); return; }
        $existing = $this->db->query("SELECT owner_id FROM `" . DB_PREFIX . "codecart_purchase_block` WHERE owner_type='product' AND owner_id='42' AND language_id>0 AND data<>'[]' LIMIT 1");
        $seedPurchaseBlocks = !$existing->num_rows;

        $sets = array(
            array('category',18,1,array(array('type'=>'info','title'=>'Доставка ноутбуків по Україні','content'=>'<p><strong>🚚 Швидка доставка по Україні.</strong> У реальному магазині тут можна показати службу доставки, терміни, страхування та умови отримання.</p><p>Цей блок задано на рівні категорії та автоматично успадковується товарами — так демонструється повторне використання контенту без дублювання.</p>'))),
            array('category',18,2,array(array('type'=>'info','title'=>'Laptop delivery across Ukraine','content'=>'<p><strong>🚚 Fast delivery across Ukraine.</strong> A live store can use this block for carriers, delivery times, insurance and collection terms.</p><p>This block is configured at category level and inherited by products, demonstrating reusable content without duplication.</p>'))),
            array('product',40,1,array(array('type'=>'info','title'=>'Доставка та оплата','content'=>'<p><strong>🚚 Нова пошта / кур’єр / самовивіз.</strong> Відправлення після підтвердження замовлення. 💳 Оплата карткою, переказом або при отриманні.</p>'),array('type'=>'colors','title'=>'Варіанти кольору','items'=>array(array('label'=>'Graphite','value'=>'#374151'),array('label'=>'Blue','value'=>'#2563eb'),array('label'=>'Silver','value'=>'#d1d5db'))))),
            array('product',40,2,array(array('type'=>'info','title'=>'Delivery and payment','content'=>'<p><strong>🚚 Nova Poshta / courier / pickup.</strong> Dispatch after order confirmation. 💳 Card, bank transfer or payment on delivery.</p>'),array('type'=>'colors','title'=>'Color options','items'=>array(array('label'=>'Graphite','value'=>'#374151'),array('label'=>'Blue','value'=>'#2563eb'),array('label'=>'Silver','value'=>'#d1d5db'))))),
            array('product',42,1,array(array('type'=>'info','title'=>'Інформаційний блок','content'=>'<p><strong>ℹ️ Приклад контентного блока.</strong> Його можна використовувати для доставки, гарантії, важливих попереджень, інструкцій або будь-якої інформації без створення окремої вкладки.</p>'),array('type'=>'size_table','title'=>'Порівняння конфігурацій','columns'=>array('Варіант','Комплектація','Гарантія'),'rows'=>array(array('Standard','Монітор + кабель','12 міс.'),array('Studio','Монітор + кабель + адаптер','24 міс.'),array('Business','2 монітори + комплект кабелів','24 міс.'))),array('type'=>'sizes','title'=>'Готові комплекти','items'=>array(array('label'=>'Standard','value'=>'1 шт.'),array('label'=>'Studio','value'=>'1 + аксесуари'),array('label'=>'Business','value'=>'2 шт.'))),array('type'=>'colors','title'=>'Кольорові варіанти','items'=>array(array('label'=>'Silver','value'=>'#d1d5db'),array('label'=>'Space Gray','value'=>'#4b5563'),array('label'=>'White','value'=>'#f3f4f6'))))),
            array('product',42,2,array(array('type'=>'info','title'=>'Information block','content'=>'<p><strong>ℹ️ Content block example.</strong> It can be used for delivery, warranty, warnings, instructions or any useful purchase information without creating a separate tab.</p>'),array('type'=>'size_table','title'=>'Configuration comparison','columns'=>array('Option','Package','Warranty'),'rows'=>array(array('Standard','Display + cable','12 mo.'),array('Studio','Display + cable + adapter','24 mo.'),array('Business','2 displays + cable set','24 mo.'))),array('type'=>'sizes','title'=>'Ready packages','items'=>array(array('label'=>'Standard','value'=>'1 pc.'),array('label'=>'Studio','value'=>'1 + accessories'),array('label'=>'Business','value'=>'2 pcs.'))),array('type'=>'colors','title'=>'Color options','items'=>array(array('label'=>'Silver','value'=>'#d1d5db'),array('label'=>'Space Gray','value'=>'#4b5563'),array('label'=>'White','value'=>'#f3f4f6'))))),
            array('product',43,1,array(array('type'=>'info','title'=>'Оплата, гарантія та сервіс','content'=>'<p>✅ Гарантія, консультація перед покупкою та післяпродажна підтримка можуть бути показані прямо біля кнопки покупки. Для реального магазину текст задається окремо для кожної мови.</p>'),array('type'=>'size_table','title'=>'Приклад конфігурацій','columns'=>array('Конфігурація','Пам’ять','Призначення'),'rows'=>array(array('Base','8 GB','Навчання та офіс'),array('Pro','16 GB','Робота та контент'),array('Business','32 GB','Професійні задачі'))))),
            array('product',43,2,array(array('type'=>'info','title'=>'Payment, warranty and service','content'=>'<p>✅ Warranty, pre-sale consultation and after-sales support can be shown directly near the purchase controls. A live store can provide separate text per language.</p>'),array('type'=>'size_table','title'=>'Configuration example','columns'=>array('Configuration','Memory','Use case'),'rows'=>array(array('Base','8 GB','Study and office'),array('Pro','16 GB','Work and content'),array('Business','32 GB','Professional tasks'))))),
            array('product',49,1,array(array('type'=>'info','title'=>'Для бізнесу та навчання','content'=>'<p>📱 Приклад тематичного блока для сценарію використання. Тут можна показувати переваги, комплект поставки, сумісність, сервіс або коротку інструкцію.</p>'))),
            array('product',49,2,array(array('type'=>'info','title'=>'For business and education','content'=>'<p>📱 Example of a use-case information block. It can show benefits, package contents, compatibility, service terms or a short guide.</p>')))
        );

        $markers = array();
        if ($seedPurchaseBlocks) foreach ($sets as $set) {
            $ownerType = (string)$set[0];
            $ownerId = (int)$set[1];
            $languageId = (int)$set[2];
            $markerKey = $ownerType . ':' . $ownerId;
            if (!isset($markers[$markerKey])) {
                $this->db->query("REPLACE INTO `" . DB_PREFIX . "codecart_purchase_block` SET owner_type='" . $this->db->escape($ownerType) . "', owner_id='" . $ownerId . "', language_id='0', mode='1', data='[]'");
                $markers[$markerKey] = true;
            }
            $encoded = json_encode(\CodeCart\Core\PurchaseBlocks::sanitize($set[3]), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $this->db->query("REPLACE INTO `" . DB_PREFIX . "codecart_purchase_block` SET owner_type='" . $this->db->escape($ownerType) . "', owner_id='" . $ownerId . "', language_id='" . $languageId . "', mode='1', data='" . $this->db->escape($encoded) . "'");
        }
        $this->setPresentationSchemaMarker();
    }

    private function setPresentationSchemaMarker() {
        if (!$this->tableExists('setting')) { return; }
        $row = $this->db->query("SELECT setting_id FROM `" . DB_PREFIX . "setting` WHERE store_id='0' AND `key`='codecart_presentation_schema_version' LIMIT 1");
        if ($row->num_rows) {
            $this->db->query("UPDATE `" . DB_PREFIX . "setting` SET code='codecart_core', value='" . self::PRESENTATION_SCHEMA_VERSION . "', serialized='0' WHERE setting_id='" . (int)$row->row['setting_id'] . "'");
        } else {
            $this->db->query("INSERT INTO `" . DB_PREFIX . "setting` SET store_id='0', code='codecart_core', `key`='codecart_presentation_schema_version', value='" . self::PRESENTATION_SCHEMA_VERSION . "', serialized='0'");
        }
        $this->config->set('codecart_presentation_schema_version', self::PRESENTATION_SCHEMA_VERSION);
    }

    private function enableConfiguredPurchaseBlocksFromRc88() {
        if ((string)$this->config->get('codecart_core_schema_version') !== '1.1.88' || !$this->tableExists('codecart_purchase_block')) { return; }
        $status = $this->db->query("SELECT setting_id, value FROM `" . DB_PREFIX . "setting` WHERE store_id='0' AND `key`='theme_default_purchase_blocks_status' LIMIT 1");
        if (!$status->num_rows || (int)$status->row['value'] !== 0) { return; }
        $configured = $this->db->query("SELECT owner_id FROM `" . DB_PREFIX . "codecart_purchase_block` WHERE mode='1' AND language_id>0 AND data<>'[]' LIMIT 1");
        if (!$configured->num_rows) { return; }
        $this->db->query("UPDATE `" . DB_PREFIX . "setting` SET value='1' WHERE setting_id='" . (int)$status->row['setting_id'] . "'");
        $this->config->set('theme_default_purchase_blocks_status', '1');
    }

    private function localizeBundledUkrainianDefaults() {
        if (!$this->tableExists('language')) { return; }
        $language = $this->db->query("SELECT language_id FROM `" . DB_PREFIX . "language` WHERE LOWER(code) IN ('uk-ua','uk') ORDER BY language_id LIMIT 1");
        if (!$language->num_rows) { return; }
        $languageId = (int)$language->row['language_id'];

        // Only exact stock/demo values are touched. Merchant-created names are preserved.
        // Banner names are global (not language-specific), so translate bundled English names
        // only when the existing store itself uses Ukrainian as its primary catalog language.
        $primaryLanguage = '';
        if ($this->tableExists('setting')) {
            $primary = $this->db->query("SELECT value FROM `" . DB_PREFIX . "setting` WHERE store_id='0' AND code='config' AND `key`='config_language' LIMIT 1");
            if ($primary->num_rows) { $primaryLanguage = strtolower(trim((string)$primary->row['value'])); }
        }
        if (in_array($primaryLanguage, array('uk-ua','uk'), true) && $this->tableExists('module')) {
            $moduleNames = array(
                'Category' => 'Категорія',
                'Home Page' => 'Головна сторінка',
                'Banner 1' => 'Банер 1'
            );
            $modules = $this->db->query("SELECT module_id, name, setting FROM `" . DB_PREFIX . "module` WHERE name IN ('Category','Home Page','Banner 1')");
            foreach ($modules->rows as $module) {
                $from = (string)$module['name'];
                if (!isset($moduleNames[$from])) { continue; }
                $setting = json_decode((string)$module['setting'], true);
                if (is_array($setting) && isset($setting['name']) && (string)$setting['name'] === $from) {
                    $setting['name'] = $moduleNames[$from];
                    $encoded = json_encode($setting, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                } else { $encoded = (string)$module['setting']; }
                $this->db->query("UPDATE `" . DB_PREFIX . "module` SET name='" . $this->db->escape($moduleNames[$from]) . "', setting='" . $this->db->escape($encoded) . "' WHERE module_id='" . (int)$module['module_id'] . "'");
            }
        }

        if (in_array($primaryLanguage, array('uk-ua','uk'), true) && $this->tableExists('banner')) {
            $bannerNames = array(
                'HP Products' => 'Товари HP',
                'Home Page Slideshow' => 'Слайдшоу головної сторінки',
                'Manufacturers' => 'Виробники'
            );
            foreach ($bannerNames as $from => $to) {
                $this->db->query("UPDATE `" . DB_PREFIX . "banner` SET name='" . $this->db->escape($to) . "' WHERE name='" . $this->db->escape($from) . "'");
            }
        }

        if ($this->tableExists('customer_group_description')) {
            $this->db->query("UPDATE `" . DB_PREFIX . "customer_group_description` SET name='За замовчуванням' WHERE language_id='" . $languageId . "' AND name='Default'");
        }

        if ($this->tableExists('attribute_group_description')) {
            $map = array('Memory'=>'Пам\'ять','Technical'=>'Технічні характеристики','Motherboard'=>'Материнська плата','Processor'=>'Процесор');
            foreach ($map as $from=>$to) {
                $this->db->query("UPDATE `" . DB_PREFIX . "attribute_group_description` SET name='" . $this->db->escape($to) . "' WHERE language_id='" . $languageId . "' AND name='" . $this->db->escape($from) . "'");
            }
        }

        if ($this->tableExists('attribute_description')) {
            $map = array('Description'=>'Опис','No. of Cores'=>'Кількість ядер','Clockspeed'=>'Тактова частота');
            foreach ($map as $from=>$to) {
                $this->db->query("UPDATE `" . DB_PREFIX . "attribute_description` SET name='" . $this->db->escape($to) . "' WHERE language_id='" . $languageId . "' AND name='" . $this->db->escape($from) . "'");
            }
            $this->db->query("UPDATE `" . DB_PREFIX . "attribute_description` SET name=CONCAT('Тест ', SUBSTRING(name, 6)) WHERE language_id='" . $languageId . "' AND name REGEXP '^test [0-9]+$'");
        }

        if ($this->tableExists('category_description')) {
            $this->db->query("UPDATE `" . DB_PREFIX . "category_description` SET name='Принтери' WHERE language_id='" . $languageId . "' AND name='Сканери' AND category_id='30'");
            $this->db->query("UPDATE `" . DB_PREFIX . "category_description` SET name='Сканери' WHERE language_id='" . $languageId . "' AND name='Сканеры'");
            $this->db->query("UPDATE `" . DB_PREFIX . "category_description` SET name='MP3-плеєри' WHERE language_id='" . $languageId . "' AND name='MP3 Плеери'");
            $this->db->query("UPDATE `" . DB_PREFIX . "category_description` SET name=CONCAT('Тест ', SUBSTRING(name, 6)) WHERE language_id='" . $languageId . "' AND name REGEXP '^test [0-9]+$'");
        }
    }

    private function ensureTaxDisplayDefault() {
        $query = $this->db->query("SELECT setting_id FROM `" . DB_PREFIX . "setting` WHERE store_id = '0' AND code = 'config' AND `key` = 'config_tax_display' LIMIT 1");
        if (!$query->num_rows) {
            $this->db->query("INSERT INTO `" . DB_PREFIX . "setting` SET store_id = '0', code = 'config', `key` = 'config_tax_display', value = 'native', serialized = '0'");
        }
    }

    private function ensureCurrencyDisplayDefaults() {
        $query = $this->db->query("SELECT setting_id FROM `" . DB_PREFIX . "setting` WHERE store_id = '0' AND code = 'config' AND `key` = 'config_currency_trim_zeros' LIMIT 1");
        if (!$query->num_rows) {
            $this->db->query("INSERT INTO `" . DB_PREFIX . "setting` SET store_id = '0', code = 'config', `key` = 'config_currency_trim_zeros', value = '0', serialized = '0'");
        }
    }

    private function ensureManufacturerImageDefaults() {
        // UPDATE must not change an existing storefront image geometry.
        // Clean-install SQL owns the modern 160x160 default; upgrades preserve merchant values.
        return;
    }


    private function ensureProductTaxDisplayMode() {
        if (!$this->tableExists('product')) { return; }
        $columns = $this->columnMap('product');
        if (!isset($columns['tax_display_mode'])) {
            $this->db->query("ALTER TABLE `" . DB_PREFIX . "product` ADD `tax_display_mode` VARCHAR(16) NOT NULL DEFAULT 'inherit' AFTER `tax_class_id`");
        }
    }


    private function ensurePurchaseBlockTable() {
        $this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "codecart_purchase_block` (
            `owner_type` varchar(8) NOT NULL,
            `owner_id` int(11) NOT NULL,
            `language_id` int(11) NOT NULL DEFAULT '0',
            `mode` tinyint(1) NOT NULL DEFAULT '1',
            `data` mediumtext NOT NULL,
            PRIMARY KEY (`owner_type`,`owner_id`,`language_id`),
            KEY `owner_lookup` (`owner_type`,`owner_id`),
            KEY `language_id` (`language_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    private function ensureFormBuilderTables() {
        $this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "codecart_form` (
            `form_id` int(11) NOT NULL AUTO_INCREMENT,
            `name` varchar(128) NOT NULL DEFAULT '',
            `kind` varchar(16) NOT NULL DEFAULT 'request',
            `recipient` varchar(255) NOT NULL DEFAULT '',
            `button_icon` varchar(64) NOT NULL DEFAULT 'fa-envelope-o',
            `button_bg` varchar(7) NOT NULL DEFAULT '#0b6fd3',
            `button_text_color` varchar(7) NOT NULL DEFAULT '#ffffff',
            `button_hover_bg` varchar(7) NOT NULL DEFAULT '#095eb4',
            `status` tinyint(1) NOT NULL DEFAULT '1',
            `date_added` datetime NOT NULL,
            `date_modified` datetime NOT NULL,
            PRIMARY KEY (`form_id`),
            KEY `status_name` (`status`,`name`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $kind = $this->db->query("SHOW COLUMNS FROM `" . DB_PREFIX . "codecart_form` LIKE 'kind'");
        if (!$kind->num_rows) {
            $this->db->query("ALTER TABLE `" . DB_PREFIX . "codecart_form` ADD `kind` varchar(16) NOT NULL DEFAULT 'request' AFTER `name`");
        }
        foreach (array(
            'button_icon' => "varchar(64) NOT NULL DEFAULT 'fa-envelope-o' AFTER `recipient`",
            'button_bg' => "varchar(7) NOT NULL DEFAULT '#0b6fd3' AFTER `button_icon`",
            'button_text_color' => "varchar(7) NOT NULL DEFAULT '#ffffff' AFTER `button_bg`",
            'button_hover_bg' => "varchar(7) NOT NULL DEFAULT '#095eb4' AFTER `button_text_color`"
        ) as $column => $definition) {
            $check = $this->db->query("SHOW COLUMNS FROM `" . DB_PREFIX . "codecart_form` LIKE '" . $this->db->escape($column) . "'");
            if (!$check->num_rows) { $this->db->query("ALTER TABLE `" . DB_PREFIX . "codecart_form` ADD `" . $column . "` " . $definition); }
        }
        $this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "codecart_form_description` (
            `form_id` int(11) NOT NULL,
            `language_id` int(11) NOT NULL,
            `title` varchar(160) NOT NULL DEFAULT '',
            `description` text NOT NULL,
            `submit_text` varchar(80) NOT NULL DEFAULT '',
            `success_text` varchar(255) NOT NULL DEFAULT '',
            `fields` mediumtext NOT NULL,
            PRIMARY KEY (`form_id`,`language_id`),
            KEY `language_id` (`language_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    private function ensureProductExtraTabTable() {
        $this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "product_extra_tab` (
            `product_id` int(11) NOT NULL,
            `language_id` int(11) NOT NULL,
            `mode` tinyint(1) NOT NULL DEFAULT '0',
            `title` varchar(128) NOT NULL DEFAULT '',
            `content` mediumtext NOT NULL,
            PRIMARY KEY (`product_id`,`language_id`),
            KEY `language_id` (`language_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    private function ensureAdaptiveCaptchaDefaults() {
        if (!$this->tableExists('setting')) { return; }
        $defaults = array(
            'captcha_basic_mode' => 'adaptive',
            'captcha_basic_min_age_ms' => '900'
        );
        foreach ($defaults as $key => $value) {
            $query = $this->db->query("SELECT setting_id FROM `" . DB_PREFIX . "setting` WHERE store_id='0' AND code='captcha_basic' AND `key`='" . $this->db->escape($key) . "' LIMIT 1");
            if (!$query->num_rows) {
                $this->db->query("INSERT INTO `" . DB_PREFIX . "setting` SET store_id='0', code='captcha_basic', `key`='" . $this->db->escape($key) . "', `value`='" . $this->db->escape($value) . "', serialized='0'");
                $this->config->set($key, $value);
            }
        }
    }

    private function invalidateModificationCacheMarker($reason = 'core_updated') {
        if (!defined('DIR_MODIFICATION')) {
            return;
        }

        $marker = rtrim((string)DIR_MODIFICATION, '/\\') . DIRECTORY_SEPARATOR . '.codecart-build';
        if (is_file($marker) && !@unlink($marker) && $this->log) {
            $this->log->write('CodeCart PRO update: unable to invalidate the OCMOD build marker. Refresh modifications manually before reopening production traffic.');
        }

        try {
            if (class_exists('\\CodeCart\\Core\\OcmodState')) {
                \CodeCart\Core\OcmodState::markDirty((string)$reason, array('package_build' => defined('CODECART_PACKAGE_BUILD') ? (string)CODECART_PACKAGE_BUILD : ''));
            }
        } catch (\Throwable $e) {
            // Repair itself must not fail because the advisory OCMOD state marker cannot be written.
        }
    }

    private function clearTemplateCache() {
        if (!defined('DIR_CACHE')) {
            return;
        }

        $root = rtrim((string)DIR_CACHE, '/\\') . DIRECTORY_SEPARATOR . 'template';

        if (!is_dir($root)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $item) {
            $path = $item->getPathname();
            if ($item->isDir()) {
                @rmdir($path);
            } elseif ($item->isFile()) {
                @unlink($path);
            }
        }
    }

    private function ensureSettingSerializedDefault() {
        if ((string)$this->config->get('codecart_setting_serialized_default') === '1') {
            return;
        }
        if (!$this->tableExists('setting') || !$this->columnExists('setting', 'serialized')) {
            return;
        }

        $query = $this->db->query("SHOW COLUMNS FROM `" . DB_PREFIX . "setting` LIKE 'serialized'");
        if (!$query->num_rows) {
            return;
        }

        $column = $query->row;
        $default = array_key_exists('Default', $column) ? $column['Default'] : null;
        $nullable = isset($column['Null']) ? strtoupper((string)$column['Null']) : 'NO';
        $type = isset($column['Type']) ? strtolower((string)$column['Type']) : '';

        if (!($default === '0' && $nullable === 'NO' && strpos($type, 'tinyint') === 0)) {
            $this->db->query("ALTER TABLE `" . DB_PREFIX . "setting` MODIFY `serialized` TINYINT(1) NOT NULL DEFAULT '0'");
        }

        $markerKey = 'codecart_setting_serialized_default';
        $exists = $this->db->query("SELECT setting_id FROM `" . DB_PREFIX . "setting` WHERE store_id='0' AND code='codecart_core' AND `key`='" . $markerKey . "' LIMIT 1");
        if ($exists->num_rows) {
            $this->db->query("UPDATE `" . DB_PREFIX . "setting` SET value='1', serialized='0' WHERE setting_id='" . (int)$exists->row['setting_id'] . "'");
        } else {
            $this->db->query("INSERT INTO `" . DB_PREFIX . "setting` SET store_id='0', code='codecart_core', `key`='" . $markerKey . "', value='1', serialized='0'");
        }
    }

    private function ensureVarchar($table, $column, $length) {
        if (!$this->tableExists($table)) {
            return;
        }

        $query = $this->db->query("SHOW COLUMNS FROM `" . DB_PREFIX . $table . "` LIKE '" . $this->db->escape($column) . "'");
        if (!$query->num_rows) {
            return;
        }

        if (preg_match('/varchar\((\d+)\)/i', $query->row['Type'], $match) && (int)$match[1] < (int)$length) {
            $nullable = strtoupper((string)$query->row['Null']) === 'YES' ? ' NULL' : ' NOT NULL';
            $default = '';
            if ($query->row['Default'] !== null) {
                $default = " DEFAULT '" . $this->db->escape($query->row['Default']) . "'";
            }
            $this->db->query("ALTER TABLE `" . DB_PREFIX . $table . "` MODIFY `" . $column . "` varchar(" . (int)$length . ")" . $nullable . $default);
        }
    }



    private function ensureOrderProductSkuSnapshot() {
        if (!$this->tableExists('order_product')) { return; }
        $query = $this->db->query("SHOW COLUMNS FROM `" . DB_PREFIX . "order_product` LIKE 'sku'");
        if (!$query->num_rows) {
            $this->db->query("ALTER TABLE `" . DB_PREFIX . "order_product` ADD `sku` varchar(64) NOT NULL DEFAULT '' AFTER `model`");
        } elseif (preg_match('/varchar\((\d+)\)/i', (string)$query->row['Type'], $match) && (int)$match[1] < 64) {
            $this->db->query("ALTER TABLE `" . DB_PREFIX . "order_product` MODIFY `sku` varchar(64) NOT NULL DEFAULT ''");
        }
    }

    private function ensureCouponCustomerGroupTable() {
        $this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "coupon_customer_group` (
            `coupon_id` int(11) NOT NULL,
            `customer_group_id` int(11) NOT NULL,
            PRIMARY KEY (`coupon_id`,`customer_group_id`),
            KEY `customer_group_id` (`customer_group_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    private function ensureCustomerOnlineUserAgent() {
        if (!$this->tableExists('customer_online')) { return; }
        $query = $this->db->query("SHOW COLUMNS FROM `" . DB_PREFIX . "customer_online` LIKE 'user_agent'");
        if (!$query->num_rows) {
            $this->db->query("ALTER TABLE `" . DB_PREFIX . "customer_online` ADD `user_agent` varchar(512) NOT NULL DEFAULT '' AFTER `referer`");
            return;
        }
        if (preg_match('/varchar\((\d+)\)/i', (string)$query->row['Type'], $match) && (int)$match[1] < 512) {
            $this->db->query("ALTER TABLE `" . DB_PREFIX . "customer_online` MODIFY `user_agent` varchar(512) NOT NULL DEFAULT ''");
        }
    }

    private function ensureCustomerOnlineVisitorKey() {
        if (!$this->tableExists('customer_online')) { return; }
        $column = $this->db->query("SHOW COLUMNS FROM `" . DB_PREFIX . "customer_online` LIKE 'visitor_key'");
        if (!$column->num_rows) {
            $this->db->query("ALTER TABLE `" . DB_PREFIX . "customer_online` ADD `visitor_key` char(64) NOT NULL DEFAULT '' FIRST");
            $this->db->query("UPDATE `" . DB_PREFIX . "customer_online` SET visitor_key = SHA2(CONCAT(ip, '|', customer_id, '|', date_added), 256) WHERE visitor_key = ''");
        }
        $pk = $this->db->query("SHOW INDEX FROM `" . DB_PREFIX . "customer_online` WHERE Key_name='PRIMARY'");
        $primaryColumns = array();
        foreach ($pk->rows as $row) { $primaryColumns[] = (string)$row['Column_name']; }
        if ($primaryColumns !== array('visitor_key')) {
            if ($primaryColumns) { $this->db->query("ALTER TABLE `" . DB_PREFIX . "customer_online` DROP PRIMARY KEY"); }
            $this->db->query("ALTER TABLE `" . DB_PREFIX . "customer_online` ADD PRIMARY KEY (`visitor_key`)");
        }
        $ipIndex = $this->db->query("SHOW INDEX FROM `" . DB_PREFIX . "customer_online` WHERE Key_name='ip'");
        if (!$ipIndex->num_rows) { $this->db->query("ALTER TABLE `" . DB_PREFIX . "customer_online` ADD KEY `ip` (`ip`)"); }
    }

    private function ensureLanguageUrlPrefix() {
        if (!$this->tableExists('language')) { return; }

        $query = $this->db->query("SHOW COLUMNS FROM `" . DB_PREFIX . "language` LIKE 'url_prefix'");
        if (!$query->num_rows) {
            $this->db->query("ALTER TABLE `" . DB_PREFIX . "language` ADD `url_prefix` varchar(32) NOT NULL DEFAULT ''");
        }

        // UPDATE is deliberately schema-only here. Existing shops may use LangDir,
        // another language router, root-language URLs or custom SEO aliases. Automatic
        // prefix assignment would change public URLs. Prefixes can be adopted explicitly
        // later from Localization > Languages; native routing remains disabled while all
        // url_prefix values are empty, and external LangDir remains the source of truth.
    }

    private function backfillMissingSeoUrls() {
        if (!$this->tableExists('seo_url') || !$this->tableExists('language')) { return; }
        if (!function_exists('codecart_unique_seo_keyword')) { return; }

        $sources = array();

        if ($this->tableExists('product_description') && $this->tableExists('product_to_store')) {
            $sources[] = array(
                'prefix' => 'product_id',
                'sql' => "SELECT pd.product_id AS entity_id, pd.language_id, pd.name AS title, p2s.store_id FROM `" . DB_PREFIX . "product_description` pd INNER JOIN `" . DB_PREFIX . "language` l ON (l.language_id=pd.language_id AND l.status='1') INNER JOIN `" . DB_PREFIX . "product_to_store` p2s ON (p2s.product_id=pd.product_id) WHERE TRIM(pd.name)<>''"
            );
        }
        if ($this->tableExists('category_description') && $this->tableExists('category_to_store')) {
            $sources[] = array(
                'prefix' => 'category_id',
                'sql' => "SELECT cd.category_id AS entity_id, cd.language_id, cd.name AS title, c2s.store_id FROM `" . DB_PREFIX . "category_description` cd INNER JOIN `" . DB_PREFIX . "language` l ON (l.language_id=cd.language_id AND l.status='1') INNER JOIN `" . DB_PREFIX . "category_to_store` c2s ON (c2s.category_id=cd.category_id) WHERE TRIM(cd.name)<>''"
            );
        }
        if ($this->tableExists('manufacturer') && $this->tableExists('manufacturer_to_store')) {
            $sources[] = array(
                'prefix' => 'manufacturer_id',
                'sql' => "SELECT m.manufacturer_id AS entity_id, l.language_id, m.name AS title, m2s.store_id FROM `" . DB_PREFIX . "manufacturer` m INNER JOIN `" . DB_PREFIX . "manufacturer_to_store` m2s ON (m2s.manufacturer_id=m.manufacturer_id) CROSS JOIN `" . DB_PREFIX . "language` l WHERE l.status='1' AND TRIM(m.name)<>''"
            );
        }
        if ($this->tableExists('information_description') && $this->tableExists('information_to_store')) {
            $sources[] = array(
                'prefix' => 'information_id',
                'sql' => "SELECT id.information_id AS entity_id, id.language_id, id.title, i2s.store_id FROM `" . DB_PREFIX . "information_description` id INNER JOIN `" . DB_PREFIX . "language` l ON (l.language_id=id.language_id AND l.status='1') INNER JOIN `" . DB_PREFIX . "information_to_store` i2s ON (i2s.information_id=id.information_id) WHERE TRIM(id.title)<>''"
            );
        }
        if ($this->tableExists('article_description') && $this->tableExists('article_to_store')) {
            $sources[] = array(
                'prefix' => 'article_id',
                'sql' => "SELECT ad.article_id AS entity_id, ad.language_id, ad.name AS title, a2s.store_id FROM `" . DB_PREFIX . "article_description` ad INNER JOIN `" . DB_PREFIX . "language` l ON (l.language_id=ad.language_id AND l.status='1') INNER JOIN `" . DB_PREFIX . "article_to_store` a2s ON (a2s.article_id=ad.article_id) WHERE TRIM(ad.name)<>''"
            );
        }
        if ($this->tableExists('blog_category_description') && $this->tableExists('blog_category_to_store')) {
            $sources[] = array(
                'prefix' => 'blog_category_id',
                'sql' => "SELECT bcd.blog_category_id AS entity_id, bcd.language_id, bcd.name AS title, b2s.store_id FROM `" . DB_PREFIX . "blog_category_description` bcd INNER JOIN `" . DB_PREFIX . "language` l ON (l.language_id=bcd.language_id AND l.status='1') INNER JOIN `" . DB_PREFIX . "blog_category_to_store` b2s ON (b2s.blog_category_id=bcd.blog_category_id) WHERE TRIM(bcd.name)<>''"
            );
        }

        foreach ($sources as $source) {
            $rows = $this->db->query($source['sql']);
            foreach ($rows->rows as $row) {
                $entityId = (int)$row['entity_id'];
                $languageId = (int)$row['language_id'];
                $storeId = (int)$row['store_id'];
                $title = trim((string)$row['title']);
                if ($entityId < 1 || $languageId < 1 || $title === '') { continue; }

                $entityQuery = $source['prefix'] . '=' . $entityId;
                $existing = $this->db->query("SELECT seo_url_id, keyword FROM `" . DB_PREFIX . "seo_url` WHERE store_id='" . $storeId . "' AND language_id='" . $languageId . "' AND `query`='" . $this->db->escape($entityQuery) . "' ORDER BY seo_url_id ASC");
                $emptyId = 0;
                $hasKeyword = false;
                foreach ($existing->rows as $existingRow) {
                    if (trim((string)$existingRow['keyword']) !== '') { $hasKeyword = true; break; }
                    if (!$emptyId) { $emptyId = (int)$existingRow['seo_url_id']; }
                }
                if ($hasKeyword) { continue; }

                $keyword = codecart_unique_seo_keyword($this->db, $title, $storeId, $languageId);
                if ($emptyId > 0) {
                    $this->db->query("UPDATE `" . DB_PREFIX . "seo_url` SET keyword='" . $this->db->escape($keyword) . "' WHERE seo_url_id='" . $emptyId . "'");
                } else {
                    $this->db->query("INSERT INTO `" . DB_PREFIX . "seo_url` SET store_id='" . $storeId . "', language_id='" . $languageId . "', `query`='" . $this->db->escape($entityQuery) . "', keyword='" . $this->db->escape($keyword) . "'");
                }
            }
        }
    }

    private function ensureModernSeoDefaults() {
        if (!$this->tableExists('setting')) { return; }
        // UPDATE-safe defaults: a missing feature flag on an existing OpenCart/ocStore
        // shop must never silently enable a new storefront behaviour. Fresh installs already
        // contain their intended modern defaults in install/opencart.sql, so those values are
        // preserved by the loop below. In an upgrade, only genuinely missing keys are added,
        // with behaviour-changing features OFF until the merchant enables them explicitly.
        $defaults = array(
            'config_seo_url' => '0',
            'config_seo_pro' => '0',
            'config_auto_seo_url' => '0',
            'config_seo_url_cache' => '0',
            'config_seopro_addslash' => '0',
            'config_seopro_lowercase' => '1',
            'config_editor' => 'summernote',
            'config_image_webp' => '0',
            'config_image_webp_quality' => '82',
            'config_image_avif' => '0',
            'config_image_avif_quality' => '72'
        );
        foreach ($defaults as $key => $value) {
            $query = $this->db->query("SELECT setting_id, value FROM `" . DB_PREFIX . "setting` WHERE store_id = '0' AND `key` = '" . $this->db->escape($key) . "' LIMIT 1");
            $current = $value;
            if ($query->num_rows) {
                // UPDATE must preserve an existing merchant's SEO enable/disable state.
                // Modern defaults apply to fresh installs and genuinely missing keys only.
                $current = (string)$query->row['value'];
            } else {
                $this->db->query("INSERT INTO `" . DB_PREFIX . "setting` SET store_id = '0', code = 'config', `key` = '" . $this->db->escape($key) . "', value = '" . $this->db->escape($value) . "', serialized = '0'");
            }
            $this->config->set($key, $current);
        }

        // Existing upload size/type policy is merchant-controlled and remains untouched on UPDATE.
    }

    private function ensureOcStoreCompatibilityTables() {
        $file = DIR_SYSTEM . 'config/codecart_ocstore_compat.sql';
        if (!is_file($file) || !is_readable($file)) { return; }
        $sql = str_replace('{DB_PREFIX}', DB_PREFIX, (string)file_get_contents($file));
        if (trim($sql) === '') { return; }

        // This is a trusted internal DDL file containing only CREATE TABLE IF NOT EXISTS.
        // It provides the ocStore tables required by the CodeCart/ocStore controllers when
        // the source shop is stock OpenCart 3.x. Existing ocStore or third-party tables are
        // preserved because no DROP/REPLACE statement is accepted here.
        foreach (preg_split('/;\s*(?:\r?\n|$)/', $sql) as $statement) {
            $statement = trim(preg_replace('/^\s*--.*$/m', '', $statement));
            if ($statement === '') { continue; }
            if (!preg_match('/^CREATE\s+TABLE\s+IF\s+NOT\s+EXISTS\s+`[A-Za-z0-9_]+`/i', $statement)) {
                throw new \RuntimeException('Unexpected statement in CodeCart PRO ocStore compatibility schema.');
            }
            $this->db->query($statement);
        }

        // Stock OpenCart has manufacturer.name but no manufacturer_description table.
        // ocStore/CodeCart PRO storefront queries filter by md.language_id, so merely creating
        // an empty table would make every existing manufacturer disappear. Backfill only
        // missing manufacturer/language pairs and never overwrite existing ocStore content.
        if ($this->tableExists('manufacturer') && $this->tableExists('manufacturer_description') && $this->tableExists('language')) {
            $this->db->query("INSERT IGNORE INTO `" . DB_PREFIX . "manufacturer_description` (`manufacturer_id`,`language_id`,`description`,`description3`,`meta_description`,`meta_keyword`,`meta_title`,`meta_h1`) SELECT m.manufacturer_id,l.language_id,'','','','',m.name,m.name FROM `" . DB_PREFIX . "manufacturer` m CROSS JOIN `" . DB_PREFIX . "language` l LEFT JOIN `" . DB_PREFIX . "manufacturer_description` md ON md.manufacturer_id=m.manufacturer_id AND md.language_id=l.language_id WHERE md.manufacturer_id IS NULL");
        }

        if ($this->tableExists('shipping_courier')) {
            $count = $this->db->query("SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "shipping_courier`");
            if ((int)$count->row['total'] === 0) {
                $this->db->query("INSERT INTO `" . DB_PREFIX . "shipping_courier` (`shipping_courier_id`,`shipping_courier_code`,`shipping_courier_name`) VALUES (1,'dhl','DHL'),(2,'fedex','Fedex'),(3,'ups','UPS'),(4,'royal-mail','Royal Mail'),(5,'usps','United States Postal Service'),(6,'auspost','Australia Post')");
            }
        }
    }

    private function ensureOcStoreSeoCompatibilityColumns() {
        // Stock OpenCart lacks several ocStore SEO columns that are used directly by
        // the CodeCart/ocStore models. Add only missing fields and preserve existing data.
        foreach (array('category','manufacturer','information') as $table) {
            if (!$this->tableExists($table)) { continue; }
            $columns = $this->columnMap($table);
            $alter = array();
            if (!isset($columns['noindex'])) { $alter[] = "ADD `noindex` TINYINT(1) NOT NULL DEFAULT '1'"; }
            if ($table === 'category' && !isset($columns['google_product_category_id'])) { $alter[] = "ADD `google_product_category_id` VARCHAR(64) NOT NULL DEFAULT ''"; }
            if ($alter) { $this->db->query("ALTER TABLE `" . DB_PREFIX . $table . "` " . implode(', ', $alter)); }
        }

        if ($this->tableExists('product')) {
            $columns = $this->columnMap('product');
            $alter = array();
            if (!isset($columns['noindex'])) { $alter[] = "ADD `noindex` TINYINT(1) NOT NULL DEFAULT '1'"; }
            if (!isset($columns['google_product_category_id'])) { $alter[] = "ADD `google_product_category_id` VARCHAR(64) NOT NULL DEFAULT ''"; }

            // A stock OpenCart 3.x product table may still use DEFAULT '0000-00-00'.
            // MySQL/MariaDB strict mode can reject an otherwise additive ALTER because of
            // that legacy default. Never rewrite merchants' existing date_available rows
            // during upgrade. Relax zero-date/strict flags only for this DB connection and
            // this ALTER, modernize the column default, then restore the exact session mode.
            $restoreSqlMode = null;
            if ($alter && isset($columns['date_available'])) {
                $dateColumn = $this->db->query("SHOW COLUMNS FROM `" . DB_PREFIX . "product` LIKE 'date_available'");
                if ($dateColumn->num_rows && (string)$dateColumn->row['Default'] === '0000-00-00') {
                    $modeQuery = $this->db->query("SELECT @@SESSION.sql_mode AS sql_mode");
                    if ($modeQuery->num_rows) {
                        $restoreSqlMode = (string)$modeQuery->row['sql_mode'];
                        $parts = array_filter(array_map('trim', explode(',', $restoreSqlMode)), function($part) {
                            return !in_array(strtoupper($part), array('STRICT_TRANS_TABLES','STRICT_ALL_TABLES','NO_ZERO_DATE','NO_ZERO_IN_DATE'), true);
                        });
                        $this->db->query("SET SESSION sql_mode='" . $this->db->escape(implode(',', $parts)) . "'");
                    }
                    $alter[] = "MODIFY `date_available` DATE NOT NULL DEFAULT '1970-01-01'";
                }
            }
            if ($alter) {
                try {
                    $this->db->query("ALTER TABLE `" . DB_PREFIX . "product` " . implode(', ', $alter));
                } finally {
                    if ($restoreSqlMode !== null) {
                        $this->db->query("SET SESSION sql_mode='" . $this->db->escape($restoreSqlMode) . "'");
                    }
                }
            }
        }

        // Preserve existing Google Base taxonomy mappings during an ocStore/OpenCart upgrade.
        // Merchant-owned mappings are copied only into empty CodeCart PRO fields and are never guessed.
        if ($this->tableExists('category') && $this->tableExists('google_base_category_to_category')) {
            $categoryColumns = $this->columnMap('category');
            if (isset($categoryColumns['google_product_category_id'])) {
                $this->db->query("UPDATE `" . DB_PREFIX . "category` c INNER JOIN `" . DB_PREFIX . "google_base_category_to_category` gbc ON (gbc.category_id=c.category_id) SET c.google_product_category_id=CAST(gbc.google_base_category_id AS CHAR) WHERE c.google_product_category_id='' AND gbc.google_base_category_id>0");
            }
        }

        // ocStore's separate H1 is optional content. Empty values fall back to the normal
        // name/title, so adding the column does not change existing storefront headings.
        foreach (array('category_description','product_description','information_description') as $table) {
            if (!$this->tableExists($table)) { continue; }
            $columns = $this->columnMap($table);
            if (!isset($columns['meta_h1'])) {
                $this->db->query("ALTER TABLE `" . DB_PREFIX . $table . "` ADD `meta_h1` VARCHAR(255) NOT NULL DEFAULT ''");
            }
        }
    }

    private function ensureMainCategoryCompatibility() {
        if (!$this->tableExists('product_to_category')) {
            return;
        }

        $columns = $this->columnMap('product_to_category');
        $added = false;
        if (!isset($columns['main_category'])) {
            $this->db->query("ALTER TABLE `" . DB_PREFIX . "product_to_category` ADD `main_category` TINYINT(1) NOT NULL DEFAULT '0'");
            $added = true;
        }

        // Some ocStore/third-party builds store the selected main category on
        // product.main_category_id. Preserve that explicit information when the
        // relation flag is introduced, but never mass-pick a category for stock
        // OpenCart products during a web upgrade. Runtime fallback stays
        // deterministic and the next normal product save persists an explicit flag.
        if ($added && $this->tableExists('product')) {
            $productColumns = $this->columnMap('product');
            if (isset($productColumns['main_category_id'])) {
                $this->db->query("UPDATE `" . DB_PREFIX . "product_to_category` p2c INNER JOIN `" . DB_PREFIX . "product` p ON p.product_id = p2c.product_id AND p.main_category_id = p2c.category_id SET p2c.main_category = '1' WHERE p.main_category_id > '0'");
            }
        }
    }


    private function ensureIndex($table, $name, array $columns, array $prefixes = array()) {
        if (!$this->tableExists($table) || !$columns) {
            return;
        }

        // Never attempt an index on a column that is absent in this ocStore/OpenCart
        // schema revision. Older builds differ (for example oc_user may have no email).
        $available = array();
        $columnQuery = $this->db->query("SHOW COLUMNS FROM `" . DB_PREFIX . $table . "`");
        foreach ($columnQuery->rows as $row) {
            $available[(string)$row['Field']] = true;
        }
        foreach ($columns as $column) {
            if (!isset($available[(string)$column])) {
                return;
            }
        }

        $query = $this->db->query("SHOW INDEX FROM `" . DB_PREFIX . $table . "`");
        $indexes = array();
        foreach ($query->rows as $row) {
            $key = (string)$row['Key_name'];
            $seq = (int)$row['Seq_in_index'];
            $indexes[$key][$seq] = (string)$row['Column_name'];
        }

        foreach ($indexes as $index_columns) {
            ksort($index_columns);
            if (array_values($index_columns) === array_values($columns)) {
                return;
            }
        }

        if (isset($indexes[$name])) {
            $alternate = substr($name . '_cc', 0, 64);
            if (isset($indexes[$alternate])) {
                return;
            }
            $name = $alternate;
        }

        $safe = array();
        foreach ($columns as $column) {
            if (!preg_match('/^[a-zA-Z0-9_]+$/', $column)) {
                return;
            }

            $part = '`' . $column . '`';

            if (isset($prefixes[$column])) {
                $prefix = (int)$prefixes[$column];

                if ($prefix < 1 || $prefix > 191) {
                    return;
                }

                $part .= '(' . $prefix . ')';
            }

            $safe[] = $part;
        }

        if (!preg_match('/^[a-zA-Z0-9_]+$/', $name)) {
            return;
        }

        $this->db->query("ALTER TABLE `" . DB_PREFIX . $table . "` ADD INDEX `" . $name . "` (" . implode(',', $safe) . ")");
    }

    private function ensureUniqueIndexIfNoDuplicates($table, $name, array $columns) {
        if (!$this->tableExists($table) || !$columns) { return; }
        foreach (array_merge(array($table, $name), $columns) as $identifier) {
            if (!preg_match('/^[A-Za-z0-9_]+$/', (string)$identifier)) { return; }
        }

        $available = array();
        $columnQuery = $this->db->query("SHOW COLUMNS FROM `" . DB_PREFIX . $table . "`");
        foreach ($columnQuery->rows as $row) { $available[(string)$row['Field']] = true; }
        foreach ($columns as $column) { if (!isset($available[$column])) { return; } }

        $indexQuery = $this->db->query("SHOW INDEX FROM `" . DB_PREFIX . $table . "`");
        $indexes = array();
        foreach ($indexQuery->rows as $row) {
            $key = (string)$row['Key_name'];
            $seq = (int)$row['Seq_in_index'];
            if (!isset($indexes[$key])) { $indexes[$key] = array('unique'=>(int)$row['Non_unique']===0,'columns'=>array()); }
            $indexes[$key]['columns'][$seq] = (string)$row['Column_name'];
        }
        foreach ($indexes as $index) {
            ksort($index['columns']);
            if ($index['unique'] && array_values($index['columns']) === array_values($columns)) { return; }
        }

        $quoted = array(); foreach ($columns as $column) { $quoted[] = '`' . $column . '`'; }
        $duplicates = $this->db->query("SELECT 1 AS duplicate_row FROM `" . DB_PREFIX . $table . "` GROUP BY " . implode(',', $quoted) . " HAVING COUNT(*) > 1 LIMIT 1");
        if ($duplicates->num_rows) {
            if ($this->log) { $this->log->write('CodeCart PRO DB: unique index ' . $table . '.' . $name . ' skipped because duplicate rows already exist.'); }
            return;
        }

        $safeName = $name;
        if (isset($indexes[$safeName])) { $safeName = substr($safeName . '_cc', 0, 64); if (isset($indexes[$safeName])) { return; } }
        $this->db->query("ALTER TABLE `" . DB_PREFIX . $table . "` ADD UNIQUE KEY `" . $safeName . "` (" . implode(',', $quoted) . ")");
    }

    private function ensureSeoUrlCompositeIndexes() {
        if (!$this->tableExists('seo_url')) {
            return;
        }

        $query = $this->db->query("SHOW INDEX FROM `" . DB_PREFIX . "seo_url`");
        $indexes = array();

        foreach ($query->rows as $row) {
            $name = (string)$row['Key_name'];
            $seq = (int)$row['Seq_in_index'];
            $indexes[$name][$seq] = (string)$row['Column_name'];
        }

        $required = array(
            'query_store_language' => array('query', 'store_id', 'language_id'),
            'keyword_store_language' => array('keyword', 'store_id', 'language_id')
        );

        foreach ($required as $name => $columns) {
            $exists = false;
            foreach ($indexes as $index_columns) {
                ksort($index_columns);
                if (array_values($index_columns) === $columns) {
                    $exists = true;
                    break;
                }
            }

            if ($exists) {
                continue;
            }

            $safe_name = $name;
            if (isset($indexes[$safe_name])) {
                $safe_name = substr($safe_name . '_cc', 0, 64);
                if (isset($indexes[$safe_name])) {
                    continue;
                }
            }

            $leading = $columns[0] === 'query' ? 'query' : 'keyword';
            $this->db->query("ALTER TABLE `" . DB_PREFIX . "seo_url` ADD INDEX `" . $safe_name . "` (`" . $leading . "`(191),`store_id`,`language_id`)");
        }
    }

    private function removeLegacyExtraEmailModification() {
        if (!$this->tableExists('modification')) {
            return;
        }

        $installIds = array();
        if ($this->tableExists('extension_install')) {
            $query = $this->db->query("SELECT extension_install_id FROM `" . DB_PREFIX . "extension_install` WHERE LOWER(`filename`) = 'extra_email_opencart3.ocmod.zip'");
            foreach ($query->rows as $row) {
                $installIds[(int)$row['extension_install_id']] = (int)$row['extension_install_id'];
            }
        }

        $conditions = array("`code` = 'Extra_email_opencart.3'", "`name` = 'Extra_email_opencart.3'");
        if ($installIds) {
            $conditions[] = "`extension_install_id` IN (" . implode(',', array_map('intval', $installIds)) . ")";
        }

        $mods = $this->db->query("SELECT modification_id, extension_install_id, code, xml FROM `" . DB_PREFIX . "modification` WHERE " . implode(' OR ', $conditions));
        foreach ($mods->rows as $row) {
            $modificationId = (int)$row['modification_id'];
            if ($this->tableExists('modification_backup')) {
                $exists = $this->db->query("SELECT backup_id FROM `" . DB_PREFIX . "modification_backup` WHERE modification_id = '" . $modificationId . "' AND code = '" . $this->db->escape((string)$row['code']) . "' LIMIT 1");
                if (!$exists->num_rows) {
                    $this->db->query("INSERT INTO `" . DB_PREFIX . "modification_backup` SET modification_id = '" . $modificationId . "', code = '" . $this->db->escape((string)$row['code']) . "', xml = '" . $this->db->escape((string)$row['xml']) . "', date_added = NOW()");
                }
            }
            $installId = isset($row['extension_install_id']) ? (int)$row['extension_install_id'] : 0;
            if ($installId > 0) {
                $installIds[$installId] = $installId;
            }
        }

        if ($mods->num_rows) {
            $this->db->query("DELETE FROM `" . DB_PREFIX . "modification` WHERE " . implode(' OR ', $conditions));
        }

        if ($installIds && $this->tableExists('extension_path')) {
            $this->db->query("DELETE FROM `" . DB_PREFIX . "extension_path` WHERE extension_install_id IN (" . implode(',', array_map('intval', $installIds)) . ")");
        }
        if ($installIds && $this->tableExists('extension_install')) {
            $this->db->query("DELETE FROM `" . DB_PREFIX . "extension_install` WHERE extension_install_id IN (" . implode(',', array_map('intval', $installIds)) . ") AND LOWER(`filename`) = 'extra_email_opencart3.ocmod.zip'");
        }

        if ($mods->num_rows && $this->log) {
            $this->log->write('CodeCart PRO migration: removed legacy Extra_email_opencart.3 OCMOD; core order mail remains authoritative. Refresh Modifications after update.');
        }
    }

    private function ensureExtensionInstallerCompatibility() {
        if ($this->tableExists('modification') && !$this->columnExists('modification', 'extension_install_id')) {
            $this->db->query("ALTER TABLE `" . DB_PREFIX . "modification` ADD `extension_install_id` INT(11) NOT NULL DEFAULT '0' AFTER `modification_id`");
        }

        $this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "modification_backup` (
            `backup_id` INT(11) NOT NULL AUTO_INCREMENT,
            `modification_id` INT(11) NOT NULL,
            `code` VARCHAR(64) NOT NULL,
            `xml` MEDIUMTEXT NOT NULL,
            `date_added` DATETIME NOT NULL,
            PRIMARY KEY (`backup_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    private function tableExists($table) {
        if (!is_string($table) || !preg_match('/^[a-zA-Z0-9_]+$/', $table)) {
            return false;
        }
        $query = $this->db->query("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '" . $this->db->escape(DB_PREFIX . $table) . "' LIMIT 1");
        return (bool)$query->num_rows;
    }

    private function columnExists($table, $column) {
        if (!is_string($table) || !is_string($column) || !preg_match('/^[a-zA-Z0-9_]+$/', $table) || !preg_match('/^[a-zA-Z0-9_]+$/', $column)) {
            return false;
        }
        if (!$this->tableExists($table)) {
            return false;
        }
        $query = $this->db->query("SHOW COLUMNS FROM `" . DB_PREFIX . $table . "` LIKE '" . $this->db->escape($column) . "'");
        return (bool)$query->num_rows;
    }



    private function ensureBundledDemoCheckoutCustomerGroups() {
        if (!$this->tableExists('customer_group') || !$this->tableExists('customer_group_description')) { return; }

        $privateGroupId = 1;
        if ($this->tableExists('setting')) {
            $defaultGroup = $this->db->query("SELECT value FROM `" . DB_PREFIX . "setting` WHERE store_id='0' AND `key`='config_customer_group_id' LIMIT 1");
            if ($defaultGroup->num_rows && (int)$defaultGroup->row['value'] > 0) { $privateGroupId = (int)$defaultGroup->row['value']; }
        }

        // Rename only the untouched bundled default labels.
        $this->db->query("UPDATE `" . DB_PREFIX . "customer_group_description` SET name='Приватна особа', description='Покупець-фізична особа. Для швидкого оформлення достатньо імені, прізвища, телефону та способу доставки.' WHERE customer_group_id='" . $privateGroupId . "' AND language_id='1' AND name='За замовчуванням'");
        $this->db->query("UPDATE `" . DB_PREFIX . "customer_group_description` SET name='Private person', description='Individual customer. Quick checkout requires only contact details and the selected delivery method.' WHERE customer_group_id='" . $privateGroupId . "' AND language_id='2' AND name='Default'");

        $company = $this->db->query("SELECT cgd.customer_group_id FROM `" . DB_PREFIX . "customer_group_description` cgd WHERE cgd.name IN ('Компанія','Company') ORDER BY cgd.customer_group_id ASC LIMIT 1");
        if ($company->num_rows) {
            $companyGroupId = (int)$company->row['customer_group_id'];
        } else {
            $this->db->query("INSERT INTO `" . DB_PREFIX . "customer_group` SET approval='0', sort_order='2'");
            $companyGroupId = (int)$this->db->getLastId();
        }
        if ($companyGroupId < 1 || $companyGroupId === $privateGroupId) { return; }

        $this->db->query("REPLACE INTO `" . DB_PREFIX . "customer_group_description` SET customer_group_id='" . $companyGroupId . "', language_id='1', name='Компанія', description='Юридична особа або ФОП. У швидкому оформленні доступне додаткове поле для реквізитів.'");
        $this->db->query("REPLACE INTO `" . DB_PREFIX . "customer_group_description` SET customer_group_id='" . $companyGroupId . "', language_id='2', name='Company', description='Business customer. Quick checkout can collect company requisites in an additional account field.'");

        if ($this->tableExists('setting')) {
            $display = $this->db->query("SELECT setting_id, value FROM `" . DB_PREFIX . "setting` WHERE store_id='0' AND `key`='config_customer_group_display' LIMIT 1");
            $ids = array($privateGroupId, $companyGroupId);
            if ($display->num_rows) {
                $current = json_decode((string)$display->row['value'], true);
                if (is_array($current)) {
                    foreach ($current as $id) { if ((int)$id > 0) { $ids[] = (int)$id; } }
                }
                $ids = array_values(array_unique(array_map('intval', $ids)));
                $encoded = json_encode(array_map('strval', $ids));
                $this->db->query("UPDATE `" . DB_PREFIX . "setting` SET value='" . $this->db->escape($encoded) . "', serialized='1' WHERE setting_id='" . (int)$display->row['setting_id'] . "'");
                $this->config->set('config_customer_group_display', array_map('strval', $ids));
            }
        }

        if ($this->tableExists('custom_field') && $this->tableExists('custom_field_description') && $this->tableExists('custom_field_customer_group')) {
            $field = $this->db->query("SELECT cfd.custom_field_id FROM `" . DB_PREFIX . "custom_field_description` cfd WHERE cfd.name IN ('Реквізити','Company requisites') ORDER BY cfd.custom_field_id ASC LIMIT 1");
            if ($field->num_rows) {
                $customFieldId = (int)$field->row['custom_field_id'];
            } else {
                $this->db->query("INSERT INTO `" . DB_PREFIX . "custom_field` SET type='textarea', value='', validation='', location='account', status='1', sort_order='1'");
                $customFieldId = (int)$this->db->getLastId();
            }
            if ($customFieldId > 0) {
                $this->db->query("UPDATE `" . DB_PREFIX . "custom_field` SET type='textarea', location='account', status='1' WHERE custom_field_id='" . $customFieldId . "'");
                $this->db->query("REPLACE INTO `" . DB_PREFIX . "custom_field_description` SET custom_field_id='" . $customFieldId . "', language_id='1', name='Реквізити'");
                $this->db->query("REPLACE INTO `" . DB_PREFIX . "custom_field_description` SET custom_field_id='" . $customFieldId . "', language_id='2', name='Company requisites'");
                $this->db->query("INSERT INTO `" . DB_PREFIX . "custom_field_customer_group` (custom_field_id, customer_group_id, required) VALUES ('" . $customFieldId . "','" . $companyGroupId . "','0') ON DUPLICATE KEY UPDATE required=VALUES(required)");
            }
        }

        // Customer groups must not accidentally change demo tax or promotional prices.
        if ($this->tableExists('tax_rate_to_customer_group')) {
            $this->db->query("INSERT IGNORE INTO `" . DB_PREFIX . "tax_rate_to_customer_group` (tax_rate_id, customer_group_id) SELECT tax_rate_id, '" . $companyGroupId . "' FROM `" . DB_PREFIX . "tax_rate_to_customer_group` WHERE customer_group_id='" . $privateGroupId . "'");
        }
        if ($this->tableExists('product_discount')) {
            $this->db->query("INSERT INTO `" . DB_PREFIX . "product_discount` (product_id, customer_group_id, quantity, priority, price, date_start, date_end) SELECT src.product_id, '" . $companyGroupId . "', src.quantity, src.priority, src.price, src.date_start, src.date_end FROM `" . DB_PREFIX . "product_discount` src WHERE src.customer_group_id='" . $privateGroupId . "' AND NOT EXISTS (SELECT 1 FROM `" . DB_PREFIX . "product_discount` dst WHERE dst.product_id=src.product_id AND dst.customer_group_id='" . $companyGroupId . "' AND dst.quantity=src.quantity AND dst.priority=src.priority AND dst.price=src.price AND dst.date_start=src.date_start AND dst.date_end=src.date_end)");
        }
        if ($this->tableExists('product_special')) {
            $this->db->query("INSERT INTO `" . DB_PREFIX . "product_special` (product_id, customer_group_id, priority, price, date_start, date_end) SELECT src.product_id, '" . $companyGroupId . "', src.priority, src.price, src.date_start, src.date_end FROM `" . DB_PREFIX . "product_special` src WHERE src.customer_group_id='" . $privateGroupId . "' AND NOT EXISTS (SELECT 1 FROM `" . DB_PREFIX . "product_special` dst WHERE dst.product_id=src.product_id AND dst.customer_group_id='" . $companyGroupId . "' AND dst.priority=src.priority AND dst.price=src.price AND dst.date_start=src.date_start AND dst.date_end=src.date_end)");
        }
        if ($this->tableExists('product_reward')) {
            $this->db->query("INSERT INTO `" . DB_PREFIX . "product_reward` (product_id, customer_group_id, points) SELECT src.product_id, '" . $companyGroupId . "', src.points FROM `" . DB_PREFIX . "product_reward` src WHERE src.customer_group_id='" . $privateGroupId . "' AND NOT EXISTS (SELECT 1 FROM `" . DB_PREFIX . "product_reward` dst WHERE dst.product_id=src.product_id AND dst.customer_group_id='" . $companyGroupId . "')");
        }
    }

    private function ensureCheckoutPaymentExtensions() {
        if (!$this->tableExists('extension')) { return; }
        foreach (array('bank_transfer', 'liqpay') as $code) {
            $q = $this->db->query("SELECT extension_id FROM `" . DB_PREFIX . "extension` WHERE type='payment' AND code='" . $this->db->escape($code) . "' LIMIT 1");
            if (!$q->num_rows) { $this->db->query("INSERT INTO `" . DB_PREFIX . "extension` SET type='payment', code='" . $this->db->escape($code) . "'"); }
        }
        if (!$this->tableExists('setting')) { return; }
        $defaults = array(
            'payment_bank_transfer_total' => array('payment_bank_transfer', '0'),
            'payment_bank_transfer_order_status_id' => array('payment_bank_transfer', '1'),
            'payment_bank_transfer_geo_zone_id' => array('payment_bank_transfer', '0'),
            'payment_bank_transfer_status' => array('payment_bank_transfer', '0'),
            'payment_bank_transfer_sort_order' => array('payment_bank_transfer', '6'),
            'payment_liqpay_public_key' => array('payment_liqpay', ''),
            'payment_liqpay_private_key' => array('payment_liqpay', ''),
            'payment_liqpay_sandbox' => array('payment_liqpay', '0'),
            'payment_liqpay_total' => array('payment_liqpay', '0'),
            'payment_liqpay_order_status_id' => array('payment_liqpay', '1'),
            'payment_liqpay_geo_zone_id' => array('payment_liqpay', '0'),
            'payment_liqpay_status' => array('payment_liqpay', '0'),
            'payment_liqpay_sort_order' => array('payment_liqpay', '7')
        );
        foreach ($defaults as $key => $row) {
            $q = $this->db->query("SELECT setting_id FROM `" . DB_PREFIX . "setting` WHERE store_id='0' AND `key`='" . $this->db->escape($key) . "' LIMIT 1");
            if (!$q->num_rows) {
                $this->db->query("INSERT INTO `" . DB_PREFIX . "setting` SET store_id='0', code='" . $this->db->escape($row[0]) . "', `key`='" . $this->db->escape($key) . "', value='" . $this->db->escape($row[1]) . "', serialized='0'");
            }
        }
    }

    private function ensureBundledModuleExtensions() {
        if (!$this->tableExists('extension')) { return; }

        // These modules are bundled with CodeCart PRO and are safe to expose in the
        // Extensions list after UPDATE. Registration does not create a module
        // instance, place it in a layout or execute storefront logic.
        $modules = array(
            'category_wall',
            'latest',
            'bestseller',
            'special',
            'popular',
            'recently_viewed',
            'manufacturer_wall',
            'filter',
            'html',
            'information',
            'store',
            'codecart_form'
        );

        foreach ($modules as $code) {
            $query = $this->db->query("SELECT extension_id FROM `" . DB_PREFIX . "extension` WHERE type = 'module' AND code = '" . $this->db->escape($code) . "' LIMIT 1");
            if (!$query->num_rows) {
                $this->db->query("INSERT INTO `" . DB_PREFIX . "extension` SET type = 'module', code = '" . $this->db->escape($code) . "'");
            }
        }
    }

    private function cleanupLegacyLayoutNoops() {
        if (!$this->tableExists('layout_module')) { return; }
        // Historical OpenCart demo data could leave no-op entries with code=0.
        // Such rows cannot resolve to a module/controller and are safe to remove.
        $this->db->query("DELETE FROM `" . DB_PREFIX . "layout_module` WHERE code='0'");
    }

    private function ensureRelationInfrastructure() {
        if (!$this->tableExists('codecart_relation')) {
            $this->db->query("CREATE TABLE `" . DB_PREFIX . "codecart_relation` (
                `relation_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                `source_type` varchar(32) NOT NULL,
                `source_id` int(11) unsigned NOT NULL,
                `target_type` varchar(32) NOT NULL,
                `target_id` int(11) unsigned NOT NULL,
                `relation_type` varchar(32) NOT NULL DEFAULT 'related',
                `origin` varchar(16) NOT NULL DEFAULT 'rule',
                `score` smallint(5) unsigned NOT NULL DEFAULT '0',
                `reason` varchar(255) NOT NULL DEFAULT '',
                `status` tinyint(1) NOT NULL DEFAULT '1',
                `date_added` datetime NOT NULL,
                `date_modified` datetime NOT NULL,
                PRIMARY KEY (`relation_id`),
                UNIQUE KEY `relation_unique` (`source_type`,`source_id`,`target_type`,`target_id`,`relation_type`,`origin`),
                KEY `source_lookup` (`source_type`,`source_id`,`relation_type`,`origin`,`status`,`score`),
                KEY `target_lookup` (`target_type`,`target_id`,`relation_type`,`status`),
                KEY `origin_status` (`origin`,`status`,`date_modified`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        }

        // Presentation revision 10: information pages are intentionally outside
        // Auto Relation Layer. Remove only obsolete experimental rows, never manual
        // product/blog relation tables.
        $this->db->query("DELETE FROM `" . DB_PREFIX . "codecart_relation` WHERE source_type='information' OR target_type='information'");
        if ($this->tableExists('setting')) {
            $defaults = array(
                'codecart_relation_status' => '0',
                'codecart_relation_storefront_status' => '0',
                'codecart_relation_product_limit' => '8',
                'codecart_relation_article_limit' => '3',
                'codecart_relation_in_stock_only' => '1',
                'codecart_relation_use_manufacturer' => '1',
                'codecart_relation_use_attributes' => '1'
            );
            foreach ($defaults as $key => $value) {
                $query = $this->db->query("SELECT setting_id FROM `" . DB_PREFIX . "setting` WHERE store_id='0' AND `key`='" . $this->db->escape($key) . "' LIMIT 1");
                if (!$query->num_rows) {
                    $this->db->query("INSERT INTO `" . DB_PREFIX . "setting` SET store_id='0', code='codecart_relation', `key`='" . $this->db->escape($key) . "', value='" . $this->db->escape($value) . "', serialized='0'");
                    $this->config->set($key, $value);
                }
            }
        }

        if ($this->tableExists('user_group')) {
            $groups = $this->db->query("SELECT user_group_id, permission FROM `" . DB_PREFIX . "user_group`");
            foreach ($groups->rows as $group) {
                $permission = json_decode((string)$group['permission'], true);
                if (!is_array($permission)) { continue; }
                $changed = false;
                foreach (array('access','modify') as $kind) {
                    if (!isset($permission[$kind]) || !is_array($permission[$kind])) { continue; }
                    $mayCatalog = (int)$group['user_group_id'] === 1 || in_array('catalog/product', $permission[$kind], true) || in_array('setting/setting', $permission[$kind], true);
                    if ($mayCatalog && !in_array('catalog/relation', $permission[$kind], true)) {
                        $permission[$kind][] = 'catalog/relation';
                        sort($permission[$kind], SORT_STRING);
                        $changed = true;
                    }
                }
                if ($changed) {
                    $encoded = json_encode($permission, JSON_UNESCAPED_SLASHES);
                    if ($encoded !== false) {
                        $this->db->query("UPDATE `" . DB_PREFIX . "user_group` SET permission='" . $this->db->escape($encoded) . "' WHERE user_group_id='" . (int)$group['user_group_id'] . "'");
                    }
                }
            }
        }
    }

    private function ensureCodeCartCoreDefaults() {
        if (!$this->tableExists('setting')) {
            return;
        }

        // Fresh-install SQL may enable the Core JSON-LD layer. On UPDATE, however,
        // a missing switch means the existing shop has never opted in. Keep it OFF so a
        // theme/SEO extension cannot suddenly receive duplicate structured data.
        $defaults = array(
            'config_admin_accent_color' => '#0b6fd3',
            'config_admin_sidebar_color' => '#1f2937',
            'config_admin_submenu_color' => '#293141',
            'config_admin_surface_color' => '#f5f7fa',
            'config_codecart_structured_data_status' => '0',
            'config_digital_checkout_status' => '0',
            'config_digital_checkout_field_email' => '1',
            'config_digital_checkout_field_lastname' => '0',
            'config_digital_checkout_field_telephone' => '0',
            'config_digital_checkout_field_company' => '0',
            'config_digital_checkout_field_address_1' => '0',
            'config_digital_checkout_field_address_2' => '0',
            'config_digital_checkout_field_city' => '0',
            'config_digital_checkout_field_postcode' => '0'
        );

        foreach ($defaults as $key => $value) {
            $query = $this->db->query("SELECT setting_id, value FROM `" . DB_PREFIX . "setting` WHERE store_id = '0' AND `key` = '" . $this->db->escape($key) . "' ORDER BY setting_id ASC LIMIT 1");
            if (!$query->num_rows) {
                $this->db->query("INSERT INTO `" . DB_PREFIX . "setting` SET store_id = '0', code = 'config', `key` = '" . $this->db->escape($key) . "', `value` = '" . $this->db->escape($value) . "', serialized = '0'");
                $this->config->set($key, $value);
            }
        }

        // OpenCart's historical 300000-byte default is too small for normal product files.
        // Upgrade only that untouched legacy default; merchant-customized limits are preserved.
        $legacyFileLimit = $this->db->query("SELECT setting_id, value FROM `" . DB_PREFIX . "setting` WHERE store_id='0' AND `key`='config_file_max_size' ORDER BY setting_id ASC LIMIT 1");
        if ($legacyFileLimit->num_rows && (string)$legacyFileLimit->row['value'] === '300000') {
            $this->db->query("UPDATE `" . DB_PREFIX . "setting` SET `value`='10485760', serialized='0' WHERE setting_id='" . (int)$legacyFileLimit->row['setting_id'] . "'");
            $this->config->set('config_file_max_size', '10485760');
        }
    }

    private function ensureCodeCartStructuredDataEvent() {
        if (!$this->tableExists('event')) {
            return;
        }

        $this->ensureCoreEvent('codecart_structured_data', 'catalog/view/common/header/after', 'event/codecart/injectStructuredData');
    }

    private function ensureOrderHistoryEventsAfter() {
        if (!$this->tableExists('event')) {
            return;
        }

        $trigger = 'catalog/model/checkout/order/addOrderHistory/after';

        $this->ensureCoreEvent('activity_order_add', $trigger, 'event/activity/addOrderHistory');
        $this->ensureCoreEvent('mail_order_add', $trigger, 'mail/order');
        $this->ensureCoreEvent('mail_order_alert', $trigger, 'mail/order/alert');
        $this->ensureCoreEvent('statistics_order_history', $trigger, 'event/statistics/addOrderHistory');
    }

    private function ensureCoreEvent($code, $trigger, $action, $sort_order = 0) {
        $query = $this->db->query("SELECT event_id FROM `" . DB_PREFIX . "event` WHERE `code` = '" . $this->db->escape($code) . "' ORDER BY event_id ASC");

        if ($query->num_rows) {
            $keep_id = (int)$query->row['event_id'];

            $this->db->query("UPDATE `" . DB_PREFIX . "event` SET `trigger` = '" . $this->db->escape($trigger) . "', `action` = '" . $this->db->escape($action) . "', `sort_order` = '" . (int)$sort_order . "' WHERE event_id = '" . $keep_id . "'");
            $this->db->query("DELETE FROM `" . DB_PREFIX . "event` WHERE `code` = '" . $this->db->escape($code) . "' AND event_id <> '" . $keep_id . "'");
            return;
        }

        // Missing core events are restored as enabled. Existing event status is preserved above.
        $this->db->query("INSERT INTO `" . DB_PREFIX . "event` SET `code` = '" . $this->db->escape($code) . "', `trigger` = '" . $this->db->escape($trigger) . "', `action` = '" . $this->db->escape($action) . "', `status` = '1', `sort_order` = '" . (int)$sort_order . "'");
    }

    private function ensureVoucherEvent() {
        if (!$this->tableExists('event')) {
            return;
        }

        $canonical = $this->db->query("SELECT event_id FROM `" . DB_PREFIX . "event` WHERE `code` = 'mail_voucher' ORDER BY event_id ASC LIMIT 1");
        $legacy = $this->db->query("SELECT event_id FROM `" . DB_PREFIX . "event` WHERE `code` = 'voucher' ORDER BY event_id ASC LIMIT 1");
        $keep_id = 0;

        if ($canonical->num_rows) {
            $keep_id = (int)$canonical->row['event_id'];
        } elseif ($legacy->num_rows) {
            $keep_id = (int)$legacy->row['event_id'];
            $this->db->query("UPDATE `" . DB_PREFIX . "event` SET `code` = 'mail_voucher' WHERE event_id = '" . $keep_id . "'");
        } else {
            $this->db->query("INSERT INTO `" . DB_PREFIX . "event` SET `code` = 'mail_voucher', `trigger` = 'catalog/model/checkout/order/addOrderHistory/after', `action` = 'extension/total/voucher/send', `status` = '1', `sort_order` = '0'");
            $keep_id = (int)$this->db->getLastId();
        }

        if ($keep_id) {
            $this->db->query("DELETE FROM `" . DB_PREFIX . "event` WHERE (`code` = 'voucher' OR `code` = 'mail_voucher') AND event_id <> '" . $keep_id . "'");
            $this->db->query("UPDATE `" . DB_PREFIX . "event` SET `trigger` = 'catalog/model/checkout/order/addOrderHistory/after', `action` = 'extension/total/voucher/send' WHERE event_id = '" . $keep_id . "'");
        }
    }

    private function schedulerInfrastructureReady() {
        foreach (array('setting','codecart_scheduler','codecart_queue','codecart_user_authorize','codecart_customer_authorize','codecart_gdpr_request','codecart_gdpr_audit','codecart_notification','codecart_idempotency','codecart_migration','codecart_admin_login_attempt','codecart_user_mfa','codecart_security_audit') as $requiredTable) {
            if (!$this->tableExists($requiredTable)) { return false; }
        }

        $key = $this->db->query("SELECT value FROM `" . DB_PREFIX . "setting` WHERE store_id = '0' AND `key` = 'codecart_scheduler_key' LIMIT 1");
        $modernization = $this->db->query("SELECT value FROM `" . DB_PREFIX . "setting` WHERE store_id = '0' AND `key` = 'codecart_db_modernization_required' LIMIT 1");
        $featureSettings = $this->db->query("SELECT `key` FROM `" . DB_PREFIX . "setting` WHERE store_id = '0' AND `key` IN ('codecart_spam_service_status','codecart_admin_device_authorize_status','codecart_customer_device_authorize_status','codecart_gdpr_status','config_cookie_consent_status','config_cookie_consent_privacy_information_id')");
        $tasks = $this->db->query("SELECT code FROM `" . DB_PREFIX . "codecart_scheduler` WHERE code IN ('core.currency.refresh','core.cart.cleanup','core.health.check','core.security.cleanup')");
        $taskCodes = array();
        foreach ($tasks->rows as $task) { $taskCodes[(string)$task['code']] = true; }

        return $key->num_rows && strlen((string)$key->row['value']) >= 32 && $modernization->num_rows && $featureSettings->num_rows >= 6 && isset($taskCodes['core.currency.refresh']) && isset($taskCodes['core.cart.cleanup']) && isset($taskCodes['core.health.check']) && isset($taskCodes['core.security.cleanup']);
    }

    private function ensureSchedulerInfrastructure() {
        $this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "codecart_scheduler` (
            `scheduler_id` int(11) NOT NULL AUTO_INCREMENT,
            `code` varchar(128) NOT NULL,
            `route` varchar(255) NOT NULL,
            `args` mediumtext NOT NULL,
            `interval_seconds` int(11) NOT NULL DEFAULT '3600',
            `status` tinyint(1) NOT NULL DEFAULT '1',
            `date_last` datetime DEFAULT NULL,
            `date_next` datetime NOT NULL,
            `last_duration_ms` int(11) NOT NULL DEFAULT '0',
            `last_status` varchar(16) NOT NULL DEFAULT '',
            `last_message` varchar(1000) NOT NULL DEFAULT '',
            `date_added` datetime NOT NULL,
            `date_modified` datetime NOT NULL,
            PRIMARY KEY (`scheduler_id`),
            UNIQUE KEY `code` (`code`),
            KEY `due` (`status`,`date_next`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "codecart_queue` (
            `queue_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            `code` varchar(128) NOT NULL,
            `route` varchar(255) NOT NULL,
            `args` mediumtext NOT NULL,
            `priority` int(11) NOT NULL DEFAULT '100',
            `status` varchar(16) NOT NULL DEFAULT 'pending',
            `attempts` smallint(5) unsigned NOT NULL DEFAULT '0',
            `max_attempts` smallint(5) unsigned NOT NULL DEFAULT '3',
            `available_at` datetime NOT NULL,
            `locked_at` datetime DEFAULT NULL,
            `locked_by` varchar(96) DEFAULT NULL,
            `last_error` text,
            `date_added` datetime NOT NULL,
            `date_modified` datetime NOT NULL,
            PRIMARY KEY (`queue_id`),
            KEY `claim` (`status`,`available_at`,`priority`,`queue_id`),
            KEY `code_status` (`code`,`status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $key = $this->db->query("SELECT setting_id, value FROM `" . DB_PREFIX . "setting` WHERE store_id = '0' AND `key` = 'codecart_scheduler_key' LIMIT 1");
        if (!$key->num_rows || strlen((string)$key->row['value']) < 32) {
            $value = bin2hex(random_bytes(32));
            if ($key->num_rows) {
                $this->db->query("UPDATE `" . DB_PREFIX . "setting` SET code = 'codecart_core', value = '" . $this->db->escape($value) . "', serialized = '0' WHERE setting_id = '" . (int)$key->row['setting_id'] . "'");
            } else {
                $this->db->query("INSERT INTO `" . DB_PREFIX . "setting` SET store_id = '0', code = 'codecart_core', `key` = 'codecart_scheduler_key', value = '" . $this->db->escape($value) . "', serialized = '0'");
            }
            $this->config->set('codecart_scheduler_key', $value);
        }
    }


    private function ensureCoreFeatureInfrastructure() {
        $this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "order_download` (
            `order_download_id` int(11) NOT NULL AUTO_INCREMENT,
            `order_id` int(11) NOT NULL,
            `order_product_id` int(11) NOT NULL,
            `product_id` int(11) NOT NULL,
            `download_id` int(11) NOT NULL,
            `name` varchar(255) NOT NULL,
            `filename` varchar(160) NOT NULL,
            `mask` varchar(128) NOT NULL,
            `date_added` datetime NOT NULL,
            PRIMARY KEY (`order_download_id`),
            UNIQUE KEY `order_product_download` (`order_product_id`,`download_id`),
            KEY `order_id` (`order_id`),
            KEY `product_id` (`product_id`),
            KEY `download_id` (`download_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "codecart_user_authorize` (
            `authorize_id` int(11) NOT NULL AUTO_INCREMENT,
            `user_id` int(11) NOT NULL,
            `token` char(64) NOT NULL DEFAULT '',
            `code_hash` char(64) NOT NULL DEFAULT '',
            `ip` varchar(45) NOT NULL DEFAULT '',
            `user_agent` varchar(512) NOT NULL DEFAULT '',
            `attempts` smallint(5) unsigned NOT NULL DEFAULT '0',
            `status` varchar(16) NOT NULL DEFAULT 'pending',
            `date_added` datetime NOT NULL,
            `date_expire` datetime NOT NULL,
            `date_used` datetime DEFAULT NULL,
            PRIMARY KEY (`authorize_id`),
            UNIQUE KEY `token` (`token`),
            KEY `user_status` (`user_id`,`status`),
            KEY `status_expire` (`status`,`date_expire`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "codecart_admin_login_attempt` (
            `attempt_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            `username` varchar(96) NOT NULL DEFAULT '',
            `ip` varchar(45) NOT NULL DEFAULT '',
            `date_added` datetime NOT NULL,
            PRIMARY KEY (`attempt_id`),
            KEY `username_ip_date` (`username`,`ip`,`date_added`),
            KEY `ip_date` (`ip`,`date_added`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "codecart_user_mfa` (
            `mfa_id` int(11) NOT NULL AUTO_INCREMENT,
            `user_id` int(11) NOT NULL,
            `type` varchar(16) NOT NULL DEFAULT 'totp',
            `secret` text NOT NULL,
            `recovery_codes` mediumtext NOT NULL,
            `status` tinyint(1) NOT NULL DEFAULT '0',
            `last_counter` bigint(20) NOT NULL DEFAULT '-1',
            `date_added` datetime NOT NULL,
            `date_modified` datetime NOT NULL,
            `date_used` datetime DEFAULT NULL,
            PRIMARY KEY (`mfa_id`),
            UNIQUE KEY `user_id` (`user_id`),
            KEY `status` (`status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "codecart_security_audit` (
            `audit_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            `actor_type` varchar(24) NOT NULL DEFAULT '',
            `actor_id` int(11) NOT NULL DEFAULT '0',
            `username` varchar(96) NOT NULL DEFAULT '',
            `event` varchar(96) NOT NULL DEFAULT '',
            `outcome` varchar(24) NOT NULL DEFAULT 'info',
            `severity` varchar(16) NOT NULL DEFAULT 'info',
            `ip` varchar(45) NOT NULL DEFAULT '',
            `user_agent` varchar(512) NOT NULL DEFAULT '',
            `context` mediumtext NOT NULL,
            `date_added` datetime NOT NULL,
            PRIMARY KEY (`audit_id`),
            KEY `event_date` (`event`,`date_added`),
            KEY `actor_date` (`actor_type`,`actor_id`,`date_added`),
            KEY `date_added` (`date_added`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "codecart_customer_authorize` (
            `authorize_id` int(11) NOT NULL AUTO_INCREMENT,
            `customer_id` int(11) NOT NULL,
            `token` char(64) NOT NULL DEFAULT '',
            `code_hash` char(64) NOT NULL DEFAULT '',
            `ip` varchar(45) NOT NULL DEFAULT '',
            `user_agent` varchar(512) NOT NULL DEFAULT '',
            `attempts` smallint(5) unsigned NOT NULL DEFAULT '0',
            `status` varchar(16) NOT NULL DEFAULT 'pending',
            `date_added` datetime NOT NULL,
            `date_expire` datetime NOT NULL,
            `date_used` datetime DEFAULT NULL,
            PRIMARY KEY (`authorize_id`),
            UNIQUE KEY `token` (`token`),
            KEY `customer_status` (`customer_id`,`status`),
            KEY `status_expire` (`status`,`date_expire`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "codecart_gdpr_request` (
            `request_id` int(11) NOT NULL AUTO_INCREMENT,
            `customer_id` int(11) NOT NULL,
            `email` varchar(96) NOT NULL DEFAULT '',
            `type` varchar(16) NOT NULL,
            `token` char(64) NOT NULL DEFAULT '',
            `status` varchar(16) NOT NULL DEFAULT 'pending',
            `date_added` datetime NOT NULL,
            `date_confirmed` datetime DEFAULT NULL,
            `date_processed` datetime DEFAULT NULL,
            `date_expire` datetime NOT NULL,
            PRIMARY KEY (`request_id`),
            KEY `customer_status` (`customer_id`,`status`),
            KEY `token_status` (`token`,`status`),
            KEY `status_added` (`status`,`date_added`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "codecart_gdpr_audit` (
            `audit_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            `request_id` int(11) NOT NULL DEFAULT '0',
            `actor_type` varchar(16) NOT NULL DEFAULT '',
            `actor_id` int(11) NOT NULL DEFAULT '0',
            `action` varchar(64) NOT NULL DEFAULT '',
            `ip` varchar(45) NOT NULL DEFAULT '',
            `details` varchar(1000) NOT NULL DEFAULT '',
            `date_added` datetime NOT NULL,
            PRIMARY KEY (`audit_id`),
            KEY `request_id` (`request_id`),
            KEY `date_added` (`date_added`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "codecart_idempotency` (
            `idempotency_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            `scope` varchar(96) NOT NULL,
            `idempotency_key` char(64) NOT NULL,
            `fingerprint` char(64) NOT NULL DEFAULT '',
            `owner_token` char(64) NOT NULL DEFAULT '',
            `attempt_count` int(10) unsigned NOT NULL DEFAULT '1',
            `status` varchar(16) NOT NULL DEFAULT 'processing',
            `result` mediumtext NOT NULL,
            `date_added` datetime NOT NULL,
            `date_modified` datetime NOT NULL,
            `date_expire` datetime NOT NULL,
            PRIMARY KEY (`idempotency_id`),
            UNIQUE KEY `scope_key` (`scope`,`idempotency_key`),
            KEY `expire` (`date_expire`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $idempotency_columns = $this->columnMap('codecart_idempotency');
        if (!isset($idempotency_columns['owner_token'])) {
            $this->db->query("ALTER TABLE `" . DB_PREFIX . "codecart_idempotency` ADD `owner_token` char(64) NOT NULL DEFAULT '' AFTER `fingerprint`");
        }
        if (!isset($idempotency_columns['attempt_count'])) {
            $this->db->query("ALTER TABLE `" . DB_PREFIX . "codecart_idempotency` ADD `attempt_count` int(10) unsigned NOT NULL DEFAULT '1' AFTER `owner_token`");
        }

        $this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "codecart_migration` (
            `migration_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            `scope` varchar(96) NOT NULL,
            `migration` varchar(190) NOT NULL,
            `version` varchar(32) NOT NULL DEFAULT '',
            `checksum` char(64) NOT NULL DEFAULT '',
            `date_applied` datetime NOT NULL,
            PRIMARY KEY (`migration_id`),
            UNIQUE KEY `scope_migration` (`scope`,`migration`),
            KEY `scope_version` (`scope`,`version`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "codecart_notification` (
            `notification_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            `code` varchar(160) NOT NULL,
            `severity` varchar(16) NOT NULL DEFAULT 'warning',
            `title` varchar(190) NOT NULL DEFAULT '',
            `message` text NOT NULL,
            `route` varchar(255) NOT NULL DEFAULT '',
            `status` varchar(16) NOT NULL DEFAULT 'unread',
            `date_added` datetime NOT NULL,
            `date_modified` datetime NOT NULL,
            PRIMARY KEY (`notification_id`),
            UNIQUE KEY `code` (`code`),
            KEY `status_severity` (`status`,`severity`),
            KEY `date_modified` (`date_modified`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    private function ensureSecuritySchemaParity() {
        if ($this->tableExists('codecart_admin_login_attempt')) {
            $columns = $this->columnMap('codecart_admin_login_attempt');
            if (isset($columns['login_attempt_id']) && !isset($columns['attempt_id'])) {
                $this->db->query("ALTER TABLE `" . DB_PREFIX . "codecart_admin_login_attempt` CHANGE `login_attempt_id` `attempt_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT");
            }
            $this->dropExactIndex('codecart_admin_login_attempt', 'date_added', array('date_added'));
        }

        if ($this->tableExists('codecart_user_mfa')) {
            $columns = $this->columnMap('codecart_user_mfa');
            if (!isset($columns['mfa_id'])) {
                $primary = $this->primaryColumns('codecart_user_mfa');
                if ($primary === array('user_id')) {
                    $this->db->query("ALTER TABLE `" . DB_PREFIX . "codecart_user_mfa` DROP PRIMARY KEY");
                }
                $this->db->query("ALTER TABLE `" . DB_PREFIX . "codecart_user_mfa` ADD `mfa_id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST");
            }
            $this->ensureUniqueIndex('codecart_user_mfa', 'user_id', array('user_id'));
            $columns = $this->columnMap('codecart_user_mfa');
            if (isset($columns['recovery_codes']) && stripos((string)$columns['recovery_codes']['Type'], 'mediumtext') === false) {
                $this->db->query("ALTER TABLE `" . DB_PREFIX . "codecart_user_mfa` MODIFY `recovery_codes` mediumtext NOT NULL");
            }
            if (!isset($columns['last_counter'])) {
                $this->db->query("ALTER TABLE `" . DB_PREFIX . "codecart_user_mfa` ADD `last_counter` bigint(20) NOT NULL DEFAULT '-1' AFTER `status`");
            }
        }

        if ($this->tableExists('codecart_security_audit')) {
            $columns = $this->columnMap('codecart_security_audit');
            if (isset($columns['context']) && stripos((string)$columns['context']['Type'], 'mediumtext') === false) {
                $this->db->query("ALTER TABLE `" . DB_PREFIX . "codecart_security_audit` MODIFY `context` mediumtext NOT NULL");
            }
            $this->dropExactIndex('codecart_security_audit', 'severity_date', array('severity','date_added'));
        }
    }

    private function columnMap($table) {
        $result = array();
        if (!$this->tableExists($table)) { return $result; }
        $query = $this->db->query("SHOW COLUMNS FROM `" . DB_PREFIX . $table . "`");
        foreach ($query->rows as $row) { $result[(string)$row['Field']] = $row; }
        return $result;
    }

    private function primaryColumns($table) {
        $result = array();
        if (!$this->tableExists($table)) { return $result; }
        $query = $this->db->query("SHOW INDEX FROM `" . DB_PREFIX . $table . "` WHERE Key_name='PRIMARY'");
        if ($query->num_rows > 1) {
            usort($query->rows, function($a, $b) { return (int)$a['Seq_in_index'] <=> (int)$b['Seq_in_index']; });
        }
        foreach ($query->rows as $row) { $result[] = (string)$row['Column_name']; }
        return $result;
    }

    private function ensureUniqueIndex($table, $name, array $columns) {
        if (!$this->tableExists($table) || !$columns || !preg_match('/^[a-zA-Z0-9_]+$/', $name)) { return; }
        $query = $this->db->query("SHOW INDEX FROM `" . DB_PREFIX . $table . "`");
        $indexes = array();
        foreach ($query->rows as $row) {
            $key=(string)$row['Key_name']; $seq=(int)$row['Seq_in_index'];
            if (!isset($indexes[$key])) { $indexes[$key]=array('unique'=>(int)$row['Non_unique']===0,'columns'=>array()); }
            $indexes[$key]['columns'][$seq]=(string)$row['Column_name'];
        }
        foreach ($indexes as $index) { ksort($index['columns']); if ($index['unique'] && array_values($index['columns']) === array_values($columns)) { return; } }
        $safe=array(); foreach($columns as $column){ if(!preg_match('/^[a-zA-Z0-9_]+$/',$column)){return;} $safe[]='`'.$column.'`'; }
        if (isset($indexes[$name])) { $name=substr($name.'_cc',0,64); if(isset($indexes[$name])){return;} }
        $this->db->query("ALTER TABLE `" . DB_PREFIX . $table . "` ADD UNIQUE KEY `" . $name . "` (" . implode(',', $safe) . ")");
    }

    private function dropExactIndex($table, $name, array $columns) {
        if (!$this->tableExists($table) || !preg_match('/^[a-zA-Z0-9_]+$/', $name)) { return; }
        $query = $this->db->query("SHOW INDEX FROM `" . DB_PREFIX . $table . "` WHERE Key_name='" . $this->db->escape($name) . "'");
        if ($query->num_rows > 1) {
            usort($query->rows, function($a, $b) { return (int)$a['Seq_in_index'] <=> (int)$b['Seq_in_index']; });
        }
        if (!$query->num_rows) { return; }
        $actual=array(); foreach($query->rows as $row){$actual[]=(string)$row['Column_name'];}
        if ($actual === array_values($columns)) { $this->db->query("ALTER TABLE `" . DB_PREFIX . $table . "` DROP INDEX `" . $name . "`"); }
    }

    private function ensureQuickCheckoutDefaults() {
        if (!$this->tableExists('setting')) { return; }

        $this->ensureScalarSetting('config', 'config_quick_checkout_status', '0');
        $this->ensureScalarSetting('config', 'config_checkout_email_fallback', 'checkout@invalid.local');

        $fields = array('firstname', 'lastname', 'email', 'telephone', 'company', 'address_1', 'address_2', 'city', 'postcode', 'country', 'zone');
        foreach ($fields as $field) {
            $modeKey = 'config_checkout_field_' . $field . '_mode';
            $existing = $this->db->query("SELECT setting_id FROM `" . DB_PREFIX . "setting` WHERE store_id='0' AND `key`='" . $this->db->escape($modeKey) . "' LIMIT 1");
            if ($existing->num_rows) { continue; }

            $legacyKey = 'config_checkout_field_' . $field;
            $legacy = $this->db->query("SELECT value FROM `" . DB_PREFIX . "setting` WHERE store_id='0' AND `key`='" . $this->db->escape($legacyKey) . "' LIMIT 1");
            if ($legacy->num_rows && !(int)$legacy->row['value']) {
                $mode = 'hidden';
            } elseif (in_array($field, array('company', 'address_2'), true)) {
                $mode = 'optional';
            } else {
                $mode = 'required';
            }

            $this->db->query("INSERT INTO `" . DB_PREFIX . "setting` SET store_id='0', code='config', `key`='" . $this->db->escape($modeKey) . "', value='" . $this->db->escape($mode) . "', serialized='0'");
            $this->config->set($modeKey, $mode);
        }
    }

    private function ensureScalarSetting($code, $key, $value) {
        $q = $this->db->query("SELECT setting_id, value FROM `" . DB_PREFIX . "setting` WHERE store_id='0' AND `key`='" . $this->db->escape($key) . "' LIMIT 1");
        if (!$q->num_rows) {
            $this->db->query("INSERT INTO `" . DB_PREFIX . "setting` SET store_id='0', code='" . $this->db->escape($code) . "', `key`='" . $this->db->escape($key) . "', value='" . $this->db->escape($value) . "', serialized='0'");
            $this->config->set($key, $value);
        } else {
            $this->config->set($key, (string)$q->row['value']);
        }
    }

    private function ensurePrivacyInformationPages() {
        if (!$this->tableExists('information') || !$this->tableExists('information_description') || !$this->tableExists('information_to_store') || !$this->tableExists('language')) {
            return;
        }

        $defaultLanguage = $this->db->query("SELECT value FROM `" . DB_PREFIX . "setting` WHERE store_id='0' AND `key`='config_language' LIMIT 1");
        $defaultCode = $defaultLanguage->num_rows ? (string)$defaultLanguage->row['value'] : 'en-gb';
        $pages = array(
            'privacy' => array(
                'setting' => 'config_cookie_consent_privacy_information_id',
                'titles' => array('uk-ua' => 'Політика конфіденційності', 'ru-ru' => 'Политика конфиденциальности', 'en-gb' => 'Privacy Policy'),
                'descriptions' => array(
                    'uk-ua' => '<h2>Політика конфіденційності</h2><p>Ми обробляємо персональні дані лише в обсязі, необхідному для роботи магазину, оформлення та виконання замовлень, підтримки клієнтів, безпеки й виконання законних вимог.</p><p>До таких даних можуть належати ім’я, контактні дані, адреса доставки, інформація про замовлення та технічні дані, необхідні для роботи сайту. Дані зберігаються лише стільки, скільки це потрібно для зазначених цілей або передбачено законом.</p><p>Для виконання замовлення окремі дані можуть передаватися платіжним, транспортним та іншим сервісним партнерам у необхідному обсязі. Контактні дані магазину для питань щодо персональних даних наведені на сторінці контактів.</p>',
                    'ru-ru' => '<h2>Политика конфиденциальности</h2><p>Мы обрабатываем персональные данные только в объёме, необходимом для работы магазина, оформления и выполнения заказов, поддержки клиентов, безопасности и выполнения законных требований.</p><p>К таким данным могут относиться имя, контактные данные, адрес доставки, информация о заказах и технические данные, необходимые для работы сайта. Данные хранятся только столько, сколько это требуется для указанных целей или предусмотрено законом.</p><p>Для выполнения заказа отдельные данные могут передаваться платёжным, транспортным и другим сервисным партнёрам в необходимом объёме. Контактные данные магазина для вопросов о персональных данных указаны на странице контактов.</p>',
                    'en-gb' => '<h2>Privacy Policy</h2><p>We process personal data only to the extent needed to operate the store, place and fulfil orders, provide customer support, protect the service and meet legal requirements.</p><p>This may include your name, contact details, delivery address, order information and technical data required to operate the website. Data is retained only for as long as required for these purposes or by law.</p><p>Where necessary to fulfil an order, relevant data may be shared with payment, delivery and other service providers. The store contact details for privacy enquiries are available on the contact page.</p>'
                )
            ),
            'cookies' => array(
                'setting' => 'config_cookie_consent_information_id',
                'titles' => array('uk-ua' => 'Політика використання cookies', 'ru-ru' => 'Политика использования cookies', 'en-gb' => 'Cookie Policy'),
                'descriptions' => array(
                    'uk-ua' => '<h2>Політика використання cookies</h2><p>Сайт використовує cookies та подібні технології для роботи кошика, авторизації, збереження налаштувань, безпеки та, якщо дозволено, аналітики.</p><p>Необхідні cookies забезпечують базові функції магазину. Необов’язкові категорії використовуються лише відповідно до налаштувань згоди. Ви можете змінити свій вибір через панель налаштувань cookies, якщо вона доступна на сайті.</p>',
                    'ru-ru' => '<h2>Политика использования cookies</h2><p>Сайт использует cookies и аналогичные технологии для работы корзины, авторизации, сохранения настроек, безопасности и, если разрешено, аналитики.</p><p>Необходимые cookies обеспечивают базовые функции магазина. Необязательные категории используются только в соответствии с настройками согласия. Вы можете изменить свой выбор через панель настроек cookies, если она доступна на сайте.</p>',
                    'en-gb' => '<h2>Cookie Policy</h2><p>This website uses cookies and similar technologies to operate the cart, sign-in, preferences, security and, where permitted, analytics.</p><p>Necessary cookies support essential store functions. Optional categories are used only according to consent settings. You can change your choice through the cookie settings panel when it is available on the website.</p>'
                )
            )
        );

        $languages = $this->db->query("SELECT language_id, code FROM `" . DB_PREFIX . "language` WHERE status='1' ORDER BY sort_order, language_id");
        $stores = array(0);
        if ($this->tableExists('store')) {
            $storeQuery = $this->db->query("SELECT store_id FROM `" . DB_PREFIX . "store`");
            foreach ($storeQuery->rows as $store) { $stores[] = (int)$store['store_id']; }
        }
        $stores = array_values(array_unique($stores));

        foreach ($pages as $page) {
            $settingQuery = $this->db->query("SELECT setting_id, value FROM `" . DB_PREFIX . "setting` WHERE store_id='0' AND `key`='" . $this->db->escape($page['setting']) . "' LIMIT 1");
            $informationId = $settingQuery->num_rows ? (int)$settingQuery->row['value'] : 0;
            if ($informationId) {
                $exists = $this->db->query("SELECT information_id FROM `" . DB_PREFIX . "information` WHERE information_id='" . $informationId . "' LIMIT 1");
                if (!$exists->num_rows) { $informationId = 0; }
            }

            if (!$informationId) {
                $defaultTitle = isset($page['titles'][$defaultCode]) ? $page['titles'][$defaultCode] : $page['titles']['en-gb'];
                $defaultLang = $this->db->query("SELECT language_id FROM `" . DB_PREFIX . "language` WHERE code='" . $this->db->escape($defaultCode) . "' LIMIT 1");
                if ($defaultLang->num_rows) {
                    $found = $this->db->query("SELECT information_id FROM `" . DB_PREFIX . "information_description` WHERE language_id='" . (int)$defaultLang->row['language_id'] . "' AND title='" . $this->db->escape($defaultTitle) . "' LIMIT 1");
                    if ($found->num_rows) { $informationId = (int)$found->row['information_id']; }
                }
            }

            if (!$informationId) {
                $sort = $this->db->query("SELECT MAX(sort_order) AS sort_order FROM `" . DB_PREFIX . "information`");
                $sortOrder = $sort->num_rows ? ((int)$sort->row['sort_order'] + 1) : 1;
                $this->db->query("INSERT INTO `" . DB_PREFIX . "information` SET bottom='1', sort_order='" . $sortOrder . "', status='1', noindex='1'");
                $informationId = (int)$this->db->getLastId();
            }

            foreach ($languages->rows as $language) {
                $code = (string)$language['code'];
                $title = isset($page['titles'][$code]) ? $page['titles'][$code] : $page['titles']['en-gb'];
                $description = isset($page['descriptions'][$code]) ? $page['descriptions'][$code] : $page['descriptions']['en-gb'];
                $existingDescription = $this->db->query("SELECT information_id FROM `" . DB_PREFIX . "information_description` WHERE information_id='" . $informationId . "' AND language_id='" . (int)$language['language_id'] . "' LIMIT 1");
                if (!$existingDescription->num_rows) {
                    $this->db->query("INSERT INTO `" . DB_PREFIX . "information_description` SET information_id='" . $informationId . "', language_id='" . (int)$language['language_id'] . "', title='" . $this->db->escape($title) . "', description='" . $this->db->escape($description) . "', meta_title='" . $this->db->escape($title) . "', meta_description='', meta_keyword='', meta_h1=''");
                }
            }
            foreach ($stores as $storeId) {
                $this->db->query("INSERT IGNORE INTO `" . DB_PREFIX . "information_to_store` SET information_id='" . $informationId . "', store_id='" . (int)$storeId . "'");
            }
            if ($settingQuery->num_rows) {
                if ((int)$settingQuery->row['value'] === 0) {
                    $this->db->query("UPDATE `" . DB_PREFIX . "setting` SET value='" . $informationId . "' WHERE setting_id='" . (int)$settingQuery->row['setting_id'] . "'");
                    $this->config->set($page['setting'], $informationId);
                }
            } else {
                $this->db->query("INSERT INTO `" . DB_PREFIX . "setting` SET store_id='0', code='config', `key`='" . $this->db->escape($page['setting']) . "', value='" . $informationId . "', serialized='0'");
                $this->config->set($page['setting'], $informationId);
            }
        }
    }

    private function ensureCoreFeatureDefaults() {
        if (!$this->tableExists('setting')) { return; }
        // Existing CodeCart PRO installs preserve every saved value. For a stock/legacy
        // OpenCart/ocStore upgrade, missing behaviour-changing switches are opt-in: enabling
        // spam filtering, login throttling or response headers without merchant approval can
        // affect forms, admin access, embedded payments or third-party storefront scripts.
        // Fresh-install SQL still owns the secure modern defaults for a new shop.
        $defaults = array(
            'codecart_spam_service_status' => '0',
            'codecart_admin_login_protection_status' => '0',
            'codecart_admin_login_max_attempts' => '5',
            'codecart_admin_login_window_minutes' => '15',
            'codecart_security_audit_retention_days' => '90',
            'codecart_gdpr_retention_days' => '365',
            'codecart_security_headers_status' => '0',
            'codecart_security_nosniff_status' => '0',
            'codecart_security_referrer_policy' => 'strict-origin-when-cross-origin',
            'codecart_security_frame_options_status' => '0',
            'codecart_security_frame_options' => 'SAMEORIGIN',
            'codecart_security_permissions_policy_status' => '0',
            'codecart_security_permissions_policy' => 'camera=(), microphone=()',
            'codecart_security_csp_report_only_status' => '0',
            'codecart_security_csp_report_only_policy' => "default-src 'self'; img-src 'self' data: https:; style-src 'self' 'unsafe-inline' https:; script-src 'self' 'unsafe-inline' https:; font-src 'self' data: https:; connect-src 'self' https:; frame-src 'self' https:",
            'codecart_security_hsts_status' => '0',
            'codecart_security_hsts_max_age' => '31536000',
            'codecart_security_hsts_subdomains' => '0',
            'codecart_admin_device_authorize_status' => '0',
            'codecart_customer_device_authorize_status' => '0',
            'codecart_gdpr_status' => '0',
            'config_seo_filter_index_mode' => 'noindex',
            'config_seo_filter_allowlist' => '',
            'config_seo_presentation_noindex' => '1',
            'config_cookie_consent_status' => '0',
            'config_cookie_consent_days' => '180',
            'config_cookie_consent_information_id' => '0',
            'config_cookie_consent_privacy_information_id' => '0',
            'config_cookie_consent_accent_color' => '#0b6fd3',
            'config_cookie_consent_icon' => 'shield_cookie_check',
            'config_cookie_consent_custom_icon' => ''
        );
        foreach ($defaults as $key => $value) {
            $q = $this->db->query("SELECT setting_id, value FROM `" . DB_PREFIX . "setting` WHERE store_id='0' AND `key`='" . $this->db->escape($key) . "' LIMIT 1");
            if (!$q->num_rows) {
                $this->db->query("INSERT INTO `" . DB_PREFIX . "setting` SET store_id='0', code='codecart_core', `key`='" . $this->db->escape($key) . "', value='" . $this->db->escape($value) . "', serialized='0'");
                $this->config->set($key, $value);
            } else {
                $this->config->set($key, (string)$q->row['value']);
            }
        }

        // New visual tokens are additive on UPDATE. Merchant-customized colours are preserved.
        $themeDefaults = array(
            'theme_default_accent_color' => '#0b6fd3',
            'theme_default_accent_hover' => '#095eb4',
            'theme_default_button_color' => '#0b6fd3',
            'theme_default_button_hover' => '#095eb4',
            'theme_default_button_text_color' => '#ffffff',
            'theme_default_buy_button_color' => '#0b6fd3',
            'theme_default_buy_button_hover' => '#095eb4',
            'theme_default_buy_button_text_color' => '#ffffff',
            'theme_default_sale_price_color' => '#d92d20',
            'theme_default_cart_button_color' => '#0b6fd3',
            'theme_default_cart_button_hover' => '#095eb4',
            'theme_default_cart_button_text_color' => '#ffffff',
            'theme_default_text_color' => '#3d4652',
            'theme_default_heading_color' => '#273142',
            'theme_default_h1_color' => '#273142',
            'theme_default_h2_color' => '#273142',
            'theme_default_background_color' => '#ffffff',
            'theme_default_surface_color' => '#f7f9fb',
            'theme_default_border_color' => '#e5e9ef',
            'theme_default_footer_background_color' => '#303030',
            'theme_default_footer_text_color' => '#e2e2e2',
            'theme_default_footer_link_color' => '#cccccc',
            'theme_default_footer_heading_color' => '#ffffff',
            'theme_default_font_family' => 'system',
            'theme_default_icon_mode' => 'auto',
            'theme_default_commerce_style' => 'standard',
            'theme_default_catalog_ajax_status' => '0',
            'theme_default_header_menu_mode' => 'horizontal',
            'theme_default_product_card_content' => 'description',
            'theme_default_product_card_attribute_limit' => '3',
            'theme_default_option_image_switch_status' => '0',
            'theme_default_purchase_blocks_status' => '1',
            'theme_default_datetimepicker_theme' => 'light'
        );
        foreach ($themeDefaults as $key => $value) {
            $q = $this->db->query("SELECT setting_id FROM `" . DB_PREFIX . "setting` WHERE store_id='0' AND `key`='" . $this->db->escape($key) . "' LIMIT 1");
            if (!$q->num_rows) {
                $this->db->query("INSERT INTO `" . DB_PREFIX . "setting` SET store_id='0', code='theme_default', `key`='" . $this->db->escape($key) . "', value='" . $this->db->escape($value) . "', serialized='0'");
            }
        }
        // Existing merchant colour settings are never rewritten during UPDATE.
    }


    private function ensureAdministratorExtensionPermissions() {
        if (!$this->tableExists('user_group') || !$this->tableExists('extension')) { return; }

        $administrator_group_id = 1;
        if ($this->tableExists('user')) {
            $root_user = $this->db->query("SELECT user_group_id FROM `" . DB_PREFIX . "user` WHERE user_id='1' LIMIT 1");
            if ($root_user->num_rows && (int)$root_user->row['user_group_id'] > 0) {
                $administrator_group_id = (int)$root_user->row['user_group_id'];
            }
        }

        $group = $this->db->query("SELECT permission FROM `" . DB_PREFIX . "user_group` WHERE user_group_id='" . (int)$administrator_group_id . "' LIMIT 1");
        if (!$group->num_rows) { return; }

        $permission = json_decode((string)$group->row['permission'], true);
        if (!is_array($permission)) { $permission = array(); }
        foreach (array('access','modify') as $kind) {
            if (!isset($permission[$kind]) || !is_array($permission[$kind])) { $permission[$kind] = array(); }
        }

        $allowed_types = array('analytics','advertise','captcha','currency','dashboard','feed','fraud','menu','module','payment','report','shipping','theme','total');
        $type_lookup = array_fill_keys($allowed_types, true);
        $extensions = $this->db->query("SELECT type, code FROM `" . DB_PREFIX . "extension` ORDER BY type, code");
        $routes = array();

        foreach ($extensions->rows as $extension) {
            $type = strtolower((string)$extension['type']);
            $code = strtolower((string)$extension['code']);
            if (!isset($type_lookup[$type])) { continue; }
            if (!preg_match('/^[a-z0-9_]+$/', $code)) { continue; }
            $routes[] = 'extension/' . $type . '/' . $code;
            if ($type === 'captcha') { $routes[] = 'captcha/' . $code; }
        }

        $routes[] = 'sale/abandoned_cart';
        $routes = array_values(array_unique($routes));
        $changed = false;
        foreach (array('access','modify') as $kind) {
            $existing = array_fill_keys(array_map('strval', $permission[$kind]), true);
            foreach ($routes as $route) {
                if (!isset($existing[$route])) {
                    $permission[$kind][] = $route;
                    $existing[$route] = true;
                    $changed = true;
                }
            }
            if ($changed) {
                $permission[$kind] = array_values(array_unique(array_map('strval', $permission[$kind])));
                sort($permission[$kind], SORT_STRING);
            }
        }

        if ($changed) {
            $encoded = json_encode($permission, JSON_UNESCAPED_SLASHES);
            if ($encoded === false) { throw new \RuntimeException('Unable to encode Administrator permissions.'); }
            $this->db->query("UPDATE `" . DB_PREFIX . "user_group` SET permission='" . $this->db->escape($encoded) . "' WHERE user_group_id='" . (int)$administrator_group_id . "'");
        }
    }

    private function ensureFormBuilderAdminPermission() {
        if (!$this->tableExists('user_group')) { return; }
        $groups = $this->db->query("SELECT user_group_id, permission FROM `" . DB_PREFIX . "user_group`");
        foreach ($groups->rows as $group) {
            $permission = json_decode((string)$group['permission'], true);
            if (!is_array($permission)) { $permission = array('access'=>array(),'modify'=>array()); }
            $changed = false;
            foreach (array('access','modify') as $kind) {
                if (!isset($permission[$kind]) || !is_array($permission[$kind])) { $permission[$kind] = array(); }
                $may_design = (int)$group['user_group_id'] === 1 || in_array('design/banner', $permission[$kind], true) || in_array('setting/setting', $permission[$kind], true);
                if ($may_design) {
                    foreach (array('design/form', 'extension/module/codecart_form') as $route) {
                        if (!in_array($route, $permission[$kind], true)) {
                            $permission[$kind][] = $route;
                            $changed = true;
                        }
                    }
                    sort($permission[$kind], SORT_STRING);
                }
            }
            if ($changed) {
                $encoded = json_encode($permission, JSON_UNESCAPED_SLASHES);
                if ($encoded !== false) { $this->db->query("UPDATE `" . DB_PREFIX . "user_group` SET permission='" . $this->db->escape($encoded) . "' WHERE user_group_id='" . (int)$group['user_group_id'] . "'"); }
            }
        }
    }

    private function ensureCoreToolsAdminPermission() {
        if (!$this->tableExists('user_group')) { return; }
        $groups = $this->db->query("SELECT user_group_id, permission FROM `" . DB_PREFIX . "user_group`");
        foreach ($groups->rows as $group) {
            $permission = json_decode((string)$group['permission'], true);
            if (!is_array($permission)) { continue; }
            $changed = false;
            foreach (array('access','modify') as $kind) {
                if (!isset($permission[$kind]) || !is_array($permission[$kind])) { continue; }
                if (in_array('setting/setting', $permission[$kind], true)) {
                    foreach (array('tool/codecart_core','tool/notification') as $route) {
                        if (!in_array($route, $permission[$kind], true)) {
                            $permission[$kind][] = $route;
                            $changed = true;
                        }
                    }
                    if ($changed) { sort($permission[$kind]); }
                }
            }
            if ($changed) {
                $this->db->query("UPDATE `" . DB_PREFIX . "user_group` SET permission='" . $this->db->escape(json_encode($permission)) . "' WHERE user_group_id='" . (int)$group['user_group_id'] . "'");
            }
        }
    }


    private function ensureCarrierChoiceShipping() {
        $this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "codecart_carrier_city` (
            `carrier_city_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            `carrier_id` varchar(16) NOT NULL,
            `provider` varchar(24) NOT NULL,
            `external_id` varchar(128) NOT NULL,
            `name` varchar(191) NOT NULL,
            `region` varchar(191) NOT NULL DEFAULT '',
            `district` varchar(191) NOT NULL DEFAULT '',
            `search_name` varchar(255) NOT NULL DEFAULT '',
            `extra_json` text,
            `date_modified` datetime NOT NULL,
            PRIMARY KEY (`carrier_city_id`),
            UNIQUE KEY `carrier_external` (`carrier_id`,`external_id`),
            KEY `carrier_name` (`carrier_id`,`name`),
            KEY `provider` (`provider`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "codecart_order_delivery` (
            `order_id` int(11) NOT NULL,
            `carrier_id` varchar(16) NOT NULL DEFAULT '',
            `provider` varchar(24) NOT NULL DEFAULT '',
            `carrier_name` varchar(128) NOT NULL DEFAULT '',
            `city_external_id` varchar(128) NOT NULL DEFAULT '',
            `city_name` varchar(191) NOT NULL DEFAULT '',
            `branch_external_id` varchar(128) NOT NULL DEFAULT '',
            `branch_name` varchar(255) NOT NULL DEFAULT '',
            `branch_address` varchar(255) NOT NULL DEFAULT '',
            `postcode` varchar(32) NOT NULL DEFAULT '',
            `date_added` datetime NOT NULL,
            PRIMARY KEY (`order_id`),
            KEY `carrier_provider` (`carrier_id`,`provider`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        if ($this->tableExists('extension')) {
            $q = $this->db->query("SELECT extension_id FROM `" . DB_PREFIX . "extension` WHERE type='shipping' AND code='carrier_choice' LIMIT 1");
            if (!$q->num_rows) {
                $this->db->query("INSERT INTO `" . DB_PREFIX . "extension` SET type='shipping', code='carrier_choice'");
            }
        }

        if ($this->tableExists('setting')) {
            $carrierDefault = '[]';
            $store = $this->db->query("SELECT value FROM `" . DB_PREFIX . "setting` WHERE store_id='0' AND `key`='config_name' LIMIT 1");
            if ($store->num_rows && in_array((string)$store->row['value'], array('CodeCart Demo Store', 'CodeCart PRO Demo Store'), true)) {
                $carrierDefault = json_encode(array(
                    array('id'=>'pickup','name'=>array('1'=>'Самовивіз','2'=>'Pickup'),'provider'=>'pickup','api_key'=>'','api_login'=>'','api_password'=>'','api_token'=>'','cost'=>'0.0000','status'=>1,'sort_order'=>0),
                    array('id'=>'novaposhta','name'=>array('1'=>'Нова пошта','2'=>'Nova Poshta'),'provider'=>'nova_poshta','api_key'=>'','api_login'=>'','api_password'=>'','api_token'=>'','cost'=>'0.0000','status'=>1,'sort_order'=>1)
                ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }
            $defaults = array(
                'shipping_carrier_choice_sort_order' => array('2', 0),
                'shipping_carrier_choice_status' => array('0', 0),
                'shipping_carrier_choice_geo_zone_id' => array('0', 0),
                'shipping_carrier_choice_tax_class_id' => array('0', 0),
                'shipping_carrier_choice_carriers' => array($carrierDefault, 1),
                'shipping_carrier_choice_compact_checkout' => array('1', 0)
            );
            foreach ($defaults as $key => $default) {
                $q = $this->db->query("SELECT setting_id FROM `" . DB_PREFIX . "setting` WHERE store_id='0' AND `key`='" . $this->db->escape($key) . "' LIMIT 1");
                if (!$q->num_rows) {
                    $this->db->query("INSERT INTO `" . DB_PREFIX . "setting` SET store_id='0', code='shipping_carrier_choice', `key`='" . $this->db->escape($key) . "', value='" . $this->db->escape($default[0]) . "', serialized='" . (int)$default[1] . "'");
                }
            }
            $this->db->query("DELETE FROM `" . DB_PREFIX . "setting` WHERE store_id='0' AND `key`='shipping_carrier_choice_exclusive_quick'");
        }

        if ($this->tableExists('user_group')) {
            $groups = $this->db->query("SELECT user_group_id, permission FROM `" . DB_PREFIX . "user_group`");
            foreach ($groups->rows as $group) {
                $permission = json_decode((string)$group['permission'], true);
                if (!is_array($permission)) { continue; }
                $changed = false;
                foreach (array('access', 'modify') as $kind) {
                    if (!isset($permission[$kind]) || !is_array($permission[$kind])) { continue; }
                    if (in_array('setting/setting', $permission[$kind], true) && !in_array('extension/shipping/carrier_choice', $permission[$kind], true)) {
                        $permission[$kind][] = 'extension/shipping/carrier_choice';
                        sort($permission[$kind]);
                        $changed = true;
                    }
                }
                if ($changed) {
                    $this->db->query("UPDATE `" . DB_PREFIX . "user_group` SET permission='" . $this->db->escape(json_encode($permission)) . "' WHERE user_group_id='" . (int)$group['user_group_id'] . "'");
                }
            }
        }
    }


    private function ensureGoogleLoginModule() {
        $this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "codecart_google_identity` (
            `google_identity_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            `customer_id` int(11) NOT NULL,
            `google_sub_hash` char(64) NOT NULL,
            `date_added` datetime NOT NULL,
            `date_modified` datetime NOT NULL,
            PRIMARY KEY (`google_identity_id`),
            UNIQUE KEY `google_sub_hash` (`google_sub_hash`),
            UNIQUE KEY `customer_id` (`customer_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        if ($this->tableExists('extension')) {
            $q = $this->db->query("SELECT extension_id FROM `" . DB_PREFIX . "extension` WHERE type='module' AND code='google_login' LIMIT 1");
            if (!$q->num_rows) { $this->db->query("INSERT INTO `" . DB_PREFIX . "extension` SET type='module', code='google_login'"); }
        }

        if ($this->tableExists('setting')) {
            $defaults = array(
                'module_google_login_status' => array('0', 0),
                'module_google_login_client_id' => array('', 0),
                'module_google_login_client_secret' => array('', 0),
                'module_google_login_auto_register' => array('1', 0),
                'module_google_login_show_login' => array('1', 0),
                'module_google_login_show_checkout' => array('0', 0),
                'module_google_login_sort_order' => array('0', 0)
            );
            foreach ($defaults as $key => $default) {
                $q = $this->db->query("SELECT setting_id FROM `" . DB_PREFIX . "setting` WHERE store_id='0' AND `key`='" . $this->db->escape($key) . "' LIMIT 1");
                if (!$q->num_rows) {
                    $this->db->query("INSERT INTO `" . DB_PREFIX . "setting` SET store_id='0', code='module_google_login', `key`='" . $this->db->escape($key) . "', value='" . $this->db->escape($default[0]) . "', serialized='" . (int)$default[1] . "'");
                }
            }
        }

        if ($this->tableExists('user_group')) {
            $groups = $this->db->query("SELECT user_group_id, permission FROM `" . DB_PREFIX . "user_group`");
            foreach ($groups->rows as $group) {
                $permission = json_decode((string)$group['permission'], true);
                if (!is_array($permission)) { continue; }
                $changed = false;
                foreach (array('access', 'modify') as $kind) {
                    if (!isset($permission[$kind]) || !is_array($permission[$kind])) { continue; }
                    if (in_array('setting/setting', $permission[$kind], true) && !in_array('extension/module/google_login', $permission[$kind], true)) {
                        $permission[$kind][] = 'extension/module/google_login';
                        sort($permission[$kind]);
                        $changed = true;
                    }
                }
                if ($changed) {
                    $this->db->query("UPDATE `" . DB_PREFIX . "user_group` SET permission='" . $this->db->escape(json_encode($permission)) . "' WHERE user_group_id='" . (int)$group['user_group_id'] . "'");
                }
            }
        }
    }

    private function ensureEditorDefault() {
        if (!$this->tableExists('setting')) { return; }
        $q = $this->db->query("SELECT setting_id,value FROM `" . DB_PREFIX . "setting` WHERE store_id='0' AND `key`='config_editor' LIMIT 1");
        if ($q->num_rows) {
            $current = strtolower(trim((string)$q->row['value']));
            // RC builds previously offered TinyMCE through a CDN. Migrate only that
            // Core-known value (or an empty value) to the bundled local editor.
            // Unknown values may belong to a third-party editor and are preserved.
            if ($current === '' || $current === 'tinymce' || $current === 'tinymce8') {
                $this->db->query("UPDATE `" . DB_PREFIX . "setting` SET code='config',value='summernote',serialized='0' WHERE setting_id='" . (int)$q->row['setting_id'] . "'");
                $this->config->set('config_editor', 'summernote');
            } else {
                $this->config->set('config_editor', (string)$q->row['value']);
            }
        } else {
            $this->db->query("INSERT INTO `" . DB_PREFIX . "setting` SET store_id='0',code='config',`key`='config_editor',value='summernote',serialized='0'");
            $this->config->set('config_editor', 'summernote');
        }
    }

    private function ensureUahCurrencySymbol() {
        if (!$this->tableExists('currency')) { return; }

        $query = $this->db->query("SELECT currency_id, symbol_left, symbol_right FROM `" . DB_PREFIX . "currency` WHERE code='UAH' LIMIT 1");
        if (!$query->num_rows) { return; }

        $left = trim((string)$query->row['symbol_left']);
        $right = trim((string)$query->row['symbol_right']);
        $legacy = array('', 'грн', 'грн.', 'UAH');

        // Do not overwrite a merchant-defined presentation. Only normalize the
        // default/legacy OpenCart-ocStore textual hryvnia forms to the official sign.
        if (in_array($left, $legacy, true) && in_array($right, $legacy, true)) {
            $this->db->query("UPDATE `" . DB_PREFIX . "currency` SET symbol_left='', symbol_right=' ₴', date_modified=NOW() WHERE currency_id='" . (int)$query->row['currency_id'] . "'");
        }
    }

    private function ensureCurrencyProviders() {
        foreach (array('nbu', 'ecb') as $code) {
            $exists = $this->db->query("SELECT extension_id FROM `" . DB_PREFIX . "extension` WHERE type = 'currency' AND code = '" . $this->db->escape($code) . "' LIMIT 1");
            if (!$exists->num_rows) {
                $this->db->query("INSERT INTO `" . DB_PREFIX . "extension` SET type = 'currency', code = '" . $this->db->escape($code) . "'");
            }
            $setting = $this->db->query("SELECT setting_id FROM `" . DB_PREFIX . "setting` WHERE store_id = '0' AND `key` = 'currency_" . $this->db->escape($code) . "_status' LIMIT 1");
            if (!$setting->num_rows) {
                // New providers are made available but remain OFF on UPDATE.
                $this->db->query("INSERT INTO `" . DB_PREFIX . "setting` SET store_id = '0', code = 'currency_" . $this->db->escape($code) . "', `key` = 'currency_" . $this->db->escape($code) . "_status', value = '0', serialized = '0'");
            }
        }

        $nbuDefaults = array(
            'currency_nbu_source' => 'auto',
            'currency_nbu_timeout' => '15',
            'currency_nbu_include_disabled' => '0',
            'currency_nbu_missing_policy' => 'skip'
        );
        foreach ($nbuDefaults as $key => $value) {
            $setting = $this->db->query("SELECT setting_id FROM `" . DB_PREFIX . "setting` WHERE store_id = '0' AND `key` = '" . $this->db->escape($key) . "' LIMIT 1");
            if (!$setting->num_rows) {
                $this->db->query("INSERT INTO `" . DB_PREFIX . "setting` SET store_id = '0', code = 'currency_nbu', `key` = '" . $this->db->escape($key) . "', value = '" . $this->db->escape($value) . "', serialized = '0'");
            }
        }

        // Do not change config_currency_engine during UPDATE. A live store may use
        // a third-party updater or intentionally manage rates manually. Fresh-install
        // SQL owns the CodeCart PRO default; upgrades only make NBU/ECB available.
    }


    private function ensureMailCampaignInfrastructure() {
        $this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "mail_campaign` (
            `campaign_id` int(11) NOT NULL AUTO_INCREMENT,
            `store_id` int(11) NOT NULL DEFAULT '0',
            `audience` varchar(32) NOT NULL,
            `subject` varchar(255) NOT NULL,
            `message` mediumtext NOT NULL,
            `status` varchar(16) NOT NULL DEFAULT 'queued',
            `total` int(11) unsigned NOT NULL DEFAULT '0',
            `sent` int(11) unsigned NOT NULL DEFAULT '0',
            `failed` int(11) unsigned NOT NULL DEFAULT '0',
            `suppressed` int(11) unsigned NOT NULL DEFAULT '0',
            `user_id` int(11) NOT NULL DEFAULT '0',
            `date_added` datetime NOT NULL,
            `date_started` datetime DEFAULT NULL,
            `date_finished` datetime DEFAULT NULL,
            `date_modified` datetime NOT NULL,
            PRIMARY KEY (`campaign_id`), KEY `status_added` (`status`,`date_added`), KEY `store_id` (`store_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "mail_campaign_queue` (
            `mail_queue_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            `campaign_id` int(11) NOT NULL,
            `customer_id` int(11) NOT NULL DEFAULT '0',
            `email` varchar(96) NOT NULL,
            `name` varchar(191) NOT NULL DEFAULT '',
            `unsubscribe_token` char(64) DEFAULT NULL,
            `status` varchar(16) NOT NULL DEFAULT 'waiting',
            `attempts` tinyint(3) unsigned NOT NULL DEFAULT '0',
            `last_error` varchar(255) NOT NULL DEFAULT '',
            `date_added` datetime NOT NULL,
            `date_sent` datetime DEFAULT NULL,
            `date_modified` datetime NOT NULL,
            PRIMARY KEY (`mail_queue_id`), UNIQUE KEY `campaign_email` (`campaign_id`,`email`), UNIQUE KEY `unsubscribe_token` (`unsubscribe_token`), KEY `campaign_status` (`campaign_id`,`status`), KEY `status_added` (`status`,`date_added`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "mail_suppression` (`email` varchar(96) NOT NULL, `reason` varchar(32) NOT NULL DEFAULT 'unsubscribe', `date_added` datetime NOT NULL, PRIMARY KEY (`email`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    private function ensureStockNotifyInfrastructure() {
        $this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "stock_notify` (
            `stock_notify_id` int(11) NOT NULL AUTO_INCREMENT,
            `product_id` int(11) NOT NULL,
            `customer_id` int(11) NOT NULL DEFAULT '0',
            `store_id` int(11) NOT NULL DEFAULT '0',
            `language_id` int(11) NOT NULL DEFAULT '0',
            `email` varchar(96) NOT NULL,
            `option_hash` char(64) NOT NULL DEFAULT '',
            `option_data` text,
            `option_label` varchar(255) NOT NULL DEFAULT '',
            `status` varchar(16) NOT NULL DEFAULT 'waiting',
            `attempts` tinyint(3) unsigned NOT NULL DEFAULT '0',
            `date_added` datetime NOT NULL,
            `date_sent` datetime DEFAULT NULL,
            `date_modified` datetime NOT NULL,
            PRIMARY KEY (`stock_notify_id`),
            UNIQUE KEY `uniq_product_email_option` (`product_id`,`store_id`,`email`,`option_hash`),
            KEY `status_product` (`status`,`product_id`),
            KEY `customer_id` (`customer_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        if (!$this->columnExists('stock_notify','option_hash')) { $this->db->query("ALTER TABLE `" . DB_PREFIX . "stock_notify` ADD `option_hash` char(64) NOT NULL DEFAULT '' AFTER `email`"); }
        if (!$this->columnExists('stock_notify','option_data')) { $this->db->query("ALTER TABLE `" . DB_PREFIX . "stock_notify` ADD `option_data` text AFTER `option_hash`"); }
        if (!$this->columnExists('stock_notify','option_label')) { $this->db->query("ALTER TABLE `" . DB_PREFIX . "stock_notify` ADD `option_label` varchar(255) NOT NULL DEFAULT '' AFTER `option_data`"); }
        $oldIndex=$this->db->query("SHOW INDEX FROM `" . DB_PREFIX . "stock_notify` WHERE Key_name='uniq_product_email'");
        if ($oldIndex->num_rows) { $this->db->query("ALTER TABLE `" . DB_PREFIX . "stock_notify` DROP INDEX `uniq_product_email`"); }
        $newIndex=$this->db->query("SHOW INDEX FROM `" . DB_PREFIX . "stock_notify` WHERE Key_name='uniq_product_email_option'");
        if (!$newIndex->num_rows) { $this->db->query("ALTER TABLE `" . DB_PREFIX . "stock_notify` ADD UNIQUE KEY `uniq_product_email_option` (`product_id`,`store_id`,`email`,`option_hash`)"); }
    }

    private function ensureCoreSchedulerTasks() {
        if (!$this->tableExists('codecart_scheduler')) {
            return;
        }
        $tasks = array(
            array('code' => 'core.currency.refresh', 'route' => 'cron/currency', 'interval' => 86400),
            array('code' => 'core.cart.cleanup', 'route' => 'cron/cart', 'interval' => 3600),
            array('code' => 'core.health.check', 'route' => 'cron/health', 'interval' => 21600),
            array('code' => 'core.security.cleanup', 'route' => 'cron/security', 'interval' => 86400),
            array('code' => 'core.stock.notify', 'route' => 'cron/stock_notify', 'interval' => 300),
            array('code' => 'core.mail.campaign', 'route' => 'cron/mail_campaign', 'interval' => 60),
            array('code' => 'core.queue.worker', 'route' => 'cron/queue_worker', 'interval' => 60)
        );
        foreach ($tasks as $task) {
            $code = $task['code']; $route = $task['route']; $interval = (int)$task['interval'];
            $query = $this->db->query("SELECT scheduler_id FROM `" . DB_PREFIX . "codecart_scheduler` WHERE code = '" . $this->db->escape($code) . "' LIMIT 1");
            if ($query->num_rows) {
                $this->db->query("UPDATE `" . DB_PREFIX . "codecart_scheduler` SET route = '" . $this->db->escape($route) . "', interval_seconds = '" . $interval . "', date_modified = NOW() WHERE scheduler_id = '" . (int)$query->row['scheduler_id'] . "'");
            } else {
                $default_status = in_array($code, array('core.stock.notify','core.mail.campaign','core.queue.worker'), true) ? 1 : 0;
                $this->db->query("INSERT INTO `" . DB_PREFIX . "codecart_scheduler` SET code = '" . $this->db->escape($code) . "', route = '" . $this->db->escape($route) . "', args = '{}', interval_seconds = '" . $interval . "', status = '" . (int)$default_status . "', date_next = NOW(), date_added = NOW(), date_modified = NOW()");
            }
        }
    }

    private function ensureSchedulerAdminPermission() {
        if (!$this->tableExists('user_group')) { return; }
        $groups = $this->db->query("SELECT user_group_id, permission FROM `" . DB_PREFIX . "user_group`");
        foreach ($groups->rows as $group) {
            $permission = json_decode((string)$group['permission'], true);
            if (!is_array($permission)) { continue; }
            $changed = false;
            foreach (array('access','modify') as $kind) {
                if (!isset($permission[$kind]) || !is_array($permission[$kind])) { continue; }
                if (in_array('setting/setting', $permission[$kind], true) && !in_array('tool/scheduler', $permission[$kind], true)) {
                    $permission[$kind][] = 'tool/scheduler';
                    sort($permission[$kind]);
                    $changed = true;
                }
                if ((in_array('marketing/coupon', $permission[$kind], true) || in_array('marketing/contact', $permission[$kind], true)) && !in_array('marketing/stock_notify', $permission[$kind], true)) {
                    $permission[$kind][] = 'marketing/stock_notify';
                    sort($permission[$kind]);
                    $changed = true;
                }
            }
            if ($changed) {
                $this->db->query("UPDATE `" . DB_PREFIX . "user_group` SET permission = '" . $this->db->escape(json_encode($permission)) . "' WHERE user_group_id = '" . (int)$group['user_group_id'] . "'");
            }
        }
    }


    /**
     * ocStore <= 3.0.3.x ships the "domovoy" dashboard, renamed to "domovyk" in
     * ocStore 3.0.4+ / CodeCart. The legacy controller is not PHP 8 compatible
     * (warnings on every dashboard view and a directory-size scan on each load).
     * Move its settings/permissions to domovyk and uninstall only the legacy
     * extension record; files are left untouched.
     */
    /**
     * Builds up to 1.9.9 imported the demo HTML module with unescaped quotes inside its
     * JSON setting, so json_decode() returned null: the block was not rendered and the
     * admin form opened empty. Repair only rows that are invalid JSON and become valid
     * after escaping HTML attribute quotes; valid or unrecognised rows are left untouched.
     */
    private function repairInvalidModuleSettings() {
        if (!$this->tableExists('module')) { return; }
        $query = $this->db->query("SELECT module_id, code, setting FROM `" . DB_PREFIX . "module`");
        foreach ($query->rows as $row) {
            $setting = (string)$row['setting'];
            $decoded = json_decode($setting, true);
            $changed = false;
            if ($setting !== '' && !is_array($decoded)) {
                $fixed = preg_replace('/(\s[a-zA-Z_:-]+)="([^"<>]*)"/', '$1=\\\\"$2\\\\"', $setting);
                $decoded = is_string($fixed) ? json_decode($fixed, true) : null;
                if (!is_array($decoded)) { continue; }
                $changed = true;
            }
            if (!is_array($decoded)) { continue; }
            // The same demo row used {"description": {"<language_id>": "<html>"}} while the
            // HTML module reads module_description[<language_id>][title|description].
            if ($row['code'] === 'html' && !isset($decoded['module_description']) && isset($decoded['description']) && is_array($decoded['description'])) {
                $descriptions = array();
                foreach ($decoded['description'] as $languageId => $html) {
                    if (is_string($html)) { $descriptions[(int)$languageId] = array('title' => '', 'description' => $html); }
                }
                unset($decoded['description']);
                $decoded['module_description'] = $descriptions;
                $changed = true;
            }
            if (!$changed) { continue; }
            $this->db->query("UPDATE `" . DB_PREFIX . "module` SET setting = '" . $this->db->escape(json_encode($decoded, JSON_UNESCAPED_UNICODE)) . "' WHERE module_id = '" . (int)$row['module_id'] . "'");
        }
    }

    /**
     * Forms saved from the admin before 1.9.10 stored OpenCart's HTML-escaped POST values
     * ("&amp;", "&quot;", "&lt;b&gt;"). The storefront prints titles escaped and the
     * description as HTML, so shoppers saw entities and literal tags. Decode only rows
     * that carry escaped markup and no real markup; clean rows are left untouched.
     */
    private function repairEntityEncodedFormTexts() {
        if (!$this->tableExists('codecart_form_description')) { return; }
        $pattern = '/&(?:amp|quot|#0?39|lt|gt);/';
        $query = $this->db->query("SELECT form_id, language_id, title, description, submit_text, success_text FROM `" . DB_PREFIX . "codecart_form_description`");
        foreach ($query->rows as $row) {
            $set = array();
            foreach (array('title', 'submit_text', 'success_text') as $field) {
                $value = (string)$row[$field];
                if (preg_match($pattern, $value)) { $set[] = "`" . $field . "` = '" . $this->db->escape(html_entity_decode($value, ENT_QUOTES, 'UTF-8')) . "'"; }
            }
            $description = (string)$row['description'];
            if (strpos($description, '<') === false && preg_match('/&lt;[a-z\/]/i', $description)) {
                $decoded = html_entity_decode($description, ENT_QUOTES, 'UTF-8');
                if (class_exists('\\CodeCart\\Core\\SafeRichHtml')) { $decoded = \CodeCart\Core\SafeRichHtml::sanitize($decoded); }
                $set[] = "`description` = '" . $this->db->escape($decoded) . "'";
            }
            if ($set) {
                $this->db->query("UPDATE `" . DB_PREFIX . "codecart_form_description` SET " . implode(', ', $set) . " WHERE form_id = '" . (int)$row['form_id'] . "' AND language_id = '" . (int)$row['language_id'] . "'");
            }
        }
        if ($this->tableExists('codecart_form')) {
            $forms = $this->db->query("SELECT form_id, name FROM `" . DB_PREFIX . "codecart_form`");
            foreach ($forms->rows as $row) {
                if (preg_match($pattern, (string)$row['name'])) {
                    $this->db->query("UPDATE `" . DB_PREFIX . "codecart_form` SET name = '" . $this->db->escape(html_entity_decode((string)$row['name'], ENT_QUOTES, 'UTF-8')) . "' WHERE form_id = '" . (int)$row['form_id'] . "'");
                }
            }
        }
    }

    /**
     * category_path must mirror the parent_id chain. The 1.9.x demo shipped one category
     * whose path pointed to another branch (breadcrumbs/filters used the wrong parent until
     * an admin re-saved it). Rebuild the path rows only for categories whose stored path
     * differs from the parent chain; consistent trees are not touched.
     */
    private function repairInconsistentCategoryPaths() {
        if (!$this->tableExists('category') || !$this->tableExists('category_path')) { return; }
        $parents = array();
        foreach ($this->db->query("SELECT category_id, parent_id FROM `" . DB_PREFIX . "category`")->rows as $row) {
            $parents[(int)$row['category_id']] = (int)$row['parent_id'];
        }
        if (!$parents || count($parents) > 50000) { return; }
        $paths = array();
        foreach ($this->db->query("SELECT category_id, path_id, level FROM `" . DB_PREFIX . "category_path` ORDER BY category_id, level")->rows as $row) {
            $paths[(int)$row['category_id']][] = (int)$row['path_id'];
        }
        foreach ($parents as $categoryId => $parentId) {
            $chain = array($categoryId);
            $seen = array($categoryId => true);
            while ($parentId > 0 && isset($parents[$parentId]) && !isset($seen[$parentId])) {
                array_unshift($chain, $parentId);
                $seen[$parentId] = true;
                $parentId = $parents[$parentId];
            }
            if ($parentId > 0) { continue; } // cycle or orphan parent: leave for manual repair
            $current = isset($paths[$categoryId]) ? $paths[$categoryId] : array();
            if ($current === $chain) { continue; }
            $this->db->query("DELETE FROM `" . DB_PREFIX . "category_path` WHERE category_id = '" . (int)$categoryId . "'");
            foreach ($chain as $level => $pathId) {
                $this->db->query("INSERT INTO `" . DB_PREFIX . "category_path` SET category_id = '" . (int)$categoryId . "', path_id = '" . (int)$pathId . "', level = '" . (int)$level . "'");
            }
        }
    }

    private function migrateLegacyDomovoyDashboard() {
        if (!$this->tableExists('extension') || !$this->tableExists('setting')) { return; }
        $legacy = $this->db->query("SELECT extension_id FROM `" . DB_PREFIX . "extension` WHERE type='dashboard' AND code='domovoy' LIMIT 1");
        if (!$legacy->num_rows) { return; }
        $modern = rtrim(DIR_SYSTEM, '/\\') . '/../admin/controller/extension/dashboard/domovyk.php';
        if (!is_file($modern)) { return; }

        $current = $this->db->query("SELECT extension_id FROM `" . DB_PREFIX . "extension` WHERE type='dashboard' AND code='domovyk' LIMIT 1");
        if (!$current->num_rows) {
            $this->db->query("INSERT INTO `" . DB_PREFIX . "extension` SET type='dashboard', code='domovyk'");
            $rows = $this->db->query("SELECT store_id, `key`, value, serialized FROM `" . DB_PREFIX . "setting` WHERE code='dashboard_domovoy'");
            foreach ($rows->rows as $row) {
                $key = preg_replace('/^dashboard_domovoy_/', 'dashboard_domovyk_', (string)$row['key']);
                $exists = $this->db->query("SELECT setting_id FROM `" . DB_PREFIX . "setting` WHERE store_id='" . (int)$row['store_id'] . "' AND `key`='" . $this->db->escape($key) . "' LIMIT 1");
                if (!$exists->num_rows) {
                    $this->db->query("INSERT INTO `" . DB_PREFIX . "setting` SET store_id='" . (int)$row['store_id'] . "', code='dashboard_domovyk', `key`='" . $this->db->escape($key) . "', value='" . $this->db->escape((string)$row['value']) . "', serialized='" . (int)$row['serialized'] . "'");
                }
            }
        }

        if ($this->tableExists('user_group')) {
            $groups = $this->db->query("SELECT user_group_id, permission FROM `" . DB_PREFIX . "user_group`");
            foreach ($groups->rows as $group) {
                $permission = json_decode((string)$group['permission'], true);
                if (!is_array($permission)) { continue; }
                $changed = false;
                foreach (array('access', 'modify') as $type) {
                    if (isset($permission[$type]) && is_array($permission[$type]) && in_array('extension/dashboard/domovoy', $permission[$type], true) && !in_array('extension/dashboard/domovyk', $permission[$type], true)) {
                        $permission[$type][] = 'extension/dashboard/domovyk';
                        $changed = true;
                    }
                }
                if ($changed) {
                    $this->db->query("UPDATE `" . DB_PREFIX . "user_group` SET permission='" . $this->db->escape(json_encode($permission)) . "' WHERE user_group_id='" . (int)$group['user_group_id'] . "'");
                }
            }
        }

        $this->db->query("DELETE FROM `" . DB_PREFIX . "extension` WHERE type='dashboard' AND code='domovoy'");
        $this->db->query("DELETE FROM `" . DB_PREFIX . "setting` WHERE code='dashboard_domovoy'");
        if ($this->log) { $this->log->write('CodeCart PRO: legacy ocStore dashboard "domovoy" was migrated to "domovyk".'); }
    }

    private function ensureDashboardHealthInfrastructure() {
        if ($this->tableExists('extension')) {
            $q = $this->db->query("SELECT extension_id FROM `" . DB_PREFIX . "extension` WHERE type='dashboard' AND code='codecart_health' LIMIT 1");
            if (!$q->num_rows) {
                $this->db->query("INSERT INTO `" . DB_PREFIX . "extension` SET type='dashboard', code='codecart_health'");
            }
        }

        if ($this->tableExists('setting')) {
            $defaults = array(
                'dashboard_codecart_health_status' => '1',
                'dashboard_codecart_health_width' => '12',
                'dashboard_codecart_health_sort_order' => '11',
                'dashboard_codecart_health_commerce_status' => '1',
                'dashboard_codecart_health_attention_status' => '1',
                'dashboard_codecart_health_system_status' => '1',
                'dashboard_codecart_health_performance_status' => '1',
                'dashboard_codecart_health_seo_status' => '1',
                'dashboard_codecart_health_security_status' => '1',
                'dashboard_codecart_health_quick_status' => '1',
                'dashboard_codecart_health_low_stock_limit' => '5'
            );
            foreach ($defaults as $key => $value) {
                $q = $this->db->query("SELECT setting_id,value FROM `" . DB_PREFIX . "setting` WHERE store_id='0' AND `key`='" . $this->db->escape($key) . "' LIMIT 1");
                if (!$q->num_rows) {
                    $this->db->query("INSERT INTO `" . DB_PREFIX . "setting` SET store_id='0', code='dashboard_codecart_health', `key`='" . $this->db->escape($key) . "', value='" . $this->db->escape($value) . "', serialized='0'");
                    $this->config->set($key, $value);
                } else {
                    $currentValue = (string)$q->row['value'];
                    if ($key === 'dashboard_codecart_health_sort_order' && ($currentValue === '' || $currentValue === '0')) {
                        $currentValue = '11';
                        $this->db->query("UPDATE `" . DB_PREFIX . "setting` SET value='11', serialized='0' WHERE setting_id='" . (int)$q->row['setting_id'] . "'");
                    }
                    $this->config->set($key, $currentValue);
                }
            }

            $quickKey = 'dashboard_codecart_health_quick_actions';
            $quick = $this->db->query("SELECT setting_id,value,serialized FROM `" . DB_PREFIX . "setting` WHERE store_id='0' AND `key`='" . $quickKey . "' LIMIT 1");
            if (!$quick->num_rows) {
                $quickValue = json_encode(array('product_add','orders','cache','ocmod','diagnostics','scheduler','logs'));
                $this->db->query("INSERT INTO `" . DB_PREFIX . "setting` SET store_id='0', code='dashboard_codecart_health', `key`='" . $quickKey . "', value='" . $this->db->escape($quickValue) . "', serialized='1'");
                $this->config->set($quickKey, array('product_add','orders','cache','ocmod','diagnostics','scheduler','logs'));
            }
        }

        if ($this->tableExists('user_group')) {
            $groups = $this->db->query("SELECT user_group_id, permission FROM `" . DB_PREFIX . "user_group`");
            foreach ($groups->rows as $group) {
                $permission = json_decode((string)$group['permission'], true);
                if (!is_array($permission)) { continue; }
                $changed = false;
                foreach (array('access','modify') as $kind) {
                    if (!isset($permission[$kind]) || !is_array($permission[$kind])) { continue; }
                    if (in_array('setting/setting', $permission[$kind], true) && !in_array('extension/dashboard/codecart_health', $permission[$kind], true)) {
                        $permission[$kind][] = 'extension/dashboard/codecart_health';
                        sort($permission[$kind]);
                        $changed = true;
                    }
                }
                if ($changed) {
                    $this->db->query("UPDATE `" . DB_PREFIX . "user_group` SET permission='" . $this->db->escape(json_encode($permission, JSON_UNESCAPED_SLASHES)) . "' WHERE user_group_id='" . (int)$group['user_group_id'] . "'");
                }
            }
        }
    }

    private function hasPendingFilesystemUpdate() {
        if (!defined('DIR_SYSTEM') || !defined('DIR_STORAGE')) { return false; }
        $publicStorage = rtrim(str_replace('\\', '/', DIR_SYSTEM . 'storage/'), '/') . '/';
        $activeStorage = rtrim(str_replace('\\', '/', DIR_STORAGE), '/') . '/';
        if (is_file($activeStorage . 'codecart/storage-relocation.pending')) { return true; }
        if ($activeStorage === $publicStorage) { return false; }
        if (!is_file($publicStorage . 'vendor/autoload.php')) { return false; }
        $marker = $activeStorage . 'codecart/vendor-build';
        $expected = defined('CODECART_PACKAGE_BUILD') ? (string)CODECART_PACKAGE_BUILD : (defined('CODECART_BUILD') ? (string)CODECART_BUILD : self::VERSION);
        $actual = is_file($marker) ? trim((string)@file_get_contents($marker)) : '';
        return $actual === '' || !hash_equals($expected, $actual);
    }

    private function removePublicComposerMetadata() {
        if (!defined('DIR_SYSTEM')) { return; }

        // Old CodeCart/OpenCart packages could leave Composer's installed.json in
        // system/storage. It is not required at runtime and reveals the complete
        // dependency/version inventory when storage is still web-accessible on a
        // server that ignores .htaccess (for example, an unconfigured Nginx host).
        $file = rtrim(DIR_SYSTEM, '/\\') . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'composer' . DIRECTORY_SEPARATOR . 'installed.json';
        if (is_file($file) && !@unlink($file) && $this->log) {
            $this->log->write('CodeCart PRO security: unable to remove public Composer installed.json.');
        }
    }

    private function syncStagedVendorToExternalStorage($forceRepair = false) {
        if (!defined('DIR_SYSTEM') || !defined('DIR_STORAGE')) { return; }
        $publicStorage = rtrim(str_replace('\\', '/', DIR_SYSTEM . 'storage/'), '/') . '/';
        $activeStorage = rtrim(str_replace('\\', '/', DIR_STORAGE), '/') . '/';
        if ($activeStorage === $publicStorage) { return; }
        $sourceVendor = $publicStorage . 'vendor/';
        $activeVendor = $activeStorage . 'vendor/';
        if (!is_file($sourceVendor . 'autoload.php')) { return; }

        $marker = $activeStorage . 'codecart/vendor-build';
        $expected = defined('CODECART_PACKAGE_BUILD') ? (string)CODECART_PACKAGE_BUILD : (defined('CODECART_BUILD') ? (string)CODECART_BUILD : self::VERSION);
        if (!$forceRepair && is_file($marker) && trim((string)@file_get_contents($marker)) === $expected) { return; }

        $suffix = substr(hash('sha256', $expected . '|' . __FILE__), 0, 10);
        $staging = $activeStorage . '.vendor-update-' . $suffix;
        $backup = $activeStorage . '.vendor-backup-' . $suffix;
        $previous = $activeStorage . 'codecart/vendor-previous/';

        try {
            if (is_dir($staging)) { $this->removeDirectory($staging); }
            if (is_dir($backup)) { $this->removeDirectory($backup); }
            $codecartDir = dirname(rtrim($previous, '/\\'));
            if (!is_dir($codecartDir) && !@mkdir($codecartDir, 0750, true) && !is_dir($codecartDir)) {
                throw new \RuntimeException('Cannot create CodeCart PRO vendor rollback directory.');
            }
            if (!@mkdir($staging, 0750, true) && !is_dir($staging)) {
                throw new \RuntimeException('Cannot create staged vendor directory.');
            }

            // Never merge two Composer lock states. Activate the bundled set exactly as shipped.
            $this->copyDirectory($sourceVendor, $staging);
            if (!is_file(rtrim($staging, '/\\') . DIRECTORY_SEPARATOR . 'autoload.php')) {
                throw new \RuntimeException('Staged vendor is incomplete.');
            }
            if (is_dir($activeVendor) && !@rename(rtrim($activeVendor, '/\\'), $backup)) {
                throw new \RuntimeException('Cannot move active vendor to backup.');
            }
            if (!@rename(rtrim($staging, '/\\'), rtrim($activeVendor, '/\\'))) {
                if (is_dir($backup) && !is_dir($activeVendor)) { @rename($backup, rtrim($activeVendor, '/\\')); }
                throw new \RuntimeException('Cannot activate staged vendor.');
            }

            // Preserve the first legacy/shared vendor snapshot permanently. It can contain
            // old OpenCart/ocStore payment dependencies still required by a legacy extension.
            if (!is_dir($previous)) {
                if (is_dir($backup) && !@rename($backup, rtrim($previous, '/\\'))) {
                    if ($this->log) { $this->log->write('CodeCart PRO vendor update: previous vendor remains at ' . $backup . ' because the persistent legacy snapshot could not be created.'); }
                }
            } elseif (is_dir($backup)) {
                $this->removeDirectory($backup);
            }

            $markerDir = dirname($marker);
            if (!is_dir($markerDir)) { @mkdir($markerDir, 0750, true); }
            if (@file_put_contents($marker, $expected, LOCK_EX) === false && $this->log) {
                $this->log->write('CodeCart PRO vendor update: unable to write package build marker; vendor will be verified again on next UPDATE.');
            }
            $this->removeDirectory($sourceVendor);
        } catch (\Throwable $e) {
            if (is_dir($staging)) { $this->removeDirectory($staging); }
            if (!is_dir($activeVendor) && is_dir($backup)) { @rename($backup, rtrim($activeVendor, '/\\')); }
            if ($this->log) { $this->log->write('CodeCart PRO vendor update staging failed: ' . $e->getMessage()); }
            throw new \RuntimeException('Vendor update staging failed: ' . $e->getMessage(), 0, $e);
        }
    }

    private function finalizeStorageRelocation() {
        if (!defined('DIR_SYSTEM') || !defined('DIR_STORAGE')) { return; }
        $marker = rtrim(DIR_STORAGE, '/\\') . DIRECTORY_SEPARATOR . 'codecart' . DIRECTORY_SEPARATOR . 'storage-relocation.pending';
        if (!is_file($marker)) { return; }
        $old = trim((string)@file_get_contents($marker));
        $expected = rtrim(str_replace('\\', '/', DIR_SYSTEM . 'storage/'), '/') . '/';
        if (rtrim(str_replace('\\', '/', DIR_STORAGE), '/') . '/' === $expected || rtrim(str_replace('\\', '/', $old), '/') . '/' !== $expected) { return; }

        // Never recursively delete persistent storage automatically during an UPDATE.
        // Preserve the old tree and deny direct web access; manual removal is an explicit
        // post-backup administrator action.
        if (is_dir($old)) {
            @file_put_contents(rtrim($old, '/\\') . DIRECTORY_SEPARATOR . '.htaccess', "<IfModule mod_authz_core.c>\nRequire all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\nOrder allow,deny\nDeny from all\n</IfModule>\n", LOCK_EX);
            @file_put_contents(rtrim($old, '/\\') . DIRECTORY_SEPARATOR . 'web.config', '<?xml version="1.0" encoding="UTF-8"?><configuration><system.webServer><security><authorization><remove users="*" roles="" verbs=""/><add accessType="Deny" users="*"/></authorization></security></system.webServer></configuration>', LOCK_EX);
            @file_put_contents(rtrim($old, '/\\') . DIRECTORY_SEPARATOR . 'index.html', '', LOCK_EX);
        }
        @rename($marker, dirname($marker) . DIRECTORY_SEPARATOR . 'storage-relocation.completed');
        if ($this->log) { $this->log->write('CodeCart PRO storage relocation: previous storage preserved at ' . $old . '; automatic deletion is disabled.'); }
    }


    private function cleanupObsoleteLegacyCoreFiles() {
        if (!defined('DIR_SYSTEM')) { return; }

        $root = dirname(rtrim(DIR_SYSTEM, '/\\')) . DIRECTORY_SEPARATOR;

        // Remove only known stock OpenCart/ocStore files that are absent from CodeCart.
        // Never remove an enabled legacy payment/module automatically.
        $bluepayEnabled = (bool)$this->config->get('payment_bluepay_redirect_status') || (bool)$this->config->get('payment_bluepay_hosted_status');
        if (!$bluepayEnabled) {
            $known = array(
                'admin/view/template/extension/payment/bluepay_redirect.twig' => array('ba3cd0cfb848ea4bf4c57c6e472f215c89f1512ec3c1c3b914cc6cc0e8044c31'),
                'catalog/view/theme/default/template/extension/payment/bluepay_redirect.twig' => array('8a8a5b249dd23c0afc26466800d8f95e7bbeeae2f751020e2a71b85af1fa32b6')
            );
            foreach ($known as $relative => $hashes) {
                $file = $root . str_replace('/', DIRECTORY_SEPARATOR, $relative);
                if (!is_file($file)) { continue; }
                $hash = @hash_file('sha256', $file);
                if ($hash && in_array($hash, $hashes, true)) { @unlink($file); }
            }
        }

        $braintreeEnabled = (bool)$this->config->get('module_pp_braintree_button_status');
        if (!$braintreeEnabled) {
            foreach (array(
                'admin/controller/extension/module/pp_braintree_button.php',
                'admin/view/template/extension/module/pp_braintree_button.twig',
                'catalog/controller/extension/module/pp_braintree_button.php',
                'catalog/view/theme/default/template/extension/module/pp_braintree_button.twig'
            ) as $relative) {
                $file = $root . str_replace('/', DIRECTORY_SEPARATOR, $relative);
                if (is_file($file)) { @unlink($file); }
            }

            // Stock ocStore 3.0.4.1 bundled an old PHP-incompatible Braintree SDK.
            // Keep third-party/active integrations safe: remove it only when the stock
            // Braintree button is disabled. Modern package vendor remains authoritative.
            $legacyBraintree = rtrim(DIR_STORAGE, '/\\') . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'braintree' . DIRECTORY_SEPARATOR . 'braintree_php';
            if (is_dir($legacyBraintree)) { $this->removeDirectory($legacyBraintree); }
            $publicBraintree = rtrim(DIR_SYSTEM, '/\\') . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'braintree' . DIRECTORY_SEPARATOR . 'braintree_php';
            if (is_dir($publicBraintree) && str_replace('\\','/', $publicBraintree) !== str_replace('\\','/', $legacyBraintree)) { $this->removeDirectory($publicBraintree); }
        }

        // Divido was a legacy bundled dependency and is intentionally not part of CodeCart.
        // Remove only the old bundled SDK directory; no merchant media/settings are touched.
        foreach (array(
            rtrim(DIR_STORAGE, '/\\') . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'divido' . DIRECTORY_SEPARATOR . 'divido-php',
            rtrim(DIR_SYSTEM, '/\\') . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'divido' . DIRECTORY_SEPARATOR . 'divido-php'
        ) as $legacyDivido) {
            if (is_dir($legacyDivido)) { $this->removeDirectory($legacyDivido); }
        }
    }

    private function removeObsoleteCookiePresetPngs() {
        if (!defined('DIR_SYSTEM')) { return; }

        $root = dirname(rtrim(DIR_SYSTEM, '/\\')) . DIRECTORY_SEPARATOR;
        $base = $root . 'catalog' . DIRECTORY_SEPARATOR . 'view' . DIRECTORY_SEPARATOR . 'javascript' . DIRECTORY_SEPARATOR . 'codecart' . DIRECTORY_SEPARATOR . 'consent' . DIRECTORY_SEPARATOR . 'icons' . DIRECTORY_SEPARATOR;
        $names = array(
            'cookie-document',
            'cookie-orbit',
            'hand-shield-check',
            'lock-circle-check',
            'shield-cookie-check',
            'shield-lock-check',
            'shield-lock'
        );

        foreach ($names as $name) {
            $legacy = $base . $name . '.png';
            $replacement = $base . $name . '.webp';
            // Delete only the exact obsolete bundled asset and only when its replacement
            // from the current build is present. Merchant images under image/catalog are untouched.
            if (is_file($legacy) && is_file($replacement)) { @unlink($legacy); }
        }

        $singleLegacy = $root . 'catalog' . DIRECTORY_SEPARATOR . 'view' . DIRECTORY_SEPARATOR . 'javascript' . DIRECTORY_SEPARATOR . 'codecart' . DIRECTORY_SEPARATOR . 'consent' . DIRECTORY_SEPARATOR . 'cookie-shield.png';
        $singleReplacement = $root . 'catalog' . DIRECTORY_SEPARATOR . 'view' . DIRECTORY_SEPARATOR . 'javascript' . DIRECTORY_SEPARATOR . 'codecart' . DIRECTORY_SEPARATOR . 'consent' . DIRECTORY_SEPARATOR . 'cookie-shield.webp';
        if (is_file($singleLegacy) && is_file($singleReplacement)) { @unlink($singleLegacy); }

        // RC91 removed two old CodeCart-only placeholders that have no runtime references.
        // They live outside image/catalog, so merchant catalog media is never touched.
        foreach (array('add_photo.png', 'no_photo.png') as $name) {
            $legacyPlaceholder = $root . 'image' . DIRECTORY_SEPARATOR . 'codecart_placeholders' . DIRECTORY_SEPARATOR . $name;
            if (is_file($legacyPlaceholder)) { @unlink($legacyPlaceholder); }
        }

        // RC91 replaced two tiny admin raster UI assets with CSS/Font Awesome.
        foreach (array(
            $root . 'admin' . DIRECTORY_SEPARATOR . 'view' . DIRECTORY_SEPARATOR . 'image' . DIRECTORY_SEPARATOR . 'checkmark.png',
            $root . 'admin' . DIRECTORY_SEPARATOR . 'view' . DIRECTORY_SEPARATOR . 'image' . DIRECTORY_SEPARATOR . 'loader-search.gif'
        ) as $legacyUiAsset) {
            if (is_file($legacyUiAsset)) { @unlink($legacyUiAsset); }
        }

        // The bundled default theme preview is WebP. Keep PNG compatibility for
        // third-party themes, but remove only CodeCart's exact obsolete default preview.
        $legacyThemePreview = $root . 'catalog' . DIRECTORY_SEPARATOR . 'view' . DIRECTORY_SEPARATOR . 'theme' . DIRECTORY_SEPARATOR . 'default' . DIRECTORY_SEPARATOR . 'image' . DIRECTORY_SEPARATOR . 'default.png';
        $webpThemePreview = $root . 'catalog' . DIRECTORY_SEPARATOR . 'view' . DIRECTORY_SEPARATOR . 'theme' . DIRECTORY_SEPARATOR . 'default' . DIRECTORY_SEPARATOR . 'image' . DIRECTORY_SEPARATOR . 'default.webp';
        if (is_file($legacyThemePreview) && is_file($webpThemePreview)) { @unlink($legacyThemePreview); }
    }

    private function copyDirectory($source, $target) {
        $source = rtrim($source, '/\\') . DIRECTORY_SEPARATOR;
        $target = rtrim($target, '/\\') . DIRECTORY_SEPARATOR;
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($source, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::SELF_FIRST);
        foreach ($iterator as $item) {
            $relative = substr($item->getPathname(), strlen($source));
            $destination = $target . $relative;
            if ($item->isLink()) { continue; }
            if ($item->isDir()) {
                if (!is_dir($destination) && !@mkdir($destination, 0750, true) && !is_dir($destination)) { throw new \RuntimeException('Cannot create storage directory: ' . $destination); }
            } else {
                $dir = dirname($destination);
                if (!is_dir($dir) && !@mkdir($dir, 0750, true) && !is_dir($dir)) { throw new \RuntimeException('Cannot create storage directory: ' . $dir); }
                if (!@copy($item->getPathname(), $destination)) { throw new \RuntimeException('Cannot copy storage file: ' . $relative); }
            }
        }
    }

    private function removeDirectory($path) {
        $path = rtrim((string)$path, '/\\');
        if ($path === '' || !is_dir($path)) { return; }
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($iterator as $item) {
            if ($item->isLink() || $item->isFile()) { @unlink($item->getPathname()); }
            elseif ($item->isDir()) { @rmdir($item->getPathname()); }
        }
        @rmdir($path);
    }

    private function updateDatabaseModernizationFlag() {
        $query = $this->db->query("SELECT TABLE_NAME, ENGINE, TABLE_COLLATION FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_TYPE = 'BASE TABLE' AND LEFT(TABLE_NAME, " . (int)strlen(DB_PREFIX) . ") = '" . $this->db->escape(DB_PREFIX) . "'");
        $required = 0;

        foreach ($query->rows as $row) {
            $engine = strtoupper((string)$row['ENGINE']);
            $collation = strtolower((string)$row['TABLE_COLLATION']);
            if ($engine !== 'INNODB' || ($collation !== '' && strpos($collation, 'utf8mb4_') !== 0)) {
                $required = 1;
                break;
            }
        }

        $existing = $this->db->query("SELECT setting_id FROM `" . DB_PREFIX . "setting` WHERE store_id = '0' AND `key` = 'codecart_db_modernization_required' LIMIT 1");
        if ($existing->num_rows) {
            $this->db->query("UPDATE `" . DB_PREFIX . "setting` SET code = 'codecart_core', value = '" . (int)$required . "', serialized = '0' WHERE setting_id = '" . (int)$existing->row['setting_id'] . "'");
        } else {
            $this->db->query("INSERT INTO `" . DB_PREFIX . "setting` SET store_id = '0', code = 'codecart_core', `key` = 'codecart_db_modernization_required', value = '" . (int)$required . "', serialized = '0'");
        }
        $this->config->set('codecart_db_modernization_required', $required);

        if ($required && $this->log) {
            $this->log->write('CodeCart PRO database modernization required: after a verified database backup open Admin > Core / Compatibility > Database Schema > Modernize tables, or run php cli.php db:preflight and php cli.php db:migrate --backup-confirmed.');
        }
    }

    private function getFailureMarker() {
        return defined('DIR_STORAGE') ? rtrim(DIR_STORAGE, '/\\') . '/codecart/core-migration.failed' : '';
    }

    private function isFailureBackoffActive() {
        $marker = $this->getFailureMarker();
        if ($marker === '' || !is_file($marker) || (time() - (int)filemtime($marker)) >= 3600) {
            return false;
        }

        // A marker from an older build must never postpone a fixed migration.
        $content = @file_get_contents($marker);
        return is_string($content) && strpos($content, 'version=' . self::VERSION . ' ') !== false;
    }

    private function writeFailureMarker(\Throwable $e) {
        $marker = $this->getFailureMarker();

        if ($marker === '') {
            return;
        }

        $directory = dirname($marker);

        if (!is_dir($directory) && !mkdir($directory, 0750, true) && !is_dir($directory)) {
            return;
        }

        $content = date('c') . ' version=' . self::VERSION . ' step=' . $this->currentStep . ' ' . get_class($e) . ': ' . str_replace(array("\r", "\n"), ' ', $e->getMessage()) . PHP_EOL;
        $temp = tempnam($directory, '.migration-');

        if ($temp === false) {
            return;
        }

        $written = file_put_contents($temp, $content, LOCK_EX);

        if ($written === false || $written !== strlen($content) || !rename($temp, $marker)) {
            if (is_file($temp)) {
                unlink($temp);
            }
        }
    }

    private function clearFailureMarker() {
        $marker = $this->getFailureMarker();

        if ($marker !== '' && is_file($marker)) {
            unlink($marker);
        }
    }



    private function ensureCodeCartCookieConsentEvent() {
        if (!$this->tableExists('event')) { return; }
        $this->ensureCoreEvent('codecart_cookie_consent', 'catalog/view/common/footer/after', 'event/codecart/injectCookieConsent', 900);
    }

    private function recordMigration($scope, $migration, $version, $checksum = '') {
        if (!$this->tableExists('codecart_migration')) { return; }
        $this->db->query("INSERT INTO `" . DB_PREFIX . "codecart_migration` (`scope`,`migration`,`version`,`checksum`,`date_applied`) VALUES ('" . $this->db->escape((string)$scope) . "','" . $this->db->escape((string)$migration) . "','" . $this->db->escape((string)$version) . "','" . $this->db->escape((string)$checksum) . "',NOW()) ON DUPLICATE KEY UPDATE `version`=VALUES(`version`), `checksum`=VALUES(`checksum`), `date_applied`=VALUES(`date_applied`)");
    }

    private function saveVersion() {
        $query = $this->db->query("SELECT setting_id FROM `" . DB_PREFIX . "setting` WHERE store_id = '0' AND `key` = 'codecart_core_schema_version' LIMIT 1");
        if ($query->num_rows) {
            $this->db->query("UPDATE `" . DB_PREFIX . "setting` SET code = 'codecart_core', value = '" . self::VERSION . "', serialized = '0' WHERE setting_id = '" . (int)$query->row['setting_id'] . "'");
        } else {
            $this->db->query("INSERT INTO `" . DB_PREFIX . "setting` SET store_id = '0', code = 'codecart_core', `key` = 'codecart_core_schema_version', value = '" . self::VERSION . "', serialized = '0'");
        }
    }
}
