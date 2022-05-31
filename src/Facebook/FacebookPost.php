<?php 
namespace Drupal\simpleFacebookPost\Facebook;

use \Facebook\Facebook as Face;
use \Facebook\Exception\ResponseException;
use \Facebook\Exception\SDKException;
use \Drupal\simpleFacebookPost\Post\Post;

class FacebookPost {

  private $app_id;
  private $app_secret;
  private $page_id;
  private $page_access_token;
  private $user_acces_token;

  /**
   *  @method   FacebookPost() Constructor de la clase
   *  @param void
   *  @return void
   */
  public function __construct()
  {
    $this->app_id     = \Drupal::state()->get('simple_facebook_post.facebook_app_id');
    $this->app_secret = \Drupal::state()->get('simple_facebook_post.facebook_app_secret');
    $this->page_id    = \Drupal::state()->get('simple_facebook_post.facebook_page_id');
    $this->page_access_token  = \Drupal::state()->get('simple_facebook_post.page_access_token');
    $this->user_acces_token   = \Drupal::state()->get('simple_facebook_post.user_acces_token');
    $this->permisos           = \Drupal::state()->get('simple_facebook_post.facebook_permisos', 'email');
    $this->version_api        = \Drupal::state()->get('simple_facebook_post.facebook_api_version', 'v13.0');
  }

  /**
   * Retorna los datos de la app registrada en facebook
   *
   * @return array 
   *    [ 
   *      'app_id'                => 'xxxx', 
   *      'app_secret'            => 'xxxx', 
   *      'default_graph_version' => 'vx.x'
   *    ]
   */
  protected function getAppAceessArray(){
    return [
      'app_id' => $this->app_id,
      'app_secret' => $this->app_secret,
      'default_graph_version' => $this->version_api
    ];
  }
  /**
   * Comprobar que se tiene acceso a la cuenta del usuario
   *
   * @return boolean|string
   */
  public function isUserLigin(){
    if(empty($this->user_acces_token)){
      return false;
    }

    $app_access = $this->getAppAceessArray();
    $app_access['default_access_token'] = $this->user_acces_token;
    $fb = new Face($app_access);

    try {
      $response = $fb->get('/me?fields=name');
    } catch(ResponseException $e) {
      $err = 'Graph returned an error: ' . $e->getMessage();
      \Drupal::logger('simple_facebook_post')->warning($err);
      return false;
    } catch(SDKException $e) {
      $err = 'Facebook SDK returned an error: ' . $e->getMessage();
      \Drupal::logger('simple_facebook_post')->warning($err);
      return false;
    }
    /** @var  \Facebook\GraphNode\GraphUser $user */
    $user = $response->getGraphUser(); // obtenemos un objeto graph tipo usuario
    $user_name = $user->getName();

    return $user_name;
  }
  /**
   * Obtenemos si es posible, el token de acceso de la pagina administrada por el usuario
   *
   * @param Face $fb 
   * @return null|string
   *  Null si no se tiene acceso o string si se encuentra el token
   */
  public function getAdministredPageToken(Face $fb){
    $config_page_id   = $this->page_id;
    $pageAccessToken  = null;

    $response = $fb->get('/me/accounts');
    foreach ($response->getDecodedBody() as $allPages) {
        foreach ($allPages as $page ) {
            if (isset($page['id']) && $page['id'] == $config_page_id) { 
                $pageAccessToken = (string) $page['access_token'];
                break;
            }
        }
    }
    return $pageAccessToken;
  }

  /**
   * Retorna si esta seteado la configuración necesaria para consumir la api de facebook
   * con nuestra app
   *
   * @return boolean
   */
  public function isAppAccessAvailable(){
    $options = $this->getAppAceessArray();
    if(empty($options['app_id']) || empty($options['app_secret'])){
      return false;
    }
    return true;
  }
  /**
   * Obtener una url de inicio de sesion para nuestra app
   * Cuando un usuario inicia con sis credenciales de Facebook se le pide aceptar los permisos requeridos por la app.
   * Cuando el login finaliza facebook envía a nuestro callback el tocken de acceso
   *
   * @param [type] $base_url
   * @return void
   */
  public function getLoginUrl($base_url = null){
    if(!$this->isAppAccessAvailable()) {
      return '';
    } 
    $base_url = $base_url ?? $GLOBALS['base_url'];
    $options = $this->getAppAceessArray();
    $fb = new Face($this->getAppAceessArray()); 
    $helper = $fb->getRedirectLoginHelper();
    $permissions = [ $this->permisos ];
    return $helper->getLoginUrl($base_url . '/facebook/fb-callback', $permissions);
  }
  
