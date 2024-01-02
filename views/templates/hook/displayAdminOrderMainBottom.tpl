{**
 * @author    Ali Bahadori <ali.bahadori41@yahoo.com>
 * @copyright Ali Bahadori 2024
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 *}

<section id="{$moduleName}-displayAdminOrderMainBottom">
  <div class="card mt-2">
    <div class="card-header">
      <h3 class="card-header-title">
        <img src="{$moduleLogoSrc}" alt="{$moduleDisplayName}" width="20" height="20">
        {$moduleDisplayName}
      </h3>
    </div>
    <div class="card-body">
      <p>{l s='This order has been paid with %moduleDisplayName%.' mod='zarinpal' sprintf=['%moduleDisplayName%' => $moduleDisplayName]}</p>
    </div>
  </div>
</section>
