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
 * The main cheve configuration form
 *
 * It uses the standard core Moodle formslib. For more info about them, please
 * visit: http://docs.moodle.org/en/Development:lib/formslib.php
 *
 * @package    mod_cheve
 * @copyright  2019 Richard Jones richardnz@outlook.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @see https://github.com/moodlehq/moodle-mod_cheve
 * @see https://github.com/justinhunt/moodle-mod_cheve */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/course/moodleform_mod.php');

/**
 * Module instance settings form
 *
 * @package    mod_cheve
 * @copyright  2019 Richard Jones richardnz@outlook.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_cheve_mod_form extends moodleform_mod {

    /**
     * Defines forms elements
     */
    public function definition() {
        global $CFG;

        $mform = $this->_form;

        // Adding the "general" fieldset, where all the common settings are showed.
        $mform->addElement('header', 'general', get_string('general', 'form'));

        // Adding the standard "name" field.
        $mform->addElement('text', 'name', get_string('chevename', 'cheve'), array('size' => '64'));
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
        }
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $mform->addHelpButton('name', 'chevename', 'cheve');

        // Adding Select for Video Type
        $mform->addElement('select', 'videotype', get_string('vidtype', 'cheve'), array('Synchronous','Asynchronous'));

        // Adding language field.
        $mform->addElement('select', 'language', get_string('lang', 'cheve'), array('SIBI','BISINDO'));
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('language', PARAM_TEXT);
        } else {
            $mform->setType('language', PARAM_CLEANHTML);
        }

        // Adding Select for Persona Gender
        $mform->addElement('select', 'personagender', get_string('gender', 'cheve'), array('Male','Female'));

        
        
        // Adding filemanager
        $options = [];
        $options['accepted_types'] = ['video'];
        $options['maxbytes'] = 0;
        $options['maxfiles'] = 1;
        $options['subdirs'] = 0;

        $mform->addElement('filemanager', 'lecturevid', get_string('lecturevid', 'cheve'), null, $options);
        
        
        // Adding the standard "intro" and "introformat" fields.
        $this->standard_intro_elements();

        // Add a specific mod_cheve field - title.
        $mform->addElement('text', 'title',
                get_string('title', 'mod_cheve'));
        $mform->setType('title', PARAM_TEXT);

        // Add standard grading elements.
        $this->standard_grading_coursemodule_elements();

        // Add standard elements, common to all modules.
        $this->standard_coursemodule_elements();

        // Add standard buttons, common to all modules.
        $this->add_action_buttons();

        // if (empty($entry->id)) {
        //     $entry = new stdClass;
        //     $entry->id = null;
        // }
        
        // $draftitemid = file_get_submitted_draft_itemid('lecturevid');
        
        // file_prepare_draft_area($draftitemid, $context->id, 'mod_cheve', 'lecturevid', $entry->id,
        //                         array('subdirs' => 0, 'maxbytes' => $maxbytes, 'maxfiles' => 50));
        
        // $entry->attachments = $draftitemid;
        
        // $mform->set_data($entry);

        // if ($data = $mform->get_data()) {
        //     // ... store or update $entry
        //     file_save_draft_area_files($data->lecturevid, $context->id, 'mod_cheve', 'lecturevid',
        //                    $entry->id, array('subdirs' => 0, 'maxbytes' => $maxbytes, 'maxfiles' => 50));
        // }
    }
}
