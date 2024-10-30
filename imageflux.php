<?php
/*
  Plugin Name: ImageFlux
  Plugin URI:
  Description: WordPressサイトの画像を一括で最適化・軽量化し、CDNを通して配信します。
  Version: 2.0.0
  Author: SAKURA internet Inc.
  Author URI: https://imageflux.sakura.ad.jp/image/
  License: GPLv2
 */

// 設定ページのリンクをサイドバーに追加する
function imageflux_settings_menu() {
    add_menu_page(
        'ImageFlux設定',
        'ImageFlux',
        'manage_options',
        'imageflux_settings',
        'imageflux_settings_page',
        'dashicons-format-image',
        99
    );
}
add_action('admin_menu', 'imageflux_settings_menu');

// プラグインの設定ページを作成する
function imageflux_settings_page() {
    if (!current_user_can('manage_options')) {
        wp_die('このページにアクセスする十分な権限がありません。');
    }

    // 除外する拡張子の設定値を取得し、配列でなければ空の配列を設定
	$exclude_extensions = get_option('imageflux_exclude_extensions', array());
    if (!is_array($exclude_extensions)) {
        $exclude_extensions = array();
    }
	
    ?>
    <div class="wrap">
        <h1 style="display: flex; align-items: center;">
            ImageFlux設定
            <div style="margin-left: 1.5cm;">
                <a href="https://console.imageflux.jp/docs/wordpress" target="_blank" style="font-size: 12px; text-decoration: none;">＜ドキュメント＞</a>
                <a href="https://console.imageflux.jp/origin/index" target="_blank" style="font-size: 12px; text-decoration: none; margin-left: 10px;">＜管理コンソール＞</a>
            </div>
        </h1>
        <form method="post" action="options.php">
            <?php settings_fields('imageflux_settings'); ?>
            <?php do_settings_sections('imageflux_settings'); ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">画像の格納先</th>
                    <td>
                        <select name="imageflux_storage">
                            <option value="http" <?php selected(get_option('imageflux_storage'), 'http'); ?>>WordPressディレクトリ</option>
                            <option value="s3" <?php selected(get_option('imageflux_storage'), 's3'); ?>>Amazon S3</option>
                        </select>
                        <span class="tooltip" data-tooltip="管理コンソールで設定したオリジン情報に合わせてください。"></span>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">ImageFluxドメイン</th>
                    <td>
                        <input type="text" name="imageflux_domain" value="<?php echo esc_attr(get_option('imageflux_domain')); ?>" placeholder="pX-XXXXX.imageflux.jp" />
                        <span class="tooltip" data-tooltip="管理コンソールで発行されたホスト名を入力してください。入力しない限り変換は行いません。"></span>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">フォーマット変換</th>
                    <td>
                        <select name="imageflux_format">
                            <option value="auto" <?php selected(get_option('imageflux_format'), 'auto'); ?>>auto</option>
                            <option value="jpg" <?php selected(get_option('imageflux_format'), 'jpg'); ?>>jpg</option>
                            <option value="png" <?php selected(get_option('imageflux_format'), 'png'); ?>>png</option>
                            <option value="gif" <?php selected(get_option('imageflux_format'), 'gif'); ?>>gif</option>
                            <option value="webp" <?php selected(get_option('imageflux_format'), 'webp'); ?>>webp</option>
                            <option value="webp:auto" <?php selected(get_option('imageflux_format'), 'webp:auto'); ?>>webp:auto</option>
                            <option value="webp:jpg" <?php selected(get_option('imageflux_format'), 'webp:jpg'); ?>>webp:jpg</option>
                            <option value="webp:png" <?php selected(get_option('imageflux_format'), 'webp:png'); ?>>webp:png</option>
                            <option value="webp:gif" <?php selected(get_option('imageflux_format'), 'webp:gif'); ?>>webp:gif</option>
                        </select>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">除外する拡張子</th>
                    <td>
                        <label><input type="checkbox" name="imageflux_exclude_extensions[]" value="jpeg" <?php checked(in_array('jpeg', $exclude_extensions), true); ?>> jpeg</label>
                        <label><input type="checkbox" name="imageflux_exclude_extensions[]" value="png" <?php checked(in_array('png', $exclude_extensions), true); ?>> png</label>
                        <label><input type="checkbox" name="imageflux_exclude_extensions[]" value="gif" <?php checked(in_array('gif', $exclude_extensions), true); ?>> gif</label>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">画質</th>
                    <td><input type="number" name="imageflux_quality" value="<?php echo esc_attr(get_option('imageflux_quality', 70)); ?>" min="1" max="100" /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Exif情報の削除 (JPEG のみ)</th>
                    <td>
                        <select name="imageflux_exif_removal">
                            <option value="" <?php selected(get_option('imageflux_exif_removal'), ''); ?>>Exif情報を削除しない</option>
                            <option value="1" <?php selected(get_option('imageflux_exif_removal'), '1'); ?>>すべてのExif情報を削除する</option>
                            <option value="2" <?php selected(get_option('imageflux_exif_removal'), '2'); ?>>Orientation以外のExif情報を削除する</option>
                        </select>
                    </td>
                </tr>
            </table>
            <div id="message-container">
                <?php
                // 設定が保存された場合のメッセージを表示
                if (isset($_GET['settings-updated']) && $_GET['settings-updated'] === 'true') {
                    echo '<div id="message"><p>設定が保存されました。</p></div>';
                    echo '<script>
                        jQuery(document).ready(function($) {
                            $("#message").fadeIn(0, function() {
                                setTimeout(function() {
                                    $("#message").fadeOut(1000);
                                }, 2000);
                            });
                        });
                    </script>';
                }
                ?>
            </div>
            <?php submit_button(); ?>
        </form>
    </div>
    <style>
        #message-container {
            position: relative;
            margin-top: 20px;
        }
        #message {
            display: none;
            color: #155724;
            font-size: 14px;
            position: absolute;
            top: -30px;
            left: 0;
        }
        .tooltip {
            position: relative;
            display: inline-block;
            margin-left: 10px;
            cursor: help;
        }
        .tooltip:before {
            content: "?";
            display: inline-block;
            width: 16px;
            height: 16px;
            line-height: 16px;
            text-align: center;
            background-color: #000;
            color: #fff;
            font-size: 12px;
            border-radius: 50%;
            opacity: 0.6;
        }
        .tooltip:hover:after {
            content: attr(data-tooltip);
            position: absolute;
            left: 30px;
            top: -5px;
            min-width: 200px;
            padding: 5px;
            background-color: rgba(0, 0, 0, 0.8);
            color: #fff;
            font-size: 12px;
            border-radius: 4px;
            z-index: 1;
        }
        input[type="text"]::placeholder {
            color: #ccc;
        }
    </style>
    <?php
}

