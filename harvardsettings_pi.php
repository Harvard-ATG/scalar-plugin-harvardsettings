<?php

class Harvardsettings {
    public $name = 'Harvard Settings'; // display name for plugin
    public $plugin_name = "harvardsettings"; // internal name for plugin
    public $plugin_dir = null; // filesystem path to plugin dir
    public $plugin_path = null; // url path to plugin dir
    public $plugin_exists = false; // true if plugin_dir exists
    public $book = null; // holds book object

    public function __construct($data=array()) {
        $this->plugin_dir = dirname(__FILE__).'/'.strtolower(get_class($this)).'/';
        if (file_exists($this->plugin_dir)) $this->plugin_exists = true;
        if (!file_exists($this->plugin_dir.'index.php')) $this->plugin_exists = false;
        $this->plugin_path = confirm_slash(base_url()).'system/application/plugins/'.strtolower(get_class($this)).'/';

        $this->book = isset($data['book']) ? $data['book'] : null;
        $this->CI =& get_instance();
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
                $this->redirect_and_exit();
                break;
            default:
                break;
        }
    }

    public function save_book_properties($book_data) {
        $book_data['book_id'] = (int) $this->book->book_id;
        return $this->CI->books->save($book_data);
    }

    public function redirect_and_exit() {
        $params = array('saved' => 1);
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
        <form id="style_form" action="<?=confirm_slash(base_url())?>system/dashboard" method="post" enctype="multipart/form-data">
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

    public function get_tab_url($query_params=array()){
        $query_params['book_id'] = $this->book->book_id;
        $query_params['zone'] = $this->plugin_name;
        $querystring = '';
        foreach($query_params as $k => $v) {
            $querystring .= $k.'='.rawurlencode($v).'&';
        }
        $querystring = substr($querystring, 0, -1);
        $base_url = confirm_slash(base_url());
        return "$base_url/system/dashboard?$querystring#tabs-{$this->plugin_name}";
    }

    public function error($msg='') {
        echo '<div class="error text-center">'.$msg.'</div>'."\n";
        return false;
    }
}
