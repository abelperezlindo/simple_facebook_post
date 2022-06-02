<?php 

namespace Drupal\simple_facebook_post\Utils;

use \Drupal\node\NodeInterface;
use \Drupal\Component\Utility\Html;

use \Facebook\Facebook;
use \Facebook\Exception\ResponseException;
use \Facebook\Exception\SDKException;




class SocialPost
{
  protected $title;
  protected $title_suffix;
  protected $body;
  protected $image_url;
  protected $content_url;

  /**
   * 
   */
  public function __construct(NodeInterface $entity, $config = []){
    /**
     * @var \Drupal\simple_facebook_post\Config\ConfigManager $config_manager 
     */
    $config_manager = \Drupal::service('simple_facebook_post.config_manager');
    if(empty($config)){
      $config = $config_manager::getMultiple([
        'title',
        'body',
        'title_suffix',
        'image',
        'image_style',
        'body_use_summary',
      ]);
    }

    if($config['title'] && $entity->hasField($config['title'])){

      $title =  $entity->{$config['title']};
      $field_type = $title->getFieldDefinition()->getType();

      if(in_array($field_type, $config_manager::TITLE_ALLOWED_FIELDS_TYPE)){
        
        if(!empty($config['title_suffix'])){
          $this->setTitle($title->value,  $config['title_suffix']);
        }
        else {
          $this->setTitle($title->value);
        }
      }
    }

    if(!empty($config['body']) && $entity->hasField($config['body'])){

      $body =  $entity->{$config['body']};
      $field_type = $body->getFieldDefinition()->getType();
      if(in_array($field_type, $config_manager::BODY_ALLOWED_FIELDS_TYPE)){
        
        if(!empty($config['body_use_summary']) && !empty($body->summary)) {
          $this->setBody($body->summary);
        }
        else {
          $this->setBody($body->value);
        }
      }
    }

    if(!empty($config['image']) && $entity->hasField($config['image'])){
      $message[] = '<div class="post-preview-img" title="Post image">';

      /** @var \Drupal\file\Plugin\Field\FieldType\FileFieldItemList $ref_list */
      $ref_list = $entity->{$config['body']}->referencedEntities(); 
      if(isset($ref_list[0])){
        /** @var \Drupal\file\Entity\File $file */
        $file_uri   = $ref_list[0]->getFileUri();
        $image_uri  = $file_uri;

        if(!empty($config['image_style'])){
          $image_uri = \Drupal\image\Entity\ImageStyle::load($config['image_style'])
            ->buildUrl($file_uri);
        }

        if(\Drupal::hasService('file_url_generator')) {
          $generator = \Drupal::service('file_url_generator');
          $this->image_url  = $generator->generateAbsoluteString($image_uri);
        }
      } 
    }

    /**
     * @var \Drupal\Core\Url $url 
     */
    $node_url  = $entity->toUrl();
    $node_url->setAbsolute(TRUE);
    $this->content_url = $node_url->toString();
  }

  protected function setTitle($title, $suffix = ''){
    $this->title = Html::decodeEntities($title);
    $this->title_suffix = $suffix;
  }
  protected function setBody($body){
    $this->body = Html::decodeEntities($body);
  }
  protected function setImageUrl($image_url){
    $this->image_url = $image_url;
  }
  protected function setContentUrl($content_url){
    $this->content_url = $content_url;
  }

  public function getTitle(){
    if(!empty($this->title_suffix)){
      return $this->title . ' | ' . $this->title_suffix;
    }
    return $this->title;
  }
  public function getBody(){
    return $this->body;
  }
  public function getImageUrl(){
    return $this->image_url;
  }
  public function getContentUrl(){
    return $this->content_url;
  }

  public function publishOnFacebookPage(array $config = []){

    $config_manager = \Drupal::service('simple_facebook_post.config_manager');
    if(empty($config)){
      $config = $config_manager::getMultiple([
        'facebook_app_id',
        'facebook_app_secret',
        'facebook_page_id',
        'facebook_api_version',
        'facebook_permisos',
        'facebook_user_acces_token',
      ]);
    }
    if(empty($config['facebook_user_acces_token'])){
      return;
    }

    $facebook = new Facebook([      
      'app_id'                => $config['facebook_app_id'],
      'app_secret'            => $config['facebook_app_secret'],
      'default_graph_version' => $config['facebook_api_version'],
      'default_access_token'  => $config['facebook_user_acces_token'],
    ]);
    $pageAccessToken = $this->_getAdministredPageToken($facebook, $config['facebook_page_id']); 
    $endpoint = '/' . $this->page_id . '/feed';
/*
  'message' => $this->getMessage(),
      //'link'    => $this->getLink(), 
      'link' => 'https://www.analisisdigital.com.ar',
      //'name'    => $this->getName(),
      //'source'  => $this->getSource()
      'source' => 'https://www.analisisdigital.com.ar/sites/default/files/styles/noticias_front_celular/public/imagenNoticiaDigital/ve.jpg?itok=L9U016Z1&timestamp=1649343701'

 */
    try {
      // Returns a `FacebookFacebookResponse` object
      $response = $facebook->post(
        $endpoint, //la cadena consulta
        [
          'message' => $this->getBody(),        // The message written in the post
          'name' => $this->getTitle(),          // The name of the link.
          'link' => $this->getContentUrl(),     // A description of a link in the post (appears beneath the caption)
          'source' => $this->getImageUrl()      // A URL to any Flash movie or video file attached to the post.

          // 'caption' => 'text' // The caption of a link in the post (appears beneath the name)
        ],
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
   * Obtenemos si es posible, el token de acceso de la pagina administrada por el usuario
   *
   * @param Face $fb 
   * @return null|string
   *  Null si no se tiene acceso o string si se encuentra el token
   */
  protected function _getAdministredPageToken(Facebook $fb, $page_id){

    $pageAccessToken  = null;

    $response = $fb->get('/me/accounts');
    foreach ($response->getDecodedBody() as $allPages) {
        foreach ($allPages as $page ) {
            if (isset($page['id']) && $page['id'] == $$page_id) { 
                $pageAccessToken = (string) $page['access_token'];
                break;
            }
        }
    }
    return $pageAccessToken;
  }

}