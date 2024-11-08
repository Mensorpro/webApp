<?php
require_once 'db_connection.php';

class APIHandler {
    private $apis = [
        'newsapi' => [
            'endpoint' => 'https://newsapi.org/v2/everything',
            'key' => 'c605a4ec99d64971bcbdb671725a3f7d'  // Replace with your actual API key
        ],
        'nyt' => [
            'endpoint' => 'https://api.nytimes.com/svc/search/v2/articlesearch.json',
            'key' => 'tca763159-a4c7-4931-98d0-4da41563ebdb'   // Replace with your actual API key
        ],
        'guardian' => [
            'endpoint' => 'https://content.guardianapis.com/search',
            'key' => 'ca763159-a4c7-4931-98d0-4da41563ebdb'  // Replace with your actual API key
        ]
    ];

    public function fetchCommunityResources($zip) {
        // Fetch from FindHelp.org API
        $url = "https://api.findhelp.com/search?postal_code=" . urlencode($zip);
        return $this->makeAPIRequest($url);
    }

    public function fetchHealthNews() {
        try {
            // Try to get cached data first
            $cached = $this->getCachedResults('health_news');
            if ($cached) {
                return $cached;
            }
            
            // If no cache, try API calls
            foreach (['fetchFromNewsAPI', 'fetchFromNYT', 'fetchFromGuardian'] as $source) {
                try {
                    $result = $this->$source();
                    if (!empty($result['articles'])) {
                        $this->cacheResults('health_news', $result);
                        return $result;
                    }
                } catch (Exception $e) {
                    error_log("Error fetching from $source: " . $e->getMessage());
                    continue;
                }
            }
            
            // If all APIs fail, return fallback data
            return $this->getFallbackNews();
            
        } catch (Exception $e) {
            error_log("Error in fetchHealthNews: " . $e->getMessage());
            return $this->getFallbackNews();
        }
    }

    private function fetchFromNewsAPI() {
        $url = $this->apis['newsapi']['endpoint'] . "?" . http_build_query([
            'q' => 'health OR community OR support',
            'language' => 'en',
            'sortBy' => 'relevancy',
            'pageSize' => 5,
            'apiKey' => $this->apis['newsapi']['key']
        ]);
        
        return $this->makeAPIRequest($url);
    }

    private function fetchFromNYT() {
        $url = $this->apis['nyt']['endpoint'] . "?" . http_build_query([
            'q' => 'health community support',
            'sort' => 'relevance',
            'api-key' => $this->apis['nyt']['key']
        ]);
        
        $result = $this->makeAPIRequest($url);
        
        // Transform NYT response to match our format
        return [
            'articles' => array_map(function($article) {
                return [
                    'title' => $article['headline']['main'],
                    'description' => $article['abstract'],
                    'url' => $article['web_url']
                ];
            }, $result['response']['docs'])
        ];
    }

    private function fetchFromGuardian() {
        $url = $this->apis['guardian']['endpoint'] . "?" . http_build_query([
            'q' => 'health community support',
            'section' => 'society',
            'api-key' => $this->apis['guardian']['key']
        ]);
        
        $result = $this->makeAPIRequest($url);
        
        // Transform Guardian response to match our format
        return [
            'articles' => array_map(function($article) {
                return [
                    'title' => $article['webTitle'],
                    'description' => $article['webTitle'],
                    'url' => $article['webUrl']
                ];
            }, $result['response']['results'])
        ];
    }

    private function getCachedResults($type) {
        global $conn;
        
        try {
            // Skip table creation check since table already exists
            $sql = "SELECT data, timestamp FROM api_cache 
                    WHERE type = ? AND timestamp > DATE_SUB(NOW(), INTERVAL 1 HOUR)";
            
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                throw new Exception("Failed to prepare cache query: " . $conn->error);
            }
            
            if (!$stmt->bind_param("s", $type)) {
                throw new Exception("Failed to bind parameters");
            }
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to execute query");
            }
            
            $result = $stmt->get_result();
            if (!$result) {
                throw new Exception("Failed to get result");
            }
            
            $data = $result->fetch_assoc();
            $stmt->close();
            
            return $data ? json_decode($data['data'], true) : null;
            
        } catch (Exception $e) {
            error_log("Cache retrieval error: " . $e->getMessage());
            return null;
        }
    }

    public function fetchHealthTopics() {
        $url = $this->apis['healthcare']['endpoint'] . "?api_key=" . $this->apis['healthcare']['key'];
        return $this->makeAPIRequest($url);
    }

    private function makeAPIRequest($url) {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_USERAGENT => 'Community Help Center/1.0'
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if (curl_errno($ch) || $httpCode !== 200) {
            curl_close($ch);
            throw new Exception("API request failed: " . curl_error($ch));
        }
        
        curl_close($ch);
        return json_decode($response, true);
    }

    // Cache results in database
    private function cacheResults($type, $data) {
        global $conn;
        
        try {
            $sql = "INSERT INTO api_cache (type, data, timestamp) 
                    VALUES (?, ?, NOW()) 
                    ON DUPLICATE KEY UPDATE 
                    data = VALUES(data), 
                    timestamp = NOW()";
            
            $stmt = $conn->prepare($sql);
            if ($stmt === false) {
                throw new Exception("Failed to prepare cache insert: " . $conn->error);
            }
            
            $json_data = json_encode($data);
            $stmt->bind_param("ss", $type, $json_data);
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to cache results: " . $stmt->error);
            }
            
        } catch (Exception $e) {
            error_log("Cache error: " . $e->getMessage());
            // Continue without caching on error
        }
    }
}
