// js/dashboard.js
document.addEventListener('DOMContentLoaded', () => {
  const selTipo = document.getElementById('tipo');
  const selN1   = document.getElementById('categoria');
  const selN2   = document.getElementById('subcategoria');
  const selN3   = document.getElementById('subsubcategoria');

  if (!selTipo || !selN1) {
    console.error('Elementos tipo/categoria no encontrados.');
    return;
  }

  // evento: cuando cambie tipo (gasto/ingreso) recargar nivel1
  selTipo.addEventListener('change', () => {
    limpiarSelect(selN1);
    limpiarSelect(selN2);
    limpiarSelect(selN3);
    cargarNivel1(selTipo.value);
  });

  // evento: cuando cambie nivel1 -> cargar nivel2
  selN1.addEventListener('change', () => {
    limpiarSelect(selN2);
    limpiarSelect(selN3);
    if (selN1.value) cargarNivel(2, selN1.value, selN2);
  });

  // evento: cuando cambie nivel2 -> cargar nivel3
  selN2.addEventListener('change', () => {
    limpiarSelect(selN3);
    if (selN2.value) cargarNivel(3, selN2.value, selN3);
  });

  // inicial: si hay un tipo seleccionado por defecto, cargar
  if (selTipo.value) cargarNivel1(selTipo.value);
});

// helpers
function limpiarSelect(sel) {
  sel.innerHTML = '<option value="">Selecciona...</option>';
}

function cargarNivel1(tipo) {
  const sel = document.getElementById('categoria');
  sel.innerHTML = '<option value="">Cargando...</option>';

  fetch(`load_categorias.php?nivel=nivel1&tipo=${encodeURIComponent(tipo)}`)
    .then(r => r.json())
    .then(data => {
      sel.innerHTML = '<option value="">Selecciona...</option>';
      if (!Array.isArray(data)) return;
      const frag = document.createDocumentFragment();
      data.forEach(x => {
        const o = document.createElement('option');
        o.value = x.id;
        o.textContent = x.nombre;
        frag.appendChild(o);
      });
      sel.appendChild(frag);
    })
    .catch(err => {
      console.error('Error cargarNivel1', err);
      sel.innerHTML = '<option value="">Error cargando categorías</option>';
    });
}

function cargarNivel(nivel, padre, destinoSelect) {
  destinoSelect.innerHTML = '<option value="">Cargando...</option>';
  fetch(`load_categorias.php?nivel=nivel${nivel}&padre=${encodeURIComponent(padre)}`)
    .then(r => r.json())
    .then(data => {
      destinoSelect.innerHTML = '<option value="">Selecciona...</option>';
      if (!Array.isArray(data)) return;
      const frag = document.createDocumentFragment();
      data.forEach(x => {
        const o = document.createElement('option');
        o.value = x.id;
        o.textContent = x.nombre;
        frag.appendChild(o);
      });
      destinoSelect.appendChild(frag);
    })
    .catch(err => {
      console.error('Error cargarNivel', nivel, err);
      destinoSelect.innerHTML = '<option value="">Error cargando</option>';
    });
}
