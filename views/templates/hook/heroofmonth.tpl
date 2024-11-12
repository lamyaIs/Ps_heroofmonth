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
<div class="hero-of-the-month-page">
    {if $hero_product}
        <div class="current-hero-product">
            <h1>{$hero_product->name|escape:'html':'UTF-8'}</h1>
            {if $hero_product->id_image}
                <img src="{$link->getImageLink($hero_product->link_rewrite|escape:'url':'UTF-8', $hero_product->id_image|escape:'url':'UTF-8', 'large_default')}" alt="{$hero_product->name|escape:'html':'UTF-8'}" class="img-fluid">
            {/if}
            <p>{$hero_product->description|escape:'html':'UTF-8'}</p>
            <a href="{$link->getProductLink($hero_product)|escape:'url':'UTF-8'}" class="btn btn-primary">Voir ce produit</a>
        </div>
    {else}
        <p>Aucun produit mis en avant ce mois-ci.</p>
    {/if}
    <h2>Produits des mois précédents</h2>
    <div class="previous-heroes">
        {foreach from=$previous_heroes item=hero}
            <div class="previous-hero">
                <h3>{$hero.product->name|escape:'html':'UTF-8'}</h3>
                {if $hero.product->id_image}
                    <img src="{$link->getImageLink($hero.product->link_rewrite|escape:'url':'UTF-8', $hero.product->id_image|escape:'url':'UTF-8', 'small_default')}" alt="{$hero.product->name|escape:'html':'UTF-8'}" class="img-fluid">
                {/if}
                <a href="{$link->getProductLink($hero.product)|escape:'url':'UTF-8'}" class="btn btn-secondary">Voir le produit</a>
            </div>
        {/foreach}
    </div>
</div>
