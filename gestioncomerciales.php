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

    // ... [resto de métodos sin cambios]
}