<?php
namespace CodeCart\Core;

/**
 * Compatibility adapter for UniShop2 on the modern CodeCart catalog core.
 *
 * This adapter intentionally preserves CodeCart's batched category loading,
 * image pipeline and strict core architecture. It exposes only the legacy
 * data contracts that UniShop2 expects where its OpenCart 3 OCMOD anchors no
 * longer exist in CodeCart.
 */
final class Unishop2Compatibility implements CompatibilityAdapterInterface {
    private $registry;
    private $config;


    public function id(): string {
        return 'theme.unishop2';
    }

    public function priority(): int {
        return 100;
    }

    public function adapt(string $contract, array $payload): array {
        switch ($contract) {
            case 'catalog.menu.data':
                return $this->adaptMenu($payload);

            case 'catalog.category_module.full_tree':
                $payload['value'] = $this->categoryModuleNeedsFullTree() || !empty($payload['value']);
                return $payload;

            case 'catalog.category_page.subcategories':
                if ($this->active()) {
                    $payload['enabled'] = $this->subcategoriesEnabled();
                    $payload['images'] = $this->subcategoryImagesEnabled();
                }
                return $payload;

            case 'catalog.category_page.banner_in_category':
                if ($this->active()) {
                    $payload['enabled'] = $this->bannerInCategoryEnabled((int)($payload['page'] ?? 1));
                }
                return $payload;

            case 'catalog.product.option_image_size':
                $size = $this->optionImageSize((int)($payload['width'] ?? 50), (int)($payload['height'] ?? 50));
                $payload['width'] = $size[0];
                $payload['height'] = $size[1];
                return $payload;

            case 'catalog.product.option_value':
                if (isset($payload['value']) && is_array($payload['value'])) {
                    $payload['value'] = $this->adaptOptionValue($payload['value'], (float)($payload['raw_price'] ?? 0));
                }
                return $payload;

            case 'catalog.banner.item':
                if (isset($payload['item']) && is_array($payload['item'])) {
                    $payload['item'] = $this->adaptBannerItem($payload['item'], (int)($payload['width'] ?? 1), (int)($payload['height'] ?? 1));
                }
                return $payload;
        }

        return $payload;
    }

    public function __construct($registry) {
        $this->registry = $registry;
        $this->config = $registry->get('config');
    }

    public function active(): bool {
        $settings = $this->config->get('config_unishop2');
        if (!is_array($settings) || !$settings) {
            return false;
        }

        $theme = (string)$this->config->get('config_theme');
        if ($theme !== 'unishop2') {
            return false;
        }

        // OFF means OFF: an installed but inactive UniShop theme must not alter catalog data.
        if ($this->config->has('theme_unishop2_status') && !(bool)$this->config->get('theme_unishop2_status')) {
            return false;
        }

        return true;
    }

    public function settings(): array {
        $settings = $this->config->get('config_unishop2');
        return is_array($settings) ? $settings : array();
    }

    public function adaptMenu(array $data): array {
        if (!$this->active() || empty($data['categories']) || !is_array($data['categories'])) {
            return $data;
        }

        $settings = $this->settings();
        $showImages = array();
        if (isset($settings['menu']['second_level']['image']) && is_array($settings['menu']['second_level']['image'])) {
            $showImages = array_fill_keys(array_map('intval', $settings['menu']['second_level']['image']), true);
        }
        $thirdLimit = isset($settings['menu']['third_level']['limit']) ? max(0, (int)$settings['menu']['third_level']['limit']) : 0;
        $topOnly = isset($settings['menu_links_show']);

        foreach ($data['categories'] as &$category) {
            $categoryId = isset($category['category_id']) ? (int)$category['category_id'] : 0;
            $category['icon'] = isset($data['icons'][$categoryId]) ? $data['icons'][$categoryId] : array();
            $category['banner'] = isset($data['banners'][$categoryId]) && (int)($category['column'] ?? 1) > 1 ? $data['banners'][$categoryId] : array();
            if (!isset($category['sort_order'])) {
                $category['sort_order'] = 0;
            }

            if (empty($category['children']) || !is_array($category['children'])) {
                continue;
            }

            foreach ($category['children'] as &$child) {
                $childId = isset($child['category_id']) ? (int)$child['category_id'] : 0;
                $child['image'] = isset($showImages[$categoryId]) ? (string)($child['thumb'] ?? '') : '';
                if (!isset($child['sort_order'])) {
                    $child['sort_order'] = 0;
                }

                if (!empty($child['children']) && is_array($child['children'])) {
                    $children = array();
                    foreach ($child['children'] as $third) {
                        if ($topOnly && isset($third['top']) && !$third['top']) {
                            continue;
                        }
                        if (!isset($third['sort_order'])) {
                            $third['sort_order'] = 0;
                        }
                        $children[] = $third;
                    }
                    $total = count($children);
                    if ($thirdLimit > 0 && $total > $thirdLimit) {
                        $child['more'] = $total;
                        $children = array_slice($children, 0, $thirdLimit);
                    } else {
                        $child['more'] = 0;
                    }
                    $child['children'] = $children;
                } else {
                    $child['children'] = array();
                    $child['more'] = 0;
                }

                if (isset($data['landinglinks'][$childId]) && is_array($data['landinglinks'][$childId])) {
                    $child['children'] = array_merge($child['children'], $data['landinglinks'][$childId]);
                    $this->sortByOrder($child['children']);
                }
            }
            unset($child);

            if (isset($data['landinglinks'][$categoryId]) && is_array($data['landinglinks'][$categoryId])) {
                $category['children'] = array_merge($category['children'], $data['landinglinks'][$categoryId]);
                $this->sortByOrder($category['children']);
            }
        }
        unset($category);

        return $data;
    }

