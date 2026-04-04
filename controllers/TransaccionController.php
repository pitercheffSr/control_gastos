<?php

class TransaccionController
{
	private $pdo;

	public function __construct(PDO $pdo)
	{
		$this->pdo = $pdo;
	}

	/**
	 * Crea una nueva transacción en la base de datos
	 */
	public function crear($datos)
	{
		if (!isset($_SESSION['usuario_id'])) {
			return ['ok' => false, 'error' => 'Sesión no válida'];
		}

		try {
			$sql = "INSERT INTO transacciones (
                        id_usuario,
                        fecha,
                        monto,
                        tipo,
                        descripcion,
                        id_categoria,
                        id_subcategoria,
                        id_subsubcategoria
                    ) VALUES (
                        :uid, :fecha, :monto, :tipo, :desc, :cat, :sub, :subsub
                    )";

			$stmt = $this->pdo->prepare($sql);

			$stmt->execute([
				':uid'    => $_SESSION['usuario_id'],
				':fecha'  => $datos['fecha'],
				':monto'  => $datos['monto'],
				':tipo'   => $datos['tipo'],
				':desc'   => $datos['descripcion'] ?? null,
				':cat'    => !empty($datos['id_categoria']) ? $datos['id_categoria'] : null,
				':sub'    => !empty($datos['id_subcategoria']) ? $datos['id_subcategoria'] : null,
				':subsub' => !empty($datos['id_subsubcategoria']) ? $datos['id_subsubcategoria'] : null
			]);

			return ['ok' => true, 'id' => $this->pdo->lastInsertId()];
		} catch (PDOException $e) {
			return ['ok' => false, 'error' => 'Error BD: ' . $e->getMessage()];
		}
	}
	public function listar()
	{
		if (!isset($_SESSION['usuario_id'])) return ['ok' => false];
		try {
			$stmt = $this->pdo->prepare("
            SELECT t.*, c.nombre as cat_nombre, sc.nombre as sub_nombre, ssc.nombre as subsub_nombre
            FROM transacciones t
            LEFT JOIN categorias c ON t.id_categoria = c.id
            LEFT JOIN subcategorias sc ON t.id_subcategoria = sc.id
            LEFT JOIN subsubcategorias ssc ON t.id_subsubcategoria = ssc.id
            WHERE t.id_usuario = ?
            ORDER BY t.fecha DESC
        ");
			$stmt->execute([$_SESSION['usuario_id']]);
			return ['ok' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)];
		} catch (PDOException $e) {
			return ['ok' => false, 'error' => $e->getMessage()];
		}
	}
}
