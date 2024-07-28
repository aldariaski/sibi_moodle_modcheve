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
 * Library of interface functions and constants for module cheve
 *
 * All the core Moodle functions, neeeded to allow the module to work
 * integrated in Moodle should be placed here.
 *
 * All the cheve specific functions, needed to implement all the module
 * logic, should go to locallib.php. This will help to save some memory when
 * Moodle is performing actions across all modules.
 *
 * @package    mod_cheve
 * @copyright  2019 Richard Jones richardnz@outlook.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @see https://github.com/moodlehq/moodle-mod_cheve
 * @see https://github.com/justinhunt/moodle-mod_cheve */

defined('MOODLE_INTERNAL') || die();

/* Moodle core API */

/**
 * Returns the information on whether the module supports a feature
 *
 * See {@link plugin_supports()} for more info.
 *
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed true if the feature is supported, null if unknown
 */
function cheve_supports($feature) {

    switch($feature) {
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_SHOW_DESCRIPTION:
            return true;
        case FEATURE_GRADE_HAS_GRADE:
            return false;
        case FEATURE_BACKUP_MOODLE2:
            return true;
        default:
            return null;
    }
}

/**
 * Saves a new instance of the cheve into the database
 *
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will create a new instance and return the id number
 * of the new instance.
 *
 * @param stdClass $cheve Submitted data from the form in mod_form.php
 * @param mod_cheve_mod_form $mform The form instance itself (if needed)
 * @return int The id of the newly inserted cheve record
 */
function cheve_add_instance(stdClass $cheve, mod_cheve_mod_form $mform = null) {
    global $DB;
    $cheve->timecreated = time();
    $cheve->id = $DB->insert_record('cheve', $cheve);
    $cmid = $cheve->coursemodule;
    $context = context_module::instance($cmid);


    if (!empty($cheve->lecturevid)) {
        $fs = get_file_storage();
        file_save_draft_area_files($cheve->lecturevid, $context->id, 'mod_cheve', 'lecturevid',
            0, array('subdirs' => 0, 'maxfiles' => 1));
        
        // Get file
        $files = $fs->get_area_files($context->id, 'mod_cheve', 'lecturevid', false, '', false);
        $file = reset($files);

        // OLD FILENAME
        $original_name = substr($file->get_filename(),0,-4);

        $new_filename = $original_name.'_ORI.mp4';
        $file->rename($file->get_filepath(),$new_filename);
        
        $suffix = substr($file->get_filename(),-8);
        
        $url = moodle_url::make_pluginfile_url(
            $file->get_contextid(),
            $file->get_component(),
            $file->get_filearea(),
            $file->get_itemid(),
            $file->get_filepath(),
            $file->get_filename()
        );

        $fhash = $file->get_pathnamehash();

        $data = array(
            'filesfrom' => 'URL',
            'batch_ID' => $context->id,
            'name' => $cheve->name,
            'videotype' => $cheve->videotype,
            'language' => $cheve->language,
            'personagender' => $cheve->personagender,
            'lecturemajor' => $cheve->lecturemajor,
            'filehash' => $fhash,
            'url' => $url->__toString(),
	    'asr' => 'Google',
	    'subtitle' => '',
        );

        $json_data = json_encode($data);

        $headers = array(
            'Content-Type: application/json',
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'http://localhost:8000/add');
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        // Execute the cURL request
        $response = curl_exec($ch);
        //sleep(1000);
        $message = json_decode($response, true)['message'];

        // Check for any cURL errors
        if (curl_errno($ch)) {
            $errorMessage = curl_error($ch);
        } else if ($message != "Request successful") {
            $errorMessage = $message;
        }
        
        if (!empty($errorMessage)) {
            $_SESSION['cheve_error'] = $errorMessage;
        }        

        // Close the cURL session
        curl_close($ch);


        # Downloading Animated Video
        $ani_filename = 'combined_'.$original_name;
        $downloadfile_ani = new mod_cheve\task\download_from_ws();
        $downloadfile_ani->set_custom_data(array(
            'contextid' => $context->id,
            'component' => 'mod_cheve',
            'filearea' => 'lecturevid',
            'mimetype' => 'video/mp4',
            'filename' => $ani_filename,
            'url' => 'animation'
        ));
        \core\task\manager::queue_adhoc_task($downloadfile_ani);


        # Downloading Subtitle
        $sub_filename = $original_name.'_SUB.txt';
        $downloadfile_sub = new mod_cheve\task\download_from_ws();
        $downloadfile_sub->set_custom_data(array(
            'contextid' => $context->id,
            'component' => 'mod_cheve',
            'filearea' => 'lecturevid',
            'mimetype' => 'text/*',
            'filename' => $sub_filename,
            'url' => 'subtitle'
        ));
        \core\task\manager::queue_adhoc_task($downloadfile_sub);

        return $cheve->id;
    }


}



