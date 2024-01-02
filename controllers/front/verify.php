<?php

/**
 * @author    Ali Bahadori <ali.bahadori41@yahoo.com>
 * @copyright Ali Bahadori 2024
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 */

/**
 * This Controller receive customer after approval on bank payment page
 */
class ZarinpalVerifyModuleFrontController extends ModuleFrontController
{
    /**
     * @var PaymentModule
     */
    public $module;

    /**
     * {@inheritdoc}
     */
    public function postProcess()
    {
        if (false === $this->checkIfContextIsValid() || false === $this->checkIfPaymentOptionIsAvailable()) {
            Tools::redirect($this->context->link->getPageLink(
                'order',
                true,
                (int) $this->context->language->id,
                [
                    'step' => 1,
                ]
            ));
        }
        $customer = new Customer($this->context->cart->id_customer);
        if (false === Validate::isLoadedObject($customer)) {
            Tools::redirect($this->context->link->getPageLink(
                'order',
                true,
                (int) $this->context->language->id,
                [
                    'step' => 1,
                ]
            ));
        }

        if ($_GET['Status'] === 'OK') {
            $amount = $this->context->cart->getOrderTotal(true);
            
            $params = [
                'merchant_id' => Configuration::get(Zarinpal::ZARINPAL_MERCHANT_CODE),
                'amount' => $amount,
                'authority' => $_GET['Authority']
            ];
    
            $jsonData = json_encode($params);
            $ch = curl_init('https://api.zarinpal.com/pg/v4/payment/verify.json');
            curl_setopt($ch, CURLOPT_USERAGENT, 'ZarinPal Rest Api v4');
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
            curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Content-Length: ' . strlen($jsonData)
            ));
    
            $result = curl_exec($ch);
            $err = curl_error($ch);
            $result = json_decode($result, true, JSON_PRETTY_PRINT);
            curl_close($ch);

            if (! $err) {
                if ($result['data']['code'] === 100) {
                    $this->module->validateOrder(
                        (int) $this->context->cart->id,
                        (int) $this->getOrderState(),
                        (float) $this->context->cart->getOrderTotal(true, Cart::BOTH),
                        $this->l('زرین پال'),
                        null,
                        [
                            'transaction_id' => $result['data']['ref_id'],
                        ],
                        (int) $this->context->currency->id,
                        false,
                        $customer->secure_key
                    );

                    Tools::redirect($this->context->link->getPageLink(
                        'order-confirmation',
                        true,
                        (int) $this->context->language->id,
                        [
                            'id_cart' => (int) $this->context->cart->id,
                            'id_module' => (int) $this->module->id,
                            'id_order' => (int) $this->module->currentOrder,
                            'key' => $customer->secure_key,
                        ]
                    ));

                } elseif ($result['data']['code'] === 101) {
                    Tools::redirect($this->context->link->getPageLink(
                        'order-confirmation',
                        true,
                        (int) $this->context->language->id,
                        [
                            'id_cart' => (int) $this->context->cart->id,
                            'id_module' => (int) $this->module->id,
                            'id_order' => (int) $this->module->currentOrder,
                            'key' => $customer->secure_key,
                        ]
                    ));
                } else {
                    $this->errors[] = Zarinpal::error_message($result['errors']['code']);
                    $this->setTemplate('module:zarinpal/views/templates/front/verify.tpl');
                }
            } else {
                $this->errors[] = "خطا در اتصال به درگاه برای تایید تراکنش : <br> $err";
                $this->setTemplate('module:zarinpal/views/templates/front/verify.tpl');
            }
        } elseif ($_GET['Status'] === 'NOK') {
            $this->errors[] = $this->l("پرداخت توسط کاربر لغو شد");
            $this->setTemplate('module:zarinpal/views/templates/front/verify.tpl');
        }
    }

    /**
     * Check if the context is valid
     *
     * @return bool
     */
    private function checkIfContextIsValid()
    {
        return true === Validate::isLoadedObject($this->context->cart)
            && true === Validate::isUnsignedInt($this->context->cart->id_customer)
            && true === Validate::isUnsignedInt($this->context->cart->id_address_delivery)
            && true === Validate::isUnsignedInt($this->context->cart->id_address_invoice);
    }

    /**
     * Check that this payment option is still available in case the customer changed
     * his address just before the end of the checkout process
     *
     * @return bool
     */
    private function checkIfPaymentOptionIsAvailable()
    {
        $modules = Module::getPaymentModules();

        if (empty($modules)) {
            return false;
        }

        foreach ($modules as $module) {
            if (isset($module['name']) && $this->module->name === $module['name']) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get OrderState identifier
     *
     * @return int
     */
    private function getOrderState()
    {
        return (int) Configuration::get('PS_OS_WS_PAYMENT');
    }
}
