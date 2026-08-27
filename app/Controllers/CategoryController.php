<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Category;

class CategoryController extends Controller
{
    private Category $model;

    public function __construct()
    {
        $this->model = new Category();
    }

    public function index(): void
    {
        $this->view('categories.index', [
            'pageTitle' => 'Categories',
            'categories' => $this->model->withProductCounts(),
            'csrf' => $this->csrfToken(),
        ]);
    }

    public function store(): void
    {
        $this->verifyCsrf();

        $name = trim((string) $this->input('name'));
        if ($name === '') {
            $this->flash('error', 'Category name is required.');
            $this->redirect('/categories');
        }

        $this->model->create([
            'name' => $name,
            'description' => trim((string) $this->input('description')) ?: null,
        ]);

        $this->flash('success', 'Category added.');
        $this->redirect('/categories');
    }

    public function destroy(string $id): void
    {
        $this->verifyCsrf();
        try {
            $this->model->delete((int) $id);
            $this->flash('success', 'Category deleted.');
        } catch (\PDOException $e) {
            $this->flash('error', 'Cannot delete — this category still has products assigned to it.');
        }
        $this->redirect('/categories');
    }

}
