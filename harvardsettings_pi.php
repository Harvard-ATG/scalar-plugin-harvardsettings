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
                            'new_accounts_created' => $processed['new_accounts_created']
                        );
                        $this->redirect_and_exit($redirect_params);
                    }
                }
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
                <legend style="font-weight: bold; font-size: 125%; padding: 5px 0; margin:0;">Bulk Associate Users:</legend>
                <div>
                    <label for="upload_users">Import CSV</label> &nbsp;
                    <input type="file" name="upload_users" id="upload_users">
                </div>
                <div style="margin-top: 10px;">
                    <input type="submit" class="generic_button large default" value="Upload"/>
                </div>
            </fieldset>
        </form>
        <?php if($imported && $color === "saved"): ?>
          <div style="padding:1em;">
            <p><strong>Users added: </strong>
                <?
                    $users_added = isset($_GET['users_added']) ? $_GET['users_added'] : 0;
                    echo '<span>'.$users_added.'</span>';
                ?>
            </p>
            <p><strong>New Accounts Created: </strong>
                <?
                    $new_accounts = isset($_GET['new_accounts_created']) ? $_GET['new_accounts_created'] : 0;
                    echo '<span>'.$new_accounts.'</span>';
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
        // go ahead and move forward assuming that I have a sheet of names and emails
        $book_id = array($this->book->book_id);
        $message = "Book Users imported";
        $users_added = 0;
        $new_accounts_created = 0;

        foreach($arr as $row){
            $user = $this->CI->users->get_by_email($row['email']);
            if ($user === false) {
                $new_user = array(
                    'email' => $row['email'],
                    'fullname' => empty($row['fullname']) ? 'placeholder' : $row['fullname'],
                    'password' => 'DISABLEDPASSWORD',
                );
                $this->CI->users->db->insert($this->CI->users->users_table, $new_user);
                $user = $this->CI->users->get_by_email($row['email']);
                $new_accounts_created++;
            }
            $user_id = $user->user_id;
            if (!empty($row['relationship']) && in_array(strtolower($row['relationship']), $this->RELATIONSHIPS)) {
                $role = strtolower($row['relationship']);
            }
            else {
                $role = 'reader';
            }
            $in_index = !empty($row['in_index']) ? $row['in_index'] : 1;
            $this->CI->users->save_books($user_id, $book_id, $role, $in_index);
            $users_added++;
        }
        return array(
            'message' => $message,
            'users_added' => $users_added,
            'new_accounts_created' => $new_accounts_created
        );
    }
}
