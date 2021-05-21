<?php

defined('ABSPATH') || exit;

class NewsletterEmails extends NewsletterModule
{

    static $instance;

    const EDITOR_COMPOSER = 2;
    const EDITOR_HTML = 1;
    const EDITOR_TINYMCE = 0;

    static $PRESETS_LIST;
    const PRESET_EMAIL_TYPE = 'composer_template';

    // Cache
    var $blocks = null;

    /**
     * @return NewsletterEmails
     */
    static function instance()
    {
        if (self::$instance == null) {
            self::$instance = new NewsletterEmails();
        }
        return self::$instance;
    }

    function __construct()
    {
        self::$PRESETS_LIST = array("cta", "invite", "announcement", "posts", "sales", "product", "tour", "simple");
        $this->themes = new NewsletterThemes('emails');
        parent::__construct('emails', '1.1.5');
        add_action('newsletter_action', array($this, 'hook_newsletter_action'), 13, 3);

        if (is_admin()) {
            add_action('wp_ajax_tnpc_render', array($this, 'tnpc_render_callback'));
            add_action('wp_ajax_tnpc_preview', array($this, 'tnpc_preview_callback'));
            add_action('wp_ajax_tnpc_css', array($this, 'tnpc_css_callback'));
            add_action('wp_ajax_tnpc_options', array($this, 'hook_wp_ajax_tnpc_options'));
            add_action('wp_ajax_tnpc_presets', array($this, 'hook_wp_ajax_tnpc_presets'));
            add_action('wp_ajax_tnpc_delete_preset', array($this, 'hook_wp_ajax_tnpc_delete_preset'));
            add_action('wp_ajax_tnpc_regenerate_email', array($this, 'hook_wp_ajax_tnpc_regenerate_email'));

            // Thank you to plugins which add the WP editor on other admin plugin pages...
            if (isset($_GET['page']) && $_GET['page'] == 'newsletter_emails_edit') {
                global $wp_actions;
                $wp_actions['wp_enqueue_editor'] = 1;
            }
        }
    }

    function options_decode($options)
    {

        // Start compatibility
        if (is_string($options) && strpos($options, 'options[') !== false) {
            $opts = array();
            parse_str($options, $opts);
            $options = $opts['options'];
        }
        // End compatibility

        if (is_array($options)) {
            return $options;
        }

        $tmp = json_decode($options, true);
        if (is_null($tmp)) {
            return json_decode(base64_decode($options), true);
        } else {
            return $tmp;
        }
    }

    /**
     *
     * @param array $options Options array
     */
    function options_encode($options)
    {
        return base64_encode(json_encode($options, JSON_HEX_TAG | JSON_HEX_AMP));
    }

    function hook_wp_ajax_tnpc_options()
    {
        global $wpdb;

        // TODO: Uniform to use id everywhere
        if (!isset($_REQUEST['id']))
            $_REQUEST['id'] = $_REQUEST['b'];

        $block = $this->get_block($_REQUEST['id']);
        if (!$block) {
            die('Block not found with id ' . esc_html($_REQUEST['id']));
        }

        if (!class_exists('NewsletterControls')) {
            include NEWSLETTER_INCLUDES_DIR . '/controls.php';
        }

        $options = $this->options_decode(stripslashes_deep($_REQUEST['options']));
        $composer = isset($_POST['composer']) ? $_POST['composer'] : [];

        $context = array('type' => '');
        if (isset($_REQUEST['context_type'])) {
            $context['type'] = $_REQUEST['context_type'];
        }

        $controls = new NewsletterControls($options);
        $fields = new NewsletterFields($controls);

        $controls->init();
        echo '<input type="hidden" name="action" value="tnpc_render">';
        echo '<input type="hidden" name="b" value="' . esc_attr($_REQUEST['id']) . '">';
        echo '<input type="hidden" name="context_type" value="' . esc_attr($context['type']) . '">';
        $inline_edits = '';
        if (isset($controls->data['inline_edits'])) {
            $inline_edits = $controls->data['inline_edits'];
        }
        echo '<input type="hidden" name="options[inline_edits]" value="' . $this->options_encode($inline_edits) . '">';

        ob_start();
        include $block['dir'] . '/options.php';
        $content = ob_get_clean();
        echo "<h2>", esc_html($block["name"]), "</h2>";
        echo $content;
        wp_die();
    }

    /**
     * Retrieves the presets list (no id in GET) or a specific preset id in GET)
     *
     * @return string
     */
    public function hook_wp_ajax_tnpc_presets()
    {

        if ($this->is_loading_preset_content_request()) {

            $preset_id = $_REQUEST['id'];
            $response = $this->get_preset_content($preset_id);

        } else {

            $response = $this->get_all_preset();

        }

        echo $response;
        die();
    }

    private function is_loading_preset_content_request()
    {
        return !empty($_REQUEST['id']);
    }

    private function get_preset_content($preset_id)
    {

        $content = '';

        if ($this->is_a_tnp_default_preset($preset_id)) {

            // Get preset from file
            $preset = $this->get_preset_from_file($preset_id);

            foreach ($preset->blocks as $item) {
                ob_start();
                $this->render_block($item->block, true, (array)$item->options);
                $content .= trim(ob_get_clean());
            }

        } else {

            // Get preset from db
            $preset_email = $this->get_email(intval($_REQUEST['id']));
            $content = $preset_email->message;

        }

        return $content;

    }

