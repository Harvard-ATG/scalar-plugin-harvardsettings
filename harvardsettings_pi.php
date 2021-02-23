<?php

class Harvardsettings {
    public $name = 'Harvard Settings'; // display name for plugin
    public $plugin_name = "harvardsettings"; // internal name for plugin
    public $plugin_dir = null; // filesystem path to plugin dir
    public $plugin_path = null; // url path to plugin dir
    public $plugin_exists = false; // true if plugin_dir exists
    public $book = null; // holds book object
    public $CI = null; // holds codeigniter controller
    public $CSV_ROWS = array('fullname','email','huid','in_index','relationship');
    public $RELATIONSHIPS = array('author','editor','commentator','reviewer','reader');

    public function __construct($data=array()) {
        $this->plugin_dir = dirname(__FILE__).'/'.strtolower(get_class($this)).'/';
        if (file_exists($this->plugin_dir)) $this->plugin_exists = true;
        if (!file_exists($this->plugin_dir.'index.php')) $this->plugin_exists = false;
        $this->plugin_path = $this->get_base_url().'system/application/plugins/'.strtolower(get_class($this)).'/';

        $this->book = isset($data['book']) ? $data['book'] : null;
        $this->CI = $this->get_codeigniter_instance();
    }

    public function check_plugin() {
        if(empty($this->book)) {
            return array(false ,"Please select a book to manage using the pulldown menu above");
        }
        if(!property_exists($this->book, 'subdomain_is_on')) {
            return array(false, "Plugin {$this->plugin_name} has not bee installed yet");
        }
        return array(true, "Plugin is ready");
    }

    public function get() {
        list($ok, $msg) = $this->check_plugin();
        if(!$ok) {
            echo $msg;
            return;
        }
        $this->display_form();
         
        if ($this->CI->data['login_is_super']) {
            $this->display_user_upload_form();
        }    
    }

    public function handle_action($action) {
        list($ok, $msg) = $this->check_plugin();
        if(!$ok) {
            return;
        }
        switch($action) {
            case "plugin_{$this->plugin_name}_form":
                $subdomain_is_on = isset($_POST['subdomain_is_on']) ? $_POST['subdomain_is_on'] : '0';
                $this->save_book_properties(array('subdomain_is_on' => (int) $subdomain_is_on));
                $this->redirect_and_exit(array('saved' => 1));
                break;
            case "plugin_{$this->plugin_name}_upload_user_form":
                $file_is_submitted = isset($_FILES['upload_users']['tmp_name']) ? true : false;
                if ($file_is_submitted){
                    $processed = $this->process_file();
                    if ($processed['message'] !== "Book Users imported") {
                        $redirect_params = array(
                          'imported' => 1,
                          'error' => true,
                          'message' => $processed['message']
                        );
                        $this->redirect_and_exit($redirect_params);
                    }
                    else {
                        $redirect_params = array(
                            'imported' => 1,
                            'message' => $processed['message'],
                            'users_added' => $processed['users_added'],
                            'new_accounts_created' => $processed['new_accounts_created'],
                            'unsuccessful' => $processed['unsuccessful']
                        );
                        $this->redirect_and_exit($redirect_params);
                    }
                }
                break;
            case "plugin_{$this->plugin_name}_download_template":
                $this->download_template();
                break;
            default:
                break;
        }
    }

    public function save_book_properties($book_data) {
        $book_data['book_id'] = (int) $this->book->book_id;
        return $this->get_codeigniter_instance()->books->save($book_data);
    }

    public function redirect_and_exit($params) {
        $tab_url = $this->get_tab_url($params);
        header("Location: $tab_url");
        exit(0);
    }

    public function get_subdomain_is_on() {
        return (bool) isset($this->book->subdomain_is_on) ? $this->book->subdomain_is_on : 0;
    }

    public function get_subdomain_url() {
        $book_subdomain_url = false;
        if($this->get_subdomain_is_on()) {
            $scheme = ($this->CI->config->item('is_https') ? 'https' : 'http');
            $domain = getenv('SCALAR_DOMAIN') ? getenv('SCALAR_DOMAIN') : $_SERVER['SERVER_NAME'];
            $book_subdomain_url = $scheme . '://' . $this->book->slug . '.' . $domain . '/';
        }
        return $book_subdomain_url;
    }

