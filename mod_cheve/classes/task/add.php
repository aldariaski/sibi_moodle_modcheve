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

    $payload = json_encode($data_arr);
    $file = $this->getFileByHash($data->filehash);

    $content = $mform->get_file_content('userfile');
    $newfilename = $mform->get_new_filename('userfile');
    $fromform->filename = $newfilename;

    $shortpath = 'lecturevid/0/'. $newfilename;
    $longpath = $CFG->wwwroot . '/local/sign/uploads/' . rawurlencode($newfilename);
    $success = $mform->save_file('userfile', $shortpath, $override);
    $fromform->url = $longpath;

    $endpointUrl = 'http://127.0.0.1:8000/add';

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, array(
        'video_file' => $file,
        'data' => $payload
    ));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    
    // Check for any cURL errors
    if (curl_errno($ch)) {
        // Handle the error
        $errorMessage = curl_error($ch);
        redirect($CFG->wwwroot . '/local/sign/manage.php', $errorMessage, null, \core\notification::ERROR);
    } else if ($message != "Request successful") {
        redirect($CFG->wwwroot . '/local/sign/manage.php', $message, null, \core\notification::ERROR);
    }

    // Process the response as needed
}

private function getFileByHash($hash) {
    // Implement the logic to retrieve the file by hash
    // You can use the get_file_by_hash() function from Moodle's file storage API
    // or any other method that suits your needs
}
