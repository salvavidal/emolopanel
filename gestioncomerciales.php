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