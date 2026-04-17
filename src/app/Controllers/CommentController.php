<?php

namespace App\Controllers;

use App\Models\CommentModel;
use CodeIgniter\RESTful\ResourceController;

class CommentController extends BaseController
{
    public function index()
    {
        $commentModel = new CommentModel();

        // Get sorting params, default to id desc
        $sortBy = $this->request->getGet('sort_by') ?? 'id';
        $sortDir = $this->request->getGet('sort_dir') ?? 'desc';

        // Validate sort params to prevent SQL injection
        $allowedSortFields = ['id', 'date'];
        $allowedSortDirs = ['asc', 'desc'];
        
        if (!in_array($sortBy, $allowedSortFields)) {
            $sortBy = 'id';
        }
        if (!in_array(strtolower($sortDir), $allowedSortDirs)) {
            $sortDir = 'desc';
        }

        // Fetch paginated results
        $data = [
            'comments' => $commentModel->orderBy($sortBy, $sortDir)->paginate(3),
            'pager'    => $commentModel->pager,
            'sortBy'   => $sortBy,
            'sortDir'  => $sortDir,
        ];

        return view('comments/index', $data);
    }

    public function create()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(403)->setJSON(['status' => 'error', 'message' => 'AJAX request required.']);
        }

        // Validation rules
        $rules = [
            'name' => [
                'rules'  => 'required|valid_email|max_length[255]',
                'errors' => [
                    'required'    => 'Email обязателен для заполнения.',
                    'valid_email' => 'Пожалуйста, введите корректный Email адрес.',
                    'max_length'  => 'Email слишком длинный.'
                ]
            ],
            'text' => [
                'rules'  => 'required',
                'errors' => [
                    'required' => 'Текст комментария обязателен для заполнения.'
                ]
            ],
            'date' => [
                'rules'  => 'required|valid_date[Y-m-d]',
                'errors' => [
                    'required'   => 'Дата обязательна для выбора.',
                    'valid_date' => 'Неверный формат даты.'
                ]
            ],
        ];

        if (!$this->validate($rules)) {
            return $this->response->setJSON([
                'status' => 'error',
                'errors' => $this->validator->getErrors()
            ]);
        }

        $commentModel = new CommentModel();
        
        $data = [
            'name' => $this->request->getPost('name'),
            'text' => $this->request->getPost('text'),
            'date' => $this->request->getPost('date'),
        ];

        if ($commentModel->insert($data)) {
            $data['id'] = $commentModel->getInsertID();
            return $this->response->setJSON([
                'status'  => 'success',
                'message' => 'Комментарий успешно добавлен.',
                'comment' => $data
            ]);
        }

        return $this->response->setStatusCode(500)->setJSON([
            'status'  => 'error',
            'message' => 'Ошибка при сохранении в базу данных.'
        ]);
    }

    public function delete($id = null)
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(403)->setJSON(['status' => 'error', 'message' => 'AJAX request required.']);
        }

        $commentModel = new CommentModel();
        
        if ($commentModel->delete($id)) {
            return $this->response->setJSON([
                'status'  => 'success',
                'message' => 'Комментарий успешно удален.'
            ]);
        }

        return $this->response->setStatusCode(500)->setJSON([
            'status'  => 'error',
            'message' => 'Ошибка при удалении.'
        ]);
    }
}
