<?php
/* ==========================================================
   UBICACIÓN: activos/areas.php/seguimientoSotano.php/sencores/actualizar_detector.php
   REGISTRA UNA REVISIÓN DEL DETECTOR (tb_revisiones) Y
   ACTUALIZA LAS FECHAS / ESTADO FÍSICO DEL DETECTOR
   ========================================================== */

require_once __DIR__ . '/../../../../clases/conexion.php';

session_start();

const URL_LISTADO = '../DectectoresdeHumo.php';

function h($v): string
{
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
}

function fechaValida(string $f): bool
{
    $d = DateTime::createFromFormat('Y-m-d', $f);
    return $d && $d->format('Y-m-d') === $f;
}

/* Sube una imagen y regresa la ruta relativa (o null) */
function guardarEvidencia(string $campo, string $prefijo): ?string
{
    if (empty($_FILES[$campo]) || $_FILES[$campo]['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($_FILES[$campo]['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Error al subir la imagen.');
    }
    if ($_FILES[$campo]['size'] > 5 * 1024 * 1024) {
        throw new Exception('La imagen no debe pesar más de 5 MB.');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->file($_FILES[$campo]['tmp_name']);
    $ext   = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'][$mime] ?? null;

    if (!$ext) {
        throw new Exception('Solo se permiten imágenes JPG, PNG o WEBP.');
    }

    $dir = __DIR__ . '/../../../../uploads/detectores';   // recidencias/uploads/detectores
    if (!is_dir($dir) && !mkdir($dir, 0775, true)) {
        throw new Exception('No se pudo crear la carpeta de evidencias.');
    }

    $nombre = $prefijo . '_' . bin2hex(random_bytes(8)) . '.' . $ext;

    if (!move_uploaded_file($_FILES[$campo]['tmp_name'], $dir . '/' . $nombre)) {
        throw new Exception('No se pudo guardar la imagen.');
    }

    return 'uploads/detectores/' . $nombre;
}

$bd = (new Conexion())->conectar();

/* ---------- DETECTOR ---------- */
$idDetector = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT)
           ?: filter_input(INPUT_POST, 'id_detector', FILTER_VALIDATE_INT);

$detector = null;

if ($idDetector) {
    $r = pg_query_params($bd,
        "SELECT d.id_detector, d.tipo_detector, d.marca, d.modelo, d.numero_serie,
                d.estado_fisico, d.fecha_ultima_revision, d.fecha_proxima_revision,
                a.nombre_area
         FROM tb_detectores_humo d
         INNER JOIN tb_areas a ON a.id_area = d.id_area
         WHERE d.id_detector = $1", [$idDetector]);
    $detector = $r ? pg_fetch_assoc($r) : null;
}

if (!$detector) {
    http_response_code(404);
    exit('Detector no encontrado.');
}

$error = null;

/* ---------- GUARDAR ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    try {
        $fRevision  = trim($_POST['fecha_revision'] ?? '');
        $fProxima   = trim($_POST['fecha_proxima_revision'] ?? '');
        $tipoRev    = trim($_POST['tipo_revision'] ?? '');
        $resultado  = trim($_POST['resultado'] ?? '');
        $estadoFis  = trim($_POST['estado_fisico'] ?? '');
        $respExt    = trim($_POST['responsable_externo'] ?? '');
        $observ     = trim($_POST['observaciones'] ?? '');

        $respExt = $respExt === '' ? null : mb_substr($respExt, 0, 100);
        $observ  = $observ === '' ? null : mb_substr($observ, 0, 500);

        if (!fechaValida($fRevision) || !fechaValida($fProxima)) {
            throw new Exception('Alguna de las fechas no es válida.');
        }
        if ($fRevision > date('Y-m-d')) {
            throw new Exception('La fecha de revisión no puede ser futura.');
        }
        if ($fProxima <= $fRevision) {
            throw new Exception('La próxima revisión debe ser posterior a la fecha de revisión.');
        }
        if (!in_array($tipoRev, ['preventiva', 'correctiva', 'inspeccion'], true)) {
            throw new Exception('Tipo de revisión no válido.');
        }
        if (!in_array($resultado, ['aprobado', 'con_observaciones', 'rechazado'], true)) {
            throw new Exception('Resultado no válido.');
        }
        if (!in_array($estadoFis, ['Bueno', 'Regular', 'Malo', 'Dañado'], true)) {
            throw new Exception('Estado físico no válido.');
        }

        $evidencia = guardarEvidencia('evidencia', 'rev');

        /* id_usuario: si tu login guarda otra clave en $_SESSION, cámbiala aquí */
        $idUsuario = $_SESSION['id_usuario'] ?? null;

        pg_query($bd, 'BEGIN');

        $ins = pg_query_params($bd,
            "INSERT INTO tb_revisiones
                (tipo_activo, id_activo, fecha_revision, fecha_proxima_revision,
                 tipo_revision, resultado, id_usuario, responsable_externo,
                 observaciones, evidencia)
             VALUES ('detectores_humo', $1, $2, $3, $4, $5, $6, $7, $8, $9)",
            [$idDetector, $fRevision, $fProxima, $tipoRev, $resultado,
             $idUsuario, $respExt, $observ, $evidencia]);

        if (!$ins) {
            throw new Exception('No se pudo registrar la revisión: ' . pg_last_error($bd));
        }

        $upd = pg_query_params($bd,
            "UPDATE tb_detectores_humo
                SET fecha_ultima_revision  = $1,
                    fecha_proxima_revision = $2,
                    estado_fisico          = $3,
                    updated_at             = NOW()
              WHERE id_detector = $4",
            [$fRevision, $fProxima, $estadoFis, $idDetector]);

        if (!$upd) {
            throw new Exception('No se pudo actualizar el detector: ' . pg_last_error($bd));
        }

        pg_query($bd, 'COMMIT');

        header('Location: ' . URL_LISTADO . '?ok=2');
        exit;

    } catch (Exception $e) {
        pg_query($bd, 'ROLLBACK');
        $error = $e->getMessage();
    }
}

