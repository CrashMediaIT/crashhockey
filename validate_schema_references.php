#!/usr/bin/env php
<?php
/**
 * Cross-Reference Validation Script
 * Validates all database table/column references across view files
 */

// Extract schema tables and columns
$schema_file = __DIR__ . '/deployment/schema.sql';
$schema_content = file_get_contents($schema_file);

// Parse schema to extract table definitions
$tables = [];
preg_match_all('/CREATE TABLE IF NOT EXISTS `(\w+)` \((.*?)\) ENGINE/s', $schema_content, $matches, PREG_SET_ORDER);

foreach ($matches as $match) {
    $table_name = $match[1];
    $table_def = $match[2];
    
    // Extract column names
    preg_match_all('/`(\w+)`\s+(?:INT|VARCHAR|TEXT|DECIMAL|DATE|TIMESTAMP|TIME|TINYINT|ENUM)/i', $table_def, $col_matches);
    $tables[$table_name] = $col_matches[1];
}

echo "=== SCHEMA ANALYSIS ===\n";
echo "Found " . count($tables) . " tables in schema.sql\n\n";

// Track issues
$issues = [];
$column_usage = [];

// Scan all view files
$view_files = glob(__DIR__ . '/views/*.php');

foreach ($view_files as $view_file) {
    $view_name = basename($view_file);
    $content = file_get_contents($view_file);
    
    // Extract SQL queries - look for table.column references
    preg_match_all('/\b(\w+)\.(\w+)\b/', $content, $refs, PREG_SET_ORDER);
    
    foreach ($refs as $ref) {
        $table = $ref[1];
        $column = $ref[2];
        
        // Skip obvious non-table references
        if (in_array($table, ['this', 'document', 'console', 'window', 'location', 'event', 'style', 'classList', 'dataset'])) {
            continue;
        }
        
        // Track usage
        if (!isset($column_usage[$table])) {
            $column_usage[$table] = [];
        }
        if (!isset($column_usage[$table][$column])) {
            $column_usage[$table][$column] = [];
        }
        $column_usage[$table][$column][] = $view_name;
        
        // Validate against schema
        if (isset($tables[$table])) {
            if (!in_array($column, $tables[$table])) {
                $issues[] = [
                    'type' => 'invalid_column',
                    'table' => $table,
                    'column' => $column,
                    'file' => $view_name,
                    'message' => "Column '$column' not found in table '$table'"
                ];
            }
        }
    }
    
    // Also check FROM and JOIN clauses for table references
    preg_match_all('/\b(?:FROM|JOIN)\s+`?(\w+)`?(?:\s+(?:AS\s+)?(\w+))?/i', $content, $table_refs, PREG_SET_ORDER);
    
    foreach ($table_refs as $tref) {
        $table = $tref[1];
        if (!isset($tables[$table]) && !in_array($table, ['SELECT', 'WHERE', 'ORDER', 'GROUP', 'LIMIT'])) {
            // Check if it's not a known table
            $issues[] = [
                'type' => 'unknown_table',
                'table' => $table,
                'file' => $view_name,
                'message' => "Table '$table' not found in schema.sql"
            ];
        }
    }
}

// Output Results
echo "\n=== VALIDATION RESULTS ===\n\n";

if (empty($issues)) {
    echo "✓ NO ISSUES FOUND - All table and column references are valid!\n";
} else {
    echo "⚠ FOUND " . count($issues) . " POTENTIAL ISSUES:\n\n";
    
    $grouped = [];
    foreach ($issues as $issue) {
        $key = $issue['type'];
        if (!isset($grouped[$key])) {
            $grouped[$key] = [];
        }
        $grouped[$key][] = $issue;
    }
    
    foreach ($grouped as $type => $type_issues) {
        echo strtoupper(str_replace('_', ' ', $type)) . " (" . count($type_issues) . "):\n";
        foreach ($type_issues as $issue) {
            echo "  - " . $issue['file'] . ": " . $issue['message'] . "\n";
        }
        echo "\n";
    }
}

// Output column usage statistics for commonly used tables
$common_tables = ['sessions', 'bookings', 'users', 'age_groups', 'skill_levels', 'packages', 
                  'user_package_credits', 'managed_athletes', 'refunds', 'user_credits'];

echo "\n=== COLUMN USAGE FOR COMMON TABLES ===\n\n";

foreach ($common_tables as $table) {
    if (isset($column_usage[$table])) {
        echo "$table:\n";
        foreach ($column_usage[$table] as $col => $files) {
            echo "  - $col (used in " . count(array_unique($files)) . " views)\n";
        }
        echo "\n";
    }
}

// Generate cross-reference report
echo "\n=== TABLE CROSS-REFERENCE ===\n\n";

$table_files = [];
foreach ($view_files as $view_file) {
    $view_name = basename($view_file);
    $content = file_get_contents($view_file);
    
    foreach (array_keys($tables) as $table) {
        if (preg_match('/\b(?:FROM|JOIN)\s+`?' . preg_quote($table) . '`?\b/i', $content)) {
            if (!isset($table_files[$table])) {
                $table_files[$table] = [];
            }
            $table_files[$table][] = $view_name;
        }
    }
}

foreach ($table_files as $table => $files) {
    if (count($files) > 2) {  // Only show tables used in multiple files
        echo "$table (" . count($files) . " views): " . implode(', ', $files) . "\n";
    }
}

echo "\n=== VALIDATION COMPLETE ===\n";
