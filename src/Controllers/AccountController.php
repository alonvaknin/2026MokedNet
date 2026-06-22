<?php
declare(strict_types=1);

namespace Controllers;

use Core\Controller;
use Core\Auth;
use Core\ActivityLog;
use Models\AccountModel;

class AccountController extends Controller
{
    public function index(): void
    {
        $this->requirePermission('canViewMokedData');
        $canEdit = Auth::can('canEditMokedData');
        $canAdd  = Auth::can('canAddAccounts');
        $this->view('pages/accounts/index', compact('canEdit', 'canAdd'));
    }

    public function apiList(): void
    {
        $this->requirePermission('canViewMokedData');
        $canEdit = Auth::can('canEditMokedData');
        $canAdd  = Auth::can('canAddAccounts');
        $rows    = AccountModel::all();
        $this->json(compact('rows', 'canEdit', 'canAdd'));
    }

    public function create(): void
    {
        $this->requireAuth();
        if (!Auth::can('canAddAccounts')) {
            $this->json(['error' => true, 'msg' => 'אין הרשאה להוסיף חשבונות'], 403);
            return;
        }
        $this->verifyCsrf();

        $appName = trim($this->post('appname', ''));
        $appUser = trim($this->post('appuser', ''));
        $appPass = trim($this->post('apppass', ''));
        $appNote = trim($this->post('appnote', ''));

        if ($appName === '' || $appUser === '' || $appPass === '' || $appNote === '') {
            $this->json(['error' => true, 'msg' => 'נא למלא את כל השדות'], 422);
            return;
        }

        $user     = Auth::user();
        $userName = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
        $id       = AccountModel::create([
            'appName'         => $appName,
            'appUser'         => $appUser,
            'appPass'         => $appPass,
            'appNote'         => $appNote,
            'userID'          => $user['id'],
            'created_by_id'   => $user['id'],
            'created_by_name' => $userName,
        ]);

        ActivityLog::create('accounts', $id, "נוסף חשבון: {$appName}");
        $this->json(['error' => false, 'msg' => 'הפרטים התווספו בהצלחה', 'id' => $id]);
    }

    public function update(string $id): void
    {
        $this->requirePermission('canEditMokedData');
        $this->verifyCsrf();

        $appName = trim($this->post('appname', ''));
        $appUser = trim($this->post('appuser', ''));
        $appPass = trim($this->post('apppass', ''));
        $appNote = trim($this->post('appnote', ''));

        if ($appName === '' || $appUser === '' || $appPass === '' || $appNote === '') {
            $this->json(['error' => true, 'msg' => 'נא למלא את כל השדות'], 422);
            return;
        }

        $user     = Auth::user();
        $userName = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));

        $ok = AccountModel::update((int)$id, [
            'appName'         => $appName,
            'appUser'         => $appUser,
            'appPass'         => $appPass,
            'appNote'         => $appNote,
            'updated_by_id'   => $user['id'],
            'updated_by_name' => $userName,
        ]);

        if (!$ok) {
            $this->json(['error' => true, 'msg' => 'לא נמצאה רשומה'], 404);
            return;
        }

        ActivityLog::log('accounts.update', 'accounts', (int)$id, "חשבון #{$id}", "עדכון: {$appName}");
        $this->json(['error' => false, 'msg' => 'החשבון עודכן בהצלחה']);
    }

    public function delete(string $id): void
    {
        $this->requirePermission('canEditMokedData');
        $this->verifyCsrf();

        $user     = Auth::user();
        $userName = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
        AccountModel::markUpdated((int)$id, $user['id'], $userName);

        $ok = AccountModel::delete((int)$id);
        if (!$ok) {
            $this->json(['error' => true, 'msg' => 'לא נמצאה רשומה'], 404);
            return;
        }

        ActivityLog::log('accounts.delete', 'accounts', (int)$id, "חשבון #{$id}", 'נמחק');
        $this->json(['error' => false, 'msg' => 'נמחק בהצלחה']);
    }
}
