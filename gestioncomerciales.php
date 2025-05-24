<?php
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

if (!defined('_PS_VERSION_')) {
    exit;
}

class Gestioncomerciales extends Module
{
    protected $fields_value = []; // Declaración de la propiedad para evitar errores en PHP 8.2+

    public function __construct()
    {
        $this->name = 'gestioncomerciales';
        $this->tab = 'administration';
        $this->version = '1.0.0';
        $this->author = 'Salvador Vidal Villahoz';
        $this->need_instance = 1;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Gestión comerciales');
        $this->description = $this->l('Gestiona la creación de comerciales y la relación con los clientes.');
        $this->ps_versions_compliancy = array('min' => '1.7', 'max' => _PS_VERSION_);
    }

    public function install()
    {
        include(dirname(__FILE__).'/sql/install.php');
        return parent::install() && $this->registerHook('header');
    }

    public function uninstall()
    {
        include(dirname(__FILE__).'/sql/uninstall.php');
        return parent::uninstall();
    }

    public function getContent()
    {
        $token = Tools::getAdminTokenLite('AdminModules');

        // Procesar la asignación de clientes a comerciales
        if (Tools::isSubmit('submitAssignClients')) {
            $this->processAssignClients();
        }

        // Procesar adición o edición de un comercial
        if (Tools::isSubmit('submitAddCommercial')) {
            $this->processAddOrEditCommercial();
        }

        // Procesar eliminación de un comercial
        if (Tools::isSubmit('delete' . $this->name)) {
            $id_commercial = (int)Tools::getValue('id');
            if ($id_commercial) {
                $this->processDeleteCommercial($id_commercial);
                Tools::redirectAdmin(AdminController::$currentIndex . '&configure=' . $this->name . '&token=' . $token);
            }
        }

        // Verificar si es una acción de edición de comercial
        $id_commercial = (int)Tools::getValue('id');
        if ($id_commercial && !Tools::isSubmit('delete' . $this->name)) {
            $commercial = $this->getCommercialById($id_commercial);
            
            // Asegurarse de que sea un array
            if (!is_array($commercial)) {
                $commercial = [];
            }
            
            $this->fields_value = array_merge([
                'id' => $id_commercial,
                'nombre_apellidos' => '',
                'telefono' => '',
                'correo' => '',
                'observaciones' => ''
            ], $commercial);
        } else {
            // Limpiar campos para nuevo comercial
            $this->fields_value = [
                'id' => '',
                'nombre_apellidos' => '',
                'telefono' => '',
                'correo' => '',
                'observaciones' => ''
            ];
        }

        // Obtener datos de comerciales y clientes para el formulario de asignación
        $commercials = $this->getAllCommercials();
        $clients = $this->getAllClients();

        // Asignar variables necesarias para la plantilla Smarty
        $this->context->smarty->assign([
            'commercials' => $commercials,
            'module_dir' => $this->_path,
            'currentIndex' => AdminController::$currentIndex . '&configure=' . $this->name,
            'token' => $token,
        ]);

        // Renderizar el formulario de comerciales y el formulario de asignación de clientes
        $output = $this->renderList();
        $output .= $this->renderAssignForm($commercials, $clients);
        
        // Agregar la tabla de clientes y comerciales asignados
        $output .= $this->renderClientCommercialList();

        return $output;
    }

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

    private function renderAssignForm($commercials, $clients)
    {
        // Preparar opciones de comerciales para el select
        $commercial_options = [];
        foreach ($commercials as $commercial) {
            $commercial_options[] = [
                'id_option' => $commercial['id'],
                'name' => $commercial['firstname'] . ' ' . $commercial['lastname']
            ];
        }

        // Configurar los campos del formulario
        $fields_form = [
            'form' => [
                'legend' => [
                    'title' => $this->l('Asignar Clientes a Comercial'),
                    'icon' => 'icon-user'
                ],
                'input' => [
                    [
                        'type' => 'select',
                        'label' => $this->l('Selecciona Comercial'),
                        'name' => 'id_comercial',
                        'required' => true,
                        'options' => [
                            'query' => $commercial_options,
                            'id' => 'id_option',
                            'name' => 'name'
                        ]
                    ]
                ],
                'submit' => [
                    'title' => $this->l('Asignar Clientes'),
                    'class' => 'btn btn-default pull-right'
                ]
            ]
        ];

        $helper = new HelperForm();
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->identifier = $this->identifier;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex . '&configure=' . $this->name;
        $helper->title = $this->displayName;
        $helper->submit_action = 'submitAssignClients';

        // Valores predeterminados
        $helper->fields_value = [
            'id_comercial' => '',
        ];

        // Generar el formulario y añadir la tabla de clientes
        $output = $helper->generateForm([$fields_form]);
        
        // Añadir la tabla de clientes con checkboxes
        $output .= '<div class="panel">
            <div class="panel-heading">' . $this->l('Seleccionar Clientes') . '</div>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th><input type="checkbox" id="checkAll" /></th>
                            <th>' . $this->l('ID') . '</th>
                            <th>' . $this->l('Nombre') . '</th>
                            <th>' . $this->l('Apellido') . '</th>
                            <th>' . $this->l('Email') . '</th>
                        </tr>
                    </thead>
                    <tbody>';

        foreach ($clients as $client) {
            $output .= '<tr>
                <td><input type="checkbox" name="id_clients[]" value="' . $client['id_customer'] . '" /></td>
                <td>' . $client['id_customer'] . '</td>
                <td>' . $client['firstname'] . '</td>
                <td>' . $client['lastname'] . '</td>
                <td>' . $client['email'] . '</td>
            </tr>';
        }

        $output .= '</tbody></table></div></div>';

        return $output;
    }

