{*
/**
* Copyright Mr-dev
*
* NOTICE OF LICENSE
*
* This file is proprietary and each license is valid for use on one website only.
* To use this file on additional websites or projects, additional licenses must be purchased.
* Redistribution, reselling, leasing, licensing, sub-licensing, or offering this resource to any third party is strictly
prohibited.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to keep compatibility with future PrestaShop updates.
*
* @author Mr-dev
* @copyright Mr-dev
* @license License valid for one website (or project) per purchase
*/
*}
<div class="hero-stats-container">
  <div class="hero-header">
    <h2>
      Hero of the Month: {$hero_product->name|escape:'html':'UTF-8'} (ID:
      {$hero_product->id|escape:'html':'UTF-8'}) for {$month|escape:'html':'UTF-8'}/{$year|escape:'html':'UTF-8'}
    </h2>
  </div>
  
  <div class="hero-product-description">
    <h3>Description:</h3>
    <p>{$product_description|escape:'html':'UTF-8'}</p>
  </div>

  <div class="hero-product-image">
    <img src="{$product_image|escape:'url':'UTF-8'}" alt="{$hero_product->name|escape:'html':'UTF-8'}"
      class="img-fluid" />
  </div>
  <div class="hero-stats-content">
    <div class="stat-item">
      <label>Total Quantity Sold:</label>
      <span>{$total_quantity_sold|escape:'html':'UTF-8'}</span>
    </div>
    <div class="stat-item">
      <label>Total Sales (Including Tax):</label>
      <span>{$total_sales|escape:'html':'UTF-8'} {$currency.iso_code|escape:'html':'UTF-8'}</span>
    </div>
    <div class="stat-item">
      <label>Average Price per Unit (Including Tax):</label>
      {if $total_quantity_sold > 0}
      <span>
        {math equation="total_sales / total_quantity_sold"
        total_sales=$total_sales
        total_quantity_sold=$total_quantity_sold
        format="%.2f"}
        {$currency.iso_code|escape:'html':'UTF-8'}
      </span>
      {else}
      <span>N/A</span>
      {/if}
    </div>
    <div class="stat-item">
      <label>Sales Period:</label>
      <span>{$month|escape:'html':'UTF-8'}/{$year|escape:'html':'UTF-8'}</span>
    </div>
  </div>
  <div class="hero-product-link">
    <a href="{$link->getProductLink($hero_product->id, $hero_product->link_rewrite)|escape:'url':'UTF-8'}"
      class="product-link-btn" target="_blank">
      View Product Page
    </a>
  </div>
  <div class="hero-actions">
    <a href="{$back_link|escape:'url':'UTF-8'}" class="hero-back-btn">
      <i class="icon-arrow-left"></i> Back to Configuration
    </a>
  </div>
</div>