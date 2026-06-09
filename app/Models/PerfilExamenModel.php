<?php

namespace App\Models;

use CodeIgniter\Model;

class PerfilExamenModel extends Model
{
    protected $table      = 'perfil_examen';
    protected $primaryKey = 'perfil_id';

    public function getAll(): array
    {
        return $this->db->table('perfil_examen')
            ->where('(deleted = 0 OR deleted IS NULL)')
            ->orderBy('nombre')
            ->get()
            ->getResultArray();
    }

    public function getPruebas(int $perfilId): array
    {
        $row = $this->db->table('perfil_examen')
            ->where('perfil_id', $perfilId)
            ->get()
            ->getRow();
        if (!$row || empty($row->pruebas)) {
            return [];
        }
        return array_map('intval', array_filter(explode(',', $row->pruebas)));
    }

    public function savePerfil(array $data, ?int $id = null): bool
    {
        $save = [
            'nombre' => trim($data['nombre'] ?? ''),
            'pruebas' => is_array($data['pruebas'] ?? null) ? implode(',', $data['pruebas']) : (string) ($data['pruebas'] ?? ''),
            'deleted' => 0,
        ];
        if ($id) {
            return $this->db->table('perfil_examen')->where('perfil_id', $id)->update($save);
        }
        return $this->db->table('perfil_examen')->insert($save) !== false;
    }

    public function deletePerfil(int $id): bool
    {
        return $this->db->table('perfil_examen')->where('perfil_id', $id)->update(['deleted' => 1]);
    }

    /**
     * Mapa prianacategoria_id => nombres de perfiles que incluyen ese análisis.
     *
     * @return array<int, list<string>>
     */
    public function getAnalysisToProfilesMap(): array
    {
        $map = [];
        foreach ($this->getAll() as $perfil) {
            $nombre = trim((string) ($perfil['nombre'] ?? ''));
            $pruebas = (string) ($perfil['pruebas'] ?? '');
            if ($nombre === '' || $pruebas === '') {
                continue;
            }
            foreach (array_filter(array_map('intval', explode(',', $pruebas))) as $analysisId) {
                if ($analysisId < 1) {
                    continue;
                }
                $map[$analysisId][] = $nombre;
            }
        }

        foreach ($map as &$perfiles) {
            sort($perfiles, SORT_NATURAL | SORT_FLAG_CASE);
        }
        unset($perfiles);

        return $map;
    }
}
