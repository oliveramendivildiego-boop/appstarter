<?php

namespace App\Controllers;

class SessionTest extends BaseController
{
    public function index()
    {
        echo "<h1>Session Test</h1>";
        
        // Información actual
        echo "<h2>Session Current State</h2>";
        echo "Session ID: " . session_id() . "<br>";
        echo "Session Cookie Name: " . session_name() . "<br>";
        echo "Session Path: " . session_save_path() . "<br>";
        echo "Session Files: <br>";
        
        $path = session_save_path();
        if (is_dir($path)) {
            $files = glob($path . '/ci_session_*');
            if (is_array($files)) {
                echo "Found " . count($files) . " session files<br>";
                foreach ($files as $file) {
                    echo "- " . basename($file) . " (" . filesize($file) . " bytes)<br>";
                }
            }
        } else {
            echo "Session path not found: $path<br>";
        }
        
        // Crear una sesión de prueba
        echo "<h2>Creating Test Session</h2>";
        session()->set('test_key', 'test_value_' . time());
        session()->set('person_id', 999);
        session()->set('username', 'testuser');
        
        echo "Set test_key = " . session()->get('test_key') . "<br>";
        echo "Set person_id = " . session()->get('person_id') . "<br>";
        
        // Comprobar archivos después de crear sesión
        echo "<h2>Session Files After Set</h2>";
        if (is_dir($path)) {
            $files = glob($path . '/ci_session_*');
            if (is_array($files)) {
                echo "Found " . count($files) . " session files<br>";
                foreach ($files as $file) {
                    echo "- " . basename($file) . " (" . filesize($file) . " bytes)<br>";
                    $content = file_get_contents($file);
                    echo "<pre>" . substr(htmlspecialchars($content), 0, 300) . "</pre>";
                }
            } else {
                echo "glob() returned error or no files<br>";
            }
        }
        
        echo "<h2>Cookie Info</h2>";
        echo "Cookies: <pre>" . print_r($_COOKIE, true) . "</pre>";
    }

    public function cleanup()
    {
        echo "<h1>Session Cleanup Test</h1>";
        
        $path = session_save_path();
        if (is_dir($path)) {
            $files = glob($path . '/ci_session_*');
            if (is_array($files)) {
                echo "Deleting " . count($files) . " session files<br>";
                foreach ($files as $file) {
                    if (unlink($file)) {
                        echo "✓ Deleted: " . basename($file) . "<br>";
                    } else {
                        echo "✗ Failed to delete: " . basename($file) . "<br>";
                    }
                }
            }
        }
        
        echo "<a href='" . site_url('sessiontest') . "'>Back to Test</a>";
    }
}