    private function is_a_tnp_default_preset($preset_id)
    {
        return in_array($preset_id, self::$PRESETS_LIST);
    }

    private function get_all_preset()
    {

        $content = "<div class='tnpc-preset-container'>";

	    if ( $this->is_normal_context_request() ) {
		    $content .= "<div class='tnpc-preset-legacy-themes'><a href='" . $this->get_admin_page_url( 'theme' ) . "'>" . __( 'Looking for legacy themes?', 'newsletter' ) . "</a></div>";
	    }

        // LOAD USER PRESETS
        $user_preset_list = $this->get_emails(self::PRESET_EMAIL_TYPE);

        foreach ($user_preset_list as $user_preset) {

            $default_icon_url = NEWSLETTER_URL . "/emails/presets/default-icon.png?ver=2";
            $preset_name = $user_preset->subject;
            $delete_preset_text = __('Delete', 'newsletter');
            $edit_preset_text = __('Edit', 'newsletter');

            // esc_js() assumes the string will be in single quote (arghhh!!!)
            $onclick_edit = 'tnpc_edit_preset(' . ((int)$user_preset->id) . ', \'' . esc_js($preset_name) . '\', event)';
            $onclick_delete = 'tnpc_delete_preset(' . ((int)$user_preset->id) . ', \'' . esc_js($preset_name) . '\', event)';
            $onclick_load = 'tnpc_load_preset(' . ((int)$user_preset->id) . ', \'' . esc_js($preset_name) . '\', event)';

            $content .= "<div class='tnpc-preset' onclick='" . esc_attr($onclick_load) . "'>\n";
            $content .= "<img src='$default_icon_url' title='" . esc_attr($preset_name) . "' alt='" . esc_attr($preset_name) . "'>\n";
            $content .= "<span class='tnpc-preset-label'>" . esc_html($user_preset->subject) . "</span>\n";
            $content .= "<span class='tnpc-delete-preset' onclick='" . esc_attr($onclick_delete) . "' title='" . esc_attr($delete_preset_text) . "'><i class='fas fa-times'></i></span>\n";
            $content .= "<span class='tnpc-edit-preset' onclick='" . esc_attr($onclick_edit) . "' title='" . esc_attr($edit_preset_text) . "'><i class='fas fa-pencil-alt'></i></span>\n";
            $content .= "</div>";

        }

        // LOAD TNP PRESETS
        foreach (self::$PRESETS_LIST as $id) {
            $preset = $this->get_preset_from_file($id);
            $preset_name = esc_html($preset->name);
            $content .= "<div class='tnpc-preset' onclick='tnpc_load_preset(\"$id\")'>";
            $content .= "<img src='$preset->icon' title='$preset_name' alt='$preset_name'/>";
            $content .= "<span class='tnpc-preset-label'>$preset_name</span>";
            $content .= "</div>";
        }

	    if ( $this->is_normal_context_request() ) {
		    $content .= $this->get_automated_spot_element();
		    $content .= $this->get_autoresponder_spot_element();
		    $content .= $this->get_raw_html_preset_element();
	    }

        return $content;

    }

	private function is_normal_context_request() {
		return empty( $_REQUEST['context_type'] );
	}

	private function is_automated_context_request() {
		return isset( $_REQUEST['context_type'] ) && $_REQUEST['context_type'] === 'automated';
	}

	private function is_autoresponder_context_request() {
		return isset( $_REQUEST['context_type'] ) && $_REQUEST['context_type'] === 'autoresponder';
	}

	private function get_automated_spot_element() {
		$result = "<div class='tnpc-preset'>";
		if ( class_exists( 'NewsletterAutomated' ) ) {
			$result .= "<a href='?page=newsletter_automated_index'>";
		} else {
			$result .= "<a href='https://www.thenewsletterplugin.com/automated?utm_source=plugin&utm_campaign=automated&utm_medium=composer'>";
		}
		$result .= "<img src='" . plugins_url( 'newsletter' ) . "/emails/images/automated.png' title='Automated addon' alt='Automated'/>";
		$result .= "<span class='tnpc-preset-label'>Daily, weekly and monthly newsletters</span></a>";
		$result .= "</div>";

		return $result;
	}

	private function get_autoresponder_spot_element() {
		$result = "<div class='tnpc-preset'>";
		if ( class_exists( 'NewsletterAutoresponder' ) ) {
			$result .= "<a href='?page=newsletter_autoresponder_index'>";
		} else {
			$result .= "<a href='https://www.thenewsletterplugin.com/autoresponder?utm_source=plugin&utm_campaign=autoresponder&utm_medium=composer' target='_blank'>";
		}
		$result .= "<img src='" . plugins_url( 'newsletter' ) . "/emails/images/autoresponder.png' title='Autoresponder addon' alt='Autoresponder'/>";
		$result .= "<span class='tnpc-preset-label'>Autoresponders</span></a>";
		$result .= "</div>";

		return $result;
	}

	private function get_raw_html_preset_element() {

		$result = "<div class='tnpc-preset tnpc-preset-html' onclick='location.href=\"" . wp_nonce_url( 'admin.php?page=newsletter_emails_new&id=rawhtml', 'newsletter-new' ) . "\"'>";
		$result .= "<img src='" . plugins_url( 'newsletter' ) . "/emails/images/rawhtml.png' title='RAW HTML' alt='RAW'/>";
		$result .= "<span class='tnpc-preset-label'>Raw HTML</span>";
		$result .= "</div>";

		$result .= "<div class='clear'></div>";
		$result .= "</div>";

		return $result;
	}

