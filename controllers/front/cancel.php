<?php
/**
 * @author    Ali Bahadori <ali.bahadori41@yahoo.com>
 * @copyright Ali Bahadori 2024
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 */

/**
 * This Controller receive customer on cancelation on bank payment page
 *
 * @todo If you use a webhook or automatic response do not use redirection
 */
class ZarinpalCancelModuleFrontController extends ModuleFrontController
{
    /**
     * {@inheritdoc}
     */
    public function postProcess()
    {
        // @todo Use a transaction identifier instead, this is just an example
        $id_order = (int) Tools::getValue('id_order');
        // @todo Use a secure key to avoid illegal access

        // Order is already saved in PrestaShop
        if (false === empty($id_order)) {
            $order = new Order($id_order);

            if (false === Validate::isLoadedObject($order)) {
                // Order not found
                Tools::redirect($this->context->link->getPageLink('index'));
            }

            $currentOrderStateId = (int) $order->getCurrentState();
            $newOrderStateId = (int) $this->getNewState($order);

            // Prevent duplicate state entry
            if ($currentOrderStateId !== $newOrderStateId
                && false === (bool) $order->hasBeenShipped()
                && false === (bool) $order->hasBeenDelivered()
            ) {
                $orderHistory = new OrderHistory();
                $orderHistory->id_order = $id_order;
                $orderHistory->changeIdOrderState(
                    $newOrderStateId,
                    $id_order
                );
                $orderHistory->addWithemail();
            }

            // @todo If order has been shipped or has been delivered and Merchandise Returns option is enabled you can do some others things

            Tools::redirect($this->context->link->getPageLink('index'));
        }

        // Order not saved, redirect to Payment step
        Tools::redirect($this->context->link->getPageLink(
            'order',
            true,
            (int) $this->context->language->id,
            [
                'step' => 4,
            ]
        ));
    }

    /**
     * @param Order $order
     *
     * @return int
     */
    private function getNewState(Order $order)
    {
        if ($order->hasBeenPaid() || $order->isInPreparation()) {
            return (int) Configuration::get('PS_OS_CANCELED');
        }

        return (int) Configuration::get('PS_OS_ERROR');
    }
}
