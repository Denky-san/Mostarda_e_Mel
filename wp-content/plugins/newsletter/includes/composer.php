<?php

/** For old style coders */
function tnp_register_block($dir) {
    return TNP_Composer::register_block($dir);
}

/**
 * Generates and HTML button for email using the values found on $options and
 * prefixed by $prefix, with the standard syntax of NewsletterFields::button().
 *
 * @param array $options
 * @param string $prefix
 * @return string
 */
function tnpc_button($options, $prefix = 'button') {
    return TNP_Composer::button($options, $prefix);
}

class TNP_Composer {

    static $block_dirs = array();

    static function register_block($dir) {
        // Checks

        if (!file_exists($dir . '/block.php')) {
            $error = new WP_Error('1', 'block.php missing on folder ' . $dir);
            NewsletterEmails::instance()->logger->error($error);
            return $error;
        }
        self::$block_dirs[] = $dir;
        return true;
    }

    /**
     * @param string $open
     * @param string $inner
     * @param string $close
     * @param string[] $markers
     *
     * @return string
     */
    static function wrap_html_element($open, $inner, $close, $markers = array('<!-- tnp -->', '<!-- /tnp -->')) {

        return $open . $markers[0] . $inner . $markers[1] . $close;
    }

    /**
     * @param string $block
     * @param string[] $markers
     *
     * @return string
     */
    static function unwrap_html_element($block, $markers = array('<!-- tnp -->', '<!-- /tnp -->')) {
        if (self::_has_markers($block, $markers)) {
            self::_escape_markers($markers);
            $pattern = sprintf('/%s(.*?)%s/s', $markers[0], $markers[1]);

            $matches = array();
            preg_match($pattern, $block, $matches);

            return $matches[1];
        }

        return $block;
    }

    /**
     * @param string $block
     * @param string[] $markers
     *
     * @return bool
     */
    private static function _has_markers($block, $markers = array('<!-- tnp -->', '<!-- /tnp -->')) {

        self::_escape_markers($markers);

        $pattern = sprintf('/%s(.*?)%s/s', $markers[0], $markers[1]);

        return preg_match($pattern, $block);
    }

    static function get_html_open($email) {
        $open = "<!DOCTYPE html>\n";
        $open .= "<html>\n<head>\n<title>{email_subject}</title>\n";
        $open .= "<meta charset=\"utf-8\">\n<meta name=\"viewport\" content=\"width=device-width, initial-scale=1\">\n";
        $open .= "<meta http-equiv=\"X-UA-Compatible\" content=\"IE=edge\">\n";
        $open .= "<style type=\"text/css\">\n";
        $open .= NewsletterEmails::instance()->get_composer_css();
        $open .= "\n</style>\n";
        $open .= "</head>\n";
        $open .= '<body style="margin: 0; padding: 0;" dir="' . (is_rtl() ? 'rtl' : 'ltr') . '">';
        $open .= "\n";
        $open .= self::get_html_preheader($email);

        return $open;
    }

    static private function get_html_preheader($email) {

        if (empty($email->options['preheader'])) {
            return "";
        }

        $preheader_text = $email->options['preheader'];
        $html = "<div style=\"display:none;font-size:1px;color:#ffffff;line-height:1px;max-height:0px;max-width:0px;opacity:0;overflow:hidden;\">$preheader_text</div>";
        $html .= "\n";

        return $html;
    }

    static function get_html_close($email) {
        return "</body>\n</html>";
    }

    /**
     *
     * @param TNP_Email $email
     * @return string
     */
    static function get_main_wrapper_open($email) {
        if (!isset($email->options['composer_background']) || $email->options['composer_background'] == 'inherit') {
            $bgcolor = '';
        } else {
            $bgcolor = $email->options['composer_background'];
        }

        return "\n<table cellpadding='0' cellspacing='0' border='0' width='100%'>\n" .
                "<tr>\n" .
                "<td bgcolor='$bgcolor' valign='top'><!-- tnp -->";
    }

    /**
     *
     * @param TNP_Email $email
     * @return string
     */
    static function get_main_wrapper_close($email) {
        return "\n<!-- /tnp -->\n" .
                "</td>\n" .
                "</tr>\n" .
                "</table>\n\n";
    }

