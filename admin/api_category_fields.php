<?php
require_once '../includes/config.php';
require_once '../includes/database.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$db = new Database();

/**
 * Valida un campo in base al suo tipo
 */
function validateFieldValue($value, $fieldType, $fieldOptions, $isRequired) {
    // Campo obbligatorio vuoto
    if ($isRequired && (empty($value) && $value !== '0')) {
        return ['valid' => false, 'error' => 'Campo obbligatorio'];
    }
    
    // Se il campo è vuoto e non obbligatorio, è valido
    if (empty($value) && !$isRequired) {
        return ['valid' => true, 'value' => ''];
    }
    
    switch ($fieldType) {
        case 'email':
            if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                return ['valid' => false, 'error' => 'Email non valida'];
            }
            break;
            
        case 'url':
            if (!filter_var($value, FILTER_VALIDATE_URL)) {
                return ['valid' => false, 'error' => 'URL non valida'];
            }
            break;
            
        case 'number':
            if (!is_numeric($value)) {
                return ['valid' => false, 'error' => 'Deve essere un numero'];
            }
            $value = floatval($value);
            break;
            
        case 'datetime-local':
            $date = DateTime::createFromFormat('Y-m-d\TH:i', $value);
            if (!$date || $date->format('Y-m-d\TH:i') !== $value) {
                return ['valid' => false, 'error' => 'Data e ora non valide'];
            }
            break;
            
        case 'select':
            if (!empty($fieldOptions)) {
                $options = explode(',', $fieldOptions);
                $options = array_map('trim', $options);
                if (!in_array($value, $options)) {
                    return ['valid' => false, 'error' => 'Opzione non valida'];
                }
            }
            break;
            
        case 'checkbox':
            // Per checkbox multipli, il valore dovrebbe essere un array serializzato o una stringa separata da virgole
            if (!empty($fieldOptions)) {
                $availableOptions = explode(',', $fieldOptions);
                $availableOptions = array_map('trim', $availableOptions);
                
                // Se il valore è una stringa, convertila in array
                if (is_string($value)) {
                    $selectedOptions = explode(',', $value);
                    $selectedOptions = array_map('trim', $selectedOptions);
                } else if (is_array($value)) {
                    $selectedOptions = $value;
                } else {
                    return ['valid' => false, 'error' => 'Formato checkbox non valido'];
                }
                
                // Verifica che tutte le opzioni selezionate siano valide
                foreach ($selectedOptions as $selected) {
                    if (!empty($selected) && !in_array($selected, $availableOptions)) {
                        return ['valid' => false, 'error' => 'Opzione checkbox non valida: ' . $selected];
                    }
                }
                
                // Riconverti in stringa per il salvataggio
                $value = implode(',', array_filter($selectedOptions));
            }
            break;
            
        case 'text':
        case 'textarea':
            // Sanitizza HTML di base
            $value = strip_tags($value, '<b><i><u><strong><em><br><p>');
            break;
            
        case 'file':
            // Per i campi file, il valore dovrebbe essere un path o URL
            // La validazione del file vero e proprio viene fatta durante l'upload
            break;
    }
    
    return ['valid' => true, 'value' => $value];
}

/**
 * Gestisce l'upload di file per campi specifici
 */
function handleFileUpload($fieldName, $fieldType, $fieldOptions) {
    if (!isset($_FILES[$fieldName]) || $_FILES[$fieldName]['error'] === UPLOAD_ERR_NO_FILE) {
        return ['success' => false, 'error' => 'Nessun file caricato'];
    }
    
    $file = $_FILES[$fieldName];
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'Errore durante l\'upload del file'];
    }
    
    // Crea directory se non esiste
    $uploadDir = '../uploads/category_fields/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // Validazione tipo file in base alle opzioni
    $allowedExtensions = [];
    $allowedMimes = [];
    
    if (!empty($fieldOptions)) {
        if (strpos($fieldOptions, 'image/*') !== false) {
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
            $allowedMimes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        } elseif (strpos($fieldOptions, '.pdf') !== false) {
            $allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png'];
            $allowedMimes = ['application/pdf', 'image/jpeg', 'image/png'];
        } else {
            // Estrae estensioni dal campo options
            preg_match_all('/\.(\w+)/', $fieldOptions, $matches);
            if (!empty($matches[1])) {
                $allowedExtensions = $matches[1];
            }
        }
    }
    
    if (empty($allowedExtensions)) {
        // Default: immagini comuni
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'pdf'];
    }
    
    // Validazione estensione
    $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($fileExtension, $allowedExtensions)) {
        return ['success' => false, 'error' => 'Tipo file non supportato. Estensioni consentite: ' . implode(', ', $allowedExtensions)];
    }
    
    // Validazione MIME type se specificato
    if (!empty($allowedMimes)) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $fileMime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($fileMime, $allowedMimes)) {
            return ['success' => false, 'error' => 'Tipo di file non valido'];
        }
    }
    
    // Validazione dimensione (max 10MB)
    if ($file['size'] > 10 * 1024 * 1024) {
        return ['success' => false, 'error' => 'File troppo grande. Massimo 10MB'];
    }
    
    // Genera nome file unico
    $fileName = uniqid() . '_' . time() . '.' . $fileExtension;
    $targetPath = $uploadDir . $fileName;
    
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        return ['success' => true, 'path' => 'uploads/category_fields/' . $fileName];
    } else {
        return ['success' => false, 'error' => 'Errore durante il salvataggio del file'];
    }
}

