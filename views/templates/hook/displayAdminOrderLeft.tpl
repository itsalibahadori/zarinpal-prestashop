{**
 * @author    Ali Bahadori <ali.bahadori41@yahoo.com>
 * @copyright Ali Bahadori 2024
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 *}

<section id="{$moduleName}-displayAdminOrderLeft">
  <div class="panel">
    <div class="panel-heading">
      <img src="{$moduleLogoSrc}" alt="{$moduleDisplayName}" width="15" height="15">
      {$moduleDisplayName}
    </div>
    <p>{l s='This order has been paid with %moduleDisplayName%.' mod='zarinpal' sprintf=['%moduleDisplayName%' => $moduleDisplayName]}</p>
  </div>
</section>