    /**
     * Check if the preset name exists and adds an incremental suffix if the name exists.
     *
     * @param string $name
     *
     * @return string
     */
    public function sanitize_preset_name($name)
    {
        global $wpdb;

        $name = empty($name) ? __('Empty name preset', 'newsletter') : $name;
        $name = sanitize_text_field($name);
        $type = self::PRESET_EMAIL_TYPE;
        $count = (int)$wpdb->get_var("SELECT COUNT(*) FROM " . NEWSLETTER_EMAILS_TABLE . " WHERE type='$type' and subject='$name'");

        $name = $count > 0 ? $name . " - " . ($count + 1) : $name;

        return $name;
    }

    function has_dynamic_blocks($theme)
    {
        preg_match_all('/data-json="(.*?)"/m', $theme, $matches, PREG_PATTERN_ORDER);
        foreach ($matches[1] as $match) {
            $a = html_entity_decode($match, ENT_QUOTES, 'UTF-8');
            $options = $this->options_decode($a);

            $block = $this->get_block($options['block_id']);
            if (!$block) {
                continue;
            }
            if ($block['type'] == 'dynamic') {
                return true;
            }
        }
        return false;
    }

    /**
     * Regenerates a saved composed email rendering each block. Regeneration is
     * conditioned (possibly) by the context. The context is usually passed to blocks
     * so they can act in the right manner.
     *
     * $context contains a type and, for automated, the last_run.
     *
     * $email can actually be even a string containing the full newsletter HTML code.
     *
     * @param TNP_Email $email (Rinominare)
     * @return string
     */
    function regenerate($email, $context = array())
    {

        // Cannot be removed due to compatibility issues with old Automated versions
        if (is_object($email)) {
            $theme = $email->message;
        } else {
            $theme = $email;
        }

        //$this->logger->debug('Starting email regeneration');
        //$this->logger->debug($context);

        if (empty($theme)) {
            $this->logger->debug('The email was empty');
            return array('body' => '', 'subject' => '');
        }

        $context = array_merge(array('last_run' => 0, 'type' => ''), $context);

        preg_match_all('/data-json="(.*?)"/m', $theme, $matches, PREG_PATTERN_ORDER);

        $result = '';
        $subject = '';

        foreach ($matches[1] as $match) {
            $a = html_entity_decode($match, ENT_QUOTES, 'UTF-8');
            $options = $this->options_decode($a);

            $block = $this->get_block($options['block_id']);
            if (!$block) {
                $this->logger->debug('Unable to load the block ' . $options['block_id']);
                //continue;
            }

            ob_start();
            $out = $this->render_block($options['block_id'], true, $options, $context);
            if (is_array($out)) {
                if ($out['return_empty_message'] || $out['stop']) {
                    if (is_object($email)) {
                        return false;
                    }
                    return array();
                }
                if ($out['skip']) {
                    if (NEWSLETTER_DEBUG) {
                        $result .= 'Block removed by request';
                    }
                    continue;
                }
                if (empty($subject) && !empty($out['subject'])) {
                    $subject = $out['subject'];
                }
            }
            $block_html = ob_get_clean();
            $result .= $block_html;
        }

        // We need to keep the CSS/HEAD part, the regenearion is only about blocks

        if (is_object($email)) {
            $result = TNP_Composer::get_main_wrapper_open($email) . $result . TNP_Composer::get_main_wrapper_close($email);
        }

        $x = strpos($theme, '<body');
        if ($x !== false) {
            $x = strpos($theme, '>', $x);
            $result = substr($theme, 0, $x + 1) . $result . '</body></html>';
        } else {

        }

        if (is_object($email)) {
            $email->message = $result;
            $email->subject = $subject;
            return true;
        }

        // Kept for compatibility
        return array('body' => $result, 'subject' => $subject);
    }

    function remove_block_data($text)
    {
        // TODO: Lavorare!
        return $text;
    }

    static function get_outlook_wrapper_open($width = 600)
    {
        return '<!--[if mso | IE]><table role="presentation" border="0" cellpadding="0" align="center" cellspacing="0" width="' . $width . '"><tr><td width="' . $width . '" style="vertical-align:top;width:' . $width . 'px;"><![endif]-->';
    }

    static function get_outlook_wrapper_close()
    {
        return "<!--[if mso | IE]></td></tr></table><![endif]-->";
    }

    function hook_safe_style_css($rules)
    {
        $rules[] = 'display';
        return $rules;
    }