/**
 * Updates an instance of the cheve in the database
 *
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will update an existing instance with new data.
 *
 * @param stdClass $cheve An object from the form in mod_form.php
 * @param mod_cheve_mod_form $mform The form instance itself (if needed)
 * @return boolean Success/Fail
 */
function cheve_update_instance(stdClass $cheve, mod_cheve_mod_form $mform = null) {
    global $DB;

    $cheve->timemodified = time();
    $cheve->id = $cheve->instance;

    $result = $DB->update_record('cheve', $cheve);

    return $result;
}

/**
 * This standard function will check all instances of this module
 * and make sure there are up-to-date events created for each of them.
 * If courseid = 0, then every cheve event in the site is checked, else
 * only cheve events belonging to the course specified are checked.
 * This is only required if the module is generating calendar events.
 *
 * @param int $courseid Course ID
 * @return bool
 */
function cheve_refresh_events($courseid = 0) {
    global $DB;

    if ($courseid == 0) {
        if (!$cheves = $DB->get_records('cheve')) {
            return true;
        }
    } else {
        if (!$cheves = $DB->get_records('cheve', array('course' => $courseid))) {
            return true;
        }
    }

    foreach ($cheves as $cheve) {
        // Create a function such as the one below to deal with updating calendar events.
        // cheve_update_events($cheve);
    }

    return true;
}

/**
 * Removes an instance of the cheve from the database
 *
 * Given an ID of an instance of this module,
 * this function will permanently delete the instance
 * and any data that depends on it.
 *
 * @param int $id Id of the module instance
 * @return boolean Success/Failure
 */
function cheve_delete_instance($id) {
    global $DB;

    if (! $cheve = $DB->get_record('cheve', array('id' => $id))) {
        return false;
    }

    // Delete any dependent records here.
    $DB->delete_records('cheve', array('id' => $cheve->id));

    return true;
}

/**
 * Returns a small object with summary information about what a
 * user has done with a given particular instance of this module
 * Used for user activity reports.
 *
 * $return->time = the time they did it
 * $return->info = a short text description
 *
 * @param stdClass $course The course record
 * @param stdClass $user The user record
 * @param cm_info|stdClass $mod The course module info object or record
 * @param stdClass $cheve The cheve instance record
 * @return stdClass|null
 */
function cheve_user_outline($course, $user, $mod, $cheve) {

    $return = new stdClass();
    $return->time = 0;
    $return->info = '';
    return $return;
}

/**
 * Prints a detailed representation of what a user has done with
 * a given particular instance of this module, for user activity reports.
 *
 * It is supposed to echo directly without returning a value.
 *
 * @param stdClass $course the current course record
 * @param stdClass $user the record of the user we are generating report for
 * @param cm_info $mod course module info
 * @param stdClass $cheve the module instance record
 */
function cheve_user_complete($course, $user, $mod, $cheve) {
}

/**
 * Given a course and a time, this module should find recent activity
 * that has occurred in cheve activities and print it out.
 *
 * @param stdClass $course The course record
 * @param bool $viewfullnames Should we display full names
 * @param int $timestart Print activity since this timestamp
 * @return boolean True if anything was printed, otherwise false
 */
function cheve_print_recent_activity($course, $viewfullnames, $timestart) {
    return false;
}