    public function display_form() {
        $subdomain_is_on = $this->get_subdomain_is_on();
        $book_subdomain_url = $this->get_subdomain_url();
        $base_url = $this->get_base_url();
        $tab_url = $this->get_tab_url();
        $action = "plugin_{$this->plugin_name}_form";
        $saved = isset($_GET['saved']) ? $_GET['saved'] == '1' : false;
        ?>
        <?php if($saved): ?>
            <div class="saved">
                <?= $this->name; ?> saved
                <div style="float:right;">
                    <a href="<?= $tab_url ?>">clear</a>
                </div>
            </div>
        <?php endif; ?>
        <form id="style_form" action="<?= $base_url ?>system/dashboard" method="post" enctype="multipart/form-data">
            <input type="hidden" name="action" value="<?= $action ?>" />
            <? if (!empty($this->book)): ?>
                <input type="hidden" name="book_id" value="<?= $this->book->book_id?>" />
            <? endif ?>
            <fieldset style="border:0;">
                <legend style="font-weight: bold; font-size: 125%; padding: 5px 0; margin:0;">URL Settings</legend>
                <div>
                    <label for="subdomain_is_on">Enable subdomain?</label> &nbsp;
                    <select id="subdomain_is_on" name="subdomain_is_on">
                        <option value="0" <?= ($subdomain_is_on?'':'selected'); ?>>No</option>
                        <option value="1" <?= ($subdomain_is_on?'selected':''); ?>>Yes</option>
                    </select>
                    <?php if($book_subdomain_url !== false): ?>
                        <a target="_blank" href="<?= htmlspecialchars($book_subdomain_url); ?>"><?= htmlspecialchars($book_subdomain_url); ?></a>
                    <?php endif; ?>
                </div>
                <div style="margin-top: 10px;">
                    <input type="submit" class="generic_button large default" value="Save"/>
                </div>
            </fieldset>
        </form>
        <?php
    }

    public function display_user_upload_form() {

        $base_url = $this->get_base_url();
        $action = "plugin_{$this->plugin_name}_upload_user_form";
        $download_template_action = "plugin_{$this->plugin_name}_download_template";
        $tab_url = $this->get_tab_url();
        $imported = isset($_GET['imported']) ? $_GET['imported'] == '1' : false;
        $color = isset($_GET['error']) ? "error" : "saved";
        $message = isset($_GET['message']) ? $_GET['message'] : NULL;
        ?>
        <?php if($imported): ?>
            <div class="<? echo $color; ?>">
                <? echo $message ?>
                <div style="float:right;">
                    <a href="<?= $tab_url ?>">clear</a>
                </div>
            </div>
        <?php endif; ?>
        <form id="style_form" action="<?= $base_url ?>system/dashboard" method="post" enctype="multipart/form-data">
            <input type="hidden" name="action" value="<?= $action ?>" />
            <? if (!empty($this->book)): ?>
                <input type="hidden" name="book_id" value="<?= $this->book->book_id?>" />
            <? endif ?>
            <fieldset style="border:0;">
                <legend style="font-weight: bold; font-size: 125%; padding: 5px 0; margin:0;">Bulk Associate Users</legend>
                <div>
                    <label for="upload_users">Import CSV</label> &nbsp;
                    <input type="file" name="upload_users" id="upload_users">
                </div>
                <div style="margin-top: 10px;">
                    <input type="submit" class="generic_button large default" value="Upload"/>
                </div>
            </fieldset>  
        </form>
        <div style="padding-left:1em;">
            <form id="download_template" action="<?= $base_url ?>system/dashboard" method="post" enctype="multipart/form-data">
                <input type="hidden" name="action" value="<?= $download_template_action ?>" />
                <? if (!empty($this->book)): ?>
                    <input type="hidden" name="book_id" value="<?= $this->book->book_id?>" />
                <? endif ?>
                <a href="javascript:{}" onclick="document.getElementById('download_template').submit();">Download Bulk Associate Users Template</a>    
            </form>
        </div>
        
        <?php if($imported && $color === "saved"): ?>
          <div style="padding:1em;">
            <p><strong>Users Added: </strong>
                <?
                    $users_added = isset($_GET['users_added']) ? htmlspecialchars($_GET['users_added']) : 0;
                    echo '<span>'.$users_added.'</span>';
                ?>
            </p>
            <p><strong>New Accounts Created: </strong>
                <?
                    $new_accounts = isset($_GET['new_accounts_created']) ? htmlspecialchars($_GET['new_accounts_created']) : 0;
                    echo '<span>'.$new_accounts.'</span>';
                ?>
            <p>
            <p><strong>Failed to Add: </strong>
                <?
                    $unsuccessful = isset($_GET['unsuccessful']) ? htmlspecialchars($_GET['unsuccessful']) : 0;
                    echo '<span>'.$unsuccessful.'</span>';
                ?>
            <p>
          </div>
        <?php endif; ?>
        <?php
    }
    
