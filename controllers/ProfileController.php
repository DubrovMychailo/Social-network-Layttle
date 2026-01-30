<?php

namespace controllers;

use core\Controller;
use core\DB;
use core\Session;

class ProfileController extends Controller
{
    private DB $db;
    private Session $session;

    public function __construct()
    {
        parent::__construct();
        $this->session = new Session();
        $this->db = new DB('localhost', 'Layttle', 'Dubrov', '2004Dubrov');
    }

    private function getUserDataByLogin(string $username)
    {
        return $this->db->selectOne('users', ['login' => $username]) ?: null;
    }

    private function getFriends(int $userId): array
    {
        return $this->db->selectRaw("SELECT u.firstname, u.lastname, u.photo FROM friends f JOIN users u ON f.friend_id = u.id WHERE f.user_id = :user_id AND f.status = 'approved'", ['user_id' => $userId]) ?: [];
    }

    private function getUserPosts(int $userId): array
    {
        $stmt = $this->db->pdo->prepare("
            SELECT p.id, p.content, p.photos, p.videos, p.gifs, p.created_at, p.updated_at,
                   u.firstname, u.lastname, u.photo AS user_photo,
                   (SELECT COALESCE(JSON_ARRAYAGG(l.user_id), '[]') 
                    FROM likes l 
                    WHERE l.post_id = p.id) AS likes,
                   (SELECT COALESCE(JSON_ARRAYAGG(JSON_OBJECT('firstname', cu.firstname, 'comment_text', c.comment_text)), '[]') 
                    FROM comments c 
                    JOIN users cu ON c.user_id = cu.id 
                    WHERE c.post_id = p.id) AS comments_data
            FROM posts p
            JOIN users u ON p.user_id = u.id
            WHERE p.user_id = :user_id
            ORDER BY p.created_at DESC
        ");

        $stmt->execute(['user_id' => $userId]);
        $posts = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($posts as &$post) {
            // Безпечне декодування JSON: якщо вже масив - лишаємо, якщо рядок - декодуємо
            $post['photos'] = is_string($post['photos']) ? (json_decode($post['photos'], true) ?: []) : ($post['photos'] ?? []);
            $post['videos'] = is_string($post['videos']) ? (json_decode($post['videos'], true) ?: []) : ($post['videos'] ?? []);
            $post['gifs']   = is_string($post['gifs']) ? (json_decode($post['gifs'], true) ?: []) : ($post['gifs'] ?? []);
            $post['likes']  = is_string($post['likes']) ? (json_decode($post['likes'], true) ?: []) : ($post['likes'] ?? []);
            $post['comments_data'] = is_string($post['comments_data']) ? (json_decode($post['comments_data'], true) ?: []) : ($post['comments_data'] ?? []);
        }

        return $posts;
    }

    public function view($username): void
    {
        $currentUser = $this->session->get('user');

        if ($username === 'me') {
            if (!$currentUser) {
                header('Location: /users/login');
                exit;
            }
            $username = $currentUser['login'];
        }

        $userData = $this->getUserDataByLogin($username);

        if (!$userData) {
            echo "<p class='alert alert-danger'>❌ Користувач не знайдений!</p>";
            return;
        }

        $isOwnProfile = $currentUser && $currentUser['login'] === $userData['login'];
        $friends = $this->getFriends($userData['id']);
        $posts = $this->getUserPosts($userData['id']);

        if (!defined('PROFILE_INCLUDED')) {
            define('PROFILE_INCLUDED', true);
            include __DIR__ . '/../views/profile/profile.php';
        }
    }

    // Решта методів (validateAndUpdateProfile) залишаються без змін
    public function validateAndUpdateProfile(array $postData, array $fileData)
    {
        // ... (ваш оригінальний код без змін)
        session_start();
        if (!isset($_SESSION['user'])) {
            return ['success' => false, 'message' => 'Користувач не авторизований.'];
        }
        $userId = $_SESSION['user']['id'];
        $firstname = trim($postData['firstname'] ?? '');
        $lastname = trim($postData['lastname'] ?? '');
        $bio = trim($postData['bio'] ?? '');
        $address = trim($postData['address'] ?? '');
        $phone = trim($postData['phone'] ?? '');
        $city = trim($postData['city'] ?? '');
        $country = trim($postData['country'] ?? '');
        $errors = [];
        if (!$firstname || !$lastname) { $errors[] = 'Ім\'я та прізвище обов\'язкові!'; }
        if ($phone && !preg_match('/^\+?[0-9]{7,15}$/', $phone)) { $errors[] = 'Некоректний номер телефону.'; }
        $photoPath = null;
        if (!empty($fileData['photo']['name'])) {
            $uploadDir = __DIR__ . '/../../uploads/';
            $filename = 'user_' . $userId . '_' . time() . '.' . pathinfo($fileData['photo']['name'], PATHINFO_EXTENSION);
            $photoPath = '/uploads/' . $filename;
            if (!move_uploaded_file($fileData['photo']['tmp_name'], $uploadDir . $filename)) { $errors[] = 'Помилка завантаження фото.'; }
        }
        if (!empty($errors)) { return ['success' => false, 'errors' => $errors]; }
        $query = "UPDATE users SET firstname=?, lastname=?, bio=?, address=?, phone=?, city=?, country=?";
        $params = [$firstname, $lastname, $bio, $address, $phone, $city, $country];
        if ($photoPath) { $query .= ", photo=?"; $params[] = $photoPath; }
        $query .= " WHERE id=?";
        $params[] = $userId;
        $stmt = $this->db->pdo->prepare($query);
        $updated = $stmt->execute($params);
        if ($updated) {
            $_SESSION['user'] = array_merge($_SESSION['user'], [
                'firstname' => $firstname, 'lastname' => $lastname, 'bio' => $bio,
                'address' => $address, 'phone' => $phone, 'city' => $city, 'country' => $country
            ]);
            if ($photoPath) { $_SESSION['user']['photo'] = $photoPath; }
            return ['success' => true, 'message' => 'Профіль оновлено.'];
        }
        return ['success' => false, 'message' => 'Помилка оновлення профілю.'];
    }
}