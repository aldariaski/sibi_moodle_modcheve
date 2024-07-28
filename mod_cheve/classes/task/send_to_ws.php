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
 * Adhoc task that updates all of the existing forum_post records with no wordcount or no charcount.
 *
 * @package    mod_forum
 * @copyright  2019 David Monllao
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_cheve\task;

defined('MOODLE_INTERNAL') || die();

/**
 * Adhoc task that updates all of the existing forum_post records with no wordcount or no charcount.
 *
 * @package     mod_cheve
 * @copyright   2019 David Monllao
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class send_to_ws extends \core\task\adhoc_task {
    public function execute() {
        $data = $this->get_custom_data();
        $data_arr = array(
            'batch_ID' => $data->batch_ID,
            'title' => $data->name,
            'class_type' => $data->videotype,
            'language' => $data->language,
            'main_speaker_gender' => $data->personagender,
            'study_major' => $data->lecturemajor,
            'course' => "idk",
            'subtitle_type' => "idk",
            "restriction" => "idk"
        );

        $ch = new \curl;
        $payload = json_encode($data_arr);
        $fs = get_file_storage();
        $file = $fs->get_file_by_hash($data->filehash);
        $ch->post('http://127.0.0.1:8000/add/', array(
            'video_file'=>$file,
            'batch_ID' => $data->batch_ID,
            'title' => $data->name,
            'class_type' => $data->videotype,
            'language' => $data->language,
            'main_speaker_gender' => $data->personagender,
            'study_major' => $data->lecturemajor,
            'course' => "idk",
            'subtitle_type' => "idk",
            "restriction" => "idk"
        ));
    }
}