    /**
     * Renders a block identified by its id, using the block options and adding a wrapper
     * if required (for the first block rendering.
     * @param type $block_id
     * @param type $wrapper
     * @param type $options
     */
    function render_block($block_id = null, $wrapper = false, $options = [], $context = [], $composer = [])
    {
        static $kses_style_filter = false;
        include_once NEWSLETTER_INCLUDES_DIR . '/helper.php';

        //Remove 'options_composer_' prefix
        $composer_defaults = [];
        foreach (TNP_Composer::get_global_style_defaults() as $global_option_name => $global_option) {
            $composer_defaults[str_replace('options_composer_', '', $global_option_name)] = $global_option;
        }
        $composer = array_merge($composer_defaults, $composer);

        // ========================================================= //
        // ===============    GLOBAL VARIABLES    ================== //
        // ========================================================= //

        $width = 600;
        $font_family = 'Helvetica, Arial, sans-serif';

        $global_title_font_family = $composer['title_font_family'];
        $global_title_font_size = $composer['title_font_size'];
        $global_title_font_color = $composer['title_font_color'];
        $global_title_font_weight = $composer['title_font_weight'];

        $global_text_font_family = $composer['text_font_family'];
        $global_text_font_size = $composer['text_font_size'];
        $global_text_font_color = $composer['text_font_color'];
        $global_text_font_weight = $composer['text_font_weight'];

        $global_button_font_family = $composer['button_font_family'];
        $global_button_font_size = $composer['button_font_size'];
        $global_button_font_color = $composer['button_font_color'];
        $global_button_font_weight = $composer['button_font_weight'];
        $global_button_background_color = $composer['button_background_color'];

        $global_block_background = $composer['block_background'];

        $info = Newsletter::instance()->get_options('info');

        // ============================================================= //
        // ===============    END GLOBAL VARIABLES    ================== //
        // ============================================================= //

        // Just in case...
        if (!is_array($options)) {
            $options = array();
        }


        add_filter('safe_style_css', [$this, 'hook_safe_style_css']);
        $options = wp_kses_post_deep($options);
        remove_filter('safe_style_css', [$this, 'hook_safe_style_css']);

        $block_options = get_option('newsletter_main');

        $block = $this->get_block($block_id);

        if (!isset($context['type']))
            $context['type'] = '';

        // Block not found
        if (!$block) {
            if ($wrapper) {
                echo '<table border="0" cellpadding="0" cellspacing="0" align="center" width="100%" style="border-collapse: collapse; width: 100%;" class="tnpc-row tnpc-row-block" data-id="', esc_attr($block_id), '">';
                echo '<tr>';
                echo '<td data-options="" bgcolor="#ffffff" align="center" style="padding: 0; font-family: Helvetica, Arial, sans-serif;" class="edit-block">';
            }
            echo $this->get_outlook_wrapper_open($width);

            echo '<p>Ops, this block type is no more registered!</p>';

            echo $this->get_outlook_wrapper_close();

            if ($wrapper) {
                echo '</td></tr></table>';
            }
            return;
        }

        $out = array('subject' => '', 'return_empty_message' => false, 'stop' => false, 'skip' => false);

        $dir = is_rtl() ? 'rtl' : 'ltr';
        $align_left = is_rtl() ? 'right' : 'left';
        $align_right = is_rtl() ? 'left' : 'right';

        ob_start();
        $logger = $this->logger;
        include $block['dir'] . '/block.php';
        $content = trim(ob_get_clean());

        if (empty($content)) {
            return $out;
        }

        $common_defaults = array(
            'block_padding_top' => 0,
            'block_padding_bottom' => 0,
            'block_padding_right' => 0,
            'block_padding_left' => 0,
            'block_background' => '',
            'block_background_2' => ''
        );

        $options = array_merge($common_defaults, $options);

        // Obsolete
        $content = str_replace('{width}', $width, $content);

        $content = $this->inline_css($content, true);

        // CSS driven by the block
        // Requited for the server side parsing and rendering
        $options['block_id'] = $block_id;

        $options['block_padding_top'] = (int)str_replace('px', '', $options['block_padding_top']);
        $options['block_padding_bottom'] = (int)str_replace('px', '', $options['block_padding_bottom']);
        $options['block_padding_right'] = (int)str_replace('px', '', $options['block_padding_right']);
        $options['block_padding_left'] = (int)str_replace('px', '', $options['block_padding_left']);

        $block_background = empty($options['block_background']) ? $global_block_background : $options['block_background'];

        // Internal TD wrapper
        $style = 'text-align: center; ';
        $style .= 'width: 100%!important; ';
        $style .= 'padding-top: ' . $options['block_padding_top'] . 'px; ';
        $style .= 'padding-left: ' . $options['block_padding_left'] . 'px; ';
        $style .= 'padding-right: ' . $options['block_padding_right'] . 'px; ';
        $style .= 'padding-bottom: ' . $options['block_padding_bottom'] . 'px; ';
        $style .= 'background-color: ' . $block_background . ';';

        if (isset($options['block_background_gradient'])) {
            $style .= 'background: linear-gradient(180deg, ' . $block_background . ' 0%, ' . $options['block_background_2'] . '  100%);';
        }

        $data = $this->options_encode($options);
        // First time block creation wrapper
        if ($wrapper) {
            echo '<table border="0" cellpadding="0" cellspacing="0" align="center" width="100%" style="border-collapse: collapse; width: 100%;" class="tnpc-row tnpc-row-block" data-id="', esc_attr($block_id), '">', "\n";
            echo "<tr>";
            echo '<td align="center" style="padding: 0;" class="edit-block">', "\n";
        }

        // Container that fixes the width and makes the block responsive
        echo $this->get_outlook_wrapper_open($width);

        echo '<table type="options" data-json="', esc_attr($data), '" class="tnpc-block-content" border="0" cellpadding="0" align="center" cellspacing="0" width="100%" style="width: 100%!important; max-width: ', $width, 'px!important">', "\n";
        echo "<tr>";
        echo '<td align="center" style="', $style, '" bgcolor="', $block_background, '" width="100%">';

        //echo "<!-- block generated content -->\n";
        echo trim($content);
        //echo "\n<!-- /block generated content -->\n";

        echo "</td></tr></table>";
        echo $this->get_outlook_wrapper_close();

        // First time block creation wrapper
        if ($wrapper) {
            echo "</td></tr></table>";
        }

        return $out;
    }