/* Valores para repoblar el formulario */
$vFecha  = $_POST['fecha_revision'] ?? date('Y-m-d');
$vProx   = $_POST['fecha_proxima_revision'] ?? '';
$vTipo   = $_POST['tipo_revision'] ?? 'preventiva';
$vRes    = $_POST['resultado'] ?? 'aprobado';
$vEstado = $_POST['estado_fisico'] ?? ($detector['estado_fisico'] ?? 'Bueno');
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Actualizar detector #<?= (int) $detector['id_detector'] ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>

<body class="bg-light">

    <div class="container py-4" style="max-width: 720px;">

        <a href="<?= URL_LISTADO ?>" class="btn btn-outline-secondary btn-sm mb-3">
            <i class="fas fa-arrow-left me-1"></i> Volver
        </a>

        <div class="card shadow-sm mb-3">
            <div class="card-body">
                <h5 class="mb-1">Detector #<?= (int) $detector['id_detector'] ?></h5>
                <p class="text-muted mb-0">
                    <?= h($detector['nombre_area']) ?> ·
                    <?= h($detector['tipo_detector']) ?> ·
                    <?= h($detector['marca']) ?> <?= h($detector['modelo']) ?> ·
                    Serie <?= h($detector['numero_serie']) ?>
                </p>
            </div>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= h($error) ?></div>
        <?php endif; ?>

        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="fas fa-sync-alt me-2 text-primary"></i>Registrar revisión</h5>
            </div>

            <div class="card-body">
                <form method="POST" enctype="multipart/form-data">

                    <input type="hidden" name="id_detector" value="<?= (int) $detector['id_detector'] ?>">

                    <div class="row g-3">

                        <div class="col-md-6">
                            <label class="form-label" for="fecha_revision">Fecha de revisión</label>
                            <input type="date" class="form-control" id="fecha_revision" name="fecha_revision"
                                value="<?= h($vFecha) ?>" max="<?= date('Y-m-d') ?>" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="fecha_proxima_revision">Próxima revisión</label>
                            <input type="date" class="form-control" id="fecha_proxima_revision"
                                name="fecha_proxima_revision" value="<?= h($vProx) ?>" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="tipo_revision">Tipo de revisión</label>
                            <select class="form-select" id="tipo_revision" name="tipo_revision" required>
                                <option value="preventiva"  <?= $vTipo === 'preventiva'  ? 'selected' : '' ?>>Preventiva</option>
                                <option value="correctiva"  <?= $vTipo === 'correctiva'  ? 'selected' : '' ?>>Correctiva</option>
                                <option value="inspeccion"  <?= $vTipo === 'inspeccion'  ? 'selected' : '' ?>>Inspección</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="resultado">Resultado</label>
                            <select class="form-select" id="resultado" name="resultado" required>
                                <option value="aprobado"          <?= $vRes === 'aprobado'          ? 'selected' : '' ?>>Aprobado</option>
                                <option value="con_observaciones" <?= $vRes === 'con_observaciones' ? 'selected' : '' ?>>Con observaciones</option>
                                <option value="rechazado"         <?= $vRes === 'rechazado'         ? 'selected' : '' ?>>Rechazado</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="estado_fisico">Estado físico actual</label>
                            <select class="form-select" id="estado_fisico" name="estado_fisico" required>
                                <?php foreach (['Bueno', 'Regular', 'Malo', 'Dañado'] as $opt): ?>
                                    <option value="<?= $opt ?>" <?= $vEstado === $opt ? 'selected' : '' ?>><?= $opt ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="responsable_externo">Responsable externo (opcional)</label>
                            <input type="text" class="form-control" id="responsable_externo"
                                name="responsable_externo" maxlength="100"
                                value="<?= h($_POST['responsable_externo'] ?? '') ?>">
                        </div>

                        <div class="col-12">
                            <label class="form-label" for="observaciones">Observaciones (opcional)</label>
                            <textarea class="form-control" id="observaciones" name="observaciones"
                                rows="3" maxlength="500"><?= h($_POST['observaciones'] ?? '') ?></textarea>
                        </div>

                        <div class="col-12">
                            <label class="form-label" for="evidencia">Evidencia (foto, opcional)</label>
                            <input type="file" class="form-control" id="evidencia" name="evidencia"
                                accept="image/jpeg,image/png,image/webp">
                        </div>

                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-4">
                        <a href="<?= URL_LISTADO ?>" class="btn btn-secondary">Cancelar</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i> Guardar revisión
                        </button>
                    </div>

                </form>
            </div>
        </div>
    </div>

    <script>
        /* La próxima revisión debe ser posterior a la fecha de revisión */
        const fRev  = document.getElementById('fecha_revision');
        const fProx = document.getElementById('fecha_proxima_revision');

        function ajustarMin() {
            if (!fRev.value) return;
            const d = new Date(fRev.value + 'T00:00:00');
            d.setDate(d.getDate() + 1);
            fProx.min = d.toLocaleDateString('en-CA');
        }

        fRev.addEventListener('change', ajustarMin);
        ajustarMin();
    </script>

</body>

</html>