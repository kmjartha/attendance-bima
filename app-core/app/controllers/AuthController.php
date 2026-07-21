<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Session;
use App\Core\Validator;
use App\Models\User;

class AuthController extends Controller
{
    public function showLogin(): string
    {
        if (auth_check()) {
            return $this->redirect('/dashboard');
        }

        $remembered = remember_me_login();
        if ($remembered) {
            $_SESSION['user'] = [
                'id'           => (int)$remembered['id'],
                'niy'          => $remembered['niy'],
                'nama'         => $remembered['nama'],
                'jabatan'      => $remembered['jabatan'],
                'role_id'      => (int)$remembered['role_id'],
                'role_name'    => $remembered['role_name'],
                'foto_profile' => $remembered['foto_profile'],
            ];
            return $this->redirect('/dashboard');
        }

        return $this->render('auth.login', [
            'title' => 'Masuk',
            'error' => Session::flash('error'),
            'old_niy' => $_COOKIE['remember_niy'] ?? '',
        ], 'auth');
    }

    public function login(): string
    {
        $token = Csrf::fromRequest();
        if (!Csrf::check($token)) {
            $this->flash('error', 'Sesi kedaluwarsa. Silakan coba lagi.');
            return $this->redirect('/login');
        }

        $v = new Validator($_POST);
        $v->required('niy', 'NIY')->required('password', 'Password');
        if ($v->fails()) {
            $this->flash('error', $v->firstError());
            return $this->redirect('/login');
        }

        $niy      = trim((string)($_POST['niy'] ?? ''));
        $password = (string)($_POST['password'] ?? '');
        $remember = !empty($_POST['remember']);

        $userModel = new User();
        $u = $userModel->findByNiy($niy);

        if (!$u || !password_verify($password, $u['password'])) {
            $this->flash('error', 'NIY atau password salah.');
            return $this->redirect('/login');
        }

        if ((int)$u['is_active'] !== 1) {
            $this->flash('error', 'Akun Anda dinonaktifkan. Hubungi HRD.');
            return $this->redirect('/login');
        }

        // Set session
        Session::regenerate();
        $_SESSION['user'] = [
            'id'           => (int)$u['id'],
            'niy'          => $u['niy'],
            'nama'         => $u['nama'],
            'jabatan'      => $u['jabatan'],
            'role_id'      => (int)$u['role_id'],
            'role_name'    => $u['role_name'],
            'foto_profile' => $u['foto_profile'],
        ];

        // Remember-me cookie (14 hari) untuk auto-login
        if ($remember) {
            setcookie('remember_me', remember_me_value($u), [
                'expires'  => time() + 60*60*24*14,
                'path'     => '/',
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
            setcookie('remember_niy', $niy, [
                'expires'  => time() + 60*60*24*14,
                'path'     => '/',
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
        } else {
            setcookie('remember_me', '', time()-3600, '/');
            setcookie('remember_niy', '', time()-3600, '/');
        }

        return $this->redirect('/dashboard');
    }

    public function profile(): string
    {
        $userModel = new User();
        $u = $userModel->findWithRole((int)user()['id']);
        if (!$u) {
            $this->flash('error', 'Data pengguna tidak ditemukan.');
            return $this->redirect('/dashboard');
        }

        return $this->render('auth.profile', [
            'title' => 'Profil Saya',
            'user'  => $u,
        ]);
    }

    public function updatePassword(): string
    {
        $token = Csrf::fromRequest();
        if (!Csrf::check($token)) {
            $this->flash('error', 'Sesi kedaluwarsa. Silakan coba lagi.');
            return $this->redirect('/profile');
        }

        $current = (string)($_POST['current_password'] ?? '');
        $new = (string)($_POST['new_password'] ?? '');
        $confirm = (string)($_POST['confirm_password'] ?? '');

        if ($current === '' || $new === '' || $confirm === '') {
            $this->flash('error', 'Semua field password harus diisi.');
            return $this->redirect('/profile');
        }

        if ($new !== $confirm) {
            $this->flash('error', 'Password baru dan konfirmasi tidak sama.');
            return $this->redirect('/profile');
        }

        if (strlen($new) < 6) {
            $this->flash('error', 'Password baru minimal 6 karakter.');
            return $this->redirect('/profile');
        }

        $userModel = new User();
        $u = $userModel->findWithRole((int)user()['id']);
        if (!$u || !password_verify($current, $u['password'])) {
            $this->flash('error', 'Password saat ini tidak cocok.');
            return $this->redirect('/profile');
        }

        $userModel->update((int)$u['id'], ['password' => password_hash($new, PASSWORD_DEFAULT)]);
        $this->flash('success', 'Password berhasil diperbarui.');
        return $this->redirect('/profile');
    }

    public function logout(): string
    {
        setcookie('remember_me', '', time() - 3600, '/');
        setcookie('remember_niy', '', time() - 3600, '/');
        Session::destroy();
        return $this->redirect('/login');
    }
}
