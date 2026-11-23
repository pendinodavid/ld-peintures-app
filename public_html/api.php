<?php
/**
 * Localisation Famille — mini-serveur.
 * Stocke et partage les positions des membres d'une "famille" (un code commun).
 * Aucune base de données : un simple fichier JSON par famille, dans /data.
 *
 * Actions :
 *   POST ?action=update  (family, id, name, lat, lng, acc)  -> enregistre ma position
 *   POST ?action=leave   (family, id)                       -> je retire ma position
 *   GET  ?action=list    (family)                           -> positions de la famille
 */

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

$DATA_DIR = __DIR__ . '/data';
if (!is_dir($DATA_DIR)) { @mkdir($DATA_DIR, 0775, true); }

// Combien de temps on garde une position avant de l'oublier (secondes).
const MAX_AGE = 86400; // 24 h

function fail($msg, $code = 400) {
    http_response_code($code);
    echo json_encode(['ok' => false, 'error' => $msg]);
    exit;
}

/** Nom de fichier sûr pour une famille (le code n'apparaît jamais en clair sur le disque). */
function family_file($dir, $family) {
    $family = strtolower(trim((string)$family));
    if (!preg_match('/^[a-z0-9\-]{3,40}$/', $family)) {
        fail('Code famille invalide : 3 à 40 lettres, chiffres ou tirets.');
    }
    return $dir . '/fam_' . hash('sha256', $family) . '.json';
}

/** Lit le fichier d'une famille sous verrou, applique $mut, puis réécrit. Renvoie le tableau. */
function with_family($file, $mut = null) {
    $fp = fopen($file, 'c+');
    if (!$fp) fail('Serveur : impossible d\'ouvrir le stockage.', 500);
    flock($fp, LOCK_EX);
    $raw  = stream_get_contents($fp);
    $data = $raw ? json_decode($raw, true) : [];
    if (!is_array($data)) $data = [];

    // On oublie les positions trop vieilles à chaque passage.
    $now = time();
    foreach ($data as $k => $m) {
        if (($now - (int)($m['t'] ?? 0)) > MAX_AGE) unset($data[$k]);
    }

    if ($mut) $data = $mut($data, $now);

    if ($mut) {
        ftruncate($fp, 0);
        rewind($fp);
        fwrite($fp, json_encode($data));
        fflush($fp);
    }
    flock($fp, LOCK_UN);
    fclose($fp);
    return $data;
}

$action = $_REQUEST['action'] ?? '';

if ($action === 'update') {
    $family = $_POST['family'] ?? '';
    $id     = (string)($_POST['id'] ?? '');
    $name   = trim((string)($_POST['name'] ?? ''));
    $lat    = $_POST['lat'] ?? '';
    $lng    = $_POST['lng'] ?? '';
    $acc    = $_POST['acc'] ?? null;

    if (!preg_match('/^[a-z0-9]{6,40}$/i', $id)) fail('Identifiant invalide.');
    if ($name === '') fail('Il manque le prénom.');
    if (!is_numeric($lat) || !is_numeric($lng)) fail('Position invalide.');
    if ((float)$lat < -90 || (float)$lat > 90 || (float)$lng < -180 || (float)$lng > 180) fail('Position hors limites.');

    $name = mb_substr($name, 0, 30);
    $file = family_file($DATA_DIR, $family);

    with_family($file, function ($data, $now) use ($id, $name, $lat, $lng, $acc) {
        $data[$id] = [
            'id'   => $id,
            'name' => $name,
            'lat'  => (float)$lat,
            'lng'  => (float)$lng,
            'acc'  => is_numeric($acc) ? (float)$acc : null,
            't'    => $now,
        ];
        return $data;
    });
    echo json_encode(['ok' => true, 't' => time()]);
    exit;
}

if ($action === 'leave') {
    $family = $_POST['family'] ?? '';
    $id     = (string)($_POST['id'] ?? '');
    $file   = family_file($DATA_DIR, $family);
    if (is_file($file)) {
        with_family($file, function ($data) use ($id) {
            unset($data[$id]);
            return $data;
        });
    }
    echo json_encode(['ok' => true]);
    exit;
}

if ($action === 'list') {
    $family  = $_GET['family'] ?? '';
    $file    = family_file($DATA_DIR, $family);
    $members = [];
    if (is_file($file)) {
        $data = with_family($file, null); // lecture seule (purge appliquée en lecture)
        foreach ($data as $m) $members[] = $m;
    }
    echo json_encode(['ok' => true, 'now' => time(), 'members' => array_values($members)]);
    exit;
}

fail('Action inconnue.');
