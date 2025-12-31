<?php

/**
 * ------------------------------------------------------------
 * TransaccionModel.php
 * ------------------------------------------------------------
 * Modelo de acceso a datos para transacciones.
 * ------------------------------------------------------------
 */

class TransaccionModel
{
	private PDO $pdo;

	public function __construct(PDO $pdo)
	{
		$this->pdo = $pdo;
	}

	public function crear(array $data): void
	{
		$sql = "
            INSERT INTO transacciones
            (id_usuario, fecha, descripcion, monto, tipo,
             id_categoria, id_subcategoria, id_subsubcategoria)
            VALUES
            (:uid, :fecha, :descripcion, :monto, :tipo,
             :cat, :subcat, :subsub)
        ";

		$stmt = $this->pdo->prepare($sql);

		$stmt->execute([
			'uid'        => $data['id_usuario'],
			'fecha'      => $data['fecha'],
			'descripcion' => $data['descripcion'],
			'monto'      => $data['monto'],
			'tipo'       => $data['tipo'],
			'cat'        => $data['id_categoria'],
			'subcat'     => $data['id_subcategoria'],
			'subsub'     => $data['id_subsubcategoria'],
		]);
	}
	/**
	 * Obtener todas las transacciones del usuario
	 */
	public function listarPorUsuario(int $idUsuario): array
	{
		$sql = "
            SELECT
                t.id,
                t.fecha,
                t.descripcion,
                t.monto,
                t.tipo,
                c.nombre AS categoria,
                s.nombre AS subcategoria
            FROM transacciones t
            LEFT JOIN categorias c ON c.id = t.id_categoria
            LEFT JOIN subcategorias s ON s.id = t.id_subcategoria
            WHERE t.id_usuario = :uid
            ORDER BY t.fecha DESC, t.id DESC
        ";

		$stmt = $this->pdo->prepare($sql);
		$stmt->execute(['uid' => $idUsuario]);

		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	/**
	 * Eliminar transacción
	 */
	public function eliminar(int $id, int $idUsuario): bool
	{
		$sql = "DELETE FROM transacciones WHERE id = :id AND id_usuario = :uid";
		$stmt = $this->pdo->prepare($sql);

		return $stmt->execute([
			'id'  => $id,
			'uid' => $idUsuario
		]);
	}
	/**
	 * Obtener una transacción por ID y usuario
	 */
	public function obtenerPorId(int $id, int $usuarioId): ?array
	{
		$stmt = $this->pdo->prepare("
        SELECT *
        FROM transacciones
        WHERE id = :id AND id_usuario = :uid
        LIMIT 1
    ");

		$stmt->execute([
			'id'  => $id,
			'uid' => $usuarioId
		]);

		$res = $stmt->fetch(PDO::FETCH_ASSOC);
		return $res ?: null;
	}

	/**
	 * Editar transacción
	 */
	public function editar(int $id, int $idUsuario, array $data): bool
	{
		$sql = "
            UPDATE transacciones
            SET
                fecha = :fecha,
                descripcion = :descripcion,
                monto = :monto,
                tipo = :tipo,
                id_categoria = :cat,
                id_subcategoria = :subcat,
                id_subsubcategoria = :subsub
            WHERE id = :id AND id_usuario = :uid
        ";

		$stmt = $this->pdo->prepare($sql);

		return $stmt->execute([
			'fecha'   => $data['fecha'],
			'descripcion' => $data['descripcion'],
			'monto'   => $data['monto'],
			'tipo'    => $data['tipo'],
			'cat'     => $data['id_categoria'],
			'subcat'  => $data['id_subcategoria'],
			'subsub'  => $data['id_subsubcategoria'],
			'id'      => $id,
			'uid'     => $idUsuario,
		]);
	}
}
