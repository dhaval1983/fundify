<?php

class BusinessListing {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    private function generateSlug($title) {
    $slug = strtolower(trim($title));
    $slug = preg_replace('/[^a-z0-9-]/', '-', $slug);
    $slug = preg_replace('/-+/', '-', $slug);
    $slug = trim($slug, '-');
    
    // Ensure uniqueness
    $originalSlug = $slug;
    $counter = 1;
    
    while (true) {
        $query = "SELECT id FROM business_listings WHERE slug = ?";
        $exists = $this->db->fetchOne($query, [$slug]);
        
        if (!$exists) {
            break;
        }
        
        $slug = $originalSlug . '-' . $counter;
        $counter++;
    }
    
    return $slug;
}
    
    // Get business listings with privacy controls and filtering
    public function getListings($userRole = 'public', $filters = [], $page = 1, $limit = 20) {
        $offset = ($page - 1) * $limit;
        $whereConditions = ["bl.status = 'active'"];
        $params = [];
        
        // Apply filters
        if (!empty($filters['industry'])) {
            $whereConditions[] = "bl.industry = ?";
            $params[] = $filters['industry'];
        }
        
        if (!empty($filters['business_stage'])) {
            $whereConditions[] = "bl.business_stage = ?";
            $params[] = $filters['business_stage'];
        }
        
        if (!empty($filters['min_funding'])) {
            $whereConditions[] = "bl.funding_amount_needed >= ?";
            $params[] = $filters['min_funding'];
        }
        
        if (!empty($filters['max_funding'])) {
            $whereConditions[] = "bl.funding_amount_needed <= ?";
            $params[] = $filters['max_funding'];
        }
        
        if (!empty($filters['location'])) {
            $whereConditions[] = "c.location_city = ?";
            $params[] = $filters['location'];
        }
        
        $whereClause = implode(' AND ', $whereConditions);
        
        // Select fields based on user role (privacy control)
        $selectFields = $this->getSelectFieldsByRole($userRole);
        
        $query = "SELECT {$selectFields} FROM business_listings bl 
                  JOIN companies c ON bl.company_id = c.id 
                  JOIN users u ON bl.user_id = u.id 
                  WHERE {$whereClause}
                  ORDER BY bl.is_featured DESC, bl.created_at DESC 
                  LIMIT ? OFFSET ?";
        
        $params[] = $limit;
        $params[] = $offset;
        
        $listings = $this->db->fetchAll($query, $params);
        
        // Apply name masking for non-registered users
        if ($userRole === 'public') {
            $listings = $this->maskSensitiveData($listings);
        }
        
        return $listings;
    }
    
    // Get single business listing by SLUG with privacy controls
public function getListingBySlug($slug, $userRole = 'public') {
    $selectFields = $this->getSelectFieldsByRole($userRole);
    
    $query = "SELECT {$selectFields} FROM business_listings bl 
              JOIN companies c ON bl.company_id = c.id 
              JOIN users u ON bl.user_id = u.id 
              WHERE bl.slug = ? AND bl.status = 'active'";
    
    $listing = $this->db->fetchOne($query, [$slug]);
    
    if (!$listing) return null;
    
    // Get team members
    $listing['team_members'] = $this->getTeamMembers($listing['id']);
    
    // Get uploaded files (based on access level)
    $listing['files'] = $this->getListingFiles($listing['id'], $userRole);
    
    // Apply privacy masking if needed
    if ($userRole === 'public') {
        $listing = $this->maskSensitiveData([$listing])[0];
    }
    
    // Track view
    $this->trackView($listing['id'], $_SESSION['user_id'] ?? null);
    
    return $listing;
}
    
    // Count total listings with filters
    public function countListings($filters = []) {
        $whereConditions = ["bl.status = 'active'"];
        $params = [];
        
        // Apply same filters as getListings
        if (!empty($filters['industry'])) {
            $whereConditions[] = "bl.industry = ?";
            $params[] = $filters['industry'];
        }
        
        if (!empty($filters['business_stage'])) {
            $whereConditions[] = "bl.business_stage = ?";
            $params[] = $filters['business_stage'];
        }
        
        if (!empty($filters['min_funding'])) {
            $whereConditions[] = "bl.funding_amount_needed >= ?";
            $params[] = $filters['min_funding'];
        }
        
        if (!empty($filters['max_funding'])) {
            $whereConditions[] = "bl.funding_amount_needed <= ?";
            $params[] = $filters['max_funding'];
        }
        
        if (!empty($filters['location'])) {
            $whereConditions[] = "c.location_city = ?";
            $params[] = $filters['location'];
        }
        
        $whereClause = implode(' AND ', $whereConditions);
        
        $query = "SELECT COUNT(*) as total FROM business_listings bl 
                  JOIN companies c ON bl.company_id = c.id 
                  WHERE {$whereClause}";
        
        $result = $this->db->fetchOne($query, $params);
        return (int)$result['total'];
    }
    
