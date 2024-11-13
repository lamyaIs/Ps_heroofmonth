<?php
/**
 * Copyright Mr-dev.
 *
 * NOTICE OF LICENSE
 *
 * This file is proprietary and each license is valid for use on one website only.
 * To use this file on additional websites or projects, additional licenses must be purchased.
 * Redistribution, reselling, leasing, licensing, sub-licensing, or offering this resource to any third party is strictly prohibited.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to keep compatibility with future PrestaShop updates.
 *
 * @author     Mr-dev
 * @copyright  Mr-dev
 * @license    License valid for one website (or project) per purchase
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

class HeroOfMonth extends Module
{
    public function __construct()
    {
        $this->name = 'heroofmonth';
        $this->tab = 'front_office_features';
        $this->version = '1.1.9';
        $this->author = 'Mr-dev';
        $this->need_instance = 0;
        $this->bootstrap = true;
        $this->ajax = true;
        $this->ps_versions_compliancy = ['min' => '1.7.0.0', 'max' => _PS_VERSION_];
        parent::__construct();
        $this->displayName = $this->l('Hero of the Month');
        $this->description = $this->l('Choose a product to highlight each month.');
    }

    public function install()
    {
        return parent::install()
            && $this->registerHook('displayHome')
            && $this->registerHook('displayProductListReviews')
            && $this->registerHook('actionFrontControllerSetMedia')
            && $this->createTable()
            && $this->initializeConfigurations();
    }

    public function uninstall()
    {
        return parent::uninstall()
            && $this->deleteConfigurations()
            && $this->deleteTable();
    }

    private function initializeConfigurations()
    {
        Configuration::updateValue('HERO_COLOR1', '');
        Configuration::updateValue('HERO_COLOR2', '');
        Configuration::updateValue('HERO_COLOR3', '');
        Configuration::updateValue('HERO_LAYOUT_TYPE', 'full');
        Configuration::updateValue('HERO_FLAG_IMAGE', 'flag_image.png');
        Configuration::updateValue('HERO_OF_THE_MONTH', null);
        Configuration::updateValue('HERO_CUSTOM_IMAGE', '');

        return true;
    }

    private function deleteConfigurations()
    {
        Configuration::deleteByName('HERO_COLOR1');
        Configuration::deleteByName('HERO_COLOR2');
        Configuration::deleteByName('HERO_COLOR3');
        Configuration::deleteByName('HERO_OF_THE_MONTH');
        Configuration::deleteByName('HERO_LAYOUT_TYPE');
        Configuration::deleteByName('HERO_FLAG_IMAGE');
        Configuration::deleteByName('HERO_CUSTOM_IMAGE');

        return true;
    }

    public function hookActionFrontControllerSetMedia()
    {
        $this->context->controller->registerStylesheet(
            'heroofmonth-css',
            'modules/' . $this->name . '/views/css/heroofmonth.css',
            ['media' => 'all', 'priority' => 1000]
        );
        $this->context->controller->registerJavascript(
            'heroofmonth-js',
            'modules/' . $this->name . '/views/js/heroofmonth.js',
            ['priority' => 1000]
        );
    }

    public function hookDisplayHome($params)
    {
        $heroProductId = Configuration::get('HERO_OF_THE_MONTH');
        if (!$heroProductId) {
            return '';
        }
        $product = new Product($heroProductId, true, $this->context->language->id);
        $cover = Product::getCover($heroProductId);
        $product->id_image = $cover ? $product->id . '-' . $cover['id_image'] : null;
        $heroData = Db::getInstance()->getRow(
            'SELECT * FROM ' . _DB_PREFIX_ . 'heroofmonth WHERE id_product = ' . (int) $heroProductId
        );
        $useShortDescription = !empty($heroData['use_short_description']);
        $customDescription = $heroData['description'] ?? 'no';
        $customImage = $heroData['image'] ?? null;
        $customImageUrl = $customImage
            ? $this->context->link->getBaseLink() . 'modules/' . $this->name . '/views/img/' . $customImage
            : null;
        $this->context->smarty->assign([
            'hero_product_id' => $heroProductId,
            'hero_product' => $product,
            'link' => $this->context->link,
            'use_short_description' => $useShortDescription,
            'custom_description' => $customDescription,
            'hero_custom_image' => $customImageUrl,
            'is_custom_image_empty' => empty($customImageUrl),
        ]);

        return $this->display(__FILE__, 'views/templates/hook/display_home.tpl');
    }

    public function hookDisplayProductListReviews($params)
    {
        $heroProductId = (int) Configuration::get('HERO_OF_THE_MONTH');
        $flagImage = Configuration::get('HERO_FLAG_IMAGE');
        if (empty($flagImage)) {
            return '';
        }
        $flagImagePath = _PS_MODULE_DIR_ . $this->name . '/views/img/' . $flagImage;
        if (
            $params['product']['id_product'] == $heroProductId
            && file_exists($flagImagePath)
        ) {
            $flagImageUrl = $this->context->link->getBaseLink() . 'modules/' . $this->name . '/views/img/' . $flagImage;
            $this->context->smarty->assign('flag_image_url', $flagImageUrl);

            return $this->display(__FILE__, 'views/templates/hook/heroofmonth_flag.tpl');
        }

        return '';
    }

    private function createTable()
    {
        $sql = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'heroofmonth` (
            `id_hero` int(11) NOT NULL AUTO_INCREMENT,
            `id_product` int(11) NOT NULL,
            `name` varchar(255) NOT NULL,
            `image` varchar(255) DEFAULT NULL,
            `month` varchar(255) NOT NULL,
            `description` TEXT DEFAULT NULL,
            `use_short_description` TINYINT(1) NOT NULL DEFAULT 0,
            `active` TINYINT(1) NOT NULL DEFAULT 1,
            PRIMARY KEY (`id_hero`)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

        return Db::getInstance()->execute($sql);
    }

    private function deleteTable()
    {
        $sql = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'heroofmonth`;';
        return Db::getInstance()->execute($sql);
    }

    public function getContent()
    {
        $output = '';
        if (Tools::getValue('delete_flag_image')) {
            $output .= $this->handleFlagImageDeletion();
        }
        if (isset($_FILES['HERO_FLAG_IMAGE']) && !empty($_FILES['HERO_FLAG_IMAGE']['tmp_name'])) {
            $output .= $this->handleFlagImageUpload();
        }
        $this->context->controller->addJS($this->_path . 'views/js/heroofmonth.js');
        $this->context->controller->addCSS($this->_path . 'views/css/heroofmonth.css');
        Media::addJsDef([
            'ajaxUrl' => $this->context->link->getAdminLink('AdminModules', true) . ' & configure=' . $this->name . ' & ajax=1 & action=searchProduct',
        ]);
        $output .= '<a href="' . $this->context->link->getAdminLink('AdminModules', false) . '&configure=' . $this->name . '&action=layoutSettings&token=' . Tools::getAdminTokenLite('AdminModules') . '" class="btn btn-primary" style="margin-bottom: 20px;">' . $this->l('Configuration du produit du mois') . '</a>';
        if ('layoutSettings' === Tools::getValue('action')) {
            return $this->renderLayoutSettingsForm();
        }
        if (Tools::isSubmit('submitLayoutSettings')) {
            $output .= $this->handleLayoutSettingsSubmission();
        }
        if (Tools::isSubmit('submitHeroOfMonth')) {
            $output .= $this->handleHeroOfMonthSubmission();
        }
        if (Tools::isSubmit('deleteheroofmonth')) {
            $output .= $this->handleHeroDeletion();
        }
        if (Tools::isSubmit('viewheroofmonth')) {
            return $this->renderHeroStatistics();
        }
        if (Tools::isSubmit('updateheroofmonth')) {
            return $this->renderEditForm($this->getHeroById((int) Tools::getValue('id_hero')));
        }
        if (Tools::isSubmit('submitEditHeroOfMonth')) {
            $output .= $this->processEditHero();
        }
        if (Tools::getValue('delete_hero_image') && Tools::getValue('id_hero')) {
            $output .= $this->handleCustomImageDeletion();
        }

        return $output . $this->renderForm() . $this->renderHeroList();
    }

    protected function renderForm()
    {
        $use_short_description = (bool) Tools::getValue('switch_description_short', 0);
        $fields_form = [
            'form' => [
                'legend' => [
                    'title' => $this->l('Add a product'),
                    'icon' => 'icon-cogs',
                ],
                'input' => [
                    [
                        'type' => 'html',
                        'label' => $this->l('Product search (ID, Name, Reference)'),
                        'name' => 'product_search_html',
                        'html_content' => $this->context->smarty->fetch($this->local_path . 'views/templates/admin/product_search.tpl'),
                    ],
                    ['type' => 'hidden', 'name' => 'HERO_OF_THE_MONTH', 'id' => 'HERO_OF_THE_MONTH'],
                    [
                        'type' => 'switch',
                        'label' => $this->l('Use the short product description'),
                        'name' => 'switch_description_short',
                        'desc' => $this->l('Enable to use the short product description instead of the custom description.'),
                        'values' => [
                            ['id' => 'active_on', 'value' => 1, 'label' => $this->l('Yes')],
                            ['id' => 'active_off', 'value' => 0, 'label' => $this->l('No')],
                        ],
                        'value' => $use_short_description,
                    ],
                    [
                        'type' => 'textarea',
                        'label' => $this->l('Customized description'),
                        'name' => 'hero_custom_description',
                        'desc' => $this->l('Add a custom description for this product.'),
                        'rows' => 10,
                        'cols' => 50,
                        'autoload_rte' => true,
                    ],
                    [
                        'type' => 'file',
                        'label' => $this->l('Customized product image'),
                        'name' => 'HERO_CUSTOM_IMAGE',
                        'desc' => $this->l('Download an image for this product.'),
                    ],
                ],
                'submit' => ['title' => $this->l('Save'), 'class' => 'btn btn-default pull-right'],
            ],
        ];
        $helper = new HelperForm();
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitHeroOfMonth';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false) . '&configure=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = [
            'fields_value' => [
                'HERO_OF_THE_MONTH' => Tools::getValue('HERO_OF_THE_MONTH'),
                'hero_custom_description' => Tools::getValue('hero_custom_description'),
                'switch_description_short' => $use_short_description,
            ],
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        ];

        return $helper->generateForm([$fields_form]);
    }

    public function renderEditForm($hero)
    {
        $this->context->smarty->assign('hero_name', $hero['name']);
        $html_content = $this->context->smarty->fetch($this->local_path . 'views/templates/admin/edit_product_search.tpl');
        $fields_form = [
            'form' => [
                'legend' => [
                    'title' => $this->l('Modify the hero product'),
                    'icon' => 'icon-cogs',
                ],
                'input' => [
                    [
                        'type' => 'html',
                        'label' => $this->l('Product search (ID, Name, Reference)'),
                        'name' => 'product_search_html',
                        'html_content' => $html_content,
                    ],
                    [
                        'type' => 'hidden',
                        'name' => 'HERO_OF_THE_MONTH',
                        'id' => 'HERO_OF_THE_MONTH',
                        'value' => $hero['id_product'],
                    ],
                    [
                        'type' => 'hidden',
                        'name' => 'id_hero',
                        'value' => $hero['id_hero'],
                    ],
                    [
                        'type' => 'textarea',
                        'label' => $this->l('Customized description'),
                        'name' => 'hero_custom_description',
                        'desc' => $this->l('Add a custom description for this product.'),
                        'rows' => 10,
                        'cols' => 50,
                        'autoload_rte' => true,
                        'value' => $hero['description'],
                        'disabled' => (bool) $hero['use_short_description'],
                        'id' => 'custom_description',
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->l('Use the short product description.'),
                        'name' => 'switch_description_short',
                        'desc' => $this->l('Enable to use the short product description instead of the custom description.'),
                        'values' => [
                            [
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Yes'),
                            ],
                            [
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('No'),
                            ],
                        ],
                        'value' => (int) $hero['use_short_description'],
                        'id' => 'switch_description',
                    ],
                    [
                        'type' => 'file',
                        'label' => $this->l('Change the image of the hero product'),
                        'name' => 'HERO_CUSTOM_IMAGE',
                        'desc' => $this->l('Download a new image for this product.'),
                    ],
                ],
            ],
        ];

        if (!empty($hero['image'])) {
            $image_path = _PS_MODULE_DIR_ . $this->name . '/views/img/' . $hero['image'];
            $image_url = $this->context->link->getBaseLink() . 'modules/' . $this->name . '/views/img/' . $hero['image'];

            if (file_exists($image_path)) {
                $delete_image_url = $this->context->link->getAdminLink('AdminModules', true) . '&configure=' . $this->name . '&delete_hero_image=1&id_hero=' . (int) $hero['id_hero'];
                $delete_image_text = $this->l('Delete the image.');

                $this->context->smarty->assign([
                    'image_url' => $image_url,
                    'delete_image_url' => $delete_image_url,
                    'delete_image_text' => $delete_image_text,
                ]);

                $fields_form['form']['input'][] = [
                    'type' => 'html',
                    'label' => $this->l('Image actuelle'),
                    'name' => 'current_image',
                    'html_content' => $this->context->smarty->fetch($this->local_path . 'views/templates/admin/current_image.tpl'),
                ];
            }
        }

        $fields_form['form']['buttons'] = [
            [
                'type' => 'button',
                'title' => $this->l('Retour'),
                'class' => 'btn btn-secondary',
                'href' => $this->context->link->getAdminLink('AdminModules') . '&configure=' . $this->name,
            ],
            [
                'type' => 'submit',
                'title' => $this->l('Save'),
                'class' => 'btn btn-default pull-right',
            ],
        ];

        $helper = new HelperForm();
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitEditHeroOfMonth';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false) . '&configure=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = [
            'fields_value' => [
                'HERO_OF_THE_MONTH' => $hero['id_product'],
                'id_hero' => $hero['id_hero'],
                'hero_custom_description' => $hero['description'],
                'switch_description_short' => (int) $hero['use_short_description'],
            ],
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        ];

        return $helper->generateForm([$fields_form]);
    }

    protected function renderHeroList()
    {
        $heroes = Db::getInstance()->executeS('SELECT * FROM ' . _DB_PREFIX_ . 'heroofmonth');
        $fields_list = [
            'id_hero' => ['title' => $this->l('ID'), 'align' => 'center', 'class' => 'fixed-width-xs'],
            'id_product' => ['title' => $this->l('ID Produit'), 'align' => 'center', 'class' => 'fixed-width-xs'],
            'name' => ['title' => $this->l('Nom')],
            'month' => ['title' => $this->l('Mois'), 'align' => 'center'],
            'active' => ['title' => $this->l('Actif'), 'align' => 'center', 'type' => 'bool', 'active' => 'status'],
        ];
        $helper = new HelperList();
        $helper->shopLinkType = '';
        $helper->simple_header = false;
        $helper->actions = ['edit', 'delete', 'view'];
        $helper->identifier = 'id_hero';
        $helper->show_toolbar = true;
        $helper->title = $this->l('Products Heroes of the Month');
        $helper->table = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex . '&configure=' . $this->name;

        return $helper->generateList($heroes, $fields_list);
    }

    protected function renderLayoutSettingsForm()
    {
        $flag_image = Configuration::get('HERO_FLAG_IMAGE');
        $image_path = _PS_MODULE_DIR_ . $this->name . '/views/img/' . $flag_image;
        $image_url = $this->context->link->getBaseLink() . 'modules/' . $this->name . '/views/img/' . $flag_image;
        $inputs = [
            [
                'type' => 'file',
                'label' => $this->l('Flag image for product of the month'),
                'name' => 'HERO_FLAG_IMAGE',
                'desc' => $this->l('Choose an image for the product of the month flag.'),
            ],
            [
                'type' => 'color',
                'label' => $this->l('Background color'),
                'name' => 'color1',
            ],
            [
                'type' => 'color',
                'label' => $this->l('Header color'),
                'name' => 'color2',
            ],
            [
                'type' => 'color',
                'label' => $this->l('Text color'),
                'name' => 'color3',
            ],
            [
                'type' => 'radio',
                'label' => $this->l('Layout'),
                'name' => 'layout_type',
                'values' => [
                    ['id' => 'full_width', 'value' => 'full', 'label' => $this->l('Full-width')],
                    ['id' => 'boxed', 'value' => 'boxed', 'label' => $this->l('Boxed')],
                ],
                'desc' => $this->l('Choose between a full-width or framed layout.'),
            ],
        ];
        if (!empty($flag_image) && file_exists($image_path)) {
            $flag_image_url = $this->context->link->getBaseLink() . 'modules/' . $this->name . '/views/img/' . $flag_image;
            $delete_flag_image_url = $this->context->link->getAdminLink('AdminModules', true) . '&delete_flag_image=1&configure=' . $this->name;
            $delete_flag_image_text = $this->l('Delete image');

            $this->context->smarty->assign([
                'flag_image_url' => $flag_image_url,
                'delete_flag_image_url' => $delete_flag_image_url,
                'delete_flag_image_text' => $delete_flag_image_text,
            ]);

            array_unshift($inputs, [
                'type' => 'html',
                'label' => $this->l('Image actuelle'),
                'name' => 'current_image',
                'html_content' => $this->context->smarty->fetch($this->local_path . 'views/templates/admin/flag_image.tpl'),
            ]);
        }

        $fields_form = [
            'form' => [
                'legend' => [
                    'title' => $this->l('Product of the month configuration'),
                    'icon' => 'icon-cogs',
                ],
                'input' => $inputs,
                'buttons' => [
                    [
                        'type' => 'button',
                        'title' => $this->l('Back'),
                        'class' => 'btn btn-secondary',
                        'href' => $this->context->link->getAdminLink('AdminModules') . '&configure=' . $this->name,
                    ],
                    [
                        'type' => 'submit',
                        'title' => $this->l('Save'),
                        'class' => 'btn btn-default pull-right',
                    ],
                ],
            ],
        ];
        $helper = new HelperForm();
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitLayoutSettings';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false) . '&configure=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = [
            'fields_value' => [
                'color1' => Configuration::get('HERO_COLOR1'),
                'color2' => Configuration::get('HERO_COLOR2'),
                'color3' => Configuration::get('HERO_COLOR3'),
                'layout_type' => Configuration::get('HERO_LAYOUT_TYPE', 'full'),
            ],
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        ];

        return $helper->generateForm([$fields_form]);
    }

    public function getHeroById($id_hero)
    {
        return Db::getInstance()->getRow('SELECT * FROM ' . _DB_PREFIX_ . 'heroofmonth WHERE id_hero = ' . (int) $id_hero);
    }

    public function processEditHero()
    {
        $id_hero = (int) Tools::getValue('id_hero');
        $product_id = (int) Tools::getValue('HERO_OF_THE_MONTH');
        $custom_description = Tools::getValue('hero_custom_description');
        $use_short_description = (int) Tools::getValue('switch_description_short');
        $month_year = date('m/Y');
        $output = '';
        if ($id_hero && Validate::isInt($product_id)) {
            $current_hero = $this->getHeroById($id_hero);
            if (isset($_FILES['HERO_CUSTOM_IMAGE']) && !empty($_FILES['HERO_CUSTOM_IMAGE']['tmp_name'])) {
                $this->handleImageUpload($id_hero, $current_hero['image']);
            }
            if ($current_hero['id_product'] != $product_id) {
                $this->deactivatePreviousHero($month_year);
                $this->addNewHero($product_id, $custom_description, $use_short_description, $month_year);
            } else {
                $this->updateHero($id_hero, $custom_description, $use_short_description);
            }
            $output .= $this->displayConfirmation($this->l('Hero product successfully updated.'));
        } else {
            $output .= $this->displayError($this->l('Error updating hero product.'));
        }

        return $output . $this->renderEditForm($this->getHeroById($id_hero));
    }

    public function ajaxProcessSearchProduct()
    {
        $search = Tools::getValue('search');
        $sql = new DbQuery();
        $sql->select('p.id_product, pl.name, p.reference');
        $sql->from('product', 'p');
        $sql->innerJoin('product_lang', 'pl', 'p.id_product = pl.id_product');
        $sql->where('(pl.name LIKE "%' . pSQL($search) . '%" OR p.id_product LIKE "' . pSQL($search) . '%" OR p.reference LIKE "%' . pSQL($search) . '%")');
        $sql->where('pl.id_lang = ' . (int) $this->context->language->id);
        $products = Db::getInstance()->executeS($sql);
        exit(json_encode($products));
    }

    protected function getSalesStats($product_id, $month, $year)
    {
        $sql = new DbQuery();
        $sql->select('SUM(od.product_quantity) as total_quantity, SUM(od.total_price_tax_incl) as total_sales');
        $sql->from('order_detail', 'od');
        $sql->innerJoin('orders', 'o', 'od.id_order = o.id_order');
        $sql->where('od.product_id = ' . (int) $product_id);
        $sql->where('MONTH(o.date_add) = ' . (int) $month);
        $sql->where('YEAR(o.date_add) = ' . (int) $year);

        return Db::getInstance()->getRow($sql);
    }

    protected function deleteHero($id_hero)
    {
        return Db::getInstance()->delete('heroofmonth', 'id_hero = ' . (int) $id_hero);
    }

    private function handleImageUpload($id_hero, $existing_image)
    {
        if (!empty($existing_image)) {
            $existing_image_path = _PS_MODULE_DIR_ . $this->name . '/views/img/' . $existing_image;
            if (file_exists($existing_image_path)) {
                unlink($existing_image_path);
            }
        }
        $file = $_FILES['HERO_CUSTOM_IMAGE'];
        $allowed_mime_types = ['image/jpeg', 'image/png', 'image/gif'];
        if (in_array($file['type'], $allowed_mime_types)) {
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = 'hero_' . (int) $id_hero . '.' . $extension;
            $destination = _PS_MODULE_DIR_ . $this->name . '/views/img/' . $filename;
            if (move_uploaded_file($file['tmp_name'], $destination)) {
                Db::getInstance()->update('heroofmonth', ['image' => pSQL($filename)], 'id_hero = ' . (int) $id_hero);
            } else {
                $this->context->controller->errors[] = $this->l('Error downloading image.');
            }
        } else {
            $this->context->controller->errors[] = $this->l('Unauthorized file type. Please upload a JPEG, PNG or GIF image.');
        }
    }

    private function deactivatePreviousHero($month_year)
    {
        Db::getInstance()->execute('UPDATE `' . _DB_PREFIX_ . "heroofmonth` SET active = 0 WHERE month = '" . pSQL($month_year) . "'");
    }

    private function addNewHero($product_id, $description, $use_short_description, $month_year)
    {
        $product = new Product($product_id, false, $this->context->language->id);
        $sql = 'INSERT INTO `' . _DB_PREFIX_ . "heroofmonth` (id_product, name, month, active, description, use_short_description) 
            VALUES ('" . (int) $product_id . "', '" . pSQL($product->name) . "', '" . pSQL($month_year) . "', 1, '" . pSQL($description) . "', " . (int) $use_short_description . ')';
        Db::getInstance()->execute($sql);
        Configuration::updateValue('HERO_OF_THE_MONTH', $product_id);
    }

    private function updateHero($id_hero, $description, $use_short_description)
    {
        Db::getInstance()->update('heroofmonth', [
            'description' => pSQL($description),
            'use_short_description' => (int) $use_short_description,
        ], 'id_hero = ' . (int) $id_hero);
    }

    private function handleFlagImageDeletion()
    {
        $flagImage = Configuration::get('HERO_FLAG_IMAGE');
        $imagePath = _PS_MODULE_DIR_ . $this->name . '/views/img/' . $flagImage;
        if (file_exists($imagePath)) {
            unlink($imagePath);
            Configuration::deleteByName('HERO_FLAG_IMAGE');

            return $this->displayConfirmation($this->l('The image has been successfully deleted.'));
        }

        return '';
    }

    private function handleFlagImageUpload()
    {
        $file = $_FILES['HERO_FLAG_IMAGE'];
        $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif'];
        if (in_array($file['type'], $allowedMimeTypes)) {
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = 'flag_image.' . $extension;
            $destination = _PS_MODULE_DIR_ . $this->name . '/views/img/' . $filename;
            if (move_uploaded_file($file['tmp_name'], $destination)) {
                Configuration::updateValue('HERO_FLAG_IMAGE', $filename);

                return $this->displayConfirmation($this->l('The image has been successfully uploaded.'));
            }

            return $this->displayError($this->l('Error while uploading the image.'));
        }

        return $this->displayError($this->l('File type not allowed. Please upload a JPEG, PNG, or GIF image.'));
    }

    private function handleLayoutSettingsSubmission()
    {
        Configuration::updateValue('HERO_COLOR1', Tools::getValue('color1'));
        Configuration::updateValue('HERO_COLOR2', Tools::getValue('color2'));
        Configuration::updateValue('HERO_COLOR3', Tools::getValue('color3'));
        Configuration::updateValue('HERO_LAYOUT_TYPE', Tools::getValue('layout_type', 'full'));

        return $this->displayConfirmation($this->l('Settings saved successfully.'));
    }

    private function handleHeroOfMonthSubmission()
    {
        $productId = Tools::getValue('HERO_OF_THE_MONTH');
        if (!Validate::isInt($productId)) {
            return $this->displayError($this->l('Invalid product ID.'));
        }
        $month = date('m');
        $year = date('Y');
        $monthYear = $month . '/' . $year;
        $customDescription = Tools::getValue('hero_custom_description');
        $useShortDescription = Tools::getValue('switch_description_short');
        $existingHero = Db::getInstance()->getRow('SELECT id_hero FROM ' . _DB_PREFIX_ . 'heroofmonth WHERE month = "' . pSQL($monthYear) . '" AND active = 1');
        if ($existingHero) {
            return $this->displayError($this->l('A product is already associated with this month. Please edit the existing product.'));
        }
        $product = new Product($productId, false, $this->context->language->id);
        $sql = 'INSERT INTO `' . _DB_PREFIX_ . "heroofmonth` (id_product, name, month, active, description, use_short_description)
            VALUES ('" . (int) $productId . "', '" . pSQL($product->name) . "', '" . pSQL($monthYear) . "', 1, '" . pSQL($customDescription) . "', " . (int) $useShortDescription . ')';
        Configuration::updateValue('HERO_OF_THE_MONTH', $productId);
        if (Db::getInstance()->execute($sql)) {
            $idHero = Db::getInstance()->Insert_ID();

            return $this->handleCustomImageUpload($idHero);
        }

        return $this->displayError($this->l('Error while adding the product.'));
    }

    private function handleCustomImageUpload($idHero)
    {
        if (isset($_FILES['HERO_CUSTOM_IMAGE']) && !empty($_FILES['HERO_CUSTOM_IMAGE']['tmp_name'])) {
            $file = $_FILES['HERO_CUSTOM_IMAGE'];
            $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif'];
            if (in_array($file['type'], $allowedMimeTypes)) {
                $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                $filename = 'hero_' . (int) $idHero . '.' . $extension;
                $destination = _PS_MODULE_DIR_ . $this->name . '/views/img/' . $filename;
                if (move_uploaded_file($file['tmp_name'], $destination)) {
                    Db::getInstance()->update('heroofmonth', ['image' => pSQL($filename)], 'id_hero = ' . (int) $idHero);
                    Configuration::updateValue('HERO_CUSTOM_IMAGE', $filename);

                    return $this->displayConfirmation($this->l('Custom image uploaded successfully.'));
                }

                return $this->displayError($this->l('Error while uploading the image. '));
            }

            return $this->displayError($this->l('File type not allowed. Please upload a JPEG, PNG, or GIF image.'));
        }

        return '';
    }

    private function handleHeroDeletion()
    {
        $idHero = (int) Tools::getValue('id_hero');
        $hero = Db::getInstance()->getRow('SELECT month FROM ' . _DB_PREFIX_ . 'heroofmonth WHERE id_hero = ' . (int) $idHero);
        if ($hero) {
            $heroMonthYear = explode('/', $hero['month']);
            $heroMonth = (int) $heroMonthYear[0];
            $heroYear = (int) $heroMonthYear[1];
            $currentMonth = (int) date('m');
            $currentYear = (int) date('Y');
            if ($heroYear < $currentYear || ($heroYear === $currentYear && $heroMonth < $currentMonth)) {
                return $this->displayError($this->l('Unable to delete this hero because the month has already passed.'));
            }
            $this->deleteHero($idHero);
            Configuration::updateValue('HERO_OF_THE_MONTH', null);

            return $this->displayConfirmation($this->l('Hero successfully deleted.'));
        }

        return $this->displayError($this->l('Hero not found.'));
    }

    private function handleCustomImageDeletion()
    {
        $idHero = (int) Tools::getValue('id_hero');
        $hero = $this->getHeroById($idHero);
        if ($hero && !empty($hero['image'])) {
            $imagePath = _PS_MODULE_DIR_ . $this->name . '/views/img/' . $hero['image'];
            if (file_exists($imagePath) && unlink($imagePath)) {
                Db::getInstance()->update('heroofmonth', ['image' => ''], 'id_hero = ' . (int) $idHero);

                return $this->displayConfirmation($this->l('The image has been successfully deleted.'));
            }

            return $this->displayError($this->l('Unable to delete the image. Please check the file permissions.'));
        }

        return $this->displayError($this->l('The image was not found or does not exist.'));
    }
    
    protected function renderHeroStatistics()
    {
        $id_hero = (int) Tools::getValue('id_hero');
        $hero = Db::getInstance()->getRow('SELECT * FROM '._DB_PREFIX_.'heroofmonth WHERE id_hero = '.$id_hero);

        if ($hero) {
            $product_id = (int) $hero['id_product'];
            $month_year = $hero['month'];
            list($month, $year) = explode('/', $month_year);

            $hero_product = new Product($product_id, true, $this->context->language->id);

            if (Validate::isLoadedObject($hero_product)) {
                $sales_stats = $this->getSalesStats($product_id, (int) $month, (int) $year);

                $admin_token = Tools::getAdminTokenLite('AdminProducts');
                $admin_product_link = $this->context->link->getAdminLink('AdminProducts').'&sell/catalog/products-v2/'.$hero_product->id.'/edit?_token='.$admin_token;

                $this->context->smarty->assign([
                    'total_quantity_sold' => (int) $sales_stats['total_quantity'],
                    'total_sales' => (float) $sales_stats['total_sales'],
                    'hero_product' => $hero_product,
                    'product_id' => $product_id,
                    'month' => $month,
                    'year' => $year,
                    'link' => $this->context->link,
                    'admin_product_link' => $admin_product_link,
                    'back_link' => $this->context->link->getAdminLink('AdminModules').'&configure='.$this->name,
                ]);

                return $this->display(__FILE__, 'views/templates/admin/heroofmonth_stats.tpl');
            } else {
                $this->context->controller->errors[] = $this->l('Invalid product.');
            }
        } else {
            $this->context->controller->errors[] = $this->l('Hero record not found.');
        }

        return '';
    }
}