    public function get_tab_url($query_params=array()){
        $query_params['book_id'] = $this->book->book_id;
        $query_params['zone'] = $this->plugin_name;
        $querystring = '';
        foreach($query_params as $k => $v) {
            $querystring .= $k.'='.rawurlencode($v).'&';
        }
        $querystring = substr($querystring, 0, -1);
        $base_url = $this->get_base_url();
        return $base_url."system/dashboard?$querystring#tabs-{$this->plugin_name}";
    }

    public function get_base_url() {
        return confirm_slash(base_url());
    }

    public function get_codeigniter_instance() {
        return get_instance();
    }

    public function error($msg='') {
        echo '<div class="error text-center">'.$msg.'</div>'."\n";
        return false;
    }

    public function process_file() {
        $filename = $_FILES['upload_users']['name'];
        $tmpName = $_FILES['upload_users']['tmp_name'];
        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        if($extension !== 'csv') {
            return array('message' => "File must be in CSV format.");
        }
        $csvAsArray = array_map('str_getcsv', file($tmpName));
        $keys = array_shift($csvAsArray);
        foreach($this->CSV_ROWS as $column) { //check that the file has the required columns
            if (!in_array($column, $keys, TRUE)){
                return array('message' => "The CSV must contain a '{$column}' column.");
            }
        }
        foreach ($csvAsArray as $i=>$row) {
            $csvAsArray[$i] = array_combine($keys, $row);
        }
        return $this->import_users($csvAsArray);
    }

    public function import_users($arr) {
        
        $book_id = array($this->book->book_id);
        $message = "Book Users imported";
        $users_added = 0;
        $new_accounts_created = 0;
        $unsuccessful = 0;
        
        // hit the PDS endpoint and get user info only if necessary
        // check if any data is missing, but huid is available
        $body_condition_univid = $this->build_body_conditions($arr, 'univid');
        
        if (!empty($body_condition_univid)) {
            // curl for data if necessary
            $conditions = array('univid' => $body_condition_univid);
            $people_by_pds_univid = $this->get_pds_emails_names($conditions, 'univid');
        }
        
        // if there is some value set by looking folks up, update the original array of data
        if(isset($people_by_pds_univid)){ // update the $arr
            foreach($arr as $key => $field) {
                $email = trim($field['email']);
                $huid = trim($field['huid']);
                $name = trim($field['fullname']);
                if (isset($people_by_pds_univid->$huid)) {
                    $arr[$key]['email'] = $people_by_pds_univid->$huid->email;
                    $arr[$key]['fullname'] = $name === '' ? $people_by_pds_univid->$huid->name : $name;
                }
            }
        }
        

        $body_condition_loginName = $this->build_body_conditions($arr, 'loginName');
        if (!empty($body_condition_loginName)) {
            // curl for data if necessary
            $conditions = array('loginName' => $body_condition_loginName);
            $people_by_pds_loginName = $this->get_pds_emails_names($conditions, 'loginName');
        }
        
        if(isset($people_by_pds_loginName)){ // update the $arr
            foreach($arr as $key => $field) {
                $email = trim($field['email']);
                if (isset($people_by_pds_loginName->$email)) {
                    $arr[$key]['fullname'] = $people_by_pds_loginName->$email->name;
                }
            }
        }
        
        
        foreach($arr as $row){
            $email_row = trim($row['email']);
            $huid_row = trim($row['huid']);
            $name = trim($row['fullname']);
            $user = $this->get_scalar_user($email_row, $huid_row, $name);
           
            if ($user === "no identifiers") {
                $unsuccessful++;
                continue;
            }
            if (!$user) {
                $user = $this->new_scalar_user($email_row, $name, $new_accounts_created);
            }
            if (!$user) {
                $unsuccessful++;
                continue;
            }
            
            $user_id = $user->user_id;
            $row_role = trim(strtolower($row['relationship']));
            $role = in_array($row_role, $this->RELATIONSHIPS) ? $row_role : 'reader';
            $row_in_index = trim(strtolower($row['in_index']));
            if ($row_in_index === 'true' || $row_in_index === '1'){
                $in_index = 1;
            }
            else {
                $in_index = 0;
            }
            $this->CI->users->save_books($user_id, $book_id, $role, $in_index);
            $users_added++;
        }
        return array(
            'message' => $message,
            'users_added' => $users_added,
            'new_accounts_created' => $new_accounts_created,
            'unsuccessful' => $unsuccessful
        );
    }
    