// 設定値を保存・取得する関数を作成する
function imageflux_settings_init() {
    register_setting('imageflux_settings', 'imageflux_storage');
    register_setting('imageflux_settings', 'imageflux_domain');
    register_setting('imageflux_settings', 'imageflux_format');
    register_setting('imageflux_settings', 'imageflux_exclude_extensions');
    register_setting('imageflux_settings', 'imageflux_quality');
    register_setting('imageflux_settings', 'imageflux_exif_removal');
}
add_action('admin_init', 'imageflux_settings_init');

function sacloud_replace_imageflux($content) {
    $imageflux_storage = get_option('imageflux_storage', 'http');
    $upload_dir = wp_upload_dir();
    $base_url = $upload_dir['baseurl'];
    preg_match_all('/<img.*?src\s*=\s*["\']([^"\']*?)["\'].*?>/i', $content, $images);
    if (!empty($images[1])) {
        foreach ($images[1] as $image_src) {
            $imageflux_domain = get_option('imageflux_domain', '');
            if (!empty($imageflux_domain)) {
                $params = array();

                $format = get_option('imageflux_format', 'auto');
                if ($format !== 'auto') {
                    $params[] = 'f=' . $format;
                }

                $exclude_extensions = get_option('imageflux_exclude_extensions', array());
                if (!empty($exclude_extensions)) {
                    $params[] = 'through=' . implode(':', $exclude_extensions);
                }

                $quality = get_option('imageflux_quality', 70);
                if ($quality !== 70) {
                    $params[] = 'q=' . $quality;
                }

                $exif_removal = get_option('imageflux_exif_removal');
                if ($exif_removal === '1') {
                    $params[] = 's=1';
                } elseif ($exif_removal === '2') {
                    $params[] = 's=2';
                }

                $params_string = implode(',', $params);

                // 選択された格納先に応じて画像URLを検知して置換
                if ($imageflux_storage === 'http') {
                    // WordPressのアップロードディレクトリ内の画像URLか判断して置換
                    if (strpos($image_src, $base_url) === 0) {
                        $relative_path = str_replace($base_url, '', $image_src);
                        $new_src = 'https://' . rtrim($imageflux_domain, '/') . '/c/' . (!empty($params_string) ? $params_string . '/' : '') . ltrim($relative_path, '/');
                        $content = str_replace($image_src, $new_src, $content);
                    }
                } elseif ($imageflux_storage === 's3') {
                    // S3由来の画像URLか判断して置換
                    if (strpos($image_src, 'amazonaws.com') !== false || strpos($image_src, 'sakurastorage.jp') !== false) {
                        $parsed_url = parse_url($image_src);
                        $relative_path = ltrim($parsed_url['path'], '/');
                        $new_src = 'https://' . rtrim($imageflux_domain, '/') . '/c/' . (!empty($params_string) ? $params_string . '/' : '') . $relative_path;
                        $content = str_replace($image_src, $new_src, $content);
                    }
                }
            }
        }
    }
    return $content;
}

// フィルターの追加
add_filter('wp_lazy_loading_enabled', '__return_false');
add_filter('wp_calculate_image_srcset_meta', '__return_null');
add_filter('the_content', 'sacloud_replace_imageflux');
add_filter('post_thumbnail_html', 'sacloud_replace_imageflux');
