{**
 * @author    Ali Bahadori <ali.bahadori41@yahoo.com>
 * @copyright Ali Bahadori 2024
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 *}

<section id="{$moduleName}-displayPaymentReturn">
  {if !empty($transaction)}
    <p>{l s='Your transaction reference is %transaction%.' mod='zarinpal' sprintf=['%transaction%' => $transaction]}</p>
  {/if}
  {if $customer.is_logged && !$customer.is_guest}
    <p><a href="{$transactionsLink}">{l s='See all previous transactions in your account.' mod='zarinpal'}</a></p>
  {/if}
</section>

