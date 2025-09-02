<?php
// Database MySQL Class per Passione Calabria
// Usa questo file invece di database.php se preferisci MySQL

class Database {
    private $pdo;
    private $host;
    private $dbname;
    private $username;
    private $password;

    public function __construct() {
        // Configurazione database MySQL
        // 🔧 MODIFICA QUESTI VALORI
        $this->host = 'db5018301966.hosting-data.io';        // Server MySQL
        $this->dbname = 'dbs14504718'; // Nome database
        $this->username = 'dbu1167357';         // Username MySQL
        $this->password = 'Barboncino692@@';             // Password MySQL

        try {
            $dsn = "mysql:host={$this->host};dbname={$this->dbname};charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
            ];

            $this->pdo = new PDO($dsn, $this->username, $this->password, $options);

        } catch (PDOException $e) {
            die('Errore connessione database MySQL: ' . $e->getMessage());
        }
    }

    // Metodi per categorie
    public function getCategories() {
        $stmt = $this->pdo->prepare('SELECT * FROM categories ORDER BY name');
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getCategoryById($id) {
        $stmt = $this->pdo->prepare('SELECT * FROM categories WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch();
    }


    // Metodi per utenti
    public function getUsers() {
        $stmt = $this->pdo->prepare('SELECT id, email, name, role, status, last_login FROM users ORDER BY created_at DESC');
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getUserById($id) {
        $stmt = $this->pdo->prepare('SELECT id, email, name, first_name, last_name, role, status FROM users WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function createUser($email, $password, $name, $role, $status) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $sql = "INSERT INTO users (email, password, name, role, status) VALUES (?, ?, ?, ?, ?)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$email, $hashed_password, $name, $role, $status]);
        return $this->pdo->lastInsertId();
    }

    public function updateUser($id, $email, $password, $name, $role, $status) {
        $sql = "UPDATE users SET email = ?, name = ?, role = ?, status = ?";
        $params = [$email, $name, $role, $status];
        if (!empty($password)) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $sql .= ", password = ?";
            $params[] = $hashed_password;
        }
        $sql .= " WHERE id = ?";
        $params[] = $id;
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    public function deleteUser($id) {
        $stmt = $this->pdo->prepare('DELETE FROM users WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->rowCount();
    }

    // Metodi per commenti
    public function getComments($status = null) {
        $sql = '
            SELECT c.*, a.title as article_title
            FROM comments c
            LEFT JOIN articles a ON c.article_id = a.id
        ';
        $params = [];
        if ($status) {
            $sql .= ' WHERE c.status = ?';
            $params[] = $status;
        }
        $sql .= ' ORDER BY c.created_at DESC';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getCommentById($id) {
        $stmt = $this->pdo->prepare('SELECT * FROM comments WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function updateCommentStatus($id, $status) {
        $stmt = $this->pdo->prepare('UPDATE comments SET status = ? WHERE id = ?');
        $stmt->execute([$status, $id]);
        return $stmt->rowCount();
    }

    public function updateCommentContent($id, $content) {
        $stmt = $this->pdo->prepare('UPDATE comments SET content = ? WHERE id = ?');
        $stmt->execute([$content, $id]);
        return $stmt->rowCount();
    }

    public function deleteComment($id) {
        $stmt = $this->pdo->prepare('DELETE FROM comments WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->rowCount();
    }

    public function createCategory($name, $description, $icon) {
        $sql = "INSERT INTO categories (name, description, icon) VALUES (?, ?, ?)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$name, $description, $icon]);
        return $this->pdo->lastInsertId();
    }

    public function updateCategory($id, $name, $description, $icon) {
        $sql = "UPDATE categories SET name = ?, description = ?, icon = ? WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$name, $description, $icon, $id]);
        return $stmt->rowCount();
    }

    public function deleteCategory($id) {
        $stmt = $this->pdo->prepare('DELETE FROM categories WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->rowCount();
    }

    public function createPlaceSuggestion($name, $description, $location, $suggested_by_name, $suggested_by_email) {
        $sql = "INSERT INTO place_suggestions (name, description, address, suggested_by_name, suggested_by_email) VALUES (?, ?, ?, ?, ?)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$name, $description, $location, $suggested_by_name, $suggested_by_email]);
        return $this->pdo->lastInsertId();
    }

    // Metodi per province
    public function getProvinces() {
        $stmt = $this->pdo->prepare('SELECT * FROM provinces ORDER BY name');
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getProvinceById($id) {
        $stmt = $this->pdo->prepare('SELECT * FROM provinces WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function createProvince($name, $description, $image_path) {
        $sql = "INSERT INTO provinces (name, description, image_path) VALUES (?, ?, ?)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$name, $description, $image_path]);
        return $this->pdo->lastInsertId();
    }

    public function updateProvince($id, $name, $description, $image_path) {
        $sql = "UPDATE provinces SET name = ?, description = ?, image_path = ? WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$name, $description, $image_path, $id]);
        return $stmt->rowCount();
    }

    public function deleteProvince($id) {
        $stmt = $this->pdo->prepare('DELETE FROM provinces WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->rowCount();
    }

    // Metodi per città
    public function getCities() {
        $stmt = $this->pdo->prepare('
            SELECT c.*, p.name as province_name
            FROM cities c
            LEFT JOIN provinces p ON c.province_id = p.id
            ORDER BY c.name
        ');
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getCitiesByProvince($provinceId) {
        $stmt = $this->pdo->prepare('SELECT * FROM cities WHERE province_id = ? ORDER BY name');
        $stmt->execute([$provinceId]);
        return $stmt->fetchAll();
    }

    public function getCityById($id) {
        $stmt = $this->pdo->prepare('SELECT * FROM cities WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function createCity($name, $province_id, $lat, $lng, $description) {
        $sql = "INSERT INTO cities (name, province_id, latitude, longitude, description) VALUES (?, ?, ?, ?, ?)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$name, $province_id, $lat, $lng, $description]);
        return $this->pdo->lastInsertId();
    }

    public function updateCity($id, $name, $province_id, $lat, $lng, $description) {
        $sql = "UPDATE cities SET name = ?, province_id = ?, latitude = ?, longitude = ?, description = ? WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$name, $province_id, $lat, $lng, $description, $id]);
        return $stmt->rowCount();
    }

    public function deleteCity($id) {
        $stmt = $this->pdo->prepare('DELETE FROM cities WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->rowCount();
    }

    // Metodi per articoli
    public function getArticles($limit = null, $offset = 0, $onlyPublished = true) {
        $sql = '
            SELECT a.*, c.name as category_name, p.name as province_name, ci.name as city_name
            FROM articles a
            LEFT JOIN categories c ON a.category_id = c.id
            LEFT JOIN provinces p ON a.province_id = p.id
            LEFT JOIN cities ci ON a.city_id = ci.id
        ';

        $params = [];
        if ($onlyPublished) {
            $sql .= ' WHERE a.status = ?';
            $params[] = 'published';
        }

        $sql .= ' ORDER BY a.created_at DESC';

        if ($limit) {
            $sql .= ' LIMIT ' . (int)$limit . ' OFFSET ' . (int)$offset;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getArticleById($id) {
        $stmt = $this->pdo->prepare('
            SELECT a.*, c.name as category_name, p.name as province_name, ci.name as city_name
            FROM articles a
            LEFT JOIN categories c ON a.category_id = c.id
            LEFT JOIN provinces p ON a.province_id = p.id
            LEFT JOIN cities ci ON a.city_id = ci.id
            WHERE a.id = ?
        ');
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function createArticle($title, $slug, $content, $excerpt, $category_id, $province_id, $city_id, $status) {
        $sql = "INSERT INTO articles (title, slug, content, excerpt, category_id, province_id, city_id, status, author) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Admin')";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$title, $slug, $content, $excerpt, $category_id, $province_id, $city_id, $status]);
        return $this->pdo->lastInsertId();
    }

    public function updateArticle($id, $title, $slug, $content, $excerpt, $category_id, $province_id, $city_id, $status) {
        $sql = "UPDATE articles SET title = ?, slug = ?, content = ?, excerpt = ?, category_id = ?, province_id = ?, city_id = ?, status = ? WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$title, $slug, $content, $excerpt, $category_id, $province_id, $city_id, $status, $id]);
        return $stmt->rowCount();
    }

    public function deleteArticle($id) {
        $stmt = $this->pdo->prepare('DELETE FROM articles WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->rowCount();
    }

    public function getFeaturedArticles($limit = 6) {
        $stmt = $this->pdo->prepare('
            SELECT a.*, c.name as category_name
            FROM articles a
            LEFT JOIN categories c ON a.category_id = c.id
            WHERE a.featured = 1 AND a.status = ?
            ORDER BY a.views DESC
            LIMIT ?
        ');
        $stmt->execute(['published', $limit]);
        return $stmt->fetchAll();
    }

    public function getArticlesByCategory($categoryId, $limit = null) {
        $sql = '
            SELECT a.*, c.name as category_name, p.name as province_name
            FROM articles a
            LEFT JOIN categories c ON a.category_id = c.id
            LEFT JOIN provinces p ON a.province_id = p.id
            WHERE a.category_id = ? AND a.status = ?
            ORDER BY a.created_at DESC
        ';

        if ($limit) {
            $sql .= ' LIMIT ' . (int)$limit;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$categoryId, 'published']);
        return $stmt->fetchAll();
    }

    public function getArticlesByProvince($provinceId, $limit = null) {
        $sql = '
            SELECT a.*, c.name as category_name, p.name as province_name
            FROM articles a
            LEFT JOIN categories c ON a.category_id = c.id
            LEFT JOIN provinces p ON a.province_id = p.id
            WHERE a.province_id = ? AND a.status = ?
            ORDER BY a.created_at DESC
        ';

        if ($limit) {
            $sql .= ' LIMIT ' . (int)$limit;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$provinceId, 'published']);
        return $stmt->fetchAll();
    }

    public function getArticleBySlug($slug) {
        $stmt = $this->pdo->prepare('
            SELECT a.*, c.name as category_name, p.name as province_name, ci.name as city_name
            FROM articles a
            LEFT JOIN categories c ON a.category_id = c.id
            LEFT JOIN provinces p ON a.province_id = p.id
            LEFT JOIN cities ci ON a.city_id = ci.id
            WHERE a.slug = ?
        ');
        $stmt->execute([$slug]);
        return $stmt->fetch();
    }

    public function getArticleCountByCategory($categoryId) {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) as count FROM articles WHERE category_id = ? AND status = ?');
        $stmt->execute([$categoryId, 'published']);
        return $stmt->fetch()['count'];
    }

    public function getArticleCountByProvince($provinceId) {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) as count FROM articles WHERE province_id = ? AND status = ?');
        $stmt->execute([$provinceId, 'published']);
        return $stmt->fetch()['count'];
    }

    public function searchArticles($query, $provinceId = null) {
        $sql = '
            SELECT a.*, c.name as category_name, p.name as province_name
            FROM articles a
            LEFT JOIN categories c ON a.category_id = c.id
            LEFT JOIN provinces p ON a.province_id = p.id
            WHERE (a.title LIKE ? OR a.content LIKE ? OR a.excerpt LIKE ?)
            AND a.status = ?
        ';

        $params = ["%$query%", "%$query%", "%$query%", 'published'];

        if ($provinceId) {
            $sql .= ' AND a.province_id = ?';
            $params[] = $provinceId;
        }

        $sql .= ' ORDER BY a.created_at DESC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function incrementArticleViews($id) {
        $stmt = $this->pdo->prepare('UPDATE articles SET views = views + 1 WHERE id = ?');
        $stmt->execute([$id]);
    }

    // Metodi per sezioni home
    public function getHomeSections() {
        $stmt = $this->pdo->prepare('SELECT * FROM home_sections WHERE is_visible = 1 ORDER BY sort_order');
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // Metodi per eventi
    public function getUpcomingEvents($limit = 10) {
        $stmt = $this->pdo->prepare('
            SELECT e.*, c.name as category_name, p.name as province_name
            FROM events e
            LEFT JOIN categories c ON e.category_id = c.id
            LEFT JOIN provinces p ON e.province_id = p.id
            WHERE e.start_date >= NOW() AND e.status = ?
            ORDER BY e.start_date ASC
            LIMIT ?
        ');
        $stmt->execute(['active', $limit]);
        return $stmt->fetchAll();
    }

    // Metodi per business
    public function getBusinesses($limit = null, $onlyApproved = true) {
        $sql = '
            SELECT b.*, c.name as category_name, p.name as province_name
            FROM businesses b
            LEFT JOIN categories c ON b.category_id = c.id
            LEFT JOIN provinces p ON b.province_id = p.id
        ';

        if ($onlyApproved) {
            $sql .= ' WHERE b.status = ?';
        }

        $sql .= ' ORDER BY b.name';

        if ($limit) {
            $sql .= ' LIMIT ' . (int)$limit;
        }

        $stmt = $this->pdo->prepare($sql);
        if ($onlyApproved) {
            $stmt->execute(['approved']);
        } else {
            $stmt->execute();
        }
        return $stmt->fetchAll();
    }

    public function getBusinessById($id) {
        $stmt = $this->pdo->prepare('SELECT * FROM businesses WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function createBusiness($name, $email, $phone, $website, $description, $category_id, $province_id, $city_id, $address, $status) {
        $sql = "INSERT INTO businesses (name, email, phone, website, description, category_id, province_id, city_id, address, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$name, $email, $phone, $website, $description, $category_id, $province_id, $city_id, $address, $status]);
        return $this->pdo->lastInsertId();
    }

    public function updateBusiness($id, $name, $email, $phone, $website, $description, $category_id, $province_id, $city_id, $address, $status) {
        $sql = "UPDATE businesses SET name = ?, email = ?, phone = ?, website = ?, description = ?, category_id = ?, province_id = ?, city_id = ?, address = ?, status = ? WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$name, $email, $phone, $website, $description, $category_id, $province_id, $city_id, $address, $status, $id]);
        return $stmt->rowCount();
    }

    public function deleteBusiness($id) {
        $stmt = $this->pdo->prepare('DELETE FROM businesses WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->rowCount();
    }

    // Metodi per impostazioni
    public function getSettings() {
        $stmt = $this->pdo->prepare('SELECT * FROM settings ORDER BY `key`');
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getSetting($key) {
        $stmt = $this->pdo->prepare('SELECT value FROM settings WHERE `key` = ?');
        $stmt->execute([$key]);
        $result = $stmt->fetch();
        return $result ? $result['value'] : null;
    }

    public function setSetting($key, $value, $type = 'text') {
        $stmt = $this->pdo->prepare('
            INSERT INTO settings (`key`, value, type, updated_at)
            VALUES (?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE
            value = VALUES(value),
            type = VALUES(type),
            updated_at = NOW()
        ');
        $stmt->execute([$key, $value, $type]);
    }

    // Metodi per statistiche database
    public function getDatabaseHealth() {
        // Conteggi tabelle
        $tables = ['articles', 'categories', 'provinces', 'cities', 'comments', 'users', 'businesses', 'events', 'user_uploads', 'business_packages', 'settings', 'home_sections', 'static_pages'];
        $counts = [];

        foreach ($tables as $table) {
            $stmt = $this->pdo->prepare("SELECT COUNT(*) as count FROM `$table`");
            $stmt->execute();
            $counts[$table] = $stmt->fetch()['count'];
        }

        // Statistiche articoli
        $stmt = $this->pdo->prepare('SELECT COUNT(*) as count FROM articles WHERE featured = 1');
        $stmt->execute();
        $featuredArticles = $stmt->fetch()['count'];

        $stmt = $this->pdo->prepare('SELECT COUNT(*) as count FROM articles WHERE status = ?');
        $stmt->execute(['published']);
        $publishedArticles = $stmt->fetch()['count'];

        $stmt = $this->pdo->prepare('SELECT SUM(views) as total FROM articles');
        $stmt->execute();
        $totalViews = $stmt->fetch()['total'] ?: 0;

        // Informazioni database
        $stmt = $this->pdo->prepare("
            SELECT
                ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size_mb
            FROM information_schema.tables
            WHERE table_schema = ?
        ");
        $stmt->execute([$this->dbname]);
        $sizeInfo = $stmt->fetch();
        $sizeMB = $sizeInfo['size_mb'] ?: 0;

        return [
            'database' => [
                'path' => "mysql://{$this->host}/{$this->dbname}",
                'size' => $sizeMB . ' MB',
                'sizeBytes' => $sizeMB * 1024 * 1024,
                'lastModified' => date('c')
            ],
            'counts' => $counts,
            'statistics' => [
                'articles' => [
                    'total' => $counts['articles'],
                    'published' => $publishedArticles,
                    'featured' => $featuredArticles,
                    'totalViews' => $totalViews
                ]
            ],
            'health' => [
                'checks' => [
                    'databaseAccessible' => true,
                    'integrityOk' => true,
                    'hasCategories' => $counts['categories'] > 0,
                    'hasProvinces' => $counts['provinces'] > 0,
                    'hasCities' => $counts['cities'] > 0
                ]
            ]
        ];
    }

    // Backup del database MySQL
    public function createBackup() {
        $backupDir = dirname(__DIR__) . '/backups';
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        $timestamp = date('Y-m-d_H-i-s');
        $backupFile = $backupDir . "/passione_calabria_mysql_backup_$timestamp.sql";

        // Comando mysqldump per creare backup
        $command = "mysqldump --host={$this->host} --user={$this->username}";
        if ($this->password) {
            $command .= " --password={$this->password}";
        }
        $command .= " {$this->dbname} > $backupFile";

        $result = shell_exec($command);

        if (file_exists($backupFile) && filesize($backupFile) > 0) {
            return $backupFile;
        }

        return false;
    }

    public function getBackups() {
        $backupDir = dirname(__DIR__) . '/backups';
        if (!is_dir($backupDir)) {
            return [];
        }

        $backups = [];
        $files = glob($backupDir . '/*mysql*.sql');

        foreach ($files as $file) {
            $backups[] = [
                'filename' => basename($file),
                'size' => filesize($file),
                'created' => date('c', filemtime($file)),
                'sizeFormatted' => number_format(filesize($file) / (1024 * 1024), 2) . ' MB'
            ];
        }

        // Ordina per data decrescente
        usort($backups, function($a, $b) {
            return strtotime($b['created']) - strtotime($a['created']);
        });

        return $backups;
    }

    // Chiudi connessione
    public function __destruct() {
        $this->pdo = null;
    }
}
?>