    // Get single business listing with privacy controls
    public function getListing($id, $userRole = 'public') {
        $selectFields = $this->getSelectFieldsByRole($userRole);
        
        $query = "SELECT {$selectFields} FROM business_listings bl 
                  JOIN companies c ON bl.company_id = c.id 
                  JOIN users u ON bl.user_id = u.id 
                  WHERE bl.id = ? AND bl.status = 'active'";
        
        $listing = $this->db->fetchOne($query, [$id]);
        
        if (!$listing) return null;
        
        // Get team members
        $listing['team_members'] = $this->getTeamMembers($id);
        
        // Get uploaded files (based on access level)
        $listing['files'] = $this->getListingFiles($id, $userRole);
        
        // Apply privacy masking if needed
        if ($userRole === 'public') {
            $listing = $this->maskSensitiveData([$listing])[0];
        }
        
        // Track view
        $this->trackView($id, $_SESSION['user_id'] ?? null);
        
        return $listing;
    }
    
    // Create new business listing
    public function create($data, $userId) {
        try {
            $this->db->beginTransaction();
            
            // Insert into business_listings
            $query = "INSERT INTO business_listings (
                company_id, user_id, title, short_pitch, detailed_description, 
                industry, business_stage, funding_amount_needed, current_monthly_revenue, 
                current_annual_revenue, equity_offered_min, equity_offered_max, 
                fund_usage_plan, target_market, revenue_model, status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $params = [
                $data['company_id'], $userId, $data['title'], $data['short_pitch'],
                $data['detailed_description'], $data['industry'], $data['business_stage'],
                $data['funding_amount_needed'], $data['current_monthly_revenue'] ?? 0,
                $data['current_annual_revenue'] ?? 0, $data['equity_offered_min'],
                $data['equity_offered_max'], $data['fund_usage_plan'], 
                $data['target_market'], $data['revenue_model'], 'active'
            ];
            
            $this->db->execute($query, $params);
            $listingId = $this->db->lastInsertId();
            
            // Add team members if provided
            if (!empty($data['team_members'])) {
                $this->addTeamMembers($listingId, $data['team_members']);
            }
            
            $this->db->commit();
            
            return ['success' => true, 'listing_id' => $listingId];
            
        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Business listing creation failed: " . $e->getMessage());
            return ['success' => false, 'error' => 'Failed to create business listing'];
        }
    }
    
    // Private helper methods
    private function getSelectFieldsByRole($userRole) {
        $baseFields = "bl.*, c.company_name, c.location_city, c.location_state, c.website_url, u.full_name as founder_name";
        
        switch ($userRole) {
            case 'public':
                return $baseFields;
            case 'registered':
                return $baseFields . ", u.email as founder_email, u.phone as founder_phone";
            case 'paid':
                return $baseFields . ", u.email as founder_email, u.phone as founder_phone, c.detailed_description as company_description";
            default:
                return $baseFields;
        }
    }
    
    private function maskSensitiveData($listings) {
        foreach ($listings as &$listing) {
            // Mask company name: "TechnoLogic Solutions" → "Te************* Solutions"
            if (isset($listing['company_name'])) {
                $listing['company_name'] = $this->maskName($listing['company_name']);
            }
            
            // Mask founder name: "Rajesh Kumar" → "Ra**** Ku***"
            if (isset($listing['founder_name'])) {
                $listing['founder_name'] = $this->maskName($listing['founder_name']);
            }
            
            // Remove sensitive fields
            unset($listing['founder_email'], $listing['founder_phone'], $listing['website_url']);
        }
        
        return $listings;
    }
    
    
    
