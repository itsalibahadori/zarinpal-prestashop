{**
 * @author    Ali Bahadori <ali.bahadori41@yahoo.com>
 * @copyright Ali Bahadori 2024
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 *}

<section id="zarinpal-binary-form" class="js-payment-binary js-payment-zarinpal disabled">
  <p class="alert alert-warning accept-cgv">{l s='You must accept the terms and conditions to be able to process your order.' mod='zarinpal'}</p>
  <form action="{$action}" method="post">
    <input type="hidden" name="option" value="binary">
    <button type="submit" class="btn btn-primary">
      {l s='Pay binary' mod='zarinpal'}
    </button>
  </form>
</section>
