<?php

if (!defined('ABSPATH')) {
    exit;
}

class CatGame_Auth {
    public static function init(): void {
        add_action('admin_post_nopriv_catgame_register', [__CLASS__, 'handle_register']);
        add_action('admin_post_catgame_register', [__CLASS__, 'handle_register']);
    }

    public static function handle_register(): void {
        if (is_user_logged_in()) {
            wp_safe_redirect(home_url('/catgame/profile'));
            exit;
        }

        check_admin_referer('catgame_register');

        $username = sanitize_user(wp_unslash($_POST['username'] ?? ''), true);
        $email = sanitize_email(wp_unslash($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $password_confirm = (string) ($_POST['password_confirm'] ?? '');

        if ($username === '' || $email === '' || $password === '' || $password_confirm === '') {
            self::redirect_error('missing_fields');
        }

        if (!validate_username($username)) {
            self::redirect_error('invalid_username');
        }

        if (!is_email($email)) {
            self::redirect_error('invalid_email');
        }

        if (strlen($password) < 8) {
            self::redirect_error('weak_password');
        }

        if ($password !== $password_confirm) {
            self::redirect_error('password_mismatch');
        }

        if (username_exists($username)) {
            self::redirect_error('username_exists');
        }

        if (email_exists($email)) {
            self::redirect_error('email_exists');
        }

        $user_id = wp_create_user($username, $password, $email);
        if (is_wp_error($user_id)) {
            self::redirect_error('registration_failed');
        }

        wp_set_current_user($user_id);
        wp_set_auth_cookie($user_id, true);

        wp_safe_redirect(add_query_arg('registered', '1', home_url('/catgame/profile')));
        exit;
    }

    public static function registration_error_message(string $code): string {
        $messages = [
            'missing_fields' => 'Completa todos los campos.',
            'invalid_username' => 'El usuario es inválido. Usa solo letras, números, guion o guion bajo.',
            'invalid_email' => 'El correo no es válido.',
            'weak_password' => 'La contraseña debe tener al menos 8 caracteres.',
            'password_mismatch' => 'Las contraseñas no coinciden.',
            'username_exists' => 'Ese nombre de usuario ya existe.',
            'email_exists' => 'Ese email ya está registrado.',
            'registration_failed' => 'No se pudo crear la cuenta. Intenta de nuevo.',
        ];

        return $messages[$code] ?? 'Error de registro.';
    }

    private static function redirect_error(string $error): void {
        wp_safe_redirect(add_query_arg('register_error', $error, home_url('/catgame/profile')));
        exit;
    }
}
