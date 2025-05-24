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
        return parent::install() && 
               $this->registerHook('header') && 
               $this->registerHook('displayBackOfficeHeader');
    }

    public function uninstall()
    {
        return parent::uninstall();
    }

    public function hookDisplayBackOfficeHeader()
    {
        if (Tools::getValue('configure') == $this->name) {
            $this->context->controller->addJS($this->_path.'views/js/back.js');
        }
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
            'ajax_url' => $this->context->link->getAdminLink('AdminModules') . '&configure=' . $this->name . '&ajax=1'
        ]);

        // Renderizar el formulario de comerciales y el formulario de asignación de clientes
        $output = $this->displayFilterButtons();
        $output .= $this->renderList();
        $output .= $this->renderAssignForm($commercials, $clients);
        $output .= $this->renderClientCommercialList();

        return $output;
    }

    private function displayFilterButtons()
    {
        return '<div class="panel">
            <div class="panel-heading">Filtrar empleados</div>
            <div class="form-wrapper">
                <div class="form-group">
                    <button type="button" class="btn btn-default" id="showAllEmployees">Ver todos los empleados</button>
                    <button type="button" class="btn btn-primary" id="showOnlyCommercials">Ver solo comerciales</button>
                </div>
            </div>
        </div>';
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

        // Generar y devolver el listado
        return $helper->generateList($commercials, $fields_list);
    }

    public function ajaxProcessGetEmployees()
    {
        $onlyCommercials = (bool)Tools::getValue('only_commercials', true);
        $employees = $this->getAllCommercials($onlyCommercials);
        die(json_encode($employees));
    }

    private function getAllCommercials($onlyCommercials = true)
    {
        $id_lang = (int)$this->context->language->id;

        $sql = '
            SELECT 
                e.id_employee AS id, 
                e.firstname, 
                e.lastname, 
                pl.name AS profile
            FROM ' . _DB_PREFIX_ . 'employee e
            LEFT JOIN ' . _DB_PREFIX_ . 'profile_lang pl ON e.id_profile = pl.id_profile AND pl.id_lang = ' . $id_lang;

        if ($onlyCommercials) {
            $sql .= ' WHERE pl.name = "Comercial"';
        }

        $sql .= ' ORDER BY e.lastname ASC, e.firstname ASC';
        
        return Db::getInstance()->executeS($sql);
    }

    // ... [Resto de métodos sin cambios]
}