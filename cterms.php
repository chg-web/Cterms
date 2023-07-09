<?php
/**
 * Cterms - Modules Prestashop
 * @author    montuy337513 pour CHG-WEB <cm@chg-web.com>
 * @copyright Since 2010 CHG-WEB
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 */
if (!defined('_PS_VERSION_')) {
    exit;
}
if (!defined('_MYSQL_ENGINE_')) {
    define('_MYSQL_ENGINE_', 'MyISAM');
}

use PrestaShop\PrestaShop\Core\Checkout\TermsAndConditions;

class Cterms extends Module
{
    private $errors = null;

    public function __construct()
    {
        $this->author = 'CHG-WEB';
        $this->name = 'cterms';
        $this->tab = 'others';
        $this->version = '1.1.3';
        $this->ps_versions_compliancy = ['min' => '1.7.6', 'max' => _PS_VERSION_];
        $this->need_instance = 1;
        $this->bootstrap = true;
        // Construction de la classe
        parent::__construct();
        // Paramètres backoffice
        $this->displayName = $this->l('Cterms');
        $this->description = $this->l('Add a clause to validate order');
        $this->confirmUninstall = $this->l('Are you sure you want to delete this module ?');
        $this->errors = [];
    }

    // Installation du module
    public function install()
    {
        return parent::install() && $this->registerHook('termsAndConditions');
    }

    // Suppression du module
    public function uninstall()
    {
        return parent::uninstall() && Configuration::deleteByName('CTERMS_ADD_TERMS_TXT') && Configuration::deleteByName('CTERMS_ADD_TERMS_LINK');
    }

    public function getContent()
    {
        if (((bool) Tools::isSubmit('submitCtermsModule')) == true) {
            $this->postProcess();
        }
        $this->context->smarty->assign([
            'cterms_url' => $this->_path,
        ]);
        $this->context->controller->addCSS($this->_path . 'views/css/admin.css', 'all');
        return $this->display(__FILE__, 'views/templates/admin/panel.tpl') . $this->displayForm();
    }

    protected function displayForm()
    {
        $helper = new HelperForm();
        $languages = $this->context->controller->getLanguages();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);
        $helper->languages = $languages;
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitCtermsModule';
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
                . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->tpl_vars = [
            'fields_value' => $this->getConfigFormValues(),
            'languages' => $languages,
            'id_language' => $this->context->language->id,
        ];

        return $helper->generateForm([$this->getConfigForm()]);
    }

    protected function getConfigForm()
    {
        return [
            'form' => [
                'legend' => $this->getLegendForm(),
                'input' => $this->getInputForm(),
                'submit' => $this->getSubmitForm(),
            ],
        ];
    }

    protected function getLegendForm()
    {
        return [
            'title' => $this->l('Settings'),
            'icon' => 'icon-cogs',
        ];
    }

    protected function getSubmitForm()
    {
        return [
            'title' => $this->l('Save'),
        ];
    }

    protected function getInputForm()
    {
        return [
            [
                'col' => 6,
                'type' => 'textarea',
                'desc' => $this->l('Enter the text to display. To add an internal link to the site to the text of the condition, simply add brackets around the word containing the link. Example: text of the [condition]'),
                'name' => 'CTERMS_ADD_TERMS_TXT',
                'label' => $this->l('Text of terms'),
                'lang' => true,
            ],
            [
                'col' => 6,
                'type' => 'text',
                'label' => $this->l('Link'),
                'name' => 'CTERMS_ADD_TERMS_LINK',
                'desc' => $this->l('Link that will be interpreted if you put a word in brackets of the text above.'),
            ],
        ];
    }

    protected function getConfigFormValues()
    {
        $languages = $this->context->language->getLanguages();
        $textTerms = [];
        foreach ($languages as $language) {
            $textTerms[$language['id_lang']] = Configuration::get('CTERMS_ADD_TERMS_TXT', $language['id_lang']);
        }

        return [
            'CTERMS_ADD_TERMS_TXT' => $textTerms,
            'CTERMS_ADD_TERMS_LINK' => Configuration::get('CTERMS_ADD_TERMS_LINK'),
        ];
    }

    /**
     * Save form data.
     */
    protected function postProcess()
    {
        $form_values = $this->getConfigFormValues();
        $languages = $this->context->language->getLanguages();
        $textTerms = [];
        foreach ($languages as $language) {
            $textTerms[$language['id_lang']] = Tools::getValue('CTERMS_ADD_TERMS_TXT_' . $language['id_lang']);
        }
        foreach (array_keys($form_values) as $key) {
            if ('CTERMS_ADD_TERMS_TXT' === $key) {
                Configuration::updateValue($key, $textTerms, true);
            } else {
                Configuration::updateValue($key, Tools::getValue($key));
            }
        }
    }

    public function hookTermsAndConditions($params)
    {
        if ($params) {
            // pour la convention PSR-4
        }
        $textTerms = Configuration::get('CTERMS_ADD_TERMS_TXT', $this->context->language->id);
        $linkTerms = Configuration::get('CTERMS_ADD_TERMS_LINK');
        $terms = new TermsAndConditions();
        $terms->setIdentifier('cterms');
        if ($linkTerms !== '') {
            $terms->setText($textTerms, $linkTerms);
        } else {
            $terms->setText($textTerms);
        }
        return [$terms];
    }
}
