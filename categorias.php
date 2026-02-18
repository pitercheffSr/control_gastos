<?php
require_once 'config.php';
checkAuth();

$uid = $_SESSION['user_id'];
$error = '';

// --- FUNCIÓN HELPER: ENCONTRAR LA RAÍZ (Padre Supremo) ---
function obtenerIdRaiz($pdo, $catId) {
    $currentId = $catId;
    // Bucle para subir niveles hasta encontrar el que tiene parent_id NULL
    while(true) {
        $stmt = $pdo->prepare("SELECT id, parent_id FROM categorias WHERE id = ?");
        $stmt->execute([$currentId]);
        $cat = $stmt->fetch();
        
        if (!$cat) return null; // Error
        if ($cat['parent_id'] === NULL) return $cat['id']; // ¡Encontrado el padre supremo!
        
        $currentId = $cat['parent_id']; // Seguimos subiendo
    }
}

// --- 1. GUARDAR NUEVA CATEGORÍA ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] == 'crear') {
    $nombre = trim($_POST['nombre']);
    $parent_id = $_POST['parent_id'];

    if (!empty($nombre)) {
        $stmt = $pdo->prepare("INSERT INTO categorias (usuario_id, nombre, parent_id, tipo_fijo) VALUES (?, ?, ?, 'personalizado')");
        try {
            $stmt->execute([$uid, $nombre, $parent_id]);
            
            // TRUCO VISUAL: Buscamos cuál es la categoría raíz de donde acabamos de guardar
            $raizId = obtenerIdRaiz($pdo, $parent_id);
            
            // Redirigimos indicando qué acordeón debe abrirse (?open=ID)
            header("Location: categorias.php?open=" . $raizId);
            exit;
        } catch (PDOException $e) {
            $error = "Error: " . $e->getMessage();
        }
    }
}

// --- 2. BORRAR CATEGORÍA ---
if (isset($_GET['borrar'])) {
    $catId = $_GET['borrar'];
    
    // Antes de borrar, averiguamos la raíz para mantener el menú abierto tras borrar
    $raizId = obtenerIdRaiz($pdo, $catId);
    
    $stmtDel = $pdo->prepare("DELETE FROM categorias WHERE id = ? AND usuario_id = ? AND parent_id IS NOT NULL");
    $stmtDel->execute([$catId, $uid]);
    
    header("Location: categorias.php?open=" . $raizId);
    exit;
}

// --- 3. OBTENER DATOS (RECURSIVO) ---
function obtenerArbolCompleto($pdo, $uid, $parentId) {
    $stmt = $pdo->prepare("SELECT * FROM categorias WHERE usuario_id = ? AND parent_id = ? ORDER BY id ASC");
    $stmt->execute([$uid, $parentId]);
    $hijos = $stmt->fetchAll();
    
    foreach ($hijos as &$hijo) {
        $hijo['subcategorias'] = obtenerArbolCompleto($pdo, $uid, $hijo['id']);
    }
    return $hijos;
}

// Obtenemos los 3 Grandes Padres
$stmtPadres = $pdo->prepare("SELECT * FROM categorias WHERE usuario_id = ? AND parent_id IS NULL ORDER BY id ASC");
$stmtPadres->execute([$uid]);
$padres = $stmtPadres->fetchAll();

foreach ($padres as &$p) {
    $p['subcategorias'] = obtenerArbolCompleto($pdo, $uid, $p['id']);
}

// --- 4. FUNCIÓN VISUAL DE ACORDEÓN INFINITO ---
function renderizarAcordeon($categorias, $parentId) {
    if (empty($categorias)) return;

    $grupoId = 'accordion-' . $parentId;
    echo '<div class="accordion accordion-flush" id="'.$grupoId.'">';
    
    foreach ($categorias as $cat) {
        $tieneHijos = !empty($cat['subcategorias']);
        $collapseId = 'collapse-' . $cat['id'];
        $headingId  = 'heading-' . $cat['id'];

        echo '<div class="accordion-item bg-transparent border-0">';
        
        echo '<h2 class="accordion-header" id="'.$headingId.'">';
        $claseBoton = $tieneHijos ? '' : 'no-arrow';
        $atributoToggle = $tieneHijos ? 'data-bs-toggle="collapse" data-bs-target="#'.$collapseId.'"' : '';
        
        echo '<button class="accordion-button collapsed shadow-none py-2 '.$claseBoton.'" type="button" '.$atributoToggle.' style="background-color: transparent;">';
            echo '<div class="d-flex w-100 justify-content-between align-items-center pe-3">';
                echo '<span class="text-dark">';
                    if($tieneHijos) {
                        echo '<i class="fas fa-folder text-warning me-2"></i>';
                    } else {
                        echo '<i class="fas fa-folder-open text-muted me-2" style="opacity:0.5"></i>';
                    }
                    echo '<strong>' . htmlspecialchars($cat['nombre']) . '</strong>';
                echo '</span>';
                echo '<div>';
                    echo '<a href="#" class="text-success me-3" title="Añadir Subcategoría" 
                             onclick="event.stopPropagation(); abrirModal('.$cat['id'].', \''.htmlspecialchars($cat['nombre']).'\')">
                             <i class="fas fa-plus"></i>
                          </a>';
                    echo '<a href="?borrar='.$cat['id'].'" class="text-danger" title="Borrar"
                             onclick="event.stopPropagation(); return confirm(\'¿Borrar '.htmlspecialchars($cat['nombre']).' y todo su contenido?\')">
                             <i class="fas fa-trash-alt"></i>
                          </a>';
                echo '</div>';
            echo '</div>';
        echo '</button>';
        echo '</h2>';

        if ($tieneHijos) {
            echo '<div id="'.$collapseId.'" class="accordion-collapse collapse" data-bs-parent="#'.$grupoId.'">';
            echo '<div class="accordion-body p-0 ps-4 border-start ms-2">'; 
                renderizarAcordeon($cat['subcategorias'], $cat['id']);
            echo '</div>';
            echo '</div>';
        }
        echo '</div>';
    }
    echo '</div>';
}

