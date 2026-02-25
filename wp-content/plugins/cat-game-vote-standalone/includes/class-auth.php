<?php

if (!defined('ABSPATH')) {
    exit;
}

class CatGame_Auth {
    private static bool $password_reset_from_plugin = false;

    public static function init(): void {
        add_action('admin_post_nopriv_catgame_login', [__CLASS__, 'handle_login']);
        add_action('admin_post_catgame_login', [__CLASS__, 'handle_login']);
        add_action('admin_post_nopriv_catgame_register', [__CLASS__, 'handle_register']);
        add_action('admin_post_catgame_register', [__CLASS__, 'handle_register']);
        add_action('admin_post_nopriv_catgame_lost_password', [__CLASS__, 'handle_lost_password']);
        add_action('admin_post_catgame_lost_password', [__CLASS__, 'handle_lost_password']);
        add_action('admin_post_nopriv_catgame_reset_password', [__CLASS__, 'handle_reset_password']);
        add_action('admin_post_catgame_reset_password', [__CLASS__, 'handle_reset_password']);
        add_action('admin_post_catgame_logout', [__CLASS__, 'handle_logout']);
        add_action('admin_post_catgame_profile_update', [__CLASS__, 'handle_profile_update']);
        add_filter('retrieve_password_message', [__CLASS__, 'filter_retrieve_password_message'], 10, 4);
    }

