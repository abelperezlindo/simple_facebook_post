<?php
namespace Drupal\simple_facebook_post\Config;

class ConfigManager{

  public const TITLE_ALLOWED_FIELDS_TYPE = ['string', 'text'];
  public const BODY_ALLOWED_FIELDS_TYPE  = ['string', 'text', 'text_with_summary', 'string_long'];
  public const IMAGE_ALLOWED_FIELDS_TYPE = ['image'];
  /**
   * Set a key in state
   */
  public static function set($key, $value){
    if(empty($key)){
      return;
    }

    \Drupal::state()->set('simple_facebook_post.' . $key, $value);
  }
   /**
   * get a variable from state
   */
  public static function get(string $key){
    if(empty($key)){
      return null;
    }

    return \Drupal::state()->get('simple_facebook_post.' . $key);
  }
  /**
   * get values of multiples variables from state
   */
  public static function getMultiple(array $keys){
    $result = $keys;
    foreach($result as $key => $value){
      $value = self::get($key);
    }
    return $result;
  }
  /**
   * get all state vars used by this module
   */
  public static function getAll(){
    $allKeys = [
      'content',
      'title',
      'title_suffix',
      'body',
      'media',
      'image_style',
      'preview_markup',
      'twitter_consumer_key',
      'twitter_consumer_secret',
      'twitter_access_token',
      'twitter_access_token_secret',
      'facebook_app_id',
      'facebook_app_secret',
      'facebook_page_id',
      'facebook_api_version',
      'facebook_permisos',
      'facebook_user_acces_token',
    ];
    return self::getMultiple($allKeys);
  }

  /** 
   * set values of multiples variables from state
   */
  public static function setMultiple(array $keys){
    
    foreach($keys as $key => $value){
      self::set($key, $value);
    }
  }
  
  public static function getFbUserAccessToken(){
    return \Drupal::state()->get('simple_facebook_post.facebook_user_acces_token', null);
  }
  public static function setFbUserAccessToken($token){
    return \Drupal::state()->set('simple_facebook_post.facebook_user_acces_token', $token);
  }

  public static function isFbConfigured(){

    $fb_config = self::getMultiple([
      'facebook_app_id',
      'facebook_app_secret',
      'facebook_page_id',
      'facebook_api_version',
      'facebook_permisos',
    ]);
    foreach($fb_config as $key => $value){
      if(empty($value)){
        return false;
      }
    }
    return true;
  }

  public static function getFieldsOptions(string $content_type = 'nothing'){

    $options = ['title' => [], 'body' => [], 'image' => []];
    if($content_type == 'nothing'){
      return $options;
    }

    /**
     * @var \Drupal\Core\Field\FieldDefinitionInterface[] $fields_def 
     */
    
    $fields_def =  \Drupal::service('entity_field.manager')
      ->getFieldDefinitions('node', $content_type );
    foreach($fields_def as $key => $value){
      if(in_array($value->getType(), self::TITLE_ALLOWED_FIELDS_TYPE)){
        $options['title'][$key] = $key;
      }

      if(in_array($value->getType(), self::BODY_ALLOWED_FIELDS_TYPE)){
        $options['title'][$key] = $key;
        $options['body'][$key] = $key;
      }
      if(in_array($value->getType(), self::IMAGE_ALLOWED_FIELDS_TYPE)){
        $options['image'][$key] = $key;
      }
    }
    return $options;
  }

  public static function getNodeTypesIds(){
    $types = [];
    $contentTypes = \Drupal::service('entity_type.manager')->getStorage('node_type')->loadMultiple();
    foreach ($contentTypes as $contentType) {
        $types[$contentType->id()] = $contentType->label();
    }
    return $types;
  }

  public static function getImageStylesOptions(){
    return \Drupal::entityQuery('image_style')->execute();
  }
  
}
