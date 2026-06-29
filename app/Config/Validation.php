<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;
use CodeIgniter\Validation\StrictRules\CreditCardRules;
use CodeIgniter\Validation\StrictRules\FileRules;
use CodeIgniter\Validation\StrictRules\FormatRules;
use CodeIgniter\Validation\StrictRules\Rules;

class Validation extends BaseConfig
{
    public array $ruleSets = [
        Rules::class,
        FormatRules::class,
        FileRules::class,
        CreditCardRules::class,
    ];

    public array $templates = [
        'list'   => 'CodeIgniter\Validation\Views\list',
        'single' => 'CodeIgniter\Validation\Views\single',
    ];

    /**
     * Reglas para el formulario de configuración
     */
    public array $config = [
        'company' => [
            'rules'  => 'required|min_length[2]|max_length[255]',
            'errors' => [
                'required'   => 'El nombre de la empresa es obligatorio.',
                'min_length' => 'La empresa debe tener al menos 2 caracteres.',
            ],
        ],
        'theme_color' => [
            'rules'  => 'permit_empty|regex_match[/^#[a-fA-F0-9]{3,6}$/]',
            'errors' => [
                'regex_match' => 'El color debe ser un valor hex válido (ej: #FF7218).',
            ],
        ],
    ];

    /**
     * Reglas para formulario de clientes/pacientes
     */
    public array $customers = [
        'first_name' => [
            'rules'  => 'required|min_length[2]|max_length[255]',
            'errors' => [
                'required'   => 'El nombre es obligatorio.',
                'min_length' => 'El nombre debe tener al menos 2 caracteres.',
            ],
        ],
        'last_name_fa' => [
            'rules'  => 'required|min_length[2]|max_length[255]',
            'errors' => [
                'required'   => 'El apellido es obligatorio.',
                'min_length' => 'El apellido debe tener al menos 2 caracteres.',
            ],
        ],
        'email' => [
            'rules'  => 'permit_empty|valid_email',
            'errors' => ['valid_email' => 'Ingrese un correo válido.'],
        ],
        'birthday' => [
            'rules'  => 'required|valid_date',
            'errors' => [
                'required'   => 'La fecha de nacimiento es obligatoria.',
                'valid_date' => 'Ingrese una fecha válida.',
            ],
        ],
        'gender' => [
            'rules'  => 'required|in_list[1,2]',
            'errors' => ['required' => 'Seleccione el género.'],
        ],
        'address_1' => [
            'rules'  => 'permit_empty|max_length[255]',
            'errors' => ['max_length' => 'La dirección no puede superar 255 caracteres.'],
        ],
    ];

    /**
     * Reglas para formulario de doctores (todos los campos menos comentarios)
     */
    public array $doctors = [
        'name' => [
            'rules'  => 'required|min_length[2]|max_length[255]',
            'errors' => [
                'required'   => 'El nombre del doctor es obligatorio.',
                'min_length' => 'El nombre debe tener al menos 2 caracteres.',
            ],
        ],
        'phone_number' => [
            'rules'  => 'permit_empty|max_length[50]',
            'errors' => [
                'max_length' => 'El teléfono no puede superar 50 caracteres.',
            ],
        ],
        'gender' => [
            'rules'  => 'required|in_list[1,2]',
            'errors' => ['required' => 'Seleccione el género.'],
        ],
        'speciality' => [
            'rules'  => 'permit_empty|max_length[255]',
            'errors' => [
                'max_length' => 'La especialidad no puede superar 255 caracteres.',
            ],
        ],
        'address' => [
            'rules'  => 'permit_empty|max_length[255]',
            'errors' => [
                'max_length' => 'La dirección no puede superar 255 caracteres.',
            ],
        ],
    ];

    /**
     * Reglas para formulario de empleados
     */
    public array $employees = [
        'first_name' => [
            'rules'  => 'required|min_length[2]|max_length[255]',
            'errors' => [
                'required'   => 'El nombre es obligatorio.',
                'min_length' => 'El nombre debe tener al menos 2 caracteres.',
            ],
        ],
        'last_name_fa' => [
            'rules'  => 'required|min_length[2]|max_length[255]',
            'errors' => [
                'required'   => 'El apellido es obligatorio.',
                'min_length' => 'El apellido debe tener al menos 2 caracteres.',
            ],
        ],
        'username' => [
            'rules'  => 'required|min_length[3]|max_length[50]',
            'errors' => [
                'required'   => 'El usuario es obligatorio.',
                'min_length' => 'El usuario debe tener al menos 3 caracteres.',
            ],
        ],
        'password' => [
            'rules'  => 'required|min_length[4]',
            'errors' => [
                'required'   => 'La contraseña es obligatoria (mínimo 4 caracteres).',
                'min_length' => 'La contraseña debe tener al menos 4 caracteres.',
            ],
        ],
        'gender' => [
            'rules'  => 'required|in_list[1,2]',
            'errors' => ['required' => 'Seleccione el género.'],
        ],
    ];

