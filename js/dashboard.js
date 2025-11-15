document.addEventListener('DOMContentLoaded', () => {
  cargarCategorias();
  const selCat = document.getElementById('categoria');
  const selSub = document.getElementById('subcategoria');
  const selSsc = document.getElementById('subsubcategoria');

  if (!selCat || !selSub || !selSsc) {
    console.error('Select elements not found: check ids "categoria", "subcategoria", "subsubcategoria"');
    return;
  }

  selCat.addEventListener('change', () => {
    cargarSubcategorias(selCat.value);
  });
  selSub.addEventListener('change', () => {
    cargarSubsubcategorias(selSub.value);
  });
});

function safeFetchJson(url) {
  return fetch(url)
    .then(r => {
      if (!r.ok) throw new Error('HTTP ' + r.status + ' ' + r.statusText);
      return r.json();
    })
    .catch(err => {
      console.error('Fetch error for', url, err);
      throw err;
    });
}

function cargarCategorias() {
  safeFetchJson('load_categorias.php?nivel=categorias')
    .then(data => {
      console.log('categorias recibidas:', data);
      const sel = document.getElementById('categoria');
      sel.innerHTML = '<option value="">Selecciona...</option>';
      if (!Array.isArray(data)) return;
      // usar fragmento para minimizar repaints
      const frag = document.createDocumentFragment();
      data.forEach(cat => {
        const opt = document.createElement('option');
        opt.value = cat.id;
        opt.textContent = cat.nombre;
        frag.appendChild(opt);
      });
      sel.appendChild(frag);
    })
    .catch(err => {
      // deja el select con una opción de error
      const sel = document.getElementById('categoria');
      if (sel) sel.innerHTML = '<option value="">Error cargando categorías</option>';
    });
}

function cargarSubcategorias(idCategoria) {
  const sel = document.getElementById('subcategoria');
  const selSubSub = document.getElementById('subsubcategoria');
  sel.innerHTML = '<option value="">Selecciona...</option>';
  selSubSub.innerHTML = '<option value="">Selecciona...</option>';
  if (!idCategoria) return;

  safeFetchJson(`load_categorias.php?nivel=subcategorias&padre=${encodeURIComponent(idCategoria)}`)
    .then(data => {
      console.log('subcategorias recibidas para', idCategoria, data);
      const frag = document.createDocumentFragment();
      data.forEach(sc => {
        const opt = document.createElement('option');
        opt.value = sc.id;
        opt.textContent = sc.nombre;
        frag.appendChild(opt);
      });
      sel.appendChild(frag);
    })
    .catch(err => {
      if (sel) sel.innerHTML = '<option value="">Error cargando subcategorías</option>';
    });
}

function cargarSubsubcategorias(idSub) {
  const sel = document.getElementById('subsubcategoria');
  sel.innerHTML = '<option value="">Selecciona...</option>';
  if (!idSub) return;

  safeFetchJson(`load_categorias.php?nivel=subsubcategorias&padre=${encodeURIComponent(idSub)}`)
    .then(data => {
      console.log('subsubcategorias recibidas para', idSub, data);
      const frag = document.createDocumentFragment();
      data.forEach(ssc => {
        const opt = document.createElement('option');
        opt.value = ssc.id;
        opt.textContent = ssc.nombre;
        frag.appendChild(opt);
      });
      sel.appendChild(frag);
    })
    .catch(err => {
      if (sel) sel.innerHTML = '<option value="">Error cargando sub-subcategorías</option>';
    });
}