// ================================
// GET - Recupera campi categoria
// ================================
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $category_id = $_GET['category_id'] ?? null;
    
    if (!$category_id) {
        http_response_code(400);
        echo json_encode(['error' => 'Category ID required']);
        exit;
    }
    
    try {
        $fields = $db->getCategoryFields($category_id);
        
        // Arricchisci i campi con metadati utili per il frontend
        foreach ($fields as &$field) {
            $field['validation'] = [
                'required' => (bool)$field['is_required'],
                'type' => $field['field_type']
            ];
            
            // Aggiungi opzioni parsed per select e checkbox
            if (in_array($field['field_type'], ['select', 'checkbox']) && !empty($field['field_options'])) {
                $field['options'] = array_map('trim', explode(',', $field['field_options']));
            }
            
            // Aggiungi attributi HTML per validazione frontend
            switch ($field['field_type']) {
                case 'email':
                    $field['html_attributes'] = ['type' => 'email'];
                    break;
                case 'url':
                    $field['html_attributes'] = ['type' => 'url'];
                    break;
                case 'number':
                    $field['html_attributes'] = ['type' => 'number', 'step' => 'any'];
                    break;
                case 'datetime-local':
                    $field['html_attributes'] = ['type' => 'datetime-local'];
                    break;
                case 'file':
                    $field['html_attributes'] = ['type' => 'file'];
                    if (!empty($field['field_options'])) {
                        $field['html_attributes']['accept'] = $field['field_options'];
                    }
                    break;
            }
        }
        
        echo json_encode(['success' => true, 'fields' => $fields]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to fetch category fields: ' . $e->getMessage()]);
    }
}

// ================================
// POST - Salva dati categoria
// ================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $article_id = $_POST['article_id'] ?? null;
    $category_id = $_POST['category_id'] ?? null;
    
    if (!$article_id || !$category_id) {
        http_response_code(400);
        echo json_encode(['error' => 'Article ID and Category ID required']);
        exit;
    }
    
    try {
        // Recupera i campi della categoria per validazione
        $categoryFields = $db->getCategoryFields($category_id);
        $fieldMap = [];
        foreach ($categoryFields as $field) {
            $fieldMap[$field['id']] = $field;
        }
        
        $validatedData = [];
        $errors = [];
        
        // Processa ogni campo
        foreach ($fieldMap as $fieldId => $fieldInfo) {
            $fieldName = 'category_field_' . $fieldId;
            $value = $_POST[$fieldName] ?? '';
            
            // Gestisce upload file per campi di tipo file
            if ($fieldInfo['field_type'] === 'file' && isset($_FILES[$fieldName])) {
                $uploadResult = handleFileUpload($fieldName, $fieldInfo['field_type'], $fieldInfo['field_options']);
                if ($uploadResult['success']) {
                    $value = $uploadResult['path'];
                } else {
                    if ($fieldInfo['is_required']) {
                        $errors[$fieldId] = $uploadResult['error'];
                        continue;
                    } else {
                        // File opzionale non caricato
                        $value = '';
                    }
                }
            }
            
            // Validazione campo
            $validation = validateFieldValue(
                $value,
                $fieldInfo['field_type'],
                $fieldInfo['field_options'],
                (bool)$fieldInfo['is_required']
            );
            
            if (!$validation['valid']) {
                $errors[$fieldId] = $validation['error'];
            } else {
                $validatedData[$fieldId] = $validation['value'];
            }
        }
        
        // Se ci sono errori, restituiscili
        if (!empty($errors)) {
            http_response_code(400);
            echo json_encode([
                'error' => 'Validation failed',
                'field_errors' => $errors
            ]);
            exit;
        }
        
        // Salva i dati validati
        $success = $db->saveArticleCategoryData($article_id, $category_id, $validatedData);
        
        if ($success) {
            echo json_encode([
                'success' => true,
                'message' => 'Dati categoria salvati con successo',
                'saved_fields' => count($validatedData)
            ]);
        } else {
            echo json_encode(['error' => 'Failed to save category data']);
        }
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'error' => 'Failed to save category data',
            'message' => $e->getMessage()
        ]);
    }
}

// ================================
// DELETE - Elimina file caricato
// ================================
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    // Gestisce eliminazione file caricati
    $input = json_decode(file_get_contents('php://input'), true);
    $filePath = $input['file_path'] ?? null;
    
    if (!$filePath) {
        http_response_code(400);
        echo json_encode(['error' => 'File path required']);
        exit;
    }
    
    // Sicurezza: verifica che il file sia nella directory corretta
    $realPath = realpath('../' . $filePath);
    $uploadsPath = realpath('../uploads/category_fields/');
    
    if ($realPath && $uploadsPath && strpos($realPath, $uploadsPath) === 0) {
        if (file_exists($realPath) && unlink($realPath)) {
            echo json_encode(['success' => true, 'message' => 'File eliminato']);
        } else {
            echo json_encode(['error' => 'File not found or cannot be deleted']);
        }
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid file path']);
    }
}
?>