    /**
     * Ajax call to render a block with a new set of options after the settings popup
     * has been saved.
     *
     * @param type $block_id
     * @param type $wrapper
     */
    function tnpc_render_callback()
    {
        if (!check_ajax_referer('save')) {
            $this->dienow('Expired request');
        }

        $block_id = $_POST['b'];
        $wrapper = isset($_POST['full']);
        $options = $this->restore_options_from_request();

        $this->render_block($block_id, $wrapper, $options, [], $_POST['composer']);
        wp_die();
    }

    function hook_wp_ajax_tnpc_regenerate_email()
    {

        $content = stripslashes($_POST['content']);
        $global_options = $_POST['composer'];

        $regenerated_content = $this->regenerate_email_blocks($content, $global_options);

        wp_send_json_success([
            'content' => $regenerated_content,
            'message' => __('Successfully updated', 'newsletter')
        ]);

    }

    private function regenerate_email_blocks($content, $global_options)
    {

        $raw_block_options = $this->extract_encoded_blocks_options($content);

        $regenerated_content = '';

        foreach ($raw_block_options as $raw_block_option) {

            /*		    $a             = html_entity_decode( $raw_block_option, ENT_QUOTES, 'UTF-8' );
                        $block_options = $this->options_decode( $a );*/

            $block_options = $this->options_decode($raw_block_option);

            $block = $this->get_block($block_options['block_id']);
            if (!$block) {
                $this->logger->debug('Unable to load the block ' . $block_options['block_id']);
            }

            ob_start();
            $this->render_block($block_options['block_id'], true, $block_options, [], $global_options);
            $block_html = ob_get_clean();

            $regenerated_content .= $block_html;
        }

        return $regenerated_content;

    }

    /**
     * @param string $html_email_content Email html content
     *
     * @return string[] Encoded options of email blocks
     */
    private function extract_encoded_blocks_options($html_email_content)
    {

        preg_match_all('/data-json="(.*?)"/m', $html_email_content, $raw_block_options, PREG_PATTERN_ORDER);

        return $raw_block_options[1];

    }

    function tnpc_preview_callback()
    {
        $email = Newsletter::instance()->get_email($_REQUEST['id'], ARRAY_A);

        if (empty($email)) {
            echo 'Wrong email identifier';
            return;
        }

        echo $email['message'];

        wp_die();
    }

    function tnpc_css_callback()
    {
        include NEWSLETTER_DIR . '/emails/tnp-composer/css/newsletter.css';
        wp_die();
    }

    /** Returns the correct admin page to edit the newsletter with the correct editor. */
    function get_editor_url($email_id, $editor_type)
    {
        switch ($editor_type) {
            case NewsletterEmails::EDITOR_COMPOSER:
                return admin_url("admin.php") . '?page=newsletter_emails_composer&id=' . $email_id;
            case NewsletterEmails::EDITOR_HTML:
                return admin_url("admin.php") . '?page=newsletter_emails_editorhtml&id=' . $email_id;
            case NewsletterEmails::EDITOR_TINYMCE:
                return admin_url("admin.php") . '?page=newsletter_emails_editortinymce&id=' . $email_id;
        }
    }

    /**
     * Returns the button linked to the correct "edit" page for the passed newsletter. The edit page can be an editor
     * or the targeting page (it depends on newsletter status).
     *
     * @param TNP_Email $email
     */
    function get_edit_button($email, $only_icon = false)
    {

        $editor_type = $this->get_editor_type($email);
        if ($email->status == 'new') {
            $edit_url = $this->get_editor_url($email->id, $editor_type);
        } else {
            $edit_url = 'admin.php?page=newsletter_emails_edit&id=' . $email->id;
        }
        switch ($editor_type) {
            case NewsletterEmails::EDITOR_COMPOSER:
                $icon_class = 'th-large';
                break;
            case NewsletterEmails::EDITOR_HTML:
                $icon_class = 'code';
                break;
            default:
                $icon_class = 'edit';
                break;
        }
        if ($only_icon) {
            return '<a class="button-primary" href="' . $edit_url . '" title="' . esc_attr__('Edit', 'newsletter') . '">' .
                '<i class="fas fa-' . $icon_class . '"></i></a>';

        } else {
            return '<a class="button-primary" href="' . $edit_url . '" title="' . esc_attr__('Edit', 'newsletter') . '">' .
                '<i class="fas fa-' . $icon_class . '"></i> ' . __('Edit', 'newsletter') . '</a>';
        }
    }

    /** Returns the correct editor type for the provided newsletter. Contains backward compatibility code. */
    function get_editor_type($email)
    {
        $email = (object)$email;
        $editor_type = $email->editor;

        // Backward compatibility
        $email_options = maybe_unserialize($email->options);
        if (isset($email_options['composer'])) {
            $editor_type = NewsletterEmails::EDITOR_COMPOSER;
        }
        // End backward compatibility

        return $editor_type;
    }

