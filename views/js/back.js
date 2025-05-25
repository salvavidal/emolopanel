/**
* 2007-2024 PrestaShop
*/

document.addEventListener('DOMContentLoaded', function() {
    // Código para los botones de filtrado
    const showAllBtn = document.getElementById('showAllEmployees');
    const showCommercialsBtn = document.getElementById('showOnlyCommercials');
    const listContainer = document.getElementById('gestioncomerciales_commercial_list');
    
    if (showAllBtn && showCommercialsBtn && listContainer) {
        showAllBtn.addEventListener('click', () => updateEmployeeList(false));
        showCommercialsBtn.addEventListener('click', () => updateEmployeeList(true));
    }

    // Código para el checkbox "Seleccionar todos"
    const checkAllBox = document.getElementById('checkAll');
    if (checkAllBox) {
        checkAllBox.addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('input[name="id_clients[]"]');
            checkboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
        });
    }

    function updateEmployeeList(onlyCommercials) {
        if (!listContainer) return;
        
        const baseUrl = listContainer.dataset.ajaxUrl;
        
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
    if (listContainer) {
        updateEmployeeList(true);
    }
});