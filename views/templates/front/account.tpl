{**
 * @author    Ali Bahadori <ali.bahadori41@yahoo.com>
 * @copyright Ali Bahadori 2024
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 *}

{extends file='customer/page.tpl'}

{block name='page_title'}
  <h1 class="h1">{$moduleDisplayName} - {l s='Transactions' mod='zarinpal'}</h1>
{/block}

{block name='page_content'}
  {if $orderPayments}
    <table class="table table-striped table-bordered hidden-sm-down">
      <thead class="thead-default">
      <tr>
        <th>{l s='Order reference' mod='zarinpal'}</th>
        <th>{l s='Payment method' mod='zarinpal'}</th>
        <th>{l s='Transaction reference' mod='zarinpal'}</th>
        <th>{l s='Amount' mod='zarinpal'}</th>
        <th>{l s='Date' mod='zarinpal'}</th>
      </tr>
      </thead>
      <tbody>
      {foreach from=$orderPayments item=orderPayment}
        <tr>
          <td>{$orderPayment.order_reference}</td>
          <td>{$orderPayment.payment_method}</td>
          <td>{$orderPayment.transaction_id}</td>
          <td>{$orderPayment.amount_formatted}</td>
          <td>{$orderPayment.date_formatted}</td>
        </tr>
      {/foreach}
      </tbody>
    </table>
  {else}
    <div class="alert alert-info">{l s='No transaction' mod='zarinpal'}</div>
  {/if}
{/block}