include 'includes/header.php';

// DETECTAR CUÁL DEBE ESTAR ABIERTO
$openId = isset($_GET['open']) ? $_GET['open'] : null;
?>

<style>
    .accordion-button.no-arrow::after { display: none !important; }
    .accordion-button:focus { box-shadow: none; border-color: rgba(0,0,0,.125); }
    .accordion-button:not(.collapsed) { color: inherit; background-color: rgba(0, 0, 0, 0.03); }
</style>

<div class="d-flex justify-content-between align-items-center mb-4 pt-3">
    <h2 class="fw-bold text-dark">Árbol de Categorías</h2>
</div>

<?php if($error): ?>
    <div class="alert alert-danger"><?= $error ?></div>
<?php endif; ?>

<div class="accordion shadow-sm" id="accordionRaiz">
    <?php foreach($padres as $index => $padre): ?>
        
        <?php 
            // LÓGICA PARA MANTENER ABIERTO:
            // 1. Si hay un ID en la URL (?open=X) y coincide con este padre, abrirlo.
            // 2. Si NO hay ID en la URL, y es el primero ($index 0), abrirlo por defecto.
            $estaAbierto = false;
            if ($openId) {
                if ($padre['id'] == $openId) $estaAbierto = true;
            } else {
                if ($index == 0) $estaAbierto = true;
            }
            $claseShow = $estaAbierto ? 'show' : '';
            $claseCollapsed = $estaAbierto ? '' : 'collapsed';
        ?>

        <div class="accordion-item border-0 mb-3 rounded overflow-hidden shadow-sm">
            <h2 class="accordion-header">
                <button class="accordion-button <?= $claseCollapsed ?>" type="button" 
                        data-bs-toggle="collapse" 
                        data-bs-target="#collapseRaiz<?= $padre['id'] ?>"
                        style="border-left: 6px solid <?= $padre['color'] ?>;">
                    <span class="fw-bold fs-5" style="color: <?= $padre['color'] ?>">
                        <?= htmlspecialchars($padre['nombre']) ?>
                    </span>
                    <span class="badge bg-light text-dark ms-2 border">Principal</span>
                </button>
            </h2>
            
            <div id="collapseRaiz<?= $padre['id'] ?>" 
                 class="accordion-collapse collapse <?= $claseShow ?>" 
                 data-bs-parent="#accordionRaiz">
                <div class="accordion-body bg-white pt-2">
                    
                    <div class="mb-3 pb-2 border-bottom">
                        <button class="btn btn-sm btn-outline-primary" 
                                onclick="abrirModal(<?= $padre['id'] ?>, '<?= htmlspecialchars($padre['nombre']) ?>')">
                            <i class="fas fa-plus-circle"></i> Nueva Categoría en <?= htmlspecialchars($padre['nombre']) ?>
                        </button>
                    </div>

                    <?php 
                    if(empty($padre['subcategorias'])) {
                        echo '<p class="text-muted fst-italic ms-3 small">No hay subcategorías.</p>';
                    } else {
                        renderizarAcordeon($padre['subcategorias'], $padre['id']); 
                    }
                    ?>

                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="modal fade" id="modalCrearCategoria" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" method="POST">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">Nueva Subcategoría</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="action" value="crear">
                <input type="hidden" name="parent_id" id="modalParentId">
                <div class="alert alert-light border">
                    Creando dentro de: <strong id="modalParentName" class="text-primary"></strong>
                </div>
                <div class="mb-3">
                    <label class="form-label">Nombre</label>
                    <input type="text" name="nombre" class="form-control" required autofocus placeholder="Ej: Luz, Agua, Netflix...">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary">Guardar</button>
            </div>
        </form>
    </div>
</div>

<script>
function abrirModal(parentId, parentName) {
    document.getElementById('modalParentId').value = parentId;
    document.getElementById('modalParentName').textContent = parentName;
    var myModal = new bootstrap.Modal(document.getElementById('modalCrearCategoria'));
    myModal.show();
}
</script>

<?php include 'includes/footer.php'; ?>