    public function categoryModuleNeedsFullTree(): bool {
        return $this->active();
    }

    public function subcategoriesEnabled(): bool {
        if (!$this->active()) {
            return false;
        }
        $settings = $this->settings();
        return !isset($settings['catalog']['subcategory']['disabled']);
    }

    public function subcategoryImagesEnabled(): bool {
        if (!$this->active()) {
            return false;
        }
        $settings = $this->settings();
        return isset($settings['catalog']['subcategory']['image']);
    }

    public function bannerInCategoryEnabled(int $page): bool {
        return $this->active() && $page === 1 && (bool)$this->config->get('module_uni_banner_in_category_status');
    }

    public function optionImageSize(int $defaultWidth, int $defaultHeight): array {
        if (!$this->active()) {
            return array(max(1, $defaultWidth), max(1, $defaultHeight));
        }
        $settings = $this->settings();
        $thumbWidth = max(1, (int)$this->config->get('theme_' . $this->config->get('config_theme') . '_image_thumb_width'));
        $thumbHeight = max(1, (int)$this->config->get('theme_' . $this->config->get('config_theme') . '_image_thumb_height'));
        if (isset($settings['options']['img_prop'])) {
            $thumbWidth = $thumbHeight;
        }
        return array(max(1, (int)round($thumbWidth / 2)), max(1, (int)round($thumbHeight / 2)));
    }

    public function adaptOptionValue(array $value, float $rawPrice): array {
        if (!$this->active()) {
            return $value;
        }
        $quantity = isset($value['quantity']) ? (int)$value['quantity'] : 0;
        $subtract = !empty($value['subtract']);
        $value['ended'] = $subtract && $quantity <= 0;
        $value['maximum'] = $subtract ? max(0, $quantity) : 100000;
        if (!isset($value['price_value'])) {
            $value['price_value'] = $rawPrice;
        }
        return $value;
    }

    public function adaptBannerItem(array $item, int $width, int $height): array {
        if ($this->active()) {
            $item['width'] = $width;
            $item['height'] = $height;
        }
        return $item;
    }

    public function satisfiesOcmod(string $code, string $file, string $search): bool {
        if (trim($code) !== 'UniShop2 template') {
            return false;
        }
        $file = str_replace('\\', '/', $file);
        $search = trim($search);
        $contracts = array(
            'catalog/controller/common/menu.php' => array(
                '$children_data = array();',
                '$category[\'name\'],',
                '$children = $this->model_catalog_category->getCategories($category[\'category_id\']);',
                '// Level 1'
            ),
            'catalog/controller/extension/module/category.php' => array(
                'if (isset($this->request->get[\'path\'])) {',
                'if ($category[\'category_id\'] == $data[\'category_id\']) {',
                '$children = $this->model_catalog_category->getCategories($category[\'category_id\']);'
            ),
            'catalog/controller/product/category.php' => array(
                '$this->model_catalog_category->getCategories($category_id);',
                '$data[\'categories\'][] = array(',
                '$data[\'categories\'] = array();'
            ),
            'catalog/controller/product/product.php' => array(
                '($option_value[\'quantity\'] > 0)',
                '$this->model_tool_image->resize($option_value[\'image\'], 50, 50),'
            ),
            'catalog/controller/extension/module/banner.php' => array(
                '\'image\' => $this->model_tool_image->resize($result[\'image\'], $setting[\'width\'], $setting[\'height\'])'
            )
        );
        return isset($contracts[$file]) && in_array($search, $contracts[$file], true);
    }

    private function sortByOrder(array &$items): void {
        if (count($items) < 2) {
            return;
        }
        usort($items, static function ($a, $b) {
            return (int)($a['sort_order'] ?? 0) <=> (int)($b['sort_order'] ?? 0);
        });
    }
}