    public function get_scalar_user($email) {
        if ($email === '') {
            return "no identifiers";
        }
        return $this->CI->users->get_by_email($email);
    }
    
    public function new_scalar_user($email_row, $name, &$new_accounts_created) {
        $new_user = array(
            'email' => $email_row,
            'fullname' => $name === '' ? 'placeholder' : $name,
            'password' => 'DISABLEDPASSWORD',
        );
        if (!$this->CI->users->db->insert($this->CI->users->users_table, $new_user)) {
            return false;
        }
        $user = $this->CI->users->get_by_email($email_row);
        if ($user) {$new_accounts_created++;}
        return $user;
    }
    
    public function get_pds_emails_names($conditions, $field) {
        $return_obj = (object) array();
        $person_json = $this->curl_pds($conditions);

        if ($person_json === NULL) {
            log_message(
                'info',
                'Request to PDS failed. Check that the endpoint and credentials are valid.'
            );
            return $return_obj;
        }

        if (isset($person_json->fault)) {
            log_message(
                'info',
                'PDS failure: ' . $person_json->fault->faultstring
            );
            return $return_obj;
        }

        foreach($person_json->results as $person) {
            $id = $person->univid;
            if ($person->privacyFerpaStatus == true) {
                $name = 'placeholder';
            }
            else {
                $name = $person->names[0]->firstName . " " . $person->names[0]->lastName;
            }

            // determine the user's valid email
            if (!$person->loginName) { // if the user doesnt have a loginName set
                $email = '';
                foreach ($person->emails as $potentialEmail) {
                    if ($potentialEmail->officialEmailIndicator === true){
                        $email = $potentialEmail->email;
                        break;
                    }
                }
                if ($email === ''){ // if email is not set, then set it by the first email in the array
                    $email = $person->emails[0]->email;
                }
            } else {
                $email = $person->loginName;
            }

            if($field === 'univid') {
                $return_obj->$id = (object) array(
                    'name' => $name,
                    'email' => $email
                );
                
            } else { // otherwise the lookup field should be the loginName
                $return_obj->$email = (object) array(
                    'name' => $name
                );
            }    
        }
        $accessing_user = $this->CI->data['login']->email." (".$this->CI->data['login']->user_id.")";
        log_message(
            'info',
            $accessing_user." accessed the PDS for info on the following: " . print_r($conditions)
        );
        
        return $return_obj;    
    }

    public function build_body_conditions($arr, $field) {
        $conditions = array();
        foreach($arr as $row) {
            $email = trim($row['email']);
            $huid = trim($row['huid']);
            $name = trim($row['fullname']);
            if ($field === 'univid') {
                if (($email === '' || $name === '') && $huid !== '') {
                    array_push($conditions, $huid);
                }
            } else { // otherwise the condition field is loginName
                if (($name === '' && $huid === '') && $email !== '') {
                    array_push($conditions, $email);
                }
            }
        }
        return $conditions;
    }
    
    public function download_template() {
        $template_data = array($this->CSV_ROWS);
        $f = fopen('php://output', 'w');
        foreach ($template_data as $line) {
            fputcsv($f, $line, ',');
        }
        fclose($f);
        header('Content-Type: application/csv');
        header('Content-Disposition: attachment; filename="user_upload_template.csv";');
        exit();
    }

    private function curl_pds($conditions) {
        // conditions should be an object like so:
        // conditions : { 'univid': [1,2,3] }
        // or conditions : { 'loginName':['tylor_dodge@harvard.edu, ...] }
        $c = curl_init();
        curl_setopt($c, CURLOPT_URL, $this->CI->config->item('pds_apigee_base_url'));
        curl_setopt($c, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($c, CURLOPT_HTTPHEADER, array(
            "X-Api-Key: " . $this->CI->config->item('pds_apigee_client_key'),
            "Accept: application/json",
            "Content-Type: application/json",
        ));
        $body_array = array(
            'fields' => array(
                'names.firstName',
                'names.lastName',
                'loginName',
                'univid',
                'privacyFerpaStatus',
                'emails.email',
                'emails.officialEmailIndicator',
              ),
            'conditions' => $conditions,
        );
        $body = json_encode($body_array);
        curl_setopt($c, CURLOPT_POSTFIELDS, $body);
        $person_json = json_decode(curl_exec($c));
        curl_close($c);
        return $person_json;
    }

}
