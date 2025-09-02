<?php
/**
 * Fix per business.php - Metodi mancanti nel database.php
 * Questo file contiene i metodi mancanti che devono essere aggiunti alla classe Database
 */

// METODI DA AGGIUNGERE ALLA CLASSE Database in includes/database.php:

/*
    // Metodi Business (da aggiungere dopo il metodo getBusinesses esistente)
    
    public function getBusinesses($limit = null, $includeAll = true) {
        $whereClause = $includeAll ? '' : 'WHERE b.status = ?';
        $sql = "
            SELECT b.*, c.name as category_name, p.name as province_name, ci.name as city_name
            FROM businesses b
            LEFT JOIN categories c ON b.category_id = c.id
            LEFT JOIN provinces p ON b.province_id = p.id
            LEFT JOIN cities ci ON b.city_id = ci.id
            $whereClause
            ORDER BY b.name
        ";

        if ($limit) {
            $sql .= ' LIMIT ' . (int)$limit;
        }

        $stmt = $this->pdo->prepare($sql);
        if ($includeAll) {
            $stmt->execute();
        } else {
            $stmt->execute(['approved']);
        }
        return $stmt->fetchAll();
    }

    public function getBusinessById($id) {
        $stmt = $this->pdo->prepare("
            SELECT b.*, c.name as category_name, p.name as province_name, ci.name as city_name
            FROM businesses b
            LEFT JOIN categories c ON b.category_id = c.id
            LEFT JOIN provinces p ON b.province_id = p.id
            LEFT JOIN cities ci ON b.city_id = ci.id
            WHERE b.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function createBusiness($name, $email, $phone, $website, $description, $category_id, $province_id, $city_id, $address, $status) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO businesses (name, email, phone, website, description, category_id, province_id, city_id, address, status, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, datetime('now'))
            ");
            return $stmt->execute([
                $name, $email, $phone, $website, $description,
                $category_id ?: null, $province_id ?: null, $city_id ?: null,
                $address, $status
            ]);
        } catch (Exception $e) {
            error_log("Error creating business: " . $e->getMessage());
            return false;
        }
    }

    public function updateBusiness($id, $name, $email, $phone, $website, $description, $category_id, $province_id, $city_id, $address, $status) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE businesses 
                SET name = ?, email = ?, phone = ?, website = ?, description = ?, 
                    category_id = ?, province_id = ?, city_id = ?, address = ?, status = ?,
                    updated_at = datetime('now')
                WHERE id = ?
            ");
            return $stmt->execute([
                $name, $email, $phone, $website, $description,
                $category_id ?: null, $province_id ?: null, $city_id ?: null,
                $address, $status, $id
            ]);
        } catch (Exception $e) {
            error_log("Error updating business: " . $e->getMessage());
            return false;
        }
    }

    public function deleteBusiness($id) {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM businesses WHERE id = ?");
            return $stmt->execute([$id]);
        } catch (Exception $e) {
            error_log("Error deleting business: " . $e->getMessage());
            return false;
        }
    }

*/
?>