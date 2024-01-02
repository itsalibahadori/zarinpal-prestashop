{**
 * @author    Ali Bahadori <ali.bahadori41@yahoo.com>
 * @copyright Ali Bahadori 2024
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 *}

{if !empty($transaction)}
  <p>{l s='Your transaction reference is %transaction%.' mod='zarinpal' sprintf=['%transaction%' => $transaction]}</p>
{/if}
