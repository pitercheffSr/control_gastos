<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Dashboard</title>
<link rel="stylesheet" href="assets/bootstrap/css/bootstrap.min.css">
<link rel="stylesheet" href="assets/icons/bootstrap-icons.css">
</head>
<body class="p-4">
<h3>Categorías</h3>
<select id="selectCat" class="form-select"></select>

<script src="assets/bootstrap/js/bootstrap.bundle.min.js"></script>
<script>
fetch('./load_categorias.php?nivel=categorias')
.then(r=>r.json())
.then(data=>{
    const sel=document.getElementById('selectCat');
    data.forEach(c=>{
        const o=document.createElement('option');
        o.value=c.id;
        o.textContent=c.nombre;
        sel.appendChild(o);
    });
});
</script>
</body>
</html>