    /**
     * Remove <doctype>, <body> and unnecessary envelopes for editing with composer
     *
     * @param string $html_email
     *
     * @return string
     */
    static function unwrap_email($html_email) {

        if (self::_has_markers($html_email)) {
            $html_email = self::unwrap_html_element($html_email);
        } else {
            //KEEP FOR OLD EMAIL COMPATIBILITY
            // Extracts only the body part
            $x = strpos($html_email, '<body');
            if ($x) {
                $x = strpos($html_email, '>', $x);
                $y = strpos($html_email, '</body>');
                $html_email = substr($html_email, $x + 1, $y - $x - 1);
            }

            /* Cleans up uncorrectly stored newsletter bodies */
            $html_email = preg_replace('/<style\s+.*?>.*?<\\/style>/is', '', $html_email);
            $html_email = preg_replace('/<meta.*?>/', '', $html_email);
            $html_email = preg_replace('/<title\s+.*?>.*?<\\/title>/i', '', $html_email);
            $html_email = trim($html_email);
        }

        // Required since esc_html DOES NOT escape the HTML entities (apparently)
        $html_email = str_replace('&', '&amp;', $html_email);
        $html_email = str_replace('"', '&quot;', $html_email);
        $html_email = str_replace('<', '&lt;', $html_email);
        $html_email = str_replace('>', '&gt;', $html_email);

        return $html_email;
    }

    private static function _escape_markers(&$markers) {
        $markers[0] = str_replace('/', '\/', $markers[0]);
        $markers[1] = str_replace('/', '\/', $markers[1]);
    }

    /**
     * Using the data collected inside $controls (and submitted by a form containing the
     * composer fields), updates the email. The message body is completed with doctype,
     * head, style and the main wrapper.
     *
     * @param TNP_Email $email
     * @param NewsletterControls $controls
     */
    static function update_email($email, $controls) {
        if (isset($controls->data['subject'])) {
            $email->subject = $controls->data['subject'];
        }

        // They should be only composer options
        foreach ($controls->data as $name => $value) {
            if (strpos($name, 'options_') === 0) {
                $email->options[substr($name, 8)] = $value;
            }
        }

        $email->editor = NewsletterEmails::EDITOR_COMPOSER;

        $email->message = self::get_html_open($email) . self::get_main_wrapper_open($email) .
                $controls->data['message'] . self::get_main_wrapper_close($email) . self::get_html_close($email);
    }

    /**
     * Prepares a controls object injecting the relevant fields from an email
     * which cannot be directly used by controls. If $email is null or missing,
     * $controls is prepared with default values.
     *
     * @param NewsletterControls $controls
     * @param TNP_Email $email
     */
    static function prepare_controls($controls, $email = null) {

        // Controls for a new email (which actually does not exist yet
        if (!empty($email)) {

            foreach ($email->options as $name => $value) {
                //if (strpos($name, 'composer_') === 0) {
                $controls->data['options_' . $name] = $value;
                //}
            }

            $controls->data['message'] = TNP_Composer::unwrap_email($email->message);
            $controls->data['subject'] = $email->subject;
        }

        if (!empty($email->options['sender_email'])) {
            $controls->data['sender_email'] = $email->options['sender_email'];
        } else {
            $controls->data['sender_email'] = Newsletter::instance()->options['sender_email'];
        }

        if (!empty($email->options['sender_name'])) {
            $controls->data['sender_name'] = $email->options['sender_name'];
        } else {
            $controls->data['sender_name'] = Newsletter::instance()->options['sender_name'];
        }

        $controls->data = array_merge(TNP_Composer::get_global_style_defaults(), $controls->data);
    }

    /**
     * Extract inline edited post field from inline_edit_list[]
     *
     * @param array $inline_edit_list
     * @param string $field_type
     * @param int $post_id
     *
     * @return string
     */
    static function get_edited_inline_post_field($inline_edit_list, $field_type, $post_id) {

        foreach ($inline_edit_list as $edit) {
            if ($edit['type'] == $field_type && $edit['post_id'] == $post_id) {
                return $edit['content'];
            }
        }

        return '';
    }