    private function renderClientCommercialList()
    {
        $clients = $this->getClientCommercialList();

        // Concatenar el nombre completo del comercial antes de pasarlo al HelperList
        foreach ($clients as &$client) {
            $client['commercial_name'] = (!empty($client['commercial_firstname']) && !empty($client['commercial_lastname']))
                ? trim($client['commercial_firstname'] . ' ' . $client['commercial_lastname'])
                : $this->l('Sin asignar');
        }

        // Configurar columnas para HelperList
        $fields_list = [
            'id_customer' => [
                'title' => $this->l('ID Cliente'),
                'align' => 'center',
                'class' => 'fixed-width-xs',
            ],
            'firstname' => [
                'title' => $this->l('Nombre'),
                'type' => 'text',
            ],
            'lastname' => [
                'title' => $this->l('Apellido'),
                'type' => 'text',
            ],
            'commercial_name' => [
                'title' => $this->l('Comercial Asignado'),
                'type' => 'text',
                'align' => 'center',
            ],
        ];

        $helper = new HelperList();
        $helper->shopLinkType = '';
        $helper->simple_header = true;
        $helper->identifier = 'id_customer';
        $helper->actions = [];
        $helper->show_toolbar = false;
        $helper->title = $this->l('Listado de Clientes y Comerciales Asignados');
        $helper->table = $this->name . '_client_commercial_list';
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex . '&configure=' . $this->name;

        return $helper->generateList($clients, $fields_list);
    }

    private function processAssignClients()
    {
        $id_comercial = (int)Tools::getValue('id_comercial');
        $id_clients = Tools::getValue('id_clients');

        if ($id_comercial && !empty($id_clients)) {
            foreach ($id_clients as $id_cliente) {
                // Eliminar cualquier asignación previa de este cliente
                Db::getInstance()->delete('comerciales_clientes', 'id_cliente = ' . (int)$id_cliente);

                // Insertar la nueva asignación con el id_employee
                Db::getInstance()->insert('comerciales_clientes', [
                    'id_comercial' => (int)$id_comercial,
                    'id_cliente' => (int)$id_cliente
                ]);
            }

            $this->context->controller->confirmations[] = $this->l('Clientes asignados correctamente al comercial.');
        } else {
            $this->context->controller->errors[] = $this->l('Selecciona un comercial y al menos un cliente.');
        }
    }

    private function getAllCommercials()
    {
        $id_lang = (int)$this->context->language->id;

        $sql = '
            SELECT 
                e.id_employee AS id, 
                e.firstname, 
                e.lastname, 
                pl.name AS profile
            FROM ' . _DB_PREFIX_ . 'employee e
            LEFT JOIN ' . _DB_PREFIX_ . 'profile_lang pl ON e.id_profile = pl.id_profile AND pl.id_lang = ' . $id_lang . '
            ORDER BY e.lastname ASC, e.firstname ASC
        ';
        return Db::getInstance()->executeS($sql);
    }

    private function getAllClients()
    {
        $sql = 'SELECT `id_customer`, `firstname`, `lastname`, `email` FROM `' . _DB_PREFIX_ . 'customer`';
        return Db::getInstance()->executeS($sql);
    }

    private function getCommercialById($id_commercial)
    {
        $sql = 'SELECT * FROM `' . _DB_PREFIX_ . 'comerciales` WHERE `id` = ' . (int)$id_commercial;
        return Db::getInstance()->getRow($sql);
    }

    private function processAddOrEditCommercial()
    {
        $id_commercial = (int)Tools::getValue('id');
        $data = [
            'nombre_apellidos' => pSQL(Tools::getValue('nombre_apellidos')),
            'telefono' => pSQL(Tools::getValue('telefono')),
            'correo' => pSQL(Tools::getValue('correo')),
            'observaciones' => pSQL(Tools::getValue('observaciones'))
        ];

        if ($id_commercial) {
            Db::getInstance()->update('comerciales', $data, 'id = ' . $id_commercial);
        } else {
            Db::getInstance()->insert('comerciales', $data);
        }

        Tools::redirectAdmin(AdminController::$currentIndex . '&configure=' . $this->name . '&token=' . Tools::getAdminTokenLite('AdminModules'));
    }

    private function processDeleteCommercial($id_commercial)
    {
        Db::getInstance()->delete('comerciales', 'id = ' . (int)$id_commercial);
    }

    private function getClientCommercialList()
    {
        $sql = '
            SELECT 
                c.id_customer, 
                c.firstname, 
                c.lastname, 
                e.firstname AS commercial_firstname, 
                e.lastname AS commercial_lastname
            FROM ' . _DB_PREFIX_ . 'customer c
            LEFT JOIN ' . _DB_PREFIX_ . 'comerciales_clientes cc ON c.id_customer = cc.id_cliente
            LEFT JOIN ' . _DB_PREFIX_ . 'employee e ON cc.id_comercial = e.id_employee
            ORDER BY c.lastname ASC, c.firstname ASC
        ';
        return Db::getInstance()->executeS($sql);
    }
}