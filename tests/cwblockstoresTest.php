<?php

use PHPUnit\Framework\TestCase;

class CWBlockStoresTest extends TestCase
{
    const REQUIRED_HOOKS = ['displayNav'];
    const REQUIRED_PROPERTIES = [
        'author',
        'confirmUninstall',
        'description',
        'displayName',
        'name',
        'ps_versions_compliancy',
        'tab',
        'version',
    ];

    /**
     * New instance should have required properties.
     */
    public function testInstanceHasRequiredProperties()
    {
        $module = new CWBlockStores();
        foreach (self::REQUIRED_PROPERTIES as $prop) {
            $this->assertNotNull($module->$prop);
        }
    }

    /**
     * CWBlockStores::install() should add required hooks.
     */
    public function testInstall()
    {
        $mock = $this
            ->getMockBuilder('CWBlockStores')
            ->setMethods(['addHooks'])
            ->getMock();

        $mock
            ->expects($this->once())
            ->method('addHooks')
            ->with($this->equalTo(self::REQUIRED_HOOKS))
            ->willReturn(true);

        $mock->install();
    }

    /**
     * CWBlockStores::hookDisplayNav() should display nothing if multistore is
     * not active.
     */
    public function testDisplayNavSingleStore()
    {
        $mock = $this
            ->getMockBuilder('CWBlockStores')
            ->setMethods(['isMultistoreActive'])
            ->getMock();

        $mock->method('isMultistoreActive')->willReturn(false);
        $this->assertSame('', $mock->hookDisplayNav([]));
    }

    /**
     * CWBlockStores::hookDisplayNav() should not set template variables if
     * template is already cached.
     */
    public function testDisplayNavWithCache()
    {
        $mock = $this
            ->getMockBuilder('CWBlockStores')
            ->setMethods([
                'display',
                'isCached',
                'isMultistoreActive',
                'setTemplateVars',
            ])
            ->getMock();

        $mock->method('display')->willReturn('cached content');
        $mock->method('isCached')->willReturn(true);
        $mock->method('isMultistoreActive')->willReturn(true);

        $mock->expects($this->never())->method('setTemplateVars');

        $this->assertSame('cached content', $mock->hookDisplayNav([]));
    }

    /**
     * CWBlockStores::hookDisplayNav() should set required template variables.
     */
    public function testDisplayNavMultistore()
    {
        $mock = $this
            ->getMockBuilder('CWBlockStores')
            ->setMethods([
                'display',
                'getContextShopGroupId',
                'getShops',
                'setTemplateVars',
                'isMultistoreActive',
                'isCached',
            ])
            ->getMock();

        $mock->method('display')->willReturn('');
        $mock->method('isMultistoreActive')->willReturn(true);
        $mock->method('isCached')->willReturn(false);
        $mock->method('getContextShopGroupId')->willReturn(1);
        $mock->method('getShops')->willReturn($shops = [
            1 => ['id_shop' => 1, 'name' => 'First shop'],
            2 => ['id_shop' => 2, 'name' => 'Second shop'],
            3 => ['id_shop' => 3, 'name' => 'Third shop'],
        ]);

        $mock
            ->expects($this->once())
            ->method('setTemplateVars')
            ->with($this->equalTo(['shops' => $shops]));

        $mock->hookDisplayNav([]);
    }

    /**
     * Provide data to CWBlockStoresTest::testLinkMethods().
     */
    public function providerTestLinkMethods()
    {
        return [
        //  controller,      Link method,           [$_GET values]
            ['index',        'getPageLink',         []],
            ['product',      'getProductLink',      ['id_product'      => 1]],
            ['category',     'getCategoryLink',     ['id_category'     => 1]],
            ['cms',          'getCMSLink',          ['id_cms'          => 1]],
            ['cms',          'getCMSCategoryLink',  ['id_cms_category' => 1]],
            ['manufacturer', 'getManufacturerLink', ['id_manufacturer' => 1]],
            ['supplier',     'getSupplierLink',     ['id_supplier'     => 1]],
            ['default',      'getModuleLink',       ['fc' => 'module', 'module' => 'my_module', 'controller' => 'default']],
            ['pagenotfound', 'getPageLink',         []],
        ];
    }