    /**
     * Check if inline_edit_list[] have inline edit field for specific post
     *
     * @param array $inline_edit_list
     * @param string $field_type
     * @param int $post_id
     *
     * @return bool
     */
    static function is_post_field_edited_inline($inline_edit_list, $field_type, $post_id) {
        if (empty($inline_edit_list) || !is_array($inline_edit_list)) {
            return false;
        }
        foreach ($inline_edit_list as $edit) {
            if ($edit['type'] == $field_type && $edit['post_id'] == $post_id) {
                return true;
            }
        }

        return false;
    }

    /**
     * Creates the HTML for a button extrating from the options, with the provided prefix, the button attributes:
     *
     * - [prefix]_url The button URL
     * - [prefix]_font_family
     * - [prefix]_font_size
     * - [prefix]_font_weight
     * - [prefix]_label
     * - [prefix]_font_color The label color
     * - [prefix]_background The button color
     *
     * TODO: Add radius and possiblt the alignment
     *
     * @param array $options
     * @param string $prefix
     * @return string
     */
    static function button($options, $prefix = 'button') {

        $defaults = [
            $prefix . '_url' => '#',
            $prefix . '_font_family' => 'Helvetica, Arial, sans-serif',
            $prefix . '_label' => 'Click Here',
            $prefix . '_font_color' => '#ffffff',
            $prefix . '_font_weight' => 'bold',
            $prefix . '_font_size' => 20,
            $prefix . '_background' => '#256F9C',
            $prefix . '_align' => 'center'
        ];

        $options = array_merge($defaults, array_filter($options));

        $b = '<table border="0" cellpadding="0" cellspacing="0" role="presentation" style="border-collapse:separate;line-height:100%;"';
        if (!empty($options[$prefix . '_align'])) {
            $b .= ' align="' . esc_attr($options[$prefix . '_align']) . '"';
        }
        if (!empty($options[$prefix . '_width'])) {
            $b .= ' width="' . esc_attr($options[$prefix . '_width']) . '"';
        }
        $b .= '>';
        $b .= '<tr>';
        $b .= '<td align="center" bgcolor="' . $options[$prefix . '_background'] . '" role="presentation" style="border:none;border-radius:3px;cursor:auto;mso-padding-alt:10px 25px;background:' . $options[$prefix . '_background'] . '" valign="middle">';
        $b .= '<a href="' . $options[$prefix . '_url'] . '"';
        $b .= ' style="display:inline-block;background:' . $options[$prefix . '_background'] . ';color:' . $options[$prefix . '_font_color'] . ';font-family:' . $options[$prefix . '_font_family'] . ';font-size:' . $options[$prefix . '_font_size'] . 'px;font-weight:' . $options[$prefix . '_font_weight'] . ';line-height:120%;margin:0;text-decoration:none;text-transform:none;padding:10px 25px;mso-padding-alt:0px;border-radius:3px;"';
        $b .= ' target="_blank">';
        $b .= $options[$prefix . '_label'];
        $b .= '</a>';
        $b .= '</td></tr></table>';
        return $b;
    }

    /**
     * Generates an IMG tag, linked if the media has an URL.
     *
     * @param TNP_Media $media
     * @param string $style
     * @return string
     */
    static function image($media, $attr = []) {

        $default_attrs = [
            'style' => 'max-width: 100%; height: auto;',
            'class' => null,
            'link-style' => 'text-decoration: none;',
            'link-class' => null,
        ];

        $attr = array_merge($default_attrs, $attr);

        //Class and style attribute are mutually exclusive.
        //Class take priority to style because classes will transform to inline style inside block rendering operation
        if (!empty($attr['class'])) {
            $styling = ' inline-class="' . $attr['class'] . '" ';
        } else {
            $styling = ' style="' . $attr['style'] . '" ';
        }

        //Class and style attribute are mutually exclusive.
        //Class take priority to style because classes will transform to inline style inside block rendering operation
        if (!empty($attr['link-class'])) {
            $link_styling = ' inline-class="' . $attr['link-class'] . '" ';
        } else {
            $link_styling = ' style="' . $attr['link-style'] . '" ';
        }

        $b = '';
        if ($media->link) {
            $b .= '<a href="' . $media->link . '" target="_blank" rel="noopener nofollow" ' . $link_styling . '>';
        }

        if ($media) {
            $b .= '<img src="' . $media->url . '" width="' . $media->width . '"'
                    . ' height="auto"'
                    . ' alt="' . esc_attr($media->alt) . '"'
                    . ' border="0" '
                    . $styling
                    . ' class="responsive" '
                    . '>';
        }

        if ($media->link) {
            $b .= '</a>';
        }

        return $b;
    }

