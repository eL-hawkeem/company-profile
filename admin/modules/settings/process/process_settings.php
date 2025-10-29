<?php

/**
 * Settings Processor Class
 * PT. Sarana Sentra Teknologi Utama - Admin Dashboard
 * 
 * Handles all logic processing for website settings management
 */
class SettingsProcessor
{
    private $db;
    public function __construct($database)
    {
        $this->db = $database;
    }
    /**
     * Handle all incoming requests
     */
    public function handleRequest($postData, $filesData)
    {
        try {
            $action = $postData['action'] ?? '';
            switch ($action) {
                case 'update_site_settings':
                    $this->updateSiteSettings($postData);
                    return ['message' => 'Pengaturan website berhasil diperbarui!', 'type' => 'success'];
                case 'update_banner':
                    $this->updateBanner($postData, $filesData);
                    return ['message' => 'Banner berhasil diperbarui!', 'type' => 'success'];
                case 'add_service':
                    $this->addService($postData, $filesData);
                    return ['message' => 'Layanan berhasil ditambahkan!', 'type' => 'success'];
                case 'update_service':
                    $this->updateService($postData, $filesData);
                    return ['message' => 'Layanan berhasil diperbarui!', 'type' => 'success'];
                case 'delete_service':
                    $this->deleteService($postData['id']);
                    return ['message' => 'Layanan berhasil dihapus!', 'type' => 'success'];
                case 'add_team_member':
                    $this->addTeamMember($postData, $filesData);
                    return ['message' => 'Anggota tim berhasil ditambahkan!', 'type' => 'success'];
                case 'update_team_member':
                    $this->updateTeamMember($postData, $filesData);
                    return ['message' => 'Anggota tim berhasil diperbarui!', 'type' => 'success'];
                case 'delete_team_member':
                    $this->deleteTeamMember($postData['id']);
                    return ['message' => 'Anggota tim berhasil dihapus!', 'type' => 'success'];
                case 'add_testimonial':
                    $this->addTestimonial($postData, $filesData);
                    return ['message' => 'Testimonial berhasil ditambahkan!', 'type' => 'success'];
                case 'update_testimonial':
                    $this->updateTestimonial($postData, $filesData);
                    return ['message' => 'Testimonial berhasil diperbarui!', 'type' => 'success'];
                case 'delete_testimonial':
                    $this->deleteTestimonial($postData['id']);
                    return ['message' => 'Testimonial berhasil dihapus!', 'type' => 'success'];
                default:
                    return ['message' => 'Aksi tidak dikenali!', 'type' => 'danger'];
            }
        } catch (Exception $e) {
            error_log('Settings Error: ' . $e->getMessage());
            return ['message' => 'Terjadi kesalahan: ' . $e->getMessage(), 'type' => 'danger'];
        }
    }
    /**
     * Get all data needed for the settings page
     */
    public function getAllData()
    {
        return [
            'siteSettings' => $this->getSiteSettings(),
            'banners' => $this->getBanners(),
            'services' => $this->getServices(),
            'teamMembers' => $this->getTeamMembers(),
            'testimonials' => $this->getTestimonials()
        ];
    }
    /**
     * Get site settings
     */
    private function getSiteSettings()
    {
        $settings = [];
        $result = $this->db->fetchAll("SELECT setting_key, setting_value FROM site_settings");
        foreach ($result as $row) {
            $value = $row['setting_value'];
            $decoded = json_decode($value, true);
            $settings[$row['setting_key']] = $decoded !== null ? $decoded : $value;
        }
        return $settings;
    }
    /**
     * Get banners
     */
    private function getBanners()
    {
        $banners = $this->db->fetchAll("SELECT id, title, subtitle, button_text, button_link, image_path, is_active FROM banners ORDER BY id");
        foreach ($banners as &$banner) {
            $imageData = $this->db->fetchOne("SELECT image_data FROM banners WHERE id = ?", [$banner['id']]);
            if ($imageData && $imageData['image_data']) {
                $banner['image_data'] = base64_encode($imageData['image_data']);
            } else {
                $banner['image_data'] = null;
            }
        }
        return $banners;
    }
    /**
     * Get services
     */
    private function getServices()
    {
        $services = $this->db->fetchAll("SELECT id, service_slug, title, description, image_path FROM services ORDER BY id");
        foreach ($services as &$service) {
            $imageData = $this->db->fetchOne("SELECT image_data, features FROM services WHERE id = ?", [$service['id']]);
            if ($imageData && $imageData['image_data']) {
                $service['image_data'] = base64_encode($imageData['image_data']);
            } else {
                $service['image_data'] = null;
            }
            if ($imageData && $imageData['features']) {
                $decodedFeatures = json_decode($imageData['features'], true);
                $service['features'] = is_array($decodedFeatures) ? $decodedFeatures : [];
            } else {
                $service['features'] = [];
            }
        }
        return $services;
    }
    /**
     * Get team members
     */
    private function getTeamMembers()
    {
        $teamMembers = $this->db->fetchAll("SELECT id, name, position, image_path, display_order FROM team_members ORDER BY display_order, id");
        foreach ($teamMembers as &$member) {
            $imageData = $this->db->fetchOne("SELECT image_data FROM team_members WHERE id = ?", [$member['id']]);
            if ($imageData && $imageData['image_data']) {
                $member['image_data'] = base64_encode($imageData['image_data']);
            } else {
                $member['image_data'] = null;
            }
        }
        return $teamMembers;
    }
    /**
     * Get testimonials
     */
    private function getTestimonials()
    {
        return $this->db->fetchAll("SELECT * FROM testimonials ORDER BY id DESC");
    }
    /**
     * Update site settings
     */
    private function updateSiteSettings($data)
    {
        $this->validateSiteSettings($data);
        $settings = [
            'visi' => $this->sanitizeInput($data['visi'] ?? ''),
            'misi' => json_encode($this->sanitizeMisiArray($data['misi'] ?? [])),
            'contact_address' => $this->sanitizeInput($data['contact_address'] ?? ''),
            'contact_phone' => $this->sanitizeInput($data['contact_phone'] ?? ''),
            'contact_whatsapp' => $this->sanitizeInput($data['contact_whatsapp'] ?? ''),
            'contact_email' => filter_var($data['contact_email'] ?? '', FILTER_SANITIZE_EMAIL),
            'contact_hours' => $this->sanitizeInput($data['contact_hours'] ?? ''),
            'Maps_url' => $this->sanitizeUrl($data['Maps_url'] ?? ''),
            'cta_title' => $this->sanitizeInput($data['cta_title'] ?? ''),
            'cta_text' => $this->sanitizeInput($data['cta_text'] ?? ''),
            'cta_button_text' => $this->sanitizeInput($data['cta_button_text'] ?? ''),
            'cta_button_link' => $this->sanitizeUrl($data['cta_button_link'] ?? ''),
            'about_title' => $this->sanitizeInput($data['about_title'] ?? ''),
            'about_text' => $this->sanitizeInput($data['about_text'] ?? ''),
            'about_features' => json_encode($this->sanitizeArrayInput($data['about_features'] ?? []))
        ];
        foreach ($settings as $key => $value) {
            $this->db->query(
                "INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?) 
                 ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)",
                [$key, $value]
            );
        }
    }
    /**
     * Update banner
     */
    private function updateBanner($data, $files)
    {
        $this->validateBannerData($data);
        $bannerId = intval($data['banner_id']);
        $updateData = [
            'title' => $this->sanitizeInput($data['title']),
            'subtitle' => $this->sanitizeInput($data['subtitle']),
            'button_text' => $this->sanitizeInput($data['button_text']),
            'button_link' => $this->sanitizeUrl($data['button_link'] ?? ''),
            'is_active' => isset($data['is_active']) ? 1 : 0
        ];
        // Handle image upload
        if (isset($files['banner_image']) && $files['banner_image']['size'] > 0) {
            $imagePath = $this->handleImageUpload($files['banner_image'], 'banners', $bannerId);
            $updateData['image_path'] = $imagePath;
            $updateData['image_data'] = null;
            $oldBanner = $this->db->fetchOne("SELECT image_path FROM banners WHERE id = ?", [$bannerId]);
            if ($oldBanner && $oldBanner['image_path']) {
                $this->deleteImage($oldBanner['image_path'], 'banners');
            }
        }
        $setParts = [];
        $params = [];
        foreach ($updateData as $column => $value) {
            $setParts[] = "$column = ?";
            $params[] = $value;
        }
        $params[] = $bannerId;
        $sql = "UPDATE banners SET " . implode(', ', $setParts) . " WHERE id = ?";
        $this->db->query($sql, $params);
    }
    /**
     * Add service
     */
    private function addService($data, $files)
    {
        $this->validateServiceData($data);
        $insertData = [
            'service_slug' => $this->sanitizeSlug($data['service_slug']),
            'title' => $this->sanitizeInput($data['title']),
            'description' => $this->sanitizeInput($data['description']),
            'features' => json_encode($this->sanitizeArrayInput($data['features'] ?? []))
        ];
        if (isset($files['service_image']) && $files['service_image']['size'] > 0) {
            $result = $this->db->fetchOne("SELECT AUTO_INCREMENT FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'services'");
            $nextId = $result ? $result['AUTO_INCREMENT'] : null;
            $imagePath = $this->handleImageUpload($files['service_image'], 'services', $nextId);
            $insertData['image_path'] = $imagePath;
            $insertData['image_data'] = null; // Clear BLOB data when using file path
        }
        $this->db->insert('services', $insertData);
    }
    /**
     * Update service
     */
    private function updateService($data, $files)
    {
        $this->validateServiceData($data);
        $serviceId = intval($data['service_id']);
        $updateData = [
            'service_slug' => $this->sanitizeSlug($data['service_slug']),
            'title' => $this->sanitizeInput($data['title']),
            'description' => $this->sanitizeInput($data['description']),
            'features' => json_encode($this->sanitizeArrayInput($data['features'] ?? []))
        ];
        if (isset($files['service_image']) && $files['service_image']['size'] > 0) {
            // Delete old image
            $oldService = $this->db->fetchOne("SELECT image_path FROM services WHERE id = ?", [$serviceId]);
            if ($oldService && $oldService['image_path']) {
                $this->deleteImage($oldService['image_path'], 'services');
            }
            $imagePath = $this->handleImageUpload($files['service_image'], 'services', $serviceId);
            $updateData['image_path'] = $imagePath;
            $updateData['image_data'] = null;
        }
        $setParts = [];
        $params = [];
        foreach ($updateData as $column => $value) {
            $setParts[] = "$column = ?";
            $params[] = $value;
        }
        $params[] = $serviceId;
        $sql = "UPDATE services SET " . implode(', ', $setParts) . " WHERE id = ?";
        $this->db->query($sql, $params);
    }
    /**
     * Delete service
     */
    private function deleteService($id)
    {
        $id = intval($id);
        $service = $this->db->fetchOne("SELECT image_path FROM services WHERE id = ?", [$id]);
        if ($service && $service['image_path']) {
            $this->deleteImage($service['image_path'], 'services');
        }
        $this->db->query("DELETE FROM services WHERE id = ?", [$id]);
    }
    /**
     * Add team member
     */
    private function addTeamMember($data, $files)
    {
        $this->validateTeamMemberData($data);
        $insertData = [
            'name' => $this->sanitizeInput($data['name']),
            'position' => $this->sanitizeInput($data['position']),
            'display_order' => intval($data['display_order'])
        ];
        if (isset($files['team_image']) && $files['team_image']['size'] > 0) {
            $nextId = $this->db->fetchColumn("SELECT AUTO_INCREMENT FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'team_members'");
            $imagePath = $this->handleImageUpload($files['team_image'], 'team', $nextId);
            $insertData['image_path'] = $imagePath;
            $insertData['image_data'] = null;
        }
        $this->db->insert('team_members', $insertData);
    }
    /**
     * Update team member
     */
    private function updateTeamMember($data, $files)
    {
        $this->validateTeamMemberData($data);
        $memberId = intval($data['member_id']);
        $updateData = [
            'name' => $this->sanitizeInput($data['name']),
            'position' => $this->sanitizeInput($data['position']),
            'display_order' => intval($data['display_order'])
        ];
        if (isset($files['team_image']) && $files['team_image']['size'] > 0) {
            // Delete old image
            $oldMember = $this->db->fetchOne("SELECT image_path FROM team_members WHERE id = ?", [$memberId]);
            if ($oldMember && $oldMember['image_path']) {
                $this->deleteImage($oldMember['image_path'], 'team');
            }
            $imagePath = $this->handleImageUpload($files['team_image'], 'team', $memberId);
            $updateData['image_path'] = $imagePath;
            $updateData['image_data'] = null;
        }
        $setParts = [];
        $params = [];
        foreach ($updateData as $column => $value) {
            $setParts[] = "$column = ?";
            $params[] = $value;
        }
        $params[] = $memberId;
        $sql = "UPDATE team_members SET " . implode(', ', $setParts) . " WHERE id = ?";
        $this->db->query($sql, $params);
    }
    /**
     * Delete team member
     */
    private function deleteTeamMember($id)
    {
        $id = intval($id);
        $member = $this->db->fetchOne("SELECT image_path FROM team_members WHERE id = ?", [$id]);
        if ($member && $member['image_path']) {
            $this->deleteImage($member['image_path'], 'team');
        }
        $this->db->query("DELETE FROM team_members WHERE id = ?", [$id]);
    }
    /**
     * Add testimonial
     */
    private function addTestimonial($data, $files)
    {
        $this->validateTestimonialData($data);
        $insertData = [
            'client_name' => $this->sanitizeInput($data['client_name']),
            'client_position' => $this->sanitizeInput($data['client_position']),
            'quote' => $this->sanitizeInput($data['quote']),
            'is_active' => isset($data['is_active']) ? 1 : 0
        ];
        if (isset($files['testimonial_image']) && $files['testimonial_image']['size'] > 0) {
            $nextId = $this->db->fetchColumn("SELECT AUTO_INCREMENT FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'testimonials'");
            $insertData['image_path'] = $this->handleImageUpload($files['testimonial_image'], 'testimonials', $nextId);
        }
        $this->db->insert('testimonials', $insertData);
    }
    /**
     * Update testimonial
     */
    private function updateTestimonial($data, $files)
    {
        $this->validateTestimonialData($data);
        $testimonialId = intval($data['testimonial_id']);
        $updateData = [
            'client_name' => $this->sanitizeInput($data['client_name']),
            'client_position' => $this->sanitizeInput($data['client_position']),
            'quote' => $this->sanitizeInput($data['quote']),
            'is_active' => isset($data['is_active']) ? 1 : 0
        ];
        if (isset($files['testimonial_image']) && $files['testimonial_image']['size'] > 0) {
            $oldTestimonial = $this->db->fetchOne("SELECT image_path FROM testimonials WHERE id = ?", [$testimonialId]);
            if ($oldTestimonial && $oldTestimonial['image_path']) {
                $this->deleteImage($oldTestimonial['image_path'], 'testimonials');
            }
            $updateData['image_path'] = $this->handleImageUpload($files['testimonial_image'], 'testimonials', $testimonialId);
        }
        $setParts = [];
        $params = [];
        foreach ($updateData as $column => $value) {
            $setParts[] = "$column = ?";
            $params[] = $value;
        }
        $params[] = $testimonialId;
        $sql = "UPDATE testimonials SET " . implode(', ', $setParts) . " WHERE id = ?";
        $this->db->query($sql, $params);
    }
    /**
     * Delete testimonial
     */
    private function deleteTestimonial($id)
    {
        $id = intval($id);
        $testimonial = $this->db->fetchOne("SELECT image_path FROM testimonials WHERE id = ?", [$id]);
        if ($testimonial && $testimonial['image_path']) {
            $this->deleteImage($testimonial['image_path'], 'testimonials');
        }
        $this->db->query("DELETE FROM testimonials WHERE id = ?", [$id]);
    }
    /**
     * Handle image upload - 
     */
    private function handleImageUpload($file, $folder, $recordId)
    {
        $this->validateImageFile($file);
        $uploadDir = __DIR__ . '/../../../uploads/' . $folder . '/';
        if (!is_dir($uploadDir)) {
            if (!mkdir($uploadDir, 0755, true)) {
                throw new Exception('Gagal membuat direktori upload.');
            }
        }
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $fileName = $folder . '_' . $recordId . '_' . time() . '.' . $extension;
        $uploadPath = $uploadDir . $fileName;
        if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
            throw new Exception('Gagal mengupload file');
        }
        $this->optimizeImage($uploadPath, $extension);
        return $fileName;
    }
    /**
     * Delete image file
     */
    private function deleteImage($imagePath, $folder)
    {
        $fileName = basename($imagePath);
        $fullPath = __DIR__ . '/../../../uploads/' . $folder . '/' . $fileName;
        if (file_exists($fullPath)) {
            unlink($fullPath);
        }
    }
    /**
     * Optimize uploaded image
     */
    private function optimizeImage($imagePath, $extension)
    {
        $maxWidth = 1200;
        $maxHeight = 800;
        $quality = 85;
        switch ($extension) {
            case 'jpg':
            case 'jpeg':
                $image = imagecreatefromjpeg($imagePath);
                break;
            case 'png':
                $image = imagecreatefrompng($imagePath);
                break;
            case 'gif':
                $image = imagecreatefromgif($imagePath);
                break;
            default:
                return;
        }
        if (!$image) return;
        $originalWidth = imagesx($image);
        $originalHeight = imagesy($image);
        if ($originalWidth > $maxWidth || $originalHeight > $maxHeight) {
            $ratio = min($maxWidth / $originalWidth, $maxHeight / $originalHeight);
            $newWidth = intval($originalWidth * $ratio);
            $newHeight = intval($originalHeight * $ratio);
            $resizedImage = imagecreatetruecolor($newWidth, $newHeight);
            if ($extension == 'png' || $extension == 'gif') {
                imagealphablending($resizedImage, false);
                imagesavealpha($resizedImage, true);
                $transparent = imagecolorallocatealpha($resizedImage, 255, 255, 255, 127);
                imagefill($resizedImage, 0, 0, $transparent);
            }
            imagecopyresampled($resizedImage, $image, 0, 0, 0, 0, $newWidth, $newHeight, $originalWidth, $originalHeight);
            switch ($extension) {
                case 'jpg':
                case 'jpeg':
                    imagejpeg($resizedImage, $imagePath, $quality);
                    break;
                case 'png':
                    imagepng($resizedImage, $imagePath, intval($quality / 10));
                    break;
                case 'gif':
                    imagegif($resizedImage, $imagePath);
                    break;
            }
            imagedestroy($resizedImage);
        }
        imagedestroy($image);
    }
    /**
     * Validate image file
     */
    private function validateImageFile($file)
    {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Error uploading file');
        }
        $maxSize = 5 * 1024 * 1024;
        if ($file['size'] > $maxSize) {
            throw new Exception('Ukuran file terlalu besar. Maksimal 5MB');
        }
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        if (!in_array($mimeType, $allowedTypes)) {
            throw new Exception('Jenis file tidak didukung. Hanya JPEG, PNG, dan GIF yang diizinkan');
        }
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, $allowedExtensions)) {
            throw new Exception('Ekstensi file tidak didukung');
        }
    }
    /**
     * Sanitize input data
     */
    private function sanitizeInput($input)
    {
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
    /**
     * Sanitize URL
     */
    private function sanitizeUrl($url)
    {
        $url = trim($url);
        if (empty($url)) {
            return '';
        }
        if (strpos($url, '/') === 0 || filter_var($url, FILTER_VALIDATE_URL)) {
            return filter_var($url, FILTER_SANITIZE_URL);
        }
        if (preg_match('/^[a-zA-Z0-9_\-\.]+\.(php|html)$/i', $url)) {
            return $this->sanitizeInput($url);
        }
        if (strpos($url, '#') === 0) {
            return $this->sanitizeInput($url);
        }
        return filter_var($url, FILTER_SANITIZE_URL);
    }
    /**
     * Sanitize slug
     */
    private function sanitizeSlug($slug)
    {
        $slug = trim($slug);
        $slug = strtolower($slug);
        $slug = preg_replace('/\s+/', '-', $slug);
        $slug = preg_replace('/[^a-z0-9\-]/', '', $slug);
        $slug = preg_replace('/-+/', '-', $slug);
        return $slug;
    }
    /**
     * Validate URL - more flexible validation
     */
    private function isValidUrl($url)
    {
        if (empty($url)) {
            return true;
        }
        if (strpos($url, '/') === 0) {
            return true;
        }
        if (filter_var($url, FILTER_VALIDATE_URL)) {
            return true;
        }
        if (preg_match('/^[a-zA-Z0-9_\-\.]+\.(php|html)$/i', $url)) {
            return true;
        }
        if (strpos($url, '#') === 0) {
            return true;
        }
        return false;
    }
    /**
     * Sanitize array input
     */
    private function sanitizeArrayInput($array)
    {
        if (!is_array($array)) return [];
        return array_filter(array_map([$this, 'sanitizeInput'], $array));
    }
    /**
     * Sanitize misi array
     */
    private function sanitizeMisiArray($array)
    {
        if (!is_array($array)) return [];
        $cleaned = array_filter(array_map([$this, 'sanitizeInput'], $array));
        return array_values($cleaned);
    }
    /**
     * Validate site settings data
     */
    private function validateSiteSettings($data)
    {
        if (empty($data['visi'])) {
            throw new Exception('Visi perusahaan harus diisi');
        }
        if (empty($data['misi']) || !is_array($data['misi']) || count(array_filter($data['misi'])) == 0) {
            throw new Exception('Minimal satu misi perusahaan harus diisi');
        }
        if (!empty($data['contact_email']) && !filter_var($data['contact_email'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Format email tidak valid');
        }
        if (!empty($data['Maps_url']) && !$this->isValidUrl($data['Maps_url'])) {
            throw new Exception('Format URL Maps tidak valid');
        }
        if (!empty($data['cta_button_link']) && !$this->isValidUrl($data['cta_button_link'])) {
            throw new Exception('Format URL tombol CTA tidak valid');
        }
    }
    /**
     * Validate banner data
     */
    private function validateBannerData($data)
    {
        if (empty($data['title'])) {
            throw new Exception('Judul banner harus diisi');
        }
        // Validate button link with flexible validation
        if (!empty($data['button_link']) && !$this->isValidUrl($data['button_link'])) {
            throw new Exception('Format URL tombol banner tidak valid');
        }
        if (empty($data['banner_id']) || !is_numeric($data['banner_id'])) {
            throw new Exception('ID banner tidak valid');
        }
    }
    /**
     * Validate service data
     */
    private function validateServiceData($data)
    {
        if (empty($data['service_slug'])) {
            throw new Exception('Slug layanan harus diisi');
        }
        if (!preg_match('/^[a-z0-9\-]+$/', $data['service_slug'])) {
            throw new Exception('Slug hanya boleh mengandung huruf kecil, angka, dan tanda hubung');
        }
        if (empty($data['title'])) {
            throw new Exception('Judul layanan harus diisi');
        }
        if (empty($data['description'])) {
            throw new Exception('Deskripsi layanan harus diisi');
        }
        $excludeId = isset($data['service_id']) ? intval($data['service_id']) : 0;
        $existing = $this->db->fetchOne(
            "SELECT id FROM services WHERE service_slug = ? AND id != ?",
            [$data['service_slug'], $excludeId]
        );
        if ($existing) {
            throw new Exception('Slug layanan sudah digunakan');
        }
    }
    /**
     * Validate team member data
     */
    private function validateTeamMemberData($data)
    {
        if (empty($data['name'])) {
            throw new Exception('Nama anggota tim harus diisi');
        }
        if (empty($data['position'])) {
            throw new Exception('Posisi/jabatan harus diisi');
        }
        if (!isset($data['display_order']) || !is_numeric($data['display_order']) || $data['display_order'] < 1) {
            throw new Exception('Urutan tampil harus berupa angka positif');
        }
        $excludeId = isset($data['member_id']) ? intval($data['member_id']) : 0;
        $existing = $this->db->fetchOne(
            "SELECT id FROM team_members WHERE display_order = ? AND id != ?",
            [intval($data['display_order']), $excludeId]
        );
        if ($existing) {
            throw new Exception('Urutan tampil sudah digunakan oleh anggota tim lain');
        }
    }
    /**
     * Validate testimonial data
     */
    private function validateTestimonialData($data)
    {
        if (empty($data['client_name'])) {
            throw new Exception('Nama klien harus diisi');
        }
        if (empty($data['quote'])) {
            throw new Exception('Testimonial harus diisi');
        }
        if (strlen($data['quote']) < 10) {
            throw new Exception('Testimonial minimal 10 karakter');
        }
        if (strlen($data['quote']) > 1000) {
            throw new Exception('Testimonial maksimal 1000 karakter');
        }
    }
    /**
     * Log activity for audit trail
     */
    private function logActivity($action, $details = '')
    {
        try {
            $userId = $_SESSION['user_id'] ?? 0;
            $this->db->insert('activity_logs', [
                'user_id' => $userId,
                'action' => $action,
                'details' => $details,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'created_at' => date('Y-m-d H:i:s')
            ]);
        } catch (Exception $e) {
            error_log('Failed to log activity: ' . $e->getMessage());
        }
    }
    /**
     * Clean old unused images (for maintenance)
     */
    public function cleanUnusedImages()
    {
        $folders = ['banners', 'team', 'testimonials', 'services'];
        $cleanedFiles = 0;
        foreach ($folders as $folder) {
            $uploadDir = __DIR__ . '/../../../uploads/' . $folder . '/';
            if (!is_dir($uploadDir)) continue;
            $files = scandir($uploadDir);
            foreach ($files as $file) {
                if (in_array($file, ['.', '..'])) continue;
                $filePath = $folder . '/' . $file;
                $isUsed = false;
                switch ($folder) {
                    case 'banners':
                        $isUsed = $this->db->fetchOne("SELECT id FROM banners WHERE image_path = ?", [$filePath]);
                        break;
                    case 'team':
                        $isUsed = $this->db->fetchOne("SELECT id FROM team_members WHERE image_path = ?", [$filePath]);
                        break;
                    case 'testimonials':
                        $isUsed = $this->db->fetchOne("SELECT id FROM testimonials WHERE image_path = ?", [$filePath]);
                        break;
                    case 'services':
                        $isUsed = $this->db->fetchOne("SELECT id FROM services WHERE image_path = ?", [$filePath]);
                        break;
                }
                if (!$isUsed) {
                    unlink($uploadDir . $file);
                    $cleanedFiles++;
                }
            }
        }
        return $cleanedFiles;
    }
    /**
     * Get statistics for dashboard
     */
    public function getStatistics()
    {
        return [
            'total_team_members' => $this->db->fetchColumn("SELECT COUNT(*) FROM team_members"),
            'active_testimonials' => $this->db->fetchColumn("SELECT COUNT(*) FROM testimonials WHERE is_active = 1"),
            'total_testimonials' => $this->db->fetchColumn("SELECT COUNT(*) FROM testimonials"),
            'total_banners' => $this->db->fetchColumn("SELECT COUNT(*) FROM banners"),
            'total_services' => $this->db->fetchColumn("SELECT COUNT(*) FROM services"),
            'storage_used' => $this->calculateStorageUsed()
        ];
    }
    /**
     * Calculate storage used by uploads
     */
    private function calculateStorageUsed()
    {
        $folders = ['banners', 'team', 'testimonials', 'services'];
        $totalSize = 0;
        foreach ($folders as $folder) {
            $uploadDir = __DIR__ . '/../../../uploads/' . $folder . '/';
            if (!is_dir($uploadDir)) continue;
            $files = scandir($uploadDir);
            foreach ($files as $file) {
                if (in_array($file, ['.', '..'])) continue;
                $totalSize += filesize($uploadDir . $file);
            }
        }
        return round($totalSize / (1024 * 1024), 2);
    }
}
