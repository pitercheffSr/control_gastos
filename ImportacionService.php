<?php

class ImportacionService {

    /**
     * Procesa un CSV en texto plano, detecta su formato y devuelve un array de transacciones auto-categorizadas.
     */
    public function procesarCsv(string $csvData, array $categoriasRaw): array {
        $transacciones = [];
        $handle = fopen('php://memory', 'rw');
        fwrite($handle, $csvData);
        rewind($handle);

        if ($handle === false) {
            throw new Exception("No se pudo leer el archivo CSV en memoria.");
        }

        $delimiter = $this->detectarDelimitador($handle);
        $bloqueActual = 'DESCONOCIDO';

        while (($data = fgetcsv($handle, 1000, $delimiter)) !== false) {
            foreach ($data as $key => $val) {
                $data[$key] = mb_convert_encoding($val, 'UTF-8', 'UTF-8, ISO-8859-1, Windows-1252');
            }

            if (isset($data[0])) {
                $data[0] = preg_replace('/^\xEF\xBB\xBF/', '', $data[0]); // Limpiar BOM
            }

            if (count($data) < 2) continue;

            // Detector de cabeceras de bancos
            if (count($data) >= 3 && strpos($data[0], 'Fecha') !== false && strpos($data[1] ?? '', 'Descripci') !== false) {
                $bloqueActual = 'PENDIENTES';
                continue;
            } else if (count($data) >= 4 && strpos($data[0], 'Fecha contable') !== false && strpos($data[2] ?? '', 'Descripci') !== false) {
                $bloqueActual = 'CONSOLIDADOS';
                continue;
            }

            $fecha_raw = trim($data[0]);

            if (preg_match('/^(\d{2,4})[-\/](\d{2})[-\/](\d{2,4})$/', $fecha_raw, $matches)) {
                if ($bloqueActual === 'PENDIENTES') {
                    $concepto = trim($data[1] ?? '');
                    $importe_raw = trim($data[2] ?? '');
                } else if ($bloqueActual === 'CONSOLIDADOS') {
                    $concepto = trim($data[2] ?? '');
                    $importe_raw = trim($data[3] ?? '');
                } else {
                    $concepto = trim($data[1] ?? '');
                    $importe_raw = trim($data[2] ?? '');
                }

                if (empty($concepto)) $concepto = "Movimiento bancario";

                if (strlen($matches[1]) === 4) {
                    $fechaFormateada = $matches[1] . '-' . $matches[2] . '-' . $matches[3]; // YYYY-MM-DD
                } else {
                    $year = strlen($matches[3]) === 2 ? '20' . $matches[3] : $matches[3];
                    $fechaFormateada = $year . '-' . $matches[2] . '-' . $matches[1]; // DD-MM-YYYY -> YYYY-MM-DD
                }

                $importe = $this->parsearImporte($importe_raw);
                $categoria_final = $this->categorizar($concepto, $categoriasRaw);

                $transacciones[] = [
                    'fecha' => $fechaFormateada,
                    'descripcion' => substr($concepto, 0, 255),
                    'importe' => $importe,
                    'categoria_id' => $categoria_final
                ];
            }
        }
        fclose($handle);
        return $transacciones;
    }

    private function detectarDelimitador($handle): string {
        $delimiter = ',';
        $firstLine = fgets($handle);
        if ($firstLine !== false) {
            if (substr_count($firstLine, ';') > substr_count($firstLine, ',')) {
                $delimiter = ';';
            }
        }
        rewind($handle);
        return $delimiter;
    }

    private function parsearImporte(string $importe_raw): float {
        $clean = preg_replace('/[^-0-9.,]/', '', $importe_raw);
        $commaPos = strrpos($clean, ',');
        $dotPos = strrpos($clean, '.');
        if ($commaPos !== false && $dotPos !== false) {
            if ($commaPos > $dotPos) { $clean = str_replace('.', '', $clean); $clean = str_replace(',', '.', $clean); }
            else { $clean = str_replace(',', '', $clean); }
        } elseif ($commaPos !== false) { $clean = str_replace(',', '.', $clean); }
        return (float) $clean;
    }

    private function categorizar(string $concepto, array $categoriasRaw): ?int {
        $conceptoLimpio = $this->limpiarTexto($concepto);
        foreach ($categoriasRaw as $cat) {
            $nombreCat = $cat['nombre'];
            $encontrado = false;

            if (preg_match('/\((.*?)\)/', $nombreCat, $coincidencias)) {
                $palabrasClave = explode(',', $coincidencias[1]);
                foreach ($palabrasClave as $palabra) {
                    $palabraLimpia = $this->limpiarTexto($palabra);
                    if (!empty($palabraLimpia) && preg_match('/(^|[^a-z0-9])' . preg_quote($palabraLimpia, '/') . '([^a-z0-9]|$)/i', $conceptoLimpio)) {
                        $encontrado = true; break;
                    }
                }
            }
            if (!$encontrado) {
                $nombreBaseLimpio = $this->limpiarTexto(trim(preg_replace('/\((.*?)\)/', '', $nombreCat)));
                if (!empty($nombreBaseLimpio) && preg_match('/(^|[^a-z0-9])' . preg_quote($nombreBaseLimpio, '/') . '([^a-z0-9]|$)/i', $conceptoLimpio)) {
                    $encontrado = true;
                }
            }
            if ($encontrado) return $cat['id'];
        }
        return null;
    }

    private function limpiarTexto(string $texto): string {
        $texto = strtolower(trim($texto));
        $buscar  = ['á','é','í','ó','ú','ü','ñ','Á','É','Í','Ó','Ú','Ü','Ñ'];
        $reemplazar = ['a','e','i','o','u','u','n','a','e','i','o','u','u','n'];
        return str_replace($buscar, $reemplazar, $texto);
    }
}
