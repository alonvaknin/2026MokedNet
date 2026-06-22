<?php
declare(strict_types=1);

namespace Controllers;

use Core\Controller;
use Core\DB;

class PreferencesController extends Controller
{
    public function index(): void
    {
        $this->requireAuth();
        $savedPrefs = $this->loadFromDB();
        $this->view('pages/preferences/index', compact('savedPrefs'));
    }

    public function save(): void
    {
        $this->requireAuth();
        $this->verifyCsrf();

        $userId = $_SESSION['user_id'];
        $prefs  = $_POST['prefs'] ?? '';

        $decoded = json_decode($prefs, true);
        if (!$decoded) {
            $this->json(['error' => 'JSON לא תקין'], 400);
        }

        DB::execute(
            'INSERT INTO user_preferences (user_id, pref_key, pref_value)
             VALUES (?, "theme", ?)
             ON DUPLICATE KEY UPDATE pref_value = VALUES(pref_value), updated_at = NOW()',
            [$userId, $prefs]
        );

        $this->json(['ok' => true]);
    }

    public function getPrefs(): void
    {
        $this->requireAuth();
        $prefs = $this->loadFromDB();
        $this->json($prefs ?: (object)[]);
    }

    private function loadFromDB(): ?array
    {
        $row = DB::row(
            'SELECT pref_value FROM user_preferences WHERE user_id = ? AND pref_key = "theme"',
            [$_SESSION['user_id']]
        );
        if (!$row) return null;
        return json_decode($row['pref_value'], true);
    }
}
