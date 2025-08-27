<?php
/ =====================================================
// 5. CLASSES/BUSINESSLISTING.PHP - Business Listing Management
// =====================================================

class BusinessListing {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
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
    
    // Get business listings with privacy controls
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
    
    // Get single business listing
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
    
    // Private helper methods
    private function getSelectFieldsByRole($userRole) {
        $baseFields = "bl.*, c.industry as company_industry, c.location_city, c.location_state";
        
        switch ($userRole) {
            case 'public':
                return $baseFields . ", u.full_name as founder_name, c.company_name";
            case 'registered':
                return $baseFields . ", u.full_name as founder_name, c.company_name, c.detailed_description";
            case 'paid':
                return $baseFields . ", u.full_name as founder_name, u.email as founder_email, c.*";
            default:
                return $baseFields . ", u.full_name as founder_name, c.company_name";
        }
    }
    
    private function maskSensitiveData($listings) {
        foreach ($listings as &$listing) {
            // Mask company name: "TechnoLogic Solutions" → "Te************* Solutions"
            if (isset($listing['company_name'])) {
                $listing['company_name'] = Utils::maskName($listing['company_name']);
            }
            
            // Mask founder name: "Rajesh Kumar" → "Ra**** Ku***"
            if (isset($listing['founder_name'])) {
                $listing['founder_name'] = Utils::maskName($listing['founder_name']);
            }
            
            // Remove sensitive fields
            unset($listing['founder_email'], $listing['website_url']);
        }
        
        return $listings;
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
        $accessLevel = ($userRole === 'paid') ? 'paid_only' : (($userRole === 'registered') ? 'registered' : 'public');
        
        $query = "SELECT * FROM uploaded_files WHERE business_listing_id = ? AND access_level IN ('public'";
        $params = [$listingId];
        
        if ($userRole === 'registered') {
            $query .= ", 'registered'";
        } elseif ($userRole === 'paid') {
            $query .= ", 'registered', 'paid_only'";
        }
        
        $query .= ") ORDER BY file_type, upload_date";
        
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
}