<?php if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

require_once __DIR__ . '/vendor/autoload.php';

use SendGrid\Mail\Mail;
use SendGrid\Mail\From;
use SendGrid\Mail\To;
use SendGrid\Mail\Bcc;
use SendGrid\Mail\BccSettings;
use SendGrid\Mail\Attachment;
use SendGrid\Mail\Cc;
use SendGrid\Mail\Content;
use SendGrid\Mail\HtmlContent;
use SendGrid\Mail\PlainTextContent;
use SendGrid\Mail\Subject;


use ZBateson\MailMimeParser\Message;
use ZBateson\MailMimeParser\Header\HeaderConsts;
use GuzzleHttp\Psr7;


class Mx_sendgrid_ext
{

    public $settings = [];
    public $config = [];

    public $version = '1.0.4';

    public function __construct($settings = '')
    {
        $this->config = ee()->config->item('mx_sendgrid');

        if ($this->config) {
            $settings = $this->config;
        }

        $this->settings = $settings;
    }

    /**
     * [activate_extension description]
     * @return [type] [description]
     */
    public function activate_extension()
    {
        $this->settings = $this->getSettingsFromFile();

        $data = [
            [
                'class'    => __CLASS__,
                'method'   => 'email_send',
                'hook'     => 'email_send',
                'settings' => serialize($this->settings),
                'priority' => 10,
                'version'  => $this->version,
                'enabled'  => 'y'
            ]
        ];

        foreach ($data as $hook) {
            ee()->db->insert('extensions', $hook);
        }
    }

    /**
     * [disable_extension description]
     * @return [type] [description]
     */
    public function disable_extension()
    {
        ee()->db->where('class', __CLASS__);
        ee()->db->delete('extensions');
    }

    /**
     * [update_extension description]
     * @param string $current [description]
     * @return [type]          [description]
     */
    public function update_extension($current = '')
    {
        // UPDATE HOOKS
        return true;
    }


    // --------------------------------
    //  Settings
    // --------------------------------

    public function settings()
    {
        $settings = array();

        $settings['host'] = array('i', '', "");

        $settings['username'] = array('i', '', "");

        $settings['password'] = array('i', '', "");

        return $settings;
    }

    /**
     * Settings Form
     *
     * @param Array   Settings
     * @return  void
     */
    function settings_form($current)
    {
        $name = 'mx_sendgrid';

        if ($current == '') {
            $current = array();
        } else {
            $current = $current;
        }

        if ($this->settings != '') {
            $current = $this->settings;
        }

        $defaults = array(
            'apikey' => '',
            'enable' => true
        );

        $values = array_replace($defaults, $current);

        $vars = array(
            'base_url'              => ee('CP/URL')->make('addons/settings/' . $name . '/save'),
            'cp_page_title'         => 'MX SendGrid Settings',
            'save_btn_text'         => 'btn_save_settings',
            'save_btn_text_working' => 'btn_saving',
            'alerts_name'           => '',
            'sections'              => array(array())
        );

        $vars['sections'] = array(
            array(
                array(
                    'title'  => 'enable',
                    'fields' => array(
                        'enable' => array(
                            'type'     => 'toggle',
                            'value'    => $values['enable'],
                            'required' => false
                        )
                    )
                ),
                array(
                    'title'  => 'apikey',
                    'fields' => array(
                        'apikey' => array(
                            'type'     => 'text',
                            'value'    => $values['apikey'],
                            'required' => true
                        )
                    )
                )
            )
        );

        return ee('View')->make('mx_sendgrid:index')->render($vars);
    }

    /**
     * Save Settings
     *
     * This function provides a little extra processing and validation
     * than the generic settings form.
     *
     * @return void
     */
    function save_settings()
    {
        if (empty($_POST)) {
            show_error(lang('unauthorized_access'));
        }

        ee()->lang->loadfile('mx_sendgrid');

        ee('CP/Alert')->makeInline('mx-sendgrid-save')
            ->asSuccess()
            ->withTitle(lang('message_success'))
            ->addToBody(lang('preferences_updated'))
            ->defer();

        ee()->db->where('class', __CLASS__);
        ee()->db->update('extensions', array('settings' => serialize($_POST)));


        ee()->functions->redirect(ee('CP/URL')->make('addons/settings/mx_sendgrid'));
    }