    /**
     * Reglas para reactivos/insumos
     */
    public array $reactivo = [
        'nombre' => [
            'rules'  => 'required|min_length[1]|max_length[255]',
            'errors' => [
                'required'   => 'El nombre del insumo es obligatorio.',
                'max_length' => 'El nombre no puede superar 255 caracteres.',
            ],
        ],
        'tipo' => [
            'rules'  => 'required|in_list[1,2,3]',
            'errors' => ['in_list' => 'Tipo de insumo no válido.'],
        ],
        'stock_minimo' => [
            'rules'  => 'permit_empty|integer|greater_than_equal_to[0]',
            'errors' => ['integer' => 'Stock mínimo debe ser un número entero.'],
        ],
        'contenido_por_presentacion' => [
            'rules'  => 'permit_empty|integer|greater_than[0]',
            'errors' => ['integer' => 'Contenido debe ser un número entero positivo.'],
        ],
    ];

    public array $lote = [
        'reactivo_id' => [
            'rules'  => 'required|integer|greater_than[0]',
            'errors' => [
                'required'     => 'Seleccione el insumo.',
                'integer'      => 'El insumo no es válido.',
                'greater_than' => 'Seleccione un insumo válido.',
            ],
        ],
        'codigo_lote' => [
            'rules'  => 'required|max_length[100]',
            'errors' => [
                'required'   => 'El código de lote es obligatorio.',
                'max_length' => 'El código de lote no puede superar 100 caracteres.',
            ],
        ],
        'cantidad' => [
            'rules'  => 'required|integer|greater_than[0]',
            'errors' => [
                'required'     => 'La cantidad es obligatoria.',
                'integer'      => 'La cantidad debe ser un número entero.',
                'greater_than' => 'La cantidad debe ser mayor que cero.',
            ],
        ],
    ];

    public array $salida = [
        'reactivo_id' => [
            'rules'  => 'required|integer|greater_than[0]',
            'errors' => [
                'required'     => 'Seleccione el insumo.',
                'integer'      => 'El insumo no es válido.',
                'greater_than' => 'Seleccione un insumo válido.',
            ],
        ],
        'cantidad' => [
            'rules'  => 'required|integer|greater_than[0]',
            'errors' => [
                'required'     => 'La cantidad es obligatoria.',
                'integer'      => 'La cantidad debe ser un número entero.',
                'greater_than' => 'La cantidad debe ser mayor que cero.',
            ],
        ],
        'numero_orden' => [
            'rules'  => 'permit_empty|max_length[64]',
            'errors' => ['max_length' => 'El número de orden no puede superar 64 caracteres.'],
        ],
        'lote_id' => [
            'rules'  => 'permit_empty|integer|greater_than[0]',
            'errors' => [
                'integer'      => 'El lote no es válido.',
                'greater_than' => 'Seleccione un lote válido.',
            ],
        ],
    ];

    public array $revertir_salida = [
        'movimiento_id' => [
            'rules'  => 'required|integer|greater_than[0]',
            'errors' => [
                'required'     => 'Movimiento no válido.',
                'integer'      => 'Movimiento no válido.',
                'greater_than' => 'Movimiento no válido.',
            ],
        ],
        'reactivo_id' => [
            'rules'  => 'required|integer|greater_than[0]',
            'errors' => [
                'required'     => 'Insumo no válido.',
                'integer'      => 'Insumo no válido.',
                'greater_than' => 'Insumo no válido.',
            ],
        ],
    ];

    public array $pagos_cierre_crear = [
        'start' => [
            'rules'  => 'required|regex_match[/^\d{4}-\d{2}-\d{2}$/]',
            'errors' => ['regex_match' => 'La fecha inicial no es válida.'],
        ],
        'end' => [
            'rules'  => 'required|regex_match[/^\d{4}-\d{2}-\d{2}$/]',
            'errors' => ['regex_match' => 'La fecha final no es válida.'],
        ],
    ];

    /**
     * Reglas para guardar registro de análisis
     */
    public array $registro = [
        'registro' => [
            'rules'  => 'required',
            'errors' => [
                'required' => 'Faltan datos del registro.',
            ],
        ],
        'registro.person_id' => [
            'rules'  => 'required',
            'errors' => [
                'required' => 'Debe seleccionar un paciente.',
            ],
        ],
    ];
}