/**
 * Prepares the recent activity data
 *
 * This callback function is supposed to populate the passed array with
 * custom activity records. These records are then rendered into HTML via
 * {@link cheve_print_recent_mod_activity()}.
 *
 * Returns void, it adds items into $activities and increases $index.
 *
 * @param array $activities sequentially indexed array of objects with added 'cmid' property
 * @param int $index the index in the $activities to use for the next record
 * @param int $timestart append activity since this time
 * @param int $courseid the id of the course we produce the report for
 * @param int $cmid course module id
 * @param int $userid check for a particular user's activity only, defaults to 0 (all users)
 * @param int $groupid check for a particular group's activity only, defaults to 0 (all groups)
 */
function cheve_get_recent_mod_activity(&$activities, &$index, $timestart, $courseid, $cmid, $userid=0, $groupid=0) {
}

/**
 * Prints single activity item prepared by {@link cheve_get_recent_mod_activity()}
 *
 * @param stdClass $activity activity record with added 'cmid' property
 * @param int $courseid the id of the course we produce the report for
 * @param bool $detail print detailed report
 * @param array $modnames as returned by {@link get_module_types_names()}
 * @param bool $viewfullnames display users' full names
 */
function cheve_print_recent_mod_activity($activity, $courseid, $detail, $modnames, $viewfullnames) {
}

/**
 * Function to be run periodically according to the moodle cron
 *
 * This function searches for things that need to be done, such
 * as sending out mail, toggling flags etc ...
 *
 * Note that this has been deprecated in favour of scheduled task API.
 *
 * @return boolean
 */
function cheve_cron () {
    return true;
}

/**
 * Returns all other caps used in the module
 *
 * For example, this could be array('moodle/site:accessallgroups') if the
 * module uses that capability.
 *
 * @return array
 */
function cheve_get_extra_capabilities() {
    return array();
}

/* Gradebook API */
/**
 * Is a given scale used by the instance of cheve?
 *
 * This function returns if a scale is being used by one cheve
 * if it has support for grading and scales.
 *
 * @param int $cheveid ID of an instance of this module
 * @param int $scaleid ID of the scale
 * @return bool true if the scale is used by the given cheve instance
 */
function cheve_scale_used($cheveid, $scaleid) {
    global $DB;
    if ($scaleid and $DB->record_exists('cheve', array('id' => $cheveid, 'grade' => -$scaleid))) {
        return true;
    } else {
        return false;
    }
}
/**
 * Checks if scale is being used by any instance of cheve.
 *
 * This is used to find out if scale used anywhere.
 *
 * @param int $scaleid ID of the scale
 * @return boolean true if the scale is used by any cheve instance
 */
function cheve_scale_used_anywhere($scaleid) {
    global $DB;
    if ($scaleid and $DB->record_exists('cheve', array('grade' => -$scaleid))) {
        return true;
    } else {
        return false;
    }
}
/**
 * Creates or updates grade item for the given cheve instance
 *
 * Needed by {@link grade_update_mod_grades()}.
 *
 * @param stdClass $cheve instance object with extra cmidnumber and modname property
 * @param bool $reset reset grades in the gradebook
 * @return void
 */
function cheve_grade_item_update(stdClass $cheve, $reset=false) {
    global $CFG;
    require_once($CFG->libdir.'/gradelib.php');
    $item = array();
    $item['itemname'] = clean_param($cheve->name, PARAM_NOTAGS);
    $item['gradetype'] = GRADE_TYPE_VALUE;
    if ($cheve->grade > 0) {
        $item['gradetype'] = GRADE_TYPE_VALUE;
        $item['grademax']  = $cheve->grade;
        $item['grademin']  = 0;
    } else if ($cheve->grade < 0) {
        $item['gradetype'] = GRADE_TYPE_SCALE;
        $item['scaleid']   = -$cheve->grade;
    } else {
        $item['gradetype'] = GRADE_TYPE_NONE;
    }
    if ($reset) {
        $item['reset'] = true;
    }
    grade_update('mod/cheve', $cheve->course, 'mod', 'cheve',
            $cheve->id, 0, null, $item);
}
/**
 * Delete grade item for given cheve instance
 *
 * @param stdClass $cheve instance object
 * @return grade_item
 */
