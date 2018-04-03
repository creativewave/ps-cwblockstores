<?php

class CWBlockStores extends Module
{
    /**
     * Registered hooks.
     *
     * @var array
     */
    const HOOKS = ['displayNav'];

    /**
     * @see ModuleCore
     */
    public $name    = 'cwblockstores';
    public $tab     = 'front_office_features';
    public $version = '1.0.0';
    public $author  = 'Creative Wave';
    public $need_instance = 0;
    public $ps_versions_compliancy = [
        'min' => '1.6',
        'max' => '1.6.99.99',
    ];

    /**
     * Initialize module.
     */
    public function __construct()
    {
        parent::__construct();

        $this->displayName      = $this->l('Block Stores Selector');
        $this->description      = $this->l('Display a stores dropdown selector.');
        $this->confirmUninstall = $this->l('Are you sure you want to uninstall?');
    }

    /**
     * Install module.
     */
    public function install(): bool
    {
        return parent::install() and $this->addHooks(static::HOOKS);
    }

    /**
     * Uninstall module.
     */
    public function uninstall(): bool
    {
        $this->_clearCache('*');

        return parent::install();
    }

    /**
     * Display a dropdown of links pointing to the current page, for each stores
     * of the context store group.
     */
    public function hookDisplayNav(array $params): string
    {
        if (!$this->isMultistoreActive()) {
            return '';
        }
        $template_name = 'nav.tpl';
        $id_cache = $this->getCacheId();
        if (!$this->isCached($template_name, $id_cache)) {
            $id_shop_group = $this->getContextShopGroupId();
            $this->setTemplateVars(['shops' => $this->getShops($id_shop_group)]);
        }

        return $this->display(__FILE__, $template_name, $id_cache);
    }

    /**
     * Add hooks.
     */
    protected function addHooks(array $hooks): bool
    {
        return array_product(array_map([$this, 'registerHook'], $hooks));
    }

    /**
     * Get shops.
     */
    protected function getShops(int $id_shop_group): array
    {
        $shops = $this->getShopGroupTree($id_shop_group);
        foreach ($shops as $id_shop => &$shop) {
            $shop['lang'] = $this->getShopDefaultLanguage($id_shop, $id_shop_group);
            $shop['iso']  = $this->getLangIsoById($shop['lang']);
        }
        $this->attachLinks($shops); // Passed by reference.

        return $shops;
    }

    /**
     * Attach links to each shops.
     * The reason of using a reference is to fetch data once for all shops.
     */
    protected function attachLinks(array &$shops): bool
    {
        $controller = $this->getControllerPublicName();

        // Process by priority
        if ('index' === $controller) {
            return array_walk($shops, [$this, 'attachPageLink'], $controller);
        }
        if ('product' === $controller and $id_product = $this->getValue('id_product')) {
            return array_walk($shops, [$this, 'attachProductLink'], $id_product);
        }
        if ('category' === $controller and $id_category = $this->getValue('id_category')) {
            return array_walk($shops, [$this, 'attachCategoryLink'], $id_category);
        }
        if ('cms' === $controller and $id_cms = $this->getValue('id_cms')) {
            return array_walk($shops, [$this, 'attachCMSLink'], $id_cms);
        }
        if ('cms' === $controller and $id_cms_category = $this->getValue('id_cms_category')) {
            return array_walk($shops, [$this, 'attachCMSCategoryLink'], $id_cms_category);
        }
        if ('manufacturer' === $controller and $id_manufacturer = $this->getValue('id_manufacturer')) {
            return array_walk($shops, [$this, 'attachManufacturerLink'], $id_manufacturer);
        }
        if ('supplier' === $controller and $id_supplier = $this->getValue('id_supplier')) {
            return array_walk($shops, [$this, 'attachSupplierLink'], $id_supplier);
        }
        if ('module' === $this->getValue('fc') and $module = $this->getValue('module')) {
            return array_walk($shops, [$this, 'attachModuleLink'], [$module, $this->getValue('controller')]);
        }
        // Default page (404, best sales, contact...).
        return array_walk($shops, [$this, 'attachPageLink'], $controller);
    }