  /**
   * Metodo encargado del proceso de posteo en la pagina de facebook ingresada en la configuracion y 
   * administrada por el usuario del cual se dispone el token de acceso
   *
   * @param Post $post La noticia en si, los campos que se usan para armar el posteo y el link
   * @return void
   */
  public function postearFacil(Post $post){

    if(empty($this->user_acces_token)){
      return false;
    }
    $options = $this->getAppAceessArray();
    $options['default_access_token'] = $this->user_acces_token;
    $fb = new Face($options);

    $pageAccessToken = $this->page_access_token;
    if (empty($pageAccessToken)){
      $pageAccessToken = $this->getAdministredPageToken($fb);
    } 


    $strReq = '/' . $this->page_id . '/feed';
    try {
      // Returns a `FacebookFacebookResponse` object
      $response = $fb->post(
        $strReq, //la cadena consulta
        $post->geFBOptionsArray(), //le pasamos datos de los párametros
        $pageAccessToken // el token  acceso a la pagina
      );
    } catch(ResponseException $e) {
        $err = 'Graph returned an error: ' . $e->getMessage();
        \Drupal::logger('simple_facebook_post')->warning($err);
        return null;
    } catch(SDKException $e) {
        $err =  'Facebook SDK returned an error: ' . $e->getMessage();
        \Drupal::logger('simple_facebook_post')->warning($err);
        return null;
    }
    /** @var \Facebook\GraphNode\GraphNode $graphNode */
    $graphNode = $response->getGraphNode();

    return $graphNode->getField('id');
  }
  /**
   * Usado en el controlador de la url callback, obtiene la respuesta de facebook y guarda el token se la operacion fue correcta
   *
   * @param [type] $state parametro "estate" enviado por facebook a nuestra url callback
   * @return int
   *  0 -> sin confuiguracion
   *  1 -> Ok
   *  -1 -> Sin autorización
   *  -2 -> Bad Request
   */
  public function getAndSaveTokenFromCallback($state){
  
    if(!$this->isAppAccessAvailable()){
      \Drupal::logger('simple_facebook_post')->alert(t('<p>Se recibio una request a la url de callback para las respuestas de facebook pero el modulo no esta configurado</p>'));
      return 0;
    }

    $app_access = $this->getAppAceessArray();
    $fb         = new Face($app_access);
    $helper     = $fb->getRedirectLoginHelper();

    if (isset($state)) {
      $helper->getPersistentDataHandler()->set('state', $state);
    }
    try {
      $acces_token_temporal = $helper->getAccessToken();
    } catch(ResponseException $e) {
      // When Graph returns an error
      $err = 'Graph returned an error: ' . $e->getMessage();
      \Drupal::logger('simple_facebook_post')->warning($err);
      
    } catch(SDKException $e) {
      // When validation fails or other local issues
      $err = 'Facebook SDK returned an error: ' . $e->getMessage();
      \Drupal::logger('simple_facebook_post')->warning($err);
    }

    if (!isset($acces_token_temporal)) {
      if ($helper->getError()) {
    
        $err = "Error: " . $helper->getError() . "<br />";
        $err .= "Error Code: " . $helper->getErrorCode() . "<br />";
        $err .= "Error Reason: " . $helper->getErrorReason() . "<br />";
        $err .= "Error Description: " . $helper->getErrorDescription() . "<br />";
        \Drupal::logger('simple_facebook_post')->warning(t($err));

        return -1;
      } else {
        header('');
        \Drupal::logger('simple_facebook_post')->warning(t('400 Bad Request'));
        return -2;
      }
    }

    $oAuth2Client = $fb->getOAuth2Client();
    $tokenMetadata = $oAuth2Client->debugToken($acces_token_temporal);
    try {
      $tokenMetadata->validateAppId($this->app_id);
      $tokenMetadata->validateExpiration();

	  } catch (SDKException $e) {
      \Drupal::logger('simple_facebook_post')->warning(t('<p>Error validando el token'));
	    return -1;
    }
    // Guardamos el tocken de acceso
    \Drupal::state()->set('simple_facebook_post.user_acces_token',(string) $acces_token_temporal);
    
    return 1;
  }

}
