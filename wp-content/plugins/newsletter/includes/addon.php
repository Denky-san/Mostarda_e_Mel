<?php

/**
 * User by add-ons as base-class.
 */
class NewsletterAddon {

    var $logger;
    var $admin_logger;
    var $name;
    var $options;
    var $version;
    var $labels;

    public function __construct($name, $version = '0.0.0') {
        $this->name = $name;
        $this->version = $version;
        if (is_admin()) {
            $old_version = get_option('newsletter_' . $name . '_version');
            if ($version !== $old_version) {
                $this->upgrade($old_version === false);
                update_option('newsletter_' . $name . '_version', $version, false);
            }
        }
        add_action('newsletter_init', array($this, 'init'));
        //Load translations from specific addon /languages/ directory
        load_plugin_textdomain('newsletter-' . $this->name, false, 'newsletter-' . $this->name . '/languages/');
    }

    /**
     * Method to be overridden and invoked on version change or on first install.
     *
     * @param bool $first_install
     */
    function upgrade($first_install = false) {
        
    }

    /**
     * Method to be overridden to initialize the add-on. It is invoked when Newsletter
     * fires the <code>newsletter_init</code> event.
     */
    function init() {
        
    }

    function get_current_language() {
        return Newsletter::instance()->get_current_language();
    }

    function is_all_languages() {
        return Newsletter::instance()->is_all_languages();
    }

    function is_allowed() {
        return Newsletter::instance()->is_allowed();
    }
    
    function get_languages() {
        return Newsletter::instance()->get_languages();
    }
    
    function is_multilanguage() {
        return Newsletter::instance()->is_multilanguage();
    }

    /**
     * General logger for this add-on.
     *
     * @return NewsletterLogger
     */
    function get_logger() {
        if (!$this->logger) {
            $this->logger = new NewsletterLogger($this->name);
        }
        return $this->logger;
    }

    /**
     * Specific logger for administrator actions.
     *
     * @return NewsletterLogger
     */
    function get_admin_logger() {
        if (!$this->admin_logger) {
            $this->admin_logger = new NewsletterLogger($this->name . '-admin');
        }
        return $this->admin_logger;
    }

    /**
     * Loads and prepares the options. It can be used to late initialize the options to save some resources on
     * add-ons which do not need to do something on each page load.
     */
    function setup_options() {
        if ($this->options) {
            return;
        }
        $this->options = get_option('newsletter_' . $this->name, []);
    }

    /**
     * Retrieve the stored options, merged with the specified language set.
     * 
     * @param string $language
     * @return array
     */
    function get_options($language = '') {
        if ($language) {
            return array_merge(get_option('newsletter_' . $this->name, []), get_option('newsletter_' . $this->name . '_' . $language, []));
        } else {
            return get_option('newsletter_' . $this->name, []);
        }
    }

    /**
     * Saved the options under the correct keys and update the internal $options
     * property.
     * @param array $options
     */
    function save_options($options, $language = '') {
        if ($language) {
            update_option('newsletter_' . $this->name . '_' . $language, $options);
        } else {
            update_option('newsletter_' . $this->name, $options);
            $this->options = $options;
        }
    }

    function merge_defaults($defaults) {
        $options = get_option('newsletter_' . $this->name, []);
        $options = array_merge($defaults, $options);
        $this->save_options($options);
    }

    /**
     * 
     */
    function setup_labels() {
        if (!$this->labels) {
            $labels = [];
        }
    }

    function get_label($key) {
        if (!$this->options)
            $this->setup_options();

        if (!empty($this->options[$key])) {
            return $this->options[$key];
        }

        if (!$this->labels)
            $this->setup_labels();

        // We assume the required key is defined. If not there is an error elsewhere.
        return $this->labels[$key];
    }

    /**
     * Equivalent to $wpdb->query() but logs the event in case of error.
     *
     * @global wpdb $wpdb
     * @param string $query
     */
    function query($query) {
        global $wpdb;

        $r = $wpdb->query($query);
        if ($r === false) {
            $logger = $this->get_logger();
            $logger->fatal($query);
            $logger->fatal($wpdb->last_error);
        }
        return $r;
    }

}

/**
 * Used by mailer add-ons as base-class. Some specific options collected by the mailer
 * are interpreted automatically.
 *
 * They are:
 *
 * `enabled` if not empty it means the mailer is active and should be registered
 *
 * The options are set up in the constructor, there is no need to setup them later.
 */
class NewsletterMailerAddon extends NewsletterAddon {

    var $enabled = false;

    function __construct($name, $version = '0.0.0') {
        parent::__construct($name, $version);
        $this->setup_options();
        $this->enabled = !empty($this->options['enabled']);
    }

    /**
     * This method must be called as `parent::init()` is overridden.
     */
    function init() {
        parent::init();
        add_action('newsletter_register_mailer', function () {
            if ($this->enabled) {
                Newsletter::instance()->register_mailer($this->get_mailer());
            }
        });
    }

    /**
     * Must return an implementation of NewsletterMailer.
     * @return NewsletterMailer
     */
    function get_mailer() {
        return null;
    }

    function get_last_run() {
        return get_option('newsletter_' . $this->name . '_last_run', 0);
    }

    function save_last_run($time) {
        update_option('newsletter_' . $this->name . '_last_run', $time);
    }

    function save_options($options, $language = '') {
        parent::save_options($options, $language);
        $this->enabled = !empty($options['enabled']);
    }

    /**
     * Returns a TNP_Mailer_Message built to send a test message to the <code>$to</code>
     * email address.
     *
     * @param string $to
     * @param string $subject
     * @return TNP_Mailer_Message
     */
    static function get_test_message($to, $subject = '', $type = '') {
        $message = new TNP_Mailer_Message();
        $message->to = $to;
        $message->to_name = '';
        if (empty($type) || $type == 'html') {
            $message->body = file_get_contents(NEWSLETTER_DIR . '/includes/test-message.html');
            $message->body = str_replace('{plugin_url}', NEWSLETTER_URL, $message->body);
        }

        if (empty($type) || $type == 'text') {
            $message->body_text = 'This is the TEXT version of a test message. You should see this message only if you email client does not support the rich text (HTML) version.';
        }

        $message->headers['X-Newsletter-Email-Id'] = '0';

        if (empty($subject)) {
            $message->subject = '[' . get_option('blogname') . '] Test message from Newsletter (' . date(DATE_ISO8601) . ')';
        } else {
            $message->subject = $subject;
        }

        if ($type) {
            $message->subject .= ' - ' . $type . ' only';
        }

        $message->from = Newsletter::instance()->options['sender_email'];
        $message->from_name = Newsletter::instance()->options['sender_name'];
        return $message;
    }

    /**
     * Returns a set of test messages to be sent to the specified email address. Used for
     * turbo mode tests. Each message has a different generated subject.
     *
     * @param string $to The destination mailbox
     * @param int $count Number of message objects to create
     * @return TNP_Mailer_Message[]
     */
    function get_test_messages($to, $count, $type = '') {
        $messages = array();
        for ($i = 0; $i < $count; $i++) {
            $messages[] = self::get_test_message($to, '[' . get_option('blogname') . '] Test message ' . ($i + 1) . ' from Newsletter (' . date(DATE_ISO8601) . ')', $type);
        }
        return $messages;
    }

}