    /**
     * Attach page link.
     */
    protected function attachPageLink(array &$shop, int $id_shop, string $controller)
    {
        if ($id_shop === $this->getContextShopId()) {
            return;
        }
        $shop['link'] = $this->context->link->getPageLink($controller, null, $shop['lang'], null, false, $id_shop);
    }

    /**
     * Attach product link.
     */
    protected function attachProductLink(array &$shop, int $id_shop, int $id_product)
    {
        if ($id_shop === $this->getContextShopId()) {
            return;
        }
        if ($this->hasShop('product', $id_product, $id_shop)) {
            $shop['link'] = $this->context->link->getProductLink($id_product, null, null, null, $shop['lang'], $id_shop);

            return;
        }
        $shop['link'] = $this->context->link->getPageLink('index', null, $shop['lang'], null, false, $id_shop);
    }

    /**
     * Attach category link.
     */
    protected function attachCategoryLink(array &$shop, int $id_shop, int $id_category)
    {
        if ($id_shop === $this->getContextShopId()) {
            return;
        }
        if ($this->hasShop('category', $id_category, $id_shop)) {
            $shop['link'] = $this->context->link->getCategoryLink($id_category, null, $shop['lang'], null, $id_shop);

            return;
        }
        $shop['link'] = $this->context->link->getPageLink('index', null, $shop['lang'], null, false, $id_shop);
    }

    /**
     * Attach CMS page link.
     */
    protected function attachCMSLink(array &$shop, int $id_shop, int $id_cms)
    {
        if ($id_shop === $this->getContextShopId()) {
            return;
        }
        if ($this->hasShop('cms', $id_cms, $id_shop)) {
            $shop['link'] = $this->context->link->getCMSLink($id_cms, null, null, $shop['lang'], $id_shop);

            return;
        }
        $shop['link'] = $this->context->link->getPageLink('index', null, $shop['lang'], null, false, $id_shop);
    }

    /**
     * Attach CMS category link.
     */
    protected function attachCMSCategoryLink(array &$shop, int $id_shop, int $id_cms_category)
    {
        if ($id_shop === $this->getContextShopId()) {
            return;
        }
        if ($this->hasShop('cms_category', $id_cms_category, $id_shop)) {
            $shop['link'] = $this->context->link->getCMSCategoryLink($id_cms_category, null, $shop['lang'], $id_shop);

            return;
        }
        $shop['link'] = $this->context->link->getPageLink('index', null, $shop['lang'], null, false, $id_shop);
    }

    /**
     * Attach manufacturer link.
     */
    protected function attachManufacturerLink(array &$shop, int $id_shop, int $id_manufacturer)
    {
        if ($id_shop === $this->getContextShopId()) {
            return;
        }
        if ($this->hasShop('manufacturer', $id_manufacturer, $id_shop)) {
            $shop['link'] = $this->context->link->getManufacturerLink($id_manufacturer, null, $shop['lang'], $id_shop);

            return;
        }
        $shop['link'] = $this->context->link->getPageLink('index', null, $shop['lang'], null, false, $id_shop);
    }

    /**
     * Attach supplier link.
     */
    protected function attachSupplierLink(array &$shop, int $id_shop, int $id_supplier)
    {
        if ($id_shop === $this->getContextShopId()) {
            return;
        }
        if ($this->hasShop('supplier', $id_supplier, $id_shop)) {
            $shop['link'] = $this->context->link->getSupplierLink($id_supplier, null, $shop['lang'], $id_shop);

            return;
        }
        $shop['link'] = $this->context->link->getPageLink('index', null, $shop['lang'], null, false, $id_shop);
    }

