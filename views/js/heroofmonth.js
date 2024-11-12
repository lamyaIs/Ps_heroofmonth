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
$(document).ready(function (){$('#product_search').on('input',function (){handleProductSearch($(this),$('#dropdownResults'));});$('#product_search_edit').on('input',function (){handleProductSearch($(this),$('#dropdownResultsEdit'));});$('form').on('submit',function (e){if ($('#HERO_OF_THE_MONTH').val() === ''){e.preventDefault();alert('Please select a product before submitting the form.');}});});function handleProductSearch(inputElement,dropdownResultsElement){let searchTerm = inputElement.val();if (searchTerm.length >= 1){$.ajax({url:ajaxUrl,dataType:'json',data:{search:searchTerm},success:function (data){dropdownResultsElement.empty();$.each(data,function (index,item){dropdownResultsElement.append('<a href="#" data-id="' + item.id_product + '">' + item.name + ' (Ref:' + item.reference + ')</a>');});dropdownResultsElement.find('a').on('click',function (e){e.preventDefault();$('#HERO_OF_THE_MONTH').val($(this).data('id'));inputElement.val($(this).text());dropdownResultsElement.empty();});},error:function (){console.log('Error retrieving results.');}});}else{dropdownResultsElement.empty();}}