    private function maskName($name) {
        if (strlen($name) <= 3) {
            return str_repeat('*', strlen($name));
        }
        
        $words = explode(' ', $name);
        $maskedWords = [];
        
        foreach ($words as $word) {
            if (strlen($word) <= 3) {
                $maskedWords[] = substr($word, 0, 1) . str_repeat('*', strlen($word) - 1);
            } else {
                $maskedWords[] = substr($word, 0, 2) . str_repeat('*', max(1, strlen($word) - 4)) . substr($word, -2);
            }
        }
        
        return implode(' ', $maskedWords);
    }
    
    private function addTeamMembers($listingId, $teamMembers) {
        $query = "INSERT INTO team_members (business_listing_id, name, role, experience, linkedin_url, is_founder, display_order) VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        foreach ($teamMembers as $index => $member) {
            $params = [
                $listingId,
                $member['name'],
                $member['role'],
                $member['experience'] ?? '',
                $member['linkedin_url'] ?? '',
                $member['is_founder'] ?? false,
                $index + 1
            ];
            
            $this->db->execute($query, $params);
        }
    }
    
    
    
    private function getTeamMembers($listingId) {
        $query = "SELECT * FROM team_members WHERE business_listing_id = ? ORDER BY display_order";
        return $this->db->fetchAll($query, [$listingId]);
    }
    
    private function getListingFiles($listingId, $userRole) {
        $accessLevels = ['public'];
        
        if ($userRole === 'registered') {
            $accessLevels[] = 'registered';
        } elseif ($userRole === 'paid') {
            $accessLevels[] = 'registered';
            $accessLevels[] = 'paid_only';
        }
        
        $placeholders = str_repeat('?,', count($accessLevels) - 1) . '?';
        
        $query = "SELECT * FROM uploaded_files 
                  WHERE business_listing_id = ? AND access_level IN ($placeholders)
                  ORDER BY file_type, upload_date";
        
        $params = array_merge([$listingId], $accessLevels);
        
        return $this->db->fetchAll($query, $params);
    }
    
    private function trackView($listingId, $userId = null) {
        $query = "INSERT INTO listing_views (business_listing_id, viewer_user_id, viewer_ip, viewer_user_agent) VALUES (?, ?, ?, ?)";
        
        $params = [
            $listingId,
            $userId,
            $_SERVER['REMOTE_ADDR'] ?? '',
            $_SERVER['HTTP_USER_AGENT'] ?? ''
        ];
        
        try {
            $this->db->execute($query, $params);
        } catch (Exception $e) {
            // Fail silently for view tracking
            error_log("View tracking failed: " . $e->getMessage());
        }
    }
    
    
    
    // Search listings with full-text search
    public function searchListings($searchTerm, $userRole = 'public', $filters = [], $page = 1, $limit = 20) {
        if (empty($searchTerm)) {
            return $this->getListings($userRole, $filters, $page, $limit);
        }
        
        $offset = ($page - 1) * $limit;
        $whereConditions = [
            "bl.status = 'active'",
            "MATCH(bl.title, bl.short_pitch, bl.detailed_description) AGAINST(? IN NATURAL LANGUAGE MODE)"
        ];
        $params = [$searchTerm];
        
        // Apply additional filters
        if (!empty($filters['industry'])) {
            $whereConditions[] = "bl.industry = ?";
            $params[] = $filters['industry'];
        }
        
        if (!empty($filters['business_stage'])) {
            $whereConditions[] = "bl.business_stage = ?";
            $params[] = $filters['business_stage'];
        }
        
        $whereClause = implode(' AND ', $whereConditions);
        $selectFields = $this->getSelectFieldsByRole($userRole);
        
        $query = "SELECT {$selectFields}, 
                  MATCH(bl.title, bl.short_pitch, bl.detailed_description) AGAINST(? IN NATURAL LANGUAGE MODE) as relevance
                  FROM business_listings bl 
                  JOIN companies c ON bl.company_id = c.id 
                  JOIN users u ON bl.user_id = u.id 
                  WHERE {$whereClause}
                  ORDER BY relevance DESC, bl.is_featured DESC
                  LIMIT ? OFFSET ?";
        
        $searchParams = array_merge([$searchTerm], $params, [$limit, $offset]);
        $listings = $this->db->fetchAll($query, $searchParams);
        
        if ($userRole === 'public') {
            $listings = $this->maskSensitiveData($listings);
        }
        
        return $listings;
    }
}