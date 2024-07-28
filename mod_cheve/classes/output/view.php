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
 * Prints a particular instance of cheve
 *
 * @package    mod_cheve
 * @copyright  202 Richard Jones richardnz@outlook.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @see https://github.com/moodlehq/moodle-mod_cheve
 * @see https://github.com/justinhunt/moodle-mod_cheve
 */

namespace mod_cheve\output;

use renderable;
use renderer_base;
use templatable;
use stdClass;

/**
 * cheve: Create a new view page renderable object
 *
 * @param object simnplemod - instance of cheve.
 * @param int id - course module id.
 * @copyright  2020 Richard Jones <richardnz@outlook.com>
 */

class view implements renderable, templatable {

    protected $cheve;
    protected $id;

    public function __construct($cheve, $id) {

        $this->cheve = $cheve;
        $this->id = $id;
    }
    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @param renderer_base $output
     * @return stdClass
     */
    public function export_for_template(renderer_base $output) {

        $data = new stdClass();

        $data->title = $this->cheve->title;
        // Moodle handles processing of std intro field.
        $data->body = format_module_intro('cheve',
                $this->cheve, $this->id);

        $cmid = $this->id;
        $context = \context_module::instance($cmid);
        $fs = get_file_storage();
        $files = $fs->get_area_files($context->id, 'mod_cheve', 'lecturevid', false, '', false);

        $data->file_original = '';
        $data->file_subbed = '';
        $data->file_subtitle = '';
        $data->has_original = false;
        $data->has_subbed = false;
        $data->has_subtitle = false;

        foreach($files as $file){
            $url = \moodle_url::make_pluginfile_url(
                $file->get_contextid(),
                $file->get_component(),
                $file->get_filearea(),
                $file->get_itemid(),
                $file->get_filepath(),
                $file->get_filename()
            );
            $suffix = substr($file->get_filename(),-8);
            if($suffix == "_ORI.mp4"){
                $data->file_original = $url;
                $data->has_original = true;
            }
            if($suffix == "_ANI.mp4"){
                $data->file_subbed = $url;
                $data->has_subbed = true;
            }
            if($suffix == "_SUB.txt"){
                $data->file_subtitle = $url;
                $data->has_subtitle = true;
            }
        }

        return $data;
    }
}
