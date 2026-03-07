<?php
// api/models/SheetMusic.php
namespace SheetMusic\Models;

use PDO;

class SheetMusic
{
    private $conn;
    private $table = 'sheet_music';

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function getAll($filters = [], $sort = 'title', $order = 'ASC', $limit = 20, $offset = 0)
    {
        $query = "SELECT s.*, i.name as instrument_name, i.icon as instrument_icon,
                         c.name as category_name
                  FROM " . $this->table . " s
                  LEFT JOIN instruments i ON s.instrument_id = i.id
                  LEFT JOIN categories c ON s.category_id = c.id
                  WHERE 1=1";

        $params = [];

        // Apply filters
        if (!empty($filters['instrument'])) {
            $query .= " AND i.slug = :instrument";
            $params[':instrument'] = $filters['instrument'];
        }

        if (!empty($filters['difficulty'])) {
            $query .= " AND s.difficulty = :difficulty";
            $params[':difficulty'] = $filters['difficulty'];
        }

        if (!empty($filters['category'])) {
            $query .= " AND c.slug = :category";
            $params[':category'] = $filters['category'];
        }

        if (!empty($filters['search'])) {
            $query .= " AND (s.title LIKE :search OR s.composer LIKE :search)";
            $params[':search'] = '%' . $filters['search'] . '%';
        }

        if (!empty($filters['featured'])) {
            $query .= " AND s.is_featured = 1";
        }

        if (!empty($filters['premium'])) {
            $query .= " AND s.is_premium = 1";
        }

        if (!empty($filters['min_price'])) {
            $query .= " AND s.price >= :min_price";
            $params[':min_price'] = $filters['min_price'];
        }

        if (!empty($filters['max_price'])) {
            $query .= " AND s.price <= :max_price";
            $params[':max_price'] = $filters['max_price'];
        }

        // Apply sorting
        $allowedSortFields = ['title', 'price', 'rating', 'created_at', 'composer'];
        if (in_array($sort, $allowedSortFields)) {
            $order = strtoupper($order) === 'DESC' ? 'DESC' : 'ASC';
            $query .= " ORDER BY s." . $sort . " " . $order;
        }

        // Apply pagination
        $query .= " LIMIT :limit OFFSET :offset";
        $params[':limit'] = $limit;
        $params[':offset'] = $offset;

        $stmt = $this->conn->prepare($query);

        // Bind parameters
        foreach ($params as $key => &$value) {
            if ($key === ':limit' || $key === ':offset') {
                $stmt->bindParam($key, $value, PDO::PARAM_INT);
            } else {
                $stmt->bindParam($key, $value);
            }
        }

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById($id)
    {
        $query = "SELECT s.*, i.name as instrument_name, i.icon as instrument_icon,
                         c.name as category_name,
                         (SELECT AVG(rating) FROM reviews WHERE sheet_id = s.id) as avg_rating,
                         (SELECT COUNT(*) FROM reviews WHERE sheet_id = s.id) as total_reviews
                  FROM " . $this->table . " s
                  LEFT JOIN instruments i ON s.instrument_id = i.id
                  LEFT JOIN categories c ON s.category_id = c.id
                  WHERE s.id = :id
                  LIMIT 0,1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();

        // Increment views count
        $this->incrementViews($id);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function incrementViews($id)
    {
        $query = "UPDATE " . $this->table . " SET views_count = views_count + 1 WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    public function create($data)
    {
        $query = "INSERT INTO " . $this->table . "
                  SET title = :title,
                      composer = :composer,
                      description = :description,
                      instrument_id = :instrument_id,
                      category_id = :category_id,
                      difficulty = :difficulty,
                      price = :price,
                      pages = :pages,
                      format = :format,
                      file_path = :file_path,
                      cover_image = :cover_image,
                      sample_path = :sample_path,
                      is_featured = :is_featured,
                      is_premium = :is_premium";

        $stmt = $this->conn->prepare($query);

        // Sanitize and bind
        $stmt->bindParam(':title', $data['title']);
        $stmt->bindParam(':composer', $data['composer']);
        $stmt->bindParam(':description', $data['description']);
        $stmt->bindParam(':instrument_id', $data['instrument_id']);
        $stmt->bindParam(':category_id', $data['category_id']);
        $stmt->bindParam(':difficulty', $data['difficulty']);
        $stmt->bindParam(':price', $data['price']);
        $stmt->bindParam(':pages', $data['pages']);
        $stmt->bindParam(':format', $data['format']);
        $stmt->bindParam(':file_path', $data['file_path']);
        $stmt->bindParam(':cover_image', $data['cover_image']);
        $stmt->bindParam(':sample_path', $data['sample_path']);
        $stmt->bindParam(':is_featured', $data['is_featured']);
        $stmt->bindParam(':is_premium', $data['is_premium']);

        return $stmt->execute();
    }

    public function update($id, $data)
    {
        $query = "UPDATE " . $this->table . "
                  SET title = :title,
                    subtitle = :subtitle,
                      composer = :composer,
                      arranger = :arranger,
                      description = :description,
                      instrument_id = :instrument_id,
                      category_id = :category_id,
                      difficulty = :difficulty,
                      price = :price,
                      pages = :pages,
                      format = :format,
                      pdf_name = COALESCE(NULLIF(TRIM(:pdf_name), ''), pdf_name),
                      is_featured = :is_featured,
                      is_premium = :is_premium
                  WHERE id = :id";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':title', $data['title']);
        $stmt->bindParam(':subtitle', $data['subtitle']);
        $stmt->bindParam(':composer', $data['composer']);
        $stmt->bindParam(':arranger', $data['arranger']);
        $stmt->bindParam(':description', $data['description']);
        $stmt->bindParam(':instrument_id', $data['instrument_id']);
        $stmt->bindParam(':category_id', $data['category_id']);
        $stmt->bindParam(':difficulty', $data['difficulty']);
        $stmt->bindParam(':price', $data['price']);
        $stmt->bindParam(':pages', $data['pages']);
        $stmt->bindParam(':format', $data['format']);
        $pdfName = $data['pdf_name'] ?? null;
        $stmt->bindParam(':pdf_name', $pdfName);
        $stmt->bindParam(':is_featured', $data['is_featured']);
        $stmt->bindParam(':is_premium', $data['is_premium']);
        $stmt->bindParam(':id', $id);

        return $stmt->execute();
    }

    public function delete($id)
    {
        $query = "DELETE FROM " . $this->table . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    public function getReviews($sheet_id)
    {
        $query = "SELECT r.*, u.username, u.avatar 
                  FROM reviews r
                  JOIN users u ON r.user_id = u.id
                  WHERE r.sheet_id = :sheet_id
                  ORDER BY r.created_at DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':sheet_id', $sheet_id);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function addReview($user_id, $sheet_id, $rating, $comment)
    {
        // Check if user already reviewed
        $check_query = "SELECT id FROM reviews WHERE user_id = :user_id AND sheet_id = :sheet_id";
        $check_stmt = $this->conn->prepare($check_query);
        $check_stmt->bindParam(':user_id', $user_id);
        $check_stmt->bindParam(':sheet_id', $sheet_id);
        $check_stmt->execute();

        if ($check_stmt->rowCount() > 0) {
            // Update existing review
            $query = "UPDATE reviews 
                      SET rating = :rating, comment = :comment 
                      WHERE user_id = :user_id AND sheet_id = :sheet_id";
        } else {
            // Insert new review
            $query = "INSERT INTO reviews (user_id, sheet_id, rating, comment) 
                      VALUES (:user_id, :sheet_id, :rating, :comment)";
        }

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':sheet_id', $sheet_id);
        $stmt->bindParam(':rating', $rating);
        $stmt->bindParam(':comment', $comment);

        if ($stmt->execute()) {
            // Update average rating
            $this->updateAverageRating($sheet_id);
            return true;
        }
        return false;
    }

    private function updateAverageRating($sheet_id)
    {
        $query = "UPDATE " . $this->table . "
                  SET rating = (SELECT AVG(rating) FROM reviews WHERE sheet_id = :sheet_id),
                      reviews_count = (SELECT COUNT(*) FROM reviews WHERE sheet_id = :sheet_id)
                  WHERE id = :sheet_id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':sheet_id', $sheet_id);
        return $stmt->execute();
    }

    public function incrementDownloads($id)
    {
        $query = "UPDATE " . $this->table . " SET downloads_count = downloads_count + 1 WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    public function getRelated($id, $limit = 4)
    {
        $sheet = $this->getById($id);

        $query = "SELECT s.*, i.name as instrument_name
                  FROM " . $this->table . " s
                  LEFT JOIN instruments i ON s.instrument_id = i.id
                  WHERE s.id != :id 
                  AND (s.instrument_id = :instrument_id OR s.category_id = :category_id)
                  ORDER BY s.rating DESC
                  LIMIT :limit";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':instrument_id', $sheet['instrument_id']);
        $stmt->bindParam(':category_id', $sheet['category_id']);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getFeatured($limit = 6)
    {
        $query = "SELECT s.*, i.name as instrument_name
                  FROM " . $this->table . " s
                  LEFT JOIN instruments i ON s.instrument_id = i.id
                  WHERE s.is_featured = 1
                  ORDER BY s.created_at DESC
                  LIMIT :limit";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getPopular($limit = 6)
    {
        $query = "SELECT s.*, i.name as instrument_name
                  FROM " . $this->table . " s
                  LEFT JOIN instruments i ON s.instrument_id = i.id
                  ORDER BY s.downloads_count DESC, s.rating DESC
                  LIMIT :limit";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>