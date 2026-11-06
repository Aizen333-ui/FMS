<?php
declare(strict_types=1);

namespace Fms\Controllers;

use Fms\Auth;
use Fms\Database;
use Fms\Guard;
use Fms\Http;
use Fms\Services\AdminService;
use Fms\ValidationException;

final class AuthController
{
    public static function home(): void
    {
        if (Auth::check()) {
            Http::redirect(Auth::home());
        }
        Http::render('auth/login', [
            'title' => 'Sign in',
            'configured' => Database::configured(),
        ]);
    }

    public static function login(): void
    {
        Guard::database();
        Guard::handle(function (): void {
            $user = Auth::attemptStaff(Http::post('username'), Http::post('password'));
            Http::flash('success', 'Signed in as ' . $user['name'] . '.');
        }, Auth::check() ? Auth::home() : '/');
    }

    public static function logout(): void
    {
        $admin = (Auth::user()['kind'] ?? '') === 'admin';
        if (Auth::check()) {
            Auth::logout();
        }
        Http::flash('success', 'You have logged out.');
        Http::redirect($admin ? '/admin/login' : '/');
    }

    public static function adminLoginForm(): void
    {
        if (Auth::role() === 'Administrator') {
            Http::redirect('/admin');
        }
        $needsSetup = false;
        if (Database::configured()) {
            try {
                $needsSetup = AdminService::count() === 0;
            } catch (\Throwable) {
                $needsSetup = false;
            }
        }
        if ($needsSetup) {
            Http::redirect('/admin/setup');
        }
        Http::render('auth/admin_login', [
            'title' => 'Administrator sign in',
            'configured' => Database::configured(),
        ]);
    }

    public static function adminLogin(): void
    {
        Guard::database();
        if (AdminService::count() === 0) {
            Http::redirect('/admin/setup');
        }
        Guard::handle(function (): void {
            Auth::attemptAdmin(Http::post('username'), (string) ($_POST['password'] ?? ''));
            Http::flash('success', 'Administrator signed in.');
        }, Auth::role() === 'Administrator' ? '/admin' : '/admin/login');
    }

    public static function setupForm(): void
    {
        Guard::database();
        if (AdminService::count() > 0) {
            Http::redirect('/admin/login');
        }
        Http::render('auth/setup', ['title' => 'Create administrator']);
    }

    public static function setup(): void
    {
        Guard::database();
        try {
            AdminService::createFirst(
                Http::post('username'),
                (string) ($_POST['password'] ?? ''),
                (string) ($_POST['confirm'] ?? '')
            );
            Http::flash('success', 'Administrator created successfully. Sign in to continue.');
            Http::clearOld();
            Http::redirect('/admin/login');
        } catch (ValidationException $e) {
            Http::flash('error', $e->getMessage());
            Http::remember($_POST);
            Http::redirect('/admin/setup');
        }
    }
}
