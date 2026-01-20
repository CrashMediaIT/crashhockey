<?php
/**
 * Feature Importer Utility Class
 * Imports packaged features with validation, database migrations, and rollback support
 */

require_once __DIR__ . '/system_validator.php';

class FeatureImporter {
    private $pdo;
    private $base_path;
    private $upload_dir;
    private $backup_dir;
    private $log = [];
    
    public function __construct($pdo, $base_path = null) {
        $this->pdo = $pdo;
        $this->base_path = $base_path ?? __DIR__ . '/..';
        $this->upload_dir = $this->base_path . '/tmp/feature_imports';
        $this->backup_dir = $this->base_path . '/tmp/feature_backups';
        
        // Create directories if they don't exist
        if (!file_exists($this->upload_dir)) {
            mkdir($this->upload_dir, 0755, true);
        }
        if (!file_exists($this->backup_dir)) {
            mkdir($this->backup_dir, 0755, true);
        }
    }
    
    /**
     * Import feature from ZIP package
     */
    public function importFeature($zip_file_path) {
        $this->log = [];
        $extract_path = null;
        $backup_id = 'backup_' . time();
        
        try {
            $this->addLog('Starting feature import...', 'info');
            
            // Validate ZIP file
            if (!file_exists($zip_file_path)) {
                throw new Exception('ZIP file not found');
            }
            
            // Extract ZIP
            $this->addLog('Extracting ZIP package...', 'info');
            $extract_path = $this->extractZip($zip_file_path);
            
            // Load and validate manifest
            $this->addLog('Loading manifest...', 'info');
            $manifest = $this->loadManifest($extract_path);
            
            // Validate manifest structure
            $this->validateManifest($manifest);
            
            // Run system validation if required
            if ($manifest['requires_validation'] ?? true) {
                $this->addLog('Running system validation...', 'info');
                $this->runSystemValidation();
            }
            
            // Create backup of files that will be modified
            $this->addLog('Creating backup...', 'info');
            $this->createBackup($manifest, $backup_id);
            
            // Begin database transaction
            $this->pdo->beginTransaction();
            
            try {
                // Execute database migrations
                if (!empty($manifest['database_migrations'])) {
                    $this->addLog('Running database migrations...', 'info');
                    $this->runMigrations($manifest['database_migrations'], $extract_path);
                }
                
                // Create directories
                if (!empty($manifest['directories'])) {
                    $this->addLog('Creating directories...', 'info');
                    $this->createDirectories($manifest['directories']);
                }
                
                // Process files (create, update, delete)
                $this->addLog('Processing files...', 'info');
                $this->processFiles($manifest['files'], $extract_path);
                
                // Update navigation
                if (!empty($manifest['navigation'])) {
                    $this->addLog('Updating navigation...', 'info');
                    $this->updateNavigation($manifest['navigation']);
                }
                
                // Commit database transaction
                $this->pdo->commit();
                
                // Clean up extracted files
                $this->cleanupExtractedFiles($extract_path);
                
                $this->addLog('Feature imported successfully!', 'success');
                
                return [
                    'success' => true,
                    'message' => 'Feature "' . $manifest['name'] . '" imported successfully',
                    'log' => $this->log,
                    'backup_id' => $backup_id
                ];
                
            } catch (Exception $e) {
                // Rollback database changes
                $this->pdo->rollBack();
                
                // Restore files from backup
                $this->addLog('Error occurred, rolling back changes...', 'error');
                $this->restoreBackup($backup_id);
                
                throw $e;
            }
            
        } catch (Exception $e) {
            $this->addLog('Import failed: ' . $e->getMessage(), 'error');
            
            // Clean up on error
            if ($extract_path && file_exists($extract_path)) {
                $this->cleanupExtractedFiles($extract_path);
            }
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'log' => $this->log
            ];
        }
    }
    
    /**
     * Extract ZIP file
     */
    private function extractZip($zip_file_path) {
        $zip = new ZipArchive();
        
        if ($zip->open($zip_file_path) !== true) {
            throw new Exception('Failed to open ZIP file');
        }
        
        $extract_path = $this->upload_dir . '/' . uniqid('extract_');
        mkdir($extract_path, 0755, true);
        
        if (!$zip->extractTo($extract_path)) {
            $zip->close();
            throw new Exception('Failed to extract ZIP file');
        }
        
        $zip->close();
        
        return $extract_path;
    }
    
    /**
     * Load and parse manifest.json
     */
    private function loadManifest($extract_path) {
        $manifest_path = $extract_path . '/manifest.json';
        
        if (!file_exists($manifest_path)) {
            throw new Exception('manifest.json not found in package');
        }
        
        $manifest_content = file_get_contents($manifest_path);
        $manifest = json_decode($manifest_content, true);
        
        if ($manifest === null) {
            throw new Exception('Invalid manifest.json format');
        }
        
        return $manifest;
    }
    
    /**
     * Validate manifest structure
     */
    private function validateManifest($manifest) {
        $required_fields = ['name', 'version'];
        
        foreach ($required_fields as $field) {
            if (!isset($manifest[$field])) {
                throw new Exception("Missing required field in manifest: $field");
            }
        }
        
        // Validate version format
        if (!preg_match('/^\d+\.\d+\.\d+$/', $manifest['version'])) {
            throw new Exception('Invalid version format. Use semantic versioning (e.g., 1.0.0)');
        }
        
        $this->addLog("Feature: {$manifest['name']} v{$manifest['version']}", 'info');
    }
    
    /**
     * Run system validation before import
     */
    private function runSystemValidation() {
        $validator = new SystemValidator($this->pdo, $this->base_path);
        $results = $validator->runAllChecks();
        
        // Check for critical issues
        if ($results['summary']['critical'] > 0) {
            throw new Exception(
                'System validation failed with ' . 
                $results['summary']['critical'] . 
                ' critical issues. Fix these before importing features.'
            );
        }
        
        if ($results['summary']['warnings'] > 10) {
            $this->addLog('Warning: System has ' . $results['summary']['warnings'] . ' warnings', 'warning');
        }
        
        $this->addLog('System validation passed', 'success');
    }
    
    /**
     * Create backup of files that will be modified
     */
    private function createBackup($manifest, $backup_id) {
        $backup_path = $this->backup_dir . '/' . $backup_id;
        mkdir($backup_path, 0755, true);
        
        $files_to_backup = [];
        
        // Files that will be updated
        if (isset($manifest['files']['update'])) {
            $files_to_backup = array_merge($files_to_backup, $manifest['files']['update']);
        }
        
        // Files that will be deleted
        if (isset($manifest['files']['delete'])) {
            $files_to_backup = array_merge($files_to_backup, $manifest['files']['delete']);
        }
        
        foreach ($files_to_backup as $file) {
            $source = $this->base_path . '/' . $file;
            if (file_exists($source)) {
                $dest = $backup_path . '/' . $file;
                $dest_dir = dirname($dest);
                
                if (!file_exists($dest_dir)) {
                    mkdir($dest_dir, 0755, true);
                }
                
                copy($source, $dest);
            }
        }
        
        // Save manifest to backup
        file_put_contents(
            $backup_path . '/manifest.json',
            json_encode($manifest, JSON_PRETTY_PRINT)
        );
        
        $this->addLog('Backup created: ' . $backup_id, 'info');
    }
    
    /**
     * Restore files from backup
     */
    private function restoreBackup($backup_id) {
        $backup_path = $this->backup_dir . '/' . $backup_id;
        
        if (!file_exists($backup_path)) {
            $this->addLog('Backup not found, cannot restore', 'error');
            return;
        }
        
        // Restore all backed up files
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($backup_path, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );
        
        foreach ($iterator as $item) {
            if ($item->isFile() && $item->getFilename() !== 'manifest.json') {
                $relative_path = substr($item->getPathname(), strlen($backup_path) + 1);
                $dest = $this->base_path . '/' . $relative_path;
                
                copy($item->getPathname(), $dest);
            }
        }
        
        $this->addLog('Files restored from backup', 'info');
    }
    
    /**
     * Run database migrations
     */
    private function runMigrations($migrations, $extract_path) {
        foreach ($migrations as $migration_file) {
            $migration_path = $extract_path . '/' . $migration_file;
            
            if (!file_exists($migration_path)) {
                throw new Exception("Migration file not found: $migration_file");
            }
            
            $sql = file_get_contents($migration_path);
            
            // Split by semicolon and execute each statement
            $statements = array_filter(
                array_map('trim', explode(';', $sql)),
                function($stmt) { return !empty($stmt); }
            );
            
            foreach ($statements as $statement) {
                try {
                    $this->pdo->exec($statement);
                } catch (PDOException $e) {
                    throw new Exception("Migration failed: " . $e->getMessage());
                }
            }
            
            $this->addLog("Migration executed: $migration_file", 'success');
        }
    }
    
    /**
     * Create directories
     */
    private function createDirectories($directories) {
        foreach ($directories as $dir) {
            $full_path = $this->base_path . '/' . $dir;
            
            if (!file_exists($full_path)) {
                if (mkdir($full_path, 0755, true)) {
                    $this->addLog("Directory created: $dir", 'success');
                } else {
                    throw new Exception("Failed to create directory: $dir");
                }
            } else {
                $this->addLog("Directory already exists: $dir", 'info');
            }
        }
    }
    
    /**
     * Process files (create, update, delete)
     */
    private function processFiles($files, $extract_path) {
        // Create new files
        if (isset($files['create'])) {
            foreach ($files['create'] as $file) {
                $source = $extract_path . '/files/' . $file;
                $dest = $this->base_path . '/' . $file;
                
                if (!file_exists($source)) {
                    throw new Exception("Source file not found: $file");
                }
                
                if (file_exists($dest)) {
                    throw new Exception("File already exists, cannot create: $file");
                }
                
                $dest_dir = dirname($dest);
                if (!file_exists($dest_dir)) {
                    mkdir($dest_dir, 0755, true);
                }
                
                if (copy($source, $dest)) {
                    $this->addLog("File created: $file", 'success');
                } else {
                    throw new Exception("Failed to create file: $file");
                }
            }
        }
        
        // Update existing files
        if (isset($files['update'])) {
            foreach ($files['update'] as $file) {
                $source = $extract_path . '/files/' . $file;
                $dest = $this->base_path . '/' . $file;
                
                if (!file_exists($source)) {
                    throw new Exception("Source file not found: $file");
                }
                
                if (copy($source, $dest)) {
                    $this->addLog("File updated: $file", 'success');
                } else {
                    throw new Exception("Failed to update file: $file");
                }
            }
        }
        
        // Delete files
        if (isset($files['delete'])) {
            foreach ($files['delete'] as $file) {
                $file_path = $this->base_path . '/' . $file;
                
                if (file_exists($file_path)) {
                    if (unlink($file_path)) {
                        $this->addLog("File deleted: $file", 'success');
                    } else {
                        throw new Exception("Failed to delete file: $file");
                    }
                }
            }
        }
    }
    
    /**
     * Update navigation in dashboard.php
     */
    private function updateNavigation($navigation) {
        $dashboard_path = $this->base_path . '/dashboard.php';
        
        if (!file_exists($dashboard_path)) {
            throw new Exception('dashboard.php not found');
        }
        
        $dashboard_content = file_get_contents($dashboard_path);
        
        // Add new routes to allowed_pages array
        if (isset($navigation['add'])) {
            foreach ($navigation['add'] as $nav_item) {
                $page_key = $nav_item['url'] ?? '';
                $page_key = str_replace('?page=', '', $page_key);
                $view_file = $nav_item['view'] ?? "views/{$page_key}.php";
                
                // Check if route already exists
                if (strpos($dashboard_content, "'$page_key'") !== false) {
                    $this->addLog("Route already exists: $page_key", 'info');
                    continue;
                }
                
                // Find the allowed_pages array and add new route
                $pattern = '/(\$allowed_pages\s*=\s*\[.*?)(\];)/s';
                if (preg_match($pattern, $dashboard_content, $matches)) {
                    $new_route = "    '{$page_key}' => '{$view_file}',\n";
                    $dashboard_content = str_replace($matches[2], $new_route . $matches[2], $dashboard_content);
                    
                    $this->addLog("Route added: $page_key", 'success');
                }
            }
            
            // Write updated dashboard.php
            file_put_contents($dashboard_path, $dashboard_content);
        }
    }
    
    /**
     * Clean up extracted files
     */
    private function cleanupExtractedFiles($extract_path) {
        if (file_exists($extract_path)) {
            $this->deleteDirectory($extract_path);
        }
    }
    
    /**
     * Recursively delete directory
     */
    private function deleteDirectory($dir) {
        if (!file_exists($dir)) {
            return;
        }
        
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        
        foreach ($iterator as $file) {
            if ($file->isDir()) {
                rmdir($file->getPathname());
            } else {
                unlink($file->getPathname());
            }
        }
        
        rmdir($dir);
    }
    
    /**
     * Add entry to log
     */
    private function addLog($message, $type = 'info') {
        $this->log[] = [
            'message' => $message,
            'type' => $type,
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }
}