    /**
     *
     * @param type $action
     * @param type $user
     * @param type $email
     * @return type
     * @global wpdb $wpdb
     */
    function hook_newsletter_action($action, $user, $email)
    {
        global $wpdb;

        switch ($action) {
            case 'v':
            case 'view':
                $id = $_GET['id'];
                if ($id == 'last') {
                    $email = $wpdb->get_row("select * from " . NEWSLETTER_EMAILS_TABLE . " where private=0 and type='message' and status='sent' order by send_on desc limit 1");
                } else {
                    $email = $this->get_email($_GET['id']);
                }
                if (empty($email)) {
                    header("HTTP/1.0 404 Not Found");
                    die('Email not found');
                }

                if (!Newsletter::instance()->is_allowed()) {

                    if ($email->status == 'new') {
                        header("HTTP/1.0 404 Not Found");
                        die('Not sent yet');
                    }

                    if ($email->private == 1) {
                        if (!$user) {
                            header("HTTP/1.0 404 Not Found");
                            die('No available for online view');
                        }
                        $sent = $wpdb->get_row($wpdb->prepare("select * from " . NEWSLETTER_SENT_TABLE . " where email_id=%d and user_id=%d limit 1", $email->id, $user->id));
                        if (!$sent) {
                            header("HTTP/1.0 404 Not Found");
                            die('No available for online view');
                        }
                    }
                }


                header('Content-Type: text/html;charset=UTF-8');
                header('X-Robots-Tag: noindex,nofollow,noarchive');
                header('Cache-Control: no-cache,no-store,private');

                echo $this->replace($email->message, $user, $email);

                die();
                break;

            case 'emails-css':
                $email_id = (int)$_GET['id'];

                $body = Newsletter::instance()->get_email_field($email_id, 'message');

                $x = strpos($body, '<style');
                if ($x === false)
                    return;

                $x = strpos($body, '>', $x);
                $y = strpos($body, '</style>');

                header('Content-Type: text/css;charset=UTF-8');

                echo substr($body, $x + 1, $y - $x - 1);

                die();
                break;

            case 'emails-composer-css':
                header('Cache: no-cache');
                header('Content-Type: text/css');
                echo $this->get_composer_css();
                die();
                break;

            case 'emails-preview':
                if (!Newsletter::instance()->is_allowed()) {
                    die('Not enough privileges');
                }

                if (!check_admin_referer('view')) {
                    die();
                }

                $theme_id = $_GET['id'];
                $theme = $this->themes->get_theme($theme_id);

                // Used by theme code
                $theme_options = $this->themes->get_options($theme_id);

                $theme_url = $theme['url'];

                header('Content-Type: text/html;charset=UTF-8');

                include $theme['dir'] . '/theme.php';

                die();
                break;

            case 'emails-preview-text':
                header('Content-Type: text/plain;charset=UTF-8');
                if (!Newsletter::instance()->is_allowed()) {
                    die('Not enough privileges');
                }

                if (!check_admin_referer('view')) {
                    die();
                }

                // Used by theme code
                $theme_options = $this->get_current_theme_options();

                $file = include $theme['dir'] . '/theme-text.php';

                if (is_file($file)) {
                    include $file;
                }

                die();
                break;


            case 'emails-create':
                // Newsletter from themes are created on frontend context because sometime WP themes change the way the content,
                // excerpt, thumbnail are extracted.
                if (!Newsletter::instance()->is_allowed()) {
                    die('Not enough privileges');
                }

                require_once NEWSLETTER_INCLUDES_DIR . '/controls.php';
                $controls = new NewsletterControls();

                if (!$controls->is_action('create')) {
                    die('Wrong call');
                }

                $theme_id = $controls->data['id'];
                $theme = $this->themes->get_theme($theme_id);

                if (!$theme) {
                    die('invalid theme');
                }

                $this->themes->save_options($theme_id, $controls->data);

                $email = array();
                $email['status'] = 'new';
                $email['subject'] = ''; //__('Here the email subject', 'newsletter');
                $email['track'] = 1;
                $email['send_on'] = time();
                $email['editor'] = NewsletterEmails::EDITOR_TINYMCE;
                $email['type'] = 'message';

                $theme_options = $this->themes->get_options($theme_id);

                $theme_url = $theme['url'];
                $theme_subject = '';

                ob_start();
                include $theme['dir'] . '/theme.php';
                $email['message'] = ob_get_clean();


                if (!empty($theme_subject)) {
                    $email['subject'] = $theme_subject;
                }

                if (file_exists($theme['dir'] . '/theme-text.php')) {
                    ob_start();
                    include $theme['dir'] . '/theme-text.php';
                    $email['message_text'] = ob_get_clean();
                } else {
                    $email['message_text'] = 'You need a modern email client to read this email. Read it online: {email_url}.';
                }

                $email = $this->save_email($email);

                $edit_url = $this->get_editor_url($email->id, $email->editor);

                header('Location: ' . $edit_url);

                die();
                break;
        }
    }

    function admin_menu()
    {
        $this->add_menu_page('index', 'Newsletters');
        $this->add_admin_page('list', 'Email List');
        $this->add_admin_page('new', 'Email New');
        $this->add_admin_page('edit', 'Email Edit');
        $this->add_admin_page('theme', 'Email Themes');
        $this->add_admin_page('composer', 'The Composer');
        $this->add_admin_page('editorhtml', 'HTML Editor');
        $this->add_admin_page('editortinymce', 'TinyMCE Editor');
    }

