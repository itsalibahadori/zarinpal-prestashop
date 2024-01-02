<?php
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License 3.0 (AFL-3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 */

use PrestaShop\PrestaShop\Core\Payment\PaymentOption;

if (!defined('_PS_VERSION_')) {
    exit;
}

class Zarinpal extends PaymentModule
{
    const ZARINPAL_EXTERNAL_ENABLED = 'ZARINPAL_PO_EXTERNAL_ENABLED';
    const ZARINPAL_MERCHANT_CODE = 'ZARINPAL_PO_MERCHANT_CODE';
    const ZARINPAL_CURRENCY = 'ZARINPAL_CURRENCY';
    const MODULE_ADMIN_CONTROLLER = 'AdminConfigureZarinpal';
    private const ZARINPAL_TRANSACTIONS_TABLE = 'zarinpal_payment_transactions';

    const HOOKS = [
        'actionPaymentCCAdd',
        'actionObjectShopAddAfter',
        'paymentOptions',
        'displayAdminOrderLeft',
        'displayAdminOrderMainBottom',
        'displayCustomerAccount',
        'displayOrderConfirmation',
        'displayOrderDetail',
        'displayPaymentByBinaries',
        'displayPaymentReturn',
        'displayPDFInvoice',
    ];

    public function __construct()
    {
        $this->name = 'zarinpal';
        $this->tab = 'payments_gateways';
        $this->version = '2.0.0';
        $this->author = 'Ali bahadori';
        $this->currencies = true;
        $this->currencies_mode = 'checkbox';
        $this->ps_versions_compliancy = [
            'min' => '1.7',
            'max' => _PS_VERSION_,
        ];
        $this->controllers = [
            'account',
            'cancel',
            'external',
            'verify',
        ];

        parent::__construct();

        $this->displayName = $this->l('Zarinpal Payment');
        $this->description = $this->l('Description of Payment Example');
    }

    /**
     * @return bool
     */
    public function install()
    {
        return (bool) parent::install()
            && (bool) $this->registerHook(static::HOOKS)
            && $this->installConfiguration()
            && $this->createZarinpalTransactionsTable()
            && $this->installTabs();
    }

    /**
     * @return bool
     */
    public function uninstall()
    {
        return (bool) parent::uninstall()
            && $this->uninstallConfiguration()
            && $this->uninstallTabs();
    }

    /**
     * Module configuration page
     */
    public function getContent()
    {
        // Redirect to our ModuleAdminController when click on Configure button
        Tools::redirectAdmin($this->context->link->getAdminLink(static::MODULE_ADMIN_CONTROLLER));
    }

    /**
     * @param array $params
     *
     * @return array Should always return an array
     */
    public function hookPaymentOptions(array $params)
    {
        /** @var Cart $cart */
        $cart = $params['cart'];

        if (false === Validate::isLoadedObject($cart) || false === $this->checkCurrency($cart)) {
            return [];
        }

        $paymentOptions = [];


        if (Configuration::get(static::ZARINPAL_EXTERNAL_ENABLED)) {
            $paymentOptions[] = $this->getExternalPaymentOption();
        }

        return $paymentOptions;
    }

    public static function createZarinpalTransactionsTable()
    {
        $sql = "CREATE TABLE IF NOT EXISTS ". self::getTransactionTableName() ." (
            `transaction_id` INT(10) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `transaction_authority` VARCHAR(255),
            `id_cart` INT(10) NOT NULL,
            `transaction_ref_code` VARCHAR(255))";

        return Db::getInstance()->execute($sql);
    }

    /**
     * This hook is used to display additional information on BO Order View, under Payment block
     *
     * @since PrestaShop 1.7.7 This hook is replaced by displayAdminOrderMainBottom on migrated BO Order View
     *
     * @param array $params
     *
     * @return string
     */
    public function hookDisplayAdminOrderLeft(array $params)
    {
        if (empty($params['id_order'])) {
            return '';
        }

        $order = new Order((int) $params['id_order']);

        if (false === Validate::isLoadedObject($order) || $order->module !== $this->name) {
            return '';
        }

        $this->context->smarty->assign([
            'moduleName' => $this->name,
            'moduleDisplayName' => $this->displayName,
            'moduleLogoSrc' => $this->getPathUri() . 'logo.png',
        ]);

        return $this->context->smarty->fetch('module:zarinpal/views/templates/hook/displayAdminOrderLeft.tpl');
    }

    /**
     * This hook is used to display additional information on BO Order View, under Payment block
     *
     * @since PrestaShop 1.7.7 This hook replace displayAdminOrderLeft on migrated BO Order View
     *
     * @param array $params
     *
     * @return string
     */
    public function hookDisplayAdminOrderMainBottom(array $params)
    {
        if (empty($params['id_order'])) {
            return '';
        }

        $order = new Order((int) $params['id_order']);

        if (false === Validate::isLoadedObject($order) || $order->module !== $this->name) {
            return '';
        }

        $this->context->smarty->assign([
            'moduleName' => $this->name,
            'moduleDisplayName' => $this->displayName,
            'moduleLogoSrc' => $this->getPathUri() . 'logo.png',
        ]);

        return $this->context->smarty->fetch('module:zarinpal/views/templates/hook/displayAdminOrderMainBottom.tpl');
    }

    /**
     * This hook is used to display information in customer account
     *
     * @param array $params
     *
     * @return string
     */
    public function hookDisplayCustomerAccount(array $params)
    {
        $this->context->smarty->assign([
            'moduleDisplayName' => $this->displayName,
            'moduleLogoSrc' => $this->getPathUri() . 'logo.png',
            'transactionsLink' => $this->context->link->getModuleLink(
                $this->name,
                'account'
            ),
        ]);

        return $this->context->smarty->fetch('module:zarinpal/views/templates/hook/displayCustomerAccount.tpl');
    }

    /**
     * This hook is used to display additional information on order confirmation page
     *
     * @param array $params
     *
     * @return string
     */
    public function hookDisplayOrderConfirmation(array $params)
    {
        if (empty($params['order'])) {
            return '';
        }

        /** @var Order $order */
        $order = $params['order'];

        if (false === Validate::isLoadedObject($order) || $order->module !== $this->name) {
            return '';
        }

        $transaction = '';

        if ($order->getOrderPaymentCollection()->count()) {
            /** @var OrderPayment $orderPayment */
            $orderPayment = $order->getOrderPaymentCollection()->getFirst();
            $transaction = $orderPayment->transaction_id;
        }

        $this->context->smarty->assign([
            'moduleName' => $this->name,
            'transaction' => $transaction,
        ]);

        return $this->context->smarty->fetch('module:zarinpal/views/templates/hook/displayOrderConfirmation.tpl');
    }

    /**
     * This hook is used to display additional information on FO (Guest Tracking and Account Orders)
     *
     * @param array $params
     *
     * @return string
     */
    public function hookDisplayOrderDetail(array $params)
    {
        if (empty($params['order'])) {
            return '';
        }

        /** @var Order $order */
        $order = $params['order'];

        if (false === Validate::isLoadedObject($order) || $order->module !== $this->name) {
            return '';
        }

        $transaction = '';

        if ($order->getOrderPaymentCollection()->count()) {
            /** @var OrderPayment $orderPayment */
            $orderPayment = $order->getOrderPaymentCollection()->getFirst();
            $transaction = $orderPayment->transaction_id;
        }

        $this->context->smarty->assign([
            'moduleName' => $this->name,
            'transaction' => $transaction,
        ]);

        return $this->context->smarty->fetch('module:zarinpal/views/templates/hook/displayOrderDetail.tpl');
    }

    /**
     * This hook is used to display additional information on bottom of order confirmation page
     *
     * @param array $params
     *
     * @return string
     */
    public function hookDisplayPaymentReturn(array $params)
    {
        if (empty($params['order'])) {
            return '';
        }

        /** @var Order $order */
        $order = $params['order'];

        if (false === Validate::isLoadedObject($order) || $order->module !== $this->name) {
            return '';
        }

        $transaction = '';

        if ($order->getOrderPaymentCollection()->count()) {
            /** @var OrderPayment $orderPayment */
            $orderPayment = $order->getOrderPaymentCollection()->getFirst();
            $transaction = $orderPayment->transaction_id;
        }

        $this->context->smarty->assign([
            'moduleName' => $this->name,
            'transaction' => $transaction,
            'transactionsLink' => $this->context->link->getModuleLink(
                $this->name,
                'account'
            ),
        ]);

        return $this->context->smarty->fetch('module:zarinpal/views/templates/hook/displayPaymentReturn.tpl');
    }

    /**
     * This hook is used to display additional information on Invoice PDF
     *
     * @param array $params
     *
     * @return string
     */
    public function hookDisplayPDFInvoice(array $params)
    {
        if (empty($params['object'])) {
            return '';
        }

        /** @var OrderInvoice $orderInvoice */
        $orderInvoice = $params['object'];

        if (false === Validate::isLoadedObject($orderInvoice)) {
            return '';
        }

        $order = $orderInvoice->getOrder();

        if (false === Validate::isLoadedObject($order) || $order->module !== $this->name) {
            return '';
        }

        $transaction = '';

        if ($order->getOrderPaymentCollection()->count()) {
            /** @var OrderPayment $orderPayment */
            $orderPayment = $order->getOrderPaymentCollection()->getFirst();
            $transaction = $orderPayment->transaction_id;
        }

        $this->context->smarty->assign([
            'moduleName' => $this->name,
            'transaction' => $transaction,
        ]);

        return $this->context->smarty->fetch('module:zarinpal/views/templates/hook/displayPDFInvoice.tpl');
    }

    /**
     * Check if currency is allowed in Payment Preferences
     *
     * @param Cart $cart
     *
     * @return bool
     */
    private function checkCurrency(Cart $cart)
    {
        $currency_order = new Currency($cart->id_currency);
        /** @var array $currencies_module */
        $currencies_module = $this->getCurrency($cart->id_currency);

        if (empty($currencies_module)) {
            return false;
        }

        foreach ($currencies_module as $currency_module) {
            if ($currency_order->id == $currency_module['id_currency']) {
                return true;
            }
        }

        return false;
    }

    /**
     * Factory of PaymentOption for External Payment
     *
     * @return PaymentOption
     */
    private function getExternalPaymentOption()
    {
        $externalOption = new PaymentOption();
        $externalOption->setModuleName($this->name);
        $externalOption->setCallToActionText($this->l(ucfirst($this->name)));
        $externalOption->setAction($this->context->link->getModuleLink($this->name, 'external', [], true));
        $externalOption->setAdditionalInformation($this->context->smarty->fetch('module:zarinpal/views/templates/front/paymentOptionExternal.tpl'));
        $externalOption->setLogo(Media::getMediaPath(_PS_MODULE_DIR_ . $this->name . '/views/img/option/external.png'));

        return $externalOption;
    }

    /**
     * Install default module configuration
     *
     * @return bool
     */
    private function installConfiguration()
    {
        return (bool) Configuration::updateGlobalValue(static::ZARINPAL_EXTERNAL_ENABLED, '0') 
            || (bool) Configuration::updateGlobalValue(static::ZARINPAL_MERCHANT_CODE, '')
            || (bool) Configuration::updateGlobalValue(static::ZARINPAL_CURRENCY, 'IRR');
    }

    /**
     * Uninstall module configuration
     *
     * @return bool
     */
    private function uninstallConfiguration()
    {
        return (bool) Configuration::deleteByName(static::ZARINPAL_EXTERNAL_ENABLED)
        || (bool) Configuration::deleteByName(static::ZARINPAL_CURRENCY)
        || (bool) Configuration::deleteByName(static::ZARINPAL_MERCHANT_CODE);
           
    }

    /**
     * Install Tabs
     *
     * @return bool
     */
    public function installTabs()
    {
        if (Tab::getIdFromClassName(static::MODULE_ADMIN_CONTROLLER)) {
            return true;
        }

        $tab = new Tab();
        $tab->class_name = static::MODULE_ADMIN_CONTROLLER;
        $tab->module = $this->name;
        $tab->active = true;
        $tab->id_parent = -1;
        $tab->name = array_fill_keys(
            Language::getIDs(false),
            $this->displayName
        );

        return (bool) $tab->add();
    }

    /**
     * Uninstall Tabs
     *
     * @return bool
     */
    public function uninstallTabs()
    {
        $id_tab = (int) Tab::getIdFromClassName(static::MODULE_ADMIN_CONTROLLER);

        if ($id_tab) {
            $tab = new Tab($id_tab);

            return (bool) $tab->delete();
        }

        return true;
    }

    public static function getTransactionTableName() 
    {
        return _DB_PREFIX_ . self::ZARINPAL_TRANSACTIONS_TABLE;
    }


    /**
     * Zarinpal error message.
     * 
     * @param int $code
     * @return string
     */
    public static function error_message($code)
    {
        $message = null;

        switch ($code) {
            case $code == -9:
                $message = self::l('اطلاعات ارسال شده نادرست می باشد.');
            break; 
            case $code == -10:
                $message = self::l('ای پی یا مرچنت كد پذیرنده صحیح نیست.');
            break; 
            case $code == -11:
                $message = self::l('مرچنت کد فعال نیست، پذیرنده مشکل خود را به امور مشتریان زرین‌پال ارجاع دهد.');
            break; 
            case $code == -12:
                $message = self::l('مرچنت کد فعال نیست، پذیرنده مشکل خود را به امور مشتریان زرین‌پال ارجاع دهد.');
            break; 
            case $code == -15:
                $message = self::l('مرچنت کد فعال نیست، پذیرنده مشکل خود را به امور مشتریان زرین‌پال ارجاع دهد.');
            break; 
            case $code == -16:
                $message = self::l('سطح تایید پذیرنده پایین تر از سطح نقره ای است.');
            break; 
            case $code == -17:
                $message = self::l('محدودیت پذیرنده در سطح آبی');
            break; 
            case $code == -30:
                $message = self::l('پذیرنده اجازه دسترسی به سرویس تسویه اشتراکی شناور را ندارد.');
            break; 
            case $code == -31:
                $message = self::l('حساب بانکی تسویه را به پنل اضافه کنید. مقادیر وارد شده برای تسهیم درست نیست. پذیرنده جهت استفاده از خدمات سرویس تسویه اشتراکی شناور، باید حساب بانکی معتبری به پنل کاربری خود اضافه نماید.');
            break; 
            case $code == -32:
                $message = self::l('مبلغ وارد شده از مبلغ کل تراکنش بیشتر است.');
            break; 
            case $code == -33:
                $message = self::l('درصدهای وارد شده صحیح نیست.');
            break; 
            case $code == -34:
                $message = self::l('مبلغ وارد شده از مبلغ کل تراکنش بیشتر است.');
            break; 
            case $code == -35:
                $message = self::l('تعداد افراد دریافت کننده تسهیم بیش از حد مجاز است.');
            break; 
            case $code == -36:
                $message = self::l('حداقل مبلغ جهت تسهیم باید ۱۰۰۰۰ ریال باشد');
            break; 
            case $code == -37:
                $message = self::l('یک یا چند شماره شبای وارد شده برای تسهیم از سمت بانک غیر فعال است.');
            break; 
            case $code == -38:
                $message = self::l('خط،عدم تعریف صحیح شبا،لطفا دقایقی دیگر تلاش کنید.');
            break; 
            case $code == -39:
                $message = self::l('خطایی رخ داده است به امور مشتریان زرین پال اطلاع دهید');
            break; 
            case $code == -40:
                $message = self::l('');
            break; 
            case $code == -50:
                $message = self::l('مبلغ پرداخت شده با مقدار مبلغ ارسالی در متد وریفای متفاوت است.');
            break; 
            case $code == -51:
                $message = self::l('پرداخت ناموفق');
            break; 
            case $code == -52:
                $message = self::l('خطای غیر منتظره‌ای رخ داده است. پذیرنده مشکل خود را به امور مشتریان زرین‌پال ارجاع دهد.');
            break; 
            case $code == -53:
                $message = self::l('پرداخت متعلق به این مرچنت کد نیست.');
            break; 
            case $code == -54:
                $message = self::l('اتوریتی نامعتبر است.');
            break;
        }

        return $message;
    }
}
