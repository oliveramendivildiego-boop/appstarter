<?php

/**
 * Helper de tablas - Migrado de CI2 para CI4
 * Usa lang(), base_url(), site_url(), anchor() de CI4
 */
helper(['url', 'text', 'form']);

if (!function_exists('build_select')) {
    /**
     * Genera un select/dropdown desde un array de opciones [value=>label]
     */
    function build_select(string $name, array $options, $selected = '', string $extra = ''): string
    {
        $opts = ['' => '-- Seleccione --'];
        foreach ($options as $k => $v) {
            $opts[$k] = $v;
        }
        return form_dropdown($name, $opts, $selected, $extra ?: 'class="form-control"');
    }
}

if (!function_exists('get_people_manage_table')) {
    function get_people_manage_table(array $people, object $controller): string
    {
        $table = '<div class="table-responsive"><table class="table table-bordered" id="sortable_table">';
        $headers = [
            '<input type="checkbox" id="select_all" name="select_all" />',
            lang('Common.common_last_name_fa'),
            lang('Common.common_first_name'),
            lang('Common.common_email'),
            lang('Common.common_phone_number'),
            '&nbsp',
        ];
        $table .= '<thead><tr class="well">';
        foreach ($headers as $header) {
            $table .= "<th>$header</th>";
        }
        $table .= '</tr></thead><tbody>';
        $table .= get_people_manage_table_data_rows($people, $controller);
        $table .= '</tbody></table></div>';
        return $table;
    }
}

if (!function_exists('get_people_manage_table_data_rows')) {
    function get_people_manage_table_data_rows(array $people, object $controller): string
    {
        $table_data_rows = '';
        $controller_name = strtolower($controller->getControllerName());

        foreach ($people as $person) {
            $table_data_rows .= get_person_data_row($person, $controller);
        }

        if (empty($people)) {
            $table_data_rows .= "<tr><td colspan='6'><div class='warning_message' style='padding:7px;'>" . lang('Common.common_no_persons_to_display') . "</div></td></tr>";
        }
        return $table_data_rows;
    }
}

if (!function_exists('get_doctors_manage_table')) {
    function get_doctors_manage_table(array $doctors, object $controller): string
    {
        $table = '<div class="table-responsive"><table class="table table-bordered" id="sortable_table">';
        $headers = [
            '<input type="checkbox" id="select_all" name="select_all" />',
            lang('Doctors.doctors_name'),
            lang('Doctors.doctors_phone'),
            lang('Doctors.doctors_speciality'),
            lang('Doctors.doctors_address'),
            '&nbsp',
        ];
        $table .= '<thead><tr class="well">';
        foreach ($headers as $header) {
            $table .= "<th>$header</th>";
        }
        $table .= '</tr></thead><tbody>';
        $table .= get_doctors_manage_table_data_rows($doctors, $controller);
        $table .= '</tbody></table></div>';
        return $table;
    }
}

if (!function_exists('get_doctors_manage_table_data_rows')) {
    function get_doctors_manage_table_data_rows(array $doctors, object $controller): string
    {
        $table_data_rows = '';
        $controller_name = 'doctors';
        foreach ($doctors as $doc) {
            $table_data_rows .= get_doctor_data_row($doc, $controller);
        }
        if (empty($doctors)) {
            $table_data_rows .= "<tr><td colspan='6'><div class='warning_message' style='padding:7px;'>" . lang('Doctors.doctors_no_doctors_to_display') . "</div></td></tr>";
        }
        return $table_data_rows;
    }
}

if (!function_exists('get_doctor_data_row')) {
    function get_doctor_data_row(object $doctor, object $controller): string
    {
        $table_data_row = '<tr>';
        $table_data_row .= "<td width='5%'><input type='checkbox' id='doctor_{$doctor->doctor_id}' name='doctor_{$doctor->doctor_id}' value='{$doctor->doctor_id}'/></td>";
        $table_data_row .= '<td width="23%">' . (function_exists('character_limiter') ? character_limiter($doctor->name ?? '', 20) : substr($doctor->name ?? '', 0, 20)) . '</td>';
        $table_data_row .= '<td width="13%">' . ($doctor->phone_number ?? '') . '</td>';
        $table_data_row .= '<td width="23%">' . (function_exists('character_limiter') ? character_limiter($doctor->speciality ?? '', 20) : substr($doctor->speciality ?? '', 0, 20)) . '</td>';
        $table_data_row .= '<td width="23%">' . (function_exists('character_limiter') ? character_limiter($doctor->address ?? '', 20) : substr($doctor->address ?? '', 0, 20)) . '</td>';
        $editIcon = '<i class="fa-solid fa-pen" aria-hidden="true"></i>';
        $actions = anchor('doctors/view/' . $doctor->doctor_id . '/', $editIcon, ['class' => 'update', 'title' => lang('Doctors.doctors_update')]);
        $phone = trim($doctor->phone_number ?? '');
        $phoneClean = preg_replace('/\D/', '', $phone);
        if ($phoneClean !== '') {
            $waNum = (strlen($phoneClean) <= 9) ? '591' . ltrim($phoneClean, '0') : $phoneClean;
            $waUrl = 'https://wa.me/' . $waNum;
            $actions .= ' <a href="' . esc($waUrl) . '" target="_blank" rel="noopener" class="text-success" title="WhatsApp"><i class="fa-brands fa-whatsapp"></i></a>';
        } else {
            $actions .= ' <span class="text-secondary" title="Sin teléfono"><i class="fa-brands fa-whatsapp" style="opacity:0.4"></i></span>';
        }
        $table_data_row .= '<td width="13%" class="text-center text-nowrap">' . $actions . '</td>';
        $table_data_row .= '</tr>';
        return $table_data_row;
    }
}

