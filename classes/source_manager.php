<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * source_manager.php
 *
 * @package   mod_videoerrorhunt
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_videoerrorhunt;

use context_module;
use moodle_exception;
use moodle_url;
use stdClass;

/**
 * Video source parsing and player data.
 */
class source_manager {
    /**
     * Method options.
     *
     * @return array Return value.
     */
    public static function options(): array {
        return [
            'upload' => get_string('sourceupload', 'videoerrorhunt'),
            'url' => get_string('sourceurl', 'videoerrorhunt'),
            'youtube' => get_string('sourceyoutube', 'videoerrorhunt'),
            'vimeo' => get_string('sourcevimeo', 'videoerrorhunt'),
        ];
    }

    /**
     * Method normalise.
     *
     * @param stdClass $data Parameter data.
     * @return void Return value.
     */
    public static function normalise(stdClass $data): void {
        $source = $data->videosource ?? 'upload';
        $config = [];
        if ($source === 'url') {
            $url = trim((string)($data->directurl ?? $data->videourl ?? ''));
            if (!self::valid_http_url($url)) {
                throw new moodle_exception('invalidvideourl', 'videoerrorhunt');
            }
            $data->videourl = $url;
            $config = ['url' => $url];
        } else if ($source === 'youtube') {
            $url = trim((string)($data->youtubeurl ?? $data->videourl ?? ''));
            $id = self::youtube_id($url);
            $data->videourl = $url;
            $config = ['id' => $id];
        } else if ($source === 'vimeo') {
            $url = trim((string)($data->vimeourl ?? $data->videourl ?? ''));
            $config = self::vimeo_config($url);
            $data->videourl = $url;
        } else {
            $data->videosource = 'upload';
            $data->videourl = '';
        }
        $data->sourceconfig = json_encode($config, JSON_UNESCAPED_SLASHES);
        unset($data->directurl, $data->youtubeurl, $data->vimeourl);
    }

    /**
     * Method prepare_form.
     *
     * @param stdClass $activity Parameter activity.
     * @param array $defaults Parameter defaults.
     * @return void Return value.
     */
    public static function prepare_form(stdClass $activity, array &$defaults): void {
        $config = json_decode((string)$activity->sourceconfig, true) ?: [];
        if ($activity->videosource === 'url') {
            $defaults['directurl'] = $config['url'] ?? $activity->videourl;
        } else if ($activity->videosource === 'youtube') {
            $defaults['youtubeurl'] = $activity->videourl;
        } else if ($activity->videosource === 'vimeo') {
            $defaults['vimeourl'] = $activity->videourl;
        }
    }

    /**
     * Method player_data.
     *
     * @param stdClass $activity Parameter activity.
     * @param context_module $context Parameter context.
     * @return array Return value.
     */
    public static function player_data(stdClass $activity, context_module $context): array {
        global $CFG;
        $config = json_decode((string)$activity->sourceconfig, true) ?: [];
        $data = [
            'isupload' => false, 'isurl' => false, 'isyoutube' => false, 'isvimeo' => false,
            'url' => '', 'youtubeid' => '', 'vimeoid' => '', 'vimeohash' => '', 'vimeoembedurl' => '',
        ];
        if ($activity->videosource === 'upload') {
            $fs = get_file_storage();
            $files = $fs->get_area_files($context->id, 'mod_videoerrorhunt', 'video', 0, 'id', false);
            $file = reset($files);
            if ($file) {
                $data['isupload'] = true;
                $data['url'] = moodle_url::make_pluginfile_url(
                    $context->id, 'mod_videoerrorhunt', 'video', 0, '/', $file->get_filename()
                )->out(false);
            }
        } else if ($activity->videosource === 'url') {
            $data['isurl'] = true;
            $data['url'] = (string)($config['url'] ?? $activity->videourl);
        } else if ($activity->videosource === 'youtube') {
            $data['isyoutube'] = true;
            $data['youtubeid'] = (string)($config['id'] ?? '');
        } else if ($activity->videosource === 'vimeo') {
            $data['isvimeo'] = true;
            $data['vimeoid'] = (string)($config['id'] ?? '');
            $data['vimeohash'] = (string)($config['hash'] ?? '');
            $data['vimeoembedurl'] = 'https://player.vimeo.com/video/' . rawurlencode($data['vimeoid'])
                . ($data['vimeohash'] !== '' ? '?h=' . rawurlencode($data['vimeohash']) : '');
        }
        return $data;
    }

    /**
     * Method valid_http_url.
     *
     * @param string $url Parameter url.
     * @return bool Return value.
     */
    private static function valid_http_url(string $url): bool {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }
        return in_array(strtolower((string)parse_url($url, PHP_URL_SCHEME)), ['http', 'https'], true);
    }

    /**
     * Method youtube_id.
     *
     * @param string $url Parameter url.
     * @return string Return value.
     */
    private static function youtube_id(string $url): string {
        if (!self::valid_http_url($url)) {
            throw new moodle_exception('invalidyoutubeurl', 'videoerrorhunt');
        }
        $host = strtolower((string)parse_url($url, PHP_URL_HOST));
        $path = trim((string)parse_url($url, PHP_URL_PATH), '/');
        $id = '';
        if (in_array($host, ['youtu.be', 'www.youtu.be'], true)) {
            $id = explode('/', $path)[0] ?? '';
        } else if (in_array($host, ['youtube.com', 'www.youtube.com', 'm.youtube.com',
            'youtube-nocookie.com', 'www.youtube-nocookie.com'], true)) {
            parse_str((string)parse_url($url, PHP_URL_QUERY), $query);
            $id = (string)($query['v'] ?? '');
            if ($id === '' && preg_match('~(?:embed|shorts)/([A-Za-z0-9_-]{6,20})~', $path, $matches)) {
                $id = $matches[1];
            }
        }
        if (!preg_match('/^[A-Za-z0-9_-]{6,20}$/', $id)) {
            throw new moodle_exception('invalidyoutubeurl', 'videoerrorhunt');
        }
        return $id;
    }

    /**
     * Method vimeo_config.
     *
     * @param string $url Parameter url.
     * @return array Return value.
     */
    private static function vimeo_config(string $url): array {
        if (!self::valid_http_url($url)) {
            throw new moodle_exception('invalidvimeourl', 'videoerrorhunt');
        }
        $host = strtolower((string)parse_url($url, PHP_URL_HOST));
        if (!in_array($host, ['vimeo.com', 'www.vimeo.com', 'player.vimeo.com'], true)) {
            throw new moodle_exception('invalidvimeourl', 'videoerrorhunt');
        }
        $path = trim((string)parse_url($url, PHP_URL_PATH), '/');
        if (!preg_match('~^(?:video/)?(\\d+)(?:/([A-Za-z0-9]+))?$~', $path, $matches)) {
            throw new moodle_exception('invalidvimeourl', 'videoerrorhunt');
        }
        parse_str((string)parse_url($url, PHP_URL_QUERY), $query);
        $hash = $matches[2] ?? '';
        if ($hash === '' && !empty($query['h']) && preg_match('/^[A-Za-z0-9]+$/', $query['h'])) {
            $hash = $query['h'];
        }
        return ['id' => $matches[1], 'hash' => $hash];
    }
}