    public static function handle_login(): void {
        if (is_user_logged_in()) {
            wp_safe_redirect(home_url('/catgame/profile'));
            exit;
        }

        check_admin_referer('catgame_login');

        $identifier = sanitize_text_field(wp_unslash($_POST['login_identifier'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        if ($identifier === '' || $password === '') {
            self::redirect_auth(['auth' => 'login', 'login_error' => 'missing_fields', 'login_identifier' => $identifier]);
        }

        $signon = wp_signon(
            [
                'user_login' => $identifier,
                'user_password' => $password,
                'remember' => true,
            ],
            is_ssl()
        );

        if (is_wp_error($signon)) {
            self::redirect_auth(['auth' => 'login', 'login_error' => 'invalid_credentials', 'login_identifier' => $identifier]);
        }

        self::redirect_after_auth((int) $signon->ID);
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

        $base = ['auth' => 'register', 'reg_username' => $username, 'reg_email' => $email];

        if ($username === '' || $email === '' || $password === '' || $password_confirm === '') {
            self::redirect_auth($base + ['register_error' => 'missing_fields']);
        }

        if (!validate_username($username)) {
            self::redirect_auth($base + ['register_error' => 'invalid_username']);
        }

        if (!is_email($email)) {
            self::redirect_auth($base + ['register_error' => 'invalid_email']);
        }

        if (strlen($password) < 8) {
            self::redirect_auth($base + ['register_error' => 'weak_password']);
        }

        if ($password !== $password_confirm) {
            self::redirect_auth($base + ['register_error' => 'password_mismatch']);
        }

        if (username_exists($username)) {
            self::redirect_auth($base + ['register_error' => 'username_exists']);
        }

        if (email_exists($email)) {
            self::redirect_auth($base + ['register_error' => 'email_exists']);
        }

        $user_id = wp_insert_user([
            'user_login' => $username,
            'user_email' => $email,
            'user_pass' => $password,
            'role' => 'subscriber',
        ]);

        if (is_wp_error($user_id)) {
            self::redirect_auth($base + ['register_error' => 'registration_failed']);
        }

        wp_set_current_user((int) $user_id);
        wp_set_auth_cookie((int) $user_id, true);

        self::redirect_after_auth((int) $user_id, ['registered' => '1']);
    }

    public static function handle_lost_password(): void {
        if (is_user_logged_in()) {
            wp_safe_redirect(home_url('/catgame/profile'));
            exit;
        }

        check_admin_referer('catgame_lost_password');

        $identifier = sanitize_text_field(wp_unslash($_POST['lost_identifier'] ?? ''));
        if ($identifier === '') {
            self::redirect_auth(['auth' => 'forgot', 'lost_error' => 'missing_identifier', 'lost_identifier' => $identifier]);
        }

        self::$password_reset_from_plugin = true;
        $result = retrieve_password($identifier);
        self::$password_reset_from_plugin = false;

        if (is_wp_error($result)) {
            self::redirect_auth(['auth' => 'forgot', 'lost_sent' => '1', 'lost_identifier' => $identifier]);
        }

        self::redirect_auth(['auth' => 'forgot', 'lost_sent' => '1', 'lost_identifier' => $identifier]);
    }

    public static function handle_reset_password(): void {
        if (is_user_logged_in()) {
            wp_safe_redirect(home_url('/catgame/profile'));
            exit;
        }

        check_admin_referer('catgame_reset_password');

        $login = sanitize_text_field(wp_unslash($_POST['rp_login'] ?? ''));
        $key = sanitize_text_field(wp_unslash($_POST['rp_key'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $password_confirm = (string) ($_POST['password_confirm'] ?? '');

        $base = ['auth' => 'reset', 'rp_login' => $login, 'key' => $key];

        $user = check_password_reset_key($key, $login);
        if (is_wp_error($user)) {
            self::redirect_auth(['auth' => 'forgot', 'lost_error' => 'invalid_reset_link']);
        }

        if ($password === '' || $password_confirm === '') {
            self::redirect_auth($base + ['reset_error' => 'missing_fields']);
        }

        if (strlen($password) < 8) {
            self::redirect_auth($base + ['reset_error' => 'weak_password']);
        }

        if ($password !== $password_confirm) {
            self::redirect_auth($base + ['reset_error' => 'password_mismatch']);
        }

        reset_password($user, $password);

        self::redirect_auth(['auth' => 'login', 'password_reset' => '1']);
    }

    public static function filter_retrieve_password_message(string $message, string $key, string $user_login, WP_User $user_data): string {
        if (!self::$password_reset_from_plugin) {
            return $message;
        }

        $default_url = network_site_url('wp-login.php?action=rp&key=' . $key . '&login=' . rawurlencode($user_login), 'login');
        $plugin_url = add_query_arg(
            [
                'auth' => 'reset',
                'key' => $key,
                'rp_login' => $user_login,
            ],
            home_url('/catgame/profile')
        );

        return str_replace($default_url, $plugin_url, $message);
    }

    public static function handle_logout(): void {
        if (!is_user_logged_in()) {
            wp_safe_redirect(home_url('/catgame/profile'));
            exit;
        }

        check_admin_referer('catgame_logout');
        wp_logout();
        wp_set_current_user(0);

        wp_safe_redirect(home_url('/catgame/profile'));
        exit;
    }

    public static function handle_profile_update(): void {
        if (!is_user_logged_in()) {
            wp_safe_redirect(home_url('/catgame/profile'));
            exit;
        }

        check_admin_referer('catgame_profile_update');

        $user_id = get_current_user_id();
        $avatar_color = sanitize_key(wp_unslash($_POST['avatar_color'] ?? 'rose'));
        $location = self::sanitize_location_values(
            wp_unslash($_POST['default_city'] ?? ''),
            wp_unslash($_POST['default_country'] ?? '')
        );
        $city = $location['city'];
        $country = $location['country'];

        $allowed_avatar_colors = ['rose', 'mint', 'lavender', 'yellow', 'sky'];
        if (!in_array($avatar_color, $allowed_avatar_colors, true)) {
            $avatar_color = 'rose';
        }

        if ($city === '' || $country === '') {
            $query = [
                'complete_profile' => '1',
                'profile_error' => 'missing_location',
                'profile_city' => $city,
                'profile_country' => $country,
                'profile_avatar' => $avatar_color,
            ];
            wp_safe_redirect(add_query_arg($query, home_url('/catgame/profile')));
            exit;
        }

        update_user_meta($user_id, 'catgame_avatar_color', $avatar_color);
        update_user_meta($user_id, 'catgame_default_city', $city);
        update_user_meta($user_id, 'catgame_default_country', $country);

        wp_safe_redirect(add_query_arg('profile_saved', '1', home_url('/catgame/profile')));
        exit;
    }

    public static function get_user_default_location(int $user_id): array {
        if ($user_id <= 0) {
            return ['city' => '', 'country' => ''];
        }

        return self::sanitize_location_values(
            get_user_meta($user_id, 'catgame_default_city', true),
            get_user_meta($user_id, 'catgame_default_country', true)
        );
    }

    public static function has_user_default_location(int $user_id): bool {
        $location = self::get_user_default_location($user_id);
        return $location['city'] !== '' && $location['country'] !== '';
    }

    private static function sanitize_location_values($city_raw, $country_raw): array {
        $city = trim(sanitize_text_field((string) $city_raw));
        $country = trim(sanitize_text_field((string) $country_raw));

        if (function_exists('mb_substr')) {
            $city = mb_substr($city, 0, 120);
            $country = mb_substr($country, 0, 120);
        } else {
            $city = substr($city, 0, 120);
            $country = substr($country, 0, 120);
        }

        return [
            'city' => $city,
            'country' => $country,
        ];
    }

    private static function redirect_after_auth(int $user_id, array $extra_query = []): void {
        $query = $extra_query;
        if (!self::has_user_default_location($user_id)) {
            $query['complete_profile'] = '1';
        }

        $target = empty($query)
            ? home_url('/catgame/profile')
            : add_query_arg($query, home_url('/catgame/profile'));

        wp_safe_redirect($target);
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

    public static function login_error_message(string $code): string {
        $messages = [
            'missing_fields' => 'Completa usuario/correo y contraseña.',
            'invalid_credentials' => 'Usuario/correo o contraseña incorrectos.',
        ];

        return $messages[$code] ?? 'No se pudo iniciar sesión.';
    }

    public static function lost_password_message(string $code): string {
        $messages = [
            'missing_identifier' => 'Ingresa usuario o correo.',
            'invalid_reset_link' => 'El enlace de recuperación no es válido o ya expiró.',
        ];

        return $messages[$code] ?? '';
    }

    public static function reset_password_message(string $code): string {
        $messages = [
            'missing_fields' => 'Completa y confirma la nueva contraseña.',
            'weak_password' => 'La nueva contraseña debe tener al menos 8 caracteres.',
            'password_mismatch' => 'Las contraseñas no coinciden.',
        ];

        return $messages[$code] ?? 'No se pudo restablecer la contraseña.';
    }

    private static function redirect_auth(array $query_args): void {
        wp_safe_redirect(add_query_arg($query_args, home_url('/catgame/profile')));
        exit;
    }
}