if (!function_exists('get_person_data_row')) {
    function get_person_data_row(object $person, object $controller): string
    {
        $controller_name = strtolower($controller->getControllerName());
        $isCustomers = ($controller_name === 'customers');
        $isEmployees = ($controller_name === 'employees');
        $actionsWidth = $isCustomers ? '12%' : ($isEmployees ? '10%' : '5%');
        
        // Para empleados, aplicar opacidad si está inactivo (active=0)
        $rowStyle = '';
        if ($isEmployees && isset($person->active) && $person->active == 0) {
            $rowStyle = ' style="opacity: 0.6;"';
        }
        
        $table_data_row = '<tr' . $rowStyle . '>';
        $table_data_row .= "<td width='5%'><input type='checkbox' id='person_{$person->person_id}' name='person_{$person->person_id}' value='{$person->person_id}'/></td>";
        $table_data_row .= '<td width="' . ($isCustomers ? '18%' : ($isEmployees ? '20%' : '20%')) . '">' . (function_exists('character_limiter') ? character_limiter($person->last_name ?? '', 13) : substr($person->last_name ?? '', 0, 13)) . '</td>';
        $table_data_row .= '<td width="' . ($isCustomers ? '18%' : ($isEmployees ? '20%' : '20%')) . '">' . (function_exists('character_limiter') ? character_limiter($person->first_name ?? '', 13) : substr($person->first_name ?? '', 0, 13)) . '</td>';
        $table_data_row .= '<td width="' . ($isCustomers ? '27%' : ($isEmployees ? '25%' : '30%')) . '">' . ($person->email ?? '') . '</td>';
        $table_data_row .= '<td width="' . ($isCustomers ? '20%' : ($isEmployees ? '15%' : '20%')) . '">' . (function_exists('character_limiter') ? character_limiter($person->phone_number ?? '', 13) : substr($person->phone_number ?? '', 0, 13)) . '</td>';
        
        $title = lang(\ucfirst($controller_name) . '.' . $controller_name . '_update');
        $editIcon = '<i class="fa-solid fa-pen" aria-hidden="true"></i>';
        $actions = anchor($controller_name . '/view/' . $person->person_id . '/', $editIcon, ['class' => 'update', 'title' => $title]);
        
        if ($isEmployees) {
            // Botón de toggle estado para empleados - más grande
            // active=1: puede loguearse, active=0: deshabilitado
            $isActive = (($person->active ?? 1) == 1);
            $statusIcon = $isActive ? 'fa-toggle-on text-success' : 'fa-toggle-off text-danger';
            $statusTitle = $isActive ? 'Desactivar empleado' : 'Activar empleado';
            $actions .= ' <button type="button" class="btn btn-lg btn-toggle-employee-status" data-person-id="' . $person->person_id . '" data-enabled="' . ($isActive ? '1' : '0') . '" title="' . $statusTitle . '" style="padding: 0.5rem 0.75rem;"><i class="fa-solid ' . $statusIcon . '" style="font-size: 1.5rem;"></i></button>';
        } else if ($isCustomers) {
            $phone = trim($person->phone_number ?? '');
            $phoneClean = preg_replace('/\D/', '', $phone);
            if ($phoneClean !== '') {
                $waNum = (strlen($phoneClean) <= 9) ? '591' . ltrim($phoneClean, '0') : $phoneClean;
                $waUrl = 'https://wa.me/' . $waNum;
                $actions .= ' <a href="' . esc($waUrl) . '" target="_blank" rel="noopener" class="text-success" title="WhatsApp"><i class="fa-brands fa-whatsapp"></i></a>';
            } else {
                $actions .= ' <span class="text-secondary" title="Sin teléfono"><i class="fa-brands fa-whatsapp" style="opacity:0.4"></i></span>';
            }
            $actions .= ' <a href="' . site_url('expediente/view/' . $person->person_id) . '" class="text-info" title="Ver historial de resultados"><i class="fa-solid fa-clock-rotate-left"></i></a>';
        }
        $table_data_row .= '<td width="' . $actionsWidth . '" class="text-center text-nowrap">' . $actions . '</td>';
        $table_data_row .= '</tr>';
        return $table_data_row;
    }
}