    /**
     * Builds a block data structure starting from the folder containing the block
     * files.
     *
     * @param string $dir
     * @return array | WP_Error
     */
    function build_block($dir)
    {
        $file = basename($dir);
        $block_id = sanitize_key($file);
        $full_file = $dir . '/block.php';
        if (!is_file($full_file)) {
            return new WP_Error('1', 'Missing block.php file in ' . $dir);
        }

        if (!is_file($dir . '/icon.png')) {
            $relative_dir = substr($dir, strlen(WP_CONTENT_DIR));
            $data['icon'] = content_url($relative_dir . '/icon.png');
        }

        $data = get_file_data($full_file, array('name' => 'Name', 'section' => 'Section', 'description' => 'Description', 'type' => 'Type'));
        $defaults = array('section' => 'content', 'name' => $file, 'descritpion' => '', 'icon' => NEWSLETTER_URL . '/images/block-icon.png', 'content' => '');
        $data = array_merge($defaults, $data);

        if (is_file($dir . '/icon.png')) {
            $relative_dir = substr($dir, strlen(WP_CONTENT_DIR));
            $data['icon'] = content_url($relative_dir . '/icon.png');
        }

        $data['id'] = $block_id;

        // Absolute path of the block files
        $data['dir'] = $dir;

        return $data;
    }

    /**
     *
     * @param type $dir
     * @return type
     */
    function scan_blocks_dir($dir)
    {

        if (!is_dir($dir)) {
            return array();
        }

        $handle = opendir($dir);
        $list = array();
        $relative_dir = substr($dir, strlen(WP_CONTENT_DIR));
        while ($file = readdir($handle)) {

            if ($file == '.' || $file == '..')
                continue;

            $data = $this->build_block($dir . '/' . $file);

            if (is_wp_error($data)) {
                $this->logger->error($data);
                continue;
            }
            $list[$data['id']] = $data;
        }
        closedir($handle);
        return $list;
    }

    /**
     * Array of arrays with every registered block and legacy block converted to the new
     * format.
     *
     * @return array
     */
    function get_blocks()
    {

        if (!is_null($this->blocks)) {
            return $this->blocks;
        }

        $this->blocks = $this->scan_blocks_dir(__DIR__ . '/blocks');

        $extended = $this->scan_blocks_dir(WP_CONTENT_DIR . '/extensions/newsletter/blocks');

        $this->blocks = array_merge($extended, $this->blocks);

        $dirs = apply_filters('newsletter_blocks_dir', array());

        $this->logger->debug('Block dirs:');
        $this->logger->debug($dirs);

        foreach ($dirs as $dir) {
            $dir = str_replace('\\', '/', $dir);
            $list = $this->scan_blocks_dir($dir);
            $this->blocks = array_merge($list, $this->blocks);
        }

        do_action('newsletter_register_blocks');

        foreach (TNP_Composer::$block_dirs as $dir) {
            $block = $this->build_block($dir);
            if (is_wp_error($block)) {
                $this->logger->error($block);
                continue;
            }
            if (!isset($this->blocks[$block['id']])) {
                $this->blocks[$block['id']] = $block;
            } else {
                $this->logger->error('The block "' . $block['id'] . '" is already registered');
            }
        }

        $this->blocks = array_reverse($this->blocks);
        return $this->blocks;
    }

    /**
     * Return a single block (associative array) checking for legacy ID as well.
     *
     * @param string $id
     * @return array
     */
    function get_block($id)
    {
        switch ($id) {
            case 'content-03-text.block':
                $id = 'text';
                break;
            case 'footer-03-social.block':
                $id = 'social';
                break;
            case 'footer-02-canspam.block':
                $id = 'canspam';
                break;
            case 'content-05-image.block':
                $id = 'image';
                break;
            case 'header-01-header.block':
                $id = 'header';
                break;
            case 'footer-01-footer.block':
                $id = 'footer';
                break;
            case 'content-02-heading.block':
                $id = 'heading';
                break;
            case 'content-07-twocols.block':
            case 'content-06-posts.block':
                $id = 'posts';
                break;
            case 'content-04-cta.block':
                $id = 'cta';
                break;
            case 'content-01-hero.block':
                $id = 'hero';
                break;
//            case 'content-02-heading.block': $id = '/plugins/newsletter/emails/blocks/heading';
//                break;
        }

        // Conversion for old full path ID
        $id = sanitize_key(basename($id));

        // TODO: Correct id for compatibility
        $blocks = $this->get_blocks();
        if (!isset($blocks[$id])) {
            return null;
        }
        return $blocks[$id];
    }

    function scan_presets_dir($dir = null)
    {

        if (is_null($dir)) {
            $dir = __DIR__ . '/presets';
        }

        if (!is_dir($dir)) {
            return array();
        }

        $handle = opendir($dir);
        $list = array();
        $relative_dir = substr($dir, strlen(WP_CONTENT_DIR));
        while ($file = readdir($handle)) {

            if ($file == '.' || $file == '..')
                continue;

            // The block unique key, we should find out how to build it, maybe an hash of the (relative) dir?
            $preset_id = sanitize_key($file);

            $full_file = $dir . '/' . $file . '/preset.json';

            if (!is_file($full_file)) {
                continue;
            }

            $icon = content_url($relative_dir . '/' . $file . '/icon.png');

            $list[$preset_id] = $icon;
        }
        closedir($handle);
        return $list;
    }

    function get_preset_from_file($id, $dir = null)
    {

        if (is_null($dir)) {
            $dir = __DIR__ . '/presets';
        }

        $id = $this->sanitize_file_name($id);

        if (!is_dir($dir . '/' . $id) || !in_array($id, self::$PRESETS_LIST)) {
            return array();
        }

        $json_content = file_get_contents("$dir/$id/preset.json");
        $json_content = str_replace("{placeholder_base_url}", plugins_url('newsletter') . '/emails/presets', $json_content);
        $json = json_decode($json_content);
        $json->icon = NEWSLETTER_URL . "/emails/presets/$id/icon.png?ver=2";

        return $json;
    }

