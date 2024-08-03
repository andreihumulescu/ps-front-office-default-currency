<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to the MIT Free License
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/license/mit
 *
 * @author    Andrei H
 * @copyright Since 2024 Andrei H
 * @license   MIT
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

class FrontOfficeDefaultCurrency extends Module
{
    private const SELECTED_CURRENCY = 'FRONTOFFICEDEFAULTCURRENCY_SELECTED_CURRENCY';

    private const HOOKS = [
        'actionFrontControllerInitBefore',
    ];

    protected $config_form = false;

    public function __construct()
    {
        $this->name = 'frontofficedefaultcurrency';
        $this->tab = 'front_office_features';
        $this->version = '1.0.0';
        $this->author = 'Andrei H';
        $this->need_instance = 1;
        $this->ps_versions_compliancy = [
            'min' => '8.0.0',
            'max' => _PS_VERSION_,
        ];
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->trans('Front Office Default Currency', [], 'Modules.Frontofficedefaultcurrency.Admin');
        $this->description = $this->trans(
            'PrestaShop module that allows you to set the default currency displayed to the customers.',
            [],
            'Modules.Frontofficedefaultcurrency.Admin'
        );

        $this->confirmUninstall = $this->trans('Are you sure you want to uninstall?', [], 'Modules.Frontofficedefaultcurrency.Admin');
    }

    /**
     * {@inheritDoc}
     */
    public function install()
    {
        if (Shop::isFeatureActive()) {
            Shop::setContext(Shop::CONTEXT_ALL);
        }

        Configuration::updateValue(self::SELECTED_CURRENCY, '');

        return parent::install()
            && $this->registerHook(self::HOOKS);
    }

    /**
     * {@inheritDoc}
     */
    public function uninstall()
    {
        Configuration::deleteByName(self::SELECTED_CURRENCY);

        return parent::uninstall();
    }

    /**
     * {@inheritDoc}
     */
    public function isUsingNewTranslationSystem()
    {
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function getContent()
    {
        $html = '';

        if ((bool) Tools::isSubmit('submitSettings')) {
            $this->postProcess();

            $html .= $this->displayConfirmation($this->trans(
                'Successfully updated the default currency',
                [],
                'Modules.Frontofficedefaultcurrency.Admin'
            ));
        }

        return $html . $this->renderForm();
    }

    /**
     * Create the form that will be displayed in the configuration page.
     */
    protected function renderForm()
    {
        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitSettings';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = [
            'fields_value' => $this->getConfigFormValues(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        ];

        return $helper->generateForm([$this->getConfigForm()]);
    }

    /**
     * Create the structure of the form.
     */
    protected function getConfigForm()
    {
        return [
            'form' => [
                'legend' => [
                    'title' => $this->trans('Settings', [], 'Admin.Actions'),
                    'icon' => 'icon-cogs',
                ],
                'input' => [
                    [
                        'type' => 'select',
                        'label' => $this->trans('Default currency', [], 'Modules.Frontofficedefaultcurrency.Admin'),
                        'name' => self::SELECTED_CURRENCY,
                        'required' => true,
                        'options' => [
                            'query' => Currency::getCurrenciesByIdShop($this->context->shop->id),
                            'id' => 'id_currency',
                            'name' => 'name',
                        ],
                        'col' => '2',
                    ],
                ],
                'submit' => [
                    'title' => $this->trans('Save', [], 'Admin.Actions'),
                ],
            ],
        ];
    }

    /**
     * Set values for the inputs.
     */
    protected function getConfigFormValues()
    {
        return [
            self::SELECTED_CURRENCY => Configuration::get(self::SELECTED_CURRENCY),
        ];
    }

    /**
     * Save form data.
     */
    protected function postProcess()
    {
        $form_values = $this->getConfigFormValues();

        foreach (array_keys($form_values) as $key) {
            Configuration::updateValue($key, Tools::getValue($key));
        }
    }

    /**
     * {@inheritDoc}
     */
    public function hookActionFrontControllerInitBefore()
    {
        if (empty($this->context->cookie->id_currency) && !empty(Configuration::get(self::SELECTED_CURRENCY))) {
            $this->context->cookie->id_currency = Configuration::get(self::SELECTED_CURRENCY);
        }
    }
}
