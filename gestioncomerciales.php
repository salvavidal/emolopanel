<?php
// ... [Código anterior sin cambios hasta el método renderList]

public function renderList()
{
    // Obtener la lista de empleados (comerciales) desde la función getAllCommercials
    $commercials = $this->getAllCommercials();

    // Definir las columnas para el listado
    $fields_list = [
        'id' => [
            'title' => $this->l('ID'),
            'align' => 'center',
            'class' => 'fixed-width-xs'
        ],
        'firstname' => [
            'title' => $this->l('Nombre'),
            'type' => 'text'
        ],
        'lastname' => [
            'title' => $this->l('Apellido'),
            'type' => 'text'
        ],
        'profile' => [
            'title' => $this->l('Perfil'),
            'type' => 'text',
            'align' => 'center'
        ]
    ];

    // Configurar HelperList para mostrar la tabla de comerciales
    $helper = new HelperList();
    $helper->shopLinkType = '';
    $helper->simple_header = true;
    $helper->identifier = 'id';
    $helper->actions = ['edit', 'delete'];
    $helper->show_toolbar = true;
    $helper->title = $this->l('Listado de Comerciales (Empleados)');
    $helper->table = $this->name . '_commercial_list';
    $helper->token = Tools::getAdminTokenLite('AdminModules');
    $helper->currentIndex = AdminController::$currentIndex . '&configure=' . $this->name;

    // Añadir la URL de AJAX como atributo data al contenedor
    $list = '<div id="' . $helper->table . '" data-ajax-url="' . $this->context->link->getAdminLink('AdminModules') . '&configure=' . $this->name . '">';
    $list .= $helper->generateList($commercials, $fields_list);
    $list .= '</div>';

    return $list;
}

// ... [Resto del código sin cambios]