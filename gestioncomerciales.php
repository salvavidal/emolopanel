<?php
/**
* 2007-2024 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
*/

if (!defined('_PS_VERSION_')) {
    exit;
}

class Gestioncomerciales extends Module
{
    protected $fields_value = [];

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
        return parent::install() && 
               $this->registerHook('header') &&
               $this->registerHook('backOfficeHeader');
    }

    public function uninstall()
    {
        include(dirname(__FILE__).'/sql/uninstall.php');
        return parent::uninstall();
    }

    public function hookBackOfficeHeader()
    {
        if (Tools::getValue('configure') == $this->name) {
            $this->context->controller->addJS($this->_path.'views/js/back.js');
        }
    }

    public function getContent()
    {
        $token = Tools::getAdminTokenLite('AdminModules');

        if (Tools::isSubmit('submitAssignClients')) {
            $this->processAssignClients();
        }

        // Obtener datos de comerciales y clientes
        $commercials = $this->getAllCommercials();
        $clients = $this->getAllClients();

        // Asignar variables para la plantilla
        $this->context->smarty->assign([
            'commercials' => $commercials,
            'module_dir' => $this->_path,
            'currentIndex' => AdminController::$currentIndex . '&configure=' . $this->name,
            'token' => $token,
        ]);

        // Renderizar el listado de comerciales y el formulario unificado de asignación
        $output = $this->renderList();
        $output .= '<div class="panel">
            <div class="panel-heading">
                ' . $this->l('Asignación de Clientes a Comercial') . '
            </div>
            <div class="form-wrapper">
                <form method="post" class="form-horizontal">
                    <div class="form-group">
                        <label class="control-label col-lg-3">' . $this->l('Seleccionar Comercial') . '</label>
                        <div class="col-lg-9">
                            <select name="id_comercial" class="form-control" required>
                                <option value="">' . $this->l('Seleccione un comercial') . '</option>';
                                foreach ($commercials as $commercial) {
                                    $output .= '<option value="' . $commercial['id'] . '">' . 
                                        $commercial['firstname'] . ' ' . $commercial['lastname'] . 
                                    '</option>';
                                }
        $output .= '</select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="control-label col-lg-3">' . $this->l('Clientes Disponibles') . '</label>
                        <div class="col-lg-9">
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
        $output .= '</tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="panel-footer">
                        <button type="submit" name="submitAssignClients" class="btn btn-default pull-right">
                            <i class="process-icon-save"></i> ' . $this->l('Guardar') . '
                        </button>
                    </div>
                </form>
            </div>
        </div>';

        $output .= $this->renderClientCommercialList();

        return $output;
    }

    private function renderList()
    {
        $commercials = $this->getAllCommercials();

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

        $list = '<div class="panel">
            <div class="panel-heading">
                ' . $this->l('Listado de Comerciales') . '
                <div class="btn-group pull-right">
                    <button type="button" id="showAllEmployees" class="btn btn-default">
                        <i class="icon-list"></i> ' . $this->l('Ver todos los empleados') . '
                    </button>
                    <button type="button" id="showOnlyCommercials" class="btn btn-primary">
                        <i class="icon-user"></i> ' . $this->l('Ver solo comerciales') . '
                    </button>
                </div>
            </div>
            <div id="' . $helper->table . '" data-ajax-url="' . $this->context->link->getAdminLink('AdminModules') . '&configure=' . $this->name . '">';
        $list .= $helper->generateList($commercials, $fields_list);
        $list .= '</div></div>';

        return $list;
    }

    private function renderClientCommercialList()
    {
        $clients = $this->getClientCommercialList();

        foreach ($clients as &$client) {
            $client['commercial_name'] = (!empty($client['commercial_firstname']) && !empty($client['commercial_lastname']))
                ? trim($client['commercial_firstname'] . ' ' . $client['commercial_lastname'])
                : $this->l('Sin asignar');
        }

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
                Db::getInstance()->delete('comerciales_clientes', 'id_cliente = ' . (int)$id_cliente);
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