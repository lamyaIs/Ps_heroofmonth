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
{if $flag_image_url}
    <img src="{$flag_image_url|escape:'html':'UTF-8'}" width="100" height="100" alt="Flag image" />
    <br>
    <a href="{$delete_flag_image_url|escape:'html':'UTF-8'}" class="btn btn-danger">{$delete_flag_image_text|escape:'html':'UTF-8'}</a>
{/if}

