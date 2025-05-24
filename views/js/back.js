/**
* 2007-2024 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2024 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

document.addEventListener('DOMContentLoaded', function() {
    const showAllBtn = document.getElementById('showAllEmployees');
    const showCommercialsBtn = document.getElementById('showOnlyCommercials');
    
    if (showAllBtn && showCommercialsBtn) {
        showAllBtn.addEventListener('click', () => updateEmployeeList(false));
        showCommercialsBtn.addEventListener('click', () => updateEmployeeList(true));
    }

    function updateEmployeeList(onlyCommercials) {
        // Obtener la URL base del data attribute
        const baseUrl = document.querySelector('#gestioncomerciales_commercial_list').dataset.ajaxUrl;
        
        if (!baseUrl) {
            console.error('URL de AJAX no encontrada');
            return;
        }

        const url = new URL(baseUrl);
        url.searchParams.append('ajax', '1');
        url.searchParams.append('action', 'getEmployees');
        url.searchParams.append('only_commercials', onlyCommercials ? '1' : '0');

        fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            }
        })
        .then(response => response.json())
        .then(employees => {
            const tableBody = document.querySelector('#gestioncomerciales_commercial_list tbody');
            if (!tableBody) return;

            tableBody.innerHTML = '';
            employees.forEach(employee => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td class="fixed-width-xs center">${employee.id}</td>
                    <td>${employee.firstname}</td>
                    <td>${employee.lastname}</td>
                    <td class="center">${employee.profile}</td>
                    <td class="text-right">
                        <div class="btn-group-action">
                            <div class="btn-group pull-right">
                                <a href="#" class="edit btn btn-default" title="Editar">
                                    <i class="icon-pencil"></i> Editar
                                </a>
                                <button class="btn btn-default dropdown-toggle" data-toggle="dropdown">
                                    <i class="icon-caret-down"></i>&nbsp;
                                </button>
                                <ul class="dropdown-menu">
                                    <li>
                                        <a href="#" class="delete" title="Eliminar">
                                            <i class="icon-trash"></i> Eliminar
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </td>
                `;
                tableBody.appendChild(row);
            });
        })
        .catch(error => console.error('Error:', error));
    }

    // Cargar solo comerciales por defecto al iniciar
    updateEmployeeList(true);
});