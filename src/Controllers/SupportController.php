<?php
declare(strict_types=1);

namespace Controllers;

use Core\Controller;
use Models\SupportModel;

class SupportController extends Controller
{
    /** דף ראשי — רשימת קטגוריות */
    public function index(): void
    {
        $this->requireAuth();
        $categories = SupportModel::categories();
        $this->view('pages/support/index', compact('categories'));
    }

    /** מוצרים לפי קטגוריה */
    public function category(string $id): void
    {
        $this->requireAuth();
        $products = SupportModel::productsByCategory((int)$id);
        $this->view('pages/support/products', compact('products', 'id'));
    }

    /** חיפוש מוצר */
    public function searchProduct(): void
    {
        $this->requireAuth();
        $q        = trim($this->get('q', ''));
        $products = $q ? SupportModel::searchProduct($q) : [];
        $this->json($products);
    }

    /** בעיות ופתרונות לפי מוצר (AJAX) */
    public function issues(): void
    {
        $this->requireAuth();
        $barcode = trim($this->post('barcode', '')) ?: null;
        $catId   = (int)$this->post('cat_id', 0) ?: null;
        $issues  = SupportModel::issuesByProduct($barcode, $catId);
        $this->json($issues);
    }

    /** ניהול issues */
    public function manageIssues(): void
    {
        $this->requirePermission('manageSupportIssues');
        $issues     = SupportModel::allIssues();
        $categories = SupportModel::categories();
        $this->view('pages/support/issues', compact('issues', 'categories'));
    }

    /** הוספת issue */
    public function addIssue(): void
    {
        $this->requirePermission('manageSupportIssues');
        $this->verifyCsrf();

        SupportModel::addIssue([
            'title'    => trim($this->post('title', '')),
            'solution' => trim($this->post('solution', '')),
            'cat_id'   => (int)$this->post('cat_id', 0) ?: null,
            'barcode'  => trim($this->post('barcode', '')) ?: null,
            'user_id'  => $_SESSION['user_id'],
        ]);

        $this->redirect('/support/issues');
    }
}