    /**
     * Returns a WP media ID for the specified post (or false if nothing can be found)
     * looking for the featured image or, if missing, taking the first media in the gallery and
     * if again missing, searching the first reference to a media in the post content.
     *
     * @param int $post_id
     * @return int
     */
    static function get_post_thumbnail_id($post_id) {
        if (is_object($post_id)) {
            $post_id = $post_id->ID;
        }

        // Find a media id to be used as featured image
        $media_id = get_post_thumbnail_id($post_id);
        if (!empty($media_id)) {
            return $media_id;
        }

        $attachments = get_children(array('numberpost' => 1, 'post_parent' => $post_id, 'post_status' => 'inherit', 'post_type' => 'attachment', 'post_mime_type' => 'image', 'order' => 'ASC', 'orderby' => 'menu_order'));
        if (!empty($attachments)) {
            foreach ($attachments as $id => &$attachment) {
                return $id;
            }
        }

        $post = get_post($post_id);

        $r = preg_match('/wp-image-(\d+)/', $post->post_content, $matches);
        if ($matches) {
            return (int) $matches[1];
        }

        return false;
    }

    static function post_content($post) {
        $content = $post->post_content;
        $content = wpautop($content);
        if (true || $options['enable shortcodes']) {
            remove_shortcode('gallery');
            add_shortcode('gallery', 'tnp_gallery_shortcode');
            $content = do_shortcode($content);
        }
        $content = str_replace('<p>', '<p class="paragraph">', $content);

        $selected_images = array();
        if (preg_match_all('/<img [^>]+>/', $content, $matches)) {
            foreach ($matches[0] as $image) {
                if (preg_match('/wp-image-([0-9]+)/i', $image, $class_id) && ( $attachment_id = absint($class_id[1]) )) {
                    $selected_images[$image] = $attachment_id;
                }
            }
        }

        foreach ($selected_images as $image => $attachment_id) {
            $src = tnp_media_resize($attachment_id, array(600, 0));
            if (is_wp_error($src)) {
                continue;
            }
            $content = str_replace($image, '<img src="' . $src . '" width="600" style="max-width: 100%">', $content);
        }

        return $content;
    }

    static function get_global_style_defaults() {
        return [
            'options_composer_title_font_family' => 'Verdana, Geneva, sans-serif',
            'options_composer_title_font_size' => 36,
            'options_composer_title_font_weight' => 'bold',
            'options_composer_title_font_color' => '#222222',
            'options_composer_text_font_family' => 'Verdana, Geneva, sans-serif',
            'options_composer_text_font_size' => 16,
            'options_composer_text_font_weight' => 'normal',
            'options_composer_text_font_color' => '#222222',
            'options_composer_button_font_family' => 'Verdana, Geneva, sans-serif',
            'options_composer_button_font_size' => 16,
            'options_composer_button_font_weight' => 'bold',
            'options_composer_button_font_color' => '#FFFFFF',
            'options_composer_button_background_color' => '#256F9C',
            'options_composer_background' => '#FFFFFF',
            'options_composer_block_background' => '#FFFFFF',
        ];
    }

}

/**
 * Generate multicolumn and responsive html template for email.
 * Initialize class with max columns per row and start to add cells.
 */
class TNP_Composer_Grid_System {

    /**
     * @var TNP_Composer_Grid_Row[]
     */
    private $rows;

    /**
     * @var int
     */
    private $cells_per_row;

    /**
     * @var int
     */
    private $cells_counter;

