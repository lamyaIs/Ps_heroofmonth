{*
/**
 * Copyright Mr-dev
 *
 * NOTICE OF LICENSE
 *
 * This file is proprietary and each license is valid for use on one website only.
 * To use this file on additional websites or projects, additional licenses must be purchased.
 * Redistribution, reselling, leasing, licensing, sub-licensing, or offering this resource to any third party is strictly prohibited.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to keep compatibility with future PrestaShop updates.
 *
 * @author     Mr-dev
 * @copyright  Mr-dev
 * @license    License valid for one website (or project) per purchase
 */
*}
{assign var="color1" value=Configuration::get('HERO_COLOR1')|escape:'html':'UTF-8'}
{assign var="color2" value=Configuration::get('HERO_COLOR2')|escape:'html':'UTF-8'}
{assign var="color3" value=Configuration::get('HERO_COLOR3')|escape:'html':'UTF-8'}
{assign var="layout_type" value=Configuration::get('HERO_LAYOUT_TYPE')|escape:'html':'UTF-8'}
<div class="hero-of-the-month-wrapper {if $layout_type == 'full'}full-width{else}boxed{/if}" style="background-color: {$color1|escape:'html':'UTF-8'};">
    <div class="hero-of-the-month-container">
        
        <div class="hero-content-left" style="background-color: {$color2|escape:'html':'UTF-8'};">
            <p class="hero-quote" style="color: {$color3|escape:'html':'UTF-8'};">
                « {$custom_description|escape:'html':'UTF-8'} »
            </p>
            <p class="hero-author" style="color: {$color3|escape:'html':'UTF-8'}; text-align: right;">- {$hero_product->manufacturer_name|escape:'html':'UTF-8'}</p>
            <a href="{$link->getProductLink($hero_product)|escape:'url':'UTF-8'}" class="btn btn-primary hero-button">View this product</a>
        </div>
        
        {if $hero_product->id_image}
            <div class="hero-content-right">
                <img src="{$hero_custom_image|escape:'url':'UTF-8'}" alt="{$hero_product->name|escape:'html':'UTF-8'}" class="img-fluid" height="250" width="250">
            </div>
        {/if}
    </div>
</div>
