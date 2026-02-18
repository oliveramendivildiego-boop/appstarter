<?php

namespace App\Models;

use CodeIgniter\Model;

class MuestraModel extends Model
{
    protected $table = 'muestra';
    protected $primaryKey = 'muestra_id';

    public function getById(int $muestraId): ?array
    {
        $row = $this->db->table('muestra')
            ->where('muestra_id', $muestraId)
            ->get()
            ->getRowArray();
        return $row ?: null;
    }

    public function getByRegistro(int $registroId): ?array
    {
        $row = $this->db->table('muestra')
            ->where('registro_id', $registroId)
            ->where('(deleted = 0 OR deleted IS NULL)')
            ->get()
            ->getRowArray();
        return $row ?: null;
    }

    public function getTiposMuestra(): array
    {
        return $this->db->table('tipo_muestra')
            ->where('(deleted = 0 OR deleted IS NULL)')
            ->orderBy('nombre')
            ->get()
            ->getResultArray();
    }

    public function generarCodigo(): string
    {
        $prefijo = 'M' . date('Ymd');
        $ultimo = $this->db->table('muestra')
            ->like('codigo_barras', $prefijo, 'after')
            ->orderBy('muestra_id', 'DESC')
            ->limit(1)
            ->get()
            ->getRow();
        $seq = 1;
        if ($ultimo && preg_match('/\d+$/', $ultimo->codigo_barras ?? '', $m)) {
            $seq = (int) $m[0] + 1;
        }
        return $prefijo . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }

    public function saveMuestra(array $data, ?int $id = null): int|bool
    {
        $save = [
            'registro_id'     => (int) ($data['registro_id'] ?? 0),
            'codigo_barras'   => trim($data['codigo_barras'] ?? '') ?: $this->generarCodigo(),
            'tipo_muestra_id' => (int) ($data['tipo_muestra_id'] ?? 1),
            'estado'          => (int) ($data['estado'] ?? 0),
            'observaciones'   => (string) ($data['observaciones'] ?? ''),
            'deleted'         => 0,
        ];
        if (!empty($data['fecha_tomada'])) $save['fecha_tomada'] = $data['fecha_tomada'];
        if (!empty($data['usuario_tomo'])) $save['usuario_tomo'] = (int) $data['usuario_tomo'];
        if (isset($data['fecha_recibida'])) $save['fecha_recibida'] = $data['fecha_recibida'];
        if (isset($data['usuario_recibio'])) $save['usuario_recibio'] = (int) $data['usuario_recibio'];
        if (isset($data['fecha_procesada'])) $save['fecha_procesada'] = $data['fecha_procesada'];

        if ($id) {
            $this->db->table('muestra')->where('muestra_id', $id)->update($save);
            return $id;
        }
        $this->db->table('muestra')->insert($save);
        return (int) $this->db->insertID();
    }

    public function cambiarEstado(int $id, int $estado, ?int $usuario = null): bool
    {
        $upd = ['estado' => $estado];
        if ($estado === 1) {
            $upd['fecha_recibida'] = date('Y-m-d H:i:s');
            $upd['usuario_recibio'] = $usuario;
        } elseif ($estado === 2) {
            $upd['fecha_procesada'] = date('Y-m-d H:i:s');
        }
        return $this->db->table('muestra')->where('muestra_id', $id)->update($upd);
    }
}
