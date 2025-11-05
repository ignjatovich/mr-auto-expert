<?php
// Escape HTML za bezbednost
function e($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

// Formatiranje datuma
function formatuj_datum($datum) {
    return date('d.m.Y H:i', strtotime($datum));
}

// Upload slike
function upload_slika($file, $folder = 'vozila') {
    $upload_dir = __DIR__ . '/../uploads/' . $folder . '/';

    // Kreiraj folder ako ne postoji
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    // Generiši jedinstveno ime
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '_' . time() . '.' . $extension;
    $filepath = $upload_dir . $filename;

    // Provera tipa fajla
    $allowed_types = ['image/jpeg', 'image/png', 'image/jpg', 'image/webp'];
    if (!in_array($file['type'], $allowed_types)) {
        return ['success' => false, 'error' => 'Nedozvoljen tip fajla'];
    }

    // Provera veličine (max 5MB)
    if ($file['size'] > 5242880) {
        return ['success' => false, 'error' => 'Fajl je prevelik (max 5MB)'];
    }

    // Upload
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return ['success' => true, 'filename' => $filename];
    } else {
        return ['success' => false, 'error' => 'Greška pri uploadu'];
    }
}

// Dobavi sve dostupne usluge iz baze
function get_usluge() {
    global $conn;

    $stmt = $conn->query("SELECT id, naziv, cena FROM usluge WHERE aktivan = 1 ORDER BY naziv");
    $usluge = [];

    while ($row = $stmt->fetch()) {
        $usluge[$row['id']] = [
            'naziv' => $row['naziv'],
            'cena' => $row['cena']
        ];
    }

    return $usluge;
}

// Dobavi naziv usluge po ID-u
function get_usluga_naziv($id) {
    global $conn;

    $stmt = $conn->prepare("SELECT naziv FROM usluge WHERE id = ?");
    $stmt->execute([$id]);
    $result = $stmt->fetch();

    return $result ? $result['naziv'] : 'Nepoznata usluga';
}

// Dobavi cenu usluge po ID-u
function get_usluga_cena($id) {
    global $conn;

    $stmt = $conn->prepare("SELECT cena FROM usluge WHERE id = ?");
    $stmt->execute([$id]);
    $result = $stmt->fetch();

    return $result ? $result['cena'] : 0;
}

// Dobavi status badge
function get_status_badge($status) {
    $badges = [
        'u_radu' => '<span class="badge badge-danger">U radu</span>',
        'zavrseno' => '<span class="badge badge-warning">Završeno</span>',
        'placeno' => '<span class="badge badge-success">Plaćeno</span>'
    ];

    return $badges[$status] ?? $status;
}

// Dobavi status text
function get_status_text($status) {
    $statusi = [
        'u_radu' => 'U radu',
        'zavrseno' => 'Završeno',
        'placeno' => 'Plaćeno'
    ];

    return $statusi[$status] ?? $status;
}
?>