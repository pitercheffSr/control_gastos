document.addEventListener('DOMContentLoaded', () => {
  cargarCategorias();
  cargarTransacciones();

  document.addEventListener("DOMContentLoaded", () => {
  cargarCategorias();
});

async function cargarCategorias() {
  try {
    const resp = await fetch("get_categorias.php");
    const data = await resp.json();

    const selCat = document.getElementById("categoria");
    const selSub = document.getElementById("subcategoria");
    const selSubSub = document.getElementById("subsubcategoria");

    // Limpiar selects
    selCat.innerHTML = "<option value=''>Selecciona...</option>";
    selSub.innerHTML = "<option value=''>Selecciona...</option>";
    selSubSub.innerHTML = "<option value=''>Selecciona...</option>";

    // Rellenar categorías
    data.categorias.forEach(c => {
      selCat.innerHTML += `<option value="${c.id}">${c.nombre}</option>`;
    });

    // Al cambiar categoría, cargar subcategorías
    selCat.addEventListener("change", () => {
      const idCat = selCat.value;
      selSub.innerHTML = "<option value=''>Selecciona...</option>";
      selSubSub.innerHTML = "<option value=''>Selecciona...</option>";

      data.subcategorias
        .filter(sc => sc.id_categoria === idCat)
        .forEach(sc => {
          selSub.innerHTML += `<option value="${sc.id}">${sc.nombre}</option>`;
        });
    });

    // Al cambiar subcategoría, cargar sub-subcategorías
    selSub.addEventListener("change", () => {
      const idSub = selSub.value;
      selSubSub.innerHTML = "<option value=''>Selecciona...</option>";

      data.subsubcategorias
        .filter(ssc => ssc.id_subcategoria === idSub)
        .forEach(ssc => {
          selSubSub.innerHTML += `<option value="${ssc.id}">${ssc.nombre}</option>`;
        });
    });
  } catch (err) {
    console.error("Error al cargar categorías:", err);
  }
}

  document.getElementById('formTransaccion').addEventListener('submit', e => {
    e.preventDefault();
    const datos = new FormData(e.target);
    fetch('procesar_transaccion.php', { method: 'POST', body: datos })
      .then(r => r.json())
      .then(resp => {
        alert(resp.mensaje);
        if(resp.ok) cargarTransacciones();
      });
  });
});

function cargarCategorias() {
  fetch('js/categoria_subcategoria.js')
    .then(resp => resp.json())
    .then(data => {
      const selCat = document.getElementById('categoria');
      selCat.innerHTML = '<option value="">Seleccionar...</option>';
      data.categorias.forEach(cat => {
        selCat.innerHTML += `<option value="${cat.id}">${cat.nombre}</option>`;
      });
    });
}

function cargarTransacciones() {
  fetch('ftch.php')
    .then(resp => resp.json())
    .then(datos => {
      const tabla = document.getElementById('tablaTransacciones');
      tabla.innerHTML = '';
      datos.forEach(t => {
        tabla.innerHTML += `
          <tr>
            <td>${t.fecha}</td>
            <td>${t.categoria}</td>
            <td>${t.subcategoria}</td>
            <td>${t.subsubcategoria}</td>
            <td>${t.importe}</td>
            <td><button class='btn btn-danger btn-sm' onclick='borrar(${t.id})'>🗑️</button></td>
          </tr>`;
      });
    });
}

function borrar(id) {
  if(confirm('¿Seguro que quieres eliminar esta transacción?')){
    fetch('procesar_transaccion.php?id='+id, { method: 'DELETE' })
    .then(r => r.json())
    .then(resp => {
      alert(resp.mensaje);
      if(resp.ok) cargarTransacciones();
    });
  }
}
