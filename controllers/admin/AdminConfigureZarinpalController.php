<?php
/**
 * @author    Ali Bahadori <ali.bahadori41@yahoo.com>
 * @copyright Ali Bahadori 2024
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 */
class AdminConfigureZarinpalController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        $this->className = 'Configuration';
        $this->table = 'configuration';

        parent::__construct();

        if (empty(Currency::checkPaymentCurrencies($this->module->id))) {
            $this->warnings[] = $this->l('No currency has been set for this module.');
        }

        $this->fields_options = [
            $this->module->name => [
                'fields' => [
                    Zarinpal::ZARINPAL_EXTERNAL_ENABLED => [
                        'type' => 'bool',
                        'title' => $this->l('فعالسازی درگاه پرداخت'),
                        'validation' => 'isBool',
                        'cast' => 'intval',
                    ],
                    Zarinpal::ZARINPAL_MERCHANT_CODE => [
                        'type' => 'text',
                        'title' => $this->l('مرچنت کد'),
                    ],
                    Zarinpal::ZARINPAL_CURRENCY => [
                        'type' => 'select',
                        'title' => $this->l('ارز درگاه'),
                        'required' => true,
                        'identifier' => 'id',
                        'list' => [
                            [
                                'id' => 'IRR',
                                'name' => 'ریال' 
                            ],
                            [
                                'id' => 'IRT',
                                'name' => 'تومان' 
                            ],
                        ],
                    ],
                ],
                'submit' => [
                    'title' => $this->l('ذخیره'),
                ],
            ],
        ];
    }
}