function cheve_grade_item_delete($cheve) {
    global $CFG;
    require_once($CFG->libdir.'/gradelib.php');
    return grade_update('mod/cheve', $cheve->course, 'mod', 'cheve',
            $cheve->id, 0, null, array('deleted' => 1));
}
/**
 * Update cheve grades in the gradebook
 *
 * Needed by {@link grade_update_mod_grades()}.
 *
 * @param stdClass $cheve instance object with extra cmidnumber and modname property
 * @param int $userid update grade of specific user only, 0 means all participants
 */
function cheve_update_grades(stdClass $cheve, $userid = 0) {
    global $CFG, $DB;
    require_once($CFG->libdir.'/gradelib.php');
    // Populate array of grade objects indexed by userid.
    $grades = array();
    grade_update('mod/cheve', $cheve->course, 'mod', 'cheve', $cheve->id, 0, $grades);
}

/* File API */

/**
 * Returns the lists of all browsable file areas within the given module context
 *
 * The file area 'intro' for the activity introduction field is added automatically
 * by {@link file_browser::get_file_info_context_module()}
 *
 * @param stdClass $course
 * @param stdClass $cm
 * @param stdClass $context
 * @return array of [(string)filearea] => (string)description
 */
function cheve_get_file_areas($course, $cm, $context) {
    return array();
}

/**
 * File browsing support for cheve file areas
 *
 * @package mod_cheve
 * @category files
 *
 * @param file_browser $browser
 * @param array $areas
 * @param stdClass $course
 * @param stdClass $cm
 * @param stdClass $context
 * @param string $filearea
 * @param int $itemid
 * @param string $filepath
 * @param string $filename
 * @return file_info instance or null if not found
 */
function cheve_get_file_info($browser, $areas, $course, $cm, $context, $filearea, $itemid, $filepath, $filename) {
    return null;
}

/**
 * Serves the files from the cheve file areas
 *
 * @package mod_cheve
 * @category files
 *
 * @param stdClass $course the course object
 * @param stdClass $cm the course module object
 * @param stdClass $context the cheve's context
 * @param string $filearea the name of the file area
 * @param array $args extra arguments (itemid, path)
 * @param bool $forcedownload whether or not force download
 * @param array $options additional options affecting the file serving
 */
function cheve_pluginfile($course, $cm, $context, $filearea, array $args, $forcedownload, array $options=array()) {
    global $DB, $CFG;
    if ($context->contextlevel != CONTEXT_MODULE) {
        send_file_not_found();
    }
    require_course_login($course, true, $cm);
    
    if ($filearea === 'lecturevid') {
        $revision = (int)array_shift($args); // Prevents caching problems - ignored here.
        $relativepath = implode('/', $args);
        $fullpath = "/$context->id/mod_cheve/lecturevid/0/$relativepath";
        $eve = array('revision' => $revision, 'relativepath' => $relativepath, 'fullpath' => $fullpath);
    }
    if (empty($fullpath)) {
        return false;
    }
    $fs = get_file_storage();
    $file = $fs->get_file_by_hash(sha1($fullpath));
    if (empty($file)) {
        return false;
    }
    
    send_stored_file($file, $lifetime, 0, false, $options);
    send_file_not_found();
}

/* Navigation API */

/**
 * Extends the global navigation tree by adding cheve nodes if there is a relevant content
 *
 * This can be called by an AJAX request so do not rely on $PAGE as it might not be set up properly.
 *
 * @param navigation_node $navref An object representing the navigation tree node of the cheve module instance
 * @param stdClass $course current course record
 * @param stdClass $module current cheve instance record
 * @param cm_info $cm course module information
 */
function cheve_extend_navigation(navigation_node $navref, stdClass $course, stdClass $module, cm_info $cm) {
    // TODO Delete this function and its docblock, or implement it.
}

/**
 * Extends the settings navigation with the cheve settings
 *
 * This function is called when the context for the page is a cheve module. This is not called by AJAX
 * so it is safe to rely on the $PAGE.
 *
 * @param settings_navigation $settingsnav complete settings navigation tree
 * @param navigation_node $chevenode cheve administration node
 */
function cheve_extend_settings_navigation(settings_navigation $settingsnav, navigation_node $chevenode=null) {
    // TODO Delete this function and its docblock, or implement it.
}
