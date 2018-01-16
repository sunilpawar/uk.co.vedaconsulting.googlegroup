<?php
class CRM_Googlegroup_Form_Setting extends CRM_Core_Form {

  protected $_values;


  function preProcess() {
    // Needs to be here as from is build before default values are set
    $this->_values = CRM_Googlegroup_Utils::getSettings();
  }
    
  /**
   * Function to actually build the form
   *
   * @return None
   * @access public
   */
  public function buildQuickForm() {

    $this->addElement('text', 'client_key', ts('Client Key'), array(
       'size' => 48,
     ));    
    $this->addElement('text', 'client_secret', ts('Client Secret'), array(
       'size' => 48,
     ));
    
     $this->addElement('text', 'domain_name', ts('Domain Names'), array(
      'size' => 48,
    ));  

    $accessToken = $this->_values['access_token'];
    
    if (empty($accessToken)) {
      $buttons = array(
        array(
          'type' => 'submit',
          'name' => ts('Connect To My Google Group'),
        ),
      );
      $this->addButtons($buttons);
    } else {
      $buttons = array(
        array(
          'type' => 'submit',
          'name' => ts('Save Domains'),
          ),
        );
      $this->addButtons($buttons);
      CRM_Core_Session::setStatus('Connected To Google', 'Information', 'info');
    }
    
    if (isset($_GET['code'])) {
      $client = CRM_Googlegroup_Utils::googleClient();
      $redirectUrl    = CRM_Utils_System::url('civicrm/googlegroup/settings', 'reset=1',  TRUE, NULL, FALSE, TRUE, TRUE);
      $client->setRedirectUri($redirectUrl);
      $client->authenticate($_GET['code']);
      CRM_Core_BAO_Setting::setItem($client->getRefreshToken(), CRM_Googlegroup_Utils::GG_SETTING_GROUP, 'access_token' );
      header('Location: ' . filter_var($redirectUrl, FILTER_SANITIZE_URL));
    }
  }

  public function setDefaultValues() {
    $defaults = $this->_values;
    $defaults['domain_name'] = implode(',', $this->_values['domain_name']);

    return $defaults;
  }

  /**
   * Function to process the form
   *
   * @access public
   *
   * @return None
   */
  public function postProcess() {
    $params = $this->controller->exportValues($this->_name);

    foreach(array('client_key', 'client_secret', 'domain_name') as $setting) {
      if ($setting == 'domain_name') {
        $params['domain_name'] = explode(',', trim($params['domain_name']));
      }
      CRM_Googlegroup_Utils::setSetting($params[$setting], $setting);
    }

    $accessToken = CRM_Core_BAO_Setting::getItem(CRM_Googlegroup_Utils::GG_SETTING_GROUP,
       'access_token', NULL, FALSE
    );
    
    if (empty($accessToken)) {
      $client = CRM_Googlegroup_Utils::googleClient();
      $redirectUrl    = CRM_Utils_System::url('civicrm/googlegroup/settings', 'reset=1',  TRUE, NULL, FALSE, TRUE, TRUE);
      $client->setRedirectUri($redirectUrl);
      $service = new Google_Service_Directory($client);
      $auth_url = $client->createAuthUrl();
      CRM_Core_Error::debug_var('$auth_url', $auth_url);
      header('Location: ' . filter_var($auth_url, FILTER_SANITIZE_URL));
    }
  }
}
