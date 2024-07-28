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

class download_from_ws extends \core\task\adhoc_task {
    public function execute() {
        $data = $this->get_custom_data();
        $data_arr = array(
            'contextid' => $data->contextid,
            'component' => $data->component,
            'filearea' => $data->filearea,
            'mimetype' => $data->mimetype,
            'itemid' => 0,
            'filepath' => '/',
            'filename' => $data->filename,
        );

        $target_url = 'http://127.0.0.1:8000'.'/'.$data->url.'/'.$data->contextid.'/';

        $fs = get_file_storage();
        
        $fs->create_file_from_url($data_arr,$target_url);
    }
}