    function get_composer_css()
    {
        $css = file_get_contents(__DIR__ . '/tnp-composer/css/newsletter.css');
        $blocks = $this->get_blocks();
        foreach ($blocks as $block) {
            if (!file_exists($block['dir'] . '/style.css')) {
                continue;
            }
            $css .= "\n\n";
            $css .= "/* " . $block['name'] . " */\n";
            $css .= file_get_contents($block['dir'] . '/style.css');
        }
        return $css;
    }

    /**
     * Send an email to the test subscribers.
     *
     * @param TNP_Email $email Could be any object with the TNP_Email attributes
     * @param NewsletterControls $controls
     */
    function send_test_email($email, $controls)
    {
        if (!$email) {
            $controls->errors = __('Newsletter should be saved before send a test', 'newsletter');
            return;
        }
        $original_subject = $email->subject;

        if ($email->subject == '') {
            $email->subject = '[TEST] Dummy subject, it was empty (remember to set it)';
        } else {
            $email->subject = $email->subject . ' (TEST)';
        }
        $users = NewsletterUsers::instance()->get_test_users();
        if (count($users) == 0) {
            $controls->errors = '' . __('There are no test subscribers to send to', 'newsletter') .
                '. <a href="https://www.thenewsletterplugin.com/plugins/newsletter/subscribers-module#test" target="_blank"><strong>' .
                __('Read more', 'newsletter') . '</strong></a>.';
        } else {
            $r = Newsletter::instance()->send($email, $users, true);
            $emails = array();
            foreach ($users as $user) {
                $emails[] = '<a href="admin.php?page=newsletter_users_edit&id=' . $user->id . '" target="_blank">' . $user->email . '</a>';
            }
            if (is_wp_error($r)) {
                $controls->errors = 'Something went wrong. Check the error logs on status page.<br>';
                $controls->errors .= __('Test subscribers:', 'newsletter');
                $controls->errors .= ' ' . implode(', ', $emails);
                $controls->errors .= '<br>';
                $controls->errors .= '<strong>' . esc_html($r->get_error_message()) . '</strong><br>';
                $controls->errors .= '<a href="https://www.thenewsletterplugin.com/documentation/email-sending-issues" target="_blank"><strong>' . __('Read more about delivery issues', 'newsletter') . '</strong></a>.';
            } else {
                $controls->messages = __('Test subscribers:', 'newsletter');

                $controls->messages .= ' ' . implode(', ', $emails);
                $controls->messages .= '.<br>';
                $controls->messages .= '<a href="https://www.thenewsletterplugin.com/documentation/subscribers#test" target="_blank"><strong>' .
                    __('Read more about test subscribers', 'newsletter') . '</strong></a>.<br>';
                $controls->messages .= '<a href="https://www.thenewsletterplugin.com/documentation/email-sending-issues" target="_blank"><strong>' . __('Read more about delivery issues', 'newsletter') . '</strong></a>.';
            }
        }
        $email->subject = $original_subject;
    }

    function restore_options_from_request()
    {

        if (isset($_POST['options']) && is_array($_POST['options'])) {
            // Get all block options
            $options = stripslashes_deep($_POST['options']);

            // Deserialize inline edits when
            // render is preformed on saving block options
            if (isset($options['inline_edits']) && !is_array($options['inline_edits'])) {
                $options['inline_edits'] = $this->options_decode($options['inline_edits']);
            }

            // Restore inline edits from data-json
            // coming from inline editing
            // and merge with current inline edit
            if (isset($_POST['encoded_options'])) {
                $decoded_options = $this->options_decode($_POST['encoded_options']);

                $to_merge_inline_edits = [];

                if (isset($decoded_options['inline_edits'])) {
                    foreach ($decoded_options['inline_edits'] as $decoded_inline_edit) {
                        $to_merge_inline_edits[$decoded_inline_edit['post_id'] . $decoded_inline_edit['type']] = $decoded_inline_edit;
                    }
                }

                //Overwrite with new edited content
                if (isset($options['inline_edits'])) {
                    foreach ($options['inline_edits'] as $inline_edit) {
                        $to_merge_inline_edits[$inline_edit['post_id'] . $inline_edit['type']] = $inline_edit;
                    }
                }

                $options['inline_edits'] = array_values($to_merge_inline_edits);
                $options = array_merge($decoded_options, $options);
            }

            return $options;
        }

        return array();
    }

    public function hook_wp_ajax_tnpc_delete_preset()
    {

        if (!wp_verify_nonce($_POST['_wpnonce'], 'preset')) {
            wp_send_json_error('Expired request');
        }

        $preset_id = (int)$_REQUEST['presetId'];

        $newsletter = Newsletter::instance();

        if ($preset_id > 0) {
            $preset = $newsletter->get_email($preset_id);

            if ($preset && $preset->type === self::PRESET_EMAIL_TYPE) {
                Newsletter::instance()->delete_email($preset_id);
                wp_send_json_success();
            } else {
                wp_send_json_error(__('Is not a preset!', 'newsletter'));
            }

        } else {
            wp_send_json_error();
        }

    }

}

NewsletterEmails::instance();
