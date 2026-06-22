<?php
declare(strict_types=1);

namespace Controllers;

use Core\Controller;
use Core\Auth;
use Core\ActivityLog;
use Models\ContactModel;

class ContactController extends Controller
{
    public function index(): void
    {
        $this->requireAuth();
        $q        = trim($this->get('q', ''));
        $dept     = $this->get('dept', '');
        $type     = $this->get('type', '');
        $contacts = ($q || $dept || $type)
            ? ContactModel::search($q, $dept, $type)
            : ContactModel::allIncludingInactive($type);
        $depts    = ContactModel::departments();
        $types    = ContactModel::types();
        $canEdit  = Auth::can('canEditDB');
        $this->view('pages/contacts/index',
            compact('contacts', 'depts', 'types', 'q', 'dept', 'type', 'canEdit'));
    }

    public function save(): void
    {
        $this->requirePermission('canEditDB');
        $this->verifyCsrf();

        $id     = (int)$this->post('id', 0);
        $before = $id ? ContactModel::logSnapshot($id) : [];

        $data = [
            'first_name'      => $this->post('first_name',   ''),
            'last_name'       => $this->post('last_name',    ''),
            'email'           => $this->post('email',        ''),
            'phone'           => $this->post('phone',        ''),
            'phone2'          => $this->post('phone2',       ''),
            'website'         => $this->post('website',      ''),
            'role'            => $this->post('role',         ''),
            'department'      => $this->post('department',   ''),
            'contact_type'    => $this->post('contact_type', 'איש קשר'),
            'address'         => $this->post('address',      ''),
            'tags'            => $this->post('tags',         ''),
            'note'            => $this->post('note',         ''),
            'is_active'       => (bool)$this->post('is_active',       1),
            'is_contacts_list'=> (bool)$this->post('is_contacts_list', 0),
        ];

        if (!$data['first_name']) $this->json(['error' => 'שם פרטי חובה'], 400);

        $label = trim($data['first_name'] . ' ' . $data['last_name']);

        if ($id) {
            ContactModel::update($id, $data);
            $after = ContactModel::logSnapshot($id);
            ActivityLog::update('contact', $id, $label, $before, $after);
        } else {
            $id = ContactModel::create($data);
            ActivityLog::create('contact', $id, $label);
        }

        $this->json(['ok' => true, 'id' => $id]);
    }

    public function toggle(string $id): void
    {
        $this->requirePermission('canEditDB');
        $this->verifyCsrf();
        $contact = ContactModel::byId((int)$id);
        $active  = ContactModel::toggleActive((int)$id);
        $label   = trim(($contact['first_name']??'') . ' ' . ($contact['last_name']??''));
        ActivityLog::toggle('contact', (int)$id, $label, (bool)$active);
        $this->json(['ok' => true, 'is_active' => $active]);
    }

    public function apiSearch(): void
    {
        $this->requireAuth();
        $q    = trim($this->get('q', ''));
        $type = $this->get('type', '');
        if (mb_strlen($q) < 1) $this->json([]);
        $this->json(ContactModel::search($q, '', $type));
    }

    /** GET /api/contacts/list — אנשי קשר מסומנים לתכתובות אוטומטיות */
    public function apiList(): void
    {
        $this->requireAuth();
        $this->json(ContactModel::contactsList());
    }
}