    /**
     * Attach module link.
     */
    protected function attachModuleLink(array &$shop, int $id_shop, array $params)
    {
        if ($id_shop === $this->getContextShopId()) {
            return;
        }
        if ($this->hasShopModule(strtolower($params[0]), $id_shop)) {
            list($module, $controller) = $params;
            $shop['link'] = $this->context->link->getModuleLink(
            $module,
            $controller,
            $this->getQueryVars(),
            null,
            $shop['lang'],
            $id_shop
            );

            return;
        }
        $shop['link'] = $this->context->link->getPageLink('index', null, $shop['lang'], null, false, $id_shop);
    }

    /**
     * Get context shop group ID.
     */
    protected function getContextShopGroupId(): int
    {
        return $this->context->shop->id_shop_group;
    }

    /**
     * Get context shop ID.
     */
    protected function getContextShopId(): int
    {
        return $this->context->shop->id;
    }

    /**
     * Get public controller name.
     */
    protected function getControllerPublicName(): string
    {
        return Dispatcher::getInstance()->getController();
    }

    /**
     * Get language ISO code by ID.
     */
    protected function getLangIsoById(int $id_lang): string
    {
        return Language::getIsoById($id_lang);
    }

    /**
     * Get query variables.
     */
    protected function getQueryVars(): array
    {
        return array_filter(Tools::getAllValues(), [$this, 'isExpectedQueryVars'], ARRAY_FILTER_USE_KEY);
    }

    /**
     * Get shop default language.
     */
    protected function getShopDefaultLanguage(int $id_shop, int $id_shop_group): int
    {
        return Configuration::get('PS_LANG_DEFAULT', null, $id_shop_group, $id_shop);
    }

    /**
     * Get shop group tree.
     */
    protected function getShopGroupTree(int $id_shop_group): array
    {
        return Shop::getTree()[$id_shop_group]['shops'];
    }

    /**
     * Get value from $_GET/$_POST.
     */
    protected function getValue(string $key, string $default = ''): string
    {
        return Tools::getValue($key, $default);
    }

    /**
     * Wether or not a shop has an entity.
     */
    protected function hasShop(string $entity, int $id_entity, int $id_shop): bool
    {
        static $ids_shops = null;

        if (null === $ids_shops) {
            $ids_shops = array_column($this->getDb()->executeS($this->getDbQuery()
                ->select('id_shop')
                ->from("${entity}_shop")
                ->where("id_$entity = $id_entity")
            ), 'id_shop');
        }

        return in_array($id_shop, $ids_shops);
    }

    /**
     * Get Db.
     */
    protected function getDb(bool $slave = false): Db
    {
        return Db::getInstance($slave ? _PS_USE_SQL_SLAVE_ : $slave);
    }

    /**
     * Get DbQuery.
     */
    protected function getDbQuery(): DbQuery
    {
        return new DbQuery();
    }

    /**
     * Wether or not a shop has a module.
     */
    protected function hasShopModule(string $module, int $id_shop): bool
    {
        static $ids_shops = null;

        if (null === $ids_shops) {
            $ids_shops = array_column($this->getDb()->executeS($this->getDbQuery()
                ->select('ms.id_shop')
                ->from('module_shop', 'ms')
                ->naturalJoin('module', 'm')
                ->where("m.name = '$module'")
            ), 'id_shop');
        }

        return in_array($id_shop, $ids_shops);
    }

    /**
     * Wether or not query variable is expected.
     */
    protected function isExpectedQueryVars(string $query_var): bool
    {
        return !in_array($query_var, ['fc', 'module', 'controller'], true);
    }

    /**
     * Wether or not multistore is active.
     */
    protected function isMultistoreActive(): bool
    {
        return Shop::isFeatureActive();
    }

    /**
     * Set template variables.
     */
    protected function setTemplateVars(array $vars): Smarty_Internal_Data
    {
        return $this->smarty->assign($vars);
    }
}