    /**
     * CWBlockStores::hookDisplayNav() should call expected `Link` methods with
     * expected parameters.
     *
     * @todo provide data to test that it should fetch the home page link when a
     * store doesn't have the current page/product/etc...
     *
     * @dataProvider providerTestLinkMethods
     */
    public function testLinkMethods(string $controller, string $link_method_name, array $get)
    {
        $shops = [
            1 => ['id_shop' => 1, 'name' => 'First shop'],
            2 => ['id_shop' => 2, 'name' => 'Second shop'],
            3 => ['id_shop' => 3, 'name' => 'Third shop'],
        ];
        $mock = $this
            ->getMockBuilder('CWBlockStores')
            ->setMethods([
                'display',
                'getContextShopId',
                'getContextShopGroupId',
                'getControllerPublicName',
                'getLangIsoById',
                'getQueryVars',
                'getShopDefaultLanguage',
                'getShopGroupTree',
                'getValue',
                'hasShop',
                'hasShopModule',
                'isCached',
                'isMultistoreActive',
                'setTemplateVars',
            ])
            ->getMock();
        $mock->context = new stdClass();
        $mock->context->link = $this
            ->getMockBuilder('Link')
            ->setMethods([$link_method_name])
            ->getMock();

        $mock->method('display')->willReturn('a string');
        $mock->method('hasShop')->willReturn(true);
        $mock->method('hasShopModule')->willReturn(true);
        $mock->method('isCached')->willReturn(false);
        $mock->method('isMultistoreActive')->willReturn(true);
        $mock->method('getContextShopId')->willReturn(1);
        $mock->method('getContextShopGroupId')->willReturn(1);
        $mock->method('getControllerPublicName')->willReturn($controller);
        $mock->method('getShopDefaultLanguage')->will($this->onConsecutiveCalls(...array_keys($shops)));
        $mock->method('getShopGroupTree')->willReturn($shops);
        $mock->method('getQueryVars')->willReturn($get);
        $mock->method('getValue')->will($this->returnCallback(static function (string $key) use ($get) {
            return $get[$key] ?? false;
        }));

        $mock_link_method = $mock->context->link
            ->expects($this->exactly(count($shops) - 1))
            ->method($link_method_name)
            ->willReturn('a string');

        switch ($link_method_name) {
            case 'getProductLink':
                $mock_link_method->withConsecutive(
                    [1, null, null, null, 2, 2],
                    [1, null, null, null, 3, 3]
                );
                break;
            case 'getCategoryLink':
                $mock_link_method->withConsecutive(
                    [1, null, 2, null, 2],
                    [1, null, 3, null, 3]
                );
                break;
            case 'getCMSLink':
                $mock_link_method->withConsecutive(
                    [1, null, null, 2, 2],
                    [1, null, null, 3, 3]
                );
                break;
            case 'getCMSCategoryLink':
                $mock_link_method->withConsecutive(
                    [1, null, 2, 2],
                    [1, null, 3, 3]
                );
                break;
            case 'getManufacturerLink':
                $mock_link_method->withConsecutive(
                    [1, null, 2, 2],
                    [1, null, 3, 3]
                );
                break;
            case 'getSupplierLink':
                $mock_link_method->withConsecutive(
                    [1, null, 2, 2],
                    [1, null, 3, 3]
                );
                break;
            case 'getModuleLink':
                $mock_link_method->withConsecutive(
                    ['my_module', $controller, $get, null, 2, 2],
                    ['my_module', $controller, $get, null, 3, 3]
                );
                break;
            case 'getPageLink':
            default:
                $mock_link_method->withConsecutive(
                    [$controller, null, 2, null, false, 2],
                    [$controller, null, 3, null, false, 3]
                );
                break;
        }

        $mock->hookDisplayNav([]);
    }
}