    /**
     * TNP_Composer_Grid_System constructor.
     *
     * @param int $columns_per_row Max columns per row
     */
    public function __construct($columns_per_row) {
        $this->cells_per_row = $columns_per_row;
        $this->cells_counter = 0;
        $this->rows = [];
    }

    public function __toString() {
        return $this->render();
    }

    /**
     * Add cell to grid
     *
     * @param TNP_Composer_Grid_Cell $cell
     */
    public function add_cell($cell) {

        if ($this->cells_counter % $this->cells_per_row === 0) {
            $this->add_row(new TNP_Composer_Grid_Row());
        }

        $row_idx = (int) floor($this->cells_counter / $this->cells_per_row);
        $this->rows[$row_idx]->add_cell($cell);
        $this->cells_counter++;
    }

    private function add_row($row) {
        $this->rows[] = $row;
    }

    public function render() {

        $str = '';
        foreach ($this->rows as $row) {
            $str .= $row->render();
        }

        return $str;
    }

}

/**
 * Class TNP_Composer_Grid_Row
 */
class TNP_Composer_Grid_Row {

    /**
     * @var TNP_Composer_Grid_Cell[]
     */
    private $cells;

    public function __construct(...$cells) {
        if (!empty($cells)) {
            foreach ($cells as $cell) {
                $this->add_cell($cell);
            }
        }
    }

    /**
     * @param TNP_Composer_Grid_Cell $cell
     */
    public function add_cell($cell) {
        $this->cells[] = $cell;
    }

    public function render() {
        $rendered_cells = '';
        $column_percentage_width = round(100 / $this->cells_count(), 0, PHP_ROUND_HALF_DOWN) . '%';
        foreach ($this->cells as $cell) {
            $rendered_cells .= $cell->render(['width' => $column_percentage_width]);
        }

        $row_template = $this->get_template();

        return str_replace('TNP_ROW_CONTENT_PH', $rendered_cells, $row_template);
    }

    private function cells_count() {
        return count($this->cells);
    }

    private function get_template() {
        return "<table border='0' cellpadding='0' cellspacing='0' width='100%'><tbody><tr><td>TNP_ROW_CONTENT_PH</td></tr></tbody></table>";
    }

}

/**
 * Class TNP_Composer_Grid_Cell
 */
class TNP_Composer_Grid_Cell {

    /**
     * @var string
     */
    private $content;

    /**
     * @var array
     */
    public $args;

    public function __construct($content = null, $args = []) {
        $default_args = [
            'width' => '100%',
            'class' => '',
            'align' => 'left',
            'valign' => 'top'
        ];

        $this->args = array_merge($default_args, $args);

        $this->content = $content ? $content : '';
    }

    public function add_content($content) {
        $this->content .= $content;
    }

    public function render($args) {
        $this->args = array_merge($this->args, $args);

        $column_template = $this->get_template();
        $column = str_replace(
                [
                    'TNP_ALIGN_PH',
                    'TNP_VALIGN_PH',
                    'TNP_WIDTH_PH',
                    'TNP_CLASS_PH',
                    'TNP_COLUMN_CONTENT_PH'
                ], [
            $this->args['align'],
            $this->args['valign'],
            $this->args['width'],
            $this->args['class'],
            $this->content
                ], $column_template);

        return $column;
    }

    private function get_template() {
        return "<table border='0' cellpadding='0' cellspacing='0' width='TNP_WIDTH_PH' align='left' style='table-layout: fixed;' class='responsive'>
                    <tbody>
                            <tr>
                                <td border='0' style='padding: 20px 10px 40px;' align='TNP_ALIGN_PH' valign='TNP_VALIGN_PH' class='TNP_CLASS_PH'>
                                    TNP_COLUMN_CONTENT_PH
                                </td>
                            </tr>
                    </tbody>
                </table>";
    }

}

class TNP_Composer_Component_Factory {

    private $options;

    /**
     * TNP_Composer_Component_Factory constructor.
     *
     * @param Controller$controller
     */
    public function __construct($controller) {
        
    }

    function heading() {
        
    }

    function paragraph() {
        
    }

    function link() {
        
    }

    function button() {
        
    }

    function image() {
        
    }

}
