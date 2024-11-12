<?php
/**
 * Copyright Mr-dev
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
        return parent::install() &&
            $this->registerHook('displayHome') &&
            $this->registerHook('displayProductListReviews') &&
            $this->registerHook('actionFrontControllerSetMedia') &&
            $this->createTable() &&
            $this->initializeConfigurations();
    }
    
    public function uninstall()
    {
        return parent::uninstall() &&
            $this->deleteConfigurations() &&
            $this->deleteTable() &&
            $this->removeTab();
    }
    
    private function initializeConfigurations()
    {
        Configuration::updateValue('HERO_COLOR1', '#000000');
        Configuration::updateValue('HERO_COLOR2', '#FFFFFF');
        Configuration::updateValue('HERO_COLOR3', '#FF0000');
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
            isset($params['product']['id_product']) &&
            $params['product']['id_product'] === $heroProductId &&
            file_exists($flagImagePath)
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
        $output .= '<a href="' . $this->context->link->getAdminLink('AdminModules', false) . '&configure=' . $this->name . '&action=layoutSettings&token=' . Tools::getAdminTokenLite('AdminModules') . '" class="btn btn-primary" style="margin-bottom: 20px;">' . $this->l('Configuration du produit du mois') . '</a>';
        if (Tools::getValue('action') === 'layoutSettings') {
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
                    'title' => $this->l('Ajouter un produit'),
                    'icon' => 'icon-cogs',
                ],
                'input' => [
                    [
                        'type' => 'html',
                        'label' => $this->l('Recherche Produit (ID, Nom, Référence)'),
                        'name' => 'product_search_html',
                        'html_content' => $this->context->smarty->fetch($this->local_path . 'views/templates/admin/product_search.tpl'),
                    ],
                    ['type' => 'hidden', 'name' => 'HERO_OF_THE_MONTH', 'id' => 'HERO_OF_THE_MONTH'],
                    [
                        'type' => 'switch',
                        'label' => $this->l('Utiliser la description courte du produit'),
                        'name' => 'switch_description_short',
                        'desc' => $this->l('Activez pour utiliser la description courte du produit au lieu de la description personnalisée.'),
                        'values' => [
                            ['id' => 'active_on', 'value' => 1, 'label' => $this->l('Oui')],
                            ['id' => 'active_off', 'value' => 0, 'label' => $this->l('Non')],
                        ],
                        'value' => $use_short_description,
                    ],
                    [
                        'type' => 'textarea',
                        'label' => $this->l('Description personnalisée'),
                        'name' => 'hero_custom_description',
                        'desc' => $this->l('Ajoutez une description personnalisée pour ce produit.'),
                        'rows' => 10,
                        'cols' => 50,
                        'autoload_rte' => true,
                    ],
                    [
                        'type' => 'file',
                        'label' => $this->l('Image personnalisée pour le produit'),
                        'name' => 'HERO_CUSTOM_IMAGE',
                        'desc' => $this->l('Téléchargez une image pour ce produit.'),
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
                    'title' => $this->l('Modifier le produit héros'),
                    'icon' => 'icon-cogs',
                ],
                'input' => [
                    [
                        'type' => 'html',
                        'label' => $this->l('Recherche Produit (ID, Nom, Référence)'),
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
                        'label' => $this->l('Description personnalisée'),
                        'name' => 'hero_custom_description',
                        'desc' => $this->l('Ajoutez une description personnalisée pour ce produit.'),
                        'rows' => 10,
                        'cols' => 50,
                        'autoload_rte' => true,
                        'value' => $hero['description'],
                        'disabled' => (bool) $hero['use_short_description'],
                        'id' => 'custom_description',
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->l('Utiliser la description courte du produit'),
                        'name' => 'switch_description_short',
                        'desc' => $this->l('Activez pour utiliser la description courte du produit au lieu de la description personnalisée.'),
                        'values' => [
                            [
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Oui'),
                            ],
                            [
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Non'),
                            ],
                        ],
                        'value' => (int) $hero['use_short_description'],
                        'id' => 'switch_description',
                    ],
                    [
                        'type' => 'file',
                        'label' => $this->l('Modifier l\'image du produit héros'),
                        'name' => 'HERO_CUSTOM_IMAGE',
                        'desc' => $this->l('Téléchargez une nouvelle image pour ce produit.'),
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
                'title' => $this->l('Sauvegarder'),
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
        $helper->title = $this->l('Produits Héros du Mois');
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
                'label' => $this->l('Image du flag pour le produit du mois'),
                'name' => 'HERO_FLAG_IMAGE',
                'desc' => $this->l('Choisissez une image pour le flag du produit du mois.'),
            ],
            [
                'type' => 'color',
                'label' => $this->l('Couleur de fond (background color)'),
                'name' => 'color1',
            ],
            [
                'type' => 'color',
                'label' => $this->l('Couleur de l\'entête (header color)'),
                'name' => 'color2',
            ],
            [
                'type' => 'color',
                'label' => $this->l('Couleur du texte (text color)'),
                'name' => 'color3',
            ],
            [
                'type' => 'radio',
                'label' => $this->l('Disposition'),
                'name' => 'layout_type',
                'values' => [
                    ['id' => 'full_width', 'value' => 'full', 'label' => $this->l('Pleine largeur (full-width)')],
                    ['id' => 'boxed', 'value' => 'boxed', 'label' => $this->l('Encadré (boxed)')],
                ],
                'desc' => $this->l('Choisissez entre une mise en page pleine largeur ou encadrée.'),
            ],
        ];
        if (!empty($flag_image) && file_exists($image_path)) {
            $flag_image_url = $this->context->link->getBaseLink() . 'modules/' . $this->name . '/views/img/' . $flag_image;
            $delete_flag_image_url = $this->context->link->getAdminLink('AdminModules', true) . '&delete_flag_image=1&configure=' . $this->name;
            $delete_flag_image_text = $this->l('Supprimer l\'image');
        
            
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
                    'title' => $this->l('Configuration du produit du mois'),
                    'icon' => 'icon-cogs',
                ],
                'input' => $inputs,
                'buttons' => [
                    [
                        'type' => 'button',
                        'title' => $this->l('Retour'),
                        'class' => 'btn btn-secondary',
                        'href' => $this->context->link->getAdminLink('AdminModules') . '&configure=' . $this->name,
                    ],
                    [
                        'type' => 'submit',
                        'title' => $this->l('Sauvegarder'),
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
            $output .= $this->displayConfirmation($this->l('Produit héros mis à jour avec succès.'));
        } else {
            $output .= $this->displayError($this->l('Erreur lors de la mise à jour du produit héros.'));
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
                $this->context->controller->errors[] = $this->l('Erreur lors du téléchargement de l\'image.');
            }
        } else {
            $this->context->controller->errors[] = $this->l('Type de fichier non autorisé. Veuillez télécharger une image JPEG, PNG ou GIF.');
        }
    }
    
    private function deactivatePreviousHero($month_year)
    {
        Db::getInstance()->execute("UPDATE `" . _DB_PREFIX_ . "heroofmonth` SET active = 0 WHERE month = '" . pSQL($month_year) . "'");
        Configuration::updateValue('HERO_OF_THE_MONTH', null);
    }
    
    private function addNewHero($product_id, $description, $use_short_description, $month_year)
    {
        $product = new Product($product_id, false, $this->context->language->id);
        $sql = "INSERT INTO `" . _DB_PREFIX_ . "heroofmonth` (id_product, name, month, active, description, use_short_description) 
            VALUES ('" . (int) $product_id . "', '" . pSQL($product->name) . "', '" . pSQL($month_year) . "', 1, '" . pSQL($description) . "', " . (int) $use_short_description . ")";
        Db::getInstance()->execute($sql);
        Configuration::updateValue('HERO_OF_THE_MONTH', $product_id);
    }
    
    private function updateHero($id_hero, $description, $use_short_description)
    {
        Db::getInstance()->update('heroofmonth', [
            'description' => pSQL($description),
            'use_short_description' => (int) $use_short_description
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
        $sql = "INSERT INTO `" . _DB_PREFIX_ . "heroofmonth` (id_product, name, month, active, description, use_short_description)
            VALUES ('" . (int) $productId . "', '" . pSQL($product->name) . "', '" . pSQL($monthYear) . "', 1, '" . pSQL($customDescription) . "', " . (int) $useShortDescription . ")";
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
}
