<?php



namespace App\Models;



use CodeIgniter\Model;



class LeyendaCultivoModel extends Model

{

    protected $table            = 'leyendas_cultivo';

    protected $primaryKey       = 'leyenda_cultivo_id';

    protected $useAutoIncrement = true;

    protected $returnType       = 'array';

    protected $allowedFields    = [

        'leyenda_cultivo_categoria_id',

        'titulo',

        'mensaje',

        'activo',

        'deleted',

        'created_at',

        'updated_at',

    ];



    public function getAll(): array

    {

        if (! $this->ensureTable()) {

            return [];

        }



        return $this->queryWithCategoria()

            ->orderBy('c.nombre', 'ASC')

            ->orderBy('lc.titulo', 'ASC')

            ->get()

            ->getResultArray();

    }



    public function getActivas(): array

    {

        if (! $this->ensureTable()) {

            return [];

        }



        return $this->queryWithCategoria()
            ->where('lc.activo', 1)
            ->groupStart()
                ->where('lc.leyenda_cultivo_categoria_id IS NULL', null, false)
                ->orGroupStart()
                    ->where('c.activo', 1)
                    ->where('(c.deleted = 0 OR c.deleted IS NULL)', null, false)
                ->groupEnd()
            ->groupEnd()
            ->orderBy('c.nombre', 'ASC')
            ->orderBy('lc.titulo', 'ASC')
            ->get()
            ->getResultArray();

    }



    public function getById(int $id): ?array

    {

        if ($id < 1 || ! $this->ensureTable()) {

            return null;

        }



        $row = $this->queryWithCategoria()

            ->where('lc.leyenda_cultivo_id', $id)

            ->get()

            ->getRowArray();



        return $row ?: null;

    }



    public function saveLeyenda(array $data, ?int $id = null): bool

    {

        if (! $this->ensureTable()) {

            return false;

        }



        $now = \App\Services\RegisterService::mysqlNowForReport();

        $categoriaId = max(0, (int) ($data['leyenda_cultivo_categoria_id'] ?? 0));

        $save = [

            'leyenda_cultivo_categoria_id' => $categoriaId > 0 ? $categoriaId : null,

            'titulo'     => trim((string) ($data['titulo'] ?? '')),

            'mensaje'    => $this->sanitizeMensajeHtml((string) ($data['mensaje'] ?? '')),

            'activo'     => (int) ($data['activo'] ?? 1) ? 1 : 0,

            'updated_at' => $now,

            'deleted'    => 0,

        ];



        if ($save['titulo'] === '' || $categoriaId < 1) {

            return false;

        }



        if ($id !== null && $id > 0) {

            return $this->db->table($this->table)

                ->where('leyenda_cultivo_id', $id)

                ->update($save) !== false;

        }



        $save['created_at'] = $now;



        return $this->db->table($this->table)->insert($save) !== false;

    }



    public function softDelete(int $id): bool

    {

        if ($id < 1 || ! $this->ensureTable()) {

            return false;

        }



        return $this->db->table($this->table)

            ->where('leyenda_cultivo_id', $id)

            ->update([

                'deleted'    => 1,

                'updated_at' => \App\Services\RegisterService::mysqlNowForReport(),

            ]) !== false;

    }



    public function sanitizeMensajeHtml(string $html): string

    {

        $html = trim($html);

        if ($html === '') {

            return '';

        }



        $allowed = '<p><br><b><strong><i><em><u><ul><ol><li><span><div>';



        return strip_tags($html, $allowed);

    }



    /**

     * @return \CodeIgniter\Database\BaseBuilder

     */

    private function queryWithCategoria()

    {

        $lc = $this->db->prefixTable($this->table);

        $cat = $this->db->prefixTable('leyendas_cultivo_categorias');



        return $this->db->table($this->table . ' lc')

            ->select('lc.*, c.nombre AS categoria_nombre, c.activo AS categoria_activo')

            ->join($cat . ' c', 'c.leyenda_cultivo_categoria_id = lc.leyenda_cultivo_categoria_id', 'left')

            ->where('(lc.deleted = 0 OR lc.deleted IS NULL)');

    }



    /**

     * Crea la tabla leyendas_cultivo si la migración aún no corrió en esta BD.

     */

    public function ensureTable(): bool

    {

        if (! $this->db->tableExists($this->db->prefixTable($this->table))) {

            try {

                $forge = \Config\Database::forge($this->db);

                $forge->addField([

                    'leyenda_cultivo_id' => [

                        'type'           => 'INT',

                        'constraint'     => 11,

                        'unsigned'       => true,

                        'auto_increment' => true,

                    ],

                    'leyenda_cultivo_categoria_id' => [

                        'type'       => 'INT',

                        'constraint' => 11,

                        'unsigned'   => true,

                        'null'       => true,

                    ],

                    'titulo' => [

                        'type'       => 'VARCHAR',

                        'constraint' => 255,

                    ],

                    'mensaje' => [

                        'type' => 'MEDIUMTEXT',

                        'null' => true,

                    ],

                    'activo' => [

                        'type'       => 'TINYINT',

                        'constraint' => 1,

                        'default'    => 1,

                    ],

                    'deleted' => [

                        'type'       => 'TINYINT',

                        'constraint' => 1,

                        'default'    => 0,

                    ],

                    'created_at' => [

                        'type' => 'DATETIME',

                        'null' => true,

                    ],

                    'updated_at' => [

                        'type' => 'DATETIME',

                        'null' => true,

                    ],

                ]);

                $forge->addKey('leyenda_cultivo_id', true);

                $forge->addKey('deleted');

                $forge->addKey('leyenda_cultivo_categoria_id');

                $forge->createTable($this->table, true);

            } catch (\Throwable $e) {

                log_message('error', 'LeyendaCultivoModel::ensureTable: {err}', ['err' => $e->getMessage()]);



                return false;

            }

        }



        model(LeyendaCultivoCategoriaModel::class)->ensureTable();

        $this->ensureCategoriaColumn();



        return $this->db->tableExists($this->db->prefixTable($this->table));

    }



    private function ensureCategoriaColumn(): void

    {

        if (! $this->db->fieldExists('leyenda_cultivo_categoria_id', $this->table)) {

            try {

                $forge = \Config\Database::forge($this->db);

                $forge->addColumn($this->table, [

                    'leyenda_cultivo_categoria_id' => [

                        'type'       => 'INT',

                        'constraint' => 11,

                        'unsigned'   => true,

                        'null'       => true,

                        'after'      => 'leyenda_cultivo_id',

                    ],

                ]);

            } catch (\Throwable $e) {

                log_message('error', 'LeyendaCultivoModel::ensureCategoriaColumn: {err}', ['err' => $e->getMessage()]);

            }

        }

    }

}

