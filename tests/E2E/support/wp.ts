import { execFileSync } from 'node:child_process';
import { resolve } from 'node:path';

/** Marks the start of the JSON result, so notices WP-CLI prints before it don't break parsing. */
const MARKER = '__WK_E2E_RESULT__';

export function siteUrl(): string {
    const url = process.env.WK_E2E_URL ?? '';
    if (!url) {
        throw new Error('WK_E2E_URL is not set in .env.local.tests; see .env.local.tests.example.');
    }

    return url.endsWith('/') ? url : `${url}/`;
}

/**
 * Runs PHP on the test site with `wp eval` and returns what it returns, as JSON.
 * The PHP reads the arguments from $args and must `return` its result.
 */
export function wp<T>(php: string, args: Record<string, unknown> = {}): T {
    const code = `
        if ('local' !== wp_get_environment_type()) {
            WP_CLI::error('The browser tests only run against a local site (WP_ENVIRONMENT_TYPE local).');
        }
        $args = json_decode((string) getenv('WK_E2E_ARGS'), true);
        $result = (function (array $args) { ${php} })($args);
        if (is_wp_error($result)) {
            WP_CLI::error($result->get_error_message());
        }
        echo '${MARKER}' . wp_json_encode($result);
    `;

    const output = execFileSync('wp', [`--url=${siteUrl()}`, 'eval', code], {
        cwd: resolve(process.env.WK_E2E_WP_PATH || '../../..'),
        env: { ...process.env, WK_E2E_ARGS: JSON.stringify(args) },
        encoding: 'utf8',
    });

    const start = output.lastIndexOf(MARKER);
    if (start === -1) {
        throw new Error(`No result from wp eval:\n${output}`);
    }

    return JSON.parse(output.slice(start + MARKER.length)) as T;
}

/**
 * A local admin only for these tests, with a fresh password per run. On multisite a super admin,
 * so Gravity Forms' capabilities never get in the way. The wp() guard keeps it off non-local sites.
 */
export function ensureAdmin(): { login: string; password: string; loginUrl: string } {
    return wp(`
        $login    = 'wk-e2e';
        $password = wp_generate_password(24, false);
        $user     = get_user_by('login', $login);
        $user_id  = $user ? $user->ID : wp_insert_user([
            'user_login' => $login,
            'user_pass'  => $password,
            'user_email' => 'wk-e2e@example.test',
            'role'       => 'administrator',
        ]);
        if (is_wp_error($user_id)) {
            return $user_id;
        }
        wp_set_password($password, $user_id);
        if (is_multisite()) {
            add_user_to_blog(get_current_blog_id(), $user_id, 'administrator');
            grant_super_admin($user_id);
        }

        // Subsites' own wp-login.php redirects to itself locally; the network login covers every subsite.
        return ['login' => $login, 'password' => $password, 'loginUrl' => network_site_url('wp-login.php')];
    `);
}

export const SUBSCRIBE_FIELD_ID = 2;
export const OTHER_SUBSCRIBE_FIELD_ID = 3;

/** A throwaway form with an email field and two subscribe checkboxes, or only the first, without a feed. */
export function createForm(subscribeFields: 1 | 2 = 2): number {
    return wp(`
        $fields = [
            ['type' => 'email', 'id' => 1, 'label' => 'E-mail'],
            ['type' => 'wk_subscribe', 'id' => ${SUBSCRIBE_FIELD_ID}, 'label' => 'Nieuwsbrief'],
            ['type' => 'wk_subscribe', 'id' => ${OTHER_SUBSCRIBE_FIELD_ID}, 'label' => 'Evenementen'],
        ];

        return GFAPI::add_form([
            'title'  => 'WK E2E ' . gmdate('Y-m-d H:i:s'),
            'fields' => array_slice($fields, 0, 1 + (int) $args['subscribe_fields']),
        ]);
    `, { subscribe_fields: subscribeFields });
}

/** Deletes the form, and its feeds with it. */
export function deleteForm(formId: number): void {
    wp(`return GFAPI::delete_form((int) $args['form_id']);`, { form_id: formId });
}

export type SavedFeed = { id: number; subscribe_field: string; wk_themes: Record<string, string[]> };

/** The form's only Wereldkundig feed as stored, read fresh from the database; null without one. */
export function savedFeed(formId: number): SavedFeed | null {
    return wp(`
        $feeds = GFAPI::get_feeds(null, (int) $args['form_id'], 'wereldkundig');
        if (is_wp_error($feeds) || ! $feeds) {
            return null;
        }

        // An empty PHP array is a JSON list; an object keeps the theme map a map.
        return [
            'id'              => (int) $feeds[0]['id'],
            'subscribe_field' => (string) rgars($feeds[0], 'meta/subscribe_field'),
            'wk_themes'       => (object) (array) rgars($feeds[0], 'meta/wk_themes'),
        ];
    `, { form_id: formId });
}

export type ThemeWithCategories = { theme: string; category: string; selectAll: string };

/**
 * The first cached theme with an "Alles in …" parent and a child category, as the feed settings show them.
 * Null when the cache has none; saving the Wereldkundig settings fills it.
 */
export function themeWithChildCategory(): ThemeWithCategories | null {
    return wp(`
        foreach ((new \\Wereldkundig\\GravityForms\\Api\\Client())->themes_setup(false, false) as $theme) {
            foreach (array_keys($theme['categories']) as $category) {
                if (preg_match('/^category-(\\d+)-\\d+$/', $category, $match) && isset($theme['categories']['select-all-' . $match[1]])) {
                    return ['theme' => $theme['id'], 'category' => $category, 'selectAll' => 'select-all-' . $match[1]];
                }
            }
        }

        return null;
    `);
}