    public function email_send($data)
    {
        $body = str_replace(array('{unwrap}', '{/unwrap}'), '', $data['finalbody']);

        if (!isset($this->settings['apikey']) || $this->settings['enable'] != true) {
            return false;
        }

        $messageOrg = Message::from($data['header_str'] . $body);

        $data['text']      = $messageOrg->getTextContent();  // plain text body
        $data['html']      = $messageOrg->getHtmlContent(); // html body
        $data['from']      = $messageOrg->getHeader('From');
        $data['fromName']  = $data['from']->getName();
        $data['fromEmail'] = $data['from']->getEmail();

        $data['fromName'] = ($data['fromName'] == "From") ? '' : $data['fromName'];

        if ($data['text'] === null && $data['html'] === null) {
            $data['text'] = $data['finalbody'];
        }

        $data['attachment'] = $messageOrg->getAllAttachmentParts();

        return self::send_api($data, $this->settings);
    }

    /**
     * @param $data
     * @param $host
     * @param $username
     * @param $password
     * @return bool
     */
    public function send_api($data, $settings)
    {
        $sent    = false;
        $tos     = [];
        $cc      = [];
        $bcc     = [];
        $subject = isset($data["subject"]) ? $data["subject"] : '';

        ee()->load->library('logger');

        // Set the recipient.
        foreach ($data["recipients"] as $key => $value) {
            $to                = self::email_split($value);
            $tos[$to['email']] = $to['name'];
        }

        // Set the cc_array
        foreach ($data["cc_array"] as $key => $value) {
            $to               = self::email_split($value);
            $cc[$to['email']] = $to['name'];
        }

        // Set the bcc_array
        foreach ($data["bcc_array"] as $key => $value) {
            $to                = self::email_split($value);
            $bcc[$to['email']] = $to['name'];
        }


        $email = new Mail();

        $email->setFrom($data['fromEmail'], $data['fromName']);

        $email->addTos($tos);

        if (count($cc) > 0) {
            $email->addCcs($cc);
        }

        if (count($bcc) > 0) {
            $email->addBcc($bcc);
        }

        $email->setSubject($subject);

        // Set the message body.
        $email->addContent("text/plain", $data['text']);

        if ($data['html'] !== null) {
            $email->addContent(new HtmlContent($data['html']));
        };



        if ($data['attachment'] !== null) {

            foreach ($data['attachment'] as $index => $attachment) {
                $attachments = [
                    [
                        base64_encode($attachment->getContentStream()),
                        $attachment->getHeaderValue(HeaderConsts::CONTENT_TYPE),
                        $attachment->getFilename(),
                        "attachment",
                        $attachment->getFilename(),
                    ]
                ];

                $email->addAttachments($attachments);
            }



        }

        $sendgrid = new \SendGrid($settings['apikey']);


        try {
            $response = $sendgrid->send($email);
            $sent     = true;

            if ($response->statusCode() > 202) {
                $sent = false;
                ee()->logger->developer("MX SendGrid: Message failed to create with: " . $response->statusCode());
            }
        } catch (Exception $e) {
            $sent = false;
            ee()->logger->developer("MX SendGrid: Message failed to create with: " . $e->getMessage());
        }

        if ($sent == true) {
            ee()->extensions->end_script = true;
            return true;
        }

        return $sent;
    }

    /**
     * [email_split description] Thanks to https://stackoverflow.com/questions/16685416/split-full-email-addresses-into-name-and-email
     * @param  [type] $str [description]
     * @return [type]      [description]
     */
    public function email_split($str)
    {
        $str .= " ";

        $re = '/(?:,\s*)?(.*?)\s*(?|<([^>]*)>|\[([^][]*)]|(\S+@\S+))/';
        preg_match_all($re, $str, $m, PREG_SET_ORDER, 0);

        $name  = (isset($m[0][1])) ? $m[0][1] : '';
        $email = (isset($m[0][2])) ? $m[0][2] : '';

        return array('name' => trim($name), 'email' => trim($email));
    }


    /**
     * [initializeSettings description]
     * @return [type] [description]
     */
    private function initializeSettings()
    {
        // Set up app settings
        $settingData = [
        ];

        return serialize($settingData);
    }

    /**
     * [getSettingsFromFile description]
     * @return [type] [description]
     */
    private function getSettingsFromFile()
    {
        /*


        if (! file_exists(PATH_THIRD . 'mx_sendgrid/config.php')) {
            return $this->initializeSettings();
        }

        require PATH_THIRD . 'mx_sendgrid/config.php';



        return $settingData;
        */

        return array();
    }